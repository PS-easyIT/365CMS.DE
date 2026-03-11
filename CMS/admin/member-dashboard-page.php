<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$memberSection   = $memberSection ?? 'overview';
$memberRoutePath = $memberRoutePath ?? '/admin/member-dashboard';
$memberViewFile  = $memberViewFile ?? (__DIR__ . '/views/member/dashboard.php');
$pageTitle       = $pageTitle ?? 'Member Dashboard';
$activePage      = $activePage ?? 'member-dashboard';
$pageAssets      = $pageAssets ?? [];
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
