<?php
declare(strict_types=1);

/**
 * Rechtliche Seiten – Entry Point
 * Route: /admin/legal-sites
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

require_once __DIR__ . '/modules/legal/LegalSitesModule.php';
$module    = new LegalSitesModule();
$alert     = null;
$userId    = (int)(Auth::instance()->getCurrentUser()->id ?? 0);

function cms_admin_legal_sites_target_url(): string
{
    return SITE_URL . '/admin/legal-sites';
}

function cms_admin_legal_sites_redirect(): never
{
    header('Location: ' . cms_admin_legal_sites_target_url());
    exit;
}

function cms_admin_legal_sites_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($payload['message'] ?? '')),
    ];
}

function cms_admin_legal_sites_flash_result(array $result): void
{
    cms_admin_legal_sites_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? ''),
    ]);
}

function cms_admin_legal_sites_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
}

/** @return array<string, true> */
function cms_admin_legal_sites_allowed_actions(): array
{
    return [
        'save' => true,
        'save_profile' => true,
        'generate' => true,
        'create_page' => true,
        'create_all_pages' => true,
    ];
}

function cms_admin_legal_sites_normalize_template_type(array $post): string
{
    $type = strtolower(trim((string) ($post['template_type'] ?? '')));

    return in_array($type, ['imprint', 'privacy', 'terms', 'revocation'], true) ? $type : '';
}

/**
 * @return array<string, callable(array, int): array>
 */
function cms_admin_legal_sites_action_handlers(LegalSitesModule $module): array
{
    return [
        'save' => static fn (array $post, int $userId): array => $module->save($post),
        'save_profile' => static fn (array $post, int $userId): array => $module->saveProfile($post),
        'generate' => static fn (array $post, int $userId): array => $module->generateTemplate(cms_admin_legal_sites_normalize_template_type($post)),
        'create_page' => static fn (array $post, int $userId): array => $module->createOrUpdatePage(cms_admin_legal_sites_normalize_template_type($post), $userId),
        'create_all_pages' => static fn (array $post, int $userId): array => $module->createOrUpdateAllPages($userId),
    ];
}

function cms_admin_legal_sites_handle_action(LegalSitesModule $module, string $action, array $post, int $userId): array
{
    $handlers = cms_admin_legal_sites_action_handlers($module);

    if (!isset($handlers[$action])) {
        return ['success' => false, 'error' => 'Unbekannte Aktion.'];
    }

    return $handlers[$action]($post, $userId);
}

function cms_admin_legal_sites_sync_profile_state(string $action, array $result): void
{
    if ($action !== 'save_profile') {
        return;
    }

    if ($result['success'] ?? false) {
        unset($_SESSION['legal_sites_profile_old']);
        return;
    }

    if (!empty($result['profile']) && is_array($result['profile'])) {
        $_SESSION['legal_sites_profile_old'] = $result['profile'];
    }
}

function cms_admin_legal_sites_apply_old_profile(array $data): array
{
    if (!empty($_SESSION['legal_sites_profile_old']) && is_array($_SESSION['legal_sites_profile_old'])) {
        $data['profile'] = array_merge($data['profile'] ?? [], $_SESSION['legal_sites_profile_old']);
        unset($_SESSION['legal_sites_profile_old']);
    }

    return $data;
}

function cms_admin_legal_sites_templates(LegalSitesModule $module): array
{
    return [
        'imprint'    => $module->getTemplateContent('imprint'),
        'privacy'    => $module->getTemplateContent('privacy'),
        'terms'      => $module->getTemplateContent('terms'),
        'revocation' => $module->getTemplateContent('revocation'),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));
    $allowedActions = cms_admin_legal_sites_allowed_actions();

    if (!Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'admin_legal_sites')) {
        cms_admin_legal_sites_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
        cms_admin_legal_sites_redirect();
    }

    if (!isset($allowedActions[$action])) {
        cms_admin_legal_sites_flash(['type' => 'danger', 'message' => 'Unbekannte oder nicht erlaubte Aktion.']);
        cms_admin_legal_sites_redirect();
    }

    $result = cms_admin_legal_sites_handle_action($module, $action, $_POST, $userId);
    cms_admin_legal_sites_sync_profile_state($action, $result);
    cms_admin_legal_sites_flash_result($result);
    cms_admin_legal_sites_redirect();
}

$alert = cms_admin_legal_sites_pull_alert();

$csrfToken  = Security::instance()->generateToken('admin_legal_sites');
$pageTitle  = 'Legal Sites';
$activePage = 'legal-sites';
$data       = cms_admin_legal_sites_apply_old_profile($module->getData());
$data['templates'] = cms_admin_legal_sites_templates($module);

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/legal/sites.php';
require_once __DIR__ . '/partials/footer.php';
