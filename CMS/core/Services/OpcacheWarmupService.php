<?php
declare(strict_types=1);

namespace CMS\Services;

use CMS\Logger;

if (!defined('ABSPATH')) {
    exit;
}

final class OpcacheWarmupService
{
    private const DEFAULT_LIMIT = 30;
    private const STATE_FILE = ABSPATH . 'cache/opcache-warmup.json';
    private const ALLOWED_TOP_LEVEL_DIRECTORIES = [
        'admin',
        'assets',
        'config',
        'core',
        'db',
        'includes',
        'install',
        'lang',
        'member',
        'plugins',
        'themes',
        'views',
    ];
    private const EXCLUDED_SEGMENTS = [
        DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR,
        DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR,
        DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR,
        DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR,
        DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR,
        DIRECTORY_SEPARATOR . 'BACKUP' . DIRECTORY_SEPARATOR,
    ];

    private static ?self $instance = null;

    /** @var array<string, mixed>|null */
    private ?array $cachedStatus = null;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    /**
     * Führt einmalig pro Dateisignatur einen OPcache-Warmup der größten PHP-Dateien aus.
     * Gedacht als Nach-Deploy-Schutznetz für den ersten Laufzeitkontakt.
     *
     * @return array<string, mixed>
     */
    public function maybeWarmAfterDeploy(int $limit = self::DEFAULT_LIMIT): array
    {
        $status = $this->getStatus($limit);
        if (!($status['available'] ?? false)) {
            return [
                'success' => false,
                'skipped' => true,
                'message' => (string)($status['message'] ?? 'OPcache-Warmup ist nicht verfügbar.'),
                'compiled' => 0,
                'failed' => [],
            ];
        }

        if (($status['is_current'] ?? false) === true) {
            return [
                'success' => true,
                'skipped' => true,
                'message' => 'OPcache-Warmup bereits aktuell.',
                'compiled' => 0,
                'failed' => [],
            ];
        }

        return $this->warmTopFiles($limit, false);
    }

