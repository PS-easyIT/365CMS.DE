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

// Tab
$tab = preg_replace('/[^a-z]/', '', $_GET['tab'] ?? 'header');
$validTabs = ['header', 'content', 'footer', 'design', 'plugins'];
if (!in_array($tab, $validTabs, true)) {
    $tab = 'header';
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
        'type'    => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? ''),
    ];
}

function cms_admin_landing_page_handle_action(LandingPageModule $module, string $action, array $post): array
{
    if ($action === 'save_header') {
        return $module->saveHeader($post);
    }

    if ($action === 'save_content') {
        return $module->saveContent($post);
    }

    if ($action === 'save_footer') {
        return $module->saveFooter($post);
    }

    if ($action === 'save_design') {
        return $module->saveDesign($post);
    }

    if ($action === 'save_feature') {
        return $module->saveFeature($post);
    }

    if ($action === 'delete_feature') {
        return $module->deleteFeature((int) ($post['feature_id'] ?? 0));
    }

    if ($action === 'save_plugin') {
        return $module->savePlugin($post);
    }

    return ['success' => false, 'error' => 'Unbekannte Aktion.'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!Security::instance()->verifyToken($postToken, 'admin_landing_page')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        cms_admin_landing_page_redirect(cms_admin_landing_page_tab_url($redirectBase, $tab));
    }

    $action = $_POST['action'] ?? '';
    $result = cms_admin_landing_page_handle_action($module, (string) $action, $_POST);
    cms_admin_landing_page_flash($result);
    cms_admin_landing_page_redirect(cms_admin_landing_page_tab_url($redirectBase, $tab));
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_landing_page');
$data       = $module->getData($tab);
$pageTitle  = 'Landing Page';
$activePage = 'landing-page';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/landing/page.php';
require __DIR__ . '/partials/footer.php';
