<?php
/**
 * Cookie Consent Service
 *
 * Lädt CookieConsent-Assets und rendert die Runtime-Konfiguration
 * aus den DB-Settings im Frontend.
 *
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class CookieConsentService
{
    private static ?self $instance = null;

    private readonly Database $db;
    private readonly string $prefix;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->db = Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    public function isEnabled(): bool
    {
        return $this->getSetting('cookie_consent_enabled', $this->getSetting('cookie_banner_enabled', '0')) === '1';
    }

    /**
     * Schaltet den Legacy-Banner im Theme aus, sobald der neue Consent-Flow aktiv ist.
     */
    public function isManagedExternally(): bool
    {
        return $this->isEnabled();
    }

    public function render(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        if ($this->isAdminRequest()) {
            return;
        }

        $assetsBasePath = ASSETS_PATH . 'cookieconsent/';
        $assetsBaseUrl = SITE_URL . '/assets/cookieconsent';

        if (!is_dir($assetsBasePath)) {
            return;
        }

        $consentConfig = $this->buildConsentConfiguration();

        $config = [
            'position' => $this->sanitizePosition($this->getSetting('cookie_banner_position', 'bottom')),
            'primaryColor' => $this->sanitizeHexColor($this->getSetting('cookie_primary_color', '#3b82f6')),
            'bannerText' => $this->getSetting('cookie_banner_text', 'Wir nutzen Cookies für eine optimale Website-Erfahrung.'),
            'acceptText' => $this->getSetting('cookie_accept_text', 'Akzeptieren'),
            'essentialText' => $this->getSetting('cookie_essential_text', 'Nur Essenzielle'),
            'policyUrl' => $this->sanitizeUrl($this->getSetting('cookie_policy_url', '/datenschutz')),
            'preferencesUrl' => SITE_URL . '/cookie-einstellungen',
            'categories' => $consentConfig['categories'],
            'sections' => $consentConfig['sections'],
        ];

        echo '<link rel="stylesheet" href="' . htmlspecialchars($assetsBaseUrl . '/cookieconsent.css', ENT_QUOTES, 'UTF-8') . '?v=20260307a">' . "\n";
        echo '<script src="' . htmlspecialchars($assetsBaseUrl . '/cookieconsent.umd.js', ENT_QUOTES, 'UTF-8') . '?v=20260307a" defer></script>' . "\n";
        echo '<script>window.CMS_COOKIECONSENT_CONFIG=' . json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';</script>' . "\n";
        echo '<script src="' . htmlspecialchars(SITE_URL . '/assets/js/cookieconsent-init.js', ENT_QUOTES, 'UTF-8') . '?v=20260307a" defer></script>' . "\n";
    }

    private function isAdminRequest(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = (string)(strtok((string)$uri, '?') ?: '/');

        return $path === '/admin' || str_starts_with($path, '/admin/');
    }

    /**
     * @return array<string, mixed>
     */
    public function getPublicConsentPage(): array
    {
        $overview = $this->getPublicConsentOverview();

        return [
            'id' => 0,
            'title' => 'Cookie-Einstellungen & Einwilligung',
            'slug' => 'cookie-einstellungen',
            'content' => $this->buildPublicConsentPageContent($overview),
            'updated_at' => (string)($overview['updated_at'] ?? ''),
        ];
    }

    /**
     * @return array{categories: array<string, array<string, mixed>>, sections: array<int, array<string, string>>}
     */
    private function buildConsentConfiguration(): array
    {
        $categories = [];
        $sections = [
            [
                'title' => 'Essenzielle Cookies',
                'description' => 'Diese Cookies und Services sind technisch notwendig und immer aktiv.',
            ],
        ];

        try {
            $categoryRows = $this->db->get_results(
                "SELECT slug, name, description, is_required, is_active
                 FROM {$this->prefix}cookie_categories
                 WHERE is_active = 1 OR is_required = 1
                 ORDER BY sort_order ASC, name ASC"
            ) ?: [];

            $serviceRows = $this->db->get_results(
                "SELECT slug, name, category_slug, is_essential, is_active
                 FROM {$this->prefix}cookie_services
                 WHERE is_active = 1 OR is_essential = 1
                 ORDER BY is_essential DESC, provider ASC, name ASC"
            ) ?: [];

            foreach ($categoryRows as $row) {
                $slug = (string)$row->slug;
                $isRequired = (int)($row->is_required ?? 0) === 1 || $slug === 'necessary';
                $categories[$slug] = [
                    'enabled' => $isRequired,
                    'readOnly' => $isRequired,
                    'services' => [],
                ];

                if (!$isRequired) {
                    $sections[] = [
                        'title' => (string)$row->name,
                        'description' => (string)($row->description ?: 'Einwilligung für diese Kategorie verwalten.'),
                        'linkedCategory' => $slug,
                    ];
                }
            }

            foreach ($serviceRows as $service) {
                $categorySlug = (string)($service->category_slug ?? 'necessary');
                $isEssential = (int)($service->is_essential ?? 0) === 1;
                if ($isEssential) {
                    $categorySlug = 'necessary';
                }

                if (!isset($categories[$categorySlug])) {
                    $categories[$categorySlug] = [
                        'enabled' => $isEssential,
                        'readOnly' => $isEssential,
                        'services' => [],
                    ];
                }

                $categories[$categorySlug]['services'][(string)$service->slug] = [
                    'label' => (string)$service->name,
                ];
            }
        } catch (\Throwable) {
            $categories = [];
        }

        if ($categories === []) {
            $categories = [
                'necessary' => ['enabled' => true, 'readOnly' => true, 'services' => []],
                'analytics' => ['enabled' => false, 'readOnly' => false, 'services' => []],
                'marketing' => ['enabled' => false, 'readOnly' => false, 'services' => []],
            ];
            $sections[] = [
                'title' => 'Analytics',
                'description' => 'Hilft uns zu verstehen, wie Besucher die Website nutzen.',
                'linkedCategory' => 'analytics',
            ];
            $sections[] = [
                'title' => 'Marketing',
                'description' => 'Wird für personalisierte Inhalte und Kampagnen verwendet.',
                'linkedCategory' => 'marketing',
            ];
        }

        return ['categories' => $categories, 'sections' => $sections];
    }

    /**
     * @return array<string, mixed>
     */
    private function getPublicConsentOverview(): array
    {
        $categories = [];
        $servicesByCategory = [];

        try {
            $categoryRows = $this->db->get_results(
                "SELECT slug, name, description, is_required, is_active
                 FROM {$this->prefix}cookie_categories
                 WHERE is_active = 1 OR is_required = 1
                 ORDER BY sort_order ASC, name ASC"
            ) ?: [];

            $serviceRows = $this->db->get_results(
                "SELECT slug, name, provider, category_slug, description, cookie_names, is_essential, is_active
                 FROM {$this->prefix}cookie_services
                 WHERE is_active = 1 OR is_essential = 1
                 ORDER BY is_essential DESC, provider ASC, name ASC"
            ) ?: [];

            foreach ($categoryRows as $row) {
                $slug = (string)$row->slug;
                $categories[$slug] = [
                    'slug' => $slug,
                    'name' => (string)$row->name,
                    'description' => (string)($row->description ?? ''),
                    'required' => (int)($row->is_required ?? 0) === 1 || $slug === 'necessary',
                    'services' => [],
                ];
            }

            foreach ($serviceRows as $service) {
                $categorySlug = (string)($service->category_slug ?? 'necessary');
                $isEssential = (int)($service->is_essential ?? 0) === 1;
                if ($isEssential) {
                    $categorySlug = 'necessary';
                }

                if (!isset($categories[$categorySlug])) {
                    $categories[$categorySlug] = [
                        'slug' => $categorySlug,
                        'name' => ucfirst(str_replace('_', ' ', $categorySlug)),
                        'description' => '',
                        'required' => $isEssential,
                        'services' => [],
                    ];
                }

                $servicesByCategory[$categorySlug][] = [
                    'slug' => (string)($service->slug ?? ''),
                    'name' => (string)($service->name ?? ''),
                    'provider' => (string)($service->provider ?? ''),
                    'description' => (string)($service->description ?? ''),
                    'cookie_names' => (string)($service->cookie_names ?? ''),
                    'is_essential' => $isEssential,
                ];
            }
        } catch (
            \Throwable
        ) {
            $categories = [];
            $servicesByCategory = [];
        }

        foreach ($categories as $slug => $category) {
            $categories[$slug]['services'] = $servicesByCategory[$slug] ?? [];
        }

        $matomoUrl = $this->sanitizeOptionalUrl($this->getSetting('cookie_matomo_self_hosted_url', ''));
        $matomoInfo = $this->buildMatomoTransparencyInfo($categories, $matomoUrl);

        return [
            'enabled' => $this->isEnabled(),
            'policy_url' => $this->sanitizeUrl($this->getSetting('cookie_policy_url', '/datenschutz')),
            'preferences_url' => SITE_URL . '/cookie-einstellungen',
            'updated_at' => $this->getSetting('cookie_scan_last_run', ''),
            'categories' => array_values($categories),
            'service_count' => array_reduce($categories, static fn(int $carry, array $category): int => $carry + count($category['services'] ?? []), 0),
            'matomo' => $matomoInfo,
        ];
    }

    /**
     * @param array<string, mixed> $overview
     */
    private function buildPublicConsentPageContent(array $overview): string
    {
        $enabledLabel = !empty($overview['enabled']) ? 'Aktiv verwaltbar' : 'Der Consent-Banner ist derzeit deaktiviert';
        $categories = (array)($overview['categories'] ?? []);
        $serviceCount = (int)($overview['service_count'] ?? 0);
        $policyUrl = (string)($overview['policy_url'] ?? '/datenschutz');
        $matomo = (array)($overview['matomo'] ?? []);
        $updatedAt = $this->formatOverviewTimestamp((string)($overview['updated_at'] ?? ''));

        $html = $this->buildPublicConsentPageStyles();
        $html .= '<section class="cms-consent-page" data-cms-consent-page data-cms-consent-state="loading">';
        $html .= '<div class="cms-consent-hero">';
        $html .= '<div class="cms-consent-hero__content">';
        $html .= '<span class="cms-consent-kicker">Datenschutz &amp; Transparenz</span>';
        $html .= '<p class="cms-consent-lead">Hier können Besucher ihre Cookie-Einwilligung transparent einsehen, jederzeit anpassen und alle aktuell konfigurierten Kategorien, Services und Matomo-Hinweise nachvollziehen.</p>';
        $html .= '</div>';
        $html .= '<div class="cms-consent-hero__stats">';
        $html .= '<div class="cms-consent-stat"><span class="cms-consent-stat__label">Kategorien</span><strong>' . count($categories) . '</strong></div>';
        $html .= '<div class="cms-consent-stat"><span class="cms-consent-stat__label">Services</span><strong>' . $serviceCount . '</strong></div>';
        $html .= '<div class="cms-consent-stat"><span class="cms-consent-stat__label">Zuletzt geprüft</span><strong>' . $this->escape($updatedAt !== '' ? $updatedAt : 'Derzeit nicht dokumentiert') . '</strong></div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="cms-consent-panel-grid">';
        $html .= '<section class="cms-consent-card cms-consent-summary">';
        $html .= '<div class="cms-consent-card__header">';
        $html .= '<h2>Aktueller Einwilligungsstatus</h2>';
        $html .= '<span class="cms-consent-status-pill" data-cms-consent-status-text>' . $this->escape($enabledLabel) . '</span>';
        $html .= '</div>';
        $html .= '<p data-cms-consent-status-detail>Die aktuelle Auswahl wird in deinem Browser gespeichert. Änderungen wirken sofort und können jederzeit erneut geöffnet werden.</p>';
        $html .= '<div class="cms-consent-meta-list">';
        $html .= '<div><span>Datenschutzerklärung</span><a href="' . $this->escapeAttr($policyUrl) . '">Jetzt öffnen</a></div>';
        $html .= '<div><span>Essenzielle Dienste</span><strong>Immer aktiv</strong></div>';
        $html .= '<div><span>Optionale Kategorien</span><strong>' . max(0, count($categories) - 1) . '</strong></div>';
        $html .= '</div>';
        $html .= '</section>';

        $html .= '<section class="cms-consent-card">';
        $html .= '<div class="cms-consent-card__header">';
        $html .= '<h2>Einwilligung verwalten</h2>';
        $html .= '<span class="cms-consent-hint">Jederzeit änderbar</span>';
        $html .= '</div>';
        $html .= '<p class="cms-consent-muted">Öffne die Präferenzen für eine feingranulare Auswahl oder bestätige mit einem Klick alle optionalen Kategorien.</p>';
        $html .= '<div class="cms-consent-actions">';
        $html .= '<button type="button" class="cms-consent-button cms-consent-button--primary" data-cms-consent-action="preferences">Auswahl anpassen</button>';
        $html .= '<button type="button" class="cms-consent-button cms-consent-button--secondary" data-cms-consent-action="accept-all">Alle akzeptieren</button>';
        $html .= '<button type="button" class="cms-consent-button cms-consent-button--ghost" data-cms-consent-action="essential">Nur essenzielle Dienste erlauben</button>';
        $html .= '</div>';
        $html .= '</section>';
        $html .= '</div>';

        $html .= '<section class="cms-consent-section">';
        $html .= '<div class="cms-consent-section__header">';
        $html .= '<h2>Verwendete Kategorien &amp; Services</h2>';
        $html .= '<p>Die folgende Übersicht zeigt pro Kategorie den aktuellen Status, die eingesetzten Dienste und bekannte Cookie-Namen.</p>';
        $html .= '</div>';
        $html .= '<div class="cms-consent-category-grid">';

        foreach ($categories as $category) {
            $slug = (string)($category['slug'] ?? '');
            $html .= '<article class="cms-consent-category" data-cms-consent-category="' . $this->escapeAttr($slug) . '">';
            $html .= '<div class="cms-consent-category__header">';
            $html .= '<div>';
            $html .= '<h3>' . $this->escape((string)($category['name'] ?? 'Kategorie')) . '</h3>';
            $html .= '<p>' . $this->escape((string)($category['description'] ?? '')) . '</p>';
            $html .= '</div>';
            $html .= '<div class="cms-consent-category__badges">';
            $html .= '<span class="cms-consent-category__status" data-cms-consent-category-status="' . $this->escapeAttr($slug) . '">Noch nicht gewählt</span>';
            if (!empty($category['required'])) {
                $html .= '<span class="cms-consent-category__badge cms-consent-category__badge--required">Pflicht</span>';
            }
            $html .= '</div>';
            $html .= '</div>';

            $services = (array)($category['services'] ?? []);
            if ($services !== []) {
                $html .= '<ul class="cms-consent-service-list">';
                foreach ($services as $service) {
                    $html .= '<li class="cms-consent-service-item">';
                    $html .= '<div class="cms-consent-service-item__title">';
                    $html .= '<strong>' . $this->escape((string)($service['name'] ?? 'Service')) . '</strong>';
                    $provider = trim((string)($service['provider'] ?? ''));
                    if ($provider !== '') {
                        $html .= '<span>' . $this->escape($provider) . '</span>';
                    }
                    $html .= '</div>';
                    $description = trim((string)($service['description'] ?? ''));
                    if ($description !== '') {
                        $html .= '<p>' . $this->escape($description) . '</p>';
                    }
                    $cookieNames = $this->formatCookieNames((string)($service['cookie_names'] ?? ''));
                    if ($cookieNames !== '') {
                        $html .= '<div class="cms-consent-service-item__meta"><span>Cookies</span><small>' . $this->escape($cookieNames) . '</small></div>';
                    }
                    if (!empty($service['is_essential'])) {
                        $html .= '<div class="cms-consent-service-item__meta"><span>Status</span><small>Essenzieller Dienst</small></div>';
                    }
                    $html .= '</li>';
                }
                $html .= '</ul>';
            } else {
                $html .= '<p class="cms-consent-empty">Für diese Kategorie sind derzeit keine Einzelservices hinterlegt.</p>';
            }

            $html .= '</article>';
        }

        $html .= '</div>';
        $html .= '</section>';

        if (!empty($matomo['enabled'])) {
            $html .= '<section class="cms-consent-section">';
            $html .= '<div class="cms-consent-card cms-consent-card--accent">';
            $html .= '<div class="cms-consent-card__header">';
            $html .= '<h2>Matomo Self-Hosted &amp; DSGVO-Hinweise</h2>';
            $html .= '<span class="cms-consent-hint">Transparenzblock</span>';
            $html .= '</div>';
            $html .= '<p class="cms-consent-muted">Für diese Website ist eine selbst gehostete Matomo-Konfiguration dokumentiert. Dadurch bleibt die Analyse-Umgebung unter eigener Kontrolle und kann datenschutzfreundlich betrieben werden.</p>';
            $html .= '<div class="cms-consent-info-grid">';
            if (!empty($matomo['url'])) {
                $html .= '<div class="cms-consent-info-item"><span>Matomo-URL</span><strong><a href="' . $this->escapeAttr((string)$matomo['url']) . '" target="_blank" rel="noopener noreferrer">' . $this->escape((string)$matomo['url']) . '</a></strong></div>';
            }
            if (!empty($matomo['site_id'])) {
                $html .= '<div class="cms-consent-info-item"><span>Site-ID</span><strong>' . $this->escape((string)$matomo['site_id']) . '</strong></div>';
            }
            $html .= '<div class="cms-consent-info-item"><span>Hosting</span><strong>' . $this->escape((string)($matomo['hosting_region'] ?? 'Deutschland / EU')) . '</strong></div>';
            $html .= '<div class="cms-consent-info-item"><span>IP-Anonymisierung</span><strong>' . (!empty($matomo['ip_anonymization']) ? 'Aktiv' : 'Nicht angegeben') . '</strong></div>';
            $html .= '<div class="cms-consent-info-item"><span>Browser-Do-Not-Track</span><strong>' . (!empty($matomo['respect_dnt']) ? 'Wird respektiert' : 'Nicht dokumentiert') . '</strong></div>';
            $html .= '<div class="cms-consent-info-item"><span>Matomo-Cookies</span><strong>' . (!empty($matomo['disable_cookies']) ? 'Deaktiviert / cookielos' : 'Aktiv bzw. nicht dokumentiert') . '</strong></div>';
            $html .= '<div class="cms-consent-info-item"><span>Log-Löschung / Aufbewahrung</span><strong>' . (int)($matomo['log_retention_days'] ?? 180) . ' Tage</strong></div>';
            $html .= '<div class="cms-consent-info-item"><span>Bewertung</span><strong>' . (!empty($matomo['is_essential']) ? 'Als essenzieller/self-hosted Dienst dokumentiert' : 'Als Analyse-Dienst dokumentiert') . '</strong></div>';
            $html .= '</div>';
            $html .= '<p class="cms-consent-muted">DSGVO-Konformität hängt immer von der tatsächlichen technischen und organisatorischen Umsetzung ab – insbesondere von IP-Anonymisierung, begrenzter Speicherdauer, Hosting in der EU bzw. ohne Drittlandtransfer und einer sauberen Dokumentation in der Datenschutzerklärung.</p>';

            $note = trim((string)($matomo['note'] ?? ''));
            if ($note !== '') {
                $html .= '<div class="cms-consent-note">' . nl2br($this->escape($note)) . '</div>';
            }
            $html .= '</div>';
            $html .= '</section>';
        }

        $html .= '</section>';

        return $html;
    }

    /**
     * @param array<int, array<string, mixed>> $categories
     * @return array<string, mixed>
     */
    private function buildMatomoTransparencyInfo(array $categories, string $matomoUrl): array
    {
        $matomoService = null;

        foreach ($categories as $category) {
            foreach ((array)($category['services'] ?? []) as $service) {
                if (($service['slug'] ?? '') === 'matomo') {
                    $matomoService = $service;
                    break 2;
                }
            }
        }

        $provider = strtolower((string)($matomoService['provider'] ?? ''));
        $name = strtolower((string)($matomoService['name'] ?? ''));
        $siteId = trim($this->getSetting('cookie_matomo_site_id', $this->getSetting('seo_analytics_matomo_site_id', '1')));
        $hostingRegion = trim($this->getSetting('cookie_matomo_hosting_region', 'Deutschland / EU'));
        $note = $this->getSetting('cookie_matomo_dsgvo_note', '');
        $respectDnt = $this->getSetting('cookie_matomo_respect_dnt', $this->getSetting('seo_analytics_respect_dnt', '0')) === '1';
        $disableCookies = $this->getSetting('cookie_matomo_disable_cookies', $this->getSetting('seo_analytics_anonymize_ip', '0')) === '1';
        $ipAnonymization = $this->getSetting('cookie_matomo_ip_anonymization', $this->getSetting('seo_analytics_anonymize_ip', '1')) === '1';
        $logRetentionDays = max(1, (int)$this->getSetting('cookie_matomo_log_retention_days', '180'));
        $isSelfHosted = $matomoUrl !== ''
            || (bool)($matomoService['is_essential'] ?? false)
            || str_contains($provider, 'self-hosted')
            || str_contains($name, 'self-hosted');

        $hasTransparencySettings = $matomoUrl !== ''
            || $siteId !== ''
            || $hostingRegion !== ''
            || trim($note) !== ''
            || $respectDnt
            || $disableCookies
            || $ipAnonymization
            || $logRetentionDays > 0;

        if (!$isSelfHosted && !$hasTransparencySettings) {
            return ['enabled' => false];
        }

        return [
            'enabled' => true,
            'url' => $matomoUrl,
            'hosting_region' => $hostingRegion,
            'site_id' => $siteId,
            'ip_anonymization' => $ipAnonymization,
            'respect_dnt' => $respectDnt,
            'disable_cookies' => $disableCookies,
            'log_retention_days' => $logRetentionDays,
            'note' => $note,
            'is_essential' => (bool)($matomoService['is_essential'] ?? false),
        ];
    }

    private function buildPublicConsentPageStyles(): string
    {
        return '<style>'
            . '.cms-consent-page{--cms-consent-border:#e5e7eb;--cms-consent-text:#0f172a;--cms-consent-muted:#64748b;--cms-consent-bg:#ffffff;--cms-consent-bg-soft:#f8fafc;--cms-consent-accent:#2563eb;display:grid;gap:2rem;margin:0 0 2.5rem;color:var(--cms-consent-text)}'
            . '.cms-consent-page *{box-sizing:border-box}'
            . '.cms-consent-hero{display:grid;gap:1.25rem;padding:1.5rem;border:1px solid var(--cms-consent-border);border-radius:24px;background:linear-gradient(135deg,#eff6ff 0%,#ffffff 50%,#f8fafc 100%);box-shadow:0 20px 45px -34px rgba(15,23,42,.45)}'
            . '.cms-consent-kicker{display:inline-flex;align-items:center;gap:.5rem;padding:.35rem .75rem;border-radius:999px;background:rgba(37,99,235,.1);color:var(--cms-consent-accent);font-size:.8rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase}'
            . '.cms-consent-lead{margin:.5rem 0 0;font-size:1.05rem;line-height:1.7;color:#1e293b}'
            . '.cms-consent-hero__stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem}'
            . '.cms-consent-stat{padding:1rem 1.1rem;border:1px solid rgba(148,163,184,.22);border-radius:18px;background:rgba(255,255,255,.82);backdrop-filter:blur(8px)}'
            . '.cms-consent-stat__label{display:block;margin-bottom:.45rem;color:var(--cms-consent-muted);font-size:.82rem;text-transform:uppercase;letter-spacing:.04em}'
            . '.cms-consent-stat strong{font-size:1rem;line-height:1.5}'
            . '.cms-consent-panel-grid,.cms-consent-category-grid{display:grid;gap:1.5rem}'
            . '.cms-consent-panel-grid{grid-template-columns:repeat(auto-fit,minmax(280px,1fr))}'
            . '.cms-consent-card,.cms-consent-category{padding:1.5rem;border:1px solid var(--cms-consent-border);border-radius:24px;background:var(--cms-consent-bg);box-shadow:0 18px 40px -34px rgba(15,23,42,.32)}'
            . '.cms-consent-card--accent{background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%)}'
            . '.cms-consent-card__header,.cms-consent-category__header,.cms-consent-section__header{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap}'
            . '.cms-consent-card__header h2,.cms-consent-section__header h2,.cms-consent-category__header h3{margin:0;color:var(--cms-consent-text)}'
            . '.cms-consent-section{display:grid;gap:1.25rem}'
            . '.cms-consent-section__header p,.cms-consent-category__header p,.cms-consent-muted,.cms-consent-empty{margin:.55rem 0 0;color:var(--cms-consent-muted);line-height:1.7}'
            . '.cms-consent-status-pill,.cms-consent-category__status,.cms-consent-category__badge,.cms-consent-hint{display:inline-flex;align-items:center;justify-content:center;padding:.45rem .8rem;border-radius:999px;font-size:.82rem;font-weight:700;background:#eff6ff;color:#1d4ed8}'
            . '.cms-consent-hint{background:#f1f5f9;color:#334155}'
            . '.cms-consent-meta-list{display:grid;gap:.85rem;margin-top:1.15rem}'
            . '.cms-consent-meta-list div,.cms-consent-info-item,.cms-consent-service-item__meta{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap}'
            . '.cms-consent-meta-list span,.cms-consent-info-item span,.cms-consent-service-item__meta span{color:var(--cms-consent-muted);font-size:.92rem}'
            . '.cms-consent-meta-list a{font-weight:700;text-decoration:none}'
            . '.cms-consent-actions{display:flex;flex-wrap:wrap;gap:.85rem;margin-top:1rem}'
            . '.cms-consent-button{appearance:none;border:1px solid transparent;border-radius:999px;padding:.9rem 1.2rem;font-weight:700;cursor:pointer;transition:all .2s ease}'
            . '.cms-consent-button--primary{background:var(--cms-consent-accent);color:#fff}'
            . '.cms-consent-button--secondary{background:#0f172a;color:#fff}'
            . '.cms-consent-button--ghost{background:#fff;border-color:var(--cms-consent-border);color:#0f172a}'
            . '.cms-consent-button:hover{transform:translateY(-1px);box-shadow:0 10px 25px -18px rgba(15,23,42,.75)}'
            . '.cms-consent-category{display:grid;gap:1rem;border-left:4px solid #cbd5e1}'
            . '.cms-consent-category[data-cms-consent-category-state="accepted"]{border-left-color:#16a34a;background:linear-gradient(180deg,#ffffff 0%,#f0fdf4 100%)}'
            . '.cms-consent-category[data-cms-consent-category-state="rejected"]{border-left-color:#dc2626;background:linear-gradient(180deg,#ffffff 0%,#fef2f2 100%)}'
            . '.cms-consent-category[data-cms-consent-category-state="always"]{border-left-color:#2563eb;background:linear-gradient(180deg,#ffffff 0%,#eff6ff 100%)}'
            . '.cms-consent-category__badges{display:flex;gap:.6rem;flex-wrap:wrap;align-items:center}'
            . '.cms-consent-category__badge--required{background:#dbeafe;color:#1e40af}'
            . '.cms-consent-service-list{display:grid;gap:.85rem;padding:0;margin:0;list-style:none}'
            . '.cms-consent-service-item{padding:1rem 1rem 1.05rem;border-radius:18px;background:var(--cms-consent-bg-soft);border:1px solid rgba(148,163,184,.2)}'
            . '.cms-consent-service-item__title{display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:flex-start;margin-bottom:.55rem}'
            . '.cms-consent-service-item__title span{color:var(--cms-consent-muted);font-size:.92rem}'
            . '.cms-consent-service-item p{margin:.35rem 0 .75rem;color:#334155;line-height:1.65}'
            . '.cms-consent-info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;margin-top:1rem}'
            . '.cms-consent-info-item{padding:1rem 1.05rem;border-radius:18px;background:rgba(255,255,255,.8);border:1px solid rgba(148,163,184,.22)}'
            . '.cms-consent-info-item strong,.cms-consent-info-item a{font-size:.98rem;line-height:1.5;word-break:break-word}'
            . '.cms-consent-note{margin-top:1rem;padding:1rem 1.1rem;border-radius:18px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;line-height:1.7}'
            . '.cms-consent-page[data-cms-consent-state="all"] .cms-consent-status-pill{background:#dcfce7;color:#166534}'
            . '.cms-consent-page[data-cms-consent-state="necessary"] .cms-consent-status-pill{background:#e0f2fe;color:#075985}'
            . '.cms-consent-page[data-cms-consent-state="custom"] .cms-consent-status-pill{background:#ede9fe;color:#5b21b6}'
            . '.cms-consent-page[data-cms-consent-state="unavailable"] .cms-consent-status-pill{background:#f1f5f9;color:#475569}'
            . '@media (max-width:768px){.cms-consent-page{gap:1.5rem}.cms-consent-hero,.cms-consent-card,.cms-consent-category{padding:1.2rem;border-radius:20px}.cms-consent-actions{flex-direction:column}.cms-consent-button{width:100%}}'
            . '</style>';
    }

    private function formatOverviewTimestamp(string $timestamp): string
    {
        $timestamp = trim($timestamp);
        if ($timestamp === '') {
            return '';
        }

        $date = date_create($timestamp);
        if ($date === false) {
            return $timestamp;
        }

        return $date->format('d.m.Y H:i');
    }

    private function getSetting(string $key, string $default = ''): string
    {
        try {
            $stmt = $this->db->prepare("SELECT option_value FROM {$this->prefix}settings WHERE option_name = ? LIMIT 1");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return $val !== false ? (string)$val : $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    private function sanitizePosition(string $position): string
    {
        return in_array($position, ['bottom', 'center'], true) ? $position : 'bottom';
    }

    private function sanitizeHexColor(string $hex): string
    {
        return preg_match('/^#[a-f0-9]{6}$/i', $hex) === 1 ? $hex : '#3b82f6';
    }

    private function sanitizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '/datenschutz';
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        return '/datenschutz';
    }

    private function sanitizeOptionalUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (!preg_match('~^[a-z][a-z0-9+.-]*://~i', $url) && preg_match('~^[a-z0-9.-]+(?::\d+)?(?:/.*)?$~i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        return '';
    }

    private function formatCookieNames(string $cookieNames): string
    {
        $names = array_values(array_filter(array_map('trim', explode(',', $cookieNames))));
        return implode(', ', $names);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function escapeAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
