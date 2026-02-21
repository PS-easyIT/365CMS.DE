<?php
/**
 * Member Media Controller Page
 * 
 * Behandelt die Medienverwaltung im Mitgliederbereich.
 * 
 * @package CMSv2\Member
 */

declare(strict_types=1);

// Load configuration
require_once dirname(__DIR__) . '/config.php';

// Debug Snippet
require_once __DIR__ . '/debug_snippet.php';

// Load autoloader
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Services\MediaService;

if (!defined('ABSPATH')) {
    exit;
}

// Initialize controller logic
require_once __DIR__ . '/includes/class-member-controller.php';

// Check if this is an AJAX request for media
if (isset($_REQUEST['media_ajax']) && $_REQUEST['media_ajax'] === '1') {
    // Determine path to media-ajax.php
    $ajaxFile = __DIR__ . '/media-ajax.php';
    if (file_exists($ajaxFile)) {
        // Include the standalone ajax handler
        // Note: media-ajax.php includes config/autoload again, using require_once handles this.
        include $ajaxFile;
        exit;
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Handler not found']);
        exit;
    }
}

$controller = new CMS\Member\MemberController();
$mediaService = new MediaService();
$settings = $mediaService->getSettings();

// Check permissions
if (!empty($settings['member_uploads_enabled']) && !$settings['member_uploads_enabled'] && !Auth::isAdmin()) {
    // If disabled, redirect to dashboard or show error
    $controller->setError('Medienverwaltung ist deaktiviert.');
    $controller->redirect('member');
}

// Render view
$controller->render('media-view', [
    'settings' => $settings,
    'user' => $controller->getUser()
]);
