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
use CMS\PageManager;
use CMS\Security;

class PagesModule
{
    private Database $db;
    private PageManager $pageManager;
    private string $prefix;

    public function __construct()
    {
        $this->db          = Database::instance();
        $this->prefix      = $this->db->getPrefix();
        $this->pageManager = PageManager::instance();
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
        $statusFilter = $_GET['status'] ?? '';
        $search       = trim($_GET['q'] ?? '');

        // Query bauen
        $where  = [];
        $params = [];

        if ($statusFilter !== '' && in_array($statusFilter, ['published', 'draft', 'private'], true)) {
            $where[]  = 'p.status = ?';
            $params[] = $statusFilter;
        }
        if ($search !== '') {
            $where[]  = '(p.title LIKE ? OR p.slug LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $pages = $this->db->get_results(
            "SELECT p.id, p.title, p.slug, p.status, p.created_at, p.updated_at,
                    u.display_name AS author
             FROM {$this->prefix}pages p
             LEFT JOIN {$this->prefix}users u ON p.author_id = u.id
             {$whereClause}
             ORDER BY p.updated_at DESC
             LIMIT 200",
            $params
        ) ?: [];

        return [
            'pages'     => $pages,
            'counts'    => compact('total', 'published', 'drafts', 'private'),
            'filter'    => $statusFilter,
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

        return [
            'page'   => $page,
            'isNew'  => $page === null,
        ];
    }

    /**
     * Seite speichern (Create oder Update)
     */
    public function save(array $post, int $userId): array
    {
        $id     = (int)($post['id'] ?? 0);
        $title  = trim($post['title'] ?? '');
        $slug   = trim($post['slug'] ?? '');
        $status = in_array($post['status'] ?? '', ['published', 'draft', 'private'], true)
            ? $post['status'] : 'draft';
        $content    = $post['content'] ?? '';
        $hideTitle  = (int)($post['hide_title'] ?? 0);
        $featuredImage = trim($post['featured_image'] ?? '');
        $metaTitle  = trim($post['meta_title'] ?? '');
        $metaDesc   = trim($post['meta_description'] ?? '');

        if ($title === '') {
            return ['success' => false, 'error' => 'Titel darf nicht leer sein.'];
        }

        try {
            if ($id > 0) {
                // Update
                $this->db->query(
                    "UPDATE {$this->prefix}pages 
                     SET title = ?, slug = ?, content = ?, status = ?,
                         hide_title = ?, featured_image = ?,
                         meta_title = ?, meta_description = ?,
                         updated_at = NOW()
                     WHERE id = ?",
                    [$title, $slug, $content, $status, $hideTitle, $featuredImage, $metaTitle, $metaDesc, $id]
                );
                return ['success' => true, 'id' => $id, 'message' => 'Seite aktualisiert.'];
            } else {
                // Create
                $newId = $this->pageManager->createPage($title, $content, $status, $userId, $hideTitle);
                if ($newId > 0) {
                    // Update meta fields
                    $this->db->query(
                        "UPDATE {$this->prefix}pages 
                         SET featured_image = ?, meta_title = ?, meta_description = ?
                         WHERE id = ?",
                        [$featuredImage, $metaTitle, $metaDesc, $newId]
                    );
                }
                return ['success' => true, 'id' => $newId, 'message' => 'Seite erstellt.'];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Speichern: ' . $e->getMessage()];
        }
    }

    /**
     * Seite löschen
     */
    public function delete(int $id): array
    {
        try {
            $this->db->query("DELETE FROM {$this->prefix}pages WHERE id = ?", [$id]);
            return ['success' => true, 'message' => 'Seite gelöscht.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Löschen.'];
        }
    }

    /**
     * Bulk-Aktion ausführen
     */
    public function bulkAction(string $action, array $ids): array
    {
        if (empty($ids)) {
            return ['success' => false, 'error' => 'Keine Einträge ausgewählt.'];
        }

        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        try {
            switch ($action) {
                case 'delete':
                    $this->db->query(
                        "DELETE FROM {$this->prefix}pages WHERE id IN ({$placeholders})",
                        $ids
                    );
                    return ['success' => true, 'message' => count($ids) . ' Seite(n) gelöscht.'];

                case 'publish':
                    $this->db->query(
                        "UPDATE {$this->prefix}pages SET status = 'published', updated_at = NOW() WHERE id IN ({$placeholders})",
                        $ids
                    );
                    return ['success' => true, 'message' => count($ids) . ' Seite(n) veröffentlicht.'];

                case 'draft':
                    $this->db->query(
                        "UPDATE {$this->prefix}pages SET status = 'draft', updated_at = NOW() WHERE id IN ({$placeholders})",
                        $ids
                    );
                    return ['success' => true, 'message' => count($ids) . ' Seite(n) als Entwurf gespeichert.'];

                default:
                    return ['success' => false, 'error' => 'Unbekannte Aktion.'];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler bei der Bulk-Aktion.'];
        }
    }
}
