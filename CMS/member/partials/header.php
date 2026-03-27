<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$pageTitle = $pageTitle ?? 'Member-Bereich';
$pageKey = $pageKey ?? 'dashboard';
$pageAssets = $pageAssets ?? [];
$controller = $controller ?? \CMS\MemberArea\MemberController::instance();
$settings = $settings ?? $controller->getSettings();
$siteUrl = defined('SITE_URL') ? SITE_URL : '';
$siteName = function_exists('cms_get_site_name') ? cms_get_site_name() : (defined('SITE_NAME') ? SITE_NAME : '365CMS');
$design = is_array($settings['design'] ?? null) ? $settings['design'] : [];
$bodyClass = 'member-shell member-page-' . preg_replace('/[^a-z0-9\-]+/i', '-', $pageKey);
$displayName = $controller->getDisplayName();
$avatar = $controller->getAvatarUrl();
$memberMenu = $controller->getMenuItems($pageKey);
$canAccessAdminPortal = $controller->canAccessAdminPortal();
$adminPortalUrl = $controller->getAdminPortalUrl();
$showAdminHeaderLink = \CMS\Auth::isAdmin();
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="robots" content="noindex,nofollow">
    <title><?= htmlspecialchars($pageTitle) ?> – <?= htmlspecialchars($siteName) ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(cms_asset_url('tabler/css/tabler.min.css'), ENT_QUOTES) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(cms_asset_url('css/member-dashboard.css'), ENT_QUOTES) ?>">
    <?php if (!empty($pageAssets['css'])): ?>
        <?php foreach ((array)$pageAssets['css'] as $css): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars((string)$css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <style>
        :root {
            --member-primary: <?= htmlspecialchars((string)($design['primary'] ?? '#6366f1')) ?>;
            --member-accent: <?= htmlspecialchars((string)($design['accent'] ?? '#8b5cf6')) ?>;
            --member-bg: <?= htmlspecialchars((string)($design['bg'] ?? '#f1f5f9')) ?>;
            --member-card-bg: <?= htmlspecialchars((string)($design['card_bg'] ?? '#ffffff')) ?>;
            --member-text: <?= htmlspecialchars((string)($design['text'] ?? '#1e293b')) ?>;
            --member-border: <?= htmlspecialchars((string)($design['border'] ?? '#e2e8f0')) ?>;
        }
    </style>
    <?php \CMS\Hooks::doAction('head'); ?>
    <?php \CMS\Hooks::doAction('member_head', $pageKey, $controller->getCurrentUser()); ?>
</head>
<body class="<?= htmlspecialchars($bodyClass) ?>">
<?php \CMS\Hooks::doAction('body_start'); ?>
<div class="page member-layout">
    <aside class="navbar navbar-vertical navbar-expand-lg member-sidebar">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#member-sidebar-menu" aria-controls="member-sidebar-menu" aria-expanded="false" aria-label="Navigation umschalten">
                <span class="navbar-toggler-icon"></span>
            </button>
            <h1 class="navbar-brand navbar-brand-autodark">
                <a href="<?= htmlspecialchars($siteUrl) ?>/member/dashboard" class="text-reset text-decoration-none d-flex align-items-center gap-2">
                    <span class="avatar avatar-sm member-brand-avatar"><?= htmlspecialchars($controller->getInitials()) ?></span>
                    <span>
                        <span class="d-block"><?= htmlspecialchars($siteName) ?></span>
                        <small class="text-secondary">Member Hub</small>
                    </span>
                </a>
            </h1>
            <div class="collapse navbar-collapse" id="member-sidebar-menu">
                <?php include __DIR__ . '/sidebar.php'; ?>
            </div>
        </div>
    </aside>
    <div class="page-wrapper member-page-wrapper">
        <header class="navbar navbar-expand-md d-print-none member-topbar">
            <div class="container-xl">
                <div class="navbar-nav flex-row order-md-last ms-auto">
                    <div class="nav-item me-3 d-none d-md-flex align-items-center text-secondary small">
                        <?= htmlspecialchars($displayName) ?>
                    </div>
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Mitgliedermenü öffnen">
                            <?php if ($avatar !== ''): ?>
                                <span class="avatar avatar-sm" style="background-image: url('<?= htmlspecialchars($avatar, ENT_QUOTES) ?>')"></span>
                            <?php else: ?>
                                <span class="avatar avatar-sm"><?= htmlspecialchars($controller->getInitials()) ?></span>
                            <?php endif; ?>
                            <div class="d-none d-xl-block ps-2">
                                <div><?= htmlspecialchars($displayName) ?></div>
                                <div class="mt-1 small text-secondary">Mitgliederbereich</div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <a href="<?= htmlspecialchars($siteUrl) ?>/member/profile" class="dropdown-item">Profil</a>
                            <a href="<?= htmlspecialchars($siteUrl) ?>/member/security" class="dropdown-item">Sicherheit</a>
                            <?php if ($canAccessAdminPortal): ?>
                                <a href="<?= htmlspecialchars($siteUrl . $adminPortalUrl) ?>" class="dropdown-item">Adminmenü</a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="<?= htmlspecialchars($siteUrl) ?>/logout" class="dropdown-item">Abmelden</a>
                        </div>
                    </div>
                </div>
                <div class="navbar-nav flex-row align-items-center gap-3 member-breadcrumb-wrap">
                    <div>
                        <div class="page-pretitle">Member Area</div>
                        <h2 class="page-title mb-0"><?= htmlspecialchars($pageTitle) ?></h2>
                    </div>
                    <?php if ($showAdminHeaderLink): ?>
                        <a href="<?= htmlspecialchars($siteUrl . $adminPortalUrl, ENT_QUOTES) ?>" class="btn btn-outline-primary btn-sm">
                            ⚙️ Admin
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        <div class="page-body">
            <div class="container-xl py-4">
                <?php include __DIR__ . '/alerts.php'; ?>
