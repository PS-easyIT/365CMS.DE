<?php
declare(strict_types=1);

/**
 * Dashboard Module – Business-Logik für die Admin-Startseite
 *
 * Lädt Statistiken aus DashboardService und bereitet Daten
 * für die View auf.
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Services\CoreModuleService;
use CMS\Services\DashboardService;
use CMS\Database;
use CMS\Auth;
use CMS\AuditLogger;

class DashboardModule
{
    private const DASHBOARD_SECTION_DEFINITIONS = [
        'work_overview' => [
            'label' => 'Zentrale Arbeitsübersicht',
            'description' => 'KPI-Karten und wichtigste Schnellzugriffe.',
            'required' => true,
        ],
        'attention' => [
            'label' => 'Nächste Aufmerksamkeit',
            'description' => 'Offene Prioritäten und kontextuelle Warnungen.',
            'required' => false,
        ],
        'system_status' => [
            'label' => 'Systemstatus',
            'description' => 'PHP, CMS, Datenbank und Speicherindikatoren.',
            'required' => false,
        ],
        'security_performance' => [
            'label' => 'Sicherheit & Performance',
            'description' => 'Security- und Performance-Kurzsignale.',
            'required' => false,
        ],
        'recent_orders' => [
            'label' => 'Neueste Bestellungen',
            'description' => 'Aktuelle Bestellungen bei aktivem Abo-/Order-Modul.',
            'required' => false,
        ],
        'recent_activity' => [
            'label' => 'Letzte Aktivitäten',
            'description' => 'Jüngste Audit-Ereignisse aus dem System.',
            'required' => false,
        ],
    ];

    private DashboardService $service;
    private Database $db;
    private string $prefix;

    public function __construct()
    {
        $this->service = DashboardService::getInstance();
        $this->db      = Database::instance();
        $this->prefix  = $this->db->getPrefix();
    }

    /**
     * Alle Dashboard-Daten laden
     */
    public function getData(): array
    {
        $stats = $this->service->getAllStats();
        $meta = is_array($stats['meta'] ?? null) ? $stats['meta'] : [];
        $subscriptionEnabled = $this->isSubscriptionSystemEnabled();
        $subscriptionOrdersEnabled = $this->isSubscriptionOrdersEnabled();
        $system = $stats['system'] ?? [];
        $security = $stats['security'] ?? [];
        $performance = $stats['performance'] ?? [];
        $orders = $stats['orders'] ?? [];
        $users = $stats['users'] ?? [];
        $pages = $stats['pages'] ?? [];
        $posts = $stats['posts'] ?? [];
        $media = $stats['media'] ?? [];
        $preferences = $this->getDashboardPreferences($subscriptionOrdersEnabled);

        return [
            'welcome'       => $this->getWelcomeData($system),
            'kpis'          => $this->buildKpis($stats, $subscriptionOrdersEnabled),
            'dashboard_sections' => $this->getDashboardSections($subscriptionOrdersEnabled),
            'dashboard_preferences' => $preferences,
            'activity'      => $this->getRecentActivity(),
            'quickLinks'    => $this->getQuickLinks(),
            'alerts'        => array_merge($this->buildDashboardHealthAlerts($meta), $this->getAlerts($stats)),
            'attention'     => $this->service->getAttentionItems($stats),
            'subscription_enabled' => $subscriptionEnabled,
            'recent_orders' => $subscriptionOrdersEnabled ? $this->service->getRecentOrders() : [],
            'orders'        => $orders,
            'system'        => $system,
            'security'      => $security,
            'performance'   => $performance,
            'meta'          => $meta,
            'highlights'    => [
                [
                    'label' => 'Neue Benutzer heute',
                    'value' => (string) ($users['new_today'] ?? 0),
                    'hint' => (string) (($users['new_this_week'] ?? 0) . ' in den letzten 7 Tagen'),
                    'icon' => 'users',
                    'url' => '/admin/users',
                ],
                [
                    'label' => 'Entwürfe & private Seiten',
                    'value' => (string) (($pages['drafts'] ?? 0) + ($pages['private'] ?? 0)),
                    'hint' => (string) (($pages['published'] ?? 0) . ' Seiten sind veröffentlicht'),
                    'icon' => 'file-text',
                    'url' => '/admin/pages',
                ],
                [
                    'label' => 'Geplante & private Beiträge',
                    'value' => (string) (($posts['scheduled'] ?? 0) + ($posts['private'] ?? 0)),
                    'hint' => (string) (($posts['published'] ?? 0) . ' Beiträge sind öffentlich sichtbar'),
                    'icon' => 'article',
                    'url' => '/admin/posts',
                ],
                [
                    'label' => 'Uploads gesamt',
                    'value' => (string) ($media['total_files'] ?? 0),
                    'hint' => (string) ($media['total_size_formatted'] ?? $this->formatBytes((int) ($media['total_size'] ?? 0))),
                    'icon' => 'photo',
                    'url' => '/admin/media',
                ],
                ...($subscriptionOrdersEnabled ? [[
                    'label' => 'Bestellungen offen',
                    'value' => (string) ($orders['pending'] ?? 0),
                    'hint' => (string) ($orders['month_revenue_formatted'] ?? '0,00 EUR') . ' Umsatz in 30 Tagen',
                    'icon' => 'shopping-cart',
                    'url' => '/admin/orders',
                ]] : []),
            ],
        ];
    }

    public function handleAction(array $post): array
    {
        $action = trim((string) ($post['action'] ?? ''));

        return match ($action) {
            'save_dashboard_preferences' => $this->saveDashboardPreferences($post),
            default => ['success' => false, 'error' => 'Unbekannte Dashboard-Aktion.'],
        };
    }

    private function getDashboardSections(bool $subscriptionOrdersEnabled): array
    {
        $sections = self::DASHBOARD_SECTION_DEFINITIONS;
        if (!$subscriptionOrdersEnabled) {
            unset($sections['recent_orders']);
        }

        return $sections;
    }

    private function getDashboardPreferences(bool $subscriptionOrdersEnabled): array
    {
        $sections = $this->getDashboardSections($subscriptionOrdersEnabled);
        $defaultVisible = array_keys($sections);
        $userId = $this->getCurrentUserId();
        if ($userId <= 0) {
            return ['visible_sections' => $defaultVisible];
        }

        try {
            $optionValue = $this->db->get_var(
                "SELECT option_value FROM {$this->prefix}settings WHERE option_name = ?",
                [$this->getDashboardPreferencesOptionName($userId)]
            );
        } catch (\Throwable) {
            return ['visible_sections' => $defaultVisible];
        }

        $decoded = is_string($optionValue) ? json_decode($optionValue, true) : null;
        $visible = is_array($decoded['visible_sections'] ?? null) ? $decoded['visible_sections'] : $defaultVisible;

        return ['visible_sections' => $this->normalizeVisibleDashboardSections($visible, $sections)];
    }

    private function saveDashboardPreferences(array $post): array
    {
        $userId = $this->getCurrentUserId();
        if ($userId <= 0) {
            return ['success' => false, 'error' => 'Dashboard-Einstellungen können ohne gültigen Benutzer nicht gespeichert werden.'];
        }

        $sections = $this->getDashboardSections($this->isSubscriptionOrdersEnabled());
        $selectedSections = is_array($post['dashboard_sections'] ?? null) ? $post['dashboard_sections'] : [];
        $visibleSections = $this->normalizeVisibleDashboardSections($selectedSections, $sections);
        $payload = [
            'visible_sections' => $visibleSections,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $optionName = $this->getDashboardPreferencesOptionName($userId);
        $optionValue = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($optionValue)) {
            return ['success' => false, 'error' => 'Dashboard-Einstellungen konnten nicht serialisiert werden.'];
        }

        try {
            $exists = (int) ($this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}settings WHERE option_name = ?", [$optionName]) ?? 0);
            $success = $exists > 0
                ? $this->db->update('settings', ['option_value' => $optionValue, 'autoload' => 0], ['option_name' => $optionName])
                : $this->db->insert('settings', ['option_name' => $optionName, 'option_value' => $optionValue, 'autoload' => 0]) !== false;
        } catch (\Throwable) {
            return ['success' => false, 'error' => 'Dashboard-Einstellungen konnten nicht gespeichert werden.'];
        }

        if (!$success) {
            return ['success' => false, 'error' => 'Dashboard-Einstellungen konnten nicht gespeichert werden.'];
        }

        AuditLogger::instance()->log(
            AuditLogger::CAT_SETTING,
            'dashboard.preferences.save',
            'Admin-Dashboard-Personalisierung gespeichert',
            'dashboard',
            $userId,
            ['visible_sections' => $visibleSections],
            'info'
        );

        return ['success' => true, 'message' => 'Dashboard-Ansicht gespeichert.'];
    }

    private function normalizeVisibleDashboardSections(array $selectedSections, array $availableSections): array
    {
        $availableKeys = array_keys($availableSections);
        $visible = [];

        foreach ($selectedSections as $section) {
            $section = trim((string) $section);
            if ($section !== '' && in_array($section, $availableKeys, true) && !in_array($section, $visible, true)) {
                $visible[] = $section;
            }
        }

        foreach ($availableSections as $sectionKey => $definition) {
            if (!empty($definition['required']) && !in_array($sectionKey, $visible, true)) {
                $visible[] = $sectionKey;
            }
        }

        return $visible !== [] ? $visible : $availableKeys;
    }

    private function getDashboardPreferencesOptionName(int $userId): string
    {
        return 'admin_dashboard_preferences_user_' . max(0, $userId);
    }

    private function getCurrentUserId(): int
    {
        $user = Auth::instance()->currentUser();

        return (int) ($user->id ?? $_SESSION['user_id'] ?? 0);
    }

    private function buildDashboardHealthAlerts(array $meta): array
    {
        $degradedSections = array_values(array_filter(
            is_array($meta['degraded_sections'] ?? null) ? $meta['degraded_sections'] : [],
            static fn (mixed $section): bool => is_string($section) && trim($section) !== ''
        ));

        if ($degradedSections === []) {
            return [];
        }

        $sectionLabels = [
            'users' => 'Benutzer',
            'pages' => 'Seiten',
            'posts' => 'Beiträge',
            'media' => 'Medien',
            'sessions' => 'Sessions',
            'security' => 'Sicherheit',
            'performance' => 'Performance',
            'system' => 'Systemstatus',
            'orders' => 'Bestellungen',
        ];

        $labels = array_map(
            static fn (string $section): string => $sectionLabels[$section] ?? ucfirst($section),
            $degradedSections
        );

        return [[
            'type' => 'warning',
            'message' => 'Einige Dashboard-Bereiche laufen aktuell im Fallback-Modus: ' . implode(', ', $labels) . '. Details stehen in den CMS Logs.',
            'url' => '/admin/cms-logs',
        ]];
    }

    private function isSubscriptionSystemEnabled(): bool
    {
        return CoreModuleService::getInstance()->isModuleEnabled('subscriptions');
    }

    private function isSubscriptionOrdersEnabled(): bool
    {
        return CoreModuleService::getInstance()->isModuleEnabled('subscription_admin_orders');
    }

    /**
     * Kopfbereich für die Admin-Startseite.
     */
    private function getWelcomeData(array $system): array
    {
        $hour = (int) date('G');
        $greeting = match (true) {
            $hour < 11 => 'Guten Morgen',
            $hour < 17 => 'Guten Tag',
            default => 'Guten Abend',
        };

        $displayName = 'im Admin';
        if (!empty($_SESSION['user_display_name'])) {
            $displayName = (string) $_SESSION['user_display_name'];
        } elseif (!empty($_SESSION['username'])) {
            $displayName = (string) $_SESSION['username'];
        }

        return [
            'greeting' => $greeting,
            'display_name' => $displayName,
            'date_label' => date('d.m.Y'),
            'time_label' => date('H:i'),
            'server_time' => (string) ($system['server_time'] ?? date('Y-m-d H:i:s')),
        ];
    }

    /**
     * KPI-Karten aufbereiten
     */
    private function buildKpis(array $stats, bool $subscriptionEnabled): array
    {
        $users = $stats['users'] ?? [];
        $pages = $stats['pages'] ?? [];
        $posts = $stats['posts'] ?? [];
        $media = $stats['media'] ?? [];
        $orders = $stats['orders'] ?? [];

        $kpis = [
            [
                'label'  => 'Benutzer',
                'value'  => $users['total'] ?? 0,
                'sub'    => ($users['active_today'] ?? 0) . ' heute aktiv',
                'color'  => 'blue',
                'icon'   => 'users',
                'url'    => '/admin/users',
            ],
            [
                'label'  => 'Seiten',
                'value'  => $pages['total'] ?? 0,
                'sub'    => ($pages['published'] ?? 0) . ' veröffentlicht',
                'color'  => 'green',
                'icon'   => 'file-text',
                'url'    => '/admin/pages',
            ],
            [
                'label'  => 'Beiträge',
                'value'  => $posts['total'] ?? 0,
                'sub'    => ($posts['published'] ?? 0) . ' sichtbar · ' . ($posts['scheduled'] ?? 0) . ' geplant',
                'color'  => 'azure',
                'icon'   => 'article',
                'url'    => '/admin/posts',
            ],
            [
                'label'  => 'Medien',
                'value'  => $media['total_files'] ?? 0,
                'sub'    => $this->formatBytes($media['total_size'] ?? 0),
                'color'  => 'purple',
                'icon'   => 'photo',
                'url'    => '/admin/media',
            ],
        ];

        if ($subscriptionEnabled) {
            $kpis[] = [
                'label'  => 'Umsatz (30T)',
                'value'  => $orders['month_revenue_formatted'] ?? '0,00 EUR',
                'sub'    => ($orders['pending'] ?? 0) . ' ausstehend',
                'color'  => 'yellow',
                'icon'   => 'currency-euro',
                'url'    => '/admin/orders',
            ];
        }

        return $kpis;
    }

    /**
     * Letzte Aktivitäten laden
     */
    private function getRecentActivity(): array
    {
        try {
            $rows = $this->db->get_results(
                "SELECT action, description AS details, user_id, created_at
                 FROM {$this->prefix}audit_log
                 ORDER BY created_at DESC
                 LIMIT 8"
            );
            return $rows ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Schnellzugriffe
     */
    private function getQuickLinks(): array
    {
        return [
            ['label' => 'Neue Seite',       'url' => '/admin/pages?action=new',  'icon' => 'file-plus',    'color' => 'blue'],
            ['label' => 'Neuer Beitrag',     'url' => '/admin/posts?action=new',  'icon' => 'pencil-plus',  'color' => 'green'],
            ['label' => 'Medien hochladen',  'url' => '/admin/media',             'icon' => 'upload',       'color' => 'purple'],
            ['label' => 'Einstellungen',     'url' => '/admin/settings',          'icon' => 'settings',     'color' => 'orange'],
        ];
    }

    /**
     * Aufmerksamkeits-Hinweise
     */
    private function getAlerts(array $stats): array
    {
        $alerts = [];

        // Offene Kommentare
        try {
            $pending = (int)$this->db->get_var(
                "SELECT COUNT(*) FROM {$this->prefix}comments WHERE status = 'pending'"
            );
            if ($pending > 0) {
                $alerts[] = [
                    'type'    => 'warning',
                    'message' => $pending . ' Kommentar(e) warten auf Freigabe',
                    'url'     => '/admin/comments',
                ];
            }
        } catch (\Throwable $e) {
            // comments table may not exist
        }

        // Security-Warnungen
        $security = $stats['security'] ?? [];
        if (!empty($security['failed_logins_24h']) && $security['failed_logins_24h'] > 10) {
            $alerts[] = [
                'type'    => 'danger',
                'message' => $security['failed_logins_24h'] . ' fehlgeschlagene Logins in den letzten 24 Stunden',
                'url'     => '/admin/security-audit',
            ];
        }

        return $alerts;
    }

    /**
     * Bytes formatieren
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 1, ',', '.') . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
        }
        return number_format($bytes / 1024, 0, ',', '.') . ' KB';
    }
}
