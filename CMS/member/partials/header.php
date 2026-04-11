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
$siteName = function_exists('cms_get_site_name') ? cms_get_site_name() : (defined('SITE_NAME') ? SITE_NAME : '365CMS');
$design = is_array($settings['design'] ?? null) ? $settings['design'] : [];
$sanitizeDesignColor = static function (mixed $value, string $fallback): string {
    $color = trim((string) $value);
    return preg_match('/^#[0-9a-f]{6}$/i', $color) === 1 ? $color : $fallback;
};
$sanitizeMemberHeaderHref = static function (mixed $value, string $fallback): string {
    $href = trim((string) $value);
    if ($href === '') {
        return $fallback;
    }

    if (str_starts_with($href, '/')) {
        return str_starts_with($href, '//') ? $fallback : $href;
    }

    if (preg_match('#^https?://#i', $href) === 1) {
        return filter_var($href, FILTER_VALIDATE_URL) ? $href : $fallback;
    }

    return $fallback;
};
$sanitizeMemberHeaderAsset = static function (mixed $value): string {
    $asset = trim((string) $value);
    if ($asset === '') {
        return '';
    }

    if (preg_match('/^[A-Za-z]:[\\\/]/', $asset) === 1 || str_starts_with($asset, '//')) {
        return '';
    }

    if (str_starts_with($asset, '/')) {
        return $asset;
    }

    if (preg_match('#^https?://#i', $asset) === 1) {
        return filter_var($asset, FILTER_VALIDATE_URL) ? $asset : '';
    }

    return '';
};
$memberPrimary = $sanitizeDesignColor($design['primary'] ?? '#6366f1', '#6366f1');
$memberAccent = $sanitizeDesignColor($design['accent'] ?? '#8b5cf6', '#8b5cf6');
$memberBg = $sanitizeDesignColor($design['bg'] ?? '#f1f5f9', '#f1f5f9');
$memberCardBg = $sanitizeDesignColor($design['card_bg'] ?? '#ffffff', '#ffffff');
$memberText = $sanitizeDesignColor($design['text'] ?? '#1e293b', '#1e293b');
$memberBorder = $sanitizeDesignColor($design['border'] ?? '#e2e8f0', '#e2e8f0');
$bodyClass = 'member-shell member-page-' . preg_replace('/[^a-z0-9\-]+/i', '-', $pageKey);
$displayName = $controller->getDisplayName();
$avatar = $controller->getAvatarUrl();
$memberMenu = $controller->getMenuItems($pageKey);
$canAccessAdminPortal = $controller->canAccessAdminPortal();
$adminPortalUrl = $controller->getAdminPortalUrl();
$showAdminHeaderLink = \CMS\Auth::isAdmin();
$dashboardHref = $sanitizeMemberHeaderHref('/member/dashboard', '/member/dashboard');
$profileHref = $sanitizeMemberHeaderHref('/member/profile', '/member/profile');
$securityHref = $sanitizeMemberHeaderHref('/member/security', '/member/security');
$logoutHref = $sanitizeMemberHeaderHref('/logout', '/logout');
$adminHref = $sanitizeMemberHeaderHref($adminPortalUrl, '/admin');
$dashboardLogo = $sanitizeMemberHeaderAsset($settings['dashboard_logo'] ?? '');
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
            --member-primary: <?= htmlspecialchars($memberPrimary, ENT_QUOTES) ?>;
            --member-accent: <?= htmlspecialchars($memberAccent, ENT_QUOTES) ?>;
            --member-bg: <?= htmlspecialchars($memberBg, ENT_QUOTES) ?>;
            --member-card-bg: <?= htmlspecialchars($memberCardBg, ENT_QUOTES) ?>;
            --member-text: <?= htmlspecialchars($memberText, ENT_QUOTES) ?>;
            --member-border: <?= htmlspecialchars($memberBorder, ENT_QUOTES) ?>;
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
                <a href="<?= htmlspecialchars($dashboardHref, ENT_QUOTES) ?>" class="text-reset text-decoration-none d-flex align-items-center gap-2">
                    <?php if ($dashboardLogo !== ''): ?>
                        <span class="d-inline-flex align-items-center justify-content-center rounded bg-white border p-1" style="width: 2.5rem; height: 2.5rem;">
                            <img src="<?= htmlspecialchars($dashboardLogo, ENT_QUOTES) ?>" alt="<?= htmlspecialchars($siteName) ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                        </span>
                    <?php else: ?>
                        <span class="avatar avatar-sm member-brand-avatar"><?= htmlspecialchars($controller->getInitials()) ?></span>
                    <?php endif; ?>
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
                            <a href="<?= htmlspecialchars($profileHref, ENT_QUOTES) ?>" class="dropdown-item">Profil</a>
                            <a href="<?= htmlspecialchars($securityHref, ENT_QUOTES) ?>" class="dropdown-item">Sicherheit</a>
                            <?php if ($canAccessAdminPortal): ?>
                                <a href="<?= htmlspecialchars($adminHref, ENT_QUOTES) ?>" class="dropdown-item">Adminmenü</a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="<?= htmlspecialchars($logoutHref, ENT_QUOTES) ?>" class="dropdown-item">Abmelden</a>
                        </div>
                    </div>
                </div>
                <div class="navbar-nav flex-row align-items-center gap-3 member-breadcrumb-wrap">
                    <div>
                        <div class="page-pretitle">Member Area</div>
                        <h2 class="page-title mb-0"><?= htmlspecialchars($pageTitle) ?></h2>
                    </div>
                    <?php if ($showAdminHeaderLink): ?>
                        <a href="<?= htmlspecialchars($adminHref, ENT_QUOTES) ?>" class="btn btn-outline-primary btn-sm">
                            ⚙️ Admin
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        <div class="page-body">
            <div class="container-xl py-4">
                <?php include __DIR__ . '/alerts.php'; ?>
