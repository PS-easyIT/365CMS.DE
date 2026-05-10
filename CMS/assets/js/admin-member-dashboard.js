(function () {
    'use strict';

    function initSortableLists() {
        document.querySelectorAll('[data-member-sortable-list]').forEach(function (list) {
            const orderInputId = list.getAttribute('data-order-input') || '';
            const orderInput = orderInputId !== '' ? document.getElementById(orderInputId) : null;
            if (!orderInput) {
                return;
            }

            let dragItem = null;

            const getItems = function () {
                return Array.prototype.slice.call(list.querySelectorAll('[data-sort-key]'));
            };

            const clearDropTargets = function () {
                getItems().forEach(function (item) {
                    item.classList.remove('is-drop-target');
                });
            };

            const syncOrder = function () {
                const values = [];
                getItems().forEach(function (item) {
                    const sortKey = item.getAttribute('data-sort-key') || '';
                    if (sortKey !== '') {
                        values.push(sortKey);
                    }
                });
                orderInput.value = values.join(',');
                updateMoveButtons();
            };

            const updateMoveButtons = function () {
                const items = getItems();
                items.forEach(function (item, index) {
                    item.querySelectorAll('[data-sort-move]').forEach(function (button) {
                        const direction = button.getAttribute('data-sort-move') || '';
                        button.disabled = (direction === 'up' && index === 0)
                            || (direction === 'down' && index === items.length - 1);
                    });
                });
            };

            const moveItem = function (item, direction) {
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
            };

            getItems().forEach(function (item) {
                item.setAttribute('aria-grabbed', 'false');

                const dragSources = item.querySelectorAll('[data-sort-handle]');
                const attachDragLifecycle = function (dragSource) {
                    if (!(dragSource instanceof HTMLElement)) {
                        return;
                    }

                    dragSource.setAttribute('draggable', 'true');

                    dragSource.addEventListener('dragstart', function (event) {
                        dragItem = item;
                        clearDropTargets();
                        item.classList.add('is-dragging');
                        item.classList.add('opacity-50');
                        item.setAttribute('aria-grabbed', 'true');

                        if (event.dataTransfer) {
                            event.dataTransfer.clearData();
                            event.dataTransfer.effectAllowed = 'move';
                            event.dataTransfer.setData('text/plain', item.getAttribute('data-sort-key') || '');
                        }
                    });

                    dragSource.addEventListener('dragend', function () {
                        item.classList.remove('is-dragging');
                        item.classList.remove('opacity-50');
                        item.setAttribute('aria-grabbed', 'false');
                        clearDropTargets();
                        dragItem = null;
                        syncOrder();
                    });
                };

                if (dragSources.length > 0) {
                    dragSources.forEach(function (dragSource) {
                        attachDragLifecycle(dragSource);
                    });
                } else {
                    attachDragLifecycle(item);
                }

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

                    const nodes = getItems();
                    const dragIndex = nodes.indexOf(dragItem);
                    const dropIndex = nodes.indexOf(item);
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
        initSortableLists();
    });
}());
