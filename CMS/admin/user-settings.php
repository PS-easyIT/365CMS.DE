<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Benutzer & Auth-Einstellungen – Entry Point
 * Route: /admin/user-settings
 */

use CMS\Auth;
use CMS\Security;

const CMS_ADMIN_USER_SETTINGS_WRITE_CAPABILITY = 'manage_users';

if (!Auth::instance()->isAdmin() || !Auth::instance()->hasCapability(CMS_ADMIN_USER_SETTINGS_WRITE_CAPABILITY)) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/users/UserSettingsModule.php';
$module = new UserSettingsModule();
$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!Security::instance()->verifyToken($postToken, 'admin_user_settings')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . '/admin/user-settings');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $result = match ($action) {
        'save' => $module->saveSettings($_POST),
        'sync_ldap' => $module->syncLdapUsers(),
        default => ['success' => false, 'error' => 'Unbekannte Aktion.'],
    };

    $_SESSION['admin_alert'] = [
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => $result['message'] ?? $result['error'] ?? '',
    ];

    header('Location: ' . SITE_URL . '/admin/user-settings');
    exit;
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken = Security::instance()->generateToken('admin_user_settings');
$data = $module->getData();
$pageTitle = 'Benutzer & Authentifizierung';
$activePage = 'user-settings';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/users/settings.php';
require __DIR__ . '/partials/footer.php';