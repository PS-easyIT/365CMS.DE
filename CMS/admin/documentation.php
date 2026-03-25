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
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/system/DocumentationModule.php';

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
    $redirect = SITE_URL . '/admin/documentation';
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

function cms_admin_documentation_flash_result(DocumentationSyncActionResult $result): void
{
    $_SESSION['admin_alert'] = [
        'type' => $result->isSuccess() ? 'success' : 'danger',
        'message' => $result->getMessage(),
    ];
}

function cms_admin_documentation_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
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

/**
 * @return array<string, callable(): DocumentationSyncActionResult>
 */
function cms_admin_documentation_action_handlers(DocumentationModule $module): array
{
    return [
        'sync_docs' => static fn (): DocumentationSyncActionResult => $module->syncDocsFromRepository(),
    ];
}

$module = new DocumentationModule();
$alert = null;
$selectedDoc = cms_admin_documentation_normalize_selected_doc($_GET['doc'] ?? null);
$actionHandlers = cms_admin_documentation_action_handlers($module);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = (string) ($_POST['csrf_token'] ?? '');
    if (!Security::instance()->verifyToken($postToken, 'admin_documentation')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
    } else {
        $action = cms_admin_documentation_normalize_action($_POST['action'] ?? null);
        $handler = $actionHandlers[$action] ?? null;
        $result = is_callable($handler)
            ? $handler()
            : new DocumentationSyncActionResult(false, null, 'Unbekannte oder nicht erlaubte Aktion.');

        cms_admin_documentation_flash_result($result);
    }

    cms_admin_documentation_redirect($selectedDoc);
}

$alert = cms_admin_documentation_pull_alert();

$csrfToken = Security::instance()->generateToken('admin_documentation');
$data = $module->getData($selectedDoc)->toArray();

$pageTitle = 'Dokumentation';
$activePage = 'documentation';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
defined('CMS_ADMIN_SYSTEM_VIEW') || define('CMS_ADMIN_SYSTEM_VIEW', true);
require __DIR__ . '/views/system/documentation.php';
require __DIR__ . '/partials/footer.php';
