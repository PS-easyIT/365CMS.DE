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

    /**
     * @return array<string, array<int, string>>
     */
    private function getTypeMap(): array
    {
        return [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'ico'],
            'video' => ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'],
            'audio' => ['mp3', 'wav', 'aac', 'flac', 'm4a'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf', 'csv'],
            'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
        ];
    }

    /**
     * @param array<int, string> $groups
     * @return array<int, string>
     */
    private function expandTypeGroups(array $groups): array
    {
        $typeMap = $this->getTypeMap();
        $extensions = [];

        foreach ($groups as $group) {
            foreach ($typeMap[$group] ?? [] as $extension) {
                $extensions[$extension] = true;
            }
        }

        return array_keys($extensions);
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function buildSettingsViewModel(array $settings): array
    {
        return array_merge($settings, [
            'allowed_types' => $this->expandTypeGroups(array_map('strval', (array) ($settings['allowed_types'] ?? []))),
            'member_allowed_types' => $this->expandTypeGroups(array_map('strval', (array) ($settings['member_allowed_types'] ?? []))),
            'sanitize_filename' => (bool) ($settings['sanitize_filenames'] ?? false),
            'unique_filename' => (bool) ($settings['unique_filenames'] ?? false),
            'lowercase_filename' => (bool) ($settings['lowercase_filenames'] ?? false),
            'thumbnail_small_w' => (int) ($settings['thumb_small_w'] ?? 150),
            'thumbnail_small_h' => (int) ($settings['thumb_small_h'] ?? 150),
            'thumbnail_medium_w' => (int) ($settings['thumb_medium_w'] ?? 300),
            'thumbnail_medium_h' => (int) ($settings['thumb_medium_h'] ?? 300),
            'thumbnail_large_w' => (int) ($settings['thumb_large_w'] ?? 1024),
            'thumbnail_large_h' => (int) ($settings['thumb_large_h'] ?? 1024),
            'thumbnail_banner_w' => (int) ($settings['thumb_banner_w'] ?? 1200),
            'thumbnail_banner_h' => (int) ($settings['thumb_banner_h'] ?? 400),
        ]);
    }

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
        $view     = trim($_GET['view'] ?? 'finder');
        $search   = trim($_GET['q'] ?? '');

        if (!in_array($view, ['list', 'grid', 'finder'], true)) {
            $view = 'finder';
        }

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

        if ($search !== '') {
            $items['folders'] = array_values(array_filter($items['folders'] ?? [], static function (array $folder) use ($search): bool {
                $haystack = strtolower((string)($folder['name'] ?? $folder['path'] ?? ''));
                return str_contains($haystack, strtolower($search));
            }));

            $items['files'] = array_values(array_filter($items['files'] ?? [], static function (array $file) use ($search): bool {
                $haystack = strtolower((string)($file['name'] ?? $file['path'] ?? ''));
                return str_contains($haystack, strtolower($search));
            }));
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
            'view'       => $view,
            'search'     => $search,
        ];
    }

    public function requiresMemberConfirmation(string $path): bool
    {
        $normalizedPath = trim(str_replace('\\', '/', $path), '/');

        return $normalizedPath !== ''
            && ($normalizedPath === 'member' || str_starts_with($normalizedPath, 'member/'));
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
        $settings = $this->service->getSettings();

        return [
            'settings'  => $this->buildSettingsViewModel($settings),
            'diskUsage' => $this->service->getDiskUsage(),
        ];
    }

    /**
     * Einstellungen speichern
     */
    public function saveSettings(array $input): array
    {
        $settings = $this->service->getSettings();

        // Size fields: form sends a plain number (MB); append 'M' if no unit suffix
        foreach (['max_upload_size', 'member_max_upload_size'] as $key) {
            if (isset($input[$key])) {
                $val = trim((string)$input[$key]);
                if (preg_match('/^\d+(?:\.\d+)?$/', $val)) {
                    $val .= 'M';
                }
                $settings[$key] = $val;
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
