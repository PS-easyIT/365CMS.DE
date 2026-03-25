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
$module      = new CookieManagerModule();
$alert       = null;
$redirectUrl = SITE_URL . '/admin/cookie-manager';

function cms_admin_cookie_manager_redirect(string $redirectUrl): never
{
    header('Location: ' . $redirectUrl);
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

function cms_admin_cookie_manager_handle_action(CookieManagerModule $module, string $action, array $post): array
{
    switch ($action) {
        case 'save_settings':
            return $module->saveSettings($post);

        case 'save_category':
            return $module->saveCategory($post);

        case 'delete_category':
            return $module->deleteCategory((int) ($post['id'] ?? 0));

        case 'save_service':
            return $module->saveService($post);

        case 'delete_service':
            return $module->deleteService((int) ($post['id'] ?? 0));

        case 'import_curated_service':
            $serviceSlug = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim((string) ($post['service_slug'] ?? '')))) ?? '';

            return $module->importCuratedService(
                $serviceSlug,
                isset($post['self_hosted']) && $post['self_hosted'] === '1'
            );

        case 'run_scan':
            return $module->runScanner();
    }

    return ['success' => false, 'error' => 'Aktion konnte nicht verarbeitet werden.'];
}

/** @var list<string> $allowedActions */
$allowedActions = [
    'save_settings',
    'save_category',
    'delete_category',
    'save_service',
    'delete_service',
    'import_curated_service',
    'run_scan',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_cookies')) {
        cms_admin_cookie_manager_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
        cms_admin_cookie_manager_redirect($redirectUrl);
    } else {
        $action = trim((string)($_POST['action'] ?? ''));
        if (!in_array($action, $allowedActions, true)) {
            cms_admin_cookie_manager_flash(['type' => 'danger', 'message' => 'Unbekannte Aktion.']);
            cms_admin_cookie_manager_redirect($redirectUrl);
        }

        $result = cms_admin_cookie_manager_handle_action($module, $action, $_POST);
        cms_admin_cookie_manager_flash_result($result);
        cms_admin_cookie_manager_redirect($redirectUrl);
    }
}

if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_cookies');
$pageTitle  = 'Cookie Manager';
$activePage = 'cookie-manager';
$data       = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/legal/cookies.php';
require_once __DIR__ . '/partials/footer.php';
