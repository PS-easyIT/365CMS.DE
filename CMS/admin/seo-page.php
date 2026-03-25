<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/seo/SeoSuiteModule.php';

/**
 * @return array<string, array{route_path: string, view_file: string, page_title: string, active_page: string, actions: list<string>}>
 */
function cms_admin_seo_allowed_page_configs(): array
{
    return [
        'dashboard' => [
            'route_path' => '/admin/seo-dashboard',
            'view_file' => __DIR__ . '/views/seo/dashboard.php',
            'page_title' => 'SEO Dashboard',
            'active_page' => 'seo-dashboard',
            'actions' => ['regenerate_sitemap_bundle', 'save_robots'],
        ],
        'audit' => [
            'route_path' => '/admin/seo-audit',
            'view_file' => __DIR__ . '/views/seo/audit.php',
            'page_title' => 'SEO Audit',
            'active_page' => 'seo-audit',
            'actions' => ['save_audit_item', 'regenerate_sitemap_bundle', 'save_robots'],
        ],
        'meta' => [
            'route_path' => '/admin/seo-meta',
            'view_file' => __DIR__ . '/views/seo/meta.php',
            'page_title' => 'SEO Meta-Daten',
            'active_page' => 'seo-meta',
            'actions' => ['save_meta_defaults', 'save_templates', 'regenerate_sitemap_bundle', 'save_robots'],
        ],
        'social' => [
            'route_path' => '/admin/seo-social',
            'view_file' => __DIR__ . '/views/seo/social.php',
            'page_title' => 'SEO Social',
            'active_page' => 'seo-social',
            'actions' => ['save_social_defaults', 'regenerate_sitemap_bundle', 'save_robots'],
        ],
        'schema' => [
            'route_path' => '/admin/seo-schema',
            'view_file' => __DIR__ . '/views/seo/schema.php',
            'page_title' => 'SEO Strukturierte Daten',
            'active_page' => 'seo-schema',
            'actions' => ['save_schema_defaults', 'regenerate_sitemap_bundle', 'save_robots'],
        ],
        'sitemap' => [
            'route_path' => '/admin/seo-sitemap',
            'view_file' => __DIR__ . '/views/seo/sitemap.php',
            'page_title' => 'SEO Sitemap',
            'active_page' => 'seo-sitemap',
            'actions' => ['save_sitemap_settings', 'submit_indexing_urls', 'delete_google_url', 'regenerate_sitemap_bundle', 'save_robots'],
        ],
        'technical' => [
            'route_path' => '/admin/seo-technical',
            'view_file' => __DIR__ . '/views/seo/technical.php',
            'page_title' => 'Technisches SEO',
            'active_page' => 'seo-technical',
            'actions' => ['save_technical_settings', 'regenerate_sitemap_bundle', 'save_robots'],
        ],
        'analytics' => [
            'route_path' => '/admin/analytics',
            'view_file' => __DIR__ . '/views/seo/analytics.php',
            'page_title' => 'SEO Analytics',
            'active_page' => 'analytics',
            'actions' => ['save_analytics_settings', 'regenerate_sitemap_bundle', 'save_robots'],
        ],
    ];
}

/**
 * @param array<string, mixed> $pageConfig
 * @return array{
 *     section: string,
 *     route_path: string,
 *     view_file: string,
 *     page_title: string,
 *     active_page: string
 * }
 */
function cms_admin_seo_normalize_page_config(array $pageConfig): array
{
    $allowedConfigs = cms_admin_seo_allowed_page_configs();
    $section = strtolower(trim((string)($pageConfig['section'] ?? 'dashboard')));

    if (!isset($allowedConfigs[$section])) {
        $section = 'dashboard';
    }

    $normalized = $allowedConfigs[$section];

    return [
        'section' => $section,
        'route_path' => (string) $normalized['route_path'],
        'view_file' => (string) $normalized['view_file'],
        'page_title' => (string) $normalized['page_title'],
        'active_page' => (string) $normalized['active_page'],
    ];
}

/**
 * @return list<string>
 */
function cms_admin_seo_allowed_routes(): array
{
    $routes = [];

    foreach (cms_admin_seo_allowed_page_configs() as $config) {
        $routes[] = (string)$config['route_path'];
    }

    return array_values(array_unique($routes));
}

function cms_admin_seo_normalize_action(string $section, mixed $action): string
{
    $normalizedAction = strtolower(trim((string)$action));
    $allowedConfigs = cms_admin_seo_allowed_page_configs();
    $allowedActions = $allowedConfigs[$section]['actions'] ?? [];

    return in_array($normalizedAction, $allowedActions, true) ? $normalizedAction : '';
}

function cms_admin_seo_normalize_redirect_target(mixed $returnTo, string $fallback): string
{
    $target = trim((string)$returnTo);
    if ($target === '') {
        return $fallback;
    }

    $targetPath = (string)parse_url($target, PHP_URL_PATH);
    if ($targetPath === '') {
        return $fallback;
    }

    return in_array($targetPath, cms_admin_seo_allowed_routes(), true) ? $targetPath : $fallback;
}

function cms_admin_seo_flash(string $type, string $message): void
{
    $_SESSION['admin_alert'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function cms_admin_seo_redirect(string $path): void
{
    header('Location: ' . SITE_URL . $path);
    exit;
}

$seoPageConfig = cms_admin_seo_normalize_page_config(array_merge(
    [
        'section' => $seoSection ?? 'dashboard',
        'route_path' => $seoRoutePath ?? '/admin/seo-dashboard',
        'view_file' => $seoViewFile ?? (__DIR__ . '/views/seo/dashboard.php'),
        'page_title' => $pageTitle ?? 'SEO',
        'active_page' => $activePage ?? 'seo-dashboard',
    ],
    is_array($seoPageConfig ?? null) ? $seoPageConfig : []
));

$seoSection = $seoPageConfig['section'];
$seoRoutePath = $seoPageConfig['route_path'];
$seoViewFile = $seoPageConfig['view_file'];
$pageTitle = $seoPageConfig['page_title'];
$activePage = $seoPageConfig['active_page'];
$module = new SeoSuiteModule();
$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    $redirectTarget = cms_admin_seo_normalize_redirect_target($_POST['return_to'] ?? '', $seoRoutePath);

    if (!Security::instance()->verifyToken($postToken, 'admin_seo_suite')) {
        cms_admin_seo_flash('danger', 'Sicherheitstoken ungültig.');
        cms_admin_seo_redirect($redirectTarget);
    }

    $action = cms_admin_seo_normalize_action($seoSection, $_POST['action'] ?? '');
    if ($action === '') {
        cms_admin_seo_flash('danger', 'Unbekannte oder für diese SEO-Seite unzulässige Aktion.');
        cms_admin_seo_redirect($redirectTarget);
    }

    $result = $module->handleAction($seoSection, $action, $_POST);
    cms_admin_seo_flash(
        !empty($result['success']) ? 'success' : 'danger',
        (string)($result['message'] ?? $result['error'] ?? 'Unbekannte Antwort.')
    );

    cms_admin_seo_redirect($redirectTarget);
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken = Security::instance()->generateToken('admin_seo_suite');
$data = $module->getData((string)$seoSection);
defined('CMS_ADMIN_SEO_VIEW') || define('CMS_ADMIN_SEO_VIEW', true);

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require $seoViewFile;
require __DIR__ . '/partials/footer.php';
