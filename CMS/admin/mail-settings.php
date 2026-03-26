<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

const CMS_ADMIN_MAIL_SETTINGS_READ_CAPABILITIES = ['manage_settings', 'manage_system'];
const CMS_ADMIN_MAIL_SETTINGS_WRITE_CAPABILITY = 'manage_settings';

function cms_admin_mail_settings_has_any_capability(array $capabilities): bool
{
    foreach ($capabilities as $capability) {
        if (is_string($capability) && $capability !== '' && Auth::instance()->hasCapability($capability)) {
            return true;
        }
    }

    return false;
}

function cms_admin_mail_settings_can_access(): bool
{
    return Auth::instance()->isAdmin()
        && cms_admin_mail_settings_has_any_capability(CMS_ADMIN_MAIL_SETTINGS_READ_CAPABILITIES);
}

function cms_admin_mail_settings_can_mutate(): bool
{
    return cms_admin_mail_settings_can_access()
        && Auth::instance()->hasCapability(CMS_ADMIN_MAIL_SETTINGS_WRITE_CAPABILITY);
}

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

$sectionPageConfig = [
    'route_path' => '/admin/mail-settings',
    'view_file' => __DIR__ . '/views/system/mail-settings.php',
    'page_title' => 'System · Mail & Azure OAuth2',
    'active_page' => 'mail-settings',
    'csrf_action' => 'admin_mail_settings',
    'guard_constant' => 'CMS_ADMIN_SYSTEM_VIEW',
    'module_file' => __DIR__ . '/modules/system/MailSettingsModule.php',
    'module_factory' => static fn (): MailSettingsModule => new MailSettingsModule(),
    'data_loader' => static fn (MailSettingsModule $module): array => $module->getData()->toArray(),
    'access_checker' => static fn (): bool => cms_admin_mail_settings_can_access(),
    'access_denied_route' => '/',
    'request_context_resolver' => static function (): array {
        $currentTab = cms_admin_mail_settings_normalize_tab((string) ($_SERVER['REQUEST_METHOD'] === 'POST'
            ? ($_POST['tab'] ?? 'transport')
            : ($_GET['tab'] ?? 'transport')));

        return [
            'section' => $currentTab,
            'template_vars' => [
                'currentTab' => $currentTab,
                'apiCsrfToken' => Security::instance()->generateToken('admin_mail_api'),
            ],
        ];
    },
    'redirect_path_resolver' => static function ($module, string $section, $result): string {
        $redirectTab = cms_admin_mail_settings_normalize_tab((string) (($result['redirect_tab'] ?? '') !== '' ? $result['redirect_tab'] : $section));

        return '/admin/mail-settings?tab=' . rawurlencode($redirectTab);
    },
    'unknown_action_message' => 'Unbekannte oder nicht erlaubte Aktion.',
    'post_handler' => static function (MailSettingsModule $module, string $section, array $post): array {
        if (!cms_admin_mail_settings_can_mutate()) {
            return ['success' => false, 'error' => 'Keine Berechtigung für Mail- und OAuth2-Änderungen.', 'redirect_tab' => cms_admin_mail_settings_normalize_tab((string) ($post['tab'] ?? $section))];
        }

        $currentTab = cms_admin_mail_settings_normalize_tab((string) ($post['tab'] ?? $section));
        $action = cms_admin_mail_settings_normalize_action((string) ($post['action'] ?? ''));
        $handlers = cms_admin_mail_settings_action_handlers($module);

        if ($action === null || !isset($handlers[$action])) {
            return ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.', 'redirect_tab' => $currentTab];
        }

        $actionTab = cms_admin_mail_settings_resolve_action_tab($action, $currentTab);
        if (!cms_admin_mail_settings_is_action_allowed_for_tab($action, $currentTab)) {
            return ['success' => false, 'error' => 'Aktion passt nicht zum gewählten Bereich.', 'redirect_tab' => $actionTab];
        }

        $result = $handlers[$action]($post)->toArray();
        $result['redirect_tab'] = $actionTab;

        return $result;
    },
];

require __DIR__ . '/partials/section-page-shell.php';
