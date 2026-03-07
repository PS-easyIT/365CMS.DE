<?php
declare(strict_types=1);

/**
 * Plugins – Entry Point
 * Route: /admin/plugins
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

require_once __DIR__ . '/modules/plugins/PluginsModule.php';
$module = new PluginsModule();
$alert  = null;

// POST-Handler: Token ZUERST verifizieren, bevor ein neuer generiert wird
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_plugins')) {
        $alert = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
    } else {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'activate':
                $result = $module->activatePlugin($_POST['slug'] ?? '');
                break;
            case 'deactivate':
                $result = $module->deactivatePlugin($_POST['slug'] ?? '');
                break;
            case 'delete':
                $result = $module->deletePlugin($_POST['slug'] ?? '');
                break;
            default:
                $result = ['success' => false, 'error' => 'Unbekannte Aktion.'];
        }
        $_SESSION['admin_alert'] = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message'] ?? $result['error'] ?? ''];
        header('Location: ' . SITE_URL . '/admin/plugins');
        exit;
    }
}

if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

// Token NACH dem POST-Handler generieren (für das Formular-Rendering)
$csrfToken = Security::instance()->generateToken('admin_plugins');

$pageTitle  = 'Plugins';
$activePage = 'plugins';
$data       = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/plugins/list.php';
require_once __DIR__ . '/partials/footer.php';
