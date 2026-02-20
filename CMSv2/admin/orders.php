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
        if (in_array($newStatus, ['pending', 'completed', 'cancelled'])) {
            $db->update("{$prefix}orders", ['status' => $newStatus], ['id' => $orderId]);
            $message = 'Status aktualisiert.';
            
            // If completed, activate subscription logic should be triggered
            // Use SubscriptionManager::activateSubscription(...)
            if ($newStatus === 'completed') {
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

$sql = "SELECT o.*, u.user_email, u.display_name, p.name as plan_name 
        FROM {$prefix}orders o 
        LEFT JOIN {$prefix}users u ON o.user_id = u.ID 
        LEFT JOIN {$prefix}subscription_plans p ON o.plan_id = p.id 
        ORDER BY o.created_at DESC 
        LIMIT $perPage OFFSET $offset";
        
$orders = $db->get_results($sql);
$total = $db->get_var("SELECT COUNT(*) FROM {$prefix}orders");
$totalPages = ceil($total / $perPage);

$csrfToken = Security::instance()->generateToken('update_order_status');

// Helper for status badges
function getStatusBadge($status) {
    switch ($status) {
        case 'completed': return '<span class="badge badge-success">Abgeschlossen</span>';
        case 'cancelled': return '<span class="badge badge-danger">Storniert</span>';
        default: return '<span class="badge badge-warning">Ausstehend</span>';
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Bestellungen verwalten - CMS Admin</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <style>
        .badge { display:inline-block; padding:0.25rem 0.5rem; border-radius:4px; font-size:0.75rem; font-weight:600; color:white; }
        .badge-success { background:#22c55e; }
        .badge-warning { background:#eab308; }
        .badge-danger { background:#ef4444; }
        .pagination { display:flex; gap:0.5rem; margin-top:2rem; }
        .pagination a { padding:0.5rem 1rem; background:#fff; border:1px solid #ddd; text-decoration:none; color:#333; }
        .pagination a.active { background:#2563eb; color:white; border-color:#2563eb; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <!-- Sidebar placeholder, should be centralized -->
            <?php include __DIR__ . '/partials/sidebar.php'; ?>
        </aside>
        
        <main class="admin-content">
            <header class="content-header">
                <h1>Bestellungen</h1>
            </header>
            
            <?php if (isset($message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <table class="table" style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom:2px solid #f1f5f9; text-align:left;">
                            <th style="padding:1rem;">Nr.</th>
                            <th style="padding:1rem;">Kunde</th>
                            <th style="padding:1rem;">Paket</th>
                            <th style="padding:1rem;">Betrag</th>
                            <th style="padding:1rem;">Datum</th>
                            <th style="padding:1rem;">Status</th>
                            <th style="padding:1rem;">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders): foreach ($orders as $o): ?>
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:1rem;"><strong>#<?php echo htmlspecialchars($o->order_number); ?></strong></td>
                            <td style="padding:1rem;">
                                <div><?php echo htmlspecialchars($o->display_name ?? 'Gast'); ?></div>
                                <small style="color:#64748b;"><?php echo htmlspecialchars($o->user_email); ?></small>
                            </td>
                            <td style="padding:1rem;"><?php echo htmlspecialchars($o->plan_name); ?></td>
                            <td style="padding:1rem;"><?php echo number_format((float)$o->total_amount, 2); ?> €</td>
                            <td style="padding:1rem;"><?php echo date('d.m.Y H:i', strtotime($o->created_at)); ?></td>
                            <td style="padding:1rem;"><?php echo getStatusBadge($o->status); ?></td>
                            <td style="padding:1rem;">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?php echo $o->id; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <select name="status" onchange="this.form.submit()" style="padding:0.25rem; border:1px solid #ddd; border-radius:4px;">
                                        <option value="pending" <?php echo $o->status === 'pending' ? 'selected' : ''; ?>>Ausstehend</option>
                                        <option value="completed" <?php echo $o->status === 'completed' ? 'selected' : ''; ?>>Abgeschlossen</option>
                                        <option value="cancelled" <?php echo $o->status === 'cancelled' ? 'selected' : ''; ?>>Storniert</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="7" style="padding:2rem; text-align:center;">Keine Bestellungen gefunden.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for($i=1; $i<=$totalPages; $i++): ?>
                        <a href="?p=<?php echo $i; ?>" class="<?php echo $page === $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
