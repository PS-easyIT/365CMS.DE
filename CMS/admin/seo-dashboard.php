<?php
declare(strict_types=1);

/**
 * SEO Dashboard – Entry Point
 * Route: /admin/seo-dashboard
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

require_once __DIR__ . '/modules/seo/SeoDashboardModule.php';
$module    = new SeoDashboardModule();
$alert     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_seo')) {
        $alert = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'regenerate_sitemap') {
            $result = $module->regenerateSitemap();
            $_SESSION['admin_alert'] = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message'] ?? $result['error'] ?? ''];
            header('Location: ' . SITE_URL . '/admin/seo-dashboard');
            exit;
        }
    }
    $csrfToken = Security::instance()->generateToken('admin_seo');
}

if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_seo');
$pageTitle  = 'SEO Dashboard';
$activePage = 'seo-dashboard';
$data       = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/seo/dashboard.php';
require_once __DIR__ . '/partials/footer.php';
