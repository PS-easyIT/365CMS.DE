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
$redirectUrl = SITE_URL . '/admin/legal-sites';

function cms_admin_legal_sites_redirect(string $redirectUrl): never
{
    header('Location: ' . $redirectUrl);
    exit;
}

function cms_admin_legal_sites_flash(array $result): void
{
    $_SESSION['admin_alert'] = [
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? ''),
    ];
}

function cms_admin_legal_sites_handle_action(LegalSitesModule $module, string $action, array $post, int $userId): array
{
    switch ($action) {
        case 'save':
            return $module->save($post);

        case 'save_profile':
            return $module->saveProfile($post);

        case 'generate':
            return $module->generateTemplate((string) ($post['template_type'] ?? ''));

        case 'create_page':
            return $module->createOrUpdatePage((string) ($post['template_type'] ?? ''), $userId);

        case 'create_all_pages':
            return $module->createOrUpdateAllPages($userId);
    }

    return ['success' => false, 'error' => 'Unbekannte Aktion.'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_legal_sites')) {
        $alert = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $result = cms_admin_legal_sites_handle_action($module, $action, $_POST, $userId);

        if ($action === 'save_profile') {
            if ($result['success'] ?? false) {
                unset($_SESSION['legal_sites_profile_old']);
            } elseif (!empty($result['profile']) && is_array($result['profile'])) {
                $_SESSION['legal_sites_profile_old'] = $result['profile'];
            }
        }

        cms_admin_legal_sites_flash($result);
        cms_admin_legal_sites_redirect($redirectUrl);
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
