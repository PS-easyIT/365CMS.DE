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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ACTION: SCAN
    if (isset($_POST['action']) && $_POST['action'] === 'scan_cookies') {
        // --- COOKIE SCAN LOGIC ---
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
            'google-analytics.com' => ['name' => '_ga', 'source' => 'Google Analytics', 'category' => 'analytics', 'provider' => 'Google LLC'],
            'googletagmanager.com' => ['name' => '_gtm', 'source' => 'Google Tag Manager', 'category' => 'analytics', 'provider' => 'Google LLC'],
            'facebook.com/tr'      => ['name' => '_fbp', 'source' => 'Facebook Pixel', 'category' => 'marketing', 'provider' => 'Meta Platforms'],
            'youtube.com/embed'    => ['name' => 'VISITOR_INFO1_LIVE', 'source' => 'YouTube', 'category' => 'marketing', 'provider' => 'Google LLC'],
            'doubleclick.net'      => ['name' => 'IDE', 'source' => 'Google Ads', 'category' => 'marketing', 'provider' => 'Google LLC'],
            'matomo'               => ['name' => '_pk_id', 'source' => 'Matomo', 'category' => 'analytics', 'provider' => 'Matomo org'],
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

        // Check DB Settings
        $ga = $db->execute("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'seo_google_analytics'")->fetch();
        if ($ga && !empty($ga->option_value)) {
            $foundCookies['_ga'] = ['name' => '_ga*', 'type' => 'third_party', 'source' => 'Google Analytics (via Settings)', 'category' => 'analytics', 'provider' => 'Google LLC'];
        }

        $scanResult = json_encode($foundCookies);
        $db->execute("INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES ('cookie_scan_result', ?) ON DUPLICATE KEY UPDATE option_value = ?", [$scanResult, $scanResult]);
        
        $message = 'Scan abgeschlossen! ' . count($foundCookies) . ' Einträge gefunden.';
        $messageType = 'success';

    // ACTION: ADD MANUAL
    } elseif (isset($_POST['action']) && $_POST['action'] === 'add_manual_cookie') {
        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'cookie_settings')) {
            $message = 'Security check failed';
            $messageType = 'error';
        } else {
            $manualCookies[] = [
                'name' => $_POST['cookie_name'],
                'provider' => $_POST['cookie_provider'],
                'category' => $_POST['cookie_category'],
                'duration' => $_POST['cookie_duration'],
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
    </style>
    <script>
        function switchTab(id) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            event.target.classList.add('active');
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
                         <?php foreach($scannedCookies as $c): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($c['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($c['provider'] ?? '-'); ?></td>
                            <td><span class="badge badge-<?php echo htmlspecialchars($c['category']); ?>"><?php echo ucfirst($c['category']); ?></span></td>
                            <td>Scan (<?php echo htmlspecialchars($c['source']); ?>)</td>
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
</body>
</html>
