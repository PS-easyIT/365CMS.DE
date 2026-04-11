<?php
declare(strict_types=1);

/**
 * PackagesModule – Abo-Pakete verwalten (CRUD)
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\AuditLogger;
use CMS\Auth;
use CMS\Logger;

class PackagesModule
{
    private const string REQUIRED_CAPABILITY = 'manage_settings';
    private const int MAX_NAME_LENGTH = 120;
    private const int MAX_SLUG_LENGTH = 120;
    private const int MAX_DESCRIPTION_LENGTH = 2000;

    private readonly \CMS\Database $db;
    private readonly string $prefix;

    public function __construct()
    {
        $this->db     = \CMS\Database::instance();
        $this->prefix = $this->db->getPrefix();
        \CMS\SubscriptionManager::instance();
        $this->ensurePlanColumns();
    }

    private function ensurePlanColumns(): void
    {
        $table = $this->prefix . 'subscription_plans';
        $col = $this->db->get_var("SHOW COLUMNS FROM {$table} LIKE 'is_featured'");
        if (!$col) {
            $this->db->getPdo()->exec("ALTER TABLE {$table} ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active");
        }
    }

    public function getData(): array
    {
        if (!$this->canAccess()) {
            return ['packages' => [], 'stats' => ['total' => 0, 'active' => 0, 'featured' => 0]];
        }

        $table = $this->prefix . 'subscription_plans';
        $packages = $this->db->get_results(
            "SELECT sp.*,
                    (SELECT COUNT(*)
                     FROM {$this->prefix}user_subscriptions us
                     WHERE us.plan_id = sp.id
                       AND us.status = 'active'
                       AND (us.end_date IS NULL OR us.end_date > NOW())) AS subscriber_count
             FROM {$table} sp
             ORDER BY sp.sort_order ASC, sp.id ASC"
        ) ?: [];

        foreach ($packages as &$pkg) {
            $pkg->features_list = array_values(array_filter([
                !empty($pkg->feature_analytics) ? 'Analytics' : null,
                !empty($pkg->feature_advanced_search) ? 'Erweiterte Suche' : null,
                !empty($pkg->feature_api_access) ? 'API-Zugriff' : null,
                !empty($pkg->feature_custom_branding) ? 'Custom Branding' : null,
                !empty($pkg->feature_priority_support) ? 'Priority Support' : null,
                !empty($pkg->feature_export_data) ? 'Datenexport' : null,
                !empty($pkg->feature_integrations) ? 'Integrationen' : null,
                !empty($pkg->feature_custom_domains) ? 'Eigene Domains' : null,
            ]));
        }

        $stats = [
            'total'    => count($packages),
            'active'   => count(array_filter($packages, fn($p) => (int)$p->is_active === 1)),
            'featured' => count(array_filter($packages, fn($p) => (int)$p->is_featured === 1)),
        ];

        return ['packages' => array_map(fn($p) => (array)$p, $packages), 'stats' => $stats];
    }

    public function seedDefaults(): array
    {
        if (!$this->canAccess()) {
            return ['success' => false, 'error' => 'Zugriff verweigert.'];
        }

        try {
            \CMS\SubscriptionManager::instance()->seedDefaultPlans();
            $this->db->execute(
                "UPDATE {$this->prefix}subscription_plans SET is_featured = CASE WHEN slug = ? THEN 1 ELSE 0 END",
                ['professional']
            );

            AuditLogger::instance()->log(
                AuditLogger::CAT_SETTING,
                'subscriptions.packages.seed_defaults',
                'Standardpakete wurden erzeugt bzw. ergänzt.',
                'subscriptions',
                null,
                ['featured_slug' => 'professional'],
                'info'
            );

            return ['success' => true, 'message' => 'Die 6 Standardpakete wurden hinterlegt bzw. ergänzt.'];
        } catch (\Throwable $e) {
            return $this->failResult('subscriptions.packages.seed_defaults_failed', 'Die Standardpakete konnten nicht angelegt werden.', $e);
        }
    }

    public function save(array $post): array
    {
        if (!$this->canAccess()) {
            return ['success' => false, 'error' => 'Zugriff verweigert.'];
        }

        $id          = (int)($post['id'] ?? 0);
        $name        = $this->sanitizeText((string)($post['name'] ?? ''), self::MAX_NAME_LENGTH);
        $slug        = $this->sanitizeSlug((string)($post['slug'] ?? ''));
        $description = $this->sanitizeText((string)($post['description'] ?? ''), self::MAX_DESCRIPTION_LENGTH);
        $priceMonthly = max(0, (float)($post['price_monthly'] ?? 0));
        $priceYearly  = max(0, (float)($post['price_yearly'] ?? 0));
        $sortOrder   = max(0, (int)($post['sort_order'] ?? 0));
        $isActive    = isset($post['is_active']) ? 1 : 0;
        $isFeatured  = isset($post['is_featured']) ? 1 : 0;
        $limits = [
            'limit_experts'     => (int)($post['limit_experts'] ?? -1),
            'limit_companies'   => (int)($post['limit_companies'] ?? -1),
            'limit_events'      => (int)($post['limit_events'] ?? -1),
            'limit_speakers'    => (int)($post['limit_speakers'] ?? -1),
            'limit_storage_mb'  => max(0, (int)($post['limit_storage_mb'] ?? 0)),
        ];
        $plugins = [
            'plugin_experts'   => isset($post['plugin_experts']) ? 1 : 0,
            'plugin_companies' => isset($post['plugin_companies']) ? 1 : 0,
            'plugin_events'    => isset($post['plugin_events']) ? 1 : 0,
            'plugin_speakers'  => isset($post['plugin_speakers']) ? 1 : 0,
        ];
        $features = [
            'feature_analytics'        => isset($post['feature_analytics']) ? 1 : 0,
            'feature_advanced_search'  => isset($post['feature_advanced_search']) ? 1 : 0,
            'feature_api_access'       => isset($post['feature_api_access']) ? 1 : 0,
            'feature_custom_branding'  => isset($post['feature_custom_branding']) ? 1 : 0,
            'feature_priority_support' => isset($post['feature_priority_support']) ? 1 : 0,
            'feature_export_data'      => isset($post['feature_export_data']) ? 1 : 0,
            'feature_integrations'     => isset($post['feature_integrations']) ? 1 : 0,
            'feature_custom_domains'   => isset($post['feature_custom_domains']) ? 1 : 0,
        ];

        if ($name === '') {
            return ['success' => false, 'error' => 'Paketname ist erforderlich.'];
        }
        if ($slug === '') {
            $slug = $this->sanitizeSlug($name);
        }
        if ($slug === '') {
            return ['success' => false, 'error' => 'Bitte einen gültigen Paket-Slug angeben.'];
        }
        if ($this->slugExists($slug, $id)) {
            return ['success' => false, 'error' => 'Der Paket-Slug ist bereits vergeben.'];
        }

        $table = $this->prefix . 'subscription_plans';
        $data  = [
            'name'        => $name,
            'slug'        => $slug,
            'description' => $description,
            'price_monthly' => $priceMonthly,
            'price_yearly'  => $priceYearly,
            'sort_order'  => $sortOrder,
            'is_active'   => $isActive,
            'is_featured' => $isFeatured,
        ] + $limits + $plugins + $features;

        try {
            if ($isFeatured === 1) {
                $this->db->execute("UPDATE {$table} SET is_featured = 0 WHERE id != ?", [$id]);
            }

            if ($id > 0) {
                $updated = $this->db->update('subscription_plans', $data, ['id' => $id]);
                if ($updated !== true) {
                    throw new \RuntimeException('Package update failed.');
                }

                AuditLogger::instance()->log(
                    AuditLogger::CAT_SETTING,
                    'subscriptions.packages.update',
                    'Paket aktualisiert.',
                    'subscriptions',
                    null,
                    ['id' => $id, 'slug' => $slug, 'featured' => $isFeatured],
                    'info'
                );
                return ['success' => true, 'message' => 'Paket aktualisiert.'];
            } else {
                if ($isFeatured === 1) {
                    $this->db->execute("UPDATE {$table} SET is_featured = 0");
                }
                $insertId = $this->db->insert('subscription_plans', $data);
                if ($insertId === false) {
                    throw new \RuntimeException('Package insert failed.');
                }

                AuditLogger::instance()->log(
                    AuditLogger::CAT_SETTING,
                    'subscriptions.packages.create',
                    'Paket erstellt.',
                    'subscriptions',
                    null,
                    ['slug' => $slug, 'featured' => $isFeatured],
                    'info'
                );
                return ['success' => true, 'message' => 'Paket erstellt.'];
            }
        } catch (\Throwable $e) {
            return $this->failResult('subscriptions.packages.save_failed', 'Paket konnte nicht gespeichert werden.', $e);
        }
    }

    public function delete(int $id): array
    {
        if (!$this->canAccess()) {
            return ['success' => false, 'error' => 'Zugriff verweigert.'];
        }

        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige ID.'];
        }

        try {
            $plan = $this->db->get_row("SELECT id, slug FROM {$this->prefix}subscription_plans WHERE id = ?", [$id]);
            if (!$plan) {
                return ['success' => false, 'error' => 'Paket nicht gefunden.'];
            }

            $activeSubscriptions = (int)$this->db->get_var(
                "SELECT COUNT(*) FROM {$this->prefix}user_subscriptions WHERE plan_id = ? AND status = 'active'",
                [$id]
            );
            if ($activeSubscriptions > 0) {
                return ['success' => false, 'error' => 'Das Paket ist noch aktiven Benutzern zugewiesen.'];
            }

            $statement = $this->db->execute(
                "DELETE FROM {$this->prefix}subscription_plans WHERE id = ? LIMIT 1",
                [$id]
            );
            if ($statement->rowCount() < 1) {
                return ['success' => false, 'error' => 'Paket wurde zwischenzeitlich entfernt. Bitte Liste neu laden.'];
            }

            AuditLogger::instance()->log(
                AuditLogger::CAT_SETTING,
                'subscriptions.packages.delete',
                'Paket gelöscht.',
                'subscriptions',
                null,
                ['id' => $id, 'slug' => (string)($plan->slug ?? '')],
                'warning'
            );

            return ['success' => true, 'message' => 'Paket gelöscht.'];
        } catch (\Throwable $e) {
            return $this->failResult('subscriptions.packages.delete_failed', 'Paket konnte nicht gelöscht werden.', $e);
        }
    }

    public function toggleStatus(int $id): array
    {
        if (!$this->canAccess()) {
            return ['success' => false, 'error' => 'Zugriff verweigert.'];
        }

        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige ID.'];
        }

        $table   = $this->prefix . 'subscription_plans';

        try {
            $current = $this->db->get_row("SELECT slug, is_active FROM {$table} WHERE id = ?", [$id]);
            if (!$current) {
                return ['success' => false, 'error' => 'Paket nicht gefunden.'];
            }

            $newStatus = (int)$current->is_active === 1 ? 0 : 1;
            $statement = $this->db->execute(
                "UPDATE {$table} SET is_active = ? WHERE id = ? AND is_active = ? LIMIT 1",
                [$newStatus, $id, (int)$current->is_active]
            );
            if ($statement->rowCount() < 1) {
                return ['success' => false, 'error' => 'Paket wurde zwischenzeitlich geändert. Bitte Liste neu laden und erneut versuchen.'];
            }

            AuditLogger::instance()->log(
                AuditLogger::CAT_SETTING,
                'subscriptions.packages.toggle',
                $newStatus ? 'Paket aktiviert.' : 'Paket deaktiviert.',
                'subscriptions',
                null,
                ['id' => $id, 'slug' => (string)($current->slug ?? ''), 'active' => $newStatus],
                'info'
            );

            return ['success' => true, 'message' => $newStatus ? 'Paket aktiviert.' : 'Paket deaktiviert.'];
        } catch (\Throwable $e) {
            return $this->failResult('subscriptions.packages.toggle_failed', 'Paketstatus konnte nicht geändert werden.', $e);
        }
    }

    private function canAccess(): bool
    {
        return class_exists(Auth::class)
            && Auth::instance()->isAdmin()
            && Auth::instance()->hasCapability(self::REQUIRED_CAPABILITY);
    }

    private function sanitizeText(string $value, int $maxLength): string
    {
        $value = trim(strip_tags($value));
        if ($value === '') {
            return '';
        }

        return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
    }

    private function sanitizeSlug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = str_replace(' ', '-', $value);
        $value = preg_replace('/[^a-z0-9-]/', '', $value) ?? '';
        $value = preg_replace('/-+/', '-', $value) ?? '';
        $value = trim($value, '-');

        if ($value === '') {
            return '';
        }

        return function_exists('mb_substr') ? mb_substr($value, 0, self::MAX_SLUG_LENGTH) : substr($value, 0, self::MAX_SLUG_LENGTH);
    }

    private function slugExists(string $slug, int $excludeId): bool
    {
        $query = "SELECT COUNT(*) FROM {$this->prefix}subscription_plans WHERE slug = ?";
        $params = [$slug];
        if ($excludeId > 0) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }

        return (int)$this->db->get_var($query, $params) > 0;
    }

    private function failResult(string $action, string $message, \Throwable $e): array
    {
        Logger::instance()->withChannel('admin.packages')->error($message, [
            'module' => 'PackagesModule',
            'action' => $action,
            'exception' => $e::class,
        ]);

        AuditLogger::instance()->log(
            AuditLogger::CAT_SETTING,
            $action,
            $message,
            'subscriptions',
            null,
            ['exception' => $e::class],
            'error'
        );

        return ['success' => false, 'error' => $message . ' Bitte Logs prüfen.'];
    }
}
