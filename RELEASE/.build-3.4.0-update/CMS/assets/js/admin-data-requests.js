(function () {
    'use strict';

    function parseConfig(id) {
        var element = document.getElementById(id);
        if (!element) {
            return {};
        }

        try {
            return JSON.parse(element.textContent || '{}');
        } catch (error) {
            console.error('Data-Requests-Konfiguration konnte nicht gelesen werden.', error);
            return {};
        }
    }

    function getRejectModal(modalId) {
        var modalElement = document.getElementById(modalId);
        if (!modalElement || !window.bootstrap || !window.bootstrap.Modal) {
            return null;
        }

        return {
            element: modalElement,
            instance: window.bootstrap.Modal.getOrCreateInstance(modalElement)
        };
    }

    function bindRejectButtons(config) {
        var modalId = config.rejectModalId || 'rejectDataRequestModal';
        var modal = getRejectModal(modalId);
        if (!modal) {
            return;
        }

        var scopeField = modal.element.querySelector('#rejectScope');
        var idField = modal.element.querySelector('#rejectRequestId');
        var titleField = modal.element.querySelector('#rejectModalTitle');
        var reasonField = modal.element.querySelector('textarea[name="reject_reason"]');

        if (!scopeField || !idField || !titleField) {
            return;
        }

        document.querySelectorAll('.js-open-data-request-reject-modal').forEach(function (button) {
            button.addEventListener('click', function () {
                scopeField.value = button.dataset.requestScope || 'privacy';
                idField.value = button.dataset.requestId || '0';
                titleField.textContent = button.dataset.requestTitle || config.defaultRejectTitle || 'Anfrage ablehnen';

                if (reasonField) {
                    reasonField.value = '';
                }

                modal.instance.show();
            });
        });
    }

    function bindStatusFilters() {
        var chips = Array.prototype.slice.call(document.querySelectorAll('.js-request-status-chip'));
        var resetLink = document.querySelector('.js-request-status-reset');
        var rows = Array.prototype.slice.call(document.querySelectorAll('.js-request-filter-row'));

        if (!chips.length || !rows.length) {
            return;
        }

        var activeStatus = '';

        function updateActiveChipState() {
            chips.forEach(function (chip) {
                var isActive = chip.getAttribute('data-request-status') === activeStatus;
                chip.classList.toggle('btn-primary', isActive);
                chip.classList.toggle('btn-outline-primary', !isActive);
            });
        }

        function updateEmptyStates() {
            document.querySelectorAll('table.admin-request-table tbody').forEach(function (tbody) {
                var tableRows = Array.prototype.slice.call(tbody.querySelectorAll('.js-request-filter-row'));
                var visibleRows = tableRows.filter(function (row) {
                    return !row.classList.contains('d-none');
                });
                var emptyRow = tbody.querySelector('.js-request-filter-empty');
                if (emptyRow) {
                    emptyRow.classList.toggle('d-none', visibleRows.length > 0);
                }
            });
        }

        function applyFilter(status) {
            activeStatus = status || '';
            rows.forEach(function (row) {
                var rowStatus = row.getAttribute('data-request-status') || '';
                var isVisible = activeStatus === '' || rowStatus === activeStatus;
                row.classList.toggle('d-none', !isVisible);
            });
            updateActiveChipState();
            updateEmptyStates();
        }

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                var status = chip.getAttribute('data-request-status') || '';
                applyFilter(activeStatus === status ? '' : status);
            });
        });

        if (resetLink) {
            resetLink.addEventListener('click', function (event) {
                event.preventDefault();
                applyFilter('');
            });
        }
    }

    function init() {
        var config = parseConfig('data-requests-config');
        bindRejectButtons(config);
        bindStatusFilters();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();