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
use CMS\ThemeManager;
use CMS\AuditLogger;
use CMS\Http\Client as HttpClient;

class FontManagerModule
{
    private Database $db;
    private string $prefix;
    private const MAX_REMOTE_FONT_FILES = 20;
    private const MAX_FONT_FILENAME_LENGTH = 180;

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

    private const CURATED_FONT_LIBRARY = [
        'IT / Tech / SaaS / Dashboards' => [
            ['name' => 'Inter', 'style' => 'Sans-Serif', 'reason' => 'Standard für UI/Dashboards, sehr lesbar'],
            ['name' => 'Roboto', 'style' => 'Sans-Serif', 'reason' => 'Meistgenutzte Google Font weltweit'],
            ['name' => 'JetBrains Mono', 'style' => 'Monospace', 'reason' => 'Code-Blöcke, Terminal-Look'],
            ['name' => 'Fira Code', 'style' => 'Monospace', 'reason' => 'Ligatur-Support, ideal für Code'],
            ['name' => 'Source Code Pro', 'style' => 'Monospace', 'reason' => 'Adobe Open Source, klassisch'],
            ['name' => 'IBM Plex Mono', 'style' => 'Monospace', 'reason' => 'IBM-Design, tech & clean'],
            ['name' => 'Space Grotesk', 'style' => 'Sans-Serif', 'reason' => 'Modern, Tech-Startups'],
            ['name' => 'DM Sans', 'style' => 'Sans-Serif', 'reason' => 'SaaS, klare Geometrie'],
            ['name' => 'Manrope', 'style' => 'Sans-Serif', 'reason' => 'Modern, gut für UI'],
            ['name' => 'IBM Plex Sans', 'style' => 'Sans-Serif', 'reason' => 'Enterprise, professionell'],
        ],
        'Business / Corporate / Firmen' => [
            ['name' => 'Open Sans', 'style' => 'Sans-Serif', 'reason' => 'Universell, seriös, sehr beliebt'],
            ['name' => 'Lato', 'style' => 'Sans-Serif', 'reason' => 'Warm & professionell'],
            ['name' => 'Montserrat', 'style' => 'Sans-Serif', 'reason' => 'Modern, Headings, Logos'],
            ['name' => 'Source Sans 3', 'style' => 'Sans-Serif', 'reason' => 'Adobe Open Source, corporate'],
            ['name' => 'Raleway', 'style' => 'Sans-Serif', 'reason' => 'Elegant, gehobenes Business'],
            ['name' => 'Work Sans', 'style' => 'Sans-Serif', 'reason' => 'Büro-Feel, sehr lesbar'],
            ['name' => 'Nunito', 'style' => 'Sans-Serif', 'reason' => 'Freundlich & seriös'],
            ['name' => 'Poppins', 'style' => 'Sans-Serif', 'reason' => 'Runde Geometrie, modern'],
            ['name' => 'Barlow', 'style' => 'Sans-Serif', 'reason' => 'Sauber, vielseitig'],
            ['name' => 'Figtree', 'style' => 'Sans-Serif', 'reason' => 'Frisch, modern, 2023+ Trend'],
        ],
        'Blogs / Editorial / Longread' => [
            ['name' => 'Merriweather', 'style' => 'Serif', 'reason' => 'Blogklassiker, sehr lesbar'],
            ['name' => 'Playfair Display', 'style' => 'Serif', 'reason' => 'Editorial, Magazin-Look'],
            ['name' => 'Lora', 'style' => 'Serif', 'reason' => 'Warm, perfekt für Fließtext'],
            ['name' => 'Source Serif 4', 'style' => 'Serif', 'reason' => 'Lesbarkeit, langer Text'],
            ['name' => 'Crimson Pro', 'style' => 'Serif', 'reason' => 'Zeitungsanmutung'],
            ['name' => 'Libre Baskerville', 'style' => 'Serif', 'reason' => 'Klassisch, traditionell'],
            ['name' => 'EB Garamond', 'style' => 'Serif', 'reason' => 'Literarisch, elegant'],
            ['name' => 'Fraunces', 'style' => 'Serif', 'reason' => 'Ausdrucksstark, modern'],
            ['name' => 'Alegreya', 'style' => 'Serif', 'reason' => 'Buchcharakter, elegant'],
            ['name' => 'Noto Serif', 'style' => 'Serif', 'reason' => 'Viele Sprachen, neutral'],
        ],
        'Kreativ / Portfolio / Design' => [
            ['name' => 'Urbanist', 'style' => 'Sans-Serif', 'reason' => 'Minimalistisch, trendy'],
            ['name' => 'Outfit', 'style' => 'Sans-Serif', 'reason' => 'Clean, modern, 2022+'],
            ['name' => 'Plus Jakarta Sans', 'style' => 'Sans-Serif', 'reason' => 'Ausgewogen, vielseitig'],
            ['name' => 'Syne', 'style' => 'Sans-Serif', 'reason' => 'Design-Agenturen'],
            ['name' => 'Abril Fatface', 'style' => 'Display', 'reason' => 'Starkes Headline-Statement'],
            ['name' => 'Oswald', 'style' => 'Condensed', 'reason' => 'Kompakt, Header'],
            ['name' => 'Bebas Neue', 'style' => 'Display', 'reason' => 'Fett, Impact, Headlines'],
            ['name' => 'Righteous', 'style' => 'Display', 'reason' => 'Retro-modern'],
            ['name' => 'Exo 2', 'style' => 'Sans-Serif', 'reason' => 'Sci-Fi, Tech-Design'],
            ['name' => 'Orbitron', 'style' => 'Display', 'reason' => 'Futuristisch'],
        ],
        'Handschrift / Dekorativ / Akzente' => [
            ['name' => 'Dancing Script', 'style' => 'Script', 'reason' => 'Elegant, beliebt'],
            ['name' => 'Pacifico', 'style' => 'Script', 'reason' => 'Freundlich, casual'],
            ['name' => 'Caveat', 'style' => 'Handwriting', 'reason' => 'Handgeschrieben, locker'],
            ['name' => 'Satisfy', 'style' => 'Script', 'reason' => 'Elegant, akzentstark'],
            ['name' => 'Patrick Hand', 'style' => 'Handwriting', 'reason' => 'Natürlich, Blog-Akzent'],
        ],
        'Multilingual / Sonderzeichen' => [
            ['name' => 'Noto Sans', 'style' => 'Sans-Serif', 'reason' => '100+ Sprachen, Google Standard'],
            ['name' => 'Rubik', 'style' => 'Sans-Serif', 'reason' => 'Hebräisch + Latein'],
            ['name' => 'Cairo', 'style' => 'Sans-Serif', 'reason' => 'Arabisch + Latein'],
            ['name' => 'Karla', 'style' => 'Sans-Serif', 'reason' => 'Breite Sprachunterstützung'],
            ['name' => 'Ubuntu', 'style' => 'Sans-Serif', 'reason' => 'Linux-Community, breit'],
        ],
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
        $scanResults = $this->scanActiveThemeFonts($customFonts);
        $useLocalFonts = $this->isLocalFontsEnabled();

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
            'useLocalFonts' => $useLocalFonts,
            'fontSize'      => $settings['font_size_base'] ?? '16',
            'lineHeight'    => $settings['font_line_height'] ?? '1.6',
            'scanResults'   => $scanResults,
            'fontCatalog'   => $this->getCuratedFontCatalog($customFonts),
            'activeThemeSlug' => ThemeManager::instance()->getActiveThemeSlug(),
        ];
    }

    public function scanThemeFonts(): array
    {
        $results = $this->scanActiveThemeFonts($this->getCustomFonts());
        $count   = count($results['detectedFonts']);

        // ADDED: Theme-Scans für spätere Diagnose im Audit-Log festhalten.
        AuditLogger::instance()->log(
            AuditLogger::CAT_THEME,
            'font.scan',
            'Theme-Schriften gescannt',
            'theme',
            null,
            ['theme' => (string)($results['theme'] ?? ''), 'detected_fonts' => $count, 'scanned_files' => (int)($results['scannedFiles'] ?? 0)],
            'info'
        );

        return [
            'success' => true,
            'message' => sprintf('Theme-Scan abgeschlossen: %d erkannte Schrift%s in %d Dateien geprüft.', $count, $count === 1 ? '' : 'en', (int)($results['scannedFiles'] ?? 0)),
        ];
    }

    public function downloadDetectedFonts(): array
    {
        $customFonts = $this->getCustomFonts();
        $scanResults = $this->scanActiveThemeFonts($customFonts);
        $detectedFonts = (array)($scanResults['detectedFonts'] ?? []);

        if ($detectedFonts === []) {
            return ['success' => false, 'error' => 'Es wurden keine externen Theme-Schriften erkannt.'];
        }

        $installed = $this->getInstalledFontNames($customFonts);
        $downloaded = 0;
        $skipped = 0;
        $errors = [];

        foreach ($detectedFonts as $font) {
            $fontName = trim((string)($font['name'] ?? ''));
            if ($fontName === '') {
                continue;
            }

            if (in_array(mb_strtolower($fontName), $installed, true)) {
                $skipped++;
                continue;
            }

            $result = $this->downloadGoogleFont($fontName);
            if (!empty($result['success'])) {
                $downloaded++;
                $installed[] = mb_strtolower($fontName);
                continue;
            }

            $message = (string)($result['error'] ?? 'Unbekannter Fehler');
            if (str_contains($message, 'bereits vorhanden')) {
                $skipped++;
                $installed[] = mb_strtolower($fontName);
                continue;
            }

            $errors[] = $fontName . ': ' . $message;
        }

        if ($downloaded === 0 && $errors !== []) {
            return ['success' => false, 'error' => 'Keine Schrift konnte lokal geladen werden. ' . implode(' | ', array_slice($errors, 0, 3))];
        }

        $message = $downloaded . ' Schrift' . ($downloaded === 1 ? '' : 'en') . ' lokal geladen';
        if ($skipped > 0) {
            $message .= ', ' . $skipped . ' bereits vorhanden';
        }
        if ($errors !== []) {
            $message .= '. Hinweise: ' . implode(' | ', array_slice($errors, 0, 3));
        }

        // ADDED: Sammel-Download im Audit-Log dokumentieren.
        AuditLogger::instance()->log(
            AuditLogger::CAT_THEME,
            'font.download.detected',
            'Erkannte Theme-Schriften lokal gespeichert',
            'font',
            null,
            ['downloaded' => $downloaded, 'skipped' => $skipped, 'errors' => $errors],
            $errors === [] ? 'info' : 'warning'
        );

        return ['success' => true, 'message' => $message . '.'];
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
                'privacy_use_local_fonts' => isset($post['use_local_fonts']) ? '1' : '0',
            ];

            $existingSettings = $this->loadExistingSettings(array_keys($settings));

            foreach ($settings as $key => $value) {
                $this->persistSetting($key, (string)$value, $existingSettings);
            }

            return [
                'success' => true,
                'message' => isset($post['use_local_fonts'])
                    ? 'Schrifteinstellungen gespeichert. Lokale On-Prem-Fonts sind jetzt im Frontend aktiv.'
                    : 'Schrifteinstellungen gespeichert. Google-Fonts-Fallback bleibt aktiv.',
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    private function isLocalFontsEnabled(): bool
    {
        try {
            $value = $this->db->get_var(
                "SELECT option_value FROM {$this->prefix}settings WHERE option_name = 'privacy_use_local_fonts' LIMIT 1"
            );
        } catch (\Throwable $e) {
            return false;
        }

        $value = strtolower(trim((string) $value));
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Benutzerdefinierte Schriftart löschen
     */
    public function deleteCustomFont(int $fontId): array
    {
        if ($fontId <= 0) {
            return ['success' => false, 'error' => 'Ungültige Font-ID.'];
        }

        $warnings = [];

        try {
            // Font-Datei(en) löschen
            $font = $this->db->get_row(
                "SELECT file_path, css_path FROM {$this->prefix}custom_fonts WHERE id = ?",
                [$fontId]
            );

            $fontFilePath = $this->resolveManagedFontPath((string) ($font->file_path ?? ''));
            if ($fontFilePath !== null) {
                $this->deleteFileIfExists($fontFilePath, $warnings);
            } elseif (!empty($font->file_path)) {
                $warnings[] = 'Dateipfad liegt außerhalb des verwalteten Fonts-Verzeichnisses.';
            }

            $cssFilePath = $this->resolveManagedFontPath((string) ($font->css_path ?? ''));
            if ($cssFilePath !== null) {
                $this->deleteFileIfExists($cssFilePath, $warnings);
            } elseif (!empty($font->css_path)) {
                $warnings[] = 'CSS-Pfad liegt außerhalb des verwalteten Fonts-Verzeichnisses.';
            }

            $this->db->execute(
                "DELETE FROM {$this->prefix}custom_fonts WHERE id = ?",
                [$fontId]
            );

            // ADDED: Löschaktionen an zentralem Audit-Log spiegeln.
            AuditLogger::instance()->log(
                AuditLogger::CAT_THEME,
                'font.delete',
                'Lokale Schriftart gelöscht',
                'font',
                $fontId,
                ['file_path' => (string)($font->file_path ?? '')],
                'warning'
            );

            $message = 'Schriftart gelöscht.';
            if (!empty($warnings)) {
                $message .= ' Hinweise: ' . implode(' | ', $warnings);
            }

            return ['success' => true, 'message' => $message];
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
        $familyParam = str_replace('%20', '+', rawurlencode($fontFamily));
        $cssUrl = 'https://fonts.googleapis.com/css2?family=' . $familyParam . ':wght@300;400;500;600;700&display=swap';
        $css = $this->fetchRemoteContent($cssUrl);
        if ($css === false) {
            // IMPROVED: Fallback auf Legacy-CSS-Endpunkt, weil manche Hoster/Firewalls css2 blocken.
            $legacyCssUrl = 'https://fonts.googleapis.com/css?family=' . $familyParam . ':300,400,500,600,700&display=swap';
            $css = $this->fetchRemoteContent($legacyCssUrl);
        }
        if ($css === false) {
            return ['success' => false, 'error' => "Google Fonts CSS konnte nicht geladen werden für \"{$fontFamily}\"."];
        }

        // Font-URLs extrahieren und lokal speichern
        $localCss = $css;
        $downloadedFiles = [];

        if (preg_match_all('/url\(([^)]+?\.(woff2?|ttf|otf)[^)]*)\)/i', $css, $matches)) {
            if (count($matches[1]) > self::MAX_REMOTE_FONT_FILES) {
                return ['success' => false, 'error' => 'Zu viele Font-Dateien im Remote-CSS erkannt.'];
            }

            foreach ($matches[1] as $remoteUrl) {
                $remoteUrl = trim($remoteUrl, "'\" ");
                if (!str_starts_with($remoteUrl, 'https://')) {
                    continue;
                }

                // Dateiname aus URL ableiten
                $urlPath = (string)parse_url($remoteUrl, PHP_URL_PATH);
                $extension = strtolower((string)pathinfo($urlPath, PATHINFO_EXTENSION));
                if ($extension === '') {
                    $extension = 'woff2';
                }
                if (!in_array($extension, ['woff2', 'woff', 'ttf', 'otf'], true)) {
                    continue;
                }

                $fileName = $this->buildManagedFontFileName($slug, $urlPath, $extension);
                if ($fileName === null) {
                    continue;
                }

                $localPath = $fontsDir . $fileName;
                $fontData = $this->fetchRemoteContent($remoteUrl);
                if ($fontData === false) {
                    continue;
                }

                if (file_put_contents($localPath, $fontData) === false) {
                    continue;
                }
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
        if (file_put_contents($cssFile, $localCss) === false) {
            return ['success' => false, 'error' => 'Lokale CSS-Datei für die Schrift konnte nicht geschrieben werden.'];
        }

        // In DB speichern
        $this->db->execute(
            "INSERT INTO {$this->prefix}custom_fonts (name, slug, format, file_path, css_path, source, created_at)
             VALUES (?, ?, 'woff2', ?, ?, 'google-fonts-local', NOW())",
            [$fontFamily, $slug, 'uploads/fonts/' . $downloadedFiles[0], 'uploads/fonts/' . $slug . '.css']
        );

        // Font-Stack registrieren
        $fontStack = "'{$fontFamily}', sans-serif";
        $this->db->execute(
            "INSERT INTO {$this->prefix}settings (option_name, option_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)",
            ['font_stack_' . $slug, $fontStack]
        );

        // ADDED: Erfolgreiche lokale Font-Downloads protokollieren.
        AuditLogger::instance()->log(
            AuditLogger::CAT_THEME,
            'font.download.google',
            'Google Font lokal gespeichert',
            'font',
            null,
            ['font_family' => $fontFamily, 'downloaded_files' => $downloadedFiles],
            'info'
        );

        return [
            'success' => true,
            'message' => "Schrift \"{$fontFamily}\" DSGVO-konform heruntergeladen (" . count($downloadedFiles) . " Dateien). Kein externer CDN-Aufruf mehr nötig.",
        ];
    }

    private function isAllowedFontUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($scheme !== 'https') {
            return false;
        }

        return in_array($host, ['fonts.googleapis.com', 'fonts.gstatic.com'], true);
    }

    private function fetchRemoteContent(string $url): string|false
    {
        if (!$this->isAllowedFontUrl($url)) {
            return false;
        }

        $acceptHeader = str_contains($url, 'fonts.gstatic.com')
            ? 'font/woff2,font/woff,font/ttf,application/octet-stream,*/*;q=0.1'
            : 'text/css,*/*;q=0.1';

        $headers = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept: ' . $acceptHeader,
            'Accept-Language: de-DE,de;q=0.9,en;q=0.8',
        ];

        $response = HttpClient::getInstance()->get($url, [
            'userAgent' => '365CMS-FontManager/1.0',
            'headers' => $headers,
            'timeout' => 20,
            'connectTimeout' => 10,
            'maxBytes' => str_contains($url, 'fonts.gstatic.com') ? 5 * 1024 * 1024 : 512 * 1024,
            'allowedContentTypes' => str_contains($url, 'fonts.gstatic.com')
                ? ['font/', 'application/octet-stream', 'application/font-', 'application/x-font-']
                : ['text/css'],
        ]);

        $content = (string) ($response['body'] ?? '');

        return (($response['success'] ?? false) === true && $content !== '') ? $content : false;
    }

    /** @param list<string> $keys
     *  @return array<string, true>
     */
    private function loadExistingSettings(array $keys): array
    {
        $keys = array_values(array_filter(array_unique($keys), static fn (mixed $key): bool => is_string($key) && $key !== ''));
        if ($keys === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $rows = $this->db->get_results(
            "SELECT option_name FROM {$this->prefix}settings WHERE option_name IN ({$placeholders})",
            $keys
        ) ?: [];

        $existing = [];
        foreach ($rows as $row) {
            $optionName = trim((string) ($row->option_name ?? ''));
            if ($optionName !== '') {
                $existing[$optionName] = true;
            }
        }

        return $existing;
    }

    /** @param array<string, true> $existingSettings */
    private function persistSetting(string $key, string $value, array &$existingSettings): void
    {
        if (isset($existingSettings[$key])) {
            $this->db->execute(
                "UPDATE {$this->prefix}settings SET option_value = ? WHERE option_name = ?",
                [$value, $key]
            );

            return;
        }

        $this->db->execute(
            "INSERT INTO {$this->prefix}settings (option_name, option_value) VALUES (?, ?)",
            [$key, $value]
        );
        $existingSettings[$key] = true;
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

    private function scanActiveThemeFonts(array $customFonts): array
    {
        $themeManager = ThemeManager::instance();
        $themePath    = $themeManager->getThemePath();
        $themeSlug    = $themeManager->getActiveThemeSlug();
        $knownFonts   = $this->getKnownFontIndex();
        $installed    = $this->getInstalledFontNames($customFonts);
        $detected     = [];
        $scannedFiles = 0;

        if (!is_dir($themePath)) {
            return [
                'theme' => $themeSlug,
                'scannedFiles' => 0,
                'detectedFonts' => [],
            ];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($themePath, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $extension = strtolower($file->getExtension());
            if (!in_array($extension, ['css', 'php', 'js', 'json', 'txt', 'md'], true)) {
                continue;
            }

            $content = $this->readFileContents($file->getPathname());
            if (!is_string($content) || $content === '') {
                continue;
            }

            $scannedFiles++;
            $relativePath = str_replace(['\\', THEME_PATH], ['/', ''], $file->getPathname());

            foreach ($this->extractGoogleFontFamilies($content) as $family) {
                $fontKey = mb_strtolower($family);
                $meta = $knownFonts[$fontKey] ?? ['style' => 'Google Font', 'reason' => 'Im Theme via Google Fonts importiert'];
                if (!isset($detected[$fontKey])) {
                    $detected[$fontKey] = [
                        'name' => $family,
                        'style' => $meta['style'],
                        'reason' => $meta['reason'],
                        'sources' => [],
                        'installed' => in_array($fontKey, $installed, true),
                    ];
                }
                $detected[$fontKey]['sources'][$relativePath] = 'Google Fonts Import';
            }

            foreach ($knownFonts as $fontKey => $meta) {
                if (stripos($content, $meta['name']) === false) {
                    continue;
                }

                if (!isset($detected[$fontKey])) {
                    $detected[$fontKey] = [
                        'name' => $meta['name'],
                        'style' => $meta['style'],
                        'reason' => $meta['reason'],
                        'sources' => [],
                        'installed' => in_array($fontKey, $installed, true),
                    ];
                }
                $detected[$fontKey]['sources'][$relativePath] = 'Theme-Datei';
            }
        }

        uasort($detected, static fn(array $a, array $b): int => strcasecmp($a['name'], $b['name']));

        return [
            'theme' => $themeSlug,
            'scannedFiles' => $scannedFiles,
            'detectedFonts' => array_values(array_map(static function (array $font): array {
                $font['sources'] = array_map(
                    static fn(string $type, string $file): array => ['file' => $file, 'type' => $type],
                    array_values($font['sources']),
                    array_keys($font['sources'])
                );
                return $font;
            }, $detected)),
        ];
    }

    private function getCuratedFontCatalog(array $customFonts): array
    {
        $installed = $this->getInstalledFontNames($customFonts);
        $catalog   = [];

        foreach (self::CURATED_FONT_LIBRARY as $category => $fonts) {
            $catalog[$category] = array_map(static function (array $font) use ($installed): array {
                $font['installed'] = in_array(mb_strtolower($font['name']), $installed, true);
                return $font;
            }, $fonts);
        }

        return $catalog;
    }

    private function getKnownFontIndex(): array
    {
        $fonts = [];
        foreach (self::CURATED_FONT_LIBRARY as $category => $items) {
            foreach ($items as $item) {
                $fonts[mb_strtolower($item['name'])] = $item;
            }
        }

        return $fonts;
    }

    private function getInstalledFontNames(array $customFonts): array
    {
        $installed = [];
        foreach ($customFonts as $font) {
            $name = trim((string)($font->name ?? ''));
            if ($name !== '') {
                $installed[] = mb_strtolower($name);
            }
        }

        return array_values(array_unique($installed));
    }

    private function extractGoogleFontFamilies(string $content): array
    {
        $families = [];

        if (preg_match_all('/fonts\.googleapis\.com\/css2?\?([^"\'\s)]+)/i', $content, $matches)) {
            foreach ($matches[1] as $queryString) {
                if (preg_match_all('/(?:^|&)family=([^&]+)/', $queryString, $familyMatches)) {
                    foreach ($familyMatches[1] as $rawFamily) {
                        $family = trim(str_replace(['+', ':'], [' ', ' '], urldecode($rawFamily)));
                        $family = preg_replace('/\s+wght\s+.*/i', '', $family) ?? $family;
                        $family = trim($family);
                        if ($family !== '') {
                            $families[] = $family;
                        }
                    }
                }
            }
        }

        return array_values(array_unique($families));
    }

    /**
     * @param array<int, string> $warnings
     */
    private function deleteFileIfExists(string $path, array &$warnings): bool
    {
        if (!is_file($path)) {
            return true;
        }

        if (!unlink($path)) {
            $warnings[] = 'Datei konnte nicht gelöscht werden: ' . basename($path);
            return false;
        }

        return true;
    }

    private function readFileContents(string $path): string|false
    {
        if (!is_file($path) || !is_readable($path)) {
            return false;
        }

        return file_get_contents($path);
    }

    private function buildManagedFontFileName(string $slug, string $urlPath, string $extension): ?string
    {
        $baseName = basename($urlPath);
        $baseName = preg_replace('/[^a-zA-Z0-9._-]/', '-', $baseName) ?? '';
        $baseName = preg_replace('/-+/', '-', $baseName) ?? '';
        $baseName = trim($baseName, '-_.');

        if ($baseName === '' || preg_match('/[\x00-\x1F\x7F]/', $baseName) === 1) {
            return null;
        }

        $expectedSuffix = '.' . strtolower($extension);
        if (!str_ends_with(strtolower($baseName), $expectedSuffix)) {
            $baseName .= $expectedSuffix;
        }

        $fileName = $slug . '-' . $baseName;
        if (strlen($fileName) > self::MAX_FONT_FILENAME_LENGTH) {
            $suffix = '-' . substr(sha1($fileName), 0, 12) . $expectedSuffix;
            $maxBaseLength = self::MAX_FONT_FILENAME_LENGTH - strlen($suffix);
            if ($maxBaseLength <= 0) {
                return null;
            }

            $fileName = substr($slug . '-' . pathinfo($baseName, PATHINFO_FILENAME), 0, $maxBaseLength) . $suffix;
        }

        return $fileName;
    }

    private function getManagedFontsDirectory(): ?string
    {
        $basePath = defined('ABSPATH') ? (string) ABSPATH : '';
        if ($basePath === '') {
            return null;
        }

        $fontsDir = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'fonts';

        if (!is_dir($fontsDir)) {
            return null;
        }

        $resolvedFontsDir = realpath($fontsDir);

        return $resolvedFontsDir !== false ? rtrim($resolvedFontsDir, '/\\') : null;
    }

    private function resolveManagedFontPath(string $storedPath): ?string
    {
        $storedPath = trim(str_replace('\\', '/', $storedPath));
        if ($storedPath === '') {
            return null;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $storedPath) === 1 || str_contains($storedPath, '..')) {
            return null;
        }

        $fontsDir = $this->getManagedFontsDirectory();
        if ($fontsDir === null) {
            return null;
        }

        $normalizedPrefix = 'uploads/fonts/';
        if (!str_starts_with(strtolower($storedPath), $normalizedPrefix)) {
            return null;
        }

        $relativePath = ltrim(substr($storedPath, strlen($normalizedPrefix)), '/');
        if ($relativePath === '') {
            return null;
        }

        $candidatePath = $fontsDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $resolvedCandidate = realpath($candidatePath);
        if ($resolvedCandidate === false) {
            return is_file($candidatePath) ? $candidatePath : null;
        }

        $normalizedFontsDir = str_replace('\\', '/', $fontsDir);
        $normalizedCandidate = str_replace('\\', '/', $resolvedCandidate);

        return str_starts_with($normalizedCandidate, $normalizedFontsDir . '/') || $normalizedCandidate === $normalizedFontsDir
            ? $resolvedCandidate
            : null;
    }
}
