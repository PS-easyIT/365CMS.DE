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
                    rr.target_url,
                    rr.redirect_type,
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
            'logs' => array_map(static fn($row) => (array)$row, $logs),
            'stats' => $stats,
        ];
    }

    public function saveRedirect(array $post): array
    {
        $id = (int)($post['redirect_id'] ?? 0);
        $source = $this->normalizePath((string)($post['source_path'] ?? ''));
        $target = trim((string)($post['target_url'] ?? ''));
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

    private function isValidTarget(string $target): bool
    {
        return str_starts_with($target, '/') || filter_var($target, FILTER_VALIDATE_URL) !== false;
    }

    private function limitString(string $value, int $length): string
    {
        return mb_substr(trim($value), 0, $length);
    }
}
