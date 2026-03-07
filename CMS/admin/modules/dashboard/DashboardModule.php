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

use CMS\Services\DashboardService;
use CMS\Database;

class DashboardModule
{
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

        return [
            'kpis'       => $this->buildKpis($stats),
            'activity'   => $this->getRecentActivity(),
            'quickLinks' => $this->getQuickLinks(),
            'alerts'     => $this->getAlerts($stats),
            'orders'     => $stats['orders'] ?? [],
            'system'     => $stats['system'] ?? [],
        ];
    }

    /**
     * KPI-Karten aufbereiten
     */
    private function buildKpis(array $stats): array
    {
        $users = $stats['users'] ?? [];
        $pages = $stats['pages'] ?? [];
        $media = $stats['media'] ?? [];
        $orders = $stats['orders'] ?? [];

        return [
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
                'label'  => 'Medien',
                'value'  => $media['total_files'] ?? 0,
                'sub'    => $this->formatBytes($media['total_size'] ?? 0),
                'color'  => 'purple',
                'icon'   => 'photo',
                'url'    => '/admin/media',
            ],
            [
                'label'  => 'Umsatz (30T)',
                'value'  => $orders['month_revenue_formatted'] ?? '0,00 EUR',
                'sub'    => ($orders['pending'] ?? 0) . ' ausstehend',
                'color'  => 'yellow',
                'icon'   => 'currency-euro',
                'url'    => '/admin/orders',
            ],
        ];
    }

    /**
     * Letzte Aktivitäten laden
     */
    private function getRecentActivity(): array
    {
        try {
            $rows = $this->db->get_results(
                "SELECT action, details, user_id, created_at
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
        if (!empty($security['failed_logins_today']) && $security['failed_logins_today'] > 10) {
            $alerts[] = [
                'type'    => 'danger',
                'message' => $security['failed_logins_today'] . ' fehlgeschlagene Logins heute',
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
