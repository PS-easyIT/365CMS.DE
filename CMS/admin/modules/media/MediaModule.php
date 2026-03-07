<?php
declare(strict_types=1);

/**
 * Media Module – Medienverwaltung (Bibliothek, Kategorien, Einstellungen)
 *
 * Nutzt CMS\Services\MediaService für alle Operationen.
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Services\MediaService;

class MediaModule
{
    private MediaService $service;

    public function __construct()
    {
        $this->service = MediaService::getInstance();
    }

    // ─── Bibliothek ──────────────────────────────────────

    /**
     * Daten für die Medien-Bibliothek
     */
    public function getLibraryData(): array
    {
        $path     = trim($_GET['path'] ?? '');
        $category = trim($_GET['category'] ?? '');

        $items = $this->service->getItems($path);
        if ($items instanceof \WP_Error) {
            $items = ['folders' => [], 'files' => []];
        }

        // Kategorie-Filter anwenden
        if ($category !== '' && !empty($items['files'])) {
            $items['files'] = array_filter($items['files'], function ($f) use ($category) {
                return ($f['category'] ?? '') === $category;
            });
            $items['files'] = array_values($items['files']);
        }

        $categories = $this->service->getCategories();
        $diskUsage  = $this->service->getDiskUsage();

        return [
            'folders'    => $items['folders'] ?? [],
            'files'      => $items['files'] ?? [],
            'categories' => $categories,
            'diskUsage'  => $diskUsage,
            'path'       => $path,
            'category'   => $category,
        ];
    }

    /**
     * Ordner erstellen
     */
    public function createFolder(string $name, string $parentPath): array
    {
        $result = $this->service->createFolder($name, $parentPath);
        if ($result instanceof \WP_Error) {
            return ['success' => false, 'error' => $result->get_error_message()];
        }
        return ['success' => true, 'message' => 'Ordner erstellt.'];
    }

    /**
     * Datei hochladen
     */
    public function uploadFile(array $file, string $targetPath): array
    {
        $result = $this->service->uploadFile($file, $targetPath);
        if ($result instanceof \WP_Error) {
            return ['success' => false, 'error' => $result->get_error_message()];
        }
        return ['success' => true, 'message' => 'Datei hochgeladen.'];
    }

    /**
     * Datei/Ordner löschen
     */
    public function deleteItem(string $path): array
    {
        $result = $this->service->deleteItem($path);
        if ($result instanceof \WP_Error) {
            return ['success' => false, 'error' => $result->get_error_message()];
        }
        return ['success' => true, 'message' => 'Element gelöscht.'];
    }

    /**
     * Datei/Ordner umbenennen
     */
    public function renameItem(string $oldPath, string $newName): array
    {
        $result = $this->service->renameItem($oldPath, $newName);
        if ($result instanceof \WP_Error) {
            return ['success' => false, 'error' => $result->get_error_message()];
        }
        return ['success' => true, 'message' => 'Element umbenannt.'];
    }

    /**
     * Kategorie zuweisen
     */
    public function assignCategory(string $filePath, string $categorySlug): array
    {
        $result = $this->service->assignCategory($filePath, $categorySlug);
        if ($result instanceof \WP_Error) {
            return ['success' => false, 'error' => $result->get_error_message()];
        }
        return ['success' => true, 'message' => 'Kategorie zugewiesen.'];
    }

    // ─── Kategorien ──────────────────────────────────────

    /**
     * Kategorien-Übersicht
     */
    public function getCategoriesData(): array
    {
        return [
            'categories' => $this->service->getCategories(),
        ];
    }

    /**
     * Kategorie hinzufügen
     */
    public function addCategory(string $name, string $slug = ''): array
    {
        $result = $this->service->addCategory($name, $slug);
        if ($result instanceof \WP_Error) {
            return ['success' => false, 'error' => $result->get_error_message()];
        }
        return ['success' => true, 'message' => 'Kategorie erstellt.'];
    }

    /**
     * Kategorie löschen
     */
    public function deleteCategory(string $slug): array
    {
        $result = $this->service->deleteCategory($slug);
        if ($result instanceof \WP_Error) {
            return ['success' => false, 'error' => $result->get_error_message()];
        }
        return ['success' => true, 'message' => 'Kategorie gelöscht.'];
    }

    // ─── Einstellungen ───────────────────────────────────

    /**
     * Aktuelle Einstellungen laden
     */
    public function getSettingsData(): array
    {
        return [
            'settings'  => $this->service->getSettings(),
            'diskUsage' => $this->service->getDiskUsage(),
        ];
    }

    /**
     * Einstellungen speichern
     */
    public function saveSettings(array $input): array
    {
        $settings = $this->service->getSettings();

        // Strings
        foreach (['max_upload_size', 'member_max_upload_size'] as $key) {
            if (isset($input[$key])) {
                $settings[$key] = trim($input[$key]);
            }
        }

        // Integers
        foreach (['jpeg_quality', 'max_width', 'max_height', 'thumbnail_small_w', 'thumbnail_small_h', 'thumbnail_medium_w', 'thumbnail_medium_h', 'thumbnail_large_w', 'thumbnail_large_h', 'thumbnail_banner_w', 'thumbnail_banner_h'] as $key) {
            if (isset($input[$key])) {
                $settings[$key] = (int)$input[$key];
            }
        }

        // Booleans
        foreach (['auto_webp', 'strip_exif', 'organize_month_year', 'sanitize_filename', 'unique_filename', 'lowercase_filename', 'member_uploads_enabled', 'member_delete_own', 'generate_thumbnails', 'block_dangerous_types', 'validate_image_content', 'require_login_for_upload', 'protect_uploads_dir'] as $key) {
            $settings[$key] = isset($input[$key]);
        }

        // Arrays
        $settings['allowed_types']        = $input['allowed_types'] ?? ['image'];
        $settings['member_allowed_types'] = $input['member_allowed_types'] ?? ['image'];

        $result = $this->service->saveSettings($settings);
        if ($result instanceof \WP_Error) {
            return ['success' => false, 'error' => $result->get_error_message()];
        }
        return ['success' => true, 'message' => 'Einstellungen gespeichert.'];
    }
}
