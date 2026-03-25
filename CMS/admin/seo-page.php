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
    $defaults = [
        'section' => 'dashboard',
        'route_path' => '/admin/seo-dashboard',
        'view_file' => __DIR__ . '/views/seo/dashboard.php',
        'page_title' => 'SEO',
        'active_page' => 'seo-dashboard',
    ];

    $normalized = array_merge($defaults, $pageConfig);

    return [
        'section' => (string) ($normalized['section'] ?? $defaults['section']),
        'route_path' => (string) ($normalized['route_path'] ?? $defaults['route_path']),
        'view_file' => (string) ($normalized['view_file'] ?? $defaults['view_file']),
        'page_title' => (string) ($normalized['page_title'] ?? $defaults['page_title']),
        'active_page' => (string) ($normalized['active_page'] ?? $defaults['active_page']),
    ];
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

$redirectTarget = static function (string $fallback) : string {
    $returnTo = (string) ($_POST['return_to'] ?? '');
    if ($returnTo !== '' && str_starts_with($returnTo, '/admin/')) {
        return $returnTo;
    }

    return $fallback;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!Security::instance()->verifyToken($postToken, 'admin_seo_suite')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . $redirectTarget($seoRoutePath));
        exit;
    }

    $result = $module->handleAction((string)$seoSection, (string)($_POST['action'] ?? ''), $_POST);
    $_SESSION['admin_alert'] = [
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => $result['message'] ?? $result['error'] ?? 'Unbekannte Antwort.',
    ];

    header('Location: ' . SITE_URL . $redirectTarget($seoRoutePath));
    exit;
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
