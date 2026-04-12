<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Services\CoreModuleService;

const CMS_ADMIN_AI_READ_CAPABILITIES = ['manage_settings', 'manage_system', 'manage_ai_services'];
const CMS_ADMIN_AI_WRITE_CAPABILITY = 'manage_settings';
const CMS_ADMIN_AI_PAGE_CONFIGS = [
    'overview' => [
        'route_path' => '/admin/ai-services',
        'page_title' => 'AI Services',
        'active_page' => 'ai-services',
    ],
    'translation' => [
        'route_path' => '/admin/ai-translation',
        'page_title' => 'AI Services · Übersetzung',
        'active_page' => 'ai-translation',
    ],
    'content_creator' => [
        'route_path' => '/admin/ai-content-creator',
        'page_title' => 'AI Services · Content Creator',
        'active_page' => 'ai-content-creator',
    ],
    'seo_creator' => [
        'route_path' => '/admin/ai-seo-creator',
        'page_title' => 'AI Services · SEO Creator',
        'active_page' => 'ai-seo-creator',
    ],
    'settings' => [
        'route_path' => '/admin/ai-settings',
        'page_title' => 'AI Services · Einstellungen',
        'active_page' => 'ai-settings',
    ],
];

const CMS_ADMIN_AI_ALLOWED_ACTIONS_BY_SECTION = [
    'overview' => [],
    'translation' => ['save_translation'],
    'content_creator' => [],
    'seo_creator' => [],
    'settings' => ['save_providers', 'save_features', 'save_logging', 'save_quotas'],
];

function cms_admin_ai_has_any_capability(array $capabilities): bool
{
    foreach ($capabilities as $capability) {
        if (is_string($capability) && $capability !== '' && Auth::instance()->hasCapability($capability)) {
            return true;
        }
    }

    return false;
}

function cms_admin_ai_normalize_section(string $section): string
{
    $section = trim($section);

    return array_key_exists($section, CMS_ADMIN_AI_PAGE_CONFIGS) ? $section : 'overview';
}

/**
 * @param array<string, mixed> $pageConfig
 * @return array{section:string,route_path:string,page_title:string,active_page:string}
 */
function cms_admin_ai_normalize_page_config(array $pageConfig): array
{
    $section = cms_admin_ai_normalize_section((string) ($pageConfig['section'] ?? 'overview'));
    $defaults = CMS_ADMIN_AI_PAGE_CONFIGS[$section] ?? CMS_ADMIN_AI_PAGE_CONFIGS['overview'];

    return [
        'section' => $section,
        'route_path' => (string) ($pageConfig['route_path'] ?? $defaults['route_path']),
        'page_title' => (string) ($pageConfig['page_title'] ?? $defaults['page_title']),
        'active_page' => (string) ($pageConfig['active_page'] ?? $defaults['active_page']),
    ];
}

function cms_admin_ai_can_access(string $activePage): bool
{
    return Auth::instance()->isAdmin()
        && cms_admin_ai_has_any_capability(CMS_ADMIN_AI_READ_CAPABILITIES)
        && (!class_exists(CoreModuleService::class) || CoreModuleService::getInstance()->isAdminPageEnabled($activePage));
}

function cms_admin_ai_can_mutate(string $activePage): bool
{
    return cms_admin_ai_can_access($activePage)
        && Auth::instance()->hasCapability(CMS_ADMIN_AI_WRITE_CAPABILITY);
}

function cms_admin_ai_normalize_action(string $action): ?string
{
    $action = trim($action);
    if ($action === '') {
        return null;
    }

    foreach (CMS_ADMIN_AI_ALLOWED_ACTIONS_BY_SECTION as $actions) {
        if (in_array($action, $actions, true)) {
            return $action;
        }
    }

    return null;
}

function cms_admin_ai_is_action_allowed_for_section(string $section, string $action): bool
{
    $section = cms_admin_ai_normalize_section($section);

    return in_array($action, CMS_ADMIN_AI_ALLOWED_ACTIONS_BY_SECTION[$section] ?? [], true);
}

function cms_admin_ai_resolve_section_for_action(string $action, string $fallbackSection = 'overview'): string
{
    foreach (CMS_ADMIN_AI_ALLOWED_ACTIONS_BY_SECTION as $section => $actions) {
        if (in_array($action, $actions, true)) {
            return $section;
        }
    }

    return cms_admin_ai_normalize_section($fallbackSection);
}

