(function () {
    'use strict';

    function parseJsonInput(id, fallback) {
        var input = document.getElementById(id);
        if (!input || !input.value) {
            return fallback;
        }

        try {
            return JSON.parse(input.value);
        } catch (_error) {
            return fallback;
        }
    }

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(value || ''));
        return div.innerHTML;
    }

    function showAlert(type, message) {
        if (typeof window.cmsAlert === 'function') {
            window.cmsAlert(message, type);
            return;
        }

        console[type === 'danger' ? 'error' : 'log'](message);
    }

    function absoluteUrlFromPath(path) {
        var normalizedPath = String(path || '').trim();

        if (normalizedPath === '') {
            return '';
        }

        try {
            return new URL(normalizedPath, window.location.origin + '/').toString();
        } catch (_error) {
            return normalizedPath;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('hubSiteForm');
        var siteConfig = parseJsonInput('hubSiteConfigInput', {});
        var templateProfiles = parseJsonInput('hubTemplateProfilesInput', {});
        var cards = parseJsonInput('cardsJsonInput', []);
        var featureCards = parseJsonInput('featureCardsJsonInput', []);
        var container;
        var emptyState;
        var input;
        var featureInput;
        var titleInput;
        var templateSelect;
        var slugPreviewInput;
        var openPublicAfterSaveInput;
        var saveAndOpenPublicButton;
        var copySlugPreviewButton;
        var cardSchemaHint;
        var languageToggleButtons;
        var initialTemplateValue;
        var activeLanguage = 'de';
        var summaryEditors = new Map();

        if (!form) {
            return;
        }

        container = document.getElementById('cardsContainer');
        emptyState = document.getElementById('cardsEmpty');
        input = document.getElementById('cardsJsonInput');
        featureInput = document.getElementById('featureCardsJsonInput');
        titleInput = form.querySelector('input[name="site_name"]');
        templateSelect = document.getElementById('hubTemplateSelect');
        slugPreviewInput = document.getElementById('hubSlugPreviewInput');
        openPublicAfterSaveInput = document.getElementById('openPublicAfterSaveInput');
        saveAndOpenPublicButton = document.getElementById('saveAndOpenPublicButton');
        copySlugPreviewButton = document.getElementById('copySlugPreviewButton');
        cardSchemaHint = document.getElementById('cardSchemaHint');
        languageToggleButtons = document.querySelectorAll('[data-hub-lang-toggle]');
        initialTemplateValue = templateSelect ? templateSelect.value : 'general-it';
        cards = Array.isArray(cards) ? cards : [];
        featureCards = Array.isArray(featureCards) ? featureCards : [];

        function templateSupportsFeatureCards(templateKey) {
            return ['general-it', 'microsoft-365', 'datenschutz', 'compliance'].indexOf(templateKey) !== -1;
        }

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
            return {
                is_feature: false,
                feature_spacing_top: 0,
                title: '',
                title_en: '',
                url: '#',
                badge: '',
                badge_en: '',
                meta: '',
                meta_en: '',
                meta_left: '',
                meta_left_en: '',
                meta_right: '',
                meta_right_en: '',
                image_url: '',
                image_alt: '',
                image_alt_en: '',
                summary: '',
                summary_en: '',
                button_text: '',
                button_text_en: '',
                button_link: ''
            };
        }

        function defaultFeatureCard() {
            return {
                insert_after: 0,
                title: '',
                title_en: '',
                text: '',
                text_en: '',
                image_url: '',
                image_alt: '',
                image_alt_en: ''
            };
        }

        function normalizeCard(card) {
            var normalized = Object.assign(defaultCard(), card || {});
            var featureSpacingTop = parseInt(normalized.feature_spacing_top, 10);

            normalized.is_feature = normalized.is_feature === true
                || normalized.is_feature === 1
                || normalized.is_feature === '1'
                || normalized.is_feature === 'true'
                || normalized.isFeature === true
                || normalized.display_variant === 'feature';
            normalized.feature_spacing_top = Number.isFinite(featureSpacingTop)
                ? Math.max(0, Math.min(240, featureSpacingTop))
                : 0;

            return normalized;
        }

        function normalizeFeatureCard(card) {
            var normalized = Object.assign(defaultFeatureCard(), card || {});
            var insertAfter = parseInt(normalized.insert_after, 10);

            normalized.insert_after = Number.isFinite(insertAfter) && insertAfter > 0 ? insertAfter : 0;

            return normalized;
        }

        function featureCardToHubCard(card) {
            var normalized = normalizeFeatureCard(card);

            return normalizeCard({
                is_feature: true,
                title: normalized.title,
                title_en: normalized.title_en,
                url: '#',
                badge: '',
                badge_en: '',
                meta: '',
                meta_en: '',
                meta_left: '',
                meta_left_en: '',
                meta_right: '',
                meta_right_en: '',
                image_url: normalized.image_url,
                image_alt: normalized.image_alt,
                image_alt_en: normalized.image_alt_en,
                summary: normalized.text,
                summary_en: normalized.text_en,
                button_text: '',
                button_text_en: '',
                button_link: ''
            });
        }

        function migrateLegacyFeatureCardsIntoCards() {
            var legacyInterval = parseInt(siteConfig.legacyFeatureCardInterval || '0', 10);
            var legacyInsertMap = {};
            var migratedCards = [];
            var regularCardCount = 0;

            if (!Array.isArray(featureCards) || featureCards.length === 0) {
                return;
            }

            featureCards.map(function (card) {
                return normalizeFeatureCard(card);
            }).forEach(function (card, index) {
                var insertAfter = card.insert_after;

                if (insertAfter <= 0 && Number.isFinite(legacyInterval) && legacyInterval > 0) {
                    insertAfter = (index + 1) * legacyInterval;
                }

                if (insertAfter <= 0) {
                    insertAfter = Number.MAX_SAFE_INTEGER;
                }

                legacyInsertMap[insertAfter] = legacyInsertMap[insertAfter] || [];
                legacyInsertMap[insertAfter].push(featureCardToHubCard(card));
            });

            cards.map(function (card) {
                return normalizeCard(card);
            }).forEach(function (card) {
                migratedCards.push(card);

                if (!card.is_feature) {
                    regularCardCount += 1;
                    if (legacyInsertMap[regularCardCount]) {
                        legacyInsertMap[regularCardCount].forEach(function (legacyCard) {
                            migratedCards.push(legacyCard);
                        });
                        delete legacyInsertMap[regularCardCount];
                    }
                }
            });

            Object.keys(legacyInsertMap)
                .map(function (key) {
                    return parseInt(key, 10);
                })
                .sort(function (left, right) {
                    return left - right;
                })
                .forEach(function (key) {
                    legacyInsertMap[key].forEach(function (legacyCard) {
                        migratedCards.push(legacyCard);
                    });
                });

            cards = migratedCards;
            featureCards = [];
            siteConfig.legacyFeatureCardInterval = 0;
            sync();
        }

        function sync() {
            input.value = JSON.stringify(cards);
            if (featureInput) {
                featureInput.value = JSON.stringify(featureCards);
            }
        }

        function destroySummaryEditors() {
            summaryEditors.forEach(function (editor, key) {
                try {
                    if (editor && typeof editor.getContents === 'function') {
                        var textarea = document.getElementById(key);
                        if (textarea) {
                            textarea.value = editor.getContents();
                        }
                    }

                    if (editor && typeof editor.destroy === 'function') {
                        editor.destroy();
                    }
                } catch (_error) {
                    // Editor cleanup should never block the form UI.
                }
            });

            summaryEditors.clear();
        }

        function initSummaryEditors() {
            if (!form || typeof window.SUNEDITOR === 'undefined') {
                return;
            }

            form.querySelectorAll('textarea[data-editor="hub-richtext"], textarea[data-editor="hub-summary"]').forEach(function (textarea) {
                var languagePane = textarea.closest('[data-lang-pane]');

                if (languagePane && languagePane.classList.contains('d-none')) {
                    return;
                }

                createRichTextEditor(textarea);
            });
        }

        function createRichTextEditor(textarea) {
            var id = textarea.id;
            var index = parseInt(textarea.dataset.index || '-1', 10);
            var key = textarea.dataset.key || '';
            var source = textarea.dataset.source || 'cards';
            var isFormField = source === 'form';
            var targetCollection = source === 'feature' ? featureCards : cards;

            if (!id || summaryEditors.has(id)) {
                return;
            }

            if (!isFormField && (index < 0 || !targetCollection[index] || !key)) {
                return;
            }

            try {
                var editor = window.SUNEDITOR.create(textarea, {
                    lang: window.SUNEDITOR_LANG && window.SUNEDITOR_LANG.de ? window.SUNEDITOR_LANG.de : 'en',
                    width: '100%',
                    height: isFormField ? '220' : '180',
                    minHeight: isFormField ? '180px' : '140px',
                    resizingBar: true,
                    resizeEnable: true,
                    charCounter: false,
                    buttonList: [
                        ['undo', 'redo'],
                        ['formatBlock'],
                        ['bold', 'italic', 'underline'],
                        ['list', 'outdent', 'indent', 'align'],
                        ['link'],
                        ['removeFormat']
                    ],
                    formats: ['p', 'div', 'blockquote'],
                    defaultTag: 'p',
                    showPathLabel: false,
                    imageFileInput: false,
                    videoFileInput: false,
                    audioFileInput: false,
                    font: ['Arial', 'Segoe UI', 'Verdana', 'Tahoma'],
                    fontSize: [12, 14, 16, 18],
                    pasteTagsWhitelist: 'p|div|blockquote|ul|ol|li|a|b|strong|i|em|u|s|br|span',
                    attributesWhitelist: {
                        all: 'style|class',
                        a: 'href|target|rel|title',
                        span: 'style|class',
                        p: 'style|class',
                        div: 'style|class'
                    }
                });

                editor.setContents(textarea.value || '');
                editor.onChange = function (contents) {
                    if (!isFormField) {
                        targetCollection[index][key] = contents;
                        sync();
                    }

                    textarea.value = contents;
                };

                summaryEditors.set(id, editor);
            } catch (_error) {
                textarea.style.display = 'block';
            }
        }

        function slugify(value) {
            return String(value || '')
                .trim()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        function currentPublicUrl() {
            var slugValue = String(slugPreviewInput.value || '').trim();
            var publicPath = slugValue === '' ? '/' : (slugValue.charAt(0) === '/' ? slugValue : '/' + slugValue);

            return absoluteUrlFromPath(publicPath);
        }
        function prepareFormSubmission(resetOpenPublicAfterSave) {
            summaryEditors.forEach(function (editor, key) {
                var textarea = document.getElementById(key);
                if (editor && textarea && typeof editor.getContents === 'function') {
                    var source = textarea.dataset.source || 'cards';
                    var index = parseInt(textarea.dataset.index || '-1', 10);
                    textarea.value = editor.getContents();

                    if (source !== 'form') {
                        var collection = source === 'feature' ? featureCards : cards;
                        if (index >= 0 && collection[index] && textarea.dataset.key) {
                            collection[index][textarea.dataset.key] = textarea.value;
                        }
                    }
                }
            });
            sync();

            if (resetOpenPublicAfterSave && document.activeElement !== saveAndOpenPublicButton) {
                openPublicAfterSaveInput.value = '0';
            }
        }


        function copyHubUrl(url) {
            if (!navigator.clipboard || typeof navigator.clipboard.writeText !== 'function') {
                showAlert('warning', 'Kopieren wird von diesem Browser leider nicht unterstützt.');
                return;
            }

            navigator.clipboard.writeText(url).then(function () {
                showAlert('success', 'Public URL wurde in die Zwischenablage kopiert.');
            }).catch(function () {
                showAlert('danger', 'Public URL konnte nicht kopiert werden.');
            });
        }

        function updateSlugPreview() {
            var storedSlug = String(siteConfig.storedSlug || '');
            var nextSlug = storedSlug || slugify(titleInput ? titleInput.value : '') || 'hub-site';

            slugPreviewInput.value = '/' + nextSlug;
            if (copySlugPreviewButton) {
                copySlugPreviewButton.disabled = nextSlug === '';
            }
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
            var templateKey = templateSelect ? templateSelect.value : initialTemplateValue;
            var featureSupported = templateSupportsFeatureCards(templateKey);
            var titleKey = activeLanguage === 'en' ? 'title_en' : 'title';
            var badgeKey = activeLanguage === 'en' ? 'badge_en' : 'badge';
            var metaKey = activeLanguage === 'en' ? 'meta_en' : 'meta';
            var metaLeftKey = activeLanguage === 'en' ? 'meta_left_en' : 'meta_left';
            var metaRightKey = activeLanguage === 'en' ? 'meta_right_en' : 'meta_right';
            var imageAltKey = activeLanguage === 'en' ? 'image_alt_en' : 'image_alt';
            var summaryKey = activeLanguage === 'en' ? 'summary_en' : 'summary';
            var buttonTextKey = activeLanguage === 'en' ? 'button_text_en' : 'button_text';
            var featureTextKey = activeLanguage === 'en' ? 'text_en' : 'text';
            var suffix = activeLanguage === 'en' ? ' (EN)' : '';

            destroySummaryEditors();
            container.innerHTML = '';
            emptyState.classList.toggle('d-none', cards.length !== 0);

            if (cardSchemaHint) {
                cardSchemaHint.textContent = 'Template-Vorgabe: ' + schema.columns + ' Kachel' + (schema.columns === 1 ? '' : 'n') + ' pro Reihe. Aktive Sprachansicht: ' + (activeLanguage === 'en' ? 'English' : 'Deutsch') + '. ' + (featureSupported ? 'Per Schalter kannst du jede Kachel als Feature-Kachel in Vollbreite markieren.' : 'Dieses Template rendert markierte Feature-Kacheln aktuell wie normale Kacheln.');
            }

            cards.forEach(function (card, index) {
                var html = '';
                card = normalizeCard(card);
                cards[index] = card;
                html += '<div class="border-bottom p-3">';
                html += '  <div class="row g-2">';
                html += '    <div class="col-12">';
                html += '      <label class="form-check form-switch mb-0">';
                html += '        <input class="form-check-input" type="checkbox" data-index="' + index + '" data-key="is_feature"' + (card.is_feature ? ' checked' : '') + (featureSupported ? '' : ' disabled') + '>';
                html += '        <span class="form-check-label">Als Feature-Kachel in voller Breite darstellen</span>';
                html += '      </label>';
                html += '      <div class="form-hint">' + escapeHtml(featureSupported ? 'Die Kachel bleibt an ihrer Position und wird im Frontend als breite Feature-Kachel gerendert.' : 'Dieses Template unterstützt aktuell keine separate Feature-Darstellung; die Markierung wird deshalb deaktiviert.') + '</div>';
                html += '    </div>';
                html += '    <div class="col-md-4"><label class="form-label small">Abstand nach oben (px)</label><input type="number" class="form-control form-control-sm" min="0" max="240" step="4" value="' + escapeHtml(String(card.feature_spacing_top || 0)) + '" data-index="' + index + '" data-key="feature_spacing_top"' + (card.is_feature ? '' : ' disabled') + '><div class="form-hint">Zusätzlicher Abstand vor dieser Feature-Kachel.</div></div>';
                html += '    <div class="col-md-6"><label class="form-label small">' + escapeHtml(schema.title_label + suffix) + '</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card[titleKey] || '') + '" data-index="' + index + '" data-key="' + titleKey + '"></div>';
                html += '    <div class="col-md-8"><label class="form-label small">URL</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.url || '') + '" data-index="' + index + '" data-key="url"></div>';
                html += '    <div class="col-md-6"><label class="form-label small">' + escapeHtml(schema.badge_label + suffix) + '</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card[badgeKey] || '') + '" data-index="' + index + '" data-key="' + badgeKey + '"></div>';
                html += '    <div class="col-md-6"><label class="form-label small">Legacy Meta' + escapeHtml(suffix) + '</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card[metaKey] || '') + '" data-index="' + index + '" data-key="' + metaKey + '"></div>';
                html += '    <div class="col-md-6"><label class="form-label small">' + escapeHtml(schema.meta_left_label + suffix) + '</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card[metaLeftKey] || '') + '" data-index="' + index + '" data-key="' + metaLeftKey + '"></div>';
                html += '    <div class="col-md-6"><label class="form-label small">' + escapeHtml(schema.meta_right_label + suffix) + '</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card[metaRightKey] || '') + '" data-index="' + index + '" data-key="' + metaRightKey + '"></div>';
                html += '    <div class="col-md-6"><label class="form-label small">' + escapeHtml(schema.button_text_label + suffix) + '</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card[buttonTextKey] || '') + '" data-index="' + index + '" data-key="' + buttonTextKey + '"></div>';
                html += '    <div class="col-md-6"><label class="form-label small">' + escapeHtml(schema.button_link_label) + '</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.button_link || '') + '" data-index="' + index + '" data-key="button_link"></div>';
                html += '    <div class="col-md-8"><label class="form-label small">' + escapeHtml(schema.image_label) + '</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.image_url || '') + '" data-index="' + index + '" data-key="image_url" placeholder="https://… oder /uploads/..."></div>';
                html += '    <div class="col-md-4"><label class="form-label small">' + escapeHtml(schema.image_alt_label + suffix) + '</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card[imageAltKey] || '') + '" data-index="' + index + '" data-key="' + imageAltKey + '"></div>';
                html += '    <div class="col-12"><label class="form-label small">' + escapeHtml(schema.summary_label + suffix) + '</label><textarea id="hub-card-summary-' + activeLanguage + '-' + index + '" class="form-control form-control-sm" rows="6" data-editor="hub-richtext" data-source="cards" data-index="' + index + '" data-key="' + summaryKey + '">' + escapeHtml(card[summaryKey] || '') + '</textarea></div>';
                html += '    <div class="col-12 text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-card" data-index="' + index + '">Entfernen</button></div>';
                html += '  </div>';
                html += '</div>';
                container.insertAdjacentHTML('beforeend', html);
            });

            sync();
            initSummaryEditors();
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

        if (titleInput) {
            titleInput.addEventListener('input', updateSlugPreview);
        }

        languageToggleButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                setActiveLanguage(button.getAttribute('data-hub-lang-toggle') || 'de');
            });
        });

        if (copySlugPreviewButton) {
            copySlugPreviewButton.addEventListener('click', function () {
                copyHubUrl(currentPublicUrl());
            });
        }

        document.querySelectorAll('[data-copy-hub-path]').forEach(function (button) {
            button.addEventListener('click', function () {
                copyHubUrl(absoluteUrlFromPath(button.getAttribute('data-copy-hub-path') || '') || currentPublicUrl());
            });
        });

        if (saveAndOpenPublicButton) {
            saveAndOpenPublicButton.addEventListener('click', function () {
                openPublicAfterSaveInput.value = '1';
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                    return;
                }

                prepareFormSubmission(false);
                form.submit();
            });
        }

        form.addEventListener('submit', function () {
            prepareFormSubmission(true);
        });

        container.addEventListener('input', function (event) {
            var target = event.target;
            var index = parseInt(target.dataset.index || '-1', 10);
            var key = target.dataset.key || '';
            var source = target.dataset.source || 'cards';
            var collection = source === 'feature' ? featureCards : cards;
            if (index < 0 || !collection[index] || !key) {
                return;
            }

            collection[index][key] = key === 'is_feature'
                ? Boolean(target.checked)
                : target.value;
            sync();
        });

        container.addEventListener('click', function (event) {
            var button = event.target.closest('.remove-card');
            var index;
            if (!button) {
                return;
            }
            index = parseInt(button.dataset.index || '-1', 10);
            if (index < 0) {
                return;
            }
            cards.splice(index, 1);
            render();
        });

        cards = cards.map(function (card) {
            return normalizeCard(card);
        });
        featureCards = featureCards.map(function (card) {
            return normalizeFeatureCard(card);
        });
        migrateLegacyFeatureCardsIntoCards();
        applyStarterCardsIfNeeded(Boolean(siteConfig.isNew));
        setActiveLanguage('de');
        updateSlugPreview();
    });
})();
