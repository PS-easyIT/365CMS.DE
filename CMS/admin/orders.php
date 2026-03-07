<?php
declare(strict_types=1);

/**
 * Bestellungen – Entry Point
 * Route: /admin/orders
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/subscriptions/OrdersModule.php';
$module    = new OrdersModule();
$alert     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_orders')) {
        $alert = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
    } else {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'assign_subscription':
                $result = $module->assignSubscription(
                    (int)($_POST['user_id'] ?? 0),
                    (int)($_POST['plan_id'] ?? 0),
                    (string)($_POST['billing_cycle'] ?? 'monthly')
                );
                $_SESSION['admin_alert'] = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message'] ?? $result['error'] ?? ''];
                header('Location: ' . SITE_URL . '/admin/orders');
                exit;

            case 'update_status':
                $id     = (int)($_POST['id'] ?? 0);
                $status = $_POST['status'] ?? '';
                $result = $module->updateStatus($id, $status);
                $_SESSION['admin_alert'] = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message'] ?? $result['error'] ?? ''];
                header('Location: ' . SITE_URL . '/admin/orders');
                exit;

            case 'delete':
                $id     = (int)($_POST['id'] ?? 0);
                $result = $module->delete($id);
                $_SESSION['admin_alert'] = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message'] ?? $result['error'] ?? ''];
                header('Location: ' . SITE_URL . '/admin/orders');
                exit;
        }
    }
    $csrfToken = Security::instance()->generateToken('admin_orders');
}

if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken    = Security::instance()->generateToken('admin_orders');
$statusFilter = $_GET['status'] ?? '';
$pageTitle    = 'Bestellungen & Zuweisung';
$activePage   = 'orders';
$data         = $module->getData($statusFilter);

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/subscriptions/orders.php';
require_once __DIR__ . '/partials/footer.php';
