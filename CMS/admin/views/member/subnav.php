<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$currentMemberPage = $activePage ?? 'member-dashboard';
$memberNavItems = [
    ['slug' => 'member-dashboard', 'label' => 'Übersicht', 'url' => SITE_URL . '/admin/member-dashboard'],
    ['slug' => 'member-dashboard-general', 'label' => 'Allgemein', 'url' => SITE_URL . '/admin/member-dashboard-general'],
    ['slug' => 'member-dashboard-widgets', 'label' => 'Dashboard Widgets', 'url' => SITE_URL . '/admin/member-dashboard-widgets'],
    ['slug' => 'member-dashboard-profile-fields', 'label' => 'Profil-Felder', 'url' => SITE_URL . '/admin/member-dashboard-profile-fields'],
];
?>
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="btn-list">
            <?php foreach ($memberNavItems as $item): ?>
                <a class="btn <?php echo $currentMemberPage === $item['slug'] ? 'btn-primary' : 'btn-outline-primary'; ?>"
                   href="<?php echo htmlspecialchars($item['url']); ?>">
                    <?php echo htmlspecialchars($item['label']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
