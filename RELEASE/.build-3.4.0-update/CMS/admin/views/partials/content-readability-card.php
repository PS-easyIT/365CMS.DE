<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Erwartet:
 * - $readabilityCard: array{
 *     badgeId:string,
 *     summaryId:string,
 *     metrics: array<int,array{id:string,label:string}>
 *   }
 */

$readabilityCard = $readabilityCard ?? [];
$readabilityMetrics = is_array($readabilityCard['metrics'] ?? null) ? $readabilityCard['metrics'] : [];
?>
<details class="card cms-edit-card h-100 w-100 cms-collapsible-card">
    <summary class="card-header d-flex justify-content-between align-items-center cms-collapsible-card__summary">
        <h3 class="card-title mb-0">Lesbarkeits-Card</h3>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger-lt text-danger" id="<?php echo htmlspecialchars((string)($readabilityCard['badgeId'] ?? '')); ?>">Kritisch</span>
            <span class="cms-collapsible-card__chevron" aria-hidden="true"></span>
        </div>
    </summary>
    <div class="card-body">
        <div class="text-secondary small mb-3" id="<?php echo htmlspecialchars((string)($readabilityCard['summaryId'] ?? '')); ?>">0 Wörter · 0 lange Sätze · 0 lange Absätze</div>
        <div class="row g-3">
            <?php foreach ($readabilityMetrics as $metric): ?>
                <div class="col-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-secondary small mb-1"><?php echo htmlspecialchars((string)($metric['label'] ?? '')); ?></div>
                        <div class="h3 m-0" id="<?php echo htmlspecialchars((string)($metric['id'] ?? '')); ?>">0</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</details>
