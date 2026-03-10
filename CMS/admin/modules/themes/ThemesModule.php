<?php
declare(strict_types=1);

/**
 * Themes Module – Theme-Verwaltung
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\ThemeManager;

class ThemesModule
{
    private ThemeManager $themeManager;

    public function __construct()
    {
        $this->themeManager = ThemeManager::instance();
    }

    /**
     * Alle Themes laden
     */
    public function getData(): array
    {
        $themes      = $this->themeManager->getAvailableThemes();
        $activeSlug  = $this->themeManager->getActiveThemeSlug();

        // Theme-JSON-Daten anreichern
        foreach ($themes as $slug => &$theme) {
            $jsonPath = THEME_PATH . $slug . '/theme.json';
            if (file_exists($jsonPath)) {
                $json = \CMS\Json::decodeArray(file_get_contents($jsonPath), []);
                if ($json) {
                    $theme['json'] = $json;
                }
            }
            // Screenshot
            $screenshotPath = THEME_PATH . $slug . '/screenshot.png';
            if (file_exists($screenshotPath)) {
                $theme['screenshot'] = '/themes/' . $slug . '/screenshot.png';
            }
            $theme['isActive'] = ($slug === $activeSlug);
        }
        unset($theme);

        return [
            'themes'      => $themes,
            'activeSlug'  => $activeSlug,
            'totalThemes' => count($themes),
        ];
    }

    /**
     * Theme aktivieren
     */
    public function activateTheme(string $slug): array
    {
        if (empty($slug)) {
            return ['success' => false, 'error' => 'Kein Theme angegeben.'];
        }

        $result = $this->themeManager->switchTheme($slug);

        if ($result === true) {
            return ['success' => true, 'message' => 'Theme "' . htmlspecialchars($slug) . '" wurde aktiviert.'];
        }

        return ['success' => false, 'error' => is_string($result) ? $result : 'Fehler beim Aktivieren.'];
    }

    /**
     * Theme löschen
     */
    public function deleteTheme(string $slug): array
    {
        if (empty($slug)) {
            return ['success' => false, 'error' => 'Kein Theme angegeben.'];
        }

        if ($slug === $this->themeManager->getActiveThemeSlug()) {
            return ['success' => false, 'error' => 'Das aktive Theme kann nicht gelöscht werden.'];
        }

        $themes = $this->themeManager->getAvailableThemes();
        if (count($themes) <= 1) {
            return ['success' => false, 'error' => 'Das letzte Theme kann nicht gelöscht werden.'];
        }

        $themeDir = THEME_PATH . basename($slug);
        if (!is_dir($themeDir)) {
            return ['success' => false, 'error' => 'Theme-Verzeichnis nicht gefunden.'];
        }

        $this->deleteDirectory($themeDir);

        return ['success' => true, 'message' => 'Theme "' . htmlspecialchars($slug) . '" wurde gelöscht.'];
    }

    /**
     * Verzeichnis rekursiv löschen
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
