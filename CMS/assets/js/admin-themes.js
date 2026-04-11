(function () {
    'use strict';

    function getSubmitButtons(form) {
        return Array.prototype.slice.call(
            form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]')
        );
    }

    function lockThemeForm(form, submitter) {
        if (!form || form.dataset.themeSubmitting === '1') {
            return;
        }

        form.dataset.themeSubmitting = '1';

        getSubmitButtons(form).forEach(function (button) {
            if (button.dataset.themeOriginalText === undefined) {
                button.dataset.themeOriginalText = button.tagName === 'INPUT'
                    ? (button.value || '')
                    : (button.textContent || '');
            }

            button.disabled = true;
            button.setAttribute('aria-disabled', 'true');
        });

        if (submitter) {
            var submittingText = submitter.dataset.submittingText || '';
            if (submittingText) {
                if (submitter.tagName === 'INPUT') {
                    submitter.value = submittingText;
                } else {
                    submitter.textContent = submittingText;
                }
            }
        }
    }

    function bindThemeSubmitLocks() {
        var forms = document.querySelectorAll('form[data-theme-submit-lock="1"]');
        if (!forms.length) {
            return;
        }

        forms.forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (event.defaultPrevented) {
                    return;
                }

                if (form.dataset.themeSubmitting === '1') {
                    event.preventDefault();
                    return;
                }

                lockThemeForm(form, event.submitter || null);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindThemeSubmitLocks();
    });
})();
