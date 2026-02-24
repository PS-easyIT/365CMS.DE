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
    $typo = $customizer->getCategory('typography');

    $fontMap = [
        // ── cms-default Kern-Schriften ──────────────────────────────────────
        'dm-sans'             => 'DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400',
        'libre-baskerville'   => 'Libre+Baskerville:ital,wght@0,400;0,700;1,400',
        'dm-mono'             => 'DM+Mono:wght@400;500',
        // ── Serifenlose Schriften ───────────────────────────────────────────
        'inter'               => 'Inter:wght@300;400;500;600;700;800',
        'roboto'              => 'Roboto:ital,wght@0,300;0,400;0,500;0,700;1,400',
        'open-sans'           => 'Open+Sans:ital,wght@0,300;0,400;0,600;0,700;1,400',
        'lato'                => 'Lato:ital,wght@0,300;0,400;0,700;1,400',
        'montserrat'          => 'Montserrat:ital,wght@0,400;0,500;0,600;0,700;1,400',
        'poppins'             => 'Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400',
        'raleway'             => 'Raleway:ital,wght@0,400;0,600;0,700;1,400',
        'nunito'              => 'Nunito:ital,wght@0,300;0,400;0,600;0,700;1,400',
        'nunito-sans'         => 'Nunito+Sans:ital,wght@0,300;0,400;0,600;0,700;1,400',
        'figtree'             => 'Figtree:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400',
        'outfit'              => 'Outfit:wght@300;400;500;600;700',
        'plus-jakarta-sans'   => 'Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400',
        'manrope'             => 'Manrope:wght@300;400;500;600;700;800',
        'work-sans'           => 'Work+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400',
        'space-grotesk'       => 'Space+Grotesk:wght@300;400;500;600;700',
        'urbanist'            => 'Urbanist:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400',
        'geist'               => 'Geist:wght@300;400;500;600;700',
        'ibm-plex-sans'       => 'IBM+Plex+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400',
        'source-sans-3'       => 'Source+Sans+3:ital,wght@0,300;0,400;0,600;0,700;1,400',
        'noto-sans'           => 'Noto+Sans:ital,wght@0,300;0,400;0,600;0,700;1,400',
        'ubuntu'              => 'Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,400',
        'barlow'              => 'Barlow:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400',
        'cabin'               => 'Cabin:ital,wght@0,400;0,500;0,600;0,700;1,400',
        'josefin-sans'        => 'Josefin+Sans:ital,wght@0,300;0,400;0,600;0,700;1,400',
        'mukta'               => 'Mukta:wght@300;400;500;600;700',
        'quicksand'           => 'Quicksand:wght@300;400;500;600;700',
        'dosis'               => 'Dosis:wght@300;400;500;600;700',
        'exo-2'               => 'Exo+2:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400',
        'overpass'            => 'Overpass:ital,wght@0,300;0,400;0,600;0,700;1,400',
        // ── Serifenschriften ────────────────────────────────────────────────
        'playfair-display'    => 'Playfair+Display:ital,wght@0,400;0,600;0,700;1,400',
        'merriweather'        => 'Merriweather:ital,wght@0,300;0,400;0,700;1,400',
        'lora'                => 'Lora:ital,wght@0,400;0,500;0,600;0,700;1,400',
        'source-serif-4'      => 'Source+Serif+4:ital,opsz,wght@0,8..60,300;0,8..60,400;0,8..60,600;0,8..60,700;1,8..60,400',
        'ibm-plex-serif'      => 'IBM+Plex+Serif:ital,wght@0,300;0,400;0,600;0,700;1,400',
        'noto-serif'          => 'Noto+Serif:ital,wght@0,400;0,600;0,700;1,400',
        'pt-serif'            => 'PT+Serif:ital,wght@0,400;0,700;1,400',
        'crimson-text'        => 'Crimson+Text:ital,wght@0,400;0,600;0,700;1,400',
        'spectral'            => 'Spectral:ital,wght@0,300;0,400;0,600;0,700;1,400',
        'bitter'              => 'Bitter:ital,wght@0,300;0,400;0,600;0,700;1,400',
        'eb-garamond'         => 'EB+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400',
        'cormorant-garamond'  => 'Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400',
        // ── Display / Headlines ─────────────────────────────────────────────
        'oswald'              => 'Oswald:wght@300;400;500;600;700',
        'bebas-neue'          => 'Bebas+Neue:wght@400',
        'syne'                => 'Syne:wght@400;500;600;700;800',
        // ── Monospace / Code ────────────────────────────────────────────────
        'jetbrains-mono'      => 'JetBrains+Mono:ital,wght@0,400;0,500;0,700;1,400',
        'ibm-plex-mono'       => 'IBM+Plex+Mono:ital,wght@0,400;0,500;0,600;1,400',
        'source-code-pro'     => 'Source+Code+Pro:ital,wght@0,400;0,500;0,600;1,400',
        'fira-code'           => 'Fira+Code:wght@400;500;600;700',
        'roboto-mono'         => 'Roboto+Mono:ital,wght@0,400;0,500;0,700;1,400',
        'geist-mono'          => 'Geist+Mono:wght@400;500;600;700',
    ];

    // Unterstütze beide Key-Konventionen:
    // cms-default  → font_family_body / font_family_heading
    // 365Network   → font_family_base / font_family_heading
    $baseFont    = $typo['font_family_body']    ?? $typo['font_family_base']    ?? 'dm-sans';
    $headingFont = $typo['font_family_heading'] ?? 'libre-baskerville';

    // Optionale Zusatz-Slots (z. B. Mono für Code-Blöcke, UI-Schrift für Nav)
    $monoFont    = $typo['font_family_mono'] ?? $typo['font_family_code'] ?? null;
    $uiFont      = $typo['font_family_ui']   ?? null;

    $fontsToLoad = [];
    foreach ([$baseFont, $headingFont, $monoFont, $uiFont] as $fontKey) {
        if ($fontKey && isset($fontMap[$fontKey]) && !isset($fontsToLoad[$fontKey])) {
            $fontsToLoad[$fontKey] = $fontMap[$fontKey];
        }
    }

    // Fallback: wenn gar nichts erkannt wurde → cms-default Standard-Set laden
    if (empty($fontsToLoad)) {
        $fontsToLoad = [
            'dm-sans'           => $fontMap['dm-sans'],
            'libre-baskerville' => $fontMap['libre-baskerville'],
            'dm-mono'           => $fontMap['dm-mono'],
        ];
    }

    // dm-mono immer mitladen wenn dm-sans Basis-Schrift ist (Code-Blöcke)
    if ($baseFont === 'dm-sans' && !isset($fontsToLoad['dm-mono'])) {
        $fontsToLoad['dm-mono'] = $fontMap['dm-mono'];
    }

    if (empty($fontsToLoad)) {
        return ['success' => false, 'message' => 'Keine Google Fonts in Theme-Konfiguration gefunden.'];
    }

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
renderAdminLayoutStart('Font Manager', 'fonts-local');
?>
<div class="admin-page-header">
    <div>
        <h2>🔤 Font Manager</h2>
        <p>Google Fonts lokal hosten und DSGVO-konform einbinden</p>
    </div>
    <div class="header-actions">
        <span class="status-badge <?php echo $isLocalFontsActive ? 'active' : 'inactive'; ?>">
            <?php echo $isLocalFontsActive ? 'Lokal Aktiviert' : 'Google CDN'; ?>
        </span>
    </div>
