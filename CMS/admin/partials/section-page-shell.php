<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

function cms_admin_section_shell_normalize_alert_type(mixed $type, string $fallback = 'danger'): string
{
    $allowedTypes = ['success', 'danger', 'error', 'warning', 'info', 'secondary'];
    $normalizedFallback = strtolower(trim($fallback));
    if (!in_array($normalizedFallback, $allowedTypes, true)) {
        $normalizedFallback = 'danger';
    }

    $alertType = strtolower(trim((string) $type));

    return in_array($alertType, $allowedTypes, true) ? $alertType : $normalizedFallback;
}

function cms_admin_section_shell_flash(string $sessionKey, array $payload): void
{
    $_SESSION[$sessionKey] = [
        'type' => cms_admin_section_shell_normalize_alert_type($payload['type'] ?? 'danger'),
        'message' => trim((string) ($payload['message'] ?? '')),
        'details' => is_array($payload['details'] ?? null) ? $payload['details'] : [],
        'error_details' => is_array($payload['error_details'] ?? null) ? $payload['error_details'] : [],
        'report_payload' => is_array($payload['report_payload'] ?? null) ? $payload['report_payload'] : [],
    ];
}

function cms_admin_section_shell_pull_flash(string $sessionKey): ?array
{
    $alert = $_SESSION[$sessionKey] ?? null;
    unset($_SESSION[$sessionKey]);

    return is_array($alert) ? $alert : null;
}

function cms_admin_section_shell_normalize_route_path(mixed $routePath): string
{
    $path = trim((string) $routePath);
    if ($path === '') {
        return '/admin';
    }

    $parsedPath = (string) parse_url($path, PHP_URL_PATH);
    if ($parsedPath !== '') {
        $path = $parsedPath;
    }

    $path = '/' . ltrim($path, '/');

    return $path === '//' ? '/admin' : $path;
}

function cms_admin_section_shell_resolve_redirect_target(mixed $resolver, string $defaultRoutePath, mixed $module = null, string $section = '', mixed $result = null): string
{
    $resolvedTarget = is_callable($resolver)
        ? trim((string) $resolver($module, $section, $result))
        : '';

    if ($resolvedTarget === '') {
        return $defaultRoutePath;
    }

    $query = (string) parse_url($resolvedTarget, PHP_URL_QUERY);
    $path = cms_admin_section_shell_normalize_route_path($resolvedTarget);

    return $query !== '' ? $path . '?' . $query : $path;
}

function cms_admin_section_shell_require_view_file(mixed $viewFile): string
{
    $resolvedViewFile = (string) $viewFile;
    if ($resolvedViewFile === '' || !is_file($resolvedViewFile)) {
        throw new RuntimeException('Admin-Section-Shell erwartet eine gültige vorhandene view_file-Konfiguration.');
    }

    return $resolvedViewFile;
}

function cms_admin_section_shell_redirect(string $routePath): never
{
    header('Location: ' . SITE_URL . $routePath);
    exit;
}

function cms_admin_section_shell_mark_csrf_verified(string $csrfAction): void
{
    if (!isset($GLOBALS['cms_admin_verified_csrf_actions']) || !is_array($GLOBALS['cms_admin_verified_csrf_actions'])) {
        $GLOBALS['cms_admin_verified_csrf_actions'] = [];
    }

    $GLOBALS['cms_admin_verified_csrf_actions'][$csrfAction] = true;
}

function cms_admin_section_shell_was_csrf_verified(string $csrfAction): bool
{
    return !empty($GLOBALS['cms_admin_verified_csrf_actions'][$csrfAction]);
}

