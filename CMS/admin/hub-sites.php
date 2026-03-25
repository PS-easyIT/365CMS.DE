<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hub / Routing Sites – Entry Point
 * Route: /admin/hub-sites
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/hub/HubSitesModule.php';
$module = new HubSitesModule();
$alert = null;

function cms_admin_hub_sites_redirect(string $redirectUrl): never
{
    header('Location: ' . $redirectUrl);
    exit;
}

function cms_admin_hub_sites_default_url(): string
{
    return SITE_URL . '/admin/hub-sites';
}

/**
 * @return list<string>
 */
function cms_admin_hub_sites_allowed_actions(): array
{
    return ['save', 'save-template', 'duplicate-template', 'delete-template', 'delete', 'duplicate'];
}

/**
 * @return list<string>
 */
function cms_admin_hub_sites_allowed_views(): array
{
    return ['list', 'edit', 'template-edit', 'templates'];
}

function cms_admin_hub_sites_flash(array $result, string $fallbackMessage): void
{
    $_SESSION['admin_alert'] = [
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? $fallbackMessage),
    ];
}

function cms_admin_hub_sites_pull_alert(): ?array
{
    if (empty($_SESSION['admin_alert']) || !is_array($_SESSION['admin_alert'])) {
        return null;
    }

    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);

    return $alert;
}

function cms_admin_hub_sites_post_redirect(string $action, array $result, array $post): string
{
    if ($action === 'save') {
        if (!empty($result['success'])) {
            if (!empty($post['open_public_after_save']) && !empty($result['slug'])) {
                return SITE_URL . '/' . ltrim((string) $result['slug'], '/');
            }

            return cms_admin_hub_sites_default_url() . '?action=edit&id=' . (int) ($result['id'] ?? 0);
        }

        return cms_admin_hub_sites_default_url();
    }

    if ($action === 'save-template' || $action === 'duplicate-template') {
        if (!empty($result['success'])) {
            return cms_admin_hub_sites_default_url() . '?action=template-edit&key=' . rawurlencode((string) ($result['key'] ?? ''));
        }

        return cms_admin_hub_sites_default_url() . '?action=templates';
    }

    if ($action === 'delete-template') {
        return cms_admin_hub_sites_default_url() . '?action=templates';
    }

    return cms_admin_hub_sites_default_url();
}

function cms_admin_hub_sites_normalize_view(string $viewAction): string
{
    return in_array($viewAction, cms_admin_hub_sites_allowed_views(), true) ? $viewAction : 'list';
}

function cms_admin_hub_sites_handle_action(HubSitesModule $module, string $action, array $post): array
{
    return match ($action) {
        'save' => [
            'result' => $module->save($post),
            'fallback' => 'Hub-Site konnte nicht gespeichert werden.',
        ],
        'save-template' => [
            'result' => $module->saveTemplate($post),
            'fallback' => 'Hub-Template konnte nicht gespeichert werden.',
        ],
        'duplicate-template' => [
            'result' => $module->duplicateTemplate((string)($post['key'] ?? '')),
            'fallback' => 'Hub-Template konnte nicht dupliziert werden.',
        ],
        'delete-template' => [
            'result' => $module->deleteTemplate((string)($post['key'] ?? '')),
            'fallback' => 'Hub-Template konnte nicht gelöscht werden.',
        ],
        'delete' => [
            'result' => $module->delete((int)($post['id'] ?? 0)),
            'fallback' => 'Hub-Site konnte nicht gelöscht werden.',
        ],
        'duplicate' => [
            'result' => $module->duplicate((int)($post['id'] ?? 0)),
            'fallback' => 'Hub-Site konnte nicht dupliziert werden.',
        ],
        default => [
            'result' => ['success' => false, 'error' => 'Unbekannte Hub-Sites-Aktion.'],
            'fallback' => 'Unbekannte Hub-Sites-Aktion.',
        ],
    };
}

/**
 * @return array{data: array<string, mixed>, pageTitle: string, activePage: string, pageAssets?: array<string, array<int, string>>, view: string}
 */
