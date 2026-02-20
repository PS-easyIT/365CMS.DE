<?php
/**
 * Plugin-Bereich Nicht Gefunden
 *
 * Wird angezeigt wenn ein Plugin-Slug nicht registriert ist.
 *
 * @package CMSv2\Member\Partials
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/member-menu.php';
?><!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bereich nicht gefunden – <?php echo htmlspecialchars(defined('SITE_NAME') ? SITE_NAME : 'CMS'); ?></title>
    <link rel="stylesheet" href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/assets/css/member.css">
    <?php renderMemberSidebarStyles(); ?>
</head>
<body class="member-body">
    <?php renderMemberSidebar(''); ?>
    <div class="member-content" style="display:flex;align-items:center;justify-content:center;min-height:60vh;">
        <div style="text-align:center;">
            <div style="font-size:3.5rem;margin-bottom:1rem;">🔌</div>
            <h2 style="font-size:1.5rem;color:#1e293b;margin:0 0 .5rem;">Plugin-Bereich nicht gefunden</h2>
            <p style="color:#64748b;margin:0 0 1.5rem;">Dieser Bereich ist nicht verfügbar oder das Plugin ist nicht aktiviert.</p>
            <a href="<?php echo defined('SITE_URL') ? htmlspecialchars(SITE_URL) : ''; ?>/member"
               style="display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;
                      background:#4f46e5;color:#fff;border-radius:8px;text-decoration:none;
                      font-weight:600;font-size:.9375rem;">
                🏠 Zurück zum Dashboard
            </a>
        </div>
    </div>
</body>
</html>
