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
const CMS_ADMIN_LEGAL_SITES_LEGAL_KEYS = [
    'legal_imprint',
    'legal_privacy',
    'legal_terms',
    'legal_revocation',
];
const CMS_ADMIN_LEGAL_SITES_ASSIGNMENT_KEYS = [
    'imprint_page_id',
    'privacy_page_id',
    'terms_page_id',
    'revocation_page_id',
];
const CMS_ADMIN_LEGAL_SITES_PROFILE_KEYS = [
    'legal_profile_entity_type',
    'legal_profile_company_name',
    'legal_profile_legal_form',
    'legal_profile_owner_name',
    'legal_profile_managing_director',
    'legal_profile_content_responsible',
    'legal_profile_street',
    'legal_profile_postal_code',
    'legal_profile_city',
    'legal_profile_country',
    'legal_profile_email',
    'legal_profile_phone',
    'legal_profile_website',
    'legal_profile_register_court',
    'legal_profile_register_number',
    'legal_profile_vat_id',
    'legal_profile_dispute_participation',
    'legal_profile_hosting_provider',
    'legal_profile_hosting_address',
    'legal_profile_privacy_contact_name',
    'legal_profile_privacy_contact_email',
    'legal_profile_analytics_name',
    'legal_profile_newsletter_provider',
    'legal_profile_external_media_providers',
    'legal_profile_webfonts_source',
    'legal_profile_webfonts_provider',
    'legal_profile_payment_providers',
    'legal_profile_essential_cookie_name',
    'legal_profile_essential_cookie_purpose',
    'legal_profile_additional_service_name',
    'legal_profile_additional_service_provider',
    'legal_profile_additional_service_purpose',
    'legal_profile_terms_scope',
    'legal_profile_contract_type',
    'legal_profile_return_costs',
];
const CMS_ADMIN_LEGAL_SITES_PROFILE_BOOLEAN_KEYS = [
    'legal_profile_analytics_self_hosted',
    'legal_profile_minimal_privacy_mode',
    'legal_profile_service_start_notice',
    'legal_profile_has_cookies',
    'legal_profile_has_contact_form',
    'legal_profile_has_registration',
    'legal_profile_has_comments',
    'legal_profile_has_newsletter',
    'legal_profile_has_analytics',
    'legal_profile_has_external_media',
    'legal_profile_has_webfonts',
    'legal_profile_has_shop',
];
const CMS_ADMIN_LEGAL_SITES_MAX_LEGAL_HTML_LENGTH = 60000;
const CMS_ADMIN_LEGAL_SITES_MAX_PROFILE_VALUE_LENGTH = 500;
const CMS_ADMIN_LEGAL_SITES_MAX_PROFILE_TEXTAREA_LENGTH = 4000;
const CMS_ADMIN_LEGAL_SITES_SESSION_OLD_SAVE_KEY = 'legal_sites_save_old';
const CMS_ADMIN_LEGAL_SITES_SESSION_OLD_PROFILE_KEY = 'legal_sites_profile_old';
const CMS_ADMIN_LEGAL_SITES_PROFILE_TEXTAREA_KEYS = [
    'legal_profile_external_media_providers',
    'legal_profile_payment_providers',
    'legal_profile_hosting_address',
    'legal_profile_essential_cookie_purpose',
    'legal_profile_additional_service_purpose',
];

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

function cms_admin_legal_sites_normalize_positive_id(mixed $value): int
{
    $normalizedId = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $normalizedId === false ? 0 : (int) $normalizedId;
}

function cms_admin_legal_sites_normalize_text(mixed $value, int $maxLength = 4000): string
{
    $normalizedValue = trim((string) $value);
    $normalizedValue = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', '', $normalizedValue) ?? '';

    return function_exists('mb_substr')
        ? mb_substr($normalizedValue, 0, $maxLength)
        : substr($normalizedValue, 0, $maxLength);
}

