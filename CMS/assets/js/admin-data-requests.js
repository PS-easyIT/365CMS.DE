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

    function init() {
        var config = parseConfig('data-requests-config');
        bindRejectButtons(config);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();