function cms_admin_hub_sites_view_config(HubSitesModule $module, string $viewAction): array
{
    return match ($viewAction) {
        'edit' => (function () use ($module): array {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
            $data = $module->getEditData($id);

            return [
                'data' => $data,
                'pageTitle' => !empty($data['isNew']) ? 'Neue Hub-Site' : 'Hub-Site bearbeiten',
                'activePage' => 'hub-sites',
                'pageAssets' => [
                    'css' => [
                        cms_asset_url('suneditor/css/suneditor.min.css'),
                        cms_asset_url('css/admin-hub-site-edit.css'),
                    ],
                    'js' => [
                        cms_asset_url('suneditor/suneditor.min.js'),
                        cms_asset_url('suneditor/lang/de.js'),
                        cms_asset_url('js/admin-hub-site-edit.js'),
                    ],
                ],
                'view' => __DIR__ . '/views/hub/edit.php',
            ];
        })(),
        'template-edit' => (function () use ($module): array {
            $key = isset($_GET['key']) ? (string)$_GET['key'] : null;
            $data = $module->getTemplateEditData($key);
            $pageAssets = ['css' => [], 'js' => []];

            $hubTemplateCssPath = rtrim((string)ASSETS_PATH, '/\\') . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'admin-hub-template-editor.css';
            if (is_file($hubTemplateCssPath)) {
                $pageAssets['css'][] = cms_asset_url('css/admin-hub-template-editor.css');
            }

            $hubTemplateJsPath = rtrim((string)ASSETS_PATH, '/\\') . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'admin-hub-template-editor.js';
            if (is_file($hubTemplateJsPath)) {
                $pageAssets['js'][] = cms_asset_url('js/admin-hub-template-editor.js');
            }

            return [
                'data' => $data,
                'pageTitle' => !empty($data['isNew']) ? 'Neues Hub-Template' : 'Hub-Template bearbeiten',
                'activePage' => 'hub-sites',
                'pageAssets' => $pageAssets,
                'view' => __DIR__ . '/views/hub/template-edit.php',
            ];
        })(),
        'templates' => [
            'data' => $module->getTemplateListData(),
            'pageTitle' => 'Hub-Site Templates',
            'activePage' => 'hub-sites',
            'view' => __DIR__ . '/views/hub/templates.php',
        ],
        default => [
            'data' => $module->getListData(),
            'pageTitle' => 'Hub-Sites',
            'activePage' => 'hub-sites',
            'view' => __DIR__ . '/views/hub/list.php',
        ],
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $postToken = (string)($_POST['csrf_token'] ?? '');

    if (!Security::instance()->verifyToken($postToken, 'admin_hub_sites')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        cms_admin_hub_sites_redirect(cms_admin_hub_sites_default_url());
    }

    if (!in_array($action, cms_admin_hub_sites_allowed_actions(), true)) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Unbekannte Hub-Sites-Aktion.'];
        cms_admin_hub_sites_redirect(cms_admin_hub_sites_default_url());
    }

    $handledAction = cms_admin_hub_sites_handle_action($module, $action, $_POST);
    $result = is_array($handledAction['result'] ?? null) ? $handledAction['result'] : ['success' => false, 'error' => 'Unbekannte Hub-Sites-Aktion.'];
    $fallbackMessage = (string)($handledAction['fallback'] ?? 'Hub-Sites-Aktion fehlgeschlagen.');

    cms_admin_hub_sites_flash($result, $fallbackMessage);
    cms_admin_hub_sites_redirect(cms_admin_hub_sites_post_redirect($action, $result, $_POST));
}

$alert = cms_admin_hub_sites_pull_alert();

$csrfToken = Security::instance()->generateToken('admin_hub_sites');
$viewAction = cms_admin_hub_sites_normalize_view((string)($_GET['action'] ?? 'list'));
$viewConfig = cms_admin_hub_sites_view_config($module, $viewAction);

$data = $viewConfig['data'];
$pageTitle = $viewConfig['pageTitle'];
$activePage = $viewConfig['activePage'];
$pageAssets = $viewConfig['pageAssets'] ?? ['css' => [], 'js' => []];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require $viewConfig['view'];
require __DIR__ . '/partials/footer.php';
