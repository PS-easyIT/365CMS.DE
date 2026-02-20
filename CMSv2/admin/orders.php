<?php
/**
 * Orders Admin Page
 * 
 * @package CMSv2\Admin
 */

declare(strict_types=1);

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

function getStatusBadge($status) {
    switch ($status) {
        case 'confirmed': return '<span class="status-badge status-success">Bestätigt</span>';
        case 'completed': return '<span class="status-badge status-success">Abgeschlossen</span>'; // Legacy/Alias
        case 'cancelled': return '<span class="status-badge status-danger">Storniert</span>';
        case 'refunded': return '<span class="status-badge status-secondary">Erstattet</span>';
        case 'confirmed': return '<span class="status-badge status-info">Bestätigt</span>';
        default: return '<span class="status-badge status-warning">Ausstehend</span>';
    }
}

// Load admin menu
require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bestellungen - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=<?php echo CMS_VERSION; ?>">
    <?php renderAdminSidebarStyles(); ?>
    <style>
        .badge { display:inline-block; padding:0.25rem 0.5rem; border-radius:4px; font-size:0.75rem; font-weight:600; color:white; }
        .badge-success { background:#22c55e; }
        .badge-warning { background:#eab308; }
        .badge-danger { background:#ef4444; }
        .badge-outline { border:1px solid #ddd; color:#666; background:#fff; padding:2px 6px; border-radius:4px; font-size:11px; }
        .pagination { display:flex; gap:0.5rem; margin-top:2rem; }
        .pagination a { padding:0.5rem 1rem; background:#fff; border:1px solid #ddd; text-decoration:none; color:#333; }
        .pagination a.active { background:#2563eb; color:white; border-color:#2563eb; }
        .action-buttons .btn-icon { width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; border-radius:4px; border:1px solid transparent; background:none; cursor:pointer; }
        .action-buttons .btn-icon:hover { background:#f1f5f9; }
        .status-badge { padding:4px 8px; border-radius:4px; font-size:12px; font-weight:500; }
        .status-success { background:#dcfce7; color:#166534; }
        .status-warning { background:#fef9c3; color:#854d0e; }
        .status-danger { background:#fee2e2; color:#991b1b; }
        .status-info { background:#dbeafe; color:#1e40af; }
        .status-secondary { background:#f1f5f9; color:#475569; }
    </style>
</head>
<body class="admin-body">
    
    <?php renderAdminSidebar('orders'); ?>
    
    <main class="admin-content">

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="data-table">
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
                    <tr>
                        <td colspan="7" class="text-center py-4">Keine Bestellungen gefunden.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="font-medium"><?php echo htmlspecialchars($order->order_number); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($order->forename . ' ' . $order->lastname); ?></div>
                                <div class="text-sm text-muted"><?php echo htmlspecialchars($order->email); ?></div>
                                <?php if($order->company): ?>
                                    <div class="text-sm text-muted font-italic"><?php echo htmlspecialchars($order->company); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-outline">
                                    <?php echo htmlspecialchars($order->plan_name ?? 'Unbekannt'); ?>
                                </span>
                                <div class="text-xs text-muted mt-1">
                                    <?php echo $order->billing_cycle === 'yearly' ? 'Jährlich' : 'Monatlich'; ?>
                                </div>
                            </td>
                            <td class="font-medium">
                                <?php echo number_format((float)$order->total_amount, 2, ',', '.'); ?> <?php echo htmlspecialchars($order->currency ?? 'EUR'); ?>
                            </td>
                            <td>
                                <?php echo date('d.m.Y', strtotime($order->created_at)); ?>
                                <small class="d-block text-muted"><?php echo date('H:i', strtotime($order->created_at)); ?></small>
                            </td>
                            <td><?php echo getStatusBadge($order->status); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button onclick="viewOrderDetails(<?php echo htmlspecialchars(json_encode($order)); ?>)" class="btn btn-sm btn-icon btn-ghost" title="Details">
                                        👁️
                                    </button>
                                    
                                    <?php if ($order->status === 'pending'): ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Bestellung als bezahlt markieren?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo $order->id; ?>">
                                        <input type="hidden" name="status" value="confirmed">
                                        <button type="submit" class="btn btn-sm btn-icon btn-success-light" title="Als bezahlt markieren">
                                            ✅
                                        </button>
                                    </form>
                                    
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Bestellung stornieren?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo $order->id; ?>">
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" class="btn btn-sm btn-icon btn-danger-light" title="Stornieren">
                                            ❌
                                        </button>
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
    </div>
    
    <?php if ($totalPages > 1): ?>
    <div class="card-footer">
        <div class="pagination">
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?p=<?php echo $i; ?>" class="page-link <?php echo $page === $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Details Modal (Basic Implementation) -->
<div id="orderModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
    <div class="modal-dialog" style="background:white; max-width:600px; margin:50px auto; border-radius:8px; padding:20px; box-shadow:0 4px 12px rgba(0,0,0,0.15);">
        <div class="modal-header d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0">Bestelldetails <span id="modalOrderNum"></span></h3>
            <button onclick="document.getElementById('orderModal').style.display='none'" class="btn-close" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <div class="modal-body" id="modalContent">
            <!-- Content filled by JS -->
        </div>
    </div>
</div>

<script>
function viewOrderDetails(order) {
    const modal = document.getElementById('orderModal');
    document.getElementById('modalOrderNum').textContent = order.order_number;
    
    const billingHtml = `
        <div class="grid grid-2 gap-4 mb-4">
            <div>
                <h4 class="text-sm font-bold text-muted uppercase mb-2">Rechnungsadresse</h4>
                <p>
                    ${order.company ? `<strong>${order.company}</strong><br>` : ''}
                    ${order.forename} ${order.lastname}<br>
                    ${order.street}<br>
                    ${order.zip} ${order.city}<br>
                    ${order.country}
                </p>
                <div class="mt-2">
                    📧 <a href="mailto:${order.user_email || order.email}">${order.user_email || order.email}</a><br>
                    ${order.phone ? `📞 ${order.phone}` : ''}
                </div>
            </div>
            <div>
                <h4 class="text-sm font-bold text-muted uppercase mb-2">Bestellinfos</h4>
                <p>
                    <strong>Zahlungsmethode:</strong> ${order.payment_method}<br>
                    <strong>Zyklus:</strong> ${order.billing_cycle === 'yearly' ? 'Jährlich' : 'Monatlich'}<br>
                    <strong>Erstellt am:</strong> ${new Date(order.created_at).toLocaleString('de-DE')}
                </p>
            </div>
        </div>
    `;
    
    document.getElementById('modalContent').innerHTML = billingHtml;
    modal.style.display = 'block';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('orderModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>

    </main>
</body>
</html>
