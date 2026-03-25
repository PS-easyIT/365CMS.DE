<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Media – Entry Point
 * Route: /admin/media
 *
 * Tabs: library (Standard), categories, settings
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/media/MediaModule.php';
$module    = new MediaModule();
$alert     = null;
$tab       = $module->normalizeTab((string)($_GET['tab'] ?? 'library'));

function cms_admin_media_redirect(string $redirectUrl): never
{
    header('Location: ' . $redirectUrl);
    exit;
}

function cms_admin_media_default_url(): string
{
    return SITE_URL . '/admin/media';
}

/**
 * @return list<string>
 */
function cms_admin_media_allowed_actions(): array
{
    return ['upload', 'create_folder', 'delete_item', 'rename_item', 'assign_category', 'add_category', 'delete_category', 'save_settings'];
}

function cms_admin_media_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => (string) ($payload['type'] ?? 'danger'),
        'message' => (string) ($payload['message'] ?? ''),
    ];
}

function cms_admin_media_flash_result(array $result): void
{
    cms_admin_media_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? ''),
    ]);
}

function cms_admin_media_pull_alert(): ?array
{
    if (empty($_SESSION['admin_alert']) || !is_array($_SESSION['admin_alert'])) {
        return null;
    }

    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);

    return $alert;
}

function cms_admin_media_resolve_path(MediaModule $module, array $post): string
{
    $path = (string)($post['parent_path'] ?? $post['target_path'] ?? '');

    if ($path !== '') {
        return $module->normalizePath($path);
    }

    $actionPath = (string)($post['item_path'] ?? $post['old_path'] ?? $post['file_path'] ?? '');
    if ($actionPath === '') {
        return '';
    }

    $normalizedActionPath = trim(str_replace('\\', '/', $actionPath), '/');
    $parentPath = trim(str_replace('\\', '/', dirname($normalizedActionPath)), '/.');

    return $module->normalizePath($parentPath);
}

/**
 * @return array<string, string>
 */
function cms_admin_media_redirect_params(MediaModule $module, string $tab, string $path): array
{
    $redirectParams = [];

    if ($tab !== 'library') {
        $redirectParams['tab'] = $tab;
    }

    if ($path !== '') {
        $redirectParams['path'] = $path;
    } else {
        $normalizedGetPath = $module->normalizePath((string)($_GET['path'] ?? ''));
        if ($normalizedGetPath !== '') {
            $redirectParams['path'] = $normalizedGetPath;
        }
    }

    $normalizedView = $module->normalizeView((string)($_GET['view'] ?? 'finder'));
    $normalizedCategory = $module->normalizeCategory((string)($_GET['category'] ?? ''));
    $normalizedSearch = $module->normalizeSearch((string)($_GET['q'] ?? ''));

    if ($normalizedView !== 'finder') {
        $redirectParams['view'] = $normalizedView;
    }
    if ($normalizedCategory !== '') {
        $redirectParams['category'] = $normalizedCategory;
    }
    if ($normalizedSearch !== '') {
        $redirectParams['q'] = $normalizedSearch;
    }
    if ((string)($_GET['confirm_member'] ?? '') === '1') {
        $redirectParams['confirm_member'] = '1';
    }

    return $redirectParams;
}

function cms_admin_media_build_redirect_url(MediaModule $module, string $tab, string $path): string
{
    $redirectParams = cms_admin_media_redirect_params($module, $tab, $path);

    return cms_admin_media_default_url() . (!empty($redirectParams) ? '?' . http_build_query($redirectParams) : '');
}

function cms_admin_media_action_redirect_url(MediaModule $module, string $action, string $tab, string $path): string
{
    return match ($action) {
        'add_category', 'delete_category' => cms_admin_media_default_url() . '?tab=categories',
        'save_settings' => cms_admin_media_default_url() . '?tab=settings',
        default => cms_admin_media_build_redirect_url($module, $tab, $path),
    };
}

/**
 * @return array{files:list<array{name:string,type:string,tmp_name:string,error:int,size:int}>, error?:string}
 */
