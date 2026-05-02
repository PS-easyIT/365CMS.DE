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
            console.error('admin-site-tables: Konfiguration konnte nicht gelesen werden.', error);
            return null;
        }
    }

    function submitHiddenForm(inputElement, formElement, value) {
        if (!inputElement || !formElement) {
            return;
        }

        inputElement.value = value;

        if (typeof formElement.requestSubmit === 'function') {
            formElement.requestSubmit();
            return;
        }

        if (typeof formElement.reportValidity === 'function' && !formElement.reportValidity()) {
            return;
        }

        if (typeof HTMLFormElement !== 'undefined'
            && HTMLFormElement.prototype
            && typeof HTMLFormElement.prototype.submit === 'function') {
            HTMLFormElement.prototype.submit.call(formElement);
            return;
        }

        formElement.submit();
    }

    function clearElement(element) {
        if (!element) {
            return;
        }

        while (element.firstChild) {
            element.removeChild(element.firstChild);
        }
    }

    function initListInteractions() {
        var deleteIdInput = document.getElementById('deleteId');
        var deleteForm = document.getElementById('deleteForm');
        var duplicateIdInput = document.getElementById('duplicateId');
        var duplicateForm = document.getElementById('duplicateForm');

        document.querySelectorAll('.js-site-tables-search-input').forEach(function (input) {
            input.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter') {
                    return;
                }

                event.preventDefault();
                var query = input.value.trim();
                var baseUrl = input.getAttribute('data-search-url') || '';
                window.location.href = baseUrl + (query !== '' ? '?q=' + encodeURIComponent(query) : '');
            });
        });

        document.querySelectorAll('.js-site-table-duplicate').forEach(function (button) {
            button.addEventListener('click', function () {
                submitHiddenForm(duplicateIdInput, duplicateForm, button.getAttribute('data-table-id') || '0');
            });
        });

        document.querySelectorAll('.js-site-table-delete').forEach(function (button) {
            button.addEventListener('click', function () {
                var tableId = button.getAttribute('data-table-id') || '0';
                var tableName = button.getAttribute('data-table-name') || '';
                var submitDelete = function () {
                    submitHiddenForm(deleteIdInput, deleteForm, tableId);
                };

                if (typeof cmsConfirm === 'function') {
                    cmsConfirm({
                        title: 'Tabelle löschen',
                        message: 'Tabelle ' + tableName + ' wirklich löschen?',
                        confirmText: 'Löschen',
                        confirmClass: 'btn-danger',
                        onConfirm: submitDelete,
                    });
                    return;
                }

                if (window.confirm('Tabelle ' + tableName + ' wirklich löschen?')) {
                    submitDelete();
                }
            });
        });
    }

    function createIconButton(className, title, label) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = className;
        button.title = title;
        button.textContent = label;
        return button;
    }

    function initEditor() {
        var config = parseConfig('site-tables-editor-config');
        if (!config) {
            return;
        }

        var columns = Array.isArray(config.columns) ? config.columns.slice() : [];
        var rows = Array.isArray(config.rows) ? config.rows.slice() : [];
        var limits = config.limits || {};
        var maxColumns = Number(limits.maxColumns || 25);
        var maxRows = Number(limits.maxRows || 250);
        var maxColumnLabelLength = Number(limits.maxColumnLabelLength || 80);
        var maxCellLength = Number(limits.maxCellLength || 5000);
        var columnsBody = document.getElementById('columnsBody');
        var rowsHeadContainer = document.querySelector('#rowsHead tr');
        var rowsBody = document.getElementById('rowsBody');
        var noRowsHint = document.getElementById('noRowsHint');
        var addColumnButton = document.getElementById('addColumn');
        var addRowButton = document.getElementById('addRow');
        var tableForm = document.getElementById('tableForm');
        var columnsJsonInput = document.getElementById('columnsJsonInput');
        var rowsJsonInput = document.getElementById('rowsJsonInput');
        var contentSourceEnabledInput = document.getElementById('contentSourceEnabled');
        var contentSourceOptions = document.getElementById('contentSourceOptions');
        var manualEditorBlocks = document.querySelectorAll('[data-manual-table-editor]');

        if (!columnsBody || !rowsHeadContainer || !rowsBody || !tableForm || !columnsJsonInput || !rowsJsonInput) {
            return;
        }

        function syncHiddenInputs() {
            columnsJsonInput.value = JSON.stringify(columns);
            rowsJsonInput.value = JSON.stringify(rows);
        }

        function sanitizeText(value, maxLength) {
            return Array.from(String(value || '')
                .replace(/[\x00-\x1F\x7F]/g, '')
                .trim())
                .slice(0, maxLength)
                .join('');
        }

        function showValidationMessage(message) {
            if (typeof cmsAlert === 'function') {
                cmsAlert('danger', message);
                return;
            }

            window.alert(message);
        }

        function isContentSourceEnabled() {
            return Boolean(contentSourceEnabledInput && contentSourceEnabledInput.checked);
        }

        function updateContentSourceMode() {
            var enabled = isContentSourceEnabled();

            if (contentSourceOptions) {
                contentSourceOptions.classList.toggle('d-none', !enabled);
            }

            manualEditorBlocks.forEach(function (block) {
                block.classList.toggle('d-none', enabled);
                block.querySelectorAll('input, button, textarea, select').forEach(function (control) {
                    control.disabled = enabled;
                });
            });

            if (enabled) {
                columnsJsonInput.value = '[]';
                rowsJsonInput.value = '[]';
            } else {
                syncHiddenInputs();
            }
        }

        function renderRowsHead() {
            clearElement(rowsHeadContainer);
            columns.forEach(function (column) {
                var th = document.createElement('th');
                th.textContent = column.label || '—';
                rowsHeadContainer.appendChild(th);
            });
            var actionTh = document.createElement('th');
            actionTh.className = 'w-1';
            rowsHeadContainer.appendChild(actionTh);
        }

        function renderRows() {
            clearElement(rowsBody);
            if (columns.length === 0 || rows.length === 0) {
                if (noRowsHint) {
                    noRowsHint.classList.toggle('d-none', rows.length > 0);
                }
                syncHiddenInputs();
                return;
            }

            if (noRowsHint) {
                noRowsHint.classList.add('d-none');
            }

            rows.forEach(function (row, rowIndex) {
                var tr = document.createElement('tr');
                columns.forEach(function (column) {
                    var td = document.createElement('td');
                    var input = document.createElement('input');
                    input.type = 'text';
                    input.className = 'form-control form-control-sm';
                    input.value = row[column.label] || '';
                    input.maxLength = maxCellLength;
                    input.addEventListener('change', function () {
                        rows[rowIndex][column.label] = sanitizeText(input.value, maxCellLength);
                        input.value = rows[rowIndex][column.label];
                        syncHiddenInputs();
                    });
                    td.appendChild(input);
                    tr.appendChild(td);
                });

                var actionTd = document.createElement('td');
                var removeButton = createIconButton('btn btn-ghost-danger btn-icon btn-sm', 'Zeile entfernen', '×');
                removeButton.addEventListener('click', function () {
                    rows.splice(rowIndex, 1);
                    renderRows();
                });
                actionTd.appendChild(removeButton);
                tr.appendChild(actionTd);
                rowsBody.appendChild(tr);
            });

            syncHiddenInputs();
        }

        function renderColumns() {
            clearElement(columnsBody);
            columns.forEach(function (column, columnIndex) {
                var tr = document.createElement('tr');
                var labelTd = document.createElement('td');
                var labelInput = document.createElement('input');
                labelInput.type = 'text';
                labelInput.className = 'form-control form-control-sm';
                labelInput.value = column.label || '';
                labelInput.maxLength = maxColumnLabelLength;
                labelInput.addEventListener('change', function () {
                    var oldLabel = columns[columnIndex].label;
                    var newLabel = sanitizeText(labelInput.value, maxColumnLabelLength) || ('Spalte ' + (columnIndex + 1));
                    columns[columnIndex].label = newLabel;
                    labelInput.value = newLabel;
                    rows.forEach(function (row) {
                        if (oldLabel !== newLabel && Object.prototype.hasOwnProperty.call(row, oldLabel)) {
                            row[newLabel] = row[oldLabel];
                            delete row[oldLabel];
                        }
                    });
                    renderColumns();
                });
                labelTd.appendChild(labelInput);
                tr.appendChild(labelTd);

                var actionTd = document.createElement('td');
                var removeButton = createIconButton('btn btn-ghost-danger btn-icon btn-sm', 'Spalte entfernen', '×');
                removeButton.addEventListener('click', function () {
                    var label = columns[columnIndex].label;
                    columns.splice(columnIndex, 1);
                    rows.forEach(function (row) {
                        delete row[label];
                    });
                    renderColumns();
                });
                actionTd.appendChild(removeButton);
                tr.appendChild(actionTd);
                columnsBody.appendChild(tr);
            });

            renderRowsHead();
            renderRows();
        }

        if (addColumnButton) {
            addColumnButton.addEventListener('click', function () {
                if (isContentSourceEnabled()) {
                    showValidationMessage('Bei aktivierter Site-Table-Quelle sind nur die auswählbaren Seiten-/Beitrags-Spalten erlaubt.');
                    return;
                }

                if (columns.length >= maxColumns) {
                    showValidationMessage('Es sind maximal ' + maxColumns + ' Spalten erlaubt.');
                    return;
                }

                columns.push({ label: 'Spalte ' + (columns.length + 1), type: 'text' });
                renderColumns();
            });
        }

        if (addRowButton) {
            addRowButton.addEventListener('click', function () {
                if (isContentSourceEnabled()) {
                    showValidationMessage('Bei aktivierter Site-Table-Quelle werden Zeilen automatisch aus Seiten/Beiträgen erzeugt.');
                    return;
                }

                if (columns.length === 0) {
                    showValidationMessage('Bitte zuerst mindestens eine Spalte anlegen.');
                    return;
                }

                if (rows.length >= maxRows) {
                    showValidationMessage('Es sind maximal ' + maxRows + ' Zeilen erlaubt.');
                    return;
                }

                var row = {};
                columns.forEach(function (column) {
                    row[column.label] = '';
                });
                rows.push(row);
                renderRows();
            });
        }

        tableForm.addEventListener('submit', function (event) {
            if (isContentSourceEnabled()) {
                columnsJsonInput.value = '[]';
                rowsJsonInput.value = '[]';
                return;
            }

            if (columns.length > maxColumns) {
                event.preventDefault();
                showValidationMessage('Es sind maximal ' + maxColumns + ' Spalten erlaubt.');
                return;
            }

            if (rows.length > maxRows) {
                event.preventDefault();
                showValidationMessage('Es sind maximal ' + maxRows + ' Zeilen erlaubt.');
                return;
            }

            syncHiddenInputs();
        });

        renderColumns();
        if (contentSourceEnabledInput) {
            contentSourceEnabledInput.addEventListener('change', updateContentSourceMode);
        }
        updateContentSourceMode();
    }

    document.addEventListener('DOMContentLoaded', function () {
        initListInteractions();
        initEditor();
    });
})();