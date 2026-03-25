<?php
declare(strict_types=1);

/**
 * Cookie Manager – Entry Point
 * Route: /admin/cookie-manager
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

require_once __DIR__ . '/modules/legal/CookieManagerModule.php';
$module = new CookieManagerModule();
$alert  = null;

function cms_admin_cookie_manager_target_url(): string
{
    return SITE_URL . '/admin/cookie-manager';
}

function cms_admin_cookie_manager_redirect(): never
{
    header('Location: ' . cms_admin_cookie_manager_target_url());
    exit;
}

function cms_admin_cookie_manager_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($payload['message'] ?? '')),
    ];
}

function cms_admin_cookie_manager_flash_result(array $result): void
{
    cms_admin_cookie_manager_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? 'Aktion abgeschlossen.'),
    ]);
}

function cms_admin_cookie_manager_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
}

function cms_admin_cookie_manager_handle_action(CookieManagerModule $module, string $action, array $post): array
{
    return match ($action) {
        'save_settings' => $module->saveSettings($post),
        'save_category' => $module->saveCategory($post),
        'delete_category' => $module->deleteCategory((int) ($post['id'] ?? 0)),
        'save_service' => $module->saveService($post),
        'delete_service' => $module->deleteService((int) ($post['id'] ?? 0)),
        'import_curated_service' => $module->importCuratedService(
            preg_replace('/[^a-z0-9_-]/', '', strtolower(trim((string) ($post['service_slug'] ?? '')))) ?? '',
            isset($post['self_hosted']) && $post['self_hosted'] === '1'
        ),
        'run_scan' => $module->runScanner(),
        default => ['success' => false, 'error' => 'Aktion konnte nicht verarbeitet werden.'],
    };
}

/** @return array<string, true> */
function cms_admin_cookie_manager_allowed_actions(): array
{
    return [
        'save_settings' => true,
        'save_category' => true,
        'delete_category' => true,
        'save_service' => true,
        'delete_service' => true,
        'import_curated_service' => true,
        'run_scan' => true,
    ];
}

function cms_admin_cookie_manager_is_allowed_action(string $action): bool
{
    return isset(cms_admin_cookie_manager_allowed_actions()[$action]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'admin_cookies')) {
        cms_admin_cookie_manager_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
        cms_admin_cookie_manager_redirect();
    }

    $action = trim((string) ($_POST['action'] ?? ''));
    if (!cms_admin_cookie_manager_is_allowed_action($action)) {
        cms_admin_cookie_manager_flash(['type' => 'danger', 'message' => 'Unbekannte Aktion.']);
        cms_admin_cookie_manager_redirect();
    }

    $result = cms_admin_cookie_manager_handle_action($module, $action, $_POST);
    cms_admin_cookie_manager_flash_result($result);
    cms_admin_cookie_manager_redirect();
}

$alert = cms_admin_cookie_manager_pull_alert();

$csrfToken  = Security::instance()->generateToken('admin_cookies');
$pageTitle  = 'Cookie Manager';
$activePage = 'cookie-manager';
$data       = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/legal/cookies.php';
require_once __DIR__ . '/partials/footer.php';
