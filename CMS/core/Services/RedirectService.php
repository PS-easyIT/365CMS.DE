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
                site_scope VARCHAR(255) NOT NULL DEFAULT '',
                target_url VARCHAR(500) NOT NULL,
                redirect_type SMALLINT NOT NULL DEFAULT 301,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                notes TEXT DEFAULT NULL,
                hits INT UNSIGNED NOT NULL DEFAULT 0,
                last_hit_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY idx_source_path_scope (source_path, site_scope),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->getPdo()->exec(
            "CREATE TABLE IF NOT EXISTS {$this->prefix}not_found_logs (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                request_path VARCHAR(255) NOT NULL,
                request_host VARCHAR(255) NOT NULL DEFAULT '',
                request_method VARCHAR(10) NOT NULL DEFAULT 'GET',
                referrer_url VARCHAR(500) DEFAULT NULL,
                ip_address VARCHAR(64) DEFAULT NULL,
                user_agent VARCHAR(500) DEFAULT NULL,
                hit_count INT UNSIGNED NOT NULL DEFAULT 1,
                first_seen_at DATETIME NOT NULL,
                last_seen_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY idx_request_path_host (request_path, request_host),
                INDEX idx_last_seen (last_seen_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->ensureColumnExists('redirect_rules', 'site_scope', "VARCHAR(255) NOT NULL DEFAULT '' AFTER source_path");
        $this->ensureColumnExists('not_found_logs', 'request_host', "VARCHAR(255) NOT NULL DEFAULT '' AFTER request_path");
        $this->ensureRedirectIndexes();
    }

    public function findRedirect(string $path, string $requestHost = ''): ?array
    {
        $normalized = $this->normalizePath($path);
        if ($this->shouldIgnorePath($normalized)) {
            return null;
        }

        $requestHost = $this->normalizeHost($requestHost);
        $candidates = $this->db->get_results(
            "SELECT * FROM {$this->prefix}redirect_rules WHERE source_path = ? AND is_active = 1",
            [$normalized]
        ) ?: [];

        $bestMatch = null;
        $bestScore = -1;
        foreach ($candidates as $candidate) {
            $score = $this->getRedirectSiteScore((array)$candidate, $normalized, $requestHost);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $candidate;
            }
        }

        if (!$bestMatch || $bestScore < 0) {
            return null;
        }

        $this->db->execute(
            "UPDATE {$this->prefix}redirect_rules SET hits = hits + 1, last_hit_at = NOW() WHERE id = ?",
            [(int)$bestMatch->id]
        );

        return [
            'id' => (int)$bestMatch->id,
            'source_path' => (string)$bestMatch->source_path,
            'site_scope' => (string)($bestMatch->site_scope ?? ''),
            'target_url' => (string)$bestMatch->target_url,
            'redirect_type' => (int)$bestMatch->redirect_type,
        ];
    }

    public function logNotFound(string $path, array $context = []): void
    {
        $normalized = $this->normalizePath($path);
        if ($this->shouldIgnorePath($normalized)) {
            return;
        }

        $requestHost = $this->normalizeHost((string)($context['request_host'] ?? ''));

        $existing = $this->db->get_row(
            "SELECT id, hit_count FROM {$this->prefix}not_found_logs WHERE request_path = ? AND request_host = ? LIMIT 1",
            [$normalized, $requestHost]
        );

        if ($existing) {
            $this->db->execute(
                "UPDATE {$this->prefix}not_found_logs
                 SET hit_count = hit_count + 1,
                     request_host = ?,
                     request_method = ?,
                     referrer_url = ?,
                     ip_address = ?,
                     user_agent = ?,
                     last_seen_at = NOW()
                 WHERE id = ?",
                [
                    $requestHost,
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
            'request_host' => $requestHost,
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
            "SELECT *
             FROM {$this->prefix}not_found_logs
             ORDER BY last_seen_at DESC
             LIMIT 200"
        ) ?: [];

        $redirectRows = array_map(fn($row): array => (array)$row, $redirects);
        $logRows = array_map(fn($row): array => $this->enrichLogWithRedirect((array)$row, $redirectRows), $logs);
        $siteScopes = $this->getAvailableSiteScopes();

        $stats = [
            'redirects_total' => count($redirects),
            'redirects_active' => count(array_filter($redirects, static fn($row) => (int)$row->is_active === 1)),
            'not_found_total' => count($logRows),
            'not_found_hits' => array_sum(array_map(static fn($row) => (int)$row['hit_count'], $logRows)),
        ];

        return [
            'redirects' => array_map(function (array $row): array {
                $row['site_scope'] = (string)($row['site_scope'] ?? '');
                $row['site_scope_label'] = $this->describeSiteScope((string)($row['site_scope'] ?? ''));
                return $row;
            }, $redirectRows),
            'logs' => $logRows,
            'stats' => $stats,
            'targets' => [
                'pages' => $this->getAvailablePageTargets(),
                'posts' => $this->getAvailablePostTargets(),
                'hubs' => $this->getAvailableHubTargets(),
            ],
            'sites' => $siteScopes,
        ];
    }

    public function saveRedirect(array $post): array
    {
        $id = (int)($post['redirect_id'] ?? 0);
        $source = $this->normalizePath((string)($post['source_path'] ?? ''));
        $siteScope = $this->normalizeSiteScopeInput((string)($post['site_scope'] ?? ''));
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

        $duplicateRedirect = $this->findRedirectBySourcePath($source, $siteScope);
        if ($duplicateRedirect !== null && (int)($duplicateRedirect['id'] ?? 0) !== $id) {
            return ['success' => false, 'error' => $this->buildDuplicateSourceError($source, $siteScope)];
        }

        $data = [
            'source_path' => $source,
            'site_scope' => $siteScope,
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
                return ['success' => false, 'error' => $this->buildDuplicateSourceError($source, $siteScope)];
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
                "SELECT id FROM {$this->prefix}redirect_rules WHERE source_path = ? AND site_scope = ? LIMIT 1",
                [$source, '']
            );

            $payload = [
                'source_path'   => $source,
                'site_scope'    => '',
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

    private function normalizeHost(string $host): string
    {
        $host = trim(strtolower($host));
        if ($host === '') {
            return '';
        }

        if (str_contains($host, ':')) {
            $host = explode(':', $host, 2)[0];
        }

        return trim($host, '.');
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

    private function normalizeSiteScopeInput(string $value): string
    {
        $value = trim($value);
        if ($value === '' || $value === 'global') {
            return '';
        }

        if (str_starts_with($value, 'host:')) {
            $host = $this->normalizeHost(substr($value, 5));
            return $host !== '' ? 'host:' . $host : '';
        }

        if (str_starts_with($value, 'path:')) {
            $path = $this->normalizePath(substr($value, 5));
            return $path !== '' && $path !== '/' ? 'path:' . $path : '';
        }

        if (preg_match('#^https?://#i', $value) === 1 || str_contains($value, '.')) {
            $host = $this->normalizeHost((string)(parse_url(preg_match('#^https?://#i', $value) === 1 ? $value : 'https://' . $value, PHP_URL_HOST) ?? $value));
            return $host !== '' ? 'host:' . $host : '';
        }

        $path = $this->normalizePath($value);
        return $path !== '' && $path !== '/' ? 'path:' . $path : '';
    }

    private function findRedirectBySourcePath(string $sourcePath, string $siteScope = ''): ?array
    {
        if ($sourcePath === '') {
            return null;
        }

        $siteScope = $this->normalizeSiteScopeInput($siteScope);

        $row = $this->db->get_row(
            "SELECT id, source_path, site_scope, target_url, is_active
             FROM {$this->prefix}redirect_rules
             WHERE source_path = ?
               AND site_scope = ?
             LIMIT 1",
            [$sourcePath, $siteScope]
        );

        return $row ? (array)$row : null;
    }

    private function getRedirectSiteScore(array $redirect, string $requestPath, string $requestHost): int
    {
        $siteScope = $this->normalizeSiteScopeInput((string)($redirect['site_scope'] ?? ''));
        if ($siteScope === '') {
            return 10;
        }

        if (str_starts_with($siteScope, 'host:')) {
            return $requestHost !== '' && $this->normalizeHost(substr($siteScope, 5)) === $requestHost ? 100 : -1;
        }

        if (str_starts_with($siteScope, 'path:')) {
            $scopePath = $this->normalizePath(substr($siteScope, 5));
            if ($scopePath === '' || $scopePath === '/') {
                return -1;
            }

            if ($requestPath === $scopePath || str_starts_with($requestPath, $scopePath . '/')) {
                return 80 + strlen($scopePath);
            }
        }

        return -1;
    }

    private function enrichLogWithRedirect(array $log, array $redirects): array
    {
        $requestPath = $this->normalizePath((string)($log['request_path'] ?? ''));
        $requestHost = $this->normalizeHost((string)($log['request_host'] ?? ''));
        $bestMatch = null;
        $bestScore = -1;

        foreach ($redirects as $redirect) {
            if ($this->normalizePath((string)($redirect['source_path'] ?? '')) !== $requestPath) {
                continue;
            }

            $score = $this->getRedirectSiteScore($redirect, $requestPath, $requestHost);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $redirect;
            }
        }

        $log['redirect_id'] = (int)($bestMatch['id'] ?? 0);
        $log['target_url'] = (string)($bestMatch['target_url'] ?? '');
        $log['redirect_type'] = (int)($bestMatch['redirect_type'] ?? 0);
        $log['redirect_notes'] = (string)($bestMatch['notes'] ?? '');
        $log['redirect_is_active'] = (int)($bestMatch['is_active'] ?? 0);
        $log['site_scope_match'] = (string)($bestMatch['site_scope'] ?? '');
        $log['request_host'] = $requestHost;
        $log['request_host_label'] = $requestHost !== '' ? $requestHost : 'Hauptsite / unbekannter Host';
        $log['site_scope_suggestion'] = $this->suggestSiteScopeForRequest($requestPath, $requestHost);

        return $log;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getAvailableSiteScopes(): array
    {
        $options = [
            [
                'value' => '',
                'label' => 'Global / alle Sites',
                'description' => 'Regel gilt host- und siteübergreifend.',
            ],
        ];

        $mainHost = $this->normalizeHost((string)(parse_url((string)SITE_URL, PHP_URL_HOST) ?? ''));
        if ($mainHost !== '') {
            $options[] = [
                'value' => 'host:' . $mainHost,
                'label' => 'Hauptsite (' . $mainHost . ')',
                'description' => 'Nur für die primäre Domain.',
            ];
        }

        $seen = array_fill_keys(array_map(static fn(array $option): string => $option['value'], $options), true);
        foreach ($this->loadHubSiteScopeOptions() as $option) {
            $value = (string)($option['value'] ?? '');
            if ($value === '' || isset($seen[$value])) {
                continue;
            }

            $seen[$value] = true;
            $options[] = $option;
        }

        return $options;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function loadHubSiteScopeOptions(): array
    {
        $selectSlug = $this->hasSiteTableSlugColumn() ? 'table_slug,' : "'' AS table_slug,";
        $rows = $this->db->get_results(
            "SELECT table_name, {$selectSlug} settings_json,
                    JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.hub_slug')) AS hub_slug
             FROM {$this->prefix}site_tables
             WHERE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.content_mode')), 'table') = 'hub'
             ORDER BY table_name ASC"
        ) ?: [];

        $options = [];
        foreach ($rows as $row) {
            $label = trim((string)($row->table_name ?? 'Hub-Site'));
            $slug = trim((string)((($row->hub_slug ?? '') !== '') ? $row->hub_slug : ($row->table_slug ?? '')));
            if ($slug !== '') {
                $options[] = [
                    'value' => 'path:/' . ltrim($slug, '/'),
                    'label' => $label . ' (Pfad /' . ltrim($slug, '/') . ')',
                    'description' => 'Greift für Pfade unterhalb dieser Hub-Site.',
                ];
            }

            $settings = json_decode((string)($row->settings_json ?? ''), true);
            $domains = is_array($settings['hub_domains'] ?? null) ? $settings['hub_domains'] : [];
            foreach ($domains as $domain) {
                $host = $this->normalizeHost((string)$domain);
                if ($host === '') {
                    continue;
                }

                $options[] = [
                    'value' => 'host:' . $host,
                    'label' => $label . ' (' . $host . ')',
                    'description' => 'Nur für die zugewiesene Hub-Domain.',
                ];
            }
        }

        return $options;
    }

    private function suggestSiteScopeForRequest(string $requestPath, string $requestHost): string
    {
        foreach ($this->getAvailableSiteScopes() as $option) {
            $value = (string)($option['value'] ?? '');
            if ($value === '') {
                continue;
            }

            if ($this->getRedirectSiteScore(['site_scope' => $value], $requestPath, $requestHost) >= 0) {
                return $value;
            }
        }

        return $requestHost !== '' ? 'host:' . $requestHost : '';
    }

    private function describeSiteScope(string $siteScope): string
    {
        $siteScope = $this->normalizeSiteScopeInput($siteScope);
        if ($siteScope === '') {
            return 'Global / alle Sites';
        }

        if (str_starts_with($siteScope, 'host:')) {
            return 'Host: ' . substr($siteScope, 5);
        }

        if (str_starts_with($siteScope, 'path:')) {
            return 'Pfadbereich: ' . substr($siteScope, 5);
        }

        return $siteScope;
    }

    private function ensureColumnExists(string $table, string $column, string $definition): void
    {
        try {
            $exists = $this->db->get_var("SHOW COLUMNS FROM {$this->prefix}{$table} LIKE '{$column}'");
            if ($exists === null) {
                $this->db->getPdo()->exec("ALTER TABLE {$this->prefix}{$table} ADD COLUMN {$column} {$definition}");
            }
        } catch (\Throwable) {
            // Schema-Upgrade darf bestehende Instanzen nicht blockieren.
        }
    }

    private function ensureRedirectIndexes(): void
    {
        try {
            $indexes = $this->db->get_results("SHOW INDEX FROM {$this->prefix}redirect_rules") ?: [];
            $hasComposite = false;
            $hasLegacy = false;

            foreach ($indexes as $index) {
                $keyName = (string)($index->Key_name ?? '');
                if ($keyName === 'idx_source_path_scope') {
                    $hasComposite = true;
                }
                if ($keyName === 'idx_source_path') {
                    $hasLegacy = true;
                }
            }

            if ($hasLegacy) {
                $this->db->getPdo()->exec("ALTER TABLE {$this->prefix}redirect_rules DROP INDEX idx_source_path");
            }

            if (!$hasComposite) {
                $this->db->getPdo()->exec("ALTER TABLE {$this->prefix}redirect_rules ADD UNIQUE INDEX idx_source_path_scope (source_path, site_scope)");
            }
        } catch (\Throwable) {
            // Bestehende Installationen dürfen auch dann weiterlaufen, wenn ein Index-Upgrade scheitert.
        }
    }

    private function buildDuplicateSourceError(string $sourcePath, string $siteScope = ''): string
    {
        $siteScope = $this->normalizeSiteScopeInput($siteScope);
        $duplicateRedirect = $this->findRedirectBySourcePath($sourcePath, $siteScope);
        if ($duplicateRedirect === null) {
            return sprintf(
                'Für die Quelle %s existiert bereits eine Weiterleitung%s. Bitte vorhandene Regel bearbeiten statt doppelt anzulegen.',
                $sourcePath,
                $siteScope !== '' ? ' für ' . $this->describeSiteScope($siteScope) : ''
            );
        }

        $duplicateTarget = trim((string)($duplicateRedirect['target_url'] ?? ''));
        $duplicateStatus = (int)($duplicateRedirect['is_active'] ?? 0) === 1 ? 'aktive' : 'inaktive';
        $duplicateScope = $this->describeSiteScope((string)($duplicateRedirect['site_scope'] ?? $siteScope));

        if ($duplicateTarget !== '') {
            return sprintf(
                'Für die Quelle %s existiert bereits eine %s Weiterleitung für %s nach %s. Bitte vorhandene Regel bearbeiten statt doppelt anzulegen.',
                $sourcePath,
                $duplicateStatus,
                $duplicateScope,
                $duplicateTarget
            );
        }

        return sprintf(
            'Für die Quelle %s existiert bereits eine %s Weiterleitung für %s. Bitte vorhandene Regel bearbeiten statt doppelt anzulegen.',
            $sourcePath,
            $duplicateStatus,
            $duplicateScope
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
