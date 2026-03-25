<?php
declare(strict_types=1);

/**
 * AntiSpam – Entry Point
 * Route: /admin/antispam
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

require_once __DIR__ . '/modules/security/AntispamModule.php';
$module      = new AntispamModule();
$alert       = null;
$redirectUrl = SITE_URL . '/admin/antispam';
$allowedActions = [
    'save_settings',
    'add_blacklist',
    'delete_blacklist',
];

function cms_admin_antispam_redirect(string $redirectUrl): never
{
    header('Location: ' . $redirectUrl);
    exit;
}

function cms_admin_antispam_flash(array $result): void
{
    $_SESSION['admin_alert'] = [
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? 'Unbekannte Antwort.'),
    ];
}

function cms_admin_antispam_handle_action(AntispamModule $module, string $action, array $post): array
{
    switch ($action) {
        case 'save_settings':
            return $module->saveSettings($post);

        case 'add_blacklist':
            return $module->addBlacklist($post);

        case 'delete_blacklist':
            return $module->deleteBlacklist((int) ($post['id'] ?? 0));
    }

    return ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $postToken = (string)($_POST['csrf_token'] ?? '');
    if (!Security::instance()->verifyToken($postToken, 'admin_antispam')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        cms_admin_antispam_redirect($redirectUrl);
    }

    $action = (string)($_POST['action'] ?? '');
    if (!in_array($action, $allowedActions, true)) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Unbekannte oder nicht erlaubte Aktion.'];
        cms_admin_antispam_redirect($redirectUrl);
    }

    $result = cms_admin_antispam_handle_action($module, $action, $_POST);

    cms_admin_antispam_flash($result);
    cms_admin_antispam_redirect($redirectUrl);
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_antispam');
$pageTitle  = 'AntiSpam';
$activePage = 'antispam';
$data       = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/security/antispam.php';
require_once __DIR__ . '/partials/footer.php';
