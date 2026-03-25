<?php
declare(strict_types=1);

/**
 * Plugins – Entry Point
 * Route: /admin/plugins
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

require_once __DIR__ . '/modules/plugins/PluginsModule.php';
$module = new PluginsModule();
$alert  = null;

function cms_admin_plugins_target_url(): string
{
    return SITE_URL . '/admin/plugins';
}

function cms_admin_plugins_redirect(): never
{
    header('Location: ' . cms_admin_plugins_target_url());
    exit;
}

function cms_admin_plugins_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($payload['message'] ?? '')),
    ];
}

function cms_admin_plugins_flash_result(array $result): void
{
    cms_admin_plugins_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? ''),
    ]);
}

function cms_admin_plugins_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
}

/** @return array<string, true> */
function cms_admin_plugins_allowed_actions(): array
{
    return [
        'activate' => true,
        'deactivate' => true,
        'delete' => true,
    ];
}

function cms_admin_plugins_normalize_slug(array $post): string
{
    return (string) preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($post['slug'] ?? '')));
}

function cms_admin_plugins_handle_action(PluginsModule $module, string $action, array $post): array
{
    return match ($action) {
        'activate' => $module->activatePlugin(cms_admin_plugins_normalize_slug($post)),
        'deactivate' => $module->deactivatePlugin(cms_admin_plugins_normalize_slug($post)),
        'delete' => $module->deletePlugin(cms_admin_plugins_normalize_slug($post)),
        default => ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'],
    };
}

// POST-Handler: Token ZUERST verifizieren, bevor ein neuer generiert wird
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));
    $allowedActions = cms_admin_plugins_allowed_actions();

    if (!Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'admin_plugins')) {
        cms_admin_plugins_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
    } elseif (!isset($allowedActions[$action])) {
        cms_admin_plugins_flash(['type' => 'danger', 'message' => 'Unbekannte oder nicht erlaubte Aktion.']);
    } else {
        cms_admin_plugins_flash_result(cms_admin_plugins_handle_action($module, $action, $_POST));
    }

    cms_admin_plugins_redirect();
}

$alert = cms_admin_plugins_pull_alert();

// Token NACH dem POST-Handler generieren (für das Formular-Rendering)
$csrfToken = Security::instance()->generateToken('admin_plugins');

$pageTitle  = 'Plugins';
$activePage = 'plugins';
$data       = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/plugins/list.php';
require_once __DIR__ . '/partials/footer.php';
