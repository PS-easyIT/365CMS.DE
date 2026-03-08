(function () {
    'use strict';

    if ((window.location && /^\/admin(?:\/|$)/.test(window.location.pathname || '')) || document.documentElement.classList.contains('admin')) {
        return;
    }

    var consentApi = null;

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
                autoclear_cookies: true,
                page_scripts: true,
                gui_options: {
                    consent_modal: {
                        layout: 'box',
                        position: position,
                        equal_weight_buttons: true,
                        flip_buttons: false
                    },
                    preferences_modal: {
                        layout: 'box',
                        position: 'right',
                        equal_weight_buttons: true,
                        flip_buttons: false
                    }
                },
                categories: categories,
                language: {
                    default: 'de',
                    translations: {
                        de: {
                            consent_modal: {
                                title: '🍪 Cookie-Einstellungen',
                                description: (cfg.bannerText || 'Wir nutzen Cookies für eine optimale Website-Erfahrung.') + ' <a href="' + (cfg.policyUrl || '/datenschutz') + '">Datenschutz</a>',
                                accept_all_btn: cfg.acceptText || 'Akzeptieren',
                                accept_necessary_btn: cfg.essentialText || 'Nur Essenzielle',
                                show_preferences_btn: 'Einstellungen'
                            },
                            preferences_modal: {
                                title: 'Cookie-Präferenzen',
                                accept_all_btn: cfg.acceptText || 'Akzeptieren',
                                accept_necessary_btn: cfg.essentialText || 'Nur Essenzielle',
                                save_preferences_btn: 'Auswahl speichern',
                                close_btn_label: 'Schließen',
                                sections: sections
                            }
                        }
                    }
                },
                onConsent: function () {
                    window.dispatchEvent(new CustomEvent('cms-cookie-consent-change', { detail: cc.getUserPreferences() }));
                },
                onChange: function () {
                    window.dispatchEvent(new CustomEvent('cms-cookie-consent-change', { detail: cc.getUserPreferences() }));
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
        var cc = getConsentApi();
        var preferences = cc && typeof cc.getUserPreferences === 'function' ? cc.getUserPreferences() : null;
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
        } else if (action === 'essential' && typeof cc.acceptCategory === 'function') {
            cc.acceptCategory('necessary');
        }

        window.setTimeout(updatePublicConsentPage, 50);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            bootConsent();
            updatePublicConsentPage();
        }, { once: true });
    } else {
        bootConsent();
        updatePublicConsentPage();
    }

    window.addEventListener('cms-cookie-consent-change', updatePublicConsentPage);
})();
