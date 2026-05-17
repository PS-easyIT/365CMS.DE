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

    const recentClipboardImages = [];
    const RECENT_CLIPBOARD_IMAGE_TTL_MS = 4000;
    const RECENT_CLIPBOARD_IMAGE_LIMIT = 6;

    function isImageBlobCandidate(file) {
        return Boolean(
            file
            && typeof file === 'object'
            && file instanceof Blob
            && String(file.type || '').toLowerCase().startsWith('image/')
        );
    }

    function pruneRecentClipboardImages() {
        const cutoff = Date.now() - RECENT_CLIPBOARD_IMAGE_TTL_MS;

        for (let index = recentClipboardImages.length - 1; index >= 0; index -= 1) {
            if (Number(recentClipboardImages[index].capturedAt || 0) < cutoff) {
                recentClipboardImages.splice(index, 1);
            }
        }
    }

    function collectClipboardImageFiles(clipboardData) {
        if (!clipboardData) {
            return [];
        }

        const candidates = [];
        const pushCandidate = function (file) {
            if (!isImageBlobCandidate(file)) {
                return;
            }

            const duplicate = candidates.some(function (candidate) {
                return String(candidate.name || '') === String(file.name || '')
                    && Number(candidate.size || 0) === Number(file.size || 0)
                    && String(candidate.type || '') === String(file.type || '')
                    && Number(candidate.lastModified || 0) === Number(file.lastModified || 0);
            });

            if (!duplicate) {
                candidates.push(file);
            }
        };

        Array.from(clipboardData.files || []).forEach(pushCandidate);

        Array.from(clipboardData.items || []).forEach(function (item) {
            if (!item || item.kind !== 'file' || !String(item.type || '').toLowerCase().startsWith('image/')) {
                return;
            }

            if (typeof item.getAsFile === 'function') {
                pushCandidate(item.getAsFile());
            }
        });

        return candidates;
    }

    function rememberClipboardImageFiles(event) {
        const clipboardData = event && event.clipboardData ? event.clipboardData : null;
        const files = collectClipboardImageFiles(clipboardData);

        if (files.length === 0) {
            return;
        }

        pruneRecentClipboardImages();

        files.forEach(function (file) {
            recentClipboardImages.push({
                capturedAt: Date.now(),
                file: file,
            });
        });

        if (recentClipboardImages.length > RECENT_CLIPBOARD_IMAGE_LIMIT) {
            recentClipboardImages.splice(0, recentClipboardImages.length - RECENT_CLIPBOARD_IMAGE_LIMIT);
        }
    }

    function hasMeaningfulImageFileName(file) {
        const name = file && typeof file.name === 'string' ? file.name.trim() : '';

        if (name === '' || !/\.[a-z0-9]+$/i.test(name)) {
            return false;
        }

        return !/^(?:image|pasted[\s_-]*image|clipboard[\s_-]*image)(?:[\s_-]*\d+)?\.[a-z0-9]+$/i.test(name);
    }

    function takeRecentClipboardImageFile(preferredMimeType) {
        pruneRecentClipboardImages();

        if (recentClipboardImages.length === 0) {
            return null;
        }

        const normalizedMimeType = String(preferredMimeType || '').toLowerCase().trim();
        let matchIndex = -1;

        if (normalizedMimeType !== '') {
            matchIndex = recentClipboardImages.findIndex(function (entry) {
                return String(entry && entry.file && entry.file.type || '').toLowerCase() === normalizedMimeType;
            });
        }

        if (matchIndex < 0) {
            matchIndex = recentClipboardImages.length - 1;
        }

        const match = recentClipboardImages.splice(matchIndex, 1)[0] || null;
        return match && match.file ? match.file : null;
    }

    function resolveClipboardUploadFile(file) {
        const recentClipboardFile = takeRecentClipboardImageFile(file && file.type ? file.type : '');

        if (!recentClipboardFile) {
            return file;
        }

        if (!file || typeof file !== 'object') {
            return recentClipboardFile;
        }

        if (hasMeaningfulImageFileName(file)) {
            return file;
        }

        return recentClipboardFile;
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

    function isInternalMediaUrl(url) {
        const normalizedUrl = normalizeInternalMediaUrl(url);

        return normalizedUrl.startsWith('/media-file') || normalizedUrl.startsWith('/uploads/');
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

    async function createUploadFileFromImageSource(imageSource, fileNamePrefix) {
        const normalizedSource = String(imageSource || '').trim();
        if (normalizedSource === '') {
            throw new Error('Bildquelle fehlt.');
        }

        let blob;

        try {
            const response = await fetch(normalizedSource);
            blob = await response.blob();
        } catch (_error) {
            throw new Error('Bildquelle konnte nicht gelesen werden.');
        }

        if (!(blob instanceof Blob) || !String(blob.type || '').toLowerCase().startsWith('image/')) {
            throw new Error('Ungültige Bildquelle.');
        }

        const baseName = String(fileNamePrefix || 'editor-image').trim() || 'editor-image';
        const fileName = `${baseName}-${Date.now()}.${inferImageExtension(blob)}`;

        if (typeof File === 'function') {
            try {
                return normalizeUploadFile(new File([blob], fileName, {
                    type: typeof blob.type === 'string' && blob.type !== '' ? blob.type : 'application/octet-stream',
                    lastModified: Date.now(),
                }));
            } catch (_error) {
                // Fallback weiter unten.
            }
        }

        try {
            Object.defineProperty(blob, 'name', {
                configurable: true,
                value: fileName,
            });
        } catch (_error) {
            // Manche Browser erlauben das Überschreiben von Blob.name nicht.
        }

        return normalizeUploadFile(blob);
    }

    async function uploadEditorInlineImage(uploadUrl, csrfToken, imageSource, uploadContext, fileNamePrefix) {
        const normalizedSource = normalizeInternalMediaUrl(imageSource);
        if (normalizedSource === '') {
            throw new Error('Bildquelle fehlt.');
        }

        if (isInternalMediaUrl(normalizedSource)) {
            return normalizedSource;
        }

        if (!/^data:image\//i.test(normalizedSource) && !/^blob:/i.test(normalizedSource)) {
            return normalizedSource;
        }

        const file = await createUploadFileFromImageSource(normalizedSource, fileNamePrefix);
        const payload = await uploadEditorImageFile(uploadUrl, csrfToken, file, uploadContext);
        const uploadedUrl = payload && payload.file && payload.file.url ? String(payload.file.url) : '';

        if (uploadedUrl === '') {
            throw new Error('Bild-Upload fehlgeschlagen.');
        }

        return uploadedUrl;
    }

    function isExternalHttpUrl(url) {
        return /^https?:\/\//i.test(String(url || '').trim());
    }

    function normalizeImageGalleryUrlList(urls) {
        const normalizedUrls = [];
        const seen = new Set();

        (Array.isArray(urls) ? urls : []).forEach(function (url) {
            const rawUrl = String(url || '').trim();
            if (rawUrl === '') {
                return;
            }

            const normalizedUrl = normalizeInternalMediaUrl(rawUrl);
            if (!isInternalMediaUrl(normalizedUrl) && !isExternalHttpUrl(normalizedUrl)) {
                return;
            }

            if (seen.has(normalizedUrl)) {
                return;
            }

            seen.add(normalizedUrl);
            normalizedUrls.push(normalizedUrl);
        });

        return normalizedUrls;
    }

    function buildImageGalleryImageItems(urls, existingImages) {
        const captionMap = new Map();

        (Array.isArray(existingImages) ? existingImages : []).forEach(function (item) {
            if (!item || typeof item !== 'object') {
                return;
            }

            const file = item.file && typeof item.file === 'object' ? item.file : item;
            const url = normalizeInternalMediaUrl(String(file && file.url ? file.url : ''));
            if (url === '' || captionMap.has(url)) {
                return;
            }

            captionMap.set(url, String(item.caption || ''));
        });

        return normalizeImageGalleryUrlList(urls).map(function (url) {
            const extensionMatch = url.match(/\.([a-z0-9]+)(?:$|[?#])/i);

            return {
                file: {
                    url: url,
                    name: url.split('/').pop() || 'Bild',
                    size: 0,
                    extension: extensionMatch ? String(extensionMatch[1] || '').toLowerCase() : '',
                },
                caption: captionMap.get(url) || '',
            };
        });
    }

    function normalizeImageGalleryData(data) {
        const nextData = data && typeof data === 'object' ? { ...data } : {};
        const imageUrls = Array.isArray(nextData.images)
            ? nextData.images.map(function (item) {
                if (!item || typeof item !== 'object') {
                    return '';
                }

                const file = item.file && typeof item.file === 'object' ? item.file : item;
                return String(file && file.url ? file.url : '');
            })
            : [];
        const rawUrls = imageUrls.length > 0
            ? imageUrls
            : Array.isArray(nextData.urls)
                ? nextData.urls
                : typeof nextData.urls === 'string'
                    ? nextData.urls.split(/\r?\n+/)
                    : [];

        const normalizedUrls = normalizeImageGalleryUrlList(rawUrls);
        const normalizedImages = buildImageGalleryImageItems(normalizedUrls, nextData.images);

        nextData.urls = normalizedUrls;
        nextData.images = normalizedImages;

        return nextData;
    }

    function uploadImageGalleryFiles(uploadUrl, csrfToken, files, uploadContext) {
        const fileList = Array.from(files || []).filter(function (file) {
            return file && typeof file === 'object';
        });

        if (fileList.length === 0) {
            return Promise.resolve([]);
        }

        return Promise.all(fileList.map(function (file) {
            return uploadEditorImageFile(uploadUrl, csrfToken, file, uploadContext).then(function (payload) {
                return payload && payload.file && payload.file.url ? String(payload.file.url) : '';
            });
        })).then(normalizeImageGalleryUrlList);
    }

    function importImageGalleryUrls(uploadUrl, csrfToken, urls, uploadContext) {
        const importCandidates = Array.isArray(urls) ? urls : [];

        if (importCandidates.length === 0) {
            return Promise.resolve([]);
        }

        return Promise.all(importCandidates.map(function (url) {
            const rawUrl = String(url || '').trim();
            if (rawUrl === '') {
                return '';
            }

            const normalizedUrl = normalizeInternalMediaUrl(rawUrl);
            if (isInternalMediaUrl(normalizedUrl)) {
                return normalizedUrl;
            }

            if (/^data:image\//i.test(normalizedUrl) || /^blob:/i.test(normalizedUrl)) {
                return uploadEditorInlineImage(uploadUrl, csrfToken, normalizedUrl, uploadContext, 'gallery-image');
            }

            if (isExternalHttpUrl(normalizedUrl)) {
                return fetchEditorImageByUrl(uploadUrl, csrfToken, normalizedUrl, uploadContext).then(function (payload) {
                    return payload && payload.file && payload.file.url ? String(payload.file.url) : '';
                });
            }

            return '';
        })).then(normalizeImageGalleryUrlList);
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

    function createInlineExecToolClass(commandName, title, icon, className) {
        return class InlineExecTool {
            static get isInline() {
                return true;
            }

            static get title() {
                return title;
            }

            render() {
                this.button = document.createElement('button');
                this.button.type = 'button';
                this.button.className = 'ce-inline-tool' + (className ? ' ' + className : '');
                this.button.innerHTML = icon;
                return this.button;
            }

            surround() {
                document.execCommand(commandName);
            }

            checkState() {
                const state = document.queryCommandState(commandName);
                if (this.button) {
                    this.button.classList.toggle('ce-inline-tool--active', Boolean(state));
                }
                return state;
            }
        };
    }

    function createMarkerToolClass() {
        return class MarkerTool {
            static get isInline() {
                return true;
            }

            static get title() {
                return 'Highlight';
            }

            static get sanitize() {
                return { mark: {} };
            }

            render() {
                this.button = document.createElement('button');
                this.button.type = 'button';
                this.button.className = 'ce-inline-tool';
                this.button.innerHTML = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h4l10-10-4-4L4 16v4z"/><path d="M14 6l4 4"/></svg>';
                return this.button;
            }

            surround() {
                const selection = window.getSelection();
                if (!selection || selection.rangeCount === 0 || selection.isCollapsed) {
                    return;
                }

                const range = selection.getRangeAt(0);
                const mark = document.createElement('mark');
                try {
                    range.surroundContents(mark);
                } catch (_error) {
                    const content = range.extractContents();
                    mark.appendChild(content);
                    range.insertNode(mark);
                }
            }

            checkState() {
                const selection = window.getSelection();
                let current = selection && selection.anchorNode ? selection.anchorNode : null;
                let active = false;
                while (current) {
                    if (current.nodeType === Node.ELEMENT_NODE && String(current.nodeName).toLowerCase() === 'mark') {
                        active = true;
                        break;
                    }
                    current = current.parentNode;
                }
                if (this.button) {
                    this.button.classList.toggle('ce-inline-tool--active', active);
                }
                return active;
            }
        };
    }

    function createSimpleInlineCodeToolClass() {
        return class SimpleInlineCodeTool {
            static get isInline() {
                return true;
            }

            static get title() {
                return 'Inline code';
            }

            static get sanitize() {
                return { code: {} };
            }

            render() {
                this.button = document.createElement('button');
                this.button.type = 'button';
                this.button.className = 'ce-inline-tool';
                this.button.innerHTML = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 17l-5-5 5-5"/><path d="M16 7l5 5-5 5"/></svg>';
                return this.button;
            }

            surround() {
                const selection = window.getSelection();
                if (!selection || selection.rangeCount === 0 || selection.isCollapsed) {
                    return;
                }
                const range = selection.getRangeAt(0);
                const code = document.createElement('code');
                try {
                    range.surroundContents(code);
                } catch (_error) {
                    const content = range.extractContents();
                    code.appendChild(content);
                    range.insertNode(code);
                }
            }

            checkState() {
                const selection = window.getSelection();
                let current = selection && selection.anchorNode ? selection.anchorNode : null;
                let active = false;
                while (current) {
                    if (current.nodeType === Node.ELEMENT_NODE && String(current.nodeName).toLowerCase() === 'code') {
                        active = true;
                        break;
                    }
                    current = current.parentNode;
                }
                if (this.button) {
                    this.button.classList.toggle('ce-inline-tool--active', active);
                }
                return active;
            }
        };
    }

    function createSimpleListToolClass() {
        return class SimpleListTool {
            static get toolbox() {
                return { title: 'Liste', icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6h11"/><path d="M9 12h11"/><path d="M9 18h11"/><circle cx="5" cy="6" r="1"/><circle cx="5" cy="12" r="1"/><circle cx="5" cy="18" r="1"/></svg>' };
            }

            constructor({ data }) {
                this.data = data && typeof data === 'object' ? data : {};
            }

            render() {
                const wrapper = document.createElement('div');
                this.style = document.createElement('select');
                this.style.className = 'form-select form-select-sm mb-2';
                this.style.innerHTML = '<option value="unordered">Aufzählung</option><option value="ordered">Nummeriert</option>';
                this.style.value = this.data.style === 'ordered' ? 'ordered' : 'unordered';
                this.textarea = document.createElement('textarea');
                this.textarea.className = 'form-control';
                this.textarea.rows = 4;
                const sourceItems = Array.isArray(this.data.items) ? this.data.items : [];
                this.textarea.value = sourceItems.map((item) => {
                    if (typeof item === 'string') {
                        return item;
                    }
                    return String(item && (item.content || item.text) ? item.content || item.text : '');
                }).filter(Boolean).join('\n');
                wrapper.appendChild(this.style);
                wrapper.appendChild(this.textarea);
                return wrapper;
            }

            save() {
                const style = this.style && this.style.value === 'ordered' ? 'ordered' : 'unordered';
                const lines = String(this.textarea ? this.textarea.value : '').split(/\r?\n+/).map((line) => line.trim()).filter(Boolean);
                return {
                    style,
                    meta: style === 'ordered' ? { start: 1, counterType: 'numeric' } : {},
                    items: lines.map((line) => ({ content: line, items: [], meta: {} })),
                };
            }
        };
    }

    function createSimpleQuoteToolClass() {
        return class SimpleQuoteTool {
            static get toolbox() {
                return { title: 'Zitat', icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 11H6a1 1 0 01-1-1V7a1 1 0 011-1h3a1 1 0 011 1v6c0 2.6-1.2 4.2-4 5"/><path d="M19 11h-4a1 1 0 01-1-1V7a1 1 0 011-1h3a1 1 0 011 1v6c0 2.6-1.2 4.2-4 5"/></svg>' };
            }

            constructor({ data }) {
                this.data = data && typeof data === 'object' ? data : {};
            }

            render() {
                const wrapper = document.createElement('div');
                this.quote = document.createElement('textarea');
                this.quote.className = 'form-control mb-2';
                this.quote.rows = 3;
                this.quote.placeholder = 'Zitat ...';
                this.quote.value = String(this.data.text || '');
                this.caption = document.createElement('input');
                this.caption.className = 'form-control form-control-sm';
                this.caption.placeholder = 'Quelle / Autor';
                this.caption.value = String(this.data.caption || '');
                wrapper.appendChild(this.quote);
                wrapper.appendChild(this.caption);
                return wrapper;
            }

            save() {
                return {
                    text: String(this.quote ? this.quote.value : ''),
                    caption: String(this.caption ? this.caption.value : ''),
                    alignment: 'left',
                };
            }
        };
    }

    function createSimpleDelimiterToolClass() {
        return class SimpleDelimiterTool {
            static get toolbox() {
                return { title: 'Trenner', icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/></svg>' };
            }

            render() {
                const hr = document.createElement('hr');
                hr.style.margin = '0.75rem 0';
                return hr;
            }

            save() {
                return {};
            }
        };
    }

    function createSimpleChecklistToolClass() {
        return class SimpleChecklistTool {
            static get toolbox() {
                return { title: 'Checklist', icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6h11"/><path d="M9 12h11"/><path d="M9 18h11"/><path d="M4 6l1.5 1.5L7.5 5.5"/><path d="M4 12l1.5 1.5L7.5 11.5"/><path d="M4 18l1.5 1.5L7.5 17.5"/></svg>' };
            }

            constructor({ data }) {
                this.data = data && typeof data === 'object' ? data : {};
            }

            render() {
                this.textarea = document.createElement('textarea');
                this.textarea.className = 'form-control';
                this.textarea.rows = 4;
                this.textarea.placeholder = '[x] Bereits erledigt\n[ ] Noch offen';
                const sourceItems = Array.isArray(this.data.items) ? this.data.items : [];
                this.textarea.value = sourceItems.map((item) => {
                    const checked = item && item.checked ? 'x' : ' ';
                    return `[${checked}] ${String(item && item.text ? item.text : '')}`;
                }).join('\n');
                return this.textarea;
            }

            save() {
                const lines = String(this.textarea ? this.textarea.value : '').split(/\r?\n+/).map((line) => line.trim()).filter(Boolean);
                const items = lines.map((line) => {
                    const match = line.match(/^\[(x| )\]\s*(.+)$/i);
                    if (!match) {
                        return { text: line, checked: false };
                    }
                    return { text: String(match[2] || '').trim(), checked: String(match[1] || '').toLowerCase() === 'x' };
                }).filter((item) => item.text !== '');
                return { items };
            }
        };
    }

    function createSimpleTableToolClass() {
        return class SimpleTableTool {
            static get toolbox() {
                return { title: 'Tabelle', icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18"/><path d="M3 9h18"/><path d="M3 15h18"/><path d="M9 3v18"/></svg>' };
            }

            constructor({ data }) {
                this.data = data && typeof data === 'object' ? data : {};
            }

            render() {
                const wrapper = document.createElement('div');
                this.heading = document.createElement('label');
                this.heading.className = 'form-check mb-2';
                this.heading.innerHTML = '<input type="checkbox" class="form-check-input"><span class="form-check-label">Erste Zeile als Header</span>';
                this.heading.querySelector('input').checked = Boolean(this.data.withHeadings);
                this.textarea = document.createElement('textarea');
                this.textarea.className = 'form-control';
                this.textarea.rows = 5;
                this.textarea.placeholder = 'Spalte A | Spalte B | Spalte C';
                const rows = Array.isArray(this.data.content) ? this.data.content : [];
                this.textarea.value = rows.map((row) => (Array.isArray(row) ? row : []).join(' | ')).join('\n');
                wrapper.appendChild(this.heading);
                wrapper.appendChild(this.textarea);
                return wrapper;
            }

            save() {
                const rows = String(this.textarea ? this.textarea.value : '').split(/\r?\n+/).map((line) => line.trim()).filter(Boolean).map((line) => line.split('|').map((cell) => String(cell || '').trim()));
                return {
                    withHeadings: Boolean(this.heading && this.heading.querySelector('input') && this.heading.querySelector('input').checked),
                    content: rows,
                };
            }
        };
    }

    function createSimpleCodeToolClass() {
        return class SimpleCodeTool {
            static get toolbox() {
                return { title: 'Code', icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 17l-5-5 5-5"/><path d="M16 7l5 5-5 5"/><path d="M13 4l-2 16"/></svg>' };
            }

            constructor({ data }) {
                this.data = data && typeof data === 'object' ? data : {};
            }

            render() {
                const wrapper = document.createElement('div');
                this.lang = document.createElement('input');
                this.lang.className = 'form-control form-control-sm mb-2';
                this.lang.placeholder = 'Sprache (z.B. bash, php, js)';
                this.lang.value = String(this.data.language || '');
                this.code = document.createElement('textarea');
                this.code.className = 'form-control';
                this.code.rows = 8;
                this.code.placeholder = 'Code...';
                this.code.value = String(this.data.code || '');
                wrapper.appendChild(this.lang);
                wrapper.appendChild(this.code);
                return wrapper;
            }

            save() {
                return {
                    language: String(this.lang ? this.lang.value : '').trim().toLowerCase().replace(/[^a-z0-9_\-+#]/g, ''),
                    code: String(this.code ? this.code.value : ''),
                };
            }
        };
    }

    function createSimpleEmbedToolClass() {
        return class SimpleEmbedTool {
            static get toolbox() {
                return { title: 'Embed', icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l-6-6 6-6"/><path d="M15 6l6 6-6 6"/></svg>' };
            }

            constructor({ data }) {
                this.data = data && typeof data === 'object' ? data : {};
            }

            render() {
                const wrapper = document.createElement('div');
                this.url = document.createElement('input');
                this.url.className = 'form-control form-control-sm mb-2';
                this.url.placeholder = 'https://...';
                this.url.value = String(this.data.source || this.data.embed || '');
                this.caption = document.createElement('input');
                this.caption.className = 'form-control form-control-sm';
                this.caption.placeholder = 'Caption (optional)';
                this.caption.value = String(this.data.caption || '');
                wrapper.appendChild(this.url);
                wrapper.appendChild(this.caption);
                return wrapper;
            }

            save() {
                const source = String(this.url ? this.url.value : '').trim();
                return {
                    service: 'embed',
                    source,
                    embed: source,
                    width: 0,
                    height: 0,
                    caption: String(this.caption ? this.caption.value : ''),
                };
            }
        };
    }

    function createSimpleLinkToolClass() {
        return class SimpleLinkTool {
            static get toolbox() {
                return { title: 'Link', icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.07 0l2.83-2.83a5 5 0 00-7.07-7.07L10 5"/><path d="M14 11a5 5 0 00-7.07 0L4.1 13.83a5 5 0 007.07 7.07L14 19"/></svg>' };
            }

            constructor({ data }) {
                this.data = data && typeof data === 'object' ? data : {};
            }

            render() {
                const wrapper = document.createElement('div');
                this.url = document.createElement('input');
                this.url.className = 'form-control form-control-sm mb-2';
                this.url.placeholder = 'https://...';
                this.url.value = String(this.data.link || '');
                this.title = document.createElement('input');
                this.title.className = 'form-control form-control-sm mb-2';
                this.title.placeholder = 'Titel';
                this.title.value = String(this.data.meta && this.data.meta.title ? this.data.meta.title : '');
                this.description = document.createElement('textarea');
                this.description.className = 'form-control form-control-sm';
                this.description.rows = 2;
                this.description.placeholder = 'Kurzbeschreibung';
                this.description.value = String(this.data.meta && this.data.meta.description ? this.data.meta.description : '');
                wrapper.appendChild(this.url);
                wrapper.appendChild(this.title);
                wrapper.appendChild(this.description);
                return wrapper;
            }

            save() {
                return {
                    link: String(this.url ? this.url.value : '').trim(),
                    meta: {
                        title: String(this.title ? this.title.value : ''),
                        description: String(this.description ? this.description.value : ''),
                        site_name: '',
                        image: { url: '' },
                    },
                };
            }
        };
    }

    function createSimpleAttachesToolClass(uploadUrl, csrfToken) {
        return class SimpleAttachesTool {
            static get toolbox() {
                return { title: 'Datei', icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 3h5v5"/><path d="M10 14L21 3"/></svg>' };
            }

            constructor({ data }) {
                this.data = data && typeof data === 'object' ? data : {};
            }

            render() {
                const wrapper = document.createElement('div');
                this.fileInput = document.createElement('input');
                this.fileInput.type = 'file';
                this.fileInput.className = 'form-control form-control-sm mb-2';
                this.title = document.createElement('input');
                this.title.className = 'form-control form-control-sm';
                this.title.placeholder = 'Label';
                this.title.value = String(this.data.title || '');
                this.status = document.createElement('div');
                this.status.className = 'small text-secondary mt-2';
                this.currentFile = this.data.file && typeof this.data.file === 'object' ? this.data.file : {};
                if (this.currentFile.url) {
                    this.status.textContent = 'Aktuell: ' + String(this.currentFile.name || this.currentFile.url);
                }
                this.fileInput.addEventListener('change', () => {
                    const file = this.fileInput.files && this.fileInput.files[0] ? this.fileInput.files[0] : null;
                    if (!file || !uploadUrl) {
                        return;
                    }
                    const formData = new FormData();
                    formData.append('action', 'upload_file');
                    formData.append('file', file);
                    fetchJson(uploadUrl, {
                        method: 'POST',
                        headers: buildHeaders(csrfToken),
                        credentials: 'same-origin',
                        body: formData,
                    }).then((payload) => {
                        if (!payload || Number(payload.success) !== 1 || !payload.file || !payload.file.url) {
                            throw new Error(payload && payload.message ? payload.message : 'Upload fehlgeschlagen.');
                        }
                        this.currentFile = payload.file;
                        this.status.textContent = 'Hochgeladen: ' + String(payload.file.name || payload.file.url);
                    }).catch((error) => {
                        this.status.textContent = error && error.message ? error.message : 'Datei konnte nicht hochgeladen werden.';
                    });
                });
                wrapper.appendChild(this.fileInput);
                wrapper.appendChild(this.title);
                wrapper.appendChild(this.status);
                return wrapper;
            }

            save() {
                return {
                    title: String(this.title ? this.title.value : ''),
                    file: this.currentFile && typeof this.currentFile === 'object'
                        ? {
                            url: String(this.currentFile.url || ''),
                            name: String(this.currentFile.name || ''),
                            size: Number(this.currentFile.size || 0),
                            extension: String(this.currentFile.extension || ''),
                        }
                        : { url: '', name: '', size: 0, extension: '' },
                };
            }
        };
    }

    function createSimpleCalloutToolClass() {
        return class SimpleCalloutTool {
            static get toolbox() {
                return { title: 'Callout', icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86L1.82 18a2 2 0 001.73 3h16.9a2 2 0 001.73-3l-8.47-14.14a2 2 0 00-3.42 0z"/></svg>' };
            }

            constructor({ data }) {
                this.data = data && typeof data === 'object' ? data : {};
            }

            render() {
                const wrapper = document.createElement('div');
                this.variant = document.createElement('select');
                this.variant.className = 'form-select form-select-sm mb-2';
                this.variant.innerHTML = '<option value="info">Info</option><option value="warning">Warnung</option><option value="success">Erfolg</option>';
                this.variant.value = ['info', 'warning', 'success'].includes(String(this.data.variant || '')) ? String(this.data.variant) : 'info';
                this.title = document.createElement('input');
                this.title.className = 'form-control form-control-sm mb-2';
                this.title.placeholder = 'Titel';
                this.title.value = String(this.data.title || '');
                this.message = document.createElement('textarea');
                this.message.className = 'form-control form-control-sm';
                this.message.rows = 3;
                this.message.placeholder = 'Hinweistext';
                this.message.value = String(this.data.message || '');
                wrapper.appendChild(this.variant);
                wrapper.appendChild(this.title);
                wrapper.appendChild(this.message);
                return wrapper;
            }

            save() {
                return {
                    variant: String(this.variant ? this.variant.value : 'info'),
                    title: String(this.title ? this.title.value : ''),
                    message: String(this.message ? this.message.value : ''),
                };
            }
        };
    }

    function createSimpleDetailsToolClass() {
        return class SimpleDetailsTool {
            static get toolbox() {
                return { title: 'Details', icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>' };
            }

            constructor({ data }) {
                this.data = data && typeof data === 'object' ? data : {};
            }

            render() {
                const wrapper = document.createElement('div');
                this.summary = document.createElement('input');
                this.summary.className = 'form-control form-control-sm mb-2';
                this.summary.placeholder = 'Zusammenfassung (Klick-Titel)';
                this.summary.value = String(this.data.summary || '');
                this.content = document.createElement('textarea');
                this.content.className = 'form-control form-control-sm';
                this.content.rows = 4;
                this.content.placeholder = 'Inhalt';
                this.content.value = String(this.data.content || '');
                wrapper.appendChild(this.summary);
                wrapper.appendChild(this.content);
                return wrapper;
            }

            save() {
                return {
                    summary: String(this.summary ? this.summary.value : ''),
                    content: String(this.content ? this.content.value : ''),
                };
            }
        };
    }

    function createImageToolConfig(imageClass, uploadUrl, csrfToken, cropperTuneKey, uploadContext) {
        if (!uploadUrl) {
            return null;
        }

        if (!imageClass) {
            return {
                class: class SimpleImageTool {
                    static get toolbox() {
                        return { title: 'Bild', icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18"/><circle cx="9" cy="9" r="1.5"/><path d="M3 16l5-5c1-.9 2.1-.9 3 0l5 5"/></svg>' };
                    }

                    constructor({ data }) {
                        this.data = data && typeof data === 'object' ? data : {};
                        this.currentPayload = this.data;
                    }

                    render() {
                        const wrapper = document.createElement('div');
                        this.urlInput = document.createElement('input');
                        this.urlInput.className = 'form-control form-control-sm mb-2';
                        this.urlInput.placeholder = 'Bild-URL oder Upload';
                        this.urlInput.value = String(this.data.file && this.data.file.url ? this.data.file.url : '');
                        this.captionInput = document.createElement('input');
                        this.captionInput.className = 'form-control form-control-sm mb-2';
                        this.captionInput.placeholder = 'Caption / Alt-Text';
                        this.captionInput.value = String(this.data.caption || '');
                        this.fileInput = document.createElement('input');
                        this.fileInput.type = 'file';
                        this.fileInput.accept = 'image/*';
                        this.fileInput.className = 'form-control form-control-sm';
                        this.fileInput.addEventListener('change', () => {
                            const file = this.fileInput.files && this.fileInput.files[0] ? this.fileInput.files[0] : null;
                            if (!file) {
                                return;
                            }
                            uploadEditorImageFile(uploadUrl, csrfToken, file, uploadContext).then((payload) => {
                                const nextUrl = payload && payload.file && payload.file.url ? String(payload.file.url) : '';
                                if (nextUrl !== '') {
                                    this.urlInput.value = nextUrl;
                                    this.currentPayload = payload;
                                }
                            }).catch((error) => {
                                console.error('Editor.js simple image upload failed', error);
                            });
                        });
                        wrapper.appendChild(this.urlInput);
                        wrapper.appendChild(this.captionInput);
                        wrapper.appendChild(this.fileInput);
                        return wrapper;
                    }

                    save() {
                        const fileUrl = String(this.urlInput ? this.urlInput.value : '').trim();
                        return {
                            file: {
                                url: fileUrl,
                                name: this.currentPayload && this.currentPayload.file ? String(this.currentPayload.file.name || '') : '',
                                size: this.currentPayload && this.currentPayload.file ? Number(this.currentPayload.file.size || 0) : 0,
                                extension: this.currentPayload && this.currentPayload.file ? String(this.currentPayload.file.extension || '') : '',
                            },
                            caption: String(this.captionInput ? this.captionInput.value : ''),
                            withBorder: false,
                            withBackground: false,
                            stretched: false,
                        };
                    }
                },
                inlineToolbar: ['link', 'bold', 'italic', 'marker'],
            };
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
                    this.uploadFile(resolveClipboardUploadFile(event.detail.file));
                    return;
                }

                if (event && event.type === 'tag' && event.detail && event.detail.data) {
                    const source = String(event.detail.data.src || '');
                    if (/^blob:/i.test(source)) {
                        const clipboardFile = takeRecentClipboardImageFile();
                        if (clipboardFile) {
                            this.uploadFile(clipboardFile);
                            return;
                        }

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
            inlineToolbar: ['link', 'bold', 'italic', 'underline', 'inlineCode', 'marker', 'spoiler'],
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
        if (!uploadUrl) {
            return null;
        }

        if (!attachesClass) {
            return {
                class: createSimpleAttachesToolClass(uploadUrl, csrfToken),
            };
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
        if (!uploadUrl) {
            return null;
        }

        if (!linkToolClass) {
            return {
                class: createSimpleLinkToolClass(),
                inlineToolbar: ['link', 'bold', 'italic', 'underline', 'inlineCode', 'marker'],
            };
        }

        return {
            class: linkToolClass,
            config: {
                endpoint: buildQueryUrl(uploadUrl, 'fetch_link'),
                headers: buildHeaders(csrfToken),
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

    function createEmbedToolConfig(embedClass) {
        return {
            class: embedClass || createSimpleEmbedToolClass(),
            inlineToolbar: ['link', 'bold', 'italic', 'underline', 'inlineCode', 'marker'],
        };
    }

    function createCarouselConfig(carouselClass, uploadUrl, csrfToken, uploadContext) {
        if (!carouselClass) {
            return null;
        }

        const config = { class: carouselClass };
        if (uploadUrl) {
            config.config = {
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
        }
        return config;
    }

    function createImageGalleryConfig(galleryClass, uploadUrl, csrfToken, uploadContext) {
        if (!galleryClass) {
            return {
                class: class SimpleImageGalleryTool {
                    static get toolbox() {
                        return { title: 'Gallery', icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 9h.01"/><path d="M21 15l-4.5-4.5a1.5 1.5 0 00-2.12 0L9 15.88"/></svg>' };
                    }

                    constructor({ data }) {
                        this.data = normalizeImageGalleryData(data && typeof data === 'object' ? data : {});
                    }

                    render() {
                        const wrapper = document.createElement('div');
                        this.textarea = document.createElement('textarea');
                        this.textarea.className = 'form-control mb-2';
                        this.textarea.rows = 5;
                        this.textarea.placeholder = 'Eine Bild-URL pro Zeile';
                        this.textarea.value = this.data.urls.join('\n');
                        this.upload = document.createElement('input');
                        this.upload.type = 'file';
                        this.upload.accept = 'image/*';
                        this.upload.multiple = true;
                        this.upload.className = 'form-control form-control-sm';
                        this.upload.addEventListener('change', () => {
                            const files = Array.from(this.upload.files || []);
                            if (files.length === 0 || !uploadUrl) {
                                return;
                            }
                            uploadImageGalleryFiles(uploadUrl, csrfToken, files, uploadContext).then((urls) => {
                                const nextUrls = normalizeImageGalleryUrlList(this.textarea.value.split(/\r?\n+/).concat(urls));
                                this.textarea.value = nextUrls.join('\n');
                            }).catch((error) => {
                                console.error('Editor.js simple gallery upload failed', error);
                            });
                        });
                        wrapper.appendChild(this.textarea);
                        wrapper.appendChild(this.upload);
                        return wrapper;
                    }

                    save() {
                        return normalizeImageGalleryData({
                            columns: 3,
                            urls: String(this.textarea ? this.textarea.value : '').split(/\r?\n+/),
                        });
                    }
                },
                inlineToolbar: true,
            };
        }

        class CmsImageGalleryTool extends galleryClass {
            constructor(options) {
                const nextOptions = { ...options };
                nextOptions.data = normalizeImageGalleryData(options && options.data ? options.data : {});

                super(nextOptions);

                this.data = normalizeImageGalleryData(this.data);
                this.cmsImagePicker = uploadUrl ? createEditorImagePicker(uploadUrl, csrfToken, uploadContext) : null;
                this.cmsGalleryStatus = null;
                this.cmsGalleryUploadInput = null;
                this.cmsGalleryImportButton = null;
            }

            _isImgUrl(url) {
                const normalizedUrl = normalizeInternalMediaUrl(url);

                return normalizedUrl !== '' && (isInternalMediaUrl(normalizedUrl) || isExternalHttpUrl(normalizedUrl));
            }

            render() {
                const wrapper = super.render();
                this.wrapper = wrapper;

                this.cmsEnsureTextarea();
                this.cmsEnsureToolbar();
                this.cmsApplyUrls(this.cmsGetUrls(), false);

                if (typeof this._acceptTuneView === 'function') {
                    this._acceptTuneView();
                }

                return wrapper;
            }

            save(blockContent) {
                const savedData = normalizeImageGalleryData(super.save(blockContent));
                this.data = savedData;

                return savedData;
            }

            validate(savedData) {
                const normalizedData = normalizeImageGalleryData(savedData);
                savedData.urls = normalizedData.urls;
                savedData.images = normalizedData.images;

                return true;
            }

            cmsEnsureTextarea() {
                if (!this.wrapper) {
                    return null;
                }

                let manualContainer = this.wrapper.querySelector('.cms-editor-gallery__manual');
                let manualToggle = this.wrapper.querySelector('.cms-editor-gallery__manual-toggle');
                let manualBody = this.wrapper.querySelector('.cms-editor-gallery__manual-body');
                let textarea = this.wrapper.querySelector('textarea.image-gallery-' + this.blockIndex);
                if (!manualContainer) {
                    manualContainer = document.createElement('div');
                    manualContainer.className = 'cms-editor-gallery__manual';

                    manualToggle = document.createElement('button');
                    manualToggle.type = 'button';
                    manualToggle.className = 'cms-editor-gallery__manual-toggle';
                    manualToggle.textContent = 'URLs manuell eingeben ▾';
                    manualToggle.addEventListener('click', (event) => {
                        event.preventDefault();
                        manualContainer.classList.toggle('is-open');
                        manualToggle.textContent = manualContainer.classList.contains('is-open')
                            ? 'URLs manuell eingeben ▴'
                            : 'URLs manuell eingeben ▾';
                        if (this.cmsGalleryImportButton) {
                            this.cmsGalleryImportButton.classList.toggle('is-visible', manualContainer.classList.contains('is-open'));
                        }
                    });

                    manualBody = document.createElement('div');
                    manualBody.className = 'cms-editor-gallery__manual-body';
                    manualContainer.appendChild(manualToggle);
                    manualContainer.appendChild(manualBody);
                    this.wrapper.insertBefore(manualContainer, this.wrapper.firstChild || null);
                }

                if (textarea) {
                    textarea.value = this.cmsGetUrls().join('\n');
                    return textarea;
                }

                textarea = document.createElement('textarea');
                textarea.className = 'image-gallery-' + this.blockIndex;
                textarea.placeholder = 'Bild-URLs hier einfügen oder per Buttons importieren …';
                textarea.value = this.cmsGetUrls().join('\n');

                ['paste', 'change', 'keyup', 'input'].forEach((eventName) => {
                    textarea.addEventListener(eventName, () => {
                        this.cmsApplyUrls(this.cmsParseTextareaUrls(), false);
                    }, false);
                });

                if (manualBody) {
                    manualBody.appendChild(textarea);
                } else {
                    this.wrapper.insertBefore(textarea, this.wrapper.firstChild || null);
                }

                return textarea;
            }

            cmsEnsureToolbar() {
                if (!this.wrapper || this.wrapper.querySelector('.cms-editor-gallery__toolbar')) {
                    return;
                }

                const toolbar = document.createElement('div');
                toolbar.className = 'cms-editor-gallery__toolbar';

                const uploadButton = document.createElement('button');
                uploadButton.type = 'button';
                uploadButton.className = 'cms-editor-gallery__button';
                uploadButton.textContent = 'Bilder hochladen';

                const libraryButton = document.createElement('button');
                libraryButton.type = 'button';
                libraryButton.className = 'cms-editor-gallery__button';
                libraryButton.textContent = 'Aus Mediathek wählen';

                const importButton = document.createElement('button');
                importButton.type = 'button';
                importButton.className = 'cms-editor-gallery__button';
                importButton.textContent = 'URLs importieren';
                importButton.classList.add('cms-editor-gallery__manual-import');

                const uploadInput = document.createElement('input');
                uploadInput.type = 'file';
                uploadInput.accept = 'image/*';
                uploadInput.multiple = true;
                uploadInput.hidden = true;

                const status = document.createElement('div');
                status.className = 'cms-editor-gallery__status';

                uploadButton.addEventListener('click', (event) => {
                    event.preventDefault();
                    uploadInput.click();
                });

                uploadInput.addEventListener('change', () => {
                    const files = Array.from(uploadInput.files || []);
                    if (files.length === 0 || !uploadUrl) {
                        return;
                    }

                    this.cmsSetGalleryStatus('Lade ' + files.length + ' Bild' + (files.length === 1 ? '' : 'er') + ' hoch …', false);
                    uploadImageGalleryFiles(uploadUrl, csrfToken, files, uploadContext).then((urls) => {
                        this.cmsAppendUrls(urls, true);
                        this.cmsSetGalleryStatus(urls.length + ' Bild' + (urls.length === 1 ? '' : 'er') + ' hinzugefügt.', false);
                    }).catch((error) => {
                        console.error('Image gallery upload failed:', error);
                        this.cmsSetGalleryStatus(error && error.message ? error.message : 'Galerie-Upload fehlgeschlagen.', true);
                    }).finally(() => {
                        uploadInput.value = '';
                    });
                });

                libraryButton.addEventListener('click', (event) => {
                    event.preventDefault();
                    if (!this.cmsImagePicker) {
                        return;
                    }

                    this.cmsImagePicker.open().then((payload) => {
                        const url = payload && payload.file && payload.file.url ? String(payload.file.url) : '';
                        if (url !== '') {
                            this.cmsAppendUrls([url], true);
                            this.cmsSetGalleryStatus('Bild aus der Mediathek hinzugefügt.', false);
                        }
                    }).catch((error) => {
                        if (!error || error.message !== 'Bildauswahl abgebrochen.') {
                            console.error('Image gallery picker error:', error);
                        }
                    });
                });

                importButton.addEventListener('click', (event) => {
                    event.preventDefault();
                    if (!uploadUrl) {
                        return;
                    }

                    const urls = this.cmsParseTextareaUrls();
                    if (urls.length === 0) {
                        this.cmsSetGalleryStatus('Keine importierbaren URLs vorhanden.', true);
                        return;
                    }

                    this.cmsSetGalleryStatus('Importiere ' + urls.length + ' Galerie-URL' + (urls.length === 1 ? '' : 's') + ' …', false);
                    importImageGalleryUrls(uploadUrl, csrfToken, urls, uploadContext).then((importedUrls) => {
                        this.cmsApplyUrls(importedUrls, true);
                        this.cmsSetGalleryStatus(importedUrls.length + ' Galerie-Bild' + (importedUrls.length === 1 ? '' : 'er') + ' importiert.', false);
                    }).catch((error) => {
                        console.error('Image gallery import failed:', error);
                        this.cmsSetGalleryStatus(error && error.message ? error.message : 'Galerie-Import fehlgeschlagen.', true);
                    });
                });

                toolbar.appendChild(uploadButton);
                toolbar.appendChild(libraryButton);
                toolbar.appendChild(importButton);
                toolbar.appendChild(uploadInput);

                this.wrapper.insertBefore(toolbar, this.wrapper.firstChild || null);
                this.wrapper.insertBefore(status, toolbar.nextSibling);

                this.cmsGalleryStatus = status;
                this.cmsGalleryUploadInput = uploadInput;
                this.cmsGalleryImportButton = importButton;
            }

            cmsParseTextareaUrls() {
                const textarea = this.wrapper ? this.wrapper.querySelector('textarea.image-gallery-' + this.blockIndex) : null;
                if (!textarea) {
                    return this.cmsGetUrls();
                }

                return normalizeImageGalleryUrlList(String(textarea.value || '').split(/\r?\n+/));
            }

            cmsGetUrls() {
                const normalizedData = normalizeImageGalleryData(this.data || {});
                if (Array.isArray(normalizedData.urls) && normalizedData.urls.length > 0) {
                    return normalizedData.urls;
                }

                if (this.wrapper) {
                    return normalizeImageGalleryUrlList(Array.from(this.wrapper.querySelectorAll('.gg-box > img')).map(function (image) {
                        return image.getAttribute('src') || '';
                    }));
                }

                return [];
            }

            cmsApplyUrls(urls, updateTextarea) {
                const nextData = normalizeImageGalleryData({ ...this.data, urls: normalizeImageGalleryUrlList(urls) });
                this.data = nextData;

                const textarea = this.wrapper ? this.wrapper.querySelector('textarea.image-gallery-' + this.blockIndex) : null;
                if (textarea && updateTextarea !== false) {
                    textarea.value = nextData.urls.join('\n');
                }

                if (typeof this._imageGallery === 'function') {
                    this._imageGallery(nextData.urls);
                }

                if (typeof this._acceptTuneView === 'function') {
                    this._acceptTuneView();
                }

                this.cmsDecorateGalleryItems();
            }

            cmsAppendUrls(urls, updateTextarea) {
                this.cmsApplyUrls(this.cmsGetUrls().concat(urls), updateTextarea);
            }

            cmsSetGalleryStatus(message, isError) {
                if (!this.cmsGalleryStatus) {
                    return;
                }

                this.cmsGalleryStatus.textContent = String(message || '');
                this.cmsGalleryStatus.classList.toggle('is-error', Boolean(isError));
            }

            cmsDecorateGalleryItems() {
                if (!this.wrapper) {
                    return;
                }

                const galleryImages = Array.from(this.wrapper.querySelectorAll('.gg-box > img'));
                const normalizedData = normalizeImageGalleryData(this.data || {});

                galleryImages.forEach((imageElement, index) => {
                    const parent = imageElement.parentElement;
                    let overlay;
                    let altInput;
                    let removeButton;

                    if (!parent) {
                        return;
                    }

                    parent.classList.add('cms-editor-gallery-item');
                    parent.dataset.cmsGalleryIndex = String(index);

                    overlay = parent.querySelector('.cms-editor-gallery-item__overlay');
                    if (!overlay) {
                        overlay = document.createElement('div');
                        overlay.className = 'cms-editor-gallery-item__overlay';
                        overlay.innerHTML = ''
                            + '<input type="text" class="form-control form-control-sm" placeholder="Alt-Text eingeben...">'
                            + '<button type="button" class="btn btn-sm btn-danger" aria-label="Bild entfernen"><i class="ti ti-x"></i></button>';
                        parent.appendChild(overlay);
                    }

                    altInput = overlay.querySelector('input');
                    removeButton = overlay.querySelector('button');

                    if (altInput) {
                        const currentCaption = normalizedData.images[index] && typeof normalizedData.images[index].caption === 'string'
                            ? normalizedData.images[index].caption
                            : '';
                        altInput.value = currentCaption;
                        if (altInput.dataset.cmsBound !== '1') {
                            altInput.dataset.cmsBound = '1';
                            altInput.addEventListener('input', () => {
                                const currentIndex = Number.parseInt(parent.dataset.cmsGalleryIndex || '-1', 10);
                                if (!Number.isInteger(currentIndex) || currentIndex < 0) {
                                    return;
                                }
                                if (!this.data.images[currentIndex]) {
                                    return;
                                }
                                this.data.images[currentIndex].caption = altInput.value;
                            });
                        }
                    }

                    if (removeButton && removeButton.dataset.cmsBound !== '1') {
                        removeButton.dataset.cmsBound = '1';
                        removeButton.addEventListener('click', (event) => {
                            const currentIndex = Number.parseInt(parent.dataset.cmsGalleryIndex || '-1', 10);
                            const urls = this.cmsGetUrls();

                            event.preventDefault();
                            if (!Number.isInteger(currentIndex) || currentIndex < 0 || currentIndex >= urls.length) {
                                return;
                            }

                            urls.splice(currentIndex, 1);
                            this.cmsApplyUrls(urls, true);
                        });
                    }
                });
            }
        }

        return {
            class: CmsImageGalleryTool,
            inlineToolbar: true,
        };
    }

    function createDrawingConfig(drawingClass, uploadUrl, csrfToken, uploadContext) {
        if (!drawingClass) {
            return null;
        }

        return {
            class: drawingClass,
            inlineToolbar: false,
            config: {
                defaultBackground: '#ffffff',
                defaultStrokeColor: '#111827',
                uploader: uploadUrl ? {
                    uploadImage: function (imageSource) {
                        return uploadEditorInlineImage(uploadUrl, csrfToken, imageSource, uploadContext, 'drawing-image');
                    },
                } : undefined,
            },
        };
    }

    function createCropperTuneConfig(cropperTuneClass, uploadUrl, csrfToken, uploadContext) {
        if (!cropperTuneClass || !uploadUrl) {
            return null;
        }

        return {
            class: cropperTuneClass,
            config: {
                uploadUrl: buildQueryUrl(uploadUrl, 'upload_image'),
                headers: buildHeaders(csrfToken),
                uploader: {
                    uploadImage: function (imageSource) {
                        return uploadEditorInlineImage(uploadUrl, csrfToken, imageSource, uploadContext, 'cropped-image');
                    },
                },
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
            checklist: resolveClass(['Checklist']),
            quote: resolveClass(['Quote']),
            warning: resolveClass(['Warning']),
            code: resolveClass(['CodeTool', 'Code']),
            raw: resolveClass(['RawTool', 'Raw']),
            table: resolveClass(['Table']),
            inlineCode: resolveClass(['InlineCode']),
            marker: resolveClass(['Marker']),
            underline: resolveClass(['Underline']),
            delimiter: resolveClass(['Delimiter']),
            image: resolveClass(['ImageTool']),
            linkTool: resolveClass(['LinkTool']),
            embed: resolveClass(['Embed']),
            attaches: resolveClass(['AttachesTool']),
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
        const fallbackTools = {
            list: createSimpleListToolClass(),
            checklist: createSimpleChecklistToolClass(),
            quote: createSimpleQuoteToolClass(),
            table: createSimpleTableToolClass(),
            delimiter: createSimpleDelimiterToolClass(),
            code: createSimpleCodeToolClass(),
            inlineCode: createSimpleInlineCodeToolClass(),
            underline: createInlineExecToolClass('underline', 'Unterstreichen', '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 4v6a6 6 0 0012 0V4"/><path d="M4 20h16"/></svg>'),
            marker: createMarkerToolClass(),
            callout: createSimpleCalloutToolClass(),
            details: createSimpleDetailsToolClass(),
        };
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

        if (holder.dataset.cmsClipboardCaptureBound !== '1') {
            holder.addEventListener('paste', rememberClipboardImageFiles, true);
            holder.dataset.cmsClipboardCaptureBound = '1';
        }

        const cropperTuneKey = resolved.cropperTune ? 'Cropper' : null;

        const baseTools = pruneUnavailableTools({
            header: {
                class: resolved.header,
                inlineToolbar: ['link', 'bold', 'italic', 'underline', 'inlineCode', 'marker', 'spoiler'],
                config: { levels: [2, 3, 4, 5], defaultLevel: 2 },
                shortcut: 'CMD+SHIFT+H',
            },
            paragraph: {
                class: resolved.paragraph,
                inlineToolbar: ['link', 'bold', 'italic', 'underline', 'inlineCode', 'marker', 'spoiler'],
                config: { preserveBlank: true },
            },
            list: {
                class: resolved.list || fallbackTools.list,
                inlineToolbar: ['link', 'bold', 'italic', 'underline', 'inlineCode', 'marker', 'spoiler'],
                config: {
                    defaultStyle: 'unordered',
                    maxLevel: 3,
                },
            },
            checklist: {
                class: resolved.checklist || fallbackTools.checklist,
                inlineToolbar: ['link', 'bold', 'italic', 'underline', 'inlineCode', 'marker', 'spoiler'],
            },
            quote: {
                class: resolved.quote || fallbackTools.quote,
                inlineToolbar: ['link', 'bold', 'italic', 'underline', 'inlineCode', 'marker', 'spoiler'],
                config: {
                    quotePlaceholder: 'Zitat eingeben',
                    captionPlaceholder: 'Quelle / Autor',
                },
            },
            warning: {
                class: resolved.warning || fallbackTools.callout,
                inlineToolbar: ['link', 'bold', 'italic'],
                config: {
                    titlePlaceholder: 'Hinweis-Titel',
                    messagePlaceholder: 'Hinweistext',
                },
            },
            callout: {
                class: fallbackTools.callout,
                inlineToolbar: ['link', 'bold', 'italic', 'underline', 'inlineCode', 'marker', 'spoiler'],
            },
            details: {
                class: fallbackTools.details,
                inlineToolbar: ['link', 'bold', 'italic', 'underline', 'inlineCode', 'marker', 'spoiler'],
            },
            code: {
                class: resolved.code || fallbackTools.code,
                shortcut: 'CMD+ALT+C',
            },
            raw: { class: resolved.raw },
            table: {
                class: resolved.table || fallbackTools.table,
                inlineToolbar: true,
                config: { rows: 3, cols: 3 },
            },
            inlineCode: { class: resolved.inlineCode || fallbackTools.inlineCode, shortcut: 'CMD+SHIFT+M' },
            marker: { class: resolved.marker || fallbackTools.marker },
            underline: { class: resolved.underline || fallbackTools.underline, shortcut: 'CMD+U' },
            delimiter: { class: resolved.delimiter || fallbackTools.delimiter },
            spacer: {
                class: spacerToolClass,
                config: {
                    presets: [15, 25, 40, 60, 75, 100],
                    defaultHeight: 15,
                },
            },
            spoiler: resolved.spoiler ? { class: resolved.spoiler } : null,
            Cropper: createCropperTuneConfig(resolved.cropperTune, uploadUrl, csrfToken, getUploadContext),
        });

        const tools = pruneUnavailableTools({
            ...baseTools,
            image: createImageToolConfig(resolved.image, uploadUrl, csrfToken, cropperTuneKey, getUploadContext),
            linkTool: createLinkToolConfig(resolved.linkTool, uploadUrl, csrfToken),
            embed: createEmbedToolConfig(resolved.embed),
            attaches: createAttachesToolConfig(resolved.attaches, uploadUrl, csrfToken),
            accordion: createAccordionConfig(resolved.accordion),
            carousel: createCarouselConfig(resolved.carousel, uploadUrl, csrfToken, getUploadContext),
            imageGallery: createImageGalleryConfig(resolved.imageGallery, uploadUrl, csrfToken, getUploadContext),
            drawingTool: createDrawingConfig(resolved.drawingTool, uploadUrl, csrfToken, getUploadContext),
        });

        const editor = new resolved.editorjs({
            holder: holderId,
            data: normalizeInitialData(initialData),
            autofocus: false,
            minHeight: 320,
            placeholder: 'Inhalt schreiben … mit / öffnest du die Blockauswahl.',
            defaultBlock: 'paragraph',
            inlineToolbar: ['link', 'bold', 'italic', 'underline', 'inlineCode', 'marker', 'spoiler'],
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

        editor.cmsAvailableTools = Object.keys(tools);

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
