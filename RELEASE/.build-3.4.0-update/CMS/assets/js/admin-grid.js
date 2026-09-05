/**
 * Grid.js – Admin-Tabellen mit Server-Side Pagination
 *
 * Initialisiert Grid.js-Instanzen für Admin-Tabellen.
 * Erwartet ein Container-Element mit [data-grid] Attribut.
 *
 * Attribute:
 *   data-grid="posts"         – Endpunkt-Name (→ /api/v1/admin/{name})
 *   data-grid-columns='[...]' – JSON-Array der Spaltendefinitionen
 *   data-grid-limit="20"      – Einträge pro Seite (Standard: 20)
 *   data-grid-sort="updated_at" – Standard-Sortierung
 *
 * Einbindung (im Admin-Footer):
 *   <link rel="stylesheet" href="/assets/gridjs/mermaid.min.css">
 *   <script src="/assets/gridjs/gridjs.umd.js"></script>
 *   <script src="/assets/js/admin-grid.js"></script>
 *
 * @package CMSv2\Admin
 */
(function () {
    'use strict';

    const ADMIN_API_BASE = '/api/v1/admin/';

    /**
     * Erstellt eine Grid.js-Instanz für einen Container.
     */
    function initGrid(container) {
        const endpoint = container.dataset.grid;
        if (!endpoint) return;

        let columns;
        try {
            columns = JSON.parse(container.dataset.gridColumns || '[]');
        } catch (e) {
            console.error('admin-grid: Ungültige Spaltendefinition', e);
            return;
        }
        if (!columns.length) return;

        const limit = parseInt(container.dataset.gridLimit, 10) || 20;
        const defaultSort = container.dataset.gridSort || undefined;

        const gridColumns = columns.map(function (col) {
            var def = { id: col.id, name: col.name || col.id };
            if (col.width) def.width = col.width;
            if (col.sort === false) def.sort = false;
            if (col.formatter) {
                // Vordefinierte Formatter
                switch (col.formatter) {
                    case 'link':
                        def.formatter = function (cell, row) {
                            return gridjs.html(
                                '<a href="' + (col.href || '#').replace('{id}', row.cells[0].data) + '">' +
                                gridjs.html(cell || '–').toString() + '</a>'
                            );
                        };
                        break;
                    case 'badge':
                        def.formatter = function (cell) {
                            var cls = (col.badgeMap && col.badgeMap[cell]) || '';
                            return gridjs.html('<span class="badge ' + cls + '">' + (cell || '–') + '</span>');
                        };
                        break;
                    case 'timeago':
                        def.formatter = function (cell) {
                            if (!cell) return '–';
                            return gridjs.html('<span title="' + cell + '">' + cell + '</span>');
                        };
                        break;
                }
            }
            return def;
        });

        var grid = new gridjs.Grid({
            columns: gridColumns,
            server: {
                url: ADMIN_API_BASE + endpoint,
                then: function (data) { return data.data; },
                total: function (data) { return data.total; },
            },
            pagination: {
                enabled: true,
                limit: limit,
                server: {
                    url: function (prev, page, limit) {
                        var sep = prev.indexOf('?') > -1 ? '&' : '?';
                        return prev + sep + 'page=' + (page + 1) + '&limit=' + limit;
                    },
                },
            },
            search: {
                enabled: true,
                server: {
                    url: function (prev, keyword) {
                        var sep = prev.indexOf('?') > -1 ? '&' : '?';
                        return prev + sep + 'search=' + encodeURIComponent(keyword);
                    },
                },
            },
            sort: {
                enabled: true,
                multiColumn: false,
                server: {
                    url: function (prev, cols) {
                        if (!cols.length) return prev;
                        var col = cols[0];
                        var id = columns[col.index] ? columns[col.index].id : '';
                        var dir = col.direction === 1 ? 'ASC' : 'DESC';
                        var sep = prev.indexOf('?') > -1 ? '&' : '?';
                        return prev + sep + 'sort=' + id + '&order=' + dir;
                    },
                },
            },
            language: {
                search: { placeholder: 'Suchen…' },
                pagination: {
                    previous: '← Zurück',
                    next: 'Weiter →',
                    showing: 'Zeige',
                    of: 'von',
                    to: 'bis',
                    results: 'Einträge',
                },
                loading: 'Laden…',
                noRecordsFound: 'Keine Einträge gefunden',
                error: 'Fehler beim Laden der Daten',
            },
            className: {
                table: 'table table-vcenter card-table',
            },
        });

        grid.render(container);
    }

    // Alle Container initialisieren
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-grid]').forEach(initGrid);
    });
})();
