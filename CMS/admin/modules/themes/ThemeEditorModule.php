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
use CMS\ThemeManager;

class ThemeEditorModule
{
    private const ALLOWED_PATH_PATTERN = '/^[A-Za-z0-9._\/-]+$/';
    private ThemeManager $themeManager;
    private string $themePath;
    private string $themeSlug;

    private const ALLOWED_EXTENSIONS = ['php', 'css', 'js', 'json', 'html', 'txt', 'md'];
    private const MAX_EDITABLE_BYTES = 1048576;

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
        $files = $this->getFileTree($this->themePath);

        $fileContent  = '';
        $fileLanguage = 'plaintext';
        $fileWarning  = null;

        $requestedFile = $this->normalizeRelativePath($currentFile);
        $currentFile = $requestedFile;

        if ($currentFile !== '') {
            $safePath = $this->resolveSafePath($currentFile);
            if ($safePath && is_file($safePath)) {
                $fileSize = filesize($safePath);
                if ($fileSize === false || $fileSize > self::MAX_EDITABLE_BYTES) {
                    $fileWarning = 'Die ausgewählte Datei ist zu groß für die sichere Browser-Bearbeitung.';
                } else {
                    $contents = file_get_contents($safePath);
                    $fileContent = is_string($contents) ? $contents : '';

                    if (str_contains($fileContent, "\0")) {
                        $fileContent = '';
                        $fileWarning = 'Die ausgewählte Datei enthält Binärdaten und kann hier nicht sicher bearbeitet werden.';
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
            'currentFile'  => $currentFile,
            'fileContent'  => $fileContent,
            'fileLanguage' => $fileLanguage,
            'fileWarning'  => $fileWarning,
        ];
    }

    /**
     * Datei speichern
     */
    public function saveFile(string $relativePath, string $content): array
    {
        $relativePath = $this->normalizeRelativePath($relativePath);
        if ($relativePath === '') {
            return ['success' => false, 'error' => 'Kein Dateipfad angegeben.'];
        }

        $safePath = $this->resolveSafePath($relativePath);
        if (!$safePath) {
            return ['success' => false, 'error' => 'Ungültiger Dateipfad.'];
        }

        if (!is_file($safePath)) {
            return ['success' => false, 'error' => 'Datei nicht gefunden.'];
        }

        $ext = strtolower(pathinfo($safePath, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return ['success' => false, 'error' => 'Dateityp nicht erlaubt.'];
        }

        $fileSize = filesize($safePath);
        if ($fileSize === false || $fileSize > self::MAX_EDITABLE_BYTES) {
            return ['success' => false, 'error' => 'Datei ist zu groß für die sichere Bearbeitung im Browser.'];
        }

        $contentBytes = strlen($content);
        if ($contentBytes > self::MAX_EDITABLE_BYTES) {
            return ['success' => false, 'error' => 'Der neue Dateiinhalt ist zu groß für die sichere Browser-Bearbeitung.'];
        }

        if (str_contains($content, "\0")) {
            return ['success' => false, 'error' => 'Dateiinhalt enthält ungültige Binärdaten.'];
        }

        if (!is_writable($safePath)) {
            return ['success' => false, 'error' => 'Datei ist nicht beschreibbar.'];
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

        return ['success' => true, 'message' => 'Datei gespeichert.'];
    }

    /**
     * Pfad-Traversal-Schutz
     */
    private function resolveSafePath(string $relativePath): ?string
    {
        $relativePath = $this->normalizeRelativePath($relativePath);
        if ($relativePath === '' || !$this->isAllowedRelativePath($relativePath)) {
            return null;
        }

        if ($relativePath === '.' || str_starts_with($relativePath, '../') || str_contains($relativePath, '/../')) {
            return null;
        }

        $realBase = realpath($this->themePath);
        if ($realBase === false) {
            return null;
        }

        $fullPath = $realBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $realFile = realpath($fullPath);
        if ($realFile === false) {
            return null;
        }

        $realBase = rtrim($realBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($realFile, $realBase)) {
            return null;
        }

        return $realFile;
    }

    /**
     * Dateibaum des Themes ermitteln
     */
    private function getFileTree(string $dir, string $prefix = ''): array
    {
        $files = [];
        $items = scandir($dir) ?: [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path     = $dir . $item;
            $relative = $prefix . $item;

            if (is_link($path)) {
                continue;
            }

            if (is_dir($path)) {
                $files[] = [
                    'name'     => $item,
                    'path'     => $relative,
                    'type'     => 'dir',
                    'children' => $this->getFileTree($path . '/', $relative . '/'),
                ];
            } else {
                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                $fileSize = filesize($path);
                if (in_array($ext, self::ALLOWED_EXTENSIONS, true) && $fileSize !== false && $fileSize <= self::MAX_EDITABLE_BYTES) {
                    $files[] = [
                        'name' => $item,
                        'path' => $relative,
                        'type' => 'file',
                        'ext'  => $ext,
                    ];
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

    private function failResult(string $action, string $message, ?\Throwable $exception = null, array $context = []): array
    {
        $this->logFailure($action, $message, $exception, $context);

        return ['success' => false, 'error' => $message . ' Bitte Logs prüfen.'];
    }

    private function logFailure(string $action, string $message, ?\Throwable $exception = null, array $context = []): void
    {
        if ($exception !== null) {
            $context['exception'] = $exception->getMessage();
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
}
