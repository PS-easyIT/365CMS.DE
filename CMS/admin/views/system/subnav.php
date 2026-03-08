<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$currentSystemPage = $activePage ?? 'info';
$systemNavGroups = [
    'Info' => [
        ['slug' => 'info', 'label' => 'Info CMS', 'url' => SITE_URL . '/admin/info'],
        ['slug' => 'documentation', 'label' => 'Dokumentation', 'url' => SITE_URL . '/admin/documentation'],
    ],
    'Diagnose' => [
        ['slug' => 'diagnose', 'label' => 'Diagnose Datenbank', 'url' => SITE_URL . '/admin/diagnose'],
        ['slug' => 'monitor-response-time', 'label' => 'Response-Time', 'url' => SITE_URL . '/admin/monitor-response-time'],
        ['slug' => 'monitor-cron-status', 'label' => 'Cron-Status', 'url' => SITE_URL . '/admin/monitor-cron-status'],
        ['slug' => 'monitor-mail-queue', 'label' => 'Mail-Queue', 'url' => SITE_URL . '/admin/monitor-mail-queue'],
        ['slug' => 'monitor-disk-usage', 'label' => 'Disk-Usage', 'url' => SITE_URL . '/admin/monitor-disk-usage'],
        ['slug' => 'monitor-scheduled-tasks', 'label' => 'Scheduled Tasks', 'url' => SITE_URL . '/admin/monitor-scheduled-tasks'],
        ['slug' => 'monitor-health-check', 'label' => 'Health-Check', 'url' => SITE_URL . '/admin/monitor-health-check'],
        ['slug' => 'monitor-email-alerts', 'label' => 'E-Mail-Benachrichtigungen', 'url' => SITE_URL . '/admin/monitor-email-alerts'],
    ],
];
?>
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="row g-3 align-items-start">
            <?php foreach ($systemNavGroups as $groupLabel => $items): ?>
                <div class="col-12 col-xl-6">
                    <div class="small text-uppercase text-secondary fw-bold mb-2"><?php echo htmlspecialchars($groupLabel); ?></div>
                    <div class="btn-list">
                        <?php foreach ($items as $item): ?>
                            <a class="btn <?php echo $currentSystemPage === $item['slug'] ? 'btn-primary' : 'btn-outline-primary'; ?>"
                               href="<?php echo htmlspecialchars($item['url']); ?>">
                                <?php echo htmlspecialchars($item['label']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