function cms_admin_section_shell_normalize_page_assets(mixed $pageAssets): array
{
    $resolvedPageAssets = is_array($pageAssets) ? $pageAssets : [];
    $cssAssets = is_array($resolvedPageAssets['css'] ?? null) ? array_values($resolvedPageAssets['css']) : [];
    $jsAssets = is_array($resolvedPageAssets['js'] ?? null) ? array_values($resolvedPageAssets['js']) : [];

    return [
        'css' => array_values(array_filter($cssAssets, static fn (mixed $asset): bool => is_string($asset) && trim($asset) !== '')),
        'js' => array_values(array_filter($jsAssets, static fn (mixed $asset): bool => is_string($asset) && trim($asset) !== '')),
    ];
}

function cms_admin_section_shell_normalize_template_vars(mixed $templateVars): array
{
    if (!is_array($templateVars)) {
        return [];
    }

    $normalized = [];
    foreach ($templateVars as $key => $value) {
        $variableName = trim((string) $key);
        if ($variableName === '' || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $variableName) !== 1) {
            continue;
        }

        $normalized[$variableName] = $value;
    }

    return $normalized;
}

function cms_admin_section_shell_apply_runtime_context(array $runtimeContext, string &$section, string &$viewFile, string &$pageTitle, string &$activePage, array &$pageAssets, array &$templateVars, mixed &$resolvedData): void
{
    $section = (string) ($runtimeContext['section'] ?? $section);
    $viewFile = cms_admin_section_shell_require_view_file($runtimeContext['view_file'] ?? $viewFile);
    $pageTitle = (string) ($runtimeContext['page_title'] ?? $pageTitle);
    $activePage = (string) ($runtimeContext['active_page'] ?? $activePage);
    $pageAssets = cms_admin_section_shell_normalize_page_assets($runtimeContext['page_assets'] ?? $pageAssets);
    $templateVars = array_merge($templateVars, cms_admin_section_shell_normalize_template_vars($runtimeContext['template_vars'] ?? []));

    if (array_key_exists('data', $runtimeContext)) {
        $resolvedData = $runtimeContext['data'];
    }
}

$sectionPageConfig = is_array($sectionPageConfig ?? null) ? $sectionPageConfig : [];
$routePath = cms_admin_section_shell_normalize_route_path($sectionPageConfig['route_path'] ?? '/admin');
$viewFile = cms_admin_section_shell_require_view_file($sectionPageConfig['view_file'] ?? '');
$pageTitle = (string)($sectionPageConfig['page_title'] ?? 'Admin');
$activePage = (string)($sectionPageConfig['active_page'] ?? 'dashboard');
$pageAssets = cms_admin_section_shell_normalize_page_assets($sectionPageConfig['page_assets'] ?? []);
$section = (string)($sectionPageConfig['section'] ?? 'overview');
$csrfAction = (string)($sectionPageConfig['csrf_action'] ?? 'admin_section');
$guardConstant = (string)($sectionPageConfig['guard_constant'] ?? '');
$moduleFile = (string)($sectionPageConfig['module_file'] ?? '');
$moduleFactory = $sectionPageConfig['module_factory'] ?? null;
$postHandler = $sectionPageConfig['post_handler'] ?? null;
$dataLoader = $sectionPageConfig['data_loader'] ?? null;
$accessChecker = $sectionPageConfig['access_checker'] ?? null;
$redirectPathResolver = $sectionPageConfig['redirect_path_resolver'] ?? null;
$requestContextResolver = $sectionPageConfig['request_context_resolver'] ?? null;
$accessDeniedRoute = cms_admin_section_shell_normalize_route_path($sectionPageConfig['access_denied_route'] ?? '/');
$alertSessionKey = (string)($sectionPageConfig['alert_session_key'] ?? 'admin_alert');
$invalidTokenMessage = (string)($sectionPageConfig['invalid_token_message'] ?? 'Sicherheitstoken ungültig.');
$unknownActionMessage = (string)($sectionPageConfig['unknown_action_message'] ?? 'Unbekannte Antwort.');
$templateVars = cms_admin_section_shell_normalize_template_vars($sectionPageConfig['template_vars'] ?? []);

$canAccess = is_callable($accessChecker)
    ? (bool) $accessChecker($sectionPageConfig, $section)
    : Auth::instance()->isAdmin();

