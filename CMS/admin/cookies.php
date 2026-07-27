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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cookie Manager – <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=20260222b">
    <?php renderAdminSidebarStyles(); ?>
    <style>
        .svc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .svc-card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; display: flex; gap: 0.75rem; cursor: pointer; transition: all 0.2s; background: #fff; }
        .svc-card:hover { border-color: #cbd5e1; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .svc-card.active { border-color: var(--admin-primary); background: var(--admin-primary-light); }
        .svc-card.locked { opacity: 0.7; cursor: not-allowed; background: #f8fafc; }
        .svc-info strong { display: block; margin-bottom: 0.25rem; color: #1e293b; }
        .svc-provider { display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 0.5rem; }
        .svc-desc { display: block; font-size: 0.85rem; color: #475569; line-height: 1.4; margin-bottom: 0.5rem; }
        .svc-cookies { display: block; font-size: 0.75rem; color: #94a3b8; font-family: monospace; }
        .svc-privacy { font-size: 0.75rem; color: var(--admin-primary); text-decoration: none; display: inline-block; margin-top: 0.25rem; }
        .svc-cat-header { margin: 2rem 0 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 0.75rem; }
        .svc-cat-note { font-size: 0.8rem; color: #64748b; margin-left: auto; }
        .preview-box { background: #e2e8f0; padding: 2rem; border-radius: 8px; display: flex; justify-content: center; margin-top: 1rem; }
        .preview-card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); max-width: 400px; width: 100%; }
        /* Tabs overrides */
        .tabs { margin-bottom: 1.5rem; border-bottom: 1px solid #e2e8f0; overflow-x: auto; white-space: nowrap; }
        .tab-btn { background: none; border: none; padding: 1rem 1.5rem; font-size: 1rem; color: #64748b; cursor: pointer; border-bottom: 2px solid transparent; transition: all 0.2s; }
        .tab-btn:hover { color: #1e293b; }
        .tab-btn.active { color: var(--admin-primary); border-bottom-color: var(--admin-primary); font-weight: 600; }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="admin-body">
    <?php renderAdminSidebar('cookies'); ?>
    
    <div class="admin-content">
        <div class="admin-page-header">
            <div>
                <h2>🍪 Cookie Manager</h2>
                <p>Verwalten Sie Cookies und den Consent Banner</p>
            </div>
            <div class="header-actions">
                <?php if ($message): ?>
                    <span style="font-size:0.9rem; margin-right:1rem;" class="<?php echo $messageType === 'success' ? 'text-success' : 'text-danger'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('tab-settings', this)">Banner Einstellungen</button>
            <button class="tab-btn" onclick="switchTab('tab-cookies', this)">Cookie Liste (<?php echo count($scannedCookies) + count($manualCookies); ?>)</button>
            <button class="tab-btn" onclick="switchTab('tab-services', this)">🔌 Dienste (<?php echo count($activeServices); ?> aktiv)</button>
            <button class="tab-btn" onclick="switchTab('tab-integration', this)">Integration & Log</button>
        </div>

        <!-- TAB: SETTINGS -->
        <div id="tab-settings" class="tab-content active">
            <form method="post" oninput="updatePreview()">
                <input type="hidden" name="action" value="save_settings">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                <div class="form-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
                    <div class="admin-card">
                        <h3>Aktivierung & Text</h3>
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="enabled" value="1" <?php echo ($settings['cookie_consent_enabled'] === '1') ? 'checked' : ''; ?>>
                                <strong>Cookie Banner auf der Website aktivieren</strong>
                            </label>
                        </div>
                        <div class="form-group" style="margin-top:1rem;">
                            <label class="form-label">Hinweistext</label>
                            <textarea name="banner_text" class="form-control" rows="3"><?php echo htmlspecialchars($settings['cookie_banner_text']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Link Datenschutz</label>
                            <input type="text" name="policy_url" class="form-control" value="<?php echo htmlspecialchars($settings['cookie_policy_url']); ?>">
                        </div>
                    </div>

                    <div class="admin-card">
                        <h3>Design</h3>
                        <div class="form-group">
                            <label class="form-label">Position</label>
                            <select name="position" class="form-control">
                                <option value="bottom" <?php echo ($settings['cookie_banner_position'] === 'bottom') ? 'selected' : ''; ?>>Unten (Sticky)</option>
                                <option value="center" <?php echo ($settings['cookie_banner_position'] === 'center') ? 'selected' : ''; ?>>Mitte (Modal)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Primärfarbe</label>
                            <input type="color" name="primary_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($settings['cookie_primary_color']); ?>" style="height:40px; padding:0.2rem;">
                        </div>
                        <div class="form-group" style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                            <div>
                                <label class="form-label">Btn: Akzeptieren</label>
                                <input type="text" name="accept_text" class="form-control" value="<?php echo htmlspecialchars($settings['cookie_accept_text']); ?>">
                            </div>
                            <div>
                                <label class="form-label">Btn: Essenzielle</label>
                                <input type="text" name="essential_text" class="form-control" value="<?php echo htmlspecialchars($settings['cookie_essential_text']); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="admin-card" style="margin-top:1.5rem;">
                    <h3>Vorschau</h3>
                    <div class="preview-box">
                        <div class="preview-card">
                            <p id="prev-text" style="font-size:0.9rem; color:#64748b; margin-bottom:1rem; margin-top:0;"><?php echo htmlspecialchars($settings['cookie_banner_text']); ?></p>
                            <div style="display:flex; justify-content:flex-end; gap:0.5rem;">
                                <button type="button" id="prev-essential" style="background:#f1f5f9; padding:0.5rem 1rem; border-radius:4px; border:none; font-size:0.8rem; cursor:pointer; color:#475569;"><?php echo htmlspecialchars($settings['cookie_essential_text']); ?></button>
                                <button type="button" id="prev-accept" style="background:<?php echo htmlspecialchars($settings['cookie_primary_color']); ?>; padding:0.5rem 1rem; border-radius:4px; border:none; color:white; font-size:0.8rem; cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,0.1);"><?php echo htmlspecialchars($settings['cookie_accept_text']); ?></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="admin-card form-actions-card">
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 Speichern</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- TAB: COOKIES -->
        <div id="tab-cookies" class="tab-content">
            
            <div class="form-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
                <!-- Manual Add -->
                <div class="admin-card">
                    <h3>➕ Cookie manuell hinzufügen</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="add_manual_cookie">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <div class="form-group">
                            <label class="form-label">Name (z.B. _ga)</label>
                            <input type="text" name="cookie_name" class="form-control" required placeholder="Cookie Name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Anbieter</label>
                            <input type="text" name="cookie_provider" class="form-control" placeholder="z.B. Google LLC">
                        </div>
                         <div class="form-group">
                            <label class="form-label">Kategorie</label>
                            <select name="cookie_category" class="form-control">
                                <option value="essential">Essenziell</option>
                                <option value="analytics">Statistik</option>
                                <option value="marketing">Marketing</option>
                            </select>
                        </div>
                         <div class="form-group">
                            <label class="form-label">Laufzeit</label>
                            <input type="text" name="cookie_duration" class="form-control" placeholder="z.B. 2 Jahre">
                        </div>
                        <div style="margin-top:1.5rem;">
                             <button type="submit" class="btn btn-primary" style="width:100%;">Hinzufügen</button>
                        </div>
                    </form>
                </div>

                <!-- Scanner -->
                <div class="admin-card">
                    <h3>📡 Auto-Scan</h3>
                    <p style="font-size:0.9rem; color:#64748b;">Der Scanner durchsucht die Startseite nach bekannten Cookies und Klassifiziert diese automatisch.</p>
                    <div style="background:#f8fafc; padding:1.5rem; border-radius:8px; text-align:center; border:1px dashed #cbd5e1; margin-top:1rem;">
                        <span style="font-size:2rem; margin-bottom:0.5rem; display:block;">🛰️</span>
                        <form method="post">
                            <input type="hidden" name="action" value="scan_cookies">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <button type="submit" class="btn btn-secondary">🔄 Jetzt scannen</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="admin-card" style="margin-top:1.5rem;">
                <h3>📋 Cookie Liste</h3>
                <div class="users-table-container">
                    <table class="users-table">
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
                                <td><span class="status-badge <?php echo htmlspecialchars($c['category'] ?? 'essential'); ?>"><?php echo ucfirst($c['category']); ?></span></td>
                                <td>Manuell</td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_manual_cookie">
                                        <input type="hidden" name="index" value="<?php echo $idx; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
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
                                        <select name="new_category" class="form-control" style="padding:0.2rem; font-size:0.85rem;" onchange="this.form.submit()" title="Kategorie ändern – sofort speichern">
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
            <div class="admin-card" style="margin-bottom:1.5rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                    <div>
                        <h3>🔌 Dienste & Drittanbieter</h3>
                        <p style="margin:0; font-size:0.875rem; color:#64748b;">Aktivieren Sie die Dienste, die auf Ihrer Website genutzt werden.</p>
                    </div>
                    <div style="display:flex; gap:1.5rem;">
                        <div style="text-align:center;"><span style="display:block; font-size:1.5rem; font-weight:700; color:#1e293b;"><?php echo $totalActive; ?></span><span style="color:#64748b; font-size:0.8rem;">Aktiv</span></div>
                        <div style="text-align:center;"><span style="display:block; font-size:1.5rem; font-weight:700; color:#cbd5e1;"><?php echo $totalServices; ?></span><span style="color:#64748b; font-size:0.8rem;">Gesamt</span></div>
                    </div>
                </div>
            </div>

            <form method="post" action="">
                <input type="hidden" name="action" value="save_services">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                <div class="admin-card form-actions-card" style="margin-bottom:2rem; display:flex; gap:0.5rem; flex-wrap:wrap; justify-content:space-between; align-items:center;">
                    <strong style="color:#334155; font-size:0.9rem;">Schnellauswahl:</strong>
                    <div style="display:flex; gap:0.5rem;">
                        <button type="button" onclick="selectCategory('essential')" class="btn btn-sm btn-secondary">🔒 Nur Essenziell</button>
                        <button type="button" onclick="selectCategory('analytics')" class="btn btn-sm btn-secondary">📊 + Statistik</button>
                        <button type="button" onclick="selectAll(true)" class="btn btn-sm btn-secondary">✅ Alle</button>
                    </div>
                    <button type="submit" class="btn btn-primary">💾 Auswahl speichern</button>
                </div>

                <?php foreach ($grouped as $cat => $services):
                    if (empty($services)) continue;
                    $countActive = $byCategory[$cat];
                ?>
                <div class="svc-cat-header">
                    <span style="font-size:1.1rem;"><?php echo $catIcons[$cat]; ?></span>
                    <h4 style="margin:0; font-size:1rem; font-weight:700; color:#1e293b;"><?php echo htmlspecialchars($categoryLabels[$cat]); ?></h4>
                    <span class="status-badge <?php echo $countActive > 0 ? 'active' : 'inactive'; ?>" style="font-size:0.75rem; margin-left:0.5rem;"><?php echo $countActive; ?> aktiv</span>
                    
                    <?php if ($cat === 'essential'): ?>
                    <span class="svc-cat-note">⚠️ Immer aktiv</span>
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
                            <span class="svc-desc" style="display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;"><?php echo htmlspecialchars($svc['description']); ?></span>
                            <span class="svc-cookies">Cookies: <?php echo htmlspecialchars(implode(', ', $svc['cookies'])); ?></span>
                            <?php if (!empty($svc['privacy_url'])): ?>
                            <a href="<?php echo htmlspecialchars($svc['privacy_url']); ?>" target="_blank" rel="noopener noreferrer" class="svc-privacy" onclick="event.stopPropagation();">Datenschutzerklärung ↗</a>
                            <?php endif; ?>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </form>
        </div>

        <!-- TAB: INTEGRATION & LOG -->
        <div id="tab-integration" class="tab-content">
            <div class="admin-card">
                <h3>🍪 Integration</h3>
                <p style="color:#64748b; margin-bottom:1.5rem;">Der Cookie Banner wird automatisch auf allen Seiten eingebunden, wenn er aktiviert ist.</p>
                
                <h4 style="margin-top:1.5rem; margin-bottom:0.5rem; color:#334155;">Einstellungen öffnen Link</h4>
                <p style="color:#64748b; font-size:0.9rem;">Nutzer können ihre Einwilligung nachträglich über diesen Link ändern. Fügen Sie diesen Code in Ihre Datenschutz-Seite oder den Footer ein:</p>
                <div style="background:#1e293b; color:#cbd5e1; padding:1rem; border-radius:6px; font-family:monospace; position:relative;">
                    <button class="btn btn-sm btn-secondary" onclick="navigator.clipboard.writeText(this.nextElementSibling.innerText)" style="position:absolute; top:0.5rem; right:0.5rem;">Kopieren</button>
                    <code>&lt;a href="#" onclick="window.CMS.Cookie.openSettings(); return false;"&gt;Cookie-Einstellungen&lt;/a&gt;</code>
                </div>

                <h4 style="margin-top:1.5rem; margin-bottom:0.5rem; color:#334155;">Javascript API</h4>
                <div style="background:#1e293b; color:#cbd5e1; padding:1rem; border-radius:6px; font-family:monospace; overflow-x:auto;">
<code>// Prüfen ob Consent vorhanden
if (window.CMS.Cookie.hasConsent('analytics')) {
    // Google Analytics laden
}

// Event Listener
window.CMS.Cookie.on('change', (consent) => {
    console.log('Consent updated:', consent);
});</code>
                </div>
            </div>

            <div class="admin-card" style="margin-top:1.5rem;">
                <h3>📜 Consent Log (Auszug letzte 20)</h3>
                <p style="color:#64748b; font-size:0.9rem;">Hier sehen Sie anonymisierte Einwilligungen (Erfordert 'cms_cookie_consents' Tabelle).</p>
                
                <div class="users-table-container">
                    <table class="users-table">
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

    </div>
    <script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
    <script>
        function switchTab(id, btn) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            if(btn) btn.classList.add('active');
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
                if (matches) {
                    cb.checked = true;
                    card.classList.add('active');
                }
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
</body>
</html>