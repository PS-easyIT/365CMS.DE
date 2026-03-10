<?php
declare(strict_types=1);

namespace CMS\Services\SEO;

if (!defined('ABSPATH')) {
    exit;
}

final class SeoAnalyticsRenderer
{
    public function __construct(private readonly SeoSettingsStore $settings)
    {
    }

    public function getAnalyticsHeadCode(): string
    {
        if ($this->shouldExcludeAdmins()) {
            return '';
        }

        $respectDnt = $this->settings->getSetting('analytics_respect_dnt') === '1';
        $anonymizeIp = $this->settings->getSetting('analytics_anonymize_ip') === '1';
        $output = '';

        if ($this->settings->getSetting('analytics_matomo_enabled') === '1') {
            $customCode = trim($this->settings->getSetting('analytics_matomo_code'));
            if ($customCode !== '') {
                $output .= "\n" . $customCode . "\n";
            } else {
                $mUrl = rtrim($this->settings->getSetting('analytics_matomo_url'), '/') . '/';
                $mSiteId = $this->settings->getSetting('analytics_matomo_site_id') ?: '1';
                if ($mUrl !== '/') {
                    $dntLine = $respectDnt ? "\n  if (navigator.doNotTrack == '1') { return; }" : '';
                    $anonLine = $anonymizeIp ? "\n  _paq.push(['setDoNotTrack', true]);\n  _paq.push(['disableCookies']);" : '';
                    $output .= "\n<!-- Matomo Analytics -->\n<script>\n  var _paq = window._paq = window._paq || [];" . $dntLine . $anonLine . "\n  _paq.push(['trackPageView']);\n  _paq.push(['enableLinkTracking']);\n  (function() {\n    var u=\"{$mUrl}\";\n    _paq.push(['setTrackerUrl', u+'matomo.php']);\n    _paq.push(['setSiteId', '{$mSiteId}']);\n    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];\n    g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);\n  })();\n</script>\n<!-- End Matomo Code -->\n";
                }
            }
        }

        if ($this->settings->getSetting('analytics_ga4_enabled') === '1') {
            $ga4Id = trim($this->settings->getSetting('analytics_ga4_id'));
            if ($ga4Id !== '') {
                $configOptions = $anonymizeIp ? "{ 'anonymize_ip': true }" : '{}';
                $dntBlock = $respectDnt ? "\n  if (navigator.doNotTrack === '1') { window['ga-disable-{$ga4Id}'] = true; }" : '';
                $output .= "\n<!-- Google Analytics 4 -->\n<script async src=\"https://www.googletagmanager.com/gtag/js?id={$ga4Id}\"></script>\n<script>{$dntBlock}\n  window.dataLayer = window.dataLayer || [];\n  function gtag(){dataLayer.push(arguments);}\n  gtag('js', new Date());\n  gtag('config', '{$ga4Id}', {$configOptions});\n</script>\n";
            }
        }

        if ($this->settings->getSetting('analytics_gtm_enabled') === '1') {
            $gtmId = trim($this->settings->getSetting('analytics_gtm_id'));
            if ($gtmId !== '') {
                $dntBlock = $respectDnt ? "\n  if (navigator.doNotTrack === '1') { return; }" : '';
                $output .= "\n<!-- Google Tag Manager -->\n<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':\nnew Date().getTime(),event:'gtm.js'});{$dntBlock}\nvar f=d.getElementsByTagName(s)[0],\nj=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=\n'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);\n})(window,document,'script','dataLayer','{$gtmId}');</script>\n<!-- End Google Tag Manager -->\n";
            }
        }

        if ($this->settings->getSetting('analytics_fb_pixel_enabled') === '1') {
            $pixelId = trim($this->settings->getSetting('analytics_fb_pixel_id'));
            if ($pixelId !== '') {
                $dntBlock = $respectDnt ? "\nif (navigator.doNotTrack === '1') { return; }" : '';
                $output .= "\n<!-- Meta Pixel Code -->\n<script>{$dntBlock}\n!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?\nn.callMethod.apply(n,arguments):n.queue.push(arguments)};\nif(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';\nn.queue=[];t=b.createElement(e);t.async=!0;\nt.src=v;s=b.getElementsByTagName(e)[0];\ns.parentNode.insertBefore(t,s)}(window,document,'script',\n'https://connect.facebook.net/en_US/fbevents.js');\nfbq('init', '{$pixelId}');\nfbq('track', 'PageView');\n</script>\n<noscript><img height=\"1\" width=\"1\" style=\"display:none\"\nsrc=\"https://www.facebook.com/tr?id={$pixelId}&ev=PageView&noscript=1\"/></noscript>\n<!-- End Meta Pixel Code -->\n";
            }
        }

        $customHead = trim($this->settings->getSetting('analytics_custom_head'));
        if ($customHead !== '') {
            $output .= "\n<!-- Custom Analytics Head Code -->\n" . $customHead . "\n";
        }

        return $output;
    }

    public function getAnalyticsBodyCode(): string
    {
        if ($this->shouldExcludeAdmins()) {
            return '';
        }

        $output = '';

        if ($this->settings->getSetting('analytics_gtm_enabled') === '1') {
            $gtmId = trim($this->settings->getSetting('analytics_gtm_id'));
            if ($gtmId !== '') {
                $output .= "\n<!-- Google Tag Manager (noscript) -->\n<noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id={$gtmId}\"\nheight=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>\n<!-- End Google Tag Manager (noscript) -->\n";
            }
        }

        $customBody = trim($this->settings->getSetting('analytics_custom_body'));
        if ($customBody !== '') {
            $output .= "\n<!-- Custom Analytics Body Code -->\n" . $customBody . "\n";
        }

        return $output;
    }

    private function shouldExcludeAdmins(): bool
    {
        return $this->settings->getSetting('analytics_exclude_admins') === '1'
            && isset($_SESSION['user_role'])
            && $_SESSION['user_role'] === 'admin';
    }
}