    /**
     * @return array<string, mixed>
     */
    public function warmTopFiles(int $limit = self::DEFAULT_LIMIT, bool $force = true): array
    {
        $limit = max(1, $limit);
        if (!$this->isOpcacheAvailable()) {
            return [
                'success' => false,
                'compiled' => 0,
                'failed' => [],
                'message' => 'OPcache oder opcache_compile_file() ist auf diesem Laufzeitpfad nicht verfügbar.',
            ];
        }

        $files = $this->discoverLargestPhpFiles($limit);
        if ($files === []) {
            return [
                'success' => false,
                'compiled' => 0,
                'failed' => [],
                'message' => 'Keine geeigneten PHP-Dateien für den Warmup gefunden.',
            ];
        }

        $signature = $this->buildSignature($files);
        $state = $this->readState();
        if (!$force && is_array($state) && ($state['signature'] ?? null) === $signature) {
            return [
                'success' => true,
                'compiled' => 0,
                'failed' => [],
                'message' => 'OPcache-Warmup bereits mit aktueller Dateisignatur vorhanden.',
            ];
        }

        $compiled = 0;
        $failed = [];

        foreach ($files as $file) {
            $realPath = (string)($file['path'] ?? '');
            if ($realPath === '' || !is_file($realPath)) {
                continue;
            }

            set_error_handler(static function (): bool {
                return true;
            });

            try {
                $result = opcache_compile_file($realPath);
            } catch (\Throwable $e) {
                $result = false;
                $failed[] = [
                    'file' => $this->toRelativePath($realPath),
                    'reason' => $e->getMessage(),
                ];
            } finally {
                restore_error_handler();
            }

            if ($result === true) {
                $compiled++;
                continue;
            }

            if (!isset($failed[array_key_last($failed)]['file']) || $failed[array_key_last($failed)]['file'] !== $this->toRelativePath($realPath)) {
                $failed[] = [
                    'file' => $this->toRelativePath($realPath),
                    'reason' => 'opcache_compile_file() lieferte false zurück.',
                ];
            }
        }

        $statePayload = [
            'signature' => $signature,
            'limit' => $limit,
            'generated_at' => date('c'),
            'compiled' => $compiled,
            'failed' => $failed,
            'files' => array_map(function (array $file): array {
                return [
                    'path' => $this->toRelativePath((string)$file['path']),
                    'size' => (int)($file['size'] ?? 0),
                    'mtime' => (int)($file['mtime'] ?? 0),
                ];
            }, $files),
        ];
        $this->writeState($statePayload);
        $this->cachedStatus = null;

        $message = sprintf(
            'OPcache-Warmup abgeschlossen: %d von %d Datei(en) kompiliert.',
            $compiled,
            count($files)
        );

        if ($failed !== []) {
            $message .= ' Fehlgeschlagen: ' . count($failed) . '.';
        }

        Logger::instance()->info($message, [
            'component' => 'opcache_warmup',
            'compiled' => $compiled,
            'failed' => $failed,
            'limit' => $limit,
        ]);

        return [
            'success' => $compiled > 0,
            'compiled' => $compiled,
            'failed' => $failed,
            'message' => $message,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(int $limit = self::DEFAULT_LIMIT): array
    {
        if ($this->cachedStatus !== null && (int)($this->cachedStatus['limit'] ?? 0) === $limit) {
            return $this->cachedStatus;
        }

        $files = $this->discoverLargestPhpFiles(max(1, $limit));
        $signature = $this->buildSignature($files);
        $state = $this->readState();
        $available = $this->isOpcacheAvailable();
        $isCurrent = is_array($state) && ($state['signature'] ?? null) === $signature;

        $status = [
            'available' => $available,
            'limit' => $limit,
            'message' => $available
                ? 'OPcache-Warmup verfügbar.'
                : 'OPcache oder opcache_compile_file() ist auf diesem Laufzeitpfad nicht verfügbar.',
            'candidate_count' => count($files),
            'is_current' => $isCurrent,
            'last_generated_at' => is_array($state) ? (string)($state['generated_at'] ?? '') : '',
            'last_compiled' => is_array($state) ? (int)($state['compiled'] ?? 0) : 0,
            'last_failed_count' => is_array($state) && isset($state['failed']) && is_array($state['failed']) ? count($state['failed']) : 0,
            'top_files' => array_map(function (array $file): array {
                return [
                    'path' => $this->toRelativePath((string)$file['path']),
                    'size' => (int)($file['size'] ?? 0),
                ];
            }, array_slice($files, 0, 5)),
        ];

        $this->cachedStatus = $status;

        return $status;
    }

    private function isOpcacheAvailable(): bool
    {
        if (!function_exists('opcache_compile_file') || !function_exists('opcache_get_status')) {
            return false;
        }

        if (PHP_SAPI === 'cli' && !filter_var((string)ini_get('opcache.enable_cli'), FILTER_VALIDATE_BOOL)) {
            return false;
        }

        $status = opcache_get_status(false);
        return is_array($status) && !empty($status['opcache_enabled']);
    }

    /**
     * @return array<int, array{path:string,size:int,mtime:int}>
     */
    private function discoverLargestPhpFiles(int $limit): array
    {
        $root = rtrim(ABSPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!is_dir($root)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
                function (\SplFileInfo $current): bool {
                    $normalizedPath = $this->normalizePath($current->getPathname());
                    $relativePath = $this->toRelativePath($normalizedPath);

                    if (!$this->isManagedWarmupCandidate($relativePath, $current->isDir())) {
                        return false;
                    }

                    if ($current->isDir()) {
                        foreach (self::EXCLUDED_SEGMENTS as $segment) {
                            if (str_contains($normalizedPath . DIRECTORY_SEPARATOR, $this->normalizePath($segment))) {
                                return false;
                            }
                        }

                        return true;
                    }

                    return str_ends_with(strtolower($normalizedPath), '.php');
                }
            )
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $path = $file->getPathname();
            $normalizedPath = $this->normalizePath($path);
            $relativePath = $this->toRelativePath($normalizedPath);

            if (!$this->isManagedWarmupCandidate($relativePath, false)) {
                continue;
            }

            $skip = false;
            foreach (self::EXCLUDED_SEGMENTS as $segment) {
                if (str_contains($normalizedPath, $this->normalizePath($segment))) {
                    $skip = true;
                    break;
                }
            }

            if ($skip) {
                continue;
            }

            $files[] = [
                'path' => $path,
                'size' => (int)$file->getSize(),
                'mtime' => (int)$file->getMTime(),
            ];
        }

        usort($files, static function (array $a, array $b): int {
            $sizeCompare = (int)$b['size'] <=> (int)$a['size'];
            if ($sizeCompare !== 0) {
                return $sizeCompare;
            }

            return strcmp((string)$a['path'], (string)$b['path']);
        });

        return array_slice($files, 0, $limit);
    }

    /**
     * @param array<int, array{path:string,size:int,mtime:int}> $files
     */
    private function buildSignature(array $files): string
    {
        return hash('sha256', json_encode($files, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readState(): ?array
    {
        if (!is_file(self::STATE_FILE) || !is_readable(self::STATE_FILE)) {
            return null;
        }

        $content = file_get_contents(self::STATE_FILE);
        if (!is_string($content) || $content === '') {
            return null;
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<string, mixed> $state
     */
    private function writeState(array $state): void
    {
        $dir = dirname(self::STATE_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            self::STATE_FILE,
            json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    private function normalizePath(string $path): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    private function isManagedWarmupCandidate(string $relativePath, bool $isDirectory): bool
    {
        $relativePath = ltrim(str_replace(['/', '\\'], '/', $relativePath), '/');
        if ($relativePath === '') {
            return true;
        }

        $segments = explode('/', $relativePath);
        $topLevel = strtolower((string) ($segments[0] ?? ''));
        if ($topLevel === '') {
            return true;
        }

        if (count($segments) === 1 && !$isDirectory) {
            return str_ends_with($topLevel, '.php');
        }

        return in_array($topLevel, self::ALLOWED_TOP_LEVEL_DIRECTORIES, true);
    }

    private function toRelativePath(string $path): string
    {
        $normalizedBase = rtrim($this->normalizePath(ABSPATH), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $normalizedPath = $this->normalizePath($path);

        if (str_starts_with($normalizedPath, $normalizedBase)) {
            return str_replace(DIRECTORY_SEPARATOR, '/', substr($normalizedPath, strlen($normalizedBase)));
        }

        return str_replace(DIRECTORY_SEPARATOR, '/', $normalizedPath);
    }
}
