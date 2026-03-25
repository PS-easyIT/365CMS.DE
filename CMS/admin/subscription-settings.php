<?php
declare(strict_types=1);

/**
 * Abo-Einstellungen – Entry Point
 * Route: /admin/subscription-settings
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

/** @return array<string, string> */
function cms_admin_subscription_settings_allowed_actions(): array
{
    return [
        'save_settings' => 'save_settings',
    ];
}

function cms_admin_subscription_settings_redirect(): never
{
    header('Location: ' . SITE_URL . '/admin/subscription-settings');
    exit;
}

function cms_admin_subscription_settings_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string)($payload['message'] ?? '')),
    ];
}

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/subscriptions/SubscriptionSettingsModule.php';
$module    = new SubscriptionSettingsModule();
$alert     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_sub_settings')) {
        cms_admin_subscription_settings_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
        cms_admin_subscription_settings_redirect();
    } else {
        $allowedActions = cms_admin_subscription_settings_allowed_actions();
        $action = (string)($_POST['action'] ?? 'save_settings');
        if (!isset($allowedActions[$action])) {
            cms_admin_subscription_settings_flash(['type' => 'danger', 'message' => 'Unbekannte Aktion.']);
            cms_admin_subscription_settings_redirect();
        }

        $result = $module->saveSettings($_POST);
        cms_admin_subscription_settings_flash([
            'type' => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? '',
        ]);
        cms_admin_subscription_settings_redirect();
    }
}

if (isset($_SESSION['admin_alert'])) {
    $alert = is_array($_SESSION['admin_alert']) ? $_SESSION['admin_alert'] : null;
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_sub_settings');
$pageTitle  = 'Aboverwaltung Einstellungen';
$activePage = 'subscription-settings';
$data       = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/subscriptions/settings.php';
require_once __DIR__ . '/partials/footer.php';
