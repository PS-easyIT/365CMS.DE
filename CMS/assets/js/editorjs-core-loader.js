'use strict';

(function () {
    var READY_EVENT = 'cms:editorjs-core-ready';
    var CORE_TIMEOUT_MS = 10000;

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

    function parseDefaultExportSymbol(source) {
        var match = String(source || '').match(/export\s*\{\s*([A-Za-z_$][\w$]*)\s+as\s+default\s*\}\s*;?\s*$/);
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

            source = String(xhr.responseText || '');
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

    function loadEditorCoreViaModule() {
        return new Promise(function (resolve, reject) {
            var bootScript;
            var timeoutId = null;
            var ready = false;
            var nonce = resolveBootNonce();

            function cleanup() {
                if (timeoutId !== null) {
                    window.clearTimeout(timeoutId);
                    timeoutId = null;
                }

                window.removeEventListener(READY_EVENT, onReadyEvent);
            }

            function finishWithResult(ok, error) {
                if (ready) {
                    return;
                }

                ready = true;
                cleanup();

                if (ok) {
                    resolve(true);
                    return;
                }

                reject(error instanceof Error ? error : new Error(String(error || 'EditorJS module boot failed.')));
            }

            function onReadyEvent(event) {
                var detail = event && event.detail && typeof event.detail === 'object'
                    ? event.detail
                    : {};

                if (detail.ok === true) {
                    finishWithResult(true, null);
                    return;
                }

                finishWithResult(false, detail.error || 'EditorJS module boot reported failure.');
            }

            if (hasEditorCore()) {
                resolve(true);
                return;
            }

            window.addEventListener(READY_EVENT, onReadyEvent, { once: false });

            timeoutId = window.setTimeout(function () {
                finishWithResult(false, new Error('EditorJS core module boot timed out.'));
            }, CORE_TIMEOUT_MS);

            bootScript = document.createElement('script');
            bootScript.type = 'module';
            bootScript.src = resolveBootUrl();
            if (nonce !== '') {
                bootScript.nonce = nonce;
            }
            bootScript.onerror = function () {
                finishWithResult(false, new Error('EditorJS core boot script could not be loaded.'));
            };

            (document.head || document.documentElement).appendChild(bootScript);
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
