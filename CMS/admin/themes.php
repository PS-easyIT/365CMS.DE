<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Themes – Entry Point
 * Route: /admin/themes
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/themes/ThemesModule.php';
$module    = new ThemesModule();
$alert     = null;

function cms_admin_themes_target_url(): string
{
    return SITE_URL . '/admin/themes';
}

function cms_admin_themes_redirect(): never
{
    header('Location: ' . cms_admin_themes_target_url());
    exit;
}

function cms_admin_themes_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($payload['message'] ?? '')),
    ];
}

function cms_admin_themes_flash_result(array $result): void
{
    cms_admin_themes_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? ''),
    ]);
}

function cms_admin_themes_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
}

/** @return array<string, true> */
function cms_admin_themes_allowed_actions(): array
{
    return [
        'activate' => true,
        'delete' => true,
    ];
}

function cms_admin_themes_normalize_slug(array $post): string
{
    return preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($post['theme'] ?? '')) ?? '';
}

function cms_admin_themes_handle_action(ThemesModule $module, string $action, array $post): array
{
    $slug = cms_admin_themes_normalize_slug($post);

    return match ($action) {
        'activate' => $module->activateTheme($slug),
        'delete' => $module->deleteTheme($slug),
        default => ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'],
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = (string) ($_POST['csrf_token'] ?? '');
    $action = trim((string) ($_POST['action'] ?? ''));
    $allowedActions = cms_admin_themes_allowed_actions();

    if (!Security::instance()->verifyToken($postToken, 'admin_themes')) {
        cms_admin_themes_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
    } elseif (!isset($allowedActions[$action])) {
        cms_admin_themes_flash(['type' => 'danger', 'message' => 'Unbekannte oder nicht erlaubte Aktion.']);
    } else {
        cms_admin_themes_flash_result(cms_admin_themes_handle_action($module, $action, $_POST));
    }

    cms_admin_themes_redirect();
}

$alert = cms_admin_themes_pull_alert();

$csrfToken  = Security::instance()->generateToken('admin_themes');
$data       = $module->getData();
$pageTitle  = 'Themes';
$activePage = 'themes';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/themes/list.php';
require __DIR__ . '/partials/footer.php';
