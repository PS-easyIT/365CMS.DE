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

    function submitForm(form, triggerButton) {
        if (!form) {
            return;
        }

        if (form.dataset.submitting === '1') {
            return;
        }

        var submitButton = resolveSubmitButton(form, triggerButton);
        var pendingText = submitButton && submitButton.getAttribute('data-pending-text')
            ? submitButton.getAttribute('data-pending-text')
            : 'Wird ausgeführt …';

        form.dataset.submitting = '1';
        setPendingButtonState(submitButton, pendingText);

        if (typeof form.requestSubmit === 'function') {
            if (submitButton && submitButton.tagName === 'BUTTON') {
                form.requestSubmit(submitButton);
                return;
            }

            form.requestSubmit();
            return;
        }

        form.submit();
    }

    function bindActionForms() {
        document.querySelectorAll('[data-font-manager-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (form.dataset.submitting === '1') {
                    event.preventDefault();
                    return;
                }

                form.dataset.submitting = '1';
                var submitter = event.submitter || resolveSubmitButton(form, null);
                var pendingText = submitter && submitter.getAttribute('data-pending-text')
                    ? submitter.getAttribute('data-pending-text')
                    : 'Wird ausgeführt …';

                setPendingButtonState(submitter, pendingText);
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