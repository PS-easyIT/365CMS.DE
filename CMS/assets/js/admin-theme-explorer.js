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
            console.error('admin-theme-explorer: Konfiguration konnte nicht gelesen werden.', error);
            return null;
        }
    }

    function bindEditor(config) {
        var editor = document.querySelector(config.editorSelector || '#codeEditor');
        var form = document.querySelector(config.formSelector || '#themeExplorerForm');
        var saveButton = document.querySelector(config.saveButtonSelector || '#themeExplorerSaveButton');
        var unsavedMessage = String((config && config.unsavedChangesMessage) || form && form.getAttribute('data-unsaved-message') || 'Es gibt ungespeicherte Änderungen.');
        var initialValue = editor ? String(editor.value || '') : '';
        var isDirty = false;

        if (!editor || !form) {
            return;
        }

        function syncDirtyState() {
            isDirty = String(editor.value || '') !== initialValue;
            form.dataset.dirty = isDirty ? '1' : '0';
        }

        function markSubmitting() {
            var pendingText = String((config && config.savePendingText) || saveButton && saveButton.getAttribute('data-pending-text') || 'Speichert …');

            if (saveButton) {
                saveButton.disabled = true;
                saveButton.dataset.originalText = saveButton.dataset.originalText || saveButton.textContent;
                saveButton.textContent = pendingText;
            }
        }

        editor.addEventListener('keydown', function (event) {
            if (event.key === 'Tab') {
                event.preventDefault();

                var start = editor.selectionStart;
                var end = editor.selectionEnd;
                editor.value = editor.value.substring(0, start) + '    ' + editor.value.substring(end);
                editor.selectionStart = editor.selectionEnd = start + 4;
                return;
            }

            if (event.ctrlKey && event.key.toLowerCase() === 's') {
                event.preventDefault();
                if (editor.hasAttribute('readonly')) {
                    return;
                }

                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                    return;
                }

                markSubmitting();
                form.submit();
            }
        });

        editor.addEventListener('input', syncDirtyState);
        syncDirtyState();

        form.addEventListener('submit', function () {
            markSubmitting();
            isDirty = false;
        });

        window.addEventListener('beforeunload', function (event) {
            if (!isDirty) {
                return;
            }

            event.preventDefault();
            event.returnValue = unsavedMessage;
        });
    }

    function bindSearch(config) {
        var input = document.querySelector(config.searchInputSelector || '#themeExplorerSearch');
        var links = Array.prototype.slice.call(document.querySelectorAll(config.treeLinkSelector || '[data-theme-explorer-path]'));
        var folders = Array.prototype.slice.call(document.querySelectorAll(config.treeFolderSelector || '[data-theme-explorer-folder]'));

        if (!input || !links.length) {
            return;
        }

        function syncFolderVisibility() {
            folders.slice().reverse().forEach(function (folder) {
                var listItem = folder.closest('li');
                if (!listItem) {
                    return;
                }

                var hasVisibleFile = Array.prototype.slice.call(listItem.querySelectorAll(config.treeLinkSelector || '[data-theme-explorer-path]')).some(function (link) {
                    var linkItem = link.closest('li');
                    return !!linkItem && linkItem.style.display !== 'none';
                });

                listItem.style.display = hasVisibleFile ? '' : 'none';
            });
        }

        input.addEventListener('input', function () {
            var query = String(input.value || '').trim().toLowerCase();

            links.forEach(function (link) {
                var listItem = link.closest('li');
                if (!listItem) {
                    return;
                }

                var text = String(link.textContent || '').trim().toLowerCase();
                listItem.style.display = query === '' || text.indexOf(query) !== -1 ? '' : 'none';
            });

            syncFolderVisibility();
        });

        syncFolderVisibility();
    }

    document.addEventListener('DOMContentLoaded', function () {
        var config = parseConfig('theme-explorer-config') || {};
        bindEditor(config);
        bindSearch(config);
    });
})();