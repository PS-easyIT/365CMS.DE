<?php
declare(strict_types=1);

/**
 * Firewall – Entry Point
 * Route: /admin/firewall
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

const CMS_ADMIN_FIREWALL_ALLOWED_ACTIONS = [
    'save_settings',
    'add_rule',
    'delete_rule',
    'toggle_rule',
];

const CMS_ADMIN_FIREWALL_ACTION_CAPABILITIES = [
    'save_settings' => 'manage_settings',
    'add_rule' => 'manage_settings',
    'delete_rule' => 'manage_settings',
    'toggle_rule' => 'manage_settings',
];

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/security/FirewallModule.php';
$module = new FirewallModule();
$alert = null;

function cms_admin_firewall_target_url(): string
{
    return SITE_URL . '/admin/firewall';
}

function cms_admin_firewall_redirect(): never
{
    header('Location: ' . cms_admin_firewall_target_url());
    exit;
}

function cms_admin_firewall_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($payload['message'] ?? '')),
    ];
}

function cms_admin_firewall_flash_result(array $result): void
{
    cms_admin_firewall_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? ''),
    ]);
}

function cms_admin_firewall_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
}

function cms_admin_firewall_normalize_action(mixed $action): string
{
    $normalizedAction = strtolower(trim((string)$action));

    return in_array($normalizedAction, CMS_ADMIN_FIREWALL_ALLOWED_ACTIONS, true) ? $normalizedAction : '';
}

function cms_admin_firewall_normalize_positive_id(mixed $id): int
{
    $normalizedId = (int)$id;

    return $normalizedId > 0 ? $normalizedId : 0;
}

function cms_admin_firewall_can_run_action(string $action): bool
{
    $requiredCapability = CMS_ADMIN_FIREWALL_ACTION_CAPABILITIES[$action] ?? '';
    if ($requiredCapability === '') {
        return false;
    }

    return Auth::instance()->hasCapability($requiredCapability);
}

function cms_admin_firewall_handle_action(FirewallModule $module, string $action, array $post): array
{
    return match ($action) {
        'save_settings' => $module->saveSettings($post),
        'add_rule' => $module->addRule($post),
        'delete_rule' => $module->deleteRule(cms_admin_firewall_normalize_positive_id($post['id'] ?? 0)),
        'toggle_rule' => $module->toggleRule(cms_admin_firewall_normalize_positive_id($post['id'] ?? 0)),
        default => ['success' => false, 'error' => 'Firewall-Aktion konnte nicht verarbeitet werden.'],
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = cms_admin_firewall_normalize_action($_POST['action'] ?? '');

    if ($action === '' || !$module->isSupportedAction($action)) {
        cms_admin_firewall_flash(['type' => 'danger', 'message' => 'Unbekannte Firewall-Aktion.']);
    } elseif (!Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'admin_firewall')) {
        cms_admin_firewall_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
    } elseif (!cms_admin_firewall_can_run_action($action)) {
        cms_admin_firewall_flash(['type' => 'danger', 'message' => 'Keine Berechtigung für diese Firewall-Aktion.']);
    } elseif (in_array($action, ['delete_rule', 'toggle_rule'], true) && cms_admin_firewall_normalize_positive_id($_POST['id'] ?? 0) <= 0) {
        cms_admin_firewall_flash(['type' => 'danger', 'message' => 'Ungültige Firewall-Regel-ID.']);
    } else {
        $result = cms_admin_firewall_handle_action($module, $action, $_POST);
        cms_admin_firewall_flash_result($result);
    }

    cms_admin_firewall_redirect();
}

$alert = cms_admin_firewall_pull_alert();

$csrfToken  = Security::instance()->generateToken('admin_firewall');
$pageTitle  = 'Firewall';
$activePage = 'firewall';
$data       = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/security/firewall.php';
require_once __DIR__ . '/partials/footer.php';
