<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$sectionNavGroups = is_array($sectionNavGroups ?? null) ? $sectionNavGroups : [];
$currentSectionPage = (string)($currentSectionPage ?? '');
$hasMultipleGroups = count($sectionNavGroups) > 1;
?>
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="row g-3 align-items-start">
            <?php foreach ($sectionNavGroups as $group): ?>
                <?php
                $groupLabel = htmlspecialchars((string)($group['label'] ?? ''));
                $groupItems = is_array($group['items'] ?? null) ? $group['items'] : [];
                $groupColumnClass = $hasMultipleGroups ? 'col-12 col-xl-6' : 'col-12';
                ?>
                <div class="<?php echo $groupColumnClass; ?>">
                    <?php if ($groupLabel !== ''): ?>
                        <div class="small text-uppercase text-secondary fw-bold mb-2"><?php echo $groupLabel; ?></div>
                    <?php endif; ?>
                    <div class="btn-list">
                        <?php foreach ($groupItems as $item): ?>
                            <?php
                            $slug = (string)($item['slug'] ?? '');
                            $label = htmlspecialchars((string)($item['label'] ?? ''));
                            $url = htmlspecialchars((string)($item['url'] ?? ''));
                            $isActive = $currentSectionPage === $slug;
                            ?>
                            <a class="btn <?php echo $isActive ? 'btn-primary' : 'btn-outline-primary'; ?>" href="<?php echo $url; ?>">
                                <?php echo $label; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
