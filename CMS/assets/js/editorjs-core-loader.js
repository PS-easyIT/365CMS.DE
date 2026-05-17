'use strict';

(function () {
    function hasEditorCore() {
        return typeof window.EditorJS === 'function';
    }

    function resolveCoreUrl() {
        var script = document.currentScript;
        var src = script && script.getAttribute ? String(script.getAttribute('src') || '') : '';

        if (src !== '') {
            return src.replace(/\/js\/editorjs-core-loader\.js(?:\?.*)?$/i, '/editorjs/editorjs.mjs');
        }

        return '/assets/editorjs/editorjs.mjs';
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

    if (loadEditorCoreSynchronously() === false && typeof console !== 'undefined' && typeof console.error === 'function') {
        console.error('EditorJS core konnte nicht geladen werden.');
    }
})();
