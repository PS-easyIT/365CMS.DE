<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/bootstrap.php';

if (class_exists('\CMS\Services\CoreModuleService')
    && !\CMS\Services\CoreModuleService::getInstance()->isModuleEnabled('subscription_member_area')) {
    header('Location: /member/dashboard');
    exit;
}

$pageTitle = 'Abo & Bestellungen';
$pageKey = 'subscription';
$pageAssets = [];
$memberService = \CMS\Services\MemberService::getInstance();
$subscription = $memberService->getUserSubscription($controller->getUserId());
$renewalNotice = $memberService->getSubscriptionRenewalNotice($controller->getUserId());
$packages = $memberService->getAvailablePackages();
$orders = $controller->getOrders();
$renewalAlertClass = match ((string) ($renewalNotice['severity'] ?? 'info')) {
    'danger' => 'alert-danger',
    'warning' => 'alert-warning',
    default => 'alert-info',
};

include __DIR__ . '/partials/header.php';
?>
<div class="row g-4">
    <div class="col-xl-4">
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Aktives Paket</h3></div>
            <div class="card-body">
                <?php if ($subscription !== null): ?>
                    <div class="display-6 mb-2"><?= htmlspecialchars((string)($subscription->package_name ?? $subscription->name ?? 'Paket')) ?></div>
                    <?php if (!empty($subscription->status)): ?>
                        <div class="badge bg-primary-lt mb-3"><?= htmlspecialchars((string)$subscription->status) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($renewalNotice['due_label'])): ?>
                        <div class="text-secondary mb-3">
                            <?= htmlspecialchars(!empty($renewalNotice['is_auto_renewal']) ? 'Nächste Verlängerung: ' : 'Laufzeit bis: ') ?><?= htmlspecialchars((string)$renewalNotice['due_label']) ?>
                        </div>
                    <?php elseif (!empty($subscription->end_date)): ?>
                        <div class="text-secondary mb-3">Läuft bis <?= htmlspecialchars((string)$subscription->end_date) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($renewalNotice['has_notice'])): ?>
                        <div class="alert <?= htmlspecialchars($renewalAlertClass, ENT_QUOTES) ?> mb-0" role="alert">
                            <?php if (!empty($renewalNotice['title'])): ?>
                                <div class="fw-semibold mb-1"><?= htmlspecialchars((string)$renewalNotice['title']) ?></div>
                            <?php endif; ?>
                            <div><?= htmlspecialchars((string)($renewalNotice['message'] ?? '')) ?></div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-secondary">Aktuell ist kein aktives Paket hinterlegt.</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Buchbare Pakete</h3></div>
            <div class="list-group list-group-flush">
                <?php if ($packages === []): ?>
                    <div class="card-body text-secondary">Keine aktiven Pakete gefunden.</div>
                <?php else: ?>
                    <?php foreach ($packages as $package): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <div class="fw-medium"><?= htmlspecialchars((string)($package->name ?? 'Paket')) ?></div>
                                    <div class="text-secondary small"><?= htmlspecialchars((string)($package->description ?? '')) ?></div>
                                </div>
                                <div class="text-end">
                                    <?php if (isset($package->price_monthly)): ?>
                                        <div class="fw-medium">€ <?= number_format((float)$package->price_monthly, 2, ',', '.') ?></div>
                                        <div class="text-secondary small">monatlich</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Bestellhistorie</h3></div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter">
                    <thead>
                        <tr>
                            <th>Bestellung</th>
                            <th>Paket</th>
                            <th>Status</th>
                            <th>Betrag</th>
                            <th>Datum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders === []): ?>
                            <tr><td colspan="5" class="text-secondary">Noch keine Bestellungen vorhanden.</td></tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <?php
                                $status = strtolower(trim((string)($order->normalized_status ?? $order->status ?? 'pending')));
                                $statusLabel = match ($status) {
                                    'paid' => 'Bezahlt',
                                    'pending' => 'Offen',
                                    'cancelled' => 'Storniert',
                                    'refunded' => 'Erstattet',
                                    'failed' => 'Fehlgeschlagen',
                                    default => $status !== '' ? $status : 'Unbekannt',
                                };
                                $statusClass = match ($status) {
                                    'paid' => 'green',
                                    'pending' => 'yellow',
                                    'cancelled', 'failed' => 'red',
                                    'refunded' => 'azure',
                                    default => 'secondary',
                                };
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-medium"><?= htmlspecialchars((string)($order->order_number ?? ('#' . (int)($order->id ?? 0)))) ?></div>
                                        <div class="text-secondary small"><?= htmlspecialchars((string)($order->resolved_customer_email ?? $order->customer_email ?? $order->email ?? '')) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars((string)($order->plan_name ?? 'Unbekannt')) ?></td>
                                    <td><span class="badge bg-<?= htmlspecialchars($statusClass) ?>-lt"><?= htmlspecialchars($statusLabel) ?></span></td>
                                    <td><?= isset($order->display_amount) ? '€ ' . number_format((float)$order->display_amount, 2, ',', '.') : '–' ?></td>
                                    <td><?= htmlspecialchars((string)($order->created_at ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php';
