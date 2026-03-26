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

const CMS_ADMIN_LEGAL_SITES_READ_CAPABILITY = 'manage_settings';
const CMS_ADMIN_LEGAL_SITES_WRITE_CAPABILITY = 'manage_settings';

function cms_admin_legal_sites_can_access(): bool
{
    return Auth::instance()->isAdmin()
        && Auth::instance()->hasCapability(CMS_ADMIN_LEGAL_SITES_READ_CAPABILITY);
}

function cms_admin_legal_sites_can_mutate(): bool
{
    return cms_admin_legal_sites_can_access()
        && Auth::instance()->hasCapability(CMS_ADMIN_LEGAL_SITES_WRITE_CAPABILITY);
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

function cms_admin_legal_sites_normalize_action(mixed $action): string
{
    $action = strtolower(trim((string) $action));

    return isset(cms_admin_legal_sites_allowed_actions()[$action]) ? $action : '';
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

$sectionPageConfig = [
    'route_path' => '/admin/legal-sites',
    'view_file' => __DIR__ . '/views/legal/sites.php',
    'page_title' => 'Legal Sites',
    'active_page' => 'legal-sites',
    'csrf_action' => 'admin_legal_sites',
    'module_file' => __DIR__ . '/modules/legal/LegalSitesModule.php',
    'module_factory' => static fn (): LegalSitesModule => new LegalSitesModule(),
    'data_loader' => static function (LegalSitesModule $module): array {
        $data = cms_admin_legal_sites_apply_old_profile($module->getData());
        $data['templates'] = cms_admin_legal_sites_templates($module);

        return $data;
    },
    'access_checker' => static fn (): bool => cms_admin_legal_sites_can_access(),
    'access_denied_route' => '/',
    'unknown_action_message' => 'Unbekannte oder nicht erlaubte Aktion.',
    'post_handler' => static function (LegalSitesModule $module, string $section, array $post): array {
        if (!cms_admin_legal_sites_can_mutate()) {
            return ['success' => false, 'error' => 'Keine Berechtigung für Legal-Sites-Mutationen.'];
        }

        $action = cms_admin_legal_sites_normalize_action($post['action'] ?? null);
        if ($action === '') {
            return ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'];
        }

        if (in_array($action, ['generate', 'create_page'], true) && cms_admin_legal_sites_normalize_template_type($post) === '') {
            return ['success' => false, 'error' => 'Ungültiger Vorlagentyp.'];
        }

        $userId = (int) (Auth::instance()->getCurrentUser()->id ?? 0);
        $result = cms_admin_legal_sites_handle_action($module, $action, $post, $userId);
        cms_admin_legal_sites_sync_profile_state($action, $result);

        return $result;
    },
];

require __DIR__ . '/partials/section-page-shell.php';
