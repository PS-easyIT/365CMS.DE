'use strict';

(function () {
    function htmlToBlocks(html) {
        if (!html || typeof html !== 'string') {
            return [];
        }

        const container = document.createElement('div');
        container.innerHTML = html;
        const blocks = [];

        Array.from(container.childNodes).forEach((node) => {
            if (node.nodeType === Node.TEXT_NODE) {
                const text = node.textContent ? node.textContent.trim() : '';
                if (text) {
                    blocks.push({
                        type: 'paragraph',
                        data: { text: text.replace(/\n/g, '<br>') },
                    });
                }
                return;
            }

            if (node.nodeType !== Node.ELEMENT_NODE) {
                return;
            }

            const element = /** @type {HTMLElement} */ (node);
            const tag = element.tagName.toLowerCase();

            if (/^h[1-6]$/.test(tag)) {
                blocks.push({
                    type: 'header',
                    data: {
                        text: element.innerHTML,
                        level: Number.parseInt(tag.substring(1), 10),
                    },
                });
                return;
            }

            if (tag === 'p') {
                const text = element.innerHTML.trim();
                if (text) {
                    blocks.push({ type: 'paragraph', data: { text } });
                }
                return;
            }

            if (tag === 'blockquote') {
                blocks.push({
                    type: 'quote',
                    data: {
                        text: element.innerHTML,
                        caption: '',
                        alignment: 'left',
                    },
                });
                return;
            }

            if (tag === 'pre') {
                const code = element.textContent || '';
                blocks.push({
                    type: 'code',
                    data: { code },
                });
                return;
            }

            if (tag === 'hr') {
                blocks.push({ type: 'delimiter', data: {} });
                return;
            }

            if (tag === 'ul' || tag === 'ol') {
                const items = Array.from(element.querySelectorAll(':scope > li')).map((li) => normalizeListItem(li, tag === 'ol' ? 'ordered' : 'unordered'));
                blocks.push({
                    type: 'list',
                    data: {
                        style: tag === 'ol' ? 'ordered' : 'unordered',
                        meta: tag === 'ol' ? { start: 1, counterType: 'numeric' } : {},
                        items,
                    },
                });
                return;
            }

            if (tag === 'img') {
                const src = element.getAttribute('src') || '';
                if (src) {
                    blocks.push({
                        type: 'image',
                        data: {
                            file: { url: src },
                            caption: element.getAttribute('alt') || '',
                            withBorder: false,
                            withBackground: false,
                            stretched: false,
                        },
                    });
                }
                return;
            }

            const fallback = element.innerHTML.trim();
            if (fallback) {
                blocks.push({ type: 'paragraph', data: { text: fallback } });
            }
        });

        return blocks;
    }

    function normalizeListItem(li, style) {
        const clone = li.cloneNode(true);
        const nestedLists = Array.from(clone.querySelectorAll(':scope > ul, :scope > ol'));
        nestedLists.forEach((child) => child.remove());

        const children = [];
        Array.from(li.children).forEach((child) => {
            const tag = child.tagName.toLowerCase();
            if (tag === 'ul' || tag === 'ol') {
                Array.from(child.querySelectorAll(':scope > li')).forEach((childLi) => {
                    children.push(normalizeListItem(childLi, style));
                });
            }
        });

        return {
            content: clone.innerHTML.trim(),
            meta: style === 'checklist' ? { checked: false } : {},
            items: children,
        };
    }

    function normalizeInitialData(initialData) {
        if (!initialData) {
            return { blocks: [] };
        }

        if (typeof initialData === 'object' && Array.isArray(initialData.blocks)) {
            return initialData;
        }

        if (typeof initialData === 'string') {
            const trimmed = initialData.trim();
            if (!trimmed) {
                return { blocks: [] };
            }

            try {
                const parsed = JSON.parse(trimmed);
                if (parsed && Array.isArray(parsed.blocks)) {
                    return parsed;
                }
            } catch (_error) {
                // absichtlich ignoriert, fällt auf HTML/Text-Fallback zurück
            }

            if (/<[a-z][\s\S]*>/i.test(trimmed)) {
                return { blocks: htmlToBlocks(trimmed) };
            }

            return {
                blocks: trimmed
                    .split(/\n{2,}/)
                    .map((chunk) => chunk.trim())
                    .filter(Boolean)
                    .map((chunk) => ({ type: 'paragraph', data: { text: chunk.replace(/\n/g, '<br>') } })),
            };
        }

        return { blocks: [] };
    }

    function resolveClass(names) {
        const variants = Array.isArray(names) ? names : [names];
        for (const name of variants) {
            if (typeof name === 'string' && typeof window[name] !== 'undefined') {
                return window[name];
            }
        }
        return null;
    }

    function buildHeaders(csrfToken) {
        return csrfToken ? { 'X-CSRF-Token': csrfToken } : {};
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function buildQueryUrl(uploadUrl, action) {
        const separator = uploadUrl.includes('?') ? '&' : '?';
        return `${uploadUrl}${separator}action=${encodeURIComponent(action)}`;
    }

    function buildRequestPayload(data, csrfToken) {
        const payload = { ...data };
        if (csrfToken) {
            payload.csrf_token = csrfToken;
        }
        return JSON.stringify(payload);
    }

    function fetchJson(url, options) {
        return fetch(url, options).then(function (response) {
            return response.text().then(function (bodyText) {
                let payload = {};
                if (bodyText) {
                    try {
                        payload = JSON.parse(bodyText);
                    } catch (_error) {
                        payload = { message: bodyText.trim() };
                    }
                }

                if (!response.ok) {
                    const message = payload && (payload.message || payload.error)
                        ? String(payload.message || payload.error)
                        : 'Request fehlgeschlagen.';
                    const error = new Error(message);
                    error.payload = payload;
                    error.status = response.status;
                    throw error;
                }

                return payload;
            });
        });
    }

    function uploadEditorImageFile(uploadUrl, csrfToken, file) {
        const formData = new FormData();
        formData.append('action', 'upload_image');
        formData.append('image', file);

        return fetchJson(uploadUrl, {
            method: 'POST',
            headers: buildHeaders(csrfToken),
            credentials: 'same-origin',
            body: formData,
        }).then(function (payload) {
            if (!payload || Number(payload.success) !== 1 || !payload.file || !payload.file.url) {
                throw new Error(payload && payload.message ? payload.message : 'Bild-Upload fehlgeschlagen.');
            }

            return payload;
        });
    }

    function fetchEditorImageByUrl(uploadUrl, csrfToken, remoteUrl) {
        return fetchJson(buildQueryUrl(uploadUrl, 'fetch_image'), {
            method: 'POST',
            headers: Object.assign({ 'Content-Type': 'application/json; charset=utf-8' }, buildHeaders(csrfToken)),
            credentials: 'same-origin',
            body: buildRequestPayload({ url: remoteUrl }, csrfToken),
        }).then(function (payload) {
            if (!payload || Number(payload.success) !== 1 || !payload.file || !payload.file.url) {
                throw new Error(payload && payload.message ? payload.message : 'Bild konnte nicht geladen werden.');
            }

            return payload;
        });
    }

    function loadEditorImageLibrary(uploadUrl, csrfToken) {
        return fetchJson(buildQueryUrl(uploadUrl, 'list_images'), {
            method: 'GET',
            headers: buildHeaders(csrfToken),
            credentials: 'same-origin',
        }).then(function (payload) {
            return Array.isArray(payload.items) ? payload.items : [];
        });
    }

    const editorImagePickerRegistry = new Map();

    function createEditorImagePicker(uploadUrl, csrfToken) {
        const registryKey = uploadUrl + '::' + String(csrfToken || '');
        if (editorImagePickerRegistry.has(registryKey)) {
            return editorImagePickerRegistry.get(registryKey);
        }

        let items = [];
        let filteredItems = [];
        let resolver = null;
        let rejecter = null;

        const overlay = document.createElement('div');
        overlay.className = 'cms-editor-image-picker';
        overlay.hidden = true;
        overlay.innerHTML = ''
            + '<div class="cms-editor-image-picker__dialog" role="dialog" aria-modal="true" aria-labelledby="cms-editor-image-picker-title">'
            + '  <div class="cms-editor-image-picker__header">'
            + '      <div>'
            + '          <h3 class="cms-editor-image-picker__title" id="cms-editor-image-picker-title">Bild auswählen</h3>'
            + '          <p class="cms-editor-image-picker__subtitle">Bestehende Uploads nutzen oder direkt ein neues Bild hochladen.</p>'
            + '      </div>'
            + '      <button type="button" class="cms-editor-image-picker__close" aria-label="Schließen">×</button>'
            + '  </div>'
            + '  <div class="cms-editor-image-picker__toolbar">'
            + '      <input type="search" class="cms-editor-image-picker__search" placeholder="Bilder durchsuchen …" aria-label="Bilder durchsuchen">'
            + '      <button type="button" class="cms-editor-image-picker__upload">Bild hochladen</button>'
            + '      <input type="file" class="cms-editor-image-picker__upload-input" accept="image/*" hidden>'
            + '  </div>'
            + '  <div class="cms-editor-image-picker__status" aria-live="polite">Lade Bilder …</div>'
            + '  <div class="cms-editor-image-picker__grid"></div>'
            + '  <div class="cms-editor-image-picker__footer">'
            + '      <button type="button" class="cms-editor-image-picker__cancel">Abbrechen</button>'
            + '  </div>'
            + '</div>';

        document.body.appendChild(overlay);

        const dialog = overlay.querySelector('.cms-editor-image-picker__dialog');
        const statusEl = overlay.querySelector('.cms-editor-image-picker__status');
        const gridEl = overlay.querySelector('.cms-editor-image-picker__grid');
        const searchEl = overlay.querySelector('.cms-editor-image-picker__search');
        const uploadButton = overlay.querySelector('.cms-editor-image-picker__upload');
        const uploadInput = overlay.querySelector('.cms-editor-image-picker__upload-input');
        const closeButtons = overlay.querySelectorAll('.cms-editor-image-picker__close, .cms-editor-image-picker__cancel');

        function closePicker() {
            overlay.hidden = true;
            overlay.classList.remove('is-open');
            if (searchEl) {
                searchEl.value = '';
            }
        }

        function cleanupPending() {
            resolver = null;
            rejecter = null;
        }

        function cancelPicker() {
            const currentReject = rejecter;
            closePicker();
            cleanupPending();
            if (typeof currentReject === 'function') {
                currentReject(new Error('Bildauswahl abgebrochen.'));
            }
        }

        function resolveSelection(payload) {
            const currentResolve = resolver;
            closePicker();
            cleanupPending();
            if (typeof currentResolve === 'function') {
                currentResolve(payload);
            }
        }

        function setStatus(message, isError) {
            if (!statusEl) {
                return;
            }

            statusEl.textContent = message;
            statusEl.classList.toggle('is-error', Boolean(isError));
        }

        function renderItems(nextItems) {
            filteredItems = Array.isArray(nextItems) ? nextItems : [];

            if (!gridEl) {
                return;
            }

            if (filteredItems.length === 0) {
                gridEl.innerHTML = '';
                setStatus('Keine Bilder gefunden.', false);
                return;
            }

            setStatus(filteredItems.length + (filteredItems.length === 1 ? ' Bild gefunden' : ' Bilder gefunden'), false);
            gridEl.innerHTML = filteredItems.map(function (item) {
                const url = escapeHtml(item.url || '');
                const name = escapeHtml(item.name || 'Bild');
                const path = escapeHtml(item.path || '');
                return ''
                    + '<button type="button" class="cms-editor-image-picker__item" data-url="' + url + '" data-name="' + name + '" data-path="' + path + '">'
                    + '  <span class="cms-editor-image-picker__thumb"><img src="' + url + '" alt="' + name + '" loading="lazy"></span>'
                    + '  <span class="cms-editor-image-picker__meta">'
                    + '      <span class="cms-editor-image-picker__name">' + name + '</span>'
                    + '      <span class="cms-editor-image-picker__path">' + path + '</span>'
                    + '  </span>'
                    + '</button>';
            }).join('');
        }

        function applySearch() {
            const query = searchEl ? String(searchEl.value || '').trim().toLowerCase() : '';
            if (!query) {
                renderItems(items);
                return;
            }

            renderItems(items.filter(function (item) {
                return String(item.name || '').toLowerCase().includes(query)
                    || String(item.path || '').toLowerCase().includes(query);
            }));
        }

        function refreshItems() {
            setStatus('Lade Bilder …', false);
            return loadEditorImageLibrary(uploadUrl, csrfToken).then(function (loadedItems) {
                items = loadedItems;
                applySearch();
                return items;
            }).catch(function (error) {
                console.error('Editor.js image library error:', error);
                items = [];
                renderItems([]);
                setStatus(error && error.message ? error.message : 'Bilder konnten nicht geladen werden.', true);
                return [];
            });
        }

        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) {
                cancelPicker();
            }
        });

        if (dialog) {
            dialog.addEventListener('click', function (event) {
                event.stopPropagation();
            });
        }

        closeButtons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                cancelPicker();
            });
        });

        overlay.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                cancelPicker();
            }
        });

        if (searchEl) {
            searchEl.addEventListener('input', applySearch);
        }

        if (gridEl) {
            gridEl.addEventListener('click', function (event) {
                const button = event.target.closest('.cms-editor-image-picker__item');
                if (!button) {
                    return;
                }

                resolveSelection({
                    success: 1,
                    file: {
                        url: button.getAttribute('data-url') || '',
                        name: button.getAttribute('data-name') || 'Bild',
                        size: 0,
                        extension: String(button.getAttribute('data-path') || '').split('.').pop() || '',
                    },
                });
            });
        }

        if (uploadButton && uploadInput) {
            uploadButton.addEventListener('click', function (event) {
                event.preventDefault();
                uploadInput.click();
            });

            uploadInput.addEventListener('change', function () {
                const file = this.files && this.files[0] ? this.files[0] : null;
                if (!file) {
                    return;
                }

                setStatus('Lade Bild hoch …', false);
                uploadEditorImageFile(uploadUrl, csrfToken, file).then(function (payload) {
                    resolveSelection(payload);
                    return refreshItems();
                }).catch(function (error) {
                    console.error('Editor.js image upload error:', error);
                    setStatus(error && error.message ? error.message : 'Upload fehlgeschlagen.', true);
                }).finally(function () {
                    uploadInput.value = '';
                });
            });
        }

        const picker = {
            open: function () {
                overlay.hidden = false;
                overlay.classList.add('is-open');

                return new Promise(function (resolve, reject) {
                    resolver = resolve;
                    rejecter = reject;
                    refreshItems().then(function () {
                        if (searchEl) {
                            searchEl.focus();
                        }
                    });
                });
            },
        };

        editorImagePickerRegistry.set(registryKey, picker);
        return picker;
    }

    function createSpacerToolClass() {
        const presetOptions = [15, 25, 40, 60, 75, 100];
        const spacerIcon = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v16"/><path d="M8 8l4-4l4 4"/><path d="M8 16l4 4l4-4"/></svg>';

        return class SpacerTool {
            static get toolbox() {
                return {
                    title: 'Abstand',
                    icon: spacerIcon,
                };
            }

            static get isReadOnlySupported() {
                return true;
            }

            constructor({ data, api, config, readOnly }) {
                this.api = api;
                this.readOnly = readOnly;
                this.config = config || {};
                this.presets = Array.isArray(this.config.presets) && this.config.presets.length > 0
                    ? this.config.presets
                    : presetOptions;
                this.data = this.normalizeData(data);
                this.wrapper = null;
                this.preview = null;
                this.currentBadge = null;
            }

            normalizeData(data) {
                const defaultHeight = Number.parseInt(this.config.defaultHeight, 10) || 15;
                const availablePresets = this.presets.filter((value) => Number.isInteger(value) && value > 0);
                const rawHeight = Number.parseInt(data && data.height, 10);
                const height = availablePresets.includes(rawHeight) ? rawHeight : defaultHeight;

                return {
                    height,
                    preset: `${height}px`,
                };
            }

            render() {
                this.wrapper = document.createElement('div');
                this.wrapper.className = 'editorjs-spacer-tool';

                const badge = document.createElement('span');
                badge.className = 'editorjs-spacer-tool__badge';
                this.currentBadge = badge;

                const preview = document.createElement('div');
                preview.className = 'editorjs-spacer-tool__preview';
                this.preview = preview;

                this.wrapper.appendChild(badge);
                this.wrapper.appendChild(preview);

                this.updateView();

                return this.wrapper;
            }

            renderSettings() {
                return this.presets.map((height) => ({
                    icon: spacerIcon,
                    label: `${height}px`,
                    closeOnActivate: true,
                    isActive: this.data.height === height,
                    onActivate: () => {
                        this.data = {
                            height,
                            preset: `${height}px`,
                        };
                        this.updateView();
                    },
                }));
            }

            updateView() {
                if (!this.wrapper || !this.preview) {
                    return;
                }

                this.wrapper.dataset.height = String(this.data.height);
                this.wrapper.style.setProperty('--editorjs-spacer-height', `${this.data.height}px`);
                this.preview.style.height = `${this.data.height}px`;

                if (this.currentBadge) {
                    this.currentBadge.textContent = `${this.data.height}px`;
                }
            }

            save() {
                return {
                    height: this.data.height,
                    preset: `${this.data.height}px`,
                };
            }

            validate(savedData) {
                const height = Number.parseInt(savedData && savedData.height, 10);
                return this.presets.includes(height);
            }
        };
    }

    function createImageToolConfig(imageClass, uploadUrl, csrfToken, cropperTuneKey) {
        if (!imageClass || !uploadUrl) {
            return null;
        }

        const imagePicker = createEditorImagePicker(uploadUrl, csrfToken);

        class CmsImageTool extends imageClass {
            constructor(options) {
                const nextOptions = { ...options };
                const mergedConfig = {
                    ...(options && options.config ? options.config : {}),
                    features: {
                        border: true,
                        stretch: true,
                        background: true,
                        caption: 'optional',
                    },
                    additionalRequestHeaders: buildHeaders(csrfToken),
                    additionalRequestData: csrfToken ? { csrf_token: csrfToken } : {},
                    endpoints: {
                        byFile: buildQueryUrl(uploadUrl, 'upload_image'),
                        byUrl: buildQueryUrl(uploadUrl, 'fetch_image'),
                    },
                    uploader: {
                        uploadByFile: function (file) {
                            return uploadEditorImageFile(uploadUrl, csrfToken, file);
                        },
                        uploadByUrl: function (remoteUrl) {
                            return fetchEditorImageByUrl(uploadUrl, csrfToken, remoteUrl);
                        },
                    },
                };

                nextOptions.config = mergedConfig;
                super(nextOptions);
                this.cmsImagePicker = imagePicker;
                this.cmsImageLibraryButton = null;
            }

            render() {
                const wrapper = super.render();
                if (!wrapper || this.cmsImageLibraryButton) {
                    return wrapper;
                }

                const actionBar = document.createElement('div');
                actionBar.className = 'cms-editor-image-tool__actions';

                const libraryButton = document.createElement('button');
                libraryButton.type = 'button';
                libraryButton.className = 'cms-editor-image-tool__library-button';
                libraryButton.textContent = 'Aus Mediathek wählen';
                libraryButton.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();

                    this.cmsImagePicker.open().then((payload) => {
                        if (payload && typeof this.onUpload === 'function') {
                            this.onUpload(payload);
                        }
                    }).catch(function (error) {
                        if (!error || error.message !== 'Bildauswahl abgebrochen.') {
                            console.error('Editor.js image picker error:', error);
                        }
                    });
                });

                actionBar.appendChild(libraryButton);
                wrapper.appendChild(actionBar);
                this.cmsImageLibraryButton = libraryButton;

                return wrapper;
            }
        }

        const config = {
            class: CmsImageTool,
            inlineToolbar: ['link', 'bold', 'italic'],
            config: {
                features: {
                    border: true,
                    stretch: true,
                    background: true,
                    caption: 'optional',
                },
                additionalRequestHeaders: buildHeaders(csrfToken),
                additionalRequestData: csrfToken ? { csrf_token: csrfToken } : {},
                endpoints: {
                    byFile: buildQueryUrl(uploadUrl, 'upload_image'),
                    byUrl: buildQueryUrl(uploadUrl, 'fetch_image'),
                },
                uploader: {
                    uploadByFile: function (file) {
                        return uploadEditorImageFile(uploadUrl, csrfToken, file);
                    },
                    uploadByUrl: function (remoteUrl) {
                        return fetchEditorImageByUrl(uploadUrl, csrfToken, remoteUrl);
                    },
                },
            },
        };

        if (cropperTuneKey) {
            config.tunes = [cropperTuneKey];
        }

        return config;
    }

    function createAttachesToolConfig(attachesClass, uploadUrl, csrfToken) {
        if (!attachesClass || !uploadUrl) {
            return null;
        }

        return {
            class: attachesClass,
            config: {
                endpoint: buildQueryUrl(uploadUrl, 'upload_file'),
                additionalRequestHeaders: buildHeaders(csrfToken),
            },
        };
    }

    function createLinkToolConfig(linkToolClass, uploadUrl, csrfToken) {
        if (!linkToolClass || !uploadUrl) {
            return null;
        }

        return {
            class: linkToolClass,
            config: {
                endpoint: buildQueryUrl(uploadUrl, 'fetch_link'),
                headers: buildHeaders(csrfToken),
            },
        };
    }

    function createEmbedConfig(embedClass) {
        if (!embedClass) {
            return null;
        }

        return {
            class: embedClass,
            inlineToolbar: true,
            config: {
                services: {
                    youtube: true,
                    vimeo: true,
                    codepen: true,
                    instagram: true,
                    x: true,
                    twitter: true,
                    facebook: true,
                    twitch: true,
                    coub: true,
                    miro: true,
                },
            },
        };
    }

    function createColumnsConfig(columnsClass, editorJsClass, childTools) {
        if (!columnsClass || !editorJsClass) {
            return null;
        }

        return {
            class: columnsClass,
            config: {
                EditorJsLibrary: editorJsClass,
                tools: childTools,
                minColumns: 2,
                maxColumns: 4,
            },
        };
    }

    function createAccordionConfig(accordionClass) {
        if (!accordionClass) {
            return null;
        }

        return {
            class: accordionClass,
            inlineToolbar: ['link', 'bold', 'italic', 'spoiler'],
            config: {
                levelPresets: [1, 2, 3, 4, 5],
            },
        };
    }

    function createCarouselConfig(carouselClass, uploadUrl, csrfToken) {
        if (!carouselClass) {
            return null;
        }

        const config = { class: carouselClass };
        if (uploadUrl) {
            config.config = {
                additionalRequestHeaders: buildHeaders(csrfToken),
                endpoints: {
                    byFile: buildQueryUrl(uploadUrl, 'upload_image'),
                    byUrl: buildQueryUrl(uploadUrl, 'fetch_image'),
                },
            };
        }
        return config;
    }

    function createImageGalleryConfig(galleryClass) {
        if (!galleryClass) {
            return null;
        }

        return {
            class: galleryClass,
            inlineToolbar: true,
        };
    }

    function createDrawingConfig(drawingClass) {
        if (!drawingClass) {
            return null;
        }

        return {
            class: drawingClass,
            inlineToolbar: false,
            config: {
                defaultBackground: '#ffffff',
                defaultStrokeColor: '#111827',
            },
        };
    }

    function createCropperTuneConfig(cropperTuneClass, uploadUrl, csrfToken) {
        if (!cropperTuneClass || !uploadUrl) {
            return null;
        }

        return {
            class: cropperTuneClass,
            config: {
                uploadUrl: buildQueryUrl(uploadUrl, 'upload_image'),
                headers: buildHeaders(csrfToken),
            },
        };
    }

    function pruneUnavailableTools(tools) {
        const nextTools = {};
        Object.entries(tools).forEach(([key, value]) => {
            if (!value || typeof value !== 'object') {
                return;
            }

            const toolClass = value.class || value;
            if (typeof toolClass === 'function') {
                nextTools[key] = value;
            }
        });
        return nextTools;
    }

    function createColumnChildTools(baseTools, withoutKeys) {
        const next = {};
        Object.entries(baseTools).forEach(([key, value]) => {
            if (withoutKeys.includes(key)) {
                return;
            }
            next[key] = value;
        });
        return next;
    }

    function addReadyEnhancers(editor, resolved) {
        editor.isReady.then(() => {
            if (typeof resolved.dragDrop === 'function') {
                try {
                    new resolved.dragDrop(editor);
                } catch (error) {
                    console.warn('DragDrop plugin konnte nicht initialisiert werden', error);
                }
            }

            if (typeof resolved.undo === 'function') {
                try {
                    new resolved.undo({ editor, config: { shortcuts: true } });
                } catch (error) {
                    console.warn('Undo plugin konnte nicht initialisiert werden', error);
                }
            }
        }).catch((error) => {
            console.error('Editor.js readiness error:', error);
        });
    }

    function buildResolvedRegistry() {
        return {
            editorjs: resolveClass(['EditorJS']),
            header: resolveClass(['Header']),
            paragraph: resolveClass(['Paragraph']),
            list: resolveClass(['EditorjsList', 'List']),
            quote: resolveClass(['Quote']),
            warning: resolveClass(['Warning']),
            code: resolveClass(['CodeTool', 'Code']),
            raw: resolveClass(['RawTool', 'Raw']),
            table: resolveClass(['Table']),
            inlineCode: resolveClass(['InlineCode']),
            underline: resolveClass(['Underline']),
            delimiter: resolveClass(['Delimiter']),
            image: resolveClass(['ImageTool']),
            linkTool: resolveClass(['LinkTool']),
            attaches: resolveClass(['AttachesTool']),
            embed: resolveClass(['Embed']),
            columns: resolveClass(['editorjsColumns', 'EditorJsColumns']),
            accordion: resolveClass(['AccordionBlock']),
            carousel: resolveClass(['Carousel']),
            imageGallery: resolveClass(['ImageGallery']),
            spoiler: resolveClass(['TgSpoilerEditorJS']),
            cropperTune: resolveClass(['CropperTune']),
            drawingTool: resolveClass(['DrawingTool']),
            dragDrop: resolveClass(['DragDrop']),
            undo: resolveClass(['Undo']),
        };
    }

    function createCmsEditor(holderId, initialData, uploadUrl, csrfToken) {
        const holder = document.getElementById(holderId);
        const resolved = buildResolvedRegistry();
        const spacerToolClass = createSpacerToolClass();

        if (!holder || typeof resolved.editorjs !== 'function') {
            throw new Error('EditorJS core ist nicht geladen oder Holder fehlt.');
        }

        const cropperTuneKey = resolved.cropperTune ? 'Cropper' : null;

        const baseTools = pruneUnavailableTools({
            header: {
                class: resolved.header,
                inlineToolbar: ['link', 'bold', 'italic', 'underline', 'spoiler'],
                config: { levels: [2, 3, 4, 5], defaultLevel: 2 },
                shortcut: 'CMD+SHIFT+H',
            },
            paragraph: {
                class: resolved.paragraph,
                inlineToolbar: ['link', 'bold', 'italic', 'underline', 'inlineCode', 'spoiler'],
                config: { preserveBlank: true },
            },
            list: {
                class: resolved.list,
                inlineToolbar: ['link', 'bold', 'italic', 'underline', 'spoiler'],
                config: {
                    defaultStyle: 'unordered',
                    maxLevel: 3,
                },
            },
            quote: {
                class: resolved.quote,
                inlineToolbar: ['link', 'bold', 'italic', 'spoiler'],
                config: {
                    quotePlaceholder: 'Zitat eingeben',
                    captionPlaceholder: 'Quelle / Autor',
                },
            },
            warning: {
                class: resolved.warning,
                inlineToolbar: ['link', 'bold', 'italic'],
                config: {
                    titlePlaceholder: 'Hinweis-Titel',
                    messagePlaceholder: 'Hinweistext',
                },
            },
            code: {
                class: resolved.code,
                shortcut: 'CMD+ALT+C',
            },
            raw: { class: resolved.raw },
            table: {
                class: resolved.table,
                inlineToolbar: true,
                config: { rows: 3, cols: 3 },
            },
            inlineCode: { class: resolved.inlineCode, shortcut: 'CMD+SHIFT+M' },
            underline: { class: resolved.underline, shortcut: 'CMD+U' },
            delimiter: { class: resolved.delimiter },
            spacer: {
                class: spacerToolClass,
                config: {
                    presets: [15, 25, 40, 60, 75, 100],
                    defaultHeight: 15,
                },
            },
            spoiler: resolved.spoiler ? { class: resolved.spoiler } : null,
            Cropper: createCropperTuneConfig(resolved.cropperTune, uploadUrl, csrfToken),
        });

        const childTools = createColumnChildTools(baseTools, ['columns', 'accordion', 'carousel', 'drawingTool']);

        const tools = pruneUnavailableTools({
            ...baseTools,
            image: createImageToolConfig(resolved.image, uploadUrl, csrfToken, cropperTuneKey),
            linkTool: createLinkToolConfig(resolved.linkTool, uploadUrl, csrfToken),
            attaches: createAttachesToolConfig(resolved.attaches, uploadUrl, csrfToken),
            embed: createEmbedConfig(resolved.embed),
            columns: createColumnsConfig(resolved.columns, resolved.editorjs, childTools),
            accordion: createAccordionConfig(resolved.accordion),
            carousel: createCarouselConfig(resolved.carousel, uploadUrl, csrfToken),
            imageGallery: createImageGalleryConfig(resolved.imageGallery),
            drawingTool: createDrawingConfig(resolved.drawingTool),
        });

        const editor = new resolved.editorjs({
            holder: holderId,
            data: normalizeInitialData(initialData),
            autofocus: false,
            minHeight: 320,
            placeholder: 'Inhalt schreiben … mit / öffnest du die Blockauswahl.',
            defaultBlock: 'paragraph',
            inlineToolbar: ['link', 'bold', 'italic', 'underline', 'inlineCode', 'spoiler'],
            tools,
            onReady: function () {
                addReadyEnhancers(editor, resolved);
            },
        });

        return editor;
    }

    function saveEditorToInput(editor, inputId) {
        const input = document.getElementById(inputId);
        if (!editor || !input) {
            return Promise.resolve();
        }

        return editor.save().then((data) => {
            input.value = JSON.stringify(data);
        });
    }

    window.createCmsEditor = createCmsEditor;
    window.saveEditorToInput = saveEditorToInput;
    window.cmsNormalizeEditorJsData = normalizeInitialData;
})();
