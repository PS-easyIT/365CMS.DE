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

        $html = '<section class="cms-consent-page" data-cms-consent-page>';
        $html .= '<p>Hier können Besucher ihre Cookie-Einwilligung transparent einsehen, jederzeit anpassen und alle aktuell konfigurierten Dienste nachvollziehen.</p>';

        $html .= '<div class="cms-consent-summary">';
        $html .= '<p><strong>Status:</strong> <span data-cms-consent-status-text>' . $this->escape($enabledLabel) . '</span></p>';
        $html .= '<p><strong>Kategorien:</strong> ' . count($categories) . ' · <strong>Services:</strong> ' . $serviceCount . '</p>';
        $html .= '<p><a href="' . $this->escapeAttr($policyUrl) . '">Datenschutzerklärung öffnen</a></p>';
        $html .= '</div>';

        $html .= '<h2>Einwilligung verwalten</h2>';
        $html .= '<p data-cms-consent-status-detail>Die aktuelle Auswahl wird in deinem Browser gespeichert. Änderungen wirken sofort.</p>';
        $html .= '<div class="cms-consent-actions">';
        $html .= '<button type="button" data-cms-consent-action="preferences">Auswahl anpassen</button> ';
        $html .= '<button type="button" data-cms-consent-action="accept-all">Alle akzeptieren</button> ';
        $html .= '<button type="button" data-cms-consent-action="essential">Nur essenzielle Dienste erlauben</button>';
        $html .= '</div>';

        $html .= '<h2>Verwendete Kategorien & Services</h2>';

        foreach ($categories as $category) {
            $slug = (string)($category['slug'] ?? '');
            $html .= '<article class="cms-consent-category" data-cms-consent-category="' . $this->escapeAttr($slug) . '">';
            $html .= '<h3>' . $this->escape((string)($category['name'] ?? 'Kategorie')) . '</h3>';
            $html .= '<p>' . $this->escape((string)($category['description'] ?? '')) . '</p>';
            $html .= '<p><strong>Status:</strong> <span data-cms-consent-category-status="' . $this->escapeAttr($slug) . '">Noch nicht gewählt</span>';
            if (!empty($category['required'])) {
                $html .= ' · <strong>Pflichtkategorie</strong>';
            }
            $html .= '</p>';

            $services = (array)($category['services'] ?? []);
            if ($services !== []) {
                $html .= '<ul>';
                foreach ($services as $service) {
                    $html .= '<li>';
                    $html .= '<strong>' . $this->escape((string)($service['name'] ?? 'Service')) . '</strong>';
                    $provider = trim((string)($service['provider'] ?? ''));
                    if ($provider !== '') {
                        $html .= ' (' . $this->escape($provider) . ')';
                    }
                    $description = trim((string)($service['description'] ?? ''));
                    if ($description !== '') {
                        $html .= '<br>' . $this->escape($description);
                    }
                    $cookieNames = $this->formatCookieNames((string)($service['cookie_names'] ?? ''));
                    if ($cookieNames !== '') {
                        $html .= '<br><small>Cookies: ' . $this->escape($cookieNames) . '</small>';
                    }
                    if (!empty($service['is_essential'])) {
                        $html .= '<br><small>Essenzieller Dienst</small>';
                    }
                    $html .= '</li>';
                }
                $html .= '</ul>';
            } else {
                $html .= '<p>Für diese Kategorie sind derzeit keine Einzelservices hinterlegt.</p>';
            }

            $html .= '</article>';
        }

        if (!empty($matomo['enabled'])) {
            $html .= '<h2>Matomo Self-Hosted & DSGVO-Hinweise</h2>';
            $html .= '<p>Für diese Website ist eine selbst gehostete Matomo-Konfiguration dokumentiert. Dadurch bleibt die Analyse-Umgebung unter eigener Kontrolle und kann datenschutzfreundlich betrieben werden.</p>';
            $html .= '<ul>';
            if (!empty($matomo['url'])) {
                $html .= '<li><strong>Matomo-URL:</strong> <a href="' . $this->escapeAttr((string)$matomo['url']) . '" target="_blank" rel="noopener noreferrer">' . $this->escape((string)$matomo['url']) . '</a></li>';
            }
            $html .= '<li><strong>Hosting:</strong> ' . $this->escape((string)($matomo['hosting_region'] ?? 'Deutschland / EU')) . '</li>';
            $html .= '<li><strong>IP-Anonymisierung:</strong> ' . (!empty($matomo['ip_anonymization']) ? 'Aktiv' : 'Nicht angegeben') . '</li>';
            $html .= '<li><strong>Log-Löschung / Aufbewahrung:</strong> ' . (int)($matomo['log_retention_days'] ?? 180) . ' Tage</li>';
            $html .= '<li><strong>Bewertung:</strong> ' . (!empty($matomo['is_essential']) ? 'Als essenzieller/self-hosted Dienst dokumentiert.' : 'Als Analyse-Dienst dokumentiert.') . '</li>';
            $html .= '</ul>';
            $html .= '<p>DSGVO-Konformität hängt immer von der tatsächlichen technischen und organisatorischen Umsetzung ab – insbesondere von IP-Anonymisierung, begrenzter Speicherdauer, Hosting in der EU bzw. ohne Drittlandtransfer und einer sauberen Dokumentation in der Datenschutzerklärung.</p>';

            $note = trim((string)($matomo['note'] ?? ''));
            if ($note !== '') {
                $html .= '<div>' . nl2br($this->escape($note)) . '</div>';
            }
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
        $isSelfHosted = $matomoUrl !== ''
            || (bool)($matomoService['is_essential'] ?? false)
            || str_contains($provider, 'self-hosted')
            || str_contains($name, 'self-hosted');

        if (!$isSelfHosted) {
            return ['enabled' => false];
        }

        return [
            'enabled' => true,
            'url' => $matomoUrl,
            'hosting_region' => $this->getSetting('cookie_matomo_hosting_region', 'Deutschland / EU'),
            'ip_anonymization' => $this->getSetting('cookie_matomo_ip_anonymization', '1') === '1',
            'log_retention_days' => max(1, (int)$this->getSetting('cookie_matomo_log_retention_days', '180')),
            'note' => $this->getSetting('cookie_matomo_dsgvo_note', ''),
            'is_essential' => (bool)($matomoService['is_essential'] ?? false),
        ];
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
