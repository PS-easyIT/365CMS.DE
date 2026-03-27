/**
 * Admin Media Integrations
 *
 * Aktiviert die native 365CMS-Medienbibliothek, Upload-Queues
 * und den internen Bild-/Logo-Picker im Admin.
 */
(function () {
    'use strict';

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

    function setElementText(element, text) {
        if (element) {
            element.textContent = text;
        }
    }

    function setElementHidden(element, hidden) {
        if (element) {
            element.hidden = hidden;
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

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function fetchJson(url, options) {
        return fetch(url, options).then(function (response) {
            return response.json().catch(function () {
                return {};
            }).then(function (payload) {
                if (!response.ok) {
                    var error = new Error(payload && payload.error ? payload.error : 'Anfrage fehlgeschlagen.');
                    error.payload = payload;
                    throw error;
                }

                return payload;
            });
        });
    }

    function setFormSubmitting(form, isSubmitting) {
        if (!form) {
            return;
        }

        form.querySelectorAll('button, input[type="submit"], input[type="file"]').forEach(function (element) {
            element.disabled = isSubmitting;
            element.setAttribute('aria-disabled', isSubmitting ? 'true' : 'false');
        });
    }

    function renderUploadResult(container, type, message) {
        if (!container) {
            return;
        }

        var item = document.createElement('div');
        item.className = 'list-group-item d-flex justify-content-between align-items-start gap-3';

        var content = document.createElement('div');
        var title = document.createElement('div');
        title.className = 'fw-semibold text-' + (type === 'success' ? 'green' : 'danger');
        title.textContent = String(message.title || '');

        var detail = document.createElement('div');
        detail.className = 'text-secondary small';
        detail.textContent = String(message.detail || '');

        content.appendChild(title);
        content.appendChild(detail);

        var badge = document.createElement('span');
        badge.className = 'badge ' + (type === 'success' ? 'bg-green-lt' : 'bg-red-lt');
        badge.textContent = type === 'success' ? 'OK' : 'Fehler';

        item.appendChild(content);
        item.appendChild(badge);
        container.appendChild(item);
    }

    function initNativeUploader() {
        var form = document.getElementById('uploadForm');
        var input = document.getElementById('uploadFiles');
        if (!form || !input || typeof window.fetch !== 'function') {
            return;
        }

        var statusElement = form.querySelector('[data-upload-status]');
        var resultsElement = form.querySelector('[data-upload-results]');

        form.addEventListener('submit', function (event) {
            var files = Array.prototype.slice.call(input.files || []);
            var uploadUrl = input.dataset.uploadUrl || '';
            var uploadPath = input.dataset.uploadPath || '';
            var csrfToken = input.dataset.csrfToken || '';

            if (!files.length || !uploadUrl || !csrfToken) {
                return;
            }

            event.preventDefault();

            if (resultsElement) {
                resultsElement.innerHTML = '';
                setElementHidden(resultsElement, false);
            }

            setElementHidden(statusElement, false);
            setElementText(statusElement, 'Upload läuft …');
            setFormSubmitting(form, true);

            var successCount = 0;
            var errorCount = 0;
            var sequence = Promise.resolve();

            files.forEach(function (file) {
                sequence = sequence.then(function () {
                    var formData = new FormData();
                    formData.append('file', file, file.name);
                    formData.append('path', uploadPath);
                    formData.append('csrf_token', csrfToken);

                    return fetchJson(uploadUrl, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    }).then(function (payload) {
                        if (payload && payload.new_token) {
                            csrfToken = String(payload.new_token);
                            input.dataset.csrfToken = csrfToken;
                        }

                        successCount += 1;
                        renderUploadResult(resultsElement, 'success', {
                            title: file.name,
                            detail: payload && payload.path ? payload.path : 'Datei erfolgreich hochgeladen.'
                        });
                    }).catch(function (error) {
                        errorCount += 1;
                        renderUploadResult(resultsElement, 'danger', {
                            title: file.name,
                            detail: error && error.message ? error.message : 'Upload fehlgeschlagen.'
                        });
                    });
                });
            });

            sequence.finally(function () {
                setFormSubmitting(form, false);

                if (successCount > 0 && errorCount === 0) {
                    setElementText(statusElement, successCount + ' Datei(en) erfolgreich hochgeladen. Bibliothek wird aktualisiert …');
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 700);
                    return;
                }

                setElementText(statusElement, successCount + ' erfolgreich, ' + errorCount + ' fehlgeschlagen.');
                showMessage(errorCount > 0 ? 'danger' : 'success', successCount > 0
                    ? 'Upload abgeschlossen.'
                    : 'Upload fehlgeschlagen.');
            });
        });
    }

    function initMediaPickers() {
        var modalElement = document.getElementById('settingsMediaPickerModal');
        var openButtons = document.querySelectorAll('[data-open-media-picker]');
        var clearButtons = document.querySelectorAll('[data-clear-media-input]');
        var activeContext = null;
        var pickerState = {
            items: [],
            loaded: false,
            query: ''
        };

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

        var pickerContainer = modalElement.querySelector('[data-media-picker-modal]');
        var titleElement = modalElement.querySelector('[data-media-picker-title]');
        var gridElement = modalElement.querySelector('[data-media-picker-grid]');
        var statusElement = modalElement.querySelector('[data-media-picker-status]');
        var searchElement = modalElement.querySelector('[data-media-picker-search]');
        var bootstrapModal = window.bootstrap ? window.bootstrap.Modal.getOrCreateInstance(modalElement) : null;
        var apiUrl = pickerContainer ? (pickerContainer.dataset.apiUrl || '') : '';
        var csrfToken = pickerContainer ? (pickerContainer.dataset.csrfToken || '') : '';

        function renderPickerItems(items) {
            if (!gridElement) {
                return;
            }

            clearElement(gridElement);

            if (!items.length) {
                var emptyColumn = document.createElement('div');
                emptyColumn.className = 'col-12';

                var emptyAlert = document.createElement('div');
                emptyAlert.className = 'alert alert-secondary mb-0';
                emptyAlert.textContent = 'Keine passenden Medien gefunden.';

                emptyColumn.appendChild(emptyAlert);
                gridElement.appendChild(emptyColumn);
                return;
            }

            items.forEach(function (item) {
                var itemUrl = String(item && item.url ? item.url : '');
                var itemName = String(item && item.name ? item.name : 'Datei');
                var itemPath = String(item && item.path ? item.path : '');

                var column = document.createElement('div');
                column.className = 'col-sm-6 col-lg-4';

                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'card card-link w-100 text-start';
                button.setAttribute('data-media-picker-select', '1');
                button.setAttribute('data-media-url', itemUrl);

                var cardBody = document.createElement('div');
                cardBody.className = 'card-body';

                var preview = document.createElement('div');
                preview.className = 'ratio ratio-4x3 mb-3 rounded overflow-hidden bg-light d-flex align-items-center justify-content-center';

                var image = document.createElement('img');
                image.src = itemUrl;
                image.alt = itemName;
                image.className = 'w-100 h-100';
                image.style.objectFit = 'cover';

                var name = document.createElement('div');
                name.className = 'fw-semibold text-truncate';
                name.textContent = itemName;

                var path = document.createElement('div');
                path.className = 'text-secondary small text-break';
                path.textContent = itemPath;

                preview.appendChild(image);
                cardBody.appendChild(preview);
                cardBody.appendChild(name);
                cardBody.appendChild(path);
                button.appendChild(cardBody);
                column.appendChild(button);
                gridElement.appendChild(column);
            });
        }

        function filterPickerItems() {
            var query = pickerState.query;
            var items = pickerState.items;

            if (!query) {
                renderPickerItems(items);
                setElementText(statusElement, items.length + ' Medien verfügbar');
                return;
            }

            var filteredItems = items.filter(function (item) {
                var haystack = String(item.name || '').toLowerCase() + ' ' + String(item.path || '').toLowerCase();
                return haystack.indexOf(query) !== -1;
            });

            renderPickerItems(filteredItems);
            setElementText(statusElement, filteredItems.length + ' Treffer');
        }

        function loadPickerItems() {
            if (!pickerContainer || !apiUrl || !csrfToken) {
                if (gridElement) {
                    gridElement.innerHTML = '<div class="col-12"><div class="alert alert-warning mb-0">Medienpicker konnte nicht geladen werden.</div></div>';
                }
                setElementText(statusElement, 'Konfiguration unvollständig');
                return Promise.resolve();
            }

            setElementText(statusElement, 'Lade Medien …');

            return fetchJson(apiUrl + '?action=list_images', {
                method: 'GET',
                headers: {
                    'X-CSRF-Token': csrfToken
                },
                credentials: 'same-origin'
            }).then(function (payload) {
                pickerState.items = Array.isArray(payload.items) ? payload.items : [];
                pickerState.loaded = true;
                filterPickerItems();
            }).catch(function (error) {
                if (gridElement) {
                    clearElement(gridElement);
                    var errorColumn = document.createElement('div');
                    errorColumn.className = 'col-12';

                    var errorAlert = document.createElement('div');
                    errorAlert.className = 'alert alert-warning mb-0';
                    errorAlert.textContent = String(error && error.message ? error.message : 'Medien konnten nicht geladen werden.');

                    errorColumn.appendChild(errorAlert);
                    gridElement.appendChild(errorColumn);
                }
                setElementText(statusElement, 'Laden fehlgeschlagen');
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

                if (!pickerState.loaded) {
                    loadPickerItems();
                } else {
                    filterPickerItems();
                }

                if (bootstrapModal) {
                    if (bootstrapModal) {
                        bootstrapModal.show();
                    }
                }
            });
        });

        if (searchElement) {
            searchElement.addEventListener('input', function () {
                pickerState.query = String(searchElement.value || '').trim().toLowerCase();
                filterPickerItems();
            });
        }

        if (gridElement) {
            gridElement.addEventListener('click', function (event) {
                var button = event.target.closest('[data-media-picker-select="1"]');
                if (!button || !activeContext || !activeContext.input) {
                    return;
                }

                var selectedValue = normalizeMediaValue(button.getAttribute('data-media-url') || '');
                if (!selectedValue) {
                    return;
                }

                activeContext.input.value = selectedValue;
                updateLinkedPreview(activeContext.input, activeContext.previewId || '');

                if (bootstrapModal) {
                    bootstrapModal.hide();
                }
            });
        }

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
        initNativeUploader();
        initMediaPickers();
        initMediaLibraryActions();
        initMediaCategoryActions();
    });
})();