function cms_admin_media_normalize_upload_batch(array $files, string $field = 'files'): array
{
    $payload = $files[$field] ?? null;
    if (!is_array($payload)) {
        return ['files' => []];
    }

    $requiredKeys = ['name', 'type', 'tmp_name', 'error', 'size'];
    foreach ($requiredKeys as $key) {
        if (!array_key_exists($key, $payload)) {
            return ['files' => [], 'error' => 'Upload-Daten sind unvollständig.'];
        }
    }

    $names = $payload['name'];

    if (!is_array($names)) {
        return ['files' => [[
            'name' => (string) $payload['name'],
            'type' => (string) $payload['type'],
            'tmp_name' => (string) $payload['tmp_name'],
            'error' => (int) $payload['error'],
            'size' => max(0, (int) $payload['size']),
        ]]];
    }

    $count = count($names);
    foreach ($requiredKeys as $key) {
        if (!is_array($payload[$key]) || count($payload[$key]) !== $count) {
            return ['files' => [], 'error' => 'Upload-Daten sind inkonsistent.'];
        }
    }

    $normalizedFiles = [];

    for ($index = 0; $index < $count; $index++) {
        $file = [
            'name' => (string) $payload['name'][$index],
            'type' => (string) $payload['type'][$index],
            'tmp_name' => (string) $payload['tmp_name'][$index],
            'error' => (int) $payload['error'][$index],
            'size' => max(0, (int) $payload['size'][$index]),
        ];

        if ($file['name'] === '' && $file['tmp_name'] === '' && $file['error'] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $normalizedFiles[] = $file;
    }

    return ['files' => $normalizedFiles];
}

/** @param list<string> $errors */
function cms_admin_media_format_upload_errors(array $errors): string
{
    if ($errors === []) {
        return '';
    }

    $visibleErrors = array_slice($errors, 0, 5);
    $message = implode(', ', $visibleErrors);
    $hiddenCount = count($errors) - count($visibleErrors);

    if ($hiddenCount > 0) {
        $message .= ' +' . $hiddenCount . ' weitere(s) Problem(e)';
    }

    return $message;
}

function cms_admin_media_handle_upload(MediaModule $module, string $path): array
{
    $uploadBatch = cms_admin_media_normalize_upload_batch($_FILES);
    if (isset($uploadBatch['error'])) {
        return [
            'type' => 'danger',
            'message' => (string) $uploadBatch['error'],
        ];
    }

    $files = $uploadBatch['files'];
    $uploaded = 0;
    $errors   = [];

    foreach ($files as $file) {
        $result = $module->uploadFile($file, $path);
        if (!empty($result['success'])) {
            $uploaded++;
            continue;
        }

        $errors[] = htmlspecialchars((string) ($file['name'] ?? 'Datei')) . ': ' . (string) ($result['error'] ?? 'Fehler');
    }

    if ($uploaded === 0 && $errors === []) {
        return [
            'type' => 'danger',
            'message' => 'Es wurden keine gültigen Upload-Dateien übermittelt.',
        ];
    }

    $formattedErrors = cms_admin_media_format_upload_errors($errors);

    if ($uploaded > 0) {
        return [
            'type' => 'success',
            'message' => $uploaded . ' Datei(en) hochgeladen.' . ($formattedErrors !== '' ? ' Fehler: ' . $formattedErrors : ''),
        ];
    }

    return [
        'type' => 'danger',
        'message' => 'Upload fehlgeschlagen.' . ($formattedErrors !== '' ? ' ' . $formattedErrors : ''),
    ];
}

function cms_admin_media_handle_action(MediaModule $module, string $action, string $tab, string $path, array $post): array
{
    $redirectUrl = cms_admin_media_action_redirect_url($module, $action, $tab, $path);

    return match ($action) {
        'upload' => [
            'flash' => cms_admin_media_handle_upload($module, $path),
            'redirect_url' => $redirectUrl,
        ],
        'create_folder' => [
            'result' => $module->createFolder(trim((string)($post['folder_name'] ?? '')), (string)($post['parent_path'] ?? '')),
            'redirect_url' => $redirectUrl,
        ],
        'delete_item' => [
            'result' => $module->deleteItem((string)($post['item_path'] ?? '')),
            'redirect_url' => $redirectUrl,
        ],
        'rename_item' => [
            'result' => $module->renameItem((string)($post['old_path'] ?? ''), trim((string)($post['new_name'] ?? ''))),
            'redirect_url' => $redirectUrl,
        ],
        'assign_category' => [
            'result' => $module->assignCategory((string)($post['file_path'] ?? ''), (string)($post['category_slug'] ?? '')),
            'redirect_url' => $redirectUrl,
        ],
        'add_category' => [
            'result' => $module->addCategory(trim((string)($post['name'] ?? '')), trim((string)($post['slug'] ?? ''))),
            'redirect_url' => $redirectUrl,
        ],
        'delete_category' => [
            'result' => $module->deleteCategory((string)($post['slug'] ?? '')),
            'redirect_url' => $redirectUrl,
        ],
        'save_settings' => [
            'result' => $module->saveSettings($post),
            'redirect_url' => $redirectUrl,
        ],
        default => [
            'flash' => ['type' => 'danger', 'message' => 'Unbekannte Aktion.'],
            'redirect_url' => cms_admin_media_default_url(),
        ],
    };
}

if ($tab === 'library') {
    $requestedPath = $module->normalizePath((string)($_GET['path'] ?? ''));
    $memberConfirmed = (string)($_GET['confirm_member'] ?? '') === '1';

    if ($module->requiresMemberConfirmation($requestedPath) && !$memberConfirmed) {
        cms_admin_media_flash([
            'type' => 'danger',
            'message' => 'Der Member-Ordner kann erst nach einer zusätzlichen Bestätigung geöffnet werden.',
        ]);
        cms_admin_media_redirect(SITE_URL . '/admin/media');
    }
}

// ─── POST-Handling ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = (string)($_POST['action'] ?? '');
    $postToken = (string)($_POST['csrf_token'] ?? '');

    if (!Security::instance()->verifyToken($postToken, 'admin_media')) {
        cms_admin_media_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
        cms_admin_media_redirect(cms_admin_media_default_url());
    }

    if (!in_array($action, cms_admin_media_allowed_actions(), true)) {
        cms_admin_media_flash(['type' => 'danger', 'message' => 'Unbekannte Aktion.']);
        cms_admin_media_redirect(cms_admin_media_default_url());
    }

    $path = cms_admin_media_resolve_path($module, $_POST);
    $handledAction = cms_admin_media_handle_action($module, $action, $tab, $path, $_POST);

    if (isset($handledAction['flash']) && is_array($handledAction['flash'])) {
        cms_admin_media_flash($handledAction['flash']);
    } elseif (isset($handledAction['result']) && is_array($handledAction['result'])) {
        cms_admin_media_flash_result($handledAction['result']);
    }

    cms_admin_media_redirect((string)($handledAction['redirect_url'] ?? cms_admin_media_default_url()));
}

