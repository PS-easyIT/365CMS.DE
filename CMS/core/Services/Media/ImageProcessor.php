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

        $imageInfo = $this->getImageSize($filePath);
        if ($imageInfo === false) {
            return new WP_Error('invalid_image', 'Ungültige Bilddatei');
        }

        $mimeType = $imageInfo['mime'] ?? '';
        $source = match ($mimeType) {
            'image/jpeg' => $this->createImageFromJpeg($filePath),
            'image/png' => $this->createImageFromPng($filePath),
            'image/gif' => $this->createImageFromGif($filePath),
            'image/webp' => $this->createImageFromWebp($filePath),
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

        $success = $this->writeWebp($source, $targetPath, max(1, min($quality, 100)));
        imagedestroy($source);

        if (!$success) {
            return new WP_Error('webp_conversion_failed', 'WebP-Konvertierung fehlgeschlagen');
        }

        return true;
    }

    private function getImageSize(string $filePath): array|false
    {
        return $this->runGdOperation(static fn() => getimagesize($filePath));
    }

    private function createImageFromJpeg(string $filePath)
    {
        return $this->runGdOperation(static fn() => imagecreatefromjpeg($filePath));
    }

    private function createImageFromPng(string $filePath)
    {
        return $this->runGdOperation(static fn() => imagecreatefrompng($filePath));
    }

    private function createImageFromGif(string $filePath)
    {
        return $this->runGdOperation(static fn() => imagecreatefromgif($filePath));
    }

    private function createImageFromWebp(string $filePath)
    {
        return $this->runGdOperation(static fn() => imagecreatefromwebp($filePath));
    }

    private function writeWebp(mixed $source, string $targetPath, int $quality): bool
    {
        return $this->runGdOperation(static fn() => imagewebp($source, $targetPath, $quality)) === true;
    }

    private function runGdOperation(callable $operation): mixed
    {
        set_error_handler(static function (): bool {
            return true;
        });

        try {
            return $operation();
        } finally {
            restore_error_handler();
        }
    }
}
