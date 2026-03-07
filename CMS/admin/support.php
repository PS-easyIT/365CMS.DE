<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Legacy-Weiterleitung: frühere Support-&-Dokumentation-Seite
 * Route: /admin/support
 */

use CMS\Auth;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

header('Location: ' . SITE_URL . '/admin/documentation');
exit;
