<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Updates – Entry Point
 * Route: /admin/updates
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/system/UpdatesModule.php';
$module    = new UpdatesModule();
$alert     = null;

function cms_admin_updates_target_url(): string
{
    return SITE_URL . '/admin/updates';
}

function cms_admin_updates_redirect(): never
{
    header('Location: ' . cms_admin_updates_target_url());
    exit;
}

function cms_admin_updates_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : (($payload['type'] ?? 'danger') === 'info' ? 'info' : 'danger'),
        'message' => trim((string) ($payload['message'] ?? '')),
    ];
}

function cms_admin_updates_flash_result(array $result, string $fallbackMessage): void
{
    cms_admin_updates_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? $fallbackMessage),
    ]);
}

function cms_admin_updates_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
}

function cms_admin_updates_store_snapshot(array $snapshot): void
{
    $_SESSION['admin_updates_snapshot'] = $snapshot;
}

function cms_admin_updates_pull_snapshot(): ?array
{
    $snapshot = $_SESSION['admin_updates_snapshot'] ?? null;
    unset($_SESSION['admin_updates_snapshot']);

    return is_array($snapshot) ? $snapshot : null;
}

/** @return array<string, true> */
function cms_admin_updates_allowed_actions(): array
{
    return [
        'check_updates' => true,
        'install_core' => true,
        'install_plugin' => true,
    ];
}

function cms_admin_updates_normalize_plugin_slug(array $post): string
{
    return (string) preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($post['plugin_slug'] ?? '')));
}

function cms_admin_updates_handle_action(UpdatesModule $module, string $action, array $post): array
{
    return match ($action) {
        'check_updates' => ['success' => true, 'type' => 'info', 'message' => 'Update-Prüfung abgeschlossen.', 'callback' => static function () use ($module): void {
            cms_admin_updates_store_snapshot($module->checkAllUpdates());
        }],
        'install_core' => $module->installCoreUpdate(),
        'install_plugin' => $module->installPluginUpdate(cms_admin_updates_normalize_plugin_slug($post)),
        default => ['success' => false, 'error' => 'Ungültige Update-Aktion.'],
    };
}

$allowedActions = cms_admin_updates_allowed_actions();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = (string) ($_POST['csrf_token'] ?? '');
    if (!Security::instance()->verifyToken($postToken, 'admin_updates')) {
        cms_admin_updates_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
        cms_admin_updates_redirect();
    }

    $action = trim((string) ($_POST['action'] ?? ''));

    if (!isset($allowedActions[$action])) {
        cms_admin_updates_flash(['type' => 'danger', 'message' => 'Ungültige Update-Aktion.']);
        cms_admin_updates_redirect();
    }

    $result = cms_admin_updates_handle_action($module, $action, $_POST);
    $callback = $result['callback'] ?? null;
    if (is_callable($callback)) {
        $callback();
        cms_admin_updates_flash([
            'type' => (string) ($result['type'] ?? 'info'),
            'message' => (string) ($result['message'] ?? 'Update-Prüfung abgeschlossen.'),
        ]);
    } else {
        $fallbackMessage = $action === 'install_core'
            ? 'Core-Update konnte nicht verarbeitet werden.'
            : 'Plugin-Update konnte nicht verarbeitet werden.';

        cms_admin_updates_flash_result($result, $fallbackMessage);
    }

    cms_admin_updates_redirect();
}

$alert = cms_admin_updates_pull_alert();
$snapshot = cms_admin_updates_pull_snapshot();

if (is_array($snapshot)) {
    $module->hydrateUpdateSnapshot($snapshot);
}

$csrfToken  = Security::instance()->generateToken('admin_updates');
$data       = $module->getData();
$pageTitle  = 'Updates';
$activePage = 'updates';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
defined('CMS_ADMIN_SYSTEM_VIEW') || define('CMS_ADMIN_SYSTEM_VIEW', true);
require __DIR__ . '/views/system/updates.php';
require __DIR__ . '/partials/footer.php';
