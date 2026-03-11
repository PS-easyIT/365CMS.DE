<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$systemSection = $systemSection ?? 'info';
$systemRoutePath = $systemRoutePath ?? '/admin/info';
$systemViewFile = $systemViewFile ?? (__DIR__ . '/views/system/info.php');
$pageTitle = $pageTitle ?? 'Info & Diagnose';
$activePage = $activePage ?? 'info';
$pageAssets = $pageAssets ?? [];
$sectionPageConfig = [
    'section' => (string)$systemSection,
    'route_path' => (string)$systemRoutePath,
    'view_file' => (string)$systemViewFile,
    'page_title' => (string)$pageTitle,
    'active_page' => (string)$activePage,
    'page_assets' => is_array($pageAssets) ? $pageAssets : [],
    'csrf_action' => 'admin_system_info',
    'guard_constant' => 'CMS_ADMIN_SYSTEM_VIEW',
    'module_file' => __DIR__ . '/modules/system/SystemInfoModule.php',
    'module_factory' => static function () {
        return new SystemInfoModule();
    },
    'post_handler' => static function ($module, string $section, array $postData): array {
        if (!$module instanceof SystemInfoModule) {
            return ['success' => false, 'error' => 'System-Modul konnte nicht initialisiert werden.'];
        }

        return $module->handleAction($section, (string)($postData['action'] ?? ''), $postData);
    },
    'data_loader' => static function ($module): array {
        return $module instanceof SystemInfoModule ? $module->getData() : [];
    },
];

require __DIR__ . '/partials/section-page-shell.php';
