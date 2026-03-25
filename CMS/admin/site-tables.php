<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Site Tables – Entry Point
 * Route: /admin/site-tables
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/tables/TablesModule.php';
$module    = new TablesModule();
$alert     = null;

/**
 * @return array{save_settings:save_settings,save:save,delete:delete,duplicate:duplicate}
 */
function cms_admin_site_tables_allowed_actions(): array
{
    return [
        'save_settings' => 'save_settings',
        'save' => 'save',
        'delete' => 'delete',
        'duplicate' => 'duplicate',
    ];
}

/**
 * @return array{list:list,settings:settings,edit:edit}
 */
function cms_admin_site_tables_allowed_views(): array
{
    return [
        'list' => 'list',
        'settings' => 'settings',
        'edit' => 'edit',
    ];
}

function cms_admin_site_tables_redirect(string $path = '/admin/site-tables'): never
{
    header('Location: ' . SITE_URL . $path);
    exit;
}

function cms_admin_site_tables_flash(array $result): void
{
    $_SESSION['admin_alert'] = [
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => trim((string) ($result['message'] ?? $result['error'] ?? 'Unbekannte Antwort.')),
    ];
}

function cms_admin_site_tables_normalize_action(mixed $action): string
{
    $action = is_string($action) ? trim($action) : '';
    $allowedActions = cms_admin_site_tables_allowed_actions();

    return $allowedActions[$action] ?? '';
}

function cms_admin_site_tables_normalize_view_action(mixed $action): string
{
    $action = is_string($action) ? trim($action) : '';
    $allowedViews = cms_admin_site_tables_allowed_views();

    return $allowedViews[$action] ?? 'list';
}

function cms_admin_site_tables_normalize_positive_id(mixed $value): int
{
    if (is_int($value)) {
        return $value > 0 ? $value : 0;
    }

    if (!is_scalar($value)) {
        return 0;
    }

    $value = trim((string) $value);
    if ($value === '' || preg_match('/^[1-9][0-9]*$/', $value) !== 1) {
        return 0;
    }

    return (int) $value;
}

function cms_admin_site_tables_edit_redirect_path(int $id): string
{
    return $id > 0
        ? '/admin/site-tables?action=edit&id=' . $id
        : '/admin/site-tables';
}

// ─── POST-Handling ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = cms_admin_site_tables_normalize_action($_POST['action'] ?? '');
    $postToken = (string) ($_POST['csrf_token'] ?? '');

    if (!Security::instance()->verifyToken($postToken, 'admin_tables')) {
        cms_admin_site_tables_flash(['success' => false, 'error' => 'Sicherheitstoken ungültig.']);
        cms_admin_site_tables_redirect();
    }

    if ($action === '') {
        cms_admin_site_tables_flash(['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.']);
        cms_admin_site_tables_redirect();
    }

    switch ($action) {
        case 'save_settings':
            $result = $module->saveDisplaySettings($_POST);
            cms_admin_site_tables_flash($result);
            cms_admin_site_tables_redirect('/admin/site-tables?action=settings');

        case 'save':
            $result = $module->save($_POST);
            cms_admin_site_tables_flash($result);
            if ($result['success']) {
                $resultId = cms_admin_site_tables_normalize_positive_id($result['id'] ?? 0);
                cms_admin_site_tables_redirect(cms_admin_site_tables_edit_redirect_path($resultId));
            } else {
                cms_admin_site_tables_redirect();
            }

        case 'delete':
            $id     = cms_admin_site_tables_normalize_positive_id($_POST['id'] ?? 0);
            $result = $module->delete($id);
            cms_admin_site_tables_flash($result);
            cms_admin_site_tables_redirect();

        case 'duplicate':
            $id     = cms_admin_site_tables_normalize_positive_id($_POST['id'] ?? 0);
            $result = $module->duplicate($id);
            cms_admin_site_tables_flash($result);
            cms_admin_site_tables_redirect();
    }
}

// ─── Session-Alert abholen ───────────────────────────────
if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken = Security::instance()->generateToken('admin_tables');

// ─── View-Routing ────────────────────────────────────────
$viewAction = cms_admin_site_tables_normalize_view_action($_GET['action'] ?? 'list');

if ($viewAction === 'settings') {
    $data       = $module->getSettingsData();
    $pageTitle  = 'Tabellen-Einstellungen';
    $activePage = 'site-tables';

    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/sidebar.php';
    require __DIR__ . '/views/tables/settings.php';
    require __DIR__ . '/partials/footer.php';
} elseif ($viewAction === 'edit') {
    $normalizedId = cms_admin_site_tables_normalize_positive_id($_GET['id'] ?? 0);
    $id        = $normalizedId > 0 ? $normalizedId : null;
    $data      = $module->getEditData($id);
    $pageTitle = $data['isNew'] ? 'Neue Tabelle' : 'Tabelle bearbeiten';
    $activePage = 'site-tables';

    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/sidebar.php';
    require __DIR__ . '/views/tables/edit.php';
    require __DIR__ . '/partials/footer.php';
} else {
    $data       = $module->getListData();
    $pageTitle  = 'Tabellen';
    $activePage = 'site-tables';

    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/sidebar.php';
    require __DIR__ . '/views/tables/list.php';
    require __DIR__ . '/partials/footer.php';
}
