<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$site = $data['site'] ?? null;
$isNew = (bool)($data['isNew'] ?? true);
$defaults = $data['defaults'] ?? [];
$templateOptions = $data['templateOptions'] ?? [];
$templateProfiles = $data['templateProfiles'] ?? [];
$settings = $site['settings'] ?? $defaults;
$cards = $site['cards'] ?? [];
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
                        <span class="badge bg-indigo-lt">EN: /<?php echo htmlspecialchars((string)($settings['hub_slug'] ?? '')); ?>/en</span>
                        <?php if (!empty($settings['hub_slug'])): ?>
                            <button type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    onclick="copyHubSlug('<?php echo htmlspecialchars(SITE_URL . '/' . ltrim((string)$settings['hub_slug'], '/'), ENT_QUOTES); ?>')">
                                Slug kopieren
                            </button>
                            <a href="<?php echo htmlspecialchars(SITE_URL . '/' . ltrim((string)$settings['hub_slug'], '/')); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">
                                DE öffnen
                            </a>
                            <a href="<?php echo htmlspecialchars(rtrim(SITE_URL . '/' . ltrim((string)$settings['hub_slug'], '/'), '/') . '/en'); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">
                                EN öffnen
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
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item"><a class="nav-link active" href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites">Content</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites?action=templates">Templates</a></li>
        </ul>

        <form method="post" action="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites" id="hubSiteForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="save">
            <?php if (!$isNew): ?>
                <input type="hidden" name="id" value="<?php echo (int)($site['id'] ?? 0); ?>">
            <?php endif; ?>
            <input type="hidden" name="open_public_after_save" id="openPublicAfterSaveInput" value="0">
            <input type="hidden" name="cards_json" id="cardsJsonInput" value="<?php echo htmlspecialchars(json_encode($cards, JSON_UNESCAPED_UNICODE)); ?>">

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <strong>Inhaltssprache</strong>
                                    <div class="text-secondary small">Deutsch bleibt Standard, English wird unter <code>/slug/en</code> ausgespielt.</div>
                                </div>
                                <div class="btn-group" role="tablist" aria-label="Hub-Sprachansicht">
                                    <button type="button" class="btn btn-primary" data-hub-lang-toggle="de" aria-pressed="true">Deutsch</button>
                                    <button type="button" class="btn btn-outline-primary" data-hub-lang-toggle="en" aria-pressed="false">English</button>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                    <label class="form-label">Template-Profil</label>
                                    <select class="form-select" name="hub_template" id="hubTemplateSelect">
                                        <?php foreach ($templateOptions as $value => $label): ?>
                                            <option value="<?php echo htmlspecialchars((string)$value); ?>" <?php echo (($settings['hub_template'] ?? '') === $value) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)$label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-hint">Layouts, Header-Links und Designvorgaben bearbeitest du zentral im Tab <strong>Templates</strong>. Beim Neuanlegen werden die Starter-Kacheln des gewählten Templates automatisch übernommen.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">CTA URL</label>
                                    <input type="text" class="form-control" name="hub_cta_url" value="<?php echo htmlspecialchars((string)($settings['hub_cta_url'] ?? '')); ?>" placeholder="/themen">
                                </div>
                                <div class="col-12" data-lang-pane="de">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Themen-Badge</label>
                                            <input type="text" class="form-control" name="hub_badge" value="<?php echo htmlspecialchars((string)($settings['hub_badge'] ?? '')); ?>" placeholder="z. B. Microsoft 365">
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
                                            <label class="form-label">Meta: Zielgruppe</label>
                                            <input type="text" class="form-control" name="hub_meta_audience" value="<?php echo htmlspecialchars((string)($settings['hub_meta_audience'] ?? '')); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Meta: Verantwortlich</label>
                                            <input type="text" class="form-control" name="hub_meta_owner" value="<?php echo htmlspecialchars((string)($settings['hub_meta_owner'] ?? '')); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Meta: Update-Zyklus</label>
                                            <input type="text" class="form-control" name="hub_meta_update_cycle" value="<?php echo htmlspecialchars((string)($settings['hub_meta_update_cycle'] ?? '')); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Meta: Fokus</label>
                                            <input type="text" class="form-control" name="hub_meta_focus" value="<?php echo htmlspecialchars((string)($settings['hub_meta_focus'] ?? '')); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Meta: KPI</label>
                                            <input type="text" class="form-control" name="hub_meta_kpi" value="<?php echo htmlspecialchars((string)($settings['hub_meta_kpi'] ?? '')); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 d-none" data-lang-pane="en">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="alert alert-info mb-0">Die englische Hub-Site wird unter <code>/<?php echo htmlspecialchars((string)($settings['hub_slug'] ?? 'hub-site')); ?>/en</code> ausgeliefert. URL und Kartenstruktur bleiben identisch.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Topic badge</label>
                                            <input type="text" class="form-control" name="hub_badge_en" value="<?php echo htmlspecialchars((string)($settings['hub_badge_en'] ?? '')); ?>" placeholder="e.g. Microsoft 365">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Hero title (EN)</label>
                                            <input type="text" class="form-control" name="hub_hero_title_en" value="<?php echo htmlspecialchars((string)($settings['hub_hero_title_en'] ?? '')); ?>" placeholder="Optional English headline">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Hero text (EN)</label>
                                            <textarea class="form-control" name="hub_hero_text_en" rows="4" placeholder="Short English intro for this hub."><?php echo htmlspecialchars((string)($settings['hub_hero_text_en'] ?? '')); ?></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">CTA label (EN)</label>
                                            <input type="text" class="form-control" name="hub_cta_label_en" value="<?php echo htmlspecialchars((string)($settings['hub_cta_label_en'] ?? '')); ?>" placeholder="e.g. Explore all topics">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Meta: Audience</label>
                                            <input type="text" class="form-control" name="hub_meta_audience_en" value="<?php echo htmlspecialchars((string)($settings['hub_meta_audience_en'] ?? '')); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Meta: Owner</label>
                                            <input type="text" class="form-control" name="hub_meta_owner_en" value="<?php echo htmlspecialchars((string)($settings['hub_meta_owner_en'] ?? '')); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Meta: Update cycle</label>
                                            <input type="text" class="form-control" name="hub_meta_update_cycle_en" value="<?php echo htmlspecialchars((string)($settings['hub_meta_update_cycle_en'] ?? '')); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Meta: Focus</label>
                                            <input type="text" class="form-control" name="hub_meta_focus_en" value="<?php echo htmlspecialchars((string)($settings['hub_meta_focus_en'] ?? '')); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Meta: KPI</label>
                                            <input type="text" class="form-control" name="hub_meta_kpi_en" value="<?php echo htmlspecialchars((string)($settings['hub_meta_kpi_en'] ?? '')); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">Hub-Kacheln</h3>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addCard">Kachel hinzufügen</button>
                        </div>
                        <div class="card-body border-bottom bg-body-secondary">
                            <div class="small text-secondary" id="cardSchemaHint">Die Felder orientieren sich am gewählten Template-Profil.</div>
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
                                    DE im neuen Tab öffnen
                                </a>
                                <a href="<?php echo htmlspecialchars(rtrim(SITE_URL . '/' . ltrim((string)$settings['hub_slug'], '/'), '/') . '/en'); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary w-100 mb-2">
                                    EN im neuen Tab öffnen
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
    var templateProfiles = <?php echo json_encode($templateProfiles, JSON_UNESCAPED_UNICODE); ?>;
    var container = document.getElementById('cardsContainer');
    var emptyState = document.getElementById('cardsEmpty');
    var input = document.getElementById('cardsJsonInput');
    var form = document.getElementById('hubSiteForm');
    var titleInput = form.querySelector('input[name="site_name"]');
    var templateSelect = document.getElementById('hubTemplateSelect');
    var slugPreviewInput = document.getElementById('hubSlugPreviewInput');
    var openPublicAfterSaveInput = document.getElementById('openPublicAfterSaveInput');
    var saveAndOpenPublicButton = document.getElementById('saveAndOpenPublicButton');
    var copySlugPreviewButton = document.getElementById('copySlugPreviewButton');
    var cardSchemaHint = document.getElementById('cardSchemaHint');
    var initialTemplateValue = templateSelect ? templateSelect.value : 'general-it';
    var activeLanguage = 'de';
    var languageToggleButtons = document.querySelectorAll('[data-hub-lang-toggle]');

    function getTemplateProfile() {
        var key = templateSelect ? templateSelect.value : initialTemplateValue;
        return templateProfiles[key] || templateProfiles['general-it'] || {};
    }

    function getCardSchema() {
        var profile = getTemplateProfile();
        var schema = profile.card_schema || {};

        return {
            columns: Math.min(3, Math.max(1, parseInt(schema.columns || 2, 10) || 2)),
            title_label: schema.title_label || 'Titel',
            summary_label: schema.summary_label || 'Kurzbeschreibung',
            badge_label: schema.badge_label || 'Badge',
            meta_left_label: schema.meta_left_label || 'Meta links',
            meta_right_label: schema.meta_right_label || 'Meta rechts',
            image_label: schema.image_label || 'Bild-URL',
            image_alt_label: schema.image_alt_label || 'Bild-Alt',
            button_text_label: schema.button_text_label || 'Button-Text',
            button_link_label: schema.button_link_label || 'Button-Link'
        };
    }

    function defaultCard() {
        return { title: '', title_en: '', url: '#', badge: '', badge_en: '', meta: '', meta_en: '', meta_left: '', meta_left_en: '', meta_right: '', meta_right_en: '', image_url: '', image_alt: '', image_alt_en: '', summary: '', summary_en: '', button_text: '', button_text_en: '', button_link: '' };
    }

    function normalizeCard(card) {
        return Object.assign(defaultCard(), card || {});
    }

    function applyStarterCardsIfNeeded(force) {
        var profile = getTemplateProfile();
        var starters = Array.isArray(profile.starter_cards) ? profile.starter_cards : [];
        if ((!force && cards.length > 0) || starters.length === 0) {
            return;
        }

        cards = starters.map(function (card) {
            return normalizeCard(card);
        });
        sync();
        render();
    }

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

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(value || ''));
        return div.innerHTML;
    }

    function sync() {
        input.value = JSON.stringify(cards);
    }

    function setActiveLanguage(lang) {
        activeLanguage = lang === 'en' ? 'en' : 'de';

        document.querySelectorAll('[data-lang-pane]').forEach(function (pane) {
            var isMatch = pane.getAttribute('data-lang-pane') === activeLanguage;
            pane.classList.toggle('d-none', !isMatch);
        });

        languageToggleButtons.forEach(function (button) {
            var isActive = button.getAttribute('data-hub-lang-toggle') === activeLanguage;
            button.classList.toggle('btn-primary', isActive);
            button.classList.toggle('btn-outline-primary', !isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        render();
    }

    function render() {
        var schema = getCardSchema();
        var titleKey = activeLanguage === 'en' ? 'title_en' : 'title';
        var badgeKey = activeLanguage === 'en' ? 'badge_en' : 'badge';
        var metaKey = activeLanguage === 'en' ? 'meta_en' : 'meta';
        var metaLeftKey = activeLanguage === 'en' ? 'meta_left_en' : 'meta_left';
        var metaRightKey = activeLanguage === 'en' ? 'meta_right_en' : 'meta_right';
        var imageAltKey = activeLanguage === 'en' ? 'image_alt_en' : 'image_alt';
        var summaryKey = activeLanguage === 'en' ? 'summary_en' : 'summary';
        var buttonTextKey = activeLanguage === 'en' ? 'button_text_en' : 'button_text';
        var suffix = activeLanguage === 'en' ? ' (EN)' : '';
        container.innerHTML = '';
        emptyState.classList.toggle('d-none', cards.length !== 0);
        if (cardSchemaHint) {
            cardSchemaHint.textContent = 'Template-Vorgabe: ' + schema.columns + ' Kachel' + (schema.columns === 1 ? '' : 'n') + ' pro Reihe. Aktive Sprachansicht: ' + (activeLanguage === 'en' ? 'English' : 'Deutsch') + '.';
        }

        cards.forEach(function (card, index) {
            card = normalizeCard(card);
            cards[index] = card;
            var html = '';
            html += '<div class="border-bottom p-3">';
            html += '  <div class="row g-2">';
            html += '    <div class="col-md-6"><label class="form-label small">' + escapeHtml(schema.title_label + suffix) + '</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card[titleKey] || '') + '" data-index="' + index + '" data-key="' + titleKey + '"></div>';
            html += '    <div class="col-md-6"><label class="form-label small">URL</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.url || '') + '" data-index="' + index + '" data-key="url"></div>';
            html += '    <div class="col-md-6"><label class="form-label small">' + escapeHtml(schema.badge_label + suffix) + '</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card[badgeKey] || '') + '" data-index="' + index + '" data-key="' + badgeKey + '"></div>';
            html += '    <div class="col-md-6"><label class="form-label small">Legacy Meta' + escapeHtml(suffix) + '</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card[metaKey] || '') + '" data-index="' + index + '" data-key="' + metaKey + '"></div>';
            html += '    <div class="col-md-6"><label class="form-label small">' + escapeHtml(schema.meta_left_label + suffix) + '</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card[metaLeftKey] || '') + '" data-index="' + index + '" data-key="' + metaLeftKey + '"></div>';
            html += '    <div class="col-md-6"><label class="form-label small">' + escapeHtml(schema.meta_right_label + suffix) + '</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card[metaRightKey] || '') + '" data-index="' + index + '" data-key="' + metaRightKey + '"></div>';
            html += '    <div class="col-md-6"><label class="form-label small">' + escapeHtml(schema.button_text_label + suffix) + '</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card[buttonTextKey] || '') + '" data-index="' + index + '" data-key="' + buttonTextKey + '"></div>';
            html += '    <div class="col-md-6"><label class="form-label small">' + escapeHtml(schema.button_link_label) + '</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.button_link || '') + '" data-index="' + index + '" data-key="button_link"></div>';
            html += '    <div class="col-md-8"><label class="form-label small">' + escapeHtml(schema.image_label) + '</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.image_url || '') + '" data-index="' + index + '" data-key="image_url" placeholder="https://… oder /uploads/..."></div>';
            html += '    <div class="col-md-4"><label class="form-label small">' + escapeHtml(schema.image_alt_label + suffix) + '</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card[imageAltKey] || '') + '" data-index="' + index + '" data-key="' + imageAltKey + '"></div>';
            html += '    <div class="col-12"><label class="form-label small">' + escapeHtml(schema.summary_label + suffix) + '</label><textarea class="form-control form-control-sm" rows="3" data-index="' + index + '" data-key="' + summaryKey + '">' + escapeHtml(card[summaryKey] || '') + '</textarea></div>';
            html += '    <div class="col-12 text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-card" data-index="' + index + '">Entfernen</button></div>';
            html += '  </div>';
            html += '</div>';
            container.insertAdjacentHTML('beforeend', html);
        });

        sync();
    }

    document.getElementById('addCard').addEventListener('click', function () {
        cards.push(defaultCard());
        render();
    });

    if (templateSelect) {
        templateSelect.addEventListener('change', function () {
            applyStarterCardsIfNeeded(cards.length === 0);
            render();
        });
    }

    titleInput.addEventListener('input', updateSlugPreview);
    languageToggleButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            setActiveLanguage(button.getAttribute('data-hub-lang-toggle') || 'de');
        });
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

    cards = cards.map(function (card) { return normalizeCard(card); });
    applyStarterCardsIfNeeded(<?php echo $isNew ? 'true' : 'false'; ?>);
    setActiveLanguage('de');
    updateSlugPreview();
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
