<?php
declare(strict_types=1);

/**
 * Theme Editor Module – Dateien des aktiven Themes bearbeiten
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\AuditLogger;
use CMS\Logger;
use CMS\Services\ErrorReportService;
use CMS\ThemeManager;

class ThemeEditorModule
{
    private const ALLOWED_PATH_PATTERN = '/^[A-Za-z0-9._\/-]+$/';
    private const MAX_TREE_DEPTH = 8;
    private const MAX_TREE_ITEMS = 600;
    private const MAX_TREE_ITEMS_PER_DIRECTORY = 200;
    private const SKIPPED_TREE_SEGMENTS = ['vendor', 'node_modules', 'cache', '.git', 'dist', 'build'];
    private ThemeManager $themeManager;
    private string $themePath;
    private string $themeSlug;

    private const ALLOWED_EXTENSIONS = ['php', 'css', 'js', 'json', 'html', 'txt', 'md'];
    private const MAX_EDITABLE_BYTES = 1048576;
    private const MAX_ERROR_CONTEXT_LENGTH = 180;

    public function __construct()
    {
        $this->themeManager = ThemeManager::instance();
        $this->themeSlug    = $this->themeManager->getActiveThemeSlug();
        $this->themePath    = $this->themeManager->getThemePath();
    }

    /**
     * Dateiliste + aktuellen Dateiinhalt laden
     */
    public function getData(string $currentFile = ''): array
    {
        $treeSummary = [
            'items' => 0,
            'skipped_items' => 0,
            'warnings' => [],
        ];
        $files = $this->getFileTree($this->themePath, '', 0, $treeSummary);

        $fileContent  = '';
        $fileLanguage = 'plaintext';
        $fileWarning  = null;
        $fileMeta     = null;

        $requestedFile = $this->normalizeRelativePath($currentFile);
        $currentFile = $requestedFile;

        if ($currentFile !== '') {
            $safePath = $this->resolveSafePath($currentFile);
            if ($safePath && is_file($safePath)) {
                $fileMeta = $this->buildFileMeta($safePath, $currentFile);
                $fileSize = filesize($safePath);
                if ($fileSize === false || $fileSize > self::MAX_EDITABLE_BYTES) {
                    $fileWarning = 'Die ausgewählte Datei ist zu groß für die sichere Browser-Bearbeitung.';
                } else {
                    $contents = file_get_contents($safePath);
                    $fileContent = is_string($contents) ? $contents : '';

                    if (str_contains($fileContent, "\0")) {
                        $fileContent = '';
                        $fileWarning = 'Die ausgewählte Datei enthält Binärdaten und kann hier nicht sicher bearbeitet werden.';
                        $fileMeta = $this->markFileMetaAsReadOnly($fileMeta, $fileWarning);
                    }
                }

                $fileLanguage = $this->getLanguage($currentFile);
            } elseif ($requestedFile !== '') {
                $currentFile = '';
                $fileWarning = 'Die angeforderte Datei konnte nicht sicher geladen werden.';
            }
        }

        return [
            'themeSlug'    => $this->themeSlug,
            'files'        => $files,
            'treeSummary'  => $treeSummary,
            'constraints'  => [
                'tree_max_depth' => self::MAX_TREE_DEPTH,
                'tree_max_items' => self::MAX_TREE_ITEMS,
                'tree_directory_limit' => self::MAX_TREE_ITEMS_PER_DIRECTORY,
                'max_editable_bytes' => self::MAX_EDITABLE_BYTES,
                'max_editable_label' => $this->formatBytes(self::MAX_EDITABLE_BYTES),
                'allowed_extensions' => self::ALLOWED_EXTENSIONS,
                'allowed_extensions_label' => implode(', ', self::ALLOWED_EXTENSIONS),
                'skipped_tree_segments' => self::SKIPPED_TREE_SEGMENTS,
                'skipped_tree_segments_label' => implode(', ', self::SKIPPED_TREE_SEGMENTS),
            ],
            'currentFile'  => $currentFile,
            'fileContent'  => $fileContent,
            'fileLanguage' => $fileLanguage,
            'fileWarning'  => $fileWarning,
            'fileMeta'     => $fileMeta,
        ];
    }

    /**
     * Datei speichern
     */
    public function saveFile(string $relativePath, string $content): array
    {
        $relativePath = $this->normalizeRelativePath($relativePath);
        if ($relativePath === '') {
            return $this->buildValidationFailureResult(
                'Kein Dateipfad angegeben.',
                'themes.editor.missing_file',
                ['Datei: (leer)']
            );
        }

        $safePath = $this->resolveSafePath($relativePath);
        if (!$safePath) {
            return $this->buildValidationFailureResult(
                'Ungültiger Dateipfad.',
                'themes.editor.invalid_file',
                ['Datei: ' . $relativePath],
                ['file' => $relativePath]
            );
        }

        if (!is_file($safePath)) {
            return $this->buildValidationFailureResult(
                'Datei nicht gefunden.',
                'themes.editor.file_missing',
                ['Datei: ' . $relativePath],
                ['file' => $relativePath]
            );
        }

        $ext = strtolower(pathinfo($safePath, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return $this->buildValidationFailureResult(
                'Dateityp nicht erlaubt.',
                'themes.editor.filetype_not_allowed',
                ['Datei: ' . $relativePath, 'Erlaubte Endungen: ' . implode(', ', self::ALLOWED_EXTENSIONS)],
                ['file' => $relativePath, 'extension' => $ext]
            );
        }

        $fileSize = filesize($safePath);
        if ($fileSize === false || $fileSize > self::MAX_EDITABLE_BYTES) {
            return $this->buildValidationFailureResult(
                'Datei ist zu groß für die sichere Bearbeitung im Browser.',
                'themes.editor.file_too_large',
                ['Datei: ' . $relativePath, 'Browser-Limit: ' . $this->formatBytes(self::MAX_EDITABLE_BYTES)],
                ['file' => $relativePath, 'size_bytes' => $fileSize === false ? null : (int) $fileSize]
            );
        }

        $contentBytes = strlen($content);
        if ($contentBytes > self::MAX_EDITABLE_BYTES) {
            return $this->buildValidationFailureResult(
                'Der neue Dateiinhalt ist zu groß für die sichere Browser-Bearbeitung.',
                'themes.editor.content_too_large',
                ['Datei: ' . $relativePath, 'Neuer Inhalt: ' . $this->formatBytes($contentBytes), 'Browser-Limit: ' . $this->formatBytes(self::MAX_EDITABLE_BYTES)],
                ['file' => $relativePath, 'content_bytes' => $contentBytes]
            );
        }

        if (str_contains($content, "\0")) {
            return $this->buildValidationFailureResult(
                'Dateiinhalt enthält ungültige Binärdaten.',
                'themes.editor.binary_content',
                ['Datei: ' . $relativePath],
                ['file' => $relativePath]
            );
        }

        if (!is_writable($safePath)) {
            return $this->buildValidationFailureResult(
                'Datei ist nicht beschreibbar.',
                'themes.editor.file_not_writable',
                ['Datei: ' . $relativePath],
                ['file' => $relativePath]
            );
        }

        // PHP-Syntax prüfen vor dem Speichern
        if ($ext === 'php') {
            try {
                token_get_all($content, TOKEN_PARSE);
            } catch (\ParseError $e) {
                return $this->failResult(
                    'themes.editor.syntax.failed',
                    'Die PHP-Syntaxprüfung ist fehlgeschlagen.',
                    $e,
                    ['file' => $relativePath]
                );
            }
        }

        try {
            if (file_put_contents($safePath, $content, LOCK_EX) === false) {
                return $this->failResult(
                    'themes.editor.write.failed',
                    'Datei konnte nicht gespeichert werden.',
                    null,
                    ['file' => $relativePath]
                );
            }
        } catch (\Throwable $e) {
            return $this->failResult(
                'themes.editor.write.failed',
                'Datei konnte nicht gespeichert werden.',
                $e,
                ['file' => $relativePath]
            );
        }

        return [
            'success' => true,
            'message' => 'Datei gespeichert.',
            'details' => [
                'Datei: ' . $relativePath,
                'Typ: .' . $ext,
                'Größe: ' . $this->formatBytes($contentBytes),
            ],
        ];
    }

    /**
     * Pfad-Traversal-Schutz
     */
    private function resolveSafePath(string $relativePath): ?string
    {
        $relativePath = $this->normalizeRelativePath($relativePath);
        if ($relativePath === '' || !$this->isAllowedRelativePath($relativePath) || $this->containsHiddenSegment($relativePath) || $this->containsSkippedSegment($relativePath)) {
            return null;
        }

        if ($relativePath === '.' || str_starts_with($relativePath, '../') || str_contains($relativePath, '/../')) {
            return null;
        }

        $realBase = realpath($this->themePath);
        if ($realBase === false) {
            return null;
        }

        $realBase = rtrim($realBase, DIRECTORY_SEPARATOR);
        $basePrefix = $realBase . DIRECTORY_SEPARATOR;

        $fullPath = $basePrefix . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        if ($this->pathContainsSymlink($fullPath, $realBase)) {
            return null;
        }

        $realFile = realpath($fullPath);
        if ($realFile === false) {
            return null;
        }

        if (!str_starts_with($realFile, $basePrefix)) {
            return null;
        }

        $resolvedRelativePath = $this->buildRelativePathFromBase($realFile, $realBase);
        if ($resolvedRelativePath === null || !$this->isAllowedRelativePath($resolvedRelativePath) || $this->containsHiddenSegment($resolvedRelativePath) || $this->containsSkippedSegment($resolvedRelativePath)) {
            return null;
        }

        return $realFile;
    }

    private function pathContainsSymlink(string $fullPath, string $realBase): bool
    {
        $realBase = rtrim($realBase, DIRECTORY_SEPARATOR);
        $relativePath = ltrim(substr($fullPath, strlen($realBase)), DIRECTORY_SEPARATOR);
        if ($relativePath === '') {
            return false;
        }

        $segments = array_values(array_filter(explode(DIRECTORY_SEPARATOR, $relativePath), static fn (string $segment): bool => $segment !== ''));
        $currentPath = $realBase;

        foreach ($segments as $segment) {
            $currentPath .= DIRECTORY_SEPARATOR . $segment;
            if (is_link($currentPath)) {
                return true;
            }

            if (!file_exists($currentPath)) {
                break;
            }
        }

        return false;
    }

    private function buildRelativePathFromBase(string $realPath, string $realBase): ?string
    {
        $realBase = rtrim($realBase, DIRECTORY_SEPARATOR);
        $basePrefix = $realBase . DIRECTORY_SEPARATOR;
        if (!str_starts_with($realPath, $basePrefix)) {
            return null;
        }

        return $this->normalizeRelativePath(str_replace(DIRECTORY_SEPARATOR, '/', substr($realPath, strlen($basePrefix))));
    }

    /**
     * Dateibaum des Themes ermitteln
     */
    private function getFileTree(string $dir, string $prefix = '', int $depth = 0, array &$summary = []): array
    {
        if ($depth >= self::MAX_TREE_DEPTH) {
            $this->pushTreeWarning($summary, 'Der Dateibaum wurde an der maximalen Ordner-Tiefe begrenzt.');
            return [];
        }

        if (($summary['items'] ?? 0) >= self::MAX_TREE_ITEMS) {
            $this->pushTreeWarning($summary, 'Der Dateibaum wurde nach Erreichen des sicheren Gesamtlimits gekürzt.');
            return [];
        }

        $files = [];
        $items = scandir($dir) ?: [];
        if (count($items) > self::MAX_TREE_ITEMS_PER_DIRECTORY + 2) {
            $this->pushTreeWarning($summary, 'Ein oder mehrere Theme-Ordner wurden nach einem sicheren Verzeichnislimit gekürzt.');
            $items = array_slice($items, 0, self::MAX_TREE_ITEMS_PER_DIRECTORY + 2);
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if ($item !== '' && $item[0] === '.') {
                continue;
            }

            $path     = $dir . $item;
            $relative = $prefix . $item;

            if (is_link($path)) {
                $summary['skipped_items'] = (int) ($summary['skipped_items'] ?? 0) + 1;
                $this->pushTreeWarning($summary, 'Symbolische Links werden im Theme-Explorer aus Sicherheitsgründen übersprungen.');
                continue;
            }

            if ($this->shouldSkipTreeSegment($item)) {
                $summary['skipped_items'] = (int) ($summary['skipped_items'] ?? 0) + 1;
                continue;
            }

            if (($summary['items'] ?? 0) >= self::MAX_TREE_ITEMS) {
                $this->pushTreeWarning($summary, 'Der Dateibaum wurde nach Erreichen des sicheren Gesamtlimits gekürzt.');
                break;
            }

            if (is_dir($path)) {
                $summary['items'] = (int) ($summary['items'] ?? 0) + 1;
                $files[] = [
                    'name'     => $item,
                    'path'     => $relative,
                    'type'     => 'dir',
                    'children' => $this->getFileTree($path . '/', $relative . '/', $depth + 1, $summary),
                ];
            } else {
                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                $fileSize = filesize($path);
                if (in_array($ext, self::ALLOWED_EXTENSIONS, true) && $fileSize !== false && $fileSize <= self::MAX_EDITABLE_BYTES) {
                    $summary['items'] = (int) ($summary['items'] ?? 0) + 1;
                    $files[] = [
                        'name' => $item,
                        'path' => $relative,
                        'type' => 'file',
                        'ext'  => $ext,
                    ];
                } else {
                    $summary['skipped_items'] = (int) ($summary['skipped_items'] ?? 0) + 1;
                }
            }
        }

        usort($files, static function (array $left, array $right): int {
            if (($left['type'] ?? '') !== ($right['type'] ?? '')) {
                return ($left['type'] ?? '') === 'dir' ? -1 : 1;
            }

            return strcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
        });

        return $files;
    }

    /**
     * Sprache anhand der Dateiendung
     */
    private function getLanguage(string $file): string
    {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return match ($ext) {
            'php'  => 'php',
            'css'  => 'css',
            'js'   => 'javascript',
            'json' => 'json',
            'html' => 'html',
            'md'   => 'markdown',
            default => 'plaintext',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFileMeta(string $safePath, string $relativePath): array
    {
        $fileSize = filesize($safePath);
        $extension = strtolower(pathinfo($safePath, PATHINFO_EXTENSION));
        $isWritable = is_writable($safePath);
        $isEditableSize = $fileSize !== false && $fileSize <= self::MAX_EDITABLE_BYTES;
        $isEditable = $isWritable && $isEditableSize && in_array($extension, self::ALLOWED_EXTENSIONS, true);

        $reason = '';
        if (!$isWritable) {
            $reason = 'Datei ist schreibgeschützt oder für PHP nicht beschreibbar.';
        } elseif (!$isEditableSize) {
            $reason = 'Datei überschreitet das sichere Browser-Limit von 1 MB.';
        } elseif (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $reason = 'Dateityp ist für den Browser-Editor nicht freigeschaltet.';
        }

        return [
            'path' => $relativePath,
            'extension' => $extension !== '' ? $extension : 'txt',
            'size_bytes' => $fileSize !== false ? (int) $fileSize : 0,
            'size_label' => $this->formatBytes($fileSize !== false ? (int) $fileSize : 0),
            'is_writable' => $isWritable,
            'is_editable' => $isEditable,
            'save_disabled_reason' => $reason,
        ];
    }

    /**
     * @param array<string, mixed>|null $fileMeta
     * @return array<string, mixed>
     */
    private function markFileMetaAsReadOnly(?array $fileMeta, string $reason): array
    {
        $fileMeta = is_array($fileMeta) ? $fileMeta : [];
        $fileMeta['is_editable'] = false;
        $fileMeta['save_disabled_reason'] = trim($reason);

        return $fileMeta;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1, ',', '.') . ' KB';
        }

        return $bytes . ' B';
    }

    private function normalizeRelativePath(string $relativePath): string
    {
        $relativePath = preg_replace('/[\x00-\x1F\x7F]/u', '', trim($relativePath)) ?? '';
        $relativePath = str_replace('\\', '/', $relativePath);
        $relativePath = preg_replace('#/+#', '/', $relativePath) ?? '';

        return ltrim($relativePath, '/');
    }

    private function isAllowedRelativePath(string $relativePath): bool
    {
        return preg_match(self::ALLOWED_PATH_PATTERN, $relativePath) === 1;
    }

    private function containsHiddenSegment(string $relativePath): bool
    {
        $segments = explode('/', trim($relativePath, '/'));

        foreach ($segments as $segment) {
            if ($segment !== '' && $segment[0] === '.') {
                return true;
            }
        }

        return false;
    }

    private function containsSkippedSegment(string $relativePath): bool
    {
        $segments = explode('/', trim($relativePath, '/'));

        foreach ($segments as $segment) {
            if ($this->shouldSkipTreeSegment($segment)) {
                return true;
            }
        }

        return false;
    }

    private function shouldSkipTreeSegment(string $segment): bool
    {
        return in_array(strtolower(trim($segment)), self::SKIPPED_TREE_SEGMENTS, true);
    }

    private function pushTreeWarning(array &$summary, string $warning): void
    {
        $warnings = is_array($summary['warnings'] ?? null) ? $summary['warnings'] : [];
        if (!in_array($warning, $warnings, true)) {
            $warnings[] = $warning;
        }

        $summary['warnings'] = $warnings;
    }

    private function failResult(string $action, string $message, ?\Throwable $exception = null, array $context = []): array
    {
        $this->logFailure($action, $message, $exception, $context);

        $errorData = $context;
        if ($exception !== null) {
            $errorData['exception'] = $this->sanitizeErrorContext($exception->getMessage());
        }

        return [
            'success' => false,
            'error' => $message . ' Bitte Logs prüfen.',
            'details' => array_values(array_filter([
                !empty($context['file']) ? 'Datei: ' . (string) $context['file'] : '',
                $exception !== null ? 'Ursache: ' . $this->sanitizeErrorContext($exception->getMessage()) : '',
            ])),
            'error_details' => [
                'code' => $action,
                'data' => $errorData,
                'context' => [
                    'source' => '/admin/theme-explorer',
                    'title' => 'Theme Explorer',
                ],
            ],
            'report_payload' => ErrorReportService::buildReportPayloadFromWpError(
                new \CMS\WP_Error($action, $message . ' Bitte Logs prüfen.', $errorData),
                [
                    'source' => '/admin/theme-explorer',
                    'title' => 'Theme Explorer',
                ]
            ),
        ];
    }

    private function buildValidationFailureResult(string $message, string $errorCode, array $details = [], array $context = []): array
    {
        $normalizedDetails = array_values(array_filter(array_map(
            static fn (mixed $detail): string => trim((string) $detail),
            $details
        ), static fn (string $detail): bool => $detail !== ''));

        return [
            'success' => false,
            'error' => $message,
            'details' => $normalizedDetails,
            'error_details' => [
                'code' => $errorCode,
                'data' => $context,
                'context' => [
                    'source' => '/admin/theme-explorer',
                    'title' => 'Theme Explorer',
                ],
            ],
            'report_payload' => ErrorReportService::buildReportPayloadFromWpError(
                new \CMS\WP_Error($errorCode, $message, $context),
                [
                    'source' => '/admin/theme-explorer',
                    'title' => 'Theme Explorer',
                ]
            ),
        ];
    }

    private function logFailure(string $action, string $message, ?\Throwable $exception = null, array $context = []): void
    {
        if ($exception !== null) {
            $context['exception'] = $this->sanitizeErrorContext($exception->getMessage());
        }

        Logger::instance()->withChannel('admin.theme-editor')->error($message, $context);
        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            $action,
            $message,
            'themes',
            null,
            $context,
            'error'
        );
    }

    private function sanitizeErrorContext(string $value): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', trim($value)) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, self::MAX_ERROR_CONTEXT_LENGTH);
        }

        return substr($value, 0, self::MAX_ERROR_CONTEXT_LENGTH);
    }
}
