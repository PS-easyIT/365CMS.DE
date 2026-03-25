<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$siteUrl      = defined('SITE_URL') ? SITE_URL : '';
$ordersBaseUrl = $siteUrl . '/admin/orders';
$orders       = $data['orders'] ?? [];
$stats        = $data['stats'] ?? [];
$statusFilter = $data['filter'] ?? '';
$assignments  = $data['assignments'] ?? [];
$plans        = $data['plans'] ?? [];
$users        = $data['users'] ?? [];
$alertData    = is_array($alert ?? null) ? $alert : [];
$statusTransitions = ['pending', 'paid', 'cancelled', 'refunded'];

$statusLabels = [
    'pending'   => ['label' => 'Offen',       'class' => 'bg-warning'],
    'paid'      => ['label' => 'Bezahlt',     'class' => 'bg-success'],
    'cancelled' => ['label' => 'Storniert',   'class' => 'bg-secondary'],
    'refunded'  => ['label' => 'Erstattet',   'class' => 'bg-info'],
    'failed'    => ['label' => 'Fehlgeschl.',  'class' => 'bg-danger'],
];

$resolveStatusMeta = static function (string $status) use ($statusLabels): array {
    return $statusLabels[$status] ?? ['label' => $status !== '' ? $status : 'unbekannt', 'class' => 'bg-secondary'];
};

$formatDateTime = static function (?string $dateValue): string {
    $timestamp = $dateValue !== null ? strtotime($dateValue) : false;

    return $timestamp !== false ? date('d.m.Y H:i', $timestamp) : '–';
};

$formatDate = static function (?string $dateValue): string {
    $timestamp = $dateValue !== null ? strtotime($dateValue) : false;

    return $timestamp !== false ? date('d.m.Y', $timestamp) : '–';
};

