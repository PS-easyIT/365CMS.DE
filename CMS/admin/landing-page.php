<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Landing Page – Entry Point
 * Route: /admin/landing-page
 * Tabs: header, content, footer, design, plugins
 */

use CMS\Auth;

const CMS_ADMIN_LANDING_PAGE_ALLOWED_TABS = ['header', 'content', 'footer', 'design', 'plugins'];

const CMS_ADMIN_LANDING_PAGE_ALLOWED_ACTIONS = [
    'save_header',
    'save_content',
    'save_footer',
    'save_design',
    'save_feature',
    'delete_feature',
    'save_plugin',
];

const CMS_ADMIN_LANDING_PAGE_WRITE_CAPABILITY = 'manage_settings';

/** @return array<string,string> */
function cms_admin_landing_page_action_tab_map(): array
{
    return [
        'save_header' => 'header',
        'save_content' => 'content',
        'save_feature' => 'content',
        'delete_feature' => 'content',
        'save_footer' => 'footer',
        'save_design' => 'design',
        'save_plugin' => 'plugins',
    ];
}

function cms_admin_landing_page_can_access(): bool
{
    return Auth::instance()->isAdmin() && Auth::instance()->hasCapability(CMS_ADMIN_LANDING_PAGE_WRITE_CAPABILITY);
}

function cms_admin_landing_page_allowed_tabs(): array
{
    return CMS_ADMIN_LANDING_PAGE_ALLOWED_TABS;
}

/**
 * @return array<int, array{key:string,label:string,url:string}>
 */
function cms_admin_landing_page_tab_config(): array
{
    return array_map(
        static fn (string $tab): array => [
            'key' => $tab,
            'label' => match ($tab) {
                'header' => 'Header',
                'content' => 'Content',
                'footer' => 'Footer',
                'design' => 'Design',
                'plugins' => 'Plugins',
                default => ucfirst($tab),
            },
            'url' => cms_admin_landing_page_tab_url($tab),
        ],
        cms_admin_landing_page_allowed_tabs()
    );
}

function cms_admin_landing_page_normalize_tab(string $tab): string
{
    $normalizedTab = preg_replace('/[^a-z]/', '', $tab);

    return in_array($normalizedTab, cms_admin_landing_page_allowed_tabs(), true) ? $normalizedTab : 'header';
}

function cms_admin_landing_page_resolve_active_tab(array $source, string $fallback = 'header'): string
{
    $candidate = (string) ($source['active_tab'] ?? $source['tab'] ?? $fallback);

    return cms_admin_landing_page_normalize_tab($candidate);
}

function cms_admin_landing_page_normalize_action(mixed $action): string
{
    $normalizedAction = strtolower(trim((string) $action));

    return in_array($normalizedAction, CMS_ADMIN_LANDING_PAGE_ALLOWED_ACTIONS, true) ? $normalizedAction : '';
}

function cms_admin_landing_page_normalize_positive_id(mixed $id): int
{
    $normalizedId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $normalizedId === false ? 0 : (int) $normalizedId;
}

function cms_admin_landing_page_tab_url(string $tab): string
{
    return '/admin/landing-page?tab=' . rawurlencode($tab);
}

/**
 * @return array{action:string,feature_id:int,active_tab:string,error:string,post:array<string,mixed>}
 */
function cms_admin_landing_page_normalize_payload(array $post): array
{
    $action = cms_admin_landing_page_normalize_action($post['action'] ?? '');
    $activeTab = cms_admin_landing_page_resolve_active_tab($post, 'header');
    $featureId = cms_admin_landing_page_normalize_positive_id($post['feature_id'] ?? 0);
    $pluginId = trim((string) ($post['plugin_id'] ?? ''));
    $error = '';

    if ($action === '') {
        $error = 'Unbekannte Aktion.';
    } elseif ($action === 'delete_feature' && $featureId < 1) {
        $error = 'Ungültige Feature-ID.';
    } elseif ($action === 'save_plugin' && $pluginId === '') {
        $error = 'Plugin-ID fehlt.';
    }

    return [
        'action' => $action,
        'feature_id' => $featureId,
        'active_tab' => $activeTab,
        'error' => $error,
        'post' => $post,
    ];
}

function cms_admin_landing_page_handle_action(LandingPageModule $module, array $payload): array
{
    return match ($payload['action']) {
        'save_header' => $module->saveHeader($payload['post']),
        'save_content' => $module->saveContent($payload['post']),
        'save_footer' => $module->saveFooter($payload['post']),
        'save_design' => $module->saveDesign($payload['post']),
        'save_feature' => $module->saveFeature($payload['post']),
        'delete_feature' => $module->deleteFeature($payload['feature_id']),
        'save_plugin' => $module->savePlugin($payload['post']),
        default => ['success' => false, 'error' => 'Unbekannte Aktion.'],
    };
}

$sectionPageConfig = [
    'route_path' => '/admin/landing-page',
    'view_file' => __DIR__ . '/views/landing/page.php',
    'page_title' => 'Landing Page',
    'active_page' => 'landing-page',
    'csrf_action' => 'admin_landing_page',
    'module_file' => __DIR__ . '/modules/landing/LandingPageModule.php',
    'module_factory' => static fn (): LandingPageModule => new LandingPageModule(),
    'data_loader' => static function (LandingPageModule $module): array {
        $tab = cms_admin_landing_page_resolve_active_tab($_GET, 'header');

        return array_merge($module->getData($tab), [
            'activeTab' => $tab,
            'tabs' => cms_admin_landing_page_tab_config(),
        ]);
    },
    'access_checker' => static fn (): bool => cms_admin_landing_page_can_access(),
    'access_denied_route' => '/',
    'redirect_path_resolver' => static function (LandingPageModule $module, string $section, mixed $result): string {
        $tab = cms_admin_landing_page_resolve_active_tab($_GET, 'header');
        if (is_array($result) && !empty($result['redirect_tab'])) {
            $tab = cms_admin_landing_page_normalize_tab((string) $result['redirect_tab']);
        }

        return cms_admin_landing_page_tab_url($tab);
    },
    'unknown_action_message' => 'Unbekannte Aktion.',
    'post_handler' => static function (LandingPageModule $module, string $section, array $post): array {
        if (!cms_admin_landing_page_can_access()) {
            return ['success' => false, 'error' => 'Keine Berechtigung für diese Aktion.'];
        }

        $payload = cms_admin_landing_page_normalize_payload($post);

        if ($payload['error'] !== '') {
            return ['success' => false, 'error' => $payload['error'], 'redirect_tab' => $payload['active_tab']];
        }

        $result = cms_admin_landing_page_handle_action($module, $payload);
        $result['redirect_tab'] = cms_admin_landing_page_action_tab_map()[$payload['action']] ?? $payload['active_tab'];

        return $result;
    },
];

require __DIR__ . '/partials/section-page-shell.php';
