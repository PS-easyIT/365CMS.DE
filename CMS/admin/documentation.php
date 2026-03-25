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

$module = new DocumentationModule();
$alert = null;
$normalizeSelectedDoc = static function ($value): ?string {
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
};
$selectedDoc = $normalizeSelectedDoc($_GET['doc'] ?? null);
$actionHandlers = [
    'sync_docs' => static fn () => $module->syncDocsFromRepository(),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = (string) ($_POST['csrf_token'] ?? '');
    if (!Security::instance()->verifyToken($postToken, 'admin_documentation')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $handler = $actionHandlers[$action] ?? null;
        $result = is_callable($handler)
            ? $handler()
            : new DocumentationSyncActionResult(false, null, 'Unbekannte Aktion.');

        $_SESSION['admin_alert'] = [
            'type' => $result->isSuccess() ? 'success' : 'danger',
            'message' => $result->getMessage(),
        ];
    }

    $redirect = SITE_URL . '/admin/documentation';
    if ($selectedDoc !== null) {
        $redirect .= '?doc=' . rawurlencode($selectedDoc);
    }
    header('Location: ' . $redirect);
    exit;
}

if (isset($_SESSION['admin_alert']) && is_array($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

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
