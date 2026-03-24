<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Explorer – Entry Point
 * Route: /admin/theme-explorer
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/themes/ThemeEditorModule.php';
$module    = new ThemeEditorModule();
$alert     = null;
$redirectBase = SITE_URL . '/admin/theme-explorer';
$allowedActions = ['save_file'];

// Datei speichern
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $postToken = (string)($_POST['csrf_token'] ?? '');
    if (!Security::instance()->verifyToken($postToken, 'admin_theme_explorer')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . $redirectBase);
        exit;
    }

    $action = (string)($_POST['action'] ?? '');
    if (!in_array($action, $allowedActions, true)) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Unbekannte oder nicht erlaubte Aktion.'];
        header('Location: ' . $redirectBase);
        exit;
    }

    $file    = (string)($_POST['file'] ?? '');
    $content = (string)($_POST['content'] ?? '');
    $result  = $module->saveFile($file, $content);
    $_SESSION['admin_alert'] = [
        'type'    => !empty($result['success']) ? 'success' : 'danger',
        'message' => $result['message'] ?? $result['error'] ?? 'Unbekannte Antwort.',
    ];

    $location = $redirectBase;
    if ($file !== '') {
        $location .= '?file=' . rawurlencode($file);
    }

    header('Location: ' . $location);
    exit;
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken    = Security::instance()->generateToken('admin_theme_explorer');
$currentFile  = (string)($_GET['file'] ?? '');
$data         = $module->getData($currentFile);
$pageTitle    = 'Theme Explorer';
$activePage   = 'theme-explorer';
$pageAssets   = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/themes/editor.php';
require __DIR__ . '/partials/footer.php';
