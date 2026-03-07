<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$siteUrl      = defined('SITE_URL') ? SITE_URL : '';
$orders       = $data['orders'] ?? [];
$stats        = $data['stats'] ?? [];
$statusFilter = $data['filter'] ?? '';

$statusLabels = [
    'pending'   => ['label' => 'Offen',       'class' => 'bg-warning'],
    'paid'      => ['label' => 'Bezahlt',     'class' => 'bg-success'],
    'cancelled' => ['label' => 'Storniert',   'class' => 'bg-secondary'],
    'refunded'  => ['label' => 'Erstattet',   'class' => 'bg-info'],
    'failed'    => ['label' => 'Fehlgeschl.',  'class' => 'bg-danger'],
];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Aboverwaltung</div>
                <h2 class="page-title">Bestellungen</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <?php if (!empty($alert)): ?>
            <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> alert-dismissible" role="alert">
                <div><?= htmlspecialchars($alert['message']) ?></div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
            </div>
        <?php endif; ?>

        <!-- KPI Cards -->
        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Gesamt</div>
                        <div class="h1 mb-0"><?= (int)$stats['total'] ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Offen</div>
                        <div class="h1 mb-0 text-warning"><?= (int)$stats['pending'] ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Bezahlt</div>
                        <div class="h1 mb-0 text-success"><?= (int)$stats['paid'] ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Umsatz</div>
                        <div class="h1 mb-0"><?= number_format((float)$stats['revenue'], 2, ',', '.') ?> €</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?= $siteUrl ?>/admin/orders" class="btn btn-sm <?= $statusFilter === '' ? 'btn-primary' : 'btn-ghost-secondary' ?>">Alle</a>
                    <?php foreach ($statusLabels as $key => $s): ?>
                        <a href="<?= $siteUrl ?>/admin/orders?status=<?= $key ?>" class="btn btn-sm <?= $statusFilter === $key ? 'btn-primary' : 'btn-ghost-secondary' ?>">
                            <?= $s['label'] ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Bestellung</th>
                            <th>Kunde</th>
                            <th>Betrag</th>
                            <th>Status</th>
                            <th>Zahlung</th>
                            <th>Datum</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="7" class="text-center text-secondary py-4">Keine Bestellungen gefunden.</td></tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($order['order_number'] ?? '#' . $order['id']) ?></strong>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($order['customer_name'] ?? $order['username'] ?? '–') ?></div>
                                        <div class="text-secondary small"><?= htmlspecialchars($order['customer_email'] ?? $order['user_email'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <strong><?= number_format((float)($order['total_amount'] ?? 0), 2, ',', '.') ?> <?= htmlspecialchars($order['currency'] ?? 'EUR') ?></strong>
                                        <?php if ((float)($order['tax_amount'] ?? 0) > 0): ?>
                                            <div class="text-secondary small">inkl. <?= number_format((float)$order['tax_amount'], 2, ',', '.') ?> MwSt.</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php $sl = $statusLabels[$order['status']] ?? ['label' => $order['status'], 'class' => 'bg-secondary']; ?>
                                        <span class="badge <?= $sl['class'] ?>"><?= $sl['label'] ?></span>
                                    </td>
                                    <td class="text-secondary"><?= htmlspecialchars($order['payment_method'] ?? '–') ?></td>
                                    <td class="text-secondary"><?= date('d.m.Y H:i', strtotime($order['created_at'] ?? 'now')) ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <a href="#" class="btn-action" data-bs-toggle="dropdown"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/></svg></a>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <?php foreach (['pending', 'paid', 'cancelled', 'refunded'] as $st): ?>
                                                    <?php if ($st !== ($order['status'] ?? '')): ?>
                                                        <form method="post" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
                                                            <input type="hidden" name="status" value="<?= $st ?>">
                                                            <button type="submit" class="dropdown-item">→ <?= $statusLabels[$st]['label'] ?></button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                                <div class="dropdown-divider"></div>
                                                <button class="dropdown-item text-danger" onclick="cmsConfirm({title:'Bestellung löschen?',message:'#<?= htmlspecialchars($order['order_number'] ?? (string)$order['id']) ?>',onConfirm:function(){document.getElementById('delOrder-<?= (int)$order['id'] ?>').submit();}})">Löschen</button>
                                            </div>
                                        </div>
                                        <form id="delOrder-<?= (int)$order['id'] ?>" method="post" class="d-none">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
