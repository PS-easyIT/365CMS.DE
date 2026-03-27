(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var actionForm = document.getElementById('actionForm');
        var actionTypeInput = document.getElementById('actionType');
        var actionIdInput = document.getElementById('actionId');
        var actionStatusInput = document.getElementById('actionStatus');
        var selectAll = document.getElementById('selectAll');
        var bulkBar = document.getElementById('bulkBar');
        var countElement = document.getElementById('selectedCount');
        var tableWrap = document.querySelector('.comments-table-responsive');

        function submitAction(action, commentId, newStatus) {
            if (!actionForm || !actionTypeInput || !actionIdInput || !actionStatusInput) {
                return;
            }

            actionTypeInput.value = action;
            actionIdInput.value = commentId;
            actionStatusInput.value = newStatus || '';
            actionForm.submit();
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