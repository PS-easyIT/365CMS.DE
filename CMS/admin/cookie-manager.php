<?php
declare(strict_types=1);

/**
 * Cookie Manager – Entry Point
 * Route: /admin/cookie-manager
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;

function cms_admin_cookie_manager_normalize_action(mixed $value): ?string
{
    $action = strtolower(trim((string) $value));

    return cms_admin_cookie_manager_is_allowed_action($action) ? $action : null;
}

function cms_admin_cookie_manager_normalize_positive_id(array $post): int
{
    $id = (int) ($post['id'] ?? 0);

    return $id > 0 ? $id : 0;
}

function cms_admin_cookie_manager_normalize_service_slug(array $post): string
{
    return preg_replace('/[^a-z0-9_-]/', '', strtolower(trim((string) ($post['service_slug'] ?? '')))) ?? '';
}

function cms_admin_cookie_manager_normalize_self_hosted(array $post, string $serviceSlug): bool
{
    if ($serviceSlug !== 'matomo') {
        return false;
    }

    return isset($post['self_hosted']) && (string) $post['self_hosted'] === '1';
}

/**
 * @return array{action:?string,id:int,service_slug:string,self_hosted:bool,post:array<string,mixed>}
 */
function cms_admin_cookie_manager_normalize_payload(array $post): array
{
    $serviceSlug = cms_admin_cookie_manager_normalize_service_slug($post);

    return [
        'action' => cms_admin_cookie_manager_normalize_action($post['action'] ?? null),
        'id' => cms_admin_cookie_manager_normalize_positive_id($post),
        'service_slug' => $serviceSlug,
        'self_hosted' => cms_admin_cookie_manager_normalize_self_hosted($post, $serviceSlug),
        'post' => $post,
    ];
}

function cms_admin_cookie_manager_handle_action(CookieManagerModule $module, array $payload): array
{
    return match ($payload['action']) {
        'save_settings' => $module->saveSettings($payload['post']),
        'save_category' => $module->saveCategory($payload['post']),
        'delete_category' => $module->deleteCategory($payload['id']),
        'save_service' => $module->saveService($payload['post']),
        'delete_service' => $module->deleteService($payload['id']),
        'import_curated_service' => $module->importCuratedService(
            $payload['service_slug'],
            $payload['self_hosted'],
            $payload['post']
        ),
        'run_scan' => $module->runScanner(),
        default => ['success' => false, 'error' => 'Aktion konnte nicht verarbeitet werden.'],
    };
}

/** @return array<string, true> */
function cms_admin_cookie_manager_allowed_actions(): array
{
    return [
        'save_settings' => true,
        'save_category' => true,
        'delete_category' => true,
        'save_service' => true,
        'delete_service' => true,
        'import_curated_service' => true,
        'run_scan' => true,
    ];
}

function cms_admin_cookie_manager_is_allowed_action(string $action): bool
{
    return isset(cms_admin_cookie_manager_allowed_actions()[$action]);
}

$sectionPageConfig = [
    'section' => 'cookie-manager',
    'route_path' => '/admin/cookie-manager',
    'view_file' => __DIR__ . '/views/legal/cookies.php',
    'page_title' => 'Cookie Manager',
    'active_page' => 'cookie-manager',
    'page_assets' => [
        'js' => [
            cms_asset_url('js/admin-cookie-manager.js'),
        ],
    ],
    'csrf_action' => 'admin_cookies',
    'module_file' => __DIR__ . '/modules/legal/CookieManagerModule.php',
    'module_factory' => static fn (): CookieManagerModule => new CookieManagerModule(),
    'data_loader' => static fn (CookieManagerModule $module): array => $module->getData(),
    'access_checker' => static fn (): bool => Auth::instance()->isAdmin(),
    'invalid_token_message' => 'Sicherheitstoken ungültig.',
    'unknown_action_message' => 'Aktion konnte nicht verarbeitet werden.',
    'post_handler' => static function (CookieManagerModule $module, string $section, array $post): array {
        $payload = cms_admin_cookie_manager_normalize_payload($post);
        if ($payload['action'] === null) {
            return ['success' => false, 'error' => 'Unbekannte Aktion.'];
        }

        if (in_array($payload['action'], ['delete_category', 'delete_service'], true)
            && $payload['id'] <= 0
        ) {
            return ['success' => false, 'error' => 'Ungültige ID.'];
        }

        if ($payload['action'] === 'import_curated_service' && $payload['service_slug'] === '') {
            return ['success' => false, 'error' => 'Unbekannter Standard-Service.'];
        }

        return cms_admin_cookie_manager_handle_action($module, $payload);
    },
];

require __DIR__ . '/partials/section-page-shell.php';
