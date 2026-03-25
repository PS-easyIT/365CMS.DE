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

const CMS_ADMIN_PACKAGES_ALLOWED_ACTIONS = [
    'save',
    'seed_defaults',
    'delete',
    'toggle',
    'save_package_settings',
];

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

function cms_admin_packages_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
}

function cms_admin_packages_normalize_action(mixed $action): string
{
    $normalizedAction = strtolower(trim((string)$action));

    return in_array($normalizedAction, CMS_ADMIN_PACKAGES_ALLOWED_ACTIONS, true) ? $normalizedAction : '';
}

function cms_admin_packages_normalize_positive_id(mixed $id): int
{
    $normalizedId = (int)$id;

    return $normalizedId > 0 ? $normalizedId : 0;
}

/**
 * @return array<string, callable(array): void>
 */
function cms_admin_packages_action_handlers(PackagesModule $module, SubscriptionSettingsModule $settingsModule): array
{
    return [
        'save' => static function (array $post) use ($module): void {
            cms_admin_packages_flash_result($module->save($post));
        },
        'seed_defaults' => static function (array $post) use ($module): void {
            cms_admin_packages_flash_result($module->seedDefaults());
        },
        'delete' => static function (array $post) use ($module): void {
            cms_admin_packages_flash_result($module->delete(cms_admin_packages_normalize_positive_id($post['id'] ?? 0)));
        },
        'toggle' => static function (array $post) use ($module): void {
            cms_admin_packages_flash_result($module->toggleStatus(cms_admin_packages_normalize_positive_id($post['id'] ?? 0)));
        },
        'save_package_settings' => static function (array $post) use ($settingsModule): void {
            cms_admin_packages_flash_action_result($settingsModule->savePackageSettings($post));
        },
    ];
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
$actionHandlers = cms_admin_packages_action_handlers($module, $settingsModule);

// ─── POST-Verarbeitung ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'admin_packages')) {
        cms_admin_packages_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
        cms_admin_packages_redirect($redirectBase);
    }

    $action = cms_admin_packages_normalize_action($_POST['action'] ?? '');
    if ($action === '' || !isset($actionHandlers[$action])) {
        cms_admin_packages_flash(['type' => 'danger', 'message' => 'Unbekannte Aktion.']);
        cms_admin_packages_redirect($redirectBase);
    }

    if (in_array($action, ['delete', 'toggle'], true) && cms_admin_packages_normalize_positive_id($_POST['id'] ?? 0) <= 0) {
        cms_admin_packages_flash(['type' => 'danger', 'message' => 'Ungültige Paket-ID.']);
        cms_admin_packages_redirect($redirectBase);
    }

    $actionHandlers[$action]($_POST);
    cms_admin_packages_redirect($redirectBase);
}

$alert = cms_admin_packages_pull_alert();

$csrfToken  = Security::instance()->generateToken('admin_packages');
$pageTitle  = 'Pakete & Abo-Einstellungen';
$activePage = 'packages';
$data       = array_merge($module->getData(), $settingsModule->getPackageData()->toArray());

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/subscriptions/packages.php';
require_once __DIR__ . '/partials/footer.php';
