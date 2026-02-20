<?php
/**
 * Member Subscription Controller
 * 
 * Shows current subscription and upgrade options
 * 
 * @package CMSv2\Member
 * @version 1.0.0
 */

declare(strict_types=1);

// Load configuration and autoloader
require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Member\MemberController;
use CMS\Services\MemberService;
use CMS\SubscriptionManager;
use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-member-controller.php';

$controller = new MemberController();
$memberService = MemberService::getInstance();
$subscriptionManager = SubscriptionManager::instance();
$db = Database::instance();
$user = $controller->getUser();

// Prepare Payment Info
$payBank   = $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'payment_info_bank'");
$payPaypal = $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'payment_info_paypal'");
$payNote   = $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'payment_info_note'");

$paymentInfo = [
    'bank'   => $payBank,
    'paypal' => $payPaypal,
    'note'   => $payNote,
];

// Determine if we show upgrade prompt (e.g. via URL param ?upgrade=1)
// or always show available plans below current
$showUpgrade = isset($_GET['upgrade']);

// Render View
$controller->render('subscription-view', [
    'subscription' => $memberService->getUserSubscription($user->id),
    'allPlans'     => $subscriptionManager->getAllPlans(),
    'paymentInfo'  => $paymentInfo,
    'permissions'  => $memberService->getUserPermissions($user->id),
    'statusBadges' => [
        'active'    => 'success',
        'expired'   => 'danger',
        'pending'   => 'warning',
        'cancelled' => 'secondary'
    ]
]);
