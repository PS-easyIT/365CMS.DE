<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Favoriten';
$pageKey = 'favorites';
$pageAssets = [];
$favorites = $controller->getFavorites();

include __DIR__ . '/partials/header.php';
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Gespeicherte Elemente</h3>
        <span class="badge bg-primary-lt"><?= (int) count($favorites) ?></span>
    </div>
    <div class="list-group list-group-flush list-group-hoverable">
        <?php if ($favorites === []): ?>
            <div class="card-body text-secondary">Es wurden noch keine Favoriten gespeichert. Sobald Plugins oder Bereiche Favoriten bereitstellen, erscheinen sie hier.</div>
        <?php else: ?>
            <?php foreach ($favorites as $favorite): ?>
                <a class="list-group-item list-group-item-action" href="<?= htmlspecialchars((string)($favorite['url'] ?? '#')) ?>">
                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <div class="fw-medium"><?= htmlspecialchars((string)($favorite['title'] ?? 'Favorit')) ?></div>
                            <div class="text-secondary small"><?= htmlspecialchars((string)($favorite['type'] ?? 'Eintrag')) ?></div>
                        </div>
                        <div class="text-secondary small"><?= htmlspecialchars((string)($favorite['created_at'] ?? '')) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php';
