<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Landing Page – Entry Point
 * Route: /admin/landing-page
 * Tabs: header, content, footer, design, plugins
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/landing/LandingPageModule.php';
$module    = new LandingPageModule();
$alert     = null;
$redirectBase = SITE_URL . '/admin/landing-page';

function cms_admin_landing_page_allowed_tabs(): array
{
    return ['header', 'content', 'footer', 'design', 'plugins'];
}

function cms_admin_landing_page_normalize_tab(string $tab): string
{
    $normalizedTab = preg_replace('/[^a-z]/', '', $tab);

    return in_array($normalizedTab, cms_admin_landing_page_allowed_tabs(), true) ? $normalizedTab : 'header';
}

function cms_admin_landing_page_redirect(string $redirectUrl): never
{
    header('Location: ' . $redirectUrl);
    exit;
}

function cms_admin_landing_page_tab_url(string $redirectBase, string $tab): string
{
    return $redirectBase . '?tab=' . rawurlencode($tab);
}

function cms_admin_landing_page_flash(array $result): void
{
    $_SESSION['admin_alert'] = [
        'type'    => ($result['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($result['message'] ?? '')),
    ];
}

function cms_admin_landing_page_flash_result(array $result): void
{
    cms_admin_landing_page_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? ''),
    ]);
}

function cms_admin_landing_page_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
}

/**
 * @return array<string, callable(array): array>
 */
function cms_admin_landing_page_action_handlers(LandingPageModule $module): array
{
    return [
        'save_header' => static fn (array $post): array => $module->saveHeader($post),
        'save_content' => static fn (array $post): array => $module->saveContent($post),
        'save_footer' => static fn (array $post): array => $module->saveFooter($post),
        'save_design' => static fn (array $post): array => $module->saveDesign($post),
        'save_feature' => static fn (array $post): array => $module->saveFeature($post),
        'delete_feature' => static fn (array $post): array => $module->deleteFeature((int) ($post['feature_id'] ?? 0)),
        'save_plugin' => static fn (array $post): array => $module->savePlugin($post),
    ];
}

$tab = cms_admin_landing_page_normalize_tab((string) ($_GET['tab'] ?? 'header'));
$actionHandlers = cms_admin_landing_page_action_handlers($module);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = (string) ($_POST['csrf_token'] ?? '');
    if (!Security::instance()->verifyToken($postToken, 'admin_landing_page')) {
        cms_admin_landing_page_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
        cms_admin_landing_page_redirect(cms_admin_landing_page_tab_url($redirectBase, $tab));
    }

    $action = trim((string) ($_POST['action'] ?? ''));
    if (!isset($actionHandlers[$action])) {
        cms_admin_landing_page_flash(['type' => 'danger', 'message' => 'Unbekannte Aktion.']);
        cms_admin_landing_page_redirect(cms_admin_landing_page_tab_url($redirectBase, $tab));
    }

    cms_admin_landing_page_flash_result($actionHandlers[$action]($_POST));
    cms_admin_landing_page_redirect(cms_admin_landing_page_tab_url($redirectBase, $tab));
}

$alert = cms_admin_landing_page_pull_alert();

$csrfToken  = Security::instance()->generateToken('admin_landing_page');
$data       = $module->getData($tab);
$pageTitle  = 'Landing Page';
$activePage = 'landing-page';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/landing/page.php';
require __DIR__ . '/partials/footer.php';
