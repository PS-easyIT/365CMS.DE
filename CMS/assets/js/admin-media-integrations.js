/**
 * Admin Media Integrations
 *
 * Aktiviert FilePond und elFinder in der Admin-Medienverwaltung.
 */
(function () {
    'use strict';

    var elfinderDependenciesPromise = null;

    function normalizeMediaValue(value) {
        if (!value) {
            return '';
        }

        if (/^https?:\/\//i.test(value)) {
            try {
                var url = new URL(value, window.location.origin);
                if (url.origin === window.location.origin) {
                    return url.pathname + url.search + url.hash;
                }
            } catch (error) {
                return value;
            }
        }

        return value;
    }

    function createPreviewMarkup(previewElement, value) {
        var variant = previewElement.dataset.previewVariant || 'logo';
        previewElement.innerHTML = '';

        if (!value) {
            previewElement.hidden = true;
            return;
        }

        var image = document.createElement('img');
        image.src = value;
        image.alt = variant === 'favicon' ? 'Favicon Vorschau' : 'Website-Logo Vorschau';
        image.style.border = '1px solid var(--tblr-border-color)';
        image.style.background = '#fff';

        if (variant === 'favicon') {
            previewElement.className = 'mt-2 d-flex align-items-center gap-2';
            image.width = 32;
            image.height = 32;
            image.style.borderRadius = '8px';
            image.style.padding = '4px';
            image.style.objectFit = 'contain';

            var code = document.createElement('code');
            code.textContent = value;
            previewElement.appendChild(image);
            previewElement.appendChild(code);
        } else {
            previewElement.className = 'mt-2';
            image.style.maxHeight = '48px';
            image.style.maxWidth = '220px';
            image.style.borderRadius = '6px';
            image.style.padding = '6px';
            previewElement.appendChild(image);
        }

        previewElement.hidden = false;
    }

    function updateLinkedPreview(input, previewId) {
        var previewElement = null;

        if (previewId) {
            previewElement = document.getElementById(previewId);
        }

        if (!previewElement && input && input.id) {
            previewElement = document.querySelector('[data-media-preview][data-input-id="' + input.id + '"]');
        }

        if (!previewElement) {
            return;
        }

        createPreviewMarkup(previewElement, input.value.trim());
    }

    function showMessage(type, message) {
        if (typeof window.cmsAlert === 'function') {
            window.cmsAlert(type, message);
            return;
        }
        console[type === 'danger' ? 'error' : 'log'](message);
    }

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            var existing = document.querySelector('script[src="' + src + '"]');
            if (existing) {
                if (existing.dataset.loaded === '1') {
                    resolve();
                    return;
                }
                existing.addEventListener('load', function () { resolve(); }, { once: true });
                existing.addEventListener('error', reject, { once: true });
                return;
            }

            var script = document.createElement('script');
            script.src = src;
            script.defer = true;
            script.addEventListener('load', function () {
                script.dataset.loaded = '1';
                resolve();
            }, { once: true });
            script.addEventListener('error', reject, { once: true });
            document.head.appendChild(script);
        });
    }

    function loadElfinderDependencies(config) {
        if (elfinderDependenciesPromise) {
            return elfinderDependenciesPromise;
        }

        elfinderDependenciesPromise = Promise.resolve()
            .then(function () {
                if (window.jQuery) {
                    return;
                }
                return loadScript(config.jqueryScript || '');
            })
            .then(function () {
                if (window.jQuery && window.jQuery.ui) {
                    return;
                }
                return loadScript(config.jqueryUiScript || '');
            })
            .then(function () {
                if (window.jQuery && window.jQuery.fn && window.jQuery.fn.elfinder) {
                    return;
                }
                return loadScript(config.elfinderScript || '');
            });

        return elfinderDependenciesPromise;
    }

    function buildConnectorUrl(connectorUrl, csrfToken) {
        return connectorUrl + (connectorUrl.indexOf('?') === -1 ? '?' : '&') + 'csrf_token=' + encodeURIComponent(csrfToken);
    }

    function initFilePond() {
        var input = document.querySelector('[data-filepond-upload]');
        if (!input || typeof window.FilePond === 'undefined') {
            return;
        }

        var csrfToken = input.dataset.csrfToken || '';
        var uploadPath = input.dataset.uploadPath || '';
        var uploadUrl = input.dataset.uploadUrl || '';
        var reloadTimer = null;

        var pond = window.FilePond.create(input, {
            allowMultiple: true,
            credits: false,
            labelIdle: 'Dateien hierher ziehen oder <span class="filepond--label-action">auswählen</span>',
            server: {
                process: function (fieldName, file, metadata, load, error, progress, abort) {
                    var formData = new FormData();
                    formData.append('filepond', file, file.name);
                    formData.append('path', uploadPath);
                    formData.append('csrf_token', csrfToken);

                    var controller = new AbortController();

                    fetch(uploadUrl, {
                        method: 'POST',
                        body: formData,
                        signal: controller.signal
                    }).then(function (response) {
                        return response.json().then(function (data) {
                            return {
                                ok: response.ok,
                                data: data
                            };
                        });
                    }).then(function (result) {
                        if (result.data && result.data.new_token) {
                            csrfToken = result.data.new_token;
                            input.dataset.csrfToken = csrfToken;
                        }

                        if (!result.ok) {
                            error((result.data && result.data.error) || 'Upload fehlgeschlagen');
                            showMessage('danger', (result.data && result.data.error) || 'Upload fehlgeschlagen.');
                            return;
                        }

                        progress(true, file.size, file.size);
                        load((result.data && result.data.id) || file.name);
                    }).catch(function (err) {
                        if (err && err.name === 'AbortError') {
                            return;
                        }
                        error('Upload fehlgeschlagen');
                        showMessage('danger', 'Upload fehlgeschlagen.');
                    });

                    return {
                        abort: function () {
                            controller.abort();
                            abort();
                        }
                    };
                }
            }
        });

        pond.on('processfile', function (fileError) {
            if (fileError) {
                return;
            }

            window.clearTimeout(reloadTimer);
            reloadTimer = window.setTimeout(function () {
                window.location.reload();
            }, 700);
        });
    }

    function initElfinder() {
        var container = document.querySelector('[data-elfinder]');
        if (!container) {
            return;
        }

        var connectorUrl = container.dataset.connectorUrl || '';
        var csrfToken = container.dataset.csrfToken || '';
        var jqueryScript = container.dataset.jqueryScript || '';
        var jqueryUiScript = container.dataset.jqueryUiScript || '';
        var elfinderScript = container.dataset.elfinderScript || '';

        if (!connectorUrl || !jqueryScript || !jqueryUiScript || !elfinderScript) {
            showMessage('danger', 'elFinder konnte nicht initialisiert werden.');
            return;
        }

        loadElfinderDependencies({
            jqueryScript: jqueryScript,
            jqueryUiScript: jqueryUiScript,
            elfinderScript: elfinderScript
        })
            .then(function () {
                if (!(window.jQuery && window.jQuery.fn && window.jQuery.fn.elfinder)) {
                    throw new Error('elFinder UI nicht verfügbar');
                }

                var url = buildConnectorUrl(connectorUrl, csrfToken);
                window.jQuery(container).elfinder({
                    url: url,
                    lang: 'de',
                    resizable: false,
                    height: container.offsetHeight || 720,
                    customData: {
                        csrf_token: csrfToken
                    }
                });
            })
            .catch(function () {
                container.innerHTML = '<div class="alert alert-warning" role="alert">elFinder konnte nicht geladen werden. Bitte lokale Vendor-Dateien prüfen.</div>';
            });
    }

    function initMediaPickers() {
        var modalElement = document.getElementById('settingsMediaPickerModal');
        var openButtons = document.querySelectorAll('[data-open-media-picker]');
        var clearButtons = document.querySelectorAll('[data-clear-media-input]');
        var activeContext = null;
        var pickerContainer;
        var pickerInstance;

        document.querySelectorAll('[data-media-preview]').forEach(function (previewElement) {
            var inputId = previewElement.dataset.inputId || '';
            var input = inputId ? document.getElementById(inputId) : null;
            if (input) {
                updateLinkedPreview(input);
            }
        });

        if (!modalElement || !openButtons.length) {
            clearButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    var input = document.getElementById(button.dataset.targetInput || '');
                    if (!input) {
                        return;
                    }
                    input.value = '';
                    updateLinkedPreview(input, button.dataset.previewId || '');
                });
            });
            return;
        }

        pickerContainer = modalElement.querySelector('[data-elfinder-picker]');
        var titleElement = modalElement.querySelector('[data-media-picker-title]');
        var bootstrapModal = window.bootstrap ? window.bootstrap.Modal.getOrCreateInstance(modalElement) : null;

        function initPicker() {
            if (!pickerContainer || pickerInstance) {
                return Promise.resolve();
            }

            var connectorUrl = pickerContainer.dataset.connectorUrl || '';
            var csrfToken = pickerContainer.dataset.csrfToken || '';
            var jqueryScript = pickerContainer.dataset.jqueryScript || '';
            var jqueryUiScript = pickerContainer.dataset.jqueryUiScript || '';
            var elfinderScript = pickerContainer.dataset.elfinderScript || '';

            if (!connectorUrl || !jqueryScript || !jqueryUiScript || !elfinderScript) {
                pickerContainer.innerHTML = '<div class="alert alert-warning" role="alert">Medienpicker konnte nicht geladen werden.</div>';
                return Promise.resolve();
            }

            return loadElfinderDependencies({
                jqueryScript: jqueryScript,
                jqueryUiScript: jqueryUiScript,
                elfinderScript: elfinderScript
            }).then(function () {
                var url = buildConnectorUrl(connectorUrl, csrfToken);
                pickerInstance = window.jQuery(pickerContainer).elfinder({
                    url: url,
                    lang: 'de',
                    resizable: false,
                    height: pickerContainer.offsetHeight || 640,
                    customData: {
                        csrf_token: csrfToken
                    },
                    getFileCallback: function (file) {
                        if (!activeContext || !activeContext.input) {
                            return;
                        }

                        var selectedValue = '';
                        if (typeof file === 'string') {
                            selectedValue = file;
                        } else if (file && typeof file === 'object') {
                            selectedValue = file.url || file.path || '';
                        }

                        selectedValue = normalizeMediaValue(selectedValue);
                        activeContext.input.value = selectedValue;
                        updateLinkedPreview(activeContext.input, activeContext.previewId || '');

                        if (bootstrapModal) {
                            bootstrapModal.hide();
                        }
                    },
                    commandsOptions: {
                        getfile: {
                            onlyURL: false,
                            multiple: false,
                            folders: false,
                            oncomplete: 'close'
                        }
                    }
                }).elfinder('instance');
            }).catch(function () {
                pickerContainer.innerHTML = '<div class="alert alert-warning" role="alert">Medienpicker konnte nicht geladen werden.</div>';
            });
        }

        openButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById(button.dataset.targetInput || '');
                if (!input) {
                    return;
                }

                activeContext = {
                    input: input,
                    previewId: button.dataset.previewId || ''
                };

                if (titleElement) {
                    titleElement.textContent = button.dataset.pickerTitle || 'Datei auswählen';
                }

                initPicker().then(function () {
                    if (bootstrapModal) {
                        bootstrapModal.show();
                    }
                });
            });
        });

        clearButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById(button.dataset.targetInput || '');
                if (!input) {
                    return;
                }

                input.value = '';
                updateLinkedPreview(input, button.dataset.previewId || '');
            });
        });

        modalElement.addEventListener('hidden.bs.modal', function () {
            activeContext = null;
        });
    }

    function parseConfig(id) {
        var element = document.getElementById(id);
        if (!element) {
            return null;
        }

        try {
            return JSON.parse(element.textContent || '{}');
        } catch (error) {
            console.error('Media-Konfiguration konnte nicht gelesen werden.', error);
            return null;
        }
    }

    function initMediaLibraryActions() {
        var config = parseConfig('media-library-config') || {};
        var deleteFormId = config.deleteFormId || 'deleteMediaForm';
        var deletePathFieldId = config.deletePathFieldId || 'deleteMediaPath';
        var memberFolderConfirmMessage = config.memberFolderConfirmMessage || 'Diesen Ordner wirklich öffnen?';
        var deleteForm = document.getElementById(deleteFormId);
        var deletePathField = document.getElementById(deletePathFieldId);

        document.querySelectorAll('[data-member-folder-confirm="1"]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                var targetUrl = link.getAttribute('data-confirm-url') || link.getAttribute('href') || '';
                if (!targetUrl) {
                    return;
                }

                event.preventDefault();
                if (window.confirm(memberFolderConfirmMessage)) {
                    window.location.href = targetUrl;
                }
            });
        });

        if (!deleteForm || !deletePathField) {
            return;
        }

        function submitDelete(path) {
            deletePathField.value = path;

            if (typeof deleteForm.requestSubmit === 'function') {
                deleteForm.requestSubmit();
                return;
            }

            deleteForm.submit();
        }

        document.querySelectorAll('.js-media-delete').forEach(function (button) {
            button.addEventListener('click', function () {
                var path = button.dataset.deletePath || '';
                var name = button.dataset.deleteName || 'Element';
                var itemType = button.dataset.deleteType || 'Element';
                var message = name + ' wirklich löschen?';

                if (!path) {
                    return;
                }

                if (typeof cmsConfirm === 'function') {
                    cmsConfirm({
                        title: itemType + ' löschen',
                        message: message,
                        confirmText: 'Löschen',
                        confirmClass: 'btn-danger',
                        onConfirm: function () {
                            submitDelete(path);
                        }
                    });
                    return;
                }

                if (window.confirm(message)) {
                    submitDelete(path);
                }
            });
        });
    }

    function initMediaCategoryActions() {
        var config = parseConfig('media-categories-config') || {};
        var deleteFormId = config.deleteFormId || 'deleteCatForm';
        var deleteSlugFieldId = config.deleteSlugFieldId || 'deleteCatSlug';
        var deleteTitle = config.deleteTitle || 'Kategorie löschen';
        var deleteConfirmText = config.deleteConfirmText || 'Löschen';
        var deleteConfirmClass = config.deleteConfirmClass || 'btn-danger';
        var messageTemplate = config.deleteMessageTemplate || 'Kategorie {name} wirklich löschen?';
        var deleteForm = document.getElementById(deleteFormId);
        var deleteSlugField = document.getElementById(deleteSlugFieldId);

        if (!deleteForm || !deleteSlugField) {
            return;
        }

        function submitDelete(slug) {
            deleteSlugField.value = slug;

            if (typeof deleteForm.requestSubmit === 'function') {
                deleteForm.requestSubmit();
                return;
            }

            deleteForm.submit();
        }

        document.querySelectorAll('.js-media-category-delete').forEach(function (button) {
            button.addEventListener('click', function () {
                var slug = button.dataset.deleteSlug || '';
                var name = button.dataset.deleteName || 'Kategorie';
                var message = messageTemplate.replace('{name}', name);

                if (!slug) {
                    return;
                }

                if (typeof cmsConfirm === 'function') {
                    cmsConfirm({
                        title: deleteTitle,
                        message: message,
                        confirmText: deleteConfirmText,
                        confirmClass: deleteConfirmClass,
                        onConfirm: function () {
                            submitDelete(slug);
                        }
                    });
                    return;
                }

                if (window.confirm(message)) {
                    submitDelete(slug);
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initFilePond();
        initElfinder();
        initMediaPickers();
        initMediaLibraryActions();
        initMediaCategoryActions();
    });
})();