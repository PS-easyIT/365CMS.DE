<?php
/**
 * Member Messages Controller
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

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-member-controller.php';

$controller = new MemberController();
$user = $controller->getUser();

// Prepare page data
$data = [
    'user' => $user,
    // Fake data for messages
    'conversations' => [
        [
            'id' => 1,
            'user' => 'Support Team',
            'avatar' => 'S',
            'subject' => 'Willkommen im neuen Dashboard!',
            'preview' => 'Hallo ' . $user->username . ', schön dass du da bist. Schau dich gerne um...',
            'date' => 'Heute, 10:30',
            'unread' => true
        ],
        [
            'id' => 2,
            'user' => 'Markus Weber',
            'avatar' => 'M',
            'subject' => 'Projekt Anfrage: Redesign',
            'preview' => 'Hi, ich habe dein Profil gesehen und wollte fragen ob du Zeit hast...',
            'date' => 'Gestern',
            'unread' => false
        ],
        [
            'id' => 3,
            'user' => 'Sarah Design',
            'avatar' => 'S',
            'subject' => 'Re: Logo Entwürfe',
            'preview' => 'Die neuen Skizzen sehen super aus! Können wir morgen kurz telefonieren?',
            'date' => '18. Feb',
            'unread' => false
        ]
    ]
];

// Set active page for menu highlighting
$data['currentPage'] = 'messages';

$controller->render('messages-view', $data);
