'use strict';

(function () {
    function isSafeLinkHref(href) {
        const value = String(href || '').trim();

        if (!value) {
            return false;
        }

        return /^(https?:|mailto:|tel:|\/|#|\.\/|\.\.\/)/i.test(value);
    }

    function parseHtmlDocument(html) {
        return new DOMParser().parseFromString(String(html || ''), 'text/html');
    }

    function serializeNode(node) {
        if (!node) {
            return '';
        }

        if (node.nodeType === Node.TEXT_NODE) {
            return escapeHtml(node.textContent || '');
        }

        if (node.nodeType !== Node.ELEMENT_NODE) {
            return '';
        }

        const element = /** @type {HTMLElement} */ (node);
        const tagName = element.tagName.toLowerCase();
        const attributes = Array.from(element.attributes).map((attribute) => ` ${attribute.name}="${escapeHtml(attribute.value)}"`).join('');

        if (tagName === 'br') {
            return `<br${attributes}>`;
        }

        return `<${tagName}${attributes}>${serializeChildNodes(element)}</${tagName}>`;
    }

    function serializeChildNodes(node) {
        return Array.from(node.childNodes || []).map((child) => serializeNode(child)).join('');
    }

    function clearElement(element) {
        if (!element) {
            return;
        }

        while (element.firstChild) {
            element.removeChild(element.firstChild);
        }
    }

    function createElement(tagName, options, children) {
        const element = document.createElement(tagName);
        const config = options && typeof options === 'object' ? options : {};
        const childList = Array.isArray(children) ? children : (typeof children === 'undefined' ? [] : [children]);

        if (config.className) {
            element.className = config.className;
        }

        if (config.text) {
            element.textContent = config.text;
        }

        if (config.attributes && typeof config.attributes === 'object') {
            Object.entries(config.attributes).forEach(([key, value]) => {
                if (value === null || typeof value === 'undefined') {
                    return;
                }

                if (value === true) {
                    element.setAttribute(key, '');
                    return;
                }

                element.setAttribute(key, String(value));
            });
        }

        if (config.dataset && typeof config.dataset === 'object') {
            Object.entries(config.dataset).forEach(([key, value]) => {
                if (value === null || typeof value === 'undefined') {
                    return;
                }

                element.dataset[key] = String(value);
            });
        }

        childList.forEach((child) => {
            if (child === null || typeof child === 'undefined') {
                return;
            }

            if (typeof child === 'string') {
                element.appendChild(document.createTextNode(child));
                return;
            }

            element.appendChild(child);
        });

        return element;
    }

    function sanitizeInlineHtml(html) {
        if (!html || typeof html !== 'string') {
            return '';
        }

        const root = parseHtmlDocument(html).body;
        const allowedTags = new Set(['A', 'B', 'STRONG', 'I', 'EM', 'U', 'S', 'MARK', 'CODE', 'BR', 'SUB', 'SUP', 'SPAN']);
        const blockedTags = new Set(['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT', 'EMBED', 'FORM', 'INPUT', 'BUTTON', 'TEXTAREA', 'SELECT', 'OPTION', 'NOSCRIPT', 'SVG', 'MATH']);

        function sanitizeNode(node) {
            if (!node || node.nodeType !== Node.ELEMENT_NODE) {
                return;
            }

            const element = /** @type {HTMLElement} */ (node);
            const tagName = element.tagName.toUpperCase();

            if (blockedTags.has(tagName)) {
                element.remove();
                return;
            }

            Array.from(element.childNodes).forEach(sanitizeNode);

            if (!allowedTags.has(tagName)) {
                const parent = element.parentNode;

                if (!parent) {
                    return;
                }

                while (element.firstChild) {
                    parent.insertBefore(element.firstChild, element);
                }

                parent.removeChild(element);
                return;
            }

            Array.from(element.attributes).forEach((attribute) => {
                const attributeName = attribute.name.toLowerCase();

                if (tagName === 'A' && (attributeName === 'href' || attributeName === 'target' || attributeName === 'rel')) {
                    return;
                }

                element.removeAttribute(attribute.name);
            });

            if (tagName === 'A') {
                const href = String(element.getAttribute('href') || '').trim();
                const target = String(element.getAttribute('target') || '').trim();

                if (!isSafeLinkHref(href)) {
                    element.removeAttribute('href');
                }

                if (target === '_blank') {
                    element.setAttribute('rel', 'noopener noreferrer');
                } else {
                    element.removeAttribute('target');
                    element.removeAttribute('rel');
                }
            }
        }

        Array.from(root.childNodes).forEach(sanitizeNode);

        return serializeChildNodes(root);
    }

    function htmlToBlocks(html) {
        if (!html || typeof html !== 'string') {
            return [];
        }

        const blockedRootTags = new Set(['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'option', 'noscript', 'svg', 'math']);
        const root = parseHtmlDocument(html).body;
        const blocks = [];

        Array.from(root.childNodes).forEach((node) => {
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

            if (blockedRootTags.has(tag)) {
                return;
            }

            if (/^h[1-6]$/.test(tag)) {
                blocks.push({
                    type: 'header',
                    data: {
                        text: sanitizeInlineHtml(serializeChildNodes(element)),
                        level: Number.parseInt(tag.substring(1), 10),
                    },
                });
                return;
            }

            if (tag === 'p') {
                const text = sanitizeInlineHtml(serializeChildNodes(element)).trim();
                if (text) {
                    blocks.push({ type: 'paragraph', data: { text } });
                }
                return;
            }

            if (tag === 'blockquote') {
                blocks.push({
                    type: 'quote',
                    data: {
                        text: sanitizeInlineHtml(serializeChildNodes(element)),
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

            const fallback = sanitizeInlineHtml(serializeChildNodes(element)).trim();
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
            content: sanitizeInlineHtml(serializeChildNodes(clone)).trim(),
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

    function resolveUploadContext(context) {
        if (typeof context === 'function') {
            try {
                return resolveUploadContext(context());
            } catch (_error) {
                return {};
            }
        }

        return context && typeof context === 'object' ? context : {};
    }

    function appendUploadContext(formData, uploadContext) {
        const context = resolveUploadContext(uploadContext);

        [
            ['content_type', context.contentType],
            ['content_slug', context.contentSlug],
            ['content_slug_fallback', context.contentSlugFallback],
            ['content_title', context.contentTitle],
            ['content_title_fallback', context.contentTitleFallback],
            ['draft_key', context.draftKey],
            ['is_new', context.isNew ? '1' : '0'],
        ].forEach(function (entry) {
            if (typeof entry[1] === 'undefined' || entry[1] === null || entry[1] === '') {
                return;
            }

            formData.append(entry[0], String(entry[1]));
        });
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

    function inferImageExtension(file) {
        const mimeType = file && typeof file.type === 'string'
            ? file.type.toLowerCase()
            : '';

        switch (mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                return 'jpg';
            case 'image/gif':
                return 'gif';
            case 'image/webp':
                return 'webp';
            case 'image/avif':
                return 'avif';
            case 'image/bmp':
                return 'bmp';
            default:
                return 'png';
        }
    }

    function normalizeUploadFile(file) {
        if (!file || typeof file !== 'object') {
            return file;
        }

        const currentName = typeof file.name === 'string' ? file.name.trim() : '';
        if (currentName !== '' && /\.[a-z0-9]+$/i.test(currentName)) {
            return file;
        }

        const normalizedName = 'clipboard-image-' + Date.now() + '.' + inferImageExtension(file);

        if (typeof File === 'function' && file instanceof Blob) {
            try {
                return new File([file], normalizedName, {
                    type: typeof file.type === 'string' && file.type !== '' ? file.type : 'application/octet-stream',
                    lastModified: Date.now(),
                });
            } catch (_error) {
                // Fallback weiter unten.
            }
        }

        try {
            Object.defineProperty(file, 'name', {
                configurable: true,
                value: normalizedName,
            });
        } catch (_error) {
            // Manche Browser erlauben das Überschreiben von Blob.name nicht.
        }

        return file;
    }

    function withCacheBuster(url) {
        const separator = url.includes('?') ? '&' : '?';
        return `${url}${separator}cb=${Date.now()}`;
    }

    function normalizeInternalMediaUrl(url) {
        const rawUrl = String(url || '').trim();
        if (rawUrl === '') {
            return '';
        }

        try {
            const parsedUrl = new URL(rawUrl, window.location.origin);
            const path = String(parsedUrl.pathname || '');
            const isInternalMediaPath = path === '/media-file' || path.startsWith('/uploads/');

            if (!isInternalMediaPath) {
                return parsedUrl.href;
            }

            return `${path}${parsedUrl.search}${parsedUrl.hash}`;
        } catch (_error) {
            return rawUrl;
        }
    }

    function normalizeUploadPayload(payload) {
        if (!payload || typeof payload !== 'object') {
            return payload;
        }

        if (payload.file && typeof payload.file === 'object' && payload.file.url) {
            payload.file.url = normalizeInternalMediaUrl(payload.file.url);
        }

        return payload;
    }

    function settleImageToolUploadState(tool, url) {
        if (!tool || !tool.ui || !tool.ui.nodes) {
            return;
        }

        const ui = tool.ui;
        const imageEl = ui.nodes.imageEl;
        const preloader = ui.nodes.imagePreloader;

        if (!imageEl || typeof url !== 'string' || url.trim() === '') {
            return;
        }

        let settled = false;
        let retried = false;
        let timeoutId = null;

        function clearPendingTimeout() {
            if (timeoutId !== null) {
                window.clearTimeout(timeoutId);
                timeoutId = null;
            }
        }

        function markFilled() {
            if (settled) {
                return;
            }

            settled = true;
            clearPendingTimeout();
            if (typeof ui.toggleStatus === 'function') {
                ui.toggleStatus('filled');
            }

            if (preloader) {
                preloader.style.backgroundImage = '';
            }
        }

        function markFallbackFilled() {
            if (settled) {
                return;
            }

            settled = true;
            clearPendingTimeout();
            if (typeof console !== 'undefined' && typeof console.warn === 'function') {
                console.warn('Editor.js Bild wurde hochgeladen, aber der Load-State musste fallbackartig finalisiert werden.', url);
            }

            if (typeof ui.toggleStatus === 'function') {
                ui.toggleStatus('filled');
            }

            if (preloader) {
                preloader.style.backgroundImage = '';
            }
        }

        function retryLoad() {
            if (retried) {
                markFallbackFilled();
                return;
            }

            retried = true;
            window.setTimeout(function () {
                if (settled) {
                    return;
                }

                try {
                    imageEl.src = withCacheBuster(url);
                } catch (_error) {
                    markFallbackFilled();
                }
            }, 180);
        }

        timeoutId = window.setTimeout(function () {
            markFallbackFilled();
        }, 1800);

        if (String(imageEl.tagName || '').toUpperCase() === 'VIDEO') {
            if (typeof imageEl.readyState === 'number' && imageEl.readyState >= 2) {
                markFilled();
                return;
            }

            imageEl.addEventListener('loadeddata', markFilled, { once: true });
            imageEl.addEventListener('error', retryLoad, { once: true });
            return;
        }

        if (typeof imageEl.complete === 'boolean' && imageEl.complete && Number(imageEl.naturalWidth || 0) > 0) {
            markFilled();
            return;
        }

        imageEl.addEventListener('load', markFilled, { once: true });
        imageEl.addEventListener('error', retryLoad, { once: true });

        window.setTimeout(function () {
            if (settled) {
                return;
            }

            if (typeof imageEl.complete === 'boolean' && imageEl.complete && Number(imageEl.naturalWidth || 0) > 0) {
                markFilled();
            }
        }, 0);

        window.setTimeout(function () {
            if (!settled && typeof imageEl.complete === 'boolean' && imageEl.complete && Number(imageEl.naturalWidth || 0) > 0) {
                markFilled();
            }
        }, 350);
    }

    function uploadEditorImageFile(uploadUrl, csrfToken, file, uploadContext) {
        const normalizedFile = normalizeUploadFile(file);
        const formData = new FormData();
        formData.append('action', 'upload_image');
        formData.append('image', normalizedFile);
        appendUploadContext(formData, uploadContext);

        return fetchJson(uploadUrl, {
            method: 'POST',
            headers: buildHeaders(csrfToken),
            credentials: 'same-origin',
            body: formData,
        }).then(function (payload) {
            if (!payload || Number(payload.success) !== 1 || !payload.file || !payload.file.url) {
                throw new Error(payload && payload.message ? payload.message : 'Bild-Upload fehlgeschlagen.');
            }

            return normalizeUploadPayload(payload);
        });
    }

    function fetchEditorImageByUrl(uploadUrl, csrfToken, remoteUrl, uploadContext) {
        const context = resolveUploadContext(uploadContext);

        return fetchJson(buildQueryUrl(uploadUrl, 'fetch_image'), {
            method: 'POST',
            headers: Object.assign({ 'Content-Type': 'application/json; charset=utf-8' }, buildHeaders(csrfToken)),
            credentials: 'same-origin',
            body: buildRequestPayload(Object.assign({ url: remoteUrl }, {
                content_type: context.contentType || '',
                content_slug: context.contentSlug || '',
                content_slug_fallback: context.contentSlugFallback || '',
                content_title: context.contentTitle || '',
                content_title_fallback: context.contentTitleFallback || '',
                draft_key: context.draftKey || '',
                is_new: context.isNew ? '1' : '0'
            }), csrfToken),
        }).then(function (payload) {
            if (!payload || Number(payload.success) !== 1 || !payload.file || !payload.file.url) {
                throw new Error(payload && payload.message ? payload.message : 'Bild konnte nicht geladen werden.');
            }

            return normalizeUploadPayload(payload);
        });
    }

    function loadEditorImageLibrary(uploadUrl, csrfToken) {
        return fetchJson(buildQueryUrl(uploadUrl, 'list_images'), {
            method: 'GET',
            headers: buildHeaders(csrfToken),
            credentials: 'same-origin',
        }).then(function (payload) {
            return Array.isArray(payload.items)
                ? payload.items.map(function (item) {
                    if (!item || typeof item !== 'object') {
                        return item;
                    }

                    if (item.url) {
                        item.url = normalizeInternalMediaUrl(item.url);
                    }

                    return item;
                })
                : [];
        });
    }

    const editorImagePickerRegistry = new Map();

    function createEditorImagePicker(uploadUrl, csrfToken, uploadContext) {
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

        const title = createElement('h3', {
            className: 'cms-editor-image-picker__title',
            text: 'Bild auswählen',
            attributes: { id: 'cms-editor-image-picker-title' },
        });
        const subtitle = createElement('p', {
            className: 'cms-editor-image-picker__subtitle',
            text: 'Bestehende Uploads nutzen oder direkt ein neues Bild hochladen.',
        });
        const closeButton = createElement('button', {
            className: 'cms-editor-image-picker__close',
            text: '×',
            attributes: { type: 'button', 'aria-label': 'Schließen' },
        });
        const searchEl = createElement('input', {
            className: 'cms-editor-image-picker__search',
            attributes: {
                type: 'search',
                placeholder: 'Bilder durchsuchen …',
                'aria-label': 'Bilder durchsuchen',
            },
        });
        const uploadButton = createElement('button', {
            className: 'cms-editor-image-picker__upload',
            text: 'Bild hochladen',
            attributes: { type: 'button' },
        });
        const uploadInput = createElement('input', {
            className: 'cms-editor-image-picker__upload-input',
            attributes: { type: 'file', accept: 'image/*', hidden: true },
        });
        const statusEl = createElement('div', {
            className: 'cms-editor-image-picker__status',
            text: 'Lade Bilder …',
            attributes: { 'aria-live': 'polite' },
        });
        const gridEl = createElement('div', { className: 'cms-editor-image-picker__grid' });
        const cancelButton = createElement('button', {
            className: 'cms-editor-image-picker__cancel',
            text: 'Abbrechen',
            attributes: { type: 'button' },
        });
        const dialog = createElement('div', {
            className: 'cms-editor-image-picker__dialog',
            attributes: {
                role: 'dialog',
                'aria-modal': 'true',
                'aria-labelledby': 'cms-editor-image-picker-title',
            },
        }, [
            createElement('div', { className: 'cms-editor-image-picker__header' }, [
                createElement('div', {}, [title, subtitle]),
                closeButton,
            ]),
            createElement('div', { className: 'cms-editor-image-picker__toolbar' }, [
                searchEl,
                uploadButton,
                uploadInput,
            ]),
            statusEl,
            gridEl,
            createElement('div', { className: 'cms-editor-image-picker__footer' }, [cancelButton]),
        ]);

        overlay.appendChild(dialog);

        document.body.appendChild(overlay);

        const closeButtons = [closeButton, cancelButton];

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

            clearElement(gridEl);

            if (filteredItems.length === 0) {
                setStatus('Keine Bilder gefunden.', false);
                return;
            }

            setStatus(filteredItems.length + (filteredItems.length === 1 ? ' Bild gefunden' : ' Bilder gefunden'), false);

            filteredItems.forEach(function (item) {
                const url = String(item.url || '');
                const name = String(item.name || 'Bild');
                const path = String(item.path || '');
                const image = createElement('img', {
                    attributes: { src: url, alt: name, loading: 'lazy' },
                });
                const button = createElement('button', {
                    className: 'cms-editor-image-picker__item',
                    attributes: { type: 'button' },
                    dataset: { url: url, name: name, path: path },
                }, [
                    createElement('span', { className: 'cms-editor-image-picker__thumb' }, [image]),
                    createElement('span', { className: 'cms-editor-image-picker__meta' }, [
                        createElement('span', { className: 'cms-editor-image-picker__name', text: name }),
                        createElement('span', { className: 'cms-editor-image-picker__path', text: path }),
                    ]),
                ]);

                gridEl.appendChild(button);
            });
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
                uploadEditorImageFile(uploadUrl, csrfToken, file, uploadContext).then(function (payload) {
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

    function createImageToolConfig(imageClass, uploadUrl, csrfToken, cropperTuneKey, uploadContext) {
        if (!imageClass || !uploadUrl) {
            return null;
        }

        const imagePicker = createEditorImagePicker(uploadUrl, csrfToken, uploadContext);

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
                            return uploadEditorImageFile(uploadUrl, csrfToken, file, uploadContext);
                        },
                        uploadByUrl: function (remoteUrl) {
                            return fetchEditorImageByUrl(uploadUrl, csrfToken, remoteUrl, uploadContext);
                        },
                    },
                };

                nextOptions.config = mergedConfig;
                super(nextOptions);
                this.cmsImagePicker = imagePicker;
                this.cmsImageLibraryButton = null;
            }

            uploadFile(file) {
                return super.uploadFile(normalizeUploadFile(file));
            }

            async onPaste(event) {
                if (event && event.type === 'file' && event.detail && event.detail.file) {
                    this.uploadFile(event.detail.file);
                    return;
                }

                if (event && event.type === 'tag' && event.detail && event.detail.data) {
                    const source = String(event.detail.data.src || '');
                    if (/^blob:/i.test(source)) {
                        try {
                            const response = await fetch(source);
                            const blob = await response.blob();
                            this.uploadFile(blob);
                        } catch (error) {
                            this.uploadingFailed(error);
                        }
                        return;
                    }
                }

                return super.onPaste(event);
            }

            onUpload(payload) {
                super.onUpload(payload);

                if (payload && Number(payload.success) === 1 && payload.file && payload.file.url) {
                    settleImageToolUploadState(this, String(payload.file.url));
                }
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
                        return uploadEditorImageFile(uploadUrl, csrfToken, file, uploadContext);
                    },
                    uploadByUrl: function (remoteUrl) {
                        return fetchEditorImageByUrl(uploadUrl, csrfToken, remoteUrl, uploadContext);
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

    function createCmsEditor(holderId, initialData, uploadUrl, csrfToken, options) {
        const holder = document.getElementById(holderId);
        const resolved = buildResolvedRegistry();
        const spacerToolClass = createSpacerToolClass();
        const editorOptions = options && typeof options === 'object' ? options : {};
        const getUploadContext = typeof editorOptions.getUploadContext === 'function'
            ? editorOptions.getUploadContext
            : function () {
                return editorOptions.uploadContext || {};
            };
        let autosaveTimer = null;

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
            image: createImageToolConfig(resolved.image, uploadUrl, csrfToken, cropperTuneKey, getUploadContext),
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
            onChange: function () {
                if (typeof editorOptions.onChange !== 'function') {
                    return;
                }

                if (autosaveTimer !== null) {
                    window.clearTimeout(autosaveTimer);
                }

                autosaveTimer = window.setTimeout(function () {
                    editor.save().then(function (data) {
                        editorOptions.onChange(normalizeInitialData(data));
                    }).catch(function (error) {
                        if (typeof console !== 'undefined' && typeof console.warn === 'function') {
                            console.warn('Editor.js change sync fehlgeschlagen.', error);
                        }
                    });
                }, 180);
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
