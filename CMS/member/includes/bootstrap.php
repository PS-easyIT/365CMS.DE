<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/class-member-controller.php';

$controller = \CMS\MemberArea\MemberController::instance();
$controller->requireAuth();
\CMS\CacheManager::instance()->sendResponseHeaders('private');
$settings = $controller->getSettings();
