<?php
declare(strict_types=1);

/**
 * Admin Dashboard – Entry Point
 *
 * Route: /admin
 * Loads: DashboardModule → views/dashboard/index.php
 *
 * @package CMSv2\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

// ─── Auth-Check ────────────────────────────────────────────────────────────
if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

// ─── Module laden ──────────────────────────────────────────────────────────
require_once __DIR__ . '/modules/dashboard/DashboardModule.php';
$module    = new DashboardModule();
$data      = $module->getData();
$csrfToken = Security::instance()->generateToken('admin_dashboard');

// ─── View-Variablen ────────────────────────────────────────────────────────
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
$pageAssets = [];

// ─── Layout rendern ────────────────────────────────────────────────────────
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/dashboard/index.php';
require_once __DIR__ . '/partials/footer.php';
