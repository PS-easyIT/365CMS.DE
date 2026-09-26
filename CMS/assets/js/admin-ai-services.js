(() => {
    'use strict';

    function bindProviderForm() {
        const form = document.getElementById('aiProviderForm');
        const select = document.getElementById('aiProviderType');
        if (!form || !select) {
            return;
        }

        let catalog = {};
        let providerValues = {};
        try {
            catalog = JSON.parse(form.dataset.providerCatalog || '{}');
            providerValues = JSON.parse(form.dataset.providerValues || '{}');
        } catch (_error) {
            catalog = {};
            providerValues = {};
        }

        const inputs = {
            label: document.getElementById('aiProviderLabel'),
            model: document.getElementById('aiProviderModel'),
            endpoint: document.getElementById('aiProviderEndpoint'),
            deployment: document.getElementById('aiProviderDeployment'),
            apiVersion: document.getElementById('aiProviderApiVersion'),
            allowedLocales: document.getElementById('aiProviderAllowedLocales'),
            allowedInternalHosts: document.getElementById('aiProviderAllowedInternalHosts'),
            profile: document.getElementById('aiProviderProfile'),
            betaOnly: document.getElementById('aiProviderBetaOnly'),
            translation: form.querySelector('[name="provider_translation_enabled"]'),
            summary: form.querySelector('[name="provider_summary_enabled"]'),
            rewrite: form.querySelector('[name="provider_rewrite_enabled"]'),
            seo: form.querySelector('[name="provider_seo_meta_enabled"]'),
            editorjs: form.querySelector('[name="provider_editorjs_enabled"]')
        };
        const description = document.getElementById('aiProviderDescription');
        const secretLabel = document.getElementById('aiProviderSecretLabel');
        const secretState = document.getElementById('aiProviderSecretState');
        const fieldGroups = Array.from(form.querySelectorAll('[data-provider-field]'));

        function toggleField(field, visible) {
            fieldGroups
                .filter((group) => group.dataset.providerField === field)
                .forEach((group) => {
                    group.hidden = !visible;
                    group.querySelectorAll('input, select, textarea').forEach((input) => {
                        input.disabled = !visible;
                    });
                });
        }

        function setValue(element, value, fallback, preserveValues) {
            if (!element || (preserveValues && String(element.value || '').trim() !== '')) {
                return;
            }
            element.value = typeof value === 'string' && value.trim() !== '' ? value : (fallback || '');
        }

        function setChecked(element, value, preserveValues) {
            if (!element || preserveValues) {
                return;
            }
            element.checked = Boolean(value);
        }

        function applyProvider(preserveValues) {
            const type = select.value || 'mock';
            const definition = catalog[type] || {};
            const values = providerValues[type] || {};
            const fields = definition.fields || {};

            if (description) {
                description.textContent = definition.description || '';
            }
            setValue(inputs.label, values.label, definition.label || type, preserveValues);
            setValue(inputs.model, values.model, definition.default_model || '', preserveValues);
            setValue(inputs.endpoint, values.endpoint, definition.default_endpoint || '', preserveValues);
            setValue(inputs.deployment, values.deployment, definition.default_deployment || '', preserveValues);
            setValue(inputs.apiVersion, values.api_version, definition.default_api_version || '', preserveValues);
            setValue(inputs.allowedLocales, values.allowed_locales, 'en', preserveValues);
            setValue(inputs.allowedInternalHosts, values.allowed_internal_hosts, '', preserveValues);
            setValue(inputs.profile, values.profile, 'editor-translation', preserveValues);
            setChecked(inputs.betaOnly, values.beta_only, preserveValues);
            setChecked(inputs.translation, values.translation_enabled, preserveValues);
            setChecked(inputs.summary, values.summary_enabled, preserveValues);
            setChecked(inputs.rewrite, values.rewrite_enabled, preserveValues);
            setChecked(inputs.seo, values.seo_meta_enabled, preserveValues);
            setChecked(inputs.editorjs, values.editorjs_enabled, preserveValues);

            if (secretLabel) {
                secretLabel.textContent = definition.secret_label || 'API-Key';
            }
            if (secretState) {
                secretState.textContent = values.secret_configured ? 'Ja' : 'Nein';
            }

            toggleField('endpoint', Boolean(fields.endpoint));
            toggleField('deployment', Boolean(fields.deployment));
            toggleField('api_version', Boolean(fields.api_version));
            toggleField('secret', Boolean(fields.secret));
            toggleField('internal_hosts', Boolean(fields.internal_hosts));
        }

        select.addEventListener('change', () => {
            [inputs.label, inputs.model, inputs.endpoint, inputs.deployment, inputs.apiVersion, inputs.allowedLocales, inputs.allowedInternalHosts]
                .filter(Boolean)
                .forEach((input) => { input.value = ''; });
            applyProvider(false);
        });

        applyProvider(true);
    }

    function bindContentDraftCopy() {
        const button = document.getElementById('copyAiContentDraftButton');
        const output = document.getElementById('aiContentDraftOutput');
        if (!button || !output) {
            return;
        }

        button.addEventListener('click', () => {
            const text = String(output.value || '');
            const restoreLabel = () => window.setTimeout(() => { button.textContent = 'Entwurf kopieren'; }, 1800);

            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                navigator.clipboard.writeText(text).then(() => {
                    button.textContent = 'Kopiert';
                    restoreLabel();
                }).catch(() => {
                    output.focus();
                    output.select();
                    button.textContent = 'Text markieren und kopieren';
                });
                return;
            }

            output.focus();
            output.select();
            button.textContent = 'Text markieren und kopieren';
        });
    }

    function bindProviderDeletionConfirm() {
        const form = document.querySelector('[data-cms-ai-delete-provider="1"]');
        if (!form) {
            return;
        }

        form.addEventListener('submit', (event) => {
            if (!window.confirm('Den aktiven Provider inklusive gespeichertem Secret endgültig löschen?')) {
                event.preventDefault();
            }
        });
    }

    function boot() {
        bindProviderForm();
        bindContentDraftCopy();
        bindProviderDeletionConfirm();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();
