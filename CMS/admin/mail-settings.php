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

function cms_admin_mail_settings_normalize_tab(string $tab): string
{
    return in_array($tab, cms_admin_mail_settings_allowed_tabs(), true) ? $tab : 'transport';
}

/**
 * @return array<string, list<string>>
 */
function cms_admin_mail_settings_allowed_actions_by_tab(): array
{
    return [
        'transport' => ['save_transport', 'send_test_email'],
        'azure' => ['save_azure', 'clear_azure_cache'],
        'graph' => ['save_graph', 'test_graph_connection', 'clear_graph_cache'],
        'logs' => ['clear_logs'],
        'queue' => ['save_queue', 'run_queue_now', 'release_queue_stale', 'enqueue_queue_test'],
    ];
}

/**
 * @return list<string>
 */
function cms_admin_mail_settings_allowed_actions(): array
{
    $actions = [];

    foreach (cms_admin_mail_settings_allowed_actions_by_tab() as $tabActions) {
        foreach ($tabActions as $action) {
            $actions[$action] = $action;
        }
    }

    return array_values($actions);
}

function cms_admin_mail_settings_normalize_action(string $action): ?string
{
    $action = trim($action);

    return in_array($action, cms_admin_mail_settings_allowed_actions(), true) ? $action : null;
}

function cms_admin_mail_settings_resolve_action_tab(string $action, string $fallbackTab = 'transport'): string
{
    foreach (cms_admin_mail_settings_allowed_actions_by_tab() as $tab => $actions) {
        if (in_array($action, $actions, true)) {
            return $tab;
        }
    }

    return cms_admin_mail_settings_normalize_tab($fallbackTab);
}

function cms_admin_mail_settings_is_action_allowed_for_tab(string $action, string $tab): bool
{
    $allowedActions = cms_admin_mail_settings_allowed_actions_by_tab()[cms_admin_mail_settings_normalize_tab($tab)] ?? [];

    return in_array($action, $allowedActions, true);
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

function cms_admin_mail_settings_flash_result(MailSettingsActionResult $result): void
{
    cms_admin_mail_settings_flash(
        $result->isSuccess() ? 'success' : 'danger',
        $result->message() !== '' ? $result->message() : ($result->error() !== '' ? $result->error() : 'Unbekannte Antwort.')
    );
}

/**
 * @return array<string, callable(array): MailSettingsActionResult>
 */
function cms_admin_mail_settings_action_handlers(MailSettingsModule $module): array
{
    return [
        'save_transport' => static fn (array $post): MailSettingsActionResult => $module->saveTransport($post),
        'save_azure' => static fn (array $post): MailSettingsActionResult => $module->saveAzure($post),
        'save_graph' => static fn (array $post): MailSettingsActionResult => $module->saveGraph($post),
        'save_queue' => static fn (array $post): MailSettingsActionResult => $module->saveQueue($post),
        'send_test_email' => static fn (array $post): MailSettingsActionResult => $module->sendTestEmail($post),
        'run_queue_now' => static fn (array $post): MailSettingsActionResult => $module->runQueueNow($post),
        'release_queue_stale' => static fn (array $post): MailSettingsActionResult => $module->releaseQueueStale(),
        'enqueue_queue_test' => static fn (array $post): MailSettingsActionResult => $module->enqueueQueueTestEmail($post),
        'test_graph_connection' => static fn (array $post): MailSettingsActionResult => $module->testGraphConnection(),
        'clear_logs' => static fn (array $post): MailSettingsActionResult => $module->clearLogs(),
        'clear_azure_cache' => static fn (array $post): MailSettingsActionResult => $module->clearAzureCache(),
        'clear_graph_cache' => static fn (array $post): MailSettingsActionResult => $module->clearGraphCache(),
    ];
}

function cms_admin_mail_settings_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
}

$module = new MailSettingsModule();
$alert = null;
$redirectBase = SITE_URL . '/admin/mail-settings';
$currentTab = cms_admin_mail_settings_normalize_tab((string) ($_GET['tab'] ?? 'transport'));
$actionHandlers = cms_admin_mail_settings_action_handlers($module);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = (string) ($_POST['csrf_token'] ?? '');
    if (!Security::instance()->verifyToken($postToken, 'admin_mail_settings')) {
        cms_admin_mail_settings_flash('danger', 'Sicherheitstoken ungültig.');
        cms_admin_mail_settings_redirect($redirectBase, (string) ($_POST['tab'] ?? $currentTab));
    }

    $currentTab = cms_admin_mail_settings_normalize_tab((string) ($_POST['tab'] ?? $currentTab));

    $action = cms_admin_mail_settings_normalize_action((string) ($_POST['action'] ?? ''));
    if ($action === null || !isset($actionHandlers[$action])) {
        cms_admin_mail_settings_flash('danger', 'Unbekannte oder nicht erlaubte Aktion.');
        cms_admin_mail_settings_redirect($redirectBase, $currentTab);
    }

    $actionTab = cms_admin_mail_settings_resolve_action_tab($action, $currentTab);
    if (!cms_admin_mail_settings_is_action_allowed_for_tab($action, $currentTab)) {
        cms_admin_mail_settings_flash('danger', 'Aktion passt nicht zum gewählten Bereich.');
        cms_admin_mail_settings_redirect($redirectBase, $actionTab);
    }

    $result = $actionHandlers[$action]($_POST);

    cms_admin_mail_settings_flash_result($result);

    cms_admin_mail_settings_redirect($redirectBase, $actionTab);
}

$alert = cms_admin_mail_settings_pull_alert();

$csrfToken = Security::instance()->generateToken('admin_mail_settings');
$apiCsrfToken = Security::instance()->generateToken('admin_mail_api');
$data = $module->getData()->toArray();
$pageTitle = 'System · Mail & Azure OAuth2';
$activePage = 'mail-settings';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
defined('CMS_ADMIN_SYSTEM_VIEW') || define('CMS_ADMIN_SYSTEM_VIEW', true);
require __DIR__ . '/views/system/mail-settings.php';
require __DIR__ . '/partials/footer.php';
