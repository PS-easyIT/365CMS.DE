<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * System-Info & Diagnose – Legacy-Entry-Point
 * Route: /admin/system-info
 */

use CMS\Auth;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

// FIX: Legacy-Route sauber auf die neue Info-Seite umleiten, statt unerreichbaren Alt-Code mitzuführen.
header('Location: ' . SITE_URL . '/admin/info');
exit;
