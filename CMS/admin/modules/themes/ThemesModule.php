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

        foreach ($themes as $slug => &$theme) {
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

        $result = $this->themeManager->deleteTheme($slug);
        if ($result !== true) {
            return ['success' => false, 'error' => is_string($result) ? $result : 'Theme konnte nicht gelöscht werden.'];
        }

        return ['success' => true, 'message' => 'Theme "' . htmlspecialchars($slug) . '" wurde gelöscht.'];
    }
}
