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

    function parsePreviewCandidates(imageElement) {
        if (!imageElement) {
            return [];
        }

        try {
            var parsed = JSON.parse(imageElement.getAttribute('data-preview-candidates') || '[]');
            return Array.isArray(parsed) ? parsed.filter(Boolean) : [];
        } catch (error) {
            return [];
        }
    }

    function bindThemePreviewFallbacks() {
        var previewRoots = document.querySelectorAll('[data-theme-preview-root]');
        if (!previewRoots.length) {
            return;
        }

        previewRoots.forEach(function (root) {
            var image = root.querySelector('[data-theme-preview-image]');
            var fallback = root.querySelector('[data-theme-preview-fallback]');
            var candidates = parsePreviewCandidates(image);
            var candidateIndex = 0;

            function showFallback() {
                if (image) {
                    image.hidden = true;
                }
                if (fallback) {
                    fallback.hidden = false;
                }
            }

            function loadNextCandidate() {
                if (!image) {
                    return;
                }

                if (candidateIndex >= candidates.length) {
                    showFallback();
                    return;
                }

                image.hidden = false;
                image.src = candidates[candidateIndex];
                candidateIndex += 1;
            }

            if (!image) {
                return;
            }

            image.addEventListener('error', loadNextCandidate);
            image.addEventListener('load', function () {
                if (fallback) {
                    fallback.hidden = true;
                }
                image.hidden = false;
            });

            if (candidates.length === 0) {
                showFallback();
                return;
            }

            if (!image.getAttribute('src')) {
                loadNextCandidate();
            } else {
                candidateIndex = 1;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindThemeSubmitLocks();
        bindThemePreviewFallbacks();
    });
})();
