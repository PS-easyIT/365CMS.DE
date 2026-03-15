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

    public function __construct()
    {
        $this->db     = Database::instance();
        $this->prefix = $this->db->getPrefix();
        $this->ensureColumns();
    }

    /**
     * Ergänzt fehlende Beitrags-Spalten in Altinstallationen.
     */
    private function ensureColumns(): void
    {
        $columns = [
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
            $where[]  = 'p.category_id = ?';
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
             ORDER BY p.updated_at DESC
             LIMIT 200",
            $params
        ) ?: [];

        $categories = $this->db->get_results(
            "SELECT id, name, slug FROM {$this->prefix}post_categories ORDER BY name ASC"
        ) ?: [];

        return [
            'posts'      => array_map(fn($p) => (array)$p, $posts),
            'categories' => array_map(fn($c) => (array)$c, $categories),
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
            "SELECT id, name FROM {$this->prefix}post_categories ORDER BY name ASC"
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
            'categories' => array_map(fn($c) => (array)$c, $categories),
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
            "SELECT c.id, c.name, c.slug, COUNT(p.id) AS post_count
             FROM {$this->prefix}post_categories c
             LEFT JOIN {$this->prefix}posts p ON p.category_id = c.id
             GROUP BY c.id, c.name, c.slug
             ORDER BY c.name ASC"
        ) ?: [];

        return [
            'categories' => array_map(fn($category) => (array) $category, $categories),
            'counts' => [
                'total' => count($categories),
                'assigned_posts' => array_sum(array_map(static fn($category): int => (int) ($category->post_count ?? 0), $categories)),
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
        $title      = trim($post['title'] ?? '');
        $slug       = trim($post['slug'] ?? '');
        $defaultStatus = function_exists('get_option') ? (string)get_option('setting_post_default_status', 'draft') : 'draft';
        if (!in_array($defaultStatus, ['published', 'draft'], true)) {
            $defaultStatus = 'draft';
        }

        $status     = in_array($post['status'] ?? '', ['published', 'draft'], true) ? $post['status'] : $defaultStatus;
        $content    = $post['content'] ?? '';
        $titleEn    = trim($post['title_en'] ?? '');
        $contentEn  = $post['content_en'] ?? '';
        $excerpt    = trim($post['excerpt'] ?? '');
        $excerptEn  = trim($post['excerpt_en'] ?? '');
        $categoryId = (int)($post['category_id'] ?? 0);
        $featuredImage = trim($post['featured_image'] ?? '');
        $featuredImageTempPath = trim($post['featured_image_temp_path'] ?? '');
        $metaTitle  = trim($post['meta_title'] ?? '');
        $metaDesc   = trim($post['meta_description'] ?? '');
        $authorDisplayName = $this->sanitizeAuthorDisplayName((string)($post['author_display_name'] ?? ''));
        $rawTags    = $post['tags'] ?? '';
        $slug       = $this->normalizeSlug($slug !== '' ? $slug : $this->generateSlug($title));

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

        if ($title === '') {
            return ['success' => false, 'error' => 'Titel darf nicht leer sein.'];
        }

        if ($slug === '') {
            return ['success' => false, 'error' => 'Bitte einen gültigen Slug angeben.'];
        }

        if ($this->isSlugTaken($slug, $id)) {
            return ['success' => false, 'error' => 'Dieser Slug ist bereits vergeben.'];
        }

        $savePayload = [
            'title' => $title,
            'title_en' => $titleEn,
            'slug' => $slug,
            'content' => $content,
            'content_en' => $contentEn,
            'excerpt' => $excerpt,
            'excerpt_en' => $excerptEn,
            'status' => $status,
            'category_id' => $categoryId ?: null,
            'featured_image' => $featuredImage,
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
                     SET title = ?, title_en = ?, slug = ?, content = ?, content_en = ?, excerpt = ?, excerpt_en = ?, status = ?,
                         category_id = ?, featured_image = ?,
                         meta_title = ?, meta_description = ?, author_display_name = ?,
                         updated_at = NOW(){$setPubAt}
                     WHERE id = ?",
                    [
                        (string)$savePayload['title'],
                        (string)($savePayload['title_en'] ?? ''),
                        (string)$savePayload['slug'],
                        (string)$savePayload['content'],
                        (string)($savePayload['content_en'] ?? ''),
                        (string)$savePayload['excerpt'],
                        (string)($savePayload['excerpt_en'] ?? ''),
                        (string)$savePayload['status'],
                        $savePayload['category_id'],
                        (string)$savePayload['featured_image'],
                        (string)$savePayload['meta_title'],
                        (string)$savePayload['meta_description'],
                        (string)($savePayload['author_display_name'] ?? ''),
                        $id,
                    ]
                );

                SEOService::getInstance()->saveContentMeta('post', $id, $post);
                $this->syncPostTags($id, $rawTags);
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
                     (title, title_en, slug, content, content_en, excerpt, excerpt_en, status, category_id, featured_image, meta_title, meta_description, author_id, author_display_name, published_at, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, {$pubAtValue}, NOW(), NOW())",
                    [
                        (string)$savePayload['title'],
                        (string)($savePayload['title_en'] ?? ''),
                        (string)$savePayload['slug'],
                        (string)$savePayload['content'],
                        (string)($savePayload['content_en'] ?? ''),
                        (string)$savePayload['excerpt'],
                        (string)($savePayload['excerpt_en'] ?? ''),
                        (string)$savePayload['status'],
                        $savePayload['category_id'],
                        (string)$savePayload['featured_image'],
                        (string)$savePayload['meta_title'],
                        (string)$savePayload['meta_description'],
                        $userId,
                        (string)($savePayload['author_display_name'] ?? ''),
                    ]
                );
                $newId = (int)$this->db->lastInsertId();
                SEOService::getInstance()->saveContentMeta('post', $newId, $post);
                $this->syncPostTags($newId, $rawTags);
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

        if ($name === '') {
            return ['success' => false, 'error' => 'Kategoriename darf nicht leer sein.'];
        }
        if ($slug === '') {
            $slug = $this->generateSlug($name);
        }

        try {
            if ($id > 0) {
                $this->db->execute(
                    "UPDATE {$this->prefix}post_categories SET name = ?, slug = ? WHERE id = ?",
                    [$name, $slug, $id]
                );
                return ['success' => true, 'message' => 'Kategorie aktualisiert.'];
            } else {
                $this->db->execute(
                    "INSERT INTO {$this->prefix}post_categories (name, slug) VALUES (?, ?)",
                    [$name, $slug]
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
        try {
            // Posts auf "keine Kategorie" setzen
            $this->db->execute("UPDATE {$this->prefix}posts SET category_id = NULL WHERE category_id = ?", [$id]);
            $this->db->execute("DELETE FROM {$this->prefix}post_categories WHERE id = ?", [$id]);
            return ['success' => true, 'message' => 'Kategorie gelöscht.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Löschen der Kategorie.'];
        }
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
