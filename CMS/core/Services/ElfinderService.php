<?php
/**
 * elFinder Service
 *
 * Stellt einen abgesicherten Connector für die Admin-Medienverwaltung bereit.
 *
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

if (!defined('ABSPATH')) {
    exit;
}

final class ElfinderService
{
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    public function handleConnectorRequest(): void
    {
        if (!class_exists('elFinder') || !class_exists('elFinderConnector')) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'elFinder ist nicht verfügbar.',
            ]);
            exit;
        }

        $this->ensureRuntimeDirectories();

        $opts = [
            'locale' => 'de',
            'debug' => false,
            'roots' => [[
                'driver' => 'LocalFileSystem',
                'path' => rtrim((string) UPLOAD_PATH, '\\/') . DIRECTORY_SEPARATOR,
                'URL' => rtrim((string) UPLOAD_URL, '/'),
                'alias' => 'Uploads',
                'uploadOverwrite' => false,
                'copyOverwrite' => true,
                'tmbPath' => rtrim((string) UPLOAD_PATH, '\\/') . DIRECTORY_SEPARATOR . '.elfinder' . DIRECTORY_SEPARATOR . '.tmb' . DIRECTORY_SEPARATOR,
                'tmbURL' => rtrim((string) UPLOAD_URL, '/') . '/.elfinder/.tmb/',
                'tmpPath' => rtrim((string) UPLOAD_PATH, '\\/') . DIRECTORY_SEPARATOR . '.elfinder' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR,
                'accessControl' => [$this, 'accessControl'],
                'attributes' => [
                    [
                        'pattern' => '/(^|\\/)\\./',
                        'hidden' => true,
                    ],
                    [
                        'pattern' => '/(^|\\/)\\.elfinder(\\/|$)/',
                        'read' => false,
                        'write' => false,
                        'locked' => true,
                        'hidden' => true,
                    ],
                ],
            ]],
        ];

        $connector = new \elFinderConnector(new \elFinder($opts));
        $connector->run();
        exit;
    }

    /**
     * @param string $attr
     * @param string $path
     * @param mixed $data
     * @param mixed $volume
     */
    public function accessControl(string $attr, string $path, $data, $volume): ?bool
    {
        $basename = basename($path);
        if ($basename !== '' && str_starts_with($basename, '.')) {
            return match ($attr) {
                'read', 'write' => false,
                'hidden', 'locked' => true,
                default => null,
            };
        }

        return null;
    }

    private function ensureRuntimeDirectories(): void
    {
        $baseDir = rtrim((string) UPLOAD_PATH, '\\/') . DIRECTORY_SEPARATOR . '.elfinder';
        $thumbDir = $baseDir . DIRECTORY_SEPARATOR . '.tmb';
        $tmpDir = $baseDir . DIRECTORY_SEPARATOR . 'tmp';

        foreach ([$baseDir, $thumbDir, $tmpDir] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }
    }
}