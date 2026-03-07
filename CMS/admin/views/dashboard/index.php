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
    ];
    $path = $icons[$name] ?? $icons['activity'];
    return '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}
?>

<!-- Page Header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Übersicht</div>
                <h2 class="page-title">Dashboard</h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
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
        if (!empty($data['alerts'])):
            foreach ($data['alerts'] as $alert): ?>
                <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> alert-dismissible" role="alert">
                    <div class="d-flex">
                        <div><?= dashIcon('alert-triangle') ?></div>
                        <div class="ms-2">
                            <?= htmlspecialchars($alert['message']) ?>
                            <?php if (!empty($alert['url'])): ?>
                                <a href="<?= htmlspecialchars($siteUrl . $alert['url']) ?>" class="alert-link ms-1">Anzeigen →</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
                </div>
            <?php endforeach;
        endif;
        ?>

        <!-- ─── KPI-Karten ──────────────────────────────────────────── -->
        <div class="row row-deck row-cards mb-4">
            <?php foreach ($data['kpis'] as $kpi): ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader"><?= htmlspecialchars($kpi['label']) ?></div>
                                <div class="ms-auto lh-1">
                                    <a href="<?= htmlspecialchars($siteUrl . $kpi['url']) ?>" class="text-secondary">
                                        Details →
                                    </a>
                                </div>
                            </div>
                            <div class="d-flex align-items-baseline mt-1">
                                <div class="h1 mb-0 me-2"><?= htmlspecialchars((string)$kpi['value']) ?></div>
                            </div>
                            <div class="mt-1">
                                <span class="text-secondary"><?= htmlspecialchars($kpi['sub']) ?></span>
                            </div>
                        </div>
                        <div class="card-stamp">
                            <div class="card-stamp-icon bg-<?= htmlspecialchars($kpi['color']) ?>">
                                <?= dashIcon($kpi['icon']) ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row row-deck row-cards">

            <!-- ─── Schnellzugriffe ────────────────────────────────── -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Schnellzugriffe</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php foreach ($data['quickLinks'] as $link): ?>
                                <div class="col-6">
                                    <a href="<?= htmlspecialchars($siteUrl . $link['url']) ?>"
                                       class="btn btn-outline-<?= htmlspecialchars($link['color']) ?> w-100">
                                        <?= dashIcon($link['icon']) ?>
                                        <?= htmlspecialchars($link['label']) ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- System-Info -->
                <?php if (!empty($data['system'])): ?>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Systemstatus</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-vcenter card-table">
                                <tbody>
                                    <?php if (!empty($data['system']['php_version'])): ?>
                                        <tr>
                                            <td class="text-secondary">PHP</td>
                                            <td><?= htmlspecialchars($data['system']['php_version']) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($data['system']['cms_version'])): ?>
                                        <tr>
                                            <td class="text-secondary">CMS</td>
                                            <td><?= htmlspecialchars($data['system']['cms_version']) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($data['system']['db_size'])): ?>
                                        <tr>
                                            <td class="text-secondary">Datenbank</td>
                                            <td><?= htmlspecialchars($data['system']['db_size']) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($data['system']['disk_free'])): ?>
                                        <tr>
                                            <td class="text-secondary">Speicher frei</td>
                                            <td><?= htmlspecialchars($data['system']['disk_free']) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ─── Letzte Aktivitäten ─────────────────────────────── -->
            <div class="col-lg-8">
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
                                                <span class="badge bg-blue-lt"><?= htmlspecialchars($entry->action ?? '') ?></span>
                                            </td>
                                            <td class="text-secondary"><?= htmlspecialchars(mb_strimwidth($entry->details ?? '', 0, 60, '…')) ?></td>
                                            <td class="text-secondary">
                                                <?= htmlspecialchars($entry->created_at ?? '') ?>
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
            </div>

        </div><!-- /.row -->

    </div><!-- /.container-xl -->
</div><!-- /.page-body -->
