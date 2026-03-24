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
<div class="card cms-edit-card h-100 w-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Lesbarkeits-Card</h3>
        <span class="badge bg-danger-lt text-danger" id="<?php echo htmlspecialchars((string)($readabilityCard['badgeId'] ?? '')); ?>">Kritisch</span>
    </div>
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
</div>
