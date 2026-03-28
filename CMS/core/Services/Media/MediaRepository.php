<?php
declare(strict_types=1);

namespace CMS\Services\Media;

use CMS\Auth;
use CMS\Json;
use CMS\Services\MediaDeliveryService;
use CMS\WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

final class MediaRepository
{
    public function __construct(
        private readonly string $uploadPath,
        private readonly string $uploadUrl,
        private readonly string $metaFile,
        private readonly array $systemFolders
    ) {
        $this->ensureSystemCategories();
    }

    public function ensureSystemCategories(): void
    {
        $meta = $this->loadMeta();
        $changed = false;
        $systemCategories = [
            'themes' => 'Themes',
            'plugins' => 'Plugins',
            'assets' => 'Assets',
            'fonts' => 'Fonts',
            'dl-manager' => 'Downloads',
            'form-uploads' => 'Form Uploads',
            'member' => 'Member',
        ];

        $existingSlugs = array_column($meta['categories'] ?? [], 'slug');
        foreach ($systemCategories as $slug => $name) {
            if (in_array($slug, $existingSlugs, true)) {
                continue;
            }

            $meta['categories'][] = [
                'name' => $name,
                'slug' => $slug,
                'count' => 0,
                'is_system' => true,
            ];
            $changed = true;
        }

        if ($changed) {
            $this->saveMeta($meta);
        }
    }

    public function getCategories(): array
    {
        $meta = $this->loadMeta();
        $counts = [];

        foreach (($meta['files'] ?? []) as $fileMeta) {
            $category = $fileMeta['category'] ?? null;
            if ($category) {
                $counts[$category] = ($counts[$category] ?? 0) + 1;
            }
        }

        return array_map(static function (array $category) use ($counts): array {
            $slug = $category['slug'] ?? '';
            $category['count'] = $counts[$slug] ?? 0;
            return $category;
        }, $meta['categories'] ?? []);
    }

    public function addCategory(string $name, string $slug = ''): bool|WP_Error
    {
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
            'count' => 0,
        ];

        return $this->saveMeta($meta);
    }

    public function deleteCategory(string $slug): bool|WP_Error
    {
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

        $meta['categories'] = array_values(array_filter(
            $meta['categories'] ?? [],
            static fn(array $category): bool => ($category['slug'] ?? '') !== $slug
        ));

        foreach ($meta['files'] as &$fileMeta) {
            if (($fileMeta['category'] ?? null) === $slug) {
                unset($fileMeta['category']);
            }
        }
        unset($fileMeta);

        return $this->saveMeta($meta);
    }

    public function assignCategory(string $filePath, string $categorySlug): bool|WP_Error
    {
        $meta = $this->loadMeta();
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

    public function loadMeta(): array
    {
        if (!file_exists($this->metaFile)) {
            return ['categories' => [], 'files' => []];
        }

        $content = file_get_contents($this->metaFile);
        $data = Json::decodeArray($content ?: '', []);

        return [
            'categories' => $data['categories'] ?? [],
            'files' => $data['files'] ?? [],
        ];
    }

    public function saveMeta(array $data): bool
    {
        return file_put_contents($this->metaFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
    }

    public function buildPublicUrl(string $relativePath): string
    {
        return MediaDeliveryService::getInstance()->buildAccessUrl($relativePath, false);
    }

    public function buildPreviewUrl(string $relativePath): string
    {
        return MediaDeliveryService::getInstance()->buildPreviewUrl($relativePath);
    }

    public function detectSystemCategory(string $relativePath): ?string
    {
        $parts = explode('/', $relativePath);
        if ($parts !== [] && in_array($parts[0], $this->systemFolders, true)) {
            return $parts[0];
        }

        return null;
    }

    public function isSystemPath(string $relativePath): bool
    {
        $normalized = trim(str_replace('\\', '/', $relativePath), '/');
        $parts = array_values(array_filter(explode('/', $normalized)));

        if ($parts === []) {
            return false;
        }

        // Top-level system folders (e.g. 'member', 'themes', 'plugins')
        if (count($parts) === 1 && in_array($parts[0], $this->systemFolders, true)) {
            return true;
        }

        // Direct children of 'member' (member/user-X) are also protected
        if (count($parts) === 2 && $parts[0] === 'member') {
            return true;
        }

        return false;
    }

    public function getItems(string $path = ''): array|WP_Error
    {
        $fullPath = $this->resolvePath($path);
        if ($fullPath instanceof WP_Error) {
            return $fullPath;
        }

        if (!is_dir($fullPath)) {
            return new WP_Error('invalid_path', 'Directory does not exist');
        }

        $items = ['folders' => [], 'files' => []];
        $meta = $this->loadMeta();

        try {
            foreach (scandir($fullPath) ?: [] as $item) {
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
                        'items_count' => count(scandir($itemPath) ?: []) - 2,
                        'category' => $category,
                        'uploader_id' => $uploaderId,
                        'uploaded_by' => $uploadedBy,
                        'is_system' => $isSystem,
                    ];
                    continue;
                }

                $items['files'][] = [
                    'name' => $item,
                    'path' => $relativePath,
                    'url' => $this->buildPublicUrl($relativePath),
                    'preview_url' => $this->buildPreviewUrl($relativePath),
                    'type' => pathinfo($itemPath, PATHINFO_EXTENSION),
                    'size' => filesize($itemPath),
                    'modified' => filemtime($itemPath),
                    'mime_type' => mime_content_type($itemPath),
                    'category' => $category,
                    'uploader_id' => $uploaderId,
                    'uploaded_by' => $uploadedBy,
                    'is_system' => $isSystem,
                ];
            }
        } catch (\Throwable $e) {
            return new WP_Error('scan_error', $e->getMessage());
        }

        return $items;
    }

    public function ensureCategory(string $name, string $slug): string
    {
        $meta = $this->loadMeta();
        $existingSlugs = array_column($meta['categories'] ?? [], 'slug');
        if (!in_array($slug, $existingSlugs, true)) {
            $meta['categories'][] = [
                'name' => $name,
                'slug' => $slug,
                'count' => 0,
                'is_system' => false,
            ];
            $this->saveMeta($meta);
        }

        return $slug;
    }

    public function purgeMetaForPath(string $relativePath): void
    {
        $meta = $this->loadMeta();
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');

        foreach (array_keys($meta['files']) as $path) {
            if ($path === $relativePath || str_starts_with($path, $relativePath . '/')) {
                unset($meta['files'][$path]);
            }
        }

        $this->saveMeta($meta);
    }

    public function renameMetaPath(string $oldRelativePath, string $newRelativePath): void
    {
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

    public function getDiskUsage(): array
    {
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
            'formatted' => $this->formatSize($size),
        ];
    }

    public function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = (int)min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function resolvePath(string $path): string|WP_Error
    {
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
        if (strpos($path, '..') !== false) {
            return new WP_Error('security_violation', 'Invalid path');
        }

        $fullPath = $this->uploadPath;
        if ($path !== '') {
            $fullPath .= DIRECTORY_SEPARATOR . trim($path, DIRECTORY_SEPARATOR);
        }

        if (strpos($fullPath, $this->uploadPath) !== 0) {
            return new WP_Error('security_violation', 'Path traversal detected');
        }

        return $fullPath;
    }
}
