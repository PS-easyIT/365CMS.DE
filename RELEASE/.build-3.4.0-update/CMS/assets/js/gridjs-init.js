/**
 * CMS Grid.js Helper — Server-Side Dynamic Admin Tables
 *
 * Provides cmsGrid() for creating Grid.js instances with server-side
 * pagination, sorting, and search against the CMS JSON-API.
 *
 * Usage:
 *   const grid = cmsGrid('#container', {
 *       url: '/api/v1/admin/posts',
 *       columns: [ ... ],
 *       sortMap: { 0: 'title', 3: 'status', 4: 'views', 5: 'updated_at' },
 *       extraParams: { status: 'published' },
 *       limit: 20
 *   });
 */
(function () {
    'use strict';

    /* ── German language pack ─────────────────────────────── */
    var LANG_DE = {
        search:  { placeholder: 'Suchen…' },
        sort:    { sortAsc: 'Aufsteigend sortieren', sortDesc: 'Absteigend sortieren' },
        pagination: {
            previous: '‹',
            next:     '›',
            navigate: function (p, pages) { return 'Seite ' + p + ' von ' + pages; },
            page:     function (p) { return 'Seite ' + p; },
            showing:  '',
            of:       'von',
            to:       'bis',
            results:  'Einträgen'
        },
        loading:          'Laden…',
        noRecordsFound:   'Keine Einträge gefunden',
        error:            'Fehler beim Laden der Daten'
    };

    /* ── Client-side relative-time formatter ──────────────── */
    window.cmsTimeAgo = function (dateStr) {
        if (!dateStr) return '';
        var now  = Date.now();
        var then = new Date(dateStr.replace(' ', 'T')).getTime();
        if (isNaN(then)) return dateStr;
        var diff = Math.floor((now - then) / 1000);
        if (diff < 60)    return 'Gerade eben';
        if (diff < 3600)  return 'vor ' + Math.floor(diff / 60) + ' Min.';
        if (diff < 86400) return 'vor ' + Math.floor(diff / 3600) + ' Std.';
        if (diff < 172800) return 'Gestern';
        if (diff < 604800) return 'vor ' + Math.floor(diff / 86400) + ' Tagen';
        if (diff < 2592000) return 'vor ' + Math.floor(diff / 604800) + ' Wochen';
        // Fallback: formatted date
        var d = new Date(then);
        return d.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
    };

    /* ── Escape HTML to prevent XSS in cell formatters ───── */
    window.cmsEsc = function (str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    };

    /**
     * Create a Grid.js table with server-side data.
     *
     * @param {string|HTMLElement} container  CSS selector or DOM element
     * @param {Object}             opts
     * @param {string}             opts.url          Base API URL
     * @param {Array}              opts.columns      Grid.js column definitions
     * @param {Object}            [opts.sortMap]     Map column-index → API sort field name
     * @param {Object}            [opts.extraParams] Additional fixed query params
     * @param {number}            [opts.limit=20]    Rows per page
     * @param {boolean}           [opts.search=true] Enable search
     * @param {Object}            [opts.language]    Override language strings
     * @returns {gridjs.Grid}
     */
    window.cmsGrid = function (container, opts) {
        if (typeof gridjs === 'undefined') {
            console.error('[cmsGrid] gridjs is not loaded');
            return null;
        }

        var limit   = opts.limit || 20;
        var baseUrl = opts.url;
        var extra   = opts.extraParams || {};
        var sortMap = opts.sortMap || {};

        function buildUrl(params) {
            var url = new URL(baseUrl, window.location.origin);
            var merged = Object.assign({}, extra, { limit: limit }, params);
            Object.keys(merged).forEach(function (k) {
                var v = merged[k];
                if (v !== undefined && v !== null && v !== '') {
                    url.searchParams.set(k, v);
                }
            });
            return url.toString();
        }

        var grid = new gridjs.Grid({
            columns: opts.columns,
            server: {
                url:  buildUrl({}),
                then: function (data) { return data.data; },
                total: function (data) { return data.total; },
                handle: function (res) {
                    if (res.ok) return res.json();
                    throw Error('HTTP ' + res.status);
                }
            },
            pagination: {
                limit: limit,
                server: {
                    url: function (prev, page, lim) {
                        var u = new URL(prev);
                        u.searchParams.set('page', page + 1);
                        u.searchParams.set('limit', lim);
                        return u.toString();
                    }
                }
            },
            sort: {
                multiColumn: false,
                server: {
                    url: function (prev, columns) {
                        if (!columns.length) return prev;
                        var col   = columns[0];
                        var u     = new URL(prev);
                        var field = sortMap[col.index];
                        if (field) {
                            u.searchParams.set('sort', field);
                            u.searchParams.set('order', col.direction === 1 ? 'ASC' : 'DESC');
                        }
                        return u.toString();
                    }
                }
            },
            search: opts.search !== false ? {
                server: {
                    url: function (prev, keyword) {
                        var u = new URL(prev);
                        if (keyword) {
                            u.searchParams.set('search', keyword);
                            u.searchParams.set('page', '1');
                        } else {
                            u.searchParams.delete('search');
                        }
                        return u.toString();
                    }
                }
            } : undefined,
            language:  opts.language || LANG_DE,
            className: {
                container: 'gridjs-cms',
                table:     'table table-vcenter card-table',
                search:    'gridjs-search-wrap'
            },
            autoWidth:  true,
            resizable:  false
        });

        var el = typeof container === 'string' ? document.querySelector(container) : container;
        if (el) grid.render(el);

        return grid;
    };

})();
