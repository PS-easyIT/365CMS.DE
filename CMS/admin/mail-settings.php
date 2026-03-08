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

$module = new MailSettingsModule();
$alert = null;
$allowedTabs = ['transport', 'azure', 'graph', 'logs', 'queue'];
$currentTab = (string) ($_GET['tab'] ?? 'transport');
if (!in_array($currentTab, $allowedTabs, true)) {
    $currentTab = 'transport';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = (string) ($_POST['csrf_token'] ?? '');
    if (!Security::instance()->verifyToken($postToken, 'admin_mail_settings')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . '/admin/mail-settings?tab=' . rawurlencode((string) ($_POST['tab'] ?? $currentTab)));
        exit;
    }

    $currentTab = (string) ($_POST['tab'] ?? $currentTab);
    if (!in_array($currentTab, $allowedTabs, true)) {
        $currentTab = 'transport';
    }

    $action = (string) ($_POST['action'] ?? '');
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
        default => ['success' => false, 'error' => 'Unbekannte Aktion.'],
    };

    $_SESSION['admin_alert'] = [
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? 'Unbekannte Antwort.'),
    ];

    header('Location: ' . SITE_URL . '/admin/mail-settings?tab=' . rawurlencode($currentTab));
    exit;
}

if (!empty($_SESSION['admin_alert'])) {
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
require __DIR__ . '/views/system/mail-settings.php';
require __DIR__ . '/partials/footer.php';
