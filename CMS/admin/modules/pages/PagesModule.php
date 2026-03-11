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
use CMS\PageManager;
use CMS\Security;
use CMS\Services\RedirectService;
use CMS\Services\ContentLocalizationService;
use CMS\Services\MediaDeliveryService;
use CMS\Services\MediaService;
use CMS\Services\SEOService;

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
            'seoMeta' => $id !== null ? SEOService::getInstance()->getContentMeta('page', $id) : SEOService::getInstance()->getContentMeta('page', 0),
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
        $defaultStatus = function_exists('get_option') ? (string)get_option('setting_page_default_status', 'draft') : 'draft';
        if (!in_array($defaultStatus, ['published', 'draft', 'private'], true)) {
            $defaultStatus = 'draft';
        }

        $status = in_array($post['status'] ?? '', ['published', 'draft', 'private'], true)
            ? $post['status']
            : $defaultStatus;
        $content    = $post['content'] ?? '';
        $titleEn    = trim($post['title_en'] ?? '');
        $contentEn  = $post['content_en'] ?? '';
        $hideTitle  = (int)($post['hide_title'] ?? 0);
        $featuredImage = trim($post['featured_image'] ?? '');
        $featuredImageTempPath = trim($post['featured_image_temp_path'] ?? '');
        $metaTitle  = trim($post['meta_title'] ?? '');
        $metaDesc   = trim($post['meta_description'] ?? '');
        $slug       = $this->normalizeSlug($slug !== '' ? $slug : $this->pageManager->generateSlug($title));

        // Move temp upload to slug subfolder (pages/{slug}/{filename})
        if ($featuredImageTempPath !== '' && str_contains($featuredImageTempPath, '/temp/')) {
            $mediaService = MediaService::getInstance();
            $mediaDelivery = MediaDeliveryService::getInstance();
            $folderSlug   = strtolower((string)preg_replace('/[^a-z0-9]+/i', '_', $slug));
            $folderSlug   = trim($folderSlug, '_');
            $newRelPath   = 'pages/' . $folderSlug . '/' . basename($featuredImageTempPath);
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
            'status' => $status,
            'content' => $content,
            'content_en' => $contentEn,
            'hide_title' => $hideTitle,
            'featured_image' => $featuredImage,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDesc,
        ];

        $filteredPayload = Hooks::applyFilters('cms_prepare_page_save_payload', $savePayload, $post, $id, $userId);
        if (is_array($filteredPayload)) {
            $savePayload = array_merge($savePayload, $filteredPayload);
        }

        try {
            if ($id > 0) {
                $existing = $this->db->get_row("SELECT slug FROM {$this->prefix}pages WHERE id = ? LIMIT 1", [$id]);
                // Update
                $updated = $this->pageManager->updatePage($id, $savePayload);
                if (!$updated) {
                    return ['success' => false, 'error' => 'Seite konnte nicht aktualisiert werden.'];
                }
                SEOService::getInstance()->saveContentMeta('page', $id, $post);
                $this->createSlugRedirectIfNeeded((string)($existing->slug ?? ''), $slug);
                Hooks::doAction('cms_after_page_save', $id, $savePayload, $post);
                return ['success' => true, 'id' => $id, 'message' => 'Seite aktualisiert.'];
            } else {
                // Create
                $newId = $this->pageManager->createPage((string)$savePayload['title'], (string)$savePayload['content'], (string)$savePayload['status'], $userId, (int)$savePayload['hide_title']);
                if ($newId > 0) {
                    // Update meta fields
                    $this->db->execute(
                        "UPDATE {$this->prefix}pages 
                         SET slug = ?, title_en = ?, content_en = ?, featured_image = ?, meta_title = ?, meta_description = ?
                         WHERE id = ?",
                        [
                            (string)$savePayload['slug'],
                            (string)($savePayload['title_en'] ?? ''),
                            (string)($savePayload['content_en'] ?? ''),
                            (string)$savePayload['featured_image'],
                            (string)$savePayload['meta_title'],
                            (string)$savePayload['meta_description'],
                            $newId,
                        ]
                    );
                    SEOService::getInstance()->saveContentMeta('page', $newId, $post);
                    Hooks::doAction('cms_after_page_save', $newId, $savePayload, $post);
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
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige Seiten-ID.'];
        }

        try {
            $success = $this->db->delete('pages', ['id' => $id]);

            if (!$success) {
                $error = trim((string)$this->db->last_error);
                return ['success' => false, 'error' => 'Fehler beim Löschen.' . ($error !== '' ? ' ' . $error : '')];
            }

            return ['success' => true, 'message' => 'Seite gelöscht.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Löschen: ' . $e->getMessage()];
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
                    $this->db->execute(
                        "DELETE FROM {$this->prefix}pages WHERE id IN ({$placeholders})",
                        $ids
                    );
                    return ['success' => true, 'message' => count($ids) . ' Seite(n) gelöscht.'];

                case 'publish':
                    $this->db->execute(
                        "UPDATE {$this->prefix}pages SET status = 'published', updated_at = NOW() WHERE id IN ({$placeholders})",
                        $ids
                    );
                    return ['success' => true, 'message' => count($ids) . ' Seite(n) veröffentlicht.'];

                case 'draft':
                    $this->db->execute(
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

    private function createSlugRedirectIfNeeded(string $oldSlug, string $newSlug): void
    {
        $oldSlug = trim($oldSlug);
        $newSlug = trim($newSlug);

        if ($oldSlug === '' || $newSlug === '' || $oldSlug === $newSlug) {
            return;
        }

        RedirectService::getInstance()->createAutomaticRedirect(
            '/' . $oldSlug,
            '/' . $newSlug,
            'Automatisch bei Seiten-Slug-Änderung angelegt'
        );

        foreach (ContentLocalizationService::getInstance()->getContentLocales() as $locale) {
            RedirectService::getInstance()->createAutomaticRedirect(
                '/' . $oldSlug . '/' . $locale,
                '/' . $newSlug . '/' . $locale,
                'Automatisch bei lokalisiertem Seiten-Slug angelegt'
            );
        }
    }
}
