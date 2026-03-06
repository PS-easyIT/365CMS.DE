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
        return $this->getSetting('cookie_consent_enabled', '0') === '1';
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

        $config = [
            'position' => $this->sanitizePosition($this->getSetting('cookie_banner_position', 'bottom')),
            'primaryColor' => $this->sanitizeHexColor($this->getSetting('cookie_primary_color', '#3b82f6')),
            'bannerText' => $this->getSetting('cookie_banner_text', 'Wir nutzen Cookies für eine optimale Website-Erfahrung.'),
            'acceptText' => $this->getSetting('cookie_accept_text', 'Akzeptieren'),
            'essentialText' => $this->getSetting('cookie_essential_text', 'Nur Essenzielle'),
            'policyUrl' => $this->sanitizeUrl($this->getSetting('cookie_policy_url', '/datenschutz')),
            'categories' => $this->resolveCategoriesFromServices(),
        ];

        echo '<link rel="stylesheet" href="' . htmlspecialchars($assetsBaseUrl . '/cookieconsent.css', ENT_QUOTES, 'UTF-8') . '?v=20260305a">' . "\n";
        echo '<script src="' . htmlspecialchars($assetsBaseUrl . '/cookieconsent.umd.js', ENT_QUOTES, 'UTF-8') . '?v=20260305a" defer></script>' . "\n";
        echo '<script>window.CMS_COOKIECONSENT_CONFIG=' . json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';</script>' . "\n";
        echo '<script src="' . htmlspecialchars(SITE_URL . '/assets/js/cookieconsent-init.js', ENT_QUOTES, 'UTF-8') . '?v=20260305a" defer></script>' . "\n";
    }

    /**
     * @return array{necessary: bool, analytics: bool, marketing: bool}
     */
    private function resolveCategoriesFromServices(): array
    {
        $activeServices = [];

        try {
            $raw = $this->getSetting('cookie_active_services', '[]');
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $activeServices = $decoded;
            }
        } catch (\Throwable) {
            $activeServices = [];
        }

        $serviceLibrary = [
            'google_analytics' => 'analytics',
            'google_gtm' => 'analytics',
            'matomo' => 'analytics',
            'hotjar' => 'analytics',
            'clarity' => 'analytics',
            'facebook_pixel' => 'marketing',
            'linkedin_insight' => 'marketing',
            'google_ads' => 'marketing',
            'twitter_pixel' => 'marketing',
            'pinterest' => 'marketing',
            'tiktok_pixel' => 'marketing',
            'snapchat' => 'marketing',
            'hubspot' => 'marketing',
            'mailchimp' => 'marketing',
            'salesforce' => 'marketing',
            'criteo' => 'marketing',
            'bing_ads' => 'marketing',
            'xing' => 'marketing',
        ];

        $hasAnalytics = false;
        $hasMarketing = false;

        foreach ($activeServices as $serviceId) {
            $category = $serviceLibrary[(string)$serviceId] ?? '';
            if ($category === 'analytics') {
                $hasAnalytics = true;
            }
            if ($category === 'marketing') {
                $hasMarketing = true;
            }
        }

        return [
            'necessary' => true,
            'analytics' => $hasAnalytics,
            'marketing' => $hasMarketing,
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
}
