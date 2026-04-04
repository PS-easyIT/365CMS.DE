(function () {
    'use strict';

    var editorToolbarButtons = [
        { block: 'header', label: 'H2', title: 'Überschrift H2', level: 2 },
        { block: 'paragraph', label: 'Text', title: 'Textabsatz' },
        { block: 'list', label: 'Liste', title: 'Liste' },
        { block: 'image', label: 'Bild', title: 'Bild' },
        { block: 'mediaText', label: 'Medien+Text', title: 'Medien + Text' },
        { block: 'imageGallery', label: 'Gallery', title: 'Gallery', columns: 3 },
        { block: 'callout', label: 'Callout', title: 'Callout / Hinweisbox' },
        { block: 'terminal', label: 'Terminal', title: 'Terminal / Command Block' },
        { block: 'codeTabs', label: 'Code Tabs', title: 'Code Tabs' },
        { block: 'mermaid', label: 'Mermaid', title: 'Mermaid / Diagramm' },
        { block: 'apiEndpoint', label: 'API', title: 'API Endpoint Block' },
        { block: 'changelog', label: 'Changelog', title: 'Changelog / Version' },
        { block: 'prosCons', label: 'Pros/Cons', title: 'Pros / Cons' },
        { block: 'embed', label: 'Embed', title: 'Embed' },
        { block: 'columns', label: 'Spalten', title: 'Spalten' },
        { block: 'accordion', label: 'Akk.', title: 'Accordion' },
        { block: 'table', label: 'Tabelle', title: 'Tabelle' },
        { block: 'quote', label: 'Zitat', title: 'Zitat' },
        { block: 'delimiter', label: 'Trenner', title: 'Trennlinie' },
        { block: 'spacer', label: 'Abstand', title: 'Leerraum / Abstand', height: 15 }
    ];

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

    function buildToolbarButtonMarkup(config) {
        var attrs = [
            'type="button"',
            'data-block="' + String(config.block || '') + '"',
            'title="' + String(config.title || config.label || '') + '"'
        ];

        if (config.level) {
            attrs.push('data-level="' + String(config.level) + '"');
        }
        if (config.height) {
            attrs.push('data-height="' + String(config.height) + '"');
        }
        if (config.columns) {
            attrs.push('data-columns="' + String(config.columns) + '"');
        }

        return '<button ' + attrs.join(' ') + '><span>' + String(config.label || config.block || '') + '</span></button>';
    }

    function ensureLiveEditorChrome(holder, editor) {
        var wrap = holder ? holder.closest('.editorjs-wrap') : null;
        var toolbar;
        var statusbar;
        var countEl;

        if (!wrap || !editor || !editor.blocks) {
            return;
        }

        toolbar = wrap.querySelector('.editorjs-toolbar');
        if (!toolbar) {
            toolbar = document.createElement('div');
            toolbar.className = 'editorjs-toolbar';
            toolbar.innerHTML = editorToolbarButtons.map(buildToolbarButtonMarkup).join('');
            wrap.insertBefore(toolbar, holder);
        }

        statusbar = wrap.querySelector('.editorjs-statusbar');
        if (!statusbar) {
            statusbar = document.createElement('div');
            statusbar.className = 'editorjs-statusbar';
            statusbar.innerHTML = '<span class="editorjs-statusbar__hint">Tippe <kbd>/</kbd> oder nutze die Schnellbuttons für die Tech-Blöcke.</span><span class="editorjs-statusbar__count"></span>';
            if (holder.nextSibling) {
                wrap.insertBefore(statusbar, holder.nextSibling);
            } else {
                wrap.appendChild(statusbar);
            }
        }

        countEl = statusbar.querySelector('.editorjs-statusbar__count');

        toolbar.addEventListener('click', function (event) {
            var btn = event.target.closest('button[data-block]');
            var blockType;
            var blockData = {};
            var level;
            var height;
            var columns;
            var lastIndex;

            if (!btn || !editor || !editor.blocks) {
                return;
            }

            blockType = btn.getAttribute('data-block');
            level = btn.getAttribute('data-level');
            height = btn.getAttribute('data-height');
            columns = btn.getAttribute('data-columns');

            if (level) {
                blockData.level = parseInt(level, 10);
            }
            if (height) {
                blockData.height = parseInt(height, 10);
                blockData.preset = height + 'px';
            }
            if (columns) {
                blockData.columns = parseInt(columns, 10);
            }

            editor.blocks.insert(blockType, blockData);

            lastIndex = editor.blocks.getBlocksCount() - 1;
            if (editor.caret && typeof editor.caret.setToBlock === 'function') {
                editor.caret.setToBlock(lastIndex, 'start');
            }
        });

        function updateBlockCount() {
            if (!countEl || !editor || !editor.blocks) {
                return;
            }

            var count = editor.blocks.getBlocksCount();
            countEl.textContent = count + (count === 1 ? ' Block' : ' Blöcke');
        }

        editor.isReady.then(function () {
            updateBlockCount();
        });

        var intervalId = window.setInterval(updateBlockCount, 2000);
        window.setTimeout(function () {
            window.clearInterval(intervalId);
        }, 300000);
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
        var serverTimestamp = Number(config.currentTimestamp || 0);
        var perfStart = typeof window.performance !== 'undefined' && typeof window.performance.now === 'function'
            ? window.performance.now()
            : null;
        var clientStart = Date.now();

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

        function getReferenceNow() {
            if (!Number.isFinite(serverTimestamp) || serverTimestamp <= 0) {
                return Date.now();
            }

            if (perfStart !== null) {
                return serverTimestamp + Math.max(0, window.performance.now() - perfStart);
            }

            return serverTimestamp + Math.max(0, Date.now() - clientStart);
        }

        function isScheduledPublication() {
            var publishAt;

            if (!statusSelect || statusSelect.value !== 'published') {
                return false;
            }

            publishAt = resolvePublishDate();
            return publishAt instanceof Date && !Number.isNaN(publishAt.getTime()) && publishAt.getTime() > getReferenceNow();
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
        var submitLocked = false;

        if (!config || typeof window.createCmsEditor !== 'function') {
            return;
        }

        form = getElement(config.formId);
        if (!form) {
            return;
        }

        function bindEditor(definition) {
            var holder = getElement(definition.holderId);
            var input = getElement(definition.inputId);
            var editorInstance;

            if (!definition || !holder || !input || editors[definition.key]) {
                return;
            }

            editorInstance = window.createCmsEditor(definition.holderId, input.value || '', config.mediaUploadUrl, config.csrfToken);
            ensureLiveEditorChrome(holder, editorInstance);

            editors[definition.key] = {
                input: input,
                instance: editorInstance
            };
        }

        (Array.isArray(config.editors) ? config.editors : []).forEach(function (definition) {
            if (!definition.lazy) {
                bindEditor(definition);
            }

            if (definition.lazy && definition.activateButtonId) {
                var trigger = getElement(definition.activateButtonId);
                if (trigger) {
                    trigger.addEventListener('click', function () {
                        bindEditor(definition);
                    });
                }
            }
        });

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
