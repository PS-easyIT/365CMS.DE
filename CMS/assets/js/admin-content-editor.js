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

    function getElement(id) {
        return id ? document.getElementById(id) : null;
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

    function initUi(config) {
        var form = getElement(config.formId);
        var removeButton = getElement(config.removeButtonId);
        var imageInput = getElement(config.imageInputId);
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
        var toggleButtons = document.querySelectorAll(config.languageToggleSelector || '');
        var languagePanes = document.querySelectorAll(config.languagePaneSelector || '');
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
                if (previewContainer) {
                    previewContainer.innerHTML = '';
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

        if (!config || typeof window.createCmsEditor !== 'function') {
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

        function safeParseEditorInput(input) {
            if (!input || !input.value) {
                return { blocks: [] };
            }

            try {
                return JSON.parse(input.value);
            } catch (_error) {
                return { blocks: [] };
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
            if (!panel) {
                return;
            }

            panel.innerHTML = '';
            panel.className = 'card border-primary mb-3 d-none';
        }

        function stripHtmlPreview(value) {
            var element = document.createElement('div');

            element.innerHTML = String(value || '');
            return String(element.textContent || element.innerText || '');
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

            panel.innerHTML = '';
            panel.className = 'card border-primary mb-3';

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

        function activateTargetPane(buttonId) {
            var button = getElement(buttonId);
            if (button) {
                button.click();
            }
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
                    holder.innerHTML = '';
                }
            }

            delete editors[key];
        }

        function bindEditor(definition, forceRecreate) {
            var holder = getElement(definition.holderId);
            var input = getElement(definition.inputId);

            if (!definition || !holder || !input) {
                return null;
            }

            if (editors[definition.key] && !forceRecreate) {
                return editors[definition.key];
            }

            if (forceRecreate) {
                destroyEditor(definition.key);
            }

            editors[definition.key] = {
                input: input,
                instance: window.createCmsEditor(definition.holderId, input.value || '', config.mediaUploadUrl, config.csrfToken)
            };

            return editors[definition.key];
        }

        function ensureEditorSaved(key) {
            var entry = editors[key];
            var definition = getDefinition(key);
            var input = definition ? getElement(definition.inputId) : null;

            if (!entry) {
                return Promise.resolve(normalizeEditorData(safeParseEditorInput(input)));
            }

            return entry.instance.save().then(function (output) {
                if (entry.input) {
                    entry.input.value = JSON.stringify(output);
                    emitChangeEvents(entry.input);
                }

                return normalizeEditorData(output);
            }).catch(function () {
                return normalizeEditorData(safeParseEditorInput(input));
            });
        }

        function applyEditorData(key, data) {
            var definition = getDefinition(key);
            var input = definition ? getElement(definition.inputId) : null;
            var normalizedData = normalizeEditorData(data);

            if (!input) {
                return;
            }

            input.value = JSON.stringify(normalizedData);
            emitChangeEvents(input);

            if (definition) {
                bindEditor(definition, true);
            }
        }

        function copyFieldValue(sourceId, targetId) {
            if (!sourceId || !targetId) {
                return;
            }

            setFieldValue(targetId, getFieldValue(sourceId));
        }

        function handleCopyAction(copyAction) {
            var button = copyAction && copyAction.buttonId ? getElement(copyAction.buttonId) : null;

            if (!copyAction) {
                return;
            }

            if (button) {
                button.addEventListener('click', function () {
                    clearNotice();
                    setButtonBusy(button, true, 'Kopiere …');

                    ensureEditorSaved(copyAction.sourceEditorKey).then(function (sourceData) {
                        copyFieldValue(copyAction.sourceTitleId, copyAction.targetTitleId);
                        copyFieldValue(copyAction.sourceSlugId, copyAction.targetSlugId);
                        copyFieldValue(copyAction.sourceExcerptId, copyAction.targetExcerptId);

                        activateTargetPane(copyAction.targetPaneButtonId);
                        applyEditorData(copyAction.targetEditorKey, sourceData);
                        showNotice('success', 'DE-Inhalt wurde in die EN-Bearbeitung übernommen.');
                    }).catch(function () {
                        showNotice('danger', 'DE-Inhalt konnte nicht in die EN-Bearbeitung kopiert werden.');
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

        function requestJson(url, body) {
            return fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body.toString()
            }).then(function (response) {
                return response.text().then(function (text) {
                    var data = null;

                    try {
                        data = JSON.parse(text);
                    } catch (_error) {
                        data = null;
                    }

                    return {
                        ok: response.ok,
                        status: response.status,
                        data: data,
                        rawText: text
                    };
                });
            });
        }

        function targetAlreadyHasContent(aiTranslation) {
            var targetDefinition = getDefinition(aiTranslation.targetEditorKey);
            var input = targetDefinition ? getElement(targetDefinition.inputId) : null;

            return getFieldValue(aiTranslation.targetTitleId) !== ''
                || getFieldValue(aiTranslation.targetSlugId) !== ''
                || getFieldValue(aiTranslation.targetExcerptId) !== ''
                || (input && !isEditorInputEmpty(input));
        }

        function applyResolvedTranslation(aiTranslation, resolvedOutput) {
            activateTargetPane(aiTranslation.targetPaneButtonId);

            if (aiTranslation.targetTitleId) {
                setFieldValue(aiTranslation.targetTitleId, resolvedOutput.title);
            }

            if (aiTranslation.targetSlugId) {
                setFieldValue(aiTranslation.targetSlugId, resolvedOutput.slug);
            }

            if (aiTranslation.targetExcerptId) {
                setFieldValue(aiTranslation.targetExcerptId, resolvedOutput.excerpt);
            }

            applyEditorData(aiTranslation.targetEditorKey, resolvedOutput.contentData || { blocks: [] });
            clearPreviewPanel();
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

                ensureEditorSaved(aiTranslation.sourceEditorKey).then(function (sourceData) {
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
                                applyResolvedTranslation(aiTranslation, resolvedOutput);
                                showNotice('success', (result.message || 'AI-Übersetzung wurde in die EN-Bearbeitung übernommen.') + warningText);
                            }, function () {
                                clearPreviewPanel();
                                showNotice('info', 'AI-Vorschlag wurde verworfen. Die aktuelle EN-Bearbeitung blieb unverändert.' + warningText);
                            });

                            return;
                        }

                        applyResolvedTranslation(aiTranslation, resolvedOutput);
                        showNotice('success', (result.message || 'Mock-Übersetzung wurde in die EN-Bearbeitung übernommen.') + warningText);
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

                        if (initialCopy
                            && definition.key === initialCopy.targetKey
                            && targetInput
                            && isEditorInputEmpty(targetInput)) {
                            ensureEditorSaved(initialCopy.sourceKey).then(function (sourceData) {
                                targetInput.value = JSON.stringify(normalizeEditorData(sourceData));
                                emitChangeEvents(targetInput);
                                bindEditor(definition);
                            }).catch(function () {
                                bindEditor(definition);
                            });
                            return;
                        }

                        bindEditor(definition);
                    });
                }
            }
        });

        if (config.copyAction) {
            handleCopyAction(config.copyAction);
        }

        if (config.aiTranslation) {
            handleAiTranslation(config.aiTranslation);
        }

        form.addEventListener('submit', function (event) {
            var keys = Object.keys(editors);

            if (submitLocked || keys.length === 0) {
                return;
            }

            submitLocked = true;
            event.preventDefault();

            Promise.all(keys.map(function (key) {
                return editors[key].instance.save().then(function (output) {
                    editors[key].input.value = JSON.stringify(output);
                }).catch(function () {
                    return null;
                });
            })).finally(function () {
                form.submit();
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var uiConfig = parseJsonInput('contentEditorUiConfig', null);
        var seoConfig = parseJsonInput('contentEditorSeoConfig', null);
        var editorJsConfig = parseJsonInput('contentEditorEditorJsConfig', null);

        if (!uiConfig) {
            return;
        }

        initUi(uiConfig);
        initSeo(seoConfig);
        initEditorJs(editorJsConfig);
    });
})();
