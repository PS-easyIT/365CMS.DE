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

function cms_admin_theme_editor_page_title(): string
{
    return 'Theme Editor';
}

function cms_admin_theme_editor_layout_defaults(): void
{
    $GLOBALS['pageTitle'] = cms_admin_theme_editor_page_title();
    $GLOBALS['activePage'] = 'theme-editor';
    $GLOBALS['pageAssets'] = [];
}

function cms_admin_theme_editor_render_admin_layout(callable $contentRenderer): void
{
    cms_admin_theme_editor_layout_defaults();

    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/sidebar.php';
    $contentRenderer();
    require __DIR__ . '/partials/footer.php';
}

/** @return array{themes:string, explorer:string} */
function cms_admin_theme_editor_fallback_links(): array
{
    return [
        'themes' => SITE_URL . '/admin/themes',
        'explorer' => SITE_URL . '/admin/theme-explorer',
    ];
}

function cms_admin_theme_editor_render_missing_customizer(string $activeThemeSlug): void
{
    $links = cms_admin_theme_editor_fallback_links();
    ?>
<div class="container-xl">
    <div class="page-header d-flex align-items-center mb-4">
        <div>
            <h2 class="page-title"><?php echo htmlspecialchars(cms_admin_theme_editor_page_title()); ?></h2>
            <div class="text-muted mt-1">Das aktive Theme stellt keinen eigenen Customizer bereit.</div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <p class="mb-3">Für das aktive Theme <code><?php echo htmlspecialchars($activeThemeSlug); ?></code> wurde keine Datei <code>admin/customizer.php</code> gefunden.</p>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?php echo htmlspecialchars($links['themes']); ?>" class="btn btn-primary">Zur Theme-Verwaltung</a>
                <a href="<?php echo htmlspecialchars($links['explorer']); ?>" class="btn btn-outline-secondary">Theme Explorer öffnen</a>
            </div>
        </div>
    </div>
</div>
<?php
}

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$themeManager    = ThemeManager::instance();
$activeThemeSlug = $themeManager->getActiveThemeSlug();
$customizerPath  = $themeManager->getThemePath() . 'admin/customizer.php';

if (is_file($customizerPath)) {
    $embedInAdminLayout = true;

    cms_admin_theme_editor_render_admin_layout(static function () use ($customizerPath): void {
        require $customizerPath;
    });
    return;
}

cms_admin_theme_editor_render_admin_layout(static function () use ($activeThemeSlug): void {
    cms_admin_theme_editor_render_missing_customizer($activeThemeSlug);
});
