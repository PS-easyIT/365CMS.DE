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
            'scanResults'   => $scanResults,
            'fontCatalog'   => $this->getCuratedFontCatalog($customFonts),
            'activeThemeSlug' => ThemeManager::instance()->getActiveThemeSlug(),
        ];
    }

    public function scanThemeFonts(): array
    {
        $results = $this->scanActiveThemeFonts($this->getCustomFonts());
        $count   = count($results['detectedFonts']);

        return [
            'success' => true,
            'message' => sprintf('Theme-Scan abgeschlossen: %d erkannte Schrift%s in %d Dateien geprüft.', $count, $count === 1 ? '' : 'en', (int)($results['scannedFiles'] ?? 0)),
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
                    $this->db->execute(
                        "UPDATE {$this->prefix}settings SET option_value = ? WHERE option_name = ?",
                        [(string)$value, $key]
                    );
                } else {
                    $this->db->execute(
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

            $this->db->execute(
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

            $content = @file_get_contents($file->getPathname());
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
}
