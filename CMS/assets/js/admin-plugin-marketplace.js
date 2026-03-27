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
        var cards = document.querySelectorAll(config.cardSelector || '.plugin-card');

        if (!search || !filter || !cards.length) {
            return;
        }

        function applyFilter() {
            var query = (search.value || '').toLowerCase();
            var category = filter.value || '';

            cards.forEach(function (card) {
                var name = (card.dataset.name || '').toLowerCase();
                var cardCategory = card.dataset.category || '';
                var matchQuery = !query || name.indexOf(query) !== -1;
                var matchCategory = !category || cardCategory === category;

                card.style.display = matchQuery && matchCategory ? '' : 'none';
            });
        }

        search.addEventListener('input', applyFilter);
        filter.addEventListener('change', applyFilter);
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindMarketplaceFilter(parseConfig('plugin-marketplace-config'));
    });
})();