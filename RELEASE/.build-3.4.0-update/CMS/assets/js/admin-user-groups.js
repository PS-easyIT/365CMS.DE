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
    }

    function submitFormAfterConfirmation(form) {
        if (!form) {
            return;
        }

        form.dataset.confirmedSubmit = '1';
        submitWithTemporarySubmitter(form);
    }

    function openModal(modalId) {
        var modalElement = document.getElementById(modalId);
        if (!modalElement || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return null;
        }

        return bootstrap.Modal.getOrCreateInstance(modalElement);
    }

    function ensureDeleteConfirmationModal() {
        var existingModal = document.getElementById('adminDeleteConfirmModal');
        if (existingModal) {
            return existingModal;
        }

        var wrapper = document.createElement('div');
        wrapper.innerHTML = ''
            + '<div class="modal modal-blur fade" id="adminDeleteConfirmModal" tabindex="-1" aria-hidden="true">'
            + '  <div class="modal-dialog modal-dialog-centered">'
            + '    <div class="modal-content">'
            + '      <div class="modal-header">'
            + '        <h5 class="modal-title">Wirklich löschen?</h5>'
            + '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>'
            + '      </div>'
            + '      <div class="modal-body">'
            + '        <p class="mb-2"><strong id="adminDeleteConfirmEntity"></strong></p>'
            + '        <p class="text-secondary mb-0">Diese Aktion kann nicht rückgängig gemacht werden.</p>'
            + '      </div>'
            + '      <div class="modal-footer">'
            + '        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>'
            + '        <button type="button" class="btn btn-danger" id="adminDeleteConfirmAccept">Löschen</button>'
            + '      </div>'
            + '    </div>'
            + '  </div>'
            + '</div>';

        document.body.appendChild(wrapper.firstElementChild);
        return document.getElementById('adminDeleteConfirmModal');
    }

    function requestDeleteConfirmation(entityLabel, onConfirm) {
        var modalElement = ensureDeleteConfirmationModal();
        var entityElement = document.getElementById('adminDeleteConfirmEntity');
        var acceptButton = document.getElementById('adminDeleteConfirmAccept');
        if (typeof onConfirm !== 'function') {
            return;
        }

        if (!modalElement || !entityElement || !acceptButton) {
            if (window.confirm('Wirklich löschen?\n\n' + (entityLabel || 'Ausgewählter Eintrag') + '\n\nDiese Aktion kann nicht rückgängig gemacht werden.')) {
                onConfirm();
            }
            return;
        }

        entityElement.textContent = entityLabel || 'Ausgewählter Eintrag';
        var modal = openModal('adminDeleteConfirmModal');
        if (!modal) {
            if (window.confirm('Wirklich löschen?\n\n' + (entityLabel || 'Ausgewählter Eintrag') + '\n\nDiese Aktion kann nicht rückgängig gemacht werden.')) {
                onConfirm();
            }
            return;
        }

        var handleConfirm = function () {
            acceptButton.removeEventListener('click', handleConfirm);
            modal.hide();
            onConfirm();
        };

        acceptButton.addEventListener('click', handleConfirm);
        modal.show();
    }

    function initGroupsBulkActions() {
        var root = document.getElementById('groupsListRoot');
        var bulkForm = document.getElementById('bulkFormGroups');
        var countElement = document.getElementById('selectedCountGroups');
        var bulkActionSelect = document.getElementById('bulkActionGroups');
        var bulkPlanWrap = document.getElementById('bulkGroupsPlanWrap');
        var bulkPlanSelect = document.getElementById('bulkGroupsPlanSelect');
        var selectAllCheckbox = document.querySelector('.js-groups-bulk-all');
        var clearSelectionButton = document.querySelector('.js-groups-bulk-clear');
        var bulkSubmitButton = bulkForm ? bulkForm.querySelector('button[type="submit"]') : null;
        var selectedIds = new Set();

        var bulkActionLabels = {
            activate: 'Gruppen aktivieren',
            deactivate: 'Gruppen deaktivieren',
            set_plan: 'Paket zuweisen',
            clear_plan: 'Paket entfernen',
            delete: 'Gruppen löschen'
        };

        if (!root || !bulkForm || !countElement || !bulkActionSelect || !bulkSubmitButton || !selectAllCheckbox || !clearSelectionButton) {
            return;
        }

        setSubmittingState(bulkForm, false);

        function getRowCheckboxes() {
            return Array.prototype.slice.call(root.querySelectorAll('.js-groups-bulk-row'));
        }

        function syncCheckboxes() {
            var rowCheckboxes = getRowCheckboxes();
            rowCheckboxes.forEach(function (checkbox) {
                checkbox.checked = selectedIds.has(String(checkbox.value));

                var card = checkbox.closest('.card');
                if (card) {
                    card.classList.toggle('border-primary', checkbox.checked);
                }
            });

            var allSelected = rowCheckboxes.length > 0 && rowCheckboxes.every(function (checkbox) {
                return checkbox.checked;
            });

            selectAllCheckbox.checked = allSelected;
        }

        function updateState() {
            var requiresPlan = bulkActionSelect.value === 'set_plan';
            var hasValidPlanSelection = !requiresPlan || (bulkPlanSelect && bulkPlanSelect.value !== '0');
            var canSubmit = selectedIds.size > 0 && bulkActionSelect.value !== '' && hasValidPlanSelection;

            countElement.textContent = String(selectedIds.size);
            clearSelectionButton.disabled = selectedIds.size === 0;
            clearSelectionButton.setAttribute('aria-disabled', selectedIds.size === 0 ? 'true' : 'false');
            bulkSubmitButton.disabled = !canSubmit;
            bulkSubmitButton.setAttribute('aria-disabled', canSubmit ? 'false' : 'true');
            bulkSubmitButton.textContent = bulkActionLabels[bulkActionSelect.value] || 'Aktion wählen…';

            if (bulkPlanWrap) {
                bulkPlanWrap.classList.toggle('d-none', !requiresPlan);
            }

            if (!requiresPlan && bulkPlanSelect) {
                bulkPlanSelect.value = '0';
            }

            syncCheckboxes();
        }

        root.addEventListener('change', function (event) {
            var target = event.target;
            if (!(target instanceof HTMLInputElement) || !target.classList.contains('js-groups-bulk-row')) {
                return;
            }

            if (target.checked) {
                selectedIds.add(String(target.value));
            } else {
                selectedIds.delete(String(target.value));
            }

            updateState();
        });

        selectAllCheckbox.addEventListener('change', function () {
            getRowCheckboxes().forEach(function (checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
                if (selectAllCheckbox.checked) {
                    selectedIds.add(String(checkbox.value));
                } else {
                    selectedIds.delete(String(checkbox.value));
                }
            });

            updateState();
        });

        clearSelectionButton.addEventListener('click', function () {
            selectedIds.clear();
            updateState();
        });

        bulkActionSelect.addEventListener('change', updateState);

        if (bulkPlanSelect) {
            bulkPlanSelect.addEventListener('change', updateState);
        }

        bulkForm.addEventListener('submit', function (event) {
            if (bulkForm.dataset.confirmedSubmit === '1') {
                bulkForm.dataset.confirmedSubmit = '0';
                return;
            }

            if (bulkForm.dataset.submitting === '1') {
                event.preventDefault();
                return;
            }

            bulkForm.querySelectorAll('input[name="ids[]"]').forEach(function (input) {
                input.remove();
            });

            if (selectedIds.size === 0) {
                event.preventDefault();
                setSubmittingState(bulkForm, false);
                return;
            }

            if (bulkActionSelect.value === '') {
                event.preventDefault();
                setSubmittingState(bulkForm, false);
                bulkActionSelect.focus();
                return;
            }

            if (bulkActionSelect.value === 'set_plan' && bulkPlanSelect && bulkPlanSelect.value === '0') {
                event.preventDefault();
                setSubmittingState(bulkForm, false);
                bulkPlanSelect.focus();
                return;
            }

            var submitBulk = function () {
                selectedIds.forEach(function (id) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = id;
                    bulkForm.appendChild(input);
                });

                setSubmittingState(bulkForm, true);
                submitFormAfterConfirmation(bulkForm);
            };

            if (bulkActionSelect.value === 'delete') {
                event.preventDefault();
                requestDeleteConfirmation('Ausgewählte Gruppen (' + selectedIds.size + ')', submitBulk);
                return;
            }

            event.preventDefault();
            submitBulk();
        });

        updateState();
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

                requestDeleteConfirmation(groupName, submitDelete);
            });
        });

        initGroupsBulkActions();
    });
})();