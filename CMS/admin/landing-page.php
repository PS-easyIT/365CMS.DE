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

// Tab
$tab = preg_replace('/[^a-z]/', '', $_GET['tab'] ?? 'header');
$validTabs = ['header', 'content', 'footer', 'design', 'plugins'];
if (!in_array($tab, $validTabs, true)) {
    $tab = 'header';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!Security::instance()->verifyToken($postToken, 'admin_landing_page')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . '/admin/landing-page?tab=' . $tab);
        exit;
    }

    $action = $_POST['action'] ?? '';
    $result = ['success' => false, 'error' => 'Unbekannte Aktion.'];

    if ($action === 'save_header') {
        $result = $module->saveHeader($_POST);
    } elseif ($action === 'save_content') {
        $result = $module->saveContent($_POST);
    } elseif ($action === 'save_footer') {
        $result = $module->saveFooter($_POST);
    } elseif ($action === 'save_design') {
        $result = $module->saveDesign($_POST);
    } elseif ($action === 'save_feature') {
        $result = $module->saveFeature($_POST);
    } elseif ($action === 'delete_feature') {
        $featureId = (int)($_POST['feature_id'] ?? 0);
        $result = $module->deleteFeature($featureId);
    } elseif ($action === 'save_plugin') {
        $result = $module->savePlugin($_POST);
    }

    $_SESSION['admin_alert'] = [
        'type'    => $result['success'] ? 'success' : 'danger',
        'message' => $result['message'] ?? $result['error'] ?? '',
    ];

    header('Location: ' . SITE_URL . '/admin/landing-page?tab=' . $tab);
    exit;
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