function cms_admin_ai_handle_action(AiServicesModule $module, string $action, array $post): array
{
    return match ($action) {
        'save_providers' => $module->saveProviders($post),
        'save_features' => $module->saveFeatures($post),
        'save_translation' => $module->saveTranslation($post),
        'save_logging' => $module->saveLogging($post),
        'save_quotas' => $module->saveQuotas($post),
        default => ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'],
    };
}

function cms_admin_ai_build_route_for_section(string $section): string
{
    $section = cms_admin_ai_normalize_section($section);

    return (string) (CMS_ADMIN_AI_PAGE_CONFIGS[$section]['route_path'] ?? CMS_ADMIN_AI_PAGE_CONFIGS['overview']['route_path']);
}

function cms_admin_ai_resolve_legacy_tab_redirect(string $tab): ?string
{
    $tab = trim($tab);
    if ($tab === '' || $tab === 'overview') {
        return null;
    }

    $map = [
        'translation' => 'translation',
        'providers' => 'settings',
        'features' => 'settings',
        'logging' => 'settings',
        'quotas' => 'settings',
    ];

    if (!isset($map[$tab])) {
        return null;
    }

    return cms_admin_ai_build_route_for_section($map[$tab]);
}

$aiPageConfig = cms_admin_ai_normalize_page_config(is_array($aiPageConfig ?? null) ? $aiPageConfig : []);

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    $legacyRedirect = cms_admin_ai_resolve_legacy_tab_redirect((string) ($_GET['tab'] ?? ''));
    if ($legacyRedirect !== null) {
        header('Location: ' . $legacyRedirect);
        exit;
    }
}

$sectionPageConfig = [
    'route_path' => $aiPageConfig['route_path'],
    'view_file' => __DIR__ . '/views/system/ai-services.php',
    'page_title' => $aiPageConfig['page_title'],
    'active_page' => $aiPageConfig['active_page'],
    'section' => $aiPageConfig['section'],
    'csrf_action' => 'admin_ai_services',
    'guard_constant' => 'CMS_ADMIN_AI_VIEW',
    'module_file' => __DIR__ . '/modules/system/AiServicesModule.php',
    'module_factory' => static fn (): AiServicesModule => new AiServicesModule(),
    'data_loader' => static fn (AiServicesModule $module): array => $module->getData(),
    'access_checker' => static fn (): bool => cms_admin_ai_can_access($aiPageConfig['active_page']),
    'access_denied_route' => '/',
    'request_context_resolver' => static fn (): array => [
        'section' => $aiPageConfig['section'],
        'template_vars' => [
            'currentSection' => $aiPageConfig['section'],
            'currentRoutePath' => $aiPageConfig['route_path'],
        ],
    ],
    'redirect_path_resolver' => static function ($module, string $section, $result) use ($aiPageConfig): string {
        $redirectSection = cms_admin_ai_normalize_section((string) ($result['redirect_section'] ?? $aiPageConfig['section']));

        return cms_admin_ai_build_route_for_section($redirectSection);
    },
    'unknown_action_message' => 'Unbekannte oder nicht erlaubte Aktion.',
    'post_handler' => static function (AiServicesModule $module, string $section, array $post) use ($aiPageConfig): array {
        if (!cms_admin_ai_can_mutate($aiPageConfig['active_page'])) {
            return [
                'success' => false,
                'error' => 'Keine Berechtigung für AI-Services-Änderungen.',
                'redirect_section' => $aiPageConfig['section'],
            ];
        }

        $action = cms_admin_ai_normalize_action((string) ($post['action'] ?? ''));
        if ($action === null) {
            return [
                'success' => false,
                'error' => 'Unbekannte oder nicht erlaubte Aktion.',
                'redirect_section' => $aiPageConfig['section'],
            ];
        }

        if (!cms_admin_ai_is_action_allowed_for_section($section, $action)) {
            return [
                'success' => false,
                'error' => 'Aktion passt nicht zum gewählten Bereich.',
                'redirect_section' => cms_admin_ai_resolve_section_for_action($action, $section),
            ];
        }

        $result = cms_admin_ai_handle_action($module, $action, $post);
        $result['redirect_section'] = cms_admin_ai_resolve_section_for_action($action, $section);

        return $result;
    },
];

require __DIR__ . '/partials/section-page-shell.php';
