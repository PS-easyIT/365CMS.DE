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

use CMS\ThemeManager;

class ThemeEditorModule
{
    private ThemeManager $themeManager;
    private string $themePath;
    private string $themeSlug;

    private const ALLOWED_EXTENSIONS = ['php', 'css', 'js', 'json', 'html', 'txt', 'md'];

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

        if ($currentFile !== '') {
            $safePath = $this->resolveSafePath($currentFile);
            if ($safePath && file_exists($safePath)) {
                $fileContent  = file_get_contents($safePath);
                $fileLanguage = $this->getLanguage($currentFile);
            }
        }

        return [
            'themeSlug'    => $this->themeSlug,
            'files'        => $files,
            'currentFile'  => $currentFile,
            'fileContent'  => $fileContent,
            'fileLanguage' => $fileLanguage,
        ];
    }

    /**
     * Datei speichern
     */
    public function saveFile(string $relativePath, string $content): array
    {
        if (empty($relativePath)) {
            return ['success' => false, 'error' => 'Kein Dateipfad angegeben.'];
        }

        $safePath = $this->resolveSafePath($relativePath);
        if (!$safePath) {
            return ['success' => false, 'error' => 'Ungültiger Dateipfad.'];
        }

        if (!file_exists($safePath)) {
            return ['success' => false, 'error' => 'Datei nicht gefunden.'];
        }

        $ext = strtolower(pathinfo($safePath, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return ['success' => false, 'error' => 'Dateityp nicht erlaubt.'];
        }

        // PHP-Syntax prüfen vor dem Speichern
        if ($ext === 'php') {
            try {
                token_get_all($content, TOKEN_PARSE);
            } catch (\ParseError $e) {
                return ['success' => false, 'error' => 'PHP-Syntaxfehler: ' . $e->getMessage()];
            }
        }

        if (file_put_contents($safePath, $content) === false) {
            return ['success' => false, 'error' => 'Datei konnte nicht gespeichert werden.'];
        }

        return ['success' => true, 'message' => 'Datei gespeichert.'];
    }

    /**
     * Pfad-Traversal-Schutz
     */
    private function resolveSafePath(string $relativePath): ?string
    {
        // Gefährliche Zeichen entfernen
        $relativePath = str_replace(['..', "\0"], '', $relativePath);

        $fullPath = $this->themePath . $relativePath;
        $realBase = realpath($this->themePath);
        $realFile = realpath(dirname($fullPath)) . DIRECTORY_SEPARATOR . basename($fullPath);

        if ($realBase === false) {
            return null;
        }

        // Verifizieren, dass der Pfad innerhalb des Theme-Verzeichnisses liegt
        if (!str_starts_with($realFile, $realBase)) {
            return null;
        }

        return $fullPath;
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

            if (is_dir($path)) {
                $files[] = [
                    'name'     => $item,
                    'path'     => $relative,
                    'type'     => 'dir',
                    'children' => $this->getFileTree($path . '/', $relative . '/'),
                ];
            } else {
                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                if (in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
                    $files[] = [
                        'name' => $item,
                        'path' => $relative,
                        'type' => 'file',
                        'ext'  => $ext,
                    ];
                }
            }
        }

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
}
