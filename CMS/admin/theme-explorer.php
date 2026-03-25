<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Explorer – Entry Point
 * Route: /admin/theme-explorer
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/themes/ThemeEditorModule.php';
$module = new ThemeEditorModule();
$alert  = null;

function cms_admin_theme_explorer_target_url(string $file = ''): string
{
    $targetUrl = SITE_URL . '/admin/theme-explorer';
    $file = trim($file);

    if ($file !== '') {
        $targetUrl .= '?file=' . rawurlencode($file);
    }

    return $targetUrl;
}

function cms_admin_theme_explorer_redirect(string $file = ''): never
{
    header('Location: ' . cms_admin_theme_explorer_target_url($file));
    exit;
}

function cms_admin_theme_explorer_allowed_actions(): array
{
    return ['save_file'];
}

function cms_admin_theme_explorer_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($payload['message'] ?? '')),
    ];
}

function cms_admin_theme_explorer_flash_result(array $result): void
{
    cms_admin_theme_explorer_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? 'Unbekannte Antwort.'),
    ]);
}

function cms_admin_theme_explorer_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
}

function cms_admin_theme_explorer_handle_action(ThemeEditorModule $module, string $action, array $post): array
{
    return match ($action) {
        'save_file' => $module->saveFile((string) ($post['file'] ?? ''), (string) ($post['content'] ?? '')),
        default => ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'],
    };
}

// Datei speichern
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $postToken = (string)($_POST['csrf_token'] ?? '');
    if (!Security::instance()->verifyToken($postToken, 'admin_theme_explorer')) {
        cms_admin_theme_explorer_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
        cms_admin_theme_explorer_redirect();
    }

    $action = (string)($_POST['action'] ?? '');
    if (!in_array($action, cms_admin_theme_explorer_allowed_actions(), true)) {
        cms_admin_theme_explorer_flash(['type' => 'danger', 'message' => 'Unbekannte oder nicht erlaubte Aktion.']);
        cms_admin_theme_explorer_redirect();
    }

    $file = (string)($_POST['file'] ?? '');
    $result = cms_admin_theme_explorer_handle_action($module, $action, $_POST);
    cms_admin_theme_explorer_flash_result($result);
    cms_admin_theme_explorer_redirect($file);
}

$alert = cms_admin_theme_explorer_pull_alert();

$csrfToken    = Security::instance()->generateToken('admin_theme_explorer');
$currentFile  = (string)($_GET['file'] ?? '');
$data         = $module->getData($currentFile);
$pageTitle    = 'Theme Explorer';
$activePage   = 'theme-explorer';
$pageAssets   = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/themes/editor.php';
require __DIR__ . '/partials/footer.php';
