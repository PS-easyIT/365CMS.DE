<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Font Manager – Entry Point
 * Route: /admin/font-manager
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/themes/FontManagerModule.php';
$module    = new FontManagerModule();
$alert     = null;

function cms_admin_font_manager_target_url(): string
{
    return SITE_URL . '/admin/font-manager';
}

function cms_admin_font_manager_redirect(): never
{
    header('Location: ' . cms_admin_font_manager_target_url());
    exit;
}

function cms_admin_font_manager_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($payload['message'] ?? '')),
    ];
}

function cms_admin_font_manager_flash_result(array $result): void
{
    cms_admin_font_manager_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? ''),
    ]);
}

function cms_admin_font_manager_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
}

/**
 * @return array<string, callable(array): array>
 */
function cms_admin_font_manager_action_handlers(FontManagerModule $module): array
{
    return [
        'save' => static fn (array $post): array => $module->saveSettings($post),
        'scan_theme_fonts' => static fn (array $post): array => $module->scanThemeFonts(),
        'delete_font' => static fn (array $post): array => $module->deleteCustomFont((int) ($post['font_id'] ?? 0)),
        'download_google_font' => static fn (array $post): array => $module->downloadGoogleFont(trim((string) ($post['google_font_family'] ?? ''))),
        'download_detected_fonts' => static fn (array $post): array => $module->downloadDetectedFonts(),
    ];
}

$actionHandlers = cms_admin_font_manager_action_handlers($module);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = (string) ($_POST['csrf_token'] ?? '');
    if (!Security::instance()->verifyToken($postToken, 'admin_font_manager')) {
        cms_admin_font_manager_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
        cms_admin_font_manager_redirect();
    }

    $action = trim((string) ($_POST['action'] ?? ''));
    if (!isset($actionHandlers[$action])) {
        cms_admin_font_manager_flash(['type' => 'danger', 'message' => 'Unbekannte Aktion.']);
        cms_admin_font_manager_redirect();
    }

    cms_admin_font_manager_flash_result($actionHandlers[$action]($_POST));
    cms_admin_font_manager_redirect();
}

$alert = cms_admin_font_manager_pull_alert();

$csrfToken  = Security::instance()->generateToken('admin_font_manager');
$data       = $module->getData();
$pageTitle  = 'Font Manager';
$activePage = 'font-manager';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/themes/fonts.php';
require __DIR__ . '/partials/footer.php';
