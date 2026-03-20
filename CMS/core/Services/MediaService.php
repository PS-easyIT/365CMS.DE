<?php
declare(strict_types=1);

namespace CMS\Services;

use CMS\Contracts\LoggerInterface;
use CMS\Json;
use CMS\Logger;
use CMS\Services\Media\ImageProcessor;
use CMS\Services\Media\MediaRepository;
use CMS\Services\Media\UploadHandler;
use CMS\WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

class MediaService {

    private const BLOCKED_UPLOAD_EXTENSIONS = ['svg'];

    private static array $instances = [];

    private string $uploadPath;
    private string $uploadUrl;
    private string $metaFile;
    private string $settingsFile;
    private LoggerInterface $logger;
    private MediaRepository $repository;
    private UploadHandler $uploadHandler;
    private ImageProcessor $imageProcessor;

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
    private function parseSize(string $size): int {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        if ($unit) {
            return (int) round((float) $size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }
        return (int) round((float) $size);
    }

    public function __construct(string $customRoot = '') {
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
        $this->logger = Logger::instance()->withChannel('media.service');

        $this->repository = new MediaRepository($this->uploadPath, $this->uploadUrl, $this->metaFile, $this->systemFolders);
        $this->imageProcessor = new ImageProcessor();
        $this->uploadHandler = new UploadHandler(
            $this->uploadPath,
            $this->repository,
            $this->imageProcessor,
            \Closure::fromCallable([$this, 'validateUploadFile']),
            $this->logger
        );

        $settings = $this->getSettings();
        $this->syncUploadsProtection((bool) ($settings['protect_uploads_dir'] ?? true));
    }

    /**
     * Get All Categories
     */
    public function getCategories(): array {
        return $this->repository->getCategories();
    }

    /**
     * Add Category
     */
    public function addCategory(string $name, string $slug = ''): bool|WP_Error {
        return $this->repository->addCategory($name, $slug);
    }

    /**
     * Delete Category
     */
    public function deleteCategory(string $slug): bool|WP_Error {
        return $this->repository->deleteCategory($slug);
    }

    /**
     * Assign Category to File
     */
    public function assignCategory(string $filePath, string $categorySlug): bool|WP_Error {
        return $this->repository->assignCategory($filePath, $categorySlug);
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
            'block_dangerous_types' => true,
            'validate_image_content' => true,
            'require_login_for_upload' => true,
            'protect_uploads_dir' => true,
        ];
    }

