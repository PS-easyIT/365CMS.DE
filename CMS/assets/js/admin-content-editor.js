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
                var currentStatus = statusMap[statusSelect.value] || statusMap.draft || null;
                if (currentStatus) {
                    statusBadge.className = currentStatus.className || statusBadge.className;
                    statusBadge.textContent = currentStatus.label || statusBadge.textContent;
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

            if (!definition || !holder || !input || editors[definition.key]) {
                return;
            }

            editors[definition.key] = {
                input: input,
                instance: window.createCmsEditor(definition.holderId, input.value || '', config.mediaUploadUrl, config.csrfToken)
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
