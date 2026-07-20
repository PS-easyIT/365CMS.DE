<?php
/**
 * Inline bootstrap for Page/Post EditorJS admin edit views.
 *
 * This intentionally lives in the PHP view layer so FTP deployments that copy
 * /CMS into the webroot do not depend on one more external boot bridge file.
 * External EditorJS UMD assets and editor-init.js are still loaded normally;
 * this bootstrap only owns the final page/post holder binding.
 *
 * Expected variables:
 * - $editorInlineBootConfig array
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$editorInlineBootConfig = is_array($editorInlineBootConfig ?? null) ? $editorInlineBootConfig : [];
$editorInlineBootJson = json_encode($editorInlineBootConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if (!is_string($editorInlineBootJson) || $editorInlineBootJson === '') {
    $editorInlineBootJson = '{}';
}
?>
<script data-cms-editorjs-inline-boot="1">
(function () {
    'use strict';

    var config = <?php echo $editorInlineBootJson; ?>;
    var runtimeTimeoutMs = 15000;
    var readyTimeoutMs = 15000;
    var pollMs = 60;
    var booted = false;
    var state = {
        config: config,
        editors: {},
        submitting: false,
        nativeSubmitPending: false,
        pendingSubmitter: null,
        bootedAt: null,
        mode: 'inline-view-boot'
    };

    function getElement(id) {
        return id ? document.getElementById(id) : null;
    }

    function log(level, message, payload) {
        var method = level === 'error' ? 'error' : (level === 'warn' ? 'warn' : 'log');
        if (typeof console === 'undefined' || typeof console[method] !== 'function') {
            return;
        }
        if (typeof payload === 'undefined') {
            console[method]('[CMS EditorJS Inline] ' + String(message || ''));
            return;
        }
        console[method]('[CMS EditorJS Inline] ' + String(message || ''), payload);
    }

    function emitInputEvents(input) {
        if (!input) {
            return;
        }
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function normalizeData(data) {
        if (typeof window.cmsNormalizeEditorJsData === 'function') {
            return window.cmsNormalizeEditorJsData(data);
        }
        if (data && typeof data === 'object' && Array.isArray(data.blocks)) {
            return data;
        }
        return { time: Date.now(), blocks: [], version: '2.31.0' };
    }

    function stringifyData(data) {
        try {
            return JSON.stringify(normalizeData(data));
        } catch (_error) {
            return JSON.stringify({ time: Date.now(), blocks: [], version: '2.31.0' });
        }
    }

    function createPlaintextFallbackData(value) {
        var normalizedValue = String(value || '').trim();

        if (normalizedValue === '') {
            return { blocks: [] };
        }

        return {
            time: Date.now(),
            version: 'cms-editor-fallback',
            blocks: [
                {
                    type: 'paragraph',
                    data: { text: normalizedValue.replace(/\n/g, '<br>') }
                }
            ]
        };
    }

    function getSubmitName(input) {
        if (!input || !input.dataset) {
            return '';
        }
        return String(input.dataset.editorSubmitName || input.getAttribute('data-editor-submit-name') || '').trim();
    }

    function findWrap(definition) {
        return definition && definition.plainWrapperId ? getElement(definition.plainWrapperId) : null;
    }

    function findTextarea(definition) {
        return definition && definition.plainTextareaId ? getElement(definition.plainTextareaId) : null;
    }

    function mark(definition, editorState, reason) {
        var holder = getElement(definition && definition.holderId ? definition.holderId : '');
        var wrap = getElement(definition && definition.holderId ? definition.holderId + '_wrap' : '');
        var nextState = String(editorState || 'loading');
        var nextReason = String(reason || '');

        [holder, wrap].forEach(function (node) {
            if (!node) {
                return;
            }
            node.dataset.editorState = nextState;
            node.dataset.editorStateReason = nextReason;
            node.setAttribute('aria-busy', nextState === 'loading' ? 'true' : 'false');
        });

        if (!window.cmsEditorDebug || typeof window.cmsEditorDebug !== 'object') {
            window.cmsEditorDebug = {};
        }
        window.cmsEditorDebug.inlineEditorJs = window.cmsEditorDebug.inlineEditorJs || {};
        window.cmsEditorDebug.inlineEditorJs[definition.key || definition.holderId || 'active'] = {
            state: nextState,
            reason: nextReason,
            holderId: definition.holderId || '',
            inputId: definition.inputId || '',
            at: new Date().toISOString()
        };
    }

    function setEnhanced(definition, enhanced) {
        var input = getElement(definition.inputId);
        var textarea = findTextarea(definition);
        var wrap = findWrap(definition);
        var submitName = getSubmitName(input);

        if (textarea && textarea.dataset && !textarea.dataset.originalName && textarea.name) {
            textarea.dataset.originalName = textarea.name;
        }

        if (enhanced) {
            if (input && submitName) {
                input.setAttribute('name', submitName);
            }
            if (textarea) {
                textarea.disabled = true;
                textarea.removeAttribute('name');
            }
            if (wrap) {
                wrap.hidden = true;
                wrap.classList.add('cms-editor-plain-wrap--enhanced');
            }
            mark(definition, 'editor', 'inline-ready');
            return;
        }

        if (input && submitName) {
            input.removeAttribute('name');
        }
        if (textarea) {
            textarea.disabled = false;
            if (textarea.dataset && textarea.dataset.originalName) {
                textarea.setAttribute('name', textarea.dataset.originalName);
            }
        }
        if (wrap) {
            wrap.hidden = false;
            wrap.classList.remove('cms-editor-plain-wrap--enhanced');
        }
        mark(definition, 'fallback', 'inline-fallback');
    }

    function runtimeReady() {
        return typeof window.EditorJS === 'function' && typeof window.createCmsEditor === 'function';
    }

    function waitForRuntime() {
        var startedAt = Date.now();
        return new Promise(function (resolve) {
            function poll() {
                if (runtimeReady()) {
                    resolve(true);
                    return;
                }
                if (Date.now() - startedAt >= runtimeTimeoutMs) {
                    resolve(false);
                    return;
                }
                window.setTimeout(poll, pollMs);
            }
            poll();
        });
    }

    function withTimeout(promise, timeoutMs) {
        return new Promise(function (resolve, reject) {
            var settled = false;
            var timer = window.setTimeout(function () {
                if (settled) {
                    return;
                }
                settled = true;
                reject(new Error('timeout'));
            }, timeoutMs);
            promise.then(function (value) {
                if (settled) {
                    return;
                }
                settled = true;
                window.clearTimeout(timer);
                resolve(value);
            }, function (error) {
                if (settled) {
                    return;
                }
                settled = true;
                window.clearTimeout(timer);
                reject(error);
            });
        });
    }

    function getDefinitions() {
        return Array.isArray(config.editors) ? config.editors.filter(function (definition) {
            return definition && definition.key && definition.holderId && definition.inputId;
        }) : [];
    }

    function getUploadContext() {
        var titleInput = getElement(config.titleInputId || config.titleFallbackInputId || '');
        var slugInput = getElement(config.slugInputId || config.slugFallbackInputId || '');
        return {
            contentType: String(config.contentType || ''),
            contentId: Number(config.contentId || 0),
            draftKey: String(config.draftKey || ''),
            title: titleInput ? titleInput.value : '',
            slug: slugInput ? slugInput.value : ''
        };
    }

    function saveDefinition(definition) {
        var entry = state.editors[definition.key];
        var input = getElement(definition.inputId);
        var textarea = findTextarea(definition);

        if (!input) {
            return Promise.resolve(false);
        }

        if (entry && entry.editor && typeof entry.editor.save === 'function') {
            return entry.editor.save().then(function (output) {
                input.value = stringifyData(output);
                emitInputEvents(input);
                return true;
            });
        }

        if (textarea && !textarea.disabled) {
            input.value = stringifyData(createPlaintextFallbackData(textarea.value));
            emitInputEvents(input);
            return Promise.resolve(true);
        }

        return Promise.resolve(false);
    }

    function prepareSerializedSubmitFields(definitions) {
        var restoreEntries = [];

        definitions.forEach(function (definition) {
            var input = getElement(definition.inputId);
            var textarea = findTextarea(definition);
            var submitName = getSubmitName(input);

            if (!input || !textarea || !submitName) {
                return;
            }

            restoreEntries.push({
                input: input,
                inputName: input.getAttribute('name') || '',
                textarea: textarea,
                textareaName: textarea.getAttribute('name') || '',
                textareaDisabled: !!textarea.disabled
            });

            input.setAttribute('name', submitName);
            textarea.disabled = true;
            textarea.removeAttribute('name');
        });

        return function restoreSerializedSubmitFields() {
            restoreEntries.forEach(function (entry) {
                entry.textarea.disabled = entry.textareaDisabled;
                if (entry.textareaName) {
                    entry.textarea.setAttribute('name', entry.textareaName);
                } else {
                    entry.textarea.removeAttribute('name');
                }
                if (entry.inputName) {
                    entry.input.setAttribute('name', entry.inputName);
                } else {
                    entry.input.removeAttribute('name');
                }
            });
        };
    }

    function initDefinition(definition) {
        var holder = getElement(definition.holderId);
        var input = getElement(definition.inputId);
        var editor;

        if (!holder || !input || definition.lazy) {
            return Promise.resolve(false);
        }

        setEnhanced(definition, false);
        mark(definition, 'loading', 'inline-create');

        try {
            editor = window.createCmsEditor(definition.holderId, input.value || '', config.mediaUploadUrl || '', config.csrfToken || '', {
                getUploadContext: getUploadContext,
                readOnly: !!(definition.readOnly || config.readOnly),
                themeTypography: config.themeTypography || {},
                onReady: function () {
                    var entry = state.editors[definition.key];
                    if (!entry || entry.failed) {
                        return;
                    }
                    entry.ready = true;
                    setEnhanced(definition, true);
                    saveDefinition(definition).catch(function (error) {
                        log('warn', 'Initial sync failed.', error);
                    });
                },
                onChange: function (output) {
                    var entry = state.editors[definition.key];
                    if (!entry || entry.failed) {
                        return;
                    }
                    input.value = stringifyData(output);
                    emitInputEvents(input);
                },
                onError: function (error, context) {
                    log('error', 'Editor runtime error.', { error: error, context: context || {}, definition: definition });
                }
            });
        } catch (error) {
            log('error', 'Editor create failed.', error);
            setEnhanced(definition, false);
            mark(definition, 'fallback', 'inline-create-failed');
            return Promise.resolve(false);
        }

        state.editors[definition.key] = {
            editor: editor,
            input: input,
            definition: definition,
            ready: false,
            failed: false
        };

        if (editor && editor.isReady && typeof editor.isReady.then === 'function') {
            return withTimeout(editor.isReady, readyTimeoutMs).then(function () {
                var entry = state.editors[definition.key];
                if (!entry || entry.failed) {
                    return false;
                }
                entry.ready = true;
                setEnhanced(definition, true);
                return true;
            }, function (error) {
                var rendered = !!holder.querySelector('.codex-editor, .ce-block, .codex-editor__redactor');
                var entry = state.editors[definition.key];
                if (rendered) {
                    if (entry) {
                        entry.ready = true;
                    }
                    setEnhanced(definition, true);
                    mark(definition, 'editor', 'inline-rendered-before-ready');
                    log('warn', 'isReady timeout/rejection ignored because EditorJS DOM is rendered.', error);
                    return true;
                }
                if (entry) {
                    entry.ready = false;
                    entry.failed = true;
                    entry.editor = null;
                }
                if (editor && typeof editor.destroy === 'function') {
                    try {
                        editor.destroy();
                    } catch (destroyError) {
                        log('warn', 'Failed editor cleanup failed.', destroyError);
                    }
                }
                setEnhanced(definition, false);
                mark(definition, 'fallback', 'inline-ready-failed');
                log('error', 'Editor ready failed.', error);
                return false;
            });
        }

        setEnhanced(definition, true);
        return Promise.resolve(true);
    }

    function submitNative(form, submitter, definitions) {
        var restoreSerializedSubmitFields = prepareSerializedSubmitFields(definitions);

        state.nativeSubmitPending = true;
        try {
            if (submitter && submitter.form === form && typeof form.requestSubmit === 'function') {
                form.requestSubmit(submitter);
                return;
            }
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
                return;
            }
            form.submit();
        } finally {
            state.nativeSubmitPending = false;
            state.pendingSubmitter = null;
            restoreSerializedSubmitFields();
        }
    }

    function bindSubmit(form, definitions) {
        if (!form || form.dataset.cmsEditorJsInlineSubmitBound === '1') {
            return;
        }
        form.dataset.cmsEditorJsInlineSubmitBound = '1';

        form.addEventListener('click', function (event) {
            var button = event.target && event.target.closest ? event.target.closest('button, input[type="submit"]') : null;
            if (button && button.form === form) {
                state.pendingSubmitter = button;
            }
        }, true);

        form.addEventListener('submit', function (event) {
            var submitter = event.submitter || state.pendingSubmitter || null;
            var confirmText = submitter && submitter.dataset ? String(submitter.dataset.confirm || '') : '';

            if (state.nativeSubmitPending) {
                state.nativeSubmitPending = false;
                return;
            }
            if (state.submitting) {
                event.preventDefault();
                return;
            }
            if (confirmText !== '' && !window.confirm(confirmText)) {
                event.preventDefault();
                return;
            }

            event.preventDefault();
            state.submitting = true;
            Promise.all(definitions.map(saveDefinition)).then(function () {
                state.submitting = false;
                submitNative(form, submitter, definitions);
            }, function (error) {
                state.submitting = false;
                log('error', 'Submit serialization failed.', error);
                window.alert('Der Block-Editor konnte den Inhalt nicht speichern. Bitte erneut versuchen.');
            });
        }, true);
    }

    function boot() {
        var form;
        var definitions;

        if (booted) {
            return;
        }
        booted = true;

        form = getElement(config.formId || '');
        definitions = getDefinitions();
        if (!form || definitions.length === 0) {
            return;
        }
        if (form.dataset && form.dataset.cmsEditorJsPrimaryBootBound === '1') {
            log('log', 'External content editor owns EditorJS boot.');
            return;
        }

        state.bootedAt = new Date().toISOString();
        window.cmsInlineEditorJsBootState = state;
        window.cmsAdminEditorJsBridgeState = state;
        if (!window.cmsEditorDebug || typeof window.cmsEditorDebug !== 'object') {
            window.cmsEditorDebug = {};
        }
        window.cmsEditorDebug.inlineEditorJsBootedAt = state.bootedAt;
        window.cmsEditorDebug.editorJsBridgeBootedAt = state.bootedAt;

        bindSubmit(form, definitions);
        waitForRuntime().then(function (ready) {
            if (!ready) {
                definitions.forEach(function (definition) {
                    setEnhanced(definition, false);
                    mark(definition, 'fallback', 'inline-runtime-missing');
                });
                log('error', 'EditorJS runtime missing. Prüfe /assets/editorjs/* und /assets/js/editor-init.js im Webroot.');
                return;
            }
            Promise.all(definitions.map(initDefinition)).then(function () {
                log('log', 'Inline EditorJS boot complete.', state);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
}());
</script>
