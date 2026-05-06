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
use CMS\Services\MediaDeliveryService;
use CMS\Services\MediaService;
use CMS\Services\MediaUsageService;
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
    private const MOVE_TARGET_MAX_NODES = 1000;

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
    private MediaUsageService $usageService;
    /**
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $categoriesCache = null;

    private const ALLOWED_VIEWS = ['list', 'grid'];
    private const ALLOWED_TABS = ['library', 'featured', 'categories', 'settings'];
    private const ALLOWED_USAGE_FILTERS = ['all', 'used', 'unused'];
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
     * @param array<int, string> $allowedExtensions
     * @return array<int, string>
     */
    private function normalizeSettingTypeSelection(array $values, array $allowedExtensions): array
    {
        $typeMap = $this->getTypeMap();
        $allowedLookup = array_fill_keys($allowedExtensions, true);
        $normalized = [];

        foreach ($values as $value) {
            $value = strtolower(trim((string) $value));
            if ($value === '') {
                continue;
            }

            if (isset($allowedLookup[$value])) {
                $normalized[$value] = true;
                continue;
            }

            foreach ($typeMap[$value] ?? [] as $extension) {
                if (isset($allowedLookup[$extension])) {
                    $normalized[$extension] = true;
                }
            }
        }

        return array_keys($normalized);
    }

    /**
     * @param array<int, string> $values
     * @param array<int, string> $allowedExtensions
     * @return array<int, string>
     */
    private function expandStoredTypeSelection(array $values, array $allowedExtensions): array
    {
        return $this->normalizeSettingTypeSelection($values, $allowedExtensions);
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
        $options = $this->buildSettingsOptions();

        return array_merge($settings, [
            'allowed_types' => $this->expandStoredTypeSelection(
                array_map('strval', (array) ($settings['allowed_types'] ?? [])),
                array_map('strval', (array) ($options['allowed_types'] ?? []))
            ),
            'member_allowed_types' => $this->expandStoredTypeSelection(
                array_map('strval', (array) ($settings['member_allowed_types'] ?? [])),
                array_map('strval', (array) ($options['member_allowed_types'] ?? []))
            ),
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
        $this->usageService = MediaUsageService::getInstance();
    }

    // ─── Bibliothek ──────────────────────────────────────

    /**
     * Daten für die Medien-Bibliothek
     */
    public function getLibraryData(): array
    {
        $path     = $this->normalizeRelativePath((string)($_GET['path'] ?? ''));
        $category = $this->normalizeCategorySlug((string)($_GET['category'] ?? ''));
        $view     = $this->normalizeView((string)($_GET['view'] ?? 'list'));
        $search   = $this->sanitizeSearch((string)($_GET['q'] ?? ''));
        $usageFilter = $this->normalizeUsageFilter((string)($_GET['usage_filter'] ?? 'all'));
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
        $stateParams = $this->buildLibraryStateParams($path, $view, $category, $search, $usageFilter, $confirmMember);
        $rootStateParams = $this->buildLibraryStateParams('', $view, $category, $search, $usageFilter, $confirmMember);

        $usageMap = $this->usageService->buildUsageMap(array_map(
            static fn (array $file): string => (string) ($file['path'] ?? ''),
            is_array($items['files'] ?? null) ? $items['files'] : []
        ));

        if ($usageFilter !== 'all' && !empty($items['files'])) {
            $items['files'] = array_values(array_filter($items['files'], function (array $file) use ($usageMap, $usageFilter): bool {
                $filePath = (string) ($file['path'] ?? '');
                $usageCount = count(is_array($usageMap[$filePath] ?? null) ? $usageMap[$filePath] : []);

                return $usageFilter === 'used' ? $usageCount > 0 : $usageCount === 0;
            }));
        }

        return [
            'folders'    => $this->buildFolderViewModels($items['folders'] ?? [], $path, $view, $category, $search, $usageFilter, $confirmMember),
            'files'      => $this->buildFileViewModels($items['files'] ?? [], $path, $usageMap),
            'categories' => $categories,
            'diskUsage'  => $diskUsage,
            'path'       => $path,
            'category'   => $category,
            'view'       => $view,
            'search'     => $search,
            'usage_filter' => $usageFilter,
            'confirm_member' => $confirmMember,
            'breadcrumbs' => $this->buildBreadcrumbs($path, $view, $category, $search, $usageFilter, $confirmMember),
            'stats' => $this->buildLibraryStats($items, $categories, $diskUsage),
            'base_url' => $this->buildAdminUrl(),
            'list_url' => $this->buildAdminUrl($this->buildLibraryStateParams($path, 'list', $category, $search, $usageFilter, $confirmMember)),
            'grid_url' => $this->buildAdminUrl($this->buildLibraryStateParams($path, 'grid', $category, $search, $usageFilter, $confirmMember)),
            'root_url' => $this->buildAdminUrl($rootStateParams),
            'filter_state' => [
                'path' => $path,
                'view' => $view,
                'category' => $category,
                'search' => $search,
                'usage_filter' => $usageFilter,
            ],
            'category_options' => $this->buildCategoryOptions($categories),
            'usage_filter_options' => [
                ['value' => 'all', 'label' => 'Alle Medien'],
                ['value' => 'unused', 'label' => 'Nur ungenutzte'],
                ['value' => 'used', 'label' => 'Nur eingebundene'],
            ],
            'move_targets' => $this->buildMoveTargetOptions(),
            'bulk_actions' => [
                ['value' => 'delete', 'label' => 'Auswahl löschen'],
                ['value' => 'move', 'label' => 'Auswahl verschieben'],
            ],
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

    /**
     * Daten für die fokussierte Ansicht aller in Beiträgen und Seiten verwendeten Titelbilder.
     */
    public function getFeaturedMediaData(): array
    {
        $search = $this->sanitizeSearch((string) ($_GET['q'] ?? ''));
        $usageScope = $this->normalizeFeaturedUsageScope((string) ($_GET['usage_scope'] ?? 'all'));
        $highlightPath = $this->normalizeRelativePath((string) ($_GET['highlight'] ?? ''));
        $highlightActive = ((string) ($_GET['replaced'] ?? '') === '1') && $highlightPath !== '';
        $featuredUsageMap = $this->usageService->buildFeaturedImageMap();
        $items = [];
        $totalReferences = 0;
        $postReferences = 0;
        $pageReferences = 0;
        $missingFiles = 0;

        foreach ($featuredUsageMap as $relativePath => $usageItems) {
            $normalizedPath = $this->normalizeRelativePath($relativePath);
            if ($normalizedPath === '') {
                continue;
            }

            $usageItems = $this->filterFeaturedUsageItemsByScope($usageItems, $usageScope);
            if ($usageItems === []) {
                continue;
            }

            if ($search !== '' && !$this->featuredMediaMatchesSearch($normalizedPath, $usageItems, $search)) {
                continue;
            }

            $usageCount = count($usageItems);
            $postCount = count(array_filter($usageItems, static fn (array $usageItem): bool => (string) ($usageItem['content_type'] ?? '') === 'post'));
            $pageCount = count(array_filter($usageItems, static fn (array $usageItem): bool => (string) ($usageItem['content_type'] ?? '') === 'page'));
            $exists = $this->service->pathExists($normalizedPath);

            $totalReferences += $usageCount;
            $postReferences += $postCount;
            $pageReferences += $pageCount;
            if (!$exists) {
                $missingFiles++;
            }

            $items[] = [
                'name' => basename($normalizedPath),
                'path' => $normalizedPath,
                'exists' => $exists,
                'preview_url' => $exists ? MediaDeliveryService::getInstance()->buildPreviewUrl($normalizedPath) : '',
                'access_url' => $exists ? MediaDeliveryService::getInstance()->buildAccessUrl($normalizedPath, true) : '',
                'usage_items' => $usageItems,
                'usage_count' => $usageCount,
                'post_count' => $postCount,
                'page_count' => $pageCount,
                'usage_count_label' => $usageCount === 1 ? '1 Verknüpfung' : $usageCount . ' Verknüpfungen',
                'is_highlighted' => $highlightActive && $normalizedPath === $highlightPath,
            ];
        }

        usort($items, static function (array $left, array $right): int {
            $usageCompare = (int) ($right['usage_count'] ?? 0) <=> (int) ($left['usage_count'] ?? 0);
            if ($usageCompare !== 0) {
                return $usageCompare;
            }

            return strcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
        });

        return [
            'items' => $items,
            'search' => $search,
            'usage_scope' => $usageScope,
            'base_url' => '/admin/media',
            'stats' => [
                'image_count' => count($items),
                'reference_count' => $totalReferences,
                'post_reference_count' => $postReferences,
                'page_reference_count' => $pageReferences,
                'missing_count' => $missingFiles,
            ],
            'constraints' => [
                'search_max_length' => self::SEARCH_MAX_LENGTH,
            ],
            'usage_scope_options' => [
                ['value' => 'all', 'label' => 'Beiträge & Seiten'],
                ['value' => 'posts', 'label' => 'Nur Beiträge'],
                ['value' => 'pages', 'label' => 'Nur Seiten'],
            ],
            'empty_state' => [
                'title' => $search !== '' ? 'Keine passenden Beitrags- oder Seitenbilder gefunden' : 'Noch keine Beitrags- oder Seitenbilder hinterlegt',
                'subtitle' => $search !== ''
                    ? 'Bitte Suchbegriff anpassen oder zurücksetzen.'
                    : 'Sobald Beiträge oder Seiten ein Titelbild haben, erscheinen sie hier gesammelt zur schnellen Pflege.',
            ],
            'help_text' => 'Hier sehen Sie ausschließlich die Bilder, die aktuell als Beitragsbild oder Seitenbild verwendet werden. Beim Ersetzen bleibt die gleiche Medien-Referenz bestehen – alle verknüpften Beiträge und Seiten ziehen also automatisch das neue Bild.',
            'highlight_path' => $highlightPath,
            'highlight_active' => $highlightActive,
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

        if ($normalizedTargetPath !== '' && !$this->directoryExists($normalizedTargetPath)) {
            return ['success' => false, 'error' => 'Der Zielordner für den Upload existiert nicht mehr.'];
        }

        $result = $this->service->uploadManagedFile($normalizedFile, $normalizedTargetPath);
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

        $storedPath = trim((string) ($result['path'] ?? ''), '/');
        $storedParentPath = $storedPath !== '' ? trim(str_replace('\\', '/', dirname($storedPath)), '/.') : '';

        return [
            'success' => true,
            'message' => 'Datei hochgeladen.',
            'details' => [
                'Datei: ' . (string)($normalizedFile['name'] ?? 'Unbekannt'),
                'Zielpfad: ' . ($storedParentPath !== '' ? $storedParentPath : '/'),
            ],
            'stored_path' => $storedPath,
            'stored_parent_path' => $storedParentPath,
        ];
    }

    /**
     * Bereits eingebundene Bilddatei durch eine neue Datei ersetzen.
     */
    public function replaceItem(string $path): array
    {
        $normalizedPath = $this->normalizeRelativePath($path);
        if ($normalizedPath === '') {
            return ['success' => false, 'error' => 'Ungültiger Bildpfad.'];
        }

        $featuredUsageMap = $this->usageService->buildFeaturedImageMap();
        if (!array_key_exists($normalizedPath, $featuredUsageMap)) {
            return ['success' => false, 'error' => 'Dieses Bild ist aktuell nicht als Beitrags- oder Seitenbild registriert. Bitte die Medienansicht neu laden.'];
        }

        $replacementFile = is_array($_FILES['replacement_file'] ?? null) ? $_FILES['replacement_file'] : null;
        if ($replacementFile === null || (int) ($replacementFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'error' => 'Bitte eine neue Bilddatei auswählen.'];
        }

        $result = $this->service->replaceManagedFile($replacementFile, $normalizedPath);
        if ($result instanceof WP_Error) {
            return $this->buildGenericFailureFromWpError($result, [
                'title' => 'Bild konnte nicht ersetzt werden',
                'source' => '/admin/media?tab=featured',
                'module' => 'media',
                'operation' => 'replace_item',
                'path' => $normalizedPath,
            ]);
        }

        $usageItems = $featuredUsageMap[$normalizedPath] ?? [];
        $usageCount = count($usageItems);

        return [
            'success' => true,
            'message' => 'Bild ersetzt.',
            'highlight_path' => $normalizedPath,
            'details' => [
                'Datei: ' . basename($normalizedPath),
                $usageCount > 0
                    ? 'Aktualisiert in ' . $usageCount . ' Beitrags-/Seiten-Verknüpfung' . ($usageCount === 1 ? '' : 'en') . '.'
                    : 'Die bestehende Medien-Referenz wurde beibehalten.',
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

        if ($this->isProtectedPath($normalizedPath)) {
            return ['success' => false, 'error' => 'Geschützte Systemordner können nicht gelöscht werden.'];
        }

        if (!$this->hasItem($normalizedPath)) {
            return ['success' => false, 'error' => 'Das gewählte Element existiert nicht mehr.'];
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

        if ($this->isProtectedPath($normalizedPath)) {
            return ['success' => false, 'error' => 'Geschützte Systemordner können nicht umbenannt werden.'];
        }

        if (!$this->hasItem($normalizedPath)) {
            return ['success' => false, 'error' => 'Das gewählte Element existiert nicht mehr.'];
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
     * Datei/Ordner verschieben
     */
    public function moveItem(string $oldPath, string $targetParentPath): array
    {
        $normalizedPath = $this->normalizeRelativePath($oldPath);
        $normalizedTargetParentPath = $this->normalizeRelativePath($targetParentPath);

        if ($normalizedPath === '') {
            return ['success' => false, 'error' => 'Verschieben mit diesen Angaben ist nicht möglich.'];
        }

        if ($this->isProtectedPath($normalizedPath)) {
            return ['success' => false, 'error' => 'Geschützte Systemordner können nicht verschoben werden.'];
        }

        if (!$this->hasItem($normalizedPath)) {
            return ['success' => false, 'error' => 'Das gewählte Element existiert nicht mehr.'];
        }

        if ($normalizedTargetParentPath !== '' && !$this->directoryExists($normalizedTargetParentPath)) {
            return ['success' => false, 'error' => 'Der gewählte Zielordner existiert nicht mehr.'];
        }

        $currentParentPath = $this->resolveParentPathFromActionPath($normalizedPath);
        if ($normalizedTargetParentPath === $currentParentPath) {
            return [
                'success' => true,
                'message' => 'Element befindet sich bereits in diesem Ordner.',
                'details' => [
                    'Pfad: ' . $normalizedPath,
                    'Ordner: ' . ($normalizedTargetParentPath !== '' ? $normalizedTargetParentPath : '/'),
                ],
            ];
        }

        if ($normalizedTargetParentPath === $normalizedPath || str_starts_with($normalizedTargetParentPath, $normalizedPath . '/')) {
            return ['success' => false, 'error' => 'Ein Ordner kann nicht in sich selbst oder einen Unterordner verschoben werden.'];
        }

        $targetPath = ltrim(($normalizedTargetParentPath !== '' ? $normalizedTargetParentPath . '/' : '') . basename($normalizedPath), '/');
        if ($targetPath === $normalizedPath) {
            return [
                'success' => true,
                'message' => 'Element befindet sich bereits am gewünschten Ziel.',
                'details' => ['Pfad: ' . $normalizedPath],
            ];
        }

        $result = $this->service->moveFile($normalizedPath, $targetPath);
        if ($result instanceof WP_Error) {
            return $this->buildGenericFailureFromWpError($result, [
                'title' => 'Element konnte nicht verschoben werden',
                'source' => '/admin/media',
                'module' => 'media',
                'operation' => 'move',
                'path' => $normalizedPath,
                'target_parent_path' => $normalizedTargetParentPath,
                'target_path' => $targetPath,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Element verschoben.',
            'details' => [
                'Quelle: ' . $normalizedPath,
                'Ziel: ' . $targetPath,
            ],
        ];
    }

    /**
     * @param array<int, string> $paths
     */
    public function bulkItems(array $paths, string $bulkAction, string $targetParentPath = ''): array
    {
        $normalizedBulkAction = strtolower(trim($bulkAction));
        if (!in_array($normalizedBulkAction, ['delete', 'move'], true)) {
            return ['success' => false, 'error' => 'Die gewählte Bulk-Aktion ist nicht erlaubt.'];
        }

        $selectedPaths = $this->normalizeBulkPaths($paths);
        if ($selectedPaths === []) {
            return ['success' => false, 'error' => 'Für die Bulk-Aktion wurden keine gültigen Elemente übermittelt.'];
        }

        $normalizedTargetParentPath = $this->normalizeRelativePath($targetParentPath);

        $missingPaths = $this->collectMissingPaths($selectedPaths);
        if ($missingPaths !== []) {
            return [
                'success' => false,
                'error' => 'Die Auswahl enthält veraltete oder bereits gelöschte Medien. Bitte die Liste aktualisieren.',
                'details' => array_map(static fn (string $path): string => 'Fehlend: ' . $path, array_slice($missingPaths, 0, 8)),
            ];
        }

        $protectedPaths = $this->collectProtectedPaths($selectedPaths);
        if ($protectedPaths !== []) {
            return [
                'success' => false,
                'error' => 'Geschützte Systemordner dürfen nicht per Bulk-Aktion verändert werden.',
                'details' => array_map(static fn (string $path): string => 'Geschützt: ' . $path, array_slice($protectedPaths, 0, 8)),
            ];
        }

        if ($normalizedBulkAction === 'move' && $normalizedTargetParentPath !== '' && !$this->directoryExists($normalizedTargetParentPath)) {
            return ['success' => false, 'error' => 'Der gewählte Zielordner existiert nicht mehr.'];
        }

        $successCount = 0;
        $skippedCount = 0;
        $details = [];
        $errorDetails = [];
        $reportPayload = [];

        foreach ($selectedPaths as $selectedPath) {
            if ($normalizedBulkAction === 'move') {
                $moveResult = $this->executeBulkMove($selectedPath, $normalizedTargetParentPath);

                if (($moveResult['status'] ?? '') === 'success') {
                    $successCount++;
                    $details[] = (string) ($moveResult['detail'] ?? ('Verschoben: ' . $selectedPath));
                    continue;
                }

                if (($moveResult['status'] ?? '') === 'skipped') {
                    $skippedCount++;
                    $details[] = (string) ($moveResult['detail'] ?? ('Übersprungen: ' . $selectedPath));
                    continue;
                }

                $errorDetails[] = (string) ($moveResult['detail'] ?? ('Fehlgeschlagen: ' . $selectedPath));
                if ($reportPayload === [] && is_array($moveResult['report_payload'] ?? null)) {
                    $reportPayload = $moveResult['report_payload'];
                }
                continue;
            }

            $deleteResult = $this->deleteItem($selectedPath);
            if (!empty($deleteResult['success'])) {
                $successCount++;
                $details[] = 'Gelöscht: ' . $selectedPath;
                continue;
            }

            $errorDetails[] = $selectedPath . ': ' . trim((string) ($deleteResult['error'] ?? 'Fehler'));
            if ($reportPayload === [] && is_array($deleteResult['report_payload'] ?? null)) {
                $reportPayload = $deleteResult['report_payload'];
            }
        }

        $verb = $normalizedBulkAction === 'move' ? 'verschoben' : 'gelöscht';
        $summaryParts = [];
        if ($successCount > 0) {
            $summaryParts[] = $successCount . ' Element(e) ' . $verb;
        }
        if ($skippedCount > 0) {
            $summaryParts[] = $skippedCount . ' übersprungen';
        }
        if ($errorDetails !== []) {
            $summaryParts[] = count($errorDetails) . ' fehlgeschlagen';
        }

        if ($summaryParts === []) {
            return [
                'success' => false,
                'error' => 'Die Bulk-Aktion konnte nicht ausgeführt werden.',
                'details' => [],
                'error_details' => array_slice($errorDetails, 0, 8),
                'report_payload' => $reportPayload,
            ];
        }

        if ($successCount === 0 && $skippedCount === 0) {
            return [
                'success' => false,
                'error' => 'Die Bulk-Aktion ist vollständig fehlgeschlagen. ' . implode(', ', $summaryParts) . '.',
                'details' => [],
                'error_details' => array_slice($errorDetails, 0, 8),
                'report_payload' => $reportPayload,
            ];
        }

        return [
            'success' => true,
            'message' => 'Bulk-Aktion abgeschlossen: ' . implode(', ', $summaryParts) . '.',
            'details' => array_slice($details, 0, 8),
            'error_details' => array_slice($errorDetails, 0, 8),
            'report_payload' => $reportPayload,
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

        if (!$this->categoryExists($normalizedSlug)) {
            return ['success' => false, 'error' => 'Die gewählte Kategorie existiert nicht mehr.'];
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
        $options = $this->buildSettingsOptions();
        $allTypes = array_map('strval', (array) ($options['allowed_types'] ?? []));
        $memberTypes = array_map('strval', (array) ($options['member_allowed_types'] ?? []));

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
            'protect_uploads_dir' => 'protect_uploads_dir',
        ] as $inputKey => $settingsKey) {
            $settings[$settingsKey] = isset($input[$inputKey]);
        }

        // Interne Upload-Endpunkte bleiben bewusst authentifiziert; die Einstellung
        // wird fail-closed auf true gehalten, statt einen nicht existierenden
        // anonymen Upload-Modus im UI vorzutäuschen.
        $settings['require_login_for_upload'] = true;

        // Arrays
        $settings['allowed_types'] = $this->normalizeSettingTypeSelection(
            array_values(array_unique(array_map('strval', (array)($input['allowed_types'] ?? $this->expandTypeGroups(['image']))))),
            $allTypes
        );
        if ($settings['allowed_types'] === []) {
            $settings['allowed_types'] = $this->expandTypeGroups(['image']);
        }

        $settings['member_allowed_types'] = $this->normalizeSettingTypeSelection(
            array_values(array_unique(array_map('strval', (array)($input['member_allowed_types'] ?? $this->expandTypeGroups(['image']))))),
            $memberTypes
        );
        if ($settings['member_allowed_types'] === []) {
            $settings['member_allowed_types'] = array_values(array_intersect(
                $this->expandTypeGroups(['image']),
                $memberTypes
            ));
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
                $this->buildSettingsChangeSummary($originalSettings, $settings),
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

        return in_array($view, self::ALLOWED_VIEWS, true) ? $view : 'list';
    }

    public function normalizeUsageFilter(string $usageFilter): string
    {
        $usageFilter = strtolower(trim($usageFilter));

        return in_array($usageFilter, self::ALLOWED_USAGE_FILTERS, true) ? $usageFilter : 'all';
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

    public function hasItem(string $path): bool
    {
        $normalizedPath = $this->normalizeRelativePath($path);
        if ($normalizedPath === '') {
            return false;
        }

        return $this->service->pathExists($normalizedPath);
    }

    public function directoryExists(string $path): bool
    {
        $normalizedPath = $this->normalizeRelativePath($path);
        if ($normalizedPath === '') {
            return trim($path) === '' && $this->service->directoryExists('');
        }

        return $this->service->directoryExists($normalizedPath);
    }

    private function isProtectedPath(string $path): bool
    {
        $normalizedPath = $this->normalizeRelativePath($path);
        if ($normalizedPath === '') {
            return false;
        }

        return $this->service->isProtectedPath($normalizedPath);
    }

    /**
     * @param array<int, string> $paths
     * @return list<string>
     */
    private function collectMissingPaths(array $paths): array
    {
        $missing = [];

        foreach ($paths as $path) {
            if (!$this->hasItem($path)) {
                $missing[] = $path;
            }
        }

        return $missing;
    }

    /**
     * @param array<int, string> $paths
     * @return list<string>
     */
    private function collectProtectedPaths(array $paths): array
    {
        $protected = [];

        foreach ($paths as $path) {
            if ($this->isProtectedPath($path)) {
                $protected[] = $path;
            }
        }

        return $protected;
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
        if ($name === '' || preg_match('#[\\/:*?"<>|]#', $name) === 1) {
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
     * @param array<int, string> $paths
     * @return list<string>
     */
    private function normalizeBulkPaths(array $paths): array
    {
        $normalized = [];

        foreach ($paths as $path) {
            $normalizedPath = $this->normalizeRelativePath((string) $path);
            if ($normalizedPath === '') {
                continue;
            }

            $normalized[$normalizedPath] = true;
        }

        $sortedPaths = array_keys($normalized);
        usort($sortedPaths, static function (string $left, string $right): int {
            $lengthCompare = strlen($left) <=> strlen($right);
            return $lengthCompare !== 0 ? $lengthCompare : strcmp($left, $right);
        });

        $filteredPaths = [];

        foreach ($sortedPaths as $path) {
            $hasAncestor = false;

            foreach ($filteredPaths as $keptPath) {
                if (str_starts_with($path, $keptPath . '/')) {
                    $hasAncestor = true;
                    break;
                }
            }

            if ($hasAncestor) {
                continue;
            }

            $filteredPaths[] = $path;
        }

        return $filteredPaths;
    }

    /**
     * @return array<string, mixed>
     */
    private function executeBulkMove(string $selectedPath, string $targetParentPath): array
    {
        if ($this->isProtectedPath($selectedPath)) {
            return [
                'status' => 'error',
                'detail' => $selectedPath . ': Geschützte Systemordner können nicht verschoben werden.',
            ];
        }

        if (!$this->hasItem($selectedPath)) {
            return [
                'status' => 'error',
                'detail' => $selectedPath . ': Das Element existiert nicht mehr.',
            ];
        }

        if ($targetParentPath !== '' && !$this->directoryExists($targetParentPath)) {
            return [
                'status' => 'error',
                'detail' => $selectedPath . ': Der Zielordner existiert nicht mehr.',
            ];
        }

        $currentParentPath = $this->resolveParentPathFromActionPath($selectedPath);

        if ($targetParentPath === $currentParentPath) {
            return [
                'status' => 'skipped',
                'detail' => $selectedPath . ' befindet sich bereits im Zielordner.',
            ];
        }

        if ($targetParentPath === $selectedPath || str_starts_with($targetParentPath, $selectedPath . '/')) {
            return [
                'status' => 'error',
                'detail' => $selectedPath . ': Ein Ordner kann nicht in sich selbst oder einen Unterordner verschoben werden.',
            ];
        }

        $targetPath = ltrim(($targetParentPath !== '' ? $targetParentPath . '/' : '') . basename($selectedPath), '/');
        if ($targetPath === $selectedPath) {
            return [
                'status' => 'skipped',
                'detail' => $selectedPath . ' ist bereits am gewünschten Ziel.',
            ];
        }

        $result = $this->service->moveFile($selectedPath, $targetPath);
        if ($result instanceof WP_Error) {
            $failure = $this->buildGenericFailureFromWpError($result, [
                'title' => 'Bulk-Verschieben fehlgeschlagen',
                'source' => '/admin/media',
                'module' => 'media',
                'operation' => 'bulk_move',
                'path' => $selectedPath,
                'target_parent_path' => $targetParentPath,
                'target_path' => $targetPath,
            ]);

            return [
                'status' => 'error',
                'detail' => $selectedPath . ': ' . trim((string) ($failure['error'] ?? 'Fehler')), 
                'report_payload' => $failure['report_payload'] ?? [],
            ];
        }

        return [
            'status' => 'success',
            'detail' => 'Verschoben: ' . $selectedPath . ' → ' . $targetPath,
        ];
    }

    /**
     * @return list<array{path:string,label:string,depth:int}>
     */
    private function buildMoveTargetOptions(): array
    {
        $options = [[
            'path' => '',
            'label' => 'Uploads',
            'depth' => 0,
        ]];

        $visited = ['' => true];
        $this->appendMoveTargetOptions($options, '', 0, $visited);

        return $options;
    }

    /**
     * @param array<int, array{path:string,label:string,depth:int}> $options
     * @param array<string, bool> $visited
     */
    private function appendMoveTargetOptions(array &$options, string $path, int $depth, array &$visited): void
    {
        if (count($visited) >= self::MOVE_TARGET_MAX_NODES) {
            return;
        }

        $items = $this->service->getItems($path);
        if ($items instanceof WP_Error) {
            return;
        }

        $folders = is_array($items['folders'] ?? null) ? $items['folders'] : [];
        usort($folders, static function (array $left, array $right): int {
            return strcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
        });

        foreach ($folders as $folder) {
            $folderPath = $this->normalizeRelativePath((string) ($folder['path'] ?? ''));
            if ($folderPath === '' || isset($visited[$folderPath])) {
                continue;
            }

            $visited[$folderPath] = true;
            $options[] = [
                'path' => $folderPath,
                'label' => str_repeat('— ', min($depth + 1, 8)) . (string) ($folder['name'] ?? $folderPath),
                'depth' => $depth + 1,
            ];

            $this->appendMoveTargetOptions($options, $folderPath, $depth + 1, $visited);
        }
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

    private function buildSettingsChangeSummary(array $original, array $updated): string
    {
        $changedKeys = $this->collectChangedSettingKeys($original, $updated);

        if ($changedKeys === ['keine expliziten Wertänderungen erkannt']) {
            return 'Geänderte Felder: keine expliziten Wertänderungen erkannt – bestehende Einstellungen wurden unverändert bestätigt.';
        }

        return 'Geänderte Felder (' . count($changedKeys) . '): ' . implode(', ', $changedKeys);
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
    private function buildLibraryStateParams(string $path, string $view, string $category, string $search, string $usageFilter, bool $confirmMember): array
    {
        $params = [];

        if ($path !== '') {
            $params['path'] = $path;
        }

        if ($view !== 'list') {
            $params['view'] = $view;
        }

        if ($category !== '') {
            $params['category'] = $category;
        }

        if ($search !== '') {
            $params['q'] = $search;
        }

        if ($usageFilter !== 'all') {
            $params['usage_filter'] = $usageFilter;
        }

        if ($confirmMember) {
            $params['confirm_member'] = '1';
        }

        return $params;
    }

    private function buildAdminUrl(array $params = []): string
    {
        $baseUrl = '/admin/media';
        if ($params === []) {
            return $baseUrl;
        }

        $normalizedParams = array_filter($params, static fn (mixed $value): bool => $value !== '' && $value !== null);

        return $normalizedParams === [] ? $baseUrl : $baseUrl . '?' . http_build_query($normalizedParams);
    }

    /**
     * @return list<array<string, string>>
     */
    private function buildBreadcrumbs(string $path, string $view, string $category, string $search, string $usageFilter, bool $confirmMember): array
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
                'url' => $isLast ? '' : $this->buildAdminUrl($this->buildLibraryStateParams($cumulative, $view, $category, $search, $usageFilter, $confirmMember)),
            ];
        }

        return $breadcrumbs;
    }

    /**
     * @param array<int, array<string, mixed>> $folders
     * @return list<array<string, mixed>>
     */
    private function buildFolderViewModels(array $folders, string $path, string $view, string $category, string $search, string $usageFilter, bool $confirmMember): array
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
                'url' => $this->buildAdminUrl($this->buildLibraryStateParams($folderPath, $view, $category, $search, $usageFilter, $confirmMember)),
                'confirm_url' => $this->buildAdminUrl($this->buildLibraryStateParams($folderPath, $view, $category, $search, $usageFilter, true)),
                'requires_confirmation' => $requiresConfirmation,
            ];
        }

        return $viewModels;
    }

    /**
     * @param array<int, array<string, mixed>> $files
     * @return list<array<string, mixed>>
     */
    private function buildFileViewModels(array $files, string $path, array $usageMap = []): array
    {
        $viewModels = [];

        foreach ($files as $file) {
            $fileName = (string)($file['name'] ?? '');
            $filePath = (string)($file['path'] ?? trim(($path !== '' ? $path . '/' : '') . $fileName, '/'));
            $fileUrl = (string)($file['url'] ?? (UPLOAD_URL . '/' . $filePath));
            $previewUrl = (string)($file['preview_url'] ?? $fileUrl);
            $fileType = $this->detectFileType($fileName);
            $usageItems = array_values(array_filter(
                is_array($usageMap[$filePath] ?? null) ? $usageMap[$filePath] : [],
                static fn (mixed $usage): bool => is_array($usage)
            ));
            $usageCount = count($usageItems);

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
                'usage_items' => $usageItems,
                'usage_count' => $usageCount,
                'usage_count_label' => $usageCount === 1 ? '1 Verwendung' : $usageCount . ' Verwendungen',
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

    /**
     * @param list<array<string, mixed>> $usageItems
     */
    private function featuredMediaMatchesSearch(string $path, array $usageItems, string $search): bool
    {
        $needle = strtolower(trim($search));
        if ($needle === '') {
            return true;
        }

        $haystacks = [
            strtolower($path),
            strtolower(basename($path)),
        ];

        foreach ($usageItems as $usageItem) {
            $haystacks[] = strtolower((string) ($usageItem['title'] ?? ''));
            $haystacks[] = strtolower((string) ($usageItem['content_type_label'] ?? ''));
            $haystacks[] = strtolower((string) ($usageItem['field_label'] ?? ''));
        }

        foreach ($haystacks as $haystack) {
            if ($haystack !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeFeaturedUsageScope(string $usageScope): string
    {
        $usageScope = strtolower(trim($usageScope));

        return in_array($usageScope, ['all', 'posts', 'pages'], true) ? $usageScope : 'all';
    }

    /**
     * @param list<array<string, mixed>> $usageItems
     * @return list<array<string, mixed>>
     */
    private function filterFeaturedUsageItemsByScope(array $usageItems, string $usageScope): array
    {
        if ($usageScope === 'all') {
            return array_values(array_filter($usageItems, static fn (mixed $usageItem): bool => is_array($usageItem)));
        }

        $targetType = $usageScope === 'posts' ? 'post' : 'page';

        return array_values(array_filter(
            $usageItems,
            static fn (mixed $usageItem): bool => is_array($usageItem) && (string) ($usageItem['content_type'] ?? '') === $targetType
        ));
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
