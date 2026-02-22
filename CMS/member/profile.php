<?php
/**
 * Member Profile Controller
 * 
 * Profilverwaltung und persönliche Einstellungen
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

$controller = new CMS\Member\MemberController();
$memberService = MemberService::getInstance();

// CSRF Token
$csrfToken = $controller->generateToken('member_profile');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$controller->verifyToken($controller->getPost('csrf_token'), 'member_profile')) {
        $controller->setError('Sicherheitsüberprüfung fehlgeschlagen.');
    } else {
        $updateData = [
            'username' => $controller->getPost('username'),
            'email' => $controller->getPost('email', 'email'),
            'first_name' => $controller->getPost('first_name'),
            'last_name' => $controller->getPost('last_name'),
            'bio' => $controller->getPost('bio', 'textarea'),
            'phone' => $controller->getPost('phone'),
            'website' => $controller->getPost('website', 'url')
        ];
        
        $result = $memberService->updateProfile($controller->getUser()->id, $updateData);
        
        if ($result === true) {
            $controller->setSuccess('Profil erfolgreich aktualisiert!');
            $controller->redirect('/member/profile');
        } else {
            $controller->setError('Fehler beim Aktualisieren des Profils.');
        }
    }
}

// Load user meta
$userMeta = $memberService->getUserMeta($controller->getUser()->id);

// Render view
$controller->render('profile-view', [
    'csrfToken' => $csrfToken,
    'userMeta' => $userMeta
]);

