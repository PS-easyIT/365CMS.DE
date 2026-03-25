<?php
declare(strict_types=1);

/**
 * OrdersModule – Bestellungen verwalten
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\AuditLogger;
use CMS\Auth;
use CMS\Logger;

final class OrdersDashboardData
{
    public function __construct(
        private array $orders,
        private array $stats,
        private string $filter,
        private array $assignments,
        private array $plans,
        private array $users,
    ) {
    }

    public function toArray(): array
    {
        return [
            'orders' => $this->orders,
            'stats' => $this->stats,
            'filter' => $this->filter,
            'assignments' => $this->assignments,
            'plans' => $this->plans,
            'users' => $this->users,
        ];
    }
}

final class OrdersActionResult
{
    private function __construct(
        private bool $success,
        private string $message,
    ) {
    }

    public static function success(string $message): self
    {
        return new self(true, $message);
    }

    public static function failure(string $message): self
    {
        return new self(false, $message);
    }

    public function toArray(): array
    {
        return $this->success
            ? ['success' => true, 'message' => $this->message]
            : ['success' => false, 'error' => $this->message];
    }
}

class OrdersModule
{
    private const array ALLOWED_STATUSES = ['pending', 'paid', 'cancelled', 'refunded', 'failed'];
    private const array ALLOWED_BILLING_CYCLES = ['monthly', 'yearly', 'lifetime'];
    private const int MAX_ORDERS = 200;
    private const int MAX_ASSIGNMENTS = 150;
    private const int MAX_USERS = 500;

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
                plan_id       INT UNSIGNED DEFAULT NULL,
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
            return;
        }

        if (!$this->hasColumn($table, 'plan_id') && !$this->hasColumn($table, 'package_id')) {
            $this->db->getPdo()->exec("ALTER TABLE {$table} ADD COLUMN plan_id INT UNSIGNED DEFAULT NULL AFTER user_id");
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return $this->db->get_var("SHOW COLUMNS FROM {$table} LIKE '{$column}'") !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    private function getOrderPlanColumn(string $table): ?string
    {
        if ($this->hasColumn($table, 'plan_id')) {
            return 'plan_id';
        }

        if ($this->hasColumn($table, 'package_id')) {
            return 'package_id';
        }

        return null;
    }

    public function getData(string $statusFilter = ''): OrdersDashboardData
    {
        if (!$this->canAccess()) {
            return new OrdersDashboardData([], $this->defaultStats(), '', [], [], []);
        }

        $table = $this->prefix . 'orders';
        $planColumn = $this->getOrderPlanColumn($table);
        $statusFilter = $this->normalizeStatus($statusFilter);

        return new OrdersDashboardData(
            $this->mapRows($this->fetchOrders($table, $planColumn, $statusFilter)),
            $this->buildStats($table),
            $statusFilter,
            $this->mapRows($this->fetchAssignments()),
            $this->mapRows($this->fetchPlans()),
            $this->mapRows($this->fetchUsers()),
        );
    }

    public function assignSubscription(int $userId, int $planId, string $billingCycle): OrdersActionResult
    {
        if (!$this->canAccess()) {
            return OrdersActionResult::failure('Zugriff verweigert.');
        }

        if ($userId <= 0 || $planId <= 0) {
            return OrdersActionResult::failure('Benutzer und Paket sind erforderlich.');
        }

        $billingCycle = $this->normalizeBillingCycle($billingCycle);

        try {
            $userExists = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}users WHERE id = ?", [$userId]);
            $planExists = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}subscription_plans WHERE id = ?", [$planId]);
            if ($userExists === 0 || $planExists === 0) {
                return OrdersActionResult::failure('Benutzer oder Paket wurde nicht gefunden.');
            }

            $success = \CMS\SubscriptionManager::instance()->assignSubscription($userId, $planId, $billingCycle);

            AuditLogger::instance()->log(
                AuditLogger::CAT_SYSTEM,
                $success ? 'subscription.assignment.created' : 'subscription.assignment.failed',
                $success
                    ? "Abonnement {$billingCycle} für Benutzer #{$userId} erstellt."
                    : "Abonnement {$billingCycle} für Benutzer #{$userId} konnte nicht erstellt werden.",
                'subscription',
                $planId,
                [
                    'user_id' => $userId,
                    'plan_id' => $planId,
                    'billing_cycle' => $billingCycle,
                ],
                $success ? 'info' : 'warning'
            );

            return $success
                ? OrdersActionResult::success('Paket wurde dem Benutzer zugewiesen.')
                : OrdersActionResult::failure('Die Zuweisung konnte nicht gespeichert werden.');
        } catch (\Throwable $e) {
            return $this->failResult('orders.assign_subscription_failed', 'Die Zuweisung konnte nicht gespeichert werden.', $e, [
                'user_id' => $userId,
                'plan_id' => $planId,
                'billing_cycle' => $billingCycle,
            ]);
        }
    }

    public function updateStatus(int $id, string $status): OrdersActionResult
    {
        if (!$this->canAccess()) {
            return OrdersActionResult::failure('Zugriff verweigert.');
        }

        if ($id <= 0) {
            return OrdersActionResult::failure('Ungültige ID.');
        }

        $status = $this->normalizeStatus($status);
        if ($status === '') {
            return OrdersActionResult::failure('Ungültiger Status.');
        }

        try {
            $order = $this->getOrderSnapshot($id);
            if ($order === null) {
                return OrdersActionResult::failure('Bestellung wurde nicht gefunden.');
            }

            if (($order['status'] ?? '') === $status) {
                return OrdersActionResult::success('Status war bereits gesetzt.');
            }

            $updated = $this->db->update('orders', ['status' => $status], ['id' => $id]);
            if (!$updated) {
                return OrdersActionResult::failure('Status konnte nicht aktualisiert werden.');
            }

            AuditLogger::instance()->log(
                AuditLogger::CAT_SYSTEM,
                'order.status.updated',
                "Bestellstatus für Bestellung #{$id} geändert.",
                'order',
                $id,
                [
                    'order_number' => $this->maskOrderNumber((string)($order['order_number'] ?? '')),
                    'from_status' => (string)($order['status'] ?? ''),
                    'to_status' => $status,
                    'customer_email' => $this->maskEmail((string)($order['customer_email'] ?? '')),
                ],
                'info'
            );

            return OrdersActionResult::success('Status aktualisiert.');
        } catch (\Throwable $e) {
            return $this->failResult('orders.update_status_failed', 'Status konnte nicht aktualisiert werden.', $e, [
                'order_id' => $id,
                'status' => $status,
            ]);
        }
    }

    public function delete(int $id): OrdersActionResult
    {
        if (!$this->canAccess()) {
            return OrdersActionResult::failure('Zugriff verweigert.');
        }

        if ($id <= 0) {
            return OrdersActionResult::failure('Ungültige ID.');
        }

        try {
            $order = $this->getOrderSnapshot($id);
            if ($order === null) {
                return OrdersActionResult::failure('Bestellung wurde nicht gefunden.');
            }

            $deleted = $this->db->delete('orders', ['id' => $id]);
            if (!$deleted) {
                return OrdersActionResult::failure('Bestellung konnte nicht gelöscht werden.');
            }

            AuditLogger::instance()->log(
                AuditLogger::CAT_SYSTEM,
                'order.deleted',
                "Bestellung #{$id} gelöscht.",
                'order',
                $id,
                [
                    'order_number' => $this->maskOrderNumber((string)($order['order_number'] ?? '')),
                    'status' => (string)($order['status'] ?? ''),
                    'customer_email' => $this->maskEmail((string)($order['customer_email'] ?? '')),
                ],
                'warning'
            );

            return OrdersActionResult::success('Bestellung gelöscht.');
        } catch (\Throwable $e) {
            return $this->failResult('orders.delete_failed', 'Bestellung konnte nicht gelöscht werden.', $e, [
                'order_id' => $id,
            ]);
        }
    }

    /**
     * @return list<object>
     */
    private function fetchOrders(string $table, ?string $planColumn, string $statusFilter): array
    {
        $where = '';
        $params = [];

        if ($statusFilter !== '') {
            $where = ' WHERE o.status = ?';
            $params[] = $statusFilter;
        }

        $planSelect = $planColumn !== null
            ? "o.{$planColumn} AS linked_plan_id, o.{$planColumn} AS plan_id, o.{$planColumn} AS package_id,"
            : "NULL AS linked_plan_id, NULL AS plan_id, NULL AS package_id,";
        $planJoin = $planColumn !== null
            ? "LEFT JOIN {$this->prefix}subscription_plans sp ON o.{$planColumn} = sp.id"
            : '';

        return $this->db->get_results(
            "SELECT o.*, {$planSelect} u.username, u.email as user_email, sp.name AS plan_name
             FROM {$table} o
             LEFT JOIN {$this->prefix}users u ON o.user_id = u.id
             {$planJoin}
             {$where}
             ORDER BY o.created_at DESC
             LIMIT " . self::MAX_ORDERS,
            $params
        ) ?: [];
    }

    /**
     * @return list<object>
     */
    private function fetchAssignments(): array
    {
        return $this->db->get_results(
            "SELECT us.*, u.username, u.email, sp.name AS plan_name
             FROM {$this->prefix}user_subscriptions us
             LEFT JOIN {$this->prefix}users u ON us.user_id = u.id
             LEFT JOIN {$this->prefix}subscription_plans sp ON us.plan_id = sp.id
             ORDER BY us.created_at DESC
               LIMIT " . self::MAX_ASSIGNMENTS
        ) ?: [];
    }

    /**
     * @return list<object>
     */
    private function fetchPlans(): array
    {
        return $this->db->get_results(
            "SELECT id, name, price_monthly, price_yearly, is_active FROM {$this->prefix}subscription_plans WHERE is_active = 1 ORDER BY sort_order ASC, price_monthly ASC"
        ) ?: [];
    }

    /**
     * @return list<object>
     */
    private function fetchUsers(): array
    {
        return $this->db->get_results(
            "SELECT id, username, email, display_name FROM {$this->prefix}users ORDER BY username ASC LIMIT " . self::MAX_USERS
        ) ?: [];
    }

    private function buildStats(string $table): array
    {
        return [
            'total'     => (int)$this->db->get_var("SELECT COUNT(*) FROM {$table}"),
            'pending'   => (int)$this->db->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'pending'"),
            'paid'      => (int)$this->db->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'paid'"),
            'revenue'   => (float)$this->db->get_var("SELECT COALESCE(SUM(total_amount), 0) FROM {$table} WHERE status = 'paid'"),
            'cancelled' => (int)$this->db->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'cancelled'"),
        ];
    }

    private function defaultStats(): array
    {
        return [
            'total' => 0,
            'pending' => 0,
            'paid' => 0,
            'revenue' => 0.0,
            'cancelled' => 0,
        ];
    }

    /**
     * @param list<object> $rows
     * @return list<array<string, mixed>>
     */
    private function mapRows(array $rows): array
    {
        return array_map(static fn($row) => (array)$row, $rows);
    }

    private function canAccess(): bool
    {
        return class_exists(Auth::class) && Auth::instance()->isAdmin();
    }

    private function failResult(string $action, string $message, \Throwable $e, array $context = []): OrdersActionResult
    {
        Logger::error($message, [
            'module' => 'OrdersModule',
            'action' => $action,
            'exception' => $e::class,
            'context' => $context,
        ]);

        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            $action,
            $message,
            'order',
            isset($context['order_id']) ? (int)$context['order_id'] : null,
            $context + ['exception' => $e::class],
            'error'
        );

        return OrdersActionResult::failure($message . ' Bitte Logs prüfen.');
    }

    private function getOrderSnapshot(int $id): ?array
    {
        $row = $this->db->get_row(
            "SELECT id, order_number, status, customer_email FROM {$this->prefix}orders WHERE id = ? LIMIT 1",
            [$id]
        );

        return $row ? (array)$row : null;
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return in_array($status, self::ALLOWED_STATUSES, true) ? $status : '';
    }

    private function normalizeBillingCycle(string $billingCycle): string
    {
        $billingCycle = strtolower(trim($billingCycle));

        return in_array($billingCycle, self::ALLOWED_BILLING_CYCLES, true) ? $billingCycle : 'monthly';
    }

    private function maskEmail(string $email): string
    {
        $email = trim($email);
        if ($email === '' || strpos($email, '@') === false) {
            return '';
        }

        [$local, $domain] = explode('@', $email, 2);
        $local = strlen($local) <= 2 ? str_repeat('*', max(1, strlen($local))) : substr($local, 0, 2) . str_repeat('*', max(1, strlen($local) - 2));

        return $local . '@' . $domain;
    }

    private function maskOrderNumber(string $orderNumber): string
    {
        $orderNumber = trim($orderNumber);
        $length = strlen($orderNumber);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($orderNumber, 0, 2) . str_repeat('*', max(1, $length - 4)) . substr($orderNumber, -2);
    }
}
