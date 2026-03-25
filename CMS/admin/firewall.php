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
$redirectUrl = SITE_URL . '/admin/firewall';

function cms_admin_firewall_redirect(string $redirectUrl): never
{
    header('Location: ' . $redirectUrl);
    exit;
}

function cms_admin_firewall_flash(array $result): void
{
    $_SESSION['admin_alert'] = [
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? ''),
    ];
}

function cms_admin_firewall_handle_action(FirewallModule $module, string $action, array $post): array
{
    switch ($action) {
        case 'save_settings':
            return $module->saveSettings($post);

        case 'add_rule':
            return $module->addRule($post);

        case 'delete_rule':
            return $module->deleteRule((int) ($post['id'] ?? 0));

        case 'toggle_rule':
            return $module->toggleRule((int) ($post['id'] ?? 0));
    }

    return ['success' => false, 'error' => 'Firewall-Aktion konnte nicht verarbeitet werden.'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));

    if (!$module->isSupportedAction($action)) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Unbekannte Firewall-Aktion.'];
    } elseif (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_firewall')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
    } else {
        $result = cms_admin_firewall_handle_action($module, $action, $_POST);
        cms_admin_firewall_flash($result);
    }

    cms_admin_firewall_redirect($redirectUrl);
}

if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_firewall');
$pageTitle  = 'Firewall';
$activePage = 'firewall';
$data       = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/security/firewall.php';
require_once __DIR__ . '/partials/footer.php';
