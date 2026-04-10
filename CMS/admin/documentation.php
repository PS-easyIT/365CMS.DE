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

    if (function_exists('mb_substr')) {
        $value = mb_substr($value, 0, 240);
    } else {
        $value = substr($value, 0, 240);
    }

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
    'access_checker' => static fn (): bool => Auth::instance()->isAdmin(),
    'redirect_path_resolver' => static fn (): string => cms_admin_documentation_redirect_url(cms_admin_documentation_normalize_selected_doc($_GET['doc'] ?? null)),
    'invalid_token_message' => 'Sicherheitstoken ungültig.',
    'unknown_action_message' => 'Unbekannte oder nicht erlaubte Aktion.',
    'post_handler' => static function (DocumentationModule $module, string $section, array $post): array {
        $normalizedPost = cms_admin_documentation_normalize_post_payload($post);
        $result = cms_admin_documentation_handle_action($module, $normalizedPost['action']);

        return [
            'success' => $result->isSuccess(),
            'message' => $result->getMessage(),
        ];
    },
];

require __DIR__ . '/partials/section-page-shell.php';
