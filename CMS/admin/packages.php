<?php
declare(strict_types=1);

/**
 * Abo-Pakete – Entry Point
 * Route: /admin/packages
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

require_once __DIR__ . '/modules/subscriptions/PackagesModule.php';
$module    = new PackagesModule();
$alert     = null;

// ─── POST-Verarbeitung ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_packages')) {
        $alert = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
    } else {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'save':
                $result = $module->save($_POST);
                $_SESSION['admin_alert'] = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message'] ?? $result['error'] ?? ''];
                header('Location: ' . SITE_URL . '/admin/packages');
                exit;

            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                $result = $module->delete($id);
                $_SESSION['admin_alert'] = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message'] ?? $result['error'] ?? ''];
                header('Location: ' . SITE_URL . '/admin/packages');
                exit;

            case 'toggle':
                $id = (int)($_POST['id'] ?? 0);
                $result = $module->toggleStatus($id);
                $_SESSION['admin_alert'] = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message'] ?? $result['error'] ?? ''];
                header('Location: ' . SITE_URL . '/admin/packages');
                exit;
        }
    }
    $csrfToken = Security::instance()->generateToken('admin_packages');
}

if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_packages');
$pageTitle  = 'Abo-Pakete';
$activePage = 'packages';
$data       = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/subscriptions/packages.php';
require_once __DIR__ . '/partials/footer.php';
