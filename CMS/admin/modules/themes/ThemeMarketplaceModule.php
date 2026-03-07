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

use CMS\ThemeManager;
use CMS\Database;

class ThemeMarketplaceModule
{
    private ThemeManager $themeManager;

    public function __construct()
    {
        $this->themeManager = ThemeManager::instance();
    }

    /**
     * Marketplace-Daten laden
     */
    public function getData(): array
    {
        $installed = $this->themeManager->getAvailableThemes();
        $catalog   = $this->getCatalog();

        // Installationsstatus anreichern
        foreach ($catalog as &$theme) {
            $slug = $theme['slug'] ?? '';
            $theme['installed'] = isset($installed[$slug]);
            $theme['active']    = ($slug === $this->themeManager->getActiveThemeSlug());
            // Versions-Vergleich
            if ($theme['installed'] && !empty($installed[$slug]['version']) && !empty($theme['version'])) {
                $theme['updateAvailable'] = version_compare($theme['version'], $installed[$slug]['version'], '>');
            } else {
                $theme['updateAvailable'] = false;
            }
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
        if (empty($slug)) {
            return ['success' => false, 'error' => 'Kein Theme angegeben.'];
        }

        // Prüfe Marketplace-URL in Settings
        $repoUrl = $this->getMarketplaceUrl();
        if (empty($repoUrl)) {
            return ['success' => false, 'error' => 'Marketplace-URL nicht konfiguriert.'];
        }

        // Theme bereits installiert?
        $themePath = THEME_PATH . basename($slug);
        if (is_dir($themePath)) {
            return ['success' => false, 'error' => 'Theme ist bereits installiert.'];
        }

        // Download-URL zusammenbauen
        $downloadUrl = rtrim($repoUrl, '/') . '/' . $slug . '/';

        // Versuche theme.json herunterzuladen um Theme zu validieren
        $ctx = stream_context_create([
            'http' => ['timeout' => 10, 'user_agent' => '365CMS Theme Installer'],
        ]);

        $themeJson = @file_get_contents($downloadUrl . 'theme.json', false, $ctx);
        if ($themeJson === false) {
            return ['success' => false, 'error' => 'Theme konnte nicht heruntergeladen werden.'];
        }

        $json = json_decode($themeJson, true);
        if (!$json || empty($json['slug'])) {
            return ['success' => false, 'error' => 'Ungültige theme.json.'];
        }

        // TODO: Vollständiger Download+Entpack-Prozess (ZIP basiert)
        // Aktuell nur Platzhalter – realer Download benötigt ZIP-Handling
        return ['success' => false, 'error' => 'Automatische Installation wird in einer zukünftigen Version verfügbar sein. Bitte lade das Theme manuell ins Verzeichnis /themes/' . htmlspecialchars($slug) . '/ hoch.'];
    }

    /**
     * Theme-Katalog aus index.json laden
     */
    private function getCatalog(): array
    {
        // Lokal verfügbare index.json (Theme-Repo)
        $indexPath = THEME_PATH . '../index.json';
        if (file_exists($indexPath)) {
            $json = json_decode(file_get_contents($indexPath), true);
            if (is_array($json)) {
                return $json;
            }
        }

        // Alternativ: index.json direkt im Theme-Verzeichnis
        $indexPath2 = THEME_PATH . 'index.json';
        if (file_exists($indexPath2)) {
            $json = json_decode(file_get_contents($indexPath2), true);
            if (is_array($json)) {
                return $json;
            }
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
}
