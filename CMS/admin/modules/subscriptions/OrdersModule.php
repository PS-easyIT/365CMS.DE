<?php
declare(strict_types=1);

/**
 * OrdersModule – Bestellungen verwalten
 */

if (!defined('ABSPATH')) {
    exit;
}

class OrdersModule
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
        $table = $this->prefix . 'orders';
        $exists = $this->db->get_var("SHOW TABLES LIKE '{$table}'");
        if (!$exists) {
            $this->db->getPdo()->exec("CREATE TABLE {$table} (
                id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_number  VARCHAR(50) NOT NULL,
                user_id       INT UNSIGNED DEFAULT NULL,
                package_id    INT UNSIGNED DEFAULT NULL,
                customer_name VARCHAR(200) DEFAULT NULL,
                customer_email VARCHAR(200) DEFAULT NULL,
                amount        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                tax_amount    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                total_amount  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                currency      VARCHAR(3) NOT NULL DEFAULT 'EUR',
                status        VARCHAR(20) NOT NULL DEFAULT 'pending',
                payment_method VARCHAR(30) DEFAULT NULL,
                payment_ref   VARCHAR(200) DEFAULT NULL,
                notes         TEXT DEFAULT NULL,
                created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_status (status),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
    }

    public function getData(string $statusFilter = ''): array
    {
        $table = $this->prefix . 'orders';
        $where = '';
        $params = [];

        if ($statusFilter !== '' && in_array($statusFilter, ['pending', 'paid', 'cancelled', 'refunded', 'failed'], true)) {
            $where = ' WHERE o.status = ?';
            $params[] = $statusFilter;
        }

        $orders = $this->db->get_results(
            "SELECT o.*, u.username, u.email as user_email
             FROM {$table} o
             LEFT JOIN {$this->prefix}users u ON o.user_id = u.id
             {$where}
             ORDER BY o.created_at DESC
             LIMIT 200",
            $params
        ) ?: [];

        // Stats
        $stats = [
            'total'     => (int)$this->db->get_var("SELECT COUNT(*) FROM {$table}"),
            'pending'   => (int)$this->db->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'pending'"),
            'paid'      => (int)$this->db->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'paid'"),
            'revenue'   => (float)$this->db->get_var("SELECT COALESCE(SUM(total_amount), 0) FROM {$table} WHERE status = 'paid'"),
            'cancelled' => (int)$this->db->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'cancelled'"),
        ];

        return ['orders' => array_map(fn($r) => (array)$r, $orders), 'stats' => $stats, 'filter' => $statusFilter];
    }

    public function updateStatus(int $id, string $status): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige ID.'];
        }
        if (!in_array($status, ['pending', 'paid', 'cancelled', 'refunded', 'failed'], true)) {
            return ['success' => false, 'error' => 'Ungültiger Status.'];
        }
        $table = $this->prefix . 'orders';
        $this->db->update('orders', ['status' => $status], ['id' => $id]);
        return ['success' => true, 'message' => 'Status aktualisiert.'];
    }

    public function delete(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige ID.'];
        }
        $table = $this->prefix . 'orders';
        $this->db->delete('orders', ['id' => $id]);
        return ['success' => true, 'message' => 'Bestellung gelöscht.'];
    }
}
