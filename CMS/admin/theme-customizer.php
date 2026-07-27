<?php
/**
 * Theme Customizer Dispatcher
 *
 * Leitet an den theme-eigenen Customizer weiter.
 * Das CMS stellt nur Auth, Security und die Datenschicht (ThemeCustomizer) bereit –
 * das visuelle Admin-UI kommt vollständig aus dem aktiven Theme.
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\ThemeManager;
use CMS\Services\ThemeCustomizer;

if (!defined('ABSPATH')) {
    exit;
}

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL . '/login');
    exit;
}

$themeManager      = ThemeManager::instance();
$activeThemeSlug   = $themeManager->getActiveThemeSlug();
$themeCustomizerFile = THEME_PATH . $activeThemeSlug . '/admin/customizer.php';

// Customizer-Datenschicht auf aktives Theme einstellen
ThemeCustomizer::instance()->setTheme($activeThemeSlug);

if (file_exists($themeCustomizerFile)) {
    // Theme übernimmt die vollständige Ausgabe
    require $themeCustomizerFile;
    exit;
}

// ── Kein theme-eigener Customizer vorhanden ──────────────────────────────────
require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Theme Customizer</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=20260222b">
    <?php renderAdminSidebarStyles(); ?>
</head>
<body class="admin-body">
<?php renderAdminSidebar('theme-customizer'); ?>
<main class="admin-content">
    <div class="admin-page-header">
        <h2>🎨 Theme Customizer</h2>
    </div>
    <div style="padding:2rem;background:#fef9c3;border:2px solid #f59e0b;border-radius:10px;color:#92400e;margin:1rem 2rem;">
        <h3>Kein Customizer für dieses Theme</h3>
        <p>Das aktive Theme <strong><?php echo htmlspecialchars($activeThemeSlug); ?></strong>
           hat keine eigene Admin-Customizer-Datei.</p>
        <p>Erwartet unter: <code><?php echo htmlspecialchars($themeCustomizerFile); ?></code></p>
        <p>Erstelle die Datei <code>themes/<?php echo htmlspecialchars($activeThemeSlug); ?>/admin/customizer.php</code>
           im Theme-Verzeichnis, um einen eigenen Customizer bereitzustellen.</p>
    </div>
</main>
</body>
</html>
