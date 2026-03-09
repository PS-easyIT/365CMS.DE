<?php
namespace CMS\Services;

use CMS\WP_Error;
use CMS\Auth;

class MediaService {

    private static array $instances = [];

    private $uploadPath;
    private $uploadUrl;
    private $metaFile;
    private $settingsFile;

    // Standard folders that cannot be deleted
    private $systemFolders = ['themes', 'plugins', 'assets', 'fonts', 'dl-manager', 'form-uploads', 'member'];

    public static function getInstance(string $customRoot = ''): self {
        $key = $customRoot !== '' ? $customRoot : '__default__';

        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($customRoot);
        }

        return self::$instances[$key];
    }

    /**
     * Parse ini size string to bytes
     */
    private function parseSize($size) {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }
        return round($size);
    }

    public function __construct($customRoot = '') {
        $this->uploadPath = $customRoot ? rtrim($customRoot, '/\\') : rtrim(UPLOAD_PATH, '/\\');
        $this->uploadUrl = rtrim(UPLOAD_URL, '/\\');
        
        // Correct URL if custom root is inside uploads
        if ($customRoot && strpos($customRoot, UPLOAD_PATH) === 0) {
            $relativePath = str_replace(UPLOAD_PATH, '', $customRoot);
            $relativePath = str_replace('\\', '/', $relativePath); // ensuring forward slashes for URL
            $relativePath = ltrim($relativePath, '/');
            $this->uploadUrl .= '/' . $relativePath;
        }

        $this->settingsFile = dirname(dirname(__DIR__)) . '/config/media-settings.json';
        $this->metaFile = dirname(dirname(__DIR__)) . '/config/media-meta.json';
        
        $this->ensureSystemCategories();
    }

    /**
     * Ensure default system categories exist
     */
    private function ensureSystemCategories(): void {
        $meta = $this->loadMeta();
        $changed = false;
        
        $systemCategories = [
            'themes' => 'Themes',
            'plugins' => 'Plugins',
            'assets' => 'Assets',
            'fonts' => 'Fonts',
            'dl-manager' => 'Downloads',
            'form-uploads' => 'Form Uploads',
            'member' => 'Member'
        ];

        $existingSlugs = array_column($meta['categories'] ?? [], 'slug');

        foreach ($systemCategories as $slug => $name) {
            if (!in_array($slug, $existingSlugs)) {
                $meta['categories'][] = [
                    'name' => $name,
                    'slug' => $slug,
                    'count' => 0,
                    'is_system' => true
                ];
                $changed = true;
            }
        }

        if ($changed) {
            $this->saveMeta($meta);
        }
    }

    /**
     * Get All Categories
     */
    public function getCategories(): array {
        $meta = $this->loadMeta();
        $counts = [];

        foreach (($meta['files'] ?? []) as $fileMeta) {
            $category = $fileMeta['category'] ?? null;
            if ($category) {
                $counts[$category] = ($counts[$category] ?? 0) + 1;
            }
        }

        return array_map(static function(array $category) use ($counts): array {
            $slug = $category['slug'] ?? '';
            $category['count'] = $counts[$slug] ?? 0;
            return $category;
        }, $meta['categories'] ?? []);
    }

    /**
     * Add Category
     */
    public function addCategory(string $name, string $slug = ''): bool|WP_Error {
        $meta = $this->loadMeta();
        $slug = $slug ?: strtolower(trim((string)preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $name = trim($name);

        if ($name === '' || $slug === '') {
            return new WP_Error('invalid_category', 'Name und Slug sind erforderlich');
        }
        
        foreach (($meta['categories'] ?? []) as $cat) {
            if (($cat['slug'] ?? '') === $slug) {
                return new WP_Error('exists', 'Category already exists');
            }
        }

        $meta['categories'][] = [
            'name' => $name,
            'slug' => $slug,
            'count' => 0
        ];

        return $this->saveMeta($meta);
    }

    /**
     * Delete Category
     */
    public function deleteCategory(string $slug): bool|WP_Error {
        $meta = $this->loadMeta();
        $slug = trim($slug);

        if ($slug === '') {
            return new WP_Error('missing_slug', 'Kategorie-Slug fehlt');
        }

        foreach (($meta['categories'] ?? []) as $category) {
            if (($category['slug'] ?? '') === $slug && !empty($category['is_system'])) {
                return new WP_Error('system_category', 'System-Kategorien können nicht gelöscht werden');
            }
        }

        $meta['categories'] = array_filter($meta['categories'], fn($c) => $c['slug'] !== $slug);
        
        // Remove category from files
        foreach ($meta['files'] as &$fileMeta) {
            if (isset($fileMeta['category']) && $fileMeta['category'] === $slug) {
                unset($fileMeta['category']);
            }
        }
        
        return $this->saveMeta($meta);
    }

    /**
     * Assign Category to File
     */
    public function assignCategory(string $filePath, string $categorySlug): bool|WP_Error {
        $meta = $this->loadMeta();
        
        // Normalize path
        $filePath = str_replace('\\', '/', $filePath);

        if ($categorySlug !== '') {
            $validSlugs = array_column($meta['categories'] ?? [], 'slug');
            if (!in_array($categorySlug, $validSlugs, true)) {
                return new WP_Error('invalid_category', 'Kategorie existiert nicht');
            }
        }
        
        if (!isset($meta['files'][$filePath])) {
            $meta['files'][$filePath] = [];
        }
        
        if ($categorySlug === '') {
            unset($meta['files'][$filePath]['category']);
        } else {
            $meta['files'][$filePath]['category'] = $categorySlug;
        }

        return $this->saveMeta($meta);
    }

    private function loadMeta(): array {
        if (!file_exists($this->metaFile)) {
            return ['categories' => [], 'files' => []];
        }
        $content = file_get_contents($this->metaFile);
        $data = json_decode($content, true);
        return [
            'categories' => $data['categories'] ?? [], 
            'files' => $data['files'] ?? []
        ];
    }

    private function saveMeta(array $data): bool {
        return file_put_contents($this->metaFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
    }

    private function buildPublicUrl(string $relativePath): string {
        $normalizedPath = trim(str_replace('\\', '/', $relativePath), '/');
        if ($normalizedPath === '') {
            return $this->uploadUrl;
        }

        $segments = array_map(static function (string $segment): string {
            return rawurlencode($segment);
        }, explode('/', $normalizedPath));

        return $this->uploadUrl . '/' . implode('/', $segments);
    }

    private function getDefaultSettings(): array {
        return [
            'max_upload_size' => '64M',
            'allowed_types' => ['image', 'document', 'video', 'audio', 'archive'],
            'auto_webp' => true,
            'strip_exif' => true,
            'jpeg_quality' => 85,
            'max_width' => 2560,
            'max_height' => 2560,
            'organize_month_year' => true,
            'sanitize_filenames' => true,
            'unique_filenames' => true,
            'lowercase_filenames' => false,
            'member_uploads_enabled' => false,
            'member_max_upload_size' => '5M',
            'member_allowed_types' => ['image', 'document'],
            'member_delete_own' => false,
            'generate_thumbnails' => false,
            'thumb_small_w' => 150,
            'thumb_small_h' => 150,
            'thumb_medium_w' => 300,
            'thumb_medium_h' => 300,
            'thumb_large_w' => 1024,
            'thumb_large_h' => 1024,
            'thumb_banner_w' => 1200,
            'thumb_banner_h' => 400,
            'block_dangerous_types' => false,
            'validate_image_content' => false,
            'require_login_for_upload' => true,
            'protect_uploads_dir' => false,
        ];
    }

    private function getTypeMap(): array {
        return [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'ico'],
            'video' => ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'],
            'audio' => ['mp3', 'wav', 'aac', 'flac', 'm4a'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf', 'csv'],
            'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
            'svg' => ['svg'],
            'plugin' => ['zip'],
            'theme' => ['zip']
        ];
    }

    private function sanitizeSizeSetting(string $size, string $fallback): string {
        $normalized = strtoupper(str_replace(' ', '', trim($size)));
        // Bare number without unit → treat as MB (matches what the admin form sends)
        if (preg_match('/^\d+(?:\.\d+)?$/', $normalized)) {
            $normalized .= 'M';
        }
        return preg_match('/^\d+(?:\.\d+)?(?:B|K|KB|M|MB|G|GB)$/', $normalized) ? $normalized : $fallback;
    }

    private function normalizeSettings(array $settings): array {
        $defaults = $this->getDefaultSettings();
        $allTypes = array_keys($this->getTypeMap());

        $allowedTypes = array_values(array_unique(array_intersect(
            array_map('strval', (array)($settings['allowed_types'] ?? $defaults['allowed_types'])),
            $allTypes
        )));
        if ($allowedTypes === []) {
            $allowedTypes = $defaults['allowed_types'];
        }

        $memberAllowedTypes = array_values(array_unique(array_intersect(
            array_map('strval', (array)($settings['member_allowed_types'] ?? $defaults['member_allowed_types'])),
            ['image', 'document', 'video', 'audio']
        )));
        if ($memberAllowedTypes === []) {
            $memberAllowedTypes = $defaults['member_allowed_types'];
        }

        return array_merge($defaults, [
            'max_upload_size' => $this->sanitizeSizeSetting((string)($settings['max_upload_size'] ?? $defaults['max_upload_size']), $defaults['max_upload_size']),
            'allowed_types' => $allowedTypes,
            'auto_webp' => !empty($settings['auto_webp']),
            'strip_exif' => !empty($settings['strip_exif']),
            'jpeg_quality' => max(60, min(100, (int)($settings['jpeg_quality'] ?? $defaults['jpeg_quality']))),
            'max_width' => max(1, min(8000, (int)($settings['max_width'] ?? $defaults['max_width']))),
            'max_height' => max(1, min(8000, (int)($settings['max_height'] ?? $defaults['max_height']))),
            'organize_month_year' => !empty($settings['organize_month_year']),
            'sanitize_filenames' => !empty($settings['sanitize_filenames']),
            'unique_filenames' => !empty($settings['unique_filenames']),
            'lowercase_filenames' => !empty($settings['lowercase_filenames']),
            'member_uploads_enabled' => !empty($settings['member_uploads_enabled']),
            'member_max_upload_size' => $this->sanitizeSizeSetting((string)($settings['member_max_upload_size'] ?? $defaults['member_max_upload_size']), $defaults['member_max_upload_size']),
            'member_allowed_types' => $memberAllowedTypes,
            'member_delete_own' => !empty($settings['member_delete_own']),
            'generate_thumbnails' => !empty($settings['generate_thumbnails']),
            'thumb_small_w' => max(50, min(4000, (int)($settings['thumb_small_w'] ?? $defaults['thumb_small_w']))),
            'thumb_small_h' => max(50, min(4000, (int)($settings['thumb_small_h'] ?? $defaults['thumb_small_h']))),
            'thumb_medium_w' => max(50, min(4000, (int)($settings['thumb_medium_w'] ?? $defaults['thumb_medium_w']))),
            'thumb_medium_h' => max(50, min(4000, (int)($settings['thumb_medium_h'] ?? $defaults['thumb_medium_h']))),
            'thumb_large_w' => max(50, min(6000, (int)($settings['thumb_large_w'] ?? $defaults['thumb_large_w']))),
            'thumb_large_h' => max(50, min(6000, (int)($settings['thumb_large_h'] ?? $defaults['thumb_large_h']))),
            'thumb_banner_w' => max(50, min(6000, (int)($settings['thumb_banner_w'] ?? $defaults['thumb_banner_w']))),
            'thumb_banner_h' => max(50, min(6000, (int)($settings['thumb_banner_h'] ?? $defaults['thumb_banner_h']))),
            'block_dangerous_types' => !empty($settings['block_dangerous_types']),
            'validate_image_content' => !empty($settings['validate_image_content']),
            'require_login_for_upload' => !empty($settings['require_login_for_upload']),
            'protect_uploads_dir' => !empty($settings['protect_uploads_dir']),
        ]);
    }

    private function getDangerousExtensions(): array {
        return [
            'php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'exe', 'com', 'bat', 'cmd', 'ps1', 'sh', 'pl', 'cgi', 'jar', 'msi', 'vbs', 'scr', 'dll', 'asp', 'aspx', 'jspx'
        ];
    }

    private function isImageExtension(string $ext): bool {
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'ico'], true);
    }

    private function buildThumbnailSizes(array $settings): array {
        return [
            'small' => [(int)$settings['thumb_small_w'], (int)$settings['thumb_small_h']],
            'medium' => [(int)$settings['thumb_medium_w'], (int)$settings['thumb_medium_h']],
            'large' => [(int)$settings['thumb_large_w'], (int)$settings['thumb_large_h']],
            'banner' => [(int)$settings['thumb_banner_w'], (int)$settings['thumb_banner_h']],
        ];
    }

    private function getGeneratedVariantPaths(string $fullPath): array {
        $variants = [];
        $info = pathinfo($fullPath);
        $extension = $info['extension'] ?? '';
        if ($extension === '') {
            return $variants;
        }

        foreach (array_keys($this->buildThumbnailSizes($this->getSettings())) as $name) {
            $variants[] = $info['dirname'] . DIRECTORY_SEPARATOR . $info['filename'] . '-' . $name . '.' . $extension;
        }

        return $variants;
    }

    private function purgeMetaForPath(string $relativePath): void {
        $meta = $this->loadMeta();
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');

        foreach (array_keys($meta['files']) as $path) {
            if ($path === $relativePath || str_starts_with($path, $relativePath . '/')) {
                unset($meta['files'][$path]);
            }
        }

        $this->saveMeta($meta);
    }

    private function renameMetaPath(string $oldRelativePath, string $newRelativePath): void {
        $meta = $this->loadMeta();
        $oldRelativePath = trim(str_replace('\\', '/', $oldRelativePath), '/');
        $newRelativePath = trim(str_replace('\\', '/', $newRelativePath), '/');
        $updated = [];

        foreach ($meta['files'] as $path => $data) {
            if ($path === $oldRelativePath || str_starts_with($path, $oldRelativePath . '/')) {
                $suffix = ltrim(substr($path, strlen($oldRelativePath)), '/');
                $newPath = $newRelativePath . ($suffix !== '' ? '/' . $suffix : '');
                $updated[$newPath] = $data;
                unset($meta['files'][$path]);
            }
        }

        $meta['files'] = array_merge($meta['files'], $updated);
        $this->saveMeta($meta);
    }

    private function syncUploadsProtection(bool $enabled): bool|WP_Error {
        $htaccessPath = $this->uploadPath . DIRECTORY_SEPARATOR . '.htaccess';

        if (!$enabled) {
            if (file_exists($htaccessPath) && !unlink($htaccessPath)) {
                return new WP_Error('protection_remove_failed', 'Upload-Verzeichnis-Schutz konnte nicht entfernt werden.');
            }

            return true;
        }

        $content = "<FilesMatch \"\\.(php|php3|php4|php5|phtml|phar|cgi|pl|py|sh)$\">\n    Require all denied\n</FilesMatch>\nOptions -ExecCGI\n";
        if (file_put_contents($htaccessPath, $content) === false) {
            return new WP_Error('protection_write_failed', 'Upload-Verzeichnis-Schutz konnte nicht geschrieben werden.');
        }

        return true;
    }

    private function reEncodeImage(string $path, string $ext, int $quality): bool {
        if (!function_exists('imagejpeg')) {
            return false;
        }

        $ext = strtolower($ext);
        $image = match ($ext) {
            'jpg', 'jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : false,
            'png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($path) : false,
            'gif' => function_exists('imagecreatefromgif') ? @imagecreatefromgif($path) : false,
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            'bmp' => function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($path) : false,
            default => false,
        };

        if ($image === false) {
            return false;
        }

        $saved = match ($ext) {
            'jpg', 'jpeg' => imagejpeg($image, $path, $quality),
            'png' => imagepng($image, $path, max(0, min(9, (int)round((100 - $quality) / 10)))),
            'gif' => imagegif($image, $path),
            'webp' => function_exists('imagewebp') ? imagewebp($image, $path, $quality) : false,
            'bmp' => function_exists('imagebmp') ? imagebmp($image, $path) : false,
            default => false,
        };

        imagedestroy($image);
        return $saved;
    }

    /**
     * Get Media Settings
     * 
     * @return array
     */
    public function getSettings(): array {
        $defaults = $this->getDefaultSettings();

        if (!file_exists($this->settingsFile)) {
            return $defaults;
        }

        $content = file_get_contents($this->settingsFile);
        if (!$content) {
            return $defaults;
        }

        $settings = json_decode($content, true);
        return $this->normalizeSettings(is_array($settings) ? $settings : []);
    }

    /**
     * Save Media Settings
     * 
     * @param array $settings
     * @return bool|WP_Error
     */
    public function saveSettings(array $settings): bool|WP_Error {
        $settings = $this->normalizeSettings($settings);
        $protectionResult = $this->syncUploadsProtection((bool)($settings['protect_uploads_dir'] ?? false));
        if ($protectionResult instanceof WP_Error) {
            return $protectionResult;
        }
        
        $json = json_encode($settings, JSON_PRETTY_PRINT);
        if ($json === false) {
            return new WP_Error('json_error', 'Invalid settings data');
        }

        if (file_put_contents($this->settingsFile, $json) === false) {
            return new WP_Error('write_error', 'Could not write settings file');
        }

        return true;
    }

    private function detectSystemCategory(string $relativePath): ?string {
        $parts = explode('/', $relativePath);
        if (count($parts) > 0 && in_array($parts[0], $this->systemFolders, true)) {
            return $parts[0];
        }
        return null;
    }
    
    private function isSystemPath(string $relativePath): bool {
        $parts = explode('/', $relativePath);
        return count($parts) > 0 && in_array($parts[0], $this->systemFolders);
    }

    /**
     * List items in a directory
     * 
     * @param string $path Relative path from UPLOAD_PATH
     * @return array|WP_Error Array of items or WP_Error on failure
     */
    public function getItems(string $path = ''): array|WP_Error {
        $fullPath = $this->resolvePath($path);
        
        if ($fullPath instanceof WP_Error) {
            return $fullPath;
        }

        if (!is_dir($fullPath)) {
            return new WP_Error('invalid_path', 'Directory does not exist');
        }

        $items = [
            'folders' => [],
            'files' => []
        ];
        
        $meta = $this->loadMeta();

        try {
            $dirContent = scandir($fullPath);
            foreach ($dirContent as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                $itemPath = $fullPath . DIRECTORY_SEPARATOR . $item;
                $relativePath = trim($path . '/' . $item, '/');
                $keyPath = str_replace('\\', '/', $relativePath);
                
                $metaData = $meta['files'][$keyPath] ?? [];
                $category = $metaData['category'] ?? $this->detectSystemCategory($relativePath);
                $uploaderId = $metaData['uploader_id'] ?? null;
                $uploadedBy = $metaData['uploaded_by'] ?? null;
                $isSystem = $this->isSystemPath($relativePath);
                
                if (is_dir($itemPath)) {
                    $items['folders'][] = [
                        'name' => $item,
                        'path' => $relativePath,
                        'type' => 'folder',
                        'modified' => filemtime($itemPath),
                        'items_count' => count(scandir($itemPath)) - 2,
                        'category' => $category,
                        'uploader_id' => $uploaderId,
                        'uploaded_by' => $uploadedBy,
                        'is_system' => $isSystem
                    ];
                } else {
                    $items['files'][] = [
                        'name' => $item,
                        'path' => $relativePath,
                        'url' => $this->buildPublicUrl($relativePath),
                        'type' => pathinfo($itemPath, PATHINFO_EXTENSION),
                        'size' => filesize($itemPath),
                        'modified' => filemtime($itemPath),
                        'mime_type' => mime_content_type($itemPath),
                        'category' => $category,
                        'uploader_id' => $uploaderId,
                        'uploaded_by' => $uploadedBy,
                        'is_system' => $isSystem
                    ];
                }
            }
        } catch (\Exception $e) {
            return new WP_Error('scan_error', $e->getMessage());
        }

        return $items;
    }

    /**
     * Create a new folder
     */
    public function createFolder(string $name, string $parentPath = ''): bool|WP_Error {
        $name = $this->sanitizeFileName($name);
        $fullPath = $this->resolvePath($parentPath);

        if ($fullPath instanceof WP_Error) {
            return $fullPath;
        }

        $newFolderPath = $fullPath . DIRECTORY_SEPARATOR . $name;

        if (file_exists($newFolderPath)) {
            return new WP_Error('exists', 'Folder already exists');
        }

        if (!mkdir($newFolderPath, 0755, true)) {
            return new WP_Error('mkdir_failed', 'Failed to create folder');
        }

        // Save metadata (uploader) for folder
        $meta = $this->loadMeta();
        $currentUser = Auth::getCurrentUser();
        $relativePath = str_replace('\\', '/', trim($parentPath . '/' . $name, '/'));
        
        $meta['files'][$relativePath] = [
            'uploader_id' => $currentUser->id ?? 0,
            'uploaded_by' => $currentUser->display_name ?? 'System',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Auto-assign category
        $pathParts = explode('/', $relativePath);
        if (count($pathParts) > 0 && in_array($pathParts[0], $this->systemFolders)) {
            $meta['files'][$relativePath]['category'] = $pathParts[0];
        }

        $this->saveMeta($meta);

        return true;
    }

    /**
     * Upload a file
     */
    public function uploadFile(array $file, string $targetPath = ''): string|WP_Error {
        $settings = $this->getSettings();
        $targetPath = trim(str_replace('\\', '/', $targetPath), '/');

        $maxSize = $this->parseSize($settings['max_upload_size'] ?? '64M');
        if (($file['size'] ?? 0) > $maxSize) {
            return new WP_Error('size_limit', 'Datei ist zu groß. Maximum: ' . $settings['max_upload_size']);
        }

        $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        $allowedGroups = $settings['allowed_types'] ?? ['image', 'document'];
        $typeMap = $this->getTypeMap();

        if (($settings['block_dangerous_types'] ?? false) && in_array($ext, $this->getDangerousExtensions(), true)) {
            return new WP_Error('dangerous_type', 'Gefährlicher Dateityp wurde blockiert: ' . $ext);
        }

        $isAllowed = false;
        foreach ($allowedGroups as $group) {
            if (isset($typeMap[$group]) && in_array($ext, $typeMap[$group], true)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            return new WP_Error('type_not_allowed', 'Dateityp nicht erlaubt: ' . $ext);
        }

        $allowedMimeMap = [
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
            'gif'  => ['image/gif'],
            'webp' => ['image/webp'],
            'bmp'  => ['image/bmp', 'image/x-bmp'],
            'ico'  => ['image/x-icon', 'image/vnd.microsoft.icon'],
            'svg'  => ['image/svg+xml', 'text/plain', 'text/html'],
            'mp4'  => ['video/mp4'],
            'webm' => ['video/webm'],
            'ogg'  => ['video/ogg', 'audio/ogg', 'application/ogg'],
            'mov'  => ['video/quicktime'],
            'avi'  => ['video/avi', 'video/x-msvideo'],
            'mkv'  => ['video/x-matroska'],
            'mp3'  => ['audio/mpeg', 'audio/mp3'],
            'wav'  => ['audio/wav', 'audio/x-wav'],
            'aac'  => ['audio/aac', 'audio/x-aac'],
            'flac' => ['audio/flac', 'audio/x-flac'],
            'm4a'  => ['audio/m4a', 'audio/x-m4a', 'audio/mp4'],
            'pdf'  => ['application/pdf'],
            'doc'  => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls'  => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'ppt'  => ['application/vnd.ms-powerpoint'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'txt'  => ['text/plain'],
            'rtf'  => ['text/rtf', 'application/rtf', 'text/richtext'],
            'csv'  => ['text/csv', 'text/plain', 'application/csv'],
            'zip'  => ['application/zip', 'application/x-zip-compressed', 'application/x-zip'],
            'rar'  => ['application/x-rar-compressed', 'application/vnd.rar'],
            '7z'   => ['application/x-7z-compressed'],
            'tar'  => ['application/x-tar'],
            'gz'   => ['application/gzip', 'application/x-gzip'],
        ];

        if (function_exists('finfo_open') && isset($allowedMimeMap[$ext])) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $realMime = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
            if ($finfo) {
                finfo_close($finfo);
            }

            if ($realMime !== false && !in_array($realMime, $allowedMimeMap[$ext], true)) {
                return new WP_Error('mime_mismatch', sprintf('MIME-Typ "%s" passt nicht zur Dateiendung ".%s". Upload abgebrochen.', $realMime, $ext));
            }
        }

        $textExtensions = ['txt', 'csv', 'rtf', 'svg', 'html', 'htm', 'xml'];
        if (in_array($ext, $textExtensions, true) || ($file['size'] ?? 0) < 512 * 1024) {
            $content = is_readable($file['tmp_name'])
                ? file_get_contents($file['tmp_name'], false, null, 0, 65536)
                : false;
            if ($content !== false && (
                stripos($content, '<?php') !== false ||
                strpos($content, '<?=') !== false ||
                strpos($content, '<%') !== false
            )) {
                return new WP_Error('php_code_detected', 'Die hochgeladene Datei enthält PHP- oder Server-Code und wurde abgelehnt.');
            }
        }

        if (($settings['validate_image_content'] ?? false) && $this->isImageExtension($ext) && @getimagesize($file['tmp_name']) === false) {
            return new WP_Error('invalid_image', 'Die Datei enthält keine gültigen Bilddaten.');
        }

        $fullPath = $this->resolvePath($targetPath);
        if ($fullPath instanceof WP_Error) {
            return $fullPath;
        }

        if (!is_dir($fullPath) && !mkdir($fullPath, 0755, true)) {
            return new WP_Error('mkdir_failed', 'Failed to create directory: ' . $targetPath);
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', 'File upload error code: ' . (int)$file['error']);
        }

        $originalInfo = pathinfo((string)$file['name']);
        $baseName = (string)($originalInfo['filename'] ?? 'upload');
        $baseName = ($settings['sanitize_filenames'] ?? true)
            ? $this->sanitizeFileName($baseName)
            : trim(str_replace(['/', '\\', "\0"], '_', $baseName));
        if ($baseName === '') {
            $baseName = 'upload';
        }

        if ($settings['lowercase_filenames'] ?? false) {
            $baseName = strtolower($baseName);
            $ext = strtolower($ext);
        }

        $willConvertToWebp = ($settings['auto_webp'] ?? false) && in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true);
        $finalExtension = $willConvertToWebp ? 'webp' : $ext;
        $fileName = $baseName . ($finalExtension !== '' ? '.' . $finalExtension : '');
        $counter = 1;

        while (file_exists($fullPath . DIRECTORY_SEPARATOR . $fileName)) {
            if (!($settings['unique_filenames'] ?? true)) {
                return new WP_Error('exists', 'Eine Datei mit diesem Namen existiert bereits.');
            }

            $fileName = $baseName . '-' . $counter . ($finalExtension !== '' ? '.' . $finalExtension : '');
            $counter++;
        }

        $destinationBaseName = pathinfo($fileName, PATHINFO_FILENAME);
        $temporaryFileName = $destinationBaseName . ($ext !== '' ? '.' . $ext : '');
        $destination = $fullPath . DIRECTORY_SEPARATOR . $temporaryFileName;

        $moved = move_uploaded_file($file['tmp_name'], $destination);
        if (!$moved && is_readable((string)$file['tmp_name'])) {
            $moved = @rename((string)$file['tmp_name'], $destination);
            if (!$moved) {
                $moved = @copy((string)$file['tmp_name'], $destination);
                if ($moved) {
                    @unlink((string)$file['tmp_name']);
                }
            }
        }

        if (!$moved) {
            return new WP_Error('move_failed', 'Failed to move uploaded file');
        }

        $imageDimensions = null;
        if ($this->isImageExtension($ext)) {
            $imageService = ImageService::getInstance();
            $imageService->autoOrient($destination);
            $imageService->resize(
                $destination,
                max(1, (int)($settings['max_width'] ?? 2560)),
                max(1, (int)($settings['max_height'] ?? 2560)),
                $destination,
                (int)($settings['jpeg_quality'] ?? 85)
            );

            if ($settings['strip_exif'] ?? false) {
                $this->reEncodeImage($destination, $ext, (int)($settings['jpeg_quality'] ?? 85));
            }
        }

        if ($willConvertToWebp) {
            $webpPath = $this->convertToWebP($destination, $ext, (int)($settings['jpeg_quality'] ?? 82));
            if ($webpPath !== null) {
                if (file_exists($destination)) {
                    unlink($destination);
                }
                $destination = $webpPath;
                $fileName = basename($webpPath);
                $ext = 'webp';
            } else {
                $fileName = basename($destination);
            }
        } else {
            $fileName = basename($destination);
        }

        if (($settings['generate_thumbnails'] ?? false) && $this->isImageExtension($ext)) {
            ImageService::getInstance()->createAllThumbnails($destination, $this->buildThumbnailSizes($settings));
        }

        if ($this->isImageExtension($ext)) {
            $imageDimensions = ImageService::getInstance()->getDimensions($destination);
        }

        $meta = $this->loadMeta();
        $currentUser = Auth::getCurrentUser();
        $relativePath = str_replace('\\', '/', trim($targetPath . '/' . $fileName, '/'));

        $category = null;
        $pathParts = explode('/', $relativePath);
        if (count($pathParts) > 0 && in_array($pathParts[0], $this->systemFolders, true)) {
            $category = $pathParts[0];
        }

        $meta['files'][$relativePath] = [
            'uploader_id' => $currentUser->id ?? 0,
            'uploaded_by' => $currentUser->display_name ?? 'System',
            'category' => $category,
            'upload_date' => date('Y-m-d H:i:s'),
            'dimensions' => $imageDimensions,
        ];
        $this->saveMeta($meta);

        return $fileName;
    }

    /**
     * Ensure a custom category exists (creates it if missing).
     * Returns the slug on success.
     */
    public function ensureCategory(string $name, string $slug): string
    {
        $meta = $this->loadMeta();
        $existingSlugs = array_column($meta['categories'] ?? [], 'slug');
        if (!in_array($slug, $existingSlugs, true)) {
            $meta['categories'][] = [
                'name'      => $name,
                'slug'      => $slug,
                'count'     => 0,
                'is_system' => false,
            ];
            $this->saveMeta($meta);
        }
        return $slug;
    }

    /**
     * Move a file to a new relative path inside the upload directory.
     * Updates meta (keeps category, uploader etc.) and updates thumbnail variants.
     * Returns the new relative path (forward slashes) or WP_Error.
     */
    public function moveFile(string $relativeOldPath, string $relativeNewPath): string|WP_Error
    {
        $relativeOldPath = trim(str_replace('\\', '/', $relativeOldPath), '/');
        $relativeNewPath = trim(str_replace('\\', '/', $relativeNewPath), '/');

        if ($relativeOldPath === $relativeNewPath) {
            return $relativeNewPath;
        }

        $fullOld = $this->resolvePath($relativeOldPath);
        if ($fullOld instanceof WP_Error) {
            return $fullOld;
        }

        if (!file_exists($fullOld)) {
            return new WP_Error('not_found', 'Quelldatei nicht gefunden: ' . $relativeOldPath);
        }

        $fullNew = rtrim($this->uploadPath, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeNewPath);
        $newDir  = dirname($fullNew);

        if (!is_dir($newDir) && !mkdir($newDir, 0755, true)) {
            return new WP_Error('mkdir_failed', 'Zielverzeichnis konnte nicht erstellt werden.');
        }

        // If destination exists, append a counter to avoid overwriting unrelated files
        if (file_exists($fullNew) && $fullNew !== $fullOld) {
            $ext     = pathinfo($relativeNewPath, PATHINFO_EXTENSION);
            $base    = $ext !== '' ? substr($relativeNewPath, 0, -(strlen($ext) + 1)) : $relativeNewPath;
            $counter = 1;
            do {
                $relativeNewPath = $base . '-' . $counter . ($ext !== '' ? '.' . $ext : '');
                $fullNew = rtrim($this->uploadPath, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeNewPath);
                $counter++;
            } while (file_exists($fullNew));
        }

        if (!rename($fullOld, $fullNew)) {
            return new WP_Error('move_failed', 'Datei konnte nicht verschoben werden.');
        }

        // Move thumbnail variants as well
        $oldVariants = $this->getGeneratedVariantPaths($fullOld);
        $newVariants = $this->getGeneratedVariantPaths($fullNew);
        foreach ($oldVariants as $i => $oldVariant) {
            if (isset($newVariants[$i]) && file_exists($oldVariant)) {
                @rename($oldVariant, $newVariants[$i]);
            }
        }

        // Update meta: transfer entry from old path to new path
        $meta = $this->loadMeta();
        $existingMeta = $meta['files'][$relativeOldPath] ?? [];
        unset($meta['files'][$relativeOldPath]);
        $meta['files'][$relativeNewPath] = $existingMeta;
        $this->saveMeta($meta);

        return $relativeNewPath;
    }

    /**
     * Delete an item (file or folder)
     */
    public function deleteItem(string $path): bool|WP_Error {
        $fullPath = $this->resolvePath($path);
        $normalizedPath = trim(str_replace('\\', '/', $path), '/');

        if ($fullPath instanceof WP_Error) {
            return $fullPath;
        }

        if (!file_exists($fullPath)) {
            return new WP_Error('not_found', 'Item not found');
        }

        if (is_dir($fullPath)) {
            // Recursive delete directory
            if (!$this->deleteDirectory($fullPath)) {
                return new WP_Error('delete_failed', 'Failed to delete directory');
            }

            $this->purgeMetaForPath($normalizedPath);
            return true;
        } else {
            foreach ([$fullPath, ...$this->getGeneratedVariantPaths($fullPath)] as $filePath) {
                if (file_exists($filePath) && !unlink($filePath)) {
                    return new WP_Error('delete_failed', 'Failed to delete file');
                }
            }

            $this->purgeMetaForPath($normalizedPath);
        }

        return true;
    }

    private function deleteDirectory(string $dir): bool {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }

    /**
     * Rename an item
     */
    public function renameItem(string $oldPath, string $newName): bool|WP_Error {
        $fullOldPath = $this->resolvePath($oldPath);
        $oldPath = trim(str_replace('\\', '/', $oldPath), '/');
        
        if ($fullOldPath instanceof WP_Error) {
            return $fullOldPath;
        }
        
        // Determine parent directory of the old path
        $parentDir = dirname($fullOldPath);
        $newName = $this->sanitizeFileName($newName);
        $fullNewPath = $parentDir . DIRECTORY_SEPARATOR . $newName;

        if (!file_exists($fullOldPath)) {
            return new WP_Error('not_found', 'Item not found');
        }

        if (file_exists($fullNewPath)) {
            return new WP_Error('exists', 'Destination already exists');
        }

        if (!rename($fullOldPath, $fullNewPath)) {
            return new WP_Error('rename_failed', 'Failed to rename item');
        }

        $newRelativePath = trim(str_replace('\\', '/', dirname($oldPath)), '/');
        $newRelativePath = trim(($newRelativePath !== '' ? $newRelativePath . '/' : '') . $newName, '/');

        if (is_dir($fullNewPath)) {
            $this->renameMetaPath($oldPath, $newRelativePath);
            return true;
        }

        $oldVariants = $this->getGeneratedVariantPaths($fullOldPath);
        $newVariants = $this->getGeneratedVariantPaths($fullNewPath);
        foreach ($oldVariants as $index => $oldVariant) {
            $newVariant = $newVariants[$index] ?? null;
            if ($newVariant !== null && file_exists($oldVariant)) {
                rename($oldVariant, $newVariant);
            }
        }

        $this->renameMetaPath($oldPath, $newRelativePath);

        return true;
    }

    /**
     * Resolve and validate path
     */
    private function resolvePath(string $path): string|WP_Error {
        // Prevent path traversal
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
        
        // Remove dot segments manually since realpath() might fail if file doesn't exist yet/deleted
        // But here we want the target *directory* + subpath.
        
        // Simple security check: subpath must not contain '..'
        if (strpos($path, '..') !== false) {
             return new WP_Error('security_violation', 'Invalid path');
        }

        $fullPath = $this->uploadPath;
        if (!empty($path)) {
            $fullPath .= DIRECTORY_SEPARATOR . trim($path, DIRECTORY_SEPARATOR);
        }
        
        // Ensure path starts with uploadPath
        if (strpos($fullPath, $this->uploadPath) !== 0) {
            return new WP_Error('security_violation', 'Path traversal detected');
        }

        return $fullPath;
    }
    
    /**
     * Sanitize filename
     */
    private function sanitizeFileName(string $filename): string {
        // Remove special chars, keep alphanumeric, dots, dashes, underscores
        $filename = preg_replace('/[^\w\-\.]/', '_', $filename);
        return $filename;
    }

    /**
     * Get disk usage
     */
    public function getDiskUsage(): array {
        $size = 0;
        $count = 0;
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->uploadPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $size += $file->getSize();
            $count++;
        }

        return [
            'size' => $size,
            'count' => $count,
            'formatted' => $this->formatSize($size)
        ];
    }

    public function formatSize(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * M-07: Konvertiert ein Bild (JPG/PNG/GIF) in WebP und speichert die neue Datei.
     *
     * Voraussetzungen: PHP-GD-Extension mit WebP-Support.
     * Gibt den Pfad zur WebP-Datei zurück oder null bei Fehler/nicht unterstützt.
     *
     * @param  string $sourcePath  Absoluter Pfad zur Quelldatei (bereits gespeichert)
     * @param  string $ext         Dateiendung (jpg|jpeg|png|gif)
     * @param  int    $quality     WebP-Qualität 0–100 (Standard: 82)
     * @return string|null         Absoluter Pfad zur .webp-Datei oder null
     */
    public function convertToWebP(string $sourcePath, string $ext, int $quality = 82): ?string
    {
        if (!function_exists('imagewebp') || !function_exists('imagecreatefromjpeg')) {
            return null; // GD nicht verfügbar oder ohne WebP-Kompilierung
        }

        $image = match (strtolower($ext)) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($sourcePath),
            'png'         => @imagecreatefrompng($sourcePath),
            'gif'         => @imagecreatefromgif($sourcePath),
            default       => null,
        };

        if ($image === null || $image === false) {
            return null;
        }

        // PNG-Transparenz erhalten
        if (in_array(strtolower($ext), ['png'], true)) {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }

        $webpPath = preg_replace('/\.' . preg_quote($ext, '/') . '$/i', '.webp', $sourcePath);

        if ($webpPath === null || $webpPath === $sourcePath) {
            imagedestroy($image);
            return null;
        }

        $success = imagewebp($image, $webpPath, $quality);
        imagedestroy($image);

        return $success ? $webpPath : null;
    }
}
