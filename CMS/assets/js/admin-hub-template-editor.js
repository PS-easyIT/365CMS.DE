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
            return String(value || '');
        }

        function clearElement(element) {
            if (!element) {
                return;
            }

            while (element.firstChild) {
                element.removeChild(element.firstChild);
            }
        }

        function createElement(tag, className, text) {
            var element = document.createElement(tag);

            if (className) {
                element.className = className;
            }

            if (text !== undefined) {
                element.textContent = String(text);
            }

            return element;
        }

        function setDataAttributes(element, data) {
            Object.keys(data || {}).forEach(function (key) {
                var value = data[key];

                if (value === undefined || value === null) {
                    return;
                }

                element.dataset[key] = String(value);
            });

            return element;
        }

        function createFieldLabel(text) {
            return createElement('label', 'form-label small', text);
        }

        function createInput(value, data, options) {
            var input = createElement('input', 'form-control form-control-sm');
            var settings = options || {};

            input.type = settings.type || 'text';
            input.value = String(value || '');
            setDataAttributes(input, data);

            if (settings.placeholder) {
                input.placeholder = settings.placeholder;
            }

            return input;
        }

        function createTextarea(value, data, options) {
            var textarea = createElement('textarea', 'form-control form-control-sm');
            var settings = options || {};

            textarea.rows = String(settings.rows || 3);
            textarea.value = String(value || '');
            setDataAttributes(textarea, data);

            return textarea;
        }

        function createFieldColumn(columnClass, labelText, control) {
            var column = createElement('div', columnClass);

            column.appendChild(createFieldLabel(labelText));
            column.appendChild(control);

            return column;
        }

        function createRemoveButtonColumn(columnClass, buttonClass, label, data) {
            var column = createElement('div', columnClass);
            var button = createElement('button', buttonClass, label);

            button.type = 'button';
            setDataAttributes(button, data);
            column.appendChild(button);

            return column;
        }

        function appendMetaChip(container, icon, label, value) {
            var chip = createElement('span', 'hub-template-preview__meta-chip');

            chip.appendChild(createElement('span', 'hub-template-preview__meta-chip-icon', icon || '•'));
            chip.appendChild(createElement('span', 'hub-template-preview__meta-chip-label', (label || '') + ':'));
            chip.appendChild(createElement('span', 'hub-template-preview__meta-chip-value', value || ''));
            container.appendChild(chip);
        }

        function appendQuicklink(container, label) {
            container.appendChild(createElement('span', 'hub-template-preview__quicklink', label || ''));
        }

        function appendTocItem(container, label) {
            var item = createElement('div', 'hub-template-preview__toc-item');

            item.appendChild(createElement('strong', 'hub-template-preview__toc-label', label || ''));
            container.appendChild(item);
        }

        function createPreviewCard(card, index, layout, imagePosition, templateProfile, getValue) {
            var article = createElement('article', 'hub-template-preview__card hub-template-preview__card--' + layout + ' hub-template-preview__card--image-' + imagePosition);
            var media = createElement('div', 'hub-template-preview__media', card.image_url ? templateProfile.mediaLabel : templateProfile.cardPrefix + ' ' + (index + 1));
            var body = createElement('div', 'hub-template-preview__card-body');
            var meta = createElement('div', 'hub-template-preview__card-meta');

            meta.appendChild(createElement('span', 'hub-template-preview__meta-token', card.meta_left || getValue('template_card_meta_left_label', 'Meta links')));
            meta.appendChild(createElement('span', 'hub-template-preview__meta-token', card.meta_right || getValue('template_card_meta_right_label', 'Meta rechts')));

            body.appendChild(createElement('span', 'hub-template-preview__card-badge', card.badge || getValue('template_card_badge_label', 'Badge')));
            body.appendChild(createElement('h5', 'hub-template-preview__card-title', card.title || ('Beispiel ' + (index + 1))));
            body.appendChild(createElement('p', 'hub-template-preview__card-text', card.summary || 'Beispieltext für die Kachel-Vorschau im Template-Editor.'));
            body.appendChild(meta);
            body.appendChild(createElement('span', 'hub-template-preview__button', card.button_text || getValue('template_card_button_text_label', 'Button-Text')));

            article.appendChild(media);
            article.appendChild(body);

            return article;
        }

        function createPreviewSectionCard(section, index, templateProfile, detailItems) {
            var modifier = (templateProfile.sectionStyles || [])[index] || 'stacked';
            var sectionCard = createElement('div', 'hub-template-preview__section-card hub-template-preview__section-card--' + modifier);
            var head = createElement('div', 'hub-template-preview__section-head');
            var list = createElement('ul', 'hub-template-preview__section-list');

            head.appendChild(createElement('span', 'hub-template-preview__section-eyebrow', (templateProfile.sectionEyebrows || [])[index] || 'Section'));
            head.appendChild(createElement('span', 'hub-template-preview__section-icon', (templateProfile.sectionIcons || [])[index] || '◆'));

            detailItems.forEach(function (item) {
                list.appendChild(createElement('li', '', item));
            });

            sectionCard.appendChild(head);
            sectionCard.appendChild(createElement('h5', 'hub-template-preview__section-title', section.title || templateProfile.sectionTitle));
            sectionCard.appendChild(createElement('p', 'hub-template-preview__section-text', section.text || templateProfile.sectionText));
            sectionCard.appendChild(list);
            sectionCard.appendChild(createElement('div', 'hub-template-preview__section-note', (templateProfile.sectionNotes || [])[index] || ''));

            return sectionCard;
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

            clearElement(previewMeta);
            metaEntries.filter(function (item) { return item.value; }).forEach(function (item) {
                appendMetaChip(previewMeta, (templateProfile.metaIcons || {})[item.key] || '•', item.label || '', item.value || '');
            });

            clearElement(previewQuicklinks);
            clearElement(previewTocGrid);
            getPreviewLinks(defaults).forEach(function (item) {
                appendQuicklink(previewQuicklinks, item.label || '');
            });

            getCardTocItems(cards).forEach(function (card, index) {
                appendTocItem(previewTocGrid, card.title || ('Karte ' + (index + 1)));
            });

            previewToc.classList.toggle('d-none', !tocEnabled || previewTocGrid.children.length === 0);
            previewQuicklinks.classList.toggle('d-none', previewQuicklinks.children.length === 0);

            previewGrid.className = 'hub-template-preview__grid hub-template-preview__grid--' + columns;
            clearElement(previewGrid);
            cards.forEach(function (card, index) {
                previewGrid.appendChild(createPreviewCard(card, index, layout, imagePosition, templateProfile, getValue));
            });

            clearElement(previewSections);
            getPreviewSections(defaults).forEach(function (section, index) {
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
                previewSections.appendChild(createPreviewSectionCard(section, index, templateProfile, detailItems));
            });
        }

        function renderLinks() {
            clearElement(linksContainer);
            linksEmpty.classList.toggle('d-none', links.length !== 0);
            links.forEach(function (link, index) {
                var wrapper = createElement('div', 'border-bottom p-3');
                var row = createElement('div', 'row g-2');

                row.appendChild(createFieldColumn('col-md-5', 'Label', createInput(link.label || '', { linkIndex: index, linkKey: 'label' })));
                row.appendChild(createFieldColumn('col-md-5', 'URL / Anchor', createInput(link.url || '', { linkIndex: index, linkKey: 'url' })));
                row.appendChild(createRemoveButtonColumn('col-md-2 d-flex align-items-end', 'btn btn-outline-danger btn-sm w-100 remove-template-link', 'Entfernen', { linkIndex: index }));

                wrapper.appendChild(row);
                linksContainer.appendChild(wrapper);
            });
            sync();
        }

        function renderSections() {
            clearElement(sectionsContainer);
            sectionsEmpty.classList.toggle('d-none', sections.length !== 0);
            sections.forEach(function (section, index) {
                var wrapper = createElement('div', 'border-bottom p-3');
                var row = createElement('div', 'row g-2');

                row.appendChild(createFieldColumn('col-md-6', 'Titel', createInput(section.title || '', { sectionIndex: index, sectionKey: 'title' })));
                row.appendChild(createFieldColumn('col-md-6', 'CTA Label', createInput(section.actionLabel || '', { sectionIndex: index, sectionKey: 'actionLabel' })));
                row.appendChild(createFieldColumn('col-12', 'Beschreibung', createTextarea(section.text || '', { sectionIndex: index, sectionKey: 'text' }, { rows: 3 })));
                row.appendChild(createFieldColumn('col-md-8', 'CTA URL', createInput(section.actionUrl || '', { sectionIndex: index, sectionKey: 'actionUrl' })));
                row.appendChild(createRemoveButtonColumn('col-md-4 d-flex align-items-end', 'btn btn-outline-danger btn-sm w-100 remove-template-section', 'Entfernen', { sectionIndex: index }));

                wrapper.appendChild(row);
                sectionsContainer.appendChild(wrapper);
            });
            sync();
        }

        function renderStarterCards() {
            clearElement(starterCardsContainer);
            starterCardsEmpty.classList.toggle('d-none', starterCards.length !== 0);
            starterCards.forEach(function (card, index) {
                var wrapper = createElement('div', 'border-bottom p-3');
                var row = createElement('div', 'row g-2');

                row.appendChild(createFieldColumn('col-md-6', 'Titel', createInput(card.title || '', { cardIndex: index, cardKey: 'title' })));
                row.appendChild(createFieldColumn('col-md-6', 'Ziel-URL', createInput(card.url || '', { cardIndex: index, cardKey: 'url' })));
                row.appendChild(createFieldColumn('col-md-6', 'Badge', createInput(card.badge || '', { cardIndex: index, cardKey: 'badge' })));
                row.appendChild(createFieldColumn('col-md-6', 'Button-Text', createInput(card.button_text || '', { cardIndex: index, cardKey: 'button_text' })));
                row.appendChild(createFieldColumn('col-md-6', 'Meta links', createInput(card.meta_left || '', { cardIndex: index, cardKey: 'meta_left' })));
                row.appendChild(createFieldColumn('col-md-6', 'Meta rechts', createInput(card.meta_right || '', { cardIndex: index, cardKey: 'meta_right' })));
                row.appendChild(createFieldColumn('col-md-8', 'Bild-URL', createInput(card.image_url || '', { cardIndex: index, cardKey: 'image_url' })));
                row.appendChild(createFieldColumn('col-md-4', 'Bild-Alt', createInput(card.image_alt || '', { cardIndex: index, cardKey: 'image_alt' })));
                row.appendChild(createFieldColumn('col-md-12', 'Button-Link', createInput(card.button_link || '', { cardIndex: index, cardKey: 'button_link' })));
                row.appendChild(createFieldColumn('col-12', 'Kurzbeschreibung', createTextarea(card.summary || '', { cardIndex: index, cardKey: 'summary' }, { rows: 3 })));
                row.appendChild(createRemoveButtonColumn('col-12 text-end', 'btn btn-outline-danger btn-sm remove-starter-card', 'Entfernen', { cardIndex: index }));

                wrapper.appendChild(row);
                starterCardsContainer.appendChild(wrapper);
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