$formatAmount = static function ($amount): string {
    return number_format((float) $amount, 2, ',', '.');
};
$filterButtonClass = static fn (string $filterValue) => $statusFilter === $filterValue ? 'btn-primary' : 'btn-ghost-secondary';
$orderNumberLabel = static fn (array $order): string => (string) ($order['order_number'] ?? '#' . ($order['id'] ?? '0'));
$customerName = static fn (array $order): string => (string) ($order['customer_name'] ?? $order['username'] ?? '–');
$customerEmail = static fn (array $order): string => (string) ($order['customer_email'] ?? $order['user_email'] ?? '');
$userOptionLabel = static function (array $user): string {
    $primary = (string) ($user['username'] ?: ($user['display_name'] ?: $user['email']));
    $email = !empty($user['email']) ? ' (' . (string) $user['email'] . ')' : '';

    return $primary . $email;
};
$planOptionLabel = static function (array $plan) use ($formatAmount): string {
    $label = (string) ($plan['name'] ?? '–');

    if (isset($plan['price_monthly'])) {
        $label .= ' – ' . $formatAmount($plan['price_monthly']) . ' €/Monat';
    }

    return $label;
};
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Aboverwaltung</div>
                <h2 class="page-title">Bestellungen &amp; Zuweisung</h2>
            </div>
            <div class="col-auto ms-auto">
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#assignModal" data-assign-reset="true">Zuweisen</button>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <?php
        $alertMarginClass = 'mb-4';
        include __DIR__ . '/../partials/flash-alert.php';
        ?>

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

        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?= htmlspecialchars($ordersBaseUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm <?= $filterButtonClass('') ?>">Alle</a>
                    <?php foreach ($statusLabels as $key => $s): ?>
                        <a href="<?= htmlspecialchars($ordersBaseUrl . '?status=' . rawurlencode($key), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm <?= $filterButtonClass($key) ?>">
                            <?= htmlspecialchars($s['label'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card mb-4">
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
                                        <?php if (!empty($order['plan_name'])): ?>
                                            <div><?= htmlspecialchars((string) $order['plan_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                        <strong><?= htmlspecialchars($orderNumberLabel($order), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($customerName($order), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="text-secondary small"><?= htmlspecialchars($customerEmail($order), ENT_QUOTES, 'UTF-8') ?></div>
                                    </td>
                                    <td>
                                        <strong><?= $formatAmount($order['total_amount'] ?? 0) ?> <?= htmlspecialchars((string) ($order['currency'] ?? 'EUR'), ENT_QUOTES, 'UTF-8') ?></strong>
                                        <?php if ((float)($order['tax_amount'] ?? 0) > 0): ?>
                                            <div class="text-secondary small">inkl. <?= $formatAmount($order['tax_amount']) ?> MwSt.</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php $statusMeta = $resolveStatusMeta((string) ($order['status'] ?? '')); ?>
                                        <span class="badge <?= htmlspecialchars($statusMeta['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusMeta['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                    <td class="text-secondary"><?= htmlspecialchars((string) ($order['payment_method'] ?? '–'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-secondary"><?= htmlspecialchars($formatDateTime(isset($order['created_at']) ? (string) $order['created_at'] : null), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <a href="#" class="btn-action" data-bs-toggle="dropdown"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/></svg></a>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <?php foreach ($statusTransitions as $st): ?>
                                                    <?php if ($st !== ($order['status'] ?? '')): ?>
                                                        <form method="post" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
                                                            <input type="hidden" name="status" value="<?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?>">
                                                            <button type="submit" class="dropdown-item">→ <?= htmlspecialchars($statusLabels[$st]['label'], ENT_QUOTES, 'UTF-8') ?></button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                                <?php if (!empty($order['user_id'])): ?>
                                                    <button type="button" class="dropdown-item" data-assign-order="<?= htmlspecialchars((string) json_encode($order, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>">Paket zuweisen</button>
                                                <?php endif; ?>
                                                <div class="dropdown-divider"></div>
                                                <button type="button" class="dropdown-item text-danger" data-delete-order-form="delOrder-<?= (int)$order['id'] ?>" data-delete-order-number="#<?= htmlspecialchars($orderNumberLabel($order), ENT_QUOTES, 'UTF-8') ?>">Löschen</button>
                                            </div>
                                        </div>
                                        <form id="delOrder-<?= (int)$order['id'] ?>" method="post" class="d-none">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
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

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Aktive Zuweisungen</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Benutzer</th>
                            <th>Paket</th>
                            <th>Status</th>
                            <th>Abrechnung</th>
                            <th>Laufzeit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assignments)): ?>
                            <tr><td colspan="5" class="text-center text-secondary py-4">Noch keine Zuweisungen vorhanden.</td></tr>
                        <?php else: ?>
                            <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td>
                                        <div><?= htmlspecialchars((string) ($assignment['username'] ?? $assignment['email'] ?? '–'), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="text-secondary small"><?= htmlspecialchars((string) ($assignment['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    </td>
                                    <td><strong><?= htmlspecialchars((string) ($assignment['plan_name'] ?? '–'), ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td><span class="badge bg-success"><?= htmlspecialchars((string) ($assignment['status'] ?? 'active'), ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= htmlspecialchars((string) ($assignment['billing_cycle'] ?? 'monthly'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-secondary">
                                        <?= htmlspecialchars($formatDate(isset($assignment['start_date']) ? (string) $assignment['start_date'] : null), ENT_QUOTES, 'UTF-8') ?>
                                        <?php if (!empty($assignment['end_date'])): ?>
                                            <div class="small">bis <?= htmlspecialchars($formatDate((string) $assignment['end_date']), ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
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

<div class="modal modal-blur fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="assign_subscription">
                <div class="modal-header">
                    <h5 class="modal-title">Paket zuweisen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Benutzer</label>
                        <select name="user_id" id="assign-user" class="form-select" required>
                            <option value="">– Benutzer wählen –</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= (int)$user['id'] ?>"><?= htmlspecialchars($userOptionLabel($user), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Paket</label>
                        <select name="plan_id" id="assign-plan" class="form-select" required>
                            <option value="">– Paket wählen –</option>
                            <?php foreach ($plans as $plan): ?>
                                <option value="<?= (int)$plan['id'] ?>"><?= htmlspecialchars($planOptionLabel($plan), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Abrechnungsintervall</label>
                        <select name="billing_cycle" id="assign-cycle" class="form-select">
                            <option value="monthly">Monatlich</option>
                            <option value="yearly">Jährlich</option>
                            <option value="lifetime">Lifetime</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Zuweisen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const assignUser = document.getElementById('assign-user');
    const assignPlan = document.getElementById('assign-plan');
    const assignCycle = document.getElementById('assign-cycle');
    const assignModalElement = document.getElementById('assignModal');

    const resetAssignForm = function () {
        if (assignUser) {
            assignUser.value = '';
        }
        if (assignPlan) {
            assignPlan.value = '';
        }
        if (assignCycle) {
            assignCycle.value = 'monthly';
        }
    };

    const openAssignFromOrder = function (order) {
        resetAssignForm();

        if (assignUser && order.user_id) {
            assignUser.value = String(order.user_id);
        }

        const linkedPlanId = order.plan_id || order.package_id || order.linked_plan_id || '';
        if (assignPlan && linkedPlanId) {
            assignPlan.value = String(linkedPlanId);
        }

        if (assignModalElement && typeof bootstrap !== 'undefined') {
            new bootstrap.Modal(assignModalElement).show();
        }
    };

    document.querySelectorAll('[data-assign-reset="true"]').forEach(function (button) {
        button.addEventListener('click', function () {
            resetAssignForm();
        });
    });

    document.querySelectorAll('[data-assign-order]').forEach(function (button) {
        button.addEventListener('click', function () {
            const payload = button.getAttribute('data-assign-order') || '{}';

            try {
                openAssignFromOrder(JSON.parse(payload));
            } catch (error) {
                resetAssignForm();
            }
        });
    });

    document.querySelectorAll('[data-delete-order-form]').forEach(function (button) {
        button.addEventListener('click', function () {
            const formId = button.getAttribute('data-delete-order-form') || '';
            const orderNumber = button.getAttribute('data-delete-order-number') || '#';
            const targetForm = formId !== '' ? document.getElementById(formId) : null;

            if (!targetForm || typeof cmsConfirm !== 'function') {
                return;
            }

            cmsConfirm({
                title: 'Bestellung löschen?',
                message: orderNumber,
                onConfirm: function () {
                    targetForm.submit();
                }
            });
        });
    });
})();
</script>
