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

const CMS_ADMIN_ANTISPAM_ALLOWED_ACTIONS = [
    'save_settings',
    'add_blacklist',
    'delete_blacklist',
];

const CMS_ADMIN_ANTISPAM_ACTION_CAPABILITIES = [
    'save_settings' => 'manage_settings',
    'add_blacklist' => 'manage_settings',
    'delete_blacklist' => 'manage_settings',
];

function cms_admin_antispam_target_url(): string
{
    return SITE_URL . '/admin/antispam';
}

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

function cms_admin_antispam_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
}

function cms_admin_antispam_normalize_action(mixed $action): string
{
    $normalizedAction = strtolower(trim((string)$action));

    return in_array($normalizedAction, CMS_ADMIN_ANTISPAM_ALLOWED_ACTIONS, true) ? $normalizedAction : '';
}

function cms_admin_antispam_normalize_positive_id(mixed $id): int
{
    $normalizedId = (int)$id;

    return $normalizedId > 0 ? $normalizedId : 0;
}

function cms_admin_antispam_can_run_action(string $action): bool
{
    $requiredCapability = CMS_ADMIN_ANTISPAM_ACTION_CAPABILITIES[$action] ?? '';
    if ($requiredCapability === '') {
        return false;
    }

    return Auth::instance()->hasCapability($requiredCapability);
}

function cms_admin_antispam_handle_action(AntispamModule $module, string $action, array $post): array
{
    switch ($action) {
        case 'save_settings':
            return $module->saveSettings($post);

        case 'add_blacklist':
            return $module->addBlacklist($post);

        case 'delete_blacklist':
            return $module->deleteBlacklist(cms_admin_antispam_normalize_positive_id($post['id'] ?? 0));
    }

    return ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $postToken = (string)($_POST['csrf_token'] ?? '');
    if (!Security::instance()->verifyToken($postToken, 'admin_antispam')) {
        cms_admin_antispam_flash(['success' => false, 'error' => 'Sicherheitstoken ungültig.']);
        cms_admin_antispam_redirect(cms_admin_antispam_target_url());
    }

    $action = cms_admin_antispam_normalize_action($_POST['action'] ?? '');
    if ($action === '') {
        cms_admin_antispam_flash(['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.']);
        cms_admin_antispam_redirect(cms_admin_antispam_target_url());
    }

    if (!cms_admin_antispam_can_run_action($action)) {
        cms_admin_antispam_flash(['success' => false, 'error' => 'Keine Berechtigung für diese AntiSpam-Aktion.']);
        cms_admin_antispam_redirect(cms_admin_antispam_target_url());
    }

    $result = cms_admin_antispam_handle_action($module, $action, $_POST);

    cms_admin_antispam_flash($result);
    cms_admin_antispam_redirect(cms_admin_antispam_target_url());
}

$alert = cms_admin_antispam_pull_alert();

$csrfToken  = Security::instance()->generateToken('admin_antispam');
$pageTitle  = 'AntiSpam';
$activePage = 'antispam';
$data       = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/security/antispam.php';
require_once __DIR__ . '/partials/footer.php';
