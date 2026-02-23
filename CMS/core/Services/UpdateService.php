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
use CMS\PluginManager;

if (!defined('ABSPATH')) {
    exit;
}

class UpdateService
{
    private static ?self $instance = null;
    private Database $db;
    
    private const GITHUB_REPO = 'PS-easyIT/365CMS.DE';
    private const GITHUB_API = 'https://api.github.com';
    private const CACHE_DURATION = 3600; // 1 hour
    
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
    }
    
    /**
     * Check for CMS core updates via GitHub
     */
    public function checkCoreUpdates(): array
    {
        $currentVersion = CMS_VERSION ?? '2.0.0';
        
        // Check cache first
        $cacheKey = 'core_update_check';
        $cached = $this->getCache($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        // Fetch latest release from GitHub
        $url = self::GITHUB_API . '/repos/' . self::GITHUB_REPO . '/releases/latest';
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
            'changelog' => $this->parseChangelog($release['body'] ?? ''),
            'release_date' => isset($release['published_at']) 
                ? date('Y-m-d', strtotime($release['published_at'])) 
                : '',
            'download_url' => $release['zipball_url'] ?? '',
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
        
        $updates = [];
        
        foreach ($plugins as $folder => $plugin) {
            $currentVersion = $plugin['version'] ?? '1.0.0';
            
            // Check if plugin has update URL in metadata
            if (isset($plugin['update_url']) && !empty($plugin['update_url'])) {
                $updateInfo = $this->fetchPluginUpdate($plugin['update_url']);
                
                if ($updateInfo && version_compare($updateInfo['version'], $currentVersion, '>')) {
                    $updates[$folder] = [
                        'name' => $plugin['name'],
                        'current_version' => $currentVersion,
                        'new_version' => $updateInfo['version'],
                        'update_available' => true,
                        'download_url' => $updateInfo['download_url'] ?? '',
                        // H-19: SHA-256-Prüfsumme aus update-Metadaten mitliefern
                        'sha256' => $updateInfo['sha256'] ?? $updateInfo['checksum_sha256'] ?? '',
                        'changelog' => $updateInfo['changelog'] ?? '',
                    ];
                }
            }
        }
        
        return $updates;
    }
    
    /**
     * Check for theme updates
     */
    public function checkThemeUpdates(): array
    {
        // Get current theme
        $themeDir = ABSPATH . 'themes/default';
        $themeJsonFile = $themeDir . '/theme.json';
        
        if (!file_exists($themeJsonFile)) {
            return [];
        }
        
        $themeData = json_decode(file_get_contents($themeJsonFile), true);
        $currentVersion = $themeData['version'] ?? '1.0.0';
        
        // Check GitHub for theme updates
        $url = self::GITHUB_API . '/repos/' . self::GITHUB_REPO . '/contents/themes/default/theme.json';
        $response = $this->fetchGitHubData($url);
        
        if (!$response || !isset($response['content'])) {
            return [];
        }
        
        // Decode base64 content
        $remoteThemeData = json_decode(base64_decode($response['content']), true);
        $latestVersion = $remoteThemeData['version'] ?? $currentVersion;
        
        $updateAvailable = version_compare($latestVersion, $currentVersion, '>');
        
        return [
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'update_available' => $updateAvailable,
            'changelog' => $remoteThemeData['changelog'] ?? [],
        ];
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
<<<<<<< HEAD
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: 365CMS-UpdateChecker/1.0',
                    'Accept: application/vnd.github.v3+json',
                ],
                'timeout' => 10,
            ],
        ]);
