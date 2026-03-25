<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gruppen – Entry Point
 * Route: /admin/groups
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/users/GroupsModule.php';
$module    = new GroupsModule();
$alert     = null;
$redirectUrl = SITE_URL . '/admin/groups';

function cms_admin_groups_redirect(string $redirectUrl): never
{
    header('Location: ' . $redirectUrl);
    exit;
}

function cms_admin_groups_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($payload['message'] ?? '')),
    ];
}

function cms_admin_groups_flash_result(array $result): void
{
    cms_admin_groups_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? ''),
    ]);
}

function cms_admin_groups_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
}

/** @return array<string, true> */
function cms_admin_groups_allowed_actions(): array
{
    return [
        'save' => true,
        'delete' => true,
    ];
}

function cms_admin_groups_normalize_action(mixed $value): ?string
{
    $action = strtolower(trim((string) $value));

    return isset(cms_admin_groups_allowed_actions()[$action]) ? $action : null;
}

function cms_admin_groups_normalize_id(array $post): int
{
    $id = (int) ($post['id'] ?? 0);

    return $id > 0 ? $id : 0;
}

/**
 * @return array<string, callable(array): array>
 */
function cms_admin_groups_action_handlers(GroupsModule $module): array
{
    return [
        'save' => static fn (array $post): array => $module->save($post),
        'delete' => static fn (array $post): array => $module->delete(cms_admin_groups_normalize_id($post)),
    ];
}

function cms_admin_groups_handle_action(GroupsModule $module, string $action, array $post): array
{
    $handlers = cms_admin_groups_action_handlers($module);

    if (!isset($handlers[$action])) {
        return ['success' => false, 'error' => 'Unbekannte Aktion.'];
    }

    return $handlers[$action]($post);
}

// ─── POST-Handling ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = cms_admin_groups_normalize_action($_POST['action'] ?? null);
    $postToken = (string) ($_POST['csrf_token'] ?? '');

    if (!Security::instance()->verifyToken($postToken, 'admin_groups')) {
        cms_admin_groups_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
        cms_admin_groups_redirect($redirectUrl);
    }

    if ($action === null) {
        cms_admin_groups_flash(['type' => 'danger', 'message' => 'Unbekannte Aktion.']);
        cms_admin_groups_redirect($redirectUrl);
    }

    if ($action === 'delete' && cms_admin_groups_normalize_id($_POST) <= 0) {
        cms_admin_groups_flash(['type' => 'danger', 'message' => 'Ungültige Gruppen-ID.']);
        cms_admin_groups_redirect($redirectUrl);
    }

    $result = cms_admin_groups_handle_action($module, $action, $_POST);
    cms_admin_groups_flash_result($result);
    cms_admin_groups_redirect($redirectUrl);
}

$alert = cms_admin_groups_pull_alert();

$csrfToken  = Security::instance()->generateToken('admin_groups');
$data       = $module->getData();
$pageTitle  = 'Gruppen';
$activePage = 'groups';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/users/groups.php';
require __DIR__ . '/partials/footer.php';
