<?php
declare(strict_types=1);

/**
 * Dokumentation – Entry Point
 * Route: /admin/documentation
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;

const CMS_ADMIN_DOCUMENTATION_READ_CAPABILITIES = ['manage_settings', 'manage_system'];
const CMS_ADMIN_DOCUMENTATION_WRITE_CAPABILITY = 'manage_settings';

function cms_admin_documentation_has_any_capability(array $capabilities): bool
{
    foreach ($capabilities as $capability) {
        if (is_string($capability) && $capability !== '' && Auth::instance()->hasCapability($capability)) {
            return true;
        }
    }

    return false;
}

function cms_admin_documentation_can_access(): bool
{
    return Auth::instance()->isAdmin()
        && cms_admin_documentation_has_any_capability(CMS_ADMIN_DOCUMENTATION_READ_CAPABILITIES);
}

function cms_admin_documentation_can_mutate(): bool
{
    return cms_admin_documentation_can_access()
        && Auth::instance()->hasCapability(CMS_ADMIN_DOCUMENTATION_WRITE_CAPABILITY);
}

function cms_admin_documentation_substring(string $value, int $start, ?int $length = null): string
{
    if (function_exists('mb_substr')) {
        return $length === null
            ? (string) mb_substr($value, $start, null, 'UTF-8')
            : (string) mb_substr($value, $start, $length, 'UTF-8');
    }

    return $length === null ? substr($value, $start) : substr($value, $start, $length);
}

function cms_admin_documentation_normalize_selected_doc($value): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '';
    $value = preg_replace('/\s+/u', ' ', $value) ?? '';
    $value = str_replace('\\', '/', $value);
    $value = ltrim($value, '/');
    if ($value === '' || str_contains($value, '../') || str_contains($value, '/..')) {
        return null;
    }

    $value = cms_admin_documentation_substring($value, 0, 240);

    $extension = strtolower((string) pathinfo($value, PATHINFO_EXTENSION));

    return in_array($extension, ['md', 'csv'], true) ? $value : null;
}

function cms_admin_documentation_redirect_url(?string $selectedDoc): string
{
    $redirect = '/admin/documentation';
    if ($selectedDoc !== null) {
        $redirect .= '?doc=' . rawurlencode($selectedDoc);
    }

    return $redirect;
}

function cms_admin_documentation_redirect(?string $selectedDoc): never
{
    header('Location: ' . cms_admin_documentation_redirect_url($selectedDoc));
    exit;
}

/**
 * @return list<string>
 */
function cms_admin_documentation_allowed_actions(): array
{
    return ['sync_docs'];
}

function cms_admin_documentation_normalize_action($value): ?string
{
    $action = trim((string) $value);

    return in_array($action, cms_admin_documentation_allowed_actions(), true) ? $action : null;
}

/** @return array{action:?string} */
function cms_admin_documentation_normalize_post_payload(array $post): array
{
    return [
        'action' => cms_admin_documentation_normalize_action($post['action'] ?? null),
    ];
}

function cms_admin_documentation_handle_action(DocumentationModule $module, ?string $action): DocumentationSyncActionResult
{
    return match ($action) {
        'sync_docs' => $module->syncDocsFromRepository(),
        default => new DocumentationSyncActionResult(false, null, 'Unbekannte oder nicht erlaubte Aktion.'),
    };
}

$sectionPageConfig = [
    'section' => 'documentation',
    'route_path' => '/admin/documentation',
    'view_file' => __DIR__ . '/views/system/documentation.php',
    'page_title' => 'Dokumentation',
    'active_page' => 'documentation',
    'page_assets' => [],
    'guard_constant' => 'CMS_ADMIN_SYSTEM_VIEW',
    'csrf_action' => 'admin_documentation',
    'module_file' => __DIR__ . '/modules/system/DocumentationModule.php',
    'module_factory' => static fn (): DocumentationModule => new DocumentationModule(),
    'data_loader' => static fn (DocumentationModule $module): array => $module->getData(cms_admin_documentation_normalize_selected_doc($_GET['doc'] ?? null))->toArray(),
    'access_checker' => static fn (): bool => cms_admin_documentation_can_access(),
    'redirect_path_resolver' => static fn (): string => cms_admin_documentation_redirect_url(cms_admin_documentation_normalize_selected_doc($_GET['doc'] ?? null)),
    'invalid_token_message' => 'Sicherheitstoken ungültig.',
    'unknown_action_message' => 'Unbekannte oder nicht erlaubte Aktion.',
    'post_handler' => static function (DocumentationModule $module, string $section, array $post): array {
        if (!cms_admin_documentation_can_mutate()) {
            return ['success' => false, 'error' => 'Keine Berechtigung für Dokumentations-Synchronisationen.'];
        }

        $normalizedPost = cms_admin_documentation_normalize_post_payload($post);
        $result = cms_admin_documentation_handle_action($module, $normalizedPost['action']);

        return [
            'success' => $result->isSuccess(),
            'message' => $result->getMessage(),
        ];
    },
];

require __DIR__ . '/partials/section-page-shell.php';
