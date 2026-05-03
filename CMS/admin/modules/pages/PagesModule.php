<?php
declare(strict_types=1);

/**
 * Pages Module – CRUD-Logik für Seiten
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Database;
use CMS\Hooks;
use CMS\CacheManager;
use CMS\AuditLogger;
use CMS\Logger;
use CMS\PageManager;
use CMS\Services\RedirectService;
use CMS\Services\ContentMediaPlacementService;
use CMS\Services\ContentLocalizationService;
use CMS\Services\SEOService;

class PagesModule
{
    private Database $db;
    private PageManager $pageManager;
    private string $prefix;

    private const DEFAULT_MS365_ROOT = [
        'slug' => 'microsoft-365',
        'name' => 'Microsoft 365',
    ];

    /** @var array<string,string> */
    private const DEFAULT_MS365_CHILD_CATEGORIES = [
        'microsoft-cloud-services' => 'Microsoft Cloud Services',
        'microsoft-copilot' => 'Microsoft Copilot',
        'microsoft-teams' => 'Microsoft Teams',
        'exchange-online' => 'Exchange Online',
        'outlook' => 'Outlook',
        'sharepoint-online' => 'SharePoint Online',
        'onedrive-for-business' => 'OneDrive for Business',
        'microsoft-entra-id' => 'Microsoft Entra ID',
        'microsoft-intune' => 'Microsoft Intune',
        'microsoft-defender' => 'Microsoft Defender',
        'microsoft-purview' => 'Microsoft Purview',
        'power-platform' => 'Power Platform',
        'power-automate' => 'Power Automate',
        'power-apps' => 'Power Apps',
        'power-bi' => 'Power BI',
        'planner-to-do' => 'Planner & To Do',
        'microsoft-viva' => 'Microsoft Viva',
        'microsoft-forms' => 'Microsoft Forms',
        'microsoft-loop' => 'Microsoft Loop',
        'windows-365' => 'Windows 365',
    ];

    /** @var string[] */
    private const ALLOWED_LIST_STATUSES = ['published', 'draft', 'private'];

    /** @var string[] */
    private const ALLOWED_BULK_ACTIONS = ['delete', 'publish', 'draft', 'set_category', 'clear_category'];

    public function __construct()
    {
        $this->db          = Database::instance();
        $this->prefix      = $this->db->getPrefix();
        $this->pageManager = PageManager::instance();
        $this->ensureCategoryColumns();
        $this->ensureDefaultCategories();
    }

    private function ensureCategoryColumns(): void
    {
        $columns = [
            'parent_id' => "ALTER TABLE {$this->prefix}post_categories ADD COLUMN parent_id INT UNSIGNED DEFAULT NULL AFTER description",
            'sort_order' => "ALTER TABLE {$this->prefix}post_categories ADD COLUMN sort_order INT DEFAULT 0 AFTER parent_id",
            'alias_domains_json' => "ALTER TABLE {$this->prefix}post_categories ADD COLUMN alias_domains_json TEXT DEFAULT NULL AFTER sort_order",
        ];

        foreach ($columns as $column => $sql) {
            try {
                $stmt = $this->db->query("SHOW COLUMNS FROM {$this->prefix}post_categories LIKE '{$column}'");
                if ($stmt instanceof \PDOStatement && !$stmt->fetch()) {
                    $this->db->query($sql);
                }
            } catch (\Throwable $e) {
                Logger::instance()->withChannel('admin.pages')->warning('Zusätzliche Kategorien-Spalte konnte nicht sichergestellt werden.', [
                    'column' => $column,
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }

    private function ensureDefaultCategories(): void
    {
        try {
            $rootId = $this->getCategoryIdBySlug((string) self::DEFAULT_MS365_ROOT['slug']);

            if ($rootId <= 0) {
                $this->db->execute(
                    "INSERT INTO {$this->prefix}post_categories (name, slug, parent_id, sort_order, alias_domains_json) VALUES (?, ?, NULL, ?, ?)",
                    [
                        (string) self::DEFAULT_MS365_ROOT['name'],
                        (string) self::DEFAULT_MS365_ROOT['slug'],
                        10,
                        '[]',
                    ]
                );
                $rootId = (int) $this->db->lastInsertId();
            } else {
                $this->db->execute(
                    "UPDATE {$this->prefix}post_categories SET name = ?, parent_id = NULL, sort_order = ? WHERE id = ?",
                    [(string) self::DEFAULT_MS365_ROOT['name'], 10, $rootId]
                );
            }

            $sortOrder = 20;
            foreach (self::DEFAULT_MS365_CHILD_CATEGORIES as $slug => $name) {
                $categoryId = $this->getCategoryIdBySlug($slug);

                if ($categoryId <= 0) {
                    $this->db->execute(
                        "INSERT INTO {$this->prefix}post_categories (name, slug, parent_id, sort_order, alias_domains_json) VALUES (?, ?, ?, ?, ?)",
                        [$name, $slug, $rootId, $sortOrder, '[]']
                    );
                } else {
                    $this->db->execute(
                        "UPDATE {$this->prefix}post_categories SET name = ?, parent_id = ?, sort_order = ? WHERE id = ?",
                        [$name, $rootId, $sortOrder, $categoryId]
                    );
                }

                $sortOrder += 10;
            }
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('admin.pages')->warning('Standardkategorien für Seiten konnten nicht vollständig synchronisiert werden.', [
                'exception' => $e->getMessage(),
            ]);
        }
    }

    private function getCategoryIdBySlug(string $slug): int
    {
        return (int) ($this->db->get_var(
            "SELECT id FROM {$this->prefix}post_categories WHERE slug = ? LIMIT 1",
            [$slug]
        ) ?: 0);
    }

    private function categoryExists(int $categoryId): bool
    {
        if ($categoryId <= 0) {
            return false;
        }

        return (int) $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}post_categories WHERE id = ?",
            [$categoryId]
        ) > 0;
    }

    private function pageExists(int $pageId): bool
    {
        if ($pageId <= 0) {
            return false;
        }

        return (int) $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}pages WHERE id = ?",
            [$pageId]
        ) > 0;
    }

    /**
     * @param array<int,int> $ids
     * @return array<int,int>
     */
    private function getExistingPageIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->db->get_results(
            "SELECT id FROM {$this->prefix}pages WHERE id IN ({$placeholders})",
            $ids
        ) ?: [];

        $existingIds = array_map(static fn (object $row): int => (int) ($row->id ?? 0), $rows);
        $existingIds = array_values(array_unique(array_filter($existingIds, static fn (int $id): bool => $id > 0)));
        sort($existingIds);

        return $existingIds;
    }

    /**
     * Daten für die Listenansicht
     */
    public function getListData(): array
    {
        // KPI-Counts
        $total     = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}pages");
        $published = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}pages WHERE status = 'published'");
        $drafts    = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}pages WHERE status = 'draft'");
        $private   = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}pages WHERE status = 'private'");

        // Filter
        $statusFilter = $this->normalizeListStatus((string)($_GET['status'] ?? ''));
        $categoryFilter = $this->normalizeExistingCategoryId((int)($_GET['category'] ?? 0));
        $search       = $this->sanitizeSearchTerm((string)($_GET['q'] ?? ''));

        // Query bauen
        $where  = [];
        $params = [];

        if ($statusFilter !== '') {
            $where[]  = 'p.status = ?';
            $params[] = $statusFilter;
        }
        if ($categoryFilter > 0) {
            $where[] = 'p.category_id = ?';
            $params[] = $categoryFilter;
        }
        if ($search !== '') {
            $where[]  = '(p.title LIKE ? OR p.title_en LIKE ? OR p.slug LIKE ? OR p.slug_en LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $pages = $this->db->get_results(
                "SELECT p.id, p.title, p.title_en, p.slug, p.slug_en, p.content_en, p.status, p.category_id, p.created_at, p.updated_at,
                    u.display_name AS author,
                    c.name AS category_name
             FROM {$this->prefix}pages p
             LEFT JOIN {$this->prefix}users u ON p.author_id = u.id
             LEFT JOIN {$this->prefix}post_categories c ON c.id = p.category_id
             {$whereClause}
             ORDER BY p.created_at DESC
             LIMIT 200",
            $params
        ) ?: [];

        $categories = $this->db->get_results(
            "SELECT id, name, slug, parent_id, sort_order FROM {$this->prefix}post_categories ORDER BY sort_order ASC, name ASC"
        ) ?: [];

        return [
            'pages'     => array_map(static function (object $page): object {
                $page->is_english_only = trim((string) ($page->title ?? '')) === ''
                    && trim((string) ($page->slug ?? '')) === ''
                    && (
                        trim((string) ($page->title_en ?? '')) !== ''
                        || trim((string) ($page->content_en ?? '')) !== ''
                        || trim((string) ($page->slug_en ?? '')) !== ''
                    );
                $page->has_english_variant = trim((string) ($page->title_en ?? '')) !== ''
                    || trim((string) ($page->content_en ?? '')) !== ''
                    || trim((string) ($page->slug_en ?? '')) !== '';
                $page->display_title = trim((string) ($page->title ?? '')) !== ''
                    ? (string) ($page->title ?? '')
                    : (string) ($page->title_en ?? '');
                $page->display_slug = trim((string) ($page->slug ?? '')) !== ''
                    ? (string) ($page->slug ?? '')
                    : (string) ($page->slug_en ?? '');

                return $page;
            }, $pages),
            'categories' => $this->buildOrderedCategoryOptions(array_map(fn($category) => (array) $category, $categories)),
            'counts'    => compact('total', 'published', 'drafts', 'private'),
            'filter'    => $statusFilter,
            'catFilter' => $categoryFilter,
            'search'    => $search,
        ];
    }

    /**
     * Daten für Edit/Create-View
     */
    public function getEditData(?int $id): array
    {
        $page = null;
        if ($id !== null) {
            $page = $this->db->get_row(
                "SELECT * FROM {$this->prefix}pages WHERE id = ?",
                [$id]
            );
        }

        $categories = $this->db->get_results(
            "SELECT id, name, slug, parent_id, sort_order FROM {$this->prefix}post_categories ORDER BY sort_order ASC, name ASC"
        ) ?: [];

        return [
            'page'   => $page,
            'isNew'  => $page === null,
            'categories' => $this->buildOrderedCategoryOptions(array_map(fn($category) => (array) $category, $categories)),
            'seoMeta' => $id !== null ? SEOService::getInstance()->getContentMeta('page', $id) : SEOService::getInstance()->getContentMeta('page', 0),
        ];
    }

    /**
     * Seite speichern (Create oder Update)
     */
    public function save(array $post, int $userId): array
    {
        $id     = (int)($post['id'] ?? 0);
        $editorLocale = in_array(strtolower(trim((string) ($post['editor_locale'] ?? 'de'))), ['de', 'en'], true)
            ? strtolower(trim((string) ($post['editor_locale'] ?? 'de')))
            : 'de';
        $existingPage = $id > 0
            ? (array) ($this->db->get_row("SELECT title, title_en, slug, slug_en, content, content_en FROM {$this->prefix}pages WHERE id = ? LIMIT 1", [$id]) ?: [])
            : [];
        $title  = $this->sanitizePlainText((string)($post['title'] ?? ''), 255);
        $slug   = trim($post['slug'] ?? '');
        $slugEn = trim((string)($post['slug_en'] ?? ''));
        $defaultStatus = function_exists('get_option') ? (string)get_option('setting_page_default_status', 'draft') : 'draft';
        if (!in_array($defaultStatus, ['published', 'draft', 'private'], true)) {
            $defaultStatus = 'draft';
        }

        $status = in_array((string)($post['status'] ?? ''), ['published', 'draft', 'private'], true)
            ? (string)$post['status']
            : $defaultStatus;
        $content    = $post['content'] ?? '';
        $titleEn    = $this->sanitizePlainText((string)($post['title_en'] ?? ''), 255);
        $contentEn  = $post['content_en'] ?? '';
        $hideTitle  = (int)($post['hide_title'] ?? 0);
        $categoryId = (int)($post['category_id'] ?? 0);
        $featuredImage = $this->sanitizeMediaReference((string)($post['featured_image'] ?? ''));
        $featuredImageTempPath = $this->sanitizeMediaReference((string)($post['featured_image_temp_path'] ?? ''));
        $metaTitle  = $this->sanitizePlainText((string)($post['meta_title'] ?? ''), 255);
        $metaDesc   = $this->sanitizePlainText((string)($post['meta_description'] ?? ''), 2000);
        if ($id > 0 && $existingPage === []) {
            return ['success' => false, 'error' => 'Die Seite existiert nicht mehr. Bitte Liste neu laden.'];
        }

        if ($editorLocale === 'en' && $existingPage !== []) {
            $title = $this->sanitizePlainText((string) ($existingPage['title'] ?? ''), 255);
            $slug = trim((string) ($existingPage['slug'] ?? ''));
            $content = $existingPage['content'] ?? '';
        }

        if ($editorLocale === 'de' && $existingPage !== []) {
            $titleEn = $this->sanitizePlainText((string) ($existingPage['title_en'] ?? ''), 255);
            $slugEn = trim((string) ($existingPage['slug_en'] ?? ''));
            $contentEn = $existingPage['content_en'] ?? '';
        }

        $slugEn     = $this->normalizeSlug($slugEn);
        $slugSource = $slug !== ''
            ? $slug
            : ($slugEn !== ''
                ? $slugEn
                : $this->pageManager->generateSlug($title !== '' ? $title : $titleEn));
        $slug       = $this->normalizeSlug($slugSource);

        if ($title === '' && $titleEn === '') {
            return ['success' => false, 'error' => 'Bitte mindestens einen deutschen oder englischen Titel angeben.'];
        }

        if ($slug === '') {
            return ['success' => false, 'error' => 'Bitte einen gültigen Slug angeben.'];
        }

        if ($categoryId > 0 && !$this->categoryExists($categoryId)) {
            return ['success' => false, 'error' => 'Die ausgewählte Kategorie existiert nicht mehr.'];
        }

        if ($this->isSlugTaken($slug, $id)) {
            return ['success' => false, 'error' => 'Dieser Slug ist bereits vergeben.'];
        }

        if ($slugEn !== '' && $this->isLocalizedSlugTaken($slugEn, $id)) {
            return ['success' => false, 'error' => 'Dieser englische Slug ist bereits vergeben.'];
        }

        $contentMediaPlacement = ContentMediaPlacementService::getInstance();
        [$content, $contentEn] = $contentMediaPlacement->relocateTemporaryContentMediaBatch([$content, $contentEn], 'page', $slug);
        $featuredImage = $contentMediaPlacement->relocateTemporaryFeaturedImage($featuredImage, $featuredImageTempPath, 'page', $slug);

        $savePayload = [
            'title' => $title,
            'title_en' => $titleEn,
            'slug' => $slug,
            'slug_en' => $slugEn !== '' ? $slugEn : null,
            'status' => $status,
            'content' => $content,
            'content_en' => $contentEn,
            'hide_title' => $hideTitle,
            'category_id' => $categoryId > 0 ? $categoryId : null,
            'featured_image' => $featuredImage,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDesc,
        ];

        $this->logContentEnSnapshot('pre_filter', $id, $savePayload, [
            'request_content_en' => $contentEn,
            'request_title_en' => $titleEn,
            'request_slug_en' => $slugEn,
            'status' => $status,
            'is_new' => $id <= 0,
        ]);

        $filteredPayload = Hooks::applyFilters('cms_prepare_page_save_payload', $savePayload, $post, $id, $userId);
        if (is_array($filteredPayload)) {
            $savePayload = array_merge($savePayload, $filteredPayload);
        }

        $this->logContentEnSnapshot('post_filter', $id, $savePayload, [
            'status' => (string) ($savePayload['status'] ?? $status),
            'is_new' => $id <= 0,
        ]);

        try {
            if ($id > 0) {
                $existing = $this->db->get_row("SELECT slug, slug_en FROM {$this->prefix}pages WHERE id = ? LIMIT 1", [$id]);
                // Update
                $updated = $this->pageManager->updatePage($id, $savePayload);
                if (!$updated) {
                    return ['success' => false, 'error' => 'Seite konnte nicht aktualisiert werden.'];
                }
                SEOService::getInstance()->saveContentMeta('page', $id, $post);
                $this->createSlugRedirectIfNeeded(
                    (string)($existing->slug ?? ''),
                    $slug,
                    (string)($existing->slug_en ?? ''),
                    (string)($savePayload['slug_en'] ?? '')
                );
                Hooks::doAction('cms_after_page_save', $id, $savePayload, $post);
                $this->clearContentCacheIfEnabled('page_update', $id);
                $this->logPersistedContentEnSnapshot($id, 'post_write', [
                    'status' => (string) ($savePayload['status'] ?? $status),
                    'is_new' => false,
                ]);
                return ['success' => true, 'id' => $id, 'message' => 'Seite aktualisiert.'];
            } else {
                // Create
                $newId = $this->pageManager->createPage((string)$savePayload['title'], (string)$savePayload['content'], (string)$savePayload['status'], $userId, (int)$savePayload['hide_title']);
                if ($newId > 0) {
                    // Update meta fields
                    $this->db->execute(
                        "UPDATE {$this->prefix}pages 
                             SET slug = ?, slug_en = ?, title_en = ?, content_en = ?, category_id = ?, featured_image = ?, meta_title = ?, meta_description = ?
                         WHERE id = ?",
                        [
                            (string)$savePayload['slug'],
                            $savePayload['slug_en'],
                            (string)($savePayload['title_en'] ?? ''),
                            (string)($savePayload['content_en'] ?? ''),
                                $savePayload['category_id'],
                            (string)$savePayload['featured_image'],
                            (string)$savePayload['meta_title'],
                            (string)$savePayload['meta_description'],
                            $newId,
                        ]
                    );
                    SEOService::getInstance()->saveContentMeta('page', $newId, $post);
                    Hooks::doAction('cms_after_page_save', $newId, $savePayload, $post);
                    $this->clearContentCacheIfEnabled('page_create', $newId);
                    $this->logPersistedContentEnSnapshot($newId, 'post_write', [
                        'status' => (string) ($savePayload['status'] ?? $status),
                        'is_new' => true,
                    ]);
                    return ['success' => true, 'id' => $newId, 'message' => 'Seite erstellt.'];
                }
                return ['success' => false, 'error' => 'Seite konnte nicht erstellt werden.'];
            }
        } catch (\Throwable $e) {
            return $this->failResult(
                'pages.save.failed',
                'Seite konnte nicht gespeichert werden.',
                $e,
                ['page_id' => $id, 'status' => $status, 'category_id' => $categoryId, 'user_id' => $userId]
            );
        }
    }

    /**
     * Seite löschen
     */
    public function delete(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige Seiten-ID.'];
        }

        if (!$this->pageExists($id)) {
            return ['success' => false, 'error' => 'Seite wurde nicht gefunden oder bereits gelöscht.'];
        }

        try {
            $statement = $this->db->execute(
                "DELETE FROM {$this->prefix}pages WHERE id = ? LIMIT 1",
                [$id]
            );

            if (!$statement || $statement->rowCount() < 1) {
                return $this->failResult(
                    'pages.delete.failed',
                    'Seite konnte nicht gelöscht werden.',
                    null,
                    ['page_id' => $id, 'db_last_error' => trim((string)$this->db->last_error)]
                );
            }

            Hooks::doAction('page_deleted', $id);
            $this->clearContentCacheIfEnabled('page_delete', $id);

            return ['success' => true, 'message' => 'Seite gelöscht.'];
        } catch (\Throwable $e) {
            return $this->failResult('pages.delete.failed', 'Seite konnte nicht gelöscht werden.', $e, ['page_id' => $id]);
        }
    }

    /**
     * Bulk-Aktion ausführen
     */
    public function bulkAction(string $action, array $ids, array $payload = []): array
    {
        $action = $this->normalizeBulkAction($action);

        if (empty($ids)) {
            return ['success' => false, 'error' => 'Keine Einträge ausgewählt.'];
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $id): bool => $id > 0)));
        if ($ids === []) {
            return ['success' => false, 'error' => 'Keine gültigen Seiten-IDs ausgewählt.'];
        }

        $existingIds = $this->getExistingPageIds($ids);
        if ($existingIds === []) {
            return ['success' => false, 'error' => 'Die ausgewählten Seiten existieren nicht mehr. Bitte Liste neu laden.'];
        }

        if (count($existingIds) !== count($ids)) {
            return ['success' => false, 'error' => 'Mindestens eine ausgewählte Seite existiert nicht mehr. Bitte Liste neu laden und Aktion erneut ausführen.'];
        }

        $ids = $existingIds;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        try {
            switch ($action) {
                case 'delete':
                    $this->db->execute(
                        "DELETE FROM {$this->prefix}pages WHERE id IN ({$placeholders})",
                        $ids
                    );

                    foreach ($ids as $pageId) {
                        Hooks::doAction('page_deleted', (int) $pageId);
                    }

                    $this->clearContentCacheIfEnabled('page_bulk_delete', (int) $ids[0]);

                    return ['success' => true, 'message' => count($ids) . ' Seite(n) gelöscht.'];

                case 'publish':
                    $this->db->execute(
                        "UPDATE {$this->prefix}pages SET status = 'published', updated_at = NOW() WHERE id IN ({$placeholders})",
                        $ids
                    );
                    $this->clearContentCacheIfEnabled('page_bulk_publish', (int) $ids[0]);
                    return ['success' => true, 'message' => count($ids) . ' Seite(n) veröffentlicht.'];

                case 'draft':
                    $this->db->execute(
                        "UPDATE {$this->prefix}pages SET status = 'draft', updated_at = NOW() WHERE id IN ({$placeholders})",
                        $ids
                    );
                    $this->clearContentCacheIfEnabled('page_bulk_draft', (int) $ids[0]);
                    return ['success' => true, 'message' => count($ids) . ' Seite(n) als Entwurf gespeichert.'];

                case 'set_category':
                    $categoryId = (int) ($payload['bulk_category_id'] ?? 0);
                    if ($categoryId <= 0) {
                        return ['success' => false, 'error' => 'Bitte eine Kategorie für die Bulk-Aktion auswählen.'];
                    }

                    if (!$this->categoryExists($categoryId)) {
                        return ['success' => false, 'error' => 'Die ausgewählte Kategorie existiert nicht.'];
                    }

                    $params = array_merge([$categoryId], $ids);
                    $this->db->execute(
                        "UPDATE {$this->prefix}pages SET category_id = ?, updated_at = NOW() WHERE id IN ({$placeholders})",
                        $params
                    );

                    $this->clearContentCacheIfEnabled('page_bulk_set_category', (int) $ids[0]);

                    return ['success' => true, 'message' => count($ids) . ' Seite(n) einer Kategorie zugewiesen.'];

                case 'clear_category':
                    $this->db->execute(
                        "UPDATE {$this->prefix}pages SET category_id = NULL, updated_at = NOW() WHERE id IN ({$placeholders})",
                        $ids
                    );
                    $this->clearContentCacheIfEnabled('page_bulk_clear_category', (int) $ids[0]);
                    return ['success' => true, 'message' => count($ids) . ' Seite(n) aus der Kategorie entfernt.'];

                default:
                    return ['success' => false, 'error' => 'Unbekannte Aktion.'];
            }
        } catch (\Throwable $e) {
            return $this->failResult(
                'pages.bulk.failed',
                'Bulk-Aktion für Seiten fehlgeschlagen.',
                $e,
                ['bulk_action' => $action, 'page_ids' => $ids]
            );
        }
    }

    private function sanitizePlainText(string $value, int $maxLength): string
    {
        $value = trim(strip_tags($value));
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', $value) ?? '';

        return function_exists('mb_substr')
            ? mb_substr($value, 0, $maxLength)
            : substr($value, 0, $maxLength);
    }

    private function logContentEnSnapshot(string $phase, int $pageId, array $payload, array $context = []): void
    {
        try {
            Logger::instance()->withChannel('admin.pages')->info('Page content_en snapshot.', array_merge([
                'phase' => $phase,
                'page_id' => $pageId,
                'title_en' => $this->summarizeEditorContentValue((string) ($payload['title_en'] ?? '')),
                'slug_en' => $this->summarizeEditorContentValue((string) ($payload['slug_en'] ?? '')),
                'content_en' => $this->summarizeEditorContentValue($payload['content_en'] ?? ''),
            ], $context));
        } catch (\Throwable $_error) {
            // Debug-Instrumentierung darf den Save-Pfad niemals stören.
        }
    }

    private function logPersistedContentEnSnapshot(int $pageId, string $phase, array $context = []): void
    {
        if ($pageId <= 0) {
            return;
        }

        try {
            $row = $this->db->get_row(
                "SELECT title_en, slug_en, content_en FROM {$this->prefix}pages WHERE id = ? LIMIT 1",
                [$pageId]
            );

            if (!is_object($row)) {
                Logger::instance()->withChannel('admin.pages')->info('Page content_en snapshot.', array_merge([
                    'phase' => $phase,
                    'page_id' => $pageId,
                    'row_missing' => true,
                ], $context));
                return;
            }

            Logger::instance()->withChannel('admin.pages')->info('Page content_en snapshot.', array_merge([
                'phase' => $phase,
                'page_id' => $pageId,
                'row_missing' => false,
                'title_en' => $this->summarizeEditorContentValue((string) ($row->title_en ?? '')),
                'slug_en' => $this->summarizeEditorContentValue((string) ($row->slug_en ?? '')),
                'content_en' => $this->summarizeEditorContentValue((string) ($row->content_en ?? '')),
            ], $context));
        } catch (\Throwable $_error) {
            // Debug-Instrumentierung darf den Save-Pfad niemals stören.
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function summarizeEditorContentValue(mixed $value): array
    {
        $stringValue = is_string($value)
            ? $value
            : (is_scalar($value) || $value === null ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if (!is_string($stringValue)) {
            $stringValue = '';
        }

        $normalizedPreview = preg_replace('/\s+/u', ' ', trim($stringValue)) ?? '';
        $summary = [
            'length' => strlen($stringValue),
            'sha1' => sha1($stringValue),
            'is_empty' => trim($stringValue) === '',
            'preview' => substr($normalizedPreview, 0, 180),
        ];

        try {
            $decoded = json_decode($stringValue, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($decoded) && isset($decoded['blocks']) && is_array($decoded['blocks'])) {
                $summary['json_blocks'] = count($decoded['blocks']);

                if (!empty($decoded['blocks'][0]) && is_array($decoded['blocks'][0])) {
                    $summary['first_block_type'] = (string) ($decoded['blocks'][0]['type'] ?? '');
                }
            }
        } catch (\Throwable $_error) {
            $summary['json_blocks'] = null;
        }

        return $summary;
    }

    private function sanitizeMediaReference(string $value): string
    {
        $value = trim(str_replace('\\', '/', $value));
        if ($value === '' || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            return '';
        }

        return function_exists('mb_substr') ? mb_substr($value, 0, 500) : substr($value, 0, 500);
    }

    private function sanitizeSearchTerm(string $value): string
    {
        return $this->sanitizePlainText($value, 120);
    }

    private function normalizeListStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return in_array($status, self::ALLOWED_LIST_STATUSES, true) ? $status : '';
    }

    private function normalizeExistingCategoryId(int $categoryId): int
    {
        return $this->categoryExists($categoryId) ? $categoryId : 0;
    }

    private function normalizeBulkAction(string $action): string
    {
        $action = trim($action);

        return in_array($action, self::ALLOWED_BULK_ACTIONS, true) ? $action : '';
    }

    private function failResult(string $action, string $message, ?\Throwable $exception = null, array $context = []): array
    {
        $this->logFailure($action, $message, $exception, $context);

        return ['success' => false, 'error' => $message . ' Bitte Logs prüfen.'];
    }

    private function clearContentCacheIfEnabled(string $reason, int $contentId): void
    {
        try {
            $enabled = (string)($this->db->get_var("SELECT option_value FROM {$this->prefix}settings WHERE option_name = 'perf_auto_clear_content_cache' LIMIT 1") ?? '1') !== '0';
            if (!$enabled) {
                return;
            }

            CacheManager::instance()->clear();
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('admin.pages')->warning('Content-Cache konnte nach Seitenänderung nicht automatisch geleert werden.', [
                'reason' => $reason,
                'page_id' => $contentId,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    private function logFailure(string $action, string $message, ?\Throwable $exception = null, array $context = []): void
    {
        if ($exception !== null) {
            $context['exception'] = $exception->getMessage();
        }

        Logger::instance()->withChannel('admin.pages')->error($message, $context);
        AuditLogger::instance()->log(
            AuditLogger::CAT_CONTENT,
            $action,
            $message,
            'pages',
            null,
            $context,
            'error'
        );
    }

    private function normalizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug) ?? $slug;
        $slug = preg_replace('/-+/', '-', $slug) ?? $slug;
        return trim($slug, '-');
    }

    private function isSlugTaken(string $slug, int $ignoreId = 0): bool
    {
        $params = [$slug];
        $sql = "SELECT COUNT(*) FROM {$this->prefix}pages WHERE slug = ?";

        if ($ignoreId > 0) {
            $sql .= " AND id != ?";
            $params[] = $ignoreId;
        }

        return (int)$this->db->get_var($sql, $params) > 0;
    }

    private function isLocalizedSlugTaken(string $slug, int $ignoreId = 0): bool
    {
        $params = [$slug, $slug];
        $sql = "SELECT COUNT(*) FROM {$this->prefix}pages WHERE (slug_en = ? OR slug = ?)";

        if ($ignoreId > 0) {
            $sql .= " AND id != ?";
            $params[] = $ignoreId;
        }

        return (int)$this->db->get_var($sql, $params) > 0;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function buildOrderedCategoryOptions(array $rows): array
    {
        $byId = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $row['id'] = $id;
            $row['parent_id'] = (int) ($row['parent_id'] ?? 0);
            $byId[$id] = $row;
        }

        $byParent = [];
        foreach ($byId as $id => $row) {
            $parentId = (int) ($row['parent_id'] ?? 0);
            if ($parentId > 0 && !isset($byId[$parentId])) {
                $parentId = 0;
            }
            $byParent[$parentId][] = $id;
        }

        $flat = [];
        $walker = function (int $parentId, int $depth) use (&$walker, &$flat, $byParent, $byId): void {
            foreach ($byParent[$parentId] ?? [] as $categoryId) {
                if (!isset($byId[$categoryId])) {
                    continue;
                }

                $row = $byId[$categoryId];
                $row['depth'] = $depth;
                $row['option_label'] = str_repeat('— ', $depth) . (string) ($row['name'] ?? '');
                $flat[] = $row;
                $walker($categoryId, $depth + 1);
            }
        };

        $walker(0, 0);

        return $flat;
    }

    private function createSlugRedirectIfNeeded(string $oldSlug, string $newSlug, string $oldLocalizedSlug = '', string $newLocalizedSlug = ''): void
    {
        $oldSlug = trim($oldSlug);
        $newSlug = trim($newSlug);
        $oldLocalizedSlug = trim($oldLocalizedSlug);
        $newLocalizedSlug = trim($newLocalizedSlug);
        $localizationService = ContentLocalizationService::getInstance();

        if ($oldSlug === '' || $newSlug === '' || $oldSlug === $newSlug) {
            // lokalisierte Redirects ggf. trotzdem weiter prüfen
        } else {
            RedirectService::getInstance()->createAutomaticRedirect(
                '/' . $oldSlug,
                '/' . $newSlug,
                'Automatisch bei Seiten-Slug-Änderung angelegt'
            );
        }

        foreach (ContentLocalizationService::getInstance()->getContentLocales() as $locale) {
            $sourceSlug = $locale === 'en' && $oldLocalizedSlug !== '' ? $oldLocalizedSlug : $oldSlug;
            $targetSlug = $locale === 'en' && $newLocalizedSlug !== '' ? $newLocalizedSlug : $newSlug;

            if ($sourceSlug === '' || $targetSlug === '' || $sourceSlug === $targetSlug) {
                continue;
            }

            $localizedSourcePath = $localizationService->buildLocalizedPath('/' . ltrim($sourceSlug, '/'), $locale);
            $localizedTargetPath = $localizationService->buildLocalizedPath('/' . ltrim($targetSlug, '/'), $locale);

            RedirectService::getInstance()->createAutomaticRedirect(
                $localizedSourcePath,
                $localizedTargetPath,
                'Automatisch bei lokalisiertem Seiten-Slug angelegt'
            );

            $legacyLocalizedSourcePath = $this->buildLegacyLocalizedPagePath($sourceSlug, $locale);
            if ($legacyLocalizedSourcePath !== $localizedSourcePath) {
                RedirectService::getInstance()->createAutomaticRedirect(
                    $legacyLocalizedSourcePath,
                    $localizedTargetPath,
                    'Legacy-Weiterleitung bei lokalisiertem Seiten-Slug angelegt'
                );
            }
        }
    }

    private function buildLegacyLocalizedPagePath(string $slug, string $locale): string
    {
        $slug = trim($slug, '/');
        $locale = trim($locale, '/');

        return '/' . $slug . '/' . $locale;
    }
}
