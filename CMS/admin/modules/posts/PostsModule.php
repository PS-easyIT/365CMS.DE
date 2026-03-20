<?php
declare(strict_types=1);

/**
 * Posts Module – CRUD-Logik für Beiträge
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Database;
use CMS\Hooks;
use CMS\Services\ContentLocalizationService;
use CMS\Services\MediaDeliveryService;
use CMS\Services\MediaService;
use CMS\Services\PermalinkService;
use CMS\Services\RedirectService;
use CMS\Services\SEOService;

class PostsModule
{
    private Database $db;
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

    public function __construct()
    {
        $this->db     = Database::instance();
        $this->prefix = $this->db->getPrefix();
        $this->ensureColumns();
        $this->ensureCategoryColumns();
        $this->ensurePostCategoryRelationTable();
        $this->ensureDefaultCategories();
    }

    /**
     * Ergänzt fehlende Beitrags-Spalten in Altinstallationen.
     */
    private function ensureColumns(): void
    {
        $columns = [
            'slug_en' => "ALTER TABLE {$this->prefix}posts ADD COLUMN slug_en VARCHAR(255) DEFAULT NULL AFTER slug",
            'featured_image' => "ALTER TABLE {$this->prefix}posts ADD COLUMN featured_image VARCHAR(500) DEFAULT NULL AFTER excerpt",
            'title_en' => "ALTER TABLE {$this->prefix}posts ADD COLUMN title_en VARCHAR(255) DEFAULT NULL AFTER title",
            'content_en' => "ALTER TABLE {$this->prefix}posts ADD COLUMN content_en LONGTEXT DEFAULT NULL AFTER content",
            'excerpt_en' => "ALTER TABLE {$this->prefix}posts ADD COLUMN excerpt_en TEXT DEFAULT NULL AFTER excerpt",
            'meta_title' => "ALTER TABLE {$this->prefix}posts ADD COLUMN meta_title VARCHAR(255) DEFAULT NULL AFTER allow_comments",
            'meta_description' => "ALTER TABLE {$this->prefix}posts ADD COLUMN meta_description TEXT DEFAULT NULL AFTER meta_title",
            'author_display_name' => "ALTER TABLE {$this->prefix}posts ADD COLUMN author_display_name VARCHAR(150) DEFAULT NULL AFTER author_id",
        ];

        foreach ($columns as $column => $sql) {
            try {
                $stmt = $this->db->query("SHOW COLUMNS FROM {$this->prefix}posts LIKE '{$column}'");
                if ($stmt instanceof \PDOStatement && !$stmt->fetch()) {
                    $this->db->query($sql);
                }
            } catch (\Throwable $e) {
                error_log(sprintf('PostsModule::ensureColumns(%s) warning: %s', $column, $e->getMessage()));
            }
        }
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
                error_log(sprintf('PostsModule::ensureCategoryColumns(%s) warning: %s', $column, $e->getMessage()));
            }
        }
    }

    private function ensurePostCategoryRelationTable(): void
    {
        try {
            $this->db->query(
                "CREATE TABLE IF NOT EXISTS {$this->prefix}post_category_rel (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    post_id BIGINT UNSIGNED NOT NULL,
                    category_id INT UNSIGNED NOT NULL,
                    UNIQUE KEY unique_post_category (post_id, category_id),
                    INDEX idx_post_id (post_id),
                    INDEX idx_category_id (category_id),
                    FOREIGN KEY (post_id) REFERENCES {$this->prefix}posts(id) ON DELETE CASCADE,
                    FOREIGN KEY (category_id) REFERENCES {$this->prefix}post_categories(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        } catch (\Throwable $e) {
            error_log(sprintf('PostsModule::ensurePostCategoryRelationTable warning: %s', $e->getMessage()));
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
            error_log(sprintf('PostsModule::ensureDefaultCategories warning: %s', $e->getMessage()));
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

    /**
     * Daten für die Listenansicht
     */
    public function getListData(): array
    {
        $total     = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}posts");
        $published = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}posts WHERE status = 'published'");
        $drafts    = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}posts WHERE status = 'draft'");

        $statusFilter   = $_GET['status'] ?? '';
        $categoryFilter = (int)($_GET['category'] ?? 0);
        $search         = trim($_GET['q'] ?? '');

        $where  = [];
        $params = [];

        if ($statusFilter !== '' && in_array($statusFilter, ['published', 'draft'], true)) {
            $where[]  = 'p.status = ?';
            $params[] = $statusFilter;
        }
        if ($categoryFilter > 0) {
            $where[]  = "(p.category_id = ? OR EXISTS (
                SELECT 1 FROM {$this->prefix}post_category_rel pcr
                WHERE pcr.post_id = p.id AND pcr.category_id = ?
            ))";
            $params[] = $categoryFilter;
            $params[] = $categoryFilter;
        }
        if ($search !== '') {
            $where[]  = '(p.title LIKE ? OR p.slug LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $posts = $this->db->get_results(
            "SELECT p.id, p.title, p.slug, p.status, p.category_id, p.created_at, p.updated_at,
                    u.display_name AS author,
                    c.name AS category_name
             FROM {$this->prefix}posts p
             LEFT JOIN {$this->prefix}users u ON p.author_id = u.id
             LEFT JOIN {$this->prefix}post_categories c ON p.category_id = c.id
             {$whereClause}
             ORDER BY p.created_at DESC
             LIMIT 200",
            $params
        ) ?: [];

        $categories = $this->db->get_results(
            "SELECT id, name, slug, parent_id, sort_order FROM {$this->prefix}post_categories ORDER BY sort_order ASC, name ASC"
        ) ?: [];

        $categories = $this->buildOrderedCategoryOptions(array_map(fn($c) => (array) $c, $categories));

        return [
            'posts'      => array_map(fn($p) => (array)$p, $posts),
            'categories' => $categories,
            'counts'     => compact('total', 'published', 'drafts'),
            'filter'     => $statusFilter,
            'catFilter'  => $categoryFilter,
            'search'     => $search,
        ];
    }

    /**
     * Daten für Edit/Create
     */
    public function getEditData(?int $id): array
    {
        $post = null;
        if ($id !== null) {
            $post = $this->db->get_row(
                "SELECT * FROM {$this->prefix}posts WHERE id = ?",
                [$id]
            );
        }

        $postData = $post ? (array)$post : null;

        $categories = $this->db->get_results(
            "SELECT id, name, slug, parent_id, sort_order FROM {$this->prefix}post_categories ORDER BY sort_order ASC, name ASC"
        ) ?: [];

        $tags = $this->db->get_results(
            "SELECT id, name, slug FROM {$this->prefix}post_tags ORDER BY name ASC"
        ) ?: [];

        $postTags = [];
        $postCategoryIds = [];
        $additionalCategoryIds = [];
        if ($postData !== null) {
            $postTags = $this->db->get_results(
                "SELECT t.id, t.name, t.slug
                 FROM {$this->prefix}post_tags t
                 INNER JOIN {$this->prefix}post_tag_rel ptr ON ptr.tag_id = t.id
                 WHERE ptr.post_id = ?
                 ORDER BY t.name ASC",
                [(int)($postData['id'] ?? 0)]
            ) ?: [];

            $postCategoryIds = $this->getPostCategoryIds((int) ($postData['id'] ?? 0));
            $primaryCategoryId = (int) ($postData['category_id'] ?? 0);
            $additionalCategoryIds = array_values(array_filter(
                $postCategoryIds,
                static fn(int $categoryId): bool => $categoryId > 0 && $categoryId !== $primaryCategoryId
            ));
        }

        return [
            'post'       => $postData,
            'isNew'      => $post === null,
            'categories' => $this->buildOrderedCategoryOptions(array_map(fn($c) => (array)$c, $categories)),
            'tags'       => array_map(fn($t) => (array)$t, $tags),
            'postTags'   => array_map(fn($t) => (array)$t, $postTags),
            'postCategoryIds' => $postCategoryIds,
            'additionalCategoryIds' => $additionalCategoryIds,
            'seoMeta'    => $id !== null ? SEOService::getInstance()->getContentMeta('post', $id) : SEOService::getInstance()->getContentMeta('post', 0),
        ];
    }

    /**
     * Daten für die Admin-Ansicht der Beitrags-Kategorien.
     */
    public function getCategoryAdminData(): array
    {
        $categories = $this->db->get_results(
            "SELECT c.id, c.name, c.slug, c.parent_id, c.sort_order, c.alias_domains_json,
                    COUNT(p.id) AS post_count,
                    (
                        SELECT COUNT(DISTINCT p2.id)
                        FROM {$this->prefix}posts p2
                        LEFT JOIN {$this->prefix}post_category_rel pcr2 ON pcr2.post_id = p2.id
                        WHERE p2.category_id = c.id OR pcr2.category_id = c.id
                    ) AS assigned_post_count
             FROM {$this->prefix}post_categories c
             LEFT JOIN {$this->prefix}posts p ON p.category_id = c.id
             GROUP BY c.id, c.name, c.slug, c.parent_id, c.sort_order, c.alias_domains_json
             ORDER BY c.sort_order ASC, c.name ASC"
        ) ?: [];

        $orderedCategories = $this->buildAdminCategoryRows(array_map(fn($category) => (array) $category, $categories));

        return [
            'categories' => $orderedCategories,
            'categoryOptions' => $this->buildOrderedCategoryOptions(array_map(fn($category) => (array) $category, $categories)),
            'counts' => [
                'total' => count($orderedCategories),
                'assigned_posts' => array_sum(array_map(static fn(array $category): int => (int) ($category['post_count_direct'] ?? 0), $orderedCategories)),
            ],
        ];
    }

    /**
     * Daten für die Admin-Ansicht der Beitrags-Tags.
     */
    public function getTagAdminData(): array
    {
        $tags = $this->db->get_results(
            "SELECT t.id, t.name, t.slug, COUNT(ptr.post_id) AS post_count
             FROM {$this->prefix}post_tags t
             LEFT JOIN {$this->prefix}post_tag_rel ptr ON ptr.tag_id = t.id
             GROUP BY t.id, t.name, t.slug
             ORDER BY t.name ASC"
        ) ?: [];

        return [
            'tags' => array_map(fn($tag) => (array) $tag, $tags),
            'counts' => [
                'total' => count($tags),
                'assigned_posts' => array_sum(array_map(static fn($tag): int => (int) ($tag->post_count ?? 0), $tags)),
            ],
        ];
    }

    /**
     * Post speichern
     */
    public function save(array $post, int $userId): array
    {
        $id         = (int)($post['id'] ?? 0);
        $title      = trim((string)($post['title'] ?? ''));
        $slug       = trim((string)($post['slug'] ?? ''));
        $slugEn     = trim((string)($post['slug_en'] ?? ''));
        $defaultStatus = function_exists('get_option') ? (string)get_option('setting_post_default_status', 'draft') : 'draft';
        if (!in_array($defaultStatus, ['published', 'draft'], true)) {
            $defaultStatus = 'draft';
        }

        $status     = in_array($post['status'] ?? '', ['published', 'draft'], true) ? $post['status'] : $defaultStatus;
        $content    = $post['content'] ?? '';
        $titleEn    = trim((string)($post['title_en'] ?? ''));
        $contentEn  = $post['content_en'] ?? '';
        $excerpt    = trim((string)($post['excerpt'] ?? ''));
        $excerptEn  = trim((string)($post['excerpt_en'] ?? ''));
        $categoryId = (int)($post['category_id'] ?? 0);
        $assignedCategoryIds = $this->normalizeSelectedCategoryIds($categoryId, $post['additional_category_ids'] ?? []);
        if ($categoryId <= 0 && $assignedCategoryIds !== []) {
            $categoryId = (int) ($assignedCategoryIds[0] ?? 0);
        }
        $featuredImage = trim($post['featured_image'] ?? '');
        $featuredImageTempPath = trim($post['featured_image_temp_path'] ?? '');
        $metaTitle  = trim($post['meta_title'] ?? '');
        $metaDesc   = trim($post['meta_description'] ?? '');
        $authorDisplayName = $this->sanitizeAuthorDisplayName((string)($post['author_display_name'] ?? ''));
        $rawTags    = $post['tags'] ?? '';
        $slugEn     = $this->normalizeSlug($slugEn);
        $slugSource = $slug !== ''
            ? $slug
            : ($slugEn !== ''
                ? $slugEn
                : $this->generateSlug($title !== '' ? $title : $titleEn));
        $slug       = $this->normalizeSlug($slugSource);
        $legacyTags = $this->serializeTagsForLegacyColumn($rawTags);

        // Move temp upload to slug subfolder (articles/{slug}/{filename})
        if ($featuredImageTempPath !== '' && str_contains($featuredImageTempPath, '/temp/')) {
            $mediaService = MediaService::getInstance();
            $mediaDelivery = MediaDeliveryService::getInstance();
            $folderSlug   = strtolower((string)preg_replace('/[^a-z0-9]+/i', '_', $slug));
            $folderSlug   = trim($folderSlug, '_');
            $newRelPath   = 'articles/' . $folderSlug . '/' . basename($featuredImageTempPath);
            $moved        = $mediaService->moveFile($featuredImageTempPath, $newRelPath);
            if (!($moved instanceof \CMS\WP_Error)) {
                $featuredImage = $mediaDelivery->buildAccessUrl((string)$moved, true);
            }
        }

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

        $savePayload = [
            'title' => $title,
            'title_en' => $titleEn,
            'slug' => $slug,
            'slug_en' => $slugEn !== '' ? $slugEn : null,
            'content' => $content,
            'content_en' => $contentEn,
            'excerpt' => $excerpt,
            'excerpt_en' => $excerptEn,
            'status' => $status,
            'category_id' => $categoryId ?: null,
            'featured_image' => $featuredImage,
            'tags' => $legacyTags,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDesc,
            'author_display_name' => $authorDisplayName,
        ];

        $filteredPayload = Hooks::applyFilters('cms_prepare_post_save_payload', $savePayload, $post, $id, $userId);
        if (is_array($filteredPayload)) {
            $savePayload = array_merge($savePayload, $filteredPayload);
        }

        try {
            if ($id > 0) {
                $existing = $this->db->get_row("SELECT slug, status, published_at, created_at FROM {$this->prefix}posts WHERE id = ? LIMIT 1", [$id]);
                // published_at setzen, wenn der Beitrag erstmals veröffentlicht wird
                // oder falls ein bereits veröffentlichter Alt-Datensatz noch kein Datum besitzt.
                $wasPublished = ($existing->status ?? '') === 'published';
                $nowPublished = $savePayload['status'] === 'published';
                $hasPublishedAt = !empty($existing->published_at ?? null);
                $setPubAt = ($nowPublished && (!$wasPublished || !$hasPublishedAt)) ? ', published_at = NOW()' : '';
                $this->db->execute(
                    "UPDATE {$this->prefix}posts 
                     SET title = ?, title_en = ?, slug = ?, slug_en = ?, content = ?, content_en = ?, excerpt = ?, excerpt_en = ?, status = ?,
                         category_id = ?, featured_image = ?, tags = ?,
                         meta_title = ?, meta_description = ?, author_display_name = ?,
                         updated_at = NOW(){$setPubAt}
                     WHERE id = ?",
                    [
                        (string)$savePayload['title'],
                        (string)($savePayload['title_en'] ?? ''),
                        (string)$savePayload['slug'],
                        $savePayload['slug_en'],
                        (string)$savePayload['content'],
                        (string)($savePayload['content_en'] ?? ''),
                        (string)$savePayload['excerpt'],
                        (string)($savePayload['excerpt_en'] ?? ''),
                        (string)$savePayload['status'],
                        $savePayload['category_id'],
                        (string)$savePayload['featured_image'],
                        (string)($savePayload['tags'] ?? ''),
                        (string)$savePayload['meta_title'],
                        (string)$savePayload['meta_description'],
                        (string)($savePayload['author_display_name'] ?? ''),
                        $id,
                    ]
                );

                SEOService::getInstance()->saveContentMeta('post', $id, $post);
                $this->syncPostTags($id, $rawTags);
                $this->syncPostCategories($id, $assignedCategoryIds !== [] ? $assignedCategoryIds : ($savePayload['category_id'] !== null ? [(int) $savePayload['category_id']] : []));
                $this->createSlugRedirectIfNeeded((string)($existing->slug ?? ''), $slug, [
                    'published_at' => (string)($existing->published_at ?? ''),
                    'created_at' => (string)($existing->created_at ?? ''),
                ]);
                Hooks::doAction('cms_after_post_save', $id, $savePayload, $post);
                return ['success' => true, 'id' => $id, 'message' => 'Beitrag aktualisiert.'];
            } else {
                $pubAtValue = ($savePayload['status'] === 'published') ? 'NOW()' : 'NULL';
                $this->db->execute(
                    "INSERT INTO {$this->prefix}posts
                     (title, title_en, slug, slug_en, content, content_en, excerpt, excerpt_en, status, category_id, featured_image, tags, meta_title, meta_description, author_id, author_display_name, published_at, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, {$pubAtValue}, NOW(), NOW())",
                    [
                        (string)$savePayload['title'],
                        (string)($savePayload['title_en'] ?? ''),
                        (string)$savePayload['slug'],
                        $savePayload['slug_en'],
                        (string)$savePayload['content'],
                        (string)($savePayload['content_en'] ?? ''),
                        (string)$savePayload['excerpt'],
                        (string)($savePayload['excerpt_en'] ?? ''),
                        (string)$savePayload['status'],
                        $savePayload['category_id'],
                        (string)$savePayload['featured_image'],
                        (string)($savePayload['tags'] ?? ''),
                        (string)$savePayload['meta_title'],
                        (string)$savePayload['meta_description'],
                        $userId,
                        (string)($savePayload['author_display_name'] ?? ''),
                    ]
                );
                $newId = (int)$this->db->lastInsertId();
                SEOService::getInstance()->saveContentMeta('post', $newId, $post);
                $this->syncPostTags($newId, $rawTags);
                $this->syncPostCategories($newId, $assignedCategoryIds !== [] ? $assignedCategoryIds : ($savePayload['category_id'] !== null ? [(int) $savePayload['category_id']] : []));
                Hooks::doAction('cms_after_post_save', $newId, $savePayload, $post);
                return ['success' => true, 'id' => $newId, 'message' => 'Beitrag erstellt.'];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Speichern: ' . $e->getMessage()];
        }
    }

    /**
     * Post löschen
     */
    public function delete(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige Beitrags-ID.'];
        }

        try {
            $success = $this->db->delete('posts', ['id' => $id]);

            if (!$success) {
                $error = trim((string)$this->db->last_error);
                return ['success' => false, 'error' => 'Fehler beim Löschen.' . ($error !== '' ? ' ' . $error : '')];
            }

            return ['success' => true, 'message' => 'Beitrag gelöscht.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Löschen: ' . $e->getMessage()];
        }
    }

    /**
     * Bulk-Aktion
     */
    public function bulkAction(string $action, array $ids, array $payload = []): array
    {
        if (empty($ids)) {
            return ['success' => false, 'error' => 'Keine Einträge ausgewählt.'];
        }

        $ids          = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        try {
            switch ($action) {
                case 'delete':
                    $this->db->execute("DELETE FROM {$this->prefix}posts WHERE id IN ({$placeholders})", $ids);
                    return ['success' => true, 'message' => count($ids) . ' Beitrag/Beiträge gelöscht.'];

                case 'publish':
                    $this->db->execute("UPDATE {$this->prefix}posts SET status = 'published', published_at = COALESCE(published_at, NOW()), updated_at = NOW() WHERE id IN ({$placeholders})", $ids);
                    return ['success' => true, 'message' => count($ids) . ' Beitrag/Beiträge veröffentlicht.'];

                case 'draft':
                    $this->db->execute("UPDATE {$this->prefix}posts SET status = 'draft', updated_at = NOW() WHERE id IN ({$placeholders})", $ids);
                    return ['success' => true, 'message' => count($ids) . ' Beitrag/Beiträge als Entwurf gesetzt.'];

                case 'set_category':
                    $selectedCategoryIds = $this->normalizeSelectedCategoryIds(
                        (int) ($payload['bulk_category_id'] ?? 0),
                        $payload['bulk_category_ids'] ?? []
                    );
                    if ($selectedCategoryIds === []) {
                        return ['success' => false, 'error' => 'Bitte mindestens eine Kategorie für die Bulk-Aktion auswählen.'];
                    }

                    $params = array_merge([(int) ($selectedCategoryIds[0] ?? 0)], $ids);
                    $this->db->execute(
                        "UPDATE {$this->prefix}posts SET category_id = ?, updated_at = NOW() WHERE id IN ({$placeholders})",
                        $params
                    );

                    foreach ($ids as $postId) {
                        $this->syncPostCategories((int) $postId, $selectedCategoryIds);
                    }

                    return ['success' => true, 'message' => count($ids) . ' Beitrag/Beiträge den gewählten Kategorien zugewiesen.'];

                case 'clear_category':
                    $this->db->execute("UPDATE {$this->prefix}posts SET category_id = NULL, updated_at = NOW() WHERE id IN ({$placeholders})", $ids);
                    foreach ($ids as $postId) {
                        $this->syncPostCategories((int) $postId, []);
                    }
                    return ['success' => true, 'message' => count($ids) . ' Beitrag/Beiträge aus der Kategorie entfernt.'];

                case 'set_author_display_name':
                    $authorDisplayName = $this->sanitizeAuthorDisplayName((string) ($payload['bulk_author_display_name'] ?? ''));
                    if ($authorDisplayName === '') {
                        return ['success' => false, 'error' => 'Bitte einen Anzeigenamen für die Bulk-Aktion angeben.'];
                    }

                    $params = array_merge([$authorDisplayName], $ids);
                    $this->db->execute(
                        "UPDATE {$this->prefix}posts SET author_display_name = ?, updated_at = NOW() WHERE id IN ({$placeholders})",
                        $params
                    );

                    return ['success' => true, 'message' => count($ids) . ' Beitrag/Beiträge mit neuem Autoren-Anzeigenamen aktualisiert.'];

                case 'clear_author_display_name':
                    $this->db->execute("UPDATE {$this->prefix}posts SET author_display_name = NULL, updated_at = NOW() WHERE id IN ({$placeholders})", $ids);
                    return ['success' => true, 'message' => count($ids) . ' Beitrag/Beiträge auf den normalen 365CMS-Anzeigenamen zurückgesetzt.'];

                default:
                    return ['success' => false, 'error' => 'Unbekannte Aktion.'];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler bei Bulk-Aktion.'];
        }
    }

    /**
     * Kategorie speichern
     */
    public function saveCategory(array $post): array
    {
        $id   = (int)($post['cat_id'] ?? 0);
        $name = trim($post['cat_name'] ?? '');
        $slug = trim($post['cat_slug'] ?? '');
        $parentId = (int) ($post['parent_id'] ?? 0);
        $normalizedDomains = $this->normalizeCategoryDomains((string) ($post['cat_domains'] ?? ''));

        if ($name === '') {
            return ['success' => false, 'error' => 'Kategoriename darf nicht leer sein.'];
        }
        if ($slug === '') {
            $slug = $this->generateSlug($name);
        }

        if ($this->isCategorySlugTaken($slug, $id)) {
            return ['success' => false, 'error' => 'Dieser Kategorie-Slug ist bereits vergeben.'];
        }

        if ($parentId > 0 && !$this->categoryExists($parentId)) {
            return ['success' => false, 'error' => 'Die gewählte Elternkategorie existiert nicht.'];
        }

        if ($id > 0 && $parentId === $id) {
            return ['success' => false, 'error' => 'Eine Kategorie kann nicht ihr eigenes Elternteil sein.'];
        }

        if ($id > 0 && $parentId > 0 && $this->isCategoryDescendant($parentId, $id)) {
            return ['success' => false, 'error' => 'Die gewählte Elternkategorie liegt bereits unterhalb dieser Kategorie.'];
        }

        if (!empty($normalizedDomains['errors'])) {
            return ['success' => false, 'error' => (string) $normalizedDomains['errors'][0]];
        }

        $domains = $normalizedDomains['domains'] ?? [];
        if ($domains !== [] && $parentId > 0) {
            return ['success' => false, 'error' => 'Zusatzdomains können nur Hauptkategorien zugeordnet werden.'];
        }

        foreach ($domains as $domain) {
            if ($this->categoryDomainExists((string) $domain, $id > 0 ? $id : null)) {
                return ['success' => false, 'error' => 'Die Zusatzdomain „' . $domain . '“ ist bereits einer anderen Kategorie zugeordnet.'];
            }

            if ($this->hubDomainExists((string) $domain)) {
                return ['success' => false, 'error' => 'Die Zusatzdomain „' . $domain . '“ ist bereits einer Hub-Site zugeordnet.'];
            }
        }

        $domainsJson = json_encode(array_values($domains), JSON_UNESCAPED_UNICODE);
        if (!is_string($domainsJson) || $domainsJson === '') {
            $domainsJson = '[]';
        }

        try {
            if ($id > 0) {
                $this->db->execute(
                    "UPDATE {$this->prefix}post_categories SET name = ?, slug = ?, parent_id = ?, alias_domains_json = ? WHERE id = ?",
                    [$name, $slug, $parentId > 0 ? $parentId : null, $domainsJson, $id]
                );
                return ['success' => true, 'message' => 'Kategorie aktualisiert.'];
            } else {
                $this->db->execute(
                    "INSERT INTO {$this->prefix}post_categories (name, slug, parent_id, alias_domains_json) VALUES (?, ?, ?, ?)",
                    [$name, $slug, $parentId > 0 ? $parentId : null, $domainsJson]
                );
                return ['success' => true, 'message' => 'Kategorie erstellt.'];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Speichern der Kategorie.'];
        }
    }

    /**
     * Kategorie löschen
     */
    public function deleteCategory(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige Kategorie.'];
        }

        $assignedPostCount = $this->countAssignedPostsForCategory($id);
        $replacementId = (int) ($_POST['replacement_category_id'] ?? 0);

        if ($assignedPostCount > 0) {
            if ($replacementId <= 0) {
                return ['success' => false, 'error' => 'Bitte eine neue Kategorie für die betroffenen Beiträge auswählen.'];
            }

            if ($replacementId === $id) {
                return ['success' => false, 'error' => 'Die Ersatzkategorie darf nicht identisch mit der zu löschenden Kategorie sein.'];
            }

            if (!$this->categoryExists($replacementId)) {
                return ['success' => false, 'error' => 'Die gewählte Ersatzkategorie existiert nicht mehr.'];
            }
        }

        try {
            if ($assignedPostCount > 0 && $replacementId > 0) {
                $this->reassignPostsFromDeletedCategory($id, $replacementId);
            } else {
                $this->db->execute("DELETE FROM {$this->prefix}post_category_rel WHERE category_id = ?", [$id]);
                $this->db->execute("UPDATE {$this->prefix}posts SET category_id = NULL WHERE category_id = ?", [$id]);
            }

            $this->db->execute("UPDATE {$this->prefix}post_categories SET parent_id = NULL WHERE parent_id = ?", [$id]);
            $this->db->execute("DELETE FROM {$this->prefix}post_categories WHERE id = ?", [$id]);

            return ['success' => true, 'message' => 'Kategorie gelöscht.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Löschen der Kategorie.'];
        }
    }

    private function countAssignedPostsForCategory(int $categoryId): int
    {
        if ($categoryId <= 0) {
            return 0;
        }

        return (int) ($this->db->get_var(
            "SELECT COUNT(DISTINCT p.id)
             FROM {$this->prefix}posts p
             LEFT JOIN {$this->prefix}post_category_rel pcr ON pcr.post_id = p.id
             WHERE p.category_id = ? OR pcr.category_id = ?",
            [$categoryId, $categoryId]
        ) ?: 0);
    }

    private function reassignPostsFromDeletedCategory(int $deletedCategoryId, int $replacementCategoryId): void
    {
        $postRows = $this->db->get_results(
            "SELECT DISTINCT p.id, p.category_id
             FROM {$this->prefix}posts p
             LEFT JOIN {$this->prefix}post_category_rel pcr ON pcr.post_id = p.id
             WHERE p.category_id = ? OR pcr.category_id = ?",
            [$deletedCategoryId, $deletedCategoryId]
        ) ?: [];

        foreach ($postRows as $row) {
            $postId = (int) ($row->id ?? 0);
            if ($postId <= 0) {
                continue;
            }

            $categoryIds = $this->getPostCategoryIds($postId);
            $categoryIds = array_values(array_filter(
                $categoryIds,
                static fn(int $categoryId): bool => $categoryId > 0 && $categoryId !== $deletedCategoryId
            ));

            if (!in_array($replacementCategoryId, $categoryIds, true)) {
                $categoryIds[] = $replacementCategoryId;
            }

            $newPrimaryCategoryId = (int) ($row->category_id ?? 0) === $deletedCategoryId
                ? $replacementCategoryId
                : ((int) ($row->category_id ?? 0) > 0 ? (int) $row->category_id : $replacementCategoryId);

            if (!in_array($newPrimaryCategoryId, $categoryIds, true)) {
                array_unshift($categoryIds, $newPrimaryCategoryId);
            }

            $this->db->execute(
                "UPDATE {$this->prefix}posts SET category_id = ?, updated_at = NOW() WHERE id = ?",
                [$newPrimaryCategoryId, $postId]
            );

            $this->syncPostCategories($postId, $categoryIds);
        }

        $this->db->execute("DELETE FROM {$this->prefix}post_category_rel WHERE category_id = ?", [$deletedCategoryId]);
    }

    /**
     * Tag speichern.
     */
    public function saveTag(array $post): array
    {
        $id = (int) ($post['tag_id'] ?? 0);
        $name = trim((string) ($post['tag_name'] ?? ''));
        $slug = trim((string) ($post['tag_slug'] ?? ''));

        if ($name === '') {
            return ['success' => false, 'error' => 'Tag-Name darf nicht leer sein.'];
        }

        $slug = $this->normalizeSlug($slug !== '' ? $slug : $this->generateSlug($name));
        if ($slug === '') {
            return ['success' => false, 'error' => 'Bitte einen gültigen Tag-Slug angeben.'];
        }

        try {
            if ($id > 0) {
                $this->db->execute(
                    "UPDATE {$this->prefix}post_tags SET name = ?, slug = ? WHERE id = ?",
                    [$name, $slug, $id]
                );

                return ['success' => true, 'message' => 'Tag aktualisiert.'];
            }

            $this->db->execute(
                "INSERT INTO {$this->prefix}post_tags (name, slug) VALUES (?, ?)",
                [$name, $slug]
            );

            return ['success' => true, 'message' => 'Tag erstellt.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Speichern des Tags.'];
        }
    }

    /**
     * Tag löschen.
     */
    public function deleteTag(int $id): array
    {
        try {
            $this->db->execute("DELETE FROM {$this->prefix}post_tag_rel WHERE tag_id = ?", [$id]);
            $this->db->execute("DELETE FROM {$this->prefix}post_tags WHERE id = ?", [$id]);

            return ['success' => true, 'message' => 'Tag gelöscht.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Löschen des Tags.'];
        }
    }

    private function sanitizeAuthorDisplayName(string $value): string
    {
        $value = trim(strip_tags($value));

        if ($value === '') {
            return '';
        }

        return function_exists('mb_substr')
            ? mb_substr($value, 0, 150)
            : substr($value, 0, 150);
    }

    /**
     * Slug generieren
     */
    private function generateSlug(string $text): string
    {
        $slug = mb_strtolower($text);
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug) ?? $slug;
        $slug = preg_replace('/-+/', '-', $slug) ?? $slug;
        return trim($slug, '-');
    }

    private function normalizeSlug(string $slug): string
    {
        $slug = mb_strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug) ?? $slug;
        $slug = preg_replace('/-+/', '-', $slug) ?? $slug;
        return trim($slug, '-');
    }

    private function isSlugTaken(string $slug, int $ignoreId = 0): bool
    {
        $params = [$slug];
        $sql = "SELECT COUNT(*) FROM {$this->prefix}posts WHERE slug = ?";

        if ($ignoreId > 0) {
            $sql .= " AND id != ?";
            $params[] = $ignoreId;
        }

        return (int)$this->db->get_var($sql, $params) > 0;
    }

    private function isLocalizedSlugTaken(string $slugEn, int $ignoreId = 0): bool
    {
        $slugEn = trim($slugEn);
        if ($slugEn === '') {
            return false;
        }

        $params = [$slugEn, $slugEn];
        $sql = "SELECT COUNT(*) FROM {$this->prefix}posts WHERE (slug_en = ? OR slug = ?)";

        if ($ignoreId > 0) {
            $sql .= " AND id != ?";
            $params[] = $ignoreId;
        }

        return (int)$this->db->get_var($sql, $params) > 0;
    }

    private function isCategorySlugTaken(string $slug, int $ignoreId = 0): bool
    {
        $params = [$slug];
        $sql = "SELECT COUNT(*) FROM {$this->prefix}post_categories WHERE slug = ?";

        if ($ignoreId > 0) {
            $sql .= " AND id != ?";
            $params[] = $ignoreId;
        }

        return (int) $this->db->get_var($sql, $params) > 0;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function buildOrderedCategoryOptions(array $rows): array
    {
        return array_map(static function (array $row): array {
            $row['option_label'] = (string) ($row['option_label'] ?? $row['name'] ?? '');
            return $row;
        }, $this->buildCategoryTreeRows($rows, false));
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function buildAdminCategoryRows(array $rows): array
    {
        return $this->buildCategoryTreeRows($rows, true);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function buildCategoryTreeRows(array $rows, bool $includeAdminMeta): array
    {
        $byId = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $row['id'] = $id;
            $row['parent_id'] = (int) ($row['parent_id'] ?? 0);
            $row['sort_order'] = (int) ($row['sort_order'] ?? 0);
            $row['post_count_direct'] = (int) ($row['post_count'] ?? 0);
            $row['domains'] = $this->decodeCategoryDomains((string) ($row['alias_domains_json'] ?? ''));
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
        $walker = function (int $parentId, int $depth) use (&$walker, &$flat, $byParent, $byId, $includeAdminMeta): int {
            $branchTotal = 0;

            foreach ($byParent[$parentId] ?? [] as $categoryId) {
                if (!isset($byId[$categoryId])) {
                    continue;
                }

                $row = $byId[$categoryId];
                $row['depth'] = $depth;
                $row['option_label'] = str_repeat('— ', $depth) . (string) ($row['name'] ?? '');
                $row['is_main_category'] = $depth === 0;
                $row['parent_name'] = '';

                if ((int) ($row['parent_id'] ?? 0) > 0 && isset($byId[(int) $row['parent_id']])) {
                    $row['parent_name'] = (string) ($byId[(int) $row['parent_id']]['name'] ?? '');
                }

                $index = count($flat);
                $flat[] = $row;
                $childrenTotal = $walker($categoryId, $depth + 1);
                $row['post_count_total'] = (int) ($row['post_count_direct'] ?? 0) + $childrenTotal;

                if (!$includeAdminMeta) {
                    unset($row['domains']);
                    unset($row['post_count_direct']);
                    unset($row['post_count_total']);
                    unset($row['parent_name']);
                    unset($row['is_main_category']);
                }

                $flat[$index] = $row;
                $branchTotal += (int) ($row['post_count_total'] ?? ($row['post_count_direct'] ?? 0));
            }

            return $branchTotal;
        };

        $walker(0, 0);

        return $flat;
    }

    /**
     * @return array{domains: array<int,string>, errors: array<int,string>}
     */
    private function normalizeCategoryDomains(string $rawDomains): array
    {
        $entries = preg_split('/[\r\n,;]+/', $rawDomains) ?: [];
        $domains = [];
        $errors = [];

        foreach ($entries as $entry) {
            $normalizedHost = $this->normalizeDomainHost($entry);
            if ($normalizedHost === '') {
                if (trim($entry) !== '') {
                    $errors[] = 'Die Zusatzdomain „' . trim($entry) . '“ ist ungültig.';
                }
                continue;
            }

            if ($this->isMainDomainHost($normalizedHost)) {
                $errors[] = 'Die Hauptdomain darf nicht als Kategorie-Zusatzdomain verwendet werden.';
                continue;
            }

            $domains[] = $normalizedHost;
        }

        return [
            'domains' => array_values(array_unique($domains)),
            'errors' => array_values(array_unique($errors)),
        ];
    }

    /**
     * @return array<int,string>
     */
    private function decodeCategoryDomains(string $json): array
    {
        $decoded = \CMS\Json::decodeArray($json !== '' ? $json : '[]', []);
        if (!is_array($decoded)) {
            return [];
        }

        $domains = [];
        foreach ($decoded as $candidate) {
            $host = $this->normalizeDomainHost((string) $candidate);
            if ($host !== '') {
                $domains[] = $host;
            }
        }

        return array_values(array_unique($domains));
    }

    private function categoryDomainExists(string $domain, ?int $excludeId = null): bool
    {
        $rows = $this->db->get_results(
            "SELECT id, alias_domains_json FROM {$this->prefix}post_categories",
            []
        ) ?: [];

        foreach ($rows as $row) {
            $rowId = (int) ($row->id ?? 0);
            if ($excludeId !== null && $rowId === $excludeId) {
                continue;
            }

            foreach ($this->decodeCategoryDomains((string) ($row->alias_domains_json ?? '')) as $candidate) {
                if ($candidate === $domain) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hubDomainExists(string $domain): bool
    {
        $rows = $this->db->get_results(
            "SELECT settings_json
             FROM {$this->prefix}site_tables
             WHERE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.content_mode')), 'table') = 'hub'",
            []
        ) ?: [];

        foreach ($rows as $row) {
            $settings = \CMS\Json::decodeArray($row->settings_json ?? null, []);
            $domains = is_array($settings['hub_domains'] ?? null) ? $settings['hub_domains'] : [];
            foreach ($domains as $candidate) {
                if ($this->normalizeDomainHost((string) $candidate) === $domain) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isMainDomainHost(string $host): bool
    {
        $siteHost = $this->normalizeDomainHost((string) (parse_url((string) SITE_URL, PHP_URL_HOST) ?? ''));
        return $siteHost !== '' && $siteHost === $host;
    }

    private function normalizeDomainHost(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $candidate = preg_match('#^https?://#i', $value) === 1 ? $value : 'https://' . ltrim($value, '/');
        $parts = parse_url($candidate);
        if ($parts === false) {
            return '';
        }

        $host = strtolower(trim((string) ($parts['host'] ?? ''), '.'));
        if ($host === '') {
            return '';
        }

        if (isset($parts['path']) && $parts['path'] !== '' && $parts['path'] !== '/') {
            return '';
        }

        if (isset($parts['query']) || isset($parts['fragment'])) {
            return '';
        }

        if (!preg_match('/^(?=.{1,253}$)(?:xn--)?[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.(?:xn--)?[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+$/i', $host)) {
            return '';
        }

        return $host;
    }

    private function isCategoryDescendant(int $candidateParentId, int $categoryId): bool
    {
        if ($candidateParentId <= 0 || $categoryId <= 0) {
            return false;
        }

        $currentParentId = $candidateParentId;
        $guard = 0;
        while ($currentParentId > 0 && $guard < 50) {
            if ($currentParentId === $categoryId) {
                return true;
            }

            $currentParentId = (int) ($this->db->get_var(
                "SELECT parent_id FROM {$this->prefix}post_categories WHERE id = ? LIMIT 1",
                [$currentParentId]
            ) ?: 0);
            $guard++;
        }

        return false;
    }

    /**
     * @return array<int, array{name:string, slug:string}>
     */
    private function normalizeTagInput(string|array $rawTags): array
    {
        $tagValues = is_array($rawTags)
            ? $rawTags
            : (preg_split('/[,\n]+/', $rawTags) ?: []);
        $normalized = [];
        $seen = [];

        foreach ($tagValues as $tagValue) {
            $name = trim((string)$tagValue);
            if ($name === '') {
                continue;
            }

            $slug = $this->normalizeSlug($this->generateSlug($name));
            if ($slug === '' || isset($seen[$slug])) {
                continue;
            }

            $seen[$slug] = true;
            $normalized[] = [
                'name' => mb_substr($name, 0, 120),
                'slug' => mb_substr($slug, 0, 160),
            ];
        }

        return $normalized;
    }

    private function syncPostTags(int $postId, string|array $rawTags): void
    {
        if ($postId <= 0) {
            return;
        }

        $tags = $this->normalizeTagInput($rawTags);

        $this->db->execute(
            "DELETE FROM {$this->prefix}post_tag_rel WHERE post_id = ?",
            [$postId]
        );

        if ($tags === []) {
            return;
        }

        foreach ($tags as $tag) {
            $existingTagId = (int)($this->db->get_var(
                "SELECT id FROM {$this->prefix}post_tags WHERE slug = ? LIMIT 1",
                [$tag['slug']]
            ) ?: 0);

            if ($existingTagId <= 0) {
                $this->db->execute(
                    "INSERT INTO {$this->prefix}post_tags (name, slug) VALUES (?, ?)",
                    [$tag['name'], $tag['slug']]
                );
                $existingTagId = (int)$this->db->lastInsertId();
            } else {
                $this->db->execute(
                    "UPDATE {$this->prefix}post_tags SET name = ? WHERE id = ?",
                    [$tag['name'], $existingTagId]
                );
            }

            if ($existingTagId > 0) {
                $this->db->execute(
                    "INSERT IGNORE INTO {$this->prefix}post_tag_rel (post_id, tag_id) VALUES (?, ?)",
                    [$postId, $existingTagId]
                );
            }
        }
    }

    private function serializeTagsForLegacyColumn(string|array $rawTags): string
    {
        $tags = $this->normalizeTagInput($rawTags);

        if ($tags === []) {
            return '';
        }

        return implode(', ', array_map(
            static fn(array $tag): string => (string) ($tag['name'] ?? ''),
            $tags
        ));
    }

    /**
     * @param mixed $additionalCategoryIds
     * @return array<int,int>
     */
    private function normalizeSelectedCategoryIds(int $primaryCategoryId, mixed $additionalCategoryIds): array
    {
        $requestedIds = [];
        if ($primaryCategoryId > 0) {
            $requestedIds[] = $primaryCategoryId;
        }

        foreach ((array) $additionalCategoryIds as $categoryId) {
            $normalizedId = (int) $categoryId;
            if ($normalizedId > 0) {
                $requestedIds[] = $normalizedId;
            }
        }

        if ($requestedIds === []) {
            return [];
        }

        return $this->getExistingCategoryIds(array_values(array_unique($requestedIds)));
    }

    /**
     * @param array<int,int> $categoryIds
     * @return array<int,int>
     */
    private function getExistingCategoryIds(array $categoryIds): array
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds), static fn(int $id): bool => $id > 0)));
        if ($categoryIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $rows = $this->db->get_results(
            "SELECT id FROM {$this->prefix}post_categories WHERE id IN ({$placeholders})",
            $categoryIds
        ) ?: [];

        $existing = [];
        foreach ($rows as $row) {
            $existing[(int) ($row->id ?? 0)] = true;
        }

        return array_values(array_filter($categoryIds, static fn(int $id): bool => isset($existing[$id])));
    }

    /**
     * @return array<int,int>
     */
    private function getPostCategoryIds(int $postId): array
    {
        if ($postId <= 0) {
            return [];
        }

        $tableExists = (bool) $this->db->get_var("SHOW TABLES LIKE '{$this->prefix}post_category_rel'");
        $categoryIds = [];

        if ($tableExists) {
            $rows = $this->db->get_results(
                "SELECT category_id FROM {$this->prefix}post_category_rel WHERE post_id = ? ORDER BY id ASC",
                [$postId]
            ) ?: [];

            foreach ($rows as $row) {
                $categoryId = (int) ($row->category_id ?? 0);
                if ($categoryId > 0) {
                    $categoryIds[] = $categoryId;
                }
            }
        }

        $primaryCategoryId = (int) ($this->db->get_var(
            "SELECT category_id FROM {$this->prefix}posts WHERE id = ? LIMIT 1",
            [$postId]
        ) ?: 0);

        if ($primaryCategoryId > 0) {
            array_unshift($categoryIds, $primaryCategoryId);
        }

        return array_values(array_unique(array_filter($categoryIds, static fn(int $id): bool => $id > 0)));
    }

    /**
     * @param array<int,int> $categoryIds
     */
    private function syncPostCategories(int $postId, array $categoryIds): void
    {
        if ($postId <= 0) {
            return;
        }

        $tableExists = (bool) $this->db->get_var("SHOW TABLES LIKE '{$this->prefix}post_category_rel'");
        if (!$tableExists) {
            return;
        }

        $categoryIds = $this->getExistingCategoryIds($categoryIds);
        $this->db->execute(
            "DELETE FROM {$this->prefix}post_category_rel WHERE post_id = ?",
            [$postId]
        );

        foreach ($categoryIds as $categoryId) {
            $this->db->execute(
                "INSERT IGNORE INTO {$this->prefix}post_category_rel (post_id, category_id) VALUES (?, ?)",
                [$postId, $categoryId]
            );
        }
    }

    /**
     * @param array<string, string> $postDates
     */
    private function createSlugRedirectIfNeeded(string $oldSlug, string $newSlug, array $postDates = []): void
    {
        $oldSlug = trim($oldSlug);
        $newSlug = trim($newSlug);

        if ($oldSlug === '' || $newSlug === '' || $oldSlug === $newSlug) {
            return;
        }

        $permalinkService = PermalinkService::getInstance();
        $publishedAt = (string)($postDates['published_at'] ?? '');
        $createdAt = (string)($postDates['created_at'] ?? '');
        $oldPath = $permalinkService->buildPostPathFromValues($oldSlug, $publishedAt, $createdAt);
        $newPath = $permalinkService->buildPostPathFromValues($newSlug, $publishedAt, $createdAt);

        RedirectService::getInstance()->createAutomaticRedirect(
            $oldPath,
            $newPath,
            'Automatisch bei Beitrags-Slug-Änderung angelegt'
        );

        $legacyOldPath = $permalinkService->getLegacyPostPath($oldSlug);
        if ($legacyOldPath !== $oldPath) {
            RedirectService::getInstance()->createAutomaticRedirect(
                $legacyOldPath,
                $newPath,
                'Legacy-Weiterleitung bei Beitrags-Slug-Änderung angelegt'
            );
        }

        foreach (ContentLocalizationService::getInstance()->getContentLocales() as $locale) {
            if ($locale === 'de') {
                continue;
            }

            $localizedOldPath = $permalinkService->buildPostPathFromValues($oldSlug, $publishedAt, $createdAt, $locale);
            $localizedNewPath = $permalinkService->buildPostPathFromValues($newSlug, $publishedAt, $createdAt, $locale);
            RedirectService::getInstance()->createAutomaticRedirect(
                $localizedOldPath,
                $localizedNewPath,
                'Automatisch bei lokalisiertem Beitrags-Slug angelegt'
            );

            $localizedLegacyOldPath = $permalinkService->getLegacyPostPath($oldSlug, $locale);
            if ($localizedLegacyOldPath !== $localizedOldPath) {
                RedirectService::getInstance()->createAutomaticRedirect(
                    $localizedLegacyOldPath,
                    $localizedNewPath,
                    'Legacy-Weiterleitung bei lokalisiertem Beitrags-Slug angelegt'
                );
            }
        }
    }
}
