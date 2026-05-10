(function () {
    'use strict';

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

        var storageKey = 'cms365-admin-recent-links';
        var emptyText = recentRoot.dataset.emptyText || 'Noch keine zuletzt genutzten Admin-Ziele gespeichert.';

        function renderEmpty(message) {
            recentRoot.innerHTML = '<div class="text-secondary">' + escapeHtml(message) + '</div>';
        }

        function renderRecentLinks() {
            if (!storageAvailable('localStorage')) {
                renderEmpty('Lokaler Verlauf ist in diesem Browser aktuell nicht verfügbar.');
                return;
            }

            var entries = [];
            try {
                entries = JSON.parse(window.localStorage.getItem(storageKey) || '[]');
            } catch (error) {
                entries = [];
            }

            if (!Array.isArray(entries)) {
                entries = [];
            }

            entries = entries.filter(function (entry) {
                return entry
                    && typeof entry.url === 'string'
                    && /^\/admin(?:\/|$)/.test(entry.url)
                    && typeof entry.label === 'string'
                    && entry.label.trim() !== '';
            }).slice(0, 6);

            if (entries.length === 0) {
                renderEmpty(emptyText);
                return;
            }

            recentRoot.innerHTML = '<div class="list-group list-group-flush">'
                + entries.map(function (entry) {
                    var timestamp = typeof entry.ts === 'string' && entry.ts.trim() !== ''
                        ? new Date(entry.ts).toLocaleString('de-DE', { dateStyle: 'short', timeStyle: 'short' })
                        : '';

                    return '<a class="list-group-item list-group-item-action" href="'
                        + escapeHtml(entry.url)
                        + '"><div class="fw-semibold">'
                        + escapeHtml(entry.label)
                        + '</div><div class="small text-secondary">'
                        + escapeHtml(timestamp !== 'Invalid Date' ? timestamp : '')
                        + '</div></a>';
                }).join('')
                + '</div>';
        }

        renderRecentLinks();
        window.addEventListener('storage', renderRecentLinks);
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
                item.addEventListener('dragstart', function (event) {
                    dragItem = item;
                    item.classList.add('is-dragging');

                    if (event.dataTransfer) {
                        event.dataTransfer.clearData();
                        event.dataTransfer.effectAllowed = 'move';
                        event.dataTransfer.setData('text/plain', item.getAttribute('data-sort-key') || '');
                    }
                });

                item.addEventListener('dragend', function () {
                    item.classList.remove('is-dragging');
                    item.classList.remove('is-drop-target');
                    dragItem = null;
                    syncOrder();
                });

                item.addEventListener('dragover', function (event) {
                    if (!dragItem || dragItem === item) {
                        return;
                    }

                    event.preventDefault();
                    item.classList.add('is-drop-target');
                    if (event.dataTransfer) {
                        event.dataTransfer.dropEffect = 'move';
                    }
                });

                item.addEventListener('dragleave', function () {
                    item.classList.remove('is-drop-target');
                });

                item.addEventListener('drop', function (event) {
                    event.preventDefault();
                    item.classList.remove('is-drop-target');

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
