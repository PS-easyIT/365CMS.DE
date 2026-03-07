<?php
declare(strict_types=1);

/**
 * CookieManagerModule – Cookie-Consent-Kategorien & Einstellungen
 */

if (!defined('ABSPATH')) {
    exit;
}

class CookieManagerModule
{
    private readonly \CMS\Database $db;
    private readonly string $prefix;

    public function __construct()
    {
        $this->db     = \CMS\Database::instance();
        $this->prefix = $this->db->getPrefix();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $this->db->getPdo()->exec(
            "CREATE TABLE IF NOT EXISTS {$this->prefix}cookie_categories (
                id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name        VARCHAR(100) NOT NULL,
                slug        VARCHAR(100) NOT NULL,
                description TEXT,
                is_required TINYINT(1) NOT NULL DEFAULT 0,
                is_active   TINYINT(1) NOT NULL DEFAULT 1,
                scripts     TEXT,
                sort_order  INT NOT NULL DEFAULT 0,
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY idx_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function getData(): array
    {
        $categories = $this->db->get_results(
            "SELECT * FROM {$this->prefix}cookie_categories ORDER BY sort_order, name"
        ) ?: [];

        $settingKeys = ['cookie_banner_enabled', 'cookie_banner_position', 'cookie_banner_text', 'cookie_banner_style', 'cookie_lifetime_days'];
        $placeholders = implode(',', array_fill(0, count($settingKeys), '?'));
        $rows = $this->db->get_results(
            "SELECT option_name, option_value FROM {$this->prefix}settings WHERE option_name IN ({$placeholders})",
            $settingKeys
        ) ?: [];
        $settings = array_fill_keys($settingKeys, '');
        foreach ($rows as $row) {
            $settings[$row->option_name] = $row->option_value;
        }

        return [
            'categories' => array_map(fn($c) => (array)$c, $categories),
            'settings'   => $settings,
        ];
    }

    public function saveSettings(array $post): array
    {
        $keys = [
            'cookie_banner_enabled'  => isset($post['cookie_banner_enabled']) ? '1' : '0',
            'cookie_banner_position' => in_array($post['cookie_banner_position'] ?? '', ['bottom', 'top', 'center'], true)
                ? $post['cookie_banner_position'] : 'bottom',
            'cookie_banner_text'     => strip_tags($post['cookie_banner_text'] ?? '', '<p><a><strong><em><br>'),
            'cookie_banner_style'    => in_array($post['cookie_banner_style'] ?? '', ['light', 'dark', 'custom'], true)
                ? $post['cookie_banner_style'] : 'dark',
            'cookie_lifetime_days'   => (string)max(1, min(365, (int)($post['cookie_lifetime_days'] ?? 30))),
        ];

        try {
            foreach ($keys as $key => $value) {
                $exists = $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}settings WHERE option_name = ?", [$key]);
                if ($exists) {
                    $this->db->update('settings', ['option_value' => $value], ['option_name' => $key]);
                } else {
                    $this->db->insert('settings', ['option_name' => $key, 'option_value' => $value]);
                }
            }
            return ['success' => true, 'message' => 'Cookie-Einstellungen gespeichert.'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    public function saveCategory(array $post): array
    {
        $id   = (int)($post['category_id'] ?? 0);
        $name = trim($post['category_name'] ?? '');
        if ($name === '') {
            return ['success' => false, 'error' => 'Name ist erforderlich.'];
        }

        $slug = $post['category_slug'] ?? '';
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $slug ?: $name))) ?? '';

        $data = [
            'name'        => $name,
            'slug'        => $slug,
            'description' => strip_tags($post['category_description'] ?? ''),
            'is_required' => isset($post['is_required']) ? 1 : 0,
            'is_active'   => isset($post['is_active']) ? 1 : 0,
            'scripts'     => strip_tags($post['category_scripts'] ?? '', '<script><noscript>'),
            'sort_order'  => (int)($post['sort_order'] ?? 0),
        ];

        try {
            if ($id > 0) {
                $this->db->update('cookie_categories', $data, ['id' => $id]);
                return ['success' => true, 'message' => 'Kategorie aktualisiert.'];
            }
            $this->db->insert('cookie_categories', $data);
            return ['success' => true, 'message' => 'Kategorie erstellt.'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    public function deleteCategory(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige ID.'];
        }
        $cat = $this->db->get_row(
            "SELECT is_required FROM {$this->prefix}cookie_categories WHERE id = ?",
            [$id]
        );
        if ($cat && (int)$cat->is_required === 1) {
            return ['success' => false, 'error' => 'Pflicht-Kategorien können nicht gelöscht werden.'];
        }
        $this->db->delete('cookie_categories', ['id' => $id]);
        return ['success' => true, 'message' => 'Kategorie gelöscht.'];
    }
}
