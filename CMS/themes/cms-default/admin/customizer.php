<?php
/**
 * CMS Default Theme - Customizer Settings
 *
 * Stellt die vollständige Admin-Oberfläche für Theme-Einstellungen bereit.
 *
 * @package CMSv2\Themes\CmsDefault\Admin
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Services\ThemeCustomizer;

$embedInAdminLayout = !empty($embedInAdminLayout);

require_once __DIR__ . '/customizer/helpers.php';

$config = require __DIR__ . '/customizer/config.php';

if (class_exists('\\CMS\\Hooks')) {
    $filteredConfig = \CMS\Hooks::applyFilters('theme_customizer_sections', $config, 'cms-default');
    if (is_array($filteredConfig)) {
        $config = $filteredConfig;
    }
}

$adminMenuLoaded = false;
foreach (cms_default_theme_customizer_get_admin_menu_paths() as $path) {
    if (is_file($path)) {
        require_once $path;
        $adminMenuLoaded = true;
        break;
    }
}

if (!$adminMenuLoaded) {
    if (!function_exists('renderAdminSidebar')) {
        function renderAdminSidebar(string $slug): void
        {
            echo '<!-- Sidebar fallback for ' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') . ' -->';
        }
    }

    if (!function_exists('renderAdminSidebarStyles')) {
        function renderAdminSidebarStyles(): void
        {
        }
    }
}

$customizer = ThemeCustomizer::instance();
if (class_exists('\\CMS\\ThemeManager')) {
    $customizer->setTheme(\CMS\ThemeManager::instance()->getActiveThemeSlug());
}

$requestedTab = (string) ($_GET['tab'] ?? 'header');
$activeTab = cms_default_theme_customizer_resolve_active_tab($config, $requestedTab);

$postResult = cms_default_theme_customizer_handle_post($customizer, $config, $activeTab);
$success = $postResult['success'];
$error = $postResult['error'];
$activeTab = cms_default_theme_customizer_resolve_active_tab($config, (string) ($postResult['activeTab'] ?? $activeTab));

$customizerCsrfToken = \CMS\Security::instance()->generateToken('theme_customizer');

if (!$embedInAdminLayout):
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme Customizer – <?php echo defined('SITE_NAME') ? htmlspecialchars((string) SITE_NAME, ENT_QUOTES, 'UTF-8') : 'CMS'; ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(cms_asset_url('css/main.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(cms_asset_url('css/admin.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <?php renderAdminSidebarStyles(); ?>
    <?php require __DIR__ . '/customizer/partials/styles.php'; ?>
</head>
<body class="admin-body">
    <?php renderAdminSidebar('theme-customizer'); ?>
    <div class="admin-content">
<?php endif; ?>

<?php require __DIR__ . '/customizer/partials/page.php'; ?>

<?php if (!$embedInAdminLayout): ?>
    </div>
    <script src="<?php echo htmlspecialchars(cms_asset_url('js/admin.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
<?php endif; ?>
