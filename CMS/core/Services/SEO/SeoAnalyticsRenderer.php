<?php
declare(strict_types=1);

namespace CMS\Services\SEO;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Consent-gesteuerte, CSP-konforme Analytics-Einbindung.
 *
 * Statt Inline-Snippets wird eine JSON-Konfiguration plus der Loader
 * `assets/js/cms-analytics.js` ausgegeben. Der Loader lädt einen Anbieter erst,
 * wenn die zugehörige Cookie-Kategorie (analytics/marketing) eingewilligt ist.
 * Die CSP wird nur um die Hosts der tatsächlich konfigurierten Anbieter erweitert.
 * Freier Custom-Code (analytics_custom_head/body, Matomo-Code) wird unter der
 * strikten CSP bewusst nicht ausgeführt.
 */
final class SeoAnalyticsRenderer
{
    private const array GOOGLE_CONNECT = ['https://*.google-analytics.com', 'https://*.analytics.google.com', 'https://www.googletagmanager.com'];
    private const array GOOGLE_IMG = ['https://*.google-analytics.com', 'https://www.googletagmanager.com'];

    public function __construct(private readonly SeoSettingsStore $settings)
    {
    }

    /**
     * Konfiguration aller aktiven Anbieter (IDs validiert).
     *
     * @return array{respectDnt: bool, providers: array<string, array<string, mixed>>}
     */
    public function getClientConfig(): array
    {
        $providers = [];
        $anonymizeIp = $this->settings->getSetting('analytics_anonymize_ip') === '1';

        $matomoUrl = $this->sanitizeMatomoUrl($this->settings->getSetting('analytics_matomo_url'));
        $matomoSiteId = $this->sanitizeId($this->settings->getSetting('analytics_matomo_site_id'), '/^\d{1,10}$/') ?: '1';
        if ($matomoUrl !== '' && $this->isProviderEnabled('matomo')) {
            $providers['matomo'] = ['category' => 'analytics', 'url' => $matomoUrl, 'siteId' => $matomoSiteId, 'disableCookies' => $anonymizeIp];
        }

        $ga4Id = $this->sanitizeId($this->settings->getSetting('analytics_ga4_id'), '/^G-[A-Z0-9]{4,20}$/');
        if ($ga4Id !== '' && $this->isProviderEnabled('ga4')) {
            $providers['ga4'] = ['category' => 'analytics', 'id' => $ga4Id, 'anonymizeIp' => $anonymizeIp];
        }

        $gtmId = $this->sanitizeId($this->settings->getSetting('analytics_gtm_id'), '/^GTM-[A-Z0-9]{4,12}$/');
        if ($gtmId !== '' && $this->isProviderEnabled('gtm')) {
            // Container können beliebige Marketing-Tags laden → strengere Kategorie.
            $providers['gtm'] = ['category' => 'marketing', 'id' => $gtmId];
        }

        $pixelId = $this->sanitizeId($this->settings->getSetting('analytics_fb_pixel_id'), '/^\d{5,20}$/');
        if ($pixelId !== '' && $this->isProviderEnabled('fb_pixel')) {
            $providers['metaPixel'] = ['category' => 'marketing', 'id' => $pixelId];
        }

        return [
            'respectDnt' => $this->settings->getSetting('analytics_respect_dnt') === '1',
            'providers' => $providers,
        ];
    }

    /**
     * CSP-Quellen der aktiven Anbieter.
     *
     * @return array<string, list<string>>
     */
    public function getCspSources(): array
    {
        if ($this->shouldExcludeAdmins()) {
            return [];
        }

        $sources = ['script-src' => [], 'connect-src' => [], 'img-src' => []];
        foreach ($this->getClientConfig()['providers'] as $key => $provider) {
            switch ($key) {
                case 'matomo':
                    $origin = $this->originOf((string) $provider['url']);
                    if ($origin !== '') {
                        $sources['script-src'][] = $origin;
                        $sources['connect-src'][] = $origin;
                        $sources['img-src'][] = $origin;
                    }
                    break;
                case 'ga4':
                case 'gtm':
                    $sources['script-src'][] = 'https://www.googletagmanager.com';
                    array_push($sources['connect-src'], ...self::GOOGLE_CONNECT);
                    array_push($sources['img-src'], ...self::GOOGLE_IMG);
                    break;
                case 'metaPixel':
                    $sources['script-src'][] = 'https://connect.facebook.net';
                    array_push($sources['connect-src'], 'https://www.facebook.com', 'https://connect.facebook.net');
                    $sources['img-src'][] = 'https://www.facebook.com';
                    break;
            }
        }

        return array_filter(array_map(static fn(array $list): array => array_values(array_unique($list)), $sources));
    }

    public function getAnalyticsHeadCode(): string
    {
        if ($this->shouldExcludeAdmins()) {
            return '';
        }

        $config = $this->getClientConfig();
        if ($config['providers'] === []) {
            return '';
        }

        $json = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        if (!is_string($json)) {
            return '';
        }

        $loader = function_exists('cms_asset_url')
            ? \cms_asset_url('js/cms-analytics.js')
            : rtrim((string) (defined('SITE_URL') ? SITE_URL : ''), '/') . '/assets/js/cms-analytics.js';

        return "\n<!-- Analytics (lädt erst nach Einwilligung) -->\n"
            . '<script type="application/json" id="cms-analytics-config">' . $json . '</script>' . "\n"
            . '<script src="' . htmlspecialchars($loader, ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n";
    }

    /**
     * Kein <noscript>-Tracking: ohne JavaScript lässt sich keine Einwilligung prüfen.
     */
    public function getAnalyticsBodyCode(): string
    {
        return '';
    }

    /**
     * Die aktuelle SEO-Oberfläche pflegt nur die IDs; alte `*_enabled`-Flags
     * aus der früheren Oberfläche deaktivieren einen Anbieter nur explizit mit '0'.
     */
    private function isProviderEnabled(string $key): bool
    {
        return $this->settings->getSetting('analytics_' . $key . '_enabled') !== '0';
    }

    private function sanitizeId(string $value, string $pattern): string
    {
        $value = strtoupper(trim($value));

        return preg_match($pattern, $value) === 1 ? $value : '';
    }

    private function sanitizeMatomoUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https'
            || preg_match('/[\'"<>\\\\\s?#]/', $url) === 1) {
            return '';
        }

        return rtrim($url, '/') . '/';
    }

    private function originOf(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $port = parse_url($url, PHP_URL_PORT);

        return $host === '' ? '' : 'https://' . $host . ($port !== null && $port !== false ? ':' . (int) $port : '');
    }

    private function shouldExcludeAdmins(): bool
    {
        return $this->settings->getSetting('analytics_exclude_admins') === '1'
            && isset($_SESSION['user_role'])
            && $_SESSION['user_role'] === 'admin';
    }
}
