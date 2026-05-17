(function () {
    'use strict';

    function parseJsonInput(id, fallback) {
        var input = getElement(id);

        if (!input || !input.value) {
            return fallback;
        }

        try {
            return JSON.parse(input.value);
        } catch (_error) {
            return fallback;
        }
    }

    function getElement(id) {
        return id ? document.getElementById(id) : null;
    }

    function queryElements(selector) {
        if (!selector) {
            return [];
        }

        try {
            return Array.prototype.slice.call(document.querySelectorAll(selector));
        } catch (_error) {
            return [];
        }
    }

    function clearElement(element) {
        if (!element) {
            return;
        }

        while (element.firstChild) {
            element.removeChild(element.firstChild);
        }
    }

    function extractTextFromHtml(value) {
        var parser;
        var doc;

        if (typeof DOMParser === 'function') {
            try {
                parser = new DOMParser();
                doc = parser.parseFromString(String(value || ''), 'text/html');
                return String((doc && doc.body && doc.body.textContent) || '');
            } catch (_error) {
                // Fall back to a plain string below.
            }
        }

        return String(value || '');
    }

    function buildPreviewUrl(base, slug, template, placeholderSlug) {
        var sanitizedTemplate = String(template || '');
        var sanitizedBase = String(base || '').replace(/\/+$/, '');
        var sanitizedSlug = String(slug || '').trim().replace(/^\/+/, '');
        var fallbackSlug = String(placeholderSlug || 'beitrag').trim().replace(/^\/+/, '');

        if (sanitizedTemplate !== '' && sanitizedTemplate.indexOf('{slug}') !== -1) {
            return sanitizedTemplate.replace(/\{slug\}/g, sanitizedSlug !== '' ? sanitizedSlug : fallbackSlug);
        }

        return sanitizedSlug !== '' ? sanitizedBase + '/' + sanitizedSlug : sanitizedBase + '/';
    }

    function parseEditorContentLength(rawValue) {
        var normalized = String(rawValue || '').trim();
        var parsed;
        var blockText = '';

        if (normalized === '') {
            return 0;
        }

        try {
            parsed = JSON.parse(normalized);
        } catch (_error) {
            return extractTextFromHtml(normalized).trim().length;
        }

        if (!parsed || !Array.isArray(parsed.blocks)) {
            return extractTextFromHtml(normalized).trim().length;
        }

        parsed.blocks.forEach(function (block) {
            var data = block && block.data && typeof block.data === 'object' ? block.data : {};
            ['text', 'caption', 'title', 'message', 'code', 'html', 'content'].forEach(function (key) {
                if (typeof data[key] === 'string') {
                    blockText += ' ' + extractTextFromHtml(data[key]);
                }
            });
        });

        return blockText.trim().length;
    }

    function initLanguageTabCompleteness(editorConfig) {
        var editors = editorConfig && Array.isArray(editorConfig.editors) ? editorConfig.editors : [];
        var tabs = queryElements('[data-lang-tab]');
        var lengths = { de: 0, en: 0 };

        editors.forEach(function (definition) {
            var input = getElement(definition && definition.inputId ? definition.inputId : '');
            var key = definition && definition.key === 'en' ? 'en' : 'de';
            if (input) {
                lengths[key] = parseEditorContentLength(input.value);
            }
        });

        tabs.forEach(function (tab) {
            var locale = tab.getAttribute('data-lang-tab') === 'en' ? 'en' : 'de';
            var dot = tab.querySelector('.cms-lang-status-dot');
            if (!dot) {
                dot = document.createElement('span');
                dot.className = 'cms-lang-status-dot';
                dot.setAttribute('aria-hidden', 'true');
                tab.appendChild(dot);
            }
            dot.classList.toggle('is-complete', lengths[locale] > 0);
        });
    }

    function initUnsavedChangesGuard(uiConfig) {
        var form = getElement(uiConfig.formId);
        var title = queryElements(uiConfig.titleSelector || '.page-header .page-title')[0] || null;
        var backLink = getElement(uiConfig.backLinkId);
        var indicator;
        var modal;
        var modalCancel;
        var modalDiscard;
        var hasUnsavedChanges = false;
        var skipGuard = false;

        if (!form || !title) {
            return;
        }

        indicator = document.createElement('span');
        indicator.className = 'cms-unsaved-indicator';
        indicator.innerHTML = '<span class="cms-unsaved-indicator__dot" aria-hidden="true"></span><span>Ungespeicherte Änderungen</span>';
        title.insertBefore(indicator, title.firstChild);

        modal = document.createElement('div');
        modal.className = 'cms-unsaved-modal';
        modal.innerHTML = ''
            + '<div class="cms-unsaved-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="cmsUnsavedTitle">'
            + '<h3 id="cmsUnsavedTitle" class="cms-unsaved-modal__title">Änderungen verwerfen?</h3>'
            + '<div class="cms-unsaved-modal__actions">'
            + '<button type="button" class="btn btn-outline-secondary btn-sm" data-role="cancel">Abbrechen</button>'
            + '<button type="button" class="btn btn-danger btn-sm" data-role="discard">Verwerfen</button>'
            + '</div>'
            + '</div>';
        document.body.appendChild(modal);

        modalCancel = modal.querySelector('[data-role="cancel"]');
        modalDiscard = modal.querySelector('[data-role="discard"]');

        function renderIndicator() {
            indicator.classList.toggle('is-visible', hasUnsavedChanges);
        }

        function markUnsaved() {
            if (skipGuard) {
                return;
            }
            hasUnsavedChanges = true;
            renderIndicator();
        }

        function clearUnsaved() {
            hasUnsavedChanges = false;
            renderIndicator();
        }

        form.addEventListener('input', markUnsaved);
        form.addEventListener('change', markUnsaved);
        form.addEventListener('submit', function () {
            skipGuard = true;
            clearUnsaved();
        });

        if (backLink) {
            backLink.addEventListener('click', function (event) {
                if (!hasUnsavedChanges) {
                    return;
                }

                event.preventDefault();
                modal.classList.add('is-open');

                if (modalDiscard) {
                    modalDiscard.onclick = function () {
                        skipGuard = true;
                        window.location.href = backLink.href;
                    };
                }
            });
        }

        if (modalCancel) {
            modalCancel.addEventListener('click', function () {
                modal.classList.remove('is-open');
            });
        }

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                modal.classList.remove('is-open');
            }
        });

        window.addEventListener('beforeunload', function (event) {
            if (!hasUnsavedChanges || skipGuard) {
                return;
            }
            event.preventDefault();
            event.returnValue = '';
        });
    }

    function enforceAccordionDefaults() {
        queryElements('.cms-collapsible-card').forEach(function (card) {
            if (card.classList.contains('cms-publication-card')) {
                card.open = true;
                return;
            }
            card.open = false;
        });
    }

    function initUi(config) {
        var form = getElement(config.formId);
        var removeButton = getElement(config.removeButtonId);
        var imageInput = getElement(config.imageInputId);
        var tempPathInput = getElement(config.tempPathInputId);
        var previewContainer = getElement(config.previewContainerId);
        var emptyState = getElement(config.emptyStateId);
        var slugInput = getElement(config.slugInputId);
        var previewUrl = getElement(config.previewUrlId);
        var statusSelect = getElement(config.statusSelectId);
        var publishDateInput = getElement(config.publishDateId);
        var publishTimeInput = getElement(config.publishTimeId);
        var publishWarning = getElement(config.publishWarningId);
        var statusBadge = getElement(config.statusBadgeId);
        var categorySelect = getElement(config.categorySelectId);
        var categoryLabel = getElement(config.categoryLabelId);
        var toggleButtons = queryElements(config.languageToggleSelector);
        var languagePanes = queryElements(config.languagePaneSelector);
        var statusMap = config.statusMap || {};
        var countBindings = Array.isArray(config.countBindings) ? config.countBindings : [];

        if (!form) {
            return;
        }

        function resolvePublishDate() {
            var dateValue = publishDateInput ? String(publishDateInput.value || '').trim() : '';
            var timeValue = publishTimeInput ? String(publishTimeInput.value || '').trim() : '';

            if (dateValue === '') {
                return null;
            }

            return new Date(dateValue + 'T' + (timeValue !== '' ? timeValue : '00:00'));
        }

        function isScheduledPublication() {
            var publishAt;

            if (!statusSelect || statusSelect.value !== 'published') {
                return false;
            }

            publishAt = resolvePublishDate();
            return publishAt instanceof Date && !Number.isNaN(publishAt.getTime()) && publishAt.getTime() > Date.now();
        }

        function switchLanguage(lang) {
            languagePanes.forEach(function (pane) {
                pane.classList.toggle('d-none', pane.getAttribute(config.languagePaneAttribute || 'data-lang-pane') !== lang);
            });

            toggleButtons.forEach(function (button) {
                var isActive = button.getAttribute(config.languageAttribute || 'data-lang-toggle') === lang;
                button.classList.toggle('btn-primary', isActive);
                button.classList.toggle('btn-outline-primary', !isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        }

        function updateUi() {
            countBindings.forEach(function (binding) {
                var source = getElement(binding.sourceId);
                var target = getElement(binding.targetId);
                if (source && target) {
                    target.textContent = String(source.value.length);
                }
            });

            if (previewUrl && slugInput) {
                previewUrl.textContent = buildPreviewUrl(config.previewBaseUrl || '', slugInput.value, config.previewUrlTemplate || '', config.previewPlaceholderSlug || 'beitrag');
            }

            if (statusSelect && statusBadge) {
                var resolvedStatusKey = isScheduledPublication() ? 'scheduled' : statusSelect.value;
                var currentStatus = statusMap[resolvedStatusKey] || statusMap.draft || null;
                if (currentStatus) {
                    statusBadge.className = currentStatus.className || statusBadge.className;
                    statusBadge.textContent = currentStatus.label || statusBadge.textContent;
                }
            }

            if (publishWarning) {
                if (isScheduledPublication()) {
                    publishWarning.textContent = 'Dieser Beitrag ist geplant und wird automatisch zum gewählten Termin veröffentlicht.';
                    publishWarning.classList.remove('d-none', 'alert-warning');
                    publishWarning.classList.add('alert-info');
                } else {
                    publishWarning.textContent = '';
                    publishWarning.classList.add('d-none');
                    publishWarning.classList.remove('alert-info');
                    publishWarning.classList.add('alert-warning');
                }
            }

            if (categorySelect && categoryLabel) {
                categoryLabel.textContent = categorySelect.options[categorySelect.selectedIndex]
                    ? categorySelect.options[categorySelect.selectedIndex].text
                    : 'Keine Kategorie';
            }
        }

        if (imageInput && imageInput.value && removeButton) {
            removeButton.classList.remove('d-none');
        }

        if (removeButton) {
            removeButton.addEventListener('click', function () {
                if (imageInput) {
                    imageInput.value = '';
                }
                if (tempPathInput) {
                    tempPathInput.value = '';
                }
                if (previewContainer) {
                    clearElement(previewContainer);
                    previewContainer.classList.add('d-none');
                }
                if (emptyState) {
                    emptyState.classList.remove('d-none');
                }
                removeButton.classList.add('d-none');
            });
        }

        countBindings.forEach(function (binding) {
            var source = getElement(binding.sourceId);
            if (!source) {
                return;
            }

            source.addEventListener('input', updateUi);
            source.addEventListener('change', updateUi);
        });

        if (statusSelect) {
            statusSelect.addEventListener('input', updateUi);
            statusSelect.addEventListener('change', updateUi);
        }

        if (publishDateInput) {
            publishDateInput.addEventListener('input', updateUi);
            publishDateInput.addEventListener('change', updateUi);
        }

        if (publishTimeInput) {
            publishTimeInput.addEventListener('input', updateUi);
            publishTimeInput.addEventListener('change', updateUi);
        }

        if (categorySelect) {
            categorySelect.addEventListener('input', updateUi);
            categorySelect.addEventListener('change', updateUi);
        }

        toggleButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                switchLanguage(button.getAttribute(config.languageAttribute || 'data-lang-toggle') || config.defaultLanguage || 'de');
            });
        });

        updateUi();
        switchLanguage(config.defaultLanguage || 'de');
    }

    function initSeo(config) {
        if (!config || !window.cmsSeoEditor || typeof window.cmsSeoEditor.init !== 'function') {
            return;
        }

        window.cmsSeoEditor.init(config);
    }

    function initEditorJs(config) {
        var form;
        var editors = {};
        var editorDefinitions = {};
        var submitLocked = false;
        var nativeSubmitPending = false;
        var pendingSubmitter = null;
        var translationPreviewActive = false;
        var translationPreviewSuppressClear = false;
        var pendingLazyBindings = {};
        var suppressInitialCopyForKeys = {};
        var editorMutationState = {};
        var editorUiState = {};

        if (!config) {
            return;
        }

        form = getElement(config.formId);
        if (!form) {
            return;
        }

        function emitChangeEvents(element) {
            if (!element) {
                return;
            }

            element.dispatchEvent(new Event('input', { bubbles: true }));
            element.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function hasEditorFactory() {
            return typeof window.createCmsEditor === 'function';
        }

        function hasEditorCore() {
            return typeof window.EditorJS === 'function';
        }

        function createPlaintextFallbackData(value) {
            var normalizedValue = String(value || '').trim();

            if (normalizedValue === '') {
                return { blocks: [] };
            }

            return {
                time: Date.now(),
                version: 'cms-editor-fallback',
                blocks: [
                    {
                        type: 'paragraph',
                        data: { text: normalizedValue.replace(/\n/g, '<br>') }
                    }
                ]
            };
        }

        function extractFallbackTextareaValue(data) {
            var blocks = data && Array.isArray(data.blocks) ? data.blocks : [];
            var textParts = [];

            blocks.forEach(function (block) {
                var blockData = block && typeof block.data === 'object' ? block.data : {};
                var value = '';

                ['text', 'message', 'title', 'code', 'caption', 'content', 'html'].some(function (key) {
                    if (typeof blockData[key] !== 'string' || blockData[key].trim() === '') {
                        return false;
                    }

                    value = extractTextFromHtml(blockData[key]);
                    return true;
                });

                if (value.trim() !== '') {
                    textParts.push(value.trim());
                }
            });

            return textParts.join('\n\n');
        }

        function renderEditorUnavailableFallback(definition, input, reason) {
            var holder = getElement(definition && definition.holderId ? definition.holderId : '');
            var warning;
            var textarea;
            var normalizedData;
            var fallbackText;

            if (!holder || !input) {
                return;
            }

            if (holder.dataset.cmsEditorFallbackBound === '1') {
                return;
            }

            holder.dataset.cmsEditorFallbackBound = '1';
            clearElement(holder);

            warning = document.createElement('div');
            warning.className = 'alert alert-warning cms-editor-fallback-warning';
            warning.textContent = 'EditorJS konnte nicht geladen werden. Fallback-Textfeld aktiv (' + String(reason || 'unknown') + ').';
            holder.appendChild(warning);

            textarea = document.createElement('textarea');
            textarea.className = 'form-control';
            textarea.rows = 14;
            textarea.setAttribute('aria-label', 'EditorJS Fallback Text');

            normalizedData = normalizeEditorData(safeParseEditorInput(input));
            fallbackText = extractFallbackTextareaValue(normalizedData);
            if (fallbackText === '') {
                fallbackText = extractTextFromHtml(String(input.value || ''));
            }
            textarea.value = fallbackText;

            textarea.addEventListener('input', function () {
                input.value = JSON.stringify(createPlaintextFallbackData(textarea.value));
                emitChangeEvents(input);
            });

            holder.appendChild(textarea);
        }

        function waitForNextPaint() {
            return new Promise(function (resolve) {
                if (typeof window.requestAnimationFrame === 'function') {
                    window.requestAnimationFrame(function () {
                        window.requestAnimationFrame(resolve);
                    });
                    return;
                }

                window.setTimeout(resolve, 0);
            });
        }

        function resolveBlockLabel(blockElement) {
            if (!blockElement || !blockElement.querySelector) {
                return 'Block';
            }

            if (blockElement.querySelector('.ce-header')) { return 'H2'; }
            if (blockElement.querySelector('.ce-paragraph')) { return 'Text'; }
            if (blockElement.querySelector('.cdx-list')) { return 'Liste'; }
            if (blockElement.querySelector('.cdx-checklist')) { return 'Checklist'; }
            if (blockElement.querySelector('.image-tool')) { return 'Bild'; }
            if (blockElement.querySelector('.editorjs-link')) { return 'Link'; }
            if (blockElement.querySelector('.editorjs-embed')) { return 'Embed'; }
            if (blockElement.querySelector('.editorjs-details')) { return 'Details'; }
            if (blockElement.querySelector('.ce-delimiter')) { return 'Trenner'; }
            if (blockElement.querySelector('.editorjs-spacer-tool')) { return 'Abstand'; }
            if (blockElement.querySelector('.cdx-warning')) { return 'Callout'; }
            if (blockElement.querySelector('.ce-code')) { return 'Code'; }
            if (blockElement.querySelector('.tc-wrap')) { return 'Tabelle'; }
            if (blockElement.querySelector('.cdx-quote')) { return 'Zitat'; }
            if (blockElement.querySelector('.gg-box')) { return 'Gallery'; }
            return 'Block';
        }

        function getBlockIndex(holder, blockElement) {
            var blocks = holder ? holder.querySelectorAll('.ce-block') : [];
            return Array.prototype.indexOf.call(blocks, blockElement);
        }

        function insertBlockAt(editorInstance, index, type, data) {
            var blockType = type || 'paragraph';
            var blockData = data || {};

            if (!editorInstance || !editorInstance.blocks) {
                return;
            }

            try {
                editorInstance.blocks.insert(blockType, blockData, undefined, index, true);
            } catch (_error) {
                editorInstance.blocks.insert(blockType, blockData);
            }
        }

        function setupImageHoverOverlay(holder, editorInstance) {
            queryElements('#' + holder.id + ' .ce-block .image-tool').forEach(function (toolElement) {
                var block = toolElement.closest('.ce-block');
                var overlay = toolElement.querySelector('.cms-image-hover-overlay');
                var captionField = toolElement.querySelector('input.cdx-input, textarea.cdx-input');
                var removeButton;
                var altInput;

                if (!block) {
                    return;
                }

                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.className = 'cms-image-hover-overlay';
                    overlay.innerHTML = ''
                        + '<input type="text" class="form-control form-control-sm" placeholder="Alt-Text eingeben...">'
                        + '<button type="button" class="btn btn-sm btn-danger" aria-label="Bild entfernen"><i class="ti ti-x"></i></button>';
                    toolElement.appendChild(overlay);
                }

                removeButton = overlay.querySelector('button');
                altInput = overlay.querySelector('input');

                if (captionField && altInput && altInput.value !== captionField.value) {
                    altInput.value = captionField.value || '';
                }

                if (captionField && altInput && altInput.dataset.cmsBound !== '1') {
                    altInput.dataset.cmsBound = '1';
                    altInput.addEventListener('input', function () {
                        captionField.value = altInput.value;
                        captionField.dispatchEvent(new Event('input', { bubbles: true }));
                        captionField.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                }

                if (removeButton && removeButton.dataset.cmsBound !== '1') {
                    removeButton.dataset.cmsBound = '1';
                    removeButton.addEventListener('click', function (event) {
                        var index = getBlockIndex(holder, block);
                        event.preventDefault();
                        if (index < 0 || !editorInstance || !editorInstance.blocks || typeof editorInstance.blocks.delete !== 'function') {
                            return;
                        }
                        editorInstance.blocks.delete(index);
                    });
                }
            });
        }

        function renderEditorBlockUi(definition, editorEntry) {
            var holder = getElement(definition.holderId);
            var redactor;
            var blocks;

            if (!holder || !editorEntry || !editorEntry.instance) {
                return;
            }

            redactor = holder.querySelector('.codex-editor__redactor') || holder;
            blocks = holder.querySelectorAll('.ce-block');

            queryElements('#' + holder.id + ' .cms-editor-insert-between').forEach(function (button) {
                button.remove();
            });

            blocks.forEach(function (block, index) {
                var nextBlock = blocks[index + 1];
                var insertButton;

                block.classList.add('cms-editor-block-shell');
                block.setAttribute('data-cms-block-label', resolveBlockLabel(block));

                if (!nextBlock) {
                    return;
                }

                insertButton = document.createElement('button');
                insertButton.type = 'button';
                insertButton.className = 'cms-editor-insert-between';
                insertButton.setAttribute('aria-label', 'Block hier einfügen');
                insertButton.textContent = '+';
                insertButton.addEventListener('click', function (event) {
                    event.preventDefault();
                    insertBlockAt(editorEntry.instance, index + 1, 'paragraph', { text: '' });
                });
                redactor.insertBefore(insertButton, nextBlock);
            });

            setupImageHoverOverlay(holder, editorEntry.instance);
        }

        function ensureGroupedToolbar(definition, editorEntry) {
            var holder = getElement(definition.holderId);
            var wrap = holder ? holder.closest('.editorjs-wrap') : null;
            var toolbar;
            var overflowButton;
            var overflowPanel;
            var groups;

            if (!holder || !wrap || wrap.querySelector('.editorjs-toolbar.cms-editor-toolbar-grouped')) {
                return;
            }

            groups = [
                {
                    className: 'basis',
                    buttons: [
                        { label: 'H2', block: 'header', data: { level: 2 } },
                        { label: 'Text', block: 'paragraph' },
                        { label: 'Liste', block: 'list' },
                        { label: 'Checklist', block: 'checklist' },
                        { label: 'Bild', block: 'image' },
                        { label: 'Trenner', block: 'delimiter' },
                        { label: 'Abstand', block: 'spacer', data: { height: 15, preset: '15px' } }
                    ]
                },
                {
                    className: 'tech',
                    buttons: [
                        { label: 'Code', block: 'code' },
                        { label: 'Tabelle', block: 'table' },
                        { label: 'Embed', block: 'embed' },
                        { label: 'Link', block: 'linkTool' },
                        { label: 'Datei', block: 'attaches' },
                        { label: 'Gallery', block: 'imageGallery', data: { columns: 3 } }
                    ]
                },
                {
                    className: 'erweitert',
                    buttons: [
                        { label: 'Callout', block: 'callout' },
                        { label: 'Details', block: 'details' },
                        { label: 'Zitat', block: 'quote' },
                        { label: 'Akkordion', block: 'accordion' }
                    ]
                }
            ];

            toolbar = document.createElement('div');
            toolbar.className = 'editorjs-toolbar cms-editor-toolbar-grouped';

            function buildButton(item) {
                var button = document.createElement('button');
                var availableTools = editorEntry
                    && editorEntry.instance
                    && Array.isArray(editorEntry.instance.cmsAvailableTools)
                    ? editorEntry.instance.cmsAvailableTools
                    : [];
                var isAvailable = availableTools.length === 0 || availableTools.indexOf(item.block) !== -1;

                if (!isAvailable) {
                    return null;
                }

                button.type = 'button';
                button.textContent = item.label;
                button.addEventListener('click', function () {
                    insertBlockAt(editorEntry.instance, editorEntry.instance.blocks.getBlocksCount(), item.block, item.data || {});
                });
                return button;
            }

            groups.slice(0, 2).forEach(function (group, groupIndex) {
                var groupEl = document.createElement('div');
                var hasButtons = false;
                groupEl.className = 'cms-editor-toolbar-group cms-editor-toolbar-group--' + group.className;
                group.buttons.forEach(function (item) {
                    var button = buildButton(item);
                    if (!button) {
                        return;
                    }
                    hasButtons = true;
                    groupEl.appendChild(button);
                });
                if (!hasButtons) {
                    return;
                }
                toolbar.appendChild(groupEl);
                if (groupIndex < 1) {
                    toolbar.appendChild(document.createElement('span')).className = 'cms-editor-toolbar-divider';
                }
            });

            overflowButton = document.createElement('button');
            overflowButton.type = 'button';
            overflowButton.className = 'cms-editor-toolbar-overflow';
            overflowButton.textContent = '···';
            overflowPanel = document.createElement('div');
            overflowPanel.className = 'cms-editor-toolbar-overflow-panel';
            groups[2].buttons.forEach(function (item) {
                var button = buildButton(item);
                if (button) {
                    overflowPanel.appendChild(button);
                }
            });
            overflowButton.addEventListener('click', function (event) {
                event.preventDefault();
                overflowPanel.classList.toggle('is-open');
            });
            document.addEventListener('click', function (event) {
                if (!toolbar.contains(event.target)) {
                    overflowPanel.classList.remove('is-open');
                }
            });

            toolbar.appendChild(document.createElement('span')).className = 'cms-editor-toolbar-divider';
            toolbar.appendChild(overflowButton);
            toolbar.appendChild(overflowPanel);
            wrap.insertBefore(toolbar, wrap.firstChild || null);
        }

        function ensureEditorUi(definition, editorEntry) {
            var holder = getElement(definition.holderId);
            if (!holder || !editorEntry || !editorEntry.instance) {
                return;
            }

            ensureGroupedToolbar(definition, editorEntry);
            renderEditorBlockUi(definition, editorEntry);

            if (!editorUiState[definition.holderId]) {
                editorUiState[definition.holderId] = true;
                new MutationObserver(function () {
                    renderEditorBlockUi(definition, editorEntry);
                }).observe(holder, { childList: true, subtree: true });
            }
        }

        function trackPendingLazyBinding(key, promise) {
            var trackedPromise;

            if (!key || !promise || typeof promise.then !== 'function') {
                return promise;
            }

            trackedPromise = Promise.resolve(promise).then(function (result) {
                if (pendingLazyBindings[key] === trackedPromise) {
                    delete pendingLazyBindings[key];
                }

                return result;
            }, function (error) {
                if (pendingLazyBindings[key] === trackedPromise) {
                    delete pendingLazyBindings[key];
                }

                throw error;
            });

            pendingLazyBindings[key] = trackedPromise;
            return trackedPromise;
        }

        function waitForPendingLazyBinding(key) {
            return key && pendingLazyBindings[key]
                ? pendingLazyBindings[key]
                : Promise.resolve();
        }

        function markEditorMutation(key) {
            var state;

            if (!key) {
                return;
            }

            state = editorMutationState[key] || {
                lastMutationAt: 0,
                settlePromise: null,
                settleTimerId: null
            };

            state.lastMutationAt = Date.now();
            editorMutationState[key] = state;
        }

        function waitForEditorMutationSettle(key) {
            var state = key ? editorMutationState[key] : null;
            var settleDelay = 260;

            if (!state || !state.lastMutationAt) {
                return Promise.resolve();
            }

            if (state.settleTimerId !== null) {
                window.clearTimeout(state.settleTimerId);
                state.settleTimerId = null;
            }

            state.settlePromise = new Promise(function (resolve) {
                var complete = function () {
                    state.settleTimerId = null;
                    state.settlePromise = null;
                    resolve();
                };

                var schedule = function () {
                    var remaining = settleDelay - (Date.now() - state.lastMutationAt);

                    if (remaining <= 0) {
                        complete();
                        return;
                    }

                    state.settleTimerId = window.setTimeout(function () {
                        schedule();
                    }, remaining);
                };

                schedule();
            });

            return state.settlePromise;
        }

        function registerEditorMutationTracking(key, holder) {
            if (!key || !holder || holder.dataset.cmsEditorMutationTracked === '1') {
                return;
            }

            ['beforeinput', 'input', 'paste', 'drop', 'cut', 'keyup', 'compositionend'].forEach(function (eventName) {
                holder.addEventListener(eventName, function () {
                    markEditorMutation(key);
                }, true);
            });

            holder.dataset.cmsEditorMutationTracked = '1';
        }

        function safeParseEditorInput(input) {
            if (!input || !input.value) {
                return { blocks: [] };
            }

            try {
                return JSON.parse(input.value);
            } catch (_error) {
                return normalizeEditorData(String(input.value || ''));
            }
        }

        function normalizeEditorData(data) {
            if (typeof window.cmsNormalizeEditorJsData === 'function') {
                try {
                    return window.cmsNormalizeEditorJsData(data);
                } catch (_error) {
                    return data || { blocks: [] };
                }
            }

            return data || { blocks: [] };
        }

        function getDefinition(key) {
            return key && editorDefinitions[key] ? editorDefinitions[key] : null;
        }

        function getNamedFormField(name) {
            var namedItem;

            if (!name || !form || !form.elements || typeof form.elements.namedItem !== 'function') {
                return null;
            }

            namedItem = form.elements.namedItem(name);

            if (!namedItem) {
                return null;
            }

            if (typeof namedItem.length === 'number' && !namedItem.tagName) {
                return namedItem[0] || null;
            }

            return namedItem;
        }

        function resolveFormField(fieldId, fieldName) {
            return getElement(fieldId) || getNamedFormField(fieldName);
        }

        function getLegacyEditorRegistry() {
            return window.cmsLegacyEditors && typeof window.cmsLegacyEditors === 'object'
                ? window.cmsLegacyEditors
                : null;
        }

        function getLegacyEditorInstance(fieldId, fieldName) {
            var field = resolveFormField(fieldId, fieldName);
            var registry = getLegacyEditorRegistry();

            if (!registry || !field) {
                return null;
            }

            if (field.id && registry.byId && registry.byId[field.id]) {
                return registry.byId[field.id];
            }

            if (field.name && registry.byName && registry.byName[field.name]) {
                return registry.byName[field.name];
            }

            return null;
        }

        function getFieldValue(id) {
            var element = getElement(id);
            return element ? String(element.value || '') : '';
        }

        function setFieldValue(id, value) {
            var element = getElement(id);
            if (!element) {
                return;
            }

            element.value = String(value || '');
            emitChangeEvents(element);
        }

        function readContentFieldValue(fieldId, fieldName) {
            var field = resolveFormField(fieldId, fieldName);
            var legacyEditor = getLegacyEditorInstance(fieldId, fieldName);
            var value = '';

            if (legacyEditor && typeof legacyEditor.getContents === 'function') {
                try {
                    value = String(legacyEditor.getContents() || '');
                } catch (_error) {
                    value = '';
                }
            }

            if (value === '' && field) {
                value = String(field.value || '');
            }

            if (field) {
                field.value = value;
                emitChangeEvents(field);
            }

            return value;
        }

        function writeContentFieldValue(fieldId, fieldName, value) {
            var field = resolveFormField(fieldId, fieldName);
            var legacyEditor = getLegacyEditorInstance(fieldId, fieldName);
            var normalizedValue = String(value || '');

            if (legacyEditor && typeof legacyEditor.setContents === 'function') {
                try {
                    legacyEditor.setContents(normalizedValue);
                } catch (_error) {
                    // Fall back to the raw field update below.
                }
            }

            if (!field) {
                return;
            }

            field.value = normalizedValue;
            emitChangeEvents(field);
        }

        function readEditorJsSourceData(fieldId, fieldName) {
            var field = resolveFormField(fieldId, fieldName);

            return normalizeEditorData(safeParseEditorInput(field));
        }

        function readUploadContextField(fieldId) {
            var element = getElement(fieldId);

            return element ? String(element.value || '').trim() : '';
        }

        function buildUploadContext() {
            var uploadContext = config.uploadContext && typeof config.uploadContext === 'object'
                ? config.uploadContext
                : {};

            return {
                contentType: String(uploadContext.contentType || ''),
                isNew: !!uploadContext.isNew,
                draftKey: String(uploadContext.draftKey || ''),
                contentSlug: readUploadContextField(uploadContext.slugInputId),
                contentSlugFallback: readUploadContextField(uploadContext.slugFallbackInputId),
                contentTitle: readUploadContextField(uploadContext.titleInputId),
                contentTitleFallback: readUploadContextField(uploadContext.titleFallbackInputId)
            };
        }

        function isEditorInputEmpty(input) {
            var data = normalizeEditorData(safeParseEditorInput(input));
            return !data || !Array.isArray(data.blocks) || data.blocks.length === 0;
        }

        function ensureNoticeBox() {
            var notice = getElement('cmsContentEditorNotice');

            if (notice) {
                return notice;
            }

            notice = document.createElement('div');
            notice.id = 'cmsContentEditorNotice';
            notice.className = 'alert d-none mb-3';
            form.insertBefore(notice, form.firstChild);

            return notice;
        }

        function showNotice(type, message) {
            var notice = ensureNoticeBox();
            var normalizedType = type === 'danger' ? 'danger' : (type || 'info');

            notice.className = 'alert alert-' + normalizedType + ' mb-3';
            notice.textContent = String(message || '');
            notice.classList.remove('d-none');
        }

        function clearNotice() {
            var notice = getElement('cmsContentEditorNotice');
            if (!notice) {
                return;
            }

            notice.textContent = '';
            notice.className = 'alert d-none mb-3';
        }

        function ensurePreviewPanel() {
            var panel = getElement('cmsContentEditorTranslationPreview');
            var notice = ensureNoticeBox();

            if (panel) {
                return panel;
            }

            panel = document.createElement('div');
            panel.id = 'cmsContentEditorTranslationPreview';
            panel.className = 'card border-primary mb-3 d-none';

            if (notice && notice.parentNode === form) {
                if (notice.nextSibling) {
                    form.insertBefore(panel, notice.nextSibling);
                } else {
                    form.appendChild(panel);
                }

                return panel;
            }

            form.insertBefore(panel, form.firstChild);
            return panel;
        }

        function clearPreviewPanel() {
            var panel = getElement('cmsContentEditorTranslationPreview');

            translationPreviewActive = false;
            if (!panel) {
                return;
            }

            clearElement(panel);
            panel.className = 'card border-primary mb-3 d-none';
        }

        function withSuppressedPreviewClear(callback) {
            translationPreviewSuppressClear = true;

            try {
                return callback();
            } finally {
                translationPreviewSuppressClear = false;
            }
        }

        function invalidateTranslationPreview(message) {
            if (!translationPreviewActive || translationPreviewSuppressClear) {
                return;
            }

            clearPreviewPanel();
            showNotice('info', message || 'Der AI-Vorschlag wurde verworfen, weil sich die EN-Bearbeitung seit der Vorschau geändert hat.');
        }

        function stripHtmlPreview(value) {
            return extractTextFromHtml(value);
        }

        function normalizePreviewText(value) {
            return stripHtmlPreview(value).replace(/\s+/g, ' ').trim();
        }

        function truncatePreviewText(value, maxLength) {
            var normalized = normalizePreviewText(value);

            if (normalized.length <= maxLength) {
                return normalized;
            }

            return normalized.slice(0, Math.max(1, maxLength - 1)).trim() + '…';
        }

        function collectListPreviewText(items, collector) {
            (Array.isArray(items) ? items : []).forEach(function (item) {
                if (typeof item === 'string') {
                    if (normalizePreviewText(item) !== '') {
                        collector.push(normalizePreviewText(item));
                    }
                    return;
                }

                if (!item || typeof item !== 'object') {
                    return;
                }

                ['content', 'text'].forEach(function (key) {
                    if (typeof item[key] === 'string' && normalizePreviewText(item[key]) !== '') {
                        collector.push(normalizePreviewText(item[key]));
                    }
                });

                if (Array.isArray(item.items)) {
                    collectListPreviewText(item.items, collector);
                }
            });
        }

        function extractBlockPreviewText(block) {
            var data = block && block.data && typeof block.data === 'object' ? block.data : {};
            var parts = [];

            switch (String(block && block.type || '')) {
                case 'paragraph':
                case 'header':
                case 'mediaText':
                    if (typeof data.text === 'string') {
                        parts.push(normalizePreviewText(data.text));
                    }
                    break;
                case 'quote':
                    ['text', 'caption'].forEach(function (key) {
                        if (typeof data[key] === 'string' && normalizePreviewText(data[key]) !== '') {
                            parts.push(normalizePreviewText(data[key]));
                        }
                    });
                    break;
                case 'warning':
                case 'callout':
                    ['title', 'message'].forEach(function (key) {
                        if (typeof data[key] === 'string' && normalizePreviewText(data[key]) !== '') {
                            parts.push(normalizePreviewText(data[key]));
                        }
                    });
                    break;
                case 'checklist':
                    (Array.isArray(data.items) ? data.items : []).forEach(function (item) {
                        if (item && typeof item === 'object' && typeof item.text === 'string' && normalizePreviewText(item.text) !== '') {
                            parts.push(normalizePreviewText(item.text));
                        }
                    });
                    break;
                case 'list':
                    collectListPreviewText(data.items, parts);
                    break;
                case 'raw':
                    if (typeof data.html === 'string' && normalizePreviewText(data.html) !== '') {
                        parts.push(normalizePreviewText(data.html));
                    }
                    break;
                default:
                    ['text', 'title', 'message', 'caption', 'html'].forEach(function (key) {
                        if (typeof data[key] === 'string' && normalizePreviewText(data[key]) !== '') {
                            parts.push(normalizePreviewText(data[key]));
                        }
                    });
                    break;
            }

            return parts.join(' · ');
        }

        function buildBlockPreviewEntries(data) {
            var normalizedData = normalizeEditorData(data);
            var blocks = Array.isArray(normalizedData && normalizedData.blocks) ? normalizedData.blocks : [];

            return blocks.map(function (block, index) {
                var type = String(block && block.type || 'unbekannt');

                return {
                    index: index + 1,
                    type: type,
                    text: extractBlockPreviewText(block)
                };
            });
        }

        function resolveTranslationOutput(aiTranslation, translation) {
            var translatedData = translation && translation.content_data ? translation.content_data : null;

            if (!translatedData && translation && typeof translation.content_json === 'string') {
                try {
                    translatedData = JSON.parse(translation.content_json);
                } catch (_error) {
                    translatedData = { blocks: [] };
                }
            }

            return {
                title: aiTranslation.targetTitleId
                    ? ((translation && typeof translation.title === 'string' && translation.title !== '')
                        ? translation.title
                        : getFieldValue(aiTranslation.sourceTitleId))
                    : '',
                slug: aiTranslation.targetSlugId
                    ? ((translation && typeof translation.slug === 'string' && translation.slug !== '')
                        ? translation.slug
                        : getFieldValue(aiTranslation.sourceSlugId))
                    : '',
                excerpt: aiTranslation.targetExcerptId
                    ? ((translation && typeof translation.excerpt === 'string' && translation.excerpt !== '')
                        ? translation.excerpt
                        : getFieldValue(aiTranslation.sourceExcerptId))
                    : '',
                contentData: normalizeEditorData(translatedData || { blocks: [] })
            };
        }

        function getTargetDraftState(aiTranslation) {
            var targetDefinition = getDefinition(aiTranslation.targetEditorKey);
            var targetInput = targetDefinition ? getElement(targetDefinition.inputId) : null;

            return {
                title: aiTranslation.targetTitleId ? getFieldValue(aiTranslation.targetTitleId) : '',
                slug: aiTranslation.targetSlugId ? getFieldValue(aiTranslation.targetSlugId) : '',
                excerpt: aiTranslation.targetExcerptId ? getFieldValue(aiTranslation.targetExcerptId) : '',
                contentData: normalizeEditorData(safeParseEditorInput(targetInput))
            };
        }

        function buildBlockDiffSummary(currentData, nextData) {
            var currentBlocks = buildBlockPreviewEntries(currentData);
            var nextBlocks = buildBlockPreviewEntries(nextData);
            var totalBlocks = Math.max(currentBlocks.length, nextBlocks.length);
            var diffs = [];

            for (var index = 0; index < totalBlocks; index += 1) {
                var currentBlock = currentBlocks[index] || { type: '—', text: '' };
                var nextBlock = nextBlocks[index] || { type: '—', text: '' };

                if (currentBlock.type === nextBlock.type
                    && normalizePreviewText(currentBlock.text) === normalizePreviewText(nextBlock.text)) {
                    continue;
                }

                diffs.push({
                    label: 'Block ' + (index + 1),
                    currentType: currentBlock.type,
                    nextType: nextBlock.type,
                    currentText: truncatePreviewText(currentBlock.text || '— leer —', 220) || '— leer —',
                    nextText: truncatePreviewText(nextBlock.text || '— leer —', 220) || '— leer —'
                });
            }

            return {
                currentBlockCount: currentBlocks.length,
                nextBlockCount: nextBlocks.length,
                totalDiffs: diffs.length,
                items: diffs.slice(0, 8),
                omittedDiffs: Math.max(0, diffs.length - 8)
            };
        }

        function createPreviewTextBlock(label, value, className) {
            var wrapper = document.createElement('div');
            var labelElement = document.createElement('div');
            var valueElement = document.createElement('div');

            wrapper.className = 'mb-2';
            labelElement.className = 'text-secondary small mb-1';
            labelElement.textContent = label;
            valueElement.className = className || 'border rounded p-2 bg-body-secondary text-break small';
            valueElement.textContent = value;

            wrapper.appendChild(labelElement);
            wrapper.appendChild(valueElement);

            return wrapper;
        }

        function createFieldPreviewColumn(fieldLabel, currentValue, nextValue) {
            var column = document.createElement('div');
            var card = document.createElement('div');
            var body = document.createElement('div');
            var title = document.createElement('h4');

            column.className = 'col-lg-6';
            card.className = 'card card-sm h-100';
            body.className = 'card-body';
            title.className = 'card-title mb-3';
            title.textContent = fieldLabel;

            body.appendChild(title);
            body.appendChild(createPreviewTextBlock('Aktuell EN', currentValue !== '' ? currentValue : '— leer —'));
            body.appendChild(createPreviewTextBlock('AI-Vorschlag', nextValue !== '' ? nextValue : '— leer —', 'border rounded p-2 bg-primary-subtle text-break small'));
            card.appendChild(body);
            column.appendChild(card);

            return column;
        }

        function renderTranslationPreview(aiTranslation, result, resolvedOutput, onApply, onDiscard) {
            var panel = ensurePreviewPanel();
            var provider = result && result.provider && typeof result.provider === 'object' ? result.provider : {};
            var warnings = Array.isArray(result && result.warnings) ? result.warnings : [];
            var stats = result && result.stats && typeof result.stats === 'object' ? result.stats : {};
            var targetDraft = getTargetDraftState(aiTranslation);
            var fieldDiffs = [];
            var blockSummary = buildBlockDiffSummary(targetDraft.contentData, resolvedOutput.contentData);
            var header = document.createElement('div');
            var headerTitle = document.createElement('div');
            var headerBadges = document.createElement('div');
            var body = document.createElement('div');
            var intro = document.createElement('div');
            var fieldsRow = document.createElement('div');
            var statsCard = document.createElement('div');
            var statsBody = document.createElement('div');
            var statsTitle = document.createElement('h4');
            var statsList = document.createElement('div');
            var blockList = document.createElement('div');
            var footer = document.createElement('div');
            var hint = document.createElement('div');
            var buttonRow = document.createElement('div');
            var discardButton = document.createElement('button');
            var applyButton = document.createElement('button');

            clearElement(panel);
            panel.className = 'card border-primary mb-3';
            translationPreviewActive = true;

            if (aiTranslation.targetTitleId
                && normalizePreviewText(targetDraft.title) !== normalizePreviewText(resolvedOutput.title)) {
                fieldDiffs.push(createFieldPreviewColumn('Titel', targetDraft.title, resolvedOutput.title));
            }

            if (aiTranslation.targetSlugId
                && normalizePreviewText(targetDraft.slug) !== normalizePreviewText(resolvedOutput.slug)) {
                fieldDiffs.push(createFieldPreviewColumn('Slug', targetDraft.slug, resolvedOutput.slug));
            }

            if (aiTranslation.targetExcerptId
                && normalizePreviewText(targetDraft.excerpt) !== normalizePreviewText(resolvedOutput.excerpt)) {
                fieldDiffs.push(createFieldPreviewColumn('Kurzfassung', targetDraft.excerpt, resolvedOutput.excerpt));
            }

            header.className = 'card-header d-flex justify-content-between align-items-center gap-3 flex-wrap';
            headerTitle.className = 'fw-semibold';
            headerTitle.textContent = 'AI-Übersetzung prüfen';
            headerBadges.className = 'd-flex flex-wrap gap-2';

            [
                provider.label ? 'Provider: ' + provider.label : '',
                provider.model ? 'Modell: ' + provider.model : '',
                typeof stats.translated_blocks === 'number' ? 'Blöcke: ' + String(stats.translated_blocks) : '',
                typeof stats.translated_segments === 'number' ? 'Segmente: ' + String(stats.translated_segments) : ''
            ].filter(function (value) {
                return value !== '';
            }).forEach(function (value) {
                var badge = document.createElement('span');
                badge.className = 'badge bg-primary-lt text-primary';
                badge.textContent = value;
                headerBadges.appendChild(badge);
            });

            header.appendChild(headerTitle);
            header.appendChild(headerBadges);

            body.className = 'card-body';
            intro.className = 'alert alert-info';
            intro.textContent = 'Die AI-Übersetzung wurde erzeugt, aber noch nicht in die EN-Bearbeitung übernommen. Bitte prüfe die Änderungen bewusst, bevor du sie anwendest.';
            body.appendChild(intro);

            if (warnings.length > 0) {
                var warningBox = document.createElement('div');
                var warningTitle = document.createElement('div');
                var warningList = document.createElement('ul');

                warningBox.className = 'alert alert-warning';
                warningTitle.className = 'fw-semibold mb-2';
                warningTitle.textContent = 'Hinweise';
                warningList.className = 'mb-0 ps-3';

                warnings.forEach(function (warning) {
                    var item = document.createElement('li');
                    item.textContent = String(warning || '');
                    warningList.appendChild(item);
                });

                warningBox.appendChild(warningTitle);
                warningBox.appendChild(warningList);
                body.appendChild(warningBox);
            }

            fieldsRow.className = 'row g-3';
            if (fieldDiffs.length > 0) {
                fieldDiffs.forEach(function (fieldColumn) {
                    fieldsRow.appendChild(fieldColumn);
                });
                body.appendChild(fieldsRow);
            }

            statsCard.className = 'card card-sm mt-3';
            statsBody.className = 'card-body';
            statsTitle.className = 'card-title mb-3';
            statsTitle.textContent = 'Editor.js-Diff';
            statsList.className = 'row g-2 mb-3';

            [
                { label: 'Aktuelle EN-Blöcke', value: String(blockSummary.currentBlockCount) },
                { label: 'Neue EN-Blöcke', value: String(blockSummary.nextBlockCount) },
                { label: 'Geänderte Blöcke', value: String(blockSummary.totalDiffs) },
                { label: 'Übersetzte Segmente', value: String(typeof stats.translated_segments === 'number' ? stats.translated_segments : 0) }
            ].forEach(function (entry) {
                var col = document.createElement('div');
                var box = document.createElement('div');
                var value = document.createElement('div');
                var label = document.createElement('div');

                col.className = 'col-sm-6 col-lg-3';
                box.className = 'border rounded p-2 h-100';
                value.className = 'fw-semibold';
                label.className = 'text-secondary small';
                value.textContent = entry.value;
                label.textContent = entry.label;
                box.appendChild(value);
                box.appendChild(label);
                col.appendChild(box);
                statsList.appendChild(col);
            });

            statsBody.appendChild(statsTitle);
            statsBody.appendChild(statsList);

            blockList.className = 'd-flex flex-column gap-2';
            if (blockSummary.items.length === 0) {
                var noChanges = document.createElement('div');
                noChanges.className = 'alert alert-secondary mb-0';
                noChanges.textContent = 'Im Editor.js-Inhalt wurden gegenüber der aktuellen EN-Fassung keine textuellen Änderungen erkannt.';
                blockList.appendChild(noChanges);
            } else {
                blockSummary.items.forEach(function (item) {
                    var diffCard = document.createElement('div');
                    var diffHeader = document.createElement('div');
                    var diffGrid = document.createElement('div');
                    var currentColumn = document.createElement('div');
                    var nextColumn = document.createElement('div');

                    diffCard.className = 'border rounded p-3';
                    diffHeader.className = 'fw-semibold mb-2';
                    diffHeader.textContent = item.label + ' (' + item.currentType + ' → ' + item.nextType + ')';
                    diffGrid.className = 'row g-2';
                    currentColumn.className = 'col-lg-6';
                    nextColumn.className = 'col-lg-6';

                    currentColumn.appendChild(createPreviewTextBlock('Aktuell EN', item.currentText));
                    nextColumn.appendChild(createPreviewTextBlock('AI-Vorschlag', item.nextText, 'border rounded p-2 bg-primary-subtle text-break small'));
                    diffGrid.appendChild(currentColumn);
                    diffGrid.appendChild(nextColumn);
                    diffCard.appendChild(diffHeader);
                    diffCard.appendChild(diffGrid);
                    blockList.appendChild(diffCard);
                });

                if (blockSummary.omittedDiffs > 0) {
                    var omitted = document.createElement('div');
                    omitted.className = 'text-secondary small';
                    omitted.textContent = '+' + String(blockSummary.omittedDiffs) + ' weitere Block-Änderungen sind vorhanden.';
                    blockList.appendChild(omitted);
                }
            }

            statsBody.appendChild(blockList);
            statsCard.appendChild(statsBody);
            body.appendChild(statsCard);

            footer.className = 'card-footer d-flex justify-content-between align-items-center gap-3 flex-wrap';
            hint.className = 'text-secondary small';
            hint.textContent = 'Hinweis: „Übernehmen“ schreibt den Vorschlag nur in den Editor. Dauerhaft gespeichert wird wie gewohnt erst beim regulären Speichern des Formulars.';
            buttonRow.className = 'btn-list';
            discardButton.type = 'button';
            discardButton.className = 'btn btn-outline-secondary';
            discardButton.textContent = 'Vorschlag verwerfen';
            applyButton.type = 'button';
            applyButton.className = 'btn btn-primary';
            applyButton.textContent = 'Übersetzung übernehmen';

            discardButton.addEventListener('click', function () {
                if (typeof onDiscard === 'function') {
                    onDiscard();
                }
            });

            applyButton.addEventListener('click', function () {
                if (typeof onApply === 'function') {
                    onApply();
                }
            });

            buttonRow.appendChild(discardButton);
            buttonRow.appendChild(applyButton);
            footer.appendChild(hint);
            footer.appendChild(buttonRow);

            panel.appendChild(header);
            panel.appendChild(body);
            panel.appendChild(footer);
        }

        function setButtonBusy(button, busy, busyLabel) {
            if (!button) {
                return;
            }

            if (busy) {
                if (!button.dataset.originalLabel) {
                    button.dataset.originalLabel = button.textContent || '';
                }

                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
                button.textContent = busyLabel || 'Wird verarbeitet …';
                return;
            }

            button.disabled = false;
            button.removeAttribute('aria-busy');
            if (button.dataset.originalLabel) {
                button.textContent = button.dataset.originalLabel;
            }
        }

        function isFormSubmitter(element) {
            var tagName;
            var type;

            if (!element || element.form !== form) {
                return false;
            }

            tagName = String(element.tagName || '').toLowerCase();
            type = String(element.type || '').toLowerCase();

            if (tagName === 'button') {
                return type === '' || type === 'submit';
            }

            if (tagName === 'input') {
                return type === 'submit' || type === 'image';
            }

            return false;
        }

        function createFallbackSubmitter(submitter) {
            var fallbackButton = document.createElement('button');

            fallbackButton.type = 'submit';
            fallbackButton.hidden = true;
            fallbackButton.tabIndex = -1;
            fallbackButton.setAttribute('aria-hidden', 'true');

            if (!submitter) {
                return fallbackButton;
            }

            if (submitter.name) {
                fallbackButton.name = submitter.name;
            }

            if (typeof submitter.value === 'string') {
                fallbackButton.value = submitter.value;
            }

            ['formaction', 'formmethod', 'formenctype', 'formtarget'].forEach(function (attributeName) {
                if (submitter.hasAttribute && submitter.hasAttribute(attributeName)) {
                    fallbackButton.setAttribute(attributeName, submitter.getAttribute(attributeName));
                }
            });

            if (submitter.hasAttribute && submitter.hasAttribute('formnovalidate')) {
                fallbackButton.setAttribute('formnovalidate', 'formnovalidate');
            }

            return fallbackButton;
        }

        function activateTargetPane(buttonId, targetEditorKey, options) {
            var button = getElement(buttonId);
            var isActive = button && button.getAttribute('aria-pressed') === 'true';
            var activateOptions = options && typeof options === 'object' ? options : {};

            function cleanup() {
                if (activateOptions.suppressInitialCopy && targetEditorKey) {
                    delete suppressInitialCopyForKeys[targetEditorKey];
                }
            }

            if (activateOptions.suppressInitialCopy && targetEditorKey) {
                suppressInitialCopyForKeys[targetEditorKey] = true;
            }

            if (button && !isActive) {
                button.click();
            }

            return waitForNextPaint().then(function () {
                return waitForPendingLazyBinding(targetEditorKey);
            }).then(function () {
                return waitForNextPaint();
            }).then(function (result) {
                cleanup();
                return result;
            }, function (error) {
                cleanup();
                throw error;
            });
        }

        function destroyEditor(key) {
            var current = editors[key];
            var definition = getDefinition(key);
            var holder;

            if (!current) {
                return;
            }

            try {
                if (current.instance && typeof current.instance.destroy === 'function') {
                    current.instance.destroy();
                }
            } catch (_error) {
                // noop
            }

            if (definition) {
                holder = getElement(definition.holderId);
                if (holder) {
                    clearElement(holder);
                }
            }

            delete editors[key];
        }

        function bindEditor(definition, forceRecreate) {
            var holder = getElement(definition.holderId);
            var input = getElement(definition.inputId);
            var createdInstance;

            if (!definition || !holder || !input) {
                return null;
            }

            if (!hasEditorFactory() || !hasEditorCore()) {
                if (typeof console !== 'undefined' && typeof console.error === 'function') {
                    console.error('[cms-editor] EditorJS bind skipped for holder "' + definition.holderId + '".', {
                        hasFactory: hasEditorFactory(),
                        hasCore: hasEditorCore()
                    });
                }

                renderEditorUnavailableFallback(definition, input, hasEditorFactory() ? 'core-missing' : 'factory-missing');
                return null;
            }

            if (editors[definition.key] && !forceRecreate) {
                ensureEditorUi(definition, editors[definition.key]);
                return editors[definition.key];
            }

            if (forceRecreate) {
                destroyEditor(definition.key);
            }

            registerEditorMutationTracking(definition.key, holder);

            try {
                createdInstance = window.createCmsEditor(definition.holderId, input.value || '', config.mediaUploadUrl, config.csrfToken, {
                    getUploadContext: buildUploadContext,
                    onChange: function (output) {
                        var currentEntry = editors[definition.key];

                        if (!currentEntry || currentEntry.input !== input) {
                            return;
                        }

                        input.value = JSON.stringify(normalizeEditorData(output));
                        emitChangeEvents(input);
                    }
                });
            } catch (error) {
                if (typeof console !== 'undefined' && typeof console.error === 'function') {
                    console.error('[cms-editor] EditorJS init failed for holder "' + definition.holderId + '".', error);
                }

                renderEditorUnavailableFallback(definition, input, 'init-failed');
                return null;
            }

            editors[definition.key] = {
                input: input,
                instance: createdInstance
            };

            ensureEditorUi(definition, editors[definition.key]);

            return editors[definition.key];
        }

        function getEditorSaveLabel(definition) {
            if (!definition || !definition.key) {
                return 'Editor-Inhalt';
            }

            if (definition.key === 'de') {
                return 'DE-Inhalt';
            }

            if (definition.key === 'en') {
                return 'EN-Inhalt';
            }

            return String(definition.key).toUpperCase() + '-Inhalt';
        }

        function saveEditorContent(key, allowFallback) {
            var entry = editors[key];
            var definition = getDefinition(key);
            var input = definition ? getElement(definition.inputId) : null;
            var activeEntry = null;

            if (!entry) {
                return Promise.resolve(normalizeEditorData(safeParseEditorInput(input)));
            }

            return waitForPendingLazyBinding(key).then(function () {
                return ensureEditorReady(key, false);
            }).then(function (readyEntry) {
                activeEntry = readyEntry || editors[key] || entry;

                if (!activeEntry || !activeEntry.instance) {
                    return normalizeEditorData(safeParseEditorInput(input));
                }

                return waitForEditorInstanceReady(activeEntry).then(function () {
                    return waitForNextPaint();
                }).then(function () {
                    return waitForEditorMutationSettle(key);
                }).then(function () {
                    return activeEntry.instance.save();
                });
            }).then(function (output) {
                if (activeEntry && activeEntry.input) {
                    activeEntry.input.value = JSON.stringify(output);
                    emitChangeEvents(activeEntry.input);
                }

                return normalizeEditorData(output);
            }).catch(function (error) {
                if (allowFallback) {
                    return normalizeEditorData(safeParseEditorInput(input));
                }

                var saveError = new Error(getEditorSaveLabel(definition) + ' konnte vor dem Speichern nicht zuverlässig aus Editor.js serialisiert werden. Bitte problematische Blöcke prüfen und erneut speichern.');

                saveError.editorDefinition = definition || null;
                saveError.originalError = error || null;

                throw saveError;
            });
        }

        function ensureEditorSaved(key) {
            return saveEditorContent(key, true);
        }

        function ensureEditorReady(key, forceRecreate) {
            var definition = getDefinition(key);
            var entry;

            if (!definition) {
                return Promise.resolve(null);
            }

            entry = bindEditor(definition, forceRecreate);
            if (!entry || !entry.instance) {
                return Promise.resolve(null);
            }

            if (entry.instance.isReady && typeof entry.instance.isReady.then === 'function') {
                return entry.instance.isReady.then(function () {
                    return entry;
                }).catch(function () {
                    return entry;
                });
            }

            return Promise.resolve(entry);
        }

        function waitForEditorInstanceReady(entry) {
            if (!entry || !entry.instance || !entry.instance.isReady || typeof entry.instance.isReady.then !== 'function') {
                return Promise.resolve(entry);
            }

            return entry.instance.isReady.then(function () {
                return entry;
            }).catch(function () {
                return entry;
            });
        }

        function applyEditorData(key, data, options) {
            var definition = getDefinition(key);
            var input = definition ? getElement(definition.inputId) : null;
            var applyOptions = options && typeof options === 'object' ? options : {};
            var normalizedData = normalizeEditorData(data);
            var renderResult;

            if (!input) {
                return Promise.resolve();
            }

            input.value = JSON.stringify(normalizedData);
            emitChangeEvents(input);

            if (!definition) {
                return Promise.resolve();
            }

            if (applyOptions.recreateEditor) {
                return ensureEditorReady(key, true).then(function (entry) {
                    return waitForNextPaint().then(function () {
                        return entry;
                    });
                }).catch(function () {
                    return ensureEditorReady(key, true).then(function (entry) {
                        return waitForNextPaint().then(function () {
                            return entry;
                        });
                    });
                });
            }

            return ensureEditorReady(key).then(function (entry) {
                var instance = entry && entry.instance ? entry.instance : null;

                if (!instance) {
                    return null;
                }

                if (instance.blocks && typeof instance.blocks.render === 'function') {
                    renderResult = instance.blocks.render(normalizedData);
                    return Promise.resolve(renderResult).then(function () {
                        return entry;
                    });
                }

                if (typeof instance.render === 'function') {
                    renderResult = instance.render(normalizedData);
                    return Promise.resolve(renderResult).then(function () {
                        return entry;
                    });
                }

                bindEditor(definition, true);
                return ensureEditorReady(key, false);
            }).catch(function () {
                bindEditor(definition, true);
                return ensureEditorReady(key, false);
            });
        }

        function copyFieldValue(sourceId, targetId) {
            if (!sourceId || !targetId) {
                return;
            }

            setFieldValue(targetId, getFieldValue(sourceId));
        }

        function targetHasDraftContent(targetEditorKey, targetTitleId, targetSlugId, targetExcerptId) {
            var targetDefinition = getDefinition(targetEditorKey);
            var targetInput = targetDefinition ? getElement(targetDefinition.inputId) : null;

            return (targetTitleId ? getFieldValue(targetTitleId) !== '' : false)
                || (targetSlugId ? getFieldValue(targetSlugId) !== '' : false)
                || (targetExcerptId ? getFieldValue(targetExcerptId) !== '' : false)
                || (targetInput && !isEditorInputEmpty(targetInput));
        }

        function handleCopyAction(copyAction) {
            var button = copyAction && copyAction.buttonId ? getElement(copyAction.buttonId) : null;

            if (!copyAction) {
                return;
            }

            if (button) {
                button.addEventListener('click', function (event) {
                    if (event) {
                        event.preventDefault();
                    }

                    clearNotice();
                    clearPreviewPanel();
                    setButtonBusy(button, true, 'Kopiere …');

                    if (copyAction.contentMode === 'legacy-html') {
                        Promise.resolve().then(function () {
                            copyFieldValue(copyAction.sourceTitleId, copyAction.targetTitleId);
                            copyFieldValue(copyAction.sourceSlugId, copyAction.targetSlugId);
                            copyFieldValue(copyAction.sourceExcerptId, copyAction.targetExcerptId);
                            writeContentFieldValue(
                                copyAction.targetContentFieldId,
                                copyAction.targetContentFieldName,
                                readContentFieldValue(copyAction.sourceContentFieldId, copyAction.sourceContentFieldName)
                            );
                        }).then(function () {
                            showNotice('success', 'DE-Inhalt wurde in die EN-Bearbeitung übernommen und bestehende EN-Inhalte überschrieben.');
                        }).catch(function (error) {
                            if (typeof console !== 'undefined' && typeof console.error === 'function') {
                                console.error('DE→EN Copy fehlgeschlagen.', error);
                            }

                            showNotice('danger', (error && error.message) ? error.message : 'DE-Inhalt konnte nicht in die EN-Bearbeitung kopiert werden.');
                        }).finally(function () {
                            setButtonBusy(button, false);
                        });

                        return;
                    }

                    Promise.resolve().then(function () {
                        if (copyAction.sourceEditorKey && editors[copyAction.sourceEditorKey]) {
                            return ensureEditorSaved(copyAction.sourceEditorKey);
                        }

                        return readEditorJsSourceData(copyAction.sourceContentFieldId, copyAction.sourceContentFieldName);
                    }).then(function (sourceData) {
                        var recreateTargetEditor = !(editors[copyAction.targetEditorKey] && editors[copyAction.targetEditorKey].instance);

                        return withSuppressedPreviewClear(function () {
                            copyFieldValue(copyAction.sourceTitleId, copyAction.targetTitleId);
                            copyFieldValue(copyAction.sourceSlugId, copyAction.targetSlugId);
                            copyFieldValue(copyAction.sourceExcerptId, copyAction.targetExcerptId);

                            return activateTargetPane(copyAction.targetPaneButtonId, copyAction.targetEditorKey, {
                                suppressInitialCopy: true
                            }).then(function () {
                                return applyEditorData(copyAction.targetEditorKey, sourceData, {
                                    recreateEditor: recreateTargetEditor
                                });
                            });
                        });
                    }).then(function () {
                        showNotice('success', 'DE-Inhalt wurde in die EN-Bearbeitung übernommen und bestehende EN-Inhalte überschrieben.');
                    }).catch(function (error) {
                        if (typeof console !== 'undefined' && typeof console.error === 'function') {
                            console.error('DE→EN Copy fehlgeschlagen.', error);
                        }

                        showNotice('danger', (error && error.message) ? error.message : 'DE-Inhalt konnte nicht in die EN-Bearbeitung kopiert werden.');
                    }).finally(function () {
                        setButtonBusy(button, false);
                    });
                });
            }
        }

        function buildTranslationPayload(aiTranslation, sourceData) {
            var params = new URLSearchParams();

            params.append('csrf_token', String(aiTranslation.csrfToken || ''));
            params.append('content_type', String(aiTranslation.contentType || 'editorjs'));
            params.append('source_locale', String(aiTranslation.sourceLocale || 'de'));
            params.append('target_locale', String(aiTranslation.targetLocale || 'en'));
            params.append('title', getFieldValue(aiTranslation.sourceTitleId));
            params.append('slug', getFieldValue(aiTranslation.sourceSlugId));
            params.append('excerpt', getFieldValue(aiTranslation.sourceExcerptId));
            params.append('editor_data', JSON.stringify(normalizeEditorData(sourceData)));

            return params;
        }

        function resolveSameOriginUrl(url) {
            var resolved;

            try {
                resolved = new URL(String(url || ''), window.location.origin);
            } catch (_error) {
                return '';
            }

            if (resolved.origin !== window.location.origin) {
                return '';
            }

            return resolved.href;
        }

        function requestJson(url, body) {
            var endpointUrl = resolveSameOriginUrl(url);
            var controller = typeof AbortController === 'function' ? new AbortController() : null;
            var timeoutId = null;
            var maxResponseLength = 1048576;

            if (endpointUrl === '') {
                return Promise.reject(new Error('AI-Übersetzungsendpunkt ist ungültig.'));
            }

            if (controller) {
                timeoutId = window.setTimeout(function () {
                    controller.abort();
                }, 45000);
            }

            return fetch(endpointUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body.toString(),
                signal: controller ? controller.signal : undefined
            }).then(function (response) {
                var declaredLength = Number(response.headers && response.headers.get('Content-Length'));

                if (Number.isFinite(declaredLength) && declaredLength > maxResponseLength) {
                    throw new Error('AI-Übersetzungsantwort ist zu groß.');
                }

                return response.text().then(function (text) {
                    var data = null;

                    if (String(text || '').length > maxResponseLength) {
                        throw new Error('AI-Übersetzungsantwort ist zu groß.');
                    }

                    try {
                        data = JSON.parse(text);
                    } catch (_error) {
                        data = null;
                    }

                    return {
                        ok: response.ok,
                        status: response.status,
                        data: data,
                        rawText: ''
                    };
                });
            }).catch(function (error) {
                if (error && error.name === 'AbortError') {
                    throw new Error('AI-Übersetzung hat das Zeitlimit überschritten.');
                }

                throw error;
            }).finally(function () {
                if (timeoutId !== null) {
                    window.clearTimeout(timeoutId);
                }
            });
        }

        function targetAlreadyHasContent(aiTranslation) {
            return targetHasDraftContent(
                aiTranslation.targetEditorKey,
                aiTranslation.targetTitleId,
                aiTranslation.targetSlugId,
                aiTranslation.targetExcerptId
            );
        }

        function applyResolvedTranslation(aiTranslation, resolvedOutput) {
            return withSuppressedPreviewClear(function () {
                return activateTargetPane(aiTranslation.targetPaneButtonId, aiTranslation.targetEditorKey, {
                    suppressInitialCopy: true
                }).then(function () {
                    if (aiTranslation.targetTitleId) {
                        setFieldValue(aiTranslation.targetTitleId, resolvedOutput.title);
                    }

                    if (aiTranslation.targetSlugId) {
                        setFieldValue(aiTranslation.targetSlugId, resolvedOutput.slug);
                    }

                    if (aiTranslation.targetExcerptId) {
                        setFieldValue(aiTranslation.targetExcerptId, resolvedOutput.excerpt);
                    }

                    return applyEditorData(aiTranslation.targetEditorKey, resolvedOutput.contentData || { blocks: [] }, {
                        recreateEditor: true
                    }).then(function () {
                        clearPreviewPanel();
                    });
                });
            });
        }

        function dispatchValidatedSubmit(submitter) {
            var resolvedSubmitter = isFormSubmitter(submitter) ? submitter : null;
            var fallbackButton;

            if (typeof form.requestSubmit === 'function') {
                nativeSubmitPending = true;

                try {
                    if (resolvedSubmitter) {
                        form.requestSubmit(resolvedSubmitter);
                    } else {
                        form.requestSubmit();
                    }
                } finally {
                    nativeSubmitPending = false;
                    pendingSubmitter = null;
                }

                return;
            }

            if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
                return;
            }

            fallbackButton = createFallbackSubmitter(resolvedSubmitter);

            nativeSubmitPending = true;
            form.appendChild(fallbackButton);

            try {
                fallbackButton.click();
            } finally {
                fallbackButton.remove();
                nativeSubmitPending = false;
                pendingSubmitter = null;
            }
        }

        function registerPreviewInvalidation(aiTranslation) {
            var targetEditorDefinition = getDefinition(aiTranslation.targetEditorKey);
            var targetFieldIds = [aiTranslation.targetTitleId, aiTranslation.targetSlugId, aiTranslation.targetExcerptId]
                .filter(function (fieldId) {
                    return !!fieldId;
                });

            targetFieldIds.forEach(function (fieldId) {
                var field = getElement(fieldId);

                if (!field || field.dataset.translationPreviewInvalidateBound === '1') {
                    return;
                }

                field.dataset.translationPreviewInvalidateBound = '1';
                field.addEventListener('input', function () {
                    invalidateTranslationPreview('Der AI-Vorschlag wurde verworfen, weil sich Titel, Slug oder Kurzfassung der EN-Bearbeitung seit der Vorschau geändert haben.');
                });
            });

            if (targetEditorDefinition) {
                var targetHolder = getElement(targetEditorDefinition.holderId);

                if (targetHolder && targetHolder.dataset.translationPreviewInvalidateBound !== '1') {
                    targetHolder.dataset.translationPreviewInvalidateBound = '1';
                    targetHolder.addEventListener('input', function () {
                        invalidateTranslationPreview('Der AI-Vorschlag wurde verworfen, weil sich der EN-Editorinhalt seit der Vorschau geändert hat.');
                    });
                }
            }
        }

        function handleAiTranslation(aiTranslation) {
            var button = aiTranslation && aiTranslation.buttonId ? getElement(aiTranslation.buttonId) : null;

            if (!aiTranslation || !button) {
                return;
            }

            button.addEventListener('click', function () {
                clearNotice();
                clearPreviewPanel();
                setButtonBusy(button, true, 'Übersetze …');

                Promise.all([
                    ensureEditorSaved(aiTranslation.sourceEditorKey),
                    ensureEditorSaved(aiTranslation.targetEditorKey)
                ]).then(function (savedStates) {
                    var sourceData = savedStates[0];
                    var requestBody = buildTranslationPayload(aiTranslation, sourceData);

                    return requestJson(aiTranslation.endpointUrl, requestBody).then(function (response) {
                        var result = response.data || {};
                        var translation = result.translation || {};
                        var shouldConfirm = result.preview_required || result.result_mode === 'preview' || targetAlreadyHasContent(aiTranslation);
                        var resolvedOutput = resolveTranslationOutput(aiTranslation, translation);
                        var warningText = Array.isArray(result.warnings) && result.warnings.length > 0
                            ? ' Hinweise: ' + result.warnings.join(' · ')
                            : '';

                        if (!response.ok || !result.success) {
                            throw new Error((result && result.error) ? result.error : 'AI-Übersetzung konnte nicht verarbeitet werden.');
                        }

                        if (shouldConfirm) {
                            showNotice('info', (result.message || 'AI-Übersetzung wurde erzeugt.') + ' Bitte Vorschau prüfen und Änderungen nur bei Bedarf übernehmen.' + warningText);

                            renderTranslationPreview(aiTranslation, result, resolvedOutput, function () {
                                applyResolvedTranslation(aiTranslation, resolvedOutput).then(function () {
                                    showNotice('success', (result.message || 'AI-Übersetzung wurde in die EN-Bearbeitung übernommen.') + warningText);
                                }).catch(function (error) {
                                    showNotice('danger', (error && error.message) ? error.message : 'AI-Übersetzung konnte nicht in die EN-Bearbeitung übernommen werden.');
                                });
                            }, function () {
                                clearPreviewPanel();
                                showNotice('info', 'AI-Vorschlag wurde verworfen. Die aktuelle EN-Bearbeitung blieb unverändert.' + warningText);
                            });

                            return;
                        }

                        return applyResolvedTranslation(aiTranslation, resolvedOutput).then(function () {
                            showNotice('success', (result.message || 'AI-Übersetzung wurde in die EN-Bearbeitung übernommen.') + warningText);
                        });
                    });
                }).catch(function (error) {
                    showNotice('danger', (error && error.message) ? error.message : 'AI-Übersetzung konnte nicht verarbeitet werden.');
                }).finally(function () {
                    setButtonBusy(button, false);
                });
            });
        }

        (Array.isArray(config.editors) ? config.editors : []).forEach(function (definition) {
            editorDefinitions[definition.key] = definition;

            if (!definition.lazy) {
                bindEditor(definition);
            }

            if (definition.lazy && definition.activateButtonId) {
                var trigger = getElement(definition.activateButtonId);
                if (trigger) {
                    trigger.addEventListener('click', function () {
                        var targetInput = getElement(definition.inputId);
                        var initialCopy = config.initialCopyOnFirstActivate || null;
                        var lazyBindingPromise;

                        if (initialCopy
                            && !suppressInitialCopyForKeys[definition.key]
                            && definition.key === initialCopy.targetKey
                            && targetInput
                            && isEditorInputEmpty(targetInput)) {
                            lazyBindingPromise = ensureEditorSaved(initialCopy.sourceKey).then(function (sourceData) {
                                targetInput.value = JSON.stringify(normalizeEditorData(sourceData));
                                emitChangeEvents(targetInput);
                                bindEditor(definition);
                                return ensureEditorReady(definition.key);
                            }).catch(function () {
                                bindEditor(definition);
                                return ensureEditorReady(definition.key);
                            });

                            trackPendingLazyBinding(definition.key, lazyBindingPromise);
                            return;
                        }

                        lazyBindingPromise = Promise.resolve().then(function () {
                            bindEditor(definition);
                            return ensureEditorReady(definition.key);
                        });

                        trackPendingLazyBinding(definition.key, lazyBindingPromise);
                    });
                }
            }
        });

        if (config.copyAction) {
            handleCopyAction(config.copyAction);
        }

        if (config.aiTranslation) {
            registerPreviewInvalidation(config.aiTranslation);
            handleAiTranslation(config.aiTranslation);
        }

        form.addEventListener('click', function (event) {
            var target = event && event.target && typeof event.target.closest === 'function'
                ? event.target.closest('button, input[type="submit"], input[type="image"]')
                : null;

            if (isFormSubmitter(target)) {
                pendingSubmitter = target;
            }
        }, true);

        form.addEventListener('submit', function (event) {
            var keys = Object.keys(editorDefinitions);
            var submitter = event && event.submitter ? event.submitter : pendingSubmitter;

            if (nativeSubmitPending) {
                return;
            }

            if (keys.length === 0) {
                pendingSubmitter = null;
                return;
            }

            event.preventDefault();

            if (submitLocked) {
                return;
            }

            clearNotice();

            submitLocked = true;

            Promise.all(keys.map(function (key) {
                return saveEditorContent(key, false);
            })).then(function () {
                submitLocked = false;
                dispatchValidatedSubmit(submitter);
            }).catch(function (error) {
                var failedDefinition = error && error.editorDefinition ? error.editorDefinition : null;
                var message = error && error.message
                    ? error.message
                    : 'Der Editor-Inhalt konnte nicht gespeichert werden. Bitte Eingaben prüfen und erneut versuchen.';

                if (failedDefinition && failedDefinition.activateButtonId) {
                    activateTargetPane(failedDefinition.activateButtonId, failedDefinition.key);
                }

                if (typeof console !== 'undefined' && typeof console.error === 'function') {
                    console.error('Editor.js-Speichern vor Formular-Submit fehlgeschlagen.', error);
                }

                submitLocked = false;
                pendingSubmitter = null;
                showNotice('danger', message);
            });
        });
    }

    function waitForEditorJsCore(editorJsConfig) {
        var hasEditorDefinitions = !!(editorJsConfig && Array.isArray(editorJsConfig.editors) && editorJsConfig.editors.length > 0);
        var readyPromise = window.cmsEditorJsCoreReady;

        if (!hasEditorDefinitions) {
            return Promise.resolve(true);
        }

        if (typeof window.EditorJS === 'function') {
            return Promise.resolve(true);
        }

        if (!readyPromise || typeof readyPromise.then !== 'function') {
            return Promise.resolve(false);
        }

        return readyPromise.then(function () {
            return typeof window.EditorJS === 'function';
        }).catch(function (error) {
            if (typeof console !== 'undefined' && typeof console.error === 'function') {
                console.error('[cms-editor] EditorJS core readiness failed.', error);
            }

            return false;
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var uiConfig = parseJsonInput('contentEditorUiConfig', null);
        var seoConfig = parseJsonInput('contentEditorSeoConfig', null);
        var editorJsConfig = parseJsonInput('contentEditorEditorJsConfig', null);

        if (!uiConfig) {
            return;
        }

        initUnsavedChangesGuard(uiConfig);
        enforceAccordionDefaults();
        initUi(uiConfig);
        initSeo(seoConfig);
        waitForEditorJsCore(editorJsConfig).then(function () {
            initEditorJs(editorJsConfig);
            initLanguageTabCompleteness(editorJsConfig);
        }, function () {
            initEditorJs(editorJsConfig);
            initLanguageTabCompleteness(editorJsConfig);
        });
    });
})();
