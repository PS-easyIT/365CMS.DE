<?php
/**
 * Cookie Consent Manager Admin Page
 * 
 * @package CMSv2\Admin
 */

declare(strict_types=1);

// Load configuration
require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Security;
use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$auth = Auth::instance();
$security = Security::instance();
$db = Database::instance();

// Initialize settings
$message = '';
$messageType = '';

// Load Manual List
$manualListJson = $db->execute("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'cookie_manual_list'")->fetch();
$manualCookies = $manualListJson ? json_decode($manualListJson->option_value ?? '[]', true) : [];
if (!is_array($manualCookies)) $manualCookies = [];

// === SERVICE BIBLIOTHEK (vordefinierte Drittanbieter-Dienste) ===
$serviceLibrary = [
    // ESSENZIELL
    'cms_session'      => ['id'=>'cms_session',      'name'=>'CMS Session',                  'provider'=>'Eigentümer',              'category'=>'essential',  'description'=>'Notwendige Sitzungs-Cookies für die Funktion des CMS.',        'cookies'=>['PHPSESSID','cms_session'],                                                'privacy_url'=>''],
    'cloudflare'       => ['id'=>'cloudflare',        'name'=>'Cloudflare',                   'provider'=>'Cloudflare Inc.',         'category'=>'essential',  'description'=>'Sicherheits- und Performance-Dienst, DDoS-Schutz.',             'cookies'=>['__cflb','__cf_bm','cf_clearance'],                                        'privacy_url'=>'https://www.cloudflare.com/privacypolicy/'],
    'recaptcha'        => ['id'=>'recaptcha',          'name'=>'Google reCAPTCHA',             'provider'=>'Google LLC',              'category'=>'essential',  'description'=>'Schützt Formulare vor Spam und automatisierten Angriffen.',     'cookies'=>['_GRECAPTCHA'],                                                           'privacy_url'=>'https://policies.google.com/privacy'],
    'stripe'           => ['id'=>'stripe',             'name'=>'Stripe',                       'provider'=>'Stripe Inc.',             'category'=>'essential',  'description'=>'Zahlungsabwicklung und Betrugsprävention.',                      'cookies'=>['__stripe_mid','__stripe_sid'],                                            'privacy_url'=>'https://stripe.com/privacy'],
    'paypal'           => ['id'=>'paypal',             'name'=>'PayPal',                       'provider'=>'PayPal Holdings Inc.',    'category'=>'essential',  'description'=>'Zahlungsabwicklung via PayPal.',                                 'cookies'=>['cookie_check','nsid','ts'],                                               'privacy_url'=>'https://www.paypal.com/de/webapps/mpp/ua/privacy-full'],
    // FUNKTIONAL
    'google_maps'      => ['id'=>'google_maps',        'name'=>'Google Maps',                  'provider'=>'Google LLC',              'category'=>'functional', 'description'=>'Karteneinbindung für Standort- und Routenanzeigen.',            'cookies'=>['NID','CONSENT','1P_JAR'],                                                 'privacy_url'=>'https://policies.google.com/privacy'],
    'youtube'          => ['id'=>'youtube',            'name'=>'YouTube',                      'provider'=>'Google LLC',              'category'=>'functional', 'description'=>'Video-Einbindung über YouTube Embeds.',                          'cookies'=>['VISITOR_INFO1_LIVE','YSC','PREF','GPS'],                                  'privacy_url'=>'https://policies.google.com/privacy'],
    'vimeo'            => ['id'=>'vimeo',              'name'=>'Vimeo',                        'provider'=>'Vimeo LLC',               'category'=>'functional', 'description'=>'Video-Hosting und Einbindung.',                                  'cookies'=>['vuid','__utmz'],                                                          'privacy_url'=>'https://vimeo.com/privacy'],
    'intercom'         => ['id'=>'intercom',           'name'=>'Intercom',                     'provider'=>'Intercom Inc.',           'category'=>'functional', 'description'=>'Live-Chat und Kundensupport-Plattform.',                         'cookies'=>['intercom-id-*','intercom-session-*'],                                     'privacy_url'=>'https://www.intercom.com/legal/privacy'],
    'zendesk'          => ['id'=>'zendesk',            'name'=>'Zendesk',                      'provider'=>'Zendesk Inc.',            'category'=>'functional', 'description'=>'Support-Chat und Ticket-System.',                                'cookies'=>['__zlcmid','ZD-suid','ZD-buid'],                                           'privacy_url'=>'https://www.zendesk.com/company/agreements-and-terms/privacy-policy/'],
    // STATISTIK
    'google_analytics' => ['id'=>'google_analytics',   'name'=>'Google Analytics',             'provider'=>'Google LLC',              'category'=>'analytics',  'description'=>'Webanalyse zur Messung von Traffic und Nutzerverhalten.',       'cookies'=>['_ga','_gid','_gat','_ga_*'],                                              'privacy_url'=>'https://policies.google.com/privacy'],
    'google_gtm'       => ['id'=>'google_gtm',         'name'=>'Google Tag Manager',           'provider'=>'Google LLC',              'category'=>'analytics',  'description'=>'Tag-Management für Tracking-Skripte (kein eigenes Tracking).',  'cookies'=>['_dc_gtm_*'],                                                              'privacy_url'=>'https://policies.google.com/privacy'],
    'matomo'           => ['id'=>'matomo',             'name'=>'Matomo Analytics',             'provider'=>'Matomo.org',              'category'=>'analytics',  'description'=>'Datenschutzfreundliche Open-Source-Webanalyse.',                 'cookies'=>['_pk_id.*','_pk_ses.*','_pk_ref.*'],                                       'privacy_url'=>'https://matomo.org/privacy-policy/'],
    'hotjar'           => ['id'=>'hotjar',             'name'=>'Hotjar',                       'provider'=>'Hotjar Ltd.',             'category'=>'analytics',  'description'=>'Heatmaps, Session-Recordings und Nutzerumfragen.',               'cookies'=>['_hjSessionUser_*','_hjSession_*','_hjid','_hjAbsoluteSessionInProgress'], 'privacy_url'=>'https://www.hotjar.com/legal/policies/privacy/'],
    'clarity'          => ['id'=>'clarity',            'name'=>'Microsoft Clarity',            'provider'=>'Microsoft Corp.',         'category'=>'analytics',  'description'=>'Heatmaps und Session-Recordings von Microsoft.',                 'cookies'=>['_clck','_clsk','CLID','ANONCHK','MR'],                                   'privacy_url'=>'https://privacy.microsoft.com'],
    // MARKETING
    'facebook_pixel'   => ['id'=>'facebook_pixel',     'name'=>'Meta Pixel (Facebook)',        'provider'=>'Meta Platforms Inc.',     'category'=>'marketing',  'description'=>'Conversion-Tracking und Retargeting für Facebook & Instagram.',  'cookies'=>['_fbp','_fbc','datr','fr'],                                                'privacy_url'=>'https://www.facebook.com/privacy/policy/'],
    'linkedin_insight' => ['id'=>'linkedin_insight',   'name'=>'LinkedIn Insight Tag',         'provider'=>'LinkedIn Corp.',          'category'=>'marketing',  'description'=>'B2B-Conversion-Tracking und Retargeting über LinkedIn.',         'cookies'=>['li_gc','li_sugr','bcookie','UserMatchHistory','AnalyticsSyncHistory'],    'privacy_url'=>'https://www.linkedin.com/legal/privacy-policy'],
    'google_ads'       => ['id'=>'google_ads',         'name'=>'Google Ads / DoubleClick',     'provider'=>'Google LLC',              'category'=>'marketing',  'description'=>'Conversion-Tracking und Retargeting für Google Ads.',           'cookies'=>['IDE','DSID','__gads','__gpi','AID','TAID'],                               'privacy_url'=>'https://policies.google.com/privacy'],
    'twitter_pixel'    => ['id'=>'twitter_pixel',      'name'=>'X (Twitter) Pixel',            'provider'=>'X Corp.',                 'category'=>'marketing',  'description'=>'Conversion-Tracking für X/Twitter Ads.',                        'cookies'=>['_twitter_sess','personalization_id','guest_id'],                          'privacy_url'=>'https://twitter.com/privacy'],
    'pinterest'        => ['id'=>'pinterest',          'name'=>'Pinterest Tag',                'provider'=>'Pinterest Inc.',          'category'=>'marketing',  'description'=>'Conversion-Tracking für Pinterest Ads.',                         'cookies'=>['_pinterest_sess','_pinterest_ct_ua','_epik'],                             'privacy_url'=>'https://policy.pinterest.com/privacy-policy'],
    'tiktok_pixel'     => ['id'=>'tiktok_pixel',       'name'=>'TikTok Pixel',                 'provider'=>'TikTok Inc.',             'category'=>'marketing',  'description'=>'Conversion-Tracking und Retargeting für TikTok Ads.',           'cookies'=>['_ttp','tt_sessionid','tt_appinfo'],                                       'privacy_url'=>'https://www.tiktok.com/legal/privacy-policy'],
    'snapchat'         => ['id'=>'snapchat',           'name'=>'Snapchat Pixel',               'provider'=>'Snap Inc.',               'category'=>'marketing',  'description'=>'Conversion-Tracking für Snapchat Ads.',                          'cookies'=>['sc_anonymous_id'],                                                        'privacy_url'=>'https://values.snap.com/privacy/privacy-policy'],
    'hubspot'          => ['id'=>'hubspot',            'name'=>'HubSpot',                      'provider'=>'HubSpot Inc.',            'category'=>'marketing',  'description'=>'CRM, Marketing-Automatisierung und Lead-Tracking.',              'cookies'=>['hubspotutk','__hstc','__hssc','__hssrc','hs_ab_test'],                    'privacy_url'=>'https://legal.hubspot.com/privacy-policy'],
    'mailchimp'        => ['id'=>'mailchimp',          'name'=>'Mailchimp',                    'provider'=>'Intuit Inc.',             'category'=>'marketing',  'description'=>'E-Mail-Marketing und Newsletter-Tracking.',                      'cookies'=>['_mailchimp_sess'],                                                        'privacy_url'=>'https://www.intuit.com/privacy/statement/'],
    'salesforce'       => ['id'=>'salesforce',         'name'=>'Salesforce Marketing Cloud',   'provider'=>'Salesforce Inc.',         'category'=>'marketing',  'description'=>'Marketing-Automatisierung und CRM.',                             'cookies'=>['_evga_*'],                                                                'privacy_url'=>'https://www.salesforce.com/privacy/'],
    'criteo'           => ['id'=>'criteo',             'name'=>'Criteo',                       'provider'=>'Criteo SA',               'category'=>'marketing',  'description'=>'Personalisiertes Retargeting-Advertising.',                      'cookies'=>['uid','optout'],                                                           'privacy_url'=>'https://www.criteo.com/privacy/'],
    'bing_ads'         => ['id'=>'bing_ads',           'name'=>'Microsoft Advertising (Bing)', 'provider'=>'Microsoft Corp.',         'category'=>'marketing',  'description'=>'Conversion-Tracking und Retargeting für Bing/Microsoft Ads.',   'cookies'=>['MUID','_uetmsclkid'],                                                     'privacy_url'=>'https://privacy.microsoft.com'],
    'xing'             => ['id'=>'xing',               'name'=>'XING / New Work',              'provider'=>'New Work SE',             'category'=>'marketing',  'description'=>'B2B-Netzwerk, Social-Sharing und Tracking.',                    'cookies'=>['xing_browserID'],                                                         'privacy_url'=>'https://privacy.xing.com/de/datenschutzerklaerung'],
];

