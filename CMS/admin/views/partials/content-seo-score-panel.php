<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Erwartet:
 * - $seoScorePanel: array{
 *     badgeId:string,
 *     scoreLabelId:string,
 *     scoreBarId:string,
 *     rulesId:string,
 *     summaryCards: array<int,array{width:string,label:string,valueId?:string,valueText?:string,suffix?:string,badgeId?:string,badgeText?:string,bodyText?:string,badgeClass?:string}>
 *   }
 */

$seoScorePanel = $seoScorePanel ?? [];
$summaryCards = is_array($seoScorePanel['summaryCards'] ?? null) ? $seoScorePanel['summaryCards'] : [];
?>
<details class="card cms-edit-card" open>
    <summary class="card-header" style="cursor:pointer; list-style:none;">
        <div class="d-flex align-items-center justify-content-between w-100">
            <h3 class="card-title mb-0">SEO-Score &amp; Checkliste</h3>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-danger-lt text-danger" id="<?php echo htmlspecialchars((string)($seoScorePanel['badgeId'] ?? '')); ?>">Rot</span>
                <strong class="h2 mb-0" id="<?php echo htmlspecialchars((string)($seoScorePanel['scoreLabelId'] ?? '')); ?>">0</strong>
            </div>
        </div>
    </summary>
    <div class="card-body">
        <div class="progress progress-sm mb-3"><div class="progress-bar bg-danger" id="<?php echo htmlspecialchars((string)($seoScorePanel['scoreBarId'] ?? '')); ?>" style="width:0%"></div></div>
        <div class="row g-3 mb-3">
            <?php foreach ($summaryCards as $card): ?>
                <div class="<?php echo htmlspecialchars((string)($card['width'] ?? 'col-md-3')); ?>">
                    <div class="border rounded p-3 h-100">
                        <div class="text-secondary small mb-1"><?php echo htmlspecialchars((string)($card['label'] ?? '')); ?></div>
                        <?php if (!empty($card['badgeId'])): ?>
                            <span class="<?php echo htmlspecialchars((string)($card['badgeClass'] ?? 'badge bg-yellow-lt text-yellow')); ?>" id="<?php echo htmlspecialchars((string)$card['badgeId']); ?>"><?php echo htmlspecialchars((string)($card['badgeText'] ?? '')); ?></span>
                        <?php elseif (!empty($card['valueId'])): ?>
                            <div class="h3 m-0" id="<?php echo htmlspecialchars((string)$card['valueId']); ?>"><?php echo htmlspecialchars((string)($card['valueText'] ?? '0')); ?></div>
                            <?php if (!empty($card['suffix'])): ?>
                                <div class="text-secondary small"><?php echo htmlspecialchars((string)$card['suffix']); ?></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="small"><?php echo htmlspecialchars((string)($card['bodyText'] ?? '')); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div id="<?php echo htmlspecialchars((string)($seoScorePanel['rulesId'] ?? '')); ?>"></div>
    </div>
</details>
