<?php
/**
 * Admin: Beiträge (Blog-Posts)
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;

if (!defined('ABSPATH')) {
    exit;
}

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/partials/admin-menu.php';

renderAdminLayoutStart('Beiträge', 'posts');
?>

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:2rem;">
    <h2 style="margin:0;">✏️ Beiträge</h2>
</div>

<div class="member-card" style="padding:3rem;text-align:center;max-width:600px;margin:0 auto;">
    <div style="font-size:4rem;margin-bottom:1.5rem;">🚧</div>
    <h3 style="font-size:1.25rem;color:#1e293b;margin:0 0 .75rem;">In Bearbeitung</h3>
    <p style="color:#64748b;margin:0 0 1.5rem;line-height:1.6;">
        Die Blog/Beiträge-Verwaltung wird aktuell entwickelt.<br>
        Dieser Bereich wird in Kürze verfügbar sein.
    </p>
    <div style="display:inline-flex;align-items:center;gap:.5rem;padding:.5rem 1.25rem;background:#fef9c3;border:1px solid #fde047;border-radius:8px;font-size:.8125rem;color:#713f12;">
        <span>⏳</span> Funktion in Entwicklung
    </div>
</div>

<?php
renderAdminLayoutEnd();
