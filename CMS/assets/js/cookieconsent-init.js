 (function () {
    'use strict';

    if ((window.location && /^\/admin(?:\/|$)/.test(window.location.pathname || '')) || document.documentElement.classList.contains('admin')) {
        return;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    var consentApi = null;
    var lastFeedConsentState = null;
    var modalOpen = false;
    var ui = null;

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

    function getConfig() {
        return window.CMS_COOKIECONSENT_CONFIG || {};
    }

    function getConsentApi() {
        return consentApi;
    }

    function getServiceMap() {
        var categories = getConfiguredCategories();
        var map = {};

        Object.keys(categories).forEach(function (slug) {
            var category = categories[slug] || {};
            var serviceMap = category.services && typeof category.services === 'object' ? category.services : {};
            map[slug] = Object.keys(serviceMap).filter(function (serviceSlug) {
                return typeof serviceSlug === 'string' && serviceSlug.trim() !== '';
            });
        });

        if (!map.necessary) {
            map.necessary = [];
        }

        return map;
    }

    function getOptionalCategories() {
        return Object.keys(getConfiguredCategories()).filter(function (slug) {
            return slug && slug !== 'necessary';
        });
    }

    function normalizeAcceptedCategories(categories) {
        var configuredCategories = getConfiguredCategories();
        var normalized = Array.isArray(categories) ? categories.filter(function (value) {
            return typeof value === 'string' && value.trim() !== '';
        }).map(function (value) {
            return value.trim();
        }) : [];

        normalized = normalized.filter(function (slug) {
            return slug === 'necessary' || Object.prototype.hasOwnProperty.call(configuredCategories, slug);
        });

        if (normalized.indexOf('necessary') === -1) {
            normalized.unshift('necessary');
        }

        return Array.from(new Set(normalized));
    }

    function buildPreferencesFromCategories(categories) {
        var acceptedCategories = normalizeAcceptedCategories(categories);
        var optionalCategories = getOptionalCategories();
        var serviceMap = getServiceMap();
        var acceptedServices = {};
        var rejectedServices = {};
        var acceptType = 'custom';

        acceptedCategories.forEach(function (slug) {
            acceptedServices[slug] = Array.isArray(serviceMap[slug]) ? serviceMap[slug].slice() : [];
        });

        optionalCategories.forEach(function (slug) {
            if (acceptedCategories.indexOf(slug) === -1) {
                rejectedServices[slug] = Array.isArray(serviceMap[slug]) ? serviceMap[slug].slice() : [];
            }
        });

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
            rejectedServices: rejectedServices
        };
    }

    function writeCookieValue(value) {
        var encoded = encodeURIComponent(value);
        var expires = new Date();
        var isSecureContext = window.location && window.location.protocol === 'https:';
        expires.setTime(expires.getTime() + (182 * 24 * 60 * 60 * 1000));
        document.cookie = 'cc_cookie=' + encoded + '; expires=' + expires.toUTCString() + '; path=/; SameSite=Lax' + (isSecureContext ? '; Secure' : '');
    }

    function persistCategories(categories) {
        var preferences = buildPreferencesFromCategories(categories);
        writeCookieValue(JSON.stringify({
            categories: preferences.acceptedCategories,
            services: preferences.acceptedServices,
            acceptType: preferences.acceptType,
            savedAt: new Date().toISOString()
        }));

        return preferences;
    }

    function renderServices(services) {
        if (!services.length) {
            return '<div class="cmp-cookie-service"><div class="cmp-cookie-service__meta">Für diese Kategorie sind aktuell keine Einzelservices hinterlegt.</div></div>';
        }

        return services.map(function (service) {
            var label = '';
            if (service && typeof service === 'object' && service.label) {
                label = String(service.label);
            }

            return ''
                + '<div class="cmp-cookie-service">'
                + '  <div class="cmp-cookie-service__title">'
                + '    <span>' + escapeHtml(label || 'Service') + '</span>'
                + '  </div>'
                + '  <div class="cmp-cookie-service__meta">Wird mit dieser Kategorie gemeinsam freigegeben.</div>'
                + '</div>';
        }).join('');
    }

    function renderPreferenceSections() {
        var cfg = getConfig();
        var categories = getConfiguredCategories();
        var sections = Array.isArray(cfg.sections) ? cfg.sections : [];

        return sections.map(function (section) {
            var linkedCategory = section && typeof section === 'object' ? String(section.linkedCategory || '') : '';
            var categoryConfig = linkedCategory && categories[linkedCategory] ? categories[linkedCategory] : null;
            var services = categoryConfig && categoryConfig.services && typeof categoryConfig.services === 'object'
                ? Object.keys(categoryConfig.services).map(function (serviceSlug) {
                    return categoryConfig.services[serviceSlug];
                })
                : [];
            var required = linkedCategory === '' || !categoryConfig || categoryConfig.readOnly === true;

            return ''
                + '<section class="cmp-cookie-section">'
                + '  <div class="cmp-cookie-section__top">'
                + '    <div>'
                + '      <h3 class="cmp-cookie-section__title">' + escapeHtml(section && section.title ? section.title : 'Kategorie') + '</h3>'
                + '      <p class="cmp-cookie-section__description">' + escapeHtml(section && section.description ? section.description : '') + '</p>'
                + '    </div>'
                + '    ' + (linkedCategory
                    ? '<label class="cmp-cookie-toggle">'
                        + '<input type="checkbox" data-cms-consent-checkbox="' + escapeHtml(linkedCategory) + '" ' + (required ? 'checked disabled' : '') + '>'
                        + '<span class="cmp-cookie-section__badge">' + (required ? 'Immer aktiv' : 'Optional') + '</span>'
                      + '</label>'
                    : '<span class="cmp-cookie-section__badge">Info</span>')
                + '  </div>'
                + (linkedCategory ? '<div class="cmp-cookie-services">' + renderServices(services) + '</div>' : '')
                + '</section>';
        }).join('');
    }

    function ensureUi() {
        var cfg = getConfig();
        if (ui || !document.body) {
            return ui;
        }

        var root = document.createElement('div');
        var banner = document.createElement('div');
        var overlay = document.createElement('div');
        var policyUrl = cfg.policyUrl || '/datenschutz';
        var primaryColor = cfg.primaryColor || '#3b82f6';

        root.id = 'cms-cookie-consent-root';
        root.style.setProperty('--cms-cc-primary', primaryColor);

        banner.className = 'cmp-cookie-banner' + (cfg.position === 'center' ? ' cmp-cookie-banner--center' : '');
        banner.innerHTML = ''
            + '<h2 class="cmp-cookie-banner__title">🍪 Cookie-Einstellungen</h2>'
            + '<p class="cmp-cookie-banner__text">' + escapeHtml(cfg.bannerText || 'Wir nutzen Cookies für eine optimale Website-Erfahrung.')
            + ' <a href="' + escapeHtml(policyUrl) + '">Datenschutz</a></p>'
            + '<div class="cmp-cookie-banner__actions">'
            + '  <button type="button" class="cmp-cookie-button cmp-cookie-button--primary" data-cms-consent-button="accept-all">' + escapeHtml(cfg.acceptText || 'Akzeptieren') + '</button>'
            + '  <button type="button" class="cmp-cookie-button cmp-cookie-button--secondary" data-cms-consent-button="essential">' + escapeHtml(cfg.essentialText || cfg.rejectText || 'Nur Essenzielle') + '</button>'
            + '  <button type="button" class="cmp-cookie-button cmp-cookie-button--ghost" data-cms-consent-button="preferences">Einstellungen</button>'
            + '</div>';

        overlay.className = 'cmp-cookie-overlay';
        overlay.hidden = true;
        overlay.innerHTML = ''
            + '<div class="cmp-cookie-modal" role="dialog" aria-modal="true" aria-labelledby="cmp-cookie-modal-title">'
            + '  <div class="cmp-cookie-modal__header">'
            + '    <div>'
            + '      <h2 id="cmp-cookie-modal-title" class="cmp-cookie-banner__title">Cookie-Präferenzen</h2>'
            + '      <p class="cmp-cookie-banner__text">Wähle aus, welche optionalen Kategorien auf dieser Website aktiviert werden dürfen.</p>'
            + '    </div>'
            + '    <button type="button" class="cmp-cookie-modal__close" aria-label="Schließen" data-cms-consent-close="1">×</button>'
            + '  </div>'
            + '  <div class="cmp-cookie-modal__body">' + renderPreferenceSections() + '</div>'
            + '  <div class="cmp-cookie-modal__footer">'
            + '    <div class="cmp-cookie-modal__meta text-secondary small">Mehr Details findest du in der <a href="' + escapeHtml(policyUrl) + '">Datenschutzerklärung</a>.</div>'
            + '    <div class="cmp-cookie-modal__actions">'
            + '      <button type="button" class="cmp-cookie-button cmp-cookie-button--secondary" data-cms-consent-button="essential">' + escapeHtml(cfg.essentialText || 'Nur Essenzielle') + '</button>'
            + '      <button type="button" class="cmp-cookie-button cmp-cookie-button--ghost" data-cms-consent-button="save">Auswahl speichern</button>'
            + '      <button type="button" class="cmp-cookie-button cmp-cookie-button--primary" data-cms-consent-button="accept-all">' + escapeHtml(cfg.acceptText || 'Akzeptieren') + '</button>'
            + '    </div>'
            + '  </div>'
            + '</div>';

        root.appendChild(banner);
        root.appendChild(overlay);
        document.body.appendChild(root);

        ui = {
            root: root,
            banner: banner,
            overlay: overlay,
            checkboxes: function () {
                return overlay.querySelectorAll('[data-cms-consent-checkbox]');
            }
        };

        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) {
                closePreferences();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modalOpen) {
                closePreferences();
            }
        });

        return ui;
    }

    function syncModalSelections() {
        var state = parseStoredPreferences() || buildPreferencesFromCategories(['necessary']);
        var currentUi = ensureUi();
        if (!currentUi) {
            return;
        }

        currentUi.checkboxes().forEach(function (checkbox) {
            var slug = checkbox.getAttribute('data-cms-consent-checkbox') || '';
            if (!slug || checkbox.disabled) {
                checkbox.checked = true;
                return;
            }

            checkbox.checked = state.acceptedCategories.indexOf(slug) !== -1;
        });
    }

    function openPreferences() {
        var currentUi = ensureUi();
        if (!currentUi) {
            return;
        }

        syncModalSelections();
        modalOpen = true;
        currentUi.overlay.hidden = false;
        document.body.classList.add('cmp-cookie-modal-open');
    }

    function closePreferences() {
        if (!ui) {
            return;
        }

        modalOpen = false;
        ui.overlay.hidden = true;
        document.body.classList.remove('cmp-cookie-modal-open');
    }

    function hideBannerIfNeeded() {
        var state = parseStoredPreferences();
        var currentUi = ensureUi();
        if (!currentUi) {
            return;
        }

        currentUi.banner.classList.toggle('cmp-cookie-hidden', !!state);
    }

    function saveFromModalSelection() {
        var selected = ['necessary'];
        var currentUi = ensureUi();

        if (!currentUi) {
            return null;
        }

        currentUi.checkboxes().forEach(function (checkbox) {
            var slug = checkbox.getAttribute('data-cms-consent-checkbox') || '';
            if (!slug || slug === 'necessary' || !checkbox.checked) {
                return;
            }

            selected.push(slug);
        });

        return persistCategories(selected);
    }

    function dispatchConsentChange(preferences) {
        window.dispatchEvent(new CustomEvent('cms-cookie-consent-change', {
            detail: preferences || getConsentPreferences()
        }));
    }

    function acceptCategory(choice) {
        var preferences = null;

        if (choice === 'all') {
            preferences = persistCategories(['necessary'].concat(getOptionalCategories()));
        } else if (choice === 'necessary') {
            preferences = persistCategories(['necessary']);
        } else if (Array.isArray(choice)) {
            preferences = persistCategories(choice);
        }

        hideBannerIfNeeded();
        closePreferences();
        dispatchConsentChange(preferences);
        return preferences;
    }

    function initConsentUi() {
        ensureUi();

        consentApi = {
            run: function () {
                return consentApi;
            },
            showPreferences: openPreferences,
            acceptCategory: acceptCategory,
            getUserPreferences: getConsentPreferences,
            validConsent: function () {
                return parseStoredPreferences() !== null;
            }
        };

        window.CookieConsent = consentApi;
        hideBannerIfNeeded();
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
        if (event.target.closest('[data-cms-consent-close="1"]')) {
            event.preventDefault();
            closePreferences();
            return;
        }

        var button = event.target.closest('[data-cms-consent-button]');
        if (button) {
            event.preventDefault();

            if (button.getAttribute('data-cms-consent-button') === 'accept-all') {
                acceptCategory('all');
                return;
            }

            if (button.getAttribute('data-cms-consent-button') === 'essential') {
                acceptCategory('necessary');
                return;
            }

            if (button.getAttribute('data-cms-consent-button') === 'preferences') {
                openPreferences();
                return;
            }

            if (button.getAttribute('data-cms-consent-button') === 'save') {
                var savedPreferences = saveFromModalSelection();
                hideBannerIfNeeded();
                closePreferences();
                dispatchConsentChange(savedPreferences);
                return;
            }
        }

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
            initConsentUi();
            updatePublicConsentPage();
            syncFeedConsentState();
        }, { once: true });
    } else {
        initConsentUi();
        updatePublicConsentPage();
        syncFeedConsentState();
    }

    window.addEventListener('cms-cookie-consent-change', updatePublicConsentPage);
    window.addEventListener('cms-cookie-consent-change', syncFeedConsentState);
})();
