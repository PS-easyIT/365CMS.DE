<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$site = $data['site'] ?? null;
$isNew = (bool)($data['isNew'] ?? true);
$defaults = $data['defaults'] ?? [];
$templateOptions = $data['templateOptions'] ?? [];
$templatePresets = $data['templatePresets'] ?? [];
$settings = $site['settings'] ?? $defaults;
$cards = $site['cards'] ?? [];
$hubLinks = json_decode((string)($settings['hub_links_json'] ?? '[]'), true);
$hubSections = json_decode((string)($settings['hub_sections_json'] ?? '[]'), true);
$hubLinks = is_array($hubLinks) ? $hubLinks : [];
$hubSections = is_array($hubSections) ? $hubSections : [];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites" class="btn btn-ghost-secondary btn-sm me-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6"/></svg>
                    Zurück
                </a>
            </div>
            <div class="col">
                <div class="page-pretitle">Hub-Sites</div>
                <h2 class="page-title"><?php echo $isNew ? 'Neue Hub-Site' : 'Hub-Site bearbeiten'; ?></h2>
            </div>
            <?php if (!$isNew): ?>
                <div class="col-auto">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-azure-lt">Public URL: /<?php echo htmlspecialchars((string)($settings['hub_slug'] ?? '')); ?></span>
                        <?php if (!empty($settings['hub_slug'])): ?>
                            <button type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    onclick="copyHubSlug('<?php echo htmlspecialchars(SITE_URL . '/' . ltrim((string)$settings['hub_slug'], '/'), ENT_QUOTES); ?>')">
                                Slug kopieren
                            </button>
                            <a href="<?php echo htmlspecialchars(SITE_URL . '/' . ltrim((string)$settings['hub_slug'], '/')); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">
                                Public Site öffnen
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <form method="post" action="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites" id="hubSiteForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="save">
            <?php if (!$isNew): ?>
                <input type="hidden" name="id" value="<?php echo (int)($site['id'] ?? 0); ?>">
            <?php endif; ?>
            <input type="hidden" name="open_public_after_save" id="openPublicAfterSaveInput" value="0">
            <input type="hidden" name="cards_json" id="cardsJsonInput" value="<?php echo htmlspecialchars(json_encode($cards, JSON_UNESCAPED_UNICODE)); ?>">
            <input type="hidden" name="hub_links_json" id="hubLinksJsonInput" value="<?php echo htmlspecialchars(json_encode($hubLinks, JSON_UNESCAPED_UNICODE)); ?>">
            <input type="hidden" name="hub_sections_json" id="hubSectionsJsonInput" value="<?php echo htmlspecialchars(json_encode($hubSections, JSON_UNESCAPED_UNICODE)); ?>">

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Basisdaten</h3></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label required">Name</label>
                                <input type="text" class="form-control" name="site_name" value="<?php echo htmlspecialchars((string)($site['table_name'] ?? '')); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Öffentlicher Slug</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="hubSlugPreviewInput" value="/<?php echo htmlspecialchars((string)($settings['hub_slug'] ?? '')); ?>" readonly>
                                    <button type="button" class="btn btn-outline-secondary" id="copySlugPreviewButton">Slug kopieren</button>
                                </div>
                                <div class="form-hint">Der Slug wird beim Speichern automatisch aus dem Titel erzeugt und als öffentliche Route im `cms-default` Theme bereitgestellt. Schon vor dem ersten Speichern wird hier eine Live-Vorschau angezeigt.</div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Beschreibung</label>
                                <textarea class="form-control" name="description" rows="2"><?php echo htmlspecialchars((string)($site['description'] ?? '')); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Hero / Einstieg</h3></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Themen-Badge</label>
                                    <input type="text" class="form-control" name="hub_badge" value="<?php echo htmlspecialchars((string)($settings['hub_badge'] ?? '')); ?>" placeholder="z. B. Microsoft 365">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Template</label>
                                    <select class="form-select" name="hub_template">
                                        <?php foreach ($templateOptions as $value => $label): ?>
                                            <option value="<?php echo htmlspecialchars((string)$value); ?>" <?php echo (($settings['hub_template'] ?? '') === $value) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)$label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Hero-Titel</label>
                                    <input type="text" class="form-control" name="hub_hero_title" value="<?php echo htmlspecialchars((string)($settings['hub_hero_title'] ?? '')); ?>" placeholder="Wenn leer, wird der Name der Hub Site verwendet">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Hero-Text</label>
                                    <textarea class="form-control" name="hub_hero_text" rows="4" placeholder="Ein kurzer Einleitungstext für diese Hub-Site."><?php echo htmlspecialchars((string)($settings['hub_hero_text'] ?? '')); ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">CTA Text</label>
                                    <input type="text" class="form-control" name="hub_cta_label" value="<?php echo htmlspecialchars((string)($settings['hub_cta_label'] ?? '')); ?>" placeholder="z. B. Alle Themen ansehen">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">CTA URL</label>
                                    <input type="text" class="form-control" name="hub_cta_url" value="<?php echo htmlspecialchars((string)($settings['hub_cta_url'] ?? '')); ?>" placeholder="/themen">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">Hub-Site Template-Editor</h3>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="applyTemplatePresetButton">Template-Vorlage anwenden</button>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 align-items-start">
                                <div class="col-lg-7">
                                    <div class="border rounded p-3 bg-body-tertiary">
                                        <div class="text-uppercase text-secondary small mb-2">Aktives Template</div>
                                        <div class="fw-semibold mb-1" id="templatePresetTitle"><?php echo htmlspecialchars((string)($templateOptions[$settings['hub_template'] ?? 'general-it'] ?? 'Hub-Site')); ?></div>
                                        <p class="text-secondary mb-0" id="templatePresetSummary">Wähle ein Template und übernimm die passenden Presets für Meta-Felder, Header-Links und Designbereiche.</p>
                                    </div>
                                </div>
                                <div class="col-lg-5">
                                    <div class="small text-secondary mb-2">Beim Anwenden werden überschrieben:</div>
                                    <ul class="small text-secondary mb-0 ps-3">
                                        <li>Meta-Felder</li>
                                        <li>Header-Links</li>
                                        <li>Designbereiche</li>
                                        <li>Card-Layout-Vorgaben</li>
                                    </ul>
                                </div>
                                <div class="col-12">
                                    <div class="row g-3 pt-2">
                                        <div class="col-md-6">
                                            <label class="form-label">Card-Layout</label>
                                            <select class="form-select" name="hub_card_layout" id="hubCardLayoutInput">
                                                <option value="standard" <?php echo (($settings['hub_card_layout'] ?? 'standard') === 'standard') ? 'selected' : ''; ?>>Standard Grid</option>
                                                <option value="feature" <?php echo (($settings['hub_card_layout'] ?? '') === 'feature') ? 'selected' : ''; ?>>Feature / großzügig</option>
                                                <option value="compact" <?php echo (($settings['hub_card_layout'] ?? '') === 'compact') ? 'selected' : ''; ?>>Kompakt / dichter</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Bildposition</label>
                                            <select class="form-select" name="hub_card_image_position" id="hubCardImagePositionInput">
                                                <option value="top" <?php echo (($settings['hub_card_image_position'] ?? 'top') === 'top') ? 'selected' : ''; ?>>Oben</option>
                                                <option value="left" <?php echo (($settings['hub_card_image_position'] ?? '') === 'left') ? 'selected' : ''; ?>>Links</option>
                                                <option value="right" <?php echo (($settings['hub_card_image_position'] ?? '') === 'right') ? 'selected' : ''; ?>>Rechts</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Bilddarstellung</label>
                                            <select class="form-select" name="hub_card_image_fit" id="hubCardImageFitInput">
                                                <option value="cover" <?php echo (($settings['hub_card_image_fit'] ?? 'cover') === 'cover') ? 'selected' : ''; ?>>Cover</option>
                                                <option value="contain" <?php echo (($settings['hub_card_image_fit'] ?? '') === 'contain') ? 'selected' : ''; ?>>Contain</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Bildformat</label>
                                            <select class="form-select" name="hub_card_image_ratio" id="hubCardImageRatioInput">
                                                <option value="wide" <?php echo (($settings['hub_card_image_ratio'] ?? 'wide') === 'wide') ? 'selected' : ''; ?>>Breit</option>
                                                <option value="square" <?php echo (($settings['hub_card_image_ratio'] ?? '') === 'square') ? 'selected' : ''; ?>>Quadratisch</option>
                                                <option value="portrait" <?php echo (($settings['hub_card_image_ratio'] ?? '') === 'portrait') ? 'selected' : ''; ?>>Hochformat</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Meta-Layout</label>
                                            <select class="form-select" name="hub_card_meta_layout" id="hubCardMetaLayoutInput">
                                                <option value="split" <?php echo (($settings['hub_card_meta_layout'] ?? 'split') === 'split') ? 'selected' : ''; ?>>Links / Rechts</option>
                                                <option value="stacked" <?php echo (($settings['hub_card_meta_layout'] ?? '') === 'stacked') ? 'selected' : ''; ?>>Gestapelt</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Template-Designbereiche</h3></div>
                        <div class="card-body">
                            <ul class="nav nav-tabs mb-3" id="hubTemplateTabs" role="tablist">
                                <li class="nav-item" role="presentation"><button class="nav-link active" type="button" data-hub-tab="meta">Meta</button></li>
                                <li class="nav-item" role="presentation"><button class="nav-link" type="button" data-hub-tab="links">Header-Links</button></li>
                                <li class="nav-item" role="presentation"><button class="nav-link" type="button" data-hub-tab="sections">Designbereiche</button></li>
                            </ul>

                            <div class="hub-tab-panel" data-hub-panel="meta">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Zielgruppe</label>
                                        <input type="text" class="form-control" name="hub_meta_audience" value="<?php echo htmlspecialchars((string)($settings['hub_meta_audience'] ?? '')); ?>" placeholder="z. B. IT-Leitung / Fachbereiche">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Verantwortlich</label>
                                        <input type="text" class="form-control" name="hub_meta_owner" value="<?php echo htmlspecialchars((string)($settings['hub_meta_owner'] ?? '')); ?>" placeholder="z. B. M365-Team">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Update-Zyklus</label>
                                        <input type="text" class="form-control" name="hub_meta_update_cycle" value="<?php echo htmlspecialchars((string)($settings['hub_meta_update_cycle'] ?? '')); ?>" placeholder="monatlich">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Fokus</label>
                                        <input type="text" class="form-control" name="hub_meta_focus" value="<?php echo htmlspecialchars((string)($settings['hub_meta_focus'] ?? '')); ?>" placeholder="Governance, Security …">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">KPI / Leitwert</label>
                                        <input type="text" class="form-control" name="hub_meta_kpi" value="<?php echo htmlspecialchars((string)($settings['hub_meta_kpi'] ?? '')); ?>" placeholder="z. B. Audit-Readiness">
                                    </div>
                                </div>
                            </div>

                            <div class="hub-tab-panel d-none" data-hub-panel="links">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <p class="text-secondary mb-0">Links erscheinen direkt unter dem Headerbereich der Hub-Site als thematische Navigation.</p>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="addHubLink">Link hinzufügen</button>
                                </div>
                                <div id="hubLinksContainer"></div>
                                <div class="text-center text-secondary py-4 d-none" id="hubLinksEmpty">Noch keine Header-Links vorhanden.</div>
                            </div>

                            <div class="hub-tab-panel d-none" data-hub-panel="sections">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <p class="text-secondary mb-0">Diese Bereiche verändern das Layout unterhalb des Headers je Template und dienen als Platzhalter für weitere Themenblöcke.</p>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="addHubSection">Designbereich hinzufügen</button>
                                </div>
                                <div id="hubSectionsContainer"></div>
                                <div class="text-center text-secondary py-4 d-none" id="hubSectionsEmpty">Noch keine Designbereiche vorhanden.</div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">Hub-Kacheln</h3>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addCard">Kachel hinzufügen</button>
                        </div>
                        <div class="card-body p-0">
                            <div id="cardsContainer"></div>
                            <div class="text-center text-secondary py-4 d-none" id="cardsEmpty">Noch keine Kacheln vorhanden.</div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Template-Varianten</h3></div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                                <li><strong>IT Themen Allgemein</strong><br><span class="text-secondary small">Breit, neutral, editorial.</span></li>
                                <li><strong>Microsoft 365</strong><br><span class="text-secondary small">Azure-/M365-Optik, modern.</span></li>
                                <li><strong>Datenschutz</strong><br><span class="text-secondary small">Vertrauen, Schutz, strukturierte Hinweise.</span></li>
                                <li><strong>Compliance</strong><br><span class="text-secondary small">Governance, Policies, Nachvollziehbarkeit.</span></li>
                                <li><strong>Linux</strong><br><span class="text-secondary small">Technischer, dunkler, terminalnaher Charakter.</span></li>
                            </ul>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <?php if (!$isNew && !empty($settings['hub_slug'])): ?>
                                <a href="<?php echo htmlspecialchars(SITE_URL . '/' . ltrim((string)$settings['hub_slug'], '/')); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary w-100 mb-2">
                                    Public Site im neuen Tab öffnen
                                </a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary w-100 mb-2"><?php echo $isNew ? 'Hub Site erstellen' : 'Hub Site aktualisieren'; ?></button>
                            <button type="button" class="btn btn-outline-primary w-100" id="saveAndOpenPublicButton"><?php echo $isNew ? 'Erstellen & Public Site öffnen' : 'Speichern & Public Site öffnen'; ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var cards = <?php echo json_encode($cards, JSON_UNESCAPED_UNICODE); ?>;
    var container = document.getElementById('cardsContainer');
    var emptyState = document.getElementById('cardsEmpty');
    var input = document.getElementById('cardsJsonInput');
    var form = document.getElementById('hubSiteForm');
    var titleInput = form.querySelector('input[name="site_name"]');
    var templateSelect = form.querySelector('select[name="hub_template"]');
    var slugPreviewInput = document.getElementById('hubSlugPreviewInput');
    var openPublicAfterSaveInput = document.getElementById('openPublicAfterSaveInput');
    var saveAndOpenPublicButton = document.getElementById('saveAndOpenPublicButton');
    var copySlugPreviewButton = document.getElementById('copySlugPreviewButton');
    var applyTemplatePresetButton = document.getElementById('applyTemplatePresetButton');
    var templatePresetTitle = document.getElementById('templatePresetTitle');
    var templatePresetSummary = document.getElementById('templatePresetSummary');
    var hubLinks = <?php echo json_encode($hubLinks, JSON_UNESCAPED_UNICODE); ?>;
    var hubSections = <?php echo json_encode($hubSections, JSON_UNESCAPED_UNICODE); ?>;
    var templateOptions = <?php echo json_encode($templateOptions, JSON_UNESCAPED_UNICODE); ?>;
    var templatePresets = <?php echo json_encode($templatePresets, JSON_UNESCAPED_UNICODE); ?>;
    var hubLinksInput = document.getElementById('hubLinksJsonInput');
    var hubSectionsInput = document.getElementById('hubSectionsJsonInput');
    var hubLinksContainer = document.getElementById('hubLinksContainer');
    var hubSectionsContainer = document.getElementById('hubSectionsContainer');
    var hubLinksEmpty = document.getElementById('hubLinksEmpty');
    var hubSectionsEmpty = document.getElementById('hubSectionsEmpty');

    function slugify(value) {
        return (value || '')
            .toString()
            .trim()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    function currentPublicUrl() {
        var slugValue = (slugPreviewInput.value || '').replace(/^\//, '').trim();
        return '<?php echo htmlspecialchars(rtrim(SITE_URL, '/'), ENT_QUOTES); ?>/' + slugValue;
    }

    function updateSlugPreview() {
        var storedSlug = '<?php echo htmlspecialchars((string)($settings['hub_slug'] ?? ''), ENT_QUOTES); ?>';
        var nextSlug = storedSlug;

        if (!nextSlug) {
            nextSlug = slugify(titleInput.value || '') || 'hub-site';
        }

        slugPreviewInput.value = '/' + nextSlug;
        copySlugPreviewButton.disabled = nextSlug === '';
    }

    function updateTemplatePresetInfo() {
        var key = templateSelect.value || 'general-it';
        var preset = templatePresets[key] || {};
        templatePresetTitle.textContent = templateOptions[key] || key;
        templatePresetSummary.textContent = preset.summary || 'Für dieses Template sind noch keine Presets hinterlegt.';
    }

    function applyTemplatePreset() {
        var key = templateSelect.value || 'general-it';
        var preset = templatePresets[key] || {};
        var meta = preset.meta || {};
        var cardDesign = preset.card_design || {};

        form.querySelector('input[name="hub_meta_audience"]').value = meta.audience || '';
        form.querySelector('input[name="hub_meta_owner"]').value = meta.owner || '';
        form.querySelector('input[name="hub_meta_update_cycle"]').value = meta.update_cycle || '';
        form.querySelector('input[name="hub_meta_focus"]').value = meta.focus || '';
        form.querySelector('input[name="hub_meta_kpi"]').value = meta.kpi || '';
        document.getElementById('hubCardLayoutInput').value = cardDesign.layout || 'standard';
        document.getElementById('hubCardImagePositionInput').value = cardDesign.image_position || 'top';
        document.getElementById('hubCardImageFitInput').value = cardDesign.image_fit || 'cover';
        document.getElementById('hubCardImageRatioInput').value = cardDesign.image_ratio || 'wide';
        document.getElementById('hubCardMetaLayoutInput').value = cardDesign.meta_layout || 'split';

        hubLinks = Array.isArray(preset.links) ? JSON.parse(JSON.stringify(preset.links)) : [];
        hubSections = Array.isArray(preset.sections) ? JSON.parse(JSON.stringify(preset.sections)) : [];

        renderHubLinks();
        renderHubSections();
        updateTemplatePresetInfo();
        cmsAlert('Template-Vorlage wurde geladen.', 'success');
    }

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(value || ''));
        return div.innerHTML;
    }

    function sync() {
        input.value = JSON.stringify(cards);
        hubLinksInput.value = JSON.stringify(hubLinks);
        hubSectionsInput.value = JSON.stringify(hubSections);
    }

    function render() {
        container.innerHTML = '';
        emptyState.classList.toggle('d-none', cards.length !== 0);

        cards.forEach(function (card, index) {
            var html = '';
            html += '<div class="border-bottom p-3">';
            html += '  <div class="row g-2">';
            html += '    <div class="col-md-6"><label class="form-label small">Titel</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.title || '') + '" data-index="' + index + '" data-key="title"></div>';
            html += '    <div class="col-md-6"><label class="form-label small">URL</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.url || '') + '" data-index="' + index + '" data-key="url"></div>';
            html += '    <div class="col-md-6"><label class="form-label small">Badge</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.badge || '') + '" data-index="' + index + '" data-key="badge"></div>';
            html += '    <div class="col-md-6"><label class="form-label small">Legacy Meta</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.meta || '') + '" data-index="' + index + '" data-key="meta"></div>';
            html += '    <div class="col-md-6"><label class="form-label small">Meta links</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.meta_left || '') + '" data-index="' + index + '" data-key="meta_left"></div>';
            html += '    <div class="col-md-6"><label class="form-label small">Meta rechts</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.meta_right || '') + '" data-index="' + index + '" data-key="meta_right"></div>';
            html += '    <div class="col-md-8"><label class="form-label small">Bild-URL</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.image_url || '') + '" data-index="' + index + '" data-key="image_url" placeholder="https://… oder /uploads/...\"></div>';
            html += '    <div class="col-md-4"><label class="form-label small">Bild-Alt</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.image_alt || '') + '" data-index="' + index + '" data-key="image_alt"></div>';
            html += '    <div class="col-12"><label class="form-label small">Kurzbeschreibung</label><textarea class="form-control form-control-sm" rows="3" data-index="' + index + '" data-key="summary">' + escapeHtml(card.summary || '') + '</textarea></div>';
            html += '    <div class="col-12 text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-card" data-index="' + index + '">Entfernen</button></div>';
            html += '  </div>';
            html += '</div>';
            container.insertAdjacentHTML('beforeend', html);
        });

        sync();
    }

    function renderHubLinks() {
        hubLinksContainer.innerHTML = '';
        hubLinksEmpty.classList.toggle('d-none', hubLinks.length !== 0);

        hubLinks.forEach(function (link, index) {
            var html = '';
            html += '<div class="border rounded p-3 mb-2">';
            html += '  <div class="row g-2">';
            html += '    <div class="col-md-5"><label class="form-label small">Link-Label</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(link.label || '') + '" data-hub-link-index="' + index + '" data-hub-link-key="label"></div>';
            html += '    <div class="col-md-5"><label class="form-label small">URL / Anchor</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(link.url || '') + '" data-hub-link-index="' + index + '" data-hub-link-key="url"></div>';
            html += '    <div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-outline-danger btn-sm w-100 remove-hub-link" data-hub-link-index="' + index + '">Entfernen</button></div>';
            html += '  </div>';
            html += '</div>';
            hubLinksContainer.insertAdjacentHTML('beforeend', html);
        });

        sync();
    }

    function renderHubSections() {
        hubSectionsContainer.innerHTML = '';
        hubSectionsEmpty.classList.toggle('d-none', hubSections.length !== 0);

        hubSections.forEach(function (section, index) {
            var html = '';
            html += '<div class="border rounded p-3 mb-2">';
            html += '  <div class="row g-2">';
            html += '    <div class="col-md-6"><label class="form-label small">Titel</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(section.title || '') + '" data-hub-section-index="' + index + '" data-hub-section-key="title"></div>';
            html += '    <div class="col-md-6"><label class="form-label small">CTA Label</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(section.actionLabel || '') + '" data-hub-section-index="' + index + '" data-hub-section-key="actionLabel"></div>';
            html += '    <div class="col-md-12"><label class="form-label small">Beschreibung</label><textarea class="form-control form-control-sm" rows="3" data-hub-section-index="' + index + '" data-hub-section-key="text">' + escapeHtml(section.text || '') + '</textarea></div>';
            html += '    <div class="col-md-8"><label class="form-label small">CTA URL</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(section.actionUrl || '') + '" data-hub-section-index="' + index + '" data-hub-section-key="actionUrl"></div>';
            html += '    <div class="col-md-4 d-flex align-items-end"><button type="button" class="btn btn-outline-danger btn-sm w-100 remove-hub-section" data-hub-section-index="' + index + '">Entfernen</button></div>';
            html += '  </div>';
            html += '</div>';
            hubSectionsContainer.insertAdjacentHTML('beforeend', html);
        });

        sync();
    }

    document.getElementById('addCard').addEventListener('click', function () {
        cards.push({ title: '', url: '', badge: '', meta: '', meta_left: '', meta_right: '', image_url: '', image_alt: '', summary: '' });
        render();
    });

    titleInput.addEventListener('input', updateSlugPreview);
    templateSelect.addEventListener('change', updateTemplatePresetInfo);
    applyTemplatePresetButton.addEventListener('click', function () {
        applyTemplatePreset();
    });
    copySlugPreviewButton.addEventListener('click', function () {
        copyHubSlug(currentPublicUrl());
    });
    saveAndOpenPublicButton.addEventListener('click', function () {
        openPublicAfterSaveInput.value = '1';
        form.submit();
    });
    form.addEventListener('submit', function () {
        if (document.activeElement !== saveAndOpenPublicButton) {
            openPublicAfterSaveInput.value = '0';
        }
    });

    document.getElementById('addHubLink').addEventListener('click', function () {
        hubLinks.push({ label: '', url: '' });
        renderHubLinks();
    });

    document.getElementById('addHubSection').addEventListener('click', function () {
        hubSections.push({ title: '', text: '', actionLabel: '', actionUrl: '' });
        renderHubSections();
    });

    container.addEventListener('input', function (event) {
        var target = event.target;
        var index = parseInt(target.dataset.index || '-1', 10);
        var key = target.dataset.key || '';
        if (index < 0 || !cards[index] || !key) {
            return;
        }
        cards[index][key] = target.value;
        sync();
    });

    container.addEventListener('click', function (event) {
        var button = event.target.closest('.remove-card');
        if (!button) {
            return;
        }
        var index = parseInt(button.dataset.index || '-1', 10);
        if (index < 0) {
            return;
        }
        cards.splice(index, 1);
        render();
    });

    hubLinksContainer.addEventListener('input', function (event) {
        var target = event.target;
        var index = parseInt(target.dataset.hubLinkIndex || '-1', 10);
        var key = target.dataset.hubLinkKey || '';
        if (index < 0 || !hubLinks[index] || !key) {
            return;
        }
        hubLinks[index][key] = target.value;
        sync();
    });

    hubLinksContainer.addEventListener('click', function (event) {
        var button = event.target.closest('.remove-hub-link');
        if (!button) {
            return;
        }
        var index = parseInt(button.dataset.hubLinkIndex || '-1', 10);
        if (index < 0) {
            return;
        }
        hubLinks.splice(index, 1);
        renderHubLinks();
    });

    hubSectionsContainer.addEventListener('input', function (event) {
        var target = event.target;
        var index = parseInt(target.dataset.hubSectionIndex || '-1', 10);
        var key = target.dataset.hubSectionKey || '';
        if (index < 0 || !hubSections[index] || !key) {
            return;
        }
        hubSections[index][key] = target.value;
        sync();
    });

    hubSectionsContainer.addEventListener('click', function (event) {
        var button = event.target.closest('.remove-hub-section');
        if (!button) {
            return;
        }
        var index = parseInt(button.dataset.hubSectionIndex || '-1', 10);
        if (index < 0) {
            return;
        }
        hubSections.splice(index, 1);
        renderHubSections();
    });

    document.querySelectorAll('[data-hub-tab]').forEach(function (tabButton) {
        tabButton.addEventListener('click', function () {
            var tab = this.dataset.hubTab || 'meta';
            document.querySelectorAll('[data-hub-tab]').forEach(function (button) {
                button.classList.toggle('active', button === tabButton);
            });
            document.querySelectorAll('[data-hub-panel]').forEach(function (panel) {
                panel.classList.toggle('d-none', panel.dataset.hubPanel !== tab);
            });
        });
    });

    render();
    renderHubLinks();
    renderHubSections();
    updateSlugPreview();
    updateTemplatePresetInfo();
})();

function copyHubSlug(url) {
    if (!navigator.clipboard || typeof navigator.clipboard.writeText !== 'function') {
        cmsAlert('Kopieren wird von diesem Browser leider nicht unterstützt.', 'warning');
        return;
    }

    navigator.clipboard.writeText(url).then(function () {
        cmsAlert('Public URL wurde in die Zwischenablage kopiert.', 'success');
    }).catch(function () {
        cmsAlert('Public URL konnte nicht kopiert werden.', 'danger');
    });
}
</script>
