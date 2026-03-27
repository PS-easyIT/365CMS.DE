(function () {
    'use strict';

    function getModalInstance(modalElement) {
        if (!modalElement || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return null;
        }

        return bootstrap.Modal.getOrCreateInstance(modalElement);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var modalElement = document.getElementById('groupModal');
        var modal = getModalInstance(modalElement);
        var groupIdInput = document.getElementById('groupId');
        var groupNameInput = document.getElementById('groupName');
        var groupDescInput = document.getElementById('groupDesc');
        var modalTitle = document.getElementById('groupModalTitle');
        var deleteGroupIdInput = document.getElementById('deleteGroupId');
        var deleteGroupForm = document.getElementById('deleteGroupForm');

        document.querySelectorAll('.js-group-modal-trigger').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!groupIdInput || !groupNameInput || !groupDescInput || !modalTitle) {
                    return;
                }

                groupIdInput.value = button.getAttribute('data-group-id') || '0';
                groupNameInput.value = button.getAttribute('data-group-name') || '';
                groupDescInput.value = button.getAttribute('data-group-description') || '';
                modalTitle.textContent = button.getAttribute('data-group-modal-title') || 'Gruppe bearbeiten';

                if (modal) {
                    modal.show();
                }
            });
        });

        document.querySelectorAll('.js-delete-group').forEach(function (button) {
            button.addEventListener('click', function () {
                var groupId = button.getAttribute('data-group-id') || '0';
                var groupName = button.getAttribute('data-group-name') || '';
                var message = 'Gruppe "' + groupName + '" wirklich löschen?';

                if (!deleteGroupIdInput || !deleteGroupForm) {
                    return;
                }

                var submitDelete = function () {
                    deleteGroupIdInput.value = groupId;
                    deleteGroupForm.submit();
                };

                if (typeof cmsConfirm === 'function') {
                    cmsConfirm({
                        title: 'Gruppe löschen',
                        message: message,
                        confirmText: 'Löschen',
                        confirmClass: 'btn-danger',
                        onConfirm: submitDelete,
                    });
                    return;
                }

                if (window.confirm(message)) {
                    submitDelete();
                }
            });
        });
    });
})();