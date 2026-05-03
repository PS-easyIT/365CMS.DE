(function () {
    'use strict';

    function parseConfig(wrapper) {
        var rawConfig = wrapper.getAttribute('data-site-table-config') || '';
        if (rawConfig === '') {
            return null;
        }

        try {
            return JSON.parse(rawConfig);
        } catch (error) {
            console.error('site-tables: Konfiguration konnte nicht gelesen werden.', error);
            return null;
        }
    }

    function clearElement(element) {
        if (!element) {
            return;
        }

        while (element.firstChild) {
            element.removeChild(element.firstChild);
        }
    }

    function createButton(label, className, disabled, onClick) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = className;
        button.textContent = label;
        button.disabled = Boolean(disabled);
        button.addEventListener('click', onClick);
        return button;
    }

    function normalizeText(value) {
        return String(value || '').replace(/\s+/g, ' ').trim();
    }

    function lower(value) {
        return normalizeText(value).toLocaleLowerCase();
    }

    function initTable(wrapper) {
        if (!wrapper || wrapper.dataset.siteTableBound === '1') {
            return;
        }

        var config = parseConfig(wrapper);
        var table = wrapper.querySelector('table.cms-site-table');
        var tbody = table && table.tBodies.length > 0 ? table.tBodies[0] : null;
        var headers = table ? Array.prototype.slice.call(table.querySelectorAll('thead th')) : [];
        var searchInput = wrapper.querySelector('[data-site-table-search]');
        var statusElement = wrapper.querySelector('[data-site-table-status]');
        var paginationElement = wrapper.querySelector('[data-site-table-pagination]');
        var labels = config && typeof config === 'object' && config.labels ? config.labels : {};
        var collator = typeof Intl !== 'undefined' && typeof Intl.Collator === 'function'
            ? new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' })
            : null;

        if (!config || !table || !tbody) {
            return;
        }

        var originalRows = Array.prototype.slice.call(tbody.querySelectorAll('tr')).filter(function (row) {
            return !(row.children.length === 1 && row.firstElementChild && row.firstElementChild.hasAttribute('colspan'));
        }).map(function (row) {
            return {
                element: row,
                text: lower(row.textContent || ''),
                cells: Array.prototype.slice.call(row.children).map(function (cell) {
                    return normalizeText(cell.textContent || '');
                }),
            };
        });

        var state = {
            query: '',
            sortIndex: -1,
            sortDirection: 'asc',
            page: 1,
        };

        function compareValues(left, right) {
            if (collator) {
                return collator.compare(left, right);
            }

            if (left === right) {
                return 0;
            }

            return left > right ? 1 : -1;
        }

        function updateHeaderState() {
            headers.forEach(function (header, index) {
                var indicator = header.querySelector('.cms-site-table__sort-indicator');
                var isActive = state.sortIndex === index;
                var ariaSort = 'none';
                var indicatorText = '↕';

                if (isActive) {
                    ariaSort = state.sortDirection === 'desc' ? 'descending' : 'ascending';
                    indicatorText = state.sortDirection === 'desc' ? '↓' : '↑';
                }

                if (header.hasAttribute('aria-sort')) {
                    header.setAttribute('aria-sort', ariaSort);
                }

                if (indicator) {
                    indicator.textContent = indicatorText;
                }
            });
        }

        function getFilteredRows() {
            if (state.query === '') {
                return originalRows.slice();
            }

            return originalRows.filter(function (row) {
                return row.text.indexOf(state.query) !== -1;
            });
        }

        function getSortedRows(rows) {
            if (!config.sortingEnabled || state.sortIndex < 0) {
                return rows.slice();
            }

            return rows.slice().sort(function (left, right) {
                var leftValue = normalizeText(left.cells[state.sortIndex] || '');
                var rightValue = normalizeText(right.cells[state.sortIndex] || '');
                var result = compareValues(leftValue, rightValue);

                return state.sortDirection === 'desc' ? result * -1 : result;
            });
        }

        function getVisibleRows(rows) {
            if (!config.paginationEnabled) {
                return rows;
            }

            var pageSize = Number(config.pageSize || 10);
            var totalPages = Math.max(1, Math.ceil(rows.length / pageSize));
            if (state.page > totalPages) {
                state.page = totalPages;
            }

            var start = (state.page - 1) * pageSize;
            return rows.slice(start, start + pageSize);
        }

        function renderStatus(filteredCount, totalCount, totalPages) {
            if (!statusElement) {
                return;
            }

            if (totalCount === 0) {
                statusElement.textContent = labels.emptyStatic || '';
                return;
            }

            var rowLabel = filteredCount === 1 ? (labels.rowsLabelSingle || 'Zeile') : (labels.rowsLabelPlural || 'Zeilen');
            var status = filteredCount + ' ' + rowLabel;

            if (filteredCount !== totalCount) {
                status += ' · ' + (labels.filteredFrom || 'gefiltert aus') + ' ' + totalCount;
            }

            if (config.paginationEnabled && totalPages > 1) {
                status += ' · ' + (labels.page || 'Seite') + ' ' + state.page + ' ' + (labels.of || 'von') + ' ' + totalPages;
            }

            statusElement.textContent = status;
        }

        function renderPagination(filteredRows) {
            if (!paginationElement) {
                return;
            }

            clearElement(paginationElement);

            if (!config.paginationEnabled) {
                return;
            }

            var pageSize = Number(config.pageSize || 10);
            var totalPages = Math.max(1, Math.ceil(filteredRows.length / pageSize));

            if (totalPages <= 1) {
                return;
            }

            var list = document.createElement('div');
            list.className = 'cms-site-table-pagination__list';

            list.appendChild(createButton(labels.previous || 'Zurück', 'cms-site-table-pagination__button', state.page <= 1, function () {
                if (state.page <= 1) {
                    return;
                }

                state.page -= 1;
                render();
            }));

            for (var page = 1; page <= totalPages; page += 1) {
                (function (pageNumber) {
                    var button = createButton(String(pageNumber), 'cms-site-table-pagination__button' + (pageNumber === state.page ? ' is-active' : ''), false, function () {
                        state.page = pageNumber;
                        render();
                    });
                    button.setAttribute('aria-current', pageNumber === state.page ? 'page' : 'false');
                    list.appendChild(button);
                })(page);
            }

            list.appendChild(createButton(labels.next || 'Weiter', 'cms-site-table-pagination__button', state.page >= totalPages, function () {
                if (state.page >= totalPages) {
                    return;
                }

                state.page += 1;
                render();
            }));

            paginationElement.appendChild(list);
        }

        function renderEmptyState() {
            var emptyRow = document.createElement('tr');
            var emptyCell = document.createElement('td');
            emptyCell.colSpan = Math.max(1, headers.length);
            emptyCell.className = 'cms-site-table__empty';
            emptyCell.textContent = labels.emptyFiltered || labels.emptyStatic || 'Keine passenden Tabellenzeilen gefunden.';
            emptyRow.appendChild(emptyCell);
            tbody.appendChild(emptyRow);
        }

        function render() {
            var filteredRows = getSortedRows(getFilteredRows());
            var visibleRows = getVisibleRows(filteredRows);
            var pageSize = Number(config.pageSize || 10);
            var totalPages = config.paginationEnabled ? Math.max(1, Math.ceil(filteredRows.length / pageSize)) : 1;

            clearElement(tbody);

            if (filteredRows.length === 0) {
                renderEmptyState();
            } else {
                visibleRows.forEach(function (row) {
                    tbody.appendChild(row.element);
                });
            }

            renderStatus(filteredRows.length, originalRows.length, totalPages);
            renderPagination(filteredRows);
            updateHeaderState();
        }

        if (searchInput && config.searchEnabled) {
            searchInput.addEventListener('input', function () {
                state.query = lower(searchInput.value || '');
                state.page = 1;
                render();
            });
        }

        if (config.sortingEnabled) {
            headers.forEach(function (header, index) {
                var trigger = header.querySelector('[data-site-table-sort]');
                if (!trigger) {
                    return;
                }

                trigger.addEventListener('click', function () {
                    if (state.sortIndex === index) {
                        state.sortDirection = state.sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        state.sortIndex = index;
                        state.sortDirection = 'asc';
                    }

                    state.page = 1;
                    render();
                });
            });
        }

        wrapper.dataset.siteTableBound = '1';
        render();
    }

    function boot() {
        document.querySelectorAll('.cms-site-table-wrap[data-site-table-config]').forEach(initTable);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
        return;
    }

    boot();
})();
