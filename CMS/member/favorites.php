<?php
/**
 * Member Favorites Controller
 * 
 * @package CMSv2\Member
 * @version 1.0.0
 */

declare(strict_types=1);

// Load configuration and autoloader
require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Member\MemberController;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-member-controller.php';

$controller = new MemberController();
$user = $controller->getUser();

// Fake data for favorites
$favorites = [
    [
        'id' => 101,
        'type' => 'tool',
        'title' => 'SEO Check v4',
        'desc' => 'Analyse deiner Website Metadaten',
        'icon' => '🔍',
        'link' => '/tools/seo'
    ],
    [
        'id' => 102,
        'type' => 'company',
        'title' => 'Muster Firma GmbH',
        'desc' => 'IT-Dienstleister aus Berlin',
        'icon' => '🏢',
        'link' => '/companies/muster'
    ],
    [
        'id' => 103,
        'type' => 'article',
        'title' => 'WordPress Security 2026',
        'desc' => 'Die wichtigsten Updates im Überblick',
        'icon' => '📄',
        'link' => '/articles/wp-sec'
    ],
    [
        'id' => 104,
        'type' => 'event',
        'title' => 'Web Dev Meetup',
        'desc' => 'Online • 25.03.2026',
        'icon' => '📅',
        'link' => '/events/meetup'
    ]
];

// Prepare page data
$data = [
    'user' => $user,
    'favorites' => $favorites,
    'currentPage' => 'favorites'
];

$controller->render('favorites-view', $data);
