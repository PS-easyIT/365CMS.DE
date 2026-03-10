<?php
declare(strict_types=1);

namespace CMS\Services\Media;

use CMS\WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

final class ImageProcessor
{
    public function convertToWebP(string $filePath, int $quality = 85): bool|WP_Error
    {
        if (!file_exists($filePath)) {
            return new WP_Error('file_not_found', 'Datei nicht gefunden');
        }

        if (!extension_loaded('gd')) {
            return new WP_Error('gd_missing', 'GD-Erweiterung ist nicht verfügbar');
        }

        $imageInfo = @getimagesize($filePath);
        if ($imageInfo === false) {
            return new WP_Error('invalid_image', 'Ungültige Bilddatei');
        }

        $mimeType = $imageInfo['mime'] ?? '';
        $source = match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($filePath),
            'image/png' => @imagecreatefrompng($filePath),
            'image/gif' => @imagecreatefromgif($filePath),
            'image/webp' => @imagecreatefromwebp($filePath),
            default => false,
        };

        if ($source === false) {
            return new WP_Error('image_load_failed', 'Bild konnte nicht geladen werden');
        }

        $targetPath = preg_replace('/\.[^.]+$/', '.webp', $filePath);
        if ($targetPath === null || $targetPath === '') {
            imagedestroy($source);
            return new WP_Error('target_path_invalid', 'WebP-Zielpfad konnte nicht erzeugt werden');
        }

        imagepalettetotruecolor($source);
        imagealphablending($source, true);
        imagesavealpha($source, true);

        $success = @imagewebp($source, $targetPath, max(1, min($quality, 100)));
        imagedestroy($source);

        if (!$success) {
            return new WP_Error('webp_conversion_failed', 'WebP-Konvertierung fehlgeschlagen');
        }

        return true;
    }
}
