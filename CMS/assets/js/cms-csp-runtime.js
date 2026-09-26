/*!
 * 365CMS CSP-Runtime
 *
 * Muss als erstes Script (nach DOMPurify) mit nonce-Attribut geladen werden:
 *   <script src="/assets/dompurify/purify.min.js" nonce="…"></script>
 *   <script src="/assets/js/cms-csp-runtime.js" nonce="…"></script>
 *
 * 1. Dynamisch per createElement('style') erzeugte <style>-Elemente (Editor.js,
 *    Editor.js-Plugins, SunEditor …) erhalten den Request-Nonce, damit sie unter
 *    `style-src 'nonce-…'` greifen. Per innerHTML eingeschleuste <style>-Tags
 *    bleiben ohne Nonce und damit blockiert.
 * 2. Für `require-trusted-types-for 'script'` wird die `default`-Policy registriert:
 *    HTML wird über DOMPurify bereinigt, Script-URLs sind nur same-origin erlaubt,
 *    String-zu-Script (eval, new Function, script.text) bleibt blockiert.
 *    Ohne DOMPurify schlägt jede HTML-Zuweisung fehl (fail closed) statt Inhalte
 *    stillschweigend zu leeren.
 */
(function () {
    'use strict';

    var currentScript = document.currentScript;
    var nonce = currentScript ? String(currentScript.nonce || currentScript.getAttribute('nonce') || '') : '';
    // Serverseitig (Security::allowCspSources) freigegebene externe Script-Origins, z. B. Analytics.
    var extraScriptOrigins = currentScript
        ? String(currentScript.getAttribute('data-script-origins') || '').split(/\s+/).filter(function (origin) {
            return /^https:\/\/(\*\.)?[a-z0-9.-]+(:\d+)?$/i.test(origin);
        })
        : [];

    if (nonce !== '' && !Document.prototype.__cmsStyleNoncePatched) {
        var nativeCreateElement = Document.prototype.createElement;
        var nativeCreateElementNS = Document.prototype.createElementNS;
        var nonceDescriptor = Object.getOwnPropertyDescriptor(HTMLElement.prototype, 'nonce');

        // Manche Bundles (z. B. vite-plugin-css-injected-by-js) setzen `style.nonce`
        // auf den Wert eines optionalen Meta-Tags und damit auf undefined. Leere
        // Werte dürfen den bereits gesetzten Request-Nonce nicht überschreiben.
        var applyNonce = function (element) {
            element.nonce = nonce;
            if (nonceDescriptor && typeof nonceDescriptor.set === 'function' && typeof nonceDescriptor.get === 'function') {
                Object.defineProperty(element, 'nonce', {
                    configurable: true,
                    get: function () {
                        return nonceDescriptor.get.call(this);
                    },
                    set: function (value) {
                        if (value !== undefined && value !== null && value !== '') {
                            nonceDescriptor.set.call(this, value);
                        }
                    }
                });
            }
            return element;
        };

        Document.prototype.createElement = function (tagName, options) {
            var element = nativeCreateElement.call(this, tagName, options);
            if (typeof tagName === 'string' && tagName.toLowerCase() === 'style') {
                applyNonce(element);
            }
            return element;
        };

        Document.prototype.createElementNS = function (namespace, qualifiedName, options) {
            var element = nativeCreateElementNS.call(this, namespace, qualifiedName, options);
            if (typeof qualifiedName === 'string' && qualifiedName.toLowerCase().replace(/^.*:/, '') === 'style') {
                applyNonce(element);
            }
            return element;
        };

        Object.defineProperty(Document.prototype, '__cmsStyleNoncePatched', { value: true });
    }

    var trustedTypes = window.trustedTypes;
    if (!trustedTypes || typeof trustedTypes.createPolicy !== 'function' || trustedTypes.defaultPolicy) {
        return;
    }

    var purify = window.DOMPurify;
    var purifyReady = !!(purify && typeof purify.sanitize === 'function' && purify.isSupported !== false);

    var PURIFY_CONFIG = {
        ADD_TAGS: ['iframe'],
        ADD_ATTR: ['target', 'contenteditable', 'allow', 'allowfullscreen', 'frameborder', 'scrolling', 'loading', 'referrerpolicy'],
        RETURN_TRUSTED_TYPE: false
    };

    // DOMPurify parst im <body>-Kontext; Tabellen-Fragmente würden der HTML-Parser
    // dort verwerfen. Sie werden deshalb im passenden Container bereinigt.
    var TABLE_CONTEXTS = [
        { test: /^\s*<tr[\s>\/]/i, before: '<table><tbody>', after: '</tbody></table>', selector: 'tbody' },
        { test: /^\s*<t[dh][\s>\/]/i, before: '<table><tbody><tr>', after: '</tr></tbody></table>', selector: 'tr' },
        { test: /^\s*<(?:thead|tbody|tfoot|caption|colgroup)[\s>\/]/i, before: '<table>', after: '</table>', selector: 'table' },
        { test: /^\s*<col[\s>\/]/i, before: '<table><colgroup>', after: '</colgroup></table>', selector: 'colgroup' }
    ];

    function sanitizeHtml(input) {
        if (!purifyReady) {
            return null;
        }

        var html = String(input);
        for (var i = 0; i < TABLE_CONTEXTS.length; i++) {
            var context = TABLE_CONTEXTS[i];
            if (context.test.test(html)) {
                var config = {};
                for (var key in PURIFY_CONFIG) {
                    if (Object.prototype.hasOwnProperty.call(PURIFY_CONFIG, key)) {
                        config[key] = PURIFY_CONFIG[key];
                    }
                }
                config.RETURN_DOM = true;
                var body = purify.sanitize(context.before + html + context.after, config);
                var container = body && body.querySelector ? body.querySelector(context.selector) : null;
                return container ? container.innerHTML : '';
            }
        }

        return purify.sanitize(html, PURIFY_CONFIG);
    }

    function isAllowedExtraOrigin(url) {
        if (url.protocol !== 'https:') {
            return false;
        }
        var host = url.host.toLowerCase();
        for (var i = 0; i < extraScriptOrigins.length; i++) {
            var allowed = extraScriptOrigins[i].toLowerCase().replace(/^https:\/\//, '');
            if (allowed.indexOf('*.') === 0) {
                var suffix = allowed.slice(1);
                if (host.length > suffix.length && host.slice(-suffix.length) === suffix) {
                    return true;
                }
            } else if (host === allowed) {
                return true;
            }
        }
        return false;
    }

    function allowSameOriginScriptUrl(input) {
        try {
            var url = new URL(String(input), document.baseURI);
            if ((url.protocol === 'https:' || url.protocol === 'http:') && url.origin === window.location.origin) {
                return url.href;
            }
            if (isAllowedExtraOrigin(url)) {
                return url.href;
            }
        } catch (error) {
            // ungültige URL → blockieren
        }

        return null;
    }

    try {
        trustedTypes.createPolicy('default', {
            createHTML: sanitizeHtml,
            createScriptURL: allowSameOriginScriptUrl,
            createScript: function () {
                return null;
            }
        });
    } catch (error) {
        if (window.console && typeof window.console.error === 'function') {
            window.console.error('[365CMS] Trusted-Types-Policy konnte nicht registriert werden.', error);
        }
    }
})();
