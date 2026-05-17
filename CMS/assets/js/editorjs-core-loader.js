'use strict';

(function () {
    var READY_EVENT = 'cms:editorjs-core-ready';

    function hasEditorCore() {
        return typeof window.EditorJS === 'function';
    }

    function resolveLoaderScript() {
        var script = document.currentScript;
        var src = script && script.getAttribute ? String(script.getAttribute('src') || '') : '';

        return {
            element: script || null,
            src: src
        };
    }

    function resolveCoreUrl() {
        var loader = resolveLoaderScript();
        var src = loader.src;

        if (src !== '') {
            return src.replace(/\/js\/editorjs-core-loader\.js(?:\?.*)?$/i, '/editorjs/editorjs.mjs');
        }

        return '/assets/editorjs/editorjs.mjs';
    }

    function resolveBootUrl() {
        var loader = resolveLoaderScript();
        var src = loader.src;

        if (src !== '') {
            return src.replace(/\/js\/editorjs-core-loader\.js(?:\?.*)?$/i, '/js/editorjs-core-boot.js');
        }

        return '/assets/js/editorjs-core-boot.js';
    }

    function resolveBootNonce() {
        var loader = resolveLoaderScript();
        var loaderScript = loader.element;
        var noncedScript;

        if (loaderScript && loaderScript.nonce) {
            return String(loaderScript.nonce);
        }

        noncedScript = document.querySelector('script[nonce]');
        if (noncedScript && noncedScript.nonce) {
            return String(noncedScript.nonce);
        }

        return '';
    }

    function logCoreError(message, error) {
        if (typeof console === 'undefined' || typeof console.error !== 'function') {
            return;
        }

        if (error) {
            console.error('[cms-editor] ' + message, error);
            return;
        }

        console.error('[cms-editor] ' + message);
    }

    function applyEditorModule(module) {
        var EditorJS = module && typeof module.default === 'function'
            ? module.default
            : null;

        if (typeof EditorJS !== 'function') {
            throw new Error('EditorJS default export missing.');
        }

        window.EditorJS = EditorJS;
        return true;
    }

    function stripSourceMapComment(source) {
        return String(source || '')
            .replace(/\/\/# sourceMappingURL=.*$/gm, '')
            .trim();
    }

    function parseDefaultExportSymbol(source) {
        var normalized = stripSourceMapComment(source);
        var match = normalized.match(/export\s*\{\s*([A-Za-z_$][\w$]*)\s+as\s+default\s*\}\s*;?\s*$/)
            || normalized.match(/export\s+default\s+([A-Za-z_$][\w$]*)\s*;?\s*$/);

        return match ? match[1] : '';
    }

    function compileClassicBundle(source, defaultSymbol) {
        var exportPattern = /export\s*\{\s*[A-Za-z_$][\w$]*\s+as\s+default\s*\}\s*;?\s*$/;
        var runtimeSource;

        if (!defaultSymbol || exportPattern.test(source) === false) {
            return '';
        }

        runtimeSource = source.replace(exportPattern, 'window.EditorJS = ' + defaultSymbol + ';');
        return runtimeSource;
    }

    function loadEditorCoreSynchronously() {
        var xhr;
        var source;
        var defaultSymbol;
        var runtimeSource;
        var coreUrl;

        if (hasEditorCore()) {
            return true;
        }

        coreUrl = resolveCoreUrl();

        try {
            xhr = new XMLHttpRequest();
            xhr.open('GET', coreUrl, false);
            xhr.send(null);

            if (!(xhr.status >= 200 && xhr.status < 300) && xhr.status !== 0) {
                return false;
            }

            source = stripSourceMapComment(xhr.responseText || '');
            defaultSymbol = parseDefaultExportSymbol(source);
            runtimeSource = compileClassicBundle(source, defaultSymbol);
            if (runtimeSource === '') {
                return false;
            }

            // eslint-disable-next-line no-new-func
            (new Function(runtimeSource))();
            return hasEditorCore();
        } catch (_error) {
            return false;
        }
    }

    function loadEditorCoreFromBlob(coreUrl) {
        if (typeof fetch !== 'function' || typeof URL === 'undefined' || typeof URL.createObjectURL !== 'function') {
            return Promise.reject(new Error('Blob module fallback unavailable.'));
        }

        return fetch(coreUrl, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store'
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('EditorJS core fetch failed (' + String(response.status || 0) + ').');
            }

            return response.text();
        }).then(function (source) {
            var blobUrl;

            source = stripSourceMapComment(source);
            if (source === '') {
                throw new Error('EditorJS core fetch returned empty source.');
            }

            blobUrl = URL.createObjectURL(new Blob([source], { type: 'text/javascript' }));
            return import(blobUrl).then(function (module) {
                applyEditorModule(module);
                return true;
            }).finally(function () {
                URL.revokeObjectURL(blobUrl);
            });
        });
    }

    function loadEditorCoreViaModule() {
        var coreUrl = resolveCoreUrl();

        if (hasEditorCore()) {
            return Promise.resolve(true);
        }

        return import(coreUrl).then(function (module) {
            applyEditorModule(module);
            return true;
        }).catch(function (directImportError) {
            return loadEditorCoreFromBlob(coreUrl).catch(function (blobImportError) {
                throw blobImportError || directImportError;
            });
        });
    }

    if (!window.cmsEditorJsCoreReady || typeof window.cmsEditorJsCoreReady.then !== 'function') {
        window.cmsEditorJsCoreReady = loadEditorCoreViaModule().catch(function (moduleError) {
            if (hasEditorCore()) {
                return true;
            }

            if (loadEditorCoreSynchronously()) {
                return true;
            }

            logCoreError('EditorJS core could not be loaded.', moduleError);
            throw moduleError;
        });
    }
})();
