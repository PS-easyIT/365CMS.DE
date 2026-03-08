<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Editor – Entry Point
 * Route: /admin/theme-editor
 */

use CMS\Auth;
use CMS\ThemeManager;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$themeManager    = ThemeManager::instance();
$activeThemeSlug = $themeManager->getActiveThemeSlug();
$customizerPath  = $themeManager->getThemePath() . 'admin/customizer.php';

if (is_file($customizerPath)) {
    $pageTitle = 'Theme Editor';
    $activePage = 'theme-editor';
    $pageAssets = [];
    $embedInAdminLayout = true;

    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/sidebar.php';
    require $customizerPath;
    require __DIR__ . '/partials/footer.php';
    return;
}

$pageTitle  = 'Theme Editor';
$activePage = 'theme-editor';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
?>
<div class="container-xl">
    <div class="page-header d-flex align-items-center mb-4">
        <div>
            <h2 class="page-title">Theme Editor</h2>
            <div class="text-muted mt-1">Das aktive Theme stellt keinen eigenen Customizer bereit.</div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <p class="mb-3">Für das aktive Theme <code><?php echo htmlspecialchars($activeThemeSlug); ?></code> wurde keine Datei <code>admin/customizer.php</code> gefunden.</p>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?php echo htmlspecialchars(SITE_URL . '/admin/themes'); ?>" class="btn btn-primary">Zur Theme-Verwaltung</a>
                <a href="<?php echo htmlspecialchars(SITE_URL . '/admin/theme-explorer'); ?>" class="btn btn-outline-secondary">Theme Explorer öffnen</a>
            </div>
        </div>
    </div>
</div>
<?php
require __DIR__ . '/partials/footer.php';
