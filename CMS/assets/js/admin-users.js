(function () {
    'use strict';

    function parseConfig(id) {
        var element = document.getElementById(id);
        if (!element) {
            return null;
        }

        try {
            return JSON.parse(element.textContent || '{}');
        } catch (error) {
            console.error('admin-users: Konfiguration konnte nicht gelesen werden.', error);
            return null;
        }
    }

    function openModal(modalId) {
        var modalElement = document.getElementById(modalId);
        if (!modalElement || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return null;
        }

        return bootstrap.Modal.getOrCreateInstance(modalElement);
    }

    function initUsersGrid() {
        var config = parseConfig('users-grid-config');
        var adminBasePath = '/admin/users';
        if (!config || typeof cmsGrid !== 'function') {
            return;
        }

        cmsGrid('#usersGrid', {
            url: config.apiUrl,
            search: false,
            limit: 20,
            extraParams: {
                role: config.role || '',
                status: config.status || '',
                search: config.search || '',
            },
            sortMap: { 0: 'username', 1: 'email', 2: 'role', 3: 'status', 4: 'created_at' },
            columns: [
                {
                    id: 'username',
                    name: 'Benutzer',
                    data: function (row) {
                        return {
                            id: row.id,
                            username: row.username,
                            display_name: row.display_name,
                            role: row.role,
                        };
                    },
                    formatter: function (cell) {
                        var initials = (cell.username || '').substring(0, 2).toUpperCase();
                        return gridjs.html(
                            '<div class="d-flex align-items-center">' +
                                '<span class="avatar avatar-sm me-2 bg-azure">' + window.cmsEsc(initials) + '</span>' +
                                '<div>' +
                                    '<a href="' + adminBasePath + '?action=edit&id=' + encodeURIComponent(cell.id) + '" class="text-reset">' + window.cmsEsc(cell.username || '') + '</a>' +
                                    (cell.display_name ? '<div class="text-secondary small">' + window.cmsEsc(cell.display_name) + '</div>' : '') +
                                '</div>' +
                            '</div>'
                        );
                    },
                },
                { id: 'email', name: 'E-Mail' },
                {
                    id: 'role',
                    name: 'Rolle',
                    formatter: function (cell) {
                        return gridjs.html('<span class="badge bg-azure-lt">' + window.cmsEsc(cell || '') + '</span>');
                    },
                },
                {
                    id: 'status',
                    name: 'Status',
                    formatter: function (cell) {
                        var map = { active: 'green', inactive: 'yellow', banned: 'red' };
                        var labelMap = { active: 'Aktiv', inactive: 'Inaktiv', banned: 'Gesperrt' };
                        var cls = map[cell] || 'secondary';
                        var label = labelMap[cell] || cell || '';
                        return gridjs.html('<span class="badge bg-' + cls + '-lt">' + window.cmsEsc(label) + '</span>');
                    },
                },
                {
                    id: 'created_at',
                    name: 'Registriert',
                    formatter: function (cell) {
                        return cell ? window.cmsEsc(String(cell).substring(0, 10).split('-').reverse().join('.')) : '–';
                    },
                },
                {
                    id: 'id',
                    name: '',
                    sort: false,
                    formatter: function (cell) {
                        return gridjs.html('<a href="' + adminBasePath + '?action=edit&id=' + encodeURIComponent(cell) + '" class="btn btn-ghost-primary btn-icon btn-sm" title="Bearbeiten">✎</a>');
                    },
                },
            ],
        });
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
        initUsersGrid();
        initUsersFilters();
        initRolesUi();
    });
})();