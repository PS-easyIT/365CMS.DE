<?php
/**
 * Privacy Settings Admin Page
 * 
 * @package CMSv2\Admin
 */

declare(strict_types=1);

// Load configuration first
require_once dirname(__DIR__) . '/config.php';

// Load autoloader
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Security;
use CMS\Database;
use CMS\ThemeManager;
use CMS\Services\ThemeCustomizer;

if (!defined('ABSPATH')) {
    exit;
}

// Security check
if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$auth = Auth::instance();
$security = Security::instance();
$db = Database::instance();
$customizer = ThemeCustomizer::instance();
// Sicherstellen, dass das aktive Theme verwendet wird
$customizer->setTheme(ThemeManager::instance()->getActiveThemeSlug());

// Handle form submissions
$message = '';
$messageType = '';

/* 
 * -------------------------------------------------------------------------
 * HELPER: Download & Localize Google Fonts
 * -------------------------------------------------------------------------
 */
function downloadGoogleFonts($customizer) {
    // 1. Determine active fonts from Theme Customizer
    $typo = $customizer->getCategory('typography');
    $googleFonts = ['inter', 'roboto', 'open-sans', 'lato', 'montserrat', 'poppins', 'raleway'];
    $fontMap = [
        'inter'       => 'Inter:wght@400;500;600;700;800',
        'roboto'      => 'Roboto:wght@400;500;700',
        'open-sans'   => 'Open+Sans:wght@400;600;700',
        'lato'        => 'Lato:wght@400;700',
        'montserrat'  => 'Montserrat:wght@400;500;600;700',
        'poppins'     => 'Poppins:wght@400;500;600;700',
        'raleway'     => 'Raleway:wght@400;600;700',
    ];

    $fontsToLoad = [];
    $baseFont    = $typo['font_family_base']    ?? 'inter';
    $headingFont = $typo['font_family_heading'] ?? 'inter';

    if (in_array($baseFont, $googleFonts, true) && isset($fontMap[$baseFont])) {
        $fontsToLoad[$baseFont] = $fontMap[$baseFont];
    }
    if ($headingFont !== $baseFont && in_array($headingFont, $googleFonts, true) && isset($fontMap[$headingFont])) {
        $fontsToLoad[$headingFont] = $fontMap[$headingFont];
    }

    if (empty($fontsToLoad)) {
        return ['success' => false, 'message' => 'Keine Google Fonts in den Theme-Einstellungen aktiv.'];
    }

    $families = implode('&family=', array_values($fontsToLoad));
    $remoteCssUrl = 'https://fonts.googleapis.com/css2?family=' . $families . '&display=swap';

    // 2. Prepare directories
    $fontsDir = ASSETS_PATH . 'fonts/';
    $cssDir   = ASSETS_PATH . 'css/';
    
    if (!is_dir($fontsDir)) mkdir($fontsDir, 0755, true);
    if (!is_dir($cssDir))   mkdir($cssDir, 0755, true);

    // 3. Fetch CSS (spoof User-Agent for WOFF2)
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n"
        ]
    ]);
    
    $cssContent = @file_get_contents($remoteCssUrl, false, $context);
    if (!$cssContent) {
        return ['success' => false, 'message' => 'Konnte CSS von Google Fonts nicht laden.'];
    }

    // 4. Parse CSS to find font URLs
    // Regex to match "url(https://...)"
    $newCssContent = preg_replace_callback('/url\((https?:\/\/[^\)]+)\)/', function($matches) use ($fontsDir) {
        $remoteFontUrl = $matches[1];
        $filename = basename(parse_url($remoteFontUrl, PHP_URL_PATH));
        
        // Ensure unique filename slightly to avoid overwrites if Google reuses names? 
        // Google usually uses unique hashes. 
        // We just keep the name.
        
        $localFontPath = $fontsDir . $filename;
        
        // Download font file
        if (!file_exists($localFontPath)) {
            $fontData = @file_get_contents($remoteFontUrl);
            if ($fontData) {
                file_put_contents($localFontPath, $fontData);
            }
        }
        
        // Return relative path for CSS (assuming css is in /assets/css/ and fonts in /assets/fonts/)
        return 'url(../fonts/' . $filename . ')';
        
    }, $cssContent);

    // 5. Save local CSS
    $localCssPath = $cssDir . 'local-fonts.css';
    if (file_put_contents($localCssPath, $newCssContent) === false) {
        return ['success' => false, 'message' => 'Konnte lokale CSS-Datei nicht speichern.'];
    }

    return ['success' => true, 'message' => 'Schriften erfolgreich lokalisiert!'];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'privacy_settings')) {
        $message = 'Sicherheitsüberprüfung fehlgeschlagen';
        $messageType = 'error';
    } else {
        if ($_POST['action'] === 'save_privacy') {
            // Placeholder for future privacy settings
            $message = 'Einstellungen gespeichert (Platzhalter)';
            $messageType = 'success';
            
        } elseif ($_POST['action'] === 'localize_fonts') {
            $result = downloadGoogleFonts($customizer);
            if ($result['success']) {
                // Set flag in DB that we are using local fonts now
                $db->execute("INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES ('privacy_use_local_fonts', '1') ON DUPLICATE KEY UPDATE option_value = '1'");
                $message = $result['message'];
                $messageType = 'success';
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        } elseif ($_POST['action'] === 'reset_fonts') {
            // Disable local fonts
            $db->execute("UPDATE {$db->getPrefix()}settings SET option_value = '0' WHERE option_name = 'privacy_use_local_fonts'");
            $message = 'Lokale Schriften deaktiviert. Es werden wieder Google CDNs verwendet (wenn konfiguriert).';
            $messageType = 'info';
        }
    }
}

