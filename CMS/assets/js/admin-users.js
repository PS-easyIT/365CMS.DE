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

    function openModal(modalId) {
        var modalElement = document.getElementById(modalId);
        if (!modalElement || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return null;
        }

        return bootstrap.Modal.getOrCreateInstance(modalElement);
    }

    function initUsersFilters() {
        var roleSelect = document.querySelector('.js-users-filter-role');
        var statusSelect = document.querySelector('.js-users-filter-status');
        var searchForm = document.querySelector('.js-users-search-form');

        if (!roleSelect || !statusSelect || !searchForm) {
            return;
        }

        function submitFilters() {
            var baseUrl = roleSelect.getAttribute('data-users-base-url') || statusSelect.getAttribute('data-users-base-url') || searchForm.getAttribute('data-users-base-url') || '';
            var searchInput = searchForm.querySelector('.js-users-search-input');
            var searchValue = searchInput ? searchInput.value.trim() : '';
            var query = new URLSearchParams();

            if (roleSelect.value !== '') {
                query.set('role', roleSelect.value);
            }
            if (statusSelect.value !== '') {
                query.set('status', statusSelect.value);
            }
            if (searchValue !== '') {
                query.set('q', searchValue);
            }

            window.location.href = baseUrl + (query.toString() !== '' ? '?' + query.toString() : '');
        }

        roleSelect.addEventListener('change', submitFilters);
        statusSelect.addEventListener('change', submitFilters);
    }

    function initUsersBulkActions() {
        var root = document.getElementById('usersListRoot');
        var bulkBar = document.getElementById('bulkBarUsers');
        var bulkForm = document.getElementById('bulkFormUsers');
        var countElement = document.getElementById('selectedCountUsers');
        var bulkActionSelect = bulkForm ? bulkForm.querySelector('[name="bulk_action"]') : null;
        var bulkSubmitButton = bulkForm ? bulkForm.querySelector('button[type="submit"]') : null;
        var selectedIds = new Set();

        var bulkActionLabels = {
            activate: 'Benutzer aktivieren',
            deactivate: 'Benutzer deaktivieren',
            hard_delete: 'Benutzer löschen'
        };

        if (!root || !bulkBar || !bulkForm || !countElement || !bulkActionSelect || !bulkSubmitButton) {
            return;
        }

        setSubmittingState(bulkForm, false);

        function syncCheckboxes() {
            root.querySelectorAll('.bulk-row-check').forEach(function (checkbox) {
                checkbox.checked = selectedIds.has(String(checkbox.value));
            });

            var rowCheckboxes = Array.prototype.slice.call(root.querySelectorAll('.bulk-row-check'));
            var allSelected = rowCheckboxes.length > 0 && rowCheckboxes.every(function (checkbox) {
                return checkbox.checked;
            });

            root.querySelectorAll('.bulk-select-all').forEach(function (checkbox) {
                checkbox.checked = allSelected;
            });
        }

        function updateState() {
            countElement.textContent = String(selectedIds.size);
            bulkBar.classList.toggle('d-none', selectedIds.size === 0);
            var canSubmit = selectedIds.size > 0 && bulkActionSelect.value !== '';
            bulkSubmitButton.disabled = !canSubmit;
            bulkSubmitButton.setAttribute('aria-disabled', canSubmit ? 'false' : 'true');
            bulkSubmitButton.textContent = bulkActionLabels[bulkActionSelect.value] || 'Aktion wählen…';
            syncCheckboxes();
        }

        root.addEventListener('change', function (event) {
            var target = event.target;
            if (!(target instanceof HTMLInputElement)) {
                return;
            }

            if (target.classList.contains('bulk-row-check')) {
                if (target.checked) {
                    selectedIds.add(String(target.value));
                } else {
                    selectedIds.delete(String(target.value));
                }
                updateState();
                return;
            }

            if (target.classList.contains('bulk-select-all')) {
                root.querySelectorAll('.bulk-row-check').forEach(function (checkbox) {
                    checkbox.checked = target.checked;
                    if (target.checked) {
                        selectedIds.add(String(checkbox.value));
                    } else {
                        selectedIds.delete(String(checkbox.value));
                    }
                });
                updateState();
            }
        });

        bulkActionSelect.addEventListener('change', updateState);

        bulkForm.addEventListener('submit', function (event) {
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

            if (bulkActionSelect.value === 'hard_delete' && !window.confirm('Die ausgewählten Benutzer wirklich dauerhaft löschen?')) {
                event.preventDefault();
                setSubmittingState(bulkForm, false);
                return;
            }

            selectedIds.forEach(function (id) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                bulkForm.appendChild(input);
            });

            setSubmittingState(bulkForm, true);
        });

        updateState();
    }

    function initRolesUi() {
        document.querySelectorAll('.toggle-group').forEach(function (button) {
            button.addEventListener('click', function () {
                var group = button.getAttribute('data-group') || '';
                var checkboxes = document.querySelectorAll('.cap-checkbox[data-group="' + group + '"]');
                var allChecked = Array.from(checkboxes).every(function (checkbox) {
                    return checkbox.checked;
                });
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = !allChecked;
                });
            });
        });

        document.querySelectorAll('.js-edit-role').forEach(function (button) {
            button.addEventListener('click', function () {
                var role = button.getAttribute('data-role') || '';
                var currentInput = document.getElementById('edit_role_current');
                var slugInput = document.getElementById('edit_role_slug');
                if (currentInput) {
                    currentInput.value = role;
                }
                if (slugInput) {
                    slugInput.value = role;
                }
                var modal = openModal('editRoleModal');
                if (modal) {
                    modal.show();
                }
            });
        });

        document.querySelectorAll('.js-delete-role').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById('delete_role_slug');
                if (input) {
                    input.value = button.getAttribute('data-role') || '';
                }
                var modal = openModal('deleteRoleModal');
                if (modal) {
                    modal.show();
                }
            });
        });

        document.querySelectorAll('.js-edit-capability').forEach(function (button) {
            button.addEventListener('click', function () {
                var capability = button.getAttribute('data-capability') || '';
                var currentInput = document.getElementById('edit_capability_current');
                var slugInput = document.getElementById('edit_capability_slug');
                if (currentInput) {
                    currentInput.value = capability;
                }
                if (slugInput) {
                    slugInput.value = capability;
                }
                var modal = openModal('editCapabilityModal');
                if (modal) {
                    modal.show();
                }
            });
        });

        document.querySelectorAll('.js-delete-capability').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById('delete_capability_slug');
                if (input) {
                    input.value = button.getAttribute('data-capability') || '';
                }
                var modal = openModal('deleteCapabilityModal');
                if (modal) {
                    modal.show();
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initUsersFilters();
        initUsersBulkActions();
        initRolesUi();
    });
})();