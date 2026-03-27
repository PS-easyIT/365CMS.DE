<?php
declare(strict_types=1);

/**
 * Theme Marketplace Module – Remote-Theme-Katalog
 *
 * Liest index.json aus dem Theme-Repository um verfügbare Themes anzuzeigen
 * und abzugleichen welche bereits lokal installiert sind.
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Http\Client as HttpClient;
use CMS\ThemeManager;
use CMS\Database;
use CMS\Services\UpdateService;

class ThemeMarketplaceModule
{
    private const MAX_MANIFEST_BYTES = 524288;
    private const MAX_ARCHIVE_ENTRIES = 2000;
    private const MAX_ARCHIVE_UNCOMPRESSED_BYTES = 52428800;
    private const MAX_CATALOG_STRING_LENGTH = 500;
    private const MANIFEST_ALLOWED_KEYS = [
        'slug',
        'name',
        'description',
        'version',
        'author',
        'manifest',
        'update_url',
        'download_url',
        'package_url',
        'archive_url',
        'purchase_url',
        'buy_url',
        'order_url',
        'is_paid',
        'price_amount',
        'price_currency',
        'price',
        'sha256',
        'checksum_sha256',
        'homepage_url',
        'docs_url',
        'changelog_url',
        'screenshot',
        'requires_cms',
        'min_cms_version',
        'requires_php',
        'min_php',
        'tested_up_to',
        'released',
        'notes',
        'type',
    ];

    private const ALLOWED_MARKETPLACE_HOSTS = [
        '365cms.de',
        'www.365cms.de',
        '365network.de',
        'www.365network.de',
        'api.github.com',
        'codeload.github.com',
        'github.com',
        'objects.githubusercontent.com',
        'raw.githubusercontent.com',
    ];

    private const DEFAULT_MARKETPLACE_URL = 'https://365cms.de/marketplace/themes';

    private ThemeManager $themeManager;
    private HttpClient $httpClient;
    private UpdateService $updateService;
    /** @var array<int, array<string, mixed>>|null */
    private ?array $catalogCache = null;
    /** @var array<string, string> */
    private array $catalogSource = [
        'type' => 'none',
        'status' => 'warning',
        'message' => 'Noch keine Marketplace-Quelle ausgewertet.',
    ];

    public function __construct()
    {
        $this->themeManager = ThemeManager::instance();
        $this->httpClient = HttpClient::getInstance();
        $this->updateService = UpdateService::getInstance();
    }

    /**
     * Marketplace-Daten laden
     */
    public function getData(): array
    {
        $installed    = $this->themeManager->getAvailableThemes();
        $installedMap = $this->getInstalledThemeMap($installed);
        $catalog      = $this->getCatalog();
        $activeTheme  = $this->normalizeThemeKey($this->themeManager->getActiveThemeSlug());

        // Installationsstatus anreichern
        foreach ($catalog as &$theme) {
            $slugKey = $this->normalizeThemeKey((string) ($theme['slug'] ?? ''));
            $localTheme = $installedMap[$slugKey] ?? null;

            $theme['installed'] = $localTheme !== null;
            $theme['active']    = $slugKey !== '' && $slugKey === $activeTheme;
            $theme['local_folder'] = (string) ($localTheme['folder'] ?? '');

            // Versions-Vergleich
            if ($localTheme !== null && !empty($localTheme['version']) && !empty($theme['version'])) {
                $theme['updateAvailable'] = version_compare((string) $theme['version'], (string) $localTheme['version'], '>');
            } else {
                $theme['updateAvailable'] = false;
            }

            $theme['integrity_hash'] = $this->resolveIntegrityHash($theme);
            $theme['integrity_hash_present'] = $theme['integrity_hash'] !== '';
            $theme['install_supported'] = $this->canAutoInstall($theme);
            $theme['manual_install_only'] = !$theme['install_supported'];
            $theme['install_reason'] = $theme['install_supported']
                ? 'Paket und SHA-256 vorhanden.'
                : $this->getManualInstallReason($theme);
        }
        unset($theme);

        return [
            'catalog'   => $catalog,
            'installed' => $installed,
            'total'     => count($catalog),
            'stats'     => $this->buildStats($catalog),
            'source'    => $this->catalogSource,
            'filters'   => [
                'statuses' => $this->buildStatusFilters(),
            ],
        ];
    }

    /**
     * Theme aus dem Marketplace installieren
     */
    public function installTheme(string $slug): array
    {
        $slug = $this->normalizeThemeKey($slug);
        if ($slug === '') {
            return ['success' => false, 'error' => 'Kein Theme angegeben.'];
        }

        $theme = $this->findCatalogTheme($slug);
        if ($theme === null) {
            return ['success' => false, 'error' => 'Theme nicht im Marketplace gefunden.'];
        }

        if (!empty($theme['installed'])) {
            return ['success' => false, 'error' => 'Theme ist bereits installiert.'];
        }

        $downloadUrl = trim((string) ($theme['download_url'] ?? ''));
        $integrityHash = $this->resolveIntegrityHash($theme);

        if (!$this->canAutoInstall($theme)) {
            return [
                'success' => false,
                'error' => $this->getManualInstallReason($theme),
            ];
        }

        if (!$this->isAllowedMarketplaceUrl($downloadUrl)) {
            return ['success' => false, 'error' => 'Download-URL liegt außerhalb der erlaubten Marketplace-Hosts.'];
        }

        $targetFolder = $this->determineThemeTargetFolder($theme, $slug);
        $targetDir = rtrim(THEME_PATH, '/\\') . DIRECTORY_SEPARATOR . $targetFolder . DIRECTORY_SEPARATOR;
        $normalizedThemeBase = rtrim(str_replace('\\', '/', (string) THEME_PATH), '/') . '/';
        $normalizedTargetDir = rtrim(str_replace('\\', '/', $targetDir), '/') . '/';

        if (!str_starts_with($normalizedTargetDir, $normalizedThemeBase)) {
            return ['success' => false, 'error' => 'Theme-Zielverzeichnis ist ungültig.'];
        }

        if (is_dir(rtrim($targetDir, '/\\'))) {
            return ['success' => false, 'error' => 'Theme ist bereits installiert.'];
        }

        $result = $this->updateService->downloadAndInstallUpdate(
            $downloadUrl,
            $integrityHash,
            $targetDir,
            'theme',
            (string) ($theme['name'] ?? $slug),
            (string) ($theme['version'] ?? 'Marketplace')
        );

        if (($result['success'] ?? false) !== true) {
            return ['success' => false, 'error' => (string) ($result['message'] ?? 'Theme konnte nicht installiert werden.')];
        }

        return ['success' => true, 'message' => 'Theme „' . ((string) ($theme['name'] ?? $slug)) . '“ wurde installiert. Du kannst es jetzt in der Theme-Verwaltung aktivieren.'];
    }

    /**
     * Theme-Katalog aus index.json laden
     */
    private function getCatalog(): array
    {
        if ($this->catalogCache !== null) {
            return $this->catalogCache;
        }

        $remoteCatalog = $this->loadRemoteCatalog();
        if ($remoteCatalog !== []) {
            $this->catalogSource = [
                'type' => 'remote',
                'status' => 'success',
                'message' => 'Theme-Katalog erfolgreich aus der Remote-Quelle geladen.',
                'url' => $this->getMarketplaceUrl(),
            ];
            return $this->catalogCache = $remoteCatalog;
        }

        $localCatalog = $this->loadLocalCatalog();
        if ($localCatalog !== []) {
            $this->catalogSource = [
                'type' => 'local',
                'status' => 'warning',
                'message' => 'Remote-Katalog derzeit nicht verfügbar; lokaler Theme-Index wird als Fallback genutzt.',
                'url' => 'index.json',
            ];
            return $this->catalogCache = $localCatalog;
        }

        $this->catalogSource = [
            'type' => 'none',
            'status' => 'warning',
            'message' => 'Es konnte weder ein Remote-Katalog noch ein lokaler Theme-Index geladen werden.',
            'url' => $this->getMarketplaceUrl(),
        ];

        return $this->catalogCache = [];
    }

    /**
     * Marketplace-URL aus Settings
     */
    private function getMarketplaceUrl(): string
    {
        try {
            $db   = Database::instance();
            $row  = $db->get_row(
                "SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'theme_marketplace_url'"
            );
            $value = $this->normalizeMarketplaceUrl((string) ($row->option_value ?? ''));

            return $value !== '' ? $value : $this->normalizeMarketplaceUrl(self::DEFAULT_MARKETPLACE_URL);
        } catch (\Throwable $e) {
            return $this->normalizeMarketplaceUrl(self::DEFAULT_MARKETPLACE_URL);
        }
    }

    private function loadLocalCatalog(): array
    {
        $themeBase = rtrim(THEME_PATH, '/\\');
        $indexPaths = [
            dirname($themeBase) . DIRECTORY_SEPARATOR . 'index.json',
            $themeBase . DIRECTORY_SEPARATOR . 'index.json',
        ];

        foreach ($indexPaths as $indexPath) {
            if (!is_file($indexPath)) {
                continue;
            }

            $content = file_get_contents($indexPath);
            if ($content === false) {
                continue;
            }

            $data = \CMS\Json::decodeArray($content, []);
            if (!is_array($data)) {
                continue;
            }

            $catalog = $this->normalizeCatalogEntries($data, dirname($indexPath));
            if ($catalog !== []) {
                return $catalog;
            }
        }

        return [];
    }

    private function loadRemoteCatalog(): array
    {
        $repoUrl = $this->getMarketplaceUrl();
        $catalogUrl = $this->resolveCatalogIndexUrl($repoUrl);
        if ($catalogUrl === '') {
            return [];
        }

        $response = $this->httpClient->get($catalogUrl, [
            'timeout' => 10,
            'connectTimeout' => 5,
            'maxBytes' => 1024 * 1024,
            'allowedContentTypes' => ['application/json', 'text/plain'],
            'userAgent' => '365CMS Theme Marketplace',
        ]);

        $content = (string) ($response['body'] ?? '');
        if (($response['success'] ?? false) !== true || $content === '') {
            return [];
        }

        $data = \CMS\Json::decodeArray($content, []);
        if (!is_array($data)) {
            return [];
        }

        return $this->normalizeCatalogEntries($data, $this->resolveBasePath($catalogUrl));
    }

    private function normalizeCatalogEntries(array $data, string $sourceBase): array
    {
        $entries = [];

        if (isset($data['themes']) && is_array($data['themes'])) {
            $entries = $data['themes'];
        } elseif (array_is_list($data)) {
            $entries = $data;
        }

        $catalog = [];
        $seenSlugs = [];
        $normalizedRemoteSourceBase = $this->normalizeMarketplaceUrl($sourceBase);

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $manifestData = $this->sanitizeManifestData(
                $this->loadManifestData($entry, $normalizedRemoteSourceBase !== '' ? $normalizedRemoteSourceBase : $sourceBase)
            );
            $entrySlug = $this->normalizeThemeKey((string) ($entry['slug'] ?? ''));
            $manifestSlug = $this->normalizeThemeKey((string) ($manifestData['slug'] ?? ''));

            if ($entrySlug !== '' && $manifestSlug !== '' && $entrySlug !== $manifestSlug) {
                continue;
            }

            $slug = $entrySlug !== '' ? $entrySlug : $manifestSlug;
            if ($slug === '') {
                continue;
            }

            if (isset($seenSlugs[$slug])) {
                continue;
            }

            $normalized = array_merge($entry, $manifestData);
            $normalized['slug'] = $slug;
            $resolvedSourceBase = $normalizedRemoteSourceBase !== '' ? $normalizedRemoteSourceBase : $sourceBase;
            $normalized['name'] = $this->normalizeCatalogString($normalized['name'] ?? $entry['name'] ?? $slug, 120);
            $normalized['description'] = $this->normalizeCatalogString($normalized['description'] ?? '', 2000);
            $normalized['version'] = $this->normalizeCatalogString($normalized['version'] ?? '', 80);
            $normalized['author'] = $this->normalizeCatalogString($normalized['author'] ?? '', 120);
            $normalized['manifest'] = $this->normalizeCatalogString($normalized['manifest'] ?? '');
            $normalized['update_url'] = $this->resolveCatalogUrl($normalized, ['update_url'], $resolvedSourceBase);
            $normalized['download_url'] = $this->resolveDownloadUrl($normalized, $resolvedSourceBase);
            $normalized['purchase_url'] = $this->resolveCatalogUrl($normalized, ['purchase_url', 'buy_url', 'order_url'], $resolvedSourceBase);
            $normalized['is_paid'] = $this->normalizeBooleanValue($normalized['is_paid'] ?? false);
            $normalized['price_amount'] = $this->normalizeCatalogString($normalized['price_amount'] ?? $normalized['price'] ?? '', 40);
            $normalized['price_currency'] = strtoupper($this->normalizeCatalogString($normalized['price_currency'] ?? 'EUR', 8));
            $normalized['requires_cms'] = $this->normalizeCatalogString($normalized['requires_cms'] ?? $normalized['min_cms_version'] ?? '', 40);
            $normalized['requires_php'] = $this->normalizeCatalogString($normalized['requires_php'] ?? $normalized['min_php'] ?? '', 40);
            $normalized['screenshot'] = $this->resolveAssetUrl((string) ($normalized['screenshot'] ?? ''), $resolvedSourceBase);
            $normalized['sha256'] = $this->resolveIntegrityHash($normalized);

            $seenSlugs[$slug] = true;
            $catalog[] = $normalized;
        }

        return $catalog;
    }

    /**
     * @param array<int, array<string, mixed>> $catalog
     * @return array<string, int>
     */
    private function buildStats(array $catalog): array
    {
        return [
            'available' => count($catalog),
            'installable' => count(array_filter($catalog, static fn (array $theme): bool => !empty($theme['install_supported']) && empty($theme['installed']))),
            'manual_only' => count(array_filter($catalog, static fn (array $theme): bool => empty($theme['install_supported']) && empty($theme['installed']))),
            'active' => count(array_filter($catalog, static fn (array $theme): bool => !empty($theme['active']))),
        ];
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function buildStatusFilters(): array
    {
        return [
            ['value' => 'installable', 'label' => 'Automatisch installierbar'],
            ['value' => 'manual', 'label' => 'Nur manuell / Anfrage'],
            ['value' => 'installed', 'label' => 'Bereits installiert'],
            ['value' => 'active', 'label' => 'Aktiv'],
        ];
    }

    private function sanitizeManifestData(array $manifestData): array
    {
        $sanitized = [];

        foreach (self::MANIFEST_ALLOWED_KEYS as $key) {
            if (!array_key_exists($key, $manifestData)) {
                continue;
            }

            $value = $manifestData[$key];
            if (is_scalar($value) || $value === null) {
                $sanitized[$key] = is_bool($value)
                    ? $value
                    : $this->normalizeCatalogString($value);
            }
        }

        return $sanitized;
    }

    private function normalizeCatalogString(mixed $value, int $length = self::MAX_CATALOG_STRING_LENGTH): string
    {
        $string = preg_replace('/[\x00-\x1F\x7F]/u', '', trim((string) $value));

        return mb_substr($string ?? '', 0, $length);
    }

    private function resolveCatalogUrl(array $theme, array $keys, string $sourceBase): string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($theme[$key] ?? ''));
            if ($value === '') {
                continue;
            }

            if ($this->isAllowedMarketplaceUrl($value)) {
                return $value;
            }

            if ($this->isAllowedMarketplaceUrl($sourceBase)) {
                $resolved = $this->resolveAllowedRelativeMarketplaceUrl($sourceBase, $value);
                if ($this->isAllowedMarketplaceUrl($resolved)) {
                    return $resolved;
                }
            }
        }

        return '';
    }

    private function loadManifestData(array $entry, string $sourceBase): array
    {
        $manifest = trim((string) ($entry['manifest'] ?? ''));
        if ($manifest === '') {
            return [];
        }

        if ($this->isAllowedMarketplaceUrl($manifest)) {
            return $this->fetchRemoteJson($manifest);
        }

        if ($this->isAllowedMarketplaceUrl($sourceBase)) {
            $remoteManifestUrl = $this->resolveAllowedRelativeMarketplaceUrl($sourceBase, $manifest);
            if ($remoteManifestUrl !== '') {
                return $this->fetchRemoteJson($remoteManifestUrl);
            }

            return [];
        }

        $relativeManifestPath = $this->normalizeRelativeCatalogPath($manifest);
        if ($relativeManifestPath === '') {
            return [];
        }

        $manifestPath = rtrim($sourceBase, '/\\') . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativeManifestPath);
        if (!is_file($manifestPath)) {
            return [];
        }

        $resolvedSourceBase = realpath($sourceBase);
        $resolvedManifestPath = realpath($manifestPath);
        if ($resolvedSourceBase === false || $resolvedManifestPath === false || !str_starts_with(str_replace('\\', '/', $resolvedManifestPath), rtrim(str_replace('\\', '/', $resolvedSourceBase), '/') . '/')) {
            return [];
        }

        if (filesize($resolvedManifestPath) > self::MAX_MANIFEST_BYTES) {
            return [];
        }

        $content = file_get_contents($resolvedManifestPath);
        if ($content === false) {
            return [];
        }

        $data = \CMS\Json::decodeArray($content, []);
        return is_array($data) ? $data : [];
    }

    private function fetchRemoteJson(string $url): array
    {
        if (!$this->isAllowedMarketplaceUrl($url)) {
            return [];
        }

        $response = $this->httpClient->get($url, [
            'timeout' => 10,
            'connectTimeout' => 5,
            'maxBytes' => 512 * 1024,
            'allowedContentTypes' => ['application/json', 'text/plain'],
            'userAgent' => '365CMS Theme Marketplace',
        ]);

        $content = (string) ($response['body'] ?? '');
        if (($response['success'] ?? false) !== true || $content === '') {
            return [];
        }

        $data = \CMS\Json::decodeArray($content, []);
        return is_array($data) ? $data : [];
    }

    private function resolveDownloadUrl(array $theme, string $sourceBase): string
    {
        foreach (['download_url', 'package_url', 'archive_url'] as $key) {
            $value = trim((string) ($theme[$key] ?? ''));
            if ($value === '') {
                continue;
            }

            if ($this->isAllowedMarketplaceUrl($value)) {
                return $value;
            }

            if ($this->isAllowedMarketplaceUrl($sourceBase)) {
                $resolved = $this->resolveAllowedRelativeMarketplaceUrl($sourceBase, $value);
                if ($this->isAllowedMarketplaceUrl($resolved)) {
                    return $resolved;
                }
            }
        }

        return '';
    }

    private function resolveAssetUrl(string $value, string $sourceBase): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $normalizedValue = $this->normalizeMarketplaceUrl($value);
        if ($normalizedValue !== '') {
            return $normalizedValue;
        }

        if ($this->normalizeMarketplaceUrl($sourceBase) !== '') {
            return $this->resolveAllowedRelativeMarketplaceUrl($sourceBase, $value);
        }

        return '';
    }

    private function resolveIntegrityHash(array $theme): string
    {
        foreach (['sha256', 'checksum_sha256'] as $key) {
            $value = strtolower(trim((string)($theme[$key] ?? '')));
            if ($value !== '' && preg_match('/^[0-9a-f]{64}$/', $value) === 1) {
                return $value;
            }
        }

        return '';
    }

    private function canAutoInstall(array $theme): bool
    {
        $downloadUrl = trim((string)($theme['download_url'] ?? ''));
        $integrityHash = $this->resolveIntegrityHash($theme);

        return $downloadUrl !== ''
            && $integrityHash !== ''
            && $this->meetsEnvironmentRequirements($theme)
            && $this->isAllowedMarketplaceUrl($downloadUrl);
    }

    private function getManualInstallReason(array $theme): string
    {
        $compatibilityReason = $this->getCompatibilityFailureReason($theme);
        if ($compatibilityReason !== '') {
            return $compatibilityReason;
        }

        $purchaseUrl = trim((string)($theme['purchase_url'] ?? ''));
        if ($this->normalizeBooleanValue($theme['is_paid'] ?? false) && $purchaseUrl !== '') {
            return 'Kostenpflichtiges Theme – bitte zuerst über den Marketplace erwerben oder anfragen.';
        }

        $downloadUrl = trim((string)($theme['download_url'] ?? ''));
        if ($downloadUrl === '') {
            return 'Für dieses Theme ist aktuell kein Installationspaket im Marketplace hinterlegt. Bitte kopiere das Theme manuell nach /themes/ und aktiviere es anschließend in der Theme-Verwaltung.';
        }

        if (!$this->isAllowedMarketplaceUrl($downloadUrl)) {
            return 'Download-URL liegt außerhalb der erlaubten Marketplace-Hosts.';
        }

        return 'Für die automatische Installation fehlt eine gültige SHA-256-Prüfsumme. Bitte Paket manuell prüfen und installieren.';
    }

    private function findCatalogTheme(string $slug): ?array
    {
        foreach ($this->getCatalog() as $theme) {
            if ($this->normalizeThemeKey((string) ($theme['slug'] ?? '')) === $slug) {
                return $theme;
            }
        }

        return null;
    }

    private function determineThemeTargetFolder(array $theme, string $fallbackSlug): string
    {
        $candidates = [
            (string) ($theme['local_folder'] ?? ''),
            (string) ($theme['slug'] ?? ''),
            (string) ($theme['name'] ?? ''),
            $fallbackSlug,
        ];

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeThemeKey($candidate);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return $fallbackSlug;
    }

    private function getInstalledThemeMap(array $installed): array
    {
        $map = [];

        foreach ($installed as $folder => $theme) {
            $key = $this->normalizeThemeKey((string) $folder);
            if ($key === '') {
                continue;
            }

            $theme['folder'] = (string) $folder;
            $map[$key] = $theme;
        }

        return $map;
    }

    private function normalizeThemeKey(string $value): string
    {
        $value = strtolower(trim($value));
        return preg_replace('/[^a-z0-9_-]/', '', $value) ?? '';
    }

    private function normalizeBooleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'ja', 'paid'], true);
    }

    private function isHttpsUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false && str_starts_with($url, 'https://');
    }

    private function isAllowedMarketplaceUrl(string $url): bool
    {
        return $this->normalizeMarketplaceUrl($url) !== '';
    }

    private function normalizeMarketplaceUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || !$this->isHttpsUrl($url)) {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return '';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        $path = (string) ($parts['path'] ?? '/');
        $query = (string) ($parts['query'] ?? '');

        if ($scheme !== 'https' || $host === '') {
            return '';
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return '';
        }

        if ($port !== null && $port !== 443) {
            return '';
        }

        if (!$this->isAllowedMarketplaceHost($host)) {
            return '';
        }

        $path = $path === '' ? '/' : $path;
        if (preg_match('/[\x00-\x1F\x7F]/', $path) === 1 || preg_match('/[\x00-\x1F\x7F]/', $query) === 1) {
            return '';
        }

        $normalizedUrl = $scheme . '://' . $host . $path;
        if ($query !== '') {
            $normalizedUrl .= '?' . $query;
        }

        return $normalizedUrl;
    }

    private function isAllowedMarketplaceHost(string $host): bool
    {
        if (in_array($host, self::ALLOWED_MARKETPLACE_HOSTS, true)) {
            return true;
        }

        return str_ends_with($host, '.githubusercontent.com');
    }

    private function resolveCatalogIndexUrl(string $marketplaceUrl): string
    {
        $marketplaceUrl = $this->normalizeMarketplaceUrl($marketplaceUrl);
        if ($marketplaceUrl === '') {
            return '';
        }

        $path = (string) parse_url($marketplaceUrl, PHP_URL_PATH);
        if (str_ends_with(strtolower($path), '.json')) {
            return $marketplaceUrl;
        }

        return $this->normalizeMarketplaceUrl(rtrim($marketplaceUrl, '/') . '/index.json');
    }

    private function resolveBasePath(string $url): string
    {
        $url = $this->normalizeMarketplaceUrl($url);
        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return '';
        }

        $scheme = (string) ($parts['scheme'] ?? '');
        $host = (string) ($parts['host'] ?? '');
        if ($scheme === '' || $host === '') {
            return '';
        }

        $path = (string) ($parts['path'] ?? '');
        $directory = preg_replace('~/[^/]+$~', '', $path) ?? '';

        return $scheme . '://' . $host . $directory;
    }

    private function detectThemeDirectory(string $extractDir, string $slug): string
    {
        if ($this->isValidThemeDirectory($extractDir)) {
            return $extractDir;
        }

        $candidates = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isDir()) {
                continue;
            }

            $path = $fileInfo->getPathname();
            if (!$this->isValidThemeDirectory($path)) {
                continue;
            }

            $candidates[] = $path;
        }

        if ($candidates === []) {
            return '';
        }

        usort($candidates, function (string $left, string $right) use ($slug): int {
            $leftBase = $this->normalizeThemeKey(basename($left));
            $rightBase = $this->normalizeThemeKey(basename($right));

            $leftMatches = (int) ($leftBase === $slug);
            $rightMatches = (int) ($rightBase === $slug);
            if ($leftMatches !== $rightMatches) {
                return $rightMatches <=> $leftMatches;
            }

            return substr_count($left, DIRECTORY_SEPARATOR) <=> substr_count($right, DIRECTORY_SEPARATOR);
        });

        return $candidates[0];
    }

    private function isValidThemeDirectory(string $path): bool
    {
        return is_dir($path)
            && is_file($path . DIRECTORY_SEPARATOR . 'style.css')
            && is_file($path . DIRECTORY_SEPARATOR . 'theme.json')
            && is_file($path . DIRECTORY_SEPARATOR . 'functions.php');
    }

    private function determineTargetFolder(string $sourceDir, string $slug): string
    {
        $folder = $this->normalizeThemeKey(basename($sourceDir));
        return $folder !== '' ? $folder : $slug;
    }

    private function moveDirectory(string $sourceDir, string $targetDir): bool
    {
        $targetBase = dirname($targetDir);
        if (!is_dir($targetBase) && !mkdir($targetBase, 0775, true) && !is_dir($targetBase)) {
            return false;
        }

        if (rename($sourceDir, $targetDir)) {
            return true;
        }

        if (!$this->copyDirectory($sourceDir, $targetDir)) {
            return false;
        }

        $this->removeDirectory($sourceDir);
        return true;
    }

    private function copyDirectory(string $sourceDir, string $targetDir): bool
    {
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            return false;
        }

        $iterator = new \DirectoryIterator($sourceDir);
        foreach ($iterator as $item) {
            if ($item->isDot()) {
                continue;
            }

            $sourcePath = $item->getPathname();
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $item->getFilename();

            if ($item->isDir()) {
                if (!$this->copyDirectory($sourcePath, $targetPath)) {
                    return false;
                }
                continue;
            }

            if (!copy($sourcePath, $targetPath)) {
                return false;
            }
        }

        return true;
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
                continue;
            }

            unlink($item->getPathname());
        }

        rmdir($path);
    }

    private function validateZipEntries(\ZipArchive $zip, string $expectedSlug): bool
    {
        $expectedSlug = trim($expectedSlug, '/\\');
        if ($expectedSlug === '') {
            return false;
        }

        if ($zip->numFiles <= 0 || $zip->numFiles > self::MAX_ARCHIVE_ENTRIES) {
            return false;
        }

        $allowedRoots = [$expectedSlug];
        $hasAllowedRoot = false;
        $totalUncompressedSize = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entryName = $zip->getNameIndex($index);
            if (!is_string($entryName) || $entryName === '') {
                return false;
            }

            $normalized = str_replace('\\', '/', $entryName);
            $normalized = ltrim($normalized, '/');

            if ($normalized === ''
                || str_contains($normalized, '../')
                || str_contains($normalized, '..\\')
                || preg_match('/[\x00-\x1F\x7F]/', $normalized) === 1
                || preg_match('~^[A-Za-z]:/~', $normalized) === 1
            ) {
                return false;
            }

            $stat = $zip->statIndex($index);
            if (!is_array($stat)) {
                return false;
            }

            $entrySize = (int) ($stat['size'] ?? 0);
            if ($entrySize < 0) {
                return false;
            }

            $totalUncompressedSize += $entrySize;
            if ($totalUncompressedSize > self::MAX_ARCHIVE_UNCOMPRESSED_BYTES) {
                return false;
            }

            $segments = array_values(array_filter(explode('/', rtrim($normalized, '/')), static fn (string $segment): bool => $segment !== ''));
            if ($segments === []) {
                continue;
            }

            foreach ($segments as $segment) {
                if ($segment === '.' || $segment === '..') {
                    return false;
                }
            }

            if ($index === 0 && preg_match('/^[a-f0-9]{7,}$/i', $segments[0]) === 1) {
                $allowedRoots[] = $segments[0];
            }

            if (!in_array($segments[0], $allowedRoots, true)) {
                return false;
            }

            $hasAllowedRoot = true;
        }

        return $hasAllowedRoot;
    }

    private function normalizeRelativeCatalogPath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');

        if ($path === '' || strlen($path) > 255 || preg_match('/[\x00-\x1F\x7F]/', $path) === 1) {
            return '';
        }

        $segments = array_values(array_filter(explode('/', $path), static fn(string $segment): bool => $segment !== ''));
        if ($segments === []) {
            return '';
        }

        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '..') {
                return '';
            }
        }

        return implode('/', $segments);
    }

    private function resolveAllowedRelativeMarketplaceUrl(string $sourceBase, string $path): string
    {
        $relativePath = $this->normalizeRelativeCatalogPath($path);
        $normalizedSourceBase = $this->normalizeMarketplaceUrl($sourceBase);
        if ($relativePath === '' || $normalizedSourceBase === '') {
            return '';
        }

        $resolved = rtrim($normalizedSourceBase, '/') . '/' . $relativePath;

        return $this->normalizeMarketplaceUrl($resolved);
    }

    private function meetsEnvironmentRequirements(array $theme): bool
    {
        return $this->getCompatibilityFailureReason($theme) === '';
    }

    private function getCompatibilityFailureReason(array $theme): string
    {
        $requiresCms = trim((string) ($theme['requires_cms'] ?? ''));
        $requiresPhp = trim((string) ($theme['requires_php'] ?? ''));
        $currentCms = defined('CMS_VERSION')
            ? (string) CMS_VERSION
            : (class_exists('\CMS\\Version') ? (string) \CMS\Version::CURRENT : '0.0.0');

        if ($requiresCms !== '' && version_compare($currentCms, $requiresCms, '<')) {
            return 'Theme erfordert mindestens 365CMS ' . $requiresCms . '.';
        }

        if ($requiresPhp !== '' && version_compare(PHP_VERSION, $requiresPhp, '<')) {
            return 'Theme erfordert mindestens PHP ' . $requiresPhp . '.';
        }

        return '';
    }
}
