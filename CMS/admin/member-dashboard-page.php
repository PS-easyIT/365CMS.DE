<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

const CMS_ADMIN_MEMBER_DASHBOARD_SECTION_CAPABILITIES = [
    'overview' => ['manage_settings', 'manage_users'],
    'general' => ['manage_settings'],
    'widgets' => ['manage_settings'],
    'profile-fields' => ['manage_users'],
    'design' => ['manage_settings'],
    'frontend-modules' => ['manage_settings'],
    'notifications' => ['manage_settings'],
    'onboarding' => ['manage_settings'],
    'plugin-widgets' => ['manage_settings'],
];

const CMS_ADMIN_MEMBER_DASHBOARD_ALLOWED_ACTIONS = ['save'];

if (!function_exists('cms_admin_member_dashboard_normalize_section')) {
    function cms_admin_member_dashboard_normalize_section(string $section): string
    {
        $section = trim($section);

        return array_key_exists($section, CMS_ADMIN_MEMBER_DASHBOARD_SECTION_CAPABILITIES) ? $section : 'overview';
    }
}

if (!function_exists('cms_admin_member_dashboard_has_any_capability')) {
    /**
     * @param list<string> $capabilities
     */
    function cms_admin_member_dashboard_has_any_capability(array $capabilities): bool
    {
        foreach ($capabilities as $capability) {
            if (\CMS\Auth::instance()->hasCapability($capability)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('cms_admin_member_dashboard_can_access_section')) {
    function cms_admin_member_dashboard_can_access_section(string $section): bool
    {
        $normalizedSection = cms_admin_member_dashboard_normalize_section($section);
        $requiredCapabilities = CMS_ADMIN_MEMBER_DASHBOARD_SECTION_CAPABILITIES[$normalizedSection] ?? [];

        return \CMS\Auth::instance()->isAdmin()
            && $requiredCapabilities !== []
            && cms_admin_member_dashboard_has_any_capability($requiredCapabilities);
    }
}

if (!function_exists('cms_admin_member_dashboard_normalize_action')) {
    function cms_admin_member_dashboard_normalize_action(mixed $action): string
    {
        $normalizedAction = trim((string) $action);

        return in_array($normalizedAction, CMS_ADMIN_MEMBER_DASHBOARD_ALLOWED_ACTIONS, true) ? $normalizedAction : '';
    }
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

$memberSection   = cms_admin_member_dashboard_normalize_section($memberDashboardPageConfig['section']);
$memberRoutePath = $memberDashboardPageConfig['route_path'];
$memberViewFile  = $memberDashboardPageConfig['view_file'];
$pageTitle       = $memberDashboardPageConfig['page_title'];
$activePage      = $memberDashboardPageConfig['active_page'];
$pageAssets      = $memberDashboardPageConfig['page_assets'];

if (!cms_admin_member_dashboard_can_access_section($memberSection)) {
    header('Location: ' . SITE_URL);
    exit;
}

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

        $normalizedSection = cms_admin_member_dashboard_normalize_section($section);
        $action = cms_admin_member_dashboard_normalize_action($postData['action'] ?? '');
        if ($action === '') {
            return ['success' => false, 'error' => 'Unbekannte Aktion.'];
        }

        if (!cms_admin_member_dashboard_can_access_section($normalizedSection)) {
            return ['success' => false, 'error' => 'Sie dürfen diesen Einstellungsbereich nicht bearbeiten.'];
        }

        $postData['_csrf_verified'] = true;

        return $action === 'save'
            ? $module->saveSection($normalizedSection, $postData)
            : ['success' => false, 'error' => 'Unbekannte Aktion.'];
    },
    'data_loader' => static function ($module) use ($memberSection): array {
        return $module instanceof MemberDashboardModule
            ? $module->getSectionData((string) $memberSection)
            : [];
    },
    'unknown_action_message' => 'Unbekannte Aktion.',
];

require __DIR__ . '/partials/section-page-shell.php';
