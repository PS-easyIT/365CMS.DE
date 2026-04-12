<?php
declare(strict_types=1);

/**
 * Seiten – Entry Point
 *
 * Route: /admin/pages
 * Actions: list (default), edit, save, delete, bulk
 *
 * @package CMSv2\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;
use CMS\Services\CoreModuleService;
use CMS\Services\EditorJsService;
use CMS\Services\EditorService;

const CMS_ADMIN_PAGES_ALLOWED_ACTIONS = ['save', 'delete', 'bulk'];
const CMS_ADMIN_PAGES_ALLOWED_VIEWS = ['list', 'edit'];
const CMS_ADMIN_PAGES_ALLOWED_BULK_ACTIONS = ['delete', 'publish', 'draft', 'set_category', 'clear_category'];
const CMS_ADMIN_PAGES_WRITE_CAPABILITY = 'manage_pages';

function cms_admin_pages_can_access(): bool
{
    return Auth::instance()->isAdmin() && Auth::instance()->hasCapability(CMS_ADMIN_PAGES_WRITE_CAPABILITY);
}

function cms_admin_pages_target_url(?int $id = null): string
{
    if ($id !== null && $id > 0) {
        return '/admin/pages?action=edit&id=' . $id;
    }

    return '/admin/pages';
}

function cms_admin_pages_normalize_action(mixed $action): string
{
    $normalizedAction = trim((string) $action);

    return in_array($normalizedAction, CMS_ADMIN_PAGES_ALLOWED_ACTIONS, true) ? $normalizedAction : '';
}

function cms_admin_pages_normalize_view(mixed $view): string
{
    $normalizedView = trim((string) $view);

    return in_array($normalizedView, CMS_ADMIN_PAGES_ALLOWED_VIEWS, true) ? $normalizedView : 'list';
}

function cms_admin_pages_normalize_positive_id(mixed $id): int
{
    $normalizedId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $normalizedId === false ? 0 : (int) $normalizedId;
}

function cms_admin_pages_normalize_bulk_action(mixed $bulkAction): string
{
    $normalizedBulkAction = trim((string) $bulkAction);

    return in_array($normalizedBulkAction, CMS_ADMIN_PAGES_ALLOWED_BULK_ACTIONS, true) ? $normalizedBulkAction : '';
}

/**
 * @return array<int,int>
 */
function cms_admin_pages_normalize_bulk_ids(mixed $ids, mixed $csvIds = ''): array
{
    $candidates = (array) $ids;
    if ($candidates === [] && trim((string) $csvIds) !== '') {
        $candidates = explode(',', (string) $csvIds);
    }

    $normalizedIds = [];
    foreach ($candidates as $id) {
        $normalizedId = cms_admin_pages_normalize_positive_id($id);
        if ($normalizedId > 0) {
            $normalizedIds[$normalizedId] = $normalizedId;
        }

        if (count($normalizedIds) >= 200) {
            break;
        }
    }

    return array_values($normalizedIds);
}

