<?php
declare(strict_types=1);

/**
 * Dashboard View – Admin-Startseite
 *
 * Erwartet:
 *   $data['kpis']       – array  KPI-Karten
 *   $data['activity']   – array  Letzte Aktivitäten
 *   $data['quickLinks'] – array  Schnellzugriffe
 *   $data['alerts']     – array  Hinweise/Warnungen
 *   $data['orders']     – array  Bestell-Statistiken
 *   $data['system']     – array  System-Infos
 *
 * @package CMSv2\Admin\Views
 */

if (!defined('ABSPATH')) {
    exit;
}

$siteUrl = defined('SITE_URL') ? SITE_URL : '';
$welcome = is_array($data['welcome'] ?? null) ? $data['welcome'] : [];
$attentionItems = is_array($data['attention'] ?? null) ? $data['attention'] : [];
$recentOrders = is_array($data['recent_orders'] ?? null) ? $data['recent_orders'] : [];
$highlights = is_array($data['highlights'] ?? null) ? $data['highlights'] : [];
$security = is_array($data['security'] ?? null) ? $data['security'] : [];
$performance = is_array($data['performance'] ?? null) ? $data['performance'] : [];
$system = is_array($data['system'] ?? null) ? $data['system'] : [];
$subscriptionEnabled = (bool) ($data['subscription_enabled'] ?? true);
$quickLinks = is_array($data['quickLinks'] ?? null) ? $data['quickLinks'] : [];
$dashboardAlerts = array_values(array_filter(
    is_array($data['alerts'] ?? null) ? $data['alerts'] : [],
    static function ($alert): bool {
        if (!is_array($alert)) {
            return false;
        }

        return in_array((string) ($alert['type'] ?? ''), ['warning', 'danger'], true);
    }
));
$headerQuickLinks = array_values(array_filter($quickLinks, static function (array $link): bool {
    return !in_array((string) ($link['label'] ?? ''), ['Neue Seite', 'Neuer Beitrag'], true);
}));

