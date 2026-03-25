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

function cms_admin_settings_normalize_tab(string $tab): string
{
    return $tab === 'content' ? 'content' : 'general';
}

function cms_admin_settings_redirect(string $tab): never
{
    header('Location: ' . cms_admin_settings_redirect_url($tab));
    exit;
}

function cms_admin_settings_redirect_url(string $tab): string
{
    return SITE_URL . '/admin/settings?tab=' . cms_admin_settings_normalize_tab($tab);
}

function cms_admin_settings_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($payload['message'] ?? '')),
    ];
}

function cms_admin_settings_flash_result(array $result): void
{
    cms_admin_settings_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? ''),
    ]);
}

function cms_admin_settings_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
}

/**
 * @return array<string, callable(array): array>
 */
function cms_admin_settings_action_handlers(SettingsModule $module): array
{
    return [
        'save' => static fn (array $post): array => $module->saveSettings($post),
        'run_site_url_migration' => static fn (array $post): array => $module->runSiteUrlMigration($post),
        'repair_imported_slugs' => static fn (array $post): array => $module->repairImportedSlugs(),
        'send_test_email' => static fn (array $post): array => $module->sendTestEmail($post),
    ];
}

$module = new SettingsModule();
$alert = null;
$currentTab = cms_admin_settings_normalize_tab((string) ($_GET['tab'] ?? 'general'));
$actionHandlers = cms_admin_settings_action_handlers($module);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = (string) ($_POST['csrf_token'] ?? '');
    $redirectTab = cms_admin_settings_normalize_tab((string) ($_POST['tab'] ?? 'general'));
    $action = (string) ($_POST['action'] ?? '');

    if (!Security::instance()->verifyToken($postToken, 'admin_settings')) {
        cms_admin_settings_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
        cms_admin_settings_redirect($redirectTab);
    }

    $currentTab = $redirectTab;
    if (!isset($actionHandlers[$action])) {
        cms_admin_settings_flash(['type' => 'danger', 'message' => 'Unbekannte Aktion.']);
        cms_admin_settings_redirect($currentTab);
    }

    $result = $actionHandlers[$action]($_POST);
    cms_admin_settings_flash_result($result);

    cms_admin_settings_redirect($currentTab);
}

$alert = cms_admin_settings_pull_alert();

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
