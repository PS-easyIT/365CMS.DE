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
 * @return list<string>
 */
function cms_admin_font_manager_allowed_actions(): array
{
    return [
        'save',
        'scan_theme_fonts',
        'delete_font',
        'download_google_font',
        'download_detected_fonts',
    ];
}

function cms_admin_font_manager_normalize_action(string $action): ?string
{
    $action = trim($action);

    return in_array($action, cms_admin_font_manager_allowed_actions(), true) ? $action : null;
}

function cms_admin_font_manager_normalize_font_id(array $post): int
{
    return max(0, (int) ($post['font_id'] ?? 0));
}

function cms_admin_font_manager_normalize_google_font_family(array $post): string
{
    $fontFamily = trim((string) ($post['google_font_family'] ?? ''));
    $fontFamily = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $fontFamily) ?? '';
    $fontFamily = preg_replace('/\s+/u', ' ', $fontFamily) ?? '';

    if (function_exists('mb_substr')) {
        return trim(mb_substr($fontFamily, 0, 120));
    }

    return trim(substr($fontFamily, 0, 120));
}

/**
 * @return array<string, callable(array): array>
 */
function cms_admin_font_manager_action_handlers(FontManagerModule $module): array
{
    return [
        'save' => static fn (array $post): array => $module->saveSettings($post),
        'scan_theme_fonts' => static fn (array $post): array => $module->scanThemeFonts(),
        'delete_font' => static fn (array $post): array => $module->deleteCustomFont(cms_admin_font_manager_normalize_font_id($post)),
        'download_google_font' => static fn (array $post): array => $module->downloadGoogleFont(cms_admin_font_manager_normalize_google_font_family($post)),
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

    $action = cms_admin_font_manager_normalize_action((string) ($_POST['action'] ?? ''));
    if ($action === null || !isset($actionHandlers[$action])) {
        cms_admin_font_manager_flash(['type' => 'danger', 'message' => 'Unbekannte oder nicht erlaubte Aktion.']);
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
