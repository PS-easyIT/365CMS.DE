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

/**
 * @return array{assign_subscription:update_status|delete}|array{}
 */
function cms_admin_orders_allowed_actions(): array
{
    return [
        'assign_subscription' => 'assign_subscription',
        'update_status' => 'update_status',
        'delete' => 'delete',
    ];
}

function cms_admin_orders_redirect(): never
{
    header('Location: ' . SITE_URL . '/admin/orders');
    exit;
}

function cms_admin_orders_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string)($payload['message'] ?? '')),
    ];
}

function cms_admin_orders_flash_result(OrdersActionResult $result): void
{
    $payload = $result->toArray();

    cms_admin_orders_flash([
        'type' => !empty($payload['success']) ? 'success' : 'danger',
        'message' => (string)($payload['message'] ?? $payload['error'] ?? ''),
    ]);
}

function cms_admin_orders_normalize_status_filter(string $status): string
{
    $status = strtolower(trim($status));
    $allowed = ['pending', 'paid', 'cancelled', 'refunded', 'failed'];

    return in_array($status, $allowed, true) ? $status : '';
}

function cms_admin_orders_handle_action(OrdersModule $module, string $action, array $post): ?OrdersActionResult
{
    return match ($action) {
        'assign_subscription' => $module->assignSubscription(
            (int)($post['user_id'] ?? 0),
            (int)($post['plan_id'] ?? 0),
            (string)($post['billing_cycle'] ?? 'monthly')
        ),
        'update_status' => $module->updateStatus(
            (int)($post['id'] ?? 0),
            (string)($post['status'] ?? '')
        ),
        'delete' => $module->delete((int)($post['id'] ?? 0)),
        default => null,
    };
}

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/subscriptions/OrdersModule.php';
$module    = new OrdersModule();
$alert     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_orders')) {
        cms_admin_orders_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
        cms_admin_orders_redirect();
    } else {
        $allowedActions = cms_admin_orders_allowed_actions();
        $action = (string)($_POST['action'] ?? '');
        if (!isset($allowedActions[$action])) {
            cms_admin_orders_flash(['type' => 'danger', 'message' => 'Unbekannte Aktion.']);
            cms_admin_orders_redirect();
        }

        $result = cms_admin_orders_handle_action($module, $action, $_POST);
        if ($result === null) {
            cms_admin_orders_flash(['type' => 'danger', 'message' => 'Unbekannte Aktion.']);
            cms_admin_orders_redirect();
        }

        cms_admin_orders_flash_result($result);
        cms_admin_orders_redirect();
    }
}

if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken    = Security::instance()->generateToken('admin_orders');
$statusFilter = cms_admin_orders_normalize_status_filter((string)($_GET['status'] ?? ''));
$pageTitle    = 'Bestellungen & Zuweisung';
$activePage   = 'orders';
$data         = $module->getData($statusFilter)->toArray();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/subscriptions/orders.php';
require_once __DIR__ . '/partials/footer.php';
