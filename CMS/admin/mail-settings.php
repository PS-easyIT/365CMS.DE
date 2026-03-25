<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/system/MailSettingsModule.php';

function cms_admin_mail_settings_allowed_tabs(): array
{
    return ['transport', 'azure', 'graph', 'logs', 'queue'];
}

function cms_admin_mail_settings_allowed_actions(): array
{
    return [
        'save_transport',
        'save_azure',
        'save_graph',
        'save_queue',
        'send_test_email',
        'run_queue_now',
        'release_queue_stale',
        'enqueue_queue_test',
        'test_graph_connection',
        'clear_logs',
        'clear_azure_cache',
        'clear_graph_cache',
    ];
}

function cms_admin_mail_settings_normalize_tab(string $tab): string
{
    return in_array($tab, cms_admin_mail_settings_allowed_tabs(), true) ? $tab : 'transport';
}

function cms_admin_mail_settings_redirect(string $redirectBase, string $tab): never
{
    header('Location: ' . $redirectBase . '?tab=' . rawurlencode(cms_admin_mail_settings_normalize_tab($tab)));
    exit;
}

function cms_admin_mail_settings_flash(string $type, string $message): void
{
    $_SESSION['admin_alert'] = [
        'type' => $type,
        'message' => $message,
    ];
}

$module = new MailSettingsModule();
$alert = null;
$redirectBase = SITE_URL . '/admin/mail-settings';
$currentTab = cms_admin_mail_settings_normalize_tab((string) ($_GET['tab'] ?? 'transport'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = (string) ($_POST['csrf_token'] ?? '');
    if (!Security::instance()->verifyToken($postToken, 'admin_mail_settings')) {
        cms_admin_mail_settings_flash('danger', 'Sicherheitstoken ungültig.');
        cms_admin_mail_settings_redirect($redirectBase, (string) ($_POST['tab'] ?? $currentTab));
    }

    $currentTab = cms_admin_mail_settings_normalize_tab((string) ($_POST['tab'] ?? $currentTab));

    $action = (string) ($_POST['action'] ?? '');
    if (!in_array($action, cms_admin_mail_settings_allowed_actions(), true)) {
        cms_admin_mail_settings_flash('danger', 'Unbekannte Aktion.');
        cms_admin_mail_settings_redirect($redirectBase, $currentTab);
    }

    $result = match ($action) {
        'save_transport' => $module->saveTransport($_POST),
        'save_azure' => $module->saveAzure($_POST),
        'save_graph' => $module->saveGraph($_POST),
        'save_queue' => $module->saveQueue($_POST),
        'send_test_email' => $module->sendTestEmail($_POST),
        'run_queue_now' => $module->runQueueNow($_POST),
        'release_queue_stale' => $module->releaseQueueStale(),
        'enqueue_queue_test' => $module->enqueueQueueTestEmail($_POST),
        'test_graph_connection' => $module->testGraphConnection(),
        'clear_logs' => $module->clearLogs(),
        'clear_azure_cache' => $module->clearAzureCache(),
        'clear_graph_cache' => $module->clearGraphCache(),
    };

    cms_admin_mail_settings_flash(
        !empty($result['success']) ? 'success' : 'danger',
        (string) ($result['message'] ?? $result['error'] ?? 'Unbekannte Antwort.')
    );

    cms_admin_mail_settings_redirect($redirectBase, $currentTab);
}

if (!empty($_SESSION['admin_alert']) && is_array($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken = Security::instance()->generateToken('admin_mail_settings');
$apiCsrfToken = Security::instance()->generateToken('admin_mail_api');
$data = $module->getData();
$pageTitle = 'System · Mail & Azure OAuth2';
$activePage = 'mail-settings';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
defined('CMS_ADMIN_SYSTEM_VIEW') || define('CMS_ADMIN_SYSTEM_VIEW', true);
require __DIR__ . '/views/system/mail-settings.php';
require __DIR__ . '/partials/footer.php';