/** Tabler-Icon-SVG als Inline-Helfer */
function dashIcon(string $name): string {
    $icons = [
        'users'         => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/>',
        'file-text'     => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M9 9l1 0"/><path d="M9 13l6 0"/><path d="M9 17l6 0"/>',
        'photo'         => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8h.01"/><path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z"/><path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l5 5"/><path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0l3 3"/>',
        'currency-euro' => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17.2 7a6 7 0 1 0 0 10"/><path d="M13 10h-8m0 4h8"/>',
        'file-plus'     => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M12 11l0 6"/><path d="M9 14l6 0"/>',
        'pencil-plus'   => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4"/><path d="M13.5 6.5l4 4"/>',
        'upload'        => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/><path d="M7 9l5 -5l5 5"/><path d="M12 4l0 12"/>',
        'settings'      => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"/><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"/>',
        'activity'      => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12h4l3 8l4 -16l3 8h4"/>',
        'alert-triangle' => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/>',
        'shield-check'  => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3l7 4v5c0 5 -3.5 7.5 -7 9c-3.5 -1.5 -7 -4 -7 -9v-5l7 -4"/><path d="M9 12l2 2l4 -4"/>',
        'server'        => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 4m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z"/><path d="M3 14m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v2a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z"/><path d="M7 8l0 .01"/><path d="M7 17l0 .01"/>',
        'shopping-cart' => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 19a1 1 0 0 0 1 1h14a1 1 0 0 0 0 -2h-14a1 1 0 0 0 -1 1z"/><path d="M6 17l1.2 -7h11.6l1.2 7"/><path d="M6 10l-1 -5h-2"/><path d="M9 21a1 1 0 1 0 0 -2a1 1 0 0 0 0 2z"/><path d="M17 21a1 1 0 1 0 0 -2a1 1 0 0 0 0 2z"/>',
    ];
    $path = $icons[$name] ?? $icons['activity'];
    return '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

function dashboardStatusBadge(string $status): string {
    return match ($status) {
        'good' => 'success',
        'warning' => 'warning',
        'critical' => 'danger',
        default => 'secondary',
    };
}
?>

<style>
    .dashboard-overview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr));
        gap: 1rem;
        align-items: stretch;
    }

    .dashboard-overview-grid .card {
        height: 100%;
        box-shadow: 0 0.125rem 0.25rem rgba(15, 23, 42, 0.06);
    }

    .dashboard-overview-card .card-header {
        min-height: 68px;
        display: flex;
        align-items: center;
    }

    .dashboard-overview-card .list-group-item,
    .dashboard-overview-card .card-body,
    .dashboard-overview-card .card-footer {
        font-size: 0.95rem;
    }

    .dashboard-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr));
        gap: 1rem;
    }

    .dashboard-kpi-tile {
        border: 1px solid rgba(37, 99, 235, 0.12);
        border-radius: 0.75rem;
        background: rgba(255, 255, 255, 0.72);
        padding: 1rem;
        min-height: 100%;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .dashboard-kpi-tile:hover {
        transform: translateY(-1px);
        box-shadow: 0 0.375rem 1rem rgba(37, 99, 235, 0.10);
    }

    .dashboard-kpi-tile .dashboard-kpi-value {
        font-size: 1.4rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .dashboard-kpi-tile .dashboard-kpi-icon {
        width: 2.5rem;
        height: 2.5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: rgba(37, 99, 235, 0.08);
        color: rgb(37, 99, 235);
        flex: 0 0 auto;
    }

    .dashboard-kpi-tile .dashboard-kpi-value.is-highlight {
        font-size: 1.1rem;
    }
</style>

<!-- Page Header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Übersicht</div>
                <h2 class="page-title">Dashboard</h2>
                <div class="text-secondary mt-1">
                    <?php echo htmlspecialchars((string) ($welcome['greeting'] ?? 'Willkommen')); ?>,
                    <?php echo htmlspecialchars((string) ($welcome['display_name'] ?? 'im Admin')); ?> ·
                    <?php echo htmlspecialchars((string) ($welcome['date_label'] ?? date('d.m.Y'))); ?> ·
                    <?php echo htmlspecialchars((string) ($welcome['time_label'] ?? date('H:i'))); ?> Uhr
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <?php foreach ($headerQuickLinks as $link): ?>
                        <a href="<?= htmlspecialchars($siteUrl . (string) ($link['url'] ?? '/admin')) ?>" class="btn btn-outline-secondary d-none d-xl-inline-flex">
                            <?= dashIcon((string) ($link['icon'] ?? 'settings')) ?>
                            <?= htmlspecialchars((string) ($link['label'] ?? 'Aktion')) ?>
                        </a>
                    <?php endforeach; ?>
                    <a href="<?= $siteUrl ?>/admin/pages?action=new" class="btn btn-primary d-none d-sm-inline-block">
                        <?= dashIcon('file-plus') ?>
                        Neue Seite
                    </a>
                    <a href="<?= $siteUrl ?>/admin/posts?action=new" class="btn btn-outline-primary d-none d-sm-inline-block">
                        <?= dashIcon('pencil-plus') ?>
                        Neuer Beitrag
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page Body -->
<div class="page-body">
    <div class="container-xl">

        <?php
        // ─── Warnungen / Hinweise ─────────────────────────────────
        if ($dashboardAlerts !== []):
            foreach ($dashboardAlerts as $alert): ?>
                <div class="alert alert-<?= htmlspecialchars((string) ($alert['type'] ?? 'info')) ?> alert-dismissible" role="alert">
                    <div class="d-flex">
                        <div><?= dashIcon('alert-triangle') ?></div>
                        <div class="ms-2">
                            <?= htmlspecialchars((string) ($alert['message'] ?? 'Hinweis')) ?>
                            <?php if (!empty($alert['url'])): ?>
                                <a href="<?= htmlspecialchars($siteUrl . (string) ($alert['url'] ?? '')) ?>" class="alert-link ms-1">Anzeigen →</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
                </div>
            <?php endforeach;
        endif;
        ?>

        <div class="card card-lg mb-4 bg-primary-lt border-primary-subtle">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 text-primary mb-2">
                    <?= dashIcon('activity') ?>
                    <span class="fw-semibold">Zentrale Arbeitsübersicht</span>
                </div>
                <h3 class="mb-2">Alles Wichtige auf einen Blick</h3>
                <p class="text-secondary mb-0">
                    Prüfe offene Aufgaben, springe direkt in häufige Bereiche und behalte Sicherheit, Performance sowie Bestellungen im Blick.
                </p>

                <div class="dashboard-kpi-grid mt-4">
                    <?php foreach ($data['kpis'] as $kpi): ?>
                        <a href="<?= htmlspecialchars($siteUrl . (string) ($kpi['url'] ?? '/admin')) ?>" class="dashboard-kpi-tile text-reset text-decoration-none">
                            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                                <div class="subheader mb-0"><?= htmlspecialchars((string) ($kpi['label'] ?? 'KPI')) ?></div>
                                <span class="dashboard-kpi-icon"><?= dashIcon((string) ($kpi['icon'] ?? 'activity')) ?></span>
                            </div>
                            <div class="dashboard-kpi-value mb-1"><?= htmlspecialchars((string) ($kpi['value'] ?? '0')) ?></div>
                            <div class="small text-secondary mb-2"><?= htmlspecialchars((string) ($kpi['sub'] ?? '')) ?></div>
                            <div class="small fw-semibold text-primary">Details →</div>
                        </a>
                    <?php endforeach; ?>

                    <?php foreach (array_slice($highlights, 0, 4) as $highlight): ?>
                        <a href="<?= htmlspecialchars($siteUrl . (string) ($highlight['url'] ?? '/admin')) ?>" class="dashboard-kpi-tile text-reset text-decoration-none">
                            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                                <div class="subheader mb-0"><?= htmlspecialchars((string) ($highlight['label'] ?? 'Hinweis')) ?></div>
                                <span class="dashboard-kpi-icon"><?= dashIcon('activity') ?></span>
                            </div>
                            <div class="dashboard-kpi-value is-highlight mb-1"><?= htmlspecialchars((string) ($highlight['value'] ?? '0')) ?></div>
                            <div class="small text-secondary mb-2"><?= htmlspecialchars((string) ($highlight['hint'] ?? '')) ?></div>
                            <div class="small fw-semibold text-primary">Öffnen →</div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="dashboard-overview-grid mb-4">
            <div class="dashboard-overview-card">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Nächste Aufmerksamkeit</h3>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if ($attentionItems !== []): ?>
                            <?php foreach ($attentionItems as $item): ?>
                                <a href="<?= htmlspecialchars((string) ($item['url'] ?? ($siteUrl . '/admin'))) ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex align-items-start justify-content-between gap-3">
                                        <div>
                                            <div class="fw-semibold mb-1"><?= htmlspecialchars((string) ($item['label'] ?? 'Hinweis')) ?></div>
                                            <div class="small text-secondary"><?= htmlspecialchars((string) ($item['hint'] ?? '')) ?></div>
                                        </div>
                                        <span class="badge bg-<?= htmlspecialchars((string) ($item['type'] ?? 'secondary')) ?>-lt">
                                            <?= htmlspecialchars((string) ($item['value'] ?? '')) ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="list-group-item text-secondary">Aktuell gibt es keine offenen Prioritäten. Das ist die gute Sorte Ruhe.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="dashboard-overview-card">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Systemstatus</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-vcenter card-table mb-0">
                            <tbody>
                                <?php if (!empty($system['php_version'])): ?>
                                    <tr>
                                        <td class="text-secondary">PHP</td>
                                        <td><?= htmlspecialchars((string) $system['php_version']) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (!empty($system['cms_version'])): ?>
                                    <tr>
                                        <td class="text-secondary">CMS</td>
                                        <td><?= htmlspecialchars((string) $system['cms_version']) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (!empty($system['mysql_version'])): ?>
                                    <tr>
                                        <td class="text-secondary">MySQL</td>
                                        <td><?= htmlspecialchars((string) $system['mysql_version']) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (!empty($performance['disk_free_formatted'])): ?>
                                    <tr>
                                        <td class="text-secondary">Speicher frei</td>
                                        <td><?= htmlspecialchars((string) $performance['disk_free_formatted']) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (!empty($system['upload_max_filesize'])): ?>
                                    <tr>
                                        <td class="text-secondary">Upload-Limit</td>
                                        <td><?= htmlspecialchars((string) $system['upload_max_filesize']) ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="dashboard-overview-card">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Sicherheit & Performance</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <div class="text-secondary small">Security-Score</div>
                                <div class="h2 mb-0"><?= htmlspecialchars((string) ($security['security_score'] ?? 0)) ?>/100</div>
                            </div>
                            <span class="badge bg-<?= htmlspecialchars(dashboardStatusBadge((string) ($security['status'] ?? 'secondary'))) ?>-lt">
                                <?= htmlspecialchars((string) ($security['status'] ?? 'unbekannt')) ?>
                            </span>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-secondary small mb-1">HTTPS</div>
                                    <div class="fw-semibold"><?= !empty($security['https_enabled']) ? 'Aktiv' : 'Prüfen' ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-secondary small mb-1">Failed Logins</div>
                                    <div class="fw-semibold"><?= htmlspecialchars((string) ($security['failed_logins_24h'] ?? 0)) ?> / 24h</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-secondary small mb-1">Performance-Score</div>
                                    <div class="fw-semibold"><?= htmlspecialchars((string) ($performance['performance_score'] ?? 0)) ?>/50</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-secondary small mb-1">RAM-Auslastung</div>
                                    <div class="fw-semibold"><?= htmlspecialchars((string) ($performance['memory_percent'] ?? 0)) ?>%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($subscriptionEnabled): ?>
                <div class="dashboard-overview-card">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Neueste Bestellungen</h3>
                        </div>
                        <?php if ($recentOrders !== []): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentOrders as $order): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex align-items-start justify-content-between gap-3">
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars((string) ($order->order_number ?? 'Bestellung')) ?></div>
                                                <div class="small text-secondary"><?= htmlspecialchars((string) ($order->customer_name ?? 'Gast')) ?></div>
                                                <div class="small text-secondary"><?= htmlspecialchars((string) ($order->created_at ?? '')) ?></div>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-semibold">
                                                    <?= htmlspecialchars(number_format((float) ($order->total_amount ?? 0), 2, ',', '.')) ?>
                                                    <?= htmlspecialchars((string) ($order->currency ?? 'EUR')) ?>
                                                </div>
                                                <span class="badge bg-azure-lt"><?= htmlspecialchars((string) ($order->status ?? 'offen')) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="card-footer">
                                <a href="<?= htmlspecialchars($siteUrl . '/admin/orders') ?>" class="btn btn-outline-primary w-100">
                                    <?= dashIcon('shopping-cart') ?>
                                    Bestellungen öffnen
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="card-body text-secondary">Noch keine Bestellungen gefunden.</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- ─── Letzte Aktivitäten ─────────────────────────────── -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Letzte Aktivitäten</h3>
            </div>
            <?php if (!empty($data['activity'])): ?>
                <div class="card-body p-0">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Aktion</th>
                                <th>Details</th>
                                <th>Zeitpunkt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['activity'] as $entry): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-blue-lt"><?= htmlspecialchars((string) ($entry->action ?? '')) ?></span>
                                    </td>
                                    <td class="text-secondary"><?= htmlspecialchars(mb_strimwidth((string) ($entry->details ?? ''), 0, 60, '…')) ?></td>
                                    <td class="text-secondary">
                                        <?= htmlspecialchars((string) ($entry->created_at ?? '')) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="card-body">
                    <div class="empty">
                        <div class="empty-icon"><?= dashIcon('activity') ?></div>
                        <p class="empty-title">Keine Aktivitäten</p>
                        <p class="empty-subtitle text-secondary">
                            Aktivitäten werden hier angezeigt, sobald Aktionen im System stattfinden.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div><!-- /.container-xl -->
</div><!-- /.page-body -->
