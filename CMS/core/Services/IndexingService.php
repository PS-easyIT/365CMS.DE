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
     */
    public function submitGoogle(string|array $urls, string $accessToken): bool
    {
        $urlList = $this->normalizeUrls($urls);
        $token = trim($accessToken);

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
     */
    public function deleteGoogle(string $url, string $accessToken): bool
    {
        $normalizedUrl = trim($url);
        $token = trim($accessToken);

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
     *     selected_root_file_matches_key:bool,
     *     selected_root_file_content_matches_key:bool,
     *     selected_root_file_valid:bool,
     *     ready_for_submission:bool,
     *     root_directory:string,
     *     root_txt_files:list<string>,
     *     validation_errors:list<string>,
     *     validation_notes:list<string>
     * }
     */
    public function getIndexNowConfigurationStatus(): array
    {
        $key = $this->resolveIndexNowKey();
        $selectedFile = $this->resolveIndexNowSelectedRootFile();
        $rootDirectories = $this->getIndexNowRootDirectories();
        $rootTxtFiles = $this->getIndexNowRootTxtFiles();
        $rootDirectory = implode(' | ', $rootDirectories);
        $dynamicKeyFileUrl = $key !== ''
            ? rtrim((string) SITE_URL, '/') . '/' . rawurlencode($key) . '.txt'
            : '';

        $selectedFileExists = false;
        $selectedFileMatchesKey = false;
        $selectedFileContentMatchesKey = false;
        $selectedFileValid = false;
        $selectedFileUrl = '';
        $validationErrors = [];
        $validationNotes = [];

        if ($key === '') {
            $validationNotes[] = 'Kein IndexNow-API-Key gespeichert.';
        } else {
            $validationNotes[] = 'Die dynamische Keydatei ist unter `' . $dynamicKeyFileUrl . '` verfügbar.';
        }

        if ($selectedFile !== '') {
            $selectedFileUrl = rtrim((string) SITE_URL, '/') . '/' . rawurlencode($selectedFile);
            $selectedFilePath = $this->findIndexNowRootFilePath($selectedFile);
            $selectedFileExists = $selectedFilePath !== null && in_array($selectedFile, $rootTxtFiles, true);

            if (!$selectedFileExists) {
                $validationErrors[] = 'Die ausgewählte Root-TXT-Datei wurde nicht gefunden.';
            } else {
                $expectedFileName = $key !== '' ? $key . '.txt' : '';
                $selectedFileMatchesKey = $key !== '' && $selectedFile === $expectedFileName;

                $selectedFileCanBeRead = true;
                if (!is_readable($selectedFilePath)) {
                    $validationErrors[] = 'Die ausgewählte Root-TXT-Datei ist nicht lesbar.';
                    $selectedFileCanBeRead = false;
                } else {
                    $fileSize = filesize($selectedFilePath);
                    if ($fileSize === false) {
                        $validationErrors[] = 'Die Größe der ausgewählten Root-TXT-Datei konnte nicht ermittelt werden.';
                        $selectedFileCanBeRead = false;
                    } elseif ((int) $fileSize > self::MAX_INDEXNOW_KEY_FILE_SIZE) {
                        $validationErrors[] = 'Die ausgewählte Root-TXT-Datei ist für eine IndexNow-Keydatei ungewöhnlich groß.';
                        $selectedFileCanBeRead = false;
                    }
                }

                $selectedContent = '';
                if ($selectedFileCanBeRead) {
                    $selectedContentRaw = file_get_contents($selectedFilePath);
                    if ($selectedContentRaw === false) {
                        $validationErrors[] = 'Die ausgewählte Root-TXT-Datei konnte nicht gelesen werden.';
                    } else {
                        $selectedContent = trim($selectedContentRaw);
                    }
                }

                $selectedFileContentMatchesKey = $key !== '' && $selectedContent === $key;
                $selectedFileValid = $selectedFileMatchesKey && $selectedFileContentMatchesKey;

                if (!$selectedFileMatchesKey && $key !== '') {
                    $validationErrors[] = 'Der Dateiname der ausgewählten TXT-Datei entspricht nicht dem API-Key.';
                }

                if (!$selectedFileContentMatchesKey && $key !== '') {
                    $validationErrors[] = 'Der Inhalt der ausgewählten TXT-Datei entspricht nicht dem API-Key.';
                }

                if ($selectedFileValid) {
                    $validationNotes[] = 'Die ausgewählte Root-TXT-Datei wurde erfolgreich gegen den API-Key geprüft.';
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
            'selected_root_file_matches_key' => $selectedFileMatchesKey,
            'selected_root_file_content_matches_key' => $selectedFileContentMatchesKey,
            'selected_root_file_valid' => $selectedFileValid,
            'ready_for_submission' => $key !== '' && ($selectedFile === '' || $selectedFileValid),
            'root_directory' => $rootDirectory,
            'root_txt_files' => $rootTxtFiles,
            'validation_errors' => $validationErrors,
            'validation_notes' => $validationNotes,
        ];
    }

    /**
     * @return list<string>
     */
    private function getIndexNowRootDirectories(): array
    {
        $candidates = [
            dirname(rtrim((string) ABSPATH, DIRECTORY_SEPARATOR)),
            rtrim((string) ABSPATH, DIRECTORY_SEPARATOR),
        ];

        $documentRoot = trim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
        if ($documentRoot !== '') {
            $candidates[] = $documentRoot;
        }

        $scriptFilename = trim((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
        if ($scriptFilename !== '') {
            $candidates[] = dirname($scriptFilename);
        }

        $normalized = [];
        foreach ($candidates as $candidate) {
            $resolved = $this->normalizeDirectoryPath($candidate);
            if ($resolved === '' || !is_dir($resolved)) {
                continue;
            }

            $normalized[] = $resolved;
        }

        return array_values(array_unique($normalized));
    }

    private function findIndexNowRootFilePath(string $selectedFile): ?string
    {
        foreach ($this->getIndexNowRootDirectories() as $directory) {
            $path = $directory . DIRECTORY_SEPARATOR . $selectedFile;
            if (is_file($path)) {
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
}