<<<<<<< Updated upstream
        
        try {
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                error_log('UpdateService: Failed to fetch from GitHub: ' . $url);
                return null;
=======

        $response = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlError !== '') {
            error_log('UpdateService: cURL-Fehler bei ' . $url . ': ' . $curlError);
            return null;
        }

        if ($httpCode !== 200) {
            error_log('UpdateService: HTTP ' . $httpCode . ' bei ' . $url);
            return null;
        }

        $data = json_decode((string) $response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('UpdateService: Ungültiges JSON von GitHub: ' . json_last_error_msg());
            return null;
        }

        return $data;
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

        // Alle DNS-Auflösungen prüfen (IPv4 + IPv6)
        $records = dns_get_record($host, DNS_A | DNS_AAAA) ?: [];
        if (empty($records)) {
            // DNS-Auflösung fehlgeschlagen → nicht blockieren (könnte Netzwerkproblem sein)
            // Aber statisch offensichtliche private Hostnamen blocken:
            if (in_array(strtolower($host), ['localhost', 'localhost.localdomain', 'ip6-localhost', 'ip6-loopback'], true)) {
                return false;
>>>>>>> Stashed changes
            }
            
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('UpdateService: Invalid JSON from GitHub: ' . json_last_error_msg());
                return null;
            }
            
            return $data;
        } catch (\Exception $e) {
            error_log('UpdateService::fetchGitHubData() Error: ' . $e->getMessage());
=======
        // SSRF-Guard: nur HTTPS-Calls zur GitHub-API erlaubt
        if (!str_starts_with($url, 'https://api.github.com/')) {
            error_log('UpdateService: Ungültige URL blockiert: ' . $url);
>>>>>>> 99c076b264547ca37d9fb41c77632a2247e7247a
            return null;
        }

        if (!extension_loaded('curl')) {
            error_log('UpdateService: cURL-Extension nicht verfügbar.');
            return null;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,        // kein Redirect-Follow (SSRF-Schutz)
            CURLOPT_CONNECTTIMEOUT => 5,            // 5 s Verbindungsaufbau
            CURLOPT_TIMEOUT        => 10,           // 10 s Gesamt-Transfer
            CURLOPT_SSL_VERIFYPEER => true,         // TLS-Zertifikat prüfen
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => [
                'User-Agent: 365CMS-UpdateChecker/1.0',
                'Accept: application/vnd.github.v3+json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlError !== '') {
            error_log('UpdateService: cURL-Fehler bei ' . $url . ': ' . $curlError);
            return null;
        }

        if ($httpCode !== 200) {
            error_log('UpdateService: HTTP ' . $httpCode . ' bei ' . $url);
            return null;
        }

        $data = json_decode((string) $response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('UpdateService: Ungültiges JSON von GitHub: ' . json_last_error_msg());
            return null;
        }

        return $data;
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

        // Alle DNS-Auflösungen prüfen (IPv4 + IPv6)
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if (empty($records)) {
            // DNS-Auflösung fehlgeschlagen → nicht blockieren (könnte Netzwerkproblem sein)
            // Aber statisch offensichtliche private Hostnamen blocken:
            if (in_array(strtolower($host), ['localhost', 'localhost.localdomain', 'ip6-localhost', 'ip6-loopback'], true)) {
                return false;
            }
            return true;
        }

        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? '';
            if ($ip !== '' && $this->isPrivateOrReservedIp($ip)) {
                error_log('UpdateService [M-20]: SSRF-Blockierung – Host "' . $host . '" löst auf private IP auf: ' . $ip);
                return false;
            }
        }

        return true;
    }

    /**
     * C-11: Fetch plugin update information via cURL (ersetzt @file_get_contents)
     * M-20: SSRF-Guard mit Private-IP-Blockliste
     */
    private function fetchPluginUpdate(string $url): ?array
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) || !str_starts_with($url, 'https://')) {
            error_log('UpdateService: fetchPluginUpdate – URL nicht HTTPS oder ungültig: ' . $url);
            return null;
        }

        // M-20: Prüfen ob der Hostname auf eine private/reservierte IP auflöst (SSRF-Schutz)
        if (!$this->isSafeExternalUrl($url)) {
            error_log('UpdateService [M-20]: fetchPluginUpdate – URL blockiert (SSRF-Guard): ' . $url);
            return null;
        }

        if (!extension_loaded('curl')) {
            error_log('UpdateService: cURL-Extension nicht verfügbar.');
            return null;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => [
                'User-Agent: 365CMS-UpdateChecker/1.0',
            ],
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlError !== '') {
            error_log('UpdateService: fetchPluginUpdate cURL-Fehler: ' . $curlError);
            return null;
        }

        return json_decode((string) $response, true) ?: null;
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
                $data = json_decode($row->option_value, true);
                if ($data) {
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
     * H-19: Plugin/Theme-Update herunterladen und nach SHA-256-Verifikation installieren.
     *
     * Ablauf:
     *  1. Download der ZIP-Datei in einen temporären Pfad
     *  2. SHA-256-Prüfsumme verifizieren (Abbruch bei Mismatch)
     *  3. ZIP in Zielverzeichnis extrahieren
     *  4. Temporäre Datei löschen
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
        // SSRF-Guard: nur HTTPS
        if (!str_starts_with($downloadUrl, 'https://')) {
            return ['success' => false, 'message' => 'Ungültige Download-URL: nur HTTPS erlaubt.', 'sha256_verified' => false];
        }

        if (!extension_loaded('curl')) {
            return ['success' => false, 'message' => 'cURL ist nicht verfügbar.', 'sha256_verified' => false];
        }

        if (!extension_loaded('zip')) {
            return ['success' => false, 'message' => 'ZIP-Extension ist nicht verfügbar.', 'sha256_verified' => false];
        }

        // Temporäre Datei erstellen (eigene .zip-Benennung, kein tempnam-Leak)
        $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR
            . '365cms_upd_' . bin2hex(random_bytes(8)) . '.zip';

        try {
            // Download via cURL
            $fp = fopen($tmpFile, 'wb');
            if ($fp === false) {
                throw new \RuntimeException('Temporäre Datei nicht beschreibbar: ' . $tmpFile);
            }

            $ch = curl_init($downloadUrl);
            curl_setopt_array($ch, [
                CURLOPT_FILE           => $fp,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT        => 120,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER     => ['User-Agent: 365CMS-Updater/1.0'],
            ]);

            $curlOk   = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);
            fclose($fp);

            if (!$curlOk || $curlErr !== '') {
                throw new \RuntimeException('Download-Fehler: ' . $curlErr);
            }
            if ($httpCode !== 200) {
                throw new \RuntimeException("Download fehlgeschlagen: HTTP {$httpCode}");
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
            $zip->extractTo($targetDir);
            $zip->close();

            // Temporäre Datei löschen
            if (file_exists($tmpFile)) { unlink($tmpFile); } // M-03

            // Update protokollieren
            $this->logUpdate($type, $name, $version);

            return ['success' => true, 'message' => "Update {$name} v{$version} erfolgreich installiert.", 'sha256_verified' => $sha256Verified];

        } catch (\Throwable $e) {
            if (file_exists($tmpFile)) { unlink($tmpFile); } // M-03
            error_log('UpdateService::downloadAndInstallUpdate() Fehler: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Fehler: ' . $e->getMessage(), 'sha256_verified' => false];
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
        return [
            'php_version' => [
                'required' => '8.1.0',
                'current' => PHP_VERSION,
                'met' => version_compare(PHP_VERSION, '8.1.0', '>='),
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
                return json_decode($result->cache_value, true);
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
}

