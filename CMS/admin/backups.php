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

function cms_admin_backups_target_url(): string
{
    return SITE_URL . '/admin/backups';
}

function cms_admin_backups_redirect(): never
{
    header('Location: ' . cms_admin_backups_target_url());
    exit;
}

function cms_admin_backups_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($payload['message'] ?? '')),
    ];
}

function cms_admin_backups_flash_result(array $result): void
{
    cms_admin_backups_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? 'Unbekannte Antwort.'),
    ]);
}

function cms_admin_backups_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
}

/** @return array<string, true> */
function cms_admin_backups_allowed_actions(): array
{
    return [
        'create_full' => true,
        'create_db' => true,
        'delete' => true,
    ];
}

function cms_admin_backups_normalize_action(mixed $value): ?string
{
    $action = strtolower(trim((string) $value));

    return isset(cms_admin_backups_allowed_actions()[$action]) ? $action : null;
}

function cms_admin_backups_normalize_backup_name(array $post): string
{
    $name = trim(basename((string) ($post['backup_name'] ?? '')));

    return preg_match('/^[a-z0-9][a-z0-9._-]{2,120}$/i', $name) === 1 ? $name : '';
}

/**
 * @return array<string, callable(array): array>
 */
function cms_admin_backups_action_handlers(BackupsModule $module): array
{
    return [
        'create_full' => static fn (array $post): array => $module->createFullBackup(),
        'create_db' => static fn (array $post): array => $module->createDatabaseBackup(),
        'delete' => static fn (array $post): array => $module->deleteBackup(cms_admin_backups_normalize_backup_name($post)),
    ];
}

$actionHandlers = cms_admin_backups_action_handlers($module);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = (string) ($_POST['csrf_token'] ?? '');
    if (!Security::instance()->verifyToken($postToken, 'admin_backups')) {
        cms_admin_backups_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
        cms_admin_backups_redirect();
    }

    $action = cms_admin_backups_normalize_action($_POST['action'] ?? null);
    if ($action === null) {
        cms_admin_backups_flash(['type' => 'danger', 'message' => 'Unbekannte Aktion.']);
        cms_admin_backups_redirect();
    }

    if ($action === 'delete' && cms_admin_backups_normalize_backup_name($_POST) === '') {
        cms_admin_backups_flash(['type' => 'danger', 'message' => 'Ungültiger Backup-Name.']);
        cms_admin_backups_redirect();
    }

    $handler = $actionHandlers[$action] ?? null;
    if (!is_callable($handler)) {
        cms_admin_backups_flash(['type' => 'danger', 'message' => 'Unbekannte Aktion.']);
        cms_admin_backups_redirect();
    }

    cms_admin_backups_flash_result($handler($_POST));
    cms_admin_backups_redirect();
}

$alert = cms_admin_backups_pull_alert();

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
