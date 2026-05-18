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
        'spacer',
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
    var THEME_PREVIEW_STYLE_CACHE = {};
    var TOOL_GLOBALS = {
        paragraph: ['CmsParagraphTool', 'Paragraph'],
        header: ['CmsHeaderTool', 'Header'],
        list: ['EditorjsList', 'List', 'CmsListTool'],
        image: ['CmsImageTool', 'ImageTool'],
        quote: ['Quote'],
        code: ['CodeTool'],
        table: ['Table'],
        delimiter: ['Delimiter'],
        spacer: ['CmsSpacerTool'],
        embed: ['Embed'],
        linkTool: ['LinkTool'],
        attaches: ['AttachesTool'],
        warning: ['Warning'],
        raw: ['RawTool'],
        accordion: ['Accordion'],
        imageGallery: ['CmsImageGalleryTool', 'ImageGallery'],
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
        space: 'spacer',
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

    function sanitizeEditableUrl(value) {
        var url = String(value || '').trim();
        var parser;

        if (url === '' || /[\u0000\r\n<>"']/.test(url)) {
            return '';
        }

        if (/^(?:\/|\.\/|\.\.\/|#)/.test(url)) {
            return url;
        }

        try {
            parser = new URL(url, window.location.origin);
        } catch (_error) {
            return '';
        }

        return /^(https?:|mailto:|tel:)$/i.test(parser.protocol) ? url : '';
    }

    function sanitizeEditableNode(node) {
        var allowedTags = {
            a: true,
            b: true,
            strong: true,
            i: true,
            em: true,
            u: true,
            code: true,
            mark: true,
            sub: true,
            sup: true,
            br: true,
            span: true
        };
        var blockedTags = {
            script: true,
            style: true,
            object: true,
            embed: true,
            iframe: true,
            form: true,
            input: true,
            button: true,
            textarea: true,
            select: true,
            svg: true,
            math: true,
            link: true,
            meta: true
        };
        var children = Array.prototype.slice.call(node.childNodes || []);

        children.forEach(function (child) {
            var tagName;
            var href;
            var title;
            var className;
            var originalAttributes = {};

            if (child.nodeType !== 1) {
                return;
            }

            tagName = String(child.tagName || '').toLowerCase();
            sanitizeEditableNode(child);

            if (blockedTags[tagName]) {
                child.parentNode.removeChild(child);
                return;
            }

            if (!allowedTags[tagName]) {
                while (child.firstChild) {
                    child.parentNode.insertBefore(child.firstChild, child);
                }
                child.parentNode.removeChild(child);
                return;
            }

            Array.prototype.slice.call(child.attributes || []).forEach(function (attribute) {
                originalAttributes[String(attribute.name || '').toLowerCase()] = String(attribute.value || '');
                child.removeAttribute(attribute.name);
            });

            if (tagName === 'a') {
                href = sanitizeEditableUrl(originalAttributes.href || '');
                if (href === '') {
                    while (child.firstChild) {
                        child.parentNode.insertBefore(child.firstChild, child);
                    }
                    child.parentNode.removeChild(child);
                    return;
                }

                child.setAttribute('href', href);
                title = String(originalAttributes.title || '').trim();
                if (title !== '') {
                    child.setAttribute('title', title.slice(0, 160));
                }
                if (/^https?:\/\//i.test(href)) {
                    child.setAttribute('target', '_blank');
                    child.setAttribute('rel', 'noopener noreferrer');
                }
            }

            if (tagName === 'span') {
                className = String(originalAttributes.class || '');
                if (/\btg-spoiler\b/.test(className)) {
                    child.setAttribute('class', 'tg-spoiler');
                }
            }

            if (tagName === 'code') {
                className = String(originalAttributes.class || '');
                if (/\blanguage-[a-z0-9_+\-#]+\b/i.test(className)) {
                    child.setAttribute('class', className.match(/\blanguage-[a-z0-9_+\-#]+\b/i)[0].toLowerCase());
                }
            }
        });
    }

    function sanitizeEditableHtml(html) {
        var template = document.createElement('template');

        template.innerHTML = String(html || '');
        sanitizeEditableNode(template.content);

        return template.innerHTML;
    }

    function validateEditorImageFile(file) {
        var maxSize = 25 * 1024 * 1024;
        var mimeType = file ? String(file.type || '') : '';
        var fileName = file ? String(file.name || '') : '';
        var hasAllowedMime = /^image\/(?:jpeg|jpg|png|gif|webp|bmp|x-icon|vnd\.microsoft\.icon)$/i.test(mimeType);
        var hasAllowedExtension = /\.(?:jpe?g|png|gif|webp|bmp|ico)$/i.test(fileName);

        if (!file) {
            return 'Keine Datei ausgewählt.';
        }

        if (!hasAllowedMime && !hasAllowedExtension) {
            return 'Nur Bilddateien sind erlaubt.';
        }

        if (Number(file.size || 0) > maxSize) {
            return 'Bild ist zu groß. Maximal erlaubt sind 25 MB.';
        }

        return '';
    }

    function sanitizePreviewFontStack(value) {
        var stack = String(value || '').trim();

        return /^[a-z0-9\s'",.\-]+$/i.test(stack) ? stack : '';
    }

    function sanitizePreviewFontSize(value, fallback) {
        var size = parseFloat(String(value || '').replace('px', ''));

        if (!isFinite(size)) {
            return fallback;
        }

        return Math.max(12, Math.min(24, size)) + 'px';
    }

    function sanitizePreviewLineHeight(value, fallback) {
        var lineHeight = parseFloat(String(value || ''));

        if (!isFinite(lineHeight)) {
            return fallback;
        }

        return String(Math.max(1.1, Math.min(2.2, lineHeight)));
    }

    function sanitizePreviewCssValue(value, fallback) {
        var cssValue = String(value || '').trim();

        if (cssValue === '' || cssValue.length > 240 || /[;{}<>]/.test(cssValue) || /(?:expression|javascript:|url\s*\()/i.test(cssValue)) {
            return fallback || '';
        }

        return cssValue;
    }

    function parseCssDeclarations(block) {
        var declarations = {};

        String(block || '').split(';').forEach(function (part) {
            var separatorIndex = part.indexOf(':');
            var property;
            var value;

            if (separatorIndex <= 0) {
                return;
            }

            property = part.slice(0, separatorIndex).trim().toLowerCase();
            value = sanitizePreviewCssValue(part.slice(separatorIndex + 1).trim(), '');
            if (property !== '' && value !== '') {
                declarations[property] = value;
            }
        });

        return declarations;
    }

    function selectorMatchesAny(selectorText, candidates) {
        var selectors = String(selectorText || '').split(',').map(function (selector) {
            return selector.trim().toLowerCase();
        }).filter(Boolean);

        return selectors.some(function (selector) {
            return candidates.some(function (candidate) {
                var normalized = String(candidate || '').toLowerCase();

                return selector === normalized || selector.endsWith(' ' + normalized) || selector.indexOf(normalized + '.') !== -1;
            });
        });
    }

    function collectCssDeclarations(css, candidates) {
        var cleanedCss = String(css || '').replace(/\/\*[\s\S]*?\*\//g, '');
        var declarations = {};
        var rulePattern = /([^{}]+)\{([^{}]*)\}/g;
        var match;

        while ((match = rulePattern.exec(cleanedCss)) !== null) {
            if (selectorMatchesAny(match[1], candidates)) {
                declarations = Object.assign(declarations, parseCssDeclarations(match[2]));
            }
        }

        return declarations;
    }

    function applyDeclarationValue(holder, declarations, propertyName, variableName) {
        var value = declarations[propertyName];

        if (holder && value) {
            holder.style.setProperty(variableName, value);
        }
    }

    function applyThemePreviewCss(holder, css) {
        var rootDeclarations = collectCssDeclarations(css, [':root']);
        var contentDeclarations = collectCssDeclarations(css, ['body', '.entry-content', '.post-content', '.page-content', '.cms-content', 'article']);
        var headingDeclarations = collectCssDeclarations(css, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']);
        var h2Declarations = collectCssDeclarations(css, ['h2']);
        var h3Declarations = collectCssDeclarations(css, ['h3']);
        var h4Declarations = collectCssDeclarations(css, ['h4']);

        if (!holder || !holder.style) {
            return;
        }

        Object.keys(rootDeclarations).forEach(function (propertyName) {
            if (propertyName.indexOf('--') === 0) {
                holder.style.setProperty(propertyName, rootDeclarations[propertyName]);
            }
        });

        applyDeclarationValue(holder, contentDeclarations, 'font-family', '--cms-editor-preview-body-font');
        applyDeclarationValue(holder, contentDeclarations, 'font-size', '--cms-editor-preview-font-size');
        applyDeclarationValue(holder, contentDeclarations, 'line-height', '--cms-editor-preview-line-height');
        applyDeclarationValue(holder, headingDeclarations, 'font-family', '--cms-editor-preview-heading-font');
        applyDeclarationValue(holder, headingDeclarations, 'font-weight', '--cms-editor-preview-heading-weight');
        applyDeclarationValue(holder, h2Declarations, 'font-size', '--cms-editor-preview-h2-size');
        applyDeclarationValue(holder, h3Declarations, 'font-size', '--cms-editor-preview-h3-size');
        applyDeclarationValue(holder, h4Declarations, 'font-size', '--cms-editor-preview-h4-size');
    }

    function loadEditorThemePreviewStyles(holder, typography) {
        var settings = typography && typeof typography === 'object' ? typography : {};
        var stylesheetUrl = sanitizePreviewCssValue(settings.themeStylesheetUrl || settings.stylesheetUrl || '', '');

        if (!holder || stylesheetUrl === '' || typeof fetch !== 'function') {
            return;
        }

        if (THEME_PREVIEW_STYLE_CACHE[stylesheetUrl]) {
            THEME_PREVIEW_STYLE_CACHE[stylesheetUrl].then(function (css) {
                applyThemePreviewCss(holder, css);
            }).catch(function (error) {
                logWarn('Theme preview stylesheet could not be applied.', error);
            });
            return;
        }

        THEME_PREVIEW_STYLE_CACHE[stylesheetUrl] = fetch(stylesheetUrl, { credentials: 'same-origin' }).then(function (response) {
            if (!response.ok) {
                throw new Error('Theme stylesheet HTTP ' + response.status);
            }

            return response.text();
        });

        THEME_PREVIEW_STYLE_CACHE[stylesheetUrl].then(function (css) {
            applyThemePreviewCss(holder, css);
        }).catch(function (error) {
            logWarn('Theme preview stylesheet could not be loaded.', error);
        });
    }

    function applyEditorPreviewTypography(holder, typography) {
        var settings = typography && typeof typography === 'object' ? typography : {};
        var bodyFont = sanitizePreviewFontStack(settings.bodyFontStack || settings.bodyFont || '');
        var headingFont = sanitizePreviewFontStack(settings.headingFontStack || settings.headingFont || bodyFont);
        var fontSize = sanitizePreviewFontSize(settings.fontSize || settings.fontSizeBase, '16px');
        var lineHeight = sanitizePreviewLineHeight(settings.lineHeight, '1.65');

        if (!holder || !holder.style) {
            return;
        }

        holder.classList.add('cms-editor-theme-preview');
        if (bodyFont !== '') {
            holder.style.setProperty('--cms-editor-preview-body-font', bodyFont);
        }
        if (headingFont !== '') {
            holder.style.setProperty('--cms-editor-preview-heading-font', headingFont);
        }
        holder.style.setProperty('--cms-editor-preview-font-size', fontSize);
        holder.style.setProperty('--cms-editor-preview-line-height', lineHeight);
        if (settings.activeThemeSlug) {
            holder.dataset.cmsActiveTheme = String(settings.activeThemeSlug).replace(/[^a-z0-9_-]/gi, '').slice(0, 80);
        }
        loadEditorThemePreviewStyles(holder, settings);
    }

    function autoresizeTextarea(textarea, minRows) {
        var lineHeight;

        if (!textarea) {
            return;
        }

        textarea.rows = Math.max(1, minRows || 1);

        function resize() {
            textarea.style.height = 'auto';
            lineHeight = parseFloat(window.getComputedStyle(textarea).lineHeight || '20') || 20;
            textarea.style.height = Math.max(textarea.scrollHeight, lineHeight * Math.max(1, minRows || 1) + 18) + 'px';
        }

        textarea.addEventListener('input', resize);
        window.setTimeout(resize, 0);
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

        if (type === 'paragraph' || type === 'header') {
            return { type: type, data: normalizeTextBlockData(data, type) };
        }

        if (type === 'image') {
            return { type: type, data: normalizeImageData(data) };
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
                data: normalizeGalleryData(data)
            };
        }

        if (type === 'spacer') {
            return {
                type: type,
                data: normalizeSpacerData(data)
            };
        }

        return { type: type, data: data };
    }

    function normalizeTextBlockData(data, type) {
        var value = data && typeof data === 'object' ? data : {};
        var alignment = String(value.alignment || 'left');
        var spacing = String(value.spacing || 'normal');

        if (['left', 'center', 'right', 'justify'].indexOf(alignment) === -1) {
            alignment = 'left';
        }
        if (['compact', 'normal', 'relaxed', 'loose'].indexOf(spacing) === -1) {
            spacing = 'normal';
        }

        return Object.assign({}, value, {
            text: String(value.text || ''),
            alignment: alignment,
            spacing: spacing,
            level: type === 'header' ? Math.min(4, Math.max(2, parseInt(value.level || 2, 10) || 2)) : value.level
        });
    }

    function normalizeImageData(data) {
        var imageData = data && typeof data === 'object' ? data : {};
        var file = imageData.file && typeof imageData.file === 'object' ? imageData.file : {};
        var alignment = String(imageData.alignment || imageData.align || 'center');
        var size = String(imageData.size || imageData.widthPreset || (imageData.stretched ? 'full' : 'normal'));
        var borderStyle = String(imageData.borderStyle || (imageData.withBorder ? 'thin' : 'none'));

        if (['left', 'center', 'right'].indexOf(alignment) === -1) {
            alignment = 'center';
        }
        if (['normal', 'wide', 'full'].indexOf(size) === -1) {
            size = 'normal';
        }
        if (['none', 'thin', 'medium', 'thick'].indexOf(borderStyle) === -1) {
            borderStyle = imageData.withBorder ? 'thin' : 'none';
        }

        return Object.assign({}, imageData, {
            file: Object.assign({}, file, { url: String(file.url || imageData.url || '') }),
            caption: String(imageData.caption || ''),
            alignment: alignment,
            size: size,
            widthPreset: size,
            borderStyle: borderStyle,
            withBorder: borderStyle !== 'none',
            withBackground: !!imageData.withBackground,
            stretched: size === 'full' || !!imageData.stretched,
            rounded: imageData.rounded !== false,
            shadow: !!imageData.shadow
        });
    }

    function normalizeGalleryData(data) {
        var galleryData = data && typeof data === 'object' ? data : {};
        var columns = parseInt(galleryData.columns || 3, 10) || 3;
        var images = Array.isArray(galleryData.images) ? galleryData.images : [];

        if ([2, 3, 4, 6].indexOf(columns) === -1) {
            columns = 3;
        }

        if (images.length === 0 && Array.isArray(galleryData.urls)) {
            images = galleryData.urls.map(function (url) {
                return { file: { url: String(url || '') }, caption: '' };
            });
        }

        images = images.map(function (item) {
            var source = item && typeof item === 'object' ? item : {};
            var file = source.file && typeof source.file === 'object' ? source.file : source;
            var url = String(file.url || source.url || '');

            if (url === '') {
                return null;
            }

            return {
                file: Object.assign({}, file, { url: url }),
                caption: String(source.caption || file.caption || '')
            };
        }).filter(Boolean);

        return {
            columns: columns,
            images: images,
            urls: images.map(function (item) {
                return item.file.url;
            })
        };
    }

    function normalizeSpacerData(data) {
        var allowedHeights = [15, 25, 40, 60, 75, 100];
        var height = parseInt(data && data.height ? data.height : 40, 10) || 40;

        if (allowedHeights.indexOf(height) === -1) {
            height = 40;
        }

        return {
            height: height,
            preset: height + 'px'
        };
    }

    function normalizeListData(data) {
        var style = data && typeof data.style === 'string' ? data.style : 'unordered';
        var meta = data && data.meta && typeof data.meta === 'object' ? data.meta : {};
        var items = Array.isArray(data && data.items) ? data.items : [];

        if (['ordered', 'unordered', 'checklist'].indexOf(style) === -1) {
            style = 'unordered';
        }

        if (items.length === 0 && Array.isArray(data && data.content)) {
            items = data.content;
        }

        return {
            style: style,
            meta: meta,
            items: normalizeListItems(items, style)
        };
    }

    function normalizeListItems(items, style) {
        return items.map(function (item) {
            var content;
            var meta;
            var children;

            if (typeof item === 'string') {
                return {
                    content: sanitizeEditableHtml(item),
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
                content: sanitizeEditableHtml(content),
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
                        items: listElementToItems(child, tag === 'ol' ? 'ordered' : 'unordered')
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

    function listElementToItems(list, style) {
        return Array.prototype.slice.call(list.children || []).filter(function (child) {
            return String(child.tagName || '').toLowerCase() === 'li';
        }).map(function (item) {
            var clone = item.cloneNode(true);
            var nestedItems = [];

            Array.prototype.slice.call(clone.children || []).forEach(function (child) {
                var tagName = String(child.tagName || '').toLowerCase();
                if (tagName === 'ul' || tagName === 'ol') {
                    nestedItems = nestedItems.concat(listElementToItems(child, tagName === 'ol' ? 'ordered' : 'unordered'));
                    child.parentNode.removeChild(child);
                }
            });

            return {
                content: sanitizeEditableHtml(clone.innerHTML || clone.textContent || ''),
                meta: style === 'checklist' ? { checked: false } : {},
                items: nestedItems
            };
        }).filter(function (item) {
            return stripTags(item.content || '') !== '' || item.items.length > 0;
        });
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
        element.innerHTML = sanitizeEditableHtml(html || '');
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

    function applyTextBlockPresentation(wrapper, editable, alignment, spacing) {
        var safeAlignment = ['left', 'center', 'right', 'justify'].indexOf(alignment) !== -1 ? alignment : 'left';
        var safeSpacing = ['compact', 'normal', 'relaxed', 'loose'].indexOf(spacing) !== -1 ? spacing : 'normal';

        if (wrapper) {
            wrapper.dataset.alignment = safeAlignment;
            wrapper.dataset.spacing = safeSpacing;
        }
        if (editable) {
            editable.style.textAlign = safeAlignment === 'justify' ? 'justify' : safeAlignment;
            editable.style.marginBottom = safeSpacing === 'compact' ? '0.25rem' : (safeSpacing === 'relaxed' ? '1rem' : (safeSpacing === 'loose' ? '1.6rem' : '0.65rem'));
        }
    }

    function applyHeaderLevelPresentation(wrapper, editable, level) {
        var safeLevel = Math.min(4, Math.max(2, parseInt(level || 2, 10) || 2));

        if (wrapper) {
            wrapper.dataset.level = String(safeLevel);
        }
        if (editable) {
            editable.dataset.level = String(safeLevel);
        }
    }

    function insertLineBreakAtSelection() {
        var selection = window.getSelection ? window.getSelection() : null;
        var range;
        var br;

        if (!selection || selection.rangeCount === 0) {
            return;
        }

        range = selection.getRangeAt(0);
        range.deleteContents();
        br = document.createElement('br');
        range.insertNode(br);
        range.setStartAfter(br);
        range.collapse(true);
        selection.removeAllRanges();
        selection.addRange(range);
    }

    function getFragmentHtml(fragment) {
        var wrapper = document.createElement('div');

        wrapper.appendChild(fragment);
        return sanitizeEditableHtml(wrapper.innerHTML || '');
    }

    function splitEditableAtSelection(editable) {
        var selection = window.getSelection ? window.getSelection() : null;
        var range;
        var afterRange;
        var afterHtml;

        if (!selection || selection.rangeCount === 0 || !editable.contains(selection.anchorNode)) {
            return '';
        }

        range = selection.getRangeAt(0);
        if (!range.collapsed) {
            range.deleteContents();
        }

        afterRange = document.createRange();
        afterRange.setStart(range.startContainer, range.startOffset);
        afterRange.setEnd(editable, editable.childNodes.length);
        afterHtml = getFragmentHtml(afterRange.extractContents());

        editable.innerHTML = sanitizeEditableHtml(editable.innerHTML || '');
        return afterHtml;
    }

    function insertParagraphAfterCurrent(api, text) {
        var blocks = api && api.blocks ? api.blocks : null;
        var index = -1;

        if (!blocks || typeof blocks.insert !== 'function') {
            insertLineBreakAtSelection();
            return;
        }

        if (typeof blocks.getCurrentBlockIndex === 'function') {
            try {
                index = blocks.getCurrentBlockIndex();
            } catch (_error) {
                index = -1;
            }
        }

        try {
            blocks.insert('paragraph', { text: sanitizeEditableHtml(text || '') }, undefined, Math.max(0, index + 1), true);
        } catch (_error) {
            blocks.insert('paragraph', { text: sanitizeEditableHtml(text || '') });
        }

        if (api && api.caret && typeof api.caret.setToBlock === 'function') {
            window.setTimeout(function () {
                try {
                    api.caret.setToBlock(Math.max(0, index + 1), 'start');
                } catch (_error) {
                    // Caret placement is a UX enhancement; content insertion already succeeded.
                }
            }, 0);
        }
    }

    function bindEditableEnterBehavior(editable, api) {
        if (!editable || editable.dataset.cmsEnterBound === '1') {
            return;
        }

        editable.dataset.cmsEnterBound = '1';
        editable.addEventListener('keydown', function (event) {
            var trailingHtml;

            if (event.key !== 'Enter' || event.isComposing) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            if (event.shiftKey || event.altKey) {
                insertLineBreakAtSelection();
                return;
            }

            trailingHtml = splitEditableAtSelection(editable);
            insertParagraphAfterCurrent(api, trailingHtml);
        });
    }

    class ParagraphTool {
        constructor(options) {
            this.data = normalizeTextBlockData(options.data || {}, 'paragraph');
            this.api = options.api || null;
            this.nodes = {};
        }
        static get toolbox() {
            return { title: 'Text', icon: '¶' };
        }
        static get conversionConfig() {
            return {
                export: function (data) { return stripTags(data && data.text ? data.text : ''); },
                import: function (text) { return { text: sanitizeEditableHtml(text || '') }; }
            };
        }
        render() {
            var wrapper = createElement('div', 'cms-editorjs-tool cms-editorjs-tool--paragraph');
            var text = createEditable('form-control cms-editorjs-editable cms-editorjs-paragraph', this.data.text || '', 'Text schreiben ...');

            wrapper.appendChild(text);
            this.nodes = { wrapper: wrapper, text: text };
            bindEditableEnterBehavior(text, this.api);
            applyTextBlockPresentation(wrapper, text, this.data.alignment || 'left', this.data.spacing || 'normal');

            return wrapper;
        }
        save() {
            return normalizeTextBlockData({
                text: this.nodes.text ? this.nodes.text.innerHTML.trim() : '',
                alignment: this.nodes.wrapper ? this.nodes.wrapper.dataset.alignment : 'left',
                spacing: this.nodes.wrapper ? this.nodes.wrapper.dataset.spacing : 'normal'
            }, 'paragraph');
        }
    }

    window.CmsParagraphTool = ParagraphTool;

    class HeaderTool {
        constructor(options) {
            this.data = normalizeTextBlockData(options.data || {}, 'header');
            this.api = options.api || null;
            this.nodes = {};
        }
        static get toolbox() {
            return { title: 'Überschrift', icon: '<b>H</b>' };
        }
        static get conversionConfig() {
            return {
                export: function (data) { return stripTags(data && data.text ? data.text : ''); },
                import: function (text) { return { text: sanitizeEditableHtml(text || ''), level: 2 }; }
            };
        }
        render() {
            var wrapper = createElement('div', 'cms-editorjs-tool cms-editorjs-tool--header');
            var text = createEditable('form-control cms-editorjs-editable cms-editorjs-header', this.data.text || '', 'Überschrift');
            var options = this.createHeaderOptions(wrapper, text);

            wrapper.appendChild(text);
            wrapper.appendChild(options);
            this.nodes = { wrapper: wrapper, text: text };
            bindEditableEnterBehavior(text, this.api);
            applyTextBlockPresentation(wrapper, text, this.data.alignment || 'left', this.data.spacing || 'normal');
            applyHeaderLevelPresentation(wrapper, text, this.data.level || 2);
            return wrapper;
        }
        createHeaderOptions(wrapper, text) {
            var options = createElement('div', 'cms-editorjs-floating-options cms-editorjs-header-options');
            var label = createElement('span', 'cms-editorjs-floating-options__label', 'Überschrift');

            options.dataset.cmsEditorUi = 'true';
            options.dataset.mutationFree = 'true';
            options.appendChild(label);

            [2, 3, 4].forEach(function (level) {
                var button = createElement('button', 'cms-editorjs-floating-options__button', 'H' + level);
                button.type = 'button';
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    applyHeaderLevelPresentation(wrapper, text, level);
                });
                options.appendChild(button);
            });

            return options;
        }
        save() {
            return normalizeTextBlockData({
                text: this.nodes.text ? this.nodes.text.innerHTML.trim() : '',
                level: Math.min(4, Math.max(2, parseInt(this.nodes.wrapper ? this.nodes.wrapper.dataset.level : '2', 10) || 2)),
                alignment: this.nodes.wrapper ? this.nodes.wrapper.dataset.alignment : 'left',
                spacing: this.nodes.wrapper ? this.nodes.wrapper.dataset.spacing : 'normal'
            }, 'header');
        }
    }

    window.CmsHeaderTool = HeaderTool;

    class ListTool {
        constructor(options) {
            this.data = normalizeListData(options.data || {});
            this.nodes = {};
        }
        static get toolbox() {
            return { title: 'Liste', icon: '☰' };
        }
        static get conversionConfig() {
            return {
                export: function (data) {
                    return normalizeListData(data || {}).items.map(function (item) {
                        return stripTags(item.content || '');
                    }).join('\n');
                },
                import: function (text) {
                    return normalizeListData({
                        style: 'unordered',
                        items: String(text || '').split('\n').map(function (line) { return line.trim(); }).filter(Boolean)
                    });
                }
            };
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
            textarea.value = Array.isArray(this.data.items) ? this.data.items.map(function (item) {
                return stripTags(item && typeof item === 'object' ? item.content : item);
            }).join('\n') : '';
            textarea.placeholder = 'Ein Listenpunkt pro Zeile';
            wrapper.appendChild(style);
            wrapper.appendChild(textarea);
            this.nodes = { style: style, textarea: textarea };
            return wrapper;
        }
        save() {
            return normalizeListData({
                style: this.nodes.style ? this.nodes.style.value : 'unordered',
                items: String(this.nodes.textarea ? this.nodes.textarea.value : '').split('\n').map(function (item) {
                    return item.trim();
                }).filter(Boolean)
            });
        }
    }

    window.CmsListTool = ListTool;

    class ImageTool {
        constructor(options) {
            this.data = normalizeImageData(options.data || {});
            this.config = options.config || {};
            this.nodes = {};
        }
        static get toolbox() {
            return { title: 'Bild', icon: '▧' };
        }
        render() {
            var wrapper = createElement('div', 'cms-editorjs-tool cms-editorjs-tool--image');
            var url = createInput('hidden', '', (this.data.file && this.data.file.url) || this.data.url || '', '');
            var caption = createInput('text', 'form-control mb-2', this.data.caption || '', 'Bildunterschrift');
            var upload = createInput('file', 'form-control form-control-sm', '', '');
            var optionsPanel = createElement('div', 'cms-editorjs-floating-options cms-editorjs-image-options');
            var optionsLabel = createElement('span', 'cms-editorjs-floating-options__label', 'Bild');
            var settings = createElement('div', 'cms-editorjs-image-settings');
            var preview = createElement('figure', 'cms-editorjs-image-preview');
            var previewImage = document.createElement('img');
            var alignment = this.createSelect([
                ['left', 'Links'],
                ['center', 'Mittig'],
                ['right', 'Rechts']
            ], this.data.alignment || 'center');
            var size = this.createSelect([
                ['normal', 'Normal'],
                ['wide', 'Breit'],
                ['full', 'Strecken']
            ], this.data.size || this.data.widthPreset || (this.data.stretched ? 'full' : 'normal'));
            var borderStyle = this.createSelect([
                ['none', 'Kein Rahmen'],
                ['thin', 'Dünn'],
                ['medium', 'Mittel'],
                ['thick', 'Dick']
            ], this.data.borderStyle || (this.data.withBorder ? 'thin' : 'none'));
            var withBackground = this.createCheckbox('Heller Hintergrund', !!this.data.withBackground);
            var rounded = this.createCheckbox('Ecken abrunden', this.data.rounded !== false);
            var shadow = this.createCheckbox('Leichter Schatten', !!this.data.shadow);
            var status = createElement('div', 'form-hint mt-1');
            var updatePreview = this.updatePreview.bind(this, wrapper, preview, previewImage, url, caption, alignment, size, borderStyle, withBackground.input, rounded.input, shadow.input);

            optionsPanel.dataset.cmsEditorUi = 'true';
            optionsPanel.dataset.mutationFree = 'true';
            upload.accept = 'image/*';
            upload.addEventListener('change', this.uploadSelectedFile.bind(this, url, status));

            caption.classList.add('cms-editorjs-image-caption');
            caption.setAttribute('aria-label', 'Bildunterschrift');

            settings.appendChild(this.createSetting('Ausrichtung', alignment));
            settings.appendChild(this.createSetting('Breite', size));
            settings.appendChild(this.createSetting('Rahmen', borderStyle));
            settings.appendChild(withBackground.wrapper);
            settings.appendChild(rounded.wrapper);
            settings.appendChild(shadow.wrapper);

            preview.appendChild(previewImage);
            [url, caption, alignment, size, borderStyle, withBackground.input, rounded.input, shadow.input].forEach(function (element) {
                element.addEventListener('input', updatePreview);
                element.addEventListener('change', updatePreview);
            });

            wrapper.appendChild(url);
            wrapper.appendChild(preview);
            wrapper.appendChild(caption);
            optionsPanel.appendChild(optionsLabel);
            optionsPanel.appendChild(upload);
            optionsPanel.appendChild(settings);
            wrapper.appendChild(optionsPanel);
            wrapper.appendChild(status);
            this.nodes = {
                url: url,
                caption: caption,
                alignment: alignment,
                size: size,
                borderStyle: borderStyle,
                withBackground: withBackground.input,
                rounded: rounded.input,
                shadow: shadow.input,
                preview: preview,
                previewImage: previewImage,
                status: status
            };
            updatePreview();
            return wrapper;
        }
        createSelect(options, value) {
            var select = document.createElement('select');
            select.className = 'form-select form-select-sm';
            options.forEach(function (item) {
                var option = document.createElement('option');
                option.value = item[0];
                option.textContent = item[1];
                select.appendChild(option);
            });
            select.value = value;
            return select;
        }
        createSetting(labelText, control) {
            var label = createElement('label', 'cms-editorjs-image-settings__field');
            var text = createElement('span', '', labelText);
            label.appendChild(text);
            label.appendChild(control);
            return label;
        }
        createCheckbox(labelText, checked) {
            var label = createElement('label', 'cms-editorjs-image-settings__check');
            var input = document.createElement('input');
            var text = createElement('span', '', labelText);
            input.type = 'checkbox';
            input.checked = checked;
            label.appendChild(input);
            label.appendChild(text);
            return { wrapper: label, input: input };
        }
        updatePreview(wrapper, preview, image, urlInput, captionInput, alignmentInput, sizeInput, borderInput, backgroundInput, roundedInput, shadowInput) {
            var url = String(urlInput.value || '').trim();
            var caption = String(captionInput.value || '').trim();
            var alignment = alignmentInput.value || 'center';
            var size = sizeInput.value || 'normal';
            var borderStyle = borderInput.value || 'none';

            preview.className = [
                'cms-editorjs-image-preview',
                'cms-editorjs-image-preview--align-' + alignment,
                'cms-editorjs-image-preview--' + size,
                'cms-editorjs-image-preview--border-' + borderStyle,
                backgroundInput.checked ? 'cms-editorjs-image-preview--background' : '',
                roundedInput.checked ? 'cms-editorjs-image-preview--rounded' : '',
                shadowInput.checked ? 'cms-editorjs-image-preview--shadow' : ''
            ].filter(Boolean).join(' ');

            if (url) {
                image.src = url;
                image.alt = caption;
                preview.hidden = false;
            } else {
                image.removeAttribute('src');
                image.alt = '';
                preview.hidden = true;
            }

            wrapper.classList.toggle('is-empty', !url);
        }
        uploadSelectedFile(urlInput, status, event) {
            var file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
            var endpoint = this.config.uploadUrl || '';
            var validationError = validateEditorImageFile(file);
            var body;

            if (validationError !== '') {
                status.textContent = validationError;
                return;
            }

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
                urlInput.dispatchEvent(new Event('input', { bubbles: true }));
                status.textContent = 'Upload abgeschlossen.';
            }).catch(function (error) {
                status.textContent = error && error.message ? error.message : 'Upload fehlgeschlagen.';
                logWarn('Image upload failed.', error);
            });
        }
        save() {
            var size = this.nodes.size ? this.nodes.size.value : 'normal';
            var borderStyle = this.nodes.borderStyle ? this.nodes.borderStyle.value : 'none';

            return normalizeImageData({
                file: { url: this.nodes.url ? this.nodes.url.value.trim() : '' },
                caption: this.nodes.caption ? this.nodes.caption.value.trim() : '',
                alignment: this.nodes.alignment ? this.nodes.alignment.value : 'center',
                size: size,
                widthPreset: size,
                borderStyle: borderStyle,
                withBorder: borderStyle !== 'none',
                withBackground: !!(this.nodes.withBackground && this.nodes.withBackground.checked),
                stretched: size === 'full',
                rounded: !!(this.nodes.rounded && this.nodes.rounded.checked),
                shadow: !!(this.nodes.shadow && this.nodes.shadow.checked)
            });
        }
    }

    window.CmsImageTool = ImageTool;

    class ImageGalleryTool {
        constructor(options) {
            this.data = normalizeGalleryData(options.data || {});
            this.config = options.config || {};
            this.nodes = {};
            this.images = this.data.images.slice();
        }
        static get toolbox() {
            return { title: 'Galerie', icon: '▦' };
        }
        render() {
            var wrapper = createElement('div', 'cms-editorjs-tool cms-editorjs-tool--gallery');
            var controls = createElement('div', 'cms-editorjs-floating-options cms-editorjs-gallery__controls');
            var controlsLabel = createElement('span', 'cms-editorjs-floating-options__label', 'Galerie');
            var columns = this.createColumnsSelect();
            var upload = createInput('file', 'form-control form-control-sm', '', '');
            var okButton = createElement('button', 'btn btn-sm btn-primary', 'OK – Galerie übernehmen');
            var status = createElement('div', 'form-hint');
            var list = createElement('div', 'cms-editorjs-gallery__grid');

            wrapper.classList.add('cms-editorjs-gallery');
            wrapper.classList.toggle('is-empty', this.images.length === 0);
            controls.dataset.cmsEditorUi = 'true';
            controls.dataset.mutationFree = 'true';
            upload.accept = 'image/*';
            upload.multiple = true;
            okButton.type = 'button';

            controls.appendChild(controlsLabel);
            controls.appendChild(this.createGalleryField('Spalten', columns));
            controls.appendChild(this.createGalleryField('Bilder auswählen', upload));
            controls.appendChild(okButton);
            controls.appendChild(status);
            wrapper.appendChild(list);
            wrapper.appendChild(controls);

            this.nodes = { wrapper: wrapper, columns: columns, upload: upload, okButton: okButton, status: status, list: list };
            this.renderImageList();

            upload.addEventListener('change', this.uploadSelectedFiles.bind(this));
            okButton.addEventListener('click', function () {
                status.textContent = 'Galerie übernommen.';
            });

            return wrapper;
        }
        createColumnsSelect() {
            var select = document.createElement('select');
            select.className = 'form-select form-select-sm';
            [2, 3, 4, 6].forEach(function (columns) {
                var option = document.createElement('option');
                option.value = String(columns);
                option.textContent = String(columns) + ' Spalten';
                select.appendChild(option);
            });
            select.value = String(this.data.columns || 3);
            return select;
        }
        createGalleryField(labelText, control) {
            var label = createElement('label', 'cms-editorjs-gallery__field');
            var text = createElement('span', '', labelText);
            label.appendChild(text);
            label.appendChild(control);
            return label;
        }
        uploadSelectedFiles(event) {
            var files = Array.prototype.slice.call(event.target.files || []).filter(function (file) {
                return file && validateEditorImageFile(file) === '';
            });
            var self = this;
            var queue = Promise.resolve();

            if (files.length === 0) {
                this.nodes.status.textContent = 'Bitte gültige Bilder bis maximal 25 MB auswählen.';
                return;
            }

            this.nodes.status.textContent = files.length === 1 ? 'Bild wird hochgeladen ...' : String(files.length) + ' Bilder werden hochgeladen ...';
            files.forEach(function (file) {
                queue = queue.then(function () {
                    return self.uploadGalleryImage(file).then(function (filePayload) {
                        self.images.push({ file: filePayload, caption: '' });
                        self.renderImageList();
                    });
                });
            });

            queue.then(function () {
                self.nodes.status.textContent = 'Auswahl bereit. Weitere Bilder wählen oder mit OK bestätigen.';
                self.nodes.upload.value = '';
            }).catch(function (error) {
                self.nodes.status.textContent = error && error.message ? error.message : 'Galerie-Upload fehlgeschlagen.';
                logWarn('Gallery upload failed.', error);
            });
        }
        uploadGalleryImage(file) {
            var endpoint = this.config.uploadUrl || '';
            var validationError = validateEditorImageFile(file);
            var body = new FormData();

            if (validationError !== '') {
                return Promise.reject(new Error(validationError));
            }

            if (!endpoint || typeof fetch !== 'function') {
                return Promise.reject(new Error('Upload-Endpunkt ist nicht verfügbar.'));
            }

            body.append('action', 'upload_image');
            body.append('image', file);
            body.append('csrf_token', this.config.csrfToken || '');

            return fetch(endpoint, {
                method: 'POST',
                body: body,
                credentials: 'same-origin',
                headers: this.config.csrfToken ? { 'X-CSRF-Token': this.config.csrfToken } : {}
            }).then(function (response) {
                return response.json();
            }).then(function (payload) {
                var filePayload = payload && payload.file && typeof payload.file === 'object' ? payload.file : {};
                var url = String(filePayload.url || payload && payload.url || '');

                if (!payload || !payload.success || url === '') {
                    throw new Error(String(payload && payload.message ? payload.message : 'Bild konnte nicht hochgeladen werden.'));
                }

                return Object.assign({}, filePayload, { url: url });
            });
        }
        renderImageList() {
            var self = this;

            if (!this.nodes.list) {
                return;
            }

            clearElement(this.nodes.list);
            if (this.nodes.wrapper) {
                this.nodes.wrapper.classList.toggle('is-empty', this.images.length === 0);
            }
            if (this.images.length === 0) {
                this.nodes.list.appendChild(createElement('div', 'cms-editorjs-gallery__empty', 'Bilder auswählen – die Galerie erscheint danach direkt hier.'));
                return;
            }

            this.images.forEach(function (image, index) {
                var item = createElement('div', 'cms-editorjs-gallery__item');
                var thumb = document.createElement('img');
                var caption = createInput('text', 'form-control form-control-sm', image.caption || '', 'Bildunterschrift');
                var remove = createElement('button', 'btn btn-sm btn-outline-danger', 'Entfernen');

                thumb.src = image.file && image.file.url ? image.file.url : '';
                thumb.alt = image.caption || '';
                remove.type = 'button';
                caption.addEventListener('input', function () {
                    self.images[index].caption = caption.value;
                    thumb.alt = caption.value;
                });
                remove.addEventListener('click', function () {
                    self.images.splice(index, 1);
                    self.renderImageList();
                });

                item.appendChild(thumb);
                item.appendChild(caption);
                item.appendChild(remove);
                self.nodes.list.appendChild(item);
            });
        }
        save() {
            return normalizeGalleryData({
                columns: this.nodes.columns ? this.nodes.columns.value : this.data.columns,
                images: this.images
            });
        }
    }

    window.CmsImageGalleryTool = ImageGalleryTool;

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
            textarea.className = 'form-control font-monospace cms-editorjs-code-textarea';
            textarea.rows = 1;
            textarea.value = this.data.code || '';
            this.nodes.textarea = textarea;
            autoresizeTextarea(textarea, 1);
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
            textarea.className = 'form-control font-monospace cms-editorjs-table-textarea';
            textarea.rows = Math.max(4, rows.length);
            textarea.value = rows.map(function (row) {
                return (Array.isArray(row) ? row : []).join(' | ');
            }).join('\n');
            textarea.placeholder = 'Zellen mit | trennen, eine Zeile pro Tabellenzeile';
            this.nodes.textarea = textarea;
            autoresizeTextarea(textarea, Math.max(2, rows.length));
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

    class SpacerTool {
        constructor(options) {
            this.data = normalizeSpacerData(options && options.data ? options.data : {});
            this.nodes = {};
        }
        static get toolbox() {
            return { title: 'Abstand', icon: '↕' };
        }
        render() {
            var wrapper = createElement('div', 'editorjs-spacer-tool');
            var header = createElement('div', 'editorjs-spacer-tool__header');
            var label = createElement('label', 'editorjs-spacer-tool__label', 'Abstand');
            var select = document.createElement('select');
            var badge = createElement('span', 'editorjs-spacer-tool__badge');
            var preview = createElement('div', 'editorjs-spacer-tool__preview');
            var help = createElement('div', 'form-hint', 'Fügt vertikalen Weißraum ein – ideal für saubere Abschnittsabstände.');

            select.className = 'form-select form-select-sm editorjs-spacer-tool__select';
            [15, 25, 40, 60, 75, 100].forEach(function (height) {
                var option = document.createElement('option');
                option.value = String(height);
                option.textContent = height + ' px';
                select.appendChild(option);
            });
            select.value = String(this.data.height || 40);

            function updatePreview() {
                var height = parseInt(select.value, 10) || 40;
                preview.style.height = height + 'px';
                badge.textContent = height + ' px';
            }

            select.addEventListener('input', updatePreview);
            select.addEventListener('change', updatePreview);

            header.appendChild(label);
            header.appendChild(select);
            wrapper.appendChild(header);
            wrapper.appendChild(badge);
            wrapper.appendChild(preview);
            wrapper.appendChild(help);

            this.nodes = { select: select, preview: preview, badge: badge };
            updatePreview();

            return wrapper;
        }
        save() {
            return normalizeSpacerData({
                height: this.nodes.select ? this.nodes.select.value : this.data.height
            });
        }
    }

    window.CmsSpacerTool = SpacerTool;

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
                var validationError = validateEditorImageFile(file);

                if (validationError !== '') {
                    return Promise.reject(new Error(validationError));
                }

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
                uploadUrl: uploadUrl,
                csrfToken: csrfToken,
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
        addTool(tools, 'spacer', {}, true);

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
            config: {
                uploadUrl: uploadUrl,
                csrfToken: csrfToken
            }
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
        applyEditorPreviewTypography(holder, resolvedOptions.themeTypography || resolvedOptions.typography || {});

        editor = new window.EditorJS({
            holder: holderId,
            data: normalizedData,
            tools: tools,
            defaultBlock: 'paragraph',
            minHeight: 160,
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
