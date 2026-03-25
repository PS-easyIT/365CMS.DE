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
            return $this->catalogCache = $remoteCatalog;
        }

        $localCatalog = $this->loadLocalCatalog();
        if ($localCatalog !== []) {
            return $this->catalogCache = $localCatalog;
        }

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
            $value = trim((string) ($row->option_value ?? ''));

            return $value !== '' ? $value : self::DEFAULT_MARKETPLACE_URL;
        } catch (\Throwable $e) {
            return self::DEFAULT_MARKETPLACE_URL;
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
        if (!$this->isAllowedMarketplaceUrl($repoUrl)) {
            return [];
        }

        $response = $this->httpClient->get(rtrim($repoUrl, '/') . '/index.json', [
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

        return $this->normalizeCatalogEntries($data, rtrim($repoUrl, '/'));
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

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $slug = $this->normalizeThemeKey((string) ($entry['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $manifestData = $this->loadManifestData($entry, $sourceBase);
            $normalized = array_merge($entry, $manifestData);
            $normalized['slug'] = $slug;
            $normalized['name'] = (string) ($normalized['name'] ?? $entry['name'] ?? $slug);
            $normalized['description'] = (string) ($normalized['description'] ?? '');
            $normalized['version'] = (string) ($normalized['version'] ?? '');
            $normalized['author'] = (string) ($normalized['author'] ?? '');
            $normalized['manifest'] = (string) ($normalized['manifest'] ?? '');
            $normalized['update_url'] = $this->resolveCatalogUrl($normalized, ['update_url'], $sourceBase);
            $normalized['download_url'] = $this->resolveDownloadUrl($normalized, $sourceBase);
            $normalized['purchase_url'] = $this->resolveCatalogUrl($normalized, ['purchase_url', 'buy_url', 'order_url'], $sourceBase);
            $normalized['is_paid'] = $this->normalizeBooleanValue($normalized['is_paid'] ?? false);
            $normalized['price_amount'] = (string) ($normalized['price_amount'] ?? $normalized['price'] ?? '');
            $normalized['price_currency'] = strtoupper((string) ($normalized['price_currency'] ?? 'EUR'));
            $normalized['requires_cms'] = (string) ($normalized['requires_cms'] ?? $normalized['min_cms_version'] ?? '');
            $normalized['requires_php'] = (string) ($normalized['requires_php'] ?? $normalized['min_php'] ?? '');
            $normalized['screenshot'] = $this->resolveAssetUrl((string) ($normalized['screenshot'] ?? ''), $sourceBase);
            $normalized['sha256'] = $this->resolveIntegrityHash($normalized);

            $catalog[] = $normalized;
        }

        return $catalog;
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
                $resolved = rtrim($sourceBase, '/') . '/' . ltrim($value, '/');
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
            return $this->fetchRemoteJson(rtrim($sourceBase, '/') . '/' . ltrim($manifest, '/'));
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
                $resolved = rtrim($sourceBase, '/') . '/' . ltrim($value, '/');
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

        if ($this->isAllowedMarketplaceUrl($value) || str_starts_with($value, '/')) {
            return $value;
        }

        if ($this->isAllowedMarketplaceUrl($sourceBase)) {
            $resolved = rtrim($sourceBase, '/') . '/' . ltrim($value, '/');
            return $this->isAllowedMarketplaceUrl($resolved) ? $resolved : '';
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
            && $this->isAllowedMarketplaceUrl($downloadUrl);
    }

    private function getManualInstallReason(array $theme): string
    {
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
        if (!$this->isHttpsUrl($url)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        if (in_array($host, self::ALLOWED_MARKETPLACE_HOSTS, true)) {
            return true;
        }

        return str_ends_with($host, '.githubusercontent.com');
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
}
