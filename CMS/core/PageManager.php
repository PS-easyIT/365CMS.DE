<?php
/**
 * Page Manager
 * 
 * Handles Pages, Content, Revisions and Search
 * 
 * @package CMSv2\Core
 */

declare(strict_types=1);

namespace CMS;

if (!defined('ABSPATH')) {
    exit;
}

class PageManager
{
    private static ?self $instance = null;
    private Database $db;
    private string $prefix;
    
    /**
     * Singleton instance
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct()
    {
        $this->db = Database::instance();
        $this->prefix = $this->db->getPrefix();
        $this->ensureColumns();
    }

    /**
     * Migrate: add hide_title column if missing (safe for existing installs)
     */
    private function ensureColumns(): void
    {
        try {
            $columns = [
                'slug_en' => "ALTER TABLE {$this->prefix}pages ADD COLUMN slug_en VARCHAR(200) DEFAULT NULL AFTER slug",
                'hide_title' => "ALTER TABLE {$this->prefix}pages ADD COLUMN hide_title TINYINT(1) NOT NULL DEFAULT 0",
                'featured_image' => "ALTER TABLE {$this->prefix}pages ADD COLUMN featured_image VARCHAR(500) DEFAULT NULL AFTER hide_title",
                'meta_title' => "ALTER TABLE {$this->prefix}pages ADD COLUMN meta_title VARCHAR(255) DEFAULT NULL AFTER featured_image",
                'meta_description' => "ALTER TABLE {$this->prefix}pages ADD COLUMN meta_description TEXT DEFAULT NULL AFTER meta_title",
                'title_en' => "ALTER TABLE {$this->prefix}pages ADD COLUMN title_en VARCHAR(255) DEFAULT NULL AFTER title",
                'content_en' => "ALTER TABLE {$this->prefix}pages ADD COLUMN content_en LONGTEXT DEFAULT NULL AFTER content",
                'category_id' => "ALTER TABLE {$this->prefix}pages ADD COLUMN category_id INT UNSIGNED DEFAULT NULL AFTER author_id",
            ];

            foreach ($columns as $column => $sql) {
                $stmt = $this->db->query("SHOW COLUMNS FROM {$this->prefix}pages LIKE '{$column}'");
                if (!$stmt->fetch()) {
                    $this->db->query($sql);
                }
            }
        } catch (\Throwable $e) {
            error_log('PageManager::ensureColumns() warning: ' . $e->getMessage());
        }
    }
    
    /**
     * Create Page
     */
    public function createPage(string $title, string $content, string $status, int $authorId, int $hideTitle = 0): int
    {
        $slug = $this->generateSlug($title);
        // Ensure unique slug
        $originalSlug = $slug;
        $count = 1;
        while ($this->getPageBySlug($slug)) {
            $slug = $originalSlug . '-' . $count++;
        }
        
        $sql = "INSERT INTO {$this->prefix}pages (title, content, slug, status, hide_title, author_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$title, $content, $slug, $status, $hideTitle, $authorId]);
        
        return (int)$this->db->insert_id();
    }
    
    /**
     * Update Page
     */
    public function updatePage(int $id, array $data): bool
    {
        $currentPage = $this->getPage($id);
        if ($currentPage === null) {
            return false;
        }

        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, ['title', 'title_en', 'content', 'content_en', 'status', 'slug', 'slug_en', 'hide_title', 'featured_image', 'meta_title', 'meta_description', 'category_id'], true)) {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }

        if ($this->hasTrackedRevisionChanges($currentPage, $data) && !$this->storeRevisionSnapshot($currentPage)) {
            return false;
        }
        
        $fields[] = "updated_at = NOW()";
        $values[] = $id;
        
        $sql = "UPDATE {$this->prefix}pages SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Prüft, ob sich revisionsrelevante Inhaltsfelder geändert haben.
     */
    private function hasTrackedRevisionChanges(array $currentPage, array $newData): bool
    {
        foreach (['title', 'content', 'excerpt'] as $field) {
            if (!array_key_exists($field, $newData)) {
                continue;
            }

            if ((string)($currentPage[$field] ?? '') !== (string)$newData[$field]) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * Delete Page
     */
    public function deletePage(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->prefix}pages WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Generate a URL-safe slug from a title, with German umlaut handling
     */
    public function generateSlug(string $title): string
    {
        // German umlauts and common accented chars → ASCII equivalents
        $title = str_replace(
            ['ä','ö','ü','Ä','Ö','Ü','ß','é','è','ê','à','â','î','ï','ô','ù','û'],
            ['ae','oe','ue','ae','oe','ue','ss','e','e','e','a','a','i','i','o','u','u'],
            $title
        );
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        return $slug !== '' ? $slug : 'seite';
    }

    /**
     * Get Page by ID (returns Array)
     */
    public function getPage(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->prefix}pages WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * Get Page by Slug (returns Array)
     */
    public function getPageBySlug(string $slug, string $locale = 'de'): ?array
    {
        $normalizedLocale = strtolower(trim($locale));
        if ($normalizedLocale === 'en') {
            $stmt = $this->db->prepare("SELECT * FROM {$this->prefix}pages WHERE (slug_{$normalizedLocale} = ? OR slug = ?) LIMIT 1");
            $stmt->execute([$slug, $slug]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM {$this->prefix}pages WHERE slug = ? LIMIT 1");
            $stmt->execute([$slug]);
        }
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * List Pages
     */
    public function listPages(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->prefix}pages ORDER BY created_at DESC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Search Pages
     */
    public function search(string $query): array
    {
        $term = '%' . $query . '%';
        $stmt = $this->db->prepare("SELECT * FROM {$this->prefix}pages WHERE (title LIKE ? OR content LIKE ? OR title_en LIKE ? OR content_en LIKE ?) AND status = 'published' ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([$term, $term, $term, $term]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Speichert einen Revisions-Snapshot der aktuellen Seite.
     */
    private function storeRevisionSnapshot(array $page): bool
    {
        $pageId = (int)($page['id'] ?? 0);
        if ($pageId <= 0) {
            return false;
        }

        $sql = "INSERT INTO {$this->prefix}page_revisions (page_id, title, content, excerpt, author_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $pageId,
            (string)($page['title'] ?? ''),
            (string)($page['content'] ?? ''),
            (string)($page['excerpt'] ?? ''),
            (int)($page['author_id'] ?? 0),
        ]);
    }

    /**
     * Gibt gespeicherte Revisionen einer Seite zurück.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRevisions(int $pageId): array
    {
        if ($pageId <= 0) {
            return [];
        }

        $sql = "SELECT pr.id,
                       pr.page_id,
                       pr.title,
                       pr.content,
                       pr.excerpt,
                       pr.author_id,
                       pr.created_at,
                       u.username,
                       u.display_name
                FROM {$this->prefix}page_revisions pr
                LEFT JOIN {$this->prefix}users u ON pr.author_id = u.id
                WHERE pr.page_id = ?
                ORDER BY pr.created_at DESC, pr.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pageId]);
        $revisions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $revisions ?: [];
    }
}
