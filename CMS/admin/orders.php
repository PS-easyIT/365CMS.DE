<?php
declare(strict_types=1);

/**
 * Orders Admin Page
 * 
 * @package CMSv2\Admin
 */

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';
require_once ABSPATH . 'includes/functions.php';

use CMS\Auth;
use CMS\Database;
use CMS\Security;
use CMS\SubscriptionManager;

if (!defined('ABSPATH')) {
    exit;
}

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$db = Database::instance();
$prefix = $db->getPrefix();
$subMgr = SubscriptionManager::instance();

$message = '';
$error   = '';

// Handle Status Change
if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'update_order_status')) {
        $error = 'Sicherheitsfehler.';
    } else {
        $orderId = (int)$_POST['order_id'];
        $newStatus = $_POST['status'];
        if (in_array($newStatus, ['pending', 'confirmed', 'cancelled', 'refunded'])) {
            $db->update("{$prefix}orders", ['status' => $newStatus], ['id' => $orderId]);
            $message = 'Status aktualisiert.';
            
            // If completed/confirmed, activate subscription logic should be triggered
            // Use SubscriptionManager::activateSubscription(...)
            if ($newStatus === 'confirmed') {
                 $order = $db->get_row("SELECT * FROM {$prefix}orders WHERE id = %d", $orderId);
                 if ($order) {
                     // TODO: implement logic to activate subscription for period
                     // $subMgr->createOrUpdateSubscription($order->user_id, $order->plan_id);
                 }
            }
        }
    }
}