</div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.5rem;">
            <!-- Card 1: Theme Auto-Scan -->
            <div class="admin-card">
                <h3>🔍 Automatisch aus Theme</h3>
                <p class="form-text">Scannt die Theme-Einstellungen und versucht, die Standard-Schriftarten (Inter, Roboto, etc.) zu laden.</p>
                <form method="post">
                    <input type="hidden" name="action" value="localize_fonts">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <button type="submit" class="btn btn-primary">Scan &amp; Download</button>
                </form>
            </div>

            <!-- Card 2: Custom URL -->
            <div class="admin-card">
                <h3>🔗 Eigene Google Webfonts URL</h3>
                <p class="form-text">
                    Fügen Sie hier die CSS-URL von Google Webfonts ein.<br>
                    (z.B. <code>https://fonts.googleapis.com/css2?family=Roboto&amp;display=swap</code>)
                </p>
                <form method="post">
                    <input type="hidden" name="action" value="localize_custom">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <div class="form-group">
                        <input type="text" name="custom_url" class="form-control" placeholder="https://fonts.googleapis.com/..." required>
                    </div>
                    <button type="submit" class="btn btn-primary">Download</button>
                </form>
            </div>
            
            <!-- Card 3: Reset -->
            <div class="admin-card" style="border-top:4px solid #ef4444;">
                <h3 style="color:#ef4444;">⚠️ Zurücksetzen</h3>
                <p class="form-text">Schaltet auf die Standard-CDN-Einbindung zurück. Die heruntergeladenen Schriftdateien bleiben erhalten.</p>
                <form method="post">
                    <input type="hidden" name="action" value="reset_fonts">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <button type="submit" class="btn btn-danger">Deaktivieren</button>
                </form>
            </div>
        </div>

<?php renderAdminLayoutEnd(); ?>