// ─── Session-Alert abholen ───────────────────────────────
$alert = cms_admin_media_pull_alert();

$csrfToken  = Security::instance()->generateToken('admin_media');
$mediaActionToken = Security::instance()->generateToken('media_action');
$mediaConnectorToken = Security::instance()->generateToken('media_connector');
$activePage = match ($tab) {
    'categories' => 'media-categories',
    'settings' => 'media-settings',
    default => 'media',
};

// ─── Tab-Routing ─────────────────────────────────────────
switch ($tab) {
    case 'categories':
        $data      = $module->getCategoriesData();
        $pageTitle = 'Medien – Kategorien';
        require __DIR__ . '/partials/header.php';
        require __DIR__ . '/partials/sidebar.php';
        require __DIR__ . '/views/media/categories.php';
        require __DIR__ . '/partials/footer.php';
        break;

    case 'settings':
        $data      = $module->getSettingsData();
        $pageTitle = 'Medien – Einstellungen';
        require __DIR__ . '/partials/header.php';
        require __DIR__ . '/partials/sidebar.php';
        require __DIR__ . '/views/media/settings.php';
        require __DIR__ . '/partials/footer.php';
        break;

    default:
        $data      = $module->getLibraryData();
        $pageTitle = 'Medien';
        $pageAssets = [
            'css' => [
                cms_asset_url('filepond/filepond.min.css'),
                cms_asset_url('elfinder/vendor/jquery-ui/jquery-ui-1.13.2.css'),
                cms_asset_url('elfinder/css/elfinder.min.css'),
                cms_asset_url('elfinder/css/theme.css'),
            ],
            'js' => [
                cms_asset_url('filepond/filepond.min.js'),
                cms_asset_url('js/admin-media-integrations.js'),
            ],
        ];
        $inlineJs = '';
        require __DIR__ . '/partials/header.php';
        require __DIR__ . '/partials/sidebar.php';
        require __DIR__ . '/views/media/library.php';
        require __DIR__ . '/partials/footer.php';
        break;
}
