<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$topbarSiteName = trim((string) ($siteName ?? (defined('SITE_NAME') ? SITE_NAME : '365CMS')));
$topbarSectionLabel = trim((string) ($topbarSectionLabel ?? 'Dashboard'));
$topbarCurrentPageLabel = trim((string) ($topbarCurrentPageLabel ?? $pageTitle ?? 'Übersicht'));
$topbarUnreadNotifications = max(0, (int) ($topbarUnreadNotifications ?? 0));
$siteUrl = defined('SITE_URL') ? SITE_URL : '';

$topbarInitialSource = trim($currentAdminFirstName . ' ' . $currentAdminLastName);
if ($topbarInitialSource === '') {
    $topbarInitialSource = trim((string) ($_SESSION['user_display_name'] ?? 'Admin'));
}
$topbarInitials = '';
foreach (preg_split('/\s+/', $topbarInitialSource) ?: [] as $token) {
    if ($token === '') {
        continue;
    }
    $topbarInitials .= strtoupper((string) substr($token, 0, 1));
    if (strlen($topbarInitials) >= 2) {
        break;
    }
}
if ($topbarInitials === '') {
    $topbarInitials = 'AD';
}

$topbarCtaLabel = 'Neuer Beitrag';
$topbarCtaHref = '/admin/posts?action=new';
$pagesSlugs = ['pages', 'landing-page', 'hub-sites', 'site-tables', 'table-of-contents'];
$mediaSlugs = ['media', 'media-featured', 'media-check', 'media-categories', 'media-settings'];
$userSlugs = ['users', 'groups', 'roles', 'user-settings'];
if (in_array((string) ($activePage ?? ''), $pagesSlugs, true)) {
    $topbarCtaLabel = 'Neue Seite';
    $topbarCtaHref = '/admin/pages?action=new';
} elseif (in_array((string) ($activePage ?? ''), $mediaSlugs, true)) {
    $topbarCtaLabel = 'Hochladen';
    $topbarCtaHref = '/admin/media?action=upload';
} elseif (in_array((string) ($activePage ?? ''), $userSlugs, true)) {
    $topbarCtaLabel = 'Neuer Benutzer';
    $topbarCtaHref = '/admin/users?action=new';
}
?>
<header class="admin-topbar" role="banner">
    <div class="admin-topbar__context">
        <span class="admin-topbar__site-name"><?= htmlspecialchars($topbarSiteName, ENT_QUOTES, 'UTF-8') ?></span>
        <span class="admin-topbar__separator" aria-hidden="true">·</span>
        <span class="admin-topbar__crumb"><?= htmlspecialchars($topbarSectionLabel, ENT_QUOTES, 'UTF-8') ?></span>
        <span class="admin-topbar__separator" aria-hidden="true">·</span>
        <span class="admin-topbar__crumb admin-topbar__crumb--current"><?= htmlspecialchars($topbarCurrentPageLabel, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="admin-topbar__actions">
        <a class="admin-topbar__help-btn" href="/admin/documentation">
            <i class="ti ti-help-circle" aria-hidden="true"></i>
            <span>Hilfe</span>
        </a>
        <a class="admin-topbar__cta-btn" href="<?= htmlspecialchars($topbarCtaHref, ENT_QUOTES, 'UTF-8') ?>">
            <i class="ti ti-plus" aria-hidden="true"></i>
            <span><?= htmlspecialchars($topbarCtaLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </a>
        <div class="admin-topbar__identity">
            <button type="button" class="admin-topbar__icon-btn" aria-label="Benachrichtigungen">
                <i class="ti ti-bell"></i>
                <?php if ($topbarUnreadNotifications > 0): ?>
                    <span class="admin-topbar__unread-dot" aria-hidden="true"></span>
                <?php endif; ?>
            </button>
            <div class="dropdown">
                <button class="admin-topbar__avatar-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Profilmenü">
                    <span class="admin-topbar__avatar-initials"><?= htmlspecialchars($topbarInitials, ENT_QUOTES, 'UTF-8') ?></span>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="/admin/user-settings">Profil</a>
                    <a class="dropdown-item text-danger" href="<?= htmlspecialchars((string) $siteUrl, ENT_QUOTES, 'UTF-8') ?>/logout">Abmelden</a>
                </div>
            </div>
        </div>
    </div>
</header>
