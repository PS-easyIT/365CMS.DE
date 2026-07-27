<?php
/**
 * SEO Indexing-Service für IndexNow und Google URL Notifications.
 *
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;
use CMS\Logger;
use CMS\VendorRegistry;
use Melbahja\Seo\Indexing\GoogleIndexer;
use Melbahja\Seo\Indexing\IndexNowEngine;
use Melbahja\Seo\Indexing\IndexNowIndexer;
use Melbahja\Seo\Indexing\URLIndexingType;

if (!defined('ABSPATH')) {
    exit;
}

VendorRegistry::instance()->loadPackage('melbahja-seo');

final class IndexingService
{
    private static ?self $instance = null;
    private const MAX_INDEXNOW_KEY_FILE_SIZE = 4096;

    /** Obergrenze für die je Zeitfenster an IndexNow gemeldeten URLs (Schutz vor Überlast bei sehr großen Zeiträumen). */
    private const MAX_RECENT_CONTENT_URLS_PER_TYPE = 500;

    /**
     * Zeitfenster für die Massen-Meldung kürzlich veröffentlichter Inhalte an IndexNow.
     *
     * @var array<string, array{interval:string, label:string}>
     */
    private const RECENT_CONTENT_RANGES = [
        '24h' => ['interval' => '-1 day', 'label' => 'Letzte 24 Stunden'],
        '48h' => ['interval' => '-2 days', 'label' => 'Letzte 48 Stunden'],
        '1w'  => ['interval' => '-1 week', 'label' => 'Letzte Woche'],
        '1m'  => ['interval' => '-1 month', 'label' => 'Letzter Monat'],
        '3m'  => ['interval' => '-3 months', 'label' => 'Letzte 3 Monate'],
        '6m'  => ['interval' => '-6 months', 'label' => 'Letzte 6 Monate'],
    ];

    /** Erlaubte Ziel-Dienste für die Massen-Meldung kürzlich veröffentlichter Inhalte. */
    private const RECENT_CONTENT_TARGETS = ['indexnow', 'google'];

    /** Settings-Gruppe/Key für das dauerhaft (verschlüsselt) gespeicherte Google-Access-Token. */
    private const GOOGLE_SETTINGS_GROUP = 'seo_indexing';
    private const GOOGLE_ACCESS_TOKEN_KEY = 'google_access_token';

    private Database $db;
    private SettingsService $settings;
    private Logger $logger;
    private string $prefix;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->db = Database::instance();
        $this->settings = SettingsService::getInstance();
        $this->logger = Logger::instance()->withChannel('seo.indexing');
        $this->prefix = $this->db->getPrefix();
    }

    /**
     * Sendet URLs an alle IndexNow-kompatiblen Endpunkte.
     */
    public function submitIndexNow(string|array $urls): bool
    {
        $urlList = $this->normalizeUrls($urls);
        if ($urlList === []) {
            $this->logger->warning('IndexNow-Submission ohne URLs verworfen.');
            return false;
        }

        $apiKey = $this->resolveIndexNowKey();
        if ($apiKey === '') {
            $this->logger->warning('IndexNow-Submission übersprungen: kein API-Key konfiguriert.');
            return false;
        }

        try {
            $indexer = new IndexNowIndexer($apiKey);
            $success = true;

            foreach (IndexNowEngine::cases() as $engine) {
                $results = $indexer->submitUrls($urlList, $engine, URLIndexingType::UPDATE);
                if (in_array(false, $results, true)) {
                    $success = false;
                    $this->logger->warning('IndexNow-Engine meldete Teilerfolg für {engine}.', [
                        'engine' => $engine->name,
                        'url_count' => count($urlList),
                    ]);
                }
            }

            return $success;
        } catch (\Throwable $e) {
            $this->logger->error('IndexNow-Submission fehlgeschlagen.', [
                'url_count' => count($urlList),
                'exception' => $e,
            ]);
            return false;
        }
    }

    /**
     * Sendet URLs an die Google Indexing API.
     * Ohne explizit übergebenes Token wird automatisch das gespeicherte Google-Access-Token verwendet.
     */
    public function submitGoogle(string|array $urls, string $accessToken = ''): bool
    {
        $urlList = $this->normalizeUrls($urls);
        $token = trim($accessToken) !== '' ? trim($accessToken) : $this->resolveGoogleAccessToken();

        if ($urlList === [] || $token === '') {
            $this->logger->warning('Google-Submission verworfen: Token oder URLs fehlen.', [
                'url_count' => count($urlList),
            ]);
            return false;
        }

        try {
            $indexer = new GoogleIndexer($token);
            $results = $indexer->submitUrls($urlList, URLIndexingType::UPDATE);
            return !in_array(false, $results, true);
        } catch (\Throwable $e) {
            $this->logger->error('Google-Submission fehlgeschlagen.', [
                'url_count' => count($urlList),
                'exception' => $e,
            ]);
            return false;
        }
    }

    /**
     * Entfernt eine URL via Google Indexing API aus dem Index.
     * Ohne explizit übergebenes Token wird automatisch das gespeicherte Google-Access-Token verwendet.
     */
    public function deleteGoogle(string $url, string $accessToken = ''): bool
    {
        $normalizedUrl = trim($url);
        $token = trim($accessToken) !== '' ? trim($accessToken) : $this->resolveGoogleAccessToken();

        if ($normalizedUrl === '' || $token === '') {
            $this->logger->warning('Google-Delete verworfen: URL oder Token fehlen.');
            return false;
        }

        try {
            $indexer = new GoogleIndexer($token);
            return $indexer->submitUrl($normalizedUrl, URLIndexingType::DELETE);
        } catch (\Throwable $e) {
            $this->logger->error('Google-Delete fehlgeschlagen.', [
                'exception' => $e,
            ]);
            return false;
        }
    }

    public function hasIndexNowKey(): bool
    {
        return $this->resolveIndexNowKey() !== '';
    }

    public function getIndexNowKey(): string
    {
        return $this->resolveIndexNowKey();
    }

    /**
     * Prüft, ob dauerhaft ein Google-Access-Token hinterlegt ist.
     */
    public function hasGoogleAccessToken(): bool
    {
        return $this->resolveGoogleAccessToken() !== '';
    }

    /**
     * Speichert das Google-Access-Token verschlüsselt und dauerhaft in der Datenbank.
     */
    public function saveGoogleAccessToken(string $accessToken): bool
    {
        $token = trim($accessToken);
        if ($token === '') {
            return $this->clearGoogleAccessToken();
        }

        return $this->settings->set(self::GOOGLE_SETTINGS_GROUP, self::GOOGLE_ACCESS_TOKEN_KEY, $token, true, 0);
    }

    /**
     * Entfernt ein zuvor gespeichertes Google-Access-Token.
     */
    public function clearGoogleAccessToken(): bool
    {
        return $this->settings->forget(self::GOOGLE_SETTINGS_GROUP, self::GOOGLE_ACCESS_TOKEN_KEY);
    }

    private function resolveGoogleAccessToken(): string
    {
        return $this->settings->getString(self::GOOGLE_SETTINGS_GROUP, self::GOOGLE_ACCESS_TOKEN_KEY, '');
    }

    /**
     * Verfügbare Zeitfenster für die Massen-Meldung kürzlich veröffentlichter Inhalte.
     *
     * @return array<string, string>
     */
    public function getRecentContentRangeOptions(): array
    {
        $labels = [];
        foreach (self::RECENT_CONTENT_RANGES as $range => $definition) {
            $labels[$range] = (string) $definition['label'];
        }

        return $labels;
    }

    /**
     * Verfügbare Ziel-Dienste für die Massen-Meldung kürzlich veröffentlichter Inhalte.
     *
     * @return list<string>
     */
    public function getRecentContentTargetOptions(): array
    {
        return self::RECENT_CONTENT_TARGETS;
    }

    /**
     * Ermittelt die öffentlichen URLs aller veröffentlichten Seiten/Beiträge im gewählten Zeitfenster.
     *
     * @return list<string>
     */
    public function getRecentContentUrls(string $range): array
    {
        $interval = self::RECENT_CONTENT_RANGES[$range]['interval'] ?? null;
        if ($interval === null) {
            return [];
        }

        $cutoffTimestamp = strtotime($interval);
        if ($cutoffTimestamp === false) {
            return [];
        }

        $cutoff = date('Y-m-d H:i:s', $cutoffTimestamp);
        $urls = [];

        try {
            $pageRows = $this->db->get_results(
                "SELECT slug
                 FROM {$this->prefix}pages
                 WHERE status = 'published' AND updated_at >= ?
                 ORDER BY updated_at DESC
                 LIMIT " . self::MAX_RECENT_CONTENT_URLS_PER_TYPE,
                [$cutoff]
            ) ?: [];

            foreach ($pageRows as $row) {
                $slug = trim((string) ($row->slug ?? ''));
                if ($slug === '') {
                    continue;
                }

                $urls[] = $this->buildPagePathUrl($slug);
            }

            $postRows = $this->db->get_results(
                "SELECT slug, published_at, created_at
                 FROM {$this->prefix}posts
                 WHERE " . \cms_post_publication_where() . "
                   AND COALESCE(published_at, created_at) >= ?
                 ORDER BY COALESCE(published_at, created_at) DESC
                 LIMIT " . self::MAX_RECENT_CONTENT_URLS_PER_TYPE,
                [$cutoff]
            ) ?: [];

            foreach ($postRows as $row) {
                $slug = trim((string) ($row->slug ?? ''));
                if ($slug === '') {
                    continue;
                }

                $urls[] = PermalinkService::getInstance()->buildPostUrlFromValues(
                    $slug,
                    (string) ($row->published_at ?? ''),
                    (string) ($row->created_at ?? '')
                );
            }
        } catch (\Throwable $e) {
            $this->logger->error('IndexNow: Ermittlung kürzlich veröffentlichter Inhalte fehlgeschlagen.', [
                'range' => $range,
                'exception' => $e,
            ]);

            return [];
        }

        return array_values(array_unique($urls));
    }

    /**
     * Meldet alle veröffentlichten Seiten/Beiträge des gewählten Zeitfensters an die gewählten Ziel-Dienste
     * (IndexNow und/oder Google). Für Google wird automatisch das dauerhaft gespeicherte Access-Token verwendet.
     *
     * @param list<string> $targets Erlaubt: 'indexnow', 'google'
     * @return array{success:bool, count:int, error:string, message:string}
     */
    public function submitRecentContent(string $range, array $targets = ['indexnow']): array
    {
        if (!array_key_exists($range, self::RECENT_CONTENT_RANGES)) {
            return ['success' => false, 'count' => 0, 'error' => 'Ungültiger Zeitraum ausgewählt.', 'message' => ''];
        }

        $targets = array_values(array_unique(array_filter(
            $targets,
            static fn(mixed $target): bool => in_array($target, self::RECENT_CONTENT_TARGETS, true)
        )));

        if ($targets === []) {
            return ['success' => false, 'count' => 0, 'error' => 'Bitte mindestens ein Ziel für die Meldung auswählen.', 'message' => ''];
        }

        $wantsIndexNow = in_array('indexnow', $targets, true);
        $wantsGoogle = in_array('google', $targets, true);

        if ($wantsIndexNow && !$this->hasIndexNowKey()) {
            return ['success' => false, 'count' => 0, 'error' => 'IndexNow ist nicht aktiv: Es ist kein API-Key konfiguriert.', 'message' => ''];
        }

        if ($wantsGoogle && !$this->hasGoogleAccessToken()) {
            return ['success' => false, 'count' => 0, 'error' => 'Google-Submission ist nicht aktiv: Es ist kein Access-Token hinterlegt.', 'message' => ''];
        }

        $urls = $this->getRecentContentUrls($range);
        $label = self::RECENT_CONTENT_RANGES[$range]['label'] ?? $range;

        if ($urls === []) {
            return ['success' => false, 'count' => 0, 'error' => 'Für „' . $label . '“ wurden keine veröffentlichten Seiten oder Beiträge gefunden.', 'message' => ''];
        }

        $messages = [];
        $errors = [];

        if ($wantsIndexNow) {
            if ($this->submitIndexNow($urls)) {
                $messages[] = 'IndexNow: ' . count($urls) . ' URL(s) aus „' . $label . '“ gemeldet.';
            } else {
                $errors[] = 'IndexNow konnte die URLs nicht vollständig entgegennehmen.';
            }
        }

        if ($wantsGoogle) {
            if ($this->submitGoogle($urls)) {
                $messages[] = 'Google: ' . count($urls) . ' URL(s) aus „' . $label . '“ gemeldet.';
            } else {
                $errors[] = 'Google konnte die URLs nicht verarbeiten (Access-Token evtl. abgelaufen).';
            }
        }

        return [
            'success' => $messages !== [] && $errors === [],
            'count' => count($urls),
            'error' => implode(' ', $errors),
            'message' => implode(' ', $messages),
        ];
    }

    private function buildPagePathUrl(string $slug): string
    {
        $slug = trim($slug);
        if ($slug === '' || $slug === '/') {
            return rtrim((string) SITE_URL, '/') . '/';
        }

        return rtrim((string) SITE_URL, '/') . '/' . ltrim($slug, '/');
    }

    /**
     * @return list<string>
     */
    public function getIndexNowRootTxtFiles(): array
    {
        $result = [];
        foreach ($this->getIndexNowRootDirectories() as $directory) {
            $files = glob($directory . DIRECTORY_SEPARATOR . '*.txt');
            if (!is_array($files) || $files === []) {
                continue;
            }

            foreach ($files as $file) {
                $basename = basename((string) $file);
                if ($basename === '' || !is_file($file)) {
                    continue;
                }

                $result[] = $basename;
            }
        }

        natcasesort($result);

        return array_values(array_unique($result));
    }

    /**
     * @return array{
     *     key:string,
     *     key_available:bool,
     *     dynamic_key_file_active:bool,
     *     dynamic_key_file_url:string,
     *     selected_root_file:string,
     *     selected_root_file_url:string,
     *     selected_root_file_exists:bool,
     *     selected_root_file_path:string,
     *     selected_root_file_matches_key:bool,
     *     selected_root_file_content_matches_key:bool,
     *     selected_root_file_valid:bool,
     *     ready_for_submission:bool,
     *     root_directory:string,
     *     root_txt_files:list<string>,
     *     validation_errors:list<string>,
     *     validation_notes:list<string>,
     *     debug:array{
     *         root_candidates:list<array{
     *             source:string,
     *             original_path:string,
     *             normalized_path:string,
     *             usable:bool,
     *             reason:string,
     *             txt_files:list<string>,
     *             selected_file_path:string,
     *             selected_file_exists:bool
     *         }>,
     *         selected_file_reason:string,
     *         selected_file_resolved_from:string
     *     }
     * }
     */
    public function getIndexNowConfigurationStatus(): array
    {
        $key = $this->resolveIndexNowKey();
        $selectedFile = $this->resolveIndexNowSelectedRootFile();
        $publicBaseUrl = $this->resolvePublicBaseUrl();
        $rootDebugCandidates = $this->getIndexNowRootDebugCandidates($selectedFile);
        $rootDirectories = array_values(array_map(
            static fn(array $candidate): string => (string) $candidate['normalized_path'],
            array_values(array_filter(
                $rootDebugCandidates,
                static fn(array $candidate): bool => !empty($candidate['usable']) && (string) ($candidate['normalized_path'] ?? '') !== ''
            ))
        ));
        $rootTxtFiles = $this->getIndexNowRootTxtFiles();
        $rootDirectory = implode(' | ', $rootDirectories);
        $dynamicKeyFileUrl = $key !== ''
            ? $publicBaseUrl . '/' . rawurlencode($key) . '.txt'
            : '';

        $selectedFileExists = false;
        $selectedFileMatchesKey = false;
        $selectedFileContentMatchesKey = false;
        $selectedFileValid = false;
        $selectedFileUrl = '';
        $selectedFilePath = '';
        $validationErrors = [];
        $validationNotes = [];
        $selectedFileReason = $selectedFile === ''
            ? 'Keine physische Root-TXT-Datei ausgewählt.'
            : 'Die ausgewählte Root-TXT-Datei wurde noch nicht geprüft.';
        $selectedFileResolvedFrom = '';

        if ($key === '') {
            $validationNotes[] = 'Kein IndexNow-API-Key gespeichert.';
        } else {
            $validationNotes[] = 'Die dynamische Keydatei ist unter `' . $dynamicKeyFileUrl . '` verfügbar.';
        }

        if ($selectedFile !== '') {
            $selectedFileUrl = $publicBaseUrl . '/' . rawurlencode($selectedFile);
            $selectedFilePath = $this->findIndexNowRootFilePath($selectedFile);
            $selectedFileExists = $selectedFilePath !== null && in_array($selectedFile, $rootTxtFiles, true);
            $selectedFilePath = $selectedFilePath ?? '';

            foreach ($rootDebugCandidates as $candidate) {
                if (!empty($candidate['selected_file_exists'])) {
                    $selectedFileResolvedFrom = (string) ($candidate['normalized_path'] ?? '');
                    break;
                }
            }

            if (!$selectedFileExists) {
                $validationErrors[] = 'Die ausgewählte Root-TXT-Datei wurde nicht gefunden.';
                $selectedFileReason = 'Die Datei wurde in keinem der geprüften Root-Pfade gefunden.';
            } else {
                $expectedFileName = $key !== '' ? $key . '.txt' : '';
                $selectedFileMatchesKey = $key !== '' && $selectedFile === $expectedFileName;
                $selectedFileReason = 'Die Datei wurde gefunden und wird jetzt gegen Name und Inhalt geprüft.';

                $selectedFileCanBeRead = true;
                if (!is_readable($selectedFilePath)) {
                    $validationErrors[] = 'Die ausgewählte Root-TXT-Datei ist nicht lesbar.';
                    $selectedFileCanBeRead = false;
                    $selectedFileReason = 'Die Datei existiert, ist aber nicht lesbar.';
                } else {
                    $fileSize = filesize($selectedFilePath);
                    if ($fileSize === false) {
                        $validationErrors[] = 'Die Größe der ausgewählten Root-TXT-Datei konnte nicht ermittelt werden.';
                        $selectedFileCanBeRead = false;
                        $selectedFileReason = 'Die Datei existiert, aber ihre Größe konnte nicht ermittelt werden.';
                    } elseif ((int) $fileSize > self::MAX_INDEXNOW_KEY_FILE_SIZE) {
                        $validationErrors[] = 'Die ausgewählte Root-TXT-Datei ist für eine IndexNow-Keydatei ungewöhnlich groß.';
                        $selectedFileCanBeRead = false;
                        $selectedFileReason = 'Die Datei ist größer als für eine IndexNow-Keydatei erwartet.';
                    }
                }

                $selectedContent = '';
                if ($selectedFileCanBeRead) {
                    $selectedContentRaw = $this->readSafeIndexNowRootFile($selectedFilePath);
                    if ($selectedContentRaw === false) {
                        $validationErrors[] = 'Die ausgewählte Root-TXT-Datei konnte nicht gelesen werden.';
                        $selectedFileReason = 'Die Datei wurde gefunden, konnte aber nicht ausgelesen werden.';
                    } else {
                        $selectedContent = trim($selectedContentRaw);
                    }
                }

                $selectedFileContentMatchesKey = $key !== '' && $selectedContent === $key;
                $selectedFileValid = $selectedFileMatchesKey && $selectedFileContentMatchesKey;

                if (!$selectedFileMatchesKey && $key !== '') {
                    $validationErrors[] = 'Der Dateiname der ausgewählten TXT-Datei entspricht nicht dem API-Key.';
                    $selectedFileReason = 'Der Dateiname passt nicht exakt zum gespeicherten API-Key.';
                }

                if (!$selectedFileContentMatchesKey && $key !== '') {
                    $validationErrors[] = 'Der Inhalt der ausgewählten TXT-Datei entspricht nicht dem API-Key.';
                    $selectedFileReason = 'Der Dateiinhalt passt nicht exakt zum gespeicherten API-Key.';
                }

                if ($selectedFileValid) {
                    $validationNotes[] = 'Die ausgewählte Root-TXT-Datei wurde erfolgreich gegen den API-Key geprüft.';
                    $selectedFileReason = 'Die Datei ist gültig: Dateiname und Inhalt entsprechen dem API-Key.';
                }
            }
        } else {
            $validationNotes[] = 'Es ist aktuell keine physische Root-TXT-Datei ausgewählt.';
        }

        return [
            'key' => $key,
            'key_available' => $key !== '',
            'dynamic_key_file_active' => $key !== '',
            'dynamic_key_file_url' => $dynamicKeyFileUrl,
            'selected_root_file' => $selectedFile,
            'selected_root_file_url' => $selectedFileUrl,
            'selected_root_file_exists' => $selectedFileExists,
            'selected_root_file_path' => $selectedFilePath,
            'selected_root_file_matches_key' => $selectedFileMatchesKey,
            'selected_root_file_content_matches_key' => $selectedFileContentMatchesKey,
            'selected_root_file_valid' => $selectedFileValid,
            'ready_for_submission' => $key !== '' && ($selectedFile === '' || $selectedFileValid),
            'root_directory' => $rootDirectory,
            'root_txt_files' => $rootTxtFiles,
            'validation_errors' => $validationErrors,
            'validation_notes' => $validationNotes,
            'debug' => [
                'root_candidates' => $rootDebugCandidates,
                'selected_file_reason' => $selectedFileReason,
                'selected_file_resolved_from' => $selectedFileResolvedFrom,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function getIndexNowRootDirectories(): array
    {
        $directories = [];
        foreach ($this->getIndexNowRootDebugCandidates('') as $candidate) {
            if (empty($candidate['usable'])) {
                continue;
            }

            $normalizedPath = (string) ($candidate['normalized_path'] ?? '');
            if ($normalizedPath === '') {
                continue;
            }

            $directories[] = $normalizedPath;
        }

        return array_values(array_unique($directories));
    }

    private function findIndexNowRootFilePath(string $selectedFile): ?string
    {
        foreach ($this->getIndexNowRootDirectories() as $directory) {
            $path = $directory . DIRECTORY_SEPARATOR . $selectedFile;
            if ($this->isSafeIndexNowRootFilePath($path, $directory)) {
                return $path;
            }
        }

        return null;
    }

    private function normalizeDirectoryPath(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return '';
        }

        $realPath = realpath($trimmed);
        if ($realPath !== false) {
            return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $realPath), DIRECTORY_SEPARATOR);
        }

        return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $trimmed), DIRECTORY_SEPARATOR);
    }

    /**
     * @return list<array{
     *     source:string,
     *     original_path:string,
     *     normalized_path:string,
     *     usable:bool,
     *     reason:string,
     *     txt_files:list<string>,
     *     selected_file_path:string,
     *     selected_file_exists:bool
     * }>
     */
    private function getIndexNowRootDebugCandidates(string $selectedFile): array
    {
        $candidates = $this->getIndexNowRootDirectoryCandidates();
        $result = [];
        $seen = [];

        foreach ($candidates as $candidate) {
            $originalPath = (string) ($candidate['path'] ?? '');
            $normalizedPath = $this->normalizeDirectoryPath($originalPath);
            $usable = true;
            $reason = 'Pfad wird geprüft.';
            $txtFiles = [];
            $selectedFilePath = '';
            $selectedFileExists = false;

            if ($normalizedPath === '') {
                $usable = false;
                $reason = 'Pfad ist leer und kann nicht geprüft werden.';
            } elseif (!is_dir($normalizedPath)) {
                $usable = false;
                $reason = 'Pfad existiert nicht als Verzeichnis.';
            } elseif (!is_readable($normalizedPath)) {
                $usable = false;
                $reason = 'Pfad ist nicht lesbar.';
            } else {
                $files = glob($normalizedPath . DIRECTORY_SEPARATOR . '*.txt');
                if (is_array($files) && $files !== []) {
                    foreach ($files as $file) {
                        if (!is_file($file)) {
                            continue;
                        }

                        $txtFiles[] = basename((string) $file);
                    }
                }

                sort($txtFiles, SORT_NATURAL | SORT_FLAG_CASE);

                if ($selectedFile !== '') {
                    $selectedCandidatePath = $normalizedPath . DIRECTORY_SEPARATOR . $selectedFile;
                    if (is_file($selectedCandidatePath)) {
                        $selectedFileExists = true;
                        $selectedFilePath = $selectedCandidatePath;
                    }
                }

                $reason = $txtFiles === []
                    ? 'Pfad geprüft, aber keine .txt-Dateien gefunden.'
                    : 'Pfad geprüft, .txt-Dateien gefunden: ' . count($txtFiles);
            }

            $uniqueKey = ($candidate['source'] ?? 'unknown') . '|' . $normalizedPath;
            if (isset($seen[$uniqueKey])) {
                continue;
            }
            $seen[$uniqueKey] = true;

            $result[] = [
                'source' => (string) ($candidate['source'] ?? 'unbekannt'),
                'original_path' => $originalPath,
                'normalized_path' => $normalizedPath,
                'usable' => $usable,
                'reason' => $reason,
                'txt_files' => $txtFiles,
                'selected_file_path' => $selectedFilePath,
                'selected_file_exists' => $selectedFileExists,
            ];
        }

        return $result;
    }

    /**
     * @return list<array{source:string, path:string}>
     */
    private function getIndexNowRootDirectoryCandidates(): array
    {
        $candidates = [
            [
                'source' => 'dirname(ABSPATH)',
                'path' => dirname(rtrim((string) ABSPATH, DIRECTORY_SEPARATOR)),
            ],
            [
                'source' => 'ABSPATH',
                'path' => rtrim((string) ABSPATH, DIRECTORY_SEPARATOR),
            ],
        ];

        $documentRoot = trim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
        if ($documentRoot !== '') {
            $candidates[] = [
                'source' => '$_SERVER[DOCUMENT_ROOT]',
                'path' => $documentRoot,
            ];
        }

        $scriptFilename = trim((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
        if ($scriptFilename !== '') {
            $candidates[] = [
                'source' => 'dirname($_SERVER[SCRIPT_FILENAME])',
                'path' => dirname($scriptFilename),
            ];
        }

        return $candidates;
    }

    private function resolveIndexNowKey(): string
    {
        try {
            if (function_exists('config')) {
                $value = config('seo.indexnow_key');
                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }
            }
        } catch (\Throwable) {
            // Fallbacks unten greifen.
        }

        $candidates = [
            $this->getDirectSettingValue('seo_indexnow_key'),
            $this->getDirectSettingValue('seo.indexnow_key'),
            $this->settings->getString('seo', 'indexnow_key', ''),
            defined('SEO_INDEXNOW_KEY') ? (string) SEO_INDEXNOW_KEY : '',
            function_exists('get_option') ? (string) get_option('seo.indexnow_key', '') : '',
            function_exists('get_option') ? (string) get_option('seo_indexnow_key', '') : '',
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    private function resolveIndexNowSelectedRootFile(): string
    {
        $candidates = [
            $this->getDirectSettingValue('seo_indexnow_key_file'),
            $this->getDirectSettingValue('seo.indexnow_key_file'),
            $this->settings->getString('seo', 'indexnow_key_file', ''),
            function_exists('get_option') ? (string) get_option('seo.indexnow_key_file', '') : '',
            function_exists('get_option') ? (string) get_option('seo_indexnow_key_file', '') : '',
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '' || preg_match('/^[A-Za-z0-9._-]+\.txt$/', $candidate) !== 1) {
                continue;
            }

            return $candidate;
        }

        return '';
    }

    private function getDirectSettingValue(string $optionName): string
    {
        try {
            $value = $this->db->get_var(
                "SELECT option_value FROM {$this->prefix}settings WHERE option_name = ? LIMIT 1",
                [$optionName]
            );

            return $value !== null ? trim((string) $value) : '';
        } catch (\Throwable) {
            return '';
        }
    }

    private function resolvePublicBaseUrl(): string
    {
        if (function_exists('cms_runtime_base_url')) {
            try {
                $runtimeBaseUrl = rtrim((string) cms_runtime_base_url(), '/');
                if ($runtimeBaseUrl !== '') {
                    return $runtimeBaseUrl;
                }
            } catch (\Throwable) {
                // Fallback auf SITE_URL, wenn die Runtime-Basis nicht verfügbar ist.
            }
        }

        return rtrim((string) SITE_URL, '/');
    }

    /**
     * @return array<int, string>
     */
    private function normalizeUrls(string|array $urls): array
    {
        $list = is_array($urls)
            ? $urls
            : (preg_split('/\r\n|\r|\n|,/', $urls) ?: []);
        $normalized = [];

        foreach ($list as $url) {
            $url = trim((string) $url);
            if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
                continue;
            }
            $normalized[] = $url;
        }

        return array_values(array_unique($normalized));
    }

    private function isSafeIndexNowRootFilePath(string $path, string $rootDirectory): bool
    {
        $realRoot = realpath($rootDirectory);
        $realPath = realpath($path);
        if ($realRoot === false || $realPath === false || !is_file($realPath) || !is_readable($realPath)) {
            return false;
        }

        $normalizedRoot = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $realRoot), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $realPath);

        return str_starts_with($normalizedPath, $normalizedRoot)
            && strtolower((string) pathinfo($normalizedPath, PATHINFO_EXTENSION)) === 'txt';
    }

    private function readSafeIndexNowRootFile(string $selectedFilePath): string|false
    {
        foreach ($this->getIndexNowRootDirectories() as $rootDirectory) {
            if (!$this->isSafeIndexNowRootFilePath($selectedFilePath, $rootDirectory)) {
                continue;
            }

            $realPath = realpath($selectedFilePath);
            if ($realPath === false) {
                return false;
            }

            $size = filesize($realPath);
            if ($size === false || $size < 0 || $size > self::MAX_INDEXNOW_KEY_FILE_SIZE) {
                return false;
            }

            return file_get_contents($realPath);
        }

        return false;
    }
}