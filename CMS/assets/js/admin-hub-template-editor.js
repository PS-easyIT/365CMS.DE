(function () {
    'use strict';

    function initHubTemplateEditor() {
        var payloadElement = document.getElementById('hubTemplateEditorPayload');
        var form = document.getElementById('hubTemplateForm');

        if (!payloadElement || !form) {
            return;
        }

        var payload = {};
        try {
            payload = JSON.parse(payloadElement.textContent || '{}');
        } catch (error) {
            console.error('Hub template payload could not be parsed.', error);
            return;
        }

        var isNewTemplate = payload.isNew === true;
        var baseTemplateDefaults = payload.baseTemplateDefaults && typeof payload.baseTemplateDefaults === 'object'
            ? payload.baseTemplateDefaults
            : {};
        var links = Array.isArray(payload.links) ? payload.links : [];
        var sections = Array.isArray(payload.sections) ? payload.sections : [];
        var starterCards = Array.isArray(payload.starterCards) ? payload.starterCards : [];
        var linksInput = document.getElementById('templateLinksJsonInput');
        var sectionsInput = document.getElementById('templateSectionsJsonInput');
        var starterCardsInput = document.getElementById('templateStarterCardsJsonInput');
        var linksContainer = document.getElementById('templateLinksContainer');
        var sectionsContainer = document.getElementById('templateSectionsContainer');
        var starterCardsContainer = document.getElementById('starterCardsContainer');
        var linksEmpty = document.getElementById('templateLinksEmpty');
        var sectionsEmpty = document.getElementById('templateSectionsEmpty');
        var starterCardsEmpty = document.getElementById('starterCardsEmpty');
        var preview = document.getElementById('hubTemplatePreview');
        var previewBadge = document.getElementById('templatePreviewBadge');
        var previewTitle = document.getElementById('templatePreviewTitle');
        var previewSummary = document.getElementById('templatePreviewSummary');
        var previewMeta = document.getElementById('templatePreviewMeta');
        var previewGrid = document.getElementById('templatePreviewGrid');
        var previewToc = document.getElementById('templatePreviewToc');
        var previewTocGrid = document.getElementById('templatePreviewTocGrid');
        var previewQuicklinks = document.getElementById('templatePreviewQuicklinks');
        var previewSections = document.getElementById('templatePreviewSections');
        var previewLayoutPill = document.getElementById('templatePreviewLayoutPill');
        var previewTypePill = document.getElementById('templatePreviewTypePill');
        var previewImagePill = document.getElementById('templatePreviewImagePill');
        var previewColumnsBadge = document.getElementById('templatePreviewColumnsBadge');
        var previewCardCount = document.getElementById('templatePreviewCardCount');
        var previewSectionTitle = document.getElementById('templatePreviewSectionTitle');
        var previewSectionText = document.getElementById('templatePreviewSectionText');
        var addTemplateLinkButton = document.getElementById('addTemplateLink');
        var addTemplateSectionButton = document.getElementById('addTemplateSection');
        var addStarterCardButton = document.getElementById('addStarterCard');
        var lastAppliedBaseTemplate = null;

        if (!linksInput || !sectionsInput || !starterCardsInput || !linksContainer || !sectionsContainer || !starterCardsContainer || !linksEmpty || !sectionsEmpty || !starterCardsEmpty || !preview || !previewBadge || !previewTitle || !previewSummary || !previewMeta || !previewGrid || !previewToc || !previewTocGrid || !previewQuicklinks || !previewSections || !previewLayoutPill || !previewTypePill || !previewImagePill || !previewColumnsBadge || !previewCardCount || !previewSectionTitle || !previewSectionText || !addTemplateLinkButton || !addTemplateSectionButton || !addStarterCardButton) {
            return;
        }

        function escapeHtml(value) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(value || ''));
            return div.innerHTML;
        }

        function cloneValue(value) {
            return JSON.parse(JSON.stringify(value || null));
        }

        function sync() {
            linksInput.value = JSON.stringify(links);
            sectionsInput.value = JSON.stringify(sections);
            starterCardsInput.value = JSON.stringify(starterCards);
            renderPreview();
        }

        function getValue(name, fallback) {
            var field = form.querySelector('[name="' + name + '"]');
            return field ? field.value : (fallback || '');
        }

        function getBaseTemplateDefaults(baseTemplate) {
            return baseTemplateDefaults[baseTemplate] || baseTemplateDefaults['general-it'] || {};
        }

        function syncSwitcherState(inputName, value) {
            form.querySelectorAll('[data-switcher="' + inputName + '"] .hub-template-switcher__btn').forEach(function (button) {
                button.classList.toggle('is-active', (button.getAttribute('data-value') || '') === String(value || ''));
            });
        }

        function setFieldValue(name, value, previousDefault) {
            var field = form.querySelector('[name="' + name + '"]');
            if (!field) {
                return;
            }

            var current = field.value || '';
            var nextValue = value || '';
            if (current === '' || current === previousDefault) {
                field.value = nextValue;
            }
        }

        function setCheckboxValue(name, value, previousDefault) {
            var field = form.querySelector('[name="' + name + '"]');
            if (!field) {
                return;
            }

            var current = field.checked;
            if (current === previousDefault) {
                field.checked = value === true;
            }
        }

        function bindSwitchers() {
            form.querySelectorAll('[data-switcher]').forEach(function (switcher) {
                switcher.addEventListener('click', function (event) {
                    var button = event.target.closest('.hub-template-switcher__btn');
                    if (!button) {
                        return;
                    }

                    var inputName = switcher.getAttribute('data-switcher');
                    var input = form.querySelector('[name="' + inputName + '"]');
                    if (!input) {
                        return;
                    }

                    input.value = button.getAttribute('data-value') || '';
                    switcher.querySelectorAll('.hub-template-switcher__btn').forEach(function (item) {
                        item.classList.toggle('is-active', item === button);
                    });
                    renderPreview();
                });
            });
        }

        function getStarterCardsForPreview() {
            var defaults = getBaseTemplateDefaults(getValue('base_template', 'general-it'));
            var cards = starterCards.slice(0, 3).filter(function (card) {
                return card && (card.title || card.summary || card.badge || card.button_text || card.image_url);
            });

            if (cards.length === 0) {
                cards = (defaults.starter_cards || []).slice(0, 3);
            }

            if (cards.length === 0) {
                cards = [
                    { title: 'Beispiel-Kachel 1', summary: 'Erste Vorschau-Kachel für das Template-Layout.', badge: 'Primary', meta_left: 'Meta links', meta_right: 'Meta rechts', button_text: 'Mehr', image_url: '' },
                    { title: 'Beispiel-Kachel 2', summary: 'Zweite Vorschau-Kachel für die nebeneinander-Darstellung.', badge: 'Secondary', meta_left: 'Owner', meta_right: 'Status', button_text: 'Öffnen', image_url: '' },
                    { title: 'Beispiel-Kachel 3', summary: 'Dritte Kachel zeigt die 3-Spalten-Variante.', badge: 'Optional', meta_left: 'Typ', meta_right: 'Live', button_text: 'Details', image_url: '' }
                ];
            }

            return cards.slice(0, 3);
        }

        function getCardTocItems(cards) {
            return (cards || []).filter(function (card) {
                return card && card.title;
            }).slice(0, 6);
        }

        function applyBaseTemplateDefaults(baseTemplate, forceCollections) {
            if (!isNewTemplate) {
                return;
            }

            var defaults = getBaseTemplateDefaults(baseTemplate);
            var previousDefaults = lastAppliedBaseTemplate ? getBaseTemplateDefaults(lastAppliedBaseTemplate) : {};
            var metaLabels = defaults.meta_labels || {};
            var meta = defaults.meta || {};
            var colors = defaults.colors || {};
            var cardSchemaDefaults = defaults.card_schema || {};
            var cardDesignDefaults = defaults.card_design || {};
            var navigationDefaults = defaults.navigation || {};
            var previousNavigationDefaults = previousDefaults.navigation || {};

            setFieldValue('template_summary', defaults.summary || '', previousDefaults.summary || '');
            setFieldValue('template_label_audience', metaLabels.audience || 'Zielgruppe', (previousDefaults.meta_labels || {}).audience || '');
            setFieldValue('template_label_owner', metaLabels.owner || 'Verantwortlich', (previousDefaults.meta_labels || {}).owner || '');
            setFieldValue('template_label_update_cycle', metaLabels.update_cycle || 'Update-Zyklus', (previousDefaults.meta_labels || {}).update_cycle || '');
            setFieldValue('template_label_focus', metaLabels.focus || 'Fokus', (previousDefaults.meta_labels || {}).focus || '');
            setFieldValue('template_label_kpi', metaLabels.kpi || 'KPI', (previousDefaults.meta_labels || {}).kpi || '');
            setFieldValue('template_meta_audience', meta.audience || '', (previousDefaults.meta || {}).audience || '');
            setFieldValue('template_meta_owner', meta.owner || '', (previousDefaults.meta || {}).owner || '');
            setFieldValue('template_meta_update_cycle', meta.update_cycle || '', (previousDefaults.meta || {}).update_cycle || '');
            setFieldValue('template_meta_focus', meta.focus || '', (previousDefaults.meta || {}).focus || '');
            setFieldValue('template_meta_kpi', meta.kpi || '', (previousDefaults.meta || {}).kpi || '');
            setCheckboxValue('template_toc_enabled', navigationDefaults.toc_enabled === true, previousNavigationDefaults.toc_enabled === true);
            setFieldValue('template_card_title_label', cardSchemaDefaults.title_label || 'Titel', (previousDefaults.card_schema || {}).title_label || '');
            setFieldValue('template_card_summary_label', cardSchemaDefaults.summary_label || 'Kurzbeschreibung', (previousDefaults.card_schema || {}).summary_label || '');
            setFieldValue('template_card_badge_label', cardSchemaDefaults.badge_label || 'Badge', (previousDefaults.card_schema || {}).badge_label || '');
            setFieldValue('template_card_meta_left_label', cardSchemaDefaults.meta_left_label || 'Meta links', (previousDefaults.card_schema || {}).meta_left_label || '');
            setFieldValue('template_card_meta_right_label', cardSchemaDefaults.meta_right_label || 'Meta rechts', (previousDefaults.card_schema || {}).meta_right_label || '');
            setFieldValue('template_card_image_label', cardSchemaDefaults.image_label || 'Bild-URL', (previousDefaults.card_schema || {}).image_label || '');
            setFieldValue('template_card_image_alt_label', cardSchemaDefaults.image_alt_label || 'Bild-Alt', (previousDefaults.card_schema || {}).image_alt_label || '');
            setFieldValue('template_card_button_text_label', cardSchemaDefaults.button_text_label || 'Button-Text', (previousDefaults.card_schema || {}).button_text_label || '');
            setFieldValue('template_card_button_link_label', cardSchemaDefaults.button_link_label || 'Button-Link', (previousDefaults.card_schema || {}).button_link_label || '');

            ['hero_start', 'hero_end', 'accent', 'surface', 'section_background', 'card_background', 'card_text', 'table_header_start', 'table_header_end'].forEach(function (key) {
                var fieldName = 'template_color_' + key;
                var field = form.querySelector('[name="' + fieldName + '"]');
                if (!field) {
                    return;
                }
                var current = field.value || '';
                var previous = (previousDefaults.colors || {})[key] || '';
                if (current === '' || current === previous) {
                    field.value = colors[key] || field.value;
                }
            });

            ['template_card_columns', 'hub_card_layout', 'hub_card_image_position', 'hub_card_image_fit', 'hub_card_image_ratio', 'hub_card_meta_layout'].forEach(function (fieldName) {
                var field = form.querySelector('[name="' + fieldName + '"]');
                if (!field) {
                    return;
                }

                var valueMap = {
                    template_card_columns: cardSchemaDefaults.columns,
                    hub_card_layout: cardDesignDefaults.layout,
                    hub_card_image_position: cardDesignDefaults.image_position,
                    hub_card_image_fit: cardDesignDefaults.image_fit,
                    hub_card_image_ratio: cardDesignDefaults.image_ratio,
                    hub_card_meta_layout: cardDesignDefaults.meta_layout
                };

                if (valueMap[fieldName] !== undefined && valueMap[fieldName] !== null && valueMap[fieldName] !== '') {
                    field.value = String(valueMap[fieldName]);
                    syncSwitcherState(fieldName, field.value);
                }
            });

            if (forceCollections || links.length === 0 || JSON.stringify(links) === JSON.stringify((previousDefaults.links || []))) {
                links = cloneValue(defaults.links || []);
            }
            if (forceCollections || sections.length === 0 || JSON.stringify(sections) === JSON.stringify((previousDefaults.sections || []))) {
                sections = cloneValue(defaults.sections || []);
            }
            if (forceCollections || starterCards.length === 0 || JSON.stringify(starterCards) === JSON.stringify((previousDefaults.starter_cards || []))) {
                starterCards = cloneValue(defaults.starter_cards || []);
            }

            lastAppliedBaseTemplate = baseTemplate;
            renderLinks();
            renderSections();
            renderStarterCards();
        }

        function getTemplatePreviewProfile(baseTemplate) {
            var profiles = {
                'microsoft-365': {
                    badge: 'Microsoft 365',
                    sectionTitle: 'Workspace & Adoption',
                    sectionText: 'Cloud, Collaboration und Governance wirken hier klarer, heller und produktnäher.',
                    cardPrefix: 'M365',
                    mediaLabel: 'Cloud',
                    metaIcons: { audience: '◈', owner: '☁', update_cycle: '↺', focus: '✦', kpi: '↑' },
                    linkIcons: ['T', 'S', 'C', 'G'],
                    sectionEyebrows: ['Workspace Layer', 'Guardrails'],
                    sectionIcons: ['☁', '✓'],
                    sectionStyles: ['spotlight', 'stacked'],
                    sectionNotes: ['Workloads & Journeys', 'Policies & Rollout']
                },
                'powershell-table': {
                    badge: 'PowerShell Table',
                    sectionTitle: 'Cmdlets & Runbooks',
                    sectionText: 'Dunkler, skriptlastiger Table-Look für Module, Runbooks und operative Automatisierung im phinit-Stil.',
                    cardPrefix: 'PS',
                    mediaLabel: 'Shell',
                    metaIcons: { audience: '⌁', owner: 'PS', update_cycle: '↻', focus: '▦', kpi: '●' },
                    linkIcons: ['C', 'R', 'M', 'A'],
                    sectionEyebrows: ['Cmdlets', 'Runbooks'],
                    sectionIcons: ['⌁', '>'],
                    sectionStyles: ['terminal', 'terminal'],
                    sectionNotes: ['$ modules=ready', '$ jobs=green']
                },
                'datenschutz': {
                    badge: 'Datenschutz',
                    sectionTitle: 'Schutz & Nachweise',
                    sectionText: 'Ruhige Vertrauensoptik mit klaren Hinweisen, Schutzcharakter und Compliance-Fokus.',
                    cardPrefix: 'DSGVO',
                    mediaLabel: 'Shield',
                    metaIcons: { audience: '§', owner: '⚖', update_cycle: '⏱', focus: '✓', kpi: '▣' },
                    linkIcons: ['§', 'V', 'T', 'R'],
                    sectionEyebrows: ['Nachweise', 'Pflichten'],
                    sectionIcons: ['✓', '⚖'],
                    sectionStyles: ['trust', 'checklist'],
                    sectionNotes: ['Dokumentation & Belege', 'Fristen & Maßnahmen']
                },
                'linux': {
                    badge: 'Linux',
                    sectionTitle: 'Platform & Ops',
                    sectionText: 'Terminalnah, dunkler und technischer — perfekt für Betrieb, Plattformen und Automatisierung.',
                    cardPrefix: 'Ops',
                    mediaLabel: 'CLI',
                    metaIcons: { audience: '⌘', owner: '#', update_cycle: '↻', focus: '▤', kpi: '●' },
                    linkIcons: ['#', '□', '>', '!'],
                    sectionEyebrows: ['Runtime', 'Runbooks'],
                    sectionIcons: ['⌘', '>'],
                    sectionStyles: ['terminal', 'terminal'],
                    sectionNotes: ['$ health=ok', '$ status=watch']
                },
                'compliance': {
                    badge: 'Compliance',
                    sectionTitle: 'Governance & Audit',
                    sectionText: 'Kontrollen, Richtlinien und Nachvollziehbarkeit stehen hier visuell stärker im Vordergrund.',
                    cardPrefix: 'Audit',
                    mediaLabel: 'Policy',
                    metaIcons: { audience: '◎', owner: '◆', update_cycle: '↺', focus: '◌', kpi: '▲' },
                    linkIcons: ['P', 'A', 'R', 'N'],
                    sectionEyebrows: ['Controls', 'Evidence'],
                    sectionIcons: ['◆', '▲'],
                    sectionStyles: ['spotlight', 'stacked'],
                    sectionNotes: ['Kontrollen & Rollen', 'Audit & Evidence']
                },
                'datenschutz-compliance-table': {
                    badge: 'Privacy & Compliance',
                    sectionTitle: 'Nachweise & Controls',
                    sectionText: 'Kombiniert Datenschutz und Compliance in einem phinit-konformen Table-Template mit klaren Reviews und Evidence-Fokus.',
                    cardPrefix: 'Evidence',
                    mediaLabel: 'Controls',
                    metaIcons: { audience: '§', owner: '◆', update_cycle: '↺', focus: '▦', kpi: '▲' },
                    linkIcons: ['N', 'C', 'F', 'A'],
                    sectionEyebrows: ['Nachweise', 'Reviews'],
                    sectionIcons: ['✓', '▲'],
                    sectionStyles: ['trust', 'stacked'],
                    sectionNotes: ['Nachweise & Kontrollen', 'Fristen & Audits']
                },
                'general-it': {
                    badge: 'General IT',
                    sectionTitle: 'IT-Architektur',
                    sectionText: 'Breit einsetzbares, neutrales Basislayout für Technologie-, Team- und Lösungsseiten.',
                    cardPrefix: 'IT',
                    mediaLabel: 'Preview',
                    metaIcons: { audience: '◎', owner: '◆', update_cycle: '↺', focus: '◌', kpi: '▲' },
                    linkIcons: ['S', 'P', 'C', 'B'],
                    sectionEyebrows: ['Architektur', 'Betrieb'],
                    sectionIcons: ['◆', '▲'],
                    sectionStyles: ['spotlight', 'stacked'],
                    sectionNotes: ['Zielbild & Standards', 'Services & Delivery']
                }
            };

            return profiles[baseTemplate] || profiles['general-it'];
        }

        function getPreviewLinks(defaults) {
            var candidates = links.length ? links : (defaults.links || []);
            return candidates.slice(0, 4).filter(function (item) {
                return item && item.label;
            });
        }

        function getPreviewSections(defaults) {
            var candidates = sections.length ? sections : (defaults.sections || []);
            return candidates.slice(0, 2).filter(function (item) {
                return item && (item.title || item.text);
            });
        }

        function renderPreview() {
            var columns = parseInt(getValue('template_card_columns', '2'), 10);
            var layout = getValue('hub_card_layout', 'standard');
            var imagePosition = getValue('hub_card_image_position', 'top');
            var baseTemplate = getValue('base_template', 'general-it');
            var defaults = getBaseTemplateDefaults(baseTemplate);
            var templateProfile = getTemplatePreviewProfile(baseTemplate);
            var tocEnabledField = form.querySelector('[name="template_toc_enabled"]');
            var tocEnabled = tocEnabledField ? tocEnabledField.checked === true : false;
            if (columns < 1 || columns > 3) {
                columns = 2;
            }

            var title = getValue('template_label', 'Template-Vorschau').trim() || 'Template-Vorschau';
            var summary = getValue('template_summary', '').trim() || defaults.summary || 'So wirken Hero, Meta-Felder und 1/2/3 Kachel-Layouts im Admin direkt beim Bearbeiten.';
            var cards = getStarterCardsForPreview();
            var metaEntries = [
                { key: 'audience', label: getValue('template_label_audience', (defaults.meta_labels || {}).audience || 'Zielgruppe'), value: getValue('template_meta_audience', (defaults.meta || {}).audience || '') || (defaults.meta || {}).audience || '' },
                { key: 'owner', label: getValue('template_label_owner', (defaults.meta_labels || {}).owner || 'Verantwortlich'), value: getValue('template_meta_owner', (defaults.meta || {}).owner || '') || (defaults.meta || {}).owner || '' },
                { key: 'update_cycle', label: getValue('template_label_update_cycle', (defaults.meta_labels || {}).update_cycle || 'Update-Zyklus'), value: getValue('template_meta_update_cycle', (defaults.meta || {}).update_cycle || '') || (defaults.meta || {}).update_cycle || '' },
                { key: 'focus', label: getValue('template_label_focus', (defaults.meta_labels || {}).focus || 'Fokus'), value: getValue('template_meta_focus', (defaults.meta || {}).focus || '') || (defaults.meta || {}).focus || '' },
                { key: 'kpi', label: getValue('template_label_kpi', (defaults.meta_labels || {}).kpi || 'KPI'), value: getValue('template_meta_kpi', (defaults.meta || {}).kpi || '') || (defaults.meta || {}).kpi || '' }
            ];

            preview.style.setProperty('--hub-preview-hero-start', getValue('template_color_hero_start', '#1e3a5f'));
            preview.style.setProperty('--hub-preview-hero-end', getValue('template_color_hero_end', '#0f2240'));
            preview.style.setProperty('--hub-preview-accent', getValue('template_color_accent', '#0d9488'));
            preview.style.setProperty('--hub-preview-surface', getValue('template_color_surface', '#ffffff'));
            preview.style.setProperty('--hub-preview-section', getValue('template_color_section_background', '#f1f5f9'));
            preview.style.setProperty('--hub-preview-card-bg', getValue('template_color_card_background', '#ffffff'));
            preview.style.setProperty('--hub-preview-card-text', getValue('template_color_card_text', '#1e293b'));
            preview.style.setProperty('--hub-preview-table-head-start', getValue('template_color_table_header_start', getValue('template_color_hero_start', '#1e3a5f')));
            preview.style.setProperty('--hub-preview-table-head-end', getValue('template_color_table_header_end', getValue('template_color_hero_end', '#0f2240')));
            preview.style.setProperty('--hub-preview-radius', Math.max(0, Math.min(48, parseInt(getValue('template_card_radius', '20'), 10) || 20)) + 'px');
            preview.className = 'hub-template-preview hub-template-preview--' + baseTemplate;

            previewBadge.textContent = templateProfile.badge;
            previewTitle.textContent = title;
            previewSummary.textContent = summary;
            previewLayoutPill.textContent = columns + ' nebeneinander';
            previewTypePill.textContent = layout.charAt(0).toUpperCase() + layout.slice(1);
            previewImagePill.textContent = 'Bild ' + (imagePosition === 'top' ? 'oben' : imagePosition === 'left' ? 'links' : 'rechts');
            previewColumnsBadge.textContent = columns + ' Kachel' + (columns === 1 ? '' : 'n');
            previewCardCount.textContent = cards.length + ' Karte' + (cards.length === 1 ? '' : 'n');
            previewSectionTitle.textContent = templateProfile.sectionTitle;
            previewSectionText.textContent = templateProfile.sectionText;

            previewMeta.innerHTML = '';
            metaEntries.filter(function (item) { return item.value; }).forEach(function (item) {
                var chip = document.createElement('span');
                chip.className = 'hub-template-preview__meta-chip';
                chip.innerHTML = ''
                    + '<span class="hub-template-preview__meta-chip-icon">' + escapeHtml((templateProfile.metaIcons || {})[item.key] || '•') + '</span>'
                    + '<span class="hub-template-preview__meta-chip-label">' + escapeHtml(item.label || '') + ':</span>'
                    + '<span class="hub-template-preview__meta-chip-value">' + escapeHtml(item.value || '') + '</span>';
                previewMeta.appendChild(chip);
            });

            previewQuicklinks.innerHTML = '';
            previewTocGrid.innerHTML = '';
            getPreviewLinks(defaults).forEach(function (item) {
                var link = document.createElement('span');
                link.className = 'hub-template-preview__quicklink';
                link.textContent = item.label || '';
                previewQuicklinks.appendChild(link);
            });

            getCardTocItems(cards).forEach(function (card, index) {
                var tocItem = document.createElement('div');
                tocItem.className = 'hub-template-preview__toc-item';
                tocItem.innerHTML = '<strong class="hub-template-preview__toc-label">' + escapeHtml(card.title || ('Karte ' + (index + 1))) + '</strong>';
                previewTocGrid.appendChild(tocItem);
            });

            previewToc.classList.toggle('d-none', !tocEnabled || previewTocGrid.children.length === 0);
            previewQuicklinks.classList.toggle('d-none', previewQuicklinks.children.length === 0);

            previewGrid.className = 'hub-template-preview__grid hub-template-preview__grid--' + columns;
            previewGrid.innerHTML = '';
            cards.forEach(function (card, index) {
                var article = document.createElement('article');
                article.className = 'hub-template-preview__card hub-template-preview__card--' + layout + ' hub-template-preview__card--image-' + imagePosition;
                article.innerHTML = ''
                    + '<div class="hub-template-preview__media">' + escapeHtml(card.image_url ? templateProfile.mediaLabel : templateProfile.cardPrefix + ' ' + (index + 1)) + '</div>'
                    + '<div class="hub-template-preview__card-body">'
                    + '  <span class="hub-template-preview__card-badge">' + escapeHtml(card.badge || getValue('template_card_badge_label', 'Badge')) + '</span>'
                    + '  <h5 class="hub-template-preview__card-title">' + escapeHtml(card.title || ('Beispiel ' + (index + 1))) + '</h5>'
                    + '  <p class="hub-template-preview__card-text">' + escapeHtml(card.summary || 'Beispieltext für die Kachel-Vorschau im Template-Editor.') + '</p>'
                    + '  <div class="hub-template-preview__card-meta">'
                    + '    <span class="hub-template-preview__meta-token">' + escapeHtml(card.meta_left || getValue('template_card_meta_left_label', 'Meta links')) + '</span>'
                    + '    <span class="hub-template-preview__meta-token">' + escapeHtml(card.meta_right || getValue('template_card_meta_right_label', 'Meta rechts')) + '</span>'
                    + '  </div>'
                    + '  <span class="hub-template-preview__button">' + escapeHtml(card.button_text || getValue('template_card_button_text_label', 'Button-Text')) + '</span>'
                    + '</div>';
                previewGrid.appendChild(article);
            });

            previewSections.innerHTML = '';
            getPreviewSections(defaults).forEach(function (section, index) {
                var sectionCard = document.createElement('div');
                var modifier = (templateProfile.sectionStyles || [])[index] || 'stacked';
                var detailItems = [];
                if (section.actionLabel) {
                    detailItems.push(section.actionLabel);
                }
                if (section.actionUrl) {
                    detailItems.push(section.actionUrl.replace('#', 'Anchor: '));
                }
                if (metaEntries[index + 3] && metaEntries[index + 3].value) {
                    detailItems.push(metaEntries[index + 3].label + ': ' + metaEntries[index + 3].value);
                }
                sectionCard.className = 'hub-template-preview__section-card hub-template-preview__section-card--' + modifier;
                sectionCard.innerHTML = ''
                    + '<div class="hub-template-preview__section-head">'
                    + '  <span class="hub-template-preview__section-eyebrow">' + escapeHtml((templateProfile.sectionEyebrows || [])[index] || 'Section') + '</span>'
                    + '  <span class="hub-template-preview__section-icon">' + escapeHtml((templateProfile.sectionIcons || [])[index] || '◆') + '</span>'
                    + '</div>'
                    + '<h5 class="hub-template-preview__section-title">' + escapeHtml(section.title || templateProfile.sectionTitle) + '</h5>'
                    + '<p class="hub-template-preview__section-text">' + escapeHtml(section.text || templateProfile.sectionText) + '</p>'
                    + '<ul class="hub-template-preview__section-list">' + detailItems.map(function (item) { return '<li>' + escapeHtml(item) + '</li>'; }).join('') + '</ul>'
                    + '<div class="hub-template-preview__section-note">' + escapeHtml((templateProfile.sectionNotes || [])[index] || '') + '</div>';
                previewSections.appendChild(sectionCard);
            });
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

        addTemplateLinkButton.addEventListener('click', function () {
            links.push({ label: '', url: '' });
            renderLinks();
        });

        addTemplateSectionButton.addEventListener('click', function () {
            sections.push({ title: '', text: '', actionLabel: '', actionUrl: '' });
            renderSections();
        });

        addStarterCardButton.addEventListener('click', function () {
            if (starterCards.length >= 3) {
                if (typeof cmsAlert === 'function') {
                    cmsAlert('warning', 'Maximal drei Starter-Kacheln pro Template sind möglich.');
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

        form.addEventListener('input', function () {
            renderPreview();
        });

        form.addEventListener('change', function (event) {
            if (isNewTemplate && event && event.target && event.target.name === 'base_template') {
                applyBaseTemplateDefaults(event.target.value || 'general-it', true);
            }
            renderPreview();
        });

        bindSwitchers();
        if (isNewTemplate) {
            applyBaseTemplateDefaults(getValue('base_template', 'general-it'), true);
        }
        renderLinks();
        renderSections();
        renderStarterCards();
        renderPreview();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHubTemplateEditor, { once: true });
    } else {
        initHubTemplateEditor();
    }
})();
