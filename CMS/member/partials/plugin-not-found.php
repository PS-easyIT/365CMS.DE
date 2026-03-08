<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="empty">
    <div class="empty-img"><img src="<?= htmlspecialchars((defined('ASSETS_URL') ? ASSETS_URL : SITE_URL . '/assets')) ?>/images/undraw_page_not_found.svg" height="128" alt="Plugin-Bereich nicht gefunden"></div>
    <p class="empty-title">Plugin-Bereich nicht gefunden</p>
    <p class="empty-subtitle text-secondary">
        Dieser Bereich wurde nicht registriert oder das Plugin ist aktuell nicht verfügbar.
    </p>
    <div class="empty-action">
        <a href="<?= htmlspecialchars(SITE_URL) ?>/member/dashboard" class="btn btn-primary">Zurück zum Dashboard</a>
    </div>
</div>
