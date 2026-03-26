<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$template = $data['template'] ?? [];
$isNew = (bool)($data['isNew'] ?? true);
$baseTemplateOptions = $data['baseTemplateOptions'] ?? [];
$templateLinks = $template['links'] ?? [];
$templateSections = $template['sections'] ?? [];
$templateMeta = $template['meta'] ?? [];
$templateMetaLabels = $template['meta_labels'] ?? [];
$templateColors = $template['colors'] ?? [];
$cardSchema = $template['card_schema'] ?? [];
$cardDesign = $template['card_design'] ?? [];
$starterCards = $template['starter_cards'] ?? [];
$baseTemplateDefaults = $data['baseTemplateDefaults'] ?? [];

$sanitizeColor = static function (mixed $value, string $fallback): string {
    $color = trim((string)$value);
    return preg_match('/^#[0-9a-fA-F]{3,8}$/', $color) ? $color : $fallback;
};

$templatePreviewStyle = implode('; ', [
    '--hub-preview-hero-start: ' . $sanitizeColor($templateColors['hero_start'] ?? '#1e3a5f', '#1e3a5f'),
    '--hub-preview-hero-end: ' . $sanitizeColor($templateColors['hero_end'] ?? '#0f2240', '#0f2240'),
    '--hub-preview-accent: ' . $sanitizeColor($templateColors['accent'] ?? '#0d9488', '#0d9488'),
    '--hub-preview-surface: ' . $sanitizeColor($templateColors['surface'] ?? '#ffffff', '#ffffff'),
    '--hub-preview-section: ' . $sanitizeColor($templateColors['section_background'] ?? '#f1f5f9', '#f1f5f9'),
    '--hub-preview-card-bg: ' . $sanitizeColor($templateColors['card_background'] ?? '#ffffff', '#ffffff'),
    '--hub-preview-card-text: ' . $sanitizeColor($templateColors['card_text'] ?? '#1e293b', '#1e293b'),
    '--hub-preview-table-head-start: ' . $sanitizeColor($templateColors['table_header_start'] ?? $templateColors['hero_start'] ?? '#1e3a5f', '#1e3a5f'),
    '--hub-preview-table-head-end: ' . $sanitizeColor($templateColors['table_header_end'] ?? $templateColors['hero_end'] ?? '#0f2240', '#0f2240'),
    '--hub-preview-radius: ' . max(0, min(48, (int)($cardDesign['card_radius'] ?? 20))) . 'px',
]);

$editorPayload = json_encode([
    'isNew' => $isNew,
    'baseTemplateDefaults' => $baseTemplateDefaults,
    'links' => $templateLinks,
    'sections' => $templateSections,
    'starterCards' => $starterCards,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

if ($editorPayload === false) {
    $editorPayload = '{}';
}
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites?action=templates" class="btn btn-ghost-secondary btn-sm me-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6"/></svg>
                    Zurück zu Templates
                </a>
            </div>
            <div class="col">
                <div class="page-pretitle">Hub-Site Templates</div>
                <h2 class="page-title"><?php echo $isNew ? 'Neues Template' : 'Template bearbeiten'; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites">Content</a></li>
            <li class="nav-item"><a class="nav-link active" href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites?action=templates">Templates</a></li>
        </ul>

        <?php if (!empty($alert)): ?>
            <?php $alertData = $alert; $alertMarginClass = 'mb-3'; require __DIR__ . '/../partials/flash-alert.php'; ?>
        <?php endif; ?>

        <form method="post" action="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites" id="hubTemplateForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="save-template">
            <input type="hidden" name="template_key" value="<?php echo htmlspecialchars((string)($template['key'] ?? '')); ?>">
            <input type="hidden" name="template_links_json" id="templateLinksJsonInput" value="<?php echo htmlspecialchars(json_encode($templateLinks, JSON_UNESCAPED_UNICODE)); ?>">
            <input type="hidden" name="template_sections_json" id="templateSectionsJsonInput" value="<?php echo htmlspecialchars(json_encode($templateSections, JSON_UNESCAPED_UNICODE)); ?>">
            <input type="hidden" name="template_starter_cards_json" id="templateStarterCardsJsonInput" value="<?php echo htmlspecialchars(json_encode($starterCards, JSON_UNESCAPED_UNICODE)); ?>">
            <script type="application/json" id="hubTemplateEditorPayload"><?php echo $editorPayload; ?></script>

            <div class="row g-4">
                <?php require __DIR__ . '/template-edit/main-column.php'; ?>
                <?php require __DIR__ . '/template-edit/sidebar-column.php'; ?>
            </div>
        </form>
    </div>
</div>
