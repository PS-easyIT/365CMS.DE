/**
 * EditorJS module bootstrap (CSP-safe).
 *
 * Loads the ESM core and exposes it on window.EditorJS for legacy
 * non-module consumers (editor-init.js / admin-content-editor.js).
 */
(async function () {
    'use strict';

    function dispatchReady(ok, errorMessage) {
        if (typeof window === 'undefined' || typeof window.dispatchEvent !== 'function') {
            return;
        }

        try {
            window.dispatchEvent(new CustomEvent('cms:editorjs-core-ready', {
                detail: {
                    ok: ok === true,
                    error: ok === true ? '' : String(errorMessage || '')
                }
            }));
        } catch (_error) {
            // Ignore dispatch failures in legacy environments.
        }
    }

    try {
        var module = await import('../editorjs/editorjs.mjs');
        var EditorJS = module && typeof module.default === 'function'
            ? module.default
            : null;

        if (typeof EditorJS !== 'function') {
            throw new Error('EditorJS default export missing.');
        }

        window.EditorJS = EditorJS;
        dispatchReady(true, '');
    } catch (error) {
        if (typeof console !== 'undefined' && typeof console.error === 'function') {
            console.error('[cms-editor] EditorJS core boot failed.', error);
        }

        dispatchReady(false, error && error.message ? error.message : 'Unknown module bootstrap error');
    }
})();