function cms_admin_legal_sites_normalize_html(mixed $value, int $maxLength = CMS_ADMIN_LEGAL_SITES_MAX_LEGAL_HTML_LENGTH): string
{
    $normalizedValue = (string) $value;
    $normalizedValue = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', '', $normalizedValue) ?? '';

    if (function_exists('mb_substr')) {
        $normalizedValue = mb_substr($normalizedValue, 0, $maxLength);
    } else {
        $normalizedValue = substr($normalizedValue, 0, $maxLength);
    }

    return sanitize_html(
        strip_tags($normalizedValue, '<p><a><strong><em><ul><ol><li><br><h2><h3><h4>'),
        'default'
    );
}

/** @return array<string, mixed> */
function cms_admin_legal_sites_normalize_save_payload(array $post): array
{
    $normalized = [];

    foreach (CMS_ADMIN_LEGAL_SITES_LEGAL_KEYS as $key) {
        if (array_key_exists($key, $post)) {
            $normalized[$key] = cms_admin_legal_sites_normalize_html($post[$key] ?? '');
        }
    }

    foreach (CMS_ADMIN_LEGAL_SITES_ASSIGNMENT_KEYS as $key) {
        if (array_key_exists($key, $post)) {
            $normalized[$key] = (string) cms_admin_legal_sites_normalize_positive_id($post[$key] ?? 0);
        }
    }

    return $normalized;
}

/** @return array<string, mixed> */
function cms_admin_legal_sites_normalize_profile_payload(array $post): array
{
    $normalized = [];

    foreach (CMS_ADMIN_LEGAL_SITES_PROFILE_KEYS as $key) {
        if (!array_key_exists($key, $post)) {
            continue;
        }

        $maxLength = in_array($key, CMS_ADMIN_LEGAL_SITES_PROFILE_TEXTAREA_KEYS, true)
            ? CMS_ADMIN_LEGAL_SITES_MAX_PROFILE_TEXTAREA_LENGTH
            : CMS_ADMIN_LEGAL_SITES_MAX_PROFILE_VALUE_LENGTH;

        $normalized[$key] = cms_admin_legal_sites_normalize_text($post[$key] ?? '', $maxLength);
    }

    foreach (CMS_ADMIN_LEGAL_SITES_PROFILE_BOOLEAN_KEYS as $key) {
        $normalized[$key] = array_key_exists($key, $post) ? '1' : '0';
    }

    return $normalized;
}

/** @return array<string, mixed> */
function cms_admin_legal_sites_normalize_action_payload(string $action, array $post): array
{
    return match ($action) {
        'save' => cms_admin_legal_sites_normalize_save_payload($post),
        'save_profile' => cms_admin_legal_sites_normalize_profile_payload($post),
        'generate', 'create_page' => [
            'template_type' => cms_admin_legal_sites_normalize_template_type($post),
        ],
        default => [],
    };
}

/** @return array{action:string,error:string,payload:array<string,mixed>} */
function cms_admin_legal_sites_normalize_request(array $post): array
{
    $action = cms_admin_legal_sites_normalize_action($post['action'] ?? null);
    $payload = $action !== '' ? cms_admin_legal_sites_normalize_action_payload($action, $post) : [];
    $error = '';

    if ($action === '') {
        $error = 'Unbekannte oder nicht erlaubte Aktion.';
    } elseif (in_array($action, ['generate', 'create_page'], true) && (string) ($payload['template_type'] ?? '') === '') {
        $error = 'Ungültiger Vorlagentyp.';
    }

    return [
        'action' => $action,
        'error' => $error,
        'payload' => $payload,
    ];
}

function cms_admin_legal_sites_handle_action(
    LegalSitesModule $module,
    string $action,
    array $post,
    int $userId,
    string $templateType = ''
): array {
    return match ($action) {
        'save' => $module->save($post),
        'save_profile' => $module->saveProfile($post),
        'generate' => $module->generateTemplate($templateType),
        'create_page' => $module->createOrUpdatePage($templateType, $userId),
        'create_all_pages' => $module->createOrUpdateAllPages($userId),
        default => ['success' => false, 'error' => 'Unbekannte Aktion.'],
    };
}

