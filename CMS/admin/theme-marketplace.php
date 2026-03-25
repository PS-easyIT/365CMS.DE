<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Marketplace – Entry Point
 * Route: /admin/theme-marketplace
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/themes/ThemeMarketplaceModule.php';
$module = new ThemeMarketplaceModule();
$alert = null;

function cms_admin_theme_marketplace_target_url(): string
{
    return SITE_URL . '/admin/theme-marketplace';
}

function cms_admin_theme_marketplace_redirect(): never
{
    header('Location: ' . cms_admin_theme_marketplace_target_url());
    exit;
}

function cms_admin_theme_marketplace_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($payload['message'] ?? '')),
    ];
}

function cms_admin_theme_marketplace_flash_result(array $result): void
{
    cms_admin_theme_marketplace_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? 'Unbekannte Antwort.'),
    ]);
}

function cms_admin_theme_marketplace_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
}

function cms_admin_theme_marketplace_normalize_slug(array $post): string
{
    return preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($post['theme'] ?? '')) ?? '';
}

function cms_admin_theme_marketplace_handle_action(ThemeMarketplaceModule $module, string $action, array $post): array
{
    return match ($action) {
        'install' => $module->installTheme(cms_admin_theme_marketplace_normalize_slug($post)),
        default => ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'],
    };
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if (!Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'admin_theme_marketplace')) {
        cms_admin_theme_marketplace_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
    } else {
        $result = cms_admin_theme_marketplace_handle_action($module, $action, $_POST);
        cms_admin_theme_marketplace_flash_result($result);
    }

    cms_admin_theme_marketplace_redirect();
}

$alert = cms_admin_theme_marketplace_pull_alert();

$csrfToken  = Security::instance()->generateToken('admin_theme_marketplace');
$data       = $module->getData();
$pageTitle  = 'Theme Marketplace';
$activePage = 'theme-marketplace';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/themes/marketplace.php';
require __DIR__ . '/partials/footer.php';
