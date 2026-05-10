(function () {
    'use strict';

    var RECENT_LINKS_STORAGE_KEY = 'cms365-admin-recent-links';
    var RECENT_LINKS_STORAGE_LIMIT = 8;
    var RECENT_LINKS_DISPLAY_LIMIT = 6;

    function storageAvailable(type) {
        var storage;
        try {
            storage = window[type];
            var testKey = '__storage_test__';
            storage.setItem(testKey, testKey);
            storage.removeItem(testKey);
            return true;
        } catch (error) {
            return !!(
                error instanceof DOMException
                && error.name === 'QuotaExceededError'
                && storage
                && storage.length !== 0
            );
        }
    }

    function safeGetLocalStorageItem(key) {
        try {
            return window.localStorage.getItem(key);
        } catch (error) {
            return null;
        }
    }

    function safeSetLocalStorageItem(key, value) {
        try {
            window.localStorage.setItem(key, value);
            return true;
        } catch (error) {
            return false;
        }
    }

    function isValidAdminUrl(url) {
        return typeof url === 'string'
            && /^\/admin(?:\/|$)/.test(url)
            && /[\x00-\x1F\x7F]/.test(url) === false;
    }

    function normalizeRecentEntry(entry) {
        if (!entry || typeof entry !== 'object') {
            return null;
        }

        var rawUrl = typeof entry.url === 'string' ? entry.url.trim() : '';
        var rawLabel = typeof entry.label === 'string' ? entry.label.trim() : '';

        if (!isValidAdminUrl(rawUrl) || rawLabel === '') {
            return null;
        }

        var normalized = {
            url: rawUrl.slice(0, 512),
            label: rawLabel.slice(0, 160)
        };

        if (typeof entry.ts === 'string') {
            var timestamp = new Date(entry.ts);
            if (!Number.isNaN(timestamp.getTime())) {
                normalized.ts = timestamp.toISOString();
            }
        }

        return normalized;
    }

    function sanitizeRecentEntries(entries, limit) {
        var normalizedEntries = [];
        var seenUrls = Object.create(null);

        if (!Array.isArray(entries)) {
            return normalizedEntries;
        }

        entries.forEach(function (entry) {
            var normalized = normalizeRecentEntry(entry);
            if (!normalized || seenUrls[normalized.url]) {
                return;
            }

            seenUrls[normalized.url] = true;
            normalizedEntries.push(normalized);
        });

        return normalizedEntries.slice(0, limit);
    }

    function readRecentEntries() {
        var parsedEntries = [];
        var rawValue = safeGetLocalStorageItem(RECENT_LINKS_STORAGE_KEY);

        if (typeof rawValue === 'string' && rawValue !== '') {
            try {
                parsedEntries = JSON.parse(rawValue);
            } catch (error) {
                parsedEntries = [];
            }
        }

        var sanitizedEntries = sanitizeRecentEntries(parsedEntries, RECENT_LINKS_STORAGE_LIMIT);
        if (JSON.stringify(sanitizedEntries) !== JSON.stringify(Array.isArray(parsedEntries) ? parsedEntries : [])) {
            safeSetLocalStorageItem(RECENT_LINKS_STORAGE_KEY, JSON.stringify(sanitizedEntries));
        }

        return sanitizedEntries;
    }

    function formatRecentTimestamp(value) {
        if (typeof value !== 'string' || value.trim() === '') {
            return '';
        }

        var timestamp = new Date(value);
        if (Number.isNaN(timestamp.getTime())) {
            return '';
        }

        try {
            return new Intl.DateTimeFormat('de-DE', {
                dateStyle: 'short',
                timeStyle: 'short'
            }).format(timestamp);
        } catch (error) {
            return timestamp.toLocaleString('de-DE');
        }
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function initRecentLinks() {
        var recentRoot = document.getElementById('dashboard-recent-links');
        if (!recentRoot) {
            return;
        }

        var emptyText = recentRoot.dataset.emptyText || 'Noch keine zuletzt genutzten Admin-Ziele gespeichert.';

        function renderEmpty(message) {
            recentRoot.innerHTML = '<div class="text-secondary">' + escapeHtml(message) + '</div>';
        }

        function renderRecentLinks() {
            if (!storageAvailable('localStorage')) {
                renderEmpty('Lokaler Verlauf ist in diesem Browser aktuell nicht verfügbar.');
                return;
            }

            var entries = readRecentEntries().slice(0, RECENT_LINKS_DISPLAY_LIMIT);

            if (entries.length === 0) {
                renderEmpty(emptyText);
                return;
            }

            recentRoot.innerHTML = '<div class="list-group list-group-flush">'
                + entries.map(function (entry) {
                    var timestamp = formatRecentTimestamp(entry.ts);

                    return '<a class="list-group-item list-group-item-action" href="'
                        + escapeHtml(entry.url)
                        + '"><div class="fw-semibold">'
                        + escapeHtml(entry.label)
                        + '</div><div class="small text-secondary">'
                        + escapeHtml(timestamp)
                        + '</div></a>';
                }).join('')
                + '</div>';
        }

        renderRecentLinks();
        window.addEventListener('storage', function (event) {
            if (event.storageArea === window.localStorage && (event.key === null || event.key === RECENT_LINKS_STORAGE_KEY)) {
                renderRecentLinks();
            }
        });
    }

    function initSortablePreferenceLists() {
        document.querySelectorAll('[data-dashboard-sortable-list]').forEach(function (list) {
            var orderInputId = list.getAttribute('data-order-input') || '';
            var orderInput = orderInputId !== '' ? document.getElementById(orderInputId) : null;
            if (!orderInput) {
                return;
            }

            var dragItem = null;

            function getItems() {
                return Array.prototype.slice.call(list.querySelectorAll('[data-sort-key]'));
            }

            function clearDropTargets() {
                getItems().forEach(function (item) {
                    item.classList.remove('is-drop-target');
                });
            }

            function syncOrder() {
                var values = [];
                getItems().forEach(function (item) {
                    var key = item.getAttribute('data-sort-key') || '';
                    if (key !== '') {
                        values.push(key);
                    }
                });
                orderInput.value = values.join(',');
                updateMoveButtons();
            }

            function updateMoveButtons() {
                var items = getItems();
                items.forEach(function (item, index) {
                    item.querySelectorAll('[data-sort-move]').forEach(function (button) {
                        var direction = button.getAttribute('data-sort-move') || '';
                        button.disabled = (direction === 'up' && index === 0)
                            || (direction === 'down' && index === items.length - 1);
                    });
                });
            }

            function moveItem(item, direction) {
                if (!(item instanceof HTMLElement)) {
                    return;
                }

                if (direction === 'up' && item.previousElementSibling) {
                    list.insertBefore(item, item.previousElementSibling);
                    syncOrder();
                    return;
                }

                if (direction === 'down' && item.nextElementSibling) {
                    list.insertBefore(item.nextElementSibling, item);
                    syncOrder();
                }
            }

            getItems().forEach(function (item) {
                item.setAttribute('aria-grabbed', 'false');

                item.addEventListener('dragstart', function (event) {
                    dragItem = item;
                    clearDropTargets();
                    item.classList.add('is-dragging');
                    item.setAttribute('aria-grabbed', 'true');

                    if (event.dataTransfer) {
                        event.dataTransfer.clearData();
                        event.dataTransfer.effectAllowed = 'move';
                        event.dataTransfer.setData('text/plain', item.getAttribute('data-sort-key') || '');
                    }
                });

                item.addEventListener('dragend', function () {
                    item.classList.remove('is-dragging');
                    item.setAttribute('aria-grabbed', 'false');
                    clearDropTargets();
                    dragItem = null;
                    syncOrder();
                });

                item.addEventListener('dragover', function (event) {
                    if (!dragItem || dragItem === item) {
                        return;
                    }

                    event.preventDefault();
                    clearDropTargets();
                    item.classList.add('is-drop-target');
                    if (event.dataTransfer) {
                        event.dataTransfer.dropEffect = 'move';
                    }
                });

                item.addEventListener('dragleave', function (event) {
                    if (event.currentTarget === item) {
                        item.classList.remove('is-drop-target');
                    }
                });

                item.addEventListener('drop', function (event) {
                    event.preventDefault();
                    clearDropTargets();

                    if (!dragItem || dragItem === item) {
                        return;
                    }

                    var nodes = getItems();
                    var dragIndex = nodes.indexOf(dragItem);
                    var dropIndex = nodes.indexOf(item);
                    if (dragIndex < 0 || dropIndex < 0) {
                        return;
                    }

                    if (dragIndex < dropIndex) {
                        list.insertBefore(dragItem, item.nextSibling);
                    } else {
                        list.insertBefore(dragItem, item);
                    }

                    syncOrder();
                });

                item.querySelectorAll('[data-sort-move]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        moveItem(item, button.getAttribute('data-sort-move') || '');
                    });
                });
            });

            syncOrder();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initRecentLinks();
        initSortablePreferenceLists();
    });
}());