// Fetch Orders
$page = (int)($_GET['p'] ?? 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Join with users and plans, sort by created_at DESC
$sql = "SELECT o.*, u.email as user_email, u.display_name, p.name as plan_name, p.price_monthly, p.price_yearly
        FROM {$prefix}orders o 
        LEFT JOIN {$prefix}users u ON o.user_id = u.id 
        LEFT JOIN {$prefix}subscription_plans p ON o.plan_id = p.id 
        ORDER BY o.created_at DESC 
        LIMIT $perPage OFFSET $offset";
        
$orders = $db->get_results($sql);
$total = $db->get_var("SELECT COUNT(*) FROM {$prefix}orders");
$totalPages = ceil($total / $perPage);

$csrfToken = Security::instance()->generateToken('update_order_status');

function getStatusBadge(string $status): string {
    return match($status) {
        'confirmed', 'completed' => '<span class="status-badge active">Bestätigt</span>',
        'cancelled'             => '<span class="status-badge" style="background:#fee2e2;color:#991b1b;">Storniert</span>',
        'refunded'              => '<span class="status-badge inactive">Erstattet</span>',
        default                 => '<span class="status-badge" style="background:#fef3c7;color:#92400e;">Ausstehend</span>',
    };
}

// Load admin menu
require_once __DIR__ . '/partials/admin-menu.php';
renderAdminLayoutStart('Bestellungen', 'orders');
?>

<div class="admin-page-header">
    <div>
        <h2>🧾 Bestellungen</h2>
        <p><?php echo (int)$total; ?> Bestellung<?php echo $total != 1 ? 'en' : ''; ?> gesamt</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="users-table-container">
    <table class="users-table">
        <thead>
            <tr>
                <th>Bestell-Nr.</th>
                <th>Kunde</th>
                <th>Paket</th>
                <th>Betrag</th>
                <th>Datum</th>
                <th>Status</th>
                <th>Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
            <tr><td colspan="7" style="text-align:center;color:#64748b;padding:2rem;">Keine Bestellungen gefunden.</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td style="font-weight:600;color:#1e293b;"><?php echo htmlspecialchars($order->order_number); ?></td>
                    <td>
                        <div style="font-weight:500;color:#1e293b;"><?php echo htmlspecialchars(trim(($order->forename ?? '') . ' ' . ($order->lastname ?? ''))); ?></div>
                        <div style="font-size:.78rem;color:#64748b;"><?php echo htmlspecialchars($order->email ?? ''); ?></div>
                        <?php if (!empty($order->company)): ?>
                            <div style="font-size:.78rem;color:#94a3b8;font-style:italic;"><?php echo htmlspecialchars($order->company); ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge-plan"><?php echo htmlspecialchars($order->plan_name ?? 'Unbekannt'); ?></span>
                        <div style="font-size:.74rem;color:#94a3b8;margin-top:.2rem;"><?php echo $order->billing_cycle === 'yearly' ? 'Jährlich' : 'Monatlich'; ?></div>
                    </td>
                    <td style="font-weight:600;color:#1e293b;">
                        <?php echo number_format((float)($order->total_amount ?? 0), 2, ',', '.'); ?> <?php echo htmlspecialchars($order->currency ?? 'EUR'); ?>
                    </td>
                    <td>
                        <div style="color:#374151;"><?php echo date('d.m.Y', strtotime($order->created_at)); ?></div>
                        <div style="font-size:.78rem;color:#94a3b8;"><?php echo date('H:i', strtotime($order->created_at)); ?></div>
                    </td>
                    <td><?php echo getStatusBadge($order->status); ?></td>
                    <td style="text-align:right; white-space:nowrap;">
                        <div style="display:flex;gap:.4rem;align-items:center;justify-content:flex-end;">
                            <button onclick="viewOrderDetails(<?php echo htmlspecialchars(json_encode($order)); ?>)" class="btn btn-sm btn-secondary" title="Details">👁️</button>
                            <?php if ($order->status === 'pending'): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Bestellung als bezahlt markieren?');">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="order_id" value="<?php echo $order->id; ?>">
                                <input type="hidden" name="status" value="confirmed">
                                <button type="submit" class="btn btn-sm btn-primary" title="Als bezahlt markieren">✅</button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Bestellung stornieren?');">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="order_id" value="<?php echo $order->id; ?>">
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" class="btn btn-sm btn-danger" title="Stornieren">❌</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<div style="display:flex;gap:.5rem;justify-content:center;margin-top:1.5rem;flex-wrap:wrap;">
    <?php if ($page > 1): ?>
        <a href="?p=<?php echo $page - 1; ?>" class="btn btn-secondary btn-sm">← Zurück</a>
    <?php endif; ?>
    <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
        <a href="?p=<?php echo $i; ?>" class="btn btn-sm <?php echo $page === $i ? 'btn-primary' : 'btn-secondary'; ?>"><?php echo $i; ?></a>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
        <a href="?p=<?php echo $page + 1; ?>" class="btn btn-secondary btn-sm">Weiter →</a>
    <?php endif; ?>
</div>
<?php endif; ?>


<!-- Details Modal -->
<div id="orderModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:640px;">
        <div class="modal-header">
            <h3>Bestelldetails <span id="modalOrderNum" style="color:var(--admin-primary);"></span></h3>
            <button class="modal-close" onclick="closeOrderModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalContent"></div>
    </div>
</div>

<script>
function viewOrderDetails(order) {
    const modal = document.getElementById('orderModal');
    document.getElementById('modalOrderNum').textContent = '#' + order.order_number;
    modal.style.display = 'flex';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    
    const html = `<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
        <div>
            <h4 style="font-size:.78rem;font-weight:700;color:#94a3b8;text-transform:uppercase;margin:0 0 .6rem;">Rechnungsadresse</h4>
            <p style="margin:0;font-size:.875rem;line-height:1.7;color:#374151;">
                ${order.company ? `<strong>${order.company}</strong><br>` : ''}
                ${order.forename || ''} ${order.lastname || ''}<br>
                ${order.street || ''}<br>
                ${order.zip || ''} ${order.city || ''}<br>
                ${order.country || ''}
            </p>
            <div style="margin-top:.75rem;font-size:.82rem;color:#475569;">
                📧 <a href="mailto:${order.user_email||order.email||''}" style="color:#2563eb;">${order.user_email||order.email||''}</a>
                ${order.phone ? `<br>📞 ${order.phone}` : ''}
            </div>
        </div>
        <div>
            <h4 style="font-size:.78rem;font-weight:700;color:#94a3b8;text-transform:uppercase;margin:0 0 .6rem;">Bestellinfos</h4>
            <dl style="margin:0;font-size:.875rem;line-height:1.9;color:#374151;">
                <dt style="font-weight:600;display:inline;">Zahlungsmethode:</dt> <dd style="display:inline;margin:0;">${order.payment_method||'–'}</dd><br>
                <dt style="font-weight:600;display:inline;">Zyklus:</dt> <dd style="display:inline;margin:0;">${order.billing_cycle==='yearly'?'Jährlich':'Monatlich'}</dd><br>
                <dt style="font-weight:600;display:inline;">Betrag:</dt> <dd style="display:inline;margin:0;font-weight:700;">${parseFloat(order.total_amount||0).toFixed(2)} ${order.currency||'EUR'}</dd><br>
                <dt style="font-weight:600;display:inline;">Erstellt:</dt> <dd style="display:inline;margin:0;">${new Date(order.created_at).toLocaleString('de-DE')}</dd>
            </dl>
        </div>
    </div>`;
    document.getElementById('modalContent').innerHTML = html;
}

function closeOrderModal() {
    document.getElementById('orderModal').style.display = 'none';
}
document.getElementById('orderModal').addEventListener('click', function(e) {
    if (e.target === this) closeOrderModal();
});
</script>
<?php renderAdminLayoutEnd(); ?>
