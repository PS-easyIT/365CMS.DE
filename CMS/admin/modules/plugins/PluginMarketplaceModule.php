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
        }
        unset($plugin);

        return [
            'plugins'   => $available,
            'installed' => $installed,
            'stats'     => [
                'available'   => count($available),
                'installed'   => count($installed),
                'installable' => count(array_filter($available, fn($p) => !$p['installed'])),
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
        if (empty($downloadUrl)) {
            return ['success' => false, 'error' => 'Keine Download-URL verfügbar. Plugin muss manuell installiert werden.'];
        }

        // URL-Validierung (nur HTTPS erlaubt, keine lokalen Adressen)
        if (!filter_var($downloadUrl, FILTER_VALIDATE_URL) || !str_starts_with($downloadUrl, 'https://')) {
            return ['success' => false, 'error' => 'Ungültige oder unsichere Download-URL.'];
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

        // ZIP entpacken
        $zip = new \ZipArchive();
        if ($zip->open($tmpFile) !== true) {
            unlink($tmpFile);
            return ['success' => false, 'error' => 'ZIP-Datei konnte nicht geöffnet werden.'];
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
        return is_array($data) ? ($data['plugins'] ?? $data) : [];
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
}
