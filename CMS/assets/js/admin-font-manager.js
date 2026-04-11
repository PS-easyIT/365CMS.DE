(function () {
    'use strict';

    function parseConfig(id) {
        var element = document.getElementById(id);
        if (!element) {
            return null;
        }

        try {
            return JSON.parse(element.textContent || '{}');
        } catch (error) {
            console.error('Font-Manager-Konfiguration konnte nicht gelesen werden.', error);
            return null;
        }
    }

    function bindPreview(config) {
        var fontStacks = config && typeof config.fontStacks === 'object' && config.fontStacks !== null
            ? config.fontStacks
            : {};
        var headingSelect = document.getElementById('headingFontSelect');
        var bodySelect = document.getElementById('bodyFontSelect');
        var previewHeading = document.getElementById('previewHeading');
        var previewSubheading = document.getElementById('previewSubheading');
        var previewBody = document.getElementById('previewBody');
        var previewSmall = document.getElementById('previewSmall');

        if (!headingSelect || !bodySelect) {
            return;
        }

        function updatePreview() {
            var headingStack = fontStacks[headingSelect.value] || 'sans-serif';
            var bodyStack = fontStacks[bodySelect.value] || 'sans-serif';

            if (previewHeading) {
                previewHeading.style.fontFamily = headingStack;
            }
            if (previewSubheading) {
                previewSubheading.style.fontFamily = headingStack;
            }
            if (previewBody) {
                previewBody.style.fontFamily = bodyStack;
            }
            if (previewSmall) {
                previewSmall.style.fontFamily = bodyStack;
            }
        }

        headingSelect.addEventListener('change', updatePreview);
        bodySelect.addEventListener('change', updatePreview);
        updatePreview();
    }

    function setPendingButtonState(button, pendingText) {
        if (!button) {
            return;
        }

        if (!button.dataset.originalText) {
            button.dataset.originalText = button.textContent;
        }

        button.disabled = true;
        button.setAttribute('aria-disabled', 'true');
        button.textContent = pendingText;
    }

    function resolveSubmitButton(form, fallbackButton) {
        if (fallbackButton) {
            return fallbackButton;
        }

        return form ? form.querySelector('button[type="submit"], input[type="submit"]') : null;
    }

    function isSubmitControl(element, form) {
        if (!element || !form || element.form !== form) {
            return false;
        }

        if (element.tagName === 'BUTTON') {
            return String(element.getAttribute('type') || element.type || 'submit').toLowerCase() === 'submit';
        }

        return element.tagName === 'INPUT'
            && String(element.getAttribute('type') || element.type || '').toLowerCase() === 'submit';
    }

    function triggerNativeSubmit(form, submitter) {
        var temporarySubmitter;

        if (!form) {
            return;
        }

        if (isSubmitControl(submitter, form) && typeof submitter.click === 'function') {
            submitter.click();
            return;
        }

        temporarySubmitter = document.createElement('button');
        temporarySubmitter.type = 'submit';
        temporarySubmitter.hidden = true;
        temporarySubmitter.tabIndex = -1;
        temporarySubmitter.setAttribute('aria-hidden', 'true');
        form.appendChild(temporarySubmitter);
        temporarySubmitter.click();
        form.removeChild(temporarySubmitter);
    }

    function markFormSubmitting(form, submitter) {
        var pendingText;

        if (!form || form.dataset.submitting === '1') {
            return false;
        }

        pendingText = submitter && submitter.getAttribute('data-pending-text')
            ? submitter.getAttribute('data-pending-text')
            : 'Wird ausgeführt …';

        form.dataset.submitting = '1';
        setPendingButtonState(submitter, pendingText);

        return true;
    }

    function submitForm(form, triggerButton) {
        var submitButton;

        if (!form) {
            return;
        }

        if (form.dataset.submitting === '1') {
            return;
        }

        submitButton = resolveSubmitButton(form, triggerButton);
        form._fontManagerPendingButton = submitButton || triggerButton || null;

        if (typeof form.requestSubmit === 'function') {
            if (submitButton
                && submitButton.tagName === 'BUTTON'
                && String(submitButton.getAttribute('type') || submitButton.type || '').toLowerCase() === 'submit') {
                form.requestSubmit(submitButton);
                return;
            }

            form.requestSubmit();
            return;
        }

        triggerNativeSubmit(form, submitButton);
    }

    function bindActionForms() {
        document.querySelectorAll('[data-font-manager-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var submitter;

                if (form.dataset.submitting === '1') {
                    event.preventDefault();
                    return;
                }

                submitter = event.submitter || form._fontManagerPendingButton || resolveSubmitButton(form, null);
                form._fontManagerPendingButton = null;

                if (!markFormSubmitting(form, submitter)) {
                    event.preventDefault();
                }
            });
        });
    }

    function bindDeleteButtons(config) {
        var deleteModal = config && typeof config.deleteModal === 'object' && config.deleteModal !== null
            ? config.deleteModal
            : {};

        document.querySelectorAll('.js-font-delete').forEach(function (button) {
            button.addEventListener('click', function () {
                if (button.disabled) {
                    return;
                }

                var fontName = button.dataset.fontName || 'diese Schriftart';
                var form = button.closest('form');

                if (typeof cmsConfirm === 'function') {
                    cmsConfirm({
                        title: deleteModal.title || 'Schriftart löschen',
                        message: 'Soll "' + fontName + '" wirklich gelöscht werden?',
                        confirmText: deleteModal.confirmText || 'Löschen',
                        confirmClass: deleteModal.confirmClass || 'btn-danger',
                        onConfirm: function () {
                            submitForm(form, button);
                        }
                    });

                    return;
                }

                if (window.confirm('Soll "' + fontName + '" wirklich gelöscht werden?')) {
                    submitForm(form, button);
                }
            });
        });
    }

    function init() {
        var config = parseConfig('font-manager-config') || {};
        bindPreview(config);
        bindActionForms();
        bindDeleteButtons(config);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();