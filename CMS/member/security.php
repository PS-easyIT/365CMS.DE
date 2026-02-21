<?php
/**
 * Member Security Controller
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
use CMS\Security;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-member-controller.php';

$controller = new MemberController();

// Handle form actions first (PRG pattern)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->handleSecurityActions();
    // handleSecurityActions() redirects on success, only continues on token failure
}

$memberService = MemberService::getInstance();
$user = $controller->getUser();

// Prepare page data
$data = [
    'securityData'  => $memberService->getSecurityData($user->id),
    'activeSessions' => $memberService->getActiveSessions($user->id),
    'csrfPassword'  => Security::instance()->generateToken('change_password'),
    'csrf2FA'       => Security::instance()->generateToken('toggle_2fa'),
];

$controller->render('security-view', $data);
