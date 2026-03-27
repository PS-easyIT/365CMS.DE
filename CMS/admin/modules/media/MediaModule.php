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

use CMS\AuditLogger;
use CMS\Logger;
use CMS\Services\MediaService;
use CMS\Services\ErrorReportService;
use CMS\WP_Error;

class MediaModule
{
    private const MAX_UPLOAD_FILENAME_LENGTH = 180;
    private const MEMBER_FOLDER_CONFIRM_MESSAGE = 'Der Member-Bereich enthält sensible Uploads. Möchten Sie den Ordner wirklich öffnen?';
    private const MAX_UPLOAD_BATCH_FILES = 20;
    private const MAX_UPLOAD_BATCH_BYTES = 104857600;
    private const MAX_UPLOAD_SIZE_MB = 256;
    private const MIN_UPLOAD_SIZE_MB = 1;
    private const SEARCH_MAX_LENGTH = 120;
    private const FOLDER_NAME_MAX_LENGTH = 120;
    private const CATEGORY_NAME_MAX_LENGTH = 80;
    private const CATEGORY_SLUG_MAX_LENGTH = 80;

    private const SETTINGS_DEFAULTS = [
        'max_upload_size' => '64M',
        'allowed_types' => ['image', 'document', 'archive', 'video', 'audio'],
        'organize_month_year' => true,
        'sanitize_filenames' => true,
        'unique_filenames' => true,
        'lowercase_filenames' => false,
        'auto_webp' => true,
        'strip_exif' => true,
        'jpeg_quality' => 85,
        'max_width' => 2560,
        'max_height' => 2560,
        'generate_thumbnails' => false,
        'thumb_small_w' => 150,
        'thumb_small_h' => 150,
        'thumb_medium_w' => 300,
        'thumb_medium_h' => 300,
        'thumb_large_w' => 1024,
        'thumb_large_h' => 1024,
        'thumb_banner_w' => 1200,
        'thumb_banner_h' => 400,
        'block_dangerous_types' => true,
        'validate_image_content' => true,
        'require_login_for_upload' => true,
        'protect_uploads_dir' => true,
        'member_uploads_enabled' => false,
        'member_max_upload_size' => '5M',
        'member_allowed_types' => ['image', 'document'],
        'member_delete_own' => false,
    ];

    private MediaService $service;
    /**
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $categoriesCache = null;

    private const ALLOWED_VIEWS = ['list', 'grid', 'finder'];
    private const ALLOWED_TABS = ['library', 'categories', 'settings'];
    private const SYSTEM_CATEGORY_SLUGS = ['themes', 'plugins', 'assets', 'fonts', 'dl-manager', 'form-uploads', 'member'];

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
     * @param array<int, string> $values
     * @param array<int, string> $allowedGroups
     * @return array<int, string>
     */
    private function normalizeSettingTypeSelection(array $values, array $allowedGroups): array
    {
        $typeMap = $this->getTypeMap();
        $normalized = [];

        foreach ($values as $value) {
            $value = strtolower(trim((string) $value));
            if ($value === '') {
                continue;
            }

            if (in_array($value, $allowedGroups, true)) {
                $normalized[$value] = true;
                continue;
            }

            foreach ($allowedGroups as $group) {
                if (in_array($value, $typeMap[$group] ?? [], true)) {
                    $normalized[$group] = true;
                    break;
                }
            }
        }

        return array_keys($normalized);
    }

    private function convertSizeSettingToMegabytes(mixed $value, int $defaultMegabytes): int
    {
        $normalizedValue = trim((string) $value);
        if ($normalizedValue === '') {
            return $defaultMegabytes;
        }

        if (preg_match('/^(\d+)(?:\.\d+)?\s*M$/i', $normalizedValue, $matches) === 1) {
            return max(1, (int) $matches[1]);
        }

        if (preg_match('/^\d+$/', $normalizedValue) === 1) {
            return max(1, (int) $normalizedValue);
        }

        return $defaultMegabytes;
    }