if (!$canAccess) {
    cms_admin_section_shell_redirect($accessDeniedRoute);
}

if ($moduleFile !== '') {
    require_once $moduleFile;
}

if (!is_callable($moduleFactory)) {
    throw new RuntimeException('Admin-Section-Shell erwartet eine callable module_factory-Konfiguration.');
}

$module = $moduleFactory();
$runtimeContext = is_callable($requestContextResolver)
    ? $requestContextResolver($module, $section, $sectionPageConfig)
    : [];
$runtimeContext = is_array($runtimeContext) ? $runtimeContext : [];
$resolvedData = array_key_exists('data', $runtimeContext) ? $runtimeContext['data'] : null;
cms_admin_section_shell_apply_runtime_context($runtimeContext, $section, $viewFile, $pageTitle, $activePage, $pageAssets, $templateVars, $resolvedData);

$redirectTarget = cms_admin_section_shell_resolve_redirect_target($redirectPathResolver, $routePath, $module, $section);
$alert = cms_admin_section_shell_pull_flash($alertSessionKey);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!Security::instance()->verifyToken($postToken, $csrfAction)) {
        cms_admin_section_shell_flash($alertSessionKey, ['type' => 'danger', 'message' => $invalidTokenMessage]);
        cms_admin_section_shell_redirect($redirectTarget);
    }

    cms_admin_section_shell_mark_csrf_verified($csrfAction);

    $result = is_callable($postHandler)
        ? $postHandler($module, $section, $_POST)
        : ['success' => false, 'error' => $unknownActionMessage];

    $redirectTarget = cms_admin_section_shell_resolve_redirect_target($redirectPathResolver, $routePath, $module, $section, $result);

    if (!empty($result['render_inline'])) {
        $alert = [
            'type' => cms_admin_section_shell_normalize_alert_type(
                $result['type'] ?? (!empty($result['success']) ? 'success' : 'danger'),
                !empty($result['success']) ? 'success' : 'danger'
            ),
            'message' => (string) ($result['message'] ?? $result['error'] ?? $unknownActionMessage),
            'details' => is_array($result['details'] ?? null) ? $result['details'] : [],
            'error_details' => is_array($result['error_details'] ?? null) ? $result['error_details'] : [],
            'report_payload' => is_array($result['report_payload'] ?? null) ? $result['report_payload'] : [],
        ];

        $inlineRuntimeContext = is_array($result['runtime_context'] ?? null) ? $result['runtime_context'] : [];
        if ($inlineRuntimeContext !== []) {
            cms_admin_section_shell_apply_runtime_context($inlineRuntimeContext, $section, $viewFile, $pageTitle, $activePage, $pageAssets, $templateVars, $resolvedData);
        }
    } else {
        cms_admin_section_shell_flash($alertSessionKey, [
            'type' => cms_admin_section_shell_normalize_alert_type(
                $result['type'] ?? (!empty($result['success']) ? 'success' : 'danger'),
                !empty($result['success']) ? 'success' : 'danger'
            ),
            'message' => $result['message'] ?? $result['error'] ?? $unknownActionMessage,
            'details' => $result['details'] ?? [],
            'error_details' => $result['error_details'] ?? [],
            'report_payload' => $result['report_payload'] ?? [],
        ]);

        cms_admin_section_shell_redirect($redirectTarget);
    }
}

$csrfToken = Security::instance()->generateToken($csrfAction);
$data = $resolvedData;
if ($data === null) {
    $data = is_callable($dataLoader)
        ? $dataLoader($module)
        : (method_exists($module, 'getData') ? $module->getData() : []);
}

if ($guardConstant !== '' && !defined($guardConstant)) {
    define($guardConstant, true);
}

if ($templateVars !== []) {
    extract($templateVars, EXTR_SKIP);
}

require __DIR__ . '/header.php';
require __DIR__ . '/sidebar.php';
require $viewFile;
require __DIR__ . '/footer.php';
