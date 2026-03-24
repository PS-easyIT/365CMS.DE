<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Allgemeine Einstellungen – Entry Point
 * Route: /admin/settings
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/settings/SettingsModule.php';
$module    = new SettingsModule();
$alert     = null;
$currentTab = ($_GET['tab'] ?? 'general') === 'content' ? 'content' : 'general';
$allowedActions = ['save', 'run_site_url_migration', 'repair_imported_slugs', 'send_test_email'];

$buildRedirectUrl = static function (string $tab): string {
	$normalizedTab = $tab === 'content' ? 'content' : 'general';
	return SITE_URL . '/admin/settings?tab=' . $normalizedTab;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    $redirectTab = ($_POST['tab'] ?? 'general') === 'content' ? 'content' : 'general';
    $action = (string)($_POST['action'] ?? '');

    if (!Security::instance()->verifyToken($postToken, 'admin_settings')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . $buildRedirectUrl($redirectTab));
        exit;
    }

    $currentTab = ($_POST['tab'] ?? 'general') === 'content' ? 'content' : 'general';
    if (!in_array($action, $allowedActions, true)) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Unbekannte Aktion.'];
        header('Location: ' . $buildRedirectUrl($currentTab));
        exit;
    }

    if ($action === 'save') {
        $result = $module->saveSettings($_POST);
    } elseif ($action === 'run_site_url_migration') {
        $result = $module->runSiteUrlMigration($_POST);
    } elseif ($action === 'repair_imported_slugs') {
        $result = $module->repairImportedSlugs();
    } elseif ($action === 'send_test_email') {
        $result = $module->sendTestEmail($_POST);
    } else {
        $result = ['success' => false, 'error' => 'Unbekannte Aktion.'];
    }

    $_SESSION['admin_alert'] = [
        'type'    => !empty($result['success']) ? 'success' : 'danger',
        'message' => $result['message'] ?? $result['error'] ?? '',
    ];

    header('Location: ' . $buildRedirectUrl($currentTab));
    exit;
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_settings');
$mediaConnectorToken = Security::instance()->generateToken('media_connector');
$data       = $module->getData();
$pageTitle  = $currentTab === 'content' ? 'Beiträge & Sites' : 'Allgemeine Einstellungen';
$activePage = $currentTab === 'content' ? 'content-settings' : 'settings';
$pageAssets = $currentTab === 'general'
    ? [
        'css' => [
            cms_asset_url('elfinder/vendor/jquery-ui/jquery-ui-1.13.2.css'),
            cms_asset_url('elfinder/css/elfinder.min.css'),
            cms_asset_url('elfinder/css/theme.css'),
        ],
        'js' => [
            cms_asset_url('js/admin-media-integrations.js'),
        ],
    ]
    : [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/settings/general.php';
require __DIR__ . '/partials/footer.php';
