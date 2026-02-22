<?php
/**
 * Member Notifications Controller
 * 
 * @package CMSv2\Member
 */

declare(strict_types=1);

// Load configuration and autoloader
require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Member\MemberController;
use CMS\Services\MemberService;
use CMS\Security;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-member-controller.php';

$controller = new MemberController();

// Handle form actions first (PRG pattern)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->handleNotificationActions();
    // handleNotificationActions() redirects on success
}

$memberService = MemberService::getInstance();
$user = $controller->getUser();

// Prepare page data
$data = [
    'preferences'         => $memberService->getNotificationPreferences($user->id),
    'recentNotifications' => $memberService->getRecentNotifications($user->id, 10),
    'csrfToken'           => Security::instance()->generateToken('member_notifications'),
];

$controller->render('notifications-view', $data);
