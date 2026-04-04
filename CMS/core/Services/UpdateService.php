<?php
/**
 * Update Service
 * 
 * Manages system, plugin and theme updates via GitHub API
 * Repository: https://github.com/PS-easyIT/365CMS.DE
 * 
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;
use CMS\Http\Client as HttpClient;
use CMS\Json;
use CMS\PluginManager;
use CMS\ThemeManager;
use CMS\Version;

if (!defined('ABSPATH')) {
    exit;
}

class UpdateService
{
    private const ALLOWED_UPDATE_HOSTS = [
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

    private static ?self $instance = null;
    private Database $db;
    private HttpClient $httpClient;
    
    /** Fallback-Werte – werden von DB-Setting überschrieben, wenn vorhanden */
    private const DEFAULT_GITHUB_REPO = 'PS-easyIT/365CMS.DE';
    private const DEFAULT_GITHUB_API = 'https://api.github.com';
    private const DEFAULT_CORE_UPDATE_URL = 'https://365cms.de/marketplace/core/365cms/update.json';
    private const DEFAULT_PLUGIN_REGISTRY_URL = 'https://365cms.de/marketplace/plugins/index.json';
    private const DEFAULT_THEME_MARKETPLACE_URL = 'https://365cms.de/marketplace/themes';
    private const CACHE_DURATION = 3600; // 1 hour

    /** Aktive Konfiguration (aus DB oder Fallback) */
    private string $githubRepo;
    private string $githubApi;
    private string $coreUpdateUrl;
    
    /**
     * Singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct()
    {
        $this->db = Database::instance();
        $this->httpClient = HttpClient::getInstance();
        $this->loadUpdateConfig();
    }

    /**
     * Load update repo configuration from DB settings (or use defaults)
     */
    private function loadUpdateConfig(): void
    {
        try {
            $row = $this->db->fetchOne(
                "SELECT option_value FROM {$this->db->getPrefix()}settings WHERE option_name = 'update_github_repo'"
            );
            $this->githubRepo = (!empty($row['option_value'])) ? $row['option_value'] : self::DEFAULT_GITHUB_REPO;

            $row2 = $this->db->fetchOne(
                "SELECT option_value FROM {$this->db->getPrefix()}settings WHERE option_name = 'update_github_api'"
            );
            $this->githubApi = (!empty($row2['option_value'])) ? rtrim($row2['option_value'], '/') : self::DEFAULT_GITHUB_API;

            $row3 = $this->db->fetchOne(
                "SELECT option_value FROM {$this->db->getPrefix()}settings WHERE option_name = 'core_update_url'"
            );
            $this->coreUpdateUrl = (!empty($row3['option_value'])) ? trim((string) $row3['option_value']) : self::DEFAULT_CORE_UPDATE_URL;
        } catch (\Throwable $e) {
            $this->githubRepo = self::DEFAULT_GITHUB_REPO;
            $this->githubApi  = self::DEFAULT_GITHUB_API;
            $this->coreUpdateUrl = self::DEFAULT_CORE_UPDATE_URL;
        }
    }
    
    /**
     * Check for CMS core updates via GitHub
     */
    public function checkCoreUpdates(): array
    {
        $currentVersion = CMS_VERSION ?? Version::CURRENT;
        
        // Check cache first
        $cacheKey = 'core_update_check';
        $cached = $this->getCache($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }

        $marketplaceRelease = $this->fetchMarketplaceJson($this->coreUpdateUrl, 512 * 1024);
        if (is_array($marketplaceRelease) && !empty($marketplaceRelease)) {
            $latestVersion = (string) ($marketplaceRelease['version'] ?? $marketplaceRelease['latest_version'] ?? '');

            if ($latestVersion !== '') {
                $result = [
                    'current_version' => $currentVersion,
                    'latest_version' => $latestVersion,
                    'update_available' => version_compare($latestVersion, $currentVersion, '>'),
                    'changelog' => $this->normalizeChangelogText($marketplaceRelease['changelog'] ?? $marketplaceRelease['notes'] ?? ''),
                    'changelog_items' => $this->normalizeChangelogList($marketplaceRelease['changelog'] ?? []),
                    'release_date' => (string) ($marketplaceRelease['released'] ?? $marketplaceRelease['published_at'] ?? ''),
                    'download_url' => $this->resolveAllowedUpdateUrl((string) ($marketplaceRelease['download_url'] ?? '')),
                    'sha256' => $this->resolveIntegrityHashValue($marketplaceRelease),
                    'release_notes' => (string) ($marketplaceRelease['notes'] ?? ''),
                    'purchase_url' => $this->resolveAllowedUpdateUrl((string) ($marketplaceRelease['purchase_url'] ?? '')),
                    'is_paid' => $this->normalizeBooleanValue($marketplaceRelease['is_paid'] ?? false),
                    'price_amount' => (string) ($marketplaceRelease['price_amount'] ?? $marketplaceRelease['price'] ?? ''),
                    'price_currency' => strtoupper((string) ($marketplaceRelease['price_currency'] ?? 'EUR')),
                    'requires_cms' => (string) ($marketplaceRelease['requires_cms'] ?? $marketplaceRelease['min_cms_version'] ?? ''),
                    'requires_php' => (string) ($marketplaceRelease['requires_php'] ?? $marketplaceRelease['min_php'] ?? ''),
                ];

                $this->setCache($cacheKey, $result);

                return $result;
            }
        }
        
        // Fetch latest release from GitHub (konfigurierbar via DB)
        $url = $this->githubApi . '/repos/' . $this->githubRepo . '/releases/latest';
        $release = $this->fetchGitHubData($url);
        
        if (!$release) {
            // No release found, return current version
            return [
                'current_version' => $currentVersion,
                'latest_version' => $currentVersion,
                'update_available' => false,
                'changelog' => [],
                'release_date' => '',
                'download_url' => '',
            ];
        }
        
        $latestVersion = ltrim($release['tag_name'] ?? $currentVersion, 'v');
        $updateAvailable = version_compare($latestVersion, $currentVersion, '>');
        
        $result = [
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'update_available' => $updateAvailable,
            'changelog' => trim((string) ($release['body'] ?? '')),
            'changelog_items' => $this->parseChangelog($release['body'] ?? ''),
            'release_date' => isset($release['published_at']) 
                ? date('Y-m-d', strtotime($release['published_at'])) 
                : '',
            'download_url' => $release['zipball_url'] ?? '',
            'sha256' => $this->resolveIntegrityHashValue($release),
            'release_notes' => $release['body'] ?? '',
        ];
        
        // Cache result
        $this->setCache($cacheKey, $result);
        
        return $result;
    }
    
    /**
     * Check for plugin updates
     */
    public function checkPluginUpdates(): array
    {
        $pluginManager = PluginManager::instance();
        $plugins = $pluginManager->getAvailablePlugins();
        $catalog = $this->loadPluginMarketplaceCatalog();
        
        $updates = [];
        
        foreach ($plugins as $folder => $plugin) {
            $currentVersion = $plugin['version'] ?? '1.0.0';

            $localUpdateData = [];
            $catalogEntry = $catalog[$folder] ?? [];

            $updateInfo = array_replace($plugin, $localUpdateData, $catalogEntry);
            $updateUrl = $this->resolveAllowedUpdateUrl((string) ($updateInfo['update_url'] ?? ''));
            if ($updateUrl !== '') {
                $remoteUpdate = $this->fetchPluginUpdate($updateUrl) ?? [];
                $updateInfo = array_replace($updateInfo, $remoteUpdate);
            }

            $latestVersion = (string) ($updateInfo['version'] ?? '');
            if ($latestVersion === '' || !version_compare($latestVersion, (string) $currentVersion, '>')) {
                continue;
            }

            $downloadUrl = $this->resolveAllowedUpdateUrl((string) ($updateInfo['download_url'] ?? ''));
            $purchaseUrl = $this->resolveAllowedUpdateUrl((string) ($updateInfo['purchase_url'] ?? ''));
            $isPaid = $this->normalizeBooleanValue($updateInfo['is_paid'] ?? false);

            $updates[$folder] = [
                'name' => (string) ($plugin['name'] ?? $catalogEntry['name'] ?? $folder),
                'current_version' => (string) $currentVersion,
                'new_version' => $latestVersion,
                'update_available' => true,
                'download_url' => $downloadUrl,
                'sha256' => $this->resolveIntegrityHashValue($updateInfo),
                'changelog' => $this->normalizeChangelogText($updateInfo['changelog'] ?? $updateInfo['notes'] ?? ''),
                'purchase_url' => $purchaseUrl,
                'is_paid' => $isPaid,
                'price_amount' => (string) ($updateInfo['price_amount'] ?? $updateInfo['price'] ?? ''),
                'price_currency' => strtoupper((string) ($updateInfo['price_currency'] ?? 'EUR')),
                'requires_cms' => (string) ($updateInfo['requires_cms'] ?? $updateInfo['min_cms_version'] ?? ''),
                'requires_php' => (string) ($updateInfo['requires_php'] ?? $updateInfo['min_php'] ?? ''),
                'install_supported' => $downloadUrl !== '',
                'manual_reason' => $this->buildMarketplaceReason($downloadUrl, $purchaseUrl, $isPaid, 'Plugin'),
            ];
        }
        
        return $updates;
    }
    
    /**
     * Check for theme updates
     */
    public function checkThemeUpdates(): array
    {
        $themeSlug = ThemeManager::instance()->getActiveThemeSlug();
        $themeDir = rtrim((string) THEME_PATH, '/\\') . DIRECTORY_SEPARATOR . $themeSlug;
        $themeJsonFile = $themeDir . DIRECTORY_SEPARATOR . 'theme.json';

        if (!file_exists($themeJsonFile)) {
            return [];
        }

        $themeData = Json::decodeArray((string) file_get_contents($themeJsonFile), []);
        $currentVersion = $themeData['version'] ?? '1.0.0';

        $localUpdateData = [];
        $catalog = $this->loadThemeMarketplaceCatalog();
        $catalogEntry = $catalog[$themeSlug] ?? [];

        $updateInfo = array_replace($themeData, $localUpdateData, $catalogEntry);
        $updateUrl = $this->resolveAllowedUpdateUrl((string) ($updateInfo['update_url'] ?? ''));
        if ($updateUrl !== '') {
            $remoteUpdate = $this->fetchPluginUpdate($updateUrl) ?? [];
            $updateInfo = array_replace($updateInfo, $remoteUpdate);
        }

        $latestVersion = (string) ($updateInfo['version'] ?? $currentVersion);
        
        $updateAvailable = version_compare($latestVersion, $currentVersion, '>');
        $downloadUrl = $this->resolveAllowedUpdateUrl((string) ($updateInfo['download_url'] ?? ''));
        $purchaseUrl = $this->resolveAllowedUpdateUrl((string) ($updateInfo['purchase_url'] ?? ''));
        $isPaid = $this->normalizeBooleanValue($updateInfo['is_paid'] ?? false);
        
        return [
            'slug' => $themeSlug,
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'update_available' => $updateAvailable,
            'changelog' => $this->normalizeChangelogText($updateInfo['changelog'] ?? $updateInfo['notes'] ?? ''),
            'download_url' => $downloadUrl,
            'purchase_url' => $purchaseUrl,
            'is_paid' => $isPaid,
            'price_amount' => (string) ($updateInfo['price_amount'] ?? $updateInfo['price'] ?? ''),
            'price_currency' => strtoupper((string) ($updateInfo['price_currency'] ?? 'EUR')),
            'sha256' => $this->resolveIntegrityHashValue($updateInfo),
            'requires_cms' => (string) ($updateInfo['requires_cms'] ?? $updateInfo['min_cms_version'] ?? ''),
            'requires_php' => (string) ($updateInfo['requires_php'] ?? $updateInfo['min_php'] ?? ''),
            'install_supported' => $downloadUrl !== '',
            'manual_reason' => $this->buildMarketplaceReason($downloadUrl, $purchaseUrl, $isPaid, 'Theme'),
        ];
    }

    private function getSettingValue(string $optionName): string
    {
        try {
            $row = $this->db->fetchOne(
                "SELECT option_value FROM {$this->db->getPrefix()}settings WHERE option_name = ?",
                [$optionName]
            );

            return trim((string) ($row['option_value'] ?? ''));
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function fetchMarketplaceJson(string $url, int $maxBytes = 1048576): ?array
    {
        if (!$this->isAllowedSensitiveRemoteUrl($url) || !$this->isSafeExternalUrl($url)) {
            return null;
        }

        $response = $this->httpClient->get($url, [
            'timeout' => 10,
            'connectTimeout' => 5,
            'headers' => ['Accept: application/json'],
            'userAgent' => '365CMS-UpdateChecker/1.0',
            'allowedContentTypes' => ['application/json', 'text/plain'],
            'maxBytes' => $maxBytes,
        ]);

        if (!$response['success']) {
            return null;
        }

        $data = Json::decode((string) ($response['body'] ?? ''), true, null);

        return is_array($data) ? $data : null;
    }

    private function loadPluginMarketplaceCatalog(): array
    {
        $registryUrl = $this->getSettingValue('plugin_registry_url');
        if ($registryUrl === '') {
            $registryUrl = self::DEFAULT_PLUGIN_REGISTRY_URL;
        }

        $data = $this->fetchMarketplaceJson($registryUrl);
        if (!is_array($data)) {
            return [];
        }

        $entries = is_array($data['plugins'] ?? null) ? $data['plugins'] : $data;
        if (!is_array($entries)) {
            return [];
        }

        return $this->normalizePluginCatalogEntries($entries, $this->resolveBaseUrl($registryUrl));
    }

    private function normalizePluginCatalogEntries(array $entries, string $sourceBase): array
    {
        $catalog = [];

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $manifestData = $this->loadCatalogManifestData($entry, $sourceBase);
            $normalized = array_replace($entry, $manifestData);
            $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($normalized['slug'] ?? '')));
            if ($slug === '') {
                continue;
            }

            $normalized['slug'] = $slug;
            $normalized['download_url'] = $this->resolveAllowedUpdateUrl((string) ($normalized['download_url'] ?? $normalized['package_url'] ?? $normalized['archive_url'] ?? ''), $sourceBase);
            $normalized['update_url'] = $this->resolveAllowedUpdateUrl((string) ($normalized['update_url'] ?? ''), $sourceBase);
            $normalized['purchase_url'] = $this->resolveAllowedUpdateUrl((string) ($normalized['purchase_url'] ?? $normalized['buy_url'] ?? $normalized['order_url'] ?? ''), $sourceBase);
            $catalog[$slug] = $normalized;
        }

        return $catalog;
    }

    private function loadThemeMarketplaceCatalog(): array
    {
        $baseUrl = $this->getSettingValue('theme_marketplace_url');
        if ($baseUrl === '') {
            $baseUrl = self::DEFAULT_THEME_MARKETPLACE_URL;
        }

        $catalogUrl = rtrim($baseUrl, '/') . '/index.json';
        $data = $this->fetchMarketplaceJson($catalogUrl);
        if (!is_array($data)) {
            return [];
        }

        return $this->normalizeThemeCatalogEntries($data, rtrim($baseUrl, '/'));
    }

    private function normalizeThemeCatalogEntries(array $data, string $sourceBase): array
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

            $manifestData = $this->loadCatalogManifestData($entry, $sourceBase);
            $normalized = array_replace($entry, $manifestData);
            $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($normalized['slug'] ?? '')));
            if ($slug === '') {
                continue;
            }

            $normalized['slug'] = $slug;
            $normalized['download_url'] = $this->resolveAllowedUpdateUrl((string) ($normalized['download_url'] ?? $normalized['package_url'] ?? $normalized['archive_url'] ?? ''), $sourceBase);
            $normalized['update_url'] = $this->resolveAllowedUpdateUrl((string) ($normalized['update_url'] ?? ''), $sourceBase);
            $normalized['purchase_url'] = $this->resolveAllowedUpdateUrl((string) ($normalized['purchase_url'] ?? $normalized['buy_url'] ?? $normalized['order_url'] ?? ''), $sourceBase);
            $catalog[$slug] = $normalized;
        }

        return $catalog;
    }

    private function loadCatalogManifestData(array $entry, string $sourceBase): array
    {
        $manifest = trim((string) ($entry['manifest'] ?? ''));
        if ($manifest === '') {
            return [];
        }

        $manifestUrl = $this->resolveAllowedUpdateUrl($manifest, $sourceBase);
        if ($manifestUrl !== '') {
            return $this->fetchMarketplaceJson($manifestUrl, 512 * 1024) ?? [];
        }

        return [];
    }

    private function resolveAllowedUpdateUrl(string $value, string $baseUrl = ''): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if ($this->isAllowedSensitiveRemoteUrl($value)) {
            return $value;
        }

        if ($baseUrl !== '' && $this->isAllowedSensitiveRemoteUrl($baseUrl)) {
            $resolved = rtrim($baseUrl, '/') . '/' . ltrim($value, '/');

            return $this->isAllowedSensitiveRemoteUrl($resolved) ? $resolved : '';
        }

        return '';
    }

    private function resolveBaseUrl(string $url): string
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

    private function resolveIntegrityHashValue(array $data): string
    {
        foreach (['sha256', 'checksum_sha256'] as $key) {
            $value = strtolower(trim((string) ($data[$key] ?? '')));
            if ($value !== '' && preg_match('/^[0-9a-f]{64}$/', $value) === 1) {
                return $value;
            }
        }

        return '';
    }

    private function normalizeBooleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'ja', 'paid'], true);
    }

    private function normalizeChangelogText(mixed $value): string
    {
        if (is_string($value)) {
            return trim($value);
        }

        if (is_array($value)) {
            $lines = [];

            foreach ($value as $key => $entry) {
                if (is_string($entry)) {
                    $lines[] = is_string($key) ? $key . ': ' . $entry : $entry;
                }
            }

            return implode("\n", $lines);
        }

        return '';
    }

    private function normalizeChangelogList(mixed $value): array
    {
        if (is_string($value)) {
            return $this->parseChangelog($value);
        }

        if (is_array($value)) {
            $items = [];

            foreach ($value as $key => $entry) {
                if (is_string($entry)) {
                    $items[] = is_string($key) ? $key . ': ' . $entry : $entry;
                }
            }

            return $items;
        }

        return [];
    }

    private function buildMarketplaceReason(string $downloadUrl, string $purchaseUrl, bool $isPaid, string $type): string
    {
        if ($isPaid && $purchaseUrl !== '') {
            return $type . ' ist kostenpflichtig und wird über den Marketplace angefragt oder gekauft.';
        }

        if ($downloadUrl === '') {
            return 'Keine direkte Download-URL im Marketplace hinterlegt.';
        }

        return '';
    }
    
    /**
     * C-11: Fetch data from GitHub API via cURL (ersetzt @file_get_contents)
     *
     * Verwendet cURL mit:
     * - Timeout (Verbindung + Transfer)
     * - TLS-Verifikation (CURLOPT_SSL_VERIFYPEER)
     * - Kein SSRF: nur absolute HTTPS-URLs zu github.com erlaubt
     */
    private function fetchGitHubData(string $url): ?array
    {
        if (!$this->isAllowedGitHubApiUrl($url)) {
            error_log('UpdateService: Invalid GitHub API URL: ' . $url);
            return null;
        }

        if (!$this->isSafeExternalUrl($url)) {
            error_log('UpdateService [L-10]: GitHub API URL blockiert (SSRF-Guard): ' . $url);
            return null;
        }

        $response = $this->httpClient->get($url, [
            'timeout' => 10,
            'connectTimeout' => 5,
            'userAgent' => '365CMS-UpdateChecker/1.0',
            'headers' => ['Accept: application/vnd.github.v3+json'],
            'allowedContentTypes' => ['application/json'],
            'maxBytes' => 2097152,
        ]);

        if (!$response['success']) {
            error_log('UpdateService: HTTP-Fehler bei ' . $url . ': ' . (string) ($response['error'] ?? 'unbekannt'));
            return null;
        }

        $data = Json::decode((string) ($response['body'] ?? ''), true, null);

        if (!is_array($data)) {
            error_log('UpdateService: Ungültiges JSON von GitHub.');
            return null;
        }

        return $data;
    }

    private function isAllowedGitHubApiUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) || !str_starts_with($url, 'https://')) {
            return false;
        }

        $baseUrl = rtrim($this->githubApi, '/');
        if (!filter_var($baseUrl, FILTER_VALIDATE_URL) || !str_starts_with($baseUrl, 'https://')) {
            $baseUrl = self::DEFAULT_GITHUB_API;
        }

        $targetParts = parse_url($url);
        $baseParts = parse_url($baseUrl);

        if (!is_array($targetParts) || !is_array($baseParts)) {
            return false;
        }

        $targetHost = strtolower((string)($targetParts['host'] ?? ''));
        $baseHost = strtolower((string)($baseParts['host'] ?? ''));
        if ($targetHost === '' || $baseHost === '' || $targetHost !== $baseHost) {
            return false;
        }

        $targetScheme = strtolower((string)($targetParts['scheme'] ?? ''));
        $baseScheme = strtolower((string)($baseParts['scheme'] ?? ''));
        if ($targetScheme !== 'https' || $baseScheme !== 'https') {
            return false;
        }

        $targetPort = (int)($targetParts['port'] ?? 443);
        $basePort = (int)($baseParts['port'] ?? 443);
        if ($targetPort !== $basePort) {
            return false;
        }

        $basePath = rtrim((string)($baseParts['path'] ?? ''), '/');
        $targetPath = (string)($targetParts['path'] ?? '');

        if ($basePath !== '' && !str_starts_with($targetPath, $basePath . '/')) {
            return $targetPath === $basePath;
        }

        return true;
    }

    /**
     * M-20: Prüft ob eine IP-Adresse zu privaten / reservierten Bereichen gehört.
     *
     * Abgedeckt:
     *  - Loopback        127.0.0.0/8, ::1
     *  - Link-Local      169.254.0.0/16, fe80::/10
     *  - Private (RFC1918) 10.x, 172.16-31.x, 192.168.x
     *  - Unique Local    fc00::/7
     *  - Multicast       224.0.0.0/4, ff00::/8
     *
     * @param  string $ip  V4 oder V6 Adresse
     * @return bool        true = privat/reserviert, false = öffentlich
     */
    private function isPrivateOrReservedIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    /**
     * M-20: SSRF-Guard – löst Hostname auf und prüft ob die IP privat/reserviert ist.
     *
     * @param  string $url  Vollständige HTTPS-URL
     * @return bool         true = URL ist sicher, false = URL blockiert
     */
    private function isSafeExternalUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (empty($host)) {
            return false;
        }

        $host = strtolower((string) $host);

        if (in_array($host, ['localhost', 'localhost.localdomain', 'ip6-localhost', 'ip6-loopback'], true)) {
            return false;
        }

        $resolvedIps = $this->resolveHostIps($host);
        if ($resolvedIps === []) {
            error_log('UpdateService [M-20]: SSRF-Blockierung – Host "' . $host . '" konnte nicht zuverlässig aufgelöst werden.');
            return false;
        }

        foreach ($resolvedIps as $ip) {
            if ($ip !== '' && $this->isPrivateOrReservedIp($ip)) {
                error_log('UpdateService [M-20]: SSRF-Blockierung – Host "' . $host . '" löst auf private IP auf: ' . $ip);
                return false;
            }
        }

        return true;
    }

    /**
     * @return string[]
     */
    private function resolveHostIps(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = [];

        if (function_exists('dns_get_record')) {
            $records = @dns_get_record($host, DNS_A | DNS_AAAA);
            if (is_array($records)) {
                foreach ($records as $record) {
                    $ip = trim((string) ($record['ip'] ?? $record['ipv6'] ?? ''));
                    if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                        $ips[] = $ip;
                    }
                }
            }
        }

        if ($ips === [] && function_exists('gethostbynamel')) {
            $fallbackRecords = @gethostbynamel($host);
            if (is_array($fallbackRecords)) {
                foreach ($fallbackRecords as $ip) {
                    $ip = trim((string) $ip);
                    if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                        $ips[] = $ip;
                    }
                }
            }
        }

        return array_values(array_unique($ips));
    }

    /**
     * C-11: Fetch plugin update information via cURL (ersetzt @file_get_contents)
     * M-20: SSRF-Guard mit Private-IP-Blockliste
     */
    private function fetchPluginUpdate(string $url): ?array
    {
        if (!$this->isAllowedSensitiveRemoteUrl($url)) {
            error_log('UpdateService: fetchPluginUpdate – URL liegt außerhalb der erlaubten Update-Hosts: ' . $url);
            return null;
        }

        // M-20: Prüfen ob der Hostname auf eine private/reservierte IP auflöst (SSRF-Schutz)
        if (!$this->isSafeExternalUrl($url)) {
            error_log('UpdateService [M-20]: fetchPluginUpdate – URL blockiert (SSRF-Guard): ' . $url);
            return null;
        }

        $response = $this->httpClient->get($url, [
            'timeout' => 10,
            'connectTimeout' => 5,
            'headers' => ['Accept: application/json'],
            'userAgent' => '365CMS-UpdateChecker/1.0',
            'allowedContentTypes' => ['application/json'],
            'maxBytes' => 1048576,
        ]);

        if (!$response['success']) {
            error_log('UpdateService: fetchPluginUpdate HTTP-Fehler: ' . (string) ($response['error'] ?? 'unbekannt'));
            return null;
        }

        $data = Json::decode((string) ($response['body'] ?? ''), true, null);

        return is_array($data) ? $data : null;
    }

    /**
     * C-10: SHA-256-Verifikation für heruntergeladene Dateien (Plugin/Theme)
     *
     * Wird beim Marketplace-Download aufgerufen, sobald eine Datei lokal gespeichert ist.
     * Der erwartete Hash steht im update.json des Anbieters (Feld: sha256).
     *
     * @param  string $filePath     Absoluter Pfad zur heruntergeladenen Datei
     * @param  string $expectedHash Erwarteter SHA-256-Hash (aus update.json/API-Response)
     * @return bool   true = Datei ist integer, false = Hash-Mismatch oder Datei fehlt
     */
    public function verifyDownloadIntegrity(string $filePath, string $expectedHash): bool
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            error_log('UpdateService: verifyDownloadIntegrity – Datei nicht gefunden: ' . $filePath);
            return false;
        }

        if (!preg_match('/^[0-9a-f]{64}$/i', $expectedHash)) {
            error_log('UpdateService: verifyDownloadIntegrity – ungültiges Hash-Format.');
            return false;
        }

        $actualHash = hash_file('sha256', $filePath);

        if ($actualHash === false) {
            error_log('UpdateService: verifyDownloadIntegrity – hash_file() fehlgeschlagen.');
            return false;
        }

        $match = hash_equals(strtolower($expectedHash), $actualHash);

        if (!$match) {
            error_log('UpdateService: SHA-256-Mismatch für ' . basename($filePath)
                . ' – erwartet: ' . $expectedHash . ' – bekommen: ' . $actualHash);
        }

        return $match;
    }
    
    /**
     * Parse changelog from release notes
     */
    private function parseChangelog(string $body): array
    {
        $lines = explode("\n", $body);
        $changelog = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Match lines starting with - or * (bullet points)
            if (preg_match('/^[-*]\s+(.+)/', $line, $matches)) {
                $changelog[] = trim($matches[1]);
            }
        }
        
        return $changelog;
    }
    
    /**
     * Get update history (REAL DATA)
     */
    public function getUpdateHistory(int $limit = 20): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM {$this->db->getPrefix()}settings
                WHERE option_name LIKE 'update_log_%'
                ORDER BY option_name DESC
                LIMIT ?
            ");
            
            if (!$stmt) {
                return [];
            }
            
            $stmt->execute([$limit]);
            
            $history = [];
            while ($row = $stmt->fetch(\PDO::FETCH_OBJ)) {
                $data = Json::decode($row->option_value ?? null, true, null);
                if (is_array($data)) {
                    $history[] = $data;
                }
            }
            
            return $history;
        } catch (\Exception $e) {
            error_log('UpdateService::getUpdateHistory() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * H-19: Plugin/Theme/Core-Update herunterladen und staging-basiert installieren.
     *
     * Ablauf:
     *  1. Download der ZIP-Datei in einen temporären Pfad
     *  2. SHA-256-Prüfsumme verifizieren (Abbruch bei Mismatch)
     *  3. ZIP in ein Staging-Verzeichnis extrahieren und Paketwurzel bestimmen
     *  4. Ziel per atomarem Directory-Swap oder per Rollback-fähigem Inhalts-Swap austauschen
     *  5. Temporäre Artefakte löschen
     *
     * @param  string $downloadUrl  HTTPS-URL zur ZIP-Datei
     * @param  string $sha256       Erwartete SHA-256-Prüfsumme (leer = Warnung, kein Abbruch)
     * @param  string $targetDir    Absoluter Ziel-Pfad (z. B. PLUGIN_PATH . 'my-plugin/')
     * @param  string $type         'plugin' | 'theme' | 'core' für Logging
     * @param  string $name         Name des Pakets für Logging
     * @param  string $version      Neue Versionsnummer für Logging
     * @return array{success: bool, message: string, sha256_verified: bool}
     */
    public function downloadAndInstallUpdate(
        string $downloadUrl,
        string $sha256,
        string $targetDir,
        string $type,
        string $name,
        string $version
    ): array {
        if (!$this->isAllowedSensitiveRemoteUrl($downloadUrl)) {
            return $this->failInstallResult(
                'updates.install.invalid_download_host',
                'Download-Quelle für das Update ist nicht erlaubt.',
                ['type' => $type, 'name' => $name, 'download_url' => $downloadUrl]
            );
        }

        if (!$this->isSafeExternalUrl($downloadUrl)) {
            return $this->failInstallResult(
                'updates.install.unsafe_download_url',
                'Download-Quelle für das Update konnte nicht verifiziert werden.',
                ['type' => $type, 'name' => $name, 'download_url' => $downloadUrl]
            );
        }

        if (!extension_loaded('zip')) {
            return $this->failInstallResult(
                'updates.install.zip_extension_missing',
                'ZIP-Unterstützung ist auf diesem System nicht verfügbar.',
                ['type' => $type, 'name' => $name]
            );
        }

        $targetDir = rtrim($targetDir, '/\\');
        if ($targetDir === '') {
            return $this->failInstallResult(
                'updates.install.invalid_target',
                'Update-Zielverzeichnis ist ungültig.',
                ['type' => $type, 'name' => $name]
            );
        }

        if (!$this->isAllowedInstallTarget($targetDir, $type)) {
            return $this->failInstallResult(
                'updates.install.target_blocked',
                'Update-Ziel liegt außerhalb der erlaubten Installationspfade.',
                ['type' => $type, 'name' => $name, 'target_dir' => $targetDir]
            );
        }

        $targetParentDir = dirname($targetDir);
        if ($targetParentDir === '' || $targetParentDir === '.' || $targetParentDir === DIRECTORY_SEPARATOR) {
            return $this->failInstallResult(
                'updates.install.target_not_stageable',
                'Update-Ziel kann nicht sicher vorbereitet werden.',
                ['type' => $type, 'name' => $name, 'target_dir' => $targetDir]
            );
        }

        if (!is_dir($targetParentDir) && !mkdir($targetParentDir, 0755, true)) {
            return $this->failInstallResult(
                'updates.install.parent_create_failed',
                'Update-Arbeitsverzeichnis konnte nicht vorbereitet werden.',
                ['type' => $type, 'name' => $name, 'target_parent' => $targetParentDir]
            );
        }

        // Temporäre Datei erstellen (eigene .zip-Benennung, kein tempnam-Leak)
        $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR
            . '365cms_upd_' . bin2hex(random_bytes(8)) . '.zip';

        $stagingRoot = $this->createAdjacentTemporaryDirectory($targetParentDir, '365cms_upd_stage_');
        $backupPath = $this->buildAdjacentTemporaryPath($targetParentDir, '365cms_upd_backup_');

        try {
            $response = $this->httpClient->get($downloadUrl, [
                'timeout' => 120,
                'connectTimeout' => 10,
                'headers' => ['Accept: application/zip, application/octet-stream'],
                'userAgent' => '365CMS-Updater/1.0',
                'allowedContentTypes' => ['application/zip', 'application/octet-stream', 'application/x-zip-compressed'],
                'maxBytes' => 268435456,
            ]);

            if (!$response['success']) {
                throw new \RuntimeException('Download-Fehler: ' . (string) ($response['error'] ?? 'unbekannt'));
            }

            $responseBody = (string) ($response['body'] ?? '');
            if ($responseBody === '') {
                throw new \RuntimeException('Update-Download war leer.');
            }

            if (file_put_contents($tmpFile, $responseBody, LOCK_EX) === false) {
                throw new \RuntimeException('Temporäre Datei nicht beschreibbar: ' . $tmpFile);
            }

            // H-19: SHA-256-Verifikation
            $sha256Verified = false;
            if (!empty($sha256)) {
                if (!$this->verifyDownloadIntegrity($tmpFile, $sha256)) {
                    if (file_exists($tmpFile)) { unlink($tmpFile); } // M-03
                    return ['success' => false, 'message' => 'SHA-256-Prüfsumme stimmt nicht überein! Update abgebrochen.', 'sha256_verified' => false];
                }
                $sha256Verified = true;
            } else {
                error_log("UpdateService: Kein SHA-256-Hash für {$name} – Installation ohne Verifikation.");
            }

            // ZIP extrahieren
            if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
                throw new \RuntimeException('Zielverzeichnis konnte nicht erstellt werden: ' . $targetDir);
            }

            $zip = new \ZipArchive();
            $zipResult = $zip->open($tmpFile);
            if ($zipResult !== true) {
                throw new \RuntimeException('ZIP konnte nicht geöffnet werden (Fehlercode: ' . $zipResult . ')');
            }

            if (!$this->validateZipEntries($zip)) {
                $zip->close();
                throw new \RuntimeException('Update-Paket enthält ungültige oder unsichere Pfade.');
            }

            if (!$zip->extractTo($stagingRoot)) {
                $zip->close();
                throw new \RuntimeException('Update-Paket konnte nicht in das Staging-Verzeichnis entpackt werden.');
            }

            $zip->close();

            $installSource = $this->detectExtractedInstallRoot($stagingRoot);
            if (!$this->directoryHasContents($installSource)) {
                throw new \RuntimeException('Update-Paket enthält keine installierbaren Dateien.');
            }

            if ($this->canUseAtomicDirectorySwap($type, $targetDir)) {
                $this->swapDirectoryAtomically($installSource, $targetDir, $backupPath);
            } else {
                $this->swapDirectoryContentsWithRollback($installSource, $targetDir, $backupPath);
            }

            // Temporäre Datei löschen
            if (file_exists($tmpFile)) { unlink($tmpFile); } // M-03

            // Update protokollieren
            $this->logUpdate($type, $name, $version);

            return ['success' => true, 'message' => "Update {$name} v{$version} erfolgreich installiert.", 'sha256_verified' => $sha256Verified];

        } catch (\Throwable $e) {
            if (file_exists($tmpFile)) { unlink($tmpFile); } // M-03
            return $this->failInstallResult(
                'updates.install.failed',
                'Update konnte nicht installiert werden.',
                [
                    'type' => $type,
                    'name' => $name,
                    'version' => $version,
                    'target_dir' => $targetDir,
                    'download_url' => $downloadUrl,
                ],
                $e
            );
        } finally {
            if (is_file($tmpFile)) {
                unlink($tmpFile);
            }

            if (is_dir($stagingRoot)) {
                $this->removeDirectory($stagingRoot);
            }

            if (is_dir($backupPath)) {
                $this->removeDirectory($backupPath);
            }
        }
    }

    /**
     * Log update
     */
    public function logUpdate(string $type, string $name, string $version): bool
    {
        $logEntry = [
            'type' => $type,
            'name' => $name,
            'version' => $version,
            'timestamp' => date('Y-m-d H:i:s'),
            'user' => $_SESSION['user_id'] ?? 'System',
        ];
        
        $optionName = 'update_log_' . time();
        
        try {
            return $this->db->insert('settings', [
                'option_name' => $optionName,
                'option_value' => json_encode($logEntry),
                'autoload' => 0,
            ]) !== false;
        } catch (\Exception $e) {
            error_log('UpdateService::logUpdate() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get system requirements (REAL DATA)
     */
    public function getSystemRequirements(): array
    {
        $requiredPhpVersion = defined('CMS_MIN_PHP_VERSION') ? CMS_MIN_PHP_VERSION : '8.4.0';

        return [
            'php_version' => [
                'required' => $requiredPhpVersion,
                'current' => PHP_VERSION,
                'met' => version_compare(PHP_VERSION, $requiredPhpVersion, '>='),
            ],
            'mysql_version' => [
                'required' => '5.7.0',
                'current' => $this->getMySQLVersion(),
                'met' => version_compare($this->getMySQLVersion(), '5.7.0', '>='),
            ],
            'extensions' => [
                'pdo' => extension_loaded('pdo'),
                'pdo_mysql' => extension_loaded('pdo_mysql'),
                'mbstring' => extension_loaded('mbstring'),
                'json' => extension_loaded('json'),
                'zip' => extension_loaded('zip'),
                'curl' => extension_loaded('curl'),
                'gd' => extension_loaded('gd'),
            ],
            'permissions' => [
                'uploads_writable' => is_writable(ABSPATH . 'uploads'),
                'cache_writable' => is_writable(ABSPATH . 'cache'),
                'logs_writable' => is_writable(ABSPATH . 'logs'),
            ],
        ];
    }
    
    /**
     * Get MySQL version (REAL DATA)
     */
    private function getMySQLVersion(): string
    {
        try {
            $stmt = $this->db->prepare("SELECT VERSION() as version");
            
            if (!$stmt) {
                return 'Unknown';
            }
            
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_OBJ);
            
            if ($result && $result->version) {
                // Extract version number (remove MariaDB suffix etc.)
                preg_match('/^\d+\.\d+\.\d+/', $result->version, $matches);
                return $matches[0] ?? $result->version;
            }
            
            return 'Unknown';
        } catch (\Exception $e) {
            error_log('UpdateService::getMySQLVersion() Error: ' . $e->getMessage());
            return 'Unknown';
        }
    }
    
    /**
     * Get cached data
     */
    private function getCache(string $key): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT cache_value 
                FROM {$this->db->getPrefix()}cache
                WHERE cache_key = ? 
                AND (expires_at IS NULL OR expires_at > NOW())
            ");
            
            if (!$stmt) {
                return null;
            }
            
            $stmt->execute([$key]);
            $result = $stmt->fetch(\PDO::FETCH_OBJ);
            
            if ($result) {
                $data = Json::decode($result->cache_value ?? null, true, null);
                return is_array($data) ? $data : null;
            }
            
            return null;
        } catch (\Exception $e) {
            error_log('UpdateService::getCache() Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Set cache data
     */
    private function setCache(string $key, array $data): bool
    {
        try {
            $expiresAt = date('Y-m-d H:i:s', time() + self::CACHE_DURATION);
            
            // Delete existing cache
            $stmt = $this->db->prepare("DELETE FROM {$this->db->getPrefix()}cache WHERE cache_key = ?");
            if ($stmt) {
                $stmt->execute([$key]);
            }
            
            // Insert new cache
            return $this->db->insert('cache', [
                'cache_key' => $key,
                'cache_value' => json_encode($data),
                'expires_at' => $expiresAt,
            ]) !== false;
        } catch (\Exception $e) {
            error_log('UpdateService::setCache() Error: ' . $e->getMessage());
            return false;
        }
    }

    private function validateZipEntries(\ZipArchive $zip): bool
    {
        $hasEntries = false;

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

            $hasEntries = true;
        }

        return $hasEntries;
    }

    private function createAdjacentTemporaryDirectory(string $parentDir, string $prefix): string
    {
        $path = $this->buildAdjacentTemporaryPath($parentDir, $prefix);

        if (!mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \RuntimeException('Temporäres Update-Verzeichnis konnte nicht erstellt werden: ' . $path);
        }

        return $path;
    }

    private function buildAdjacentTemporaryPath(string $parentDir, string $prefix): string
    {
        $parentDir = rtrim($parentDir, '/\\');

        do {
            $path = $parentDir . DIRECTORY_SEPARATOR . $prefix . bin2hex(random_bytes(8));
        } while (file_exists($path));

        return $path;
    }

    private function detectExtractedInstallRoot(string $stagingRoot): string
    {
        $entries = $this->listDirectoryEntries($stagingRoot);

        if (count($entries) === 1) {
            $candidate = $stagingRoot . DIRECTORY_SEPARATOR . $entries[0];
            if (is_dir($candidate)) {
                return $candidate;
            }
        }

        return $stagingRoot;
    }

    private function directoryHasContents(string $directory): bool
    {
        return $this->listDirectoryEntries($directory) !== [];
    }

    /**
     * @return string[]
     */
    private function listDirectoryEntries(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $entries = scandir($directory);
        if ($entries === false) {
            return [];
        }

        return array_values(array_filter($entries, static fn (string $entry): bool => $entry !== '.' && $entry !== '..'));
    }

    private function canUseAtomicDirectorySwap(string $type, string $targetDir): bool
    {
        if ($type === 'core') {
            return false;
        }

        $absoluteRoot = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) ABSPATH), DIRECTORY_SEPARATOR);
        $normalizedTarget = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $targetDir), DIRECTORY_SEPARATOR);

        return $normalizedTarget !== '' && $normalizedTarget !== $absoluteRoot;
    }

    private function swapDirectoryAtomically(string $sourceDir, string $targetDir, string $backupPath): void
    {
        $targetExists = is_dir($targetDir);
        $backupCreated = false;

        if ($targetExists) {
            if (!rename($targetDir, $backupPath)) {
                throw new \RuntimeException('Bestehendes Zielverzeichnis konnte nicht in das Backup verschoben werden: ' . $targetDir);
            }

            $backupCreated = true;
        }

        if (!rename($sourceDir, $targetDir)) {
            if ($backupCreated && !is_dir($targetDir) && is_dir($backupPath)) {
                rename($backupPath, $targetDir);
            }

            throw new \RuntimeException('Staging-Verzeichnis konnte nicht atomar in das Ziel verschoben werden: ' . $targetDir);
        }

        if ($backupCreated && is_dir($backupPath)) {
            $this->removeDirectory($backupPath);
        }
    }

    private function swapDirectoryContentsWithRollback(string $sourceDir, string $targetDir, string $backupPath): void
    {
        if (!is_dir($sourceDir)) {
            throw new \RuntimeException('Staging-Quelle für das Update fehlt: ' . $sourceDir);
        }

        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
            throw new \RuntimeException('Zielverzeichnis konnte nicht vorbereitet werden: ' . $targetDir);
        }

        if (!mkdir($backupPath, 0755, true) && !is_dir($backupPath)) {
            throw new \RuntimeException('Rollback-Verzeichnis konnte nicht erstellt werden: ' . $backupPath);
        }

        $backedUpEntries = [];
        $movedEntries = [];

        try {
            foreach ($this->listDirectoryEntries($targetDir) as $entry) {
                $from = $targetDir . DIRECTORY_SEPARATOR . $entry;
                $to = $backupPath . DIRECTORY_SEPARATOR . $entry;

                if (!rename($from, $to)) {
                    throw new \RuntimeException('Bestehender Zielinhalt konnte nicht ins Rollback-Verzeichnis verschoben werden: ' . $entry);
                }

                $backedUpEntries[] = $entry;
            }

            foreach ($this->listDirectoryEntries($sourceDir) as $entry) {
                $from = $sourceDir . DIRECTORY_SEPARATOR . $entry;
                $to = $targetDir . DIRECTORY_SEPARATOR . $entry;

                if (!rename($from, $to)) {
                    throw new \RuntimeException('Staging-Inhalt konnte nicht in das Ziel verschoben werden: ' . $entry);
                }

                $movedEntries[] = $entry;
            }
        } catch (\Throwable $e) {
            foreach (array_reverse($movedEntries) as $entry) {
                $current = $targetDir . DIRECTORY_SEPARATOR . $entry;
                $rollback = $sourceDir . DIRECTORY_SEPARATOR . $entry;

                if (file_exists($current)) {
                    @rename($current, $rollback);
                }
            }

            foreach (array_reverse($backedUpEntries) as $entry) {
                $current = $backupPath . DIRECTORY_SEPARATOR . $entry;
                $rollback = $targetDir . DIRECTORY_SEPARATOR . $entry;

                if (file_exists($current)) {
                    @rename($current, $rollback);
                }
            }

            throw $e;
        }

        if (is_dir($backupPath)) {
            $this->removeDirectory($backupPath);
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $entries = scandir($directory);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);
                continue;
            }

            if (file_exists($path) || is_link($path)) {
                unlink($path);
            }
        }

        rmdir($directory);
    }

    private function isAllowedInstallTarget(string $targetDir, string $type): bool
    {
        $targetDir = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $targetDir), DIRECTORY_SEPARATOR);
        if ($targetDir === '') {
            return false;
        }

        if ($type === 'core') {
            $root = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) ABSPATH), DIRECTORY_SEPARATOR);

            return $targetDir === $root && !is_link($targetDir);
        }

        $root = match ($type) {
            'plugin' => defined('PLUGIN_PATH') ? (string) PLUGIN_PATH : (string) ABSPATH . 'plugins' . DIRECTORY_SEPARATOR,
            'theme' => defined('THEME_PATH') ? (string) THEME_PATH : (string) ABSPATH . 'themes' . DIRECTORY_SEPARATOR,
            default => '',
        };

        if ($root === '') {
            return false;
        }

        $normalizedRoot = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $root), DIRECTORY_SEPARATOR);
        if ($normalizedRoot === '') {
            return false;
        }

        if ($targetDir !== $normalizedRoot && !str_starts_with($targetDir, $normalizedRoot . DIRECTORY_SEPARATOR)) {
            return false;
        }

        if (is_link($targetDir)) {
            return false;
        }

        $parentDir = dirname($targetDir);

        return $parentDir !== '' && !is_link($parentDir);
    }

    private function failInstallResult(string $action, string $message, array $context = [], ?\Throwable $exception = null): array
    {
        if ($exception !== null) {
            $context['exception'] = $exception->getMessage();
        }

        \CMS\Logger::instance()->withChannel('updates.service')->error($message, $context);
        \CMS\AuditLogger::instance()->log(
            \CMS\AuditLogger::CAT_SYSTEM,
            $action,
            $message,
            'updates',
            null,
            $context,
            'error'
        );

        return ['success' => false, 'message' => $message . ' Bitte Logs prüfen.', 'sha256_verified' => false];
    }

    private function isAllowedSensitiveRemoteUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) || !str_starts_with($url, 'https://')) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        if (in_array($host, self::ALLOWED_UPDATE_HOSTS, true)) {
            return true;
        }

        return str_ends_with($host, '.githubusercontent.com');
    }
}

