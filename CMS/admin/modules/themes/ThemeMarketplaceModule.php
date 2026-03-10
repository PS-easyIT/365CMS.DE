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

class ThemeMarketplaceModule
{
    private ThemeManager $themeManager;
    private HttpClient $httpClient;

    public function __construct()
    {
        $this->themeManager = ThemeManager::instance();
        $this->httpClient = HttpClient::getInstance();
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

            $theme['install_supported'] = !empty($theme['download_url']);
            $theme['manual_install_only'] = empty($theme['download_url']);
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
        if ($downloadUrl === '') {
            return [
                'success' => false,
                'error' => 'Für dieses Theme ist aktuell kein Installationspaket im Marketplace hinterlegt. Bitte kopiere das Theme manuell nach /themes/ und aktiviere es anschließend in der Theme-Verwaltung.',
            ];
        }

        if (!$this->isHttpsUrl($downloadUrl)) {
            return ['success' => false, 'error' => 'Ungültige oder unsichere Download-URL für das Theme.'];
        }

        if (!class_exists(\ZipArchive::class)) {
            return ['success' => false, 'error' => 'Die automatische Theme-Installation benötigt die PHP-Erweiterung ZipArchive.'];
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'cms_theme_');
        if ($tmpFile === false) {
            return ['success' => false, 'error' => 'Temporäre Datei konnte nicht erstellt werden.'];
        }

        $extractDir = $tmpFile . '_dir';

        try {
            $response = $this->httpClient->get($downloadUrl, [
                'timeout' => 30,
                'connectTimeout' => 10,
                'maxBytes' => 25 * 1024 * 1024,
                'allowedContentTypes' => ['application/zip', 'application/octet-stream', 'application/x-zip-compressed'],
                'userAgent' => '365CMS Theme Installer',
            ]);

            $content = (string) ($response['body'] ?? '');
            if (($response['success'] ?? false) !== true || $content === '') {
                return ['success' => false, 'error' => 'Theme-Paket konnte nicht heruntergeladen werden.'];
            }

            if (file_put_contents($tmpFile, $content) === false) {
                return ['success' => false, 'error' => 'Temporäres Theme-Paket konnte nicht geschrieben werden.'];
            }

            if (!mkdir($extractDir, 0775, true) && !is_dir($extractDir)) {
                return ['success' => false, 'error' => 'Temporäres Entpack-Verzeichnis konnte nicht erstellt werden.'];
            }

            $zip = new \ZipArchive();
            if ($zip->open($tmpFile) !== true) {
                return ['success' => false, 'error' => 'ZIP-Datei konnte nicht geöffnet werden.'];
            }

            if (!$zip->extractTo($extractDir)) {
                $zip->close();
                return ['success' => false, 'error' => 'Theme-Paket konnte nicht entpackt werden.'];
            }

            $zip->close();

            $sourceDir = $this->detectThemeDirectory($extractDir, $slug);
            if ($sourceDir === '') {
                return ['success' => false, 'error' => 'Im Theme-Paket wurde kein gültiges Theme-Verzeichnis gefunden.'];
            }

            $targetFolder = $this->determineTargetFolder($sourceDir, $slug);
            $targetDir = rtrim(THEME_PATH, '/\\') . DIRECTORY_SEPARATOR . $targetFolder;

            if (is_dir($targetDir)) {
                return ['success' => false, 'error' => 'Theme-Zielverzeichnis existiert bereits.'];
            }

            if (!$this->moveDirectory($sourceDir, $targetDir)) {
                return ['success' => false, 'error' => 'Theme-Dateien konnten nicht in das Zielverzeichnis verschoben werden.'];
            }
        } finally {
            if (is_file($tmpFile)) {
                unlink($tmpFile);
            }

            if (is_dir($extractDir)) {
                $this->removeDirectory($extractDir);
            }
        }

        return ['success' => true, 'message' => 'Theme „' . ((string) ($theme['name'] ?? $slug)) . '“ wurde installiert. Du kannst es jetzt in der Theme-Verwaltung aktivieren.'];
    }

    /**
     * Theme-Katalog aus index.json laden
     */
    private function getCatalog(): array
    {
        $localCatalog = $this->loadLocalCatalog();
        if ($localCatalog !== []) {
            return $localCatalog;
        }

        $remoteCatalog = $this->loadRemoteCatalog();
        if ($remoteCatalog !== []) {
            return $remoteCatalog;
        }

        return [];
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
            return $row->option_value ?? '';
        } catch (\Throwable $e) {
            return '';
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
        if (!$this->isHttpsUrl($repoUrl)) {
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
            $normalized['download_url'] = $this->resolveDownloadUrl($normalized, $sourceBase);
            $normalized['screenshot'] = $this->resolveAssetUrl((string) ($normalized['screenshot'] ?? ''), $sourceBase);

            $catalog[] = $normalized;
        }

        return $catalog;
    }

    private function loadManifestData(array $entry, string $sourceBase): array
    {
        $manifest = trim((string) ($entry['manifest'] ?? ''));
        if ($manifest === '') {
            return [];
        }

        if ($this->isHttpsUrl($manifest)) {
            return $this->fetchRemoteJson($manifest);
        }

        if ($this->isHttpsUrl($sourceBase)) {
            return $this->fetchRemoteJson(rtrim($sourceBase, '/') . '/' . ltrim($manifest, '/'));
        }

        $manifestPath = rtrim($sourceBase, '/\\') . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($manifest, '/\\'));
        if (!is_file($manifestPath)) {
            return [];
        }

        $content = file_get_contents($manifestPath);
        if ($content === false) {
            return [];
        }

        $data = \CMS\Json::decodeArray($content, []);
        return is_array($data) ? $data : [];
    }

    private function fetchRemoteJson(string $url): array
    {
        if (!$this->isHttpsUrl($url)) {
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

            if ($this->isHttpsUrl($value)) {
                return $value;
            }

            if ($this->isHttpsUrl($sourceBase)) {
                return rtrim($sourceBase, '/') . '/' . ltrim($value, '/');
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

        if ($this->isHttpsUrl($value) || str_starts_with($value, '/')) {
            return $value;
        }

        if ($this->isHttpsUrl($sourceBase)) {
            return rtrim($sourceBase, '/') . '/' . ltrim($value, '/');
        }

        return '';
    }

    private function findCatalogTheme(string $slug): ?array
    {
        foreach ($this->getData()['catalog'] as $theme) {
            if ($this->normalizeThemeKey((string) ($theme['slug'] ?? '')) === $slug) {
                return $theme;
            }
        }

        return null;
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

    private function isHttpsUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false && str_starts_with($url, 'https://');
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
}