$categoryLabels = ['essential'=>'Essenziell', 'functional'=>'Funktional', 'analytics'=>'Statistik', 'marketing'=>'Marketing'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ACTION: SCAN
    if (isset($_POST['action']) && $_POST['action'] === 'scan_cookies') {
        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'cookie_settings')) {
            $message = 'Security check failed';
            $messageType = 'error';
        } else {
            $siteUrl = SITE_URL;
            $foundCookies = [];
            
            // 1. Server-Side Scan
            $ch = curl_init($siteUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headers = substr($response, 0, $headerSize);
            curl_close($ch);

            if (preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headers, $matches)) {
                foreach ($matches[1] as $cookieName) {
                    $foundCookies[$cookieName] = [
                        'name' => $cookieName,
                        'type' => 'first_party',
                        'source' => 'Server (Header)',
                        'category' => 'essential',
                        'provider' => 'Eigentümer'
                    ];
                }
            }

            // 2. Content Heuristics Scan
            $content = @file_get_contents($siteUrl, false, stream_context_create(['http'=>['timeout'=>5]]));
            
            $patterns = [
                'google-analytics.com' => ['name' => '_ga',               'source' => 'Google Analytics', 'category' => 'analytics', 'provider' => 'Google LLC'],
                'googletagmanager.com' => ['name' => '_gtm',              'source' => 'Google Tag Manager','category' => 'analytics', 'provider' => 'Google LLC'],
                'facebook.com/tr'      => ['name' => '_fbp',              'source' => 'Facebook Pixel',   'category' => 'marketing', 'provider' => 'Meta Platforms'],
                'youtube.com/embed'    => ['name' => 'VISITOR_INFO1_LIVE','source' => 'YouTube',           'category' => 'marketing', 'provider' => 'Google LLC'],
                'doubleclick.net'      => ['name' => 'IDE',               'source' => 'Google Ads',        'category' => 'marketing', 'provider' => 'Google LLC'],
                'matomo'               => ['name' => '_pk_id',            'source' => 'Matomo',            'category' => 'analytics', 'provider' => 'Matomo org'],
            ];

            if ($content) {
                foreach ($patterns as $domain => $info) {
                    if (stripos($content, $domain) !== false) {
                        $foundCookies[$info['name']] = [
                            'name' => $info['name'] . '*',
                            'type' => 'third_party',
                            'source' => $info['source'],
                            'category' => $info['category'],
                            'provider' => $info['provider']
                        ];
                    }
                }
            }

            // Check DB Settings (Google Analytics via SEO settings)
            $ga = $db->execute("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'seo_google_analytics'")->fetch();
            if ($ga && !empty($ga->option_value)) {
                $foundCookies['_ga'] = ['name' => '_ga*', 'type' => 'third_party', 'source' => 'Google Analytics (via Settings)', 'category' => 'analytics', 'provider' => 'Google LLC'];
            }

            $scanResult = json_encode($foundCookies);
            $db->execute("INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES ('cookie_scan_result', ?) ON DUPLICATE KEY UPDATE option_value = ?", [$scanResult, $scanResult]);
            
            $message = 'Scan abgeschlossen! ' . count($foundCookies) . ' Einträge gefunden.';
            $messageType = 'success';
        }

    // ACTION: SAVE SERVICES
    } elseif (isset($_POST['action']) && $_POST['action'] === 'save_services') {
        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'cookie_settings')) {
            $message = 'Security check failed';
            $messageType = 'error';
        } else {
            $rawSelected = $_POST['active_services'] ?? [];
            // Essentielle Services immer erzwingen
            $forcedEssential = array_keys(array_filter($serviceLibrary, fn($s) => $s['category'] === 'essential'));
            $validated = array_filter($rawSelected, fn($id) => isset($serviceLibrary[$id]));
            $merged = array_values(array_unique(array_merge($forcedEssential, $validated)));
            $json = json_encode($merged);
            $db->execute("INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES ('cookie_active_services', ?) ON DUPLICATE KEY UPDATE option_value = ?", [$json, $json]);
            $message = count($merged) . ' Dienste gespeichert (inkl. ' . count($forcedEssential) . ' essenzielle).';
            $messageType = 'success';
        }

    // ACTION: UPDATE SCAN COOKIE CATEGORY
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_cookie_category') {
        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'cookie_settings')) {
            $message = 'Security check failed';
            $messageType = 'error';
        } else {
            $cookieKey = $_POST['cookie_key'] ?? '';
            $newCat = $_POST['new_category'] ?? '';
            $allowedCats = ['essential', 'functional', 'analytics', 'marketing'];
            if ($cookieKey && in_array($newCat, $allowedCats, true)) {
                $scanRow = $db->execute("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'cookie_scan_result'")->fetch();
                $tmpCookies = $scanRow ? json_decode($scanRow->option_value ?? '[]', true) : [];
                if (isset($tmpCookies[$cookieKey])) {
                    $tmpCookies[$cookieKey]['category'] = $newCat;
                    $json = json_encode($tmpCookies);
                    $db->execute("INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES ('cookie_scan_result', ?) ON DUPLICATE KEY UPDATE option_value = ?", [$json, $json]);
                    $message = 'Kategorie von „' . htmlspecialchars($tmpCookies[$cookieKey]['name']) . '" auf „' . $categoryLabels[$newCat] . '" gesetzt.';
                    $messageType = 'success';
                }
            }
        }

    // ACTION: ADD MANUAL
    } elseif (isset($_POST['action']) && $_POST['action'] === 'add_manual_cookie') {
        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'cookie_settings')) {
            $message = 'Security check failed';
            $messageType = 'error';
        } else {
            $allowedCats = ['essential', 'functional', 'analytics', 'marketing'];
            $manualCookies[] = [
                'name' => trim(strip_tags($_POST['cookie_name'] ?? '')),
                'provider' => trim(strip_tags($_POST['cookie_provider'] ?? '')),
                'category' => in_array($_POST['cookie_category'] ?? '', $allowedCats, true) ? $_POST['cookie_category'] : 'essential',
                'duration' => trim(strip_tags($_POST['cookie_duration'] ?? '')),
                'type'     => 'manual'
            ];
            $json = json_encode($manualCookies);
            $db->execute("INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES ('cookie_manual_list', ?) ON DUPLICATE KEY UPDATE option_value = ?", [$json, $json]);
            $message = 'Cookie manuell hinzugefügt.';
            $messageType = 'success';
        }

    // ACTION: DELETE MANUAL
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_manual_cookie') {
        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'cookie_settings')) {
            $message = 'Security check failed';
            $messageType = 'error';
        } else {
            $index = (int)$_POST['index'];
            if (isset($manualCookies[$index])) {
                array_splice($manualCookies, $index, 1);
                $json = json_encode($manualCookies);
                $db->execute("INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES ('cookie_manual_list', ?) ON DUPLICATE KEY UPDATE option_value = ?", [$json, $json]);
                $message = 'Eintrag gelöscht.';
                $messageType = 'success';
            }
        }

    // ACTION: SAVE SETTINGS
    } elseif (isset($_POST['action']) && $_POST['action'] === 'save_settings') {
        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'cookie_settings')) {
            $message = 'Security check failed';
            $messageType = 'error';
        } else {
            $settings = [
                'cookie_consent_enabled' => isset($_POST['enabled']) ? '1' : '0',
                'cookie_banner_position' => $_POST['position'] ?? 'bottom',
                'cookie_banner_text' => $_POST['banner_text'] ?? '',
                'cookie_accept_text' => $_POST['accept_text'] ?? '',
                'cookie_essential_text' => $_POST['essential_text'] ?? '',
                'cookie_policy_url' => $_POST['policy_url'] ?? '',
                'cookie_primary_color' => $_POST['primary_color'] ?? '#3b82f6'
            ];
            
            foreach ($settings as $key => $val) {
                $db->execute("INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE option_value = ?", [$key, $val, $val]);
            }
            $message = 'Einstellungen gespeichert!';
            $messageType = 'success';
        }
    }
}

