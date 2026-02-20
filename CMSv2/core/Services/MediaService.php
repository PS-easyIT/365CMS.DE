<?php
namespace CMS\Services;

use CMS\WP_Error;
use CMS\Auth;

class MediaService {

    private $uploadPath;
    private $uploadUrl;
    private $metaFile;
    private $settingsFile;

    // Standard folders that cannot be deleted
    private $systemFolders = ['themes', 'plugins', 'assets', 'fonts', 'dl-manager', 'form-uploads'];

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
            $this->uploadUrl .= $relativePath;
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
            'form-uploads' => 'Form Uploads'
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
        return $meta['categories'] ?? [];
    }

    /**
     * Add Category
     */
    public function addCategory(string $name, string $slug = ''): bool|WP_Error {
        $meta = $this->loadMeta();
        $slug = $slug ?: strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        
        foreach ($meta['categories'] as $cat) {
            if ($cat['slug'] === $slug) return new WP_Error('exists', 'Category already exists');
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
    public function deleteCategory(string $slug): bool {
        $meta = $this->loadMeta();
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
        
        if (!isset($meta['files'][$filePath])) {
            $meta['files'][$filePath] = [];
        }
        
        $meta['files'][$filePath]['category'] = $categorySlug;
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
        return file_put_contents($this->metaFile, json_encode($data, JSON_PRETTY_PRINT)) !== false;
    }

    /**
     * Get Media Settings
     * 
     * @return array
     */
    public function getSettings(): array {
        $defaults = [
            'max_upload_size' => '64M',
            'allowed_types' => ['image', 'document', 'video', 'audio', 'archive'],
            'auto_webp' => true,
            'strip_exif' => true,
            'max_width' => 2560,
            'max_height' => 2560,
            'organize_month_year' => true
        ];

        if (!file_exists($this->settingsFile)) {
            return $defaults;
        }

        $content = file_get_contents($this->settingsFile);
        if (!$content) {
            return $defaults;
        }

        $settings = json_decode($content, true);
        return array_merge($defaults, is_array($settings) ? $settings : []);
    }

    /**
     * Save Media Settings
     * 
     * @param array $settings
     * @return bool|WP_Error
     */
    public function saveSettings(array $settings): bool|WP_Error {
        // Validation could go here
        
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
        if (count($parts) > 0 && in_array($parts[0], ['themes', 'plugins', 'assets', 'fonts', 'dl-manager', 'form-uploads'])) {
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
        
        if (is_wp_error($fullPath)) {
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
                        'url' => $this->uploadUrl . '/' . $relativePath,
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

        if (is_wp_error($fullPath)) {
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
        
        // Check file size
        $maxSize = $this->parseSize($settings['max_upload_size'] ?? '64M');
        if ($file['size'] > $maxSize) {
            return new WP_Error('size_limit', 'Datei ist zu groß. Maximum: ' . $settings['max_upload_size']);
        }

        // Check file type
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedGroups = $settings['allowed_types'] ?? ['image', 'document'];
        
        $typeMap = [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'ico'],
            'video' => ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'],
            'audio' => ['mp3', 'wav', 'aac', 'flac', 'm4a'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf', 'csv'],
            'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
            'svg' => ['svg'],
            'plugin' => ['zip'],
            'theme' => ['zip']
        ];
        
        $isAllowed = false;
        foreach ($allowedGroups as $group) {
            if (isset($typeMap[$group]) && in_array($ext, $typeMap[$group])) {
                $isAllowed = true;
                break;
            }
        }
        
        if (!$isAllowed) {
            return new WP_Error('type_not_allowed', 'Dateityp nicht erlaubt: ' . $ext);
        }

        $fullPath = $this->resolvePath($targetPath);

        if (is_wp_error($fullPath)) {
            return $fullPath;
        }

        // Ensure directory exists
        if (!is_dir($fullPath)) {
            if (!mkdir($fullPath, 0755, true)) {
                return new WP_Error('mkdir_failed', 'Failed to create directory: ' . $targetPath);
            }
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', 'File upload error code: ' . $file['error']);
        }


        $fileName = $this->sanitizeFileName($file['name']);
        
        // Ensure unique filename
        $info = pathinfo($fileName);
        $baseName = $info['filename'];
        $extension = $info['extension'] ?? '';
        $counter = 1;
        
        while (file_exists($fullPath . DIRECTORY_SEPARATOR . $fileName)) {
            $fileName = $baseName . '-' . $counter . '.' . $extension;
            $counter++;
        }

        $destination = $fullPath . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return new WP_Error('move_failed', 'Failed to move uploaded file');
        }

        // Save metadata (uploader, category)
        $meta = $this->loadMeta();
        $currentUser = Auth::getCurrentUser();
        $relativePath = str_replace('\\', '/', trim($targetPath . '/' . $fileName, '/'));
        
        // Auto-assign category based on path
        $category = null;
        $pathParts = explode('/', $relativePath);
        if (count($pathParts) > 0 && in_array($pathParts[0], $this->systemFolders)) {
            $category = $pathParts[0];
        }

        $meta['files'][$relativePath] = [
            'uploader_id' => $currentUser->id ?? 0,
            'uploaded_by' => $currentUser->display_name ?? 'System',
            'category' => $category,
            'upload_date' => date('Y-m-d H:i:s')
        ];
        $this->saveMeta($meta);

        return $fileName;
    }

    /**
     * Delete an item (file or folder)
     */
    public function deleteItem(string $path): bool|WP_Error {
        $fullPath = $this->resolvePath($path);

        if (is_wp_error($fullPath)) {
            return $fullPath;
        }

        if (!file_exists($fullPath)) {
            return new WP_Error('not_found', 'Item not found');
        }

        if (is_dir($fullPath)) {
            // Recursive delete directory
            return $this->deleteDirectory($fullPath);
        } else {
            if (!unlink($fullPath)) {
                return new WP_Error('delete_failed', 'Failed to delete file');
            }
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
        
        if (is_wp_error($fullOldPath)) {
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
}
