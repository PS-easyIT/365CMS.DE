<?php
/**
 * Member Dashboard Controller
 * 
 * Zentrale Übersicht für Mitglieder mit personalisierten Widgets
 * 
 * @package CMSv2\Member
 */

declare(strict_types=1);

// Load configuration
require_once dirname(__DIR__) . '/config.php';

// Load autoloader
require_once CORE_PATH . 'autoload.php';

use CMS\Member\MemberController;
use CMS\Services\MemberService;

if (!defined('ABSPATH')) {
    exit;
}

// Initialize controller
require_once __DIR__ . '/includes/class-member-controller.php';

$controller = new MemberController();
$memberService = MemberService::getInstance();

// Dashboard-Daten abrufen
$dashboardData = $memberService->getMemberDashboardData($controller->getUser()->id);

// Render view
$controller->render('dashboard-view', [
    'dashboardData' => $dashboardData
]);