// Load settings
$settings = [];
$keys = ['cookie_consent_enabled', 'cookie_banner_position', 'cookie_banner_text', 'cookie_accept_text', 'cookie_essential_text', 'cookie_policy_url', 'cookie_primary_color'];
foreach ($keys as $k) {
    $row = $db->execute("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ?", [$k])->fetch();
    $settings[$k] = $row ? $row->option_value : '';
}

// Load Scan Results
$scanResultJson = $db->execute("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'cookie_scan_result'")->fetch();
$scannedCookies = $scanResultJson ? json_decode($scanResultJson->option_value ?? '[]', true) : [];
if (!is_array($scannedCookies)) $scannedCookies = [];

// Load Active Services
$activeServicesJson = $db->execute("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'cookie_active_services'")->fetch();
$activeServices = $activeServicesJson ? json_decode($activeServicesJson->option_value ?? '[]', true) : [];
if (!is_array($activeServices)) $activeServices = [];
// Essentielle immer aktiv
foreach ($serviceLibrary as $id => $svc) {
    if ($svc['category'] === 'essential' && !in_array($id, $activeServices, true)) {
        $activeServices[] = $id;
    }
}

// Defaults
if (empty($settings['cookie_banner_text'])) $settings['cookie_banner_text'] = 'Wir nutzen Cookies für eine optimale Website-Erfahrung. Einige sind essenziell, andere helfen uns, Inhalte zu personalisieren.';
if (empty($settings['cookie_primary_color'])) $settings['cookie_primary_color'] = '#3b82f6';
if (empty($settings['cookie_banner_position'])) $settings['cookie_banner_position'] = 'bottom';