    /** @return array<string, mixed> */
    private function buildSettingsOptions(): array
    {
        return [
            'allowed_types' => $this->expandTypeGroups(['image', 'document', 'archive', 'video', 'audio']),
            'member_allowed_types' => $this->expandTypeGroups(['image', 'document']),
            'thumbnail_sizes' => [
                ['label' => 'Small', 'width_field' => 'thumbnail_small_w', 'height_field' => 'thumbnail_small_h'],
                ['label' => 'Medium', 'width_field' => 'thumbnail_medium_w', 'height_field' => 'thumbnail_medium_h'],
                ['label' => 'Large', 'width_field' => 'thumbnail_large_w', 'height_field' => 'thumbnail_large_h'],
                ['label' => 'Banner', 'width_field' => 'thumbnail_banner_w', 'height_field' => 'thumbnail_banner_h'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function buildSettingsViewModel(array $settings): array
    {
        $settings = array_merge(self::SETTINGS_DEFAULTS, $settings);

        return array_merge($settings, [
            'allowed_types' => $this->expandTypeGroups(array_map('strval', (array) ($settings['allowed_types'] ?? []))),
            'member_allowed_types' => $this->expandTypeGroups(array_map('strval', (array) ($settings['member_allowed_types'] ?? []))),
            'max_upload_size' => $this->convertSizeSettingToMegabytes($settings['max_upload_size'] ?? null, 64),
            'member_max_upload_size' => $this->convertSizeSettingToMegabytes($settings['member_max_upload_size'] ?? null, 5),
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
        $path     = $this->normalizeRelativePath((string)($_GET['path'] ?? ''));
        $category = $this->normalizeCategorySlug((string)($_GET['category'] ?? ''));
        $view     = $this->normalizeView((string)($_GET['view'] ?? 'finder'));
        $search   = $this->sanitizeSearch((string)($_GET['q'] ?? ''));
        $confirmMember = (string)($_GET['confirm_member'] ?? '') === '1';

        if ($category !== '' && !$this->categoryExists($category)) {
            $category = '';
        }

        $items = $this->service->getItems($path);
        if ($items instanceof \WP_Error) {
            $this->logWpError('media.library.items_failed', $items, [
                'operation' => 'list_items',
                'path' => $path,
            ]);
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

        $categories = $this->getCategories();
        $diskUsage  = $this->service->getDiskUsage();
        $stateParams = $this->buildLibraryStateParams($path, $view, $category, $search, $confirmMember);

        return [
            'folders'    => $this->buildFolderViewModels($items['folders'] ?? [], $path, $view, $category, $search, $confirmMember),
            'files'      => $this->buildFileViewModels($items['files'] ?? [], $path),
            'categories' => $categories,
            'diskUsage'  => $diskUsage,
            'path'       => $path,
            'category'   => $category,
            'view'       => $view,
            'search'     => $search,
            'confirm_member' => $confirmMember,
            'breadcrumbs' => $this->buildBreadcrumbs($path, $view, $category, $search, $confirmMember),
            'stats' => $this->buildLibraryStats($items, $categories, $diskUsage),
            'base_url' => $this->buildAdminUrl(),
            'finder_url' => $this->buildAdminUrl($this->buildLibraryStateParams($path, 'finder', $category, $search, $confirmMember)),
            'list_url' => $this->buildAdminUrl($this->buildLibraryStateParams($path, 'list', $category, $search, $confirmMember)),
            'grid_url' => $this->buildAdminUrl($this->buildLibraryStateParams($path, 'grid', $category, $search, $confirmMember)),
            'root_url' => $this->buildAdminUrl($stateParams),
            'filter_state' => [
                'path' => $path,
                'view' => $view,
                'category' => $category,
                'search' => $search,
            ],
            'category_options' => $this->buildCategoryOptions($categories),
            'member_folder_confirm_message' => self::MEMBER_FOLDER_CONFIRM_MESSAGE,
            'constraints' => [
                'max_upload_files' => self::MAX_UPLOAD_BATCH_FILES,
                'max_upload_batch_bytes' => self::MAX_UPLOAD_BATCH_BYTES,
                'max_upload_batch_label' => $this->formatBytes(self::MAX_UPLOAD_BATCH_BYTES),
                'search_max_length' => self::SEARCH_MAX_LENGTH,
                'folder_name_max_length' => self::FOLDER_NAME_MAX_LENGTH,
            ],
            'empty_state' => [
                'title' => 'Dieser Ordner ist leer',
                'subtitle' => 'Legen Sie einen Ordner an oder laden Sie Dateien hoch.',
            ],
        ];
    }

    public function requiresMemberConfirmation(string $path): bool
    {
        $normalizedPath = $this->normalizeRelativePath($path);

        return $normalizedPath !== ''
            && ($normalizedPath === 'member' || str_starts_with($normalizedPath, 'member/'));
    }

    /**
     * Ordner erstellen
     */
    public function createFolder(string $name, string $parentPath): array
    {
        $normalizedParentPath = $this->normalizeRelativePath($parentPath);
        $sanitizedName = $this->sanitizeFolderName($name);
        if ($sanitizedName === '') {
            return ['success' => false, 'error' => 'Ordnername ist ungültig.'];
        }

        $result = $this->service->createFolder($sanitizedName, $normalizedParentPath);
        if ($result instanceof WP_Error) {
            return $this->buildGenericFailureFromWpError($result, [
                'title' => 'Ordner konnte nicht erstellt werden',
                'source' => '/admin/media',
                'module' => 'media',
                'operation' => 'create_folder',
                'path' => $normalizedParentPath,
                'folder_name' => $sanitizedName,
            ]);
        }
        return [
            'success' => true,
            'message' => 'Ordner erstellt.',
            'details' => [
                'Ordner: ' . $sanitizedName,
                'Zielpfad: ' . ($normalizedParentPath !== '' ? $normalizedParentPath : '/'),
            ],
        ];
    }

    /**
     * Datei hochladen
     */
    public function uploadFile(array $file, string $targetPath): array
    {
        $normalizedTargetPath = $this->normalizeRelativePath($targetPath);
        $normalizedFile = $this->normalizeUploadFile($file);

        if ($normalizedFile === null) {
            return ['success' => false, 'error' => 'Upload-Datei ist ungültig.'];
        }

        $result = $this->service->uploadFile($normalizedFile, $normalizedTargetPath);
        if ($result instanceof WP_Error) {
            return $this->buildGenericFailureFromWpError($result, [
                'title' => 'Datei konnte nicht hochgeladen werden',
                'source' => '/admin/media',
                'module' => 'media',
                'operation' => 'upload',
                'path' => $normalizedTargetPath,
                'filename' => (string)($normalizedFile['name'] ?? ''),
            ]);
        }
        return [
            'success' => true,
            'message' => 'Datei hochgeladen.',
            'details' => [
                'Datei: ' . (string)($normalizedFile['name'] ?? 'Unbekannt'),
                'Zielpfad: ' . ($normalizedTargetPath !== '' ? $normalizedTargetPath : '/'),
            ],
        ];
    }

    /**
     * Datei/Ordner löschen
     */
    public function deleteItem(string $path): array
    {
        $normalizedPath = $this->normalizeRelativePath($path);
        if ($normalizedPath === '') {
            return ['success' => false, 'error' => 'Elementpfad ist ungültig.'];
        }

        $result = $this->service->deleteItem($normalizedPath);
        if ($result instanceof WP_Error) {
            return $this->buildGenericFailureFromWpError($result, [
                'title' => 'Element konnte nicht gelöscht werden',
                'source' => '/admin/media',
                'module' => 'media',
                'operation' => 'delete',
                'path' => $normalizedPath,
            ]);
        }
        return [
            'success' => true,
            'message' => 'Element gelöscht.',
            'details' => ['Pfad: ' . $normalizedPath],
        ];
    }

    /**
     * Datei/Ordner umbenennen
     */
    public function renameItem(string $oldPath, string $newName): array
    {
        $normalizedPath = $this->normalizeRelativePath($oldPath);
        $sanitizedName = $this->sanitizeItemName($newName);
        if ($normalizedPath === '' || $sanitizedName === '') {
            return ['success' => false, 'error' => 'Umbenennen mit diesen Angaben ist nicht möglich.'];
        }

        $result = $this->service->renameItem($normalizedPath, $sanitizedName);
        if ($result instanceof WP_Error) {
            return $this->buildGenericFailureFromWpError($result, [
                'title' => 'Element konnte nicht umbenannt werden',
                'source' => '/admin/media',
                'module' => 'media',
                'operation' => 'rename',
                'path' => $normalizedPath,
                'new_name' => $sanitizedName,
            ]);
        }
        return [
            'success' => true,
            'message' => 'Element umbenannt.',
            'details' => [
                'Pfad: ' . $normalizedPath,
                'Neuer Name: ' . $sanitizedName,
            ],
        ];
    }

    /**
     * Kategorie zuweisen
     */
    public function assignCategory(string $filePath, string $categorySlug): array
    {
        $normalizedPath = $this->normalizeRelativePath($filePath);
        $normalizedCategory = $this->normalizeCategorySlug($categorySlug);

        if ($normalizedPath === '') {
            return ['success' => false, 'error' => 'Dateipfad ist ungültig.'];
        }

        if ($normalizedCategory !== '' && !$this->categoryExists($normalizedCategory)) {
            return ['success' => false, 'error' => 'Kategorie existiert nicht.'];
        }

        $result = $this->service->assignCategory($normalizedPath, $normalizedCategory);
        if ($result instanceof WP_Error) {
            return $this->buildGenericFailureFromWpError($result, [
                'title' => 'Kategorie konnte nicht zugewiesen werden',
                'source' => '/admin/media/categories',
                'module' => 'media',
                'operation' => 'assign_category',
                'path' => $normalizedPath,
                'category' => $normalizedCategory,
            ]);
        }
        return [
            'success' => true,
            'message' => 'Kategorie zugewiesen.',
            'details' => [
                'Datei: ' . $normalizedPath,
                'Kategorie: ' . ($normalizedCategory !== '' ? $normalizedCategory : 'Ohne Kategorie'),
            ],
        ];
    }

    // ─── Kategorien ──────────────────────────────────────

    /**
     * Kategorien-Übersicht
     */
    public function getCategoriesData(): array
    {
        return [
            'categories' => $this->getCategories(),
            'system_slugs' => self::SYSTEM_CATEGORY_SLUGS,
            'constraints' => [
                'category_name_max_length' => self::CATEGORY_NAME_MAX_LENGTH,
                'category_slug_max_length' => self::CATEGORY_SLUG_MAX_LENGTH,
            ],
        ];
    }

    /**
     * Kategorie hinzufügen
     */
    public function addCategory(string $name, string $slug = ''): array
    {
        $sanitizedName = $this->sanitizeCategoryName($name);
        $normalizedSlug = $this->normalizeCategorySlug($slug !== '' ? $slug : $sanitizedName);

        if ($sanitizedName === '' || $normalizedSlug === '' || in_array($normalizedSlug, self::SYSTEM_CATEGORY_SLUGS, true)) {
            return ['success' => false, 'error' => 'Kategorie konnte mit diesen Angaben nicht erstellt werden.'];
        }

        $result = $this->service->addCategory($sanitizedName, $normalizedSlug);
        if ($result instanceof WP_Error) {
            return $this->buildGenericFailureFromWpError($result, [
                'title' => 'Kategorie konnte nicht erstellt werden',
                'source' => '/admin/media/categories',
                'module' => 'media',
                'operation' => 'add_category',
                'category' => $normalizedSlug,
            ]);
        }

        $this->resetCategoriesCache();

        return [
            'success' => true,
            'message' => 'Kategorie erstellt.',
            'details' => [
                'Name: ' . $sanitizedName,
                'Slug: ' . $normalizedSlug,
            ],
        ];
    }

    /**
     * Kategorie löschen
     */
    public function deleteCategory(string $slug): array
    {
        $normalizedSlug = $this->normalizeCategorySlug($slug);
        if ($normalizedSlug === '' || in_array($normalizedSlug, self::SYSTEM_CATEGORY_SLUGS, true)) {
            return ['success' => false, 'error' => 'Kategorie kann nicht gelöscht werden.'];
        }

        $result = $this->service->deleteCategory($normalizedSlug);
        if ($result instanceof WP_Error) {
            return $this->buildGenericFailureFromWpError($result, [
                'title' => 'Kategorie konnte nicht gelöscht werden',
                'source' => '/admin/media/categories',
                'module' => 'media',
                'operation' => 'delete_category',
                'category' => $normalizedSlug,
            ]);
        }

        $this->resetCategoriesCache();

        return [
            'success' => true,
            'message' => 'Kategorie gelöscht.',
            'details' => ['Slug: ' . $normalizedSlug],
        ];
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
            'options' => $this->buildSettingsOptions(),
            'constraints' => [
                'min_upload_size_mb' => self::MIN_UPLOAD_SIZE_MB,
                'max_upload_size_mb' => self::MAX_UPLOAD_SIZE_MB,
                'jpeg_quality_min' => 60,
                'jpeg_quality_max' => 100,
                'dimension_min' => 1,
                'dimension_max' => 8000,
                'thumbnail_min' => 50,
                'thumbnail_max' => 6000,
            ],
        ];
    }

    /**
     * Einstellungen speichern
     */
    public function saveSettings(array $input): array
    {
        $settings = $this->service->getSettings();
        $originalSettings = $settings;
        $allTypes = array_keys($this->getTypeMap());
        $memberTypes = ['image', 'document', 'video', 'audio'];

        // Size fields: form sends a plain number (MB); append 'M' if no unit suffix
        foreach (['max_upload_size', 'member_max_upload_size'] as $key) {
            if (isset($input[$key])) {
                $val = trim((string)$input[$key]);
                if (preg_match('/^\d+(?:\.\d+)?$/', $val)) {
                    $val .= 'M';
                }
                if (preg_match('/^(\d+)(?:\.\d+)?M$/i', $val, $matches) === 1) {
                    $mb = max(1, min(256, (int)$matches[1]));
                    $val = $mb . 'M';
                }
                $settings[$key] = $val;
            }
        }

        // Integers
        foreach ([
            'jpeg_quality' => 'jpeg_quality',
            'max_width' => 'max_width',
            'max_height' => 'max_height',
            'thumbnail_small_w' => 'thumb_small_w',
            'thumbnail_small_h' => 'thumb_small_h',
            'thumbnail_medium_w' => 'thumb_medium_w',
            'thumbnail_medium_h' => 'thumb_medium_h',
            'thumbnail_large_w' => 'thumb_large_w',
            'thumbnail_large_h' => 'thumb_large_h',
            'thumbnail_banner_w' => 'thumb_banner_w',
            'thumbnail_banner_h' => 'thumb_banner_h',
        ] as $inputKey => $settingsKey) {
            if (isset($input[$inputKey])) {
                $value = (int)$input[$inputKey];
                $settings[$settingsKey] = match ($settingsKey) {
                    'jpeg_quality' => max(60, min(100, $value)),
                    'max_width', 'max_height' => max(1, min(8000, $value)),
                    'thumb_large_w', 'thumb_large_h', 'thumb_banner_w', 'thumb_banner_h' => max(50, min(6000, $value)),
                    default => max(50, min(4000, $value)),
                };
            }
        }

        // Booleans
        foreach ([
            'auto_webp' => 'auto_webp',
            'strip_exif' => 'strip_exif',
            'organize_month_year' => 'organize_month_year',
            'sanitize_filename' => 'sanitize_filenames',
            'unique_filename' => 'unique_filenames',
            'lowercase_filename' => 'lowercase_filenames',
            'member_uploads_enabled' => 'member_uploads_enabled',
            'member_delete_own' => 'member_delete_own',
            'generate_thumbnails' => 'generate_thumbnails',
            'block_dangerous_types' => 'block_dangerous_types',
            'validate_image_content' => 'validate_image_content',
            'require_login_for_upload' => 'require_login_for_upload',
            'protect_uploads_dir' => 'protect_uploads_dir',
        ] as $inputKey => $settingsKey) {
            $settings[$settingsKey] = isset($input[$inputKey]);
        }

        // Arrays
        $settings['allowed_types'] = $this->normalizeSettingTypeSelection(
            array_values(array_unique(array_map('strval', (array)($input['allowed_types'] ?? ['image'])))),
            $allTypes
        );
        if ($settings['allowed_types'] === []) {
            $settings['allowed_types'] = ['image'];
        }

        $settings['member_allowed_types'] = $this->normalizeSettingTypeSelection(
            array_values(array_unique(array_map('strval', (array)($input['member_allowed_types'] ?? ['image'])))),
            $memberTypes
        );
        if ($settings['member_allowed_types'] === []) {
            $settings['member_allowed_types'] = ['image'];
        }

        $result = $this->service->saveSettings($settings);
        if ($result instanceof WP_Error) {
            return $this->buildGenericFailureFromWpError($result, [
                'title' => 'Medien-Einstellungen konnten nicht gespeichert werden',
                'source' => '/admin/media/settings',
                'module' => 'media',
                'operation' => 'save_settings',
                'changed_fields' => $this->collectChangedSettingKeys($originalSettings, $settings),
            ]);
        }
        return [
            'success' => true,
            'message' => 'Einstellungen gespeichert.',
            'details' => [
                'Geänderte Felder: ' . implode(', ', $this->collectChangedSettingKeys($originalSettings, $settings)),
            ],
        ];
    }

    public function normalizeTab(string $tab): string
    {
        $tab = strtolower(trim($tab));

        return in_array($tab, self::ALLOWED_TABS, true) ? $tab : 'library';
    }

    public function normalizePath(string $path): string
    {
        return $this->normalizeRelativePath($path);
    }

    public function resolveParentPathFromActionPath(string $path): string
    {
        $normalizedPath = $this->normalizeRelativePath($path);
        if ($normalizedPath === '') {
            return '';
        }

        $parentPath = dirname($normalizedPath);

        if ($parentPath === '.' || $parentPath === '/' || $parentPath === '\\') {
            return '';
        }

        return $this->normalizeRelativePath($parentPath);
    }

    public function normalizeView(string $view): string
    {
        $view = strtolower(trim($view));

        return in_array($view, self::ALLOWED_VIEWS, true) ? $view : 'finder';
    }

    public function normalizeCategory(string $slug): string
    {
        return $this->normalizeCategorySlug($slug);
    }

    public function normalizeSearch(string $search): string
    {
        return $this->sanitizeSearch($search);
    }

    public function getMemberFolderConfirmMessage(): string
    {
        return self::MEMBER_FOLDER_CONFIRM_MESSAGE;
    }

    private function normalizeRelativePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        $path = preg_replace('#/+#', '/', $path) ?? '';
        $path = trim($path, '/');

        if ($path === '' || str_contains($path, '..') || preg_match('/[\x00-\x1F\x7F]/', $path) === 1) {
            return '';
        }

        return preg_match('#^[A-Za-z0-9._\-/]+$#', $path) === 1 ? $path : '';
    }

    private function normalizeCategorySlug(string $slug): string
    {
        $slug = strtolower(trim(strip_tags($slug)));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug) ?? '';
        $slug = preg_replace('/-+/', '-', $slug) ?? '';

        return trim($slug, '-');
    }

    private function sanitizeCategoryName(string $name): string
    {
        $name = trim(strip_tags($name));

        return function_exists('mb_substr') ? mb_substr($name, 0, self::CATEGORY_NAME_MAX_LENGTH) : substr($name, 0, self::CATEGORY_NAME_MAX_LENGTH);
    }

    private function sanitizeFolderName(string $name): string
    {
        $name = trim(strip_tags($name));
        if ($name === '' || preg_match('/[\\\/\:\*\?"<>\|]/', $name) === 1) {
            return '';
        }

        return function_exists('mb_substr') ? mb_substr($name, 0, self::FOLDER_NAME_MAX_LENGTH) : substr($name, 0, self::FOLDER_NAME_MAX_LENGTH);
    }

    private function sanitizeItemName(string $name): string
    {
        return $this->sanitizeFolderName($name);
    }

    private function sanitizeSearch(string $search): string
    {
        $search = trim(strip_tags($search));

        return function_exists('mb_substr') ? mb_substr($search, 0, self::SEARCH_MAX_LENGTH) : substr($search, 0, self::SEARCH_MAX_LENGTH);
    }

    /**
     * @param array<string, mixed> $original
     * @param array<string, mixed> $updated
     * @return list<string>
     */
    private function collectChangedSettingKeys(array $original, array $updated): array
    {
        $changed = [];

        foreach ($updated as $key => $value) {
            if (($original[$key] ?? null) !== $value) {
                $changed[] = (string) $key;
            }
        }

        return $changed === [] ? ['keine expliziten Wertänderungen erkannt'] : $changed;
    }

    private function categoryExists(string $slug): bool
    {
        foreach ($this->getCategories() as $category) {
            if (($category['slug'] ?? '') === $slug) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCategories(): array
    {
        if ($this->categoriesCache !== null) {
            return $this->categoriesCache;
        }

        $categories = $this->service->getCategories();
        $this->categoriesCache = is_array($categories) ? $categories : [];

        return $this->categoriesCache;
    }

    private function resetCategoriesCache(): void
    {
        $this->categoriesCache = null;
    }

    private function normalizeUploadFile(array $file): ?array
    {
        $requiredKeys = ['name', 'type', 'tmp_name', 'error', 'size'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $file)) {
                return null;
            }
        }

        $name = trim((string)$file['name']);
        $tmpName = (string)$file['tmp_name'];
        if ($name === '' || $tmpName === '') {
            return null;
        }

        $name = function_exists('mb_substr')
            ? mb_substr($name, 0, self::MAX_UPLOAD_FILENAME_LENGTH)
            : substr($name, 0, self::MAX_UPLOAD_FILENAME_LENGTH);

        if (!is_uploaded_file($tmpName)) {
            return null;
        }

        return [
            'name' => $name,
            'type' => (string)$file['type'],
            'tmp_name' => $tmpName,
            'error' => (int)$file['error'],
            'size' => max(0, (int)$file['size']),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function buildLibraryStateParams(string $path, string $view, string $category, string $search, bool $confirmMember): array
    {
        $params = [];

        if ($path !== '') {
            $params['path'] = $path;
        }

        if ($view !== 'finder') {
            $params['view'] = $view;
        }

        if ($category !== '') {
            $params['category'] = $category;
        }

        if ($search !== '') {
            $params['q'] = $search;
        }

        if ($confirmMember) {
            $params['confirm_member'] = '1';
        }

        return $params;
    }

    private function buildAdminUrl(array $params = []): string
    {
        $baseUrl = SITE_URL . '/admin/media';
        if ($params === []) {
            return $baseUrl;
        }

        $normalizedParams = array_filter($params, static fn (mixed $value): bool => $value !== '' && $value !== null);

        return $normalizedParams === [] ? $baseUrl : $baseUrl . '?' . http_build_query($normalizedParams);
    }

    /**
     * @return list<array<string, string>>
     */
    private function buildBreadcrumbs(string $path, string $view, string $category, string $search, bool $confirmMember): array
    {
        if ($path === '') {
            return [];
        }

        $breadcrumbs = [];
        $parts = explode('/', trim($path, '/'));
        $cumulative = '';

        foreach ($parts as $index => $part) {
            $cumulative .= ($cumulative !== '' ? '/' : '') . $part;
            $isLast = $index === count($parts) - 1;
            $breadcrumbs[] = [
                'label' => $part,
                'path' => $cumulative,
                'url' => $isLast ? '' : $this->buildAdminUrl($this->buildLibraryStateParams($cumulative, $view, $category, $search, $confirmMember)),
            ];
        }

        return $breadcrumbs;
    }

    /**
     * @param array<int, array<string, mixed>> $folders
     * @return list<array<string, mixed>>
     */
    private function buildFolderViewModels(array $folders, string $path, string $view, string $category, string $search, bool $confirmMember): array
    {
        $viewModels = [];

        foreach ($folders as $folder) {
            $folderName = (string)($folder['name'] ?? '');
            $folderPath = (string)($folder['path'] ?? trim(($path !== '' ? $path . '/' : '') . $folderName, '/'));
            $requiresConfirmation = $this->requiresMemberConfirmation($folderPath) && !$confirmMember;

            $viewModels[] = [
                'name' => $folderName,
                'path' => $folderPath,
                'items_count' => (int)($folder['items_count'] ?? 0),
                'category' => (string)($folder['category'] ?? ''),
                'modified_label' => !empty($folder['modified']) ? date('d.m.Y H:i', (int)$folder['modified']) : '—',
                'is_system' => !empty($folder['is_system']),
                'url' => $this->buildAdminUrl($this->buildLibraryStateParams($folderPath, $view, $category, $search, $confirmMember)),
                'confirm_url' => $this->buildAdminUrl($this->buildLibraryStateParams($folderPath, $view, $category, $search, true)),
                'requires_confirmation' => $requiresConfirmation,
            ];
        }

        return $viewModels;
    }

    /**
     * @param array<int, array<string, mixed>> $files
     * @return list<array<string, mixed>>
     */
    private function buildFileViewModels(array $files, string $path): array
    {
        $viewModels = [];

        foreach ($files as $file) {
            $fileName = (string)($file['name'] ?? '');
            $filePath = (string)($file['path'] ?? trim(($path !== '' ? $path . '/' : '') . $fileName, '/'));
            $fileUrl = (string)($file['url'] ?? (UPLOAD_URL . '/' . $filePath));
            $previewUrl = (string)($file['preview_url'] ?? $fileUrl);
            $fileType = $this->detectFileType($fileName);

            $viewModels[] = [
                'name' => $fileName,
                'path' => $filePath,
                'url' => $fileUrl,
                'preview_url' => $previewUrl,
                'category' => (string)($file['category'] ?? ''),
                'category_label' => (string)($file['category'] ?? 'Ohne Kategorie'),
                'modified_label' => !empty($file['modified']) ? date('d.m.Y H:i', (int)$file['modified']) : '—',
                'file_type' => $fileType,
                'is_image' => $fileType === 'image',
                'formatted_size' => $this->formatBytes(isset($file['size']) ? (int)$file['size'] : null),
            ] + $file;
        }

        return $viewModels;
    }

    /**
     * @param array<int, array<string, mixed>> $categories
     * @return list<array<string, mixed>>
     */
    private function buildCategoryOptions(array $categories): array
    {
        $options = [];

        foreach ($categories as $category) {
            $options[] = [
                'slug' => (string)($category['slug'] ?? ''),
                'name' => (string)($category['name'] ?? ''),
                'count' => (int)($category['count'] ?? 0),
            ];
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $items
     * @param array<int, array<string, mixed>> $categories
     * @param array<string, mixed> $diskUsage
     * @return array<string, string|int>
     */
    private function buildLibraryStats(array $items, array $categories, array $diskUsage): array
    {
        return [
            'file_count' => (int)($diskUsage['count'] ?? count($items['files'] ?? [])),
            'storage_label' => (string)($diskUsage['formatted'] ?? '0 B'),
            'folder_count' => count($items['folders'] ?? []),
            'category_count' => count($categories),
        ];
    }

    private function detectFileType(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'ico', 'svg'], true)) {
            return 'image';
        }

        if (in_array($extension, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'], true)) {
            return 'video';
        }

        if (in_array($extension, ['mp3', 'wav', 'aac', 'flac', 'm4a'], true)) {
            return 'audio';
        }

        return 'document';
    }

    private function formatBytes(?int $bytes): string
    {
        if ($bytes === null) {
            return '—';
        }

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }

    private function buildGenericFailureFromWpError(WP_Error $error, array $context): array
    {
        $payload = ErrorReportService::buildFailureResultFromWpError($error, $context);
        $operation = (string)($context['operation'] ?? 'media_operation');
        $title = (string)($context['title'] ?? 'Medien-Aktion fehlgeschlagen');

        $this->logWpError('media.' . $operation . '.failed', $error, $context + [
            'report_payload' => $payload['report_payload'] ?? [],
            'error_details' => $payload['error_details'] ?? [],
        ]);

        return [
            'success' => false,
            'error' => $title . '. Bitte Logs prüfen.',
            'details' => is_array($payload['details'] ?? null) ? $payload['details'] : [],
            'error_details' => is_array($payload['error_details'] ?? null) ? $payload['error_details'] : [],
            'report_payload' => $payload['report_payload'] ?? [],
        ];
    }

    private function logWpError(string $action, WP_Error $error, array $context = []): void
    {
        $logContext = [
            'error_code' => $error->get_error_code(),
            'error_message' => $error->get_error_message(),
            'error_data' => is_array($error->get_error_data()) ? $error->get_error_data() : [],
            'context' => $context,
        ];

        Logger::instance()->withChannel('admin.media')->warning('Media-Aktion fehlgeschlagen.', $logContext);
        AuditLogger::instance()->log(
            AuditLogger::CAT_MEDIA,
            $action,
            'Media-Aktion fehlgeschlagen',
            'media',
            null,
            [
                'error_code' => $error->get_error_code(),
                'context' => $context,
            ],
            'warning'
        );
    }
}
