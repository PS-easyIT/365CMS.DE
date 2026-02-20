<?php
/**
 * Public Order Process Controller
 * 
 * Handles subscription checkout for logged-in and guest users (if guests allowed)
 * 
 * @package CMSv2\Member
 */

declare(strict_types=1);

use CMS\Auth;
use CMS\Database;
use CMS\SubscriptionManager;

if (!defined('ABSPATH')) {
    exit;
}

$db = Database::instance();
$auth = Auth::instance();
$subMgr = SubscriptionManager::instance();

// GET plan details
$planId = filter_input(INPUT_GET, 'plan_id', FILTER_VALIDATE_INT);
$plan = $planId ? $subMgr->getPlan($planId) : null;

if (!$plan) {
    // Redirect to subscription page or home if no plan selected
    // For logged in users:
    if ($auth->isLoggedIn()) {
        header('Location: ' . SITE_URL . '/member/subscription');
    } else {
        header('Location: ' . SITE_URL . '/');
    }
    exit;
}

// Handle Form Submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simple validation
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $zip = trim($_POST['zip'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'bank_transfer';
    
    // Auth Check / Registration
    $userId = $auth->isLoggedIn() ? $auth->currentUser()->id : null;
    
    if (!$userId && $email) {
        // Try to find user by email
        $existing = $db->get_row("SELECT * FROM {$db->getPrefix()}users WHERE user_email = %s", $email);
        if ($existing) {
            $error = 'Diese E-Mail-Adresse ist bereits registriert. Bitte loggen Sie sich ein.';
        } else {
            // Auto-register (simplified) or prompt login
            // For now, require login if exists, or show error "Use Login"
            // Implementation: Create user logic here is complex (password hashing etc).
            // Recommend redirect to register with return URL?
            $error = 'Bitte registrieren Sie sich zuerst oder loggen Sie sich ein.';
        }
    }
    
    if (!$error && $userId) {
        // Create Order
        $billingData = json_encode([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'address' => $address,
            'city' => $city,
            'zip' => $zip,
            'country' => $country,
            'company' => $_POST['company'] ?? '',
            'vat_id' => $_POST['vat_id'] ?? ''
        ]);
        
        // Generate Temp Order Number (will update after ID)
        $tempOrderNum = 'TEMP-' . time();
        
        $inserted = $db->insert("{$db->getPrefix()}orders", [
            'order_number' => $tempOrderNum,
            'user_id' => $userId,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'total_amount' => $plan->price_monthly, // Assuming monthly for now
            'payment_method' => $paymentMethod,
            'billing_address' => $billingData,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($inserted) {
            $orderId = $db->insert_id;
            
            // Generate Real Order Number
            // Format: ORD-{Y}-{ID} or custom setting
            $format = $db->get_var("SELECT setting_value FROM {$db->getPrefix()}settings WHERE setting_key = 'order_number_format'") ?? 'ORD-{Y}-{ID}';
            
            $orderNum = str_replace(
                ['{Y}', '{m}', '{d}', '{ID}'],
                [date('Y'), date('m'), date('d'), str_pad((string)$orderId, 5, '0', STR_PAD_LEFT)],
                $format
            );
            
            $db->update("{$db->getPrefix()}orders", ['order_number' => $orderNum], ['id' => $orderId]);
            
            // Redirect to Success Page
            // Ideally passing order_id secured (e.g. hash)
            // For now showing success message in view
            $success = true;
            $order = (object)[
                'id' => $orderId,
                'order_number' => $orderNum,
                'total' => $plan->price_monthly,
                'payment_method' => $paymentMethod
            ];
            
            // Render Success View
            require_once __DIR__ . '/partials/order-success.php';
            exit;
        } else {
            $error = 'Fehler beim Erstellen der Bestellung.';
        }
    }
}

// Prefill known data
$user = $auth->currentUser(); // object or null
$formData = [
    'email' => $user->user_email ?? '',
    'first_name' => $user->first_name ?? '', // checks meta if available
    'last_name' => $user->last_name ?? '',
    'address' => '',
    'city' => '',
    'zip' => '',
    'country' => 'Deutschland'
];
// Try to get address from user meta if available (depends on meta structure)
if ($user) {
    // Example: get meta
    // $formData['address'] = $db->get_var(...) 
}

// Render Request View
$payBank   = $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'payment_info_bank'");
$payPaypal = $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'payment_info_paypal'");
$paymentInfo = [
    'bank'   => $payBank,
    'paypal' => $payPaypal,
];
include __DIR__ . '/partials/order-form-view.php';