$csrfToken = $security->generateToken('cookie_settings');

require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Cookie Manager - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <?php renderAdminSidebarStyles(); ?>
    <style>
        .adm-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; }
        .adm-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.875rem; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.625rem; border: 1px solid #cbd5e1; border-radius: 6px; }
        .btn-primary { background: #3b82f6; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; }
        .btn-danger { background: #ef4444; color: white; border: none; padding: 0.25rem 0.75rem; border-radius: 6px; cursor: pointer; font-size: 0.8rem; }
        
        .cookie-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .cookie-table th { text-align: left; padding: 0.75rem; background: #f8fafc; color: #475569; font-weight: 600; border-bottom: 1px solid #e2e8f0; }
        .cookie-table td { padding: 0.75rem; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .badge-essential { background: #dbfafe; color: #0e7490; }
        .badge-analytics { background: #fef3c7; color: #b45309; }
        .badge-marketing { background: #fee2e2; color: #b91c1c; }
        
        .preview-box { background: #f1f5f9; padding: 2rem; border-radius: 12px; display: flex; align-items: center; justify-content: center; min-height: 150px; }
        .preview-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); max-width: 400px; width:100%; }
        
        .tabs { display: flex; gap: 1rem; border-bottom: 1px solid #e2e8f0; margin-bottom: 1rem; }
        .tab-btn { background: none; border: none; padding: 0.75rem 1rem; cursor: pointer; color: #64748b; font-weight: 600; border-bottom: 2px solid transparent; }
        .tab-btn.active { color: #3b82f6; border-bottom-color: #3b82f6; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .code-block { background: #1e293b; color: #e2e8f0; padding: 1rem; border-radius: 6px; font-family: monospace; overflow-x: auto; margin:1rem 0; }
        .copy-btn { float: right; background: #3b82f6; color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem; }

        /* === SERVICE BIBLIOTHEK === */
        .svc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 0.85rem; margin-top: 0.75rem; }
        .svc-card { background: #fff; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 0.9rem 1.1rem; display: flex; gap: 0.75rem; align-items: flex-start; cursor: pointer; transition: border-color 0.15s, background 0.15s; }
        .svc-card:hover { border-color: #94a3b8; }
        .svc-card.active { border-color: #3b82f6; background: #eff6ff; }
        .svc-card.locked { border-color: #10b981; background: #f0fdf4; cursor: default; }
        .svc-card input[type=checkbox] { margin-top: 3px; flex-shrink: 0; width: 16px; height: 16px; accent-color: #3b82f6; }
        .svc-info { flex: 1; min-width: 0; }
        .svc-info strong { font-size: 0.875rem; color: #1e293b; display: block; }
        .svc-provider { font-size: 0.775rem; color: #64748b; display: block; }
        .svc-desc { font-size: 0.8rem; color: #475569; margin-top: 0.2rem; display: block; }
        .svc-cookies { font-size: 0.72rem; color: #94a3b8; margin-top: 0.2rem; display: block; font-style: italic; }
        .svc-privacy { font-size: 0.72rem; color: #3b82f6; text-decoration: none; display: inline-block; margin-top: 0.15rem; }
        .svc-privacy:hover { text-decoration: underline; }
        .svc-cat-header { display: flex; align-items: center; gap: 0.5rem; margin: 1.5rem 0 0.75rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0; }
        .svc-cat-header h4 { margin: 0; font-size: 0.95rem; color: #334155; font-weight: 700; }
        .svc-cat-note { font-size: 0.75rem; color: #64748b; margin-left: auto; }
        .badge-functional { background: #e0f2fe; color: #0369a1; }
        .btn-sm { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 0.35rem 0.75rem; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 500; }
        .btn-sm:hover { background: #e2e8f0; }
        .btn-sm-grey { background: #f8fafc; }
        .cat-sel { padding: 0.2rem 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.78rem; background: #fff; cursor: pointer; }
        .svc-stats { display: flex; gap: 1rem; flex-wrap: wrap; }
        .svc-stat-item { text-align: center; }
        .svc-stat-num { font-size: 1.5rem; font-weight: 700; color: #3b82f6; display: block; }
        .svc-stat-label { font-size: 0.75rem; color: #64748b; }
    </style>
    <script>
        function switchTab(id) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            event.target.classList.add('active');
        }
        function selectAll(state) {
            document.querySelectorAll('.svc-card:not(.locked) input[type=checkbox]').forEach(cb => {
                cb.checked = state;
                cb.closest('.svc-card').classList.toggle('active', state);
            });
        }
        function selectCategory(cat) {
            document.querySelectorAll('.svc-card:not(.locked) input[type=checkbox]').forEach(cb => {
                const card = cb.closest('.svc-card');
                const matches = card.dataset.cat === cat;
                cb.checked = matches;
                card.classList.toggle('active', matches);
            });
        }
        function toggleSvcCard(cb) {
            if (!cb.closest('.svc-card').classList.contains('locked')) {
                cb.closest('.svc-card').classList.toggle('active', cb.checked);
            }
        }
        function updatePreview() {
            const text = document.querySelector('[name="banner_text"]').value;
            const btnAccept = document.querySelector('[name="accept_text"]').value;
            const btnEssential = document.querySelector('[name="essential_text"]').value;
            const color = document.querySelector('[name="primary_color"]').value;
            
            document.getElementById('prev-text').textContent = text || 'Wir verwenden Cookies...';
            document.getElementById('prev-accept').textContent = btnAccept || 'Akzeptieren';
            document.getElementById('prev-accept').style.backgroundColor = color;
            document.getElementById('prev-essential').textContent = btnEssential || 'Nur Essenzielle';
        }
    </script>
</head>
<body class="admin-body">
    <?php renderAdminSidebar('cookies'); ?>
    
    <div class="admin-content">
        <div class="admin-page-header">
            <h2>🍪 Cookie Managed</h2>
            <p>Verwalten Sie Cookies und den Consent Banner.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('tab-settings')">Banner Einstellungen</button>
            <button class="tab-btn" onclick="switchTab('tab-cookies')">Cookie Liste (<?php echo count($scannedCookies) + count($manualCookies); ?>)</button>
            <button class="tab-btn" onclick="switchTab('tab-services')">🔌 Dienste (<?php echo count($activeServices); ?> aktiv)</button>
            <button class="tab-btn" onclick="switchTab('tab-integration')">Integration & Log</button>
        </div>

        <!-- TAB: SETTINGS -->
        <div id="tab-settings" class="tab-content active">
            <form method="post" action="" oninput="updatePreview()">
                <input type="hidden" name="action" value="save_settings">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                <div class="adm-grid">
                    <div class="adm-card">
                        <h3>Aktivierung & Text</h3>
                        <label style="display:flex; gap:0.5rem; align-items:center;">
                            <input type="checkbox" name="enabled" value="1" <?php echo ($settings['cookie_consent_enabled'] === '1') ? 'checked' : ''; ?>>
                            <strong>Cookie Banner auf der Website aktivieren</strong>
                        </label>
                        <div class="form-group" style="margin-top:1rem;">
                            <label>Hinweistext</label>
                            <textarea name="banner_text" rows="3"><?php echo htmlspecialchars($settings['cookie_banner_text']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Link Datenschutz</label>
                            <input type="text" name="policy_url" value="<?php echo htmlspecialchars($settings['cookie_policy_url']); ?>">
                        </div>
                    </div>

                    <div class="adm-card">
                        <h3>Design</h3>
                        <div class="form-group">
                            <label>Position</label>
                            <select name="position">
                                <option value="bottom" <?php echo ($settings['cookie_banner_position'] === 'bottom') ? 'selected' : ''; ?>>Unten (Sticky)</option>
                                <option value="center" <?php echo ($settings['cookie_banner_position'] === 'center') ? 'selected' : ''; ?>>Mitte (Modal)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Primärfarbe</label>
                            <input type="color" name="primary_color" value="<?php echo htmlspecialchars($settings['cookie_primary_color']); ?>" style="height:40px;">
                        </div>
                        <div class="form-group" style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem;">
                            <div>
                                <label>Btn: Akzeptieren</label>
                                <input type="text" name="accept_text" value="<?php echo htmlspecialchars($settings['cookie_accept_text']); ?>">
                            </div>
                            <div>
                                <label>Btn: Essenzielle</label>
                                <input type="text" name="essential_text" value="<?php echo htmlspecialchars($settings['cookie_essential_text']); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="adm-card" style="margin-top:1.5rem;">
                    <h3>Vorschau</h3>
                    <div class="preview-box">
                        <div class="preview-card">
                            <p id="prev-text" style="font-size:0.9rem; color:#64748b; margin-bottom:1rem;"><?php echo htmlspecialchars($settings['cookie_banner_text']); ?></p>
                            <div style="display:flex; justify-content:flex-end; gap:0.5rem;">
                                <button type="button" id="prev-essential" style="background:#f1f5f9; padding:0.5rem 1rem; border-radius:4px; border:none; font-size:0.8rem;"><?php echo htmlspecialchars($settings['cookie_essential_text']); ?></button>
                                <button type="button" id="prev-accept" style="background:<?php echo htmlspecialchars($settings['cookie_primary_color']); ?>; padding:0.5rem 1rem; border-radius:4px; border:none; color:white; font-size:0.8rem;"><?php echo htmlspecialchars($settings['cookie_accept_text']); ?></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-top:1rem; text-align:right;">
                    <button type="submit" class="btn-primary">Speichern</button>
                </div>
            </form>
        </div>

        <!-- TAB: COOKIES -->
        <div id="tab-cookies" class="tab-content">
            
            <div class="adm-grid">
                <!-- Manual Add -->
                <div class="adm-card">
                    <h3>➕ Cookie manuell hinzufügen</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="add_manual_cookie">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <div class="form-group">
                            <label>Name (z.B. _ga)</label>
                            <input type="text" name="cookie_name" required placeholder="Cookie Name">
                        </div>
                        <div class="form-group">
                            <label>Anbieter</label>
                            <input type="text" name="cookie_provider" placeholder="z.B. Google LLC">
                        </div>
                         <div class="form-group">
                            <label>Kategorie</label>
                            <select name="cookie_category">
                                <option value="essential">Essenziell</option>
                                <option value="analytics">Statistik</option>
                                <option value="marketing">Marketing</option>
                            </select>
                        </div>
                         <div class="form-group">
                            <label>Laufzeit</label>
                            <input type="text" name="cookie_duration" placeholder="z.B. 2 Jahre">
                        </div>
                        <button type="submit" class="btn-primary" style="width:100%;">Hinzufügen</button>
                    </form>
                </div>

                <!-- Scanner -->
                <div class="adm-card">
                    <h3>📡 Auto-Scan</h3>
                    <p style="font-size:0.9rem; color:#64748b;">Der Scanner durchsucht die Startseite nach bekannten Cookies.</p>
                    <form method="post">
                        <input type="hidden" name="action" value="scan_cookies">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <button type="submit" class="btn-primary" style="background:#64748b;">🔄 Jetzt scannen</button>
                    </form>
                </div>
            </div>

            <div class="adm-card" style="margin-top:1.5rem;">
                <h3>📋 Cookie Liste</h3>
                <table class="cookie-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Anbieter</th>
                            <th>Kategorie</th>
                            <th>Quelle</th>
                            <th>Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Manual Cookies -->
                        <?php foreach($manualCookies as $idx => $c): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($c['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($c['provider']); ?></td>
                            <td><span class="badge badge-<?php echo htmlspecialchars($c['category']); ?>"><?php echo ucfirst($c['category']); ?></span></td>
                            <td>Manuell</td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_manual_cookie">
                                    <input type="hidden" name="index" value="<?php echo $idx; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <button type="submit" class="btn-danger">Löschen</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <!-- Scanned Cookies -->
                        <?php foreach($scannedCookies as $key => $c): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($c['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($c['provider'] ?? '-'); ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="update_cookie_category">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="cookie_key" value="<?php echo htmlspecialchars((string)$key); ?>">
                                    <select name="new_category" class="cat-sel" onchange="this.form.submit()" title="Kategorie ändern – sofort speichern">
                                        <?php foreach($categoryLabels as $val => $lbl): ?>
                                        <option value="<?php echo $val; ?>" <?php echo (($c['category'] ?? '') === $val) ? 'selected' : ''; ?>><?php echo htmlspecialchars($lbl); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td>Scan (<?php echo htmlspecialchars($c['source'] ?? ''); ?>)</td>
                            <td><span style="color:#94a3b8; font-size:0.8rem;">Auto-Erkannt</span></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($manualCookies) && empty($scannedCookies)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:2rem; color:#94a3b8;">Noch keine Cookies in der Liste.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB: SERVICES -->
        <div id="tab-services" class="tab-content">

            <?php
            $grouped    = ['essential'=>[], 'functional'=>[], 'analytics'=>[], 'marketing'=>[]];
            foreach ($serviceLibrary as $svc) { $grouped[$svc['category']][] = $svc; }
            $totalActive   = count($activeServices);
            $totalServices = count($serviceLibrary);
            $byCategory    = array_map(fn($g) => count(array_filter($g, fn($s) => in_array($s['id'], $activeServices, true))), $grouped);
            $catIcons      = ['essential'=>'🔒', 'functional'=>'⚙️', 'analytics'=>'📊', 'marketing'=>'📣'];
            ?>

            <!-- Stats-Leiste -->
            <div class="adm-card" style="margin-bottom:1.5rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                    <div>
                        <h3 style="margin:0 0 0.25rem;">🔌 Dienste & Drittanbieter</h3>
                        <p style="margin:0; font-size:0.875rem; color:#64748b;">Aktivieren Sie die Dienste, die auf Ihrer Website genutzt werden. <strong>Essentielle Dienste</strong> werden immer geladen – auch bei Ablehnung im Banner. Alle anderen nur mit Zustimmung.</p>
                    </div>
                    <div class="svc-stats">
                        <div class="svc-stat-item"><span class="svc-stat-num"><?php echo $totalActive; ?></span><span class="svc-stat-label">Aktiv</span></div>
                        <div class="svc-stat-item"><span class="svc-stat-num" style="color:#64748b;"><?php echo $totalServices; ?></span><span class="svc-stat-label">Gesamt</span></div>
                        <div class="svc-stat-item"><span class="svc-stat-num" style="color:#10b981;"><?php echo $byCategory['essential']; ?></span><span class="svc-stat-label">Essenziell</span></div>
                        <div class="svc-stat-item"><span class="svc-stat-num" style="color:#f59e0b;"><?php echo $byCategory['analytics']; ?></span><span class="svc-stat-label">Statistik</span></div>
                        <div class="svc-stat-item"><span class="svc-stat-num" style="color:#ef4444;"><?php echo $byCategory['marketing']; ?></span><span class="svc-stat-label">Marketing</span></div>
                    </div>
                </div>
            </div>

            <form method="post" action="">
                <input type="hidden" name="action" value="save_services">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:1rem; align-items:center;">
                    <strong style="color:#334155; font-size:0.9rem;">Schnellauswahl:</strong>
                    <button type="button" onclick="selectCategory('essential')" class="btn-sm">🔒 Nur Essenziell</button>
                    <button type="button" onclick="selectCategory('analytics')" class="btn-sm">📊 + Statistik</button>
                    <button type="button" onclick="selectAll(true)" class="btn-sm">✅ Alle aktivieren</button>
                    <button type="button" onclick="selectAll(false)" class="btn-sm btn-sm-grey">❌ Alle deaktivieren</button>
                    <button type="submit" class="btn-primary" style="margin-left:auto; padding:0.5rem 1.25rem;">💾 Speichern</button>
                </div>

                <?php foreach ($grouped as $cat => $services):
                    if (empty($services)) continue;
                    $countActive = $byCategory[$cat];
                ?>
                <div class="svc-cat-header">
                    <span style="font-size:1.1rem;"><?php echo $catIcons[$cat]; ?></span>
                    <h4><?php echo htmlspecialchars($categoryLabels[$cat]); ?></h4>
                    <span class="badge badge-<?php echo $cat; ?>" style="padding:0.2rem 0.5rem; border-radius:999px; font-size:0.72rem; font-weight:600;"><?php echo $countActive; ?>/<?php echo count($services); ?> aktiv</span>
                    <?php if ($cat === 'essential'): ?>
                    <span class="svc-cat-note">⚠️ Immer aktiv – auch bei „<?php echo htmlspecialchars($settings['cookie_essential_text'] ?: 'Nur Essenzielle'); ?>"</span>
                    <?php elseif ($cat === 'functional'): ?>
                    <span class="svc-cat-note">Benötigt Zustimmung (außer bei eingebetteten Pflichtfunktionen)</span>
                    <?php elseif ($cat === 'analytics'): ?>
                    <span class="svc-cat-note">Benötigt Statistik-Zustimmung</span>
                    <?php elseif ($cat === 'marketing'): ?>
                    <span class="svc-cat-note">Nur mit ausdrücklicher Zustimmung des Nutzers</span>
                    <?php endif; ?>
                </div>
                <div class="svc-grid">
                    <?php foreach ($services as $svc):
                        $isEssential = $svc['category'] === 'essential';
                        $isActive    = in_array($svc['id'], $activeServices, true);
                    ?>
                    <label class="svc-card <?php echo $isEssential ? 'locked' : ($isActive ? 'active' : ''); ?>" data-cat="<?php echo $cat; ?>">
                        <input type="checkbox"
                               name="active_services[]"
                               value="<?php echo htmlspecialchars($svc['id']); ?>"
                               <?php echo $isActive ? 'checked' : ''; ?>
                               <?php echo $isEssential ? 'onclick="return false;" title="Essentielle Dienste sind immer aktiv."' : 'onchange="toggleSvcCard(this);"'; ?>>
                        <div class="svc-info">
                            <strong><?php echo htmlspecialchars($svc['name']); ?></strong>
                            <span class="svc-provider"><?php echo htmlspecialchars($svc['provider']); ?></span>
                            <span class="svc-desc"><?php echo htmlspecialchars($svc['description']); ?></span>
                            <span class="svc-cookies">Cookies: <?php echo htmlspecialchars(implode(', ', $svc['cookies'])); ?></span>
                            <?php if (!empty($svc['privacy_url'])): ?>
                            <a href="<?php echo htmlspecialchars($svc['privacy_url']); ?>" target="_blank" rel="noopener noreferrer" class="svc-privacy" onclick="event.stopPropagation();">Datenschutzerklärung ↗</a>
                            <?php endif; ?>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>

                <div style="margin-top:1.5rem; text-align:right;">
                    <button type="submit" class="btn-primary">💾 Dienste speichern</button>
                </div>
            </form>
        </div>

        <!-- TAB: INTEGRATION & LOG -->
        <div id="tab-integration" class="tab-content">
            <div class="adm-card">
                <h3>🍪 Integration</h3>
                <p>Der Cookie Banner wird automatisch auf allen Seiten eingebunden, wenn er aktiviert ist.</p>
                
                <h4 style="margin-top:1.5rem; margin-bottom:0.5rem; color:#334155;">Einstellungen öffnen</h4>
                <p>Nutzer können ihre Einwilligung nachträglich über diesen Link ändern. Fügen Sie diesen Code in Ihre Datenschutz-Seite oder den Footer ein:</p>
                <div class="code-block">
                    <button class="copy-btn" onclick="navigator.clipboard.writeText(this.nextElementSibling.innerText)">Kopieren</button>
                    <code>&lt;a href="#" onclick="window.CMS.Cookie.openSettings(); return false;"&gt;Cookie-Einstellungen&lt;/a&gt;</code>
                </div>

                <h4 style="margin-top:1.5rem; margin-bottom:0.5rem; color:#334155;">Javascript API</h4>
                <div class="code-block">
                    <code>
// Prüfen ob Consent vorhanden
if (window.CMS.Cookie.hasConsent('analytics')) {
    // Google Analytics laden
}

// Event Listener
window.CMS.Cookie.on('change', (consent) => {
    console.log('Consent updated:', consent);
});
                    </code>
                </div>
            </div>

            <div class="adm-card" style="margin-top:1.5rem;">
                <h3>📜 Consent Log (Auszug letzte 20)</h3>
                <p style="color:#64748b; font-size:0.9rem;">Hier sehen Sie anonymisierte Einwilligungen (Erfordert 'cms_cookie_consents' Tabelle).</p>
                
                <table class="cookie-table" style="margin-top:1rem;">
                    <thead>
                        <tr>
                            <th>Zeitstempel</th>
                            <th>Consent-ID</th>
                            <th>Version</th>
                            <th>Typ</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:2rem; color:#94a3b8;">
                                <em>Consent-Logging ist noch nicht aktiv. Aktivieren Sie die erweiterte Protokollierung in den Einstellungen.</em>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>
