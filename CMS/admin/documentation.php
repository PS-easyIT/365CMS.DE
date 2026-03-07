<?php
declare(strict_types=1);

/**
 * Dokumentation – Entry Point
 * Route: /admin/documentation
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

require_once __DIR__ . '/modules/system/DocumentationModule.php';

$module = new DocumentationModule();
$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!Security::instance()->verifyToken($postToken, 'admin_documentation')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
    } else {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'sync_docs') {
            $result = $module->syncDocsFromRepository();
            $_SESSION['admin_alert'] = [
                'type' => !empty($result['success']) ? 'success' : 'danger',
                'message' => (string)($result['message'] ?? $result['error'] ?? 'Unbekannte Antwort beim Doku-Sync.'),
            ];
        }
    }

    $redirect = SITE_URL . '/admin/documentation';
    if (!empty($_GET['doc'])) {
        $redirect .= '?doc=' . rawurlencode((string)$_GET['doc']);
    }
    header('Location: ' . $redirect);
    exit;
}

if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken = Security::instance()->generateToken('admin_documentation');
$data = $module->getData($_GET['doc'] ?? null);

$pageTitle = 'Dokumentation';
$activePage = 'documentation';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/system/documentation.php';
require __DIR__ . '/partials/footer.php';
