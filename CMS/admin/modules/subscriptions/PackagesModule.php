<?php
declare(strict_types=1);

/**
 * PackagesModule – Abo-Pakete verwalten (CRUD)
 */

if (!defined('ABSPATH')) {
    exit;
}

class PackagesModule
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
        $table = $this->prefix . 'subscription_packages';
        $exists = $this->db->get_var("SHOW TABLES LIKE '{$table}'");
        if (!$exists) {
            $this->db->getPdo()->exec("CREATE TABLE {$table} (
                id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name        VARCHAR(100) NOT NULL,
                slug        VARCHAR(100) NOT NULL,
                description TEXT DEFAULT NULL,
                price       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                currency    VARCHAR(3) NOT NULL DEFAULT 'EUR',
                duration    INT UNSIGNED NOT NULL DEFAULT 30 COMMENT 'Laufzeit in Tagen',
                features    TEXT DEFAULT NULL COMMENT 'JSON-Array der Features',
                max_users   INT UNSIGNED DEFAULT NULL,
                sort_order  INT UNSIGNED NOT NULL DEFAULT 0,
                is_active   TINYINT(1) NOT NULL DEFAULT 1,
                is_featured TINYINT(1) NOT NULL DEFAULT 0,
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY idx_slug (slug),
                INDEX idx_active (is_active),
                INDEX idx_sort (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
    }

    public function getData(): array
    {
        $table    = $this->prefix . 'subscription_packages';
        $packages = $this->db->get_results("SELECT * FROM {$table} ORDER BY sort_order ASC, id ASC") ?: [];

        // Subscriber-Counts
        $subTable = $this->prefix . 'subscriptions';
        $hasSubs  = (bool)$this->db->get_var("SHOW TABLES LIKE '{$subTable}'");
        foreach ($packages as &$pkg) {
            $pkg->features_list = !empty($pkg->features) ? json_decode($pkg->features, true) : [];
            $pkg->subscriber_count = 0;
            if ($hasSubs) {
                $pkg->subscriber_count = (int)$this->db->get_var(
                    "SELECT COUNT(*) FROM {$subTable} WHERE package_id = ? AND status = 'active'",
                    [(int)$pkg->id]
                );
            }
        }

        $stats = [
            'total'    => count($packages),
            'active'   => count(array_filter($packages, fn($p) => (int)$p->is_active === 1)),
            'featured' => count(array_filter($packages, fn($p) => (int)$p->is_featured === 1)),
        ];

        return ['packages' => array_map(fn($p) => (array)$p, $packages), 'stats' => $stats];
    }

    public function save(array $post): array
    {
        $id          = (int)($post['id'] ?? 0);
        $name        = trim($post['name'] ?? '');
        $slug        = trim($post['slug'] ?? '');
        $description = trim($post['description'] ?? '');
        $price       = max(0, (float)($post['price'] ?? 0));
        $currency    = strtoupper(trim($post['currency'] ?? 'EUR'));
        $duration    = max(1, (int)($post['duration'] ?? 30));
        $maxUsers    = !empty($post['max_users']) ? (int)$post['max_users'] : null;
        $sortOrder   = max(0, (int)($post['sort_order'] ?? 0));
        $isActive    = isset($post['is_active']) ? 1 : 0;
        $isFeatured  = isset($post['is_featured']) ? 1 : 0;

        // Features aus Textarea (eine pro Zeile)
        $featuresRaw = trim($post['features'] ?? '');
        $features    = $featuresRaw !== '' ? json_encode(array_values(array_filter(array_map('trim', explode("\n", $featuresRaw))))) : null;

        if ($name === '') {
            return ['success' => false, 'error' => 'Paketname ist erforderlich.'];
        }
        if ($slug === '') {
            $slug = preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', strtolower($name))) ?? '';
        }
        if (!in_array($currency, ['EUR', 'USD', 'CHF', 'GBP'], true)) {
            $currency = 'EUR';
        }

        $table = $this->prefix . 'subscription_packages';
        $data  = [
            'name'        => $name,
            'slug'        => $slug,
            'description' => $description,
            'price'       => $price,
            'currency'    => $currency,
            'duration'    => $duration,
            'features'    => $features,
            'max_users'   => $maxUsers,
            'sort_order'  => $sortOrder,
            'is_active'   => $isActive,
            'is_featured' => $isFeatured,
        ];

        try {
            if ($id > 0) {
                $this->db->update('subscription_packages', $data, ['id' => $id]);
                return ['success' => true, 'message' => 'Paket aktualisiert.'];
            } else {
                $this->db->insert('subscription_packages', $data);
                return ['success' => true, 'message' => 'Paket erstellt.'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    public function delete(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige ID.'];
        }
        $table = $this->prefix . 'subscription_packages';
        $this->db->delete('subscription_packages', ['id' => $id]);
        return ['success' => true, 'message' => 'Paket gelöscht.'];
    }

    public function toggleStatus(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige ID.'];
        }
        $table   = $this->prefix . 'subscription_packages';
        $current = $this->db->get_row("SELECT is_active FROM {$table} WHERE id = ?", [$id]);
        if (!$current) {
            return ['success' => false, 'error' => 'Paket nicht gefunden.'];
        }
        $newStatus = (int)$current->is_active === 1 ? 0 : 1;
        $this->db->update('subscription_packages', ['is_active' => $newStatus], ['id' => $id]);
        return ['success' => true, 'message' => $newStatus ? 'Paket aktiviert.' : 'Paket deaktiviert.'];
    }
}