function cms_admin_pages_build_inline_edit_data(PagesModule $module, array $post): array
{
    $id = cms_admin_pages_normalize_positive_id($post['id'] ?? 0);
    $editData = $module->getEditData($id > 0 ? $id : null);
    $existingPage = is_object($editData['page'] ?? null) ? (array) $editData['page'] : [];

    $draftPage = array_merge($existingPage, [
        'id' => $id > 0 ? $id : (int) ($existingPage['id'] ?? 0),
        'title' => (string) ($post['title'] ?? ($existingPage['title'] ?? '')),
        'title_en' => (string) ($post['title_en'] ?? ($existingPage['title_en'] ?? '')),
        'slug' => (string) ($post['slug'] ?? ($existingPage['slug'] ?? '')),
        'slug_en' => (string) ($post['slug_en'] ?? ($existingPage['slug_en'] ?? '')),
        'status' => (string) ($post['status'] ?? ($existingPage['status'] ?? 'draft')),
        'content' => $post['content'] ?? ($existingPage['content'] ?? ''),
        'content_en' => $post['content_en'] ?? ($existingPage['content_en'] ?? ''),
        'hide_title' => !empty($post['hide_title']) ? 1 : 0,
        'category_id' => cms_admin_pages_normalize_positive_id($post['category_id'] ?? ($existingPage['category_id'] ?? 0)),
        'featured_image' => (string) ($post['featured_image'] ?? ($existingPage['featured_image'] ?? '')),
        'meta_title' => (string) ($post['meta_title'] ?? ($existingPage['meta_title'] ?? '')),
        'meta_description' => (string) ($post['meta_description'] ?? ($existingPage['meta_description'] ?? '')),
    ]);

    $editData['page'] = (object) $draftPage;
    $editData['seoMeta'] = array_merge(is_array($editData['seoMeta'] ?? null) ? $editData['seoMeta'] : [], [
        'focus_keyphrase' => (string) ($post['focus_keyphrase'] ?? ''),
        'canonical_url' => (string) ($post['canonical_url'] ?? ''),
        'robots_index' => !empty($post['robots_index']),
        'robots_follow' => !empty($post['robots_follow']),
        'og_title' => (string) ($post['og_title'] ?? ''),
        'og_description' => (string) ($post['og_description'] ?? ''),
        'og_image' => (string) ($post['og_image'] ?? ''),
        'twitter_title' => (string) ($post['twitter_title'] ?? ''),
        'twitter_description' => (string) ($post['twitter_description'] ?? ''),
        'twitter_image' => (string) ($post['twitter_image'] ?? ''),
        'twitter_card' => (string) ($post['twitter_card'] ?? 'summary_large_image'),
        'schema_type' => (string) ($post['schema_type'] ?? 'WebPage'),
        'sitemap_priority' => (string) ($post['sitemap_priority'] ?? ''),
        'sitemap_changefreq' => (string) ($post['sitemap_changefreq'] ?? 'weekly'),
        'hreflang_group' => (string) ($post['hreflang_group'] ?? ''),
    ]);

    return $editData;
}

function cms_admin_pages_view_config(PagesModule $module, string $view, ?array $overrideEditData = null): array
{
    $normalizedView = cms_admin_pages_normalize_view($view);
    $aiTranslationEnabled = !class_exists(CoreModuleService::class)
        || CoreModuleService::getInstance()->isModuleEnabled('ai_services');
    $baseTemplateVars = [
        'editorMediaToken' => Security::instance()->generateToken('editorjs_media'),
        'aiTranslationEnabled' => $aiTranslationEnabled,
        'aiTranslationToken' => $aiTranslationEnabled ? Security::instance()->generateToken('admin_ai_editorjs_translation') : '',
        'aiTranslationUrl' => $aiTranslationEnabled ? '/admin/ai-translate-editorjs' : '',
        'useEditorJs' => false,
    ];

    if ($normalizedView === 'edit') {
        $useEditorJs = EditorService::isEditorJs();
        $pageAssets = [];

        if ($useEditorJs) {
            $pageAssets = EditorJsService::getInstance()->getPageAssets();
        } else {
            EditorService::getInstance();
        }

        $pageAssets['css'] = $pageAssets['css'] ?? [];
        $pageAssets['js'] = $pageAssets['js'] ?? [];
        if (!class_exists(CoreModuleService::class) || CoreModuleService::getInstance()->isModuleEnabled('seo')) {
            $pageAssets['js'][] = cms_asset_url('js/admin-seo-editor.js');
        }
        $pageAssets['js'][] = cms_asset_url('js/admin-content-editor.js');

        $id = cms_admin_pages_normalize_positive_id($_GET['id'] ?? 0);
        $editData = is_array($overrideEditData) ? $overrideEditData : $module->getEditData($id);

        return [
            'section' => 'edit',
            'view_file' => __DIR__ . '/views/pages/edit.php',
            'page_title' => $editData['isNew'] ? 'Neue Seite' : 'Seite bearbeiten',
            'active_page' => 'pages',
            'page_assets' => $pageAssets,
            'template_vars' => $baseTemplateVars + [
                'useEditorJs' => $useEditorJs,
                'editData' => $editData,
            ],
            'data' => $editData,
        ];
    }

    $listData = $module->getListData();

    return [
        'section' => 'list',
        'view_file' => __DIR__ . '/views/pages/list.php',
        'page_title' => 'Seiten',
        'active_page' => 'pages',
        'page_assets' => [
            'css' => [],
            'js' => [],
        ],
        'template_vars' => $baseTemplateVars + [
            'listData' => $listData,
        ],
        'data' => $listData,
    ];
}

