<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Erwartet:
 * - $previewCard: array{
 *     serpTitleId:string,
 *     serpTitle:string,
 *     serpUrlId:string,
 *     serpUrl:string,
 *     serpDescriptionId:string,
 *     serpDescription:string,
 *     socialImageId:string,
 *     socialImage:string,
 *     socialImageVisible:bool,
 *     socialTitleId:string,
 *     socialTitle:string,
 *     socialDescriptionId:string,
 *     socialDescription:string
 *   }
 */

$previewCard = $previewCard ?? [];
$socialImageVisible = !empty($previewCard['socialImageVisible']);
?>
<div class="card cms-edit-card h-100 w-100">
    <div class="card-header"><h3 class="card-title">Vorschau-Card</h3></div>
    <div class="card-body">
        <div class="text-uppercase text-secondary small mb-2">SERP</div>
        <div class="border rounded p-3 bg-light mb-4">
            <div id="<?php echo htmlspecialchars((string)($previewCard['serpTitleId'] ?? '')); ?>" class="fw-semibold text-primary mb-1"><?php echo htmlspecialchars((string)($previewCard['serpTitle'] ?? '')); ?></div>
            <div id="<?php echo htmlspecialchars((string)($previewCard['serpUrlId'] ?? '')); ?>" class="small text-success mb-1"><?php echo htmlspecialchars((string)($previewCard['serpUrl'] ?? '')); ?></div>
            <div id="<?php echo htmlspecialchars((string)($previewCard['serpDescriptionId'] ?? '')); ?>" class="small text-secondary"><?php echo htmlspecialchars((string)($previewCard['serpDescription'] ?? '')); ?></div>
        </div>
        <div class="text-uppercase text-secondary small mb-2">Social</div>
        <div class="border rounded overflow-hidden bg-light">
            <img id="<?php echo htmlspecialchars((string)($previewCard['socialImageId'] ?? '')); ?>"
                 src="<?php echo htmlspecialchars((string)($previewCard['socialImage'] ?? '')); ?>"
                 alt=""
                 style="display:<?php echo $socialImageVisible ? 'block' : 'none'; ?>; width:100%; height:160px; object-fit:cover;">
            <div class="p-3">
                <div class="text-uppercase text-secondary small mb-1">facebook / x</div>
                <div id="<?php echo htmlspecialchars((string)($previewCard['socialTitleId'] ?? '')); ?>" class="fw-semibold mb-1"><?php echo htmlspecialchars((string)($previewCard['socialTitle'] ?? '')); ?></div>
                <div id="<?php echo htmlspecialchars((string)($previewCard['socialDescriptionId'] ?? '')); ?>" class="small text-secondary"><?php echo htmlspecialchars((string)($previewCard['socialDescription'] ?? '')); ?></div>
            </div>
        </div>
    </div>
</div>
