<?php
/**
 * Member Messages Controller
 * 
 * @package CMSv2\Member
 */

declare(strict_types=1);

// Load configuration and autoloader
require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Member\MemberController;
use CMS\Services\MemberService;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-member-controller.php';

$controller = new MemberController();
$user = $controller->getUser();

// Prepare page data
// NOTE: Messaging feature is not yet backed by a database table.
// A proper implementation requires: cms_messages table, MessageService class, CRUD operations.
// For now we show an empty state instead of fake/hardcoded demo data.
$data = [
    'user' => $user,
    'conversations' => [],  // Empty until messaging DB/Service is implemented
    'feature_status' => 'coming_soon',
];

// Set active page for menu highlighting
$data['currentPage'] = 'messages';

$controller->render('messages-view', $data);
