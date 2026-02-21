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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'scan_cookies') {
        // --- COOKIE SCAN LOGIC ---
        
        // 1. Server-Side Scan (cURL Header Check)
        $siteUrl = SITE_URL;
        $foundCookies = [];
        
        // Init cURL
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

        // Parse Set-Cookie Headers
        if (preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headers, $matches)) {
            foreach ($matches[1] as $cookieName) {
                // Ignore PHP Session if desired, but good to list
                $foundCookies[$cookieName] = [
                    'name' => $cookieName,
                    'type' => 'first_party',
                    'source' => 'Server (Header)',
                    'category' => 'essential' // Default assumption
                ];
            }
        }

        // 2. Content Heuristics Scan (Body Check for Scripts)
        // We read the front page content + maybe settings to guess 3rd party
        $content = file_get_contents($siteUrl, false, stream_context_create(['http'=>['timeout'=>5]]));
        
        $patterns = [
            'google-analytics.com' => ['name' => '_ga', 'source' => 'Google Analytics', 'category' => 'analytics'],
            'googletagmanager.com' => ['name' => '_gtm', 'source' => 'Google Tag Manager', 'category' => 'analytics'],
            'facebook.com/tr'      => ['name' => '_fbp', 'source' => 'Facebook Pixel', 'category' => 'marketing'],
            'youtube.com/embed'    => ['name' => 'VISITOR_INFO1_LIVE', 'source' => 'YouTube', 'category' => 'marketing'],
            'doubleclick.net'      => ['name' => 'IDE', 'source' => 'Google Ads', 'category' => 'marketing'],
            'matomo'               => ['name' => '_pk_id', 'source' => 'Matomo', 'category' => 'analytics'],
            'fonts.googleapis.com' => ['name' => '(Fonts)', 'source' => 'Google Fonts', 'category' => 'functional'], // Fonts usually don't set cookies anymore but good to detect connection
        ];

        foreach ($patterns as $domain => $info) {
            if (stripos($content, $domain) !== false) {
                $foundCookies[$info['name']] = [
                    'name' => $info['name'] . '*', 
                    'type' => 'third_party', 
                    'source' => $info['source'],
                    'category' => $info['category']
                ];
            }
        }

        // Check DB Settings specifically
        $ga = $db->execute("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'seo_google_analytics'")->fetch();
        if ($ga && !empty($ga->option_value)) {
            $foundCookies['_ga'] = ['name' => '_ga*', 'type' => 'third_party', 'source' => 'Google Analytics (via Settings)', 'category' => 'analytics'];
        }

        // Save scan result to DB (as JSON in a setting)
        $scanResult = json_encode($foundCookies);
        $db->execute("INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES ('cookie_scan_result', ?) ON DUPLICATE KEY UPDATE option_value = ?", [$scanResult, $scanResult]);
        
        $message = 'Scan erfolgreich abgeschlossen! ' . count($foundCookies) . ' potenzielle Cookies/Dienste gefunden.';
        $messageType = 'success';

    } elseif (!$security->verifyToken($_POST['csrf_token'] ?? '', 'cookie_settings')) {
        $message = 'Security check failed';
        $messageType = 'error';
    } else {
        // Save cookie settings
        $settings = [
            'cookie_consent_enabled' => isset($_POST['enabled']) ? '1' : '0',
            'cookie_banner_position' => $_POST['position'] ?? 'bottom',
            'cookie_banner_text' => $_POST['banner_text'] ?? 'Wir verwenden Cookies, um Ihre Erfahrung zu verbessern.',
            'cookie_accept_text' => $_POST['accept_text'] ?? 'Alle akzeptieren',
            'cookie_essential_text' => $_POST['essential_text'] ?? 'Nur Essenzielle',
            'cookie_policy_url' => $_POST['policy_url'] ?? '/datenschutz',
            'cookie_primary_color' => $_POST['primary_color'] ?? '#3b82f6'
        ];
        
        foreach ($settings as $key => $val) {
            $db->execute("INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE option_value = ?", [$key, $val, $val]);
        }
        $message = 'Cookie-Einstellungen gespeichert!';
        $messageType = 'success';
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

// Set defaults if empty
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
        /* Shared Admin Styles (mimic users.php) */
        .adm-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; }
        .adm-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; transition: box-shadow .2s; }
        .adm-card:hover { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); }
        .adm-card h3 { margin: 0 0 0.5rem 0; font-size: 1.1rem; color: #1e293b; display: flex; align-items: center; gap: 0.5rem; }
        
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 500; color: #475569; margin-bottom: 0.5rem; font-size: 0.875rem; }
        .form-group input[type="text"], 
        .form-group input[type="url"], 
        .form-group textarea, 
        .form-group select { 
            width: 100%; padding: 0.625rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.875rem; transition: border-color 0.2s; 
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: #3b82f6; ring: 2px solid #93c5fd; }
        
        .checkbox-wrapper { display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; cursor: pointer; transition: background 0.2s; }
        .checkbox-wrapper:hover { background: #f1f5f9; }
        .checkbox-wrapper input[type="checkbox"] { width: 1.25rem; height: 1.25rem; cursor: pointer; accent-color: #3b82f6; }
        .checkbox-wrapper label { margin: 0; cursor: pointer; color: #334155; font-weight: 600; }

        .btn-primary { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; padding: 0.75rem 2rem; border-radius: 8px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.5); transition: transform 0.1s, box-shadow 0.1s; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 8px -1px rgba(59, 130, 246, 0.6); }
        .btn-primary:active { transform: translateY(0); }

        /* Preview Specific */
        .preview-container { background: #f1f5f9; border-radius: 12px; padding: 2rem; display: flex; align-items: center; justify-content: center; min-height: 200px; position: relative; border: 2px dashed #cbd5e1; }
        .cookie-preview-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .cookie-preview-actions { display: flex; gap: 0.5rem; margin-top: 1rem; justify-content: flex-end; }
        
        /* Status Badge for Header */
        .status-badge { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .status-badge.active { background: #dcfce7; color: #166534; }
        .status-badge.inactive { background: #f1f5f9; color: #64748b; }
    </style>
    <script>
        function updatePreview() {
            const text = document.querySelector('[name="banner_text"]').value;
            const btnAccept = document.querySelector('[name="accept_text"]').value;
            const btnEssential = document.querySelector('[name="essential_text"]').value;
            const color = document.querySelector('[name="primary_color"]').value;
            const position = document.querySelector('[name="position"]').value;
            
            document.getElementById('prev-text').textContent = text || 'Wir verwenden Cookies...';
            document.getElementById('prev-accept').textContent = btnAccept || 'Akzeptieren';
            document.getElementById('prev-accept').style.backgroundColor = color;
            document.getElementById('prev-essential').textContent = btnEssential || 'Nur Essenzielle';
            
            // Simulating position (just visually for the box)
            const card = document.querySelector('.cookie-preview-card');
            if(position === 'center') {
                card.style.margin = 'auto';
                card.style.borderRadius = '12px';
            } else {
                card.style.margin = 'auto'; // In preview we keep it centered but maybe change width?
                card.style.borderRadius = '8px'; 
            }
        }
    </script>
</head>
<body class="admin-body">
    <?php renderAdminSidebar('cookies'); ?>
    
    <div class="admin-content">
        <div class="admin-page-header">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h2>🍪 Cookie Managed</h2>
                    <p>Konfigurieren Sie den Consent Manager für Ihre Besucher.</p>
                </div>
                <div>
                   <?php if($settings['cookie_consent_enabled'] === '1'): ?>
                        <span class="status-badge active">● Aktiv</span>
                   <?php else: ?>
                        <span class="status-badge inactive">○ Inaktiv</span>
                   <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <!-- SCANNER SECTION -->
        <div class="adm-card" style="margin-bottom: 2rem; border-color: #cbd5e1; background:#f8fafc;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                <div>
                    <h3 style="margin-top:0;">📡 Cookie & Service Scanner</h3>
                    <p style="margin:0; font-size:0.9rem; color:#64748b;">Analysiert die Startseite auf gesetzte Cookies und externe Dienste (Analytics, Youtube, etc.).</p>
                </div>
                <form method="post">
                    <!-- No token check needed for scan, or add token to this form too -->
                    <input type="hidden" name="action" value="scan_cookies">
                    <button type="submit" class="btn-primary" style="background: linear-gradient(135deg, #64748b 0%, #475569 100%);">
                        🔍 Jetzt Scannen
                    </button>
                </form>
            </div>
            
            <?php if (!empty($scannedCookies)): ?>
                <div style="margin-top:1.5rem; display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem;">
                    <div>
                        <h4 style="margin:0 0 0.5rem 0; color:#1e293b;">🏠 Eigene Cookies (First-Party)</h4>
                        <?php 
                        $hasFirst = false;
                        foreach($scannedCookies as $c) {
                            if($c['type'] === 'first_party') {
                                $hasFirst = true;
                                echo '<div style="background:white; padding:0.5rem; border:1px solid #e2e8f0; border-radius:4px; margin-bottom:0.5rem; font-size:0.85rem;">';
                                echo '<strong>' . htmlspecialchars($c['name']) . '</strong>';
                                echo '<br><span style="color:#64748b; font-size:0.75rem;">' . htmlspecialchars($c['source']) . ' (' . htmlspecialchars($c['category']) . ')</span>';
                                echo '</div>';
                            }
                        }
                        if(!$hasFirst) echo '<p style="color:#94a3b8; font-size:0.85rem;">Keine gefunden.</p>';
                        ?>
                    </div>
                    <div>
                        <h4 style="margin:0 0 0.5rem 0; color:#1e293b;">🌍 Fremd-Cookies (Third-Party)</h4>
                         <?php 
                        $hasThird = false;
                        foreach($scannedCookies as $c) {
                            if($c['type'] === 'third_party') {
                                $hasThird = true;
                                echo '<div style="background:white; padding:0.5rem; border:1px solid #e2e8f0; border-left:3px solid #f59e0b; border-radius:4px; margin-bottom:0.5rem; font-size:0.85rem;">';
                                echo '<strong>' . htmlspecialchars($c['name']) . '</strong>';
                                echo '<br><span style="color:#64748b; font-size:0.75rem;">' . htmlspecialchars($c['source']) . ' (' . htmlspecialchars($c['category']) . ')</span>';
                                echo '</div>';
                            }
                        }
                        if(!$hasThird) echo '<p style="color:#94a3b8; font-size:0.85rem;">Keine bekannten Tracking-Dienste gefunden.</p>';
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <!-- END SCANNER SECTION -->

        <form method="post" action="" oninput="updatePreview()">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <!-- Main Grid -->
            <div class="adm-grid">
                
                <!-- Column 1: Settings -->
                <div style="display:flex; flex-direction:column; gap:1.5rem;">
                    
                    <!-- Activation Card -->
                    <div class="adm-card">
                        <h3>⚙️ Status</h3>
                        <label class="checkbox-wrapper">
                            <input type="checkbox" name="enabled" value="1" <?php echo ($settings['cookie_consent_enabled'] === '1') ? 'checked' : ''; ?>>
                            <span>Cookie Banner auf der Website aktivieren</span>
                        </label>
                    </div>

                    <!-- Texts Card -->
                    <div class="adm-card">
                        <h3>📝 Inhalte</h3>
                        <div class="form-group">
                            <label>Hinweistext</label>
                            <textarea name="banner_text" rows="4"><?php echo htmlspecialchars($settings['cookie_banner_text']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Link zur Datenschutzerklärung</label>
                            <input type="text" name="policy_url" value="<?php echo htmlspecialchars($settings['cookie_policy_url']); ?>" placeholder="/datenschutz">
                        </div>
                    </div>

                    <!-- Buttons Card -->
                    <div class="adm-card">
                        <h3>🔘 Buttons</h3>
                        <div class="form-group">
                            <label>Beschriftung "Alle akzeptieren"</label>
                            <input type="text" name="accept_text" value="<?php echo htmlspecialchars($settings['cookie_accept_text']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Beschriftung "Nur Essenzielle"</label>
                            <input type="text" name="essential_text" value="<?php echo htmlspecialchars($settings['cookie_essential_text']); ?>">
                        </div>
                    </div>

                </div>

                <!-- Column 2: Design & Preview -->
                <div style="display:flex; flex-direction:column; gap:1.5rem;">
                    
                    <!-- Design Card -->
                    <div class="adm-card">
                        <h3>🎨 Design</h3>
                        <div class="form-group">
                            <label>Positionierung</label>
                            <select name="position">
                                <option value="bottom" <?php echo ($settings['cookie_banner_position'] === 'bottom') ? 'selected' : ''; ?>>Unten fixiert (Sticky)</option>
                                <option value="center" <?php echo ($settings['cookie_banner_position'] === 'center') ? 'selected' : ''; ?>>Zentriert (Modal)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Primärfarbe (Buttons & Links)</label>
                            <div style="display:flex; align-items:center; gap:0.5rem;">
                                <input type="color" name="primary_color" value="<?php echo htmlspecialchars($settings['cookie_primary_color']); ?>" style="width:50px; height:40px; padding:0; border:none; cursor:pointer;">
                                <input type="text" value="<?php echo htmlspecialchars($settings['cookie_primary_color']); ?>" readonly style="width:100px; background:#f1f5f9; border:none; color:#64748b;">
                            </div>
                        </div>
                    </div>

                    <!-- Preview Card -->
                    <div class="adm-card" style="flex:1;">
                        <h3>👁️ Live-Vorschau</h3>
                        <div class="preview-container">
                            <div class="cookie-preview-card">
                                <h4 style="margin:0 0 0.5rem 0; font-size:1rem;">Cookie Einstellungen</h4>
                                <p id="prev-text" style="font-size:0.85rem; color:#64748b; margin-bottom:1rem; line-height:1.5;">
                                    <?php echo htmlspecialchars($settings['cookie_banner_text']); ?>
                                </p>
                                <div class="cookie-preview-actions">
                                    <button type="button" id="prev-essential" style="background:#f1f5f9; color:#475569; border:none; padding:0.5rem 1rem; border-radius:4px; font-weight:600; font-size:0.8rem;">
                                        <?php echo htmlspecialchars($settings['cookie_essential_text']); ?>
                                    </button>
                                    <button type="button" id="prev-accept" style="background:<?php echo htmlspecialchars($settings['cookie_primary_color']); ?>; color:white; border:none; padding:0.5rem 1rem; border-radius:4px; font-weight:600; font-size:0.8rem;">
                                        <?php echo htmlspecialchars($settings['cookie_accept_text']); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p style="text-align:center; font-size:0.75rem; color:#94a3b8; margin:0.5rem 0 0 0;">Die Darstellung auf der Website passt sich dem Theme an.</p>
                    </div>

                </div>
            </div>

            <!-- Footer Actions -->
            <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; display:flex; justify-content:flex-end;">
                <button type="submit" class="btn-primary">
                    💾 Einstellungen speichern
                </button>
            </div>

        </form>
    </div>
</body>
</html>
