<?php
declare(strict_types=1);

/**
 * Font Manager Module – Schriftarten-Verwaltung
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Database;

class FontManagerModule
{
    private Database $db;
    private string $prefix;

    private const SYSTEM_FONTS = [
        'system-ui'       => 'System UI (Standard)',
        'arial'           => 'Arial',
        'georgia'         => 'Georgia',
        'times-new-roman' => 'Times New Roman',
        'courier-new'     => 'Courier New',
        'verdana'         => 'Verdana',
        'trebuchet-ms'    => 'Trebuchet MS',
    ];

    private const FONT_STACKS = [
        'system-ui'       => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif",
        'arial'           => "Arial, 'Helvetica Neue', Helvetica, sans-serif",
        'georgia'         => "Georgia, 'Times New Roman', Times, serif",
        'times-new-roman' => "'Times New Roman', Times, serif",
        'courier-new'     => "'Courier New', Courier, monospace",
        'verdana'         => "Verdana, Geneva, sans-serif",
        'trebuchet-ms'    => "'Trebuchet MS', 'Lucida Grande', sans-serif",
    ];

    public function __construct()
    {
        $this->db     = Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    /**
     * Font-Daten laden
     */
    public function getData(): array
    {
        $settings = $this->getSettings();
        $customFonts = $this->getCustomFonts();

        // Custom Fonts in Auswahl-Optionen und Font-Stacks integrieren
        $allFonts  = self::SYSTEM_FONTS;
        $allStacks = self::FONT_STACKS;
        foreach ($customFonts as $font) {
            $key = $font->slug ?? '';
            if ($key !== '') {
                $allFonts[$key]  = ($font->name ?? $key) . ' (Lokal)';
                $allStacks[$key] = "'{$font->name}', sans-serif";
            }
        }

        return [
            'systemFonts'   => $allFonts,
            'fontStacks'    => $allStacks,
            'customFonts'   => $customFonts,
            'headingFont'   => $settings['font_heading'] ?? 'system-ui',
            'bodyFont'      => $settings['font_body'] ?? 'system-ui',
            'fontSize'      => $settings['font_size_base'] ?? '16',
            'lineHeight'    => $settings['font_line_height'] ?? '1.6',
        ];
    }

    /**
     * Font-Einstellungen speichern
     */
    public function saveSettings(array $post): array
    {
        try {
            $settings = [
                'font_heading'     => preg_replace('/[^a-zA-Z0-9_-]/', '', $post['heading_font'] ?? 'system-ui'),
                'font_body'        => preg_replace('/[^a-zA-Z0-9_-]/', '', $post['body_font'] ?? 'system-ui'),
                'font_size_base'   => max(12, min(24, (int)($post['font_size'] ?? 16))),
                'font_line_height' => max(1.0, min(2.5, (float)($post['line_height'] ?? 1.6))),
            ];

            foreach ($settings as $key => $value) {
                $existing = $this->db->get_var(
                    "SELECT COUNT(*) FROM {$this->prefix}settings WHERE option_name = ?",
                    [$key]
                );

                if ((int)$existing > 0) {
                    $this->db->query(
                        "UPDATE {$this->prefix}settings SET option_value = ? WHERE option_name = ?",
                        [(string)$value, $key]
                    );
                } else {
                    $this->db->query(
                        "INSERT INTO {$this->prefix}settings (option_name, option_value) VALUES (?, ?)",
                        [$key, (string)$value]
                    );
                }
            }

            return ['success' => true, 'message' => 'Schrifteinstellungen gespeichert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    /**
     * Benutzerdefinierte Schriftart löschen
     */
    public function deleteCustomFont(int $fontId): array
    {
        if ($fontId <= 0) {
            return ['success' => false, 'error' => 'Ungültige Font-ID.'];
        }

        try {
            // Font-Datei(en) löschen
            $font = $this->db->get_row(
                "SELECT file_path FROM {$this->prefix}custom_fonts WHERE id = ?",
                [$fontId]
            );
            if ($font && !empty($font->file_path)) {
                $absPath = (defined('ABSPATH') ? ABSPATH : '') . ltrim($font->file_path, '/');
                if (is_file($absPath)) {
                    @unlink($absPath);
                }
                // CSS-Datei entfernen
                $cssPath = preg_replace('/\.\w+$/', '.css', $absPath);
                if ($cssPath && is_file($cssPath)) {
                    @unlink($cssPath);
                }
            }

            $this->db->query(
                "DELETE FROM {$this->prefix}custom_fonts WHERE id = ?",
                [$fontId]
            );
            return ['success' => true, 'message' => 'Schriftart gelöscht.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    /**
     * Google Font DSGVO-konform lokal herunterladen
     */
    public function downloadGoogleFont(string $fontFamily): array
    {
        $fontFamily = trim($fontFamily);
        if ($fontFamily === '' || !preg_match('/^[a-zA-Z0-9 ]+$/', $fontFamily)) {
            return ['success' => false, 'error' => 'Ungültiger Schriftname. Nur Buchstaben, Zahlen und Leerzeichen erlaubt.'];
        }

        $fontsDir = (defined('ABSPATH') ? ABSPATH : '') . 'uploads/fonts/';
        if (!is_dir($fontsDir) && !mkdir($fontsDir, 0755, true)) {
            return ['success' => false, 'error' => 'Schriften-Verzeichnis konnte nicht erstellt werden.'];
        }

        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', strtolower($fontFamily)) ?? '');

        // Prüfe ob bereits heruntergeladen
        $existing = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}custom_fonts WHERE name = ?",
            [$fontFamily]
        );
        if ((int)$existing > 0) {
            return ['success' => false, 'error' => "Schrift \"{$fontFamily}\" ist bereits vorhanden."];
        }

        // Google Fonts CSS API (WOFF2 via User-Agent)
        $cssUrl = 'https://fonts.googleapis.com/css2?family=' . urlencode(str_replace(' ', '+', $fontFamily)) . ':wght@300;400;500;600;700&display=swap';
        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'header'  => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n",
            ],
        ]);
        $css = @file_get_contents($cssUrl, false, $context);
        if ($css === false) {
            return ['success' => false, 'error' => "Google Fonts CSS konnte nicht geladen werden für \"{$fontFamily}\"."];
        }

        // Font-URLs extrahieren und lokal speichern
        $localCss = $css;
        $downloadedFiles = [];

        if (preg_match_all('/url\(([^)]+\.woff2[^)]*)\)/i', $css, $matches)) {
            foreach ($matches[1] as $remoteUrl) {
                $remoteUrl = trim($remoteUrl, "'\" ");
                if (!str_starts_with($remoteUrl, 'https://')) {
                    continue;
                }

                // Dateiname aus URL ableiten
                $urlPath = (string)parse_url($remoteUrl, PHP_URL_PATH);
                $fileName = $slug . '-' . basename($urlPath);
                if (!str_ends_with($fileName, '.woff2')) {
                    $fileName .= '.woff2';
                }

                $localPath = $fontsDir . $fileName;
                $fontData = @file_get_contents($remoteUrl, false, $context);
                if ($fontData === false) {
                    continue;
                }

                file_put_contents($localPath, $fontData);
                $downloadedFiles[] = $fileName;

                // URL im CSS ersetzen
                $relUrl = SITE_URL . '/uploads/fonts/' . $fileName;
                $localCss = str_replace($remoteUrl, $relUrl, $localCss);
            }
        }

        if (empty($downloadedFiles)) {
            return ['success' => false, 'error' => "Keine Font-Dateien konnten heruntergeladen werden für \"{$fontFamily}\"."];
        }

        // Lokales CSS speichern
        $cssFile = $fontsDir . $slug . '.css';
        file_put_contents($cssFile, $localCss);

        // In DB speichern
        $this->db->query(
            "INSERT INTO {$this->prefix}custom_fonts (name, slug, format, file_path, css_path, source, created_at)
             VALUES (?, ?, 'woff2', ?, ?, 'google-fonts-local', NOW())",
            [$fontFamily, $slug, 'uploads/fonts/' . $downloadedFiles[0], 'uploads/fonts/' . $slug . '.css']
        );

        // Font-Stack registrieren
        $fontStack = "'{$fontFamily}', sans-serif";
        $this->db->query(
            "INSERT INTO {$this->prefix}settings (option_name, option_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)",
            ['font_stack_' . $slug, $fontStack]
        );

        return [
            'success' => true,
            'message' => "Schrift \"{$fontFamily}\" DSGVO-konform heruntergeladen (" . count($downloadedFiles) . " Dateien). Kein externer CDN-Aufruf mehr nötig.",
        ];
    }

    /**
     * Einstellungen aus der DB
     */
    private function getSettings(): array
    {
        $settings = [];
        try {
            $rows = $this->db->get_results(
                "SELECT option_name, option_value FROM {$this->prefix}settings WHERE option_name LIKE 'font_%'"
            ) ?: [];
            foreach ($rows as $row) {
                $settings[$row->option_name] = $row->option_value;
            }
        } catch (\Throwable $e) {
            // Defaults verwenden
        }
        return $settings;
    }

    /**
     * Benutzerdefinierte Schriftarten laden
     */
    private function getCustomFonts(): array
    {
        try {
            return $this->db->get_results(
                "SELECT * FROM {$this->prefix}custom_fonts ORDER BY name ASC"
            ) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
