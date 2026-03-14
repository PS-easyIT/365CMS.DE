(function () {
    'use strict';

    if ((window.location && /^\/admin(?:\/|$)/.test(window.location.pathname || '')) || document.documentElement.classList.contains('admin')) {
        return;
    }

    var consentApi = null;
    var lastFeedConsentState = null;

    function getCookieValue(name) {
        var prefix = name + '=';
        var parts = document.cookie ? document.cookie.split('; ') : [];

        for (var i = 0; i < parts.length; i += 1) {
            if (parts[i].indexOf(prefix) === 0) {
                return parts[i].slice(prefix.length);
            }
        }

        return '';
    }

    function getConfiguredCategories() {
        var cfg = window.CMS_COOKIECONSENT_CONFIG || {};

        return cfg.categories && typeof cfg.categories === 'object'
            ? cfg.categories
            : {};
    }

    function parseStoredPreferences() {
        var rawCookie = getCookieValue('cc_cookie');
        var decoded = null;
        var acceptedCategories = [];
        var acceptedServices = {};
        var configuredCategories = getConfiguredCategories();
        var optionalCategories = Object.keys(configuredCategories).filter(function (slug) {
            return slug && slug !== 'necessary';
        });
        var acceptType = 'custom';

        if (!rawCookie) {
            return null;
        }

        try {
            decoded = JSON.parse(decodeURIComponent(rawCookie));
        } catch (error) {
            try {
                decoded = JSON.parse(rawCookie);
            } catch (_error) {
                return null;
            }
        }

        if (!decoded || typeof decoded !== 'object') {
            return null;
        }

        acceptedCategories = Array.isArray(decoded.categories)
            ? decoded.categories.filter(function (value) {
                return typeof value === 'string' && value.trim() !== '';
            }).map(function (value) {
                return value.trim();
            })
            : [];

        if (acceptedCategories.indexOf('necessary') === -1) {
            acceptedCategories.unshift('necessary');
        }

        acceptedServices = decoded.services && typeof decoded.services === 'object'
            ? decoded.services
            : {};

        if (acceptedCategories.length <= 1) {
            acceptType = 'necessary';
        } else if (optionalCategories.length > 0 && optionalCategories.every(function (slug) {
            return acceptedCategories.indexOf(slug) !== -1;
        })) {
            acceptType = 'all';
        }

        return {
            acceptType: acceptType,
            acceptedCategories: acceptedCategories,
            rejectedCategories: optionalCategories.filter(function (slug) {
                return acceptedCategories.indexOf(slug) === -1;
            }),
            acceptedServices: acceptedServices,
            rejectedServices: {}
        };
    }

    function getConsentPreferences() {
        var storedPreferences = parseStoredPreferences();
        var cc = getConsentApi();

        if (storedPreferences) {
            return storedPreferences;
        }

        return cc && typeof cc.getUserPreferences === 'function'
            ? cc.getUserPreferences()
            : null;
    }

    function getConsentApi() {
        if (window.CookieConsent && typeof window.CookieConsent.run === 'function') {
            return window.CookieConsent;
        }

        return consentApi;
    }

    function bootConsent() {
        var cfg = window.CMS_COOKIECONSENT_CONFIG || {};
        var cc = getConsentApi();

        if (!cc || typeof cc.run !== 'function' || !document.body) {
            return;
        }

        var position = cfg.position === 'center' ? 'middle center' : 'bottom center';
        var primary = cfg.primaryColor || '#3b82f6';
        var categories = cfg.categories || {
            necessary: {
                enabled: true,
                readOnly: true
            },
            analytics: {
                enabled: false
            },
            marketing: {
                enabled: false
            }
        };
        var sections = cfg.sections || [
            {
                title: 'Essenzielle Cookies',
                description: 'Diese Cookies sind für den Betrieb der Website erforderlich.'
            },
            {
                title: 'Analytics',
                description: 'Hilft uns zu verstehen, wie Besucher die Website nutzen.',
                linkedCategory: 'analytics'
            },
            {
                title: 'Marketing',
                description: 'Wird für personalisierte Inhalte und Kampagnen verwendet.',
                linkedCategory: 'marketing'
            }
        ];

        try {
            cc.run({
                revision: 1,
                autoClearCookies: true,
                manageScriptTags: true,
                guiOptions: {
                    consentModal: {
                        layout: 'box',
                        position: position,
                        equalWeightButtons: true,
                        flipButtons: false
                    },
                    preferencesModal: {
                        layout: 'box',
                        position: 'right',
                        equalWeightButtons: true,
                        flipButtons: false
                    }
                },
                categories: categories,
                language: {
                    default: 'de',
                    translations: {
                        de: {
                            consentModal: {
                                title: '🍪 Cookie-Einstellungen',
                                description: (cfg.bannerText || 'Wir nutzen Cookies für eine optimale Website-Erfahrung.')
                                    + ' <a href="#preferences" data-cc="show-preferencesModal" class="cc__link">Einstellungen</a>'
                                    + ' · <a href="' + (cfg.policyUrl || '/datenschutz') + '" class="cc__link">Datenschutz</a>',
                                acceptAllBtn: cfg.acceptText || 'Akzeptieren',
                                acceptNecessaryBtn: cfg.rejectText || cfg.essentialText || 'Ablehnen',
                                showPreferencesBtn: 'Einstellungen',
                                closeIconLabel: 'Schließen und ablehnen'
                            },
                            preferencesModal: {
                                title: 'Cookie-Präferenzen',
                                acceptAllBtn: cfg.acceptText || 'Akzeptieren',
                                acceptNecessaryBtn: cfg.essentialText || 'Nur Essenzielle',
                                savePreferencesBtn: 'Auswahl speichern',
                                closeIconLabel: 'Schließen',
                                sections: sections
                            }
                        }
                    }
                },
                onConsent: function () {
                    window.dispatchEvent(new CustomEvent('cms-cookie-consent-change', { detail: getConsentPreferences() }));
                },
                onChange: function () {
                    window.dispatchEvent(new CustomEvent('cms-cookie-consent-change', { detail: getConsentPreferences() }));
                }
            });

            consentApi = cc;

            document.documentElement.style.setProperty('--cc-btn-primary-bg', primary);
        } catch (error) {
            console.warn('CookieConsent konnte nicht initialisiert werden:', error);
            return;
        }
    }

    function updatePublicConsentPage() {
        var page = document.querySelector('[data-cms-consent-page]');
        if (!page) {
            return;
        }

        var statusText = page.querySelector('[data-cms-consent-status-text]');
        var statusDetail = page.querySelector('[data-cms-consent-status-detail]');
        var preferences = getConsentPreferences();
        var acceptedCategories = preferences && Array.isArray(preferences.acceptedCategories)
            ? preferences.acceptedCategories
            : [];
        var rejectedCategories = preferences && Array.isArray(preferences.rejectedCategories)
            ? preferences.rejectedCategories
            : [];
        var acceptType = preferences && preferences.acceptType ? preferences.acceptType : '';

        if (statusText) {
            if (!preferences) {
                page.setAttribute('data-cms-consent-state', 'unavailable');
                statusText.textContent = 'Consent-Status derzeit nicht verfügbar';
            } else if (acceptType === 'all') {
                page.setAttribute('data-cms-consent-state', 'all');
                statusText.textContent = 'Alle optionalen Kategorien sind akzeptiert';
            } else if (acceptType === 'necessary') {
                page.setAttribute('data-cms-consent-state', 'necessary');
                statusText.textContent = 'Nur essenzielle Kategorien sind aktiv';
            } else {
                page.setAttribute('data-cms-consent-state', 'custom');
                statusText.textContent = 'Individuelle Auswahl gespeichert';
            }
        } else if (preferences) {
            page.setAttribute('data-cms-consent-state', acceptType === 'all' ? 'all' : (acceptType === 'necessary' ? 'necessary' : 'custom'));
        } else {
            page.setAttribute('data-cms-consent-state', 'unavailable');
        }

        if (statusDetail) {
            if (!preferences) {
                statusDetail.textContent = 'Die Einstellungen können angezeigt werden, sobald das Consent-Tool initialisiert wurde.';
            } else {
                statusDetail.textContent = 'Aktiv: ' + (acceptedCategories.length ? acceptedCategories.join(', ') : 'keine optionalen Kategorien')
                    + (rejectedCategories.length ? ' · Abgelehnt: ' + rejectedCategories.join(', ') : '');
            }
        }

        page.querySelectorAll('[data-cms-consent-category-status]').forEach(function (element) {
            var slug = element.getAttribute('data-cms-consent-category-status') || '';
            var card = element.closest('[data-cms-consent-category]');
            if (!slug) {
                return;
            }

            if (slug === 'necessary') {
                element.textContent = 'Immer aktiv';
                if (card) {
                    card.setAttribute('data-cms-consent-category-state', 'always');
                }
                return;
            }

            if (acceptedCategories.indexOf(slug) !== -1) {
                element.textContent = 'Akzeptiert';
                if (card) {
                    card.setAttribute('data-cms-consent-category-state', 'accepted');
                }
                return;
            }

            if (rejectedCategories.indexOf(slug) !== -1) {
                element.textContent = 'Abgelehnt';
                if (card) {
                    card.setAttribute('data-cms-consent-category-state', 'rejected');
                }
                return;
            }

            element.textContent = 'Noch nicht gewählt';
            if (card) {
                card.setAttribute('data-cms-consent-category-state', 'pending');
            }
        });
    }

    function hasFeedConsentFromPreferences(preferences) {
        if (!preferences) {
            return false;
        }

        var acceptedCategories = Array.isArray(preferences.acceptedCategories)
            ? preferences.acceptedCategories
            : [];

        if (acceptedCategories.indexOf('external_media') !== -1) {
            return true;
        }

        var acceptedServices = preferences.acceptedServices && typeof preferences.acceptedServices === 'object'
            ? preferences.acceptedServices
            : {};
        var externalServices = Array.isArray(acceptedServices.external_media)
            ? acceptedServices.external_media
            : [];

        return externalServices.indexOf('cms_feed') !== -1;
    }

    function shouldReloadForFeedContent() {
        var path = window.location && typeof window.location.pathname === 'string'
            ? window.location.pathname
            : '/';

        if (path.length > 1 && path.charAt(path.length - 1) === '/') {
            path = path.slice(0, -1);
        }

        return path === ''
            || path === '/'
            || /^\/[a-z]{2}$/i.test(path)
            || path === '/feed'
            || path === '/feeds'
            || /^\/feed\//.test(path)
            || /^\/feeds\//.test(path)
            || document.querySelector('[data-cms-feed-protected]') !== null;
    }

    function syncFeedConsentState() {
        var preferences = getConsentPreferences();
        var hasConsent = hasFeedConsentFromPreferences(preferences);

        updateFeedProtectedContent();

        if (lastFeedConsentState !== null && hasConsent !== lastFeedConsentState && shouldReloadForFeedContent()) {
            window.location.reload();
            return;
        }

        lastFeedConsentState = hasConsent;
    }

    function updateFeedProtectedContent() {
        var protectedSections = document.querySelectorAll('[data-cms-feed-protected]');
        if (!protectedSections.length) {
            return;
        }

        var preferences = getConsentPreferences();
        var hasConsent = hasFeedConsentFromPreferences(preferences);

        protectedSections.forEach(function (section) {
            if (hasConsent) {
                section.hidden = false;
                section.removeAttribute('aria-hidden');
                return;
            }

            section.hidden = true;
            section.setAttribute('aria-hidden', 'true');
        });
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-cms-consent-action]');
        if (!trigger) {
            return;
        }

        event.preventDefault();

        var action = trigger.getAttribute('data-cms-consent-action');
        var cc = getConsentApi();
        if (!cc) {
            return;
        }

        if (action === 'preferences' && typeof cc.showPreferences === 'function') {
            cc.showPreferences();
        } else if (action === 'accept-all' && typeof cc.acceptCategory === 'function') {
            cc.acceptCategory('all');
        } else if ((action === 'essential' || action === 'reject') && typeof cc.acceptCategory === 'function') {
            cc.acceptCategory('necessary');
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            bootConsent();
            updatePublicConsentPage();
            syncFeedConsentState();
        }, { once: true });
    } else {
        bootConsent();
        updatePublicConsentPage();
        syncFeedConsentState();
    }

    window.addEventListener('cms-cookie-consent-change', updatePublicConsentPage);
    window.addEventListener('cms-cookie-consent-change', syncFeedConsentState);
})();
