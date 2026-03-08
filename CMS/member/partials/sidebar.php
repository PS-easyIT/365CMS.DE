<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$controller = $controller ?? \CMS\MemberArea\MemberController::instance();
$pageKey = $pageKey ?? 'dashboard';
$menuItems = $memberMenu ?? $controller->getMenuItems($pageKey);
$settings = $settings ?? $controller->getSettings();
$greeting = str_replace('{name}', $controller->getDisplayName(), (string)($settings['dashboard_greeting'] ?? 'Willkommen zurück, {name}!'));
?>
<div class="member-sidebar-inner w-100">
    <div class="member-sidebar-greeting card card-sm mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <span class="avatar avatar-lg"><?= htmlspecialchars($controller->getInitials()) ?></span>
                <div>
                    <div class="fw-semibold"><?= htmlspecialchars($greeting) ?></div>
                    <div class="text-secondary small">Alles Wichtige an einem Ort.</div>
                </div>
            </div>
        </div>
    </div>
    <ul class="navbar-nav pt-lg-3">
        <?php foreach ($menuItems as $item): ?>
            <li class="nav-item <?= !empty($item['active']) ? 'active' : '' ?>">
                <a class="nav-link" href="<?= htmlspecialchars((string)$item['url']) ?>" <?= !empty($item['active']) ? 'aria-current="page"' : '' ?>>
                    <span class="nav-link-icon d-md-none d-lg-inline-block"><?= htmlspecialchars((string)($item['icon'] ?? '•')) ?></span>
                    <span class="nav-link-title"><?= htmlspecialchars((string)($item['label'] ?? 'Bereich')) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
