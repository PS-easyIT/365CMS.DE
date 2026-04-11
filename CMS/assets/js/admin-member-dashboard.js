(function () {
    'use strict';

    function initPluginWidgetSorting() {
        document.querySelectorAll('[data-member-plugin-widget-list]').forEach(function (list) {
            const orderInputId = list.getAttribute('data-order-input') || '';
            const orderInput = orderInputId !== '' ? document.getElementById(orderInputId) : null;
            if (!orderInput) {
                return;
            }

            let dragItem = null;

            const syncOrder = function () {
                const values = [];
                list.querySelectorAll('[data-plugin]').forEach(function (item) {
                    const plugin = item.getAttribute('data-plugin') || '';
                    if (plugin !== '') {
                        values.push(plugin);
                    }
                });
                orderInput.value = values.join(',');
            };

            list.querySelectorAll('[data-plugin]').forEach(function (item) {
                item.addEventListener('dragstart', function () {
                    dragItem = item;
                    item.classList.add('opacity-50');
                });

                item.addEventListener('dragend', function () {
                    item.classList.remove('opacity-50');
                    dragItem = null;
                    syncOrder();
                });

                item.addEventListener('dragover', function (event) {
                    event.preventDefault();
                });

                item.addEventListener('drop', function (event) {
                    event.preventDefault();
                    if (!dragItem || dragItem === item) {
                        return;
                    }

                    const nodes = Array.prototype.slice.call(list.children);
                    const dragIndex = nodes.indexOf(dragItem);
                    const dropIndex = nodes.indexOf(item);
                    if (dragIndex < dropIndex) {
                        list.insertBefore(dragItem, item.nextSibling);
                    } else {
                        list.insertBefore(dragItem, item);
                    }

                    syncOrder();
                });
            });

            syncOrder();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initPluginWidgetSorting();
    });
}());
