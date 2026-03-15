<?php
/**
 * Redirect Service
 *
 * Verwalten von 404-Logs und Weiterleitungen.
 *
 * @package CMS\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class RedirectService
{
    private static ?self $instance = null;

    private readonly Database $db;
    private readonly string $prefix;
    private ?bool $hasSiteTableSlugColumn = null;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->db = Database::instance();
        $this->prefix = $this->db->getPrefix();
        $this->ensureTables();
    }

    public function ensureTables(): void
    {
        $this->db->getPdo()->exec(
            "CREATE TABLE IF NOT EXISTS {$this->prefix}redirect_rules (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                source_path VARCHAR(255) NOT NULL,
                target_url VARCHAR(500) NOT NULL,
                redirect_type SMALLINT NOT NULL DEFAULT 301,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                notes TEXT DEFAULT NULL,
                hits INT UNSIGNED NOT NULL DEFAULT 0,
                last_hit_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY idx_source_path (source_path),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->getPdo()->exec(
            "CREATE TABLE IF NOT EXISTS {$this->prefix}not_found_logs (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                request_path VARCHAR(255) NOT NULL,
                request_method VARCHAR(10) NOT NULL DEFAULT 'GET',
                referrer_url VARCHAR(500) DEFAULT NULL,
                ip_address VARCHAR(64) DEFAULT NULL,
                user_agent VARCHAR(500) DEFAULT NULL,
                hit_count INT UNSIGNED NOT NULL DEFAULT 1,
                first_seen_at DATETIME NOT NULL,
                last_seen_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY idx_request_path (request_path),
                INDEX idx_last_seen (last_seen_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function findRedirect(string $path): ?array
    {
        $normalized = $this->normalizePath($path);
        if ($this->shouldIgnorePath($normalized)) {
            return null;
        }

        $rule = $this->db->get_row(
            "SELECT * FROM {$this->prefix}redirect_rules WHERE source_path = ? AND is_active = 1 LIMIT 1",
            [$normalized]
        );

        if (!$rule) {
            return null;
        }

        $this->db->execute(
            "UPDATE {$this->prefix}redirect_rules SET hits = hits + 1, last_hit_at = NOW() WHERE id = ?",
            [(int)$rule->id]
        );

        return [
            'id' => (int)$rule->id,
            'source_path' => (string)$rule->source_path,
            'target_url' => (string)$rule->target_url,
            'redirect_type' => (int)$rule->redirect_type,
        ];
    }

    public function logNotFound(string $path, array $context = []): void
    {
        $normalized = $this->normalizePath($path);
        if ($this->shouldIgnorePath($normalized)) {
            return;
        }

        $existing = $this->db->get_row(
            "SELECT id, hit_count FROM {$this->prefix}not_found_logs WHERE request_path = ? LIMIT 1",
            [$normalized]
        );

        if ($existing) {
            $this->db->execute(
                "UPDATE {$this->prefix}not_found_logs
                 SET hit_count = hit_count + 1,
                     request_method = ?,
                     referrer_url = ?,
                     ip_address = ?,
                     user_agent = ?,
                     last_seen_at = NOW()
                 WHERE id = ?",
                [
                    (string)($context['request_method'] ?? 'GET'),
                    $this->limitString((string)($context['referrer_url'] ?? ''), 500),
                    $this->limitString((string)($context['ip_address'] ?? ''), 64),
                    $this->limitString((string)($context['user_agent'] ?? ''), 500),
                    (int)$existing->id,
                ]
            );
            return;
        }

        $this->db->insert('not_found_logs', [
            'request_path' => $normalized,
            'request_method' => $this->limitString((string)($context['request_method'] ?? 'GET'), 10),
            'referrer_url' => $this->limitString((string)($context['referrer_url'] ?? ''), 500),
            'ip_address' => $this->limitString((string)($context['ip_address'] ?? ''), 64),
            'user_agent' => $this->limitString((string)($context['user_agent'] ?? ''), 500),
            'hit_count' => 1,
            'first_seen_at' => date('Y-m-d H:i:s'),
            'last_seen_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getAdminData(): array
    {
        $redirects = $this->db->get_results(
            "SELECT * FROM {$this->prefix}redirect_rules ORDER BY is_active DESC, updated_at DESC, source_path ASC"
        ) ?: [];

        $logs = $this->db->get_results(
            "SELECT nfl.*,
                    rr.id AS redirect_id,
                    rr.target_url,
                    rr.redirect_type,
                    rr.notes AS redirect_notes,
                    rr.is_active AS redirect_is_active
             FROM {$this->prefix}not_found_logs nfl
             LEFT JOIN {$this->prefix}redirect_rules rr ON rr.source_path = nfl.request_path
             ORDER BY nfl.last_seen_at DESC
             LIMIT 200"
        ) ?: [];

        $stats = [
            'redirects_total' => count($redirects),
            'redirects_active' => count(array_filter($redirects, static fn($row) => (int)$row->is_active === 1)),
            'not_found_total' => count($logs),
            'not_found_hits' => array_sum(array_map(static fn($row) => (int)$row->hit_count, $logs)),
        ];

        return [
            'redirects' => array_map(static fn($row) => (array)$row, $redirects),
            'logs' => array_map(static function ($row): array {
                $mapped = (array)$row;
                $mapped['redirect_id'] = isset($mapped['redirect_id']) ? (int)$mapped['redirect_id'] : 0;
                return $mapped;
            }, $logs),
            'stats' => $stats,
            'targets' => [
                'pages' => $this->getAvailablePageTargets(),
                'posts' => $this->getAvailablePostTargets(),
                'hubs' => $this->getAvailableHubTargets(),
            ],
        ];
    }

    public function saveRedirect(array $post): array
    {
        $id = (int)($post['redirect_id'] ?? 0);
        $source = $this->normalizePath((string)($post['source_path'] ?? ''));
        $target = $this->resolveTargetFromPost($post);
        $type = (int)($post['redirect_type'] ?? 301);
        $notes = trim((string)($post['notes'] ?? ''));
        $isActive = isset($post['is_active']) ? 1 : 0;

        if ($source === '' || $source === '/') {
            return ['success' => false, 'error' => 'Bitte einen gültigen Quellpfad angeben.'];
        }
        if ($target === '') {
            return ['success' => false, 'error' => 'Bitte eine Ziel-URL angeben.'];
        }
        if (!in_array($type, [301, 302], true)) {
            $type = 301;
        }
        if (!$this->isValidTarget($target)) {
            return ['success' => false, 'error' => 'Die Ziel-URL ist ungültig.'];
        }
        if ($this->isSameTargetAsSource($source, $target)) {
            return ['success' => false, 'error' => 'Quelle und Ziel dürfen nicht identisch sein.'];
        }

        $duplicateRedirect = $this->findRedirectBySourcePath($source);
        if ($duplicateRedirect !== null && (int)($duplicateRedirect['id'] ?? 0) !== $id) {
            return ['success' => false, 'error' => $this->buildDuplicateSourceError($source)];
        }

        $data = [
            'source_path' => $source,
            'target_url' => $target,
            'redirect_type' => $type,
            'is_active' => $isActive,
            'notes' => $notes,
        ];

        try {
            if ($id > 0) {
                $this->db->update('redirect_rules', $data, ['id' => $id]);
                return ['success' => true, 'message' => 'Weiterleitung aktualisiert.'];
            }

            $this->db->insert('redirect_rules', $data);
            return ['success' => true, 'message' => 'Weiterleitung angelegt.'];
        } catch (\Throwable $e) {
            if ($this->isDuplicateRedirectException($e)) {
                return ['success' => false, 'error' => $this->buildDuplicateSourceError($source)];
            }

            return ['success' => false, 'error' => 'Fehler beim Speichern: ' . $e->getMessage()];
        }
    }

    public function deleteRedirect(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige ID.'];
        }
        $this->db->delete('redirect_rules', ['id' => $id]);
        return ['success' => true, 'message' => 'Weiterleitung gelöscht.'];
    }

    public function deleteRedirectsBySlug(string $slug): array
    {
        $normalizedSlug = $this->normalizeSlugFilter($slug);
        if ($normalizedSlug === '') {
            return ['success' => false, 'error' => 'Bitte einen gültigen Slug angeben.'];
        }

        $likePattern = '%' . $normalizedSlug . '%';
        $candidates = $this->db->get_results(
            "SELECT id, source_path
             FROM {$this->prefix}redirect_rules
             WHERE source_path LIKE ?",
            [$likePattern]
        ) ?: [];

        $matchingIds = [];
        foreach ($candidates as $candidate) {
            $sourcePath = (string)($candidate->source_path ?? '');
            if (!$this->pathContainsSlugSegment($sourcePath, $normalizedSlug)) {
                continue;
            }

            $matchingIds[] = (int)($candidate->id ?? 0);
        }

        $matchingIds = array_values(array_filter($matchingIds, static fn(int $id): bool => $id > 0));
        if ($matchingIds === []) {
            return [
                'success' => false,
                'error' => sprintf('Keine Weiterleitungen mit dem Muster */%s/* gefunden.', $normalizedSlug),
            ];
        }

        $placeholders = implode(',', array_fill(0, count($matchingIds), '?'));
        $this->db->execute(
            "DELETE FROM {$this->prefix}redirect_rules WHERE id IN ({$placeholders})",
            $matchingIds
        );

        return [
            'success' => true,
            'message' => sprintf('%d Weiterleitung(en) mit */%s/* wurden gelöscht.', count($matchingIds), $normalizedSlug),
        ];
    }

    public function toggleRedirect(int $id): array
    {
        $row = $this->db->get_row("SELECT is_active FROM {$this->prefix}redirect_rules WHERE id = ? LIMIT 1", [$id]);
        if (!$row) {
            return ['success' => false, 'error' => 'Weiterleitung nicht gefunden.'];
        }

        $newStatus = (int)$row->is_active === 1 ? 0 : 1;
        $this->db->update('redirect_rules', ['is_active' => $newStatus], ['id' => $id]);

        return ['success' => true, 'message' => $newStatus === 1 ? 'Weiterleitung aktiviert.' : 'Weiterleitung deaktiviert.'];
    }

    public function clearLogs(): array
    {
        $this->db->query("TRUNCATE TABLE {$this->prefix}not_found_logs");
        return ['success' => true, 'message' => '404-Protokoll wurde geleert.'];
    }

    public function createAutomaticRedirect(string $sourcePath, string $targetUrl, string $notes = 'Automatisch bei Slug-Änderung angelegt'): array
    {
        $source = $this->normalizePath($sourcePath);
        $target = trim($targetUrl);

        if ($source === '' || $source === '/' || $target === '') {
            return ['success' => false, 'error' => 'Ungültige Redirect-Daten.'];
        }

        if (!$this->isValidTarget($target)) {
            return ['success' => false, 'error' => 'Ungültige Ziel-URL.'];
        }

        try {
            $existing = $this->db->get_row(
                "SELECT id FROM {$this->prefix}redirect_rules WHERE source_path = ? LIMIT 1",
                [$source]
            );

            $payload = [
                'source_path'   => $source,
                'target_url'    => $target,
                'redirect_type' => 301,
                'is_active'     => 1,
                'notes'         => $notes,
            ];

            if ($existing) {
                $this->db->update('redirect_rules', $payload, ['id' => (int)$existing->id]);
                return ['success' => true, 'message' => 'Automatische Weiterleitung aktualisiert.'];
            }

            $this->db->insert('redirect_rules', $payload);
            return ['success' => true, 'message' => 'Automatische Weiterleitung angelegt.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Redirect konnte nicht angelegt werden: ' . $e->getMessage()];
        }
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $path = (string)parse_url($path, PHP_URL_PATH);
        }

        $path = '/' . ltrim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    private function shouldIgnorePath(string $path): bool
    {
        if ($path === '' || $path === '/') {
            return true;
        }

        foreach (['/admin', '/member', '/api', '/assets', '/uploads', '/vendor'] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        if (preg_match('/\.(css|js|map|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|eot|pdf|xml|txt)$/i', $path) === 1) {
            return true;
        }

        return in_array($path, ['/favicon.ico', '/robots.txt', '/sitemap.xml'], true);
    }

    private function resolveTargetFromPost(array $post): string
    {
        $targetKind = strtolower(trim((string)($post['target_kind'] ?? 'manual')));

        return match ($targetKind) {
            'page' => $this->getPageTargetUrl((int)($post['target_page_id'] ?? 0)),
            'post' => $this->getPostTargetUrl((int)($post['target_post_id'] ?? 0)),
            'hub' => $this->getHubTargetUrl((int)($post['target_hub_id'] ?? 0)),
            default => $this->normalizeManualTarget((string)(
                trim((string)($post['target_url_manual'] ?? '')) !== ''
                    ? $post['target_url_manual']
                    : ($post['target_url'] ?? '')
            )),
        };
    }

    private function isValidTarget(string $target): bool
    {
        return str_starts_with($target, '/') || filter_var($target, FILTER_VALIDATE_URL) !== false;
    }

    private function isSameTargetAsSource(string $source, string $target): bool
    {
        if (!str_starts_with($target, '/')) {
            return false;
        }

        return $this->normalizePath($target) === $source;
    }

    private function normalizeManualTarget(string $target): string
    {
        $target = trim($target);
        if ($target === '') {
            return '';
        }

        if (filter_var($target, FILTER_VALIDATE_URL) !== false) {
            return $target;
        }

        return $this->normalizePath($target);
    }

    private function findRedirectBySourcePath(string $sourcePath): ?array
    {
        if ($sourcePath === '') {
            return null;
        }

        $row = $this->db->get_row(
            "SELECT id, source_path, target_url, is_active
             FROM {$this->prefix}redirect_rules
             WHERE source_path = ?
             LIMIT 1",
            [$sourcePath]
        );

        return $row ? (array)$row : null;
    }

    private function buildDuplicateSourceError(string $sourcePath): string
    {
        $duplicateRedirect = $this->findRedirectBySourcePath($sourcePath);
        if ($duplicateRedirect === null) {
            return sprintf(
                'Für die Quelle %s existiert bereits eine Weiterleitung. Bitte vorhandene Regel bearbeiten statt doppelt anzulegen.',
                $sourcePath
            );
        }

        $duplicateTarget = trim((string)($duplicateRedirect['target_url'] ?? ''));
        $duplicateStatus = (int)($duplicateRedirect['is_active'] ?? 0) === 1 ? 'aktive' : 'inaktive';

        if ($duplicateTarget !== '') {
            return sprintf(
                'Für die Quelle %s existiert bereits eine %s Weiterleitung nach %s. Bitte vorhandene Regel bearbeiten statt doppelt anzulegen.',
                $sourcePath,
                $duplicateStatus,
                $duplicateTarget
            );
        }

        return sprintf(
            'Für die Quelle %s existiert bereits eine %s Weiterleitung. Bitte vorhandene Regel bearbeiten statt doppelt anzulegen.',
            $sourcePath,
            $duplicateStatus
        );
    }

    private function isDuplicateRedirectException(\Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'duplicate entry')
            || str_contains($message, 'idx_source_path')
            || str_contains($message, 'sqlstate[23000]');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAvailablePageTargets(): array
    {
        $rows = $this->db->get_results(
            "SELECT id, title, slug
             FROM {$this->prefix}pages
             WHERE status = 'published' AND slug IS NOT NULL AND slug != ''
             ORDER BY title ASC
             LIMIT 500"
        ) ?: [];

        return array_map(function ($row): array {
            $title = trim((string)($row->title ?? ''));
            $slug = trim((string)($row->slug ?? ''));

            return [
                'id' => (int)($row->id ?? 0),
                'label' => $title !== '' ? $title : $slug,
                'slug' => $slug,
                'url' => '/' . ltrim($slug, '/'),
            ];
        }, $rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAvailablePostTargets(): array
    {
        $rows = $this->db->get_results(
            "SELECT id, title, slug, published_at, created_at
             FROM {$this->prefix}posts
             WHERE status = 'published' AND slug IS NOT NULL AND slug != ''
             ORDER BY title ASC
             LIMIT 500"
        ) ?: [];

        return array_map(function ($row): array {
            $title = trim((string)($row->title ?? ''));
            $slug = trim((string)($row->slug ?? ''));

            return [
                'id' => (int)($row->id ?? 0),
                'label' => $title !== '' ? $title : $slug,
                'slug' => $slug,
                'url' => class_exists('CMS\\Services\\PermalinkService')
                    ? PermalinkService::getInstance()->buildPostPathFromValues(
                        $slug,
                        (string)($row->published_at ?? ''),
                        (string)($row->created_at ?? '')
                    )
                    : '/blog/' . ltrim($slug, '/'),
            ];
        }, $rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAvailableHubTargets(): array
    {
        $selectSlug = $this->hasSiteTableSlugColumn() ? 'table_slug,' : "'' AS table_slug,";

        $rows = $this->db->get_results(
            "SELECT id, table_name, {$selectSlug}
                    JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.hub_slug')) AS hub_slug
             FROM {$this->prefix}site_tables
             WHERE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.content_mode')), 'table') = 'hub'
             ORDER BY table_name ASC
             LIMIT 300"
        ) ?: [];

        return array_map(function ($row): array {
            $slug = trim((string)(($row->hub_slug ?? '') !== '' ? $row->hub_slug : ($row->table_slug ?? '')));
            $label = trim((string)($row->table_name ?? ''));

            return [
                'id' => (int)($row->id ?? 0),
                'label' => $label !== '' ? $label : $slug,
                'slug' => $slug,
                'url' => '/' . ltrim($slug, '/'),
            ];
        }, array_filter($rows, static function ($row): bool {
            $hubSlug = trim((string)(($row->hub_slug ?? '') !== '' ? $row->hub_slug : ($row->table_slug ?? '')));
            return $hubSlug !== '';
        }));
    }

    private function getPageTargetUrl(int $id): string
    {
        if ($id <= 0) {
            return '';
        }

        $slug = (string)$this->db->get_var(
            "SELECT slug FROM {$this->prefix}pages WHERE id = ? AND status = 'published' LIMIT 1",
            [$id]
        );

        return $slug !== '' ? '/' . ltrim($slug, '/') : '';
    }

    private function getPostTargetUrl(int $id): string
    {
        if ($id <= 0) {
            return '';
        }

        $post = $this->db->get_row(
            "SELECT slug, published_at, created_at FROM {$this->prefix}posts WHERE id = ? AND status = 'published' LIMIT 1",
            [$id]
        );

        $slug = trim((string)($post->slug ?? ''));

        if ($slug === '') {
            return '';
        }

        return class_exists('CMS\\Services\\PermalinkService')
            ? PermalinkService::getInstance()->buildPostPathFromValues(
                $slug,
                (string)($post->published_at ?? ''),
                (string)($post->created_at ?? '')
            )
            : '/blog/' . ltrim($slug, '/');
    }

    private function getHubTargetUrl(int $id): string
    {
        if ($id <= 0) {
            return '';
        }

        $selectSlug = $this->hasSiteTableSlugColumn() ? 'table_slug,' : "'' AS table_slug,";
        $row = $this->db->get_row(
            "SELECT {$selectSlug}
                    JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.hub_slug')) AS hub_slug
             FROM {$this->prefix}site_tables
             WHERE id = ?
               AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.content_mode')), 'table') = 'hub'
             LIMIT 1",
            [$id]
        );

        if (!$row) {
            return '';
        }

        $slug = trim((string)((($row->hub_slug ?? '') !== '') ? $row->hub_slug : ($row->table_slug ?? '')));

        return $slug !== '' ? '/' . ltrim($slug, '/') : '';
    }

    private function hasSiteTableSlugColumn(): bool
    {
        if ($this->hasSiteTableSlugColumn !== null) {
            return $this->hasSiteTableSlugColumn;
        }

        try {
            $column = $this->db->get_var("SHOW COLUMNS FROM {$this->prefix}site_tables LIKE 'table_slug'");
            $this->hasSiteTableSlugColumn = $column !== null;
        } catch (\Throwable) {
            $this->hasSiteTableSlugColumn = false;
        }

        return $this->hasSiteTableSlugColumn;
    }

    private function limitString(string $value, int $length): string
    {
        return mb_substr(trim($value), 0, $length);
    }

    private function normalizeSlugFilter(string $slug): string
    {
        $slug = trim($slug);
        $slug = trim($slug, " \t\n\r\0\x0B/");
        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9\-_]+/i', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug;
    }

    private function pathContainsSlugSegment(string $path, string $slug): bool
    {
        $normalizedPath = trim($this->normalizePath($path), '/');
        if ($normalizedPath === '') {
            return false;
        }

        $segments = array_values(array_filter(explode('/', strtolower($normalizedPath)), static fn(string $segment): bool => $segment !== ''));
        return in_array(strtolower($slug), $segments, true);
    }
}
