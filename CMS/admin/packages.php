<?php
declare(strict_types=1);

/**
 * Abo-Pakete – Entry Point
 * Route: /admin/packages
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

function cms_admin_packages_redirect(string $redirectBase): never
{
    header('Location: ' . $redirectBase);
    exit;
}

function cms_admin_packages_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($payload['message'] ?? '')),
    ];
}

function cms_admin_packages_flash_result(array $result): void
{
    cms_admin_packages_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? ''),
    ]);
}

function cms_admin_packages_flash_action_result(SubscriptionSettingsActionResult $result): void
{
    $payload = $result->toArray();

    cms_admin_packages_flash([
        'type' => !empty($payload['success']) ? 'success' : 'danger',
        'message' => (string) ($payload['message'] ?? $payload['error'] ?? ''),
    ]);
}

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/subscriptions/PackagesModule.php';
$module          = new PackagesModule();
require_once __DIR__ . '/modules/subscriptions/SubscriptionSettingsModule.php';
$settingsModule  = new SubscriptionSettingsModule();
$alert     = null;
$redirectBase = SITE_URL . '/admin/packages';
$allowedActions = [
    'save',
    'seed_defaults',
    'delete',
    'toggle',
    'save_package_settings',
];

// ─── POST-Verarbeitung ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_packages')) {
        cms_admin_packages_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
        cms_admin_packages_redirect($redirectBase);
    } else {
        $action = trim((string)($_POST['action'] ?? ''));
        if (!in_array($action, $allowedActions, true)) {
            cms_admin_packages_flash(['type' => 'danger', 'message' => 'Unbekannte Aktion.']);
            cms_admin_packages_redirect($redirectBase);
        }

        switch ($action) {
            case 'save':
                $result = $module->save($_POST);
                cms_admin_packages_flash_result($result);
                cms_admin_packages_redirect($redirectBase);

            case 'seed_defaults':
                $result = $module->seedDefaults();
                cms_admin_packages_flash_result($result);
                cms_admin_packages_redirect($redirectBase);

            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                $result = $module->delete($id);
                cms_admin_packages_flash_result($result);
                cms_admin_packages_redirect($redirectBase);

            case 'toggle':
                $id = (int)($_POST['id'] ?? 0);
                $result = $module->toggleStatus($id);
                cms_admin_packages_flash_result($result);
                cms_admin_packages_redirect($redirectBase);

            case 'save_package_settings':
                $result = $settingsModule->savePackageSettings($_POST);
                cms_admin_packages_flash_action_result($result);
                cms_admin_packages_redirect($redirectBase);
        }
    }
}

if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_packages');
$pageTitle  = 'Pakete & Abo-Einstellungen';
$activePage = 'packages';
$data       = array_merge($module->getData(), $settingsModule->getPackageData()->toArray());

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/subscriptions/packages.php';
require_once __DIR__ . '/partials/footer.php';
