<?php
/**
 * Member Privacy Controller
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
    $controller->handlePrivacyActions();
    // handlePrivacyActions() redirects or exits on success/data-export
}

$memberService = MemberService::getInstance();
$user = $controller->getUser();

// Prepare page data
$data = [
    'privacySettings' => $memberService->getPrivacySettings($user->id),
    'dataOverview'    => $memberService->getDataOverview($user->id),
    'csrfPrivacy'     => Security::instance()->generateToken('privacy_settings'),
    'csrfExport'      => Security::instance()->generateToken('data_export'),
    'csrfDelete'      => Security::instance()->generateToken('account_delete'),
];

$controller->render('privacy-view', $data);
