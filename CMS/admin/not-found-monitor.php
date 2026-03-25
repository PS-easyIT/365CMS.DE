<?php
declare(strict_types=1);

/**
 * 404-Monitor – Entry Point
 * Route: /admin/not-found-monitor
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/seo/RedirectManagerModule.php';
$module = new RedirectManagerModule();
$alert = null;

function cms_admin_not_found_monitor_redirect(string $redirectUrl): never
{
    header('Location: ' . $redirectUrl);
    exit;
}

function cms_admin_not_found_monitor_default_redirect(): string
{
    return SITE_URL . '/admin/not-found-monitor';
}

function cms_admin_not_found_monitor_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($payload['message'] ?? '')),
        'details' => is_array($payload['details'] ?? null) ? $payload['details'] : [],
    ];
}

function cms_admin_not_found_monitor_flash_result(array $result): void
{
    cms_admin_not_found_monitor_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? ''),
        'details' => $result['details'] ?? [],
    ]);
}

function cms_admin_not_found_monitor_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
}

/**
 * @return array<string, callable(array): array>
 */
function cms_admin_not_found_monitor_action_handlers(RedirectManagerModule $module): array
{
    return [
        'save_redirect' => static fn (array $post): array => $module->saveRedirect($post),
        'clear_logs' => static fn (array $post): array => $module->clearLogs(),
    ];
}

function cms_admin_not_found_monitor_handle_action(RedirectManagerModule $module, string $action, array $post): array
{
    $handlers = cms_admin_not_found_monitor_action_handlers($module);

    if (!isset($handlers[$action])) {
        return ['success' => false, 'error' => 'Unbekannte Aktion.'];
    }

    return $handlers[$action]($post);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'admin_redirect_manager')) {
        cms_admin_not_found_monitor_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
        cms_admin_not_found_monitor_redirect(cms_admin_not_found_monitor_default_redirect());
    }

    $action = (string) ($_POST['action'] ?? '');
    $result = cms_admin_not_found_monitor_handle_action($module, $action, $_POST);

    cms_admin_not_found_monitor_flash_result($result);
    cms_admin_not_found_monitor_redirect(cms_admin_not_found_monitor_default_redirect());
}

$alert = cms_admin_not_found_monitor_pull_alert();

$csrfToken = Security::instance()->generateToken('admin_redirect_manager');
$pageTitle = '404-Monitor';
$activePage = 'not-found-monitor';
$pageAssets = [
    'js' => [
        cms_asset_url('js/admin-seo-redirects.js'),
    ],
];
$data = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
defined('CMS_ADMIN_SEO_VIEW') || define('CMS_ADMIN_SEO_VIEW', true);
require_once __DIR__ . '/views/seo/not-found.php';
require_once __DIR__ . '/partials/footer.php';
