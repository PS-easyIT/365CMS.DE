'use strict';

(function () {
    var BLOCK_TOOL_NAMES = [
        'paragraph',
        'header',
        'list',
        'image',
        'quote',
        'code',
        'table',
        'delimiter',
        'embed',
        'linkTool',
        'attaches',
        'warning',
        'raw',
        'accordion',
        'imageGallery'
    ];
    var INLINE_TOOL_NAMES = ['inlineCode', 'underline', 'spoiler'];
    var PLUGIN_NAMES = ['undo', 'dragDrop'];
    var TOOL_NAMES = BLOCK_TOOL_NAMES.concat(INLINE_TOOL_NAMES);
    var VERSION = 'cms-editorjs-org-assets-2026-05-17';
    var TOOL_GLOBALS = {
        paragraph: ['Paragraph'],
        header: ['Header'],
        list: ['EditorjsList', 'List'],
        image: ['ImageTool'],
        quote: ['Quote'],
        code: ['CodeTool'],
        table: ['Table'],
        delimiter: ['Delimiter'],
        embed: ['Embed'],
        linkTool: ['LinkTool'],
        attaches: ['AttachesTool'],
        warning: ['Warning'],
        raw: ['RawTool'],
        accordion: ['Accordion'],
        imageGallery: ['ImageGallery'],
        inlineCode: ['InlineCode'],
        underline: ['Underline'],
        spoiler: ['TgSpoilerEditorJS', 'Spoiler']
    };
    var PLUGIN_GLOBALS = {
        undo: ['Undo'],
        dragDrop: ['DragDrop']
    };
    var TOOL_ALIASES = {
        checklist: 'list',
        link: 'linkTool',
        gallery: 'imageGallery',
        image_gallery: 'imageGallery',
        imageGallery: 'imageGallery',
        callout: 'warning',
        details: 'accordion'
    };

    function logInfo(message, payload) {
        if (typeof console !== 'undefined' && typeof console.info === 'function') {
            console.info('[EditorJS][CMS] ' + message, payload || null);
        }
    }

    function logWarn(message, payload) {
        if (typeof console !== 'undefined' && typeof console.warn === 'function') {
            console.warn('[EditorJS][CMS] ' + message, payload || null);
        }
    }

    function stripTags(value) {
        var element = document.createElement('div');
        element.innerHTML = String(value || '').replace(/<br\s*\/?>/gi, '\n');
        return (element.textContent || element.innerText || '').trim();
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function normalizeBlock(block) {
        var type = block && typeof block.type === 'string' ? block.type : 'paragraph';
        var data = block && block.data && typeof block.data === 'object' ? block.data : {};

        type = TOOL_ALIASES[type] || type;

        if (block && block.type === 'checklist') {
            return {
                type: 'list',
                data: normalizeListData(Object.assign({}, data, { style: 'checklist' }))
            };
        }

        if (BLOCK_TOOL_NAMES.indexOf(type) === -1) {
            return {
                type: 'paragraph',
                data: { text: stripTags(data.text || data.content || data.html || data.caption || '') }
            };
        }

        if (type === 'list') {
            return { type: type, data: normalizeListData(data) };
        }

        if (type === 'warning') {
            return {
                type: type,
                data: {
                    title: stripTags(data.title || data.caption || 'Hinweis'),
                    message: data.message || data.text || data.content || ''
                }
            };
        }

        if (type === 'raw') {
            return { type: type, data: { html: String(data.html || data.text || data.content || '') } };
        }

        if (type === 'imageGallery') {
            return {
                type: type,
                data: Object.assign({}, data, {
                    urls: Array.isArray(data.urls) ? data.urls : []
                })
            };
        }

        return { type: type, data: data };
    }

    function normalizeListData(data) {
        var style = data && typeof data.style === 'string' ? data.style : 'unordered';
        var meta = data && data.meta && typeof data.meta === 'object' ? data.meta : {};

        if (['ordered', 'unordered', 'checklist'].indexOf(style) === -1) {
            style = 'unordered';
        }

        return {
            style: style,
            meta: meta,
            items: normalizeListItems(Array.isArray(data && data.items) ? data.items : [], style)
        };
    }

    function normalizeListItems(items, style) {
        return items.map(function (item) {
            var content;
            var meta;
            var children;

            if (typeof item === 'string') {
                return {
                    content: item,
                    meta: style === 'checklist' ? { checked: false } : {},
                    items: []
                };
            }

            if (!item || typeof item !== 'object') {
                return null;
            }

            content = typeof item.content === 'string'
                ? item.content
                : (typeof item.text === 'string' ? item.text : '');
            meta = item.meta && typeof item.meta === 'object' ? item.meta : {};
            if (style === 'checklist' && typeof item.checked === 'boolean' && typeof meta.checked !== 'boolean') {
                meta = Object.assign({}, meta, { checked: item.checked });
            }
            children = Array.isArray(item.items) ? item.items : [];

            return {
                content: content,
                meta: meta,
                items: normalizeListItems(children, style)
            };
        }).filter(Boolean);
    }

    function textToBlocks(value) {
        return String(value || '')
            .replace(/\r\n/g, '\n')
            .split(/\n{2,}/)
            .map(function (part) {
                return part.trim();
            })
            .filter(Boolean)
            .map(function (part) {
                return {
                    type: 'paragraph',
                    data: { text: escapeHtml(part).replace(/\n/g, '<br>') }
                };
            });
    }

    function htmlToBlocks(value) {
        var root = document.createElement('div');
        var blocks = [];

        root.innerHTML = String(value || '');
        Array.prototype.slice.call(root.children).forEach(function (child) {
            var tag = String(child.tagName || '').toLowerCase();
            var text = child.innerHTML || child.textContent || '';

            if (/^h[1-6]$/.test(tag)) {
                blocks.push({
                    type: 'header',
                    data: { text: stripTags(text), level: Math.min(4, Math.max(2, parseInt(tag.substring(1), 10) || 2)) }
                });
                return;
            }

            if (tag === 'blockquote') {
                blocks.push({ type: 'quote', data: { text: stripTags(text), caption: '' } });
                return;
            }

            if (tag === 'pre') {
                blocks.push({ type: 'code', data: { code: child.textContent || '' } });
                return;
            }

            if (tag === 'hr') {
                blocks.push({ type: 'delimiter', data: {} });
                return;
            }

            if (tag === 'ul' || tag === 'ol') {
                blocks.push({
                    type: 'list',
                    data: {
                        style: tag === 'ol' ? 'ordered' : 'unordered',
                        items: Array.prototype.slice.call(child.querySelectorAll('li')).map(function (item) {
                            return stripTags(item.innerHTML || item.textContent || '');
                        }).filter(Boolean)
                    }
                });
                return;
            }

            if (tag === 'table') {
                blocks.push({ type: 'table', data: tableElementToData(child) });
                return;
            }

            if (tag === 'img') {
                blocks.push({ type: 'image', data: { file: { url: child.getAttribute('src') || '' }, caption: child.getAttribute('alt') || '' } });
                return;
            }

            if (stripTags(text) !== '') {
                blocks.push({ type: 'paragraph', data: { text: text } });
            }
        });

        if (blocks.length === 0 && stripTags(value) !== '') {
            return textToBlocks(stripTags(value));
        }

        return blocks;
    }

    function tableElementToData(table) {
        return {
            withHeadings: false,
            content: Array.prototype.slice.call(table.querySelectorAll('tr')).map(function (row) {
                return Array.prototype.slice.call(row.children).map(function (cell) {
                    return stripTags(cell.innerHTML || cell.textContent || '');
                });
            }).filter(function (row) {
                return row.length > 0;
            })
        };
    }

    function normalizeInitialData(raw) {
        var decoded;
        var value = typeof raw === 'string' ? raw.trim() : raw;

        if (!value) {
            return { time: Date.now(), version: VERSION, blocks: [] };
        }

        if (typeof value === 'object') {
            decoded = value;
        } else {
            try {
                decoded = JSON.parse(value);
            } catch (_error) {
                decoded = null;
            }
        }

        if (decoded && Array.isArray(decoded.blocks)) {
            return {
                time: decoded.time || Date.now(),
                version: decoded.version || VERSION,
                blocks: decoded.blocks.map(normalizeBlock)
            };
        }

        return {
            time: Date.now(),
            version: VERSION,
            blocks: String(value).indexOf('<') !== -1 ? htmlToBlocks(value) : textToBlocks(value)
        };
    }

    function createElement(tagName, className, text) {
        var element = document.createElement(tagName);
        if (className) {
            element.className = className;
        }
        if (typeof text === 'string') {
            element.textContent = text;
        }
        return element;
    }

    function createEditable(className, html, placeholder) {
        var element = createElement('div', className);
        element.contentEditable = 'true';
        element.innerHTML = html || '';
        if (placeholder) {
            element.dataset.placeholder = placeholder;
        }
        return element;
    }

    function createInput(type, className, value, placeholder) {
        var input = document.createElement('input');
        input.type = type || 'text';
        input.className = className || 'form-control';
        input.value = value || '';
        if (placeholder) {
            input.placeholder = placeholder;
        }
        return input;
    }

    class HeaderTool {
        constructor(options) {
            this.data = options.data || {};
            this.nodes = {};
        }
        static get toolbox() {
            return { title: 'Überschrift', icon: '<b>H</b>' };
        }
        render() {
            var wrapper = createElement('div', 'cms-editorjs-tool cms-editorjs-tool--header');
            var level = createInput('number', 'form-control form-control-sm', String(this.data.level || 2), '2');
            level.min = '2';
            level.max = '4';
            var text = createEditable('form-control cms-editorjs-editable cms-editorjs-header', this.data.text || '', 'Überschrift');
            wrapper.appendChild(level);
            wrapper.appendChild(text);
            this.nodes = { level: level, text: text };
            return wrapper;
        }
        save() {
            return {
                text: this.nodes.text ? this.nodes.text.innerHTML.trim() : '',
                level: Math.min(4, Math.max(2, parseInt(this.nodes.level ? this.nodes.level.value : '2', 10) || 2))
            };
        }
    }

    class ListTool {
        constructor(options) {
            this.data = options.data || {};
            this.nodes = {};
        }
        static get toolbox() {
            return { title: 'Liste', icon: '☰' };
        }
        render() {
            var wrapper = createElement('div', 'cms-editorjs-tool cms-editorjs-tool--list');
            var style = document.createElement('select');
            var textarea = document.createElement('textarea');
            ['unordered', 'ordered'].forEach(function (value) {
                var option = document.createElement('option');
                option.value = value;
                option.textContent = value === 'ordered' ? 'Nummeriert' : 'Aufzählung';
                style.appendChild(option);
            });
            style.className = 'form-select form-select-sm mb-2';
            style.value = this.data.style === 'ordered' ? 'ordered' : 'unordered';
            textarea.className = 'form-control';
            textarea.rows = 5;
            textarea.value = Array.isArray(this.data.items) ? this.data.items.map(stripTags).join('\n') : '';
            textarea.placeholder = 'Ein Listenpunkt pro Zeile';
            wrapper.appendChild(style);
            wrapper.appendChild(textarea);
            this.nodes = { style: style, textarea: textarea };
            return wrapper;
        }
        save() {
            return {
                style: this.nodes.style ? this.nodes.style.value : 'unordered',
                items: String(this.nodes.textarea ? this.nodes.textarea.value : '').split('\n').map(function (item) {
                    return item.trim();
                }).filter(Boolean)
            };
        }
    }

    class ImageTool {
        constructor(options) {
            this.data = options.data || {};
            this.config = options.config || {};
            this.nodes = {};
        }
        static get toolbox() {
            return { title: 'Bild', icon: '▧' };
        }
        render() {
            var wrapper = createElement('div', 'cms-editorjs-tool cms-editorjs-tool--image');
            var url = createInput('url', 'form-control mb-2', (this.data.file && this.data.file.url) || this.data.url || '', 'Bild-URL');
            var caption = createInput('text', 'form-control mb-2', this.data.caption || '', 'Bildunterschrift');
            var upload = createInput('file', 'form-control form-control-sm', '', '');
            var status = createElement('div', 'form-hint mt-1');
            upload.accept = 'image/*';
            upload.addEventListener('change', this.uploadSelectedFile.bind(this, url, status));
            wrapper.appendChild(url);
            wrapper.appendChild(caption);
            wrapper.appendChild(upload);
            wrapper.appendChild(status);
            this.nodes = { url: url, caption: caption, status: status };
            return wrapper;
        }
        uploadSelectedFile(urlInput, status, event) {
            var file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
            var endpoint = this.config.uploadUrl || '';
            var body;
            if (!file || !endpoint || typeof fetch !== 'function') {
                return;
            }
            body = new FormData();
            body.append('action', 'upload_image');
            body.append('image', file);
            body.append('csrf_token', this.config.csrfToken || '');
            status.textContent = 'Upload läuft ...';
            fetch(endpoint, {
                method: 'POST',
                body: body,
                credentials: 'same-origin',
                headers: this.config.csrfToken ? { 'X-CSRF-Token': this.config.csrfToken } : {}
            }).then(function (response) {
                return response.json();
            }).then(function (payload) {
                var uploadedUrl = payload && payload.file && payload.file.url ? payload.file.url : '';
                if (!uploadedUrl) {
                    throw new Error(payload && payload.message ? payload.message : 'Upload ohne Bild-URL.');
                }
                urlInput.value = uploadedUrl;
                status.textContent = 'Upload abgeschlossen.';
            }).catch(function (error) {
                status.textContent = error && error.message ? error.message : 'Upload fehlgeschlagen.';
                logWarn('Image upload failed.', error);
            });
        }
        save() {
            return {
                file: { url: this.nodes.url ? this.nodes.url.value.trim() : '' },
                caption: this.nodes.caption ? this.nodes.caption.value.trim() : '',
                withBorder: false,
                withBackground: false,
                stretched: false
            };
        }
    }

    class QuoteTool {
        constructor(options) {
            this.data = options.data || {};
            this.nodes = {};
        }
        static get toolbox() {
            return { title: 'Zitat', icon: '“”' };
        }
        render() {
            var wrapper = createElement('div', 'cms-editorjs-tool cms-editorjs-tool--quote');
            var text = createEditable('form-control cms-editorjs-editable mb-2', this.data.text || '', 'Zitat');
            var caption = createInput('text', 'form-control', this.data.caption || '', 'Quelle');
            wrapper.appendChild(text);
            wrapper.appendChild(caption);
            this.nodes = { text: text, caption: caption };
            return wrapper;
        }
        save() {
            return {
                text: this.nodes.text ? this.nodes.text.innerHTML.trim() : '',
                caption: this.nodes.caption ? this.nodes.caption.value.trim() : '',
                alignment: 'left'
            };
        }
    }

    class CodeTool {
        constructor(options) {
            this.data = options.data || {};
            this.nodes = {};
        }
        static get toolbox() {
            return { title: 'Code', icon: '&lt;/&gt;' };
        }
        render() {
            var textarea = document.createElement('textarea');
            textarea.className = 'form-control font-monospace';
            textarea.rows = 8;
            textarea.value = this.data.code || '';
            this.nodes.textarea = textarea;
            return textarea;
        }
        save() {
            return { code: this.nodes.textarea ? this.nodes.textarea.value : '' };
        }
    }

    class TableTool {
        constructor(options) {
            this.data = options.data || {};
            this.nodes = {};
        }
        static get toolbox() {
            return { title: 'Tabelle', icon: '▦' };
        }
        render() {
            var textarea = document.createElement('textarea');
            var rows = Array.isArray(this.data.content) && this.data.content.length > 0 ? this.data.content : [['', '', ''], ['', '', ''], ['', '', '']];
            textarea.className = 'form-control font-monospace';
            textarea.rows = Math.max(4, rows.length);
            textarea.value = rows.map(function (row) {
                return (Array.isArray(row) ? row : []).join(' | ');
            }).join('\n');
            textarea.placeholder = 'Zellen mit | trennen, eine Zeile pro Tabellenzeile';
            this.nodes.textarea = textarea;
            return textarea;
        }
        save() {
            return {
                withHeadings: !!this.data.withHeadings,
                content: String(this.nodes.textarea ? this.nodes.textarea.value : '').split('\n').map(function (row) {
                    return row.split('|').map(function (cell) {
                        return cell.trim();
                    });
                }).filter(function (row) {
                    return row.some(Boolean);
                })
            };
        }
    }

    class DelimiterTool {
        static get toolbox() {
            return { title: 'Trenner', icon: '---' };
        }
        render() {
            return createElement('hr', 'cms-editorjs-delimiter');
        }
        save() {
            return {};
        }
    }

    function resolveToolClass(toolName) {
        var globalNames = TOOL_GLOBALS[toolName];
        var toolClass = null;

        if (!Array.isArray(globalNames)) {
            globalNames = globalNames ? [globalNames] : [];
        }

        globalNames.some(function (globalName) {
            if (typeof window[globalName] === 'function') {
                toolClass = window[globalName];
                return true;
            }

            return false;
        });

        if (typeof toolClass !== 'function') {
            throw new Error('EditorJS tool missing: ' + toolName + ' (' + String(globalNames.join('|') || 'unknown') + ')');
        }

        return toolClass;
    }

    function tryResolveToolClass(toolName) {
        try {
            return resolveToolClass(toolName);
        } catch (error) {
            logWarn('Optional EditorJS tool skipped: ' + toolName, error);
            return null;
        }
    }

    function tryResolvePluginClass(pluginName) {
        var globalNames = PLUGIN_GLOBALS[pluginName];
        var pluginClass = null;

        if (!Array.isArray(globalNames)) {
            globalNames = globalNames ? [globalNames] : [];
        }

        globalNames.some(function (globalName) {
            if (typeof window[globalName] === 'function') {
                pluginClass = window[globalName];
                return true;
            }

            return false;
        });

        if (typeof pluginClass !== 'function') {
            logWarn('Optional EditorJS plugin skipped: ' + pluginName, { globals: globalNames });
            return null;
        }

        return pluginClass;
    }

    function addTool(tools, toolName, definition, required) {
        var toolClass = required ? resolveToolClass(toolName) : tryResolveToolClass(toolName);

        if (!toolClass) {
            return false;
        }

        tools[toolName] = Object.assign({}, definition || {}, { class: toolClass });
        return true;
    }

    function buildMediaUrl(uploadUrl, action) {
        var baseUrl = uploadUrl || '/api/media';
        var separator = String(baseUrl).indexOf('?') === -1 ? '?' : '&';

        return String(baseUrl) + separator + 'action=' + encodeURIComponent(action);
    }

    function normalizeImageResponse(payload) {
        var response = payload && typeof payload === 'object' ? payload : {};
        var file = response.file && typeof response.file === 'object' ? response.file : {};
        var url = String(file.url || response.url || '');

        if (!response.success || url === '') {
            throw new Error(String(response.message || 'Bild konnte nicht verarbeitet werden.'));
        }

        return {
            success: 1,
            file: Object.assign({}, file, { url: url })
        };
    }

    function buildImageUploader(uploadUrl, csrfToken) {
        return {
            uploadByFile: function (file) {
                var body = new FormData();

                body.append('image', file);
                body.append('csrf_token', csrfToken || '');

                return fetch(buildMediaUrl(uploadUrl, 'upload_image'), {
                    method: 'POST',
                    body: body,
                    credentials: 'same-origin',
                    headers: csrfToken ? { 'X-CSRF-Token': csrfToken } : {}
                }).then(function (response) {
                    return response.json();
                }).then(normalizeImageResponse);
            },
            uploadByUrl: function (url) {
                return fetch(buildMediaUrl(uploadUrl, 'fetch_image'), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: Object.assign({
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }, csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
                    body: JSON.stringify({ url: String(url || '') })
                }).then(function (response) {
                    return response.json();
                }).then(normalizeImageResponse);
            }
        };
    }

    function buildFileUploader(uploadUrl, csrfToken) {
        return {
            uploadByFile: function (file) {
                var body = new FormData();

                body.append('file', file);
                body.append('attachment', file);
                body.append('csrf_token', csrfToken || '');

                return fetch(buildMediaUrl(uploadUrl, 'upload_file'), {
                    method: 'POST',
                    body: body,
                    credentials: 'same-origin',
                    headers: csrfToken ? { 'X-CSRF-Token': csrfToken } : {}
                }).then(function (response) {
                    return response.json();
                }).then(function (payload) {
                    var response = payload && typeof payload === 'object' ? payload : {};
                    var filePayload = response.file && typeof response.file === 'object' ? response.file : {};
                    var url = String(filePayload.url || response.url || '');

                    if (!response.success || url === '') {
                        throw new Error(String(response.message || 'Datei konnte nicht verarbeitet werden.'));
                    }

                    return {
                        success: 1,
                        file: Object.assign({}, filePayload, {
                            url: url,
                            name: filePayload.name || (file && file.name) || 'download'
                        })
                    };
                });
            }
        };
    }

    function buildTools(uploadUrl, csrfToken) {
        var tools = {};
        var inlineToolbar = ['bold', 'italic', 'link'];

        if (addTool(tools, 'inlineCode', {}, false)) {
            inlineToolbar.push('inlineCode');
        }

        if (addTool(tools, 'underline', {}, false)) {
            inlineToolbar.push('underline');
        }

        if (addTool(tools, 'spoiler', {}, false)) {
            inlineToolbar.push('spoiler');
        }

        addTool(tools, 'paragraph', {
            inlineToolbar: inlineToolbar,
            config: { placeholder: 'Text schreiben ...' }
        }, true);

        addTool(tools, 'header', {
            inlineToolbar: inlineToolbar,
            config: { levels: [2, 3, 4], defaultLevel: 2 }
        }, true);

        addTool(tools, 'list', {
            inlineToolbar: inlineToolbar,
            config: { defaultStyle: 'unordered', maxLevel: 4 }
        }, true);

        addTool(tools, 'image', {
            inlineToolbar: inlineToolbar,
            config: {
                uploader: buildImageUploader(uploadUrl, csrfToken),
                captionPlaceholder: 'Bildunterschrift',
                buttonContent: 'Bild auswählen'
            }
        }, true);

        addTool(tools, 'quote', {
            inlineToolbar: inlineToolbar,
            config: {
                quotePlaceholder: 'Zitat',
                captionPlaceholder: 'Quelle'
            }
        }, true);

        addTool(tools, 'code', {}, true);
        addTool(tools, 'table', {
            inlineToolbar: inlineToolbar,
            config: { rows: 3, cols: 3, maxRows: 20, maxCols: 10, maxrows: 20, maxcols: 10 }
        }, true);
        addTool(tools, 'delimiter', {}, true);

        addTool(tools, 'embed', {
            inlineToolbar: inlineToolbar,
            config: {
                services: {
                    youtube: true,
                    vimeo: true,
                    twitter: true,
                    instagram: true,
                    codepen: true
                }
            }
        }, false);
        addTool(tools, 'linkTool', {
            inlineToolbar: inlineToolbar,
            config: { endpoint: buildMediaUrl(uploadUrl, 'fetch_link') }
        }, false);
        addTool(tools, 'attaches', {
            config: {
                uploader: buildFileUploader(uploadUrl, csrfToken),
                buttonText: 'Datei auswählen'
            }
        }, false);
        addTool(tools, 'warning', {
            inlineToolbar: inlineToolbar,
            config: { titlePlaceholder: 'Titel', messagePlaceholder: 'Hinweistext' }
        }, false);
        addTool(tools, 'raw', {}, false);
        addTool(tools, 'accordion', {
            inlineToolbar: inlineToolbar
        }, false);
        addTool(tools, 'imageGallery', {
            config: {}
        }, false);

        return tools;
    }

    function getEditorHistoryState(editor) {
        var undo = editor && editor.cmsPlugins ? editor.cmsPlugins.undo : null;

        return {
            canUndo: !!(undo && typeof undo.canUndo === 'function' && undo.canUndo()),
            canRedo: !!(undo && typeof undo.canRedo === 'function' && undo.canRedo())
        };
    }

    function dispatchPluginState(holder, editor) {
        var detail = {
            plugins: editor && editor.cmsPlugins ? Object.keys(editor.cmsPlugins) : [],
            history: getEditorHistoryState(editor)
        };

        if (holder && typeof holder.dispatchEvent === 'function' && typeof window.CustomEvent === 'function') {
            holder.dispatchEvent(new CustomEvent('cms-editor-plugin-state', { detail: detail }));
        }

        if (editor) {
            editor.cmsPluginState = detail;
        }
    }

    function initializeEditorPlugins(editor, holder, normalizedData) {
        var UndoPlugin = tryResolvePluginClass('undo');
        var DragDropPlugin = tryResolvePluginClass('dragDrop');
        var undoInstance;

        editor.cmsPlugins = editor.cmsPlugins || {};

        if (UndoPlugin && !editor.cmsPlugins.undo) {
            try {
                undoInstance = new UndoPlugin({
                    editor: editor,
                    maxLength: 80,
                    config: { debounceTimer: 350 },
                    onUpdate: function () {
                        dispatchPluginState(holder, editor);
                    }
                });
                if (undoInstance && typeof undoInstance.initialize === 'function') {
                    undoInstance.initialize(normalizedData || { blocks: [] });
                }
                editor.cmsPlugins.undo = undoInstance;
            } catch (error) {
                logWarn('EditorJS undo plugin init failed.', error);
            }
        }

        if (DragDropPlugin && !editor.cmsPlugins.dragDrop) {
            try {
                editor.cmsPlugins.dragDrop = new DragDropPlugin(editor, '2px solid #2563eb');
            } catch (error) {
                logWarn('EditorJS drag/drop plugin init failed.', error);
            }
        }

        dispatchPluginState(holder, editor);
    }

    function createCmsEditor(holderId, initialData, uploadUrl, csrfToken, options) {
        var holder = document.getElementById(holderId);
        var editor;
        var normalizedData;
        var tools;
        var resolvedOptions = options && typeof options === 'object' ? options : {};

        if (!holder) {
            throw new Error('EditorJS holder missing: ' + holderId);
        }

        if (typeof window.EditorJS !== 'function') {
            throw new Error('EditorJS UMD core missing.');
        }

        normalizedData = normalizeInitialData(initialData);
        tools = buildTools(uploadUrl, csrfToken);
        holder.dataset.editorState = 'loading';

        editor = new window.EditorJS({
            holder: holderId,
            data: normalizedData,
            tools: tools,
            defaultBlock: 'paragraph',
            minHeight: 320,
            autofocus: false,
            placeholder: 'Schreibe Inhalt oder wähle einen Block ...',
            onReady: function () {
                holder.dataset.editorState = 'editor';
                initializeEditorPlugins(editor, holder, normalizedData);
                logInfo('Editor ready.', { holderId: holderId, tools: TOOL_NAMES });
            },
            onChange: function () {
                if (typeof resolvedOptions.onChange !== 'function') {
                    return;
                }
                editor.save().then(function (output) {
                    resolvedOptions.onChange(normalizeInitialData(output));
                }).catch(function (error) {
                    logWarn('Change sync failed.', error);
                });
            }
        });
        editor.cmsAvailableTools = Object.keys(tools);

        return editor;
    }

    function saveEditorToInput(editor, input) {
        if (!editor || typeof editor.save !== 'function' || !input) {
            return Promise.resolve(false);
        }

        return editor.save().then(function (output) {
            input.value = JSON.stringify(normalizeInitialData(output));
            return true;
        });
    }

    window.createCmsEditor = createCmsEditor;
    window.saveEditorToInput = saveEditorToInput;
    window.cmsNormalizeEditorJsData = normalizeInitialData;
    window.cmsEditorJsOrgAssetTools = TOOL_NAMES.slice();
    window.cmsEditorJsOrgAssetPlugins = PLUGIN_NAMES.slice();
    window.cmsEditorJsCoreReady = Promise.resolve(typeof window.EditorJS === 'function');

    if (!window.cmsEditorDebug || typeof window.cmsEditorDebug !== 'object') {
        window.cmsEditorDebug = {};
    }
    window.cmsEditorDebug.orgAssetBoot = {
        version: VERSION,
        hasCore: typeof window.EditorJS === 'function',
        tools: TOOL_NAMES.slice(),
        plugins: PLUGIN_NAMES.slice(),
        globals: Object.assign({}, TOOL_GLOBALS),
        pluginGlobals: Object.assign({}, PLUGIN_GLOBALS),
        loadedAt: new Date().toISOString()
    };

    logInfo('Factory loaded.', window.cmsEditorDebug.orgAssetBoot);
})();
