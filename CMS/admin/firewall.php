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

function cms_admin_firewall_handle_action(FirewallModule $module, string $action, array $post): array
{
    return match ($action) {
        'save_settings' => $module->saveSettings($post),
        'add_rule' => $module->addRule($post),
        'delete_rule' => $module->deleteRule((int) ($post['id'] ?? 0)),
        'toggle_rule' => $module->toggleRule((int) ($post['id'] ?? 0)),
        default => ['success' => false, 'error' => 'Firewall-Aktion konnte nicht verarbeitet werden.'],
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));

    if (!$module->isSupportedAction($action)) {
        cms_admin_firewall_flash(['type' => 'danger', 'message' => 'Unbekannte Firewall-Aktion.']);
    } elseif (!Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'admin_firewall')) {
        cms_admin_firewall_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
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