function cms_admin_legal_sites_sync_profile_state(string $action, array $result): void
{
    if ($action !== 'save_profile') {
        return;
    }

    if ($result['success'] ?? false) {
        unset($_SESSION[CMS_ADMIN_LEGAL_SITES_SESSION_OLD_PROFILE_KEY]);
        return;
    }

    if (!empty($result['profile']) && is_array($result['profile'])) {
        $_SESSION[CMS_ADMIN_LEGAL_SITES_SESSION_OLD_PROFILE_KEY] = $result['profile'];
    }
}

function cms_admin_legal_sites_sync_save_state(string $action, array $payload, array $result): void
{
    if ($action !== 'save') {
        return;
    }

    if ($result['success'] ?? false) {
        unset($_SESSION[CMS_ADMIN_LEGAL_SITES_SESSION_OLD_SAVE_KEY]);
        return;
    }

    if ($payload !== []) {
        $_SESSION[CMS_ADMIN_LEGAL_SITES_SESSION_OLD_SAVE_KEY] = $payload;
    }
}

function cms_admin_legal_sites_apply_old_profile(array $data): array
{
    if (!empty($_SESSION[CMS_ADMIN_LEGAL_SITES_SESSION_OLD_PROFILE_KEY]) && is_array($_SESSION[CMS_ADMIN_LEGAL_SITES_SESSION_OLD_PROFILE_KEY])) {
        $data['profile'] = array_merge($data['profile'] ?? [], $_SESSION[CMS_ADMIN_LEGAL_SITES_SESSION_OLD_PROFILE_KEY]);
        unset($_SESSION[CMS_ADMIN_LEGAL_SITES_SESSION_OLD_PROFILE_KEY]);
    }

    return $data;
}

function cms_admin_legal_sites_apply_old_save(array $data): array
{
    $oldSave = $_SESSION[CMS_ADMIN_LEGAL_SITES_SESSION_OLD_SAVE_KEY] ?? null;
    if (!is_array($oldSave) || $oldSave === []) {
        return $data;
    }

    foreach (CMS_ADMIN_LEGAL_SITES_LEGAL_KEYS as $key) {
        if (!array_key_exists($key, $oldSave)) {
            continue;
        }

        $data['pages'][$key]['content'] = (string) $oldSave[$key];
    }

    foreach (CMS_ADMIN_LEGAL_SITES_ASSIGNMENT_KEYS as $key) {
        if (array_key_exists($key, $oldSave)) {
            $data['assigned_pages'][$key] = (string) $oldSave[$key];
        }
    }

    unset($_SESSION[CMS_ADMIN_LEGAL_SITES_SESSION_OLD_SAVE_KEY]);

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
    'page_assets' => [
        'js' => [
            cms_asset_url('js/admin-legal-sites.js'),
        ],
    ],
    'csrf_action' => 'admin_legal_sites',
    'module_file' => __DIR__ . '/modules/legal/LegalSitesModule.php',
    'module_factory' => static fn (): LegalSitesModule => new LegalSitesModule(),
    'data_loader' => static function (LegalSitesModule $module): array {
        $data = cms_admin_legal_sites_apply_old_profile($module->getData());
        $data = cms_admin_legal_sites_apply_old_save($data);
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

        $request = cms_admin_legal_sites_normalize_request($post);
        if ($request['error'] !== '') {
            return ['success' => false, 'error' => $request['error']];
        }

        $action = $request['action'];
        $normalizedPost = $request['payload'];
        $templateType = in_array($action, ['generate', 'create_page'], true)
            ? (string) ($normalizedPost['template_type'] ?? '')
            : '';

        $userId = (int) (Auth::instance()->getCurrentUser()->id ?? 0);
        $result = cms_admin_legal_sites_handle_action($module, $action, $normalizedPost, $userId, $templateType);
        cms_admin_legal_sites_sync_profile_state($action, $result);
        cms_admin_legal_sites_sync_save_state($action, $normalizedPost, $result);

        return $result;
    },
];

require __DIR__ . '/partials/section-page-shell.php';
