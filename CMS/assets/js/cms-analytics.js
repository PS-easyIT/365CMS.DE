/*!
 * 365CMS Analytics-Loader
 *
 * Lädt konfigurierte Anbieter (Matomo, GA4, GTM, Meta Pixel) erst, wenn die
 * zugehörige Cookie-Kategorie eingewilligt ist (cc_cookie / CookieConsent-API,
 * Event `cms-cookie-consent-change`). Konfiguration: #cms-analytics-config
 * (serverseitig validierte IDs, siehe SeoAnalyticsRenderer). Kein Inline-Code,
 * Script-Hosts sind per CSP nur für die konfigurierten Anbieter freigegeben.
 */
(function () {
    'use strict';

    var configElement = document.getElementById('cms-analytics-config');
    if (!configElement || window.__cmsAnalyticsLoader) {
        return;
    }
    window.__cmsAnalyticsLoader = true;

    var config;
    try {
        config = JSON.parse(configElement.textContent || '{}');
    } catch (error) {
        return;
    }

    var providers = config && typeof config.providers === 'object' && config.providers ? config.providers : {};
    var started = {};

    function doNotTrack() {
        if (!config.respectDnt) {
            return false;
        }
        var value = String(navigator.doNotTrack || window.doNotTrack || navigator.msDoNotTrack || '');
        return value === '1' || value === 'yes';
    }

    function readConsentCookie() {
        var parts = document.cookie ? document.cookie.split('; ') : [];
        for (var i = 0; i < parts.length; i++) {
            if (parts[i].indexOf('cc_cookie=') === 0) {
                var raw = parts[i].slice('cc_cookie='.length);
                try {
                    return JSON.parse(decodeURIComponent(raw));
                } catch (error) {
                    try {
                        return JSON.parse(raw);
                    } catch (innerError) {
                        return null;
                    }
                }
            }
        }
        return null;
    }

    function acceptedCategories(eventDetail) {
        if (eventDetail && Array.isArray(eventDetail.acceptedCategories)) {
            return eventDetail.acceptedCategories;
        }
        var api = window.CookieConsent;
        if (api && typeof api.getUserPreferences === 'function') {
            var preferences = api.getUserPreferences() || {};
            if (Array.isArray(preferences.acceptedCategories)) {
                return preferences.acceptedCategories;
            }
        }
        var stored = readConsentCookie();
        return stored && Array.isArray(stored.categories) ? stored.categories : [];
    }

    function addScript(src) {
        var script = document.createElement('script');
        script.async = true;
        script.src = src;
        document.head.appendChild(script);
    }

    var loaders = {
        matomo: function (provider) {
            var base = String(provider.url || '');
            if (!/^https:\/\/[^\s"'<>]+\/$/.test(base)) {
                return;
            }
            var paq = window._paq = window._paq || [];
            if (provider.disableCookies) {
                paq.push(['disableCookies']);
            }
            paq.push(['trackPageView']);
            paq.push(['enableLinkTracking']);
            paq.push(['setTrackerUrl', base + 'matomo.php']);
            paq.push(['setSiteId', String(provider.siteId || '1')]);
            addScript(base + 'matomo.js');
        },
        ga4: function (provider) {
            var id = String(provider.id || '');
            window.dataLayer = window.dataLayer || [];
            window.gtag = window.gtag || function () {
                window.dataLayer.push(arguments);
            };
            window.gtag('js', new Date());
            window.gtag('config', id, provider.anonymizeIp ? { anonymize_ip: true } : {});
            addScript('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id));
        },
        gtm: function (provider) {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
            addScript('https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(String(provider.id || '')));
        },
        metaPixel: function (provider) {
            if (!window.fbq) {
                var fbq = window.fbq = function () {
                    if (fbq.callMethod) {
                        fbq.callMethod.apply(fbq, arguments);
                    } else {
                        fbq.queue.push(arguments);
                    }
                };
                if (!window._fbq) {
                    window._fbq = fbq;
                }
                fbq.push = fbq;
                fbq.loaded = true;
                fbq.version = '2.0';
                fbq.queue = [];
                addScript('https://connect.facebook.net/en_US/fbevents.js');
            }
            window.fbq('init', String(provider.id || ''));
            window.fbq('track', 'PageView');
        }
    };

    function run(event) {
        if (doNotTrack()) {
            return;
        }
        var categories = acceptedCategories(event && event.detail);
        Object.keys(providers).forEach(function (key) {
            var provider = providers[key];
            if (started[key] || !loaders[key] || !provider || categories.indexOf(provider.category) === -1) {
                return;
            }
            started[key] = true;
            try {
                loaders[key](provider);
            } catch (error) {
                started[key] = false;
            }
        });
    }

    window.addEventListener('cms-cookie-consent-change', run);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            run(null);
        }, { once: true });
    } else {
        run(null);
    }
})();
