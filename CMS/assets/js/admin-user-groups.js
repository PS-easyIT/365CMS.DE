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

        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
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

        if (form.dataset.submitting === '1') {
            return;
        }

        form.submit();
    }

    document.addEventListener('DOMContentLoaded', function () {
        var modalElement = document.getElementById('groupModal');
        var groupForm = document.getElementById('groupForm');
        var groupIdInput = document.getElementById('groupId');
        var groupNameInput = document.getElementById('groupName');
        var groupSlugInput = document.getElementById('groupSlug');
        var groupDescInput = document.getElementById('groupDesc');
        var groupPlanInput = document.getElementById('groupPlan');
        var groupIsActiveInput = document.getElementById('groupIsActive');
        var modalTitle = document.getElementById('groupModalTitle');
        var deleteGroupIdInput = document.getElementById('deleteGroupId');
        var deleteGroupForm = document.getElementById('deleteGroupForm');
        var groupMemberInputs = Array.prototype.slice.call(document.querySelectorAll('input[name="member_ids[]"]'));

        function applyGroupModalData(button) {
            if (!button || !groupIdInput || !groupNameInput || !groupDescInput || !modalTitle || !groupSlugInput || !groupPlanInput || !groupIsActiveInput) {
                return;
            }

            var memberIdsRaw = button.getAttribute('data-group-member-ids') || '[]';
            var memberIds = [];
            try {
                memberIds = JSON.parse(memberIdsRaw);
            } catch (error) {
                memberIds = [];
            }

            var selectedIds = new Set(Array.isArray(memberIds) ? memberIds.map(function (id) { return String(id); }) : []);

            groupIdInput.value = button.getAttribute('data-group-id') || '0';
            groupNameInput.value = button.getAttribute('data-group-name') || '';
            groupSlugInput.value = button.getAttribute('data-group-slug') || '';
            groupDescInput.value = button.getAttribute('data-group-description') || '';
            groupPlanInput.value = button.getAttribute('data-group-plan-id') || '0';
            groupIsActiveInput.checked = (button.getAttribute('data-group-is-active') || '1') === '1';
            modalTitle.textContent = button.getAttribute('data-group-modal-title') || 'Gruppe bearbeiten';

            groupMemberInputs.forEach(function (input) {
                input.checked = selectedIds.has(String(input.value));
            });

            setSubmittingState(groupForm, false);
        }

        if (modalElement) {
            modalElement.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                if (!button) {
                    return;
                }

                applyGroupModalData(button);
            });
        }

        document.querySelectorAll('.js-group-modal-trigger').forEach(function (button) {
            button.addEventListener('click', function () {
                if (modalElement) {
                    return;
                }

                applyGroupModalData(button);
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
                var message = 'Gruppe "' + groupName + '" wirklich löschen? Zugeordnete Mitgliedschaften werden dabei ebenfalls entfernt.';

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