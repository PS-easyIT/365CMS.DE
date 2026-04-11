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
    private const string REQUIRED_CAPABILITY = 'manage_settings';
    private const array ALLOWED_STATUSES = ['pending', 'paid', 'cancelled', 'refunded', 'failed'];
    private const array STATUS_ALIASES = [
        'confirmed' => 'paid',
        'completed' => 'paid',
    ];
    private const array STATUS_TRANSITIONS = [
        'pending' => ['paid', 'cancelled', 'failed'],
        'paid' => ['refunded', 'cancelled'],
        'failed' => ['pending', 'paid', 'cancelled'],
        'cancelled' => ['pending'],
        'refunded' => [],
    ];
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
                billing_cycle VARCHAR(20) NOT NULL DEFAULT 'monthly',
                payment_ref   VARCHAR(200) DEFAULT NULL,
                notes         TEXT DEFAULT NULL,
                contact_data  LONGTEXT DEFAULT NULL,
                forename      VARCHAR(100) DEFAULT NULL,
                lastname      VARCHAR(100) DEFAULT NULL,
                company       VARCHAR(100) DEFAULT NULL,
                email         VARCHAR(200) DEFAULT NULL,
                phone         VARCHAR(50) DEFAULT NULL,
                street        VARCHAR(255) DEFAULT NULL,
                zip           VARCHAR(20) DEFAULT NULL,
                city          VARCHAR(100) DEFAULT NULL,
                country       VARCHAR(100) DEFAULT NULL,
                created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_status (status),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            return;
        }

        $this->ensureColumn($table, 'plan_id', "ALTER TABLE {$table} ADD COLUMN plan_id INT UNSIGNED DEFAULT NULL AFTER user_id");
        $this->ensureColumn($table, 'customer_name', "ALTER TABLE {$table} ADD COLUMN customer_name VARCHAR(200) DEFAULT NULL AFTER plan_id");
        $this->ensureColumn($table, 'customer_email', "ALTER TABLE {$table} ADD COLUMN customer_email VARCHAR(200) DEFAULT NULL AFTER customer_name");
        $this->ensureColumn($table, 'amount', "ALTER TABLE {$table} ADD COLUMN amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER customer_email");
        $this->ensureColumn($table, 'tax_amount', "ALTER TABLE {$table} ADD COLUMN tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER amount");
        $this->ensureColumn($table, 'total_amount', "ALTER TABLE {$table} ADD COLUMN total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER tax_amount");
        $this->ensureColumn($table, 'currency', "ALTER TABLE {$table} ADD COLUMN currency VARCHAR(3) NOT NULL DEFAULT 'EUR' AFTER total_amount");
        $this->ensureColumn($table, 'payment_method', "ALTER TABLE {$table} ADD COLUMN payment_method VARCHAR(30) DEFAULT NULL AFTER status");
        $this->ensureColumn($table, 'billing_cycle', "ALTER TABLE {$table} ADD COLUMN billing_cycle VARCHAR(20) NOT NULL DEFAULT 'monthly' AFTER payment_method");
        $this->ensureColumn($table, 'payment_ref', "ALTER TABLE {$table} ADD COLUMN payment_ref VARCHAR(200) DEFAULT NULL AFTER billing_cycle");
        $this->ensureColumn($table, 'notes', "ALTER TABLE {$table} ADD COLUMN notes TEXT DEFAULT NULL AFTER payment_ref");
        $this->ensureColumn($table, 'contact_data', "ALTER TABLE {$table} ADD COLUMN contact_data LONGTEXT DEFAULT NULL AFTER notes");
        $this->ensureColumn($table, 'forename', "ALTER TABLE {$table} ADD COLUMN forename VARCHAR(100) DEFAULT NULL AFTER contact_data");
        $this->ensureColumn($table, 'lastname', "ALTER TABLE {$table} ADD COLUMN lastname VARCHAR(100) DEFAULT NULL AFTER forename");
        $this->ensureColumn($table, 'company', "ALTER TABLE {$table} ADD COLUMN company VARCHAR(100) DEFAULT NULL AFTER lastname");
        $this->ensureColumn($table, 'email', "ALTER TABLE {$table} ADD COLUMN email VARCHAR(200) DEFAULT NULL AFTER company");
        $this->ensureColumn($table, 'phone', "ALTER TABLE {$table} ADD COLUMN phone VARCHAR(50) DEFAULT NULL AFTER email");
        $this->ensureColumn($table, 'street', "ALTER TABLE {$table} ADD COLUMN street VARCHAR(255) DEFAULT NULL AFTER phone");
        $this->ensureColumn($table, 'zip', "ALTER TABLE {$table} ADD COLUMN zip VARCHAR(20) DEFAULT NULL AFTER street");
        $this->ensureColumn($table, 'city', "ALTER TABLE {$table} ADD COLUMN city VARCHAR(100) DEFAULT NULL AFTER zip");
        $this->ensureColumn($table, 'country', "ALTER TABLE {$table} ADD COLUMN country VARCHAR(100) DEFAULT NULL AFTER city");
        $this->ensureColumn($table, 'updated_at', "ALTER TABLE {$table} ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");

        try {
            $this->db->getPdo()->exec("ALTER TABLE {$table} MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending'");
        } catch (\Throwable) {
        }
    }

    private function ensureColumn(string $table, string $column, string $sql): void
    {
        if ($this->hasColumn($table, $column)) {
            return;
        }

        try {
            $this->db->getPdo()->exec($sql);
        } catch (\Throwable) {
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
            $this->mapOrderRows($this->fetchOrders($table, $planColumn, $statusFilter)),
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
            return $this->denyResult();
        }

        if ($id <= 0) {
            return $this->invalidIdResult();
        }

        $status = $this->normalizeStatus($status);
        if ($status === '') {
            return OrdersActionResult::failure('Ungültiger Status.');
        }

        try {
            $order = $this->requireOrderSnapshot($id);
            if ($order instanceof OrdersActionResult) {
                return $order;
            }

            $currentStatusRaw = strtolower(trim((string) ($order['status_raw'] ?? $order['status'] ?? '')));

            if (($order['status'] ?? '') === $status) {
                return OrdersActionResult::success('Status war bereits gesetzt.');
            }

            if (!$this->canTransitionStatus((string) ($order['status'] ?? ''), $status)) {
                return OrdersActionResult::failure('Dieser Statuswechsel ist für die Bestellung nicht zulässig.');
            }

            $statement = $this->db->execute(
                "UPDATE {$this->prefix}orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND LOWER(TRIM(status)) = ? LIMIT 1",
                [$status, $id, $currentStatusRaw]
            );

            if ($statement->rowCount() < 1) {
                return OrdersActionResult::failure('Bestellung wurde zwischenzeitlich geändert. Bitte Liste neu laden und erneut versuchen.');
            }

            if (!$statement) {
                return OrdersActionResult::failure('Status konnte nicht aktualisiert werden.');
            }

            AuditLogger::instance()->log(
                AuditLogger::CAT_SYSTEM,
                'order.status.updated',
                "Bestellstatus für Bestellung #{$id} geändert.",
                'order',
                $id,
                $this->buildOrderAuditContext($order, [
                    'from_status' => (string)($order['status'] ?? ''),
                    'to_status' => $status,
                ]),
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
            return $this->denyResult();
        }

        if ($id <= 0) {
            return $this->invalidIdResult();
        }

        try {
            $order = $this->requireOrderSnapshot($id);
            if ($order instanceof OrdersActionResult) {
                return $order;
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
                $this->buildOrderAuditContext($order, [
                    'status' => (string)($order['status'] ?? ''),
                ]),
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
            $filterStatuses = $this->getDatabaseStatusesForFilter($statusFilter);
            $where = ' WHERE o.status IN (' . implode(', ', array_fill(0, count($filterStatuses), '?')) . ')';
            $params = $filterStatuses;
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
        $stats = $this->db->get_row(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN LOWER(TRIM(status)) = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN LOWER(TRIM(status)) IN ('paid', 'confirmed', 'completed') THEN 1 ELSE 0 END) AS paid,
                SUM(CASE WHEN LOWER(TRIM(status)) = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
                SUM(CASE WHEN LOWER(TRIM(status)) = 'refunded' THEN 1 ELSE 0 END) AS refunded,
                SUM(CASE WHEN LOWER(TRIM(status)) = 'failed' THEN 1 ELSE 0 END) AS failed,
                SUM(CASE WHEN LOWER(TRIM(status)) IN ('paid', 'confirmed', 'completed') THEN COALESCE(NULLIF(total_amount, 0), amount, 0) ELSE 0 END) AS revenue
             FROM {$table}"
        );

        return [
            'total'     => (int) ($stats->total ?? 0),
            'pending'   => (int) ($stats->pending ?? 0),
            'paid'      => (int) ($stats->paid ?? 0),
            'revenue'   => (float) ($stats->revenue ?? 0),
            'cancelled' => (int) ($stats->cancelled ?? 0),
            'refunded'  => (int) ($stats->refunded ?? 0),
            'failed'    => (int) ($stats->failed ?? 0),
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
            'refunded' => 0,
            'failed' => 0,
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

    /**
     * @param list<object> $rows
     * @return list<array<string, mixed>>
     */
    private function mapOrderRows(array $rows): array
    {
        return array_map(fn ($row): array => $this->mapOrderRow((array) $row), $rows);
    }

    private function canAccess(): bool
    {
        return class_exists(Auth::class)
            && Auth::instance()->isAdmin()
            && Auth::instance()->hasCapability(self::REQUIRED_CAPABILITY);
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

    private function denyResult(): OrdersActionResult
    {
        return OrdersActionResult::failure('Zugriff verweigert.');
    }

    private function invalidIdResult(): OrdersActionResult
    {
        return OrdersActionResult::failure('Ungültige ID.');
    }

    private function getOrderSnapshot(int $id): ?array
    {
        $row = $this->db->get_row(
            "SELECT * FROM {$this->prefix}orders WHERE id = ? LIMIT 1",
            [$id]
        );

        return $row ? $this->mapOrderRow((array) $row) : null;
    }

    private function requireOrderSnapshot(int $id): array|OrdersActionResult
    {
        $order = $this->getOrderSnapshot($id);

        return $order ?? OrdersActionResult::failure('Bestellung wurde nicht gefunden.');
    }

    private function buildOrderAuditContext(array $order, array $extra = []): array
    {
        return $extra + [
            'order_number' => $this->maskOrderNumber((string) ($order['order_number'] ?? '')),
            'customer_email' => $this->maskEmail((string) ($order['customer_email'] ?? '')),
        ];
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));

        if (isset(self::STATUS_ALIASES[$status])) {
            $status = self::STATUS_ALIASES[$status];
        }

        return in_array($status, self::ALLOWED_STATUSES, true) ? $status : '';
    }

    /**
     * @return list<string>
     */
    private function getDatabaseStatusesForFilter(string $status): array
    {
        $status = $this->normalizeStatus($status);
        if ($status === '') {
            return [];
        }

        return match ($status) {
            'paid' => ['paid', 'confirmed', 'completed'],
            default => [$status],
        };
    }

    /**
     * @return list<string>
     */
    private function getAvailableTransitions(string $status): array
    {
        $status = $this->normalizeStatus($status);
        if ($status === '') {
            return [];
        }

        return self::STATUS_TRANSITIONS[$status] ?? [];
    }

    private function canTransitionStatus(string $currentStatus, string $nextStatus): bool
    {
        $currentStatus = $this->normalizeStatus($currentStatus);
        $nextStatus = $this->normalizeStatus($nextStatus);

        if ($currentStatus === '' || $nextStatus === '' || $currentStatus === $nextStatus) {
            return false;
        }

        return in_array($nextStatus, $this->getAvailableTransitions($currentStatus), true);
    }

    private function normalizeBillingCycle(string $billingCycle): string
    {
        $billingCycle = strtolower(trim($billingCycle));

        return in_array($billingCycle, self::ALLOWED_BILLING_CYCLES, true) ? $billingCycle : 'monthly';
    }

    /**
     * @param array<string, mixed> $order
     * @return array<string, mixed>
     */
    private function mapOrderRow(array $order): array
    {
        $contactData = $this->decodeContactData((string) ($order['contact_data'] ?? ''));
        $status = $this->normalizeStatus((string) ($order['status'] ?? ''));
        $rawStatus = strtolower(trim((string) ($order['status'] ?? '')));
        $resolvedStatus = $status !== '' ? $status : $rawStatus;

        $customerEmail = $this->resolveOrderCustomerEmail($order, $contactData);
        $customerName = $this->resolveOrderCustomerName($order, $contactData, $customerEmail);
        $amount = isset($order['amount']) ? (float) $order['amount'] : 0.0;
        $totalAmount = isset($order['total_amount']) ? (float) $order['total_amount'] : 0.0;
        if ($totalAmount <= 0.0 && $amount > 0.0) {
            $totalAmount = $amount;
        }

        $linkedPlanId = (int) ($order['linked_plan_id'] ?? $order['plan_id'] ?? $order['package_id'] ?? 0);

        $order['status'] = $resolvedStatus !== '' ? $resolvedStatus : 'pending';
        $order['status_raw'] = $rawStatus !== '' ? $rawStatus : 'pending';
        $order['customer_email'] = $customerEmail;
        $order['customer_name'] = $customerName;
        $order['amount'] = $amount;
        $order['total_amount'] = $totalAmount;
        $order['currency'] = (string) ($order['currency'] ?? 'EUR');
        $order['plan_id'] = $linkedPlanId > 0 ? $linkedPlanId : (int) ($order['plan_id'] ?? 0);
        $order['linked_plan_id'] = $linkedPlanId;
        $order['available_transitions'] = $resolvedStatus !== '' ? $this->getAvailableTransitions($resolvedStatus) : [];

        return $order;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeContactData(string $contactData): array
    {
        if ($contactData === '') {
            return [];
        }

        $decoded = json_decode($contactData, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed> $contactData
     */
    private function resolveOrderCustomerEmail(array $order, array $contactData): string
    {
        $candidates = [
            (string) ($order['customer_email'] ?? ''),
            (string) ($order['email'] ?? ''),
            (string) ($contactData['email'] ?? ''),
            (string) ($order['user_email'] ?? ''),
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed> $contactData
     */
    private function resolveOrderCustomerName(array $order, array $contactData, string $fallbackEmail): string
    {
        $contactName = trim(((string) ($contactData['first_name'] ?? '')) . ' ' . ((string) ($contactData['last_name'] ?? '')));
        $legacyName = trim(((string) ($order['forename'] ?? '')) . ' ' . ((string) ($order['lastname'] ?? '')));
        $candidates = [
            (string) ($order['customer_name'] ?? ''),
            $contactName,
            $legacyName,
            (string) ($order['display_name'] ?? ''),
            (string) ($order['username'] ?? ''),
            $fallbackEmail,
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return 'Gast';
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
