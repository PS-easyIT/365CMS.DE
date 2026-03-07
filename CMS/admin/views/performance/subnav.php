<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$currentPerformancePage = $activePage ?? 'performance';
$performanceNavItems = [
    ['slug' => 'performance', 'label' => 'Übersicht', 'url' => SITE_URL . '/admin/performance'],
    ['slug' => 'performance-cache', 'label' => 'Cache-Verwaltung', 'url' => SITE_URL . '/admin/performance-cache'],
    ['slug' => 'performance-media', 'label' => 'Medien-Optimierung', 'url' => SITE_URL . '/admin/performance-media'],
    ['slug' => 'performance-database', 'label' => 'Datenbank-Wartung', 'url' => SITE_URL . '/admin/performance-database'],
    ['slug' => 'performance-settings', 'label' => 'Performance-Einstellungen', 'url' => SITE_URL . '/admin/performance-settings'],
    ['slug' => 'performance-sessions', 'label' => 'Session-Verwaltung', 'url' => SITE_URL . '/admin/performance-sessions'],
];
?>
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="btn-list">
            <?php foreach ($performanceNavItems as $item): ?>
                <a class="btn <?php echo $currentPerformancePage === $item['slug'] ? 'btn-primary' : 'btn-outline-primary'; ?>"
                   href="<?php echo htmlspecialchars($item['url']); ?>">
                    <?php echo htmlspecialchars($item['label']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
