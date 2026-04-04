<?php
/**
 * CMS Default Theme - Customizer Settings
 *
 * Verdrahtet den Theme-Customizer gegen die Runtime-Partials.
 *
 * @package CMSv2\Themes\CmsDefault\Admin
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Security;
use CMS\ThemeManager;
use CMS\Services\ThemeCustomizer;

require_once __DIR__ . '/customizer/helpers.php';

$config = require __DIR__ . '/customizer/config.php';

$customizer = ThemeCustomizer::instance();
if (class_exists(ThemeManager::class)) {
    $customizer->setTheme(ThemeManager::instance()->getActiveThemeSlug());
}

$embedInAdminLayout = (bool) ($embedInAdminLayout ?? true);
$activeTab = cms_default_theme_customizer_resolve_active_tab($config, (string) ($_GET['tab'] ?? 'header'));

$postResult = cms_default_theme_customizer_handle_post($customizer, $config, $activeTab);
$success = $postResult['success'];
$error = $postResult['error'];
$activeTab = $postResult['activeTab'];

if ($success === null && $error === null && isset($alert) && is_array($alert)) {
    $alertMessage = trim((string) ($alert['message'] ?? ''));
    if ($alertMessage !== '') {
        $alertType = function_exists('cms_admin_section_shell_normalize_alert_type')
            ? cms_admin_section_shell_normalize_alert_type($alert['type'] ?? 'info', 'info')
            : 'info';

        if ($alertType === 'success') {
            $success = $alertMessage;
        } else {
            $error = $alertMessage;
        }
    }
}

$customizerCsrfToken = (string) ($csrfToken ?? Security::instance()->generateToken('theme_customizer'));

require __DIR__ . '/customizer/partials/page.php';
