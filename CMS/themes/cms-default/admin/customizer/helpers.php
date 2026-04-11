<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Security;
use CMS\Services\MediaService;
use CMS\Services\ThemeCustomizer;

/**
 * @return list<string>
 */
function cms_default_theme_customizer_get_admin_menu_paths(): array
{
    return [
        (defined('ABSPATH') ? rtrim(ABSPATH, '/\\') : '') . '/admin/partials/admin-menu.php',
        dirname(__DIR__, 3) . '/CMS/admin/partials/admin-menu.php',
        dirname(__DIR__, 2) . '/admin/partials/admin-menu.php',
    ];
}

function cms_default_theme_customizer_resolve_active_tab(array $config, string $requestedTab): string
{
    return isset($config[$requestedTab]) ? $requestedTab : 'header';
}

function cms_default_theme_customizer_normalize_default_value(mixed $default): string
{
    if (is_bool($default)) {
        return $default ? '1' : '0';
    }

    return (string) $default;
}

function cms_default_theme_customizer_sanitize_asset_url(mixed $value): string
{
    $url = trim((string) $value);
    if ($url === '') {
        return '';
    }

    if (preg_match('/^[A-Za-z]:[\\\\\/]/', $url) === 1 || preg_match('#^(?:javascript|data|vbscript):#i', $url) === 1) {
        return '';
    }

    if (str_starts_with($url, '//')) {
        return '';
    }

    if (str_starts_with($url, '/')) {
        return $url;
    }

    if (!preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
        $relativePath = preg_replace('#^(?:\./)+#', '', str_replace('\\', '/', $url)) ?? '';
        $relativePath = ltrim($relativePath, '/');
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return '';
        }

        return '/' . $relativePath;
    }

    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return '';
    }

    $parts = parse_url($url);
    $scheme = strtolower((string) ($parts['scheme'] ?? ''));

    return in_array($scheme, ['http', 'https'], true) ? $url : '';
}

function cms_default_theme_customizer_build_uploaded_logo_path(string $storedLogo): string
{
    return cms_default_theme_customizer_sanitize_asset_url('/uploads/theme-logos/' . ltrim($storedLogo, '/'));
}

function cms_default_theme_customizer_verify_csrf(): bool
{
    if (function_exists('cms_admin_section_shell_was_csrf_verified')
        && cms_admin_section_shell_was_csrf_verified('theme_customizer')
    ) {
        return true;
    }

    return Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'theme_customizer');
}

/**
 * @return array{success:?string,error:?string,activeTab:string}
 */
function cms_default_theme_customizer_handle_post(ThemeCustomizer $customizer, array $config, string $activeTab): array
{
    $success = null;
    $error = null;

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return [
            'success' => $success,
            'error' => $error,
            'activeTab' => $activeTab,
        ];
    }

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'reset_theme_tab') {
        return cms_default_theme_customizer_handle_reset($customizer, $config, $activeTab);
    }

    if ($action === 'save_theme_options') {
        return cms_default_theme_customizer_handle_save($customizer, $config, $activeTab);
    }

    return [
        'success' => $success,
        'error' => 'Unbekannte oder nicht erlaubte Customizer-Aktion.',
        'activeTab' => $activeTab,
    ];
}

/**
 * @return array{success:?string,error:?string,activeTab:string}
 */
function cms_default_theme_customizer_handle_reset(ThemeCustomizer $customizer, array $config, string $activeTab): array
{
    if (!cms_default_theme_customizer_verify_csrf()) {
        return [
            'success' => null,
            'error' => 'Sicherheitscheck fehlgeschlagen. Bitte erneut versuchen.',
            'activeTab' => $activeTab,
        ];
    }

    $resetTab = cms_default_theme_customizer_resolve_active_tab($config, (string) ($_POST['active_section'] ?? $activeTab));

    foreach ($config[$resetTab]['sections'] as $fieldKey => $fieldConfig) {
        $default = cms_default_theme_customizer_normalize_default_value($fieldConfig['default'] ?? '');
        $customizer->set($resetTab, $fieldKey, $default);
    }

    if (class_exists('\\CMS\\Hooks')) {
        \CMS\Hooks::doAction('theme_customizer_save', 'cms-default', $resetTab, $config[$resetTab]['sections'] ?? [], ['mode' => 'reset']);
    }

    return [
        'success' => 'Einstellungen für „' . $config[$resetTab]['title'] . '“ auf Standardwerte zurückgesetzt.',
        'error' => null,
        'activeTab' => $resetTab,
    ];
}

/**
 * @return array{success:?string,error:?string,activeTab:string}
 */
function cms_default_theme_customizer_handle_save(ThemeCustomizer $customizer, array $config, string $activeTab): array
{
    if (!cms_default_theme_customizer_verify_csrf()) {
        return [
            'success' => null,
            'error' => 'Sicherheitscheck fehlgeschlagen. Bitte erneut versuchen.',
            'activeTab' => $activeTab,
        ];
    }

    $error = cms_default_theme_customizer_handle_logo_upload($customizer);

    if ($error !== null) {
        return [
            'success' => null,
            'error' => $error,
            'activeTab' => $activeTab,
        ];
    }

    $saveTab = cms_default_theme_customizer_resolve_active_tab($config, (string) ($_POST['active_section'] ?? $activeTab));

    foreach ($config[$saveTab]['sections'] as $fieldKey => $fieldConfig) {
        $sectionKey = $saveTab;
        $inputName = $sectionKey . '_' . $fieldKey;

        if ($sectionKey === 'header' && $fieldKey === 'logo_url') {
            $customizer->set($sectionKey, $fieldKey, cms_default_theme_customizer_sanitize_asset_url($_POST[$inputName] ?? ''));
            continue;
        }

        if (($fieldConfig['type'] ?? '') === 'checkbox') {
            $value = isset($_POST[$inputName]) ? '1' : '0';
        } elseif (($fieldConfig['type'] ?? '') === 'image_upload') {
            $value = $_POST[$inputName] ?? null;
            if ($value === null) {
                continue;
            }
        } else {
            $value = $_POST[$inputName] ?? '';
        }

        $customizer->set($sectionKey, $fieldKey, $value);
    }

    if (class_exists('\\CMS\\Hooks')) {
        \CMS\Hooks::doAction('theme_customizer_save', 'cms-default', $saveTab, $config[$saveTab]['sections'] ?? [], ['mode' => 'save']);
    }

    return [
        'success' => 'Einstellungen für „' . $config[$saveTab]['title'] . '“ gespeichert.',
        'error' => null,
        'activeTab' => $saveTab,
    ];
}

function cms_default_theme_customizer_handle_logo_upload(ThemeCustomizer $customizer): ?string
{
    $file = $_FILES['logo_upload_file'] ?? null;

    if (!is_array($file) || empty($file['tmp_name'])) {
        return null;
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'ico'];
    $fileExt = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));

    if (!in_array($fileExt, $allowedExts, true)) {
        return 'Ungültiges Dateiformat. Erlaubt: JPG, PNG, GIF, WebP, BMP, ICO';
    }

    $storedLogo = MediaService::getInstance()->uploadFile($file, 'theme-logos');
    if ($storedLogo instanceof \CMS\WP_Error) {
        return 'Logo-Upload fehlgeschlagen: ' . $storedLogo->get_error_message();
    }

    $customizer->set('header', 'logo_url', cms_default_theme_customizer_build_uploaded_logo_path((string) $storedLogo));

    return null;
}
