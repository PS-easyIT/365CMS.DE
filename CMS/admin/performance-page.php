<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$performanceSection   = $performanceSection ?? 'overview';
$performanceRoutePath = $performanceRoutePath ?? '/admin/performance';
$performanceViewFile  = $performanceViewFile ?? (__DIR__ . '/views/seo/performance.php');
$pageTitle            = $pageTitle ?? 'Performance';
$activePage           = $activePage ?? 'performance';
$pageAssets           = $pageAssets ?? [];
$sectionPageConfig = [
    'section' => (string)$performanceSection,
    'route_path' => (string)$performanceRoutePath,
    'view_file' => (string)$performanceViewFile,
    'page_title' => (string)$pageTitle,
    'active_page' => (string)$activePage,
    'page_assets' => is_array($pageAssets) ? $pageAssets : [],
    'csrf_action' => 'admin_performance',
    'guard_constant' => 'CMS_ADMIN_PERFORMANCE_VIEW',
    'module_file' => __DIR__ . '/modules/seo/PerformanceModule.php',
    'module_factory' => static function () {
        return new PerformanceModule();
    },
    'post_handler' => static function ($module, string $section, array $postData): array {
        if (!$module instanceof PerformanceModule) {
            return ['success' => false, 'error' => 'Performance-Modul konnte nicht initialisiert werden.'];
        }

        return $module->handleAction($section, (string)($postData['action'] ?? ''), $postData);
    },
    'data_loader' => static function ($module): array {
        return $module instanceof PerformanceModule ? $module->getData() : [];
    },
];

require __DIR__ . '/partials/section-page-shell.php';
