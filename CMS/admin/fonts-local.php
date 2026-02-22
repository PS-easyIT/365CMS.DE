<?php
/**
 * Privacy Settings Admin Page
 * 
 * @package CMSv2\Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Security;
use CMS\Database;
use CMS\ThemeManager;
use CMS\Services\ThemeCustomizer;

if (!defined('ABSPATH')) { exit; }
if (!Auth::instance()->isAdmin()) { header('Location: ' . SITE_URL); exit; }

$auth = Auth::instance();
$security = Security::instance();
$db = Database::instance();
$customizer = ThemeCustomizer::instance();
$customizer->setTheme(ThemeManager::instance()->getActiveThemeSlug());

$message = '';
$messageType = '';

/**
 * Downloads fonts from a CSS URL (Google Fonts compatible)
 */
function localizeFontsFromUrl($url) {
    if (!filter_var($url, FILTER_VALIDATE_URL) || strpos($url, 'fonts.googleapis.com') === false) {
        return ['success' => false, 'message' => 'Ungültige Google Fonts URL.'];
    }

    $fontsDir = ASSETS_PATH . 'fonts/';
    $cssDir   = ASSETS_PATH . 'css/';
    if (!is_dir($fontsDir)) mkdir($fontsDir, 0755, true);
    if (!is_dir($cssDir))   mkdir($cssDir, 0755, true);

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n"
        ]
    ]);
    
    $cssContent = @file_get_contents($url, false, $context);
    if (!$cssContent) return ['success' => false, 'message' => 'Konnte CSS nicht laden.'];

    $newCssContent = preg_replace_callback('/url\((https?:\/\/[^\)]+)\)/', function($matches) use ($fontsDir) {
        $remoteFontUrl = $matches[1];
        $filename = basename(parse_url($remoteFontUrl, PHP_URL_PATH));
        $localFontPath = $fontsDir . $filename;
        
        if (!file_exists($localFontPath)) {
            $fontData = @file_get_contents($remoteFontUrl);
            if ($fontData) file_put_contents($localFontPath, $fontData);
        }
        return 'url(../fonts/' . $filename . ')';
    }, $cssContent);

    if (file_put_contents($cssDir . 'local-fonts.css', $newCssContent) === false) {
        return ['success' => false, 'message' => 'Schreibfehler bei local-fonts.css'];
    }

    return ['success' => true, 'message' => 'Schriften erfolgreich heruntergeladen!'];
}

function downloadGoogleFonts($customizer) {
    // Basic automatic detection logic (simulated/hardcoded)
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

    if (in_array($baseFont, $googleFonts) && isset($fontMap[$baseFont])) $fontsToLoad[$baseFont] = $fontMap[$baseFont];
    if (in_array($headingFont, $googleFonts) && isset($fontMap[$headingFont])) $fontsToLoad[$headingFont] = $fontMap[$headingFont];

    if (empty($fontsToLoad)) return ['success' => false, 'message' => 'Keine Standard-Google Fonts in Theme-Config gefunden.'];

    $families = implode('&family=', array_values($fontsToLoad));
    $url = 'https://fonts.googleapis.com/css2?family=' . $families . '&display=swap';
    
    return localizeFontsFromUrl($url);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'privacy_settings')) {
        $message = 'Sicherheitsüberprüfung fehlgeschlagen';
        $messageType = 'error';
    } else {
        if ($_POST['action'] === 'localize_fonts') {
            $result = downloadGoogleFonts($customizer);
            if ($result['success']) {
                $db->execute("INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES ('privacy_use_local_fonts', '1') ON DUPLICATE KEY UPDATE option_value = '1'");
                $message = $result['message']; $messageType = 'success';
            } else {
                $message = $result['message']; $messageType = 'error';
            }
        
        } elseif ($_POST['action'] === 'localize_custom') {
            $result = localizeFontsFromUrl($_POST['custom_url']);
            if ($result['success']) {
                $db->execute("INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES ('privacy_use_local_fonts', '1') ON DUPLICATE KEY UPDATE option_value = '1'");
                $message = $result['message']; $messageType = 'success';
            } else {
                $message = $result['message']; $messageType = 'error';
            }

        } elseif ($_POST['action'] === 'reset_fonts') {
            $db->execute("UPDATE {$db->getPrefix()}settings SET option_value = '0' WHERE option_name = 'privacy_use_local_fonts'");
            $message = 'Lokale Schriften deaktiviert.';
            $messageType = 'info';
        }
    }
}

$useLocalFonts = $db->execute("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'privacy_use_local_fonts'")->fetch();
$isLocalFontsActive = ($useLocalFonts && $useLocalFonts->option_value === '1');
$csrfToken = $security->generateToken('privacy_settings');

require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Font Manager - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=202602">
    <?php renderAdminSidebarStyles(); ?>
</head>
<body class="admin-body">
    <?php renderAdminSidebar('fonts-local'); ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h2>🔤 Font Manager</h2>
                <span class="status-badge <?php echo $isLocalFontsActive ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo $isLocalFontsActive ? 'Lokal Aktiviert' : 'Google CDN'; ?>
                </span>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="adm-grid">
            <!-- Card 1: Theme Auto-Scan -->
            <div class="adm-card">
                <h3>🔍 Automatisch aus Theme</h3>
                <p style="font-size:0.9rem; color:#64748b;">Scannt die Theme-Einstellungen und versucht, die Standard-Schriftarten (Inter, Roboto, etc.) zu laden.</p>
                <form method="post">
                    <input type="hidden" name="action" value="localize_fonts">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <button type="submit" class="btn-primary">Scan & Download</button>
                </form>
            </div>

            <!-- Card 2: Custom URL -->
            <div class="adm-card">
                <h3>🔗 Eigene Google Webfonts URL</h3>
                <p style="font-size:0.9rem; color:#64748b;">
                    Fügen Sie hier die CSS-URL von Google Webfonts ein.<br>
                    (z.B. <code>https://fonts.googleapis.com/css2?family=Roboto&display=swap</code>)
                </p>
                <form method="post">
                    <input type="hidden" name="action" value="localize_custom">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <div class="form-group">
                        <input type="text" name="custom_url" placeholder="https://fonts.googleapis.com/..." required>
                    </div>
                    <button type="submit" class="btn-primary">Download</button>
                </form>
            </div>
            
            <!-- Card 3: Reset -->
             <div class="adm-card" style="border-color:#fca5a5; background:#fef2f2;">
                 <h3 style="color:#b91c1c;">⚠️ Reset</h3>
                 <p style="font-size:0.9rem; color:#7f1d1d;">Schaltet auf die Standard-CDN-Einbindung zurück.</p>
                 <form method="post">
                    <input type="hidden" name="action" value="reset_fonts">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <button type="submit" class="btn-primary" style="background:#ef4444;">Deaktivieren</button>
                 </form>
             </div>
        </div>
    </div>
</body>
</html>
