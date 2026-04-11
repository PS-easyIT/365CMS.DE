<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Table of Contents – Entry Point
 * Route: /admin/table-of-contents
 */

use CMS\Auth;
use CMS\Security;

const CMS_ADMIN_TOC_CAPABILITY = 'manage_settings';

function cms_admin_toc_can_access(): bool
{
    return Auth::instance()->isAdmin()
        && Auth::instance()->hasCapability(CMS_ADMIN_TOC_CAPABILITY);
}

function cms_admin_toc_redirect(): never
{
    header('Location: /admin/table-of-contents');
    exit;
}

if (!cms_admin_toc_can_access()) {
    header('Location: /');
    exit;
}

require_once __DIR__ . '/modules/toc/TocModule.php';
$module    = new TocModule();
$alert     = null;

// ─── POST-Handling ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $postToken = $_POST['csrf_token'] ?? '';

    if (!Security::instance()->verifyToken($postToken, 'admin_toc')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        cms_admin_toc_redirect();
    }

    if ($action === 'save') {
        $result = $module->saveSettings($_POST);
        $_SESSION['admin_alert'] = [
            'type'    => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? '',
        ];
        cms_admin_toc_redirect();
    }
}

// ─── Session-Alert abholen ───────────────────────────────
if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken = Security::instance()->generateToken('admin_toc');

// ─── View ────────────────────────────────────────────────
$settings   = $module->getSettings();
$pageTitle  = 'Inhaltsverzeichnis';
$activePage = 'table-of-contents';

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/toc/settings.php';
require __DIR__ . '/partials/footer.php';
