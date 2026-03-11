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
        '365network.de',
        'www.365network.de',
        'api.github.com',
        'codeload.github.com',
        'github.com',
        'objects.githubusercontent.com',
        'raw.githubusercontent.com',
    ];

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
        // index.json aus Plugin-Repository laden
        $registryUrl = '';
        $row = $this->db->get_row(
            "SELECT option_value FROM {$this->prefix}settings WHERE option_name = 'plugin_registry_url'"
        );
        $registryUrl = $row->option_value ?? '';

        // Lokale index.json als Fallback
        $localIndex = defined('PLUGINS_PATH') ? dirname(PLUGINS_PATH) . '/index.json' : '';
        if (empty($registryUrl) && $localIndex && file_exists($localIndex)) {
            $json = \CMS\Json::decodeArray(file_get_contents($localIndex), []);
            return is_array($json) ? ($json['plugins'] ?? $json) : [];
        }

        if (empty($registryUrl)) {
            return $this->getBuiltinCatalog();
        }

        if (!$this->isAllowedMarketplaceUrl($registryUrl)) {
            return $this->getBuiltinCatalog();
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
            return $this->getBuiltinCatalog();
        }

        $data = \CMS\Json::decodeArray($content, []);
        $plugins = is_array($data) ? ($data['plugins'] ?? $data) : [];

        return $this->sanitizeCatalogEntries(is_array($plugins) ? $plugins : []);
    }

    private function getInstalledSlugs(): array
    {
        $pluginsDir = defined('PLUGINS_PATH') ? PLUGINS_PATH : (defined('ABSPATH') ? ABSPATH . 'plugins/' : '');
        $slugs = [];
        if (is_dir($pluginsDir)) {
            foreach (new \DirectoryIterator($pluginsDir) as $item) {
                if ($item->isDot() || !$item->isDir()) continue;
                $slugs[] = $item->getFilename();
            }
        }
        return $slugs;
    }

    private function getBuiltinCatalog(): array
    {
        return [
            ['slug' => 'cms-contact', 'name' => 'CMS Contact', 'description' => 'Kontaktformulare & Anfragen-Verwaltung', 'version' => '1.0.0', 'author' => '365 Network', 'category' => 'Kommunikation'],
            ['slug' => 'cms-events', 'name' => 'CMS Events', 'description' => 'Events & Veranstaltungen verwalten', 'version' => '1.0.0', 'author' => '365 Network', 'category' => 'Inhalte'],
            ['slug' => 'cms-companies', 'name' => 'CMS Companies', 'description' => 'Firmenverzeichnis & -profile', 'version' => '1.0.0', 'author' => '365 Network', 'category' => 'Verzeichnis'],
            ['slug' => 'cms-experts', 'name' => 'CMS Experts', 'description' => 'Experten-Profile & -verzeichnis', 'version' => '1.0.0', 'author' => '365 Network', 'category' => 'Verzeichnis'],
            ['slug' => 'cms-speakers', 'name' => 'CMS Speakers', 'description' => 'Referenten-Verwaltung', 'version' => '1.0.0', 'author' => '365 Network', 'category' => 'Verzeichnis'],
            ['slug' => 'cms-booking', 'name' => 'CMS Booking', 'description' => 'Buchungssystem für Events & Termine', 'version' => '1.0.0', 'author' => '365 Network', 'category' => 'E-Commerce'],
            ['slug' => 'cms-newsletter', 'name' => 'CMS Newsletter', 'description' => 'Newsletter- & E-Mail-Kampagnen', 'version' => '1.0.0', 'author' => '365 Network', 'category' => 'Kommunikation'],
            ['slug' => 'cms-forum', 'name' => 'CMS Forum', 'description' => 'Community-Forum', 'version' => '1.0.0', 'author' => '365 Network', 'category' => 'Community'],
            ['slug' => 'cms-feed', 'name' => 'CMS Feed', 'description' => 'RSS-Feeds & Social-Stream', 'version' => '1.0.0', 'author' => '365 Network', 'category' => 'Inhalte'],
            ['slug' => 'cms-marketplace', 'name' => 'CMS Marketplace', 'description' => 'Marktplatz für Produkte & Dienstleistungen', 'version' => '1.0.0', 'author' => '365 Network', 'category' => 'E-Commerce'],
        ];
    }

    private function sanitizeCatalogEntries(array $entries): array
    {
        $sanitized = [];

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $downloadUrl = trim((string) ($entry['download_url'] ?? ''));
            if ($downloadUrl !== '' && !$this->isAllowedMarketplaceUrl($downloadUrl)) {
                $entry['download_url'] = '';
            }

            $entry['slug'] = preg_replace('/[^a-z0-9_-]/', '', strtolower((string)($entry['slug'] ?? '')));
            $entry['name'] = (string)($entry['name'] ?? $entry['slug'] ?? '');
            $entry['description'] = (string)($entry['description'] ?? '');
            $entry['version'] = (string)($entry['version'] ?? '');
            $entry['author'] = (string)($entry['author'] ?? '');
            $entry['category'] = (string)($entry['category'] ?? '');
            $entry['sha256'] = $this->resolveIntegrityHash($entry);

            $sanitized[] = $entry;
        }

        return $sanitized;
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
}
