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
$userId    = (int)(Auth::instance()->getCurrentUser()->id ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_legal_sites')) {
        $alert = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
    } else {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'save':
                $result = $module->save($_POST);
                break;
            case 'save_profile':
                $result = $module->saveProfile($_POST);
                break;
            case 'generate':
                $result = $module->generateTemplate($_POST['template_type'] ?? '');
                break;
            case 'create_page':
                $result = $module->createOrUpdatePage($_POST['template_type'] ?? '', $userId);
                break;
            case 'create_all_pages':
                $result = $module->createOrUpdateAllPages($userId);
                break;
            default:
                $result = ['success' => false, 'error' => 'Unbekannte Aktion.'];
        }

        if ($action === 'save_profile') {
            if ($result['success'] ?? false) {
                unset($_SESSION['legal_sites_profile_old']);
            } elseif (!empty($result['profile']) && is_array($result['profile'])) {
                $_SESSION['legal_sites_profile_old'] = $result['profile'];
            }
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
$pageTitle  = 'Legal Sites';
$activePage = 'legal-sites';
$data       = $module->getData();

if (!empty($_SESSION['legal_sites_profile_old']) && is_array($_SESSION['legal_sites_profile_old'])) {
    $data['profile'] = array_merge($data['profile'] ?? [], $_SESSION['legal_sites_profile_old']);
    unset($_SESSION['legal_sites_profile_old']);
}

$data['templates'] = [
    'imprint'    => $module->getTemplateContent('imprint'),
    'privacy'    => $module->getTemplateContent('privacy'),
    'terms'      => $module->getTemplateContent('terms'),
    'revocation' => $module->getTemplateContent('revocation'),
];

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/legal/sites.php';
require_once __DIR__ . '/partials/footer.php';
