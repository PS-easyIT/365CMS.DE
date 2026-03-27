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

function cms_admin_landing_page_can_access(): bool
{
    return Auth::instance()->isAdmin() && Auth::instance()->hasCapability(CMS_ADMIN_LANDING_PAGE_WRITE_CAPABILITY);
}

function cms_admin_landing_page_allowed_tabs(): array
{
    return CMS_ADMIN_LANDING_PAGE_ALLOWED_TABS;
}

function cms_admin_landing_page_normalize_tab(string $tab): string
{
    $normalizedTab = preg_replace('/[^a-z]/', '', $tab);

    return in_array($normalizedTab, cms_admin_landing_page_allowed_tabs(), true) ? $normalizedTab : 'header';
}

function cms_admin_landing_page_normalize_action(mixed $action): string
{
    $normalizedAction = trim((string) $action);

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
 * @return array{action:string,feature_id:int,post:array<string,mixed>}
 */
function cms_admin_landing_page_normalize_payload(array $post): array
{
    return [
        'action' => cms_admin_landing_page_normalize_action($post['action'] ?? ''),
        'feature_id' => cms_admin_landing_page_normalize_positive_id($post['feature_id'] ?? 0),
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
        $tab = cms_admin_landing_page_normalize_tab((string) ($_GET['tab'] ?? 'header'));

        return $module->getData($tab);
    },
    'access_checker' => static fn (): bool => cms_admin_landing_page_can_access(),
    'access_denied_route' => '/',
    'redirect_path_resolver' => static function (): string {
        $tab = cms_admin_landing_page_normalize_tab((string) ($_GET['tab'] ?? 'header'));

        return cms_admin_landing_page_tab_url($tab);
    },
    'unknown_action_message' => 'Unbekannte Aktion.',
    'post_handler' => static function (LandingPageModule $module, string $section, array $post): array {
        if (!Auth::instance()->hasCapability(CMS_ADMIN_LANDING_PAGE_WRITE_CAPABILITY)) {
            return ['success' => false, 'error' => 'Keine Berechtigung für diese Aktion.'];
        }

        $payload = cms_admin_landing_page_normalize_payload($post);

        if ($payload['action'] === '') {
            return ['success' => false, 'error' => 'Unbekannte Aktion.'];
        }

        if ($payload['action'] === 'delete_feature' && $payload['feature_id'] < 1) {
            return ['success' => false, 'error' => 'Ungültige Feature-ID.'];
        }

        return cms_admin_landing_page_handle_action($module, $payload);
    },
];

require __DIR__ . '/partials/section-page-shell.php';
