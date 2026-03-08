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

        <form method="post" action="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites" id="hubTemplateForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="save-template">
            <input type="hidden" name="template_key" value="<?php echo htmlspecialchars((string)($template['key'] ?? '')); ?>">
            <input type="hidden" name="template_links_json" id="templateLinksJsonInput" value="<?php echo htmlspecialchars(json_encode($templateLinks, JSON_UNESCAPED_UNICODE)); ?>">
            <input type="hidden" name="template_sections_json" id="templateSectionsJsonInput" value="<?php echo htmlspecialchars(json_encode($templateSections, JSON_UNESCAPED_UNICODE)); ?>">
            <input type="hidden" name="template_starter_cards_json" id="templateStarterCardsJsonInput" value="<?php echo htmlspecialchars(json_encode($starterCards, JSON_UNESCAPED_UNICODE)); ?>">

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Basisdaten</h3></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-7">
                                    <label class="form-label required">Template-Name</label>
                                    <input type="text" class="form-control" name="template_label" value="<?php echo htmlspecialchars((string)($template['label'] ?? '')); ?>" required>
                                    <div class="form-hint">Der sichtbare Name darf geändert werden, der interne Key bleibt stabil — keine unnötigen Hub-Site-Explosionen inklusive.</div>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Basis-Layout</label>
                                    <select class="form-select" name="base_template">
                                        <?php foreach ($baseTemplateOptions as $value => $label): ?>
                                            <option value="<?php echo htmlspecialchars((string)$value); ?>" <?php echo (($template['base_template'] ?? 'general-it') === $value) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)$label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Kurzbeschreibung</label>
                                    <textarea class="form-control" name="template_summary" rows="3"><?php echo htmlspecialchars((string)($template['summary'] ?? '')); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Template-Meta & Beschriftungen</h3></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Meta-Label: Zielgruppe</label>
                                    <input type="text" class="form-control" name="template_label_audience" value="<?php echo htmlspecialchars((string)($templateMetaLabels['audience'] ?? 'Zielgruppe')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Meta-Wert</label>
                                    <input type="text" class="form-control" name="template_meta_audience" value="<?php echo htmlspecialchars((string)($templateMeta['audience'] ?? '')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Meta-Label: Verantwortlich</label>
                                    <input type="text" class="form-control" name="template_label_owner" value="<?php echo htmlspecialchars((string)($templateMetaLabels['owner'] ?? 'Verantwortlich')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Meta-Wert</label>
                                    <input type="text" class="form-control" name="template_meta_owner" value="<?php echo htmlspecialchars((string)($templateMeta['owner'] ?? '')); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Meta-Label: Update-Zyklus</label>
                                    <input type="text" class="form-control" name="template_label_update_cycle" value="<?php echo htmlspecialchars((string)($templateMetaLabels['update_cycle'] ?? 'Update-Zyklus')); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Meta-Label: Fokus</label>
                                    <input type="text" class="form-control" name="template_label_focus" value="<?php echo htmlspecialchars((string)($templateMetaLabels['focus'] ?? 'Fokus')); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Meta-Label: KPI</label>
                                    <input type="text" class="form-control" name="template_label_kpi" value="<?php echo htmlspecialchars((string)($templateMetaLabels['kpi'] ?? 'KPI')); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Meta-Wert: Update-Zyklus</label>
                                    <input type="text" class="form-control" name="template_meta_update_cycle" value="<?php echo htmlspecialchars((string)($templateMeta['update_cycle'] ?? '')); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Meta-Wert: Fokus</label>
                                    <input type="text" class="form-control" name="template_meta_focus" value="<?php echo htmlspecialchars((string)($templateMeta['focus'] ?? '')); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Meta-Wert: KPI</label>
                                    <input type="text" class="form-control" name="template_meta_kpi" value="<?php echo htmlspecialchars((string)($templateMeta['kpi'] ?? '')); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Farben & Flächen</h3></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label">Hero Verlauf Start</label><input type="color" class="form-control form-control-color" name="template_color_hero_start" value="<?php echo htmlspecialchars((string)($templateColors['hero_start'] ?? '#1f2937')); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Hero Verlauf Ende</label><input type="color" class="form-control form-control-color" name="template_color_hero_end" value="<?php echo htmlspecialchars((string)($templateColors['hero_end'] ?? '#0f172a')); ?>"></div>
                                <div class="col-md-4"><label class="form-label">Akzent / Buttons</label><input type="color" class="form-control form-control-color" name="template_color_accent" value="<?php echo htmlspecialchars((string)($templateColors['accent'] ?? '#2563eb')); ?>"></div>
                                <div class="col-md-4"><label class="form-label">Oberfläche</label><input type="color" class="form-control form-control-color" name="template_color_surface" value="<?php echo htmlspecialchars((string)($templateColors['surface'] ?? '#ffffff')); ?>"></div>
                                <div class="col-md-4"><label class="form-label">Bereichs-Hintergrund</label><input type="color" class="form-control form-control-color" name="template_color_section_background" value="<?php echo htmlspecialchars((string)($templateColors['section_background'] ?? '#ffffff')); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Card-Hintergrund</label><input type="color" class="form-control form-control-color" name="template_color_card_background" value="<?php echo htmlspecialchars((string)($templateColors['card_background'] ?? '#ffffff')); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Card-Text</label><input type="color" class="form-control form-control-color" name="template_color_card_text" value="<?php echo htmlspecialchars((string)($templateColors['card_text'] ?? '#0f172a')); ?>"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Kachel-Schema</h3></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Kacheln pro Reihe</label>
                                    <select class="form-select" name="template_card_columns">
                                        <?php foreach ([1, 2, 3] as $columnCount): ?>
                                            <option value="<?php echo $columnCount; ?>" <?php echo ((int)($cardSchema['columns'] ?? 2) === $columnCount) ? 'selected' : ''; ?>><?php echo $columnCount; ?> nebeneinander</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4"><label class="form-label">Card-Layout</label><select class="form-select" name="hub_card_layout"><option value="standard" <?php echo (($cardDesign['layout'] ?? 'standard') === 'standard') ? 'selected' : ''; ?>>Standard Grid</option><option value="feature" <?php echo (($cardDesign['layout'] ?? '') === 'feature') ? 'selected' : ''; ?>>Feature / großzügig</option><option value="compact" <?php echo (($cardDesign['layout'] ?? '') === 'compact') ? 'selected' : ''; ?>>Kompakt / dichter</option></select></div>
                                <div class="col-md-4"><label class="form-label">Meta-Layout</label><select class="form-select" name="hub_card_meta_layout"><option value="split" <?php echo (($cardDesign['meta_layout'] ?? 'split') === 'split') ? 'selected' : ''; ?>>Links / Rechts</option><option value="stacked" <?php echo (($cardDesign['meta_layout'] ?? '') === 'stacked') ? 'selected' : ''; ?>>Gestapelt</option></select></div>
                                <div class="col-md-4"><label class="form-label">Bildposition</label><select class="form-select" name="hub_card_image_position"><option value="top" <?php echo (($cardDesign['image_position'] ?? 'top') === 'top') ? 'selected' : ''; ?>>Oben</option><option value="left" <?php echo (($cardDesign['image_position'] ?? '') === 'left') ? 'selected' : ''; ?>>Links</option><option value="right" <?php echo (($cardDesign['image_position'] ?? '') === 'right') ? 'selected' : ''; ?>>Rechts</option></select></div>
                                <div class="col-md-4"><label class="form-label">Bilddarstellung</label><select class="form-select" name="hub_card_image_fit"><option value="cover" <?php echo (($cardDesign['image_fit'] ?? 'cover') === 'cover') ? 'selected' : ''; ?>>Cover</option><option value="contain" <?php echo (($cardDesign['image_fit'] ?? '') === 'contain') ? 'selected' : ''; ?>>Contain</option></select></div>
                                <div class="col-md-4"><label class="form-label">Bildformat</label><select class="form-select" name="hub_card_image_ratio"><option value="wide" <?php echo (($cardDesign['image_ratio'] ?? 'wide') === 'wide') ? 'selected' : ''; ?>>Breit</option><option value="square" <?php echo (($cardDesign['image_ratio'] ?? '') === 'square') ? 'selected' : ''; ?>>Quadratisch</option><option value="portrait" <?php echo (($cardDesign['image_ratio'] ?? '') === 'portrait') ? 'selected' : ''; ?>>Hochformat</option></select></div>
                                <div class="col-md-6"><label class="form-label">Feldlabel: Titel</label><input type="text" class="form-control" name="template_card_title_label" value="<?php echo htmlspecialchars((string)($cardSchema['title_label'] ?? 'Titel')); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Feldlabel: Kurzbeschreibung</label><input type="text" class="form-control" name="template_card_summary_label" value="<?php echo htmlspecialchars((string)($cardSchema['summary_label'] ?? 'Kurzbeschreibung')); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Feldlabel: Badge</label><input type="text" class="form-control" name="template_card_badge_label" value="<?php echo htmlspecialchars((string)($cardSchema['badge_label'] ?? 'Badge')); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Feldlabel: Meta links</label><input type="text" class="form-control" name="template_card_meta_left_label" value="<?php echo htmlspecialchars((string)($cardSchema['meta_left_label'] ?? 'Meta links')); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Feldlabel: Meta rechts</label><input type="text" class="form-control" name="template_card_meta_right_label" value="<?php echo htmlspecialchars((string)($cardSchema['meta_right_label'] ?? 'Meta rechts')); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Feldlabel: Bild-URL</label><input type="text" class="form-control" name="template_card_image_label" value="<?php echo htmlspecialchars((string)($cardSchema['image_label'] ?? 'Bild-URL')); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Feldlabel: Bild-Alt</label><input type="text" class="form-control" name="template_card_image_alt_label" value="<?php echo htmlspecialchars((string)($cardSchema['image_alt_label'] ?? 'Bild-Alt')); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Feldlabel: Button-Text</label><input type="text" class="form-control" name="template_card_button_text_label" value="<?php echo htmlspecialchars((string)($cardSchema['button_text_label'] ?? 'Button-Text')); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Feldlabel: Button-Link</label><input type="text" class="form-control" name="template_card_button_link_label" value="<?php echo htmlspecialchars((string)($cardSchema['button_link_label'] ?? 'Button-Link')); ?>"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">Starter-Kacheln</h3>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addStarterCard">Starter-Kachel hinzufügen</button>
                        </div>
                        <div class="card-body p-0">
                            <div id="starterCardsContainer"></div>
                            <div class="text-center text-secondary py-4 d-none" id="starterCardsEmpty">Keine Starter-Kacheln definiert. Mindestens eine lohnt sich meistens.</div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">Header-Links</h3>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addTemplateLink">Link hinzufügen</button>
                        </div>
                        <div class="card-body p-0">
                            <div id="templateLinksContainer"></div>
                            <div class="text-center text-secondary py-4 d-none" id="templateLinksEmpty">Noch keine Header-Links vorhanden.</div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">Designbereiche</h3>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addTemplateSection">Bereich hinzufügen</button>
                        </div>
                        <div class="card-body p-0">
                            <div id="templateSectionsContainer"></div>
                            <div class="text-center text-secondary py-4 d-none" id="templateSectionsEmpty">Noch keine Bereiche vorhanden.</div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Hinweis</h3></div>
                        <div class="card-body text-secondary small">
                            Dieses Profil steuert Aufbau, Farben und Starter-Kacheln aller zugeordneten Hub-Sites. Templates sind hier also wirklich Templates — keine verkleideten Einzelinstanzen mehr.
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Empfehlung</h3></div>
                        <div class="card-body text-secondary small">
                            Zwei Kacheln nebeneinander funktionieren für viele Layouts am ausgewogensten. Für kompakte oder produktnahe Hubs kannst du hier aber gezielt auf 1 oder 3 Spalten wechseln.
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100"><?php echo $isNew ? 'Template anlegen' : 'Template speichern'; ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var links = <?php echo json_encode($templateLinks, JSON_UNESCAPED_UNICODE); ?>;
    var sections = <?php echo json_encode($templateSections, JSON_UNESCAPED_UNICODE); ?>;
    var starterCards = <?php echo json_encode($starterCards, JSON_UNESCAPED_UNICODE); ?>;
    var linksInput = document.getElementById('templateLinksJsonInput');
    var sectionsInput = document.getElementById('templateSectionsJsonInput');
    var starterCardsInput = document.getElementById('templateStarterCardsJsonInput');
    var linksContainer = document.getElementById('templateLinksContainer');
    var sectionsContainer = document.getElementById('templateSectionsContainer');
    var starterCardsContainer = document.getElementById('starterCardsContainer');
    var linksEmpty = document.getElementById('templateLinksEmpty');
    var sectionsEmpty = document.getElementById('templateSectionsEmpty');
    var starterCardsEmpty = document.getElementById('starterCardsEmpty');

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(value || ''));
        return div.innerHTML;
    }

    function sync() {
        linksInput.value = JSON.stringify(links);
        sectionsInput.value = JSON.stringify(sections);
        starterCardsInput.value = JSON.stringify(starterCards);
    }

    function renderLinks() {
        linksContainer.innerHTML = '';
        linksEmpty.classList.toggle('d-none', links.length !== 0);
        links.forEach(function (link, index) {
            var html = '';
            html += '<div class="border-bottom p-3"><div class="row g-2">';
            html += '<div class="col-md-5"><label class="form-label small">Label</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(link.label || '') + '" data-link-index="' + index + '" data-link-key="label"></div>';
            html += '<div class="col-md-5"><label class="form-label small">URL / Anchor</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(link.url || '') + '" data-link-index="' + index + '" data-link-key="url"></div>';
            html += '<div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-outline-danger btn-sm w-100 remove-template-link" data-link-index="' + index + '">Entfernen</button></div>';
            html += '</div></div>';
            linksContainer.insertAdjacentHTML('beforeend', html);
        });
        sync();
    }

    function renderSections() {
        sectionsContainer.innerHTML = '';
        sectionsEmpty.classList.toggle('d-none', sections.length !== 0);
        sections.forEach(function (section, index) {
            var html = '';
            html += '<div class="border-bottom p-3"><div class="row g-2">';
            html += '<div class="col-md-6"><label class="form-label small">Titel</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(section.title || '') + '" data-section-index="' + index + '" data-section-key="title"></div>';
            html += '<div class="col-md-6"><label class="form-label small">CTA Label</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(section.actionLabel || '') + '" data-section-index="' + index + '" data-section-key="actionLabel"></div>';
            html += '<div class="col-12"><label class="form-label small">Beschreibung</label><textarea class="form-control form-control-sm" rows="3" data-section-index="' + index + '" data-section-key="text">' + escapeHtml(section.text || '') + '</textarea></div>';
            html += '<div class="col-md-8"><label class="form-label small">CTA URL</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(section.actionUrl || '') + '" data-section-index="' + index + '" data-section-key="actionUrl"></div>';
            html += '<div class="col-md-4 d-flex align-items-end"><button type="button" class="btn btn-outline-danger btn-sm w-100 remove-template-section" data-section-index="' + index + '">Entfernen</button></div>';
            html += '</div></div>';
            sectionsContainer.insertAdjacentHTML('beforeend', html);
        });
        sync();
    }

    function renderStarterCards() {
        starterCardsContainer.innerHTML = '';
        starterCardsEmpty.classList.toggle('d-none', starterCards.length !== 0);
        starterCards.forEach(function (card, index) {
            var html = '';
            html += '<div class="border-bottom p-3"><div class="row g-2">';
            html += '<div class="col-md-6"><label class="form-label small">Titel</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.title || '') + '" data-card-index="' + index + '" data-card-key="title"></div>';
            html += '<div class="col-md-6"><label class="form-label small">Ziel-URL</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.url || '') + '" data-card-index="' + index + '" data-card-key="url"></div>';
            html += '<div class="col-md-6"><label class="form-label small">Badge</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.badge || '') + '" data-card-index="' + index + '" data-card-key="badge"></div>';
            html += '<div class="col-md-6"><label class="form-label small">Button-Text</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.button_text || '') + '" data-card-index="' + index + '" data-card-key="button_text"></div>';
            html += '<div class="col-md-6"><label class="form-label small">Meta links</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.meta_left || '') + '" data-card-index="' + index + '" data-card-key="meta_left"></div>';
            html += '<div class="col-md-6"><label class="form-label small">Meta rechts</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.meta_right || '') + '" data-card-index="' + index + '" data-card-key="meta_right"></div>';
            html += '<div class="col-md-8"><label class="form-label small">Bild-URL</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.image_url || '') + '" data-card-index="' + index + '" data-card-key="image_url"></div>';
            html += '<div class="col-md-4"><label class="form-label small">Bild-Alt</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.image_alt || '') + '" data-card-index="' + index + '" data-card-key="image_alt"></div>';
            html += '<div class="col-md-12"><label class="form-label small">Button-Link</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.button_link || '') + '" data-card-index="' + index + '" data-card-key="button_link"></div>';
            html += '<div class="col-12"><label class="form-label small">Kurzbeschreibung</label><textarea class="form-control form-control-sm" rows="3" data-card-index="' + index + '" data-card-key="summary">' + escapeHtml(card.summary || '') + '</textarea></div>';
            html += '<div class="col-12 text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-starter-card" data-card-index="' + index + '">Entfernen</button></div>';
            html += '</div></div>';
            starterCardsContainer.insertAdjacentHTML('beforeend', html);
        });
        sync();
    }

    document.getElementById('addTemplateLink').addEventListener('click', function () {
        links.push({ label: '', url: '' });
        renderLinks();
    });

    document.getElementById('addTemplateSection').addEventListener('click', function () {
        sections.push({ title: '', text: '', actionLabel: '', actionUrl: '' });
        renderSections();
    });

    document.getElementById('addStarterCard').addEventListener('click', function () {
        if (starterCards.length >= 3) {
            if (typeof cmsAlert === 'function') {
                cmsAlert('Maximal drei Starter-Kacheln pro Template sind möglich.', 'warning');
            }
            return;
        }

        starterCards.push({ title: '', url: '#', summary: '', badge: '', meta_left: '', meta_right: '', image_url: '', image_alt: '', button_text: '', button_link: '' });
        renderStarterCards();
    });

    linksContainer.addEventListener('input', function (event) {
        var target = event.target;
        var index = parseInt(target.dataset.linkIndex || '-1', 10);
        var key = target.dataset.linkKey || '';
        if (index < 0 || !links[index] || !key) {
            return;
        }
        links[index][key] = target.value;
        sync();
    });

    linksContainer.addEventListener('click', function (event) {
        var button = event.target.closest('.remove-template-link');
        if (!button) {
            return;
        }
        var index = parseInt(button.dataset.linkIndex || '-1', 10);
        if (index < 0) {
            return;
        }
        links.splice(index, 1);
        renderLinks();
    });

    sectionsContainer.addEventListener('input', function (event) {
        var target = event.target;
        var index = parseInt(target.dataset.sectionIndex || '-1', 10);
        var key = target.dataset.sectionKey || '';
        if (index < 0 || !sections[index] || !key) {
            return;
        }
        sections[index][key] = target.value;
        sync();
    });

    sectionsContainer.addEventListener('click', function (event) {
        var button = event.target.closest('.remove-template-section');
        if (!button) {
            return;
        }
        var index = parseInt(button.dataset.sectionIndex || '-1', 10);
        if (index < 0) {
            return;
        }
        sections.splice(index, 1);
        renderSections();
    });

    starterCardsContainer.addEventListener('input', function (event) {
        var target = event.target;
        var index = parseInt(target.dataset.cardIndex || '-1', 10);
        var key = target.dataset.cardKey || '';
        if (index < 0 || !starterCards[index] || !key) {
            return;
        }
        starterCards[index][key] = target.value;
        sync();
    });

    starterCardsContainer.addEventListener('click', function (event) {
        var button = event.target.closest('.remove-starter-card');
        if (!button) {
            return;
        }
        var index = parseInt(button.dataset.cardIndex || '-1', 10);
        if (index < 0) {
            return;
        }
        starterCards.splice(index, 1);
        renderStarterCards();
    });

    renderLinks();
    renderSections();
    renderStarterCards();
})();
</script>
