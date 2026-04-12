<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Services\CoreModuleService;

const CMS_ADMIN_AI_SERVICES_READ_CAPABILITIES = ['manage_settings', 'manage_system', 'manage_ai_services'];
const CMS_ADMIN_AI_SERVICES_WRITE_CAPABILITY = 'manage_settings';

function cms_admin_ai_services_has_any_capability(array $capabilities): bool
{
    foreach ($capabilities as $capability) {
        if (is_string($capability) && $capability !== '' && Auth::instance()->hasCapability($capability)) {
            return true;
        }
    }

    return false;
}

function cms_admin_ai_services_can_access(): bool
{
    return Auth::instance()->isAdmin()
    && cms_admin_ai_services_has_any_capability(CMS_ADMIN_AI_SERVICES_READ_CAPABILITIES)
    && CoreModuleService::getInstance()->isAdminPageEnabled('ai-services');
}

function cms_admin_ai_services_can_mutate(): bool
{
    return cms_admin_ai_services_can_access()
        && Auth::instance()->hasCapability(CMS_ADMIN_AI_SERVICES_WRITE_CAPABILITY);
}

/** @return list<string> */
function cms_admin_ai_services_allowed_tabs(): array
{
    return ['overview', 'providers', 'features', 'translation', 'logging', 'quotas'];
}

function cms_admin_ai_services_normalize_tab(string $tab): string
{
    return in_array($tab, cms_admin_ai_services_allowed_tabs(), true) ? $tab : 'overview';
}

/** @return array<string, list<string>> */
function cms_admin_ai_services_allowed_actions_by_tab(): array
{
    return [
        'overview' => [],
        'providers' => ['save_providers'],
        'features' => ['save_features'],
        'translation' => ['save_translation'],
        'logging' => ['save_logging'],
        'quotas' => ['save_quotas'],
    ];
}

/** @return list<string> */
function cms_admin_ai_services_allowed_actions(): array
{
    $actions = [];

    foreach (cms_admin_ai_services_allowed_actions_by_tab() as $tabActions) {
        foreach ($tabActions as $action) {
            $actions[$action] = $action;
        }
    }

    return array_values($actions);
}

function cms_admin_ai_services_normalize_action(string $action): ?string
{
    $action = trim($action);

    return in_array($action, cms_admin_ai_services_allowed_actions(), true) ? $action : null;
}

function cms_admin_ai_services_resolve_action_tab(string $action, string $fallbackTab = 'overview'): string
{
    foreach (cms_admin_ai_services_allowed_actions_by_tab() as $tab => $actions) {
        if (in_array($action, $actions, true)) {
            return $tab;
        }
    }

    return cms_admin_ai_services_normalize_tab($fallbackTab);
}

function cms_admin_ai_services_is_action_allowed_for_tab(string $action, string $tab): bool
{
    $allowedActions = cms_admin_ai_services_allowed_actions_by_tab()[cms_admin_ai_services_normalize_tab($tab)] ?? [];

    return in_array($action, $allowedActions, true);
}

/**
 * @return array{tab:string,action:?string,post:array<string,mixed>}
 */
function cms_admin_ai_services_normalize_payload(array $post, string $section): array
{
    return [
        'tab' => cms_admin_ai_services_normalize_tab((string) ($post['tab'] ?? $section)),
        'action' => cms_admin_ai_services_normalize_action((string) ($post['action'] ?? '')),
        'post' => $post,
    ];
}

/** @return array<string, mixed> */
function cms_admin_ai_services_handle_action(AiServicesModule $module, array $payload): array
{
    return match ($payload['action']) {
        'save_providers' => $module->saveProviders($payload['post']),
        'save_features' => $module->saveFeatures($payload['post']),
        'save_translation' => $module->saveTranslation($payload['post']),
        'save_logging' => $module->saveLogging($payload['post']),
        'save_quotas' => $module->saveQuotas($payload['post']),
        default => ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'],
    };
}

$sectionPageConfig = [
    'route_path' => '/admin/ai-services',
    'view_file' => __DIR__ . '/views/system/ai-services.php',
    'page_title' => 'System · AI Services',
    'active_page' => 'ai-services',
    'csrf_action' => 'admin_ai_services',
    'guard_constant' => 'CMS_ADMIN_SYSTEM_VIEW',
    'module_file' => __DIR__ . '/modules/system/AiServicesModule.php',
    'module_factory' => static fn (): AiServicesModule => new AiServicesModule(),
    'data_loader' => static fn (AiServicesModule $module): array => $module->getData(),
    'access_checker' => static fn (): bool => cms_admin_ai_services_can_access(),
    'access_denied_route' => '/',
    'request_context_resolver' => static function (): array {
        $currentTab = cms_admin_ai_services_normalize_tab((string) ($_SERVER['REQUEST_METHOD'] === 'POST'
            ? ($_POST['tab'] ?? 'overview')
            : ($_GET['tab'] ?? 'overview')));

        return [
            'section' => $currentTab,
            'template_vars' => [
                'currentTab' => $currentTab,
            ],
        ];
    },
    'redirect_path_resolver' => static function ($module, string $section, $result): string {
        $redirectTab = cms_admin_ai_services_normalize_tab((string) (($result['redirect_tab'] ?? '') !== '' ? $result['redirect_tab'] : $section));

        return '/admin/ai-services?tab=' . rawurlencode($redirectTab);
    },
    'unknown_action_message' => 'Unbekannte oder nicht erlaubte Aktion.',
    'post_handler' => static function (AiServicesModule $module, string $section, array $post): array {
        if (!cms_admin_ai_services_can_mutate()) {
            return ['success' => false, 'error' => 'Keine Berechtigung für AI-Services-Änderungen.', 'redirect_tab' => cms_admin_ai_services_normalize_tab((string) ($post['tab'] ?? $section))];
        }

        $payload = cms_admin_ai_services_normalize_payload($post, $section);
        $currentTab = $payload['tab'];

        if ($payload['action'] === null) {
            return ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.', 'redirect_tab' => $currentTab];
        }

        $actionTab = cms_admin_ai_services_resolve_action_tab($payload['action'], $currentTab);
        if (!cms_admin_ai_services_is_action_allowed_for_tab($payload['action'], $currentTab)) {
            return ['success' => false, 'error' => 'Aktion passt nicht zum gewählten Bereich.', 'redirect_tab' => $actionTab];
        }

        $result = cms_admin_ai_services_handle_action($module, $payload);
        $result['redirect_tab'] = $actionTab;

        return $result;
    },
];

require __DIR__ . '/partials/section-page-shell.php';