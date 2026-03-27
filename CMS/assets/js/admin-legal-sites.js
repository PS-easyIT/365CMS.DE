(function () {
    'use strict';

    function setFormPendingState(form, pending) {
        if (!form) {
            return;
        }

        form.dataset.submitting = pending ? '1' : '0';

        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (button) {
            button.disabled = pending;
            button.setAttribute('aria-disabled', pending ? 'true' : 'false');
        });
    }

    function parseConfig(id) {
        var element = document.getElementById(id);
        if (!element) {
            return {};
        }

        try {
            return JSON.parse(element.textContent || '{}');
        } catch (error) {
            console.error('Legal-Sites-Konfiguration konnte nicht gelesen werden.', error);
            return {};
        }
    }

    function initLegalProfileRequirements(config) {
        var entityTypeSelect = document.getElementById('legalProfileEntityType');
        var entityHint = document.getElementById('legalProfileEntityHint');
        var companyHint = config.companyHint || 'Firmenprofil aktiv.';
        var privateHint = config.privateHint || 'Privatprofil aktiv.';

        if (!entityTypeSelect) {
            return;
        }

        function updateLegalProfileRequirements() {
            var entityType = entityTypeSelect.value || 'company';
            var isCompany = entityType === 'company';

            document.querySelectorAll('[data-required-for]').forEach(function (wrapper) {
                var requiredFor = wrapper.getAttribute('data-required-for');
                var input = wrapper.querySelector('input, select, textarea');
                var indicator = wrapper.querySelector('.js-required-indicator');
                var isRequired = requiredFor === entityType;

                wrapper.classList.toggle('opacity-75', !isRequired);

                if (input) {
                    input.required = isRequired;
                }

                if (indicator) {
                    indicator.classList.toggle('d-none', !isRequired);
                }
            });

            document.querySelectorAll('[data-recommended-for="company"]').forEach(function (wrapper) {
                wrapper.classList.toggle('opacity-75', !isCompany);
            });

            if (entityHint) {
                entityHint.textContent = isCompany ? companyHint : privateHint;
            }
        }

        entityTypeSelect.addEventListener('change', updateLegalProfileRequirements);
        updateLegalProfileRequirements();
    }

    function initPrivacyFeatureDetails() {
        var featureEmptyState = document.getElementById('legalPrivacyFeatureEmptyState');

        function updatePrivacyFeatureDetails() {
            var visibleCount = 0;

            document.querySelectorAll('[data-privacy-feature]').forEach(function (block) {
                var fieldName = block.getAttribute('data-privacy-feature');
                var toggle = document.querySelector('input[name="' + fieldName + '"]');
                var isEnabled = !!(toggle && toggle.checked);

                block.classList.toggle('d-none', !isEnabled);

                if (isEnabled) {
                    visibleCount += 1;
                }
            });

            if (featureEmptyState) {
                featureEmptyState.classList.toggle('d-none', visibleCount > 0);
            }
        }

        document.querySelectorAll('input[name^="legal_profile_has_"]').forEach(function (toggle) {
            toggle.addEventListener('change', updatePrivacyFeatureDetails);
        });

        updatePrivacyFeatureDetails();
    }

    function initTemplateInsertButtons(config) {
        var title = config.insertTemplateTitle || 'Vorlage einfügen?';
        var message = config.insertTemplateMessage || 'Vorhandener Inhalt wird überschrieben.';
        var confirmText = config.insertTemplateConfirmText || 'Einfügen';

        document.querySelectorAll('.js-insert-template').forEach(function (button) {
            button.addEventListener('click', function () {
                var targetId = button.getAttribute('data-target') || '';
                var template = button.getAttribute('data-template') || '';
                var field = document.getElementById(targetId);

                if (!field) {
                    return;
                }

                if (field.value.trim() === '') {
                    field.value = template;
                    return;
                }

                if (typeof window.cmsConfirm === 'function') {
                    window.cmsConfirm({
                        title: title,
                        message: message,
                        confirmText: confirmText,
                        onConfirm: function () {
                            field.value = template;
                        }
                    });
                    return;
                }

                if (window.confirm(message)) {
                    field.value = template;
                }
            });
        });
    }

    function initSubmitGuards() {
        document.querySelectorAll('form[method="post"]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (form.dataset.submitting === '1') {
                    event.preventDefault();
                    return;
                }

                setFormPendingState(form, true);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var config = parseConfig('legal-sites-config');

        initLegalProfileRequirements(config);
        initPrivacyFeatureDetails();
        initTemplateInsertButtons(config);
        initSubmitGuards();
    });
})();