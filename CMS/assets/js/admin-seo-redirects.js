(function () {
    'use strict';

    function parseConfig(id) {
        const element = document.getElementById(id);
        if (!element) {
            return null;
        }

        try {
            return JSON.parse(element.textContent || '{}');
        } catch (error) {
            console.error('SEO-Redirect-Konfiguration konnte nicht gelesen werden.', error);
            return null;
        }
    }

    function normalizeTargetUrl(value) {
        const trimmed = String(value || '').trim();
        if (!trimmed) {
            return '';
        }

        if (/^https?:\/\//i.test(trimmed)) {
            try {
                const absoluteUrl = new URL(trimmed, window.location.origin);
                if (absoluteUrl.origin === window.location.origin) {
                    return normalizeTargetUrl((absoluteUrl.pathname || '/') + (absoluteUrl.search || '') + (absoluteUrl.hash || ''));
                }
            } catch (_) {
                return trimmed;
            }

            return trimmed;
        }

        let normalized = trimmed;
        if (!normalized.startsWith('/')) {
            normalized = '/' + normalized;
        }

        if (normalized.length > 1) {
            normalized = normalized.replace(/\/+$/, '');
        }

        return normalized || '/';
    }

    function initRedirectEditor(options) {
        const modalElement = document.getElementById(options.modalId);
        if (!modalElement) {
            return;
        }

        const titleElement = document.getElementById(options.titleId);
        const idField = document.getElementById(options.idFieldId);
        const sourceField = document.getElementById(options.sourceFieldId);
        const siteScopeField = document.getElementById(options.siteScopeFieldId);
        const targetKindField = document.getElementById(options.targetKindFieldId);
        const targetPageField = document.getElementById(options.targetPageFieldId);
        const targetPostField = document.getElementById(options.targetPostFieldId);
        const targetHubField = document.getElementById(options.targetHubFieldId);
        const targetManualField = document.getElementById(options.targetManualFieldId);
        const targetHiddenField = document.getElementById(options.targetHiddenFieldId);
        const targetPageGroup = document.getElementById(options.targetPageGroupId);
        const targetPostGroup = document.getElementById(options.targetPostGroupId);
        const targetHubGroup = document.getElementById(options.targetHubGroupId);
        const targetManualGroup = document.getElementById(options.targetManualGroupId);
        const typeField = document.getElementById(options.typeFieldId);
        const notesField = document.getElementById(options.notesFieldId);
        const activeField = document.getElementById(options.activeFieldId);
        const form = modalElement.querySelector('form');
        const targetCatalog = options.targets || { pages: [], posts: [], hubs: [] };
        const siteCatalog = options.sites || [];

        function getModalInstance() {
            if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
                return null;
            }
            return bootstrap.Modal.getOrCreateInstance(modalElement);
        }

        function updateTargetFieldVisibility() {
            const kind = targetKindField.value || 'manual';
            targetPageGroup.hidden = kind !== 'page';
            targetPostGroup.hidden = kind !== 'post';
            targetHubGroup.hidden = kind !== 'hub';
            targetManualGroup.hidden = kind !== 'manual';
            targetManualField.required = kind === 'manual';
            targetPageField.required = kind === 'page';
            targetPostField.required = kind === 'post';
            targetHubField.required = kind === 'hub';
        }

        function resetSelectors() {
            targetPageField.value = '';
            targetPostField.value = '';
            targetHubField.value = '';
            targetManualField.value = '';
            targetHiddenField.value = '';
        }

        function syncHiddenTargetValue() {
            const kind = targetKindField.value || 'manual';
            if (kind === 'page') {
                const item = (targetCatalog.pages || []).find((entry) => String(entry.id) === String(targetPageField.value || ''));
                targetHiddenField.value = item ? (item.url || '') : '';
                return;
            }
            if (kind === 'post') {
                const item = (targetCatalog.posts || []).find((entry) => String(entry.id) === String(targetPostField.value || ''));
                targetHiddenField.value = item ? (item.url || '') : '';
                return;
            }
            if (kind === 'hub') {
                const item = (targetCatalog.hubs || []).find((entry) => String(entry.id) === String(targetHubField.value || ''));
                targetHiddenField.value = item ? (item.url || '') : '';
                return;
            }

            targetHiddenField.value = targetManualField.value.trim();
        }

        function applyTargetValue(url) {
            const normalized = normalizeTargetUrl(url);
            resetSelectors();
            const mappings = [
                ['page', targetCatalog.pages || []],
                ['post', targetCatalog.posts || []],
                ['hub', targetCatalog.hubs || []]
            ];

            for (const [kind, items] of mappings) {
                const match = items.find((item) => normalizeTargetUrl(item.url) === normalized);
                if (match) {
                    targetKindField.value = kind;
                    updateTargetFieldVisibility();
                    if (kind === 'page') {
                        targetPageField.value = String(match.id || '');
                    } else if (kind === 'post') {
                        targetPostField.value = String(match.id || '');
                    } else if (kind === 'hub') {
                        targetHubField.value = String(match.id || '');
                    }
                    targetHiddenField.value = match.url || '';
                    return;
                }
            }

            targetKindField.value = 'manual';
            updateTargetFieldVisibility();
            targetManualField.value = url || '';
            targetHiddenField.value = url || '';
        }

        function applySuggestedSiteScope(value) {
            const normalized = String(value || '');
            const match = siteCatalog.find((site) => String(site.value || '') === normalized);
            siteScopeField.value = match ? normalized : '';
        }

        function resetForm() {
            titleElement.textContent = options.createTitle;
            idField.value = '0';
            sourceField.value = '';
            siteScopeField.value = '';
            targetKindField.value = 'manual';
            resetSelectors();
            updateTargetFieldVisibility();
            typeField.value = '301';
            notesField.value = '';
            activeField.checked = true;
        }

        function openCreateModal() {
            const modal = getModalInstance();
            if (!modal) {
                return;
            }

            resetForm();
            modal.show();
            window.setTimeout(() => sourceField.focus(), 120);
        }

        function openEditModal(payload) {
            const modal = getModalInstance();
            if (!modal) {
                return;
            }

            resetForm();
            const redirectId = payload.redirect_id || payload.id || 0;
            titleElement.textContent = redirectId ? options.editTitle : options.createTitle;
            idField.value = redirectId;
            sourceField.value = payload.source_path || payload.request_path || '';
            applySuggestedSiteScope(payload.site_scope || payload.site_scope_match || payload.site_scope_suggestion || '');
            applyTargetValue(payload.target_url || '');
            if (payload.redirect_type) {
                typeField.value = String(payload.redirect_type);
            }

            const noteParts = [];
            if (options.mode === 'not-found') {
                if (payload.redirect_notes) {
                    noteParts.push(String(payload.redirect_notes));
                }
                if (payload.request_host_label) {
                    noteParts.push('Host: ' + payload.request_host_label);
                }
                if (payload.referrer_url) {
                    noteParts.push('Referrer: ' + payload.referrer_url);
                }
                if (payload.hit_count) {
                    noteParts.push('404-Hits: ' + payload.hit_count);
                }
                notesField.value = noteParts.join(' | ');
                activeField.checked = String(payload.redirect_is_active || '1') !== '0';
            } else {
                notesField.value = payload.notes || '';
                activeField.checked = String(payload.is_active || '0') === '1' || payload.is_active === 1;
            }

            modal.show();
            window.setTimeout(() => sourceField.focus(), 120);
        }

        targetKindField.addEventListener('change', function () {
            resetSelectors();
            updateTargetFieldVisibility();
        });

        [targetPageField, targetPostField, targetHubField].forEach((field) => field.addEventListener('change', syncHiddenTargetValue));
        targetManualField.addEventListener('input', syncHiddenTargetValue);
        targetManualField.addEventListener('change', syncHiddenTargetValue);

        if (form) {
            form.addEventListener('submit', function () {
                syncHiddenTargetValue();
            });
        }

        updateTargetFieldVisibility();

        if (options.mode === 'redirect-manager') {
            document.querySelectorAll('.js-create-redirect').forEach((button) => button.addEventListener('click', openCreateModal));
            document.querySelectorAll('.js-edit-redirect').forEach((button) => {
                button.addEventListener('click', function () {
                    try {
                        openEditModal(JSON.parse(button.dataset.redirect || '{}'));
                    } catch (error) {
                        console.error('Weiterleitung konnte nicht geladen werden.', error);
                    }
                });
            });
        }

        if (options.mode === 'not-found') {
            const hideResolvedToggle = document.getElementById('toggle-hide-resolved-404');
            const resolvedRows = Array.from(document.querySelectorAll('tr[data-log-resolved]'));
            const hiddenResolvedEmptyState = document.querySelector('.js-hidden-resolved-empty');
            const hideResolvedStorageKey = 'cms-admin-hide-resolved-404';

            function applyResolvedFilter(hideResolved) {
                let visibleRows = 0;
                resolvedRows.forEach((row) => {
                    const resolved = row.dataset.logResolved === '1';
                    const shouldHide = hideResolved && resolved;
                    row.hidden = shouldHide;
                    if (!shouldHide) {
                        visibleRows += 1;
                    }
                });

                if (hiddenResolvedEmptyState) {
                    hiddenResolvedEmptyState.hidden = !(hideResolved && visibleRows === 0 && resolvedRows.length > 0);
                }
            }

            if (hideResolvedToggle) {
                hideResolvedToggle.checked = window.localStorage.getItem(hideResolvedStorageKey) === '1';
                applyResolvedFilter(hideResolvedToggle.checked);
                hideResolvedToggle.addEventListener('change', function () {
                    const hideResolved = hideResolvedToggle.checked;
                    window.localStorage.setItem(hideResolvedStorageKey, hideResolved ? '1' : '0');
                    applyResolvedFilter(hideResolved);
                });
            }

            document.querySelectorAll('.js-takeover-log').forEach((button) => {
                button.addEventListener('click', function () {
                    try {
                        openEditModal(JSON.parse(button.dataset.log || '{}'));
                    } catch (error) {
                        console.error('404-Eintrag konnte nicht geladen werden.', error);
                    }
                });
            });
        }
    }

    function init() {
        const redirectConfig = parseConfig('seo-redirect-manager-config');
        if (redirectConfig) {
            initRedirectEditor({
                mode: 'redirect-manager',
                modalId: 'redirectModal',
                titleId: 'redirect-modal-title',
                idFieldId: 'redirect-id',
                sourceFieldId: 'redirect-source',
                siteScopeFieldId: 'redirect-site-scope',
                targetKindFieldId: 'redirect-target-kind',
                targetPageFieldId: 'redirect-target-page',
                targetPostFieldId: 'redirect-target-post',
                targetHubFieldId: 'redirect-target-hub',
                targetManualFieldId: 'redirect-target-manual',
                targetHiddenFieldId: 'redirect-target-hidden',
                targetPageGroupId: 'redirect-target-page-group',
                targetPostGroupId: 'redirect-target-post-group',
                targetHubGroupId: 'redirect-target-hub-group',
                targetManualGroupId: 'redirect-target-manual-group',
                typeFieldId: 'redirect-type',
                notesFieldId: 'redirect-notes',
                activeFieldId: 'redirect-active',
                createTitle: 'Weiterleitung anlegen',
                editTitle: 'Weiterleitung bearbeiten',
                targets: redirectConfig.targets || { pages: [], posts: [], hubs: [] },
                sites: redirectConfig.sites || []
            });
        }

        const notFoundConfig = parseConfig('seo-not-found-config');
        if (notFoundConfig) {
            initRedirectEditor({
                mode: 'not-found',
                modalId: 'notFoundRedirectModal',
                titleId: 'redirect-modal-title',
                idFieldId: 'redirect-id',
                sourceFieldId: 'redirect-source',
                siteScopeFieldId: 'redirect-site-scope',
                targetKindFieldId: 'redirect-target-kind',
                targetPageFieldId: 'redirect-target-page',
                targetPostFieldId: 'redirect-target-post',
                targetHubFieldId: 'redirect-target-hub',
                targetManualFieldId: 'redirect-target-manual',
                targetHiddenFieldId: 'redirect-target-hidden',
                targetPageGroupId: 'redirect-target-page-group',
                targetPostGroupId: 'redirect-target-post-group',
                targetHubGroupId: 'redirect-target-hub-group',
                targetManualGroupId: 'redirect-target-manual-group',
                typeFieldId: 'redirect-type',
                notesFieldId: 'redirect-notes',
                activeFieldId: 'redirect-active',
                createTitle: '404-Weiterleitung übernehmen',
                editTitle: '404-Weiterleitung bearbeiten',
                targets: notFoundConfig.targets || { pages: [], posts: [], hubs: [] },
                sites: notFoundConfig.sites || []
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
