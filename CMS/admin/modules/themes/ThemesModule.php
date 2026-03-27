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

use CMS\Logger;
use CMS\ThemeManager;

class ThemesModule
{
    private Logger $logger;
    private ThemeManager $themeManager;

    public function __construct()
    {
        $this->logger = Logger::instance()->withChannel('admin.themes');
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
        $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug) ?? '';
        if ($slug === '') {
            return ['success' => false, 'error' => 'Kein Theme angegeben.'];
        }

        $result = $this->themeManager->switchTheme($slug);

        if ($result === true) {
            return ['success' => true, 'message' => 'Theme "' . $slug . '" wurde aktiviert.'];
        }

        if (!is_string($result)) {
            $this->logger->warning('Theme-Aktivierung lieferte ein unerwartetes Ergebnis.', [
                'slug' => $slug,
                'result_type' => get_debug_type($result),
            ]);
        }

        return ['success' => false, 'error' => is_string($result) ? $result : 'Fehler beim Aktivieren.'];
    }

    /**
     * Theme löschen
     */
    public function deleteTheme(string $slug): array
    {
        $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug) ?? '';
        if ($slug === '') {
            return ['success' => false, 'error' => 'Kein Theme angegeben.'];
        }

        $result = $this->themeManager->deleteTheme($slug);
        if ($result !== true) {
            if (!is_string($result)) {
                $this->logger->warning('Theme-Löschung lieferte ein unerwartetes Ergebnis.', [
                    'slug' => $slug,
                    'result_type' => get_debug_type($result),
                ]);
            }

            return ['success' => false, 'error' => is_string($result) ? $result : 'Theme konnte nicht gelöscht werden.'];
        }

        return ['success' => true, 'message' => 'Theme "' . $slug . '" wurde gelöscht.'];
    }
}
