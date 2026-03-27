(function () {
    function parseConfig(id) {
        var element = document.getElementById(id);
        if (!element) {
            return null;
        }

        try {
            return JSON.parse(element.textContent || '{}');
        } catch (error) {
            return null;
        }
    }

    function bindMarketplaceFilter(config) {
        if (!config) {
            return;
        }

        var search = document.getElementById(config.searchInputId || 'pluginSearch');
        var filter = document.getElementById(config.categoryFilterId || 'categoryFilter');
        var statusFilter = document.getElementById(config.statusFilterId || 'statusFilter');
        var cards = document.querySelectorAll(config.cardSelector || '.plugin-card');
        var emptyState = document.querySelector(config.emptyStateSelector || '');

        if (!search || !cards.length) {
            return;
        }

        function applyFilter() {
            var query = (search.value || '').toLowerCase();
            var category = filter ? (filter.value || '') : '';
            var status = statusFilter ? (statusFilter.value || '') : '';
            var visibleCount = 0;

            cards.forEach(function (card) {
                var name = (card.dataset.name || '').toLowerCase();
                var cardCategory = card.dataset.category || '';
                var cardStatus = card.dataset.status || '';
                var matchQuery = !query || name.indexOf(query) !== -1;
                var matchCategory = !category || cardCategory === category;
                var matchStatus = !status || cardStatus === status;
                var visible = matchQuery && matchCategory && matchStatus;

                card.style.display = visible ? '' : 'none';
                if (visible) {
                    visibleCount += 1;
                }
            });

            if (emptyState) {
                emptyState.classList.toggle('d-none', visibleCount !== 0);
            }
        }

        search.addEventListener('input', applyFilter);
        if (filter) {
            filter.addEventListener('change', applyFilter);
        }
        if (statusFilter) {
            statusFilter.addEventListener('change', applyFilter);
        }

        applyFilter();
    }

    function bindMarketplaceInstallForms(config) {
        if (!config) {
            return;
        }

        var forms = document.querySelectorAll(config.installFormSelector || 'form[data-marketplace-install-form]');
        if (!forms.length) {
            return;
        }

        forms.forEach(function (form) {
            form.addEventListener('submit', function () {
                if (form.dataset.confirmAccepted !== '1' || form.dataset.submitting === '1') {
                    return;
                }

                var button = form.querySelector('[data-marketplace-submit-button]');
                if (!button || button.dataset.marketplaceSubmitting === '1') {
                    return;
                }

                form.dataset.submitting = '1';
                button.dataset.marketplaceSubmitting = '1';
                button.dataset.originalHtml = button.innerHTML;
                button.disabled = true;
                button.setAttribute('aria-disabled', 'true');
                button.innerHTML = button.dataset.submittingText || 'Installiere…';
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var config = parseConfig('plugin-marketplace-config');
        bindMarketplaceFilter(config);
        bindMarketplaceInstallForms(config);
    });
})();