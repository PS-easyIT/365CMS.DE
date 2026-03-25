<?php
declare(strict_types=1);

/**
 * Plugin Marketplace – Entry Point
 * Route: /admin/plugin-marketplace
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

require_once __DIR__ . '/modules/plugins/PluginMarketplaceModule.php';
$module = new PluginMarketplaceModule();
$alert = null;

function cms_admin_plugin_marketplace_target_url(): string
{
    return SITE_URL . '/admin/plugin-marketplace';
}

function cms_admin_plugin_marketplace_redirect(): never
{
    header('Location: ' . cms_admin_plugin_marketplace_target_url());
    exit;
}

function cms_admin_plugin_marketplace_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($payload['message'] ?? '')),
    ];
}

function cms_admin_plugin_marketplace_flash_result(array $result): void
{
    cms_admin_plugin_marketplace_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? 'Unbekannte Antwort.'),
    ]);
}

function cms_admin_plugin_marketplace_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
}

/** @return array<string, true> */
function cms_admin_plugin_marketplace_allowed_actions(): array
{
    return [
        'install' => true,
    ];
}

function cms_admin_plugin_marketplace_normalize_slug(array $post): string
{
    return (string) preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($post['slug'] ?? '')));
}

function cms_admin_plugin_marketplace_handle_action(PluginMarketplaceModule $module, string $action, array $post): array
{
    return match ($action) {
        'install' => $module->installPlugin(cms_admin_plugin_marketplace_normalize_slug($post)),
        default => ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'],
    };
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $allowedActions = cms_admin_plugin_marketplace_allowed_actions();

    if (!Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'admin_plugin_mp')) {
        cms_admin_plugin_marketplace_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
    } elseif (!isset($allowedActions[$action])) {
        cms_admin_plugin_marketplace_flash(['type' => 'danger', 'message' => 'Unbekannte oder nicht erlaubte Aktion.']);
    } else {
        $result = cms_admin_plugin_marketplace_handle_action($module, $action, $_POST);
        cms_admin_plugin_marketplace_flash_result($result);
    }

    cms_admin_plugin_marketplace_redirect();
}

$alert = cms_admin_plugin_marketplace_pull_alert();

$csrfToken  = Security::instance()->generateToken('admin_plugin_mp');
$pageTitle  = 'Plugin Marketplace';
$activePage = 'plugin-marketplace';
$data       = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/plugins/marketplace.php';
require_once __DIR__ . '/partials/footer.php';
