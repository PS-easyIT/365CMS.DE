<?php
declare(strict_types=1);

/**
 * LegalSitesModule – Impressum, Datenschutz, AGB, Widerruf verwalten
 */

if (!defined('ABSPATH')) {
    exit;
}

class LegalSitesModule
{
    private readonly \CMS\Database $db;
    private readonly string $prefix;

    /** @var list<string> */
    private const array LEGAL_KEYS = [
        'legal_imprint',
        'legal_privacy',
        'legal_terms',
        'legal_revocation',
    ];

    /** @var array<string, string> */
    private const array LABELS = [
        'legal_imprint'    => 'Impressum',
        'legal_privacy'    => 'Datenschutzerklärung',
        'legal_terms'      => 'AGB',
        'legal_revocation' => 'Widerrufsbelehrung',
    ];

    public function __construct()
    {
        $this->db     = \CMS\Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    public function getData(): array
    {
        $pages = [];
        foreach (self::LEGAL_KEYS as $key) {
            $row = $this->db->get_row(
                "SELECT option_value FROM {$this->prefix}settings WHERE option_name = ?",
                [$key]
            );
            $pages[$key] = [
                'label'   => self::LABELS[$key],
                'content' => $row->option_value ?? '',
            ];
        }

        // Zugewiesene Seiten-IDs
        $assignedPages = [];
        foreach (['imprint_page_id', 'privacy_page_id', 'terms_page_id', 'revocation_page_id'] as $k) {
            $row = $this->db->get_row(
                "SELECT option_value FROM {$this->prefix}settings WHERE option_name = ?",
                [$k]
            );
            $assignedPages[$k] = $row->option_value ?? '';
        }

        // Alle veröffentlichten Seiten für Zuordnung
        $allPages = $this->db->get_results(
            "SELECT id, title FROM {$this->prefix}pages WHERE status = 'published' ORDER BY title"
        ) ?: [];

        return [
            'pages'          => $pages,
            'assigned_pages' => $assignedPages,
            'all_pages'      => array_map(fn($p) => (array)$p, $allPages),
        ];
    }

    public function save(array $post): array
    {
        try {
            // Rechtliche Inhalte speichern
            foreach (self::LEGAL_KEYS as $key) {
                if (!array_key_exists($key, $post)) {
                    continue;
                }

                $value = strip_tags($post[$key] ?? '', '<p><a><strong><em><ul><ol><li><br><h2><h3><h4>');
                $exists = $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}settings WHERE option_name = ?", [$key]);
                if ($exists) {
                    $this->db->update('settings', ['option_value' => $value], ['option_name' => $key]);
                } else {
                    $this->db->insert('settings', ['option_name' => $key, 'option_value' => $value]);
                }
            }

            // Seiten-Zuordnungen speichern
            foreach (['imprint_page_id', 'privacy_page_id', 'terms_page_id', 'revocation_page_id'] as $k) {
                if (!array_key_exists($k, $post)) {
                    continue;
                }

                $value = (string)(int)($post[$k] ?? 0);
                $exists = $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}settings WHERE option_name = ?", [$k]);
                if ($exists) {
                    $this->db->update('settings', ['option_value' => $value], ['option_name' => $k]);
                } else {
                    $this->db->insert('settings', ['option_name' => $k, 'option_value' => $value]);
                }
            }

            return ['success' => true, 'message' => 'Rechtliche Seiten gespeichert.'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    public function getTemplateContent(string $type): string
    {
        $templates = $this->getTemplates();

        return $templates[$type] ?? '';
    }

    public function generateTemplate(string $type): array
    {
        $templates = $this->getTemplates();

        if (!isset($templates[$type])) {
            return ['success' => false, 'error' => 'Unbekannter Vorlagentyp.'];
        }

        $key = 'legal_' . $type;
        $exists = $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}settings WHERE option_name = ?", [$key]);
        if ($exists) {
            $this->db->update('settings', ['option_value' => $templates[$type]], ['option_name' => $key]);
        } else {
            $this->db->insert('settings', ['option_name' => $key, 'option_value' => $templates[$type]]);
        }

        return ['success' => true, 'message' => self::LABELS[$key] . '-Vorlage generiert.'];
    }

    /** @return array<string, string> */
    private function getTemplates(): array
    {
        return [
            'imprint'    => '<h2>Impressum</h2><p>Angaben gemäß § 5 TMG:</p><p><strong>[Firmenname]</strong><br>[Straße Nr.]<br>[PLZ Ort]</p><p><strong>Vertreten durch:</strong><br>[Name Geschäftsführer]</p><p><strong>Kontakt:</strong><br>Telefon: [Telefon]<br>E-Mail: [E-Mail]</p><p><strong>Registereintrag:</strong><br>Registergericht: [Gericht]<br>Registernummer: [HRB-Nr.]</p><p><strong>Umsatzsteuer-ID:</strong><br>gemäß §27a UStG: [USt-ID]</p>',
            'privacy'    => '<h2>Datenschutzerklärung</h2><h3>1. Datenschutz auf einen Blick</h3><p>Die folgenden Hinweise geben einen einfachen Überblick darüber, was mit Ihren personenbezogenen Daten passiert, wenn Sie diese Website besuchen.</p><h3>2. Allgemeine Hinweise und Pflichtinformationen</h3><p><strong>Verantwortlich:</strong><br>[Firmenname]<br>[Adresse]<br>[E-Mail]</p><h3>3. Datenerfassung auf dieser Website</h3><p>Die Datenverarbeitung auf dieser Website erfolgt durch den Websitebetreiber.</p>',
            'terms'      => '<h2>Allgemeine Geschäftsbedingungen</h2><h3>§ 1 Geltungsbereich</h3><p>Diese AGB gelten für alle Verträge zwischen [Firmenname] und dem Kunden.</p><h3>§ 2 Vertragsschluss</h3><p>Die Darstellung der Produkte im Online-Shop stellt kein rechtlich bindendes Angebot dar.</p>',
            'revocation' => '<h2>Widerrufsbelehrung</h2><h3>Widerrufsrecht</h3><p>Sie haben das Recht, binnen vierzehn Tagen ohne Angabe von Gründen diesen Vertrag zu widerrufen.</p><p>Die Widerrufsfrist beträgt vierzehn Tage ab dem Tag des Vertragsabschlusses.</p>',
        ];
    }
}
