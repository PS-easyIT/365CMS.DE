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
        try {
            \CMS\SubscriptionManager::instance()->seedDefaultPlans();
            $this->db->execute(
                "UPDATE {$this->prefix}subscription_plans SET is_featured = CASE WHEN slug = ? THEN 1 ELSE 0 END",
                ['professional']
            );
            return ['success' => true, 'message' => 'Die 6 Standardpakete wurden hinterlegt bzw. ergänzt.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Anlegen der Standardpakete: ' . $e->getMessage()];
        }
    }

    public function save(array $post): array
    {
        $id          = (int)($post['id'] ?? 0);
        $name        = trim($post['name'] ?? '');
        $slug        = trim($post['slug'] ?? '');
        $description = trim($post['description'] ?? '');
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
            $slug = preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', strtolower($name))) ?? '';
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
                $this->db->update('subscription_plans', $data, ['id' => $id]);
                return ['success' => true, 'message' => 'Paket aktualisiert.'];
            } else {
                if ($isFeatured === 1) {
                    $this->db->execute("UPDATE {$table} SET is_featured = 0");
                }
                $this->db->insert('subscription_plans', $data);
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
        $activeSubscriptions = (int)$this->db->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}user_subscriptions WHERE plan_id = ? AND status = 'active'",
            [$id]
        );
        if ($activeSubscriptions > 0) {
            return ['success' => false, 'error' => 'Das Paket ist noch aktiven Benutzern zugewiesen.'];
        }

        $this->db->delete('subscription_plans', ['id' => $id]);
        return ['success' => true, 'message' => 'Paket gelöscht.'];
    }

    public function toggleStatus(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige ID.'];
        }
        $table   = $this->prefix . 'subscription_plans';
        $current = $this->db->get_row("SELECT is_active FROM {$table} WHERE id = ?", [$id]);
        if (!$current) {
            return ['success' => false, 'error' => 'Paket nicht gefunden.'];
        }
        $newStatus = (int)$current->is_active === 1 ? 0 : 1;
        $this->db->update('subscription_plans', ['is_active' => $newStatus], ['id' => $id]);
        return ['success' => true, 'message' => $newStatus ? 'Paket aktiviert.' : 'Paket deaktiviert.'];
    }
}
