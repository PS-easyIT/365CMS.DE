<?php
/**
 * Plugin Section Dispatcher
 *
 * Wird vom Router für /member/plugin/:slug aufgerufen (nach handleRoute()).
 * Rendert das vollständige Member-Layout und ruft dann den render_callback
 * des registrierten Plugins auf.
 *
 * Verfügbare Variablen (bereitgestellt von PluginDashboardRegistry::handleRoute()):
 * - $slug    : string – Plugin-Slug
 * - $params  : array  – Route-Parameter
 * - $section : array  – Registrierungsdaten des Plugins
 * - $user    : object – Aktueller Benutzer
 *
 * @package CMSv2\Member
 */

declare(strict_types=1);

// Laden der Basis-Deps falls direkt aufgerufen
if (!defined('ABSPATH')) {
    require_once dirname(__DIR__) . '/config.php';
}
if (!class_exists('\CMS\Auth')) {
    require_once CORE_PATH . 'autoload.php';
}

use CMS\Member\PluginDashboardRegistry;
use CMS\Auth;

// Auth-Check
if (!Auth::instance()->isLoggedIn()) {
    header('Location: ' . SITE_URL . '/login');
    exit;
}

// Registry holen
$registry = PluginDashboardRegistry::instance();
$registry->init();

// Slug aus Route-Param oder GET (Fallback für direkte Aufrufe ohne saubere URL)
$slug    = $slug ?? ($_GET['slug'] ?? '');
$params  = $params ?? [];
$section = $section ?? $registry->getSection($slug);
$user    = $user ?? Auth::instance()->getCurrentUser();

// Nicht gefunden → Fehlerseite
if ($section === null) {
    require_once __DIR__ . '/partials/plugin-not-found.php';
    exit;
}

// Partials laden
require_once __DIR__ . '/partials/member-menu.php';

// ── Seitentitel für <title> und Breadcrumbs ───────────────────────────────────
$pageTitle = htmlspecialchars($section['label']);
$pageIcon  = $section['icon'];

?><!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> – <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/member.css">
    <?php renderMemberSidebarStyles(); ?>
    <style>
        /* ── Plugin-Section Layout ───────────────────────────────────── */
        .plugin-section-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1.25rem;
            border-bottom: 2px solid #e2e8f0;
        }
        .plugin-section-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
            background: #f1f5f9;
        }
        .plugin-section-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #1e293b;
            margin: 0 0 .2rem;
        }
        .plugin-section-subtitle {
            font-size: .875rem;
            color: #64748b;
            margin: 0;
        }
        .plugin-section-body {
            /* Plugins können interne Styles selbst mitbringen */
        }
    </style>
    <?php
    // Plugins können zusätzliche Styles in <head> injizieren
    \CMS\Hooks::doAction('member_plugin_section_head', $section['slug'], $user);
    ?>
</head>
<body class="member-body">

    <?php renderMemberSidebar('plugin_' . $section['slug']); ?>

    <div class="member-content">

        <!-- Flash Messages -->
        <?php if (!empty($_SESSION['success'])): ?>
        <div class="member-alert member-alert-success" style="margin-bottom:1.25rem;">
            <span class="alert-icon">✓</span>
            <span><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
        <div class="member-alert member-alert-error" style="margin-bottom:1.25rem;">
            <span class="alert-icon">✕</span>
            <span><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
        </div>
        <?php endif; ?>

        <!-- ── Header des Plugin-Bereichs ──────────────────────────────────── -->
        <div class="plugin-section-header">
            <div class="plugin-section-icon">
                <?php echo $pageIcon; ?>
            </div>
            <div>
                <h1 class="plugin-section-title"><?php echo $pageTitle; ?></h1>
                <?php if (!empty($section['dashboard_widget']['description'])): ?>
                <p class="plugin-section-subtitle">
                    <?php echo htmlspecialchars($section['dashboard_widget']['description']); ?>
                </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Plugin-Inhalt ────────────────────────────────────────────────── -->
        <div class="plugin-section-body">
            <?php
            if (is_callable($section['render_callback'])) {
                call_user_func($section['render_callback'], $user, $params);
            } else {
                echo '<p style="color:#ef4444;">Fehler: Kein render_callback für diesen Bereich definiert.</p>';
            }
            ?>
        </div>

    </div>

    <?php \CMS\Hooks::doAction('member_plugin_section_footer', $section['slug'], $user); ?>
</body>
</html>
