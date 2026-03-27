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

    function initPagesGrid(config) {
        if (!config || typeof window.cmsGrid !== 'function' || typeof window.gridjs === 'undefined') {
            return;
        }

        window.cmsGrid('#pagesGrid', {
            url: config.apiUrl || '',
            search: false,
            limit: 20,
            extraParams: {
                status: config.status || '',
                category: config.category || 0,
                search: config.search || ''
            },
            sortMap: {1: 'title', 2: 'slug', 4: 'status', 6: 'created_at'},
            columns: [
                {
                    id: 'id',
                    name: window.gridjs.html('<input class="form-check-input bulk-select-all" type="checkbox" aria-label="Alle Seiten auswählen">'),
                    sort: false,
                    formatter: function (cell) {
                        return window.gridjs.html('<input class="form-check-input bulk-row-check" type="checkbox" value="' + encodeURIComponent(cell) + '" aria-label="Seite auswählen">');
                    }
                },
                {
                    id: 'title',
                    name: 'Titel',
                    data: function (row) { return { id: row.id, title: row.title, slug: row.slug }; },
                    formatter: function (cell) {
                        return window.gridjs.html(
                            '<div>' +
                                '<a href="' + (config.siteUrl || '') + '/admin/pages?action=edit&id=' + encodeURIComponent(cell.id) + '" class="text-reset fw-medium">' + window.cmsEsc(cell.title || '') + '</a>' +
                            '</div>'
                        );
                    }
                },
                {
                    id: 'slug',
                    name: 'Slug',
                    formatter: function (cell) { return '/' + window.cmsEsc(cell || ''); }
                },
                {
                    id: 'category_name',
                    name: 'Kategorie',
                    formatter: function (cell) {
                        return cell ? window.gridjs.html('<span class="badge bg-azure-lt">' + window.cmsEsc(cell) + '</span>') : '–';
                    }
                },
                {
                    id: 'status',
                    name: 'Status',
                    formatter: function (cell) {
                        var map = { published: 'bg-green', draft: 'bg-yellow', private: 'bg-purple' };
                        var labelMap = { published: 'Veröffentlicht', draft: 'Entwurf', private: 'Privat' };

                        return window.gridjs.html('<span class="badge ' + (map[cell] || 'bg-secondary') + '">' + window.cmsEsc(labelMap[cell] || cell || '') + '</span>');
                    }
                },
                { id: 'author_name', name: 'Autor' },
                { id: 'created_at', name: 'Erstellt am' },
                {
                    id: 'id',
                    name: '',
                    sort: false,
                    formatter: function (cell) {
                        return window.gridjs.html('<a href="' + (config.siteUrl || '') + '/admin/pages?action=edit&id=' + encodeURIComponent(cell) + '" class="btn btn-sm btn-outline-primary">Bearbeiten</a>');
                    }
                }
            ]
        });
    }

    function bindPagesFilterAutoSubmit() {
        document.querySelectorAll('.js-pages-filter-submit').forEach(function (element) {
            element.addEventListener('change', function () {
                if (element.form) {
                    element.form.submit();
                }
            });
        });
    }

    function bindPagesBulkUi() {
        var gridRoot = document.getElementById('pagesGrid');
        var bulkForm = document.getElementById('bulkFormPages');
        var bulkBar = document.getElementById('bulkBarPages');
        var countEl = document.getElementById('selectedCountPages');
        var bulkActionSelect = bulkForm ? bulkForm.querySelector('[name="bulk_action"]') : null;
        var bulkCategorySelect = document.getElementById('bulkCategoryPages');
        var selectedIds = new Set();

        if (!gridRoot || !bulkForm || !bulkBar || !countEl) {
            return;
        }

        function updateBulkActionUi() {
            if (!bulkActionSelect || !bulkCategorySelect) {
                return;
            }

            var requiresCategory = bulkActionSelect.value === 'set_category';
            bulkCategorySelect.classList.toggle('d-none', !requiresCategory);
            bulkCategorySelect.required = requiresCategory;

            if (!requiresCategory) {
                bulkCategorySelect.value = '0';
            }
        }

        function syncInputs() {
            gridRoot.querySelectorAll('.bulk-row-check').forEach(function (checkbox) {
                checkbox.checked = selectedIds.has(String(checkbox.value));
            });

            var allCheckboxes = Array.prototype.slice.call(gridRoot.querySelectorAll('.bulk-row-check'));
            var selectAll = gridRoot.querySelector('.bulk-select-all');
            if (selectAll) {
                selectAll.checked = allCheckboxes.length > 0 && allCheckboxes.every(function (checkbox) {
                    return checkbox.checked;
                });
            }
        }

        function updateBulkState() {
            countEl.textContent = String(selectedIds.size);
            bulkBar.classList.toggle('d-none', selectedIds.size === 0);
            syncInputs();
        }

        gridRoot.addEventListener('change', function (event) {
            var target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            if (target.classList.contains('bulk-row-check')) {
                if (target.checked) {
                    selectedIds.add(String(target.value));
                } else {
                    selectedIds.delete(String(target.value));
                }

                updateBulkState();
                return;
            }

            if (target.classList.contains('bulk-select-all')) {
                gridRoot.querySelectorAll('.bulk-row-check').forEach(function (checkbox) {
                    checkbox.checked = target.checked;
                    if (target.checked) {
                        selectedIds.add(String(checkbox.value));
                    } else {
                        selectedIds.delete(String(checkbox.value));
                    }
                });

                updateBulkState();
            }
        });

        bulkForm.addEventListener('submit', function (event) {
            bulkForm.querySelectorAll('input[name="ids[]"]').forEach(function (input) {
                input.remove();
            });

            if (selectedIds.size === 0) {
                event.preventDefault();
                return;
            }

            if (bulkActionSelect && bulkActionSelect.value === 'set_category' && bulkCategorySelect && bulkCategorySelect.value === '0') {
                event.preventDefault();
                bulkCategorySelect.focus();
                return;
            }

            selectedIds.forEach(function (id) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                bulkForm.appendChild(input);
            });
        });

        var observer = new MutationObserver(function () {
            syncInputs();
        });

        observer.observe(gridRoot, { childList: true, subtree: true });
        if (bulkActionSelect) {
            bulkActionSelect.addEventListener('change', updateBulkActionUi);
        }

        updateBulkActionUi();
        updateBulkState();
    }

    document.addEventListener('DOMContentLoaded', function () {
        initPagesGrid(parseConfig('pages-grid-config'));
        bindPagesFilterAutoSubmit();
        bindPagesBulkUi();
    });
})();