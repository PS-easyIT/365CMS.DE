(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var actionForm = document.getElementById('actionForm');
        var actionTypeInput = document.getElementById('actionType');
        var actionIdInput = document.getElementById('actionId');
        var actionStatusInput = document.getElementById('actionStatus');
        var selectAll = document.getElementById('selectAll');
        var bulkBar = document.getElementById('bulkBar');
        var bulkForm = document.getElementById('bulkForm');
        var countElement = document.getElementById('selectedCount');
        var tableWrap = document.querySelector('.comments-table-responsive');

        function submitForm(form) {
            if (!form) {
                return;
            }

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
                return;
            }

            if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
                return;
            }

            if (typeof HTMLFormElement !== 'undefined'
                && HTMLFormElement.prototype
                && typeof HTMLFormElement.prototype.submit === 'function') {
                HTMLFormElement.prototype.submit.call(form);
                return;
            }

            form.submit();
        }

        function submitAction(action, commentId, newStatus) {
            if (!actionForm || !actionTypeInput || !actionIdInput || !actionStatusInput) {
                return;
            }

            actionTypeInput.value = action;
            actionIdInput.value = commentId;
            actionStatusInput.value = newStatus || '';
            submitForm(actionForm);
        }

        document.querySelectorAll('.js-comment-action').forEach(function (button) {
            button.addEventListener('click', function () {
                submitAction(
                    button.getAttribute('data-comment-action') || '',
                    button.getAttribute('data-comment-id') || '0',
                    button.getAttribute('data-comment-status') || ''
                );
            });
        });

        document.querySelectorAll('.js-comment-delete').forEach(function (button) {
            button.addEventListener('click', function () {
                var commentId = button.getAttribute('data-comment-id') || '0';
                var confirmDelete = function () {
                    submitAction('delete', commentId, '');
                };

                if (typeof cmsConfirm === 'function') {
                    cmsConfirm({
                        title: 'Kommentar löschen',
                        message: 'Kommentar endgültig löschen? Dies kann nicht rückgängig gemacht werden.',
                        confirmText: 'Löschen',
                        confirmClass: 'btn-danger',
                        onConfirm: confirmDelete,
                    });
                    return;
                }

                if (window.confirm('Kommentar endgültig löschen? Dies kann nicht rückgängig gemacht werden.')) {
                    confirmDelete();
                }
            });
        });

        function updateBulkBar() {
            if (!bulkBar || !countElement) {
                return;
            }

            var checkedCount = document.querySelectorAll('.row-check:checked').length;
            countElement.textContent = String(checkedCount);
            bulkBar.classList.toggle('d-none', checkedCount === 0);
        }

        function setDropdownOverflow(open) {
            if (!tableWrap) {
                return;
            }

            tableWrap.style.overflow = open ? 'visible' : '';
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                document.querySelectorAll('.row-check').forEach(function (checkbox) {
                    checkbox.checked = selectAll.checked;
                });
                updateBulkBar();
            });
        }

        if (bulkForm) {
            bulkForm.addEventListener('submit', function (event) {
                var bulkAction = bulkForm.querySelector('select[name="bulk_action"]');
                var selectedAction = bulkAction ? String(bulkAction.value || '').trim() : '';
                var checkedCount = document.querySelectorAll('.row-check:checked').length;

                if (selectedAction !== 'delete') {
                    return;
                }

                if (bulkForm.dataset.confirmAccepted === '1') {
                    bulkForm.dataset.confirmAccepted = '0';
                    return;
                }

                event.preventDefault();

                var message = checkedCount > 1
                    ? String(checkedCount) + ' Kommentare endgültig löschen? Dies kann nicht rückgängig gemacht werden.'
                    : 'Kommentar endgültig löschen? Dies kann nicht rückgängig gemacht werden.';
                var confirmDelete = function () {
                    bulkForm.dataset.confirmAccepted = '1';
                    submitForm(bulkForm);
                };

                if (typeof cmsConfirm === 'function') {
                    cmsConfirm({
                        title: 'Kommentare löschen',
                        message: message,
                        confirmText: 'Löschen',
                        confirmClass: 'btn-danger',
                        statusClass: 'bg-danger',
                        onConfirm: confirmDelete,
                    });
                    return;
                }

                if (window.confirm(message)) {
                    confirmDelete();
                }
            });
        }

        document.querySelectorAll('.row-check').forEach(function (checkbox) {
            checkbox.addEventListener('change', updateBulkBar);
        });

        document.querySelectorAll('.comments-table-responsive .dropdown').forEach(function (dropdown) {
            dropdown.addEventListener('show.bs.dropdown', function () {
                setDropdownOverflow(true);
            });

            dropdown.addEventListener('hidden.bs.dropdown', function () {
                setDropdownOverflow(false);
            });
        });

        updateBulkBar();
    });
})();