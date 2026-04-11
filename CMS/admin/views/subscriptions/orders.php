<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$ordersBaseUrl = '/admin/orders';
$orders       = $data['orders'] ?? [];
$stats        = $data['stats'] ?? [];
$statusFilter = $data['filter'] ?? '';
$assignments  = $data['assignments'] ?? [];
$plans        = $data['plans'] ?? [];
$users        = $data['users'] ?? [];
$alertData    = is_array($alert ?? null) ? $alert : [];
$statusLabels = [
    'pending'   => ['label' => 'Offen',       'class' => 'bg-warning'],
    'paid'      => ['label' => 'Bezahlt',     'class' => 'bg-success'],
    'cancelled' => ['label' => 'Storniert',   'class' => 'bg-secondary'],
    'refunded'  => ['label' => 'Erstattet',   'class' => 'bg-info'],
    'failed'    => ['label' => 'Fehlgeschl.',  'class' => 'bg-danger'],
    'confirmed' => ['label' => 'Bezahlt',     'class' => 'bg-success'],
    'completed' => ['label' => 'Bezahlt',     'class' => 'bg-success'],
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
$renderMetricCard = static function (string $label, string $value, string $valueClass = ''): void {
    ?>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="subheader"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="h1 mb-0<?= $valueClass !== '' ? ' ' . htmlspecialchars($valueClass, ENT_QUOTES, 'UTF-8') : '' ?>"><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    </div>
    <?php
};
$renderEmptyTableRow = static function (int $colspan, string $message): void {
    ?>
    <tr>
        <td colspan="<?= $colspan ?>" class="text-center text-secondary py-4"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <?php
};
$renderStatusBadge = static function (string $label, string $class): void {
    ?>
    <span class="badge <?= htmlspecialchars($class, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
    <?php
};
$renderPrimarySecondaryText = static function (string $primary, string $secondary = ''): void {
    ?>
    <div><?= htmlspecialchars($primary, ENT_QUOTES, 'UTF-8') ?></div>
    <?php if ($secondary !== ''): ?>
        <div class="text-secondary small"><?= htmlspecialchars($secondary, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php
};
$renderSelectField = static function (string $wrapperClass, string $label, string $name, string $id, array $options, bool $required = false): void {
    ?>
    <div class="<?= htmlspecialchars($wrapperClass, ENT_QUOTES, 'UTF-8') ?>">
        <label class="form-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
        <select name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" class="form-select"<?= $required ? ' required' : '' ?>>
            <?php foreach ($options as $option): ?>
                <option value="<?= htmlspecialchars((string) ($option['value'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($option['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php
};
$renderOrderFormContext = static function (string $csrfToken, string $action, ?int $orderId = null): void {
    ?>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="action" value="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($orderId !== null): ?>
        <input type="hidden" name="id" value="<?= $orderId ?>">
    <?php endif; ?>
    <?php
};
$renderOrderStatusAction = static function (string $csrfToken, int $orderId, string $statusValue, string $label) use ($renderOrderFormContext): void {
    ?>
    <form method="post" class="d-inline">
        <?php $renderOrderFormContext($csrfToken, 'update_status', $orderId); ?>
        <input type="hidden" name="status" value="<?= htmlspecialchars($statusValue, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="dropdown-item">→ <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></button>
    </form>
    <?php
};
$renderOrderDeleteForm = static function (string $csrfToken, int $orderId, string $formId) use ($renderOrderFormContext): void {
    ?>
    <form id="<?= htmlspecialchars($formId, ENT_QUOTES, 'UTF-8') ?>" method="post" class="d-none">
        <?php $renderOrderFormContext($csrfToken, 'delete', $orderId); ?>
    </form>
    <?php
};
$orderAssignPayload = static fn (array $order): string => htmlspecialchars((string) json_encode($order, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
$renderOrderActionsMenu = static function (
    string $csrfToken,
    array $order,
    array $availableTransitions,
    array $statusLabels,
    string $deleteFormId
) use ($renderOrderStatusAction, $orderAssignPayload): void {
    $orderId = (int) ($order['id'] ?? 0);
    ?>
    <div class="dropdown">
        <a href="#" class="btn-action" data-bs-toggle="dropdown"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/></svg></a>
        <div class="dropdown-menu dropdown-menu-end">
            <?php foreach ($availableTransitions as $statusTransition): ?>
                <?php $renderOrderStatusAction($csrfToken, $orderId, $statusTransition, (string) $statusLabels[$statusTransition]['label']); ?>
            <?php endforeach; ?>
            <?php if (!empty($order['user_id'])): ?>
                <button type="button" class="dropdown-item" data-assign-order="<?= $orderAssignPayload($order) ?>">Paket zuweisen</button>
            <?php endif; ?>
            <div class="dropdown-divider"></div>
            <button type="button" class="dropdown-item text-danger" data-delete-order-form="<?= htmlspecialchars($deleteFormId, ENT_QUOTES, 'UTF-8') ?>" data-delete-order-number="#<?= htmlspecialchars((string) ($order['order_number'] ?? '#' . $orderId), ENT_QUOTES, 'UTF-8') ?>">Löschen</button>
        </div>
    </div>
    <?php
};
$statusOptions = static function (array $currentOrder) use ($statusLabels): array {
    $transitions = $currentOrder['available_transitions'] ?? [];

    if (!is_array($transitions)) {
        return [];
    }

    return array_values(array_filter(
        array_map('strval', $transitions),
        static fn (string $candidate): bool => isset($statusLabels[$candidate])
    ));
};
$assignmentUserLabel = static fn (array $assignment): string => (string) ($assignment['username'] ?? $assignment['email'] ?? '–');
$assignmentEmailLabel = static fn (array $assignment): string => (string) ($assignment['email'] ?? '');
$assignmentPlanLabel = static fn (array $assignment): string => (string) ($assignment['plan_name'] ?? '–');
$assignmentStatusClass = static fn (array $assignment): string => ((string) ($assignment['status'] ?? 'active')) === 'active' ? 'bg-success' : 'bg-secondary';
$assignmentStatusLabel = static fn (array $assignment): string => (string) ($assignment['status'] ?? 'active');
$assignmentBillingLabel = static fn (array $assignment): string => (string) ($assignment['billing_cycle'] ?? 'monthly');
$assignmentRangeLabel = static function (array $assignment) use ($formatDate): array {
    $startDate = $formatDate(isset($assignment['start_date']) ? (string) $assignment['start_date'] : null);
    $endDate = !empty($assignment['end_date']) ? 'bis ' . $formatDate((string) $assignment['end_date']) : '';

    return ['start' => $startDate, 'end' => $endDate];
};
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
$billingCycleOptions = [
    'monthly' => 'Monatlich',
    'yearly' => 'Jährlich',
    'lifetime' => 'Lifetime',
];

$metricCards = [
    ['label' => 'Gesamt', 'value' => (string) ((int) ($stats['total'] ?? 0)), 'class' => ''],
    ['label' => 'Offen', 'value' => (string) ((int) ($stats['pending'] ?? 0)), 'class' => 'text-warning'],
    ['label' => 'Bezahlt', 'value' => (string) ((int) ($stats['paid'] ?? 0)), 'class' => 'text-success'],
    ['label' => 'Umsatz', 'value' => $formatAmount($stats['revenue'] ?? 0) . ' €', 'class' => ''],
];
$assignUserOptions = array_merge(
    [['value' => '', 'label' => '– Benutzer wählen –']],
    array_map(
        static fn (array $user): array => [
            'value' => (string) ((int) ($user['id'] ?? 0)),
            'label' => $userOptionLabel($user),
        ],
        $users
    )
);
$assignPlanOptions = array_merge(
    [['value' => '', 'label' => '– Paket wählen –']],
    array_map(
        static fn (array $plan): array => [
            'value' => (string) ((int) ($plan['id'] ?? 0)),
            'label' => $planOptionLabel($plan),
        ],
        $plans
    )
);
$assignCycleOptions = array_map(
    static fn (string $label, string $value): array => ['value' => $value, 'label' => $label],
    $billingCycleOptions,
    array_keys($billingCycleOptions)
);
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
            <?php foreach ($metricCards as $metricCard): ?>
                <?php $renderMetricCard($metricCard['label'], $metricCard['value'], $metricCard['class']); ?>
            <?php endforeach; ?>
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
                            <?php $renderEmptyTableRow(7, 'Keine Bestellungen gefunden.'); ?>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <?php
                                $statusMeta = $resolveStatusMeta((string) ($order['status'] ?? ''));
                                $availableTransitions = $statusOptions($order);
                                $deleteFormId = 'delOrder-' . (int) ($order['id'] ?? 0);
                                ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($order['plan_name'])): ?>
                                            <div><?= htmlspecialchars((string) $order['plan_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                        <strong><?= htmlspecialchars($orderNumberLabel($order), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </td>
                                    <td>
                                        <?php $renderPrimarySecondaryText($customerName($order), $customerEmail($order)); ?>
                                    </td>
                                    <td>
                                        <strong><?= $formatAmount($order['total_amount'] ?? 0) ?> <?= htmlspecialchars((string) ($order['currency'] ?? 'EUR'), ENT_QUOTES, 'UTF-8') ?></strong>
                                        <?php if ((float)($order['tax_amount'] ?? 0) > 0): ?>
                                            <div class="text-secondary small">inkl. <?= $formatAmount($order['tax_amount']) ?> MwSt.</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php $renderStatusBadge((string) $statusMeta['label'], (string) $statusMeta['class']); ?>
                                    </td>
                                    <td class="text-secondary"><?= htmlspecialchars((string) ($order['payment_method'] ?? '–'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-secondary"><?= htmlspecialchars($formatDateTime(isset($order['created_at']) ? (string) $order['created_at'] : null), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php $renderOrderActionsMenu($csrfToken, $order, $availableTransitions, $statusLabels, $deleteFormId); ?>
                                        <?php $renderOrderDeleteForm($csrfToken, (int) ($order['id'] ?? 0), $deleteFormId); ?>
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
                            <?php $renderEmptyTableRow(5, 'Noch keine Zuweisungen vorhanden.'); ?>
                        <?php else: ?>
                            <?php foreach ($assignments as $assignment): ?>
                                <?php $assignmentRange = $assignmentRangeLabel($assignment); ?>
                                <tr>
                                    <td>
                                        <?php $renderPrimarySecondaryText($assignmentUserLabel($assignment), $assignmentEmailLabel($assignment)); ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($assignmentPlanLabel($assignment), ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td><?php $renderStatusBadge($assignmentStatusLabel($assignment), $assignmentStatusClass($assignment)); ?></td>
                                    <td><?= htmlspecialchars($assignmentBillingLabel($assignment), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-secondary">
                                        <?= htmlspecialchars($assignmentRange['start'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php if ($assignmentRange['end'] !== ''): ?>
                                            <div class="small"><?= htmlspecialchars($assignmentRange['end'], ENT_QUOTES, 'UTF-8') ?></div>
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
                <?php $renderOrderFormContext($csrfToken, 'assign_subscription'); ?>
                <div class="modal-header">
                    <h5 class="modal-title">Paket zuweisen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <?php $renderSelectField('mb-3', 'Benutzer', 'user_id', 'assign-user', $assignUserOptions, true); ?>
                    <?php $renderSelectField('mb-3', 'Paket', 'plan_id', 'assign-plan', $assignPlanOptions, true); ?>
                    <?php $renderSelectField('mb-0', 'Abrechnungsintervall', 'billing_cycle', 'assign-cycle', $assignCycleOptions); ?>
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
