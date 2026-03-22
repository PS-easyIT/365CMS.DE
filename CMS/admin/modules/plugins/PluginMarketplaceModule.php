<?php
declare(strict_types=1);

/**
 * PluginMarketplaceModule – Verfügbare Plugins aus der Registry
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Http\Client as HttpClient;

class PluginMarketplaceModule
{
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

    private const DEFAULT_REGISTRY_URL = 'https://365cms.de/marketplace/plugins/index.json';

    private readonly \CMS\Database $db;
    private readonly string $prefix;

    public function __construct()
    {
        $this->db     = \CMS\Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    public function getData(): array
    {
        $available = $this->loadRegistry();
        $installed = $this->getInstalledSlugs();

        foreach ($available as &$plugin) {
            $plugin['installed'] = in_array($plugin['slug'] ?? '', $installed, true);
            $plugin['auto_install_supported'] = $this->canAutoInstall($plugin);
            $plugin['manual_install_only'] = !$plugin['auto_install_supported'];
            $plugin['integrity_hash_present'] = $this->resolveIntegrityHash($plugin) !== '';
            $plugin['install_reason'] = $plugin['auto_install_supported']
                ? 'Paket und SHA-256 vorhanden.'
                : $this->getManualInstallReason($plugin);
        }
        unset($plugin);

        return [
            'plugins'   => $available,
            'installed' => $installed,
            'stats'     => [
                'available'   => count($available),
                'installed'   => count($installed),
                'installable' => count(array_filter($available, fn($p) => empty($p['installed']) && !empty($p['auto_install_supported']))),
            ],
        ];
    }

    public function installPlugin(string $slug): array
    {
        $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower($slug));
        if ($slug === '') {
            return ['success' => false, 'error' => 'Ungültiger Plugin-Slug.'];
        }

        // Prüfe ob Plugin im Registry vorhanden
        $available = $this->loadRegistry();
        $found = null;
        foreach ($available as $p) {
            if (($p['slug'] ?? '') === $slug) {
                $found = $p;
                break;
            }
        }

        if (!$found) {
            return ['success' => false, 'error' => 'Plugin nicht im Marketplace gefunden.'];
        }

        $downloadUrl = $found['download_url'] ?? '';
        $integrityHash = $this->resolveIntegrityHash($found);

        if (!$this->canAutoInstall($found)) {
            return ['success' => false, 'error' => $this->getManualInstallReason($found)];
        }

        if (!$this->isAllowedMarketplaceUrl($downloadUrl)) {
            return ['success' => false, 'error' => 'Download-URL liegt außerhalb der erlaubten Marketplace-Hosts.'];
        }

        // Download + Entpacken (vereinfachte Logik)
        $pluginsDir = defined('PLUGINS_PATH') ? PLUGINS_PATH : (defined('ABSPATH') ? ABSPATH . 'plugins/' : '');
        $targetDir  = $pluginsDir . $slug;

        if (is_dir($targetDir)) {
            return ['success' => false, 'error' => 'Plugin ist bereits installiert.'];
        }

        // Remote-Download
        $tmpFile = tempnam(sys_get_temp_dir(), 'cms_plugin_');
        if ($tmpFile === false) {
            return ['success' => false, 'error' => 'Temporäre Datei konnte nicht erstellt werden.'];
        }
        $response = HttpClient::getInstance()->get($downloadUrl, [
            'userAgent' => '365CMS-PluginMarketplace/1.0',
            'timeout' => 30,
            'connectTimeout' => 10,
            'maxBytes' => 25 * 1024 * 1024,
            'allowedContentTypes' => ['application/zip', 'application/octet-stream', 'application/x-zip-compressed'],
        ]);
        $content = (string) ($response['body'] ?? '');

        if (($response['success'] ?? false) !== true || $content === '') {
            if (is_file($tmpFile)) {
                unlink($tmpFile);
            }

            return ['success' => false, 'error' => 'Download fehlgeschlagen.'];
        }
        file_put_contents($tmpFile, $content);

        if (!\CMS\Services\UpdateService::getInstance()->verifyDownloadIntegrity($tmpFile, $integrityHash)) {
            if (is_file($tmpFile)) {
                unlink($tmpFile);
            }

            return ['success' => false, 'error' => 'SHA-256-Prüfsumme des Plugin-Pakets stimmt nicht. Installation aus Sicherheitsgründen abgebrochen.'];
        }

        // ZIP entpacken
        $zip = new \ZipArchive();
        if ($zip->open($tmpFile) !== true) {
            unlink($tmpFile);
            return ['success' => false, 'error' => 'ZIP-Datei konnte nicht geöffnet werden.'];
        }

        if (!$this->validateZipEntries($zip, $slug)) {
            $zip->close();
            unlink($tmpFile);
            return ['success' => false, 'error' => 'Plugin-Paket enthält ungültige oder unsichere Pfade.'];
        }

        $zip->extractTo($pluginsDir);
        $zip->close();
        unlink($tmpFile);

        if (!is_dir($targetDir)) {
            return ['success' => false, 'error' => 'Entpacken fehlgeschlagen – Verzeichnis nicht gefunden.'];
        }

        return ['success' => true, 'message' => "Plugin \"{$slug}\" installiert. Aktiviere es unter Plugin-Verwaltung."];
    }

    private function loadRegistry(): array
    {
        $registryUrl = $this->getRegistryUrl();
        if ($registryUrl !== '') {
            $remoteRegistry = $this->loadRemoteRegistry($registryUrl);
            if ($remoteRegistry !== []) {
                return $remoteRegistry;
            }
        }

        $localIndex = $this->resolveLocalRegistryPath();
        if ($localIndex === '' || !is_file($localIndex)) {
            return [];
        }

        $content = file_get_contents($localIndex);
        if ($content === false || $content === '') {
            return [];
        }

        $json = \CMS\Json::decodeArray($content, []);
        $plugins = is_array($json) ? ($json['plugins'] ?? $json) : [];

        return $this->sanitizeCatalogEntries(is_array($plugins) ? $plugins : [], dirname($localIndex));
    }

    private function getRegistryUrl(): string
    {
        $row = $this->db->get_row(
            "SELECT option_value FROM {$this->prefix}settings WHERE option_name = 'plugin_registry_url'"
        );

        $value = trim((string) ($row->option_value ?? ''));

        return $value !== '' ? $value : self::DEFAULT_REGISTRY_URL;
    }

    private function loadRemoteRegistry(string $registryUrl): array
    {
        if (!$this->isAllowedMarketplaceUrl($registryUrl)) {
            return [];
        }

        $response = HttpClient::getInstance()->get($registryUrl, [
            'userAgent' => '365CMS-PluginMarketplace/1.0',
            'timeout' => 10,
            'connectTimeout' => 5,
            'maxBytes' => 1024 * 1024,
            'allowedContentTypes' => ['application/json', 'text/plain'],
        ]);
        $content = (string) ($response['body'] ?? '');

        if (($response['success'] ?? false) !== true || $content === '') {
            return [];
        }

        $data = \CMS\Json::decodeArray($content, []);
        $plugins = is_array($data) ? ($data['plugins'] ?? $data) : [];

        return $this->sanitizeCatalogEntries(
            is_array($plugins) ? $plugins : [],
            $this->resolveBasePath($registryUrl)
        );
    }

    private function getInstalledSlugs(): array
    {
        $pluginsDir = $this->resolvePluginsDir();
        $slugs = [];
        if (is_dir($pluginsDir)) {
            foreach (new \DirectoryIterator($pluginsDir) as $item) {
                if ($item->isDot() || !$item->isDir()) continue;
                $slugs[] = $item->getFilename();
            }
        }
        return $slugs;
    }

    private function sanitizeCatalogEntries(array $entries, string $sourceBase = ''): array
    {
        $sanitized = [];

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $manifestData = $this->loadManifestData($entry, $sourceBase);
            $normalized = array_merge($entry, $manifestData);

            $downloadUrl = $this->resolveCatalogUrl($normalized, ['download_url', 'package_url', 'archive_url'], $sourceBase);
            $purchaseUrl = $this->resolveCatalogUrl($normalized, ['purchase_url', 'buy_url', 'order_url'], $sourceBase);
            $updateUrl = $this->resolveCatalogUrl($normalized, ['update_url'], $sourceBase);

            $normalized['slug'] = preg_replace('/[^a-z0-9_-]/', '', strtolower((string)($normalized['slug'] ?? '')));
            if (($normalized['slug'] ?? '') === '') {
                continue;
            }

            $normalized['name'] = (string)($normalized['name'] ?? $normalized['slug'] ?? '');
            $normalized['description'] = (string)($normalized['description'] ?? '');
            $normalized['version'] = (string)($normalized['version'] ?? '');
            $normalized['author'] = (string)($normalized['author'] ?? '');
            $normalized['category'] = (string)($normalized['category'] ?? '');
            $normalized['manifest'] = (string)($normalized['manifest'] ?? '');
            $normalized['update_url'] = $updateUrl;
            $normalized['download_url'] = $downloadUrl;
            $normalized['purchase_url'] = $purchaseUrl;
            $normalized['is_paid'] = $this->normalizeBooleanValue($normalized['is_paid'] ?? false);
            $normalized['price_amount'] = (string)($normalized['price_amount'] ?? $normalized['price'] ?? '');
            $normalized['price_currency'] = strtoupper((string)($normalized['price_currency'] ?? 'EUR'));
            $normalized['requires_cms'] = (string)($normalized['requires_cms'] ?? $normalized['min_cms_version'] ?? '');
            $normalized['requires_php'] = (string)($normalized['requires_php'] ?? $normalized['min_php'] ?? '');
            $normalized['sha256'] = $this->resolveIntegrityHash($normalized);

            $sanitized[] = $normalized;
        }

        return $sanitized;
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

        if ($sourceBase !== '' && $this->isAllowedMarketplaceUrl($sourceBase)) {
            return $this->fetchRemoteJson(rtrim($sourceBase, '/') . '/' . ltrim($manifest, '/'));
        }

        $manifestPath = rtrim($sourceBase, '/\\') . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($manifest, '/\\'));
        if (!is_file($manifestPath)) {
            return [];
        }

        $content = file_get_contents($manifestPath);
        if ($content === false || $content === '') {
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

        $response = HttpClient::getInstance()->get($url, [
            'userAgent' => '365CMS-PluginMarketplace/1.0',
            'timeout' => 10,
            'connectTimeout' => 5,
            'maxBytes' => 512 * 1024,
            'allowedContentTypes' => ['application/json', 'text/plain'],
        ]);
        $content = (string) ($response['body'] ?? '');

        if (($response['success'] ?? false) !== true || $content === '') {
            return [];
        }

        $data = \CMS\Json::decodeArray($content, []);

        return is_array($data) ? $data : [];
    }

    private function resolveCatalogUrl(array $entry, array $keys, string $sourceBase): string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($entry[$key] ?? ''));
            if ($value === '') {
                continue;
            }

            if ($this->isAllowedMarketplaceUrl($value)) {
                return $value;
            }

            if ($sourceBase !== '' && $this->isAllowedMarketplaceUrl($sourceBase)) {
                $resolved = rtrim($sourceBase, '/') . '/' . ltrim($value, '/');
                if ($this->isAllowedMarketplaceUrl($resolved)) {
                    return $resolved;
                }
            }
        }

        return '';
    }

    private function resolveBasePath(string $url): string
    {
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

    private function normalizeBooleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'ja', 'paid'], true);
    }

    private function canAutoInstall(array $plugin): bool
    {
        $downloadUrl = trim((string)($plugin['download_url'] ?? ''));
        $integrityHash = $this->resolveIntegrityHash($plugin);

        return $downloadUrl !== ''
            && $integrityHash !== ''
            && $this->isAllowedMarketplaceUrl($downloadUrl);
    }

    private function resolveIntegrityHash(array $plugin): string
    {
        foreach (['sha256', 'checksum_sha256'] as $key) {
            $value = strtolower(trim((string)($plugin[$key] ?? '')));
            if ($value !== '' && preg_match('/^[0-9a-f]{64}$/', $value) === 1) {
                return $value;
            }
        }

        return '';
    }

    private function getManualInstallReason(array $plugin): string
    {
        $purchaseUrl = trim((string)($plugin['purchase_url'] ?? ''));
        if ($this->normalizeBooleanValue($plugin['is_paid'] ?? false) && $purchaseUrl !== '') {
            return 'Kostenpflichtiges Plugin – bitte zuerst über den Marketplace erwerben oder anfragen.';
        }

        $downloadUrl = trim((string)($plugin['download_url'] ?? ''));
        if ($downloadUrl === '') {
            return 'Keine Download-URL verfügbar. Plugin muss manuell installiert werden.';
        }

        if (!$this->isAllowedMarketplaceUrl($downloadUrl)) {
            return 'Download-URL liegt außerhalb der erlaubten Marketplace-Hosts.';
        }

        return 'Für die automatische Installation fehlt eine gültige SHA-256-Prüfsumme. Bitte Paket manuell prüfen und installieren.';
    }

    private function isAllowedMarketplaceUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($scheme !== 'https' || $host === '') {
            return false;
        }

        if (in_array($host, self::ALLOWED_MARKETPLACE_HOSTS, true)) {
            return true;
        }

        return str_ends_with($host, '.githubusercontent.com');
    }

    private function validateZipEntries(\ZipArchive $zip, string $expectedSlug): bool
    {
        $expectedSlug = trim($expectedSlug, '/\\');
        if ($expectedSlug === '') {
            return false;
        }

        $hasMatchingRoot = false;

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
                || preg_match('~^[A-Za-z]:/~', $normalized) === 1
            ) {
                return false;
            }

            $segments = array_values(array_filter(explode('/', rtrim($normalized, '/')), static fn (string $segment): bool => $segment !== ''));
            if ($segments === []) {
                continue;
            }

            if ($segments[0] !== $expectedSlug) {
                return false;
            }

            $hasMatchingRoot = true;
        }

        return $hasMatchingRoot;
    }

    private function resolvePluginsDir(): string
    {
        if (defined('PLUGIN_PATH')) {
            return rtrim((string) PLUGIN_PATH, '/\\') . DIRECTORY_SEPARATOR;
        }

        if (defined('PLUGINS_PATH')) {
            return rtrim((string) PLUGINS_PATH, '/\\') . DIRECTORY_SEPARATOR;
        }

        return defined('ABSPATH') ? ABSPATH . 'plugins/' : '';
    }

    private function resolveLocalRegistryPath(): string
    {
        $pluginsDir = $this->resolvePluginsDir();
        if ($pluginsDir === '') {
            return '';
        }

        return rtrim(dirname(rtrim($pluginsDir, '/\\')), '/\\') . DIRECTORY_SEPARATOR . 'index.json';
    }
}