// Load current settings
$useLocalFonts = $db->execute("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'privacy_use_local_fonts'")->fetch();
$isLocalFontsActive = ($useLocalFonts && $useLocalFonts->option_value === '1');

// Generate CSRF token
$csrfToken = $security->generateToken('privacy_settings');

// Load admin menu
require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Font Manager - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <?php renderAdminSidebarStyles(); ?>
    <style>
        /* Shared Admin Styles */
        .adm-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; }
        .adm-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; transition: box-shadow .2s; }
        .adm-card:hover { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); }
        .adm-card h3 { margin: 0 0 0.5rem 0; font-size: 1.1rem; color: #1e293b; display: flex; align-items: center; gap: 0.5rem; }
        
        .btn-localize { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.5); transition: all 0.2s; }
        .btn-localize:hover { transform: translateY(-2px); box-shadow: 0 6px 8px -1px rgba(37, 99, 235, 0.6); }
        
        .btn-reset { background: #fff; border: 1px solid #cbd5e1; color: #64748b; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s; }
        .btn-reset:hover { background: #f8fafc; color: #334155; border-color: #94a3b8; }

        .status-badge { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .status-badge.active { background: #dcfce7; color: #166534; }
        .status-badge.inactive { background: #fff1f2; color: #be123c; }

        .info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; margin-top: 1rem; font-size: 0.9rem; color: #475569; line-height: 1.6; }
        .info-box strong { color: #1e293b; }
        
        .font-list { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.5rem; }
        .font-tag { background: #e2e8f0; color: #475569; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.8rem; font-weight: 500; font-family: monospace; }
        
        /* Animation for scanning */
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.7; } 100% { opacity: 1; } }
        .scanning .btn-localize { pointer-events: none; animation: pulse 1.5s infinite; }
    </style>
</head>
<body class="admin-body">
    
    <?php renderAdminSidebar('fonts-local'); ?>
    
    <!-- Main Content -->
    <div class="admin-content">
        
        <div class="admin-page-header">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h2>🔤 Font Manager</h2>
                    <p>Lokale Hosting-Lösung für Google Fonts (DSGVO-Konformität).</p>
                </div>
                <div>
                   <?php if($isLocalFontsActive): ?>
                        <span class="status-badge active">● Lokal Aktiviert</span>
                   <?php else: ?>
                        <span class="status-badge inactive">○ CDN (Google)</span>
                   <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 2rem;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="adm-grid">
            
            <!-- Status Card -->
            <div class="adm-card">
                <h3>📝 Status & Konfiguration</h3>
                
                <div class="info-box">
                    <?php if($isLocalFontsActive): ?>
                        <div style="color: #166534; font-weight: 600; margin-bottom: 0.5rem;">✅ Sicheitshinweis: Konform</div>
                        Ihre Website lädt Schriftarten aktuell vom <strong>eigenen Server</strong>. Es bestehen keine Verbindungen zu Google-Servern beim Laden der Fonts.
                    <?php else: ?>
                        <div style="color: #be123c; font-weight: 600; margin-bottom: 0.5rem;">⚠️ Sicherheitshinweis: Nicht Optimiert</div>
                        Schriftarten werden direkt von <strong>fonts.googleapis.com</strong> geladen. Dies überträgt die IP-Adresse Ihrer Besucher an Google (USA) und kann ohne explizite Einwilligung abgemahnt werden.
                    <?php endif; ?>
                </div>

                <form method="post" style="margin-top: auto; display: flex; flex-direction: column; gap: 1rem;">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <?php if (!$isLocalFontsActive): ?>
                        <div style="background: #eff6ff; border: 1px solid #dbeafe; padding: 1rem; border-radius: 8px;">
                            <p style="margin: 0 0 1rem 0; font-size: 0.9rem; color: #1e40af;">
                                Ein Klick scannt das aktive Theme nach Google Fonts, lädt diese herunter und bindet sie lokal ein.
                            </p>
                            <input type="hidden" name="action" value="localize_fonts">
                            <button type="submit" class="btn-localize" onclick="this.closest('form').classList.add('scanning'); this.innerHTML='📥 Lade herunter...';">
                                📥 Schriften scannen & lokal hosten
                            </button>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <input type="hidden" name="action" value="reset_fonts">
                            
                             <!-- Optional: Re-Scan Button with different value -->
                             <button type="submit" name="action" value="localize_fonts" class="btn-localize">
                                🔄 Neu scannen & Aktualisieren
                            </button>

                            <button type="submit" name="action" value="reset_fonts" class="btn-reset">
                                🔙 Zurücksetzen auf Google CDN
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Info / Detected Fonts Card -->
            <div class="adm-card">
                <h3>🔍 Erkannte Schriftarten</h3>
                <p style="font-size: 0.9rem; color: #64748b; margin: 0;">
                    Diese Schriftarten sind in Ihrem Theme (Theme.json / Customizer) konfiguriert und werden verarbeitet:
                </p>
                
                <?php
                // Quickly peek at customizer config to show what would be downloaded
                $typo = $customizer->getCategory('typography');
                $base = ucfirst($typo['font_family_base'] ?? 'Inter');
                $head = ucfirst($typo['font_family_heading'] ?? 'Inter');
                $fonts = array_unique([$base, $head]);
                ?>
                
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;">🔠</div>
                    <ul class="font-list" style="justify-content: center;">
                        <?php foreach($fonts as $f): ?>
                            <li class="font-tag"><?php echo htmlspecialchars($f); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p style="margin-top: 1rem; font-size: 0.8rem; color: #94a3b8;">
                        Dateipfad: <code>/assets/fonts/</code>
                    </p>
                    <?php if($isLocalFontsActive && file_exists(ASSETS_PATH . 'css/local-fonts.css')): ?>
                        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: #166534;">
                            ✓ local-fonts.css generiert (<?php echo date('d.m.Y H:i', filemtime(ASSETS_PATH . 'css/local-fonts.css')); ?>)
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        
    </div>
    
</body>
</html>
