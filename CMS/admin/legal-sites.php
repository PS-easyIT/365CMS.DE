<?php
declare(strict_types=1);

/**
 * Rechtliche Seiten – Entry Point
 * Route: /admin/legal-sites
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

require_once __DIR__ . '/modules/legal/LegalSitesModule.php';
$module    = new LegalSitesModule();
$alert     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_legal_sites')) {
        $alert = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
    } else {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'save':
                $result = $module->save($_POST);
                break;
            case 'generate':
                $result = $module->generateTemplate($_POST['template_type'] ?? '');
                break;
            default:
                $result = ['success' => false, 'error' => 'Unbekannte Aktion.'];
        }
        $_SESSION['admin_alert'] = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message'] ?? $result['error'] ?? ''];
        header('Location: ' . SITE_URL . '/admin/legal-sites');
        exit;
    }
    $csrfToken = Security::instance()->generateToken('admin_legal_sites');
}

if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_legal_sites');
$pageTitle  = 'Rechtliche Seiten';
$activePage = 'legal-sites';
$data       = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/legal/sites.php';
require_once __DIR__ . '/partials/footer.php';
