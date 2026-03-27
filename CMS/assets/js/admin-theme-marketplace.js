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
            console.error('admin-theme-marketplace: Konfiguration konnte nicht gelesen werden.', error);
            return null;
        }
    }

    function bindMarketplaceFilter(config) {
        if (!config) {
            return;
        }

        var search = document.getElementById(config.searchInputId || 'themeMarketplaceSearch');
        var statusFilter = document.getElementById(config.statusFilterId || 'themeMarketplaceStatusFilter');
        var cards = document.querySelectorAll(config.cardSelector || '.theme-marketplace-card');
        var emptyState = document.querySelector(config.emptyStateSelector || '');

        if (!search || !cards.length) {
            return;
        }

        function applyFilter() {
            var query = (search.value || '').toLowerCase();
            var status = statusFilter ? (statusFilter.value || '') : '';
            var visibleCount = 0;

            cards.forEach(function (card) {
                var name = (card.dataset.name || '').toLowerCase();
                var cardStatus = card.dataset.status || '';
                var matches = (!query || name.indexOf(query) !== -1) && (!status || cardStatus === status);

                card.style.display = matches ? '' : 'none';
                if (matches) {
                    visibleCount += 1;
                }
            });

            if (emptyState) {
                emptyState.classList.toggle('d-none', visibleCount !== 0);
            }
        }

        search.addEventListener('input', applyFilter);
        if (statusFilter) {
            statusFilter.addEventListener('change', applyFilter);
        }

        applyFilter();
    }

    function bindMarketplaceInstallForms(config) {
        if (!config) {
            return;
        }

        var forms = document.querySelectorAll(config.installFormSelector || 'form[data-theme-marketplace-install-form]');
        if (!forms.length) {
            return;
        }

        forms.forEach(function (form) {
            form.addEventListener('submit', function () {
                if (form.dataset.confirmAccepted !== '1') {
                    return;
                }

                var button = form.querySelector('[data-theme-marketplace-submit-button]');
                if (!button || button.dataset.themeMarketplaceSubmitting === '1') {
                    return;
                }

                button.dataset.themeMarketplaceSubmitting = '1';
                button.disabled = true;
                button.innerHTML = button.dataset.submittingText || 'Installiere…';
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var config = parseConfig('theme-marketplace-config');
        bindMarketplaceFilter(config);
        bindMarketplaceInstallForms(config);
    });
})();
