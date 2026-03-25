<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Backup & Restore – Entry Point
 * Route: /admin/backups
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/system/BackupsModule.php';
$module    = new BackupsModule();
$alert     = null;
$redirectUrl = SITE_URL . '/admin/backups';

function cms_admin_backups_redirect(string $redirectUrl): never
{
    header('Location: ' . $redirectUrl);
    exit;
}

function cms_admin_backups_flash(array $result): void
{
    $_SESSION['admin_alert'] = [
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? 'Unbekannte Antwort.'),
    ];
}

$actionHandlers = [
    'create_full' => static fn () => $module->createFullBackup(),
    'create_db' => static fn () => $module->createDatabaseBackup(),
    'delete' => static fn () => $module->deleteBackup((string) ($_POST['backup_name'] ?? '')),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = (string) ($_POST['csrf_token'] ?? '');
    if (!Security::instance()->verifyToken($postToken, 'admin_backups')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        cms_admin_backups_redirect($redirectUrl);
    }

    $action = (string) ($_POST['action'] ?? '');
    $handler = $actionHandlers[$action] ?? null;
    $result = is_callable($handler)
        ? $handler()
        : ['success' => false, 'error' => 'Unbekannte Aktion.'];

    cms_admin_backups_flash($result);
    cms_admin_backups_redirect($redirectUrl);
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_backups');
$data       = $module->getData();
$pageTitle  = 'Backup & Restore';
$activePage = 'backups';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
defined('CMS_ADMIN_SYSTEM_VIEW') || define('CMS_ADMIN_SYSTEM_VIEW', true);
require __DIR__ . '/views/system/backups.php';
require __DIR__ . '/partials/footer.php';
