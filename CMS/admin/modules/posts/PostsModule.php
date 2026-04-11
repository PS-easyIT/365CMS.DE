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

require_once __DIR__ . '/PostsCategoryViewModelBuilder.php';

use CMS\AuditLogger;
use CMS\Database;
use CMS\Hooks;
use CMS\Logger;
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
    private PostsCategoryViewModelBuilder $categoryViewModelBuilder;

    /**
     * @var array<int,array{root: array{slug:string,name:string,sort_order:int}, children: array<string,string>}>
     */
    private const DEFAULT_CATEGORY_TREES = [
        [
            'root' => [
                'slug' => 'microsoft-365',
                'name' => 'Microsoft 365',
                'sort_order' => 10,
            ],
            'children' => [
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
            ],
        ],
        [
            'root' => [
                'slug' => 'technik-it',
                'name' => 'Technik & IT',
                'sort_order' => 200,
            ],
            'children' => [
                'it-infrastruktur' => 'IT-Infrastruktur',
                'cyber-security' => 'Cyber Security',
                'netzwerk-systeme' => 'Netzwerk & Systeme',
                'cloud-devops' => 'Cloud & DevOps',
                'softwareentwicklung' => 'Softwareentwicklung',
                'ki-automatisierung' => 'KI & Automatisierung',
                'hardware-devices' => 'Hardware & Devices',
                'open-source-tools' => 'Open Source & Tools',
            ],
        ],
    ];

    /** @var string[] */
    private const ALLOWED_LIST_STATUSES = ['published', 'scheduled', 'draft', 'private'];

    /** @var string[] */
    private const ALLOWED_BULK_ACTIONS = [
        'delete',
        'publish',
        'draft',
        'set_category',
        'clear_category',
        'set_author_display_name',
        'clear_author_display_name',
    ];

    public function __construct()
    {
        $this->db     = Database::instance();
        $this->prefix = $this->db->getPrefix();
        $this->categoryViewModelBuilder = new PostsCategoryViewModelBuilder();
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
            'replacement_category_id' => "ALTER TABLE {$this->prefix}post_categories ADD COLUMN replacement_category_id INT UNSIGNED DEFAULT NULL AFTER alias_domains_json",
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
            foreach (self::DEFAULT_CATEGORY_TREES as $tree) {
                $root = (array) ($tree['root'] ?? []);
                $rootSlug = (string) ($root['slug'] ?? '');
                $rootName = (string) ($root['name'] ?? '');
                $rootSortOrder = (int) ($root['sort_order'] ?? 0);

                if ($rootSlug === '' || $rootName === '') {
                    continue;
                }

                $rootId = $this->getCategoryIdBySlug($rootSlug);

                if ($rootId <= 0) {
                    $this->db->execute(
                        "INSERT INTO {$this->prefix}post_categories (name, slug, parent_id, sort_order, alias_domains_json) VALUES (?, ?, NULL, ?, ?)",
                        [$rootName, $rootSlug, $rootSortOrder, '[]']
                    );
                    $rootId = (int) $this->db->lastInsertId();
                } else {
                    $this->db->execute(
                        "UPDATE {$this->prefix}post_categories SET name = ?, parent_id = NULL, sort_order = ? WHERE id = ?",
                        [$rootName, $rootSortOrder, $rootId]
                    );
                }

                $sortOrder = $rootSortOrder + 10;
                foreach ((array) ($tree['children'] ?? []) as $slug => $name) {
                    $categoryId = $this->getCategoryIdBySlug((string) $slug);

                    if ($categoryId <= 0) {
                        $this->db->execute(
                            "INSERT INTO {$this->prefix}post_categories (name, slug, parent_id, sort_order, alias_domains_json) VALUES (?, ?, ?, ?, ?)",
                            [(string) $name, (string) $slug, $rootId, $sortOrder, '[]']
                        );
                    } else {
                        $this->db->execute(
                            "UPDATE {$this->prefix}post_categories SET name = ?, parent_id = ?, sort_order = ? WHERE id = ?",
                            [(string) $name, $rootId, $sortOrder, $categoryId]
                        );
                    }

                    $sortOrder += 10;
                }
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
        $currentDateTime = date('Y-m-d H:i:s');
        $total     = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}posts");
        $published = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}posts p WHERE " . cms_post_publication_where('p'));
        $scheduled = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}posts WHERE status = 'published' AND published_at IS NOT NULL AND published_at > ?", [$currentDateTime]);
        $drafts    = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}posts WHERE status = 'draft'");
        $private   = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}posts WHERE status = 'private'");

        $statusFilter   = $this->normalizeListStatus((string)($_GET['status'] ?? ''));
        $categoryFilter = $this->normalizeExistingCategoryId((int)($_GET['category'] ?? 0));
        $search         = $this->sanitizeSearchTerm((string)($_GET['q'] ?? ''));

        $where  = [];
        $params = [];

        if ($statusFilter !== '') {
            if ($statusFilter === 'published') {
                $where[] = cms_post_publication_where('p');
            } elseif ($statusFilter === 'scheduled') {
                $where[] = "p.status = 'published' AND p.published_at IS NOT NULL AND p.published_at > ?";
                $params[] = $currentDateTime;
            } elseif ($statusFilter === 'draft') {
                $where[]  = 'p.status = ?';
                $params[] = $statusFilter;
            } elseif ($statusFilter === 'private') {
                $where[]  = 'p.status = ?';
                $params[] = $statusFilter;
            }
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

        $categories = $this->categoryViewModelBuilder->buildOrderedCategoryOptions(array_map(fn($c) => (array) $c, $categories));

        return [
            'posts'      => array_map(fn($p) => (array)$p, $posts),
            'categories' => $categories,
            'counts'     => compact('total', 'published', 'scheduled', 'drafts', 'private'),
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
        if ($postData !== null) {
            $postTags = $this->db->get_results(
                "SELECT t.id, t.name, t.slug
                 FROM {$this->prefix}post_tags t
                 INNER JOIN {$this->prefix}post_tag_rel ptr ON ptr.tag_id = t.id
                 WHERE ptr.post_id = ?
                 ORDER BY t.name ASC",
                [(int)($postData['id'] ?? 0)]
            ) ?: [];
        }

        return [
            'post'       => $postData,
            'isNew'      => $post === null,
            'categories' => $this->categoryViewModelBuilder->buildOrderedCategoryOptions(array_map(fn($c) => (array)$c, $categories)),
            'assignedCategoryIds' => $postData !== null ? $this->getPostCategoryIds((int) ($postData['id'] ?? 0)) : [],
            'tags'       => array_map(fn($t) => (array)$t, $tags),
            'postTags'   => array_map(fn($t) => (array)$t, $postTags),
            'seoMeta'    => $id !== null ? SEOService::getInstance()->getContentMeta('post', $id) : SEOService::getInstance()->getContentMeta('post', 0),
        ];
    }

    /**
     * Daten für die Admin-Ansicht der Beitrags-Kategorien.
     */
    public function getCategoryAdminData(): array
    {
        $categories = $this->db->get_results(
            "SELECT c.id, c.name, c.slug, c.parent_id, c.sort_order, c.alias_domains_json, c.replacement_category_id,
                    (
                        SELECT COUNT(DISTINCT p2.id)
                        FROM {$this->prefix}posts p2
                        LEFT JOIN {$this->prefix}post_category_rel pcr2 ON pcr2.post_id = p2.id
                        WHERE p2.category_id = c.id OR pcr2.category_id = c.id
                    ) AS assigned_post_count
             FROM {$this->prefix}post_categories c
             LEFT JOIN {$this->prefix}posts p ON p.category_id = c.id
             GROUP BY c.id, c.name, c.slug, c.parent_id, c.sort_order, c.alias_domains_json, c.replacement_category_id
             ORDER BY c.sort_order ASC, c.name ASC"
        ) ?: [];

        $orderedCategories = $this->categoryViewModelBuilder->buildAdminCategoryRows(array_map(fn($category) => (array) $category, $categories));

        return [
            'categories' => $orderedCategories,
            'categoryOptions' => $this->categoryViewModelBuilder->buildOrderedCategoryOptions(array_map(fn($category) => (array) $category, $categories)),
            'counts' => [
                'total' => count($orderedCategories),
                'assigned_posts' => array_sum(array_map(static fn(array $category): int => (int) ($category['post_count_direct'] ?? 0), $orderedCategories)),
            ],
        ];
    }

    /**
     * Daten für die Admin-Ansicht der Beitrags-Tags.
     */
    public function getTagAdminData(int $editTagId = 0): array
    {
        $tags = $this->db->get_results(
            "SELECT t.id, t.name, t.slug, COUNT(ptr.post_id) AS post_count
             FROM {$this->prefix}post_tags t
             LEFT JOIN {$this->prefix}post_tag_rel ptr ON ptr.tag_id = t.id
             GROUP BY t.id, t.name, t.slug
             ORDER BY t.name ASC"
        ) ?: [];

        $tagRows = array_map(fn($tag) => (array) $tag, $tags);
        $editTag = null;

        if ($editTagId > 0) {
            foreach ($tagRows as $tagRow) {
                if ((int) ($tagRow['id'] ?? 0) === $editTagId) {
                    $editTag = $tagRow;
                    break;
                }
            }
        }

        return [
            'tags' => $tagRows,
            'tagOptions' => $tagRows,
            'editTag' => $editTag,
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
        $title      = $this->sanitizePlainText((string)($post['title'] ?? ''), 255);
        $slug       = trim((string)($post['slug'] ?? ''));
        $slugEn     = trim((string)($post['slug_en'] ?? ''));
        $defaultStatus = function_exists('get_option') ? (string)get_option('setting_post_default_status', 'draft') : 'draft';
        if (!in_array($defaultStatus, ['published', 'draft', 'private'], true)) {
            $defaultStatus = 'draft';
        }

        $status     = in_array((string)($post['status'] ?? ''), ['published', 'draft', 'private'], true) ? (string)$post['status'] : $defaultStatus;
        $content    = $post['content'] ?? '';
        $titleEn    = $this->sanitizePlainText((string)($post['title_en'] ?? ''), 255);
        $contentEn  = $post['content_en'] ?? '';
        $excerpt    = $this->sanitizePlainText((string)($post['excerpt'] ?? ''), 2000);
        $excerptEn  = $this->sanitizePlainText((string)($post['excerpt_en'] ?? ''), 2000);
        $categoryId = (int)($post['category_id'] ?? 0);
        $assignedCategoryIds = $this->normalizeSelectedCategoryIds($categoryId, $post['additional_category_ids'] ?? []);
        if ($categoryId <= 0 && $assignedCategoryIds !== []) {
            $categoryId = (int) ($assignedCategoryIds[0] ?? 0);
        }
        $featuredImage = $this->sanitizeMediaReference((string)($post['featured_image'] ?? ''));
        $featuredImageTempPath = $this->sanitizeMediaReference((string)($post['featured_image_temp_path'] ?? ''));
        $publishDateRaw = trim((string)($post['publish_date'] ?? ''));
        $publishTimeRaw = trim((string)($post['publish_time'] ?? ''));
        $metaTitle  = $this->sanitizePlainText((string)($post['meta_title'] ?? ''), 255);
        $metaDesc   = $this->sanitizePlainText((string)($post['meta_description'] ?? ''), 2000);
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

        $publishedAtInput = $this->normalizePublishedAtInput($publishDateRaw, $publishTimeRaw);
        if ($publishedAtInput['error'] !== null) {
            return ['success' => false, 'error' => (string) $publishedAtInput['error']];
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
            'published_at' => $publishedAtInput['value'],
        ];

        $filteredPayload = Hooks::applyFilters('cms_prepare_post_save_payload', $savePayload, $post, $id, $userId);
        if (is_array($filteredPayload)) {
            $savePayload = array_merge($savePayload, $filteredPayload);
        }

        try {
            if ($id > 0) {
                $existing = $this->db->get_row("SELECT slug, status, published_at, created_at FROM {$this->prefix}posts WHERE id = ? LIMIT 1", [$id]);
                $resolvedPublishedAt = $this->resolvePublishedAtValue((string) $savePayload['status'], $savePayload['published_at'], $existing);
                $this->db->execute(
                    "UPDATE {$this->prefix}posts 
                     SET title = ?, title_en = ?, slug = ?, slug_en = ?, content = ?, content_en = ?, excerpt = ?, excerpt_en = ?, status = ?,
                         category_id = ?, featured_image = ?, tags = ?,
                         meta_title = ?, meta_description = ?, author_display_name = ?, published_at = ?,
                         updated_at = NOW()
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
                        $resolvedPublishedAt,
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
                $resolvedPublishedAt = $this->resolvePublishedAtValue((string) $savePayload['status'], $savePayload['published_at'], null);
                $this->db->execute(
                    "INSERT INTO {$this->prefix}posts
                     (title, title_en, slug, slug_en, content, content_en, excerpt, excerpt_en, status, category_id, featured_image, tags, meta_title, meta_description, author_id, author_display_name, published_at, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
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
                        $resolvedPublishedAt,
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
            return $this->failResult(
                'posts.save.failed',
                'Beitrag konnte nicht gespeichert werden.',
                $e,
                ['post_id' => $id, 'status' => $status, 'category_id' => $categoryId, 'user_id' => $userId]
            );
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
                return $this->failResult(
                    'posts.delete.failed',
                    'Beitrag konnte nicht gelöscht werden.',
                    null,
                    ['post_id' => $id, 'db_last_error' => trim((string)$this->db->last_error)]
                );
            }

            Hooks::doAction('post_deleted', $id);

            return ['success' => true, 'message' => 'Beitrag gelöscht.'];
        } catch (\Throwable $e) {
            return $this->failResult('posts.delete.failed', 'Beitrag konnte nicht gelöscht werden.', $e, ['post_id' => $id]);
        }
    }

    /**
     * Bulk-Aktion
     */
    public function bulkAction(string $action, array $ids, array $payload = []): array
    {
        $action = $this->normalizeBulkAction($action);

        if (empty($ids)) {
            return ['success' => false, 'error' => 'Keine Einträge ausgewählt.'];
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $id): bool => $id > 0)));
        if ($ids === []) {
            return ['success' => false, 'error' => 'Keine gültigen Beitrags-IDs ausgewählt.'];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        try {
            switch ($action) {
                case 'delete':
                    $this->db->execute("DELETE FROM {$this->prefix}posts WHERE id IN ({$placeholders})", $ids);

                    foreach ($ids as $postId) {
                        Hooks::doAction('post_deleted', (int) $postId);
                    }

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
            return $this->failResult(
                'posts.bulk.failed',
                'Bulk-Aktion für Beiträge fehlgeschlagen.',
                $e,
                ['bulk_action' => $action, 'post_ids' => $ids]
            );
        }
    }

    /**
     * Kategorie speichern
     */
    public function saveCategory(array $post): array
    {
        $id   = (int)($post['cat_id'] ?? 0);
        $name = $this->sanitizePlainText((string)($post['cat_name'] ?? ''), 255);
        $slug = $this->normalizeSlug((string)($post['cat_slug'] ?? ''));
        $parentId = (int) ($post['parent_id'] ?? 0);
        $replacementCategoryId = (int) ($post['replacement_category_id'] ?? 0);
        $normalizedDomains = $this->normalizeCategoryDomains((string) ($post['cat_domains'] ?? ''));

        if ($name === '') {
            return ['success' => false, 'error' => 'Kategoriename darf nicht leer sein.'];
        }
        if ($slug === '') {
            $slug = $this->normalizeSlug($this->generateSlug($name));
        }

        if ($slug === '') {
            return ['success' => false, 'error' => 'Bitte einen gültigen Kategorie-Slug angeben.'];
        }

        if ($id > 0 && !$this->categoryExists($id)) {
            return ['success' => false, 'error' => 'Die zu bearbeitende Kategorie existiert nicht mehr.'];
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

        if ($replacementCategoryId > 0) {
            if ($replacementCategoryId === $id) {
                return ['success' => false, 'error' => 'Die Ersatzkategorie darf nicht identisch mit der aktuellen Kategorie sein.'];
            }

            if (!$this->categoryExists($replacementCategoryId)) {
                return ['success' => false, 'error' => 'Die gewählte Ersatzkategorie existiert nicht.'];
            }
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
                    "UPDATE {$this->prefix}post_categories SET name = ?, slug = ?, parent_id = ?, alias_domains_json = ?, replacement_category_id = ? WHERE id = ?",
                    [$name, $slug, $parentId > 0 ? $parentId : null, $domainsJson, $replacementCategoryId > 0 ? $replacementCategoryId : null, $id]
                );
                return ['success' => true, 'message' => 'Kategorie aktualisiert.'];
            } else {
                $this->db->execute(
                    "INSERT INTO {$this->prefix}post_categories (name, slug, parent_id, alias_domains_json, replacement_category_id) VALUES (?, ?, ?, ?, ?)",
                    [$name, $slug, $parentId > 0 ? $parentId : null, $domainsJson, $replacementCategoryId > 0 ? $replacementCategoryId : null]
                );
                return ['success' => true, 'message' => 'Kategorie erstellt.'];
            }
        } catch (\Throwable $e) {
            return $this->failResult(
                'posts.category.save.failed',
                'Kategorie konnte nicht gespeichert werden.',
                $e,
                ['category_id' => $id, 'parent_id' => $parentId, 'replacement_category_id' => $replacementCategoryId]
            );
        }
    }

    /**
     * Kategorie löschen
     */
    public function deleteCategory(int $id, int $replacementId = 0): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige Kategorie.'];
        }

        if ($replacementId <= 0) {
            $replacementId = $this->getStoredReplacementCategoryId($id);
        }

        return $this->deleteCategoryResolved($id, $replacementId);
    }

    public function deleteCategoriesWithStoredReplacement(): array
    {
        $categories = $this->getCategoriesWithStoredReplacement();
        if ($categories === []) {
            return ['success' => false, 'error' => 'Es sind keine Kategorien mit hinterlegter Ersatzkategorie vorhanden.'];
        }

        $eligible = [];
        $skippedInvalid = [];

        foreach ($categories as $category) {
            $categoryId = (int) ($category['id'] ?? 0);
            $categoryName = trim((string) ($category['name'] ?? 'Kategorie'));
            $replacementId = (int) ($category['replacement_category_id'] ?? 0);

            if ($categoryId <= 0 || $replacementId <= 0 || $replacementId === $categoryId || !$this->categoryExists($replacementId)) {
                $skippedInvalid[] = $categoryName;
                continue;
            }

            $eligible[$categoryId] = [
                'id' => $categoryId,
                'name' => $categoryName,
                'replacement_category_id' => $replacementId,
            ];
        }

        if ($eligible === []) {
            return ['success' => false, 'error' => 'Keine der Kategorien mit hinterlegter Ersatzkategorie ist aktuell löschbar.'];
        }

        $orderedCategories = [];
        $cyclicCategories = [];
        $pending = $eligible;

        while ($pending !== []) {
            $targetedPendingIds = [];
            foreach ($pending as $pendingCategory) {
                $targetId = (int) ($pendingCategory['replacement_category_id'] ?? 0);
                if ($targetId > 0 && isset($pending[$targetId])) {
                    $targetedPendingIds[$targetId] = true;
                }
            }

            $progress = false;
            foreach ($pending as $categoryId => $pendingCategory) {
                if (isset($targetedPendingIds[$categoryId])) {
                    continue;
                }

                $orderedCategories[] = $pendingCategory;
                unset($pending[$categoryId]);
                $progress = true;
            }

            if (!$progress) {
                foreach ($pending as $pendingCategory) {
                    $cyclicCategories[] = trim((string) ($pendingCategory['name'] ?? 'Kategorie'));
                }
                break;
            }
        }

        $deletedNames = [];
        $failedNames = [];

        foreach ($orderedCategories as $category) {
            $categoryId = (int) ($category['id'] ?? 0);
            $categoryName = trim((string) ($category['name'] ?? 'Kategorie'));
            $replacementId = (int) ($category['replacement_category_id'] ?? 0);
            $result = $this->deleteCategoryResolved($categoryId, $replacementId);

            if (!empty($result['success'])) {
                $deletedNames[] = $categoryName;
                continue;
            }

            $failedNames[] = $categoryName;
        }

        if ($deletedNames === []) {
            $details = [];
            if ($cyclicCategories !== []) {
                $details[] = 'Zyklische Ersatzketten: ' . implode(', ', $cyclicCategories) . '.';
            }
            if ($skippedInvalid !== []) {
                $details[] = 'Ungültige Ersatzkategorien: ' . implode(', ', $skippedInvalid) . '.';
            }
            if ($failedNames !== []) {
                $details[] = 'Fehlgeschlagen: ' . implode(', ', $failedNames) . '.';
            }

            return ['success' => false, 'error' => trim('Es konnte keine Kategorie gelöscht werden. ' . implode(' ', $details))];
        }

        $messageParts = [];
        $messageParts[] = count($deletedNames) . ' Kategorie/Kategorien mit Ersatzkategorie wurden gelöscht und automatisch umgestellt.';

        if ($cyclicCategories !== []) {
            $messageParts[] = count($cyclicCategories) . ' wegen zyklischer Ersatzketten übersprungen.';
        }

        if ($skippedInvalid !== []) {
            $messageParts[] = count($skippedInvalid) . ' wegen ungültiger Ersatzkategorie übersprungen.';
        }

        if ($failedNames !== []) {
            $messageParts[] = count($failedNames) . ' konnten nicht gelöscht werden.';
        }

        return ['success' => true, 'message' => implode(' ', $messageParts)];
    }

    private function deleteCategoryResolved(int $id, int $replacementId = 0): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige Kategorie.'];
        }

        $assignedPostCount = $this->countAssignedPostsForCategory($id);

        if ($assignedPostCount > 0) {
            if ($replacementId <= 0) {
                if ($this->countAvailableReplacementCategories($id) <= 0) {
                    return ['success' => false, 'error' => 'Es ist keine Ersatzkategorie verfügbar. Bitte lege zuerst eine weitere Kategorie an, bevor du diese löschst.'];
                }

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
            $this->db->execute("UPDATE {$this->prefix}post_categories SET replacement_category_id = NULL WHERE replacement_category_id = ?", [$id]);
            $this->db->execute("DELETE FROM {$this->prefix}post_categories WHERE id = ?", [$id]);

            return ['success' => true, 'message' => 'Kategorie gelöscht.'];
        } catch (\Throwable $e) {
            return $this->failResult(
                'posts.category.delete.failed',
                'Kategorie konnte nicht gelöscht werden.',
                $e,
                ['category_id' => $id, 'replacement_category_id' => $replacementId]
            );
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

    private function getStoredReplacementCategoryId(int $categoryId): int
    {
        if ($categoryId <= 0) {
            return 0;
        }

        $replacementId = (int) ($this->db->get_var(
            "SELECT replacement_category_id FROM {$this->prefix}post_categories WHERE id = ? LIMIT 1",
            [$categoryId]
        ) ?: 0);

        if ($replacementId <= 0 || $replacementId === $categoryId) {
            return 0;
        }

        return $this->categoryExists($replacementId) ? $replacementId : 0;
    }

    /**
     * @return array<int,array{id:int,name:string,replacement_category_id:int}>
     */
    private function getCategoriesWithStoredReplacement(): array
    {
        $rows = $this->db->get_results(
            "SELECT id, name, replacement_category_id
             FROM {$this->prefix}post_categories
             WHERE replacement_category_id IS NOT NULL AND replacement_category_id > 0
             ORDER BY name ASC"
        ) ?: [];

        $categories = [];
        foreach ($rows as $row) {
            $categoryId = (int) ($row->id ?? 0);
            if ($categoryId <= 0) {
                continue;
            }

            $categories[] = [
                'id' => $categoryId,
                'name' => trim((string) ($row->name ?? 'Kategorie')),
                'replacement_category_id' => (int) ($row->replacement_category_id ?? 0),
            ];
        }

        return $categories;
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
        $name = $this->sanitizePlainText((string) ($post['tag_name'] ?? ''), 120);
        $slug = $this->normalizeSlug((string) ($post['tag_slug'] ?? ''));

        if ($name === '') {
            return ['success' => false, 'error' => 'Tag-Name darf nicht leer sein.'];
        }

        $slug = $slug !== '' ? $slug : $this->normalizeSlug($this->generateSlug($name));
        if ($slug === '') {
            return ['success' => false, 'error' => 'Bitte einen gültigen Tag-Slug angeben.'];
        }

        if ($id > 0 && !$this->tagExists($id)) {
            return ['success' => false, 'error' => 'Der zu bearbeitende Tag existiert nicht mehr.'];
        }

        if ($this->isTagSlugTaken($slug, $id)) {
            return ['success' => false, 'error' => 'Dieser Tag-Slug ist bereits vergeben.'];
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
            return $this->failResult('posts.tag.save.failed', 'Tag konnte nicht gespeichert werden.', $e, ['tag_id' => $id]);
        }
    }

    /**
     * Tag löschen.
     */
    public function deleteTag(int $id, int $replacementId = 0): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültiger Tag.'];
        }

        $assignedPostCount = $this->countAssignedPostsForTag($id);

        if ($assignedPostCount > 0) {
            if ($replacementId <= 0) {
                if ($this->countAvailableReplacementTags($id) <= 0) {
                    return ['success' => false, 'error' => 'Es ist kein Ersatztag verfügbar. Bitte lege zuerst einen weiteren Tag an, bevor du diesen löschst.'];
                }

                return ['success' => false, 'error' => 'Bitte einen Ersatztag für die betroffenen Beiträge auswählen.'];
            }

            if ($replacementId === $id) {
                return ['success' => false, 'error' => 'Der Ersatztag darf nicht identisch mit dem zu löschenden Tag sein.'];
            }

            if (!$this->tagExists($replacementId)) {
                return ['success' => false, 'error' => 'Der gewählte Ersatztag existiert nicht mehr.'];
            }
        }

        try {
            if ($assignedPostCount > 0 && $replacementId > 0) {
                $this->reassignPostsFromDeletedTag($id, $replacementId);
            } else {
                $this->db->execute("DELETE FROM {$this->prefix}post_tag_rel WHERE tag_id = ?", [$id]);
            }

            $this->db->execute("DELETE FROM {$this->prefix}post_tags WHERE id = ?", [$id]);

            return ['success' => true, 'message' => 'Tag gelöscht.'];
        } catch (\Throwable $e) {
            return $this->failResult('posts.tag.delete.failed', 'Tag konnte nicht gelöscht werden.', $e, ['tag_id' => $id, 'replacement_tag_id' => $replacementId]);
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

    private function sanitizePlainText(string $value, int $maxLength): string
    {
        $value = trim(strip_tags($value));
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', $value) ?? '';

        return function_exists('mb_substr')
            ? mb_substr($value, 0, $maxLength)
            : substr($value, 0, $maxLength);
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

    private function logFailure(string $action, string $message, ?\Throwable $exception = null, array $context = []): void
    {
        if ($exception !== null) {
            $context['exception'] = $exception->getMessage();
        }

        Logger::instance()->withChannel('admin.posts')->error($message, $context);
        AuditLogger::instance()->log(
            AuditLogger::CAT_CONTENT,
            $action,
            $message,
            'posts',
            null,
            $context,
            'error'
        );
    }

    /**
     * Slug generieren
     */
    private function generateSlug(string $text): string
    {
        $slug = $this->toLowercase($text);
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug) ?? $slug;
        $slug = preg_replace('/-+/', '-', $slug) ?? $slug;
        return trim($slug, '-');
    }

    private function normalizeSlug(string $slug): string
    {
        $slug = $this->toLowercase(trim($slug));
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug) ?? $slug;
        $slug = preg_replace('/-+/', '-', $slug) ?? $slug;
        return trim($slug, '-');
    }

    private function toLowercase(string $value): string
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower($value)
            : strtolower($value);
    }

    private function truncateText(string $value, int $maxLength): string
    {
        return function_exists('mb_substr')
            ? mb_substr($value, 0, $maxLength)
            : substr($value, 0, $maxLength);
    }

    /**
     * @return array{value:?string,error:?string}
     */
    private function normalizePublishedAtInput(string $date, string $time): array
    {
        $date = trim($date);
        $time = trim($time);

        if ($date === '' && $time === '') {
            return ['value' => null, 'error' => null];
        }

        if ($date === '') {
            return ['value' => null, 'error' => 'Bitte ein Veröffentlichungsdatum angeben.'];
        }

        if ($time === '') {
            $time = '00:00';
        }

        $publishedAt = \DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time);
        $errors = \DateTimeImmutable::getLastErrors();
        $hasErrors = is_array($errors) && ((int) ($errors['warning_count'] ?? 0) > 0 || (int) ($errors['error_count'] ?? 0) > 0);

        if (!$publishedAt instanceof \DateTimeImmutable || $hasErrors) {
            return ['value' => null, 'error' => 'Bitte ein gültiges Veröffentlichungsdatum mit Uhrzeit angeben.'];
        }

        return ['value' => $publishedAt->format('Y-m-d H:i:s'), 'error' => null];
    }

    private function resolvePublishedAtValue(string $status, ?string $requestedPublishedAt, ?object $existing): ?string
    {
        $requestedPublishedAt = is_string($requestedPublishedAt) && trim($requestedPublishedAt) !== ''
            ? trim($requestedPublishedAt)
            : null;

        $existingPublishedAt = $existing !== null && !empty($existing->published_at)
            ? trim((string) $existing->published_at)
            : null;

        if ($status === 'draft' || $status === 'private') {
            return $requestedPublishedAt;
        }

        if ($requestedPublishedAt !== null) {
            return $requestedPublishedAt;
        }

        if ($existingPublishedAt !== null && $existingPublishedAt !== '') {
            return $existingPublishedAt;
        }

        return date('Y-m-d H:i:s');
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

    private function isTagSlugTaken(string $slug, int $ignoreId = 0): bool
    {
        $params = [$slug];
        $sql = "SELECT COUNT(*) FROM {$this->prefix}post_tags WHERE slug = ?";

        if ($ignoreId > 0) {
            $sql .= " AND id != ?";
            $params[] = $ignoreId;
        }

        return (int) $this->db->get_var($sql, $params) > 0;
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

    private function countAvailableReplacementCategories(int $excludeId): int
    {
        return (int) ($this->db->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}post_categories WHERE id != ?",
            [$excludeId]
        ) ?: 0);
    }

    private function tagExists(int $tagId): bool
    {
        if ($tagId <= 0) {
            return false;
        }

        return (int) ($this->db->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}post_tags WHERE id = ?",
            [$tagId]
        ) ?: 0) > 0;
    }

    private function countAvailableReplacementTags(int $excludeId): int
    {
        return (int) ($this->db->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}post_tags WHERE id != ?",
            [$excludeId]
        ) ?: 0);
    }

    private function countAssignedPostsForTag(int $tagId): int
    {
        if ($tagId <= 0) {
            return 0;
        }

        return (int) ($this->db->get_var(
            "SELECT COUNT(DISTINCT post_id) FROM {$this->prefix}post_tag_rel WHERE tag_id = ?",
            [$tagId]
        ) ?: 0);
    }

    private function reassignPostsFromDeletedTag(int $deletedTagId, int $replacementTagId): void
    {
        $postRows = $this->db->get_results(
            "SELECT DISTINCT post_id FROM {$this->prefix}post_tag_rel WHERE tag_id = ?",
            [$deletedTagId]
        ) ?: [];

        $postIds = [];
        foreach ($postRows as $row) {
            $postId = (int) ($row->post_id ?? 0);
            if ($postId > 0) {
                $postIds[] = $postId;
            }
        }

        if ($postIds !== []) {
            $this->db->execute(
                "INSERT IGNORE INTO {$this->prefix}post_tag_rel (post_id, tag_id)
                 SELECT DISTINCT post_id, ?
                 FROM {$this->prefix}post_tag_rel
                 WHERE tag_id = ?",
                [$replacementTagId, $deletedTagId]
            );
        }

        $this->db->execute("DELETE FROM {$this->prefix}post_tag_rel WHERE tag_id = ?", [$deletedTagId]);

        foreach ($postIds as $postId) {
            $tagNames = $this->getPostTagNames($postId);
            $this->db->execute(
                "UPDATE {$this->prefix}posts SET tags = ?, updated_at = NOW() WHERE id = ?",
                [implode(', ', $tagNames), $postId]
            );
        }
    }

    /**
     * @return array<int,string>
     */
    private function getPostTagNames(int $postId): array
    {
        if ($postId <= 0) {
            return [];
        }

        $rows = $this->db->get_results(
            "SELECT t.name
             FROM {$this->prefix}post_tags t
             INNER JOIN {$this->prefix}post_tag_rel ptr ON ptr.tag_id = t.id
             WHERE ptr.post_id = ?
             ORDER BY t.name ASC",
            [$postId]
        ) ?: [];

        $names = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row->name ?? ''));
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
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
                'name' => $this->truncateText($name, 120),
                'slug' => $this->truncateText($slug, 160),
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
