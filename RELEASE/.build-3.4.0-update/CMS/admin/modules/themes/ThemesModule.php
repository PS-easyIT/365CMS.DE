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

    /** @return array<string, mixed>|null */
    private function findManagedTheme(string $slug): ?array
    {
        $normalizedSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug) ?? '';
        if ($normalizedSlug === '') {
            return null;
        }

        foreach ($this->themeManager->getAvailableThemes() as $folder => $theme) {
            $normalizedFolder = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $folder) ?? '';
            if ($normalizedFolder !== $normalizedSlug) {
                continue;
            }

            if (!is_array($theme)) {
                $theme = [];
            }

            $theme['folder'] = (string) ($theme['folder'] ?? $folder);

            return $theme;
        }

        return null;
    }

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

        $theme = $this->findManagedTheme($slug);
        if ($theme === null) {
            return ['success' => false, 'error' => 'Theme ist nicht mehr verfügbar. Bitte Liste aktualisieren.'];
        }

        $folder = (string) ($theme['folder'] ?? $slug);

        $result = $this->themeManager->switchTheme($folder);

        if ($result === true) {
            return ['success' => true, 'message' => 'Theme "' . $folder . '" wurde aktiviert.'];
        }

        if (!is_string($result)) {
            $this->logger->warning('Theme-Aktivierung lieferte ein unerwartetes Ergebnis.', [
                'slug' => $folder,
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

        $theme = $this->findManagedTheme($slug);
        if ($theme === null) {
            return ['success' => false, 'error' => 'Theme ist nicht mehr verfügbar. Bitte Liste aktualisieren.'];
        }

        $folder = (string) ($theme['folder'] ?? $slug);

        $result = $this->themeManager->deleteTheme($folder);
        if ($result !== true) {
            if (!is_string($result)) {
                $this->logger->warning('Theme-Löschung lieferte ein unerwartetes Ergebnis.', [
                    'slug' => $folder,
                    'result_type' => get_debug_type($result),
                ]);
            }

            return ['success' => false, 'error' => is_string($result) ? $result : 'Theme konnte nicht gelöscht werden.'];
        }

        return ['success' => true, 'message' => 'Theme "' . $folder . '" wurde gelöscht.'];
    }
}