    private function getTypeMap(): array {
        return [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'ico'],
            'video' => ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'],
            'audio' => ['mp3', 'wav', 'aac', 'flac', 'm4a'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf', 'csv'],
            'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
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

    private function normalizeBooleanSetting(array $settings, string $key, bool $fallback): bool {
        if (!array_key_exists($key, $settings)) {
            return $fallback;
        }

        $value = $settings[$key];

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'no', 'off', ''], true)) {
                return false;
            }
        }

        return !empty($value);
    }

    private function getSettingValue(array $settings, array $keys, mixed $fallback): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $settings)) {
                return $settings[$key];
            }
        }

        return $fallback;
    }

    /**
     * @param array<int,string> $rawValues
     * @param array<int,string> $allowedGroups
     * @return array<int,string>
     */
    private function normalizeAllowedTypeSelection(array $rawValues, array $allowedGroups): array
    {
        $typeMap = $this->getTypeMap();
        $normalized = [];

        foreach ($rawValues as $value) {
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

    private function normalizeSettings(array $settings): array {
        $defaults = $this->getDefaultSettings();
        $allTypes = array_keys($this->getTypeMap());

        $allowedTypes = $this->normalizeAllowedTypeSelection(
            array_map('strval', (array) $this->getSettingValue($settings, ['allowed_types'], $defaults['allowed_types'])),
            $allTypes
        );
        if ($allowedTypes === []) {
            $allowedTypes = $defaults['allowed_types'];
        }

        $memberAllowedTypes = $this->normalizeAllowedTypeSelection(
            array_map('strval', (array) $this->getSettingValue($settings, ['member_allowed_types'], $defaults['member_allowed_types'])),
            ['image', 'document', 'video', 'audio']
        );
        if ($memberAllowedTypes === []) {
            $memberAllowedTypes = $defaults['member_allowed_types'];
        }

        return array_merge($defaults, [
            'max_upload_size' => $this->sanitizeSizeSetting((string) $this->getSettingValue($settings, ['max_upload_size'], $defaults['max_upload_size']), $defaults['max_upload_size']),
            'allowed_types' => $allowedTypes,
            'auto_webp' => $this->normalizeBooleanSetting($settings, 'auto_webp', (bool) $defaults['auto_webp']),
            'strip_exif' => $this->normalizeBooleanSetting($settings, 'strip_exif', (bool) $defaults['strip_exif']),
            'jpeg_quality' => max(60, min(100, (int) $this->getSettingValue($settings, ['jpeg_quality'], $defaults['jpeg_quality']))),
            'max_width' => max(1, min(8000, (int) $this->getSettingValue($settings, ['max_width'], $defaults['max_width']))),
            'max_height' => max(1, min(8000, (int) $this->getSettingValue($settings, ['max_height'], $defaults['max_height']))),
            'organize_month_year' => $this->normalizeBooleanSetting($settings, 'organize_month_year', (bool) $defaults['organize_month_year']),
            'sanitize_filenames' => $this->normalizeBooleanSetting($settings, array_key_exists('sanitize_filenames', $settings) ? 'sanitize_filenames' : 'sanitize_filename', (bool) $defaults['sanitize_filenames']),
            'unique_filenames' => $this->normalizeBooleanSetting($settings, array_key_exists('unique_filenames', $settings) ? 'unique_filenames' : 'unique_filename', (bool) $defaults['unique_filenames']),
            'lowercase_filenames' => $this->normalizeBooleanSetting($settings, array_key_exists('lowercase_filenames', $settings) ? 'lowercase_filenames' : 'lowercase_filename', (bool) $defaults['lowercase_filenames']),
            'member_uploads_enabled' => $this->normalizeBooleanSetting($settings, 'member_uploads_enabled', (bool) $defaults['member_uploads_enabled']),
            'member_max_upload_size' => $this->sanitizeSizeSetting((string) $this->getSettingValue($settings, ['member_max_upload_size'], $defaults['member_max_upload_size']), $defaults['member_max_upload_size']),
            'member_allowed_types' => $memberAllowedTypes,
            'member_delete_own' => $this->normalizeBooleanSetting($settings, 'member_delete_own', (bool) $defaults['member_delete_own']),
            'generate_thumbnails' => $this->normalizeBooleanSetting($settings, 'generate_thumbnails', (bool) $defaults['generate_thumbnails']),
            'thumb_small_w' => max(50, min(4000, (int) $this->getSettingValue($settings, ['thumb_small_w', 'thumbnail_small_w'], $defaults['thumb_small_w']))),
            'thumb_small_h' => max(50, min(4000, (int) $this->getSettingValue($settings, ['thumb_small_h', 'thumbnail_small_h'], $defaults['thumb_small_h']))),
            'thumb_medium_w' => max(50, min(4000, (int) $this->getSettingValue($settings, ['thumb_medium_w', 'thumbnail_medium_w'], $defaults['thumb_medium_w']))),
            'thumb_medium_h' => max(50, min(4000, (int) $this->getSettingValue($settings, ['thumb_medium_h', 'thumbnail_medium_h'], $defaults['thumb_medium_h']))),
            'thumb_large_w' => max(50, min(6000, (int) $this->getSettingValue($settings, ['thumb_large_w', 'thumbnail_large_w'], $defaults['thumb_large_w']))),
            'thumb_large_h' => max(50, min(6000, (int) $this->getSettingValue($settings, ['thumb_large_h', 'thumbnail_large_h'], $defaults['thumb_large_h']))),
            'thumb_banner_w' => max(50, min(6000, (int) $this->getSettingValue($settings, ['thumb_banner_w', 'thumbnail_banner_w'], $defaults['thumb_banner_w']))),
            'thumb_banner_h' => max(50, min(6000, (int) $this->getSettingValue($settings, ['thumb_banner_h', 'thumbnail_banner_h'], $defaults['thumb_banner_h']))),
            'block_dangerous_types' => $this->normalizeBooleanSetting($settings, 'block_dangerous_types', (bool) $defaults['block_dangerous_types']),
            'validate_image_content' => $this->normalizeBooleanSetting($settings, 'validate_image_content', (bool) $defaults['validate_image_content']),
            'require_login_for_upload' => $this->normalizeBooleanSetting($settings, 'require_login_for_upload', (bool) $defaults['require_login_for_upload']),
            'protect_uploads_dir' => $this->normalizeBooleanSetting($settings, 'protect_uploads_dir', (bool) $defaults['protect_uploads_dir']),
        ]);
    }

    /**
     * @param array<string,mixed> $settings
     * @return array<string,mixed>
     */
    public function buildUploadValidationSettings(array $settings, bool $memberContext = false): array
    {
        $normalized = $this->normalizeSettings($settings);

        if (!$memberContext) {
            return $normalized;
        }

        $normalized['max_upload_size'] = (string) ($normalized['member_max_upload_size'] ?? $normalized['max_upload_size']);
        $normalized['allowed_types'] = array_values(array_map('strval', (array) ($normalized['member_allowed_types'] ?? $normalized['allowed_types'])));

        return $normalized;
    }

    private function getDangerousExtensions(): array {
        return [
            'php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'exe', 'com', 'bat', 'cmd', 'ps1', 'sh', 'pl', 'cgi', 'jar', 'msi', 'vbs', 'scr', 'dll', 'asp', 'aspx', 'jspx'
        ];
    }

    private function getAllowedMimeMap(): array {
        return [
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
            'gif'  => ['image/gif'],
            'webp' => ['image/webp'],
            'bmp'  => ['image/bmp', 'image/x-bmp'],
            'ico'  => ['image/x-icon', 'image/vnd.microsoft.icon'],
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
    }

    /**
     * @return array{ext: string}|WP_Error
     */
    private function validateFile(array $file, array $settings): array|WP_Error {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', 'File upload error code: ' . (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE));
        }

        $fileName = trim((string) ($file['name'] ?? ''));
        $tmpName = (string) ($file['tmp_name'] ?? '');
        $fileSize = (int) ($file['size'] ?? 0);

        if ($fileName === '' || $tmpName === '' || !is_file($tmpName)) {
            return new WP_Error('invalid_upload', 'Ungültige Upload-Datei.');
        }

        $maxSize = $this->parseSize((string) ($settings['max_upload_size'] ?? '64M'));
        if ($fileSize > $maxSize) {
            return new WP_Error('size_limit', 'Datei ist zu groß. Maximum: ' . ($settings['max_upload_size'] ?? '64M'));
        }

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($ext === '') {
            return new WP_Error('missing_extension', 'Die Datei hat keine erlaubte Dateiendung.');
        }

        if (in_array($ext, self::BLOCKED_UPLOAD_EXTENSIONS, true)) {
            return new WP_Error('type_blocked', 'SVG-Uploads sind aus Sicherheitsgründen deaktiviert.');
        }

        $allowedGroups = array_map('strval', (array) ($settings['allowed_types'] ?? ['image', 'document']));
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

        $allowedMimeMap = $this->getAllowedMimeMap();
        if (function_exists('finfo_open') && isset($allowedMimeMap[$ext])) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $realMime = $finfo ? finfo_file($finfo, $tmpName) : false;
            if ($finfo) {
                finfo_close($finfo);
            }

            if ($realMime !== false && !in_array($realMime, $allowedMimeMap[$ext], true)) {
                return new WP_Error('mime_mismatch', sprintf('MIME-Typ "%s" passt nicht zur Dateiendung ".%s". Upload abgebrochen.', $realMime, $ext));
            }
        }

        if (($settings['validate_image_content'] ?? false) && $this->isImageExtension($ext) && $this->readImageSize($tmpName, 'upload_validation') === null) {
            return new WP_Error('invalid_image', 'Die Datei enthält keine gültigen Bilddaten.');
        }

        return ['ext' => $ext];
    }

    /**
     * Öffentliche, zentrale Upload-Validierung für alle Datei-Upload-Endpunkte.
     *
     * @param array<string,mixed> $file
     * @param array<string,mixed>|null $settings
     * @return array{ext:string}|WP_Error
     */
    public function validateUploadFile(array $file, ?array $settings = null): array|WP_Error
    {
        $resolvedSettings = $settings !== null
            ? $this->normalizeSettings($settings)
            : $this->getSettings();

        return $this->validateFile($file, $resolvedSettings);
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

        $content = "Options -Indexes\n"
            . "<IfModule mod_headers.c>\n"
            . "    Header always set X-Content-Type-Options \"nosniff\"\n"
            . "    Header always set Content-Disposition \"attachment\"\n"
            . "</IfModule>\n"
            . "<FilesMatch \"\\.(avif|bmp|gif|ico|jpe?g|png|webp)$\">\n"
            . "    <IfModule mod_headers.c>\n"
            . "        Header always unset Content-Disposition\n"
            . "        Header always set Content-Disposition \"inline\"\n"
            . "    </IfModule>\n"
            . "</FilesMatch>\n"
            . "<FilesMatch \"\\.(php|php3|php4|php5|phtml|phar|cgi|pl|py|sh)$\">\n"
            . "    Require all denied\n"
            . "</FilesMatch>\n"
            . "Options -ExecCGI\n";
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
        $image = $this->loadGdImage($path, $ext);

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

    private function readImageSize(string $path, string $context = 'image'): ?array
    {
        $result = $this->runImageOperation('getimagesize', $path, $context, static fn (): array|false => getimagesize($path));

        return is_array($result) ? $result : null;
    }

    private function loadGdImage(string $path, string $ext): \GdImage|false
    {
        $loader = match ($ext) {
            'jpg', 'jpeg' => 'imagecreatefromjpeg',
            'png' => 'imagecreatefrompng',
            'gif' => 'imagecreatefromgif',
            'webp' => 'imagecreatefromwebp',
            'bmp' => 'imagecreatefrombmp',
            default => null,
        };

        if ($loader === null || !function_exists($loader) || !is_file($path) || !is_readable($path)) {
            return false;
        }

        $result = $this->runImageOperation($loader, $path, 'reencode', static fn () => $loader($path));

        return $result instanceof \GdImage ? $result : false;
    }

    private function runImageOperation(string $operation, string $path, string $context, callable $callback): mixed
    {
        $warning = null;

        set_error_handler(static function (int $severity, string $message) use (&$warning): bool {
            $warning = $message;
            return true;
        });

        try {
            $result = $callback();
        } finally {
            restore_error_handler();
        }

        if (($result === false || $result === null) && $warning !== null) {
            $this->logger->warning('Medien-Bildoperation fehlgeschlagen.', [
                'operation' => $operation,
                'path' => $path,
                'context' => $context,
                'warning' => $warning,
            ]);
        }

        return $result;
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

        $settings = Json::decodeArray($content, []);
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

    /**
     * List items in a directory
     * 
     * @param string $path Relative path from UPLOAD_PATH
     * @return array|WP_Error Array of items or WP_Error on failure
     */
    public function getItems(string $path = ''): array|WP_Error {
        return $this->repository->getItems($path);
    }

    /**
     * Create a new folder
     */
    public function createFolder(string $name, string $parentPath = ''): bool|WP_Error {
        return $this->uploadHandler->createFolder($parentPath, $name);
    }

    /**
     * Upload a file
     */
    public function uploadFile(array $file, string $targetPath = '', ?array $validationSettings = null): string|WP_Error {
        $settings = $this->getSettings();
        $result = $this->uploadHandler->uploadFile(
            $file,
            $targetPath,
            (bool) ($settings['auto_webp'] ?? false),
            $validationSettings
        );
        if ($result instanceof WP_Error) {
            return $result;
        }

        return (string) ($result['name'] ?? '');
    }

    /**
     * Ensure a custom category exists (creates it if missing).
     * Returns the slug on success.
     */
    public function ensureCategory(string $name, string $slug): string
    {
        return $this->repository->ensureCategory($name, $slug);
    }

    /**
     * Move a file to a new relative path inside the upload directory.
     * Updates meta (keeps category, uploader etc.) and updates thumbnail variants.
     * Returns the new relative path (forward slashes) or WP_Error.
     */
    public function moveFile(string $relativeOldPath, string $relativeNewPath): string|WP_Error
    {
        $result = $this->uploadHandler->moveFile($relativeOldPath, $relativeNewPath);
        return $result instanceof WP_Error ? $result : trim(str_replace('\\', '/', $relativeNewPath), '/');
    }

    /**
     * Delete an item (file or folder)
     */
    public function deleteItem(string $path): bool|WP_Error {
        return $this->uploadHandler->deleteItem($path);
    }

    /**
     * Rename an item
     */
    public function renameItem(string $oldPath, string $newName): bool|WP_Error {
        return $this->uploadHandler->renameItem($oldPath, $newName);
    }

    /**
     * Get disk usage
     */
    public function getDiskUsage(): array {
        return $this->repository->getDiskUsage();
    }

    public function formatSize(int $bytes): string {
        return $this->repository->formatSize($bytes);
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
        $result = $this->imageProcessor->convertToWebP($sourcePath, $quality);
        if ($result instanceof WP_Error) {
            return null;
        }

        $webpPath = preg_replace('/\.' . preg_quote($ext, '/') . '$/i', '.webp', $sourcePath);
        return is_string($webpPath) ? $webpPath : null;
    }
}
