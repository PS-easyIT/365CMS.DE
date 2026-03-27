<?php
declare(strict_types=1);

/**
 * PluginMarketplaceModule – Verfügbare Plugins aus der Registry
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Http\Client as HttpClient;
use CMS\Services\UpdateService;

class PluginMarketplaceModule
{
    private const MAX_REGISTRY_BYTES = 1048576;
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
        'category',
        'manifest',
        'update_url',
        'download_url',
        'package_url',
        'archive_url',
        'purchase_url',
        'buy_url',
        'order_url',
        'is_paid',
        'is_commercial',
        'price_amount',
        'price_currency',
        'price',
        'contact_form_slug',
        'purchase_type',
        'sha256',
        'checksum_sha256',
        'package_size',
        'homepage_url',
        'docs_url',
        'changelog_url',
        'icon_url',
        'screenshot',
        'requires_cms',
        'min_cms_version',
        'requires_php',
        'min_php',
        'tested_up_to',
        'released',
        'notes',
        'submission_source',
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

    private const DEFAULT_REGISTRY_URL = 'https://365cms.de/marketplace/plugins/index.json';

    private readonly \CMS\Database $db;
    private readonly string $prefix;
    private readonly HttpClient $httpClient;
    private readonly UpdateService $updateService;
    /** @var array<int, array<string, mixed>>|null */
    private ?array $registryCache = null;

    public function __construct()
    {
        $this->db     = \CMS\Database::instance();
        $this->prefix = $this->db->getPrefix();
        $this->httpClient = HttpClient::getInstance();
        $this->updateService = UpdateService::getInstance();
    }

    public function getData(): array
    {
        $available = $this->loadRegistry();
        $installed = $this->getInstalledSlugs();
        $installedLookup = array_fill_keys($installed, true);

        foreach ($available as &$plugin) {
            $pluginSlug = $this->normalizePluginSlug((string) ($plugin['slug'] ?? ''));
            $plugin['installed'] = $pluginSlug !== '' && isset($installedLookup[$pluginSlug]);
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
        $slug = $this->normalizePluginSlug($slug);
        if ($slug === '') {
            return ['success' => false, 'error' => 'Ungültiger Plugin-Slug.'];
        }

        $found = $this->findCatalogPlugin($slug);

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

        $pluginsDir = $this->resolvePluginsDir();
        if ($pluginsDir === '') {
            return ['success' => false, 'error' => 'Plugin-Zielverzeichnis ist nicht verfügbar.'];
        }

        $targetDir = $pluginsDir . $slug . DIRECTORY_SEPARATOR;

        if (is_dir($targetDir)) {
            return ['success' => false, 'error' => 'Plugin ist bereits installiert.'];
        }

        $result = $this->updateService->downloadAndInstallUpdate(
            $downloadUrl,
            $integrityHash,
            $targetDir,
            'plugin',
            (string) ($found['name'] ?? $slug),
            (string) ($found['version'] ?? 'Marketplace')
        );

        if (($result['success'] ?? false) !== true) {
            return ['success' => false, 'error' => (string) ($result['message'] ?? 'Plugin konnte nicht installiert werden.')];
        }

        return ['success' => true, 'message' => "Plugin \"{$slug}\" installiert. Aktiviere es unter Plugin-Verwaltung."];
    }

    private function loadRegistry(): array
    {
        if ($this->registryCache !== null) {
            return $this->registryCache;
        }

        $registryUrl = $this->getRegistryUrl();
        if ($registryUrl !== '') {
            $remoteRegistry = $this->loadRemoteRegistry($registryUrl);
            if ($remoteRegistry !== []) {
                return $this->registryCache = $remoteRegistry;
            }
        }

        $localIndex = $this->resolveLocalRegistryPath();
        if ($localIndex === '' || !is_file($localIndex)) {
            return $this->registryCache = [];
        }

        $content = file_get_contents($localIndex);
        if ($content === false || $content === '') {
            return $this->registryCache = [];
        }

        $json = \CMS\Json::decodeArray($content, []);
        $plugins = is_array($json) ? ($json['plugins'] ?? $json) : [];

        return $this->registryCache = $this->sanitizeCatalogEntries(is_array($plugins) ? $plugins : [], dirname($localIndex));
    }

    private function getRegistryUrl(): string
    {
        $row = $this->db->get_row(
            "SELECT option_value FROM {$this->prefix}settings WHERE option_name = 'plugin_registry_url'"
        );

        $value = trim((string) ($row->option_value ?? ''));

        $normalizedRegistryUrl = $this->normalizeMarketplaceUrl($value);
        if ($normalizedRegistryUrl !== '') {
            return $normalizedRegistryUrl;
        }

        return $this->normalizeMarketplaceUrl(self::DEFAULT_REGISTRY_URL);
    }

    private function loadRemoteRegistry(string $registryUrl): array
    {
        $registryUrl = $this->normalizeMarketplaceUrl($registryUrl);
        if ($registryUrl === '') {
            return [];
        }

        $response = $this->httpClient->get($registryUrl, [
            'userAgent' => '365CMS-PluginMarketplace/1.0',
            'timeout' => 10,
            'connectTimeout' => 5,
            'maxBytes' => self::MAX_REGISTRY_BYTES,
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

                $slug = $this->normalizePluginSlug($item->getFilename());
                if ($slug !== '') {
                    $slugs[$slug] = true;
                }
            }
        }

        return array_keys($slugs);
    }

    private function sanitizeCatalogEntries(array $entries, string $sourceBase = ''): array
    {
        $sanitized = [];
        $seenSlugs = [];
        $normalizedRemoteSourceBase = $this->normalizeMarketplaceUrl($sourceBase);

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $manifestData = $this->sanitizeManifestData(
                $this->loadManifestData($entry, $normalizedRemoteSourceBase !== '' ? $normalizedRemoteSourceBase : $sourceBase)
            );
            $entrySlug = $this->normalizePluginSlug((string) ($entry['slug'] ?? ''));
            $manifestSlug = $this->normalizePluginSlug((string) ($manifestData['slug'] ?? ''));

            if ($entrySlug !== '' && $manifestSlug !== '' && $entrySlug !== $manifestSlug) {
                continue;
            }

            $normalized = array_merge($entry, $manifestData);
            $normalized['slug'] = $entrySlug !== '' ? $entrySlug : $manifestSlug;

            $resolvedSourceBase = $normalizedRemoteSourceBase !== '' ? $normalizedRemoteSourceBase : $sourceBase;
            $downloadUrl = $this->resolveCatalogUrl($normalized, ['download_url', 'package_url', 'archive_url'], $resolvedSourceBase);
            $purchaseUrl = $this->resolveCatalogUrl($normalized, ['purchase_url', 'buy_url', 'order_url'], $resolvedSourceBase);
            $updateUrl = $this->resolveCatalogUrl($normalized, ['update_url'], $resolvedSourceBase);

            if (($normalized['slug'] ?? '') === '') {
                continue;
            }

            if (isset($seenSlugs[$normalized['slug']])) {
                continue;
            }

            $normalized['name'] = $this->normalizeCatalogString($normalized['name'] ?? $normalized['slug'] ?? '');
            $normalized['description'] = $this->normalizeCatalogString($normalized['description'] ?? '', 2000);
            $normalized['version'] = $this->normalizeCatalogString($normalized['version'] ?? '', 80);
            $normalized['author'] = $this->normalizeCatalogString($normalized['author'] ?? '', 120);
            $normalized['category'] = $this->normalizeCatalogString($normalized['category'] ?? '', 80);
            $normalized['manifest'] = $this->normalizeCatalogString($normalized['manifest'] ?? '');
            $normalized['update_url'] = $updateUrl;
            $normalized['download_url'] = $downloadUrl;
            $normalized['purchase_url'] = $purchaseUrl;
            $normalized['is_paid'] = $this->normalizeBooleanValue($normalized['is_paid'] ?? false);
            $normalized['price_amount'] = $this->normalizeCatalogString($normalized['price_amount'] ?? $normalized['price'] ?? '', 40);
            $normalized['price_currency'] = strtoupper($this->normalizeCatalogString($normalized['price_currency'] ?? 'EUR', 8));
            $normalized['requires_cms'] = $this->normalizeCatalogString($normalized['requires_cms'] ?? $normalized['min_cms_version'] ?? '', 40);
            $normalized['requires_php'] = $this->normalizeCatalogString($normalized['requires_php'] ?? $normalized['min_php'] ?? '', 40);
            $normalized['sha256'] = $this->resolveIntegrityHash($normalized);

            $seenSlugs[$normalized['slug']] = true;
            $sanitized[] = $normalized;
        }

        return $sanitized;
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

    private function loadManifestData(array $entry, string $sourceBase): array
    {
        $manifest = trim((string) ($entry['manifest'] ?? ''));
        if ($manifest === '') {
            return [];
        }

        $manifestUrl = $this->normalizeMarketplaceUrl($manifest);
        if ($manifestUrl !== '') {
            return $this->fetchRemoteJson($manifestUrl);
        }

        if ($sourceBase !== '' && $this->normalizeMarketplaceUrl($sourceBase) !== '') {
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
        if ($content === false || $content === '') {
            return [];
        }

        $data = \CMS\Json::decodeArray($content, []);

        return is_array($data) ? $data : [];
    }

    private function fetchRemoteJson(string $url): array
    {
        $url = $this->normalizeMarketplaceUrl($url);
        if ($url === '') {
            return [];
        }

        $response = $this->httpClient->get($url, [
            'userAgent' => '365CMS-PluginMarketplace/1.0',
            'timeout' => 10,
            'connectTimeout' => 5,
            'maxBytes' => self::MAX_MANIFEST_BYTES,
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

            $normalizedUrl = $this->normalizeMarketplaceUrl($value);
            if ($normalizedUrl !== '') {
                return $normalizedUrl;
            }

            if ($sourceBase !== '' && $this->normalizeMarketplaceUrl($sourceBase) !== '') {
                $resolved = $this->resolveAllowedRelativeMarketplaceUrl($sourceBase, $value);
                if ($resolved !== '') {
                    return $resolved;
                }
            }
        }

        return '';
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

    private function normalizePluginSlug(string $slug): string
    {
        return (string) preg_replace('/[^a-z0-9_-]/', '', strtolower($slug));
    }

    private function findCatalogPlugin(string $slug): ?array
    {
        foreach ($this->loadRegistry() as $plugin) {
            if ($this->normalizePluginSlug((string) ($plugin['slug'] ?? '')) === $slug) {
                return $plugin;
            }
        }

        return null;
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
            && $this->meetsEnvironmentRequirements($plugin)
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
        $compatibilityReason = $this->getCompatibilityFailureReason($plugin);
        if ($compatibilityReason !== '') {
            return $compatibilityReason;
        }

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
        return $this->normalizeMarketplaceUrl($url) !== '';
    }

    private function normalizeMarketplaceUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
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

    private function meetsEnvironmentRequirements(array $plugin): bool
    {
        return $this->getCompatibilityFailureReason($plugin) === '';
    }

    private function getCompatibilityFailureReason(array $plugin): string
    {
        $requiresCms = trim((string) ($plugin['requires_cms'] ?? ''));
        $requiresPhp = trim((string) ($plugin['requires_php'] ?? ''));
        $currentCms = defined('CMS_VERSION')
            ? (string) CMS_VERSION
            : (class_exists('\CMS\\Version') ? (string) \CMS\Version::CURRENT : '0.0.0');

        if ($requiresCms !== '' && version_compare($currentCms, $requiresCms, '<')) {
            return 'Plugin erfordert mindestens 365CMS ' . $requiresCms . '.';
        }

        if ($requiresPhp !== '' && version_compare(PHP_VERSION, $requiresPhp, '<')) {
            return 'Plugin erfordert mindestens PHP ' . $requiresPhp . '.';
        }

        return '';
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