// ─── Auth-Check ────────────────────────────────────────────────────────────
if (!cms_admin_pages_can_access()) {
    header('Location: /');
    exit;
}

require_once __DIR__ . '/modules/pages/PagesModule.php';

$sectionPageConfig = [
    'route_path' => '/admin/pages',
    'view_file' => __DIR__ . '/views/pages/list.php',
    'page_title' => 'Seiten',
    'active_page' => 'pages',
    'csrf_action' => 'admin_pages',
    'module_file' => __DIR__ . '/modules/pages/PagesModule.php',
    'module_factory' => static fn (): PagesModule => new PagesModule(),
    'access_checker' => static fn (): bool => cms_admin_pages_can_access(),
    'request_context_resolver' => static function (PagesModule $module): array {
        $view = cms_admin_pages_normalize_view($_GET['action'] ?? 'list');

        return cms_admin_pages_view_config($module, $view);
    },
    'redirect_path_resolver' => static function (PagesModule $module, string $section, mixed $result): string {
        if (is_array($result) && isset($result['redirect_path']) && is_string($result['redirect_path'])) {
            return $result['redirect_path'];
        }

        if ($section === 'edit') {
            return cms_admin_pages_target_url(cms_admin_pages_normalize_positive_id($_GET['id'] ?? 0));
        }

        return cms_admin_pages_target_url();
    },
    'post_handler' => static function (PagesModule $module, string $section, array $post): array {
        $postAction = cms_admin_pages_normalize_action($post['action'] ?? '');

        if ($postAction === '') {
            return ['success' => false, 'error' => 'Unbekannte Seiten-Aktion.'];
        }

        switch ($postAction) {
            case 'save':
                $userId = Auth::instance()->getCurrentUser()->id ?? 0;
                $result = $module->save($post, (int) $userId);
                if (!empty($result['success'])) {
                    $result['redirect_path'] = cms_admin_pages_target_url(cms_admin_pages_normalize_positive_id($result['id'] ?? 0));
                    return $result;
                }

                return [
                    'success' => false,
                    'error' => (string) ($result['error'] ?? 'Seite konnte nicht gespeichert werden.'),
                    'details' => is_array($result['details'] ?? null) ? $result['details'] : [],
                    'render_inline' => true,
                    'runtime_context' => cms_admin_pages_view_config($module, 'edit', cms_admin_pages_build_inline_edit_data($module, $post)),
                ];

            case 'delete':
                $id = cms_admin_pages_normalize_positive_id($post['id'] ?? 0);
                if ($id < 1) {
                    return ['success' => false, 'error' => 'Ungültige Seiten-ID.'];
                }

                return $module->delete($id);

            case 'bulk':
                $bulkAction = cms_admin_pages_normalize_bulk_action($post['bulk_action'] ?? '');
                $bulkIds = cms_admin_pages_normalize_bulk_ids($post['ids'] ?? [], $post['bulk_ids'] ?? '');
                if ($bulkAction === '') {
                    return ['success' => false, 'error' => 'Unbekannte Bulk-Aktion für Seiten.'];
                }

                if ($bulkIds === []) {
                    return ['success' => false, 'error' => 'Bitte mindestens eine gültige Seite auswählen.'];
                }

                return $module->bulkAction($bulkAction, $bulkIds, $post);

            default:
                return ['success' => false, 'error' => 'Unbekannte Seiten-Aktion.'];
        }
    },
];

require __DIR__ . '/partials/section-page-shell.php';
