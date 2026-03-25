<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('cms_admin_member_dashboard_normalize_page_config')) {
    /**
     * @param array<string, mixed> $pageConfig
     * @return array{section:string,route_path:string,view_file:string,page_title:string,active_page:string,page_assets:array<int|string,mixed>}
     */
    function cms_admin_member_dashboard_normalize_page_config(array $pageConfig): array
    {
        $resolvedPageAssets = $pageConfig['page_assets'] ?? [];

        return [
            'section' => (string)($pageConfig['section'] ?? 'overview'),
            'route_path' => (string)($pageConfig['route_path'] ?? '/admin/member-dashboard'),
            'view_file' => (string)($pageConfig['view_file'] ?? (__DIR__ . '/views/member/dashboard.php')),
            'page_title' => (string)($pageConfig['page_title'] ?? 'Member Dashboard'),
            'active_page' => (string)($pageConfig['active_page'] ?? 'member-dashboard'),
            'page_assets' => is_array($resolvedPageAssets) ? $resolvedPageAssets : [],
        ];
    }
}

$memberDashboardPageConfig = cms_admin_member_dashboard_normalize_page_config(
    array_merge(
        [
            'section' => $memberSection ?? 'overview',
            'route_path' => $memberRoutePath ?? '/admin/member-dashboard',
            'view_file' => $memberViewFile ?? (__DIR__ . '/views/member/dashboard.php'),
            'page_title' => $pageTitle ?? 'Member Dashboard',
            'active_page' => $activePage ?? 'member-dashboard',
            'page_assets' => $pageAssets ?? [],
        ],
        isset($memberDashboardPageConfig) && is_array($memberDashboardPageConfig)
            ? $memberDashboardPageConfig
            : []
    )
);

$memberSection   = $memberDashboardPageConfig['section'];
$memberRoutePath = $memberDashboardPageConfig['route_path'];
$memberViewFile  = $memberDashboardPageConfig['view_file'];
$pageTitle       = $memberDashboardPageConfig['page_title'];
$activePage      = $memberDashboardPageConfig['active_page'];
$pageAssets      = $memberDashboardPageConfig['page_assets'];
$sectionPageConfig = [
    'section' => (string)$memberSection,
    'route_path' => (string)$memberRoutePath,
    'view_file' => (string)$memberViewFile,
    'page_title' => (string)$pageTitle,
    'active_page' => (string)$activePage,
    'page_assets' => is_array($pageAssets) ? $pageAssets : [],
    'csrf_action' => 'admin_member_dashboard',
    'guard_constant' => 'CMS_ADMIN_MEMBER_VIEW',
    'module_file' => __DIR__ . '/modules/member/MemberDashboardModule.php',
    'module_factory' => static function () {
        return new MemberDashboardModule();
    },
    'post_handler' => static function ($module, string $section, array $postData): array {
        if (!$module instanceof MemberDashboardModule) {
            return ['success' => false, 'error' => 'Member-Dashboard-Modul konnte nicht initialisiert werden.'];
        }

        $postData['_csrf_verified'] = true;

        $action = (string)($postData['action'] ?? '');

        return $action === 'save'
            ? $module->saveSection($section, $postData)
            : ['success' => false, 'error' => 'Unbekannte Aktion.'];
    },
    'data_loader' => static function ($module): array {
        return $module instanceof MemberDashboardModule ? $module->getData() : [];
    },
    'unknown_action_message' => 'Unbekannte Aktion.',
];

require __DIR__ . '/partials/section-page-shell.php';
