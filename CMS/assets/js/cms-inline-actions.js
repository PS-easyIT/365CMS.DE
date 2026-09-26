/*!
 * 365CMS Declarative Actions
 *
 * CSP-konformer Ersatz für Inline-Event-Handler (onclick/onchange/onsubmit),
 * die unter `script-src 'self' 'nonce-…'` nicht ausgeführt werden.
 *
 *   data-cms-confirm="Text"               Klick bzw. Formular-Submit nur nach confirm()
 *   data-cms-confirm-modal                Klick öffnet cmsConfirm(); bei Bestätigung wird das
 *     data-cms-confirm-title / -message    umgebende Formular abgeschickt
 *     data-cms-confirm-text / -class
 *   data-cms-call="fn"                    ruft eine freigegebene globale Funktion auf
 *     data-cms-call-args='[…]'             JSON-Argumente
 *     data-cms-call-context="form|element" Formular bzw. Element als erstes Argument
 *     data-cms-call-on="click|change"      auslösendes Event (Standard: click)
 *   data-cms-enter-call="fn"              Funktion bei Enter-Taste (Eingabefelder)
 *   data-cms-sync-target="id"             Wert bei change in ein anderes Feld übernehmen
 *
 * Aufrufbar sind nur Funktionen aus CALLABLE, damit eingeschleustes Markup keine
 * beliebigen globalen Funktionen auslösen kann.
 */
(function () {
    'use strict';

    if (window.__cmsDeclarativeActions) {
        return;
    }
    window.__cmsDeclarativeActions = true;

    var CALLABLE = [
        'applyFilters',
        'rejectDeletion',
        'rejectRequest',
        'applyFirewallBaseline',
        'changeFirewallRuleMode',
        'deleteFirewallRule',
        'cmsAdminBackupsShowErrorLog',
        'cmsAdminBackupsConfirmSubmit'
    ];

    function resolveCallable(name) {
        if (CALLABLE.indexOf(name) === -1 || typeof window[name] !== 'function') {
            return null;
        }
        return window[name];
    }

    function parseArgs(element) {
        var raw = element.getAttribute('data-cms-call-args');
        if (!raw) {
            return [];
        }
        try {
            var parsed = JSON.parse(raw);
            return Array.isArray(parsed) ? parsed : [];
        } catch (error) {
            return [];
        }
    }

    function invoke(element, event) {
        var fn = resolveCallable(element.getAttribute('data-cms-call') || '');
        if (!fn) {
            return;
        }

        var args = parseArgs(element);
        var context = element.getAttribute('data-cms-call-context');
        if (context === 'form') {
            args.unshift(element.form || element.closest('form'));
        } else if (context === 'element') {
            args.unshift(element);
        }

        if (element.tagName === 'A') {
            event.preventDefault();
        }

        fn.apply(window, args);
    }

    function submitForm(form) {
        if (!form) {
            return;
        }
        if (typeof window.cmsSubmitFormSafely === 'function') {
            window.cmsSubmitFormSafely(form);
            return;
        }
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
        }
        form.submit();
    }

    function eventElement(event) {
        return event.target instanceof Element ? event.target : null;
    }

    // Bestätigungen zuerst (Capture), damit abgebrochene Klicks keine weiteren Handler erreichen.
    document.addEventListener('click', function (event) {
        var target = eventElement(event);
        var element = target ? target.closest('[data-cms-confirm]') : null;
        if (!element || element instanceof HTMLFormElement) {
            return;
        }
        if (!window.confirm(element.getAttribute('data-cms-confirm') || '')) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }, true);

    document.addEventListener('click', function (event) {
        var target = eventElement(event);
        if (!target) {
            return;
        }

        var modalTrigger = target.closest('[data-cms-confirm-modal]');
        if (modalTrigger) {
            event.preventDefault();
            var form = modalTrigger.form || modalTrigger.closest('form');
            if (typeof window.cmsConfirm !== 'function') {
                if (window.confirm(modalTrigger.getAttribute('data-cms-confirm-message') || '')) {
                    submitForm(form);
                }
                return;
            }
            window.cmsConfirm({
                title: modalTrigger.getAttribute('data-cms-confirm-title') || 'Sind Sie sicher?',
                message: modalTrigger.getAttribute('data-cms-confirm-message') || '',
                confirmText: modalTrigger.getAttribute('data-cms-confirm-text') || 'Bestätigen',
                confirmClass: modalTrigger.getAttribute('data-cms-confirm-class') || 'btn-danger',
                onConfirm: function () {
                    submitForm(form);
                }
            });
            return;
        }

        var callTrigger = target.closest('[data-cms-call]');
        if (callTrigger && (callTrigger.getAttribute('data-cms-call-on') || 'click') === 'click') {
            invoke(callTrigger, event);
        }
    });

    document.addEventListener('change', function (event) {
        var element = eventElement(event);
        if (!element) {
            return;
        }

        if (element.matches('[data-cms-call][data-cms-call-on="change"]')) {
            invoke(element, event);
        }

        var syncTarget = element.getAttribute('data-cms-sync-target');
        if (syncTarget) {
            var field = document.getElementById(syncTarget);
            if (field) {
                field.value = element.value;
            }
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter') {
            return;
        }
        var target = eventElement(event);
        var element = target ? target.closest('[data-cms-enter-call]') : null;
        var fn = element ? resolveCallable(element.getAttribute('data-cms-enter-call') || '') : null;
        if (fn) {
            event.preventDefault();
            fn();
        }
    });

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-cms-confirm')) {
            return;
        }
        if (!window.confirm(form.getAttribute('data-cms-confirm') || '')) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }, true);
})();
