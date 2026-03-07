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
    private readonly \CMS\PageManager $pageManager;
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

    /** @var array<string, array<string, string>> */
    private const array PAGE_CONFIG = [
        'imprint' => [
            'setting_key' => 'legal_imprint',
            'page_id_key' => 'imprint_page_id',
            'title'       => 'Impressum',
            'slug'        => 'impressum',
            'meta_desc'   => 'Impressum und Anbieterkennzeichnung.',
        ],
        'privacy' => [
            'setting_key' => 'legal_privacy',
            'page_id_key' => 'privacy_page_id',
            'title'       => 'Datenschutzerklärung',
            'slug'        => 'datenschutz',
            'meta_desc'   => 'Informationen zur Verarbeitung personenbezogener Daten.',
        ],
        'terms' => [
            'setting_key' => 'legal_terms',
            'page_id_key' => 'terms_page_id',
            'title'       => 'AGB',
            'slug'        => 'agb',
            'meta_desc'   => 'Allgemeine Geschäftsbedingungen.',
        ],
        'revocation' => [
            'setting_key' => 'legal_revocation',
            'page_id_key' => 'revocation_page_id',
            'title'       => 'Widerrufsbelehrung',
            'slug'        => 'widerruf',
            'meta_desc'   => 'Informationen zum gesetzlichen Widerrufsrecht.',
        ],
    ];

    /** @var array<string, string> */
    private const array PROFILE_DEFAULTS = [
        'legal_profile_company_name'        => '',
        'legal_profile_legal_form'          => '',
        'legal_profile_owner_name'          => '',
        'legal_profile_managing_director'   => '',
        'legal_profile_content_responsible' => '',
        'legal_profile_street'              => '',
        'legal_profile_postal_code'         => '',
        'legal_profile_city'                => '',
        'legal_profile_country'             => 'Deutschland',
        'legal_profile_email'               => '',
        'legal_profile_phone'               => '',
        'legal_profile_website'             => '',
        'legal_profile_register_court'      => '',
        'legal_profile_register_number'     => '',
        'legal_profile_vat_id'              => '',
        'legal_profile_dispute_participation' => 'no',
        'legal_profile_hosting_provider'    => '',
        'legal_profile_hosting_address'     => '',
        'legal_profile_privacy_contact_name' => '',
        'legal_profile_privacy_contact_email' => '',
        'legal_profile_analytics_name'      => '',
        'legal_profile_payment_providers'   => '',
        'legal_profile_terms_scope'         => 'b2c',
        'legal_profile_contract_type'       => 'services',
        'legal_profile_return_costs'        => 'customer',
        'legal_profile_service_start_notice' => '0',
        'legal_profile_has_cookies'         => '1',
        'legal_profile_has_contact_form'    => '1',
        'legal_profile_has_registration'    => '0',
        'legal_profile_has_comments'        => '0',
        'legal_profile_has_newsletter'      => '0',
        'legal_profile_has_analytics'       => '0',
        'legal_profile_has_external_media'  => '0',
        'legal_profile_has_webfonts'        => '0',
        'legal_profile_has_shop'            => '0',
    ];

    public function __construct()
    {
        $this->db     = \CMS\Database::instance();
        $this->pageManager = \CMS\PageManager::instance();
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
            'profile'        => $this->loadProfile(),
            'page_configs'   => self::PAGE_CONFIG,
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

    public function saveProfile(array $post): array
    {
        try {
            foreach (array_keys(self::PROFILE_DEFAULTS) as $key) {
                $value = $this->sanitizeProfileValue($key, $post[$key] ?? self::PROFILE_DEFAULTS[$key]);
                $this->saveSetting($key, $value);
            }

            return ['success' => true, 'message' => 'Standardwerte für Legal Sites gespeichert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Speichern der Standardwerte: ' . $e->getMessage()];
        }
    }

    public function getTemplateContent(string $type): string
    {
        $templates = $this->getTemplates($this->loadProfile());

        return $templates[$type] ?? '';
    }

    public function generateTemplate(string $type): array
    {
        $templates = $this->getTemplates($this->loadProfile());

        if (!isset($templates[$type])) {
            return ['success' => false, 'error' => 'Unbekannter Vorlagentyp.'];
        }

        $key = 'legal_' . $type;
        $this->saveSetting($key, $templates[$type]);

        return ['success' => true, 'message' => self::LABELS[$key] . '-Vorlage generiert.'];
    }

    public function createOrUpdatePage(string $type, int $userId): array
    {
        $config = self::PAGE_CONFIG[$type] ?? null;
        if ($config === null) {
            return ['success' => false, 'error' => 'Unbekannter Seitentyp.'];
        }

        try {
            $content = $this->getTemplateContent($type);
            if ($content === '') {
                return ['success' => false, 'error' => 'Für diesen Bereich konnte kein Inhalt generiert werden.'];
            }

            $this->saveSetting($config['setting_key'], $content);

            $pageId = (int)$this->getSetting($config['page_id_key']);
            $title = $config['title'];
            $slug = $config['slug'];
            $metaDesc = $config['meta_desc'];

            if ($pageId > 0) {
                $page = $this->pageManager->getPage($pageId);
                if ($page !== null) {
                    $this->pageManager->updatePage($pageId, [
                        'title'            => $title,
                        'slug'             => $slug,
                        'content'          => $content,
                        'status'           => 'published',
                        'meta_title'       => $title,
                        'meta_description' => $metaDesc,
                    ]);

                    return ['success' => true, 'message' => $title . ' wurde aktualisiert.', 'page_id' => $pageId];
                }
            }

            $existingBySlug = $this->pageManager->getPageBySlug($slug);
            if ($existingBySlug !== null && !empty($existingBySlug['id'])) {
                $pageId = (int)$existingBySlug['id'];
                $this->pageManager->updatePage($pageId, [
                    'title'            => $title,
                    'slug'             => $slug,
                    'content'          => $content,
                    'status'           => 'published',
                    'meta_title'       => $title,
                    'meta_description' => $metaDesc,
                ]);
            } else {
                $pageId = $this->pageManager->createPage($title, $content, 'published', $userId, 0);
                $this->pageManager->updatePage($pageId, [
                    'slug'             => $slug,
                    'meta_title'       => $title,
                    'meta_description' => $metaDesc,
                ]);
            }

            $this->saveSetting($config['page_id_key'], (string)$pageId);

            return ['success' => true, 'message' => $title . ' wurde als Seite erstellt.', 'page_id' => $pageId];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler bei der Seitenerstellung: ' . $e->getMessage()];
        }
    }

    public function createOrUpdateAllPages(int $userId): array
    {
        $created = [];
        foreach (array_keys(self::PAGE_CONFIG) as $type) {
            $result = $this->createOrUpdatePage($type, $userId);
            if (!$result['success']) {
                return $result;
            }

            $created[] = self::PAGE_CONFIG[$type]['title'];
        }

        return [
            'success' => true,
            'message' => 'Rechtstext-Seiten erstellt/aktualisiert: ' . implode(', ', $created) . '.',
        ];
    }

    /** @return array<string, string> */
    private function getTemplates(array $profile): array
    {
        return [
            'imprint'    => $this->buildImprintTemplate($profile),
            'privacy'    => $this->buildPrivacyTemplate($profile),
            'terms'      => $this->buildTermsTemplate($profile),
            'revocation' => $this->buildRevocationTemplate($profile),
        ];
    }

    /** @return array<string, string> */
    private function loadProfile(): array
    {
        $settings = [];
        $keys = array_keys(self::PROFILE_DEFAULTS);
        $rows = $this->db->get_results(
            "SELECT option_name, option_value FROM {$this->prefix}settings WHERE option_name IN ('" . implode("','", $keys) . "')"
        ) ?: [];

        foreach ($rows as $row) {
            $settings[$row->option_name] = (string)$row->option_value;
        }

        $defaults = [
            'legal_profile_company_name' => (string)$this->getSetting('site_name', defined('SITE_NAME') ? (string)SITE_NAME : ''),
            'legal_profile_email'        => (string)$this->getSetting('admin_email', ''),
            'legal_profile_website'      => (string)$this->getSetting('site_url', defined('SITE_URL') ? (string)SITE_URL : ''),
        ];

        return array_merge(self::PROFILE_DEFAULTS, $defaults, $settings);
    }

    private function sanitizeProfileValue(string $key, mixed $value): string
    {
        $booleanKeys = [
            'legal_profile_has_cookies',
            'legal_profile_has_contact_form',
            'legal_profile_has_registration',
            'legal_profile_has_comments',
            'legal_profile_has_newsletter',
            'legal_profile_has_analytics',
            'legal_profile_has_external_media',
            'legal_profile_has_webfonts',
            'legal_profile_has_shop',
        ];

        if (in_array($key, $booleanKeys, true)) {
            return !empty($value) ? '1' : '0';
        }

        if ($key === 'legal_profile_dispute_participation') {
            return in_array((string)$value, ['yes', 'no'], true) ? (string)$value : 'no';
        }

        if ($key === 'legal_profile_terms_scope') {
            return in_array((string)$value, ['b2c', 'b2b', 'mixed'], true) ? (string)$value : 'b2c';
        }

        if ($key === 'legal_profile_contract_type') {
            return in_array((string)$value, ['goods', 'services', 'digital', 'mixed'], true) ? (string)$value : 'services';
        }

        if ($key === 'legal_profile_return_costs') {
            return in_array((string)$value, ['customer', 'merchant'], true) ? (string)$value : 'customer';
        }

        if (in_array($key, ['legal_profile_email', 'legal_profile_privacy_contact_email'], true)) {
            return filter_var((string)$value, FILTER_VALIDATE_EMAIL) ?: '';
        }

        if ($key === 'legal_profile_website') {
            $website = trim((string)$value);
            return $website === '' ? '' : ((string)filter_var($website, FILTER_SANITIZE_URL));
        }

        return trim(strip_tags((string)$value));
    }

    private function saveSetting(string $key, string $value): void
    {
        $exists = (int)$this->db->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}settings WHERE option_name = ?",
            [$key]
        );

        if ($exists > 0) {
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
    }

    private function getSetting(string $key, string $fallback = ''): string
    {
        $value = $this->db->get_var(
            "SELECT option_value FROM {$this->prefix}settings WHERE option_name = ? LIMIT 1",
            [$key]
        );

        return $value !== null ? (string)$value : $fallback;
    }

    private function buildImprintTemplate(array $profile): string
    {
        $companyName = $this->profileValue($profile, 'legal_profile_company_name', 'Ihr Unternehmen');
        $legalForm = $this->profileValue($profile, 'legal_profile_legal_form');
        $owner = $this->profileValue($profile, 'legal_profile_owner_name');
        $managingDirector = $this->profileValue($profile, 'legal_profile_managing_director');
        $contentResponsible = $this->profileValue($profile, 'legal_profile_content_responsible', $owner !== '' ? $owner : $managingDirector);
        $email = $this->profileValue($profile, 'legal_profile_email');
        $phone = $this->profileValue($profile, 'legal_profile_phone');
        $website = $this->profileValue($profile, 'legal_profile_website');
        $registerCourt = $this->profileValue($profile, 'legal_profile_register_court');
        $registerNumber = $this->profileValue($profile, 'legal_profile_register_number');
        $vatId = $this->profileValue($profile, 'legal_profile_vat_id');

        $html = '<h2>Impressum</h2>';
        $html .= '<p>Angaben gemäß § 5 DDG</p>';
        $html .= '<p><strong>' . $this->escape($companyName) . '</strong>';
        if ($legalForm !== '') {
            $html .= '<br>' . $this->escape($legalForm);
        }
        $html .= '<br>' . $this->nl2brEscaped($this->buildAddress($profile)) . '</p>';

        if ($owner !== '' || $managingDirector !== '') {
            $html .= '<h3>Vertreten durch</h3><p>';
            if ($owner !== '') {
                $html .= '<strong>Inhaber:</strong> ' . $this->escape($owner);
            }
            if ($managingDirector !== '') {
                $html .= ($owner !== '' ? '<br>' : '') . '<strong>Geschäftsführung:</strong> ' . $this->escape($managingDirector);
            }
            $html .= '</p>';
        }

        $contactLines = [];
        if ($phone !== '') {
            $contactLines[] = '<strong>Telefon:</strong> ' . $this->escape($phone);
        }
        if ($email !== '') {
            $contactLines[] = '<strong>E-Mail:</strong> <a href="mailto:' . $this->escapeAttr($email) . '">' . $this->escape($email) . '</a>';
        }
        if ($website !== '') {
            $contactLines[] = '<strong>Website:</strong> <a href="' . $this->escapeAttr($website) . '" target="_blank" rel="noopener noreferrer">' . $this->escape($website) . '</a>';
        }
        if ($contactLines !== []) {
            $html .= '<h3>Kontakt</h3><p>' . implode('<br>', $contactLines) . '</p>';
        }

        if ($registerCourt !== '' || $registerNumber !== '') {
            $html .= '<h3>Registereintrag</h3><p>';
            if ($registerCourt !== '') {
                $html .= '<strong>Registergericht:</strong> ' . $this->escape($registerCourt);
            }
            if ($registerNumber !== '') {
                $html .= ($registerCourt !== '' ? '<br>' : '') . '<strong>Registernummer:</strong> ' . $this->escape($registerNumber);
            }
            $html .= '</p>';
        }

        if ($vatId !== '') {
            $html .= '<h3>Umsatzsteuer-ID</h3><p>Umsatzsteuer-Identifikationsnummer gemäß § 27a UStG: ' . $this->escape($vatId) . '</p>';
        }

        if ($contentResponsible !== '') {
            $html .= '<h3>Inhaltlich verantwortlich</h3><p>' . $this->escape($contentResponsible) . '</p>';
        }

        $html .= '<h3>EU-Streitschlichtung</h3><p>Die Europäische Kommission stellt eine Plattform zur Online-Streitbeilegung bereit: <a href="https://ec.europa.eu/consumers/odr/" target="_blank" rel="noopener noreferrer">https://ec.europa.eu/consumers/odr/</a>.</p>';
        $html .= '<p>' . ($profile['legal_profile_dispute_participation'] === 'yes'
            ? 'Wir nehmen an einem Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teil.'
            : 'Wir sind nicht verpflichtet und nicht bereit, an einem Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.') . '</p>';

        $html .= '<h3>Haftung für Inhalte</h3><p>Die Inhalte dieser Website werden mit größtmöglicher Sorgfalt erstellt. Eine Gewähr für Vollständigkeit, Richtigkeit und Aktualität kann jedoch nicht in jedem Einzelfall übernommen werden. Bei Bekanntwerden konkreter Rechtsverletzungen werden betroffene Inhalte umgehend geprüft und gegebenenfalls entfernt.</p>';
        $html .= '<h3>Haftung für Links</h3><p>Diese Website kann Links zu externen Angeboten enthalten. Für Inhalte verlinkter Seiten sind ausschließlich deren jeweilige Betreiber verantwortlich. Zum Zeitpunkt der Verlinkung waren keine Rechtsverstöße erkennbar. Bei Bekanntwerden rechtswidriger Inhalte werden entsprechende Links entfernt.</p>';
        $html .= '<h3>Urheberrecht</h3><p>Texte, Bilder, Grafiken und sonstige Inhalte dieser Website unterliegen dem geltenden Urheberrecht. Eine Nutzung außerhalb der gesetzlichen Schranken bedarf der vorherigen Zustimmung des jeweiligen Rechteinhabers.</p>';

        return sanitize_html($html, 'default');
    }

    private function buildPrivacyTemplate(array $profile): string
    {
        $companyName = $this->profileValue($profile, 'legal_profile_company_name', 'Ihr Unternehmen');
        $email = $this->profileValue($profile, 'legal_profile_email');
        $privacyContactName = $this->profileValue($profile, 'legal_profile_privacy_contact_name', $companyName);
        $privacyContactEmail = $this->profileValue($profile, 'legal_profile_privacy_contact_email', $email);
        $hostingProvider = $this->profileValue($profile, 'legal_profile_hosting_provider');
        $hostingAddress = $this->profileValue($profile, 'legal_profile_hosting_address');
        $analyticsName = $this->profileValue($profile, 'legal_profile_analytics_name');
        $paymentProviders = $this->profileValue($profile, 'legal_profile_payment_providers');

        $html = '<h2>Datenschutzerklärung</h2>';
        $html .= '<h3>1. Verantwortliche Stelle</h3><p>Verantwortlich für die Datenverarbeitung auf dieser Website ist:<br><strong>' . $this->escape($privacyContactName) . '</strong><br>' . $this->nl2brEscaped($this->buildAddress($profile));
        if ($privacyContactEmail !== '') {
            $html .= '<br>E-Mail: <a href="mailto:' . $this->escapeAttr($privacyContactEmail) . '">' . $this->escape($privacyContactEmail) . '</a>';
        }
        $html .= '</p>';

        $html .= '<h3>2. Allgemeine Hinweise</h3><p>Der Schutz personenbezogener Daten wird ernst genommen. Personenbezogene Daten werden nur im Rahmen der gesetzlichen Vorgaben sowie dieser Datenschutzerklärung verarbeitet.</p>';

        if ($hostingProvider !== '') {
            $html .= '<h3>3. Hosting</h3><p>Diese Website wird bei <strong>' . $this->escape($hostingProvider) . '</strong>';
            if ($hostingAddress !== '') {
                $html .= ' (' . $this->escape($hostingAddress) . ')';
            }
            $html .= ' gehostet. Server-Log-Dateien werden verarbeitet, um Stabilität, Sicherheit und den technischen Betrieb der Website sicherzustellen.</p>';
        } else {
            $html .= '<h3>3. Hosting und Server-Log-Dateien</h3><p>Beim Besuch dieser Website werden technisch erforderliche Informationen wie IP-Adresse, Zeitpunkt des Zugriffs, Browsertyp und angeforderte Ressource in Server-Log-Dateien verarbeitet. Die Verarbeitung erfolgt zur Gewährleistung von Sicherheit, Stabilität und Fehleranalyse.</p>';
        }

        $html .= '<h3>4. Kontaktaufnahme</h3><p>Wenn Sie per E-Mail';
        if ($profile['legal_profile_has_contact_form'] === '1') {
            $html .= ' oder Kontaktformular';
        }
        $html .= ' Kontakt aufnehmen, werden Ihre Angaben ausschließlich zur Bearbeitung Ihrer Anfrage verarbeitet.</p>';

        if ($profile['legal_profile_has_cookies'] === '1') {
            $html .= '<h3>5. Cookies und Einwilligungsverwaltung</h3><p>Diese Website verwendet technisch notwendige Cookies. Soweit optionale Dienste eingesetzt werden, erfolgt deren Aktivierung erst nach Ihrer Auswahl im Consent-Banner. Bereits erteilte Einwilligungen können jederzeit angepasst oder widerrufen werden.</p>';
        }

        if ($profile['legal_profile_has_registration'] === '1') {
            $html .= '<h3>6. Registrierung und Benutzerkonto</h3><p>Bei der Registrierung werden nur die für die Einrichtung und Nutzung des Benutzerkontos erforderlichen Daten verarbeitet. Pflichtangaben werden ausschließlich zur Bereitstellung des jeweiligen Dienstes genutzt.</p>';
        }

        if ($profile['legal_profile_has_newsletter'] === '1') {
            $html .= '<h3>7. Newsletter</h3><p>Wenn Sie sich für den Newsletter anmelden, wird Ihre E-Mail-Adresse zur Zustellung der gewünschten Informationen verarbeitet. Die Einwilligung kann jederzeit mit Wirkung für die Zukunft widerrufen werden.</p>';
        }

        if ($profile['legal_profile_has_comments'] === '1') {
            $html .= '<h3>8. Kommentare und Beiträge</h3><p>Beim Hinterlassen von Kommentaren können neben dem Inhalt auch technische Metadaten wie Zeitstempel und IP-Adresse verarbeitet werden, um die Kommentarfunktion bereitzustellen und Missbrauch zu verhindern.</p>';
        }

        if ($profile['legal_profile_has_analytics'] === '1') {
            $html .= '<h3>9. Analyse und Reichweitenmessung</h3><p>';
            $html .= $analyticsName !== ''
                ? 'Auf dieser Website wird ' . $this->escape($analyticsName) . ' zur Analyse von Nutzung und Reichweite eingesetzt. Eine Aktivierung erfolgt nur im Rahmen der jeweils gewählten Einwilligung.'
                : 'Sofern Analyse- oder Trackingdienste eingesetzt werden, erfolgt deren Aktivierung ausschließlich nach entsprechender Einwilligung.';
            $html .= '</p>';
        }

        if ($profile['legal_profile_has_external_media'] === '1' || $profile['legal_profile_has_webfonts'] === '1') {
            $html .= '<h3>10. Externe Inhalte und eingebundene Dienste</h3><p>';
            $parts = [];
            if ($profile['legal_profile_has_external_media'] === '1') {
                $parts[] = 'Externe Medieninhalte oder Drittanbieter-Ressourcen können erst nach Ihrer Interaktion geladen werden';
            }
            if ($profile['legal_profile_has_webfonts'] === '1') {
                $parts[] = 'eingebundene Schriftarten werden datenschutzfreundlich und möglichst lokal bereitgestellt';
            }
            $html .= $this->escape(implode('. ', $parts)) . '.</p>';
        }

        if ($profile['legal_profile_has_shop'] === '1' || $paymentProviders !== '') {
            $html .= '<h3>11. Vertrags- und Zahlungsabwicklung</h3><p>Zur Bearbeitung von Bestellungen und zur Abwicklung von Zahlungen werden die hierfür erforderlichen Daten verarbeitet.';
            if ($paymentProviders !== '') {
                $html .= ' Eingesetzte Zahlungsdienstleister: ' . $this->escape($paymentProviders) . '.';
            }
            $html .= '</p>';
        }

        $html .= '<h3>12. Rechtsgrundlagen</h3><p>Die Verarbeitung personenbezogener Daten erfolgt je nach Sachverhalt auf Grundlage Ihrer Einwilligung, zur Vertragserfüllung, zur Erfüllung rechtlicher Verpflichtungen oder auf Grundlage berechtigter Interessen, sofern diese den Schutz Ihrer Rechte und Freiheiten nicht überwiegen.</p>';
        $html .= '<h3>13. Speicherdauer</h3><p>Personenbezogene Daten werden nur so lange gespeichert, wie dies für den jeweiligen Verarbeitungszweck erforderlich ist oder gesetzliche Aufbewahrungspflichten bestehen.</p>';
        $html .= '<h3>14. Ihre Rechte</h3><p>Sie haben das Recht auf Auskunft, Berichtigung, Löschung, Einschränkung der Verarbeitung, Datenübertragbarkeit sowie Widerspruch gegen bestimmte Verarbeitungen. Bereits erteilte Einwilligungen können jederzeit mit Wirkung für die Zukunft widerrufen werden.</p>';
        $html .= '<h3>15. Beschwerderecht</h3><p>Wenn Sie der Auffassung sind, dass die Verarbeitung Ihrer personenbezogenen Daten rechtswidrig erfolgt, können Sie sich bei einer zuständigen Datenschutzaufsichtsbehörde beschweren.</p>';

        return sanitize_html($html, 'default');
    }

    private function buildTermsTemplate(array $profile): string
    {
        $companyName = $this->profileValue($profile, 'legal_profile_company_name', 'der Anbieter');
        $email = $this->profileValue($profile, 'legal_profile_email');
        $scope = $this->profileValue($profile, 'legal_profile_terms_scope', 'b2c');
        $contractType = $this->profileValue($profile, 'legal_profile_contract_type', 'services');
        $website = $this->profileValue($profile, 'legal_profile_website');

        $scopeText = match ($scope) {
            'b2b' => 'ausschließlich gegenüber Unternehmern im Sinne des § 14 BGB, juristischen Personen des öffentlichen Rechts oder öffentlich-rechtlichen Sondervermögen',
            'mixed' => 'gegenüber Verbrauchern im Sinne des § 13 BGB sowie gegenüber Unternehmern im Sinne des § 14 BGB',
            default => 'gegenüber Verbrauchern im Sinne des § 13 BGB',
        };

        $subjectText = match ($contractType) {
            'goods' => 'den Verkauf und die Lieferung von Waren',
            'digital' => 'die Bereitstellung digitaler Inhalte und digitaler Leistungen',
            'mixed' => 'den Verkauf von Waren sowie die Erbringung von Dienstleistungen und digitalen Leistungen',
            default => 'die Erbringung von Dienstleistungen und Werkleistungen',
        };

        $html = '<h2>Allgemeine Geschäftsbedingungen</h2>';
        $html .= '<h3>§ 1 Geltungsbereich</h3><p>Diese Allgemeinen Geschäftsbedingungen gelten für alle Verträge zwischen ' . $this->escape($companyName) . ' und unseren Kunden ' . $this->escape($scopeText) . ', soweit nicht ausdrücklich und schriftlich etwas anderes vereinbart wurde.</p>';
        $html .= '<p>Sie regeln insbesondere ' . $this->escape($subjectText) . '. Individuelle Vereinbarungen mit dem Kunden haben stets Vorrang vor diesen AGB.</p>';

        $html .= '<h3>§ 2 Vertragspartner und Vertragsgegenstand</h3><p>Vertragspartner ist ' . $this->escape($companyName) . '. Gegenstand des Vertrages ist jeweils die im Angebot, auf der Produktseite, in der Buchungsübersicht oder in der individuellen Vereinbarung beschriebene Leistung.</p>';

        $html .= '<h3>§ 3 Vertragsschluss</h3><p>Die Präsentation unserer Leistungen';
        if ($website !== '') {
            $html .= ' auf der Website <a href="' . $this->escapeAttr($website) . '" target="_blank" rel="noopener noreferrer">' . $this->escape($website) . '</a>';
        }
        $html .= ' stellt kein rechtlich bindendes Angebot dar, sondern eine unverbindliche Aufforderung zur Abgabe einer Bestellung oder Anfrage.</p>';
        $html .= '<p>Ein Vertrag kommt erst zustande, wenn wir eine Bestellung, Buchung oder Anfrage ausdrücklich bestätigen, mit der Leistung beginnen oder die Ware versenden.</p>';

        $html .= '<h3>§ 4 Preise und Zahlungsbedingungen</h3><p>Alle Preise verstehen sich in Euro. Sofern nicht anders ausgewiesen, enthalten Preise gegenüber Verbrauchern die gesetzliche Umsatzsteuer. Gegenüber Unternehmern können Preise als Nettopreise zuzüglich gesetzlicher Umsatzsteuer ausgewiesen werden.</p>';
        $html .= '<p>Zahlungen sind sofort nach Vertragsschluss bzw. Rechnungsstellung ohne Abzug fällig, sofern keine abweichende Zahlungsfrist vereinbart wurde. Bei Zahlungsverzug gelten die gesetzlichen Vorschriften.</p>';

        $html .= '<h3>§ 5 Leistungserbringung, Lieferung und Verfügbarkeit</h3>';
        if ($contractType === 'goods' || $contractType === 'mixed') {
            $html .= '<p>Lieferzeiten werden individuell angegeben oder ergeben sich aus der jeweiligen Produktbeschreibung. Teillieferungen sind zulässig, soweit sie dem Kunden zumutbar sind.</p>';
        }
        if ($contractType === 'services' || $contractType === 'mixed') {
            $html .= '<p>Dienstleistungen und Werkleistungen werden zu den vereinbarten Terminen oder innerhalb des vereinbarten Leistungszeitraums erbracht. Voraussetzung ist, dass der Kunde die erforderlichen Mitwirkungshandlungen rechtzeitig erfüllt.</p>';
        }
        if ($contractType === 'digital') {
            $html .= '<p>Digitale Inhalte oder digitale Leistungen werden nach Vertragsschluss und nach Eingang einer vereinbarten Zahlung bereitgestellt, sofern keine abweichende Vereinbarung getroffen wurde.</p>';
        }
        $html .= '<p>Sollte eine Leistung aus Gründen, die wir nicht zu vertreten haben, nicht verfügbar sein, werden wir den Kunden hierüber unverzüglich informieren und bereits erhaltene Gegenleistungen erstatten.</p>';

        $html .= '<h3>§ 6 Eigentumsvorbehalt</h3>';
        if ($contractType === 'goods' || $contractType === 'mixed') {
            $html .= '<p>Bis zur vollständigen Bezahlung verbleiben gelieferte Waren in unserem Eigentum.</p>';
        } else {
            $html .= '<p>Soweit körperliche Unterlagen oder Datenträger geliefert werden, bleiben diese bis zur vollständigen Bezahlung unser Eigentum.</p>';
        }

        $html .= '<h3>§ 7 Gewährleistung und Mängelrechte</h3><p>Es gelten die gesetzlichen Mängelrechte. Gegenüber Unternehmern können die Gewährleistungsrechte im gesetzlich zulässigen Umfang eingeschränkt sein. Offensichtliche Mängel sind uns möglichst zeitnah mitzuteilen.</p>';

        if ($scope !== 'b2b') {
            $html .= '<h3>§ 8 Widerrufsrecht für Verbraucher</h3><p>Verbrauchern steht bei Fernabsatzverträgen grundsätzlich ein gesetzliches Widerrufsrecht zu. Die Einzelheiten ergeben sich aus der gesonderten Widerrufsbelehrung.</p>';
        } else {
            $html .= '<h3>§ 8 Kein gesetzliches Widerrufsrecht</h3><p>Ein gesetzliches Widerrufsrecht besteht für Unternehmer nicht, soweit nicht ausdrücklich etwas anderes vereinbart wurde.</p>';
        }

        $html .= '<h3>§ 9 Haftung</h3><p>Wir haften unbeschränkt für Vorsatz und grobe Fahrlässigkeit sowie bei Verletzung von Leben, Körper oder Gesundheit. Bei leicht fahrlässiger Verletzung wesentlicher Vertragspflichten ist die Haftung auf den vertragstypischen, vorhersehbaren Schaden begrenzt. Im Übrigen ist die Haftung ausgeschlossen, soweit gesetzlich zulässig.</p>';

        $html .= '<h3>§ 10 Schlussbestimmungen</h3><p>Es gilt das Recht der Bundesrepublik Deutschland unter Ausschluss des UN-Kaufrechts, soweit keine zwingenden Verbraucherschutzvorschriften entgegenstehen.</p>';
        if ($scope === 'b2b') {
            $html .= '<p>Ist der Kunde Kaufmann, juristische Person des öffentlichen Rechts oder öffentlich-rechtliches Sondervermögen, ist der Sitz unseres Unternehmens Gerichtsstand für alle Streitigkeiten aus dem Vertragsverhältnis.</p>';
        }
        if ($email !== '') {
            $html .= '<p>Für Rückfragen erreichen Sie uns unter <a href="mailto:' . $this->escapeAttr($email) . '">' . $this->escape($email) . '</a>.</p>';
        }

        return sanitize_html($html, 'default');
    }

    private function buildRevocationTemplate(array $profile): string
    {
        $companyName = $this->profileValue($profile, 'legal_profile_company_name', 'den Anbieter');
        $email = $this->profileValue($profile, 'legal_profile_email');
        $phone = $this->profileValue($profile, 'legal_profile_phone');
        $scope = $this->profileValue($profile, 'legal_profile_terms_scope', 'b2c');
        $contractType = $this->profileValue($profile, 'legal_profile_contract_type', 'services');
        $returnCosts = $this->profileValue($profile, 'legal_profile_return_costs', 'customer');
        $serviceStartNotice = $this->profileValue($profile, 'legal_profile_service_start_notice', '0') === '1';

        if ($scope === 'b2b') {
            $html = '<h2>Hinweis zum Widerrufsrecht</h2>';
            $html .= '<p>Diese Angebote richten sich ausschließlich an Unternehmer. Für Unternehmer besteht kein gesetzliches Widerrufsrecht.</p>';
            return sanitize_html($html, 'default');
        }

        $contactDetails = '<strong>' . $this->escape($companyName) . '</strong><br>' . $this->nl2brEscaped($this->buildAddress($profile));
        if ($phone !== '') {
            $contactDetails .= '<br>Telefon: ' . $this->escape($phone);
        }
        if ($email !== '') {
            $contactDetails .= '<br>E-Mail: <a href="mailto:' . $this->escapeAttr($email) . '">' . $this->escape($email) . '</a>';
        }

        $periodText = match ($contractType) {
            'goods' => 'Die Widerrufsfrist beträgt vierzehn Tage ab dem Tag, an dem Sie oder ein von Ihnen benannter Dritter, der nicht Beförderer ist, die Ware in Besitz genommen haben.',
            'digital' => 'Die Widerrufsfrist beträgt vierzehn Tage ab dem Tag des Vertragsabschlusses, sofern nicht das Widerrufsrecht bei digitalen Inhalten vorzeitig erlischt.',
            'mixed' => 'Die Widerrufsfrist beträgt bei Dienstleistungen vierzehn Tage ab dem Tag des Vertragsabschlusses und bei Warenlieferungen vierzehn Tage ab dem Tag, an dem Sie oder ein von Ihnen benannter Dritter, der nicht Beförderer ist, die letzte Ware in Besitz genommen haben.',
            default => 'Die Widerrufsfrist beträgt vierzehn Tage ab dem Tag des Vertragsabschlusses.',
        };

        $returnCostText = $returnCosts === 'merchant'
            ? 'Wir tragen die unmittelbaren Kosten der Rücksendung der Waren.'
            : 'Sie tragen die unmittelbaren Kosten der Rücksendung der Waren.';

        $html = '<h2>Widerrufsbelehrung</h2>';
        $html .= '<p>Verbrauchern steht bei außerhalb von Geschäftsräumen geschlossenen Verträgen und bei Fernabsatzverträgen grundsätzlich ein gesetzliches Widerrufsrecht zu. Verbraucher ist jede natürliche Person, die ein Rechtsgeschäft zu Zwecken abschließt, die überwiegend weder ihrer gewerblichen noch ihrer selbständigen beruflichen Tätigkeit zugerechnet werden können.</p>';
        $html .= '<h3>Widerrufsrecht</h3><p>Sie haben das Recht, binnen vierzehn Tagen ohne Angabe von Gründen diesen Vertrag zu widerrufen.</p>';
        $html .= '<p>' . $this->escape($periodText) . '</p>';
        $html .= '<h3>Ausübung des Widerrufs</h3><p>Um Ihr Widerrufsrecht auszuüben, müssen Sie uns mittels einer eindeutigen Erklärung über Ihren Entschluss, diesen Vertrag zu widerrufen, informieren. Unsere Kontaktdaten lauten:</p>';
        $html .= '<p>' . $contactDetails . '</p>';
        $html .= '<p>Sie können dafür das unten stehende Muster-Widerrufsformular verwenden, das jedoch nicht vorgeschrieben ist.';
        if ($email !== '') {
            $html .= ' Die Erklärung kann auch per E-Mail an <a href="mailto:' . $this->escapeAttr($email) . '">' . $this->escape($email) . '</a> erfolgen.';
        }
        $html .= ' Zur Wahrung der Widerrufsfrist reicht es aus, dass Sie die Mitteilung vor Ablauf der Widerrufsfrist absenden.</p>';

        $html .= '<h3>Folgen des Widerrufs</h3><p>Wenn Sie diesen Vertrag widerrufen, haben wir Ihnen alle Zahlungen, die wir von Ihnen erhalten haben, einschließlich der Standard-Lieferkosten, unverzüglich und spätestens binnen vierzehn Tagen ab dem Tag zurückzuzahlen, an dem Ihre Mitteilung über den Widerruf dieses Vertrags bei uns eingegangen ist. Für die Rückzahlung verwenden wir dasselbe Zahlungsmittel, das Sie bei der ursprünglichen Transaktion eingesetzt haben, es sei denn, mit Ihnen wurde ausdrücklich etwas anderes vereinbart. In keinem Fall werden Ihnen wegen dieser Rückzahlung Entgelte berechnet.</p>';

        if ($contractType === 'goods' || $contractType === 'mixed') {
            $html .= '<p>Wir können die Rückzahlung verweigern, bis wir die Waren wieder zurückerhalten haben oder bis Sie den Nachweis erbracht haben, dass Sie die Waren zurückgesandt haben, je nachdem, welcher Zeitpunkt der frühere ist.</p>';
            $html .= '<p>Sie haben die Waren unverzüglich und in jedem Fall spätestens binnen vierzehn Tagen ab dem Tag, an dem Sie uns über den Widerruf dieses Vertrags unterrichten, an uns zurückzusenden oder zu übergeben. Die Frist ist gewahrt, wenn Sie die Waren vor Ablauf der Frist absenden.</p>';
            $html .= '<p>' . $this->escape($returnCostText) . '</p>';
            $html .= '<p>Sie müssen für einen etwaigen Wertverlust der Waren nur aufkommen, wenn dieser Wertverlust auf einen zur Prüfung der Beschaffenheit, Eigenschaften und Funktionsweise der Waren nicht notwendigen Umgang mit ihnen zurückzuführen ist.</p>';
        }

        if ($contractType === 'services' || $contractType === 'mixed') {
            $html .= '<p>Haben Sie verlangt, dass die Dienstleistung bereits während der Widerrufsfrist beginnen soll, so haben Sie uns einen angemessenen Betrag zu zahlen, der dem Anteil der bis zum Zeitpunkt Ihres Widerrufs bereits erbrachten Leistungen im Vergleich zum Gesamtumfang der vertraglich vorgesehenen Leistungen entspricht.</p>';
        }

        if ($contractType === 'digital') {
            $html .= '<p>Bei Verträgen über digitale Inhalte kann das Widerrufsrecht vorzeitig erlöschen, wenn wir mit der Ausführung des Vertrags begonnen haben, nachdem Sie ausdrücklich zugestimmt haben, dass wir vor Ablauf der Widerrufsfrist mit der Ausführung beginnen, und Sie Ihre Kenntnis davon bestätigt haben, dass Sie dadurch Ihr Widerrufsrecht verlieren.</p>';
        }

        if ($serviceStartNotice && ($contractType === 'services' || $contractType === 'digital' || $contractType === 'mixed')) {
            $html .= '<h3>Hinweis zum vorzeitigen Beginn der Ausführung</h3><p>Wenn Sie ausdrücklich verlangen, dass wir vor Ablauf der Widerrufsfrist mit der Leistung beginnen, und die gesetzlichen Voraussetzungen vorliegen, kann Ihr Widerrufsrecht bei vollständiger Vertragserfüllung vorzeitig erlöschen.</p>';
        }

        $html .= '<h3>Muster-Widerrufsformular</h3>';
        $html .= '<p>(Wenn Sie den Vertrag widerrufen wollen, dann füllen Sie bitte dieses Formular aus und senden Sie es zurück.)</p>';
        $html .= '<p>An:<br>' . $contactDetails . '</p>';
        $html .= '<p>Hiermit widerrufe(n) ich/wir (*) den von mir/uns (*) abgeschlossenen Vertrag über '; 
        $html .= match ($contractType) {
            'goods' => 'den Kauf der folgenden Waren: ____________________',
            'digital' => 'die Bereitstellung der folgenden digitalen Inhalte: ____________________',
            'mixed' => 'den Kauf der folgenden Waren / die Erbringung der folgenden Dienstleistung: ____________________',
            default => 'die Erbringung der folgenden Dienstleistung: ____________________',
        };
        $html .= '<br>Bestellt am (*) / erhalten am (*): ____________________';
        $html .= '<br>Name des/der Verbraucher(s): ____________________';
        $html .= '<br>Anschrift des/der Verbraucher(s): ____________________';
        $html .= '<br>Unterschrift des/der Verbraucher(s) (nur bei Mitteilung auf Papier): ____________________';
        $html .= '<br>Datum: ____________________';
        $html .= '<br>(*) Unzutreffendes streichen.</p>';

        return sanitize_html($html, 'default');
    }

    private function buildAddress(array $profile): string
    {
        $lines = [];
        $street = $this->profileValue($profile, 'legal_profile_street');
        $postalCode = $this->profileValue($profile, 'legal_profile_postal_code');
        $city = $this->profileValue($profile, 'legal_profile_city');
        $country = $this->profileValue($profile, 'legal_profile_country');

        if ($street !== '') {
            $lines[] = $street;
        }
        $cityLine = trim($postalCode . ' ' . $city);
        if ($cityLine !== '') {
            $lines[] = $cityLine;
        }
        if ($country !== '') {
            $lines[] = $country;
        }

        return implode("\n", $lines);
    }

    private function profileValue(array $profile, string $key, string $fallback = ''): string
    {
        $value = trim((string)($profile[$key] ?? ''));
        return $value !== '' ? $value : $fallback;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function escapeAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function nl2brEscaped(string $value): string
    {
        return nl2br($this->escape($value), false);
    }
}
