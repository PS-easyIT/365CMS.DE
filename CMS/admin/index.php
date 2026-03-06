<?php
/**
 * Admin Front Controller
 *
 * Einstiegspunkt für die modulare Admin-Architektur.
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    require_once dirname(__DIR__) . '/config.php';
}

require_once __DIR__ . '/modules/dashboard/page.php';
