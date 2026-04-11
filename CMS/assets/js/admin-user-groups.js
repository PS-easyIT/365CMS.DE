(function () {
    'use strict';

    function setSubmittingState(form, isSubmitting) {
        if (!form) {
            return;
        }

        form.dataset.submitting = isSubmitting ? '1' : '0';

        var submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = isSubmitting;
            submitButton.setAttribute('aria-disabled', isSubmitting ? 'true' : 'false');
        }
    }

    function submitWithTemporarySubmitter(form) {
        if (!form) {
            return;
        }

        var submitter = document.createElement('button');
        submitter.type = 'submit';
        submitter.hidden = true;
        submitter.tabIndex = -1;
        submitter.setAttribute('aria-hidden', 'true');
        form.appendChild(submitter);
        submitter.click();
        submitter.remove();
    }

    document.addEventListener('DOMContentLoaded', function () {
        var modalElement = document.getElementById('groupModal');
        var groupForm = document.getElementById('groupForm');
        var groupIdInput = document.getElementById('groupId');
        var groupNameInput = document.getElementById('groupName');
        var groupDescInput = document.getElementById('groupDesc');
        var modalTitle = document.getElementById('groupModalTitle');
        var deleteGroupIdInput = document.getElementById('deleteGroupId');
        var deleteGroupForm = document.getElementById('deleteGroupForm');

        if (modalElement) {
            modalElement.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                if (!button || !groupIdInput || !groupNameInput || !groupDescInput || !modalTitle) {
                    return;
                }

                groupIdInput.value = button.getAttribute('data-group-id') || '0';
                groupNameInput.value = button.getAttribute('data-group-name') || '';
                groupDescInput.value = button.getAttribute('data-group-description') || '';
                modalTitle.textContent = button.getAttribute('data-group-modal-title') || 'Gruppe bearbeiten';
                setSubmittingState(groupForm, false);
            });
        }

        document.querySelectorAll('.js-group-modal-trigger').forEach(function (button) {
            button.addEventListener('click', function () {
                if (modalElement || !groupIdInput || !groupNameInput || !groupDescInput || !modalTitle) {
                    return;
                }

                groupIdInput.value = button.getAttribute('data-group-id') || '0';
                groupNameInput.value = button.getAttribute('data-group-name') || '';
                groupDescInput.value = button.getAttribute('data-group-description') || '';
                modalTitle.textContent = button.getAttribute('data-group-modal-title') || 'Gruppe bearbeiten';
            });
        });

        if (groupForm) {
            groupForm.addEventListener('submit', function (event) {
                if (groupForm.dataset.submitting === '1') {
                    event.preventDefault();
                    return;
                }

                setSubmittingState(groupForm, true);
            });
        }

        document.querySelectorAll('.js-delete-group').forEach(function (button) {
            button.addEventListener('click', function () {
                var groupId = button.getAttribute('data-group-id') || '0';
                var groupName = button.getAttribute('data-group-name') || '';
                var message = 'Gruppe "' + groupName + '" wirklich löschen?';

                if (!deleteGroupIdInput || !deleteGroupForm) {
                    return;
                }

                var submitDelete = function () {
                    if (deleteGroupForm.dataset.submitting === '1') {
                        return;
                    }

                    deleteGroupForm.dataset.submitting = '1';
                    deleteGroupIdInput.value = groupId;
                    submitWithTemporarySubmitter(deleteGroupForm);
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