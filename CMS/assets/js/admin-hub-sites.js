(function () {
    'use strict';

    function submitHiddenForm(inputElement, formElement, value) {
        if (!inputElement || !formElement) {
            return;
        }

        inputElement.value = value;
        formElement.submit();
    }

    function showConfirm(options, fallbackMessage, onConfirm) {
        if (typeof cmsConfirm === 'function') {
            cmsConfirm(Object.assign({}, options, { onConfirm: onConfirm }));
            return;
        }

        if (window.confirm(fallbackMessage)) {
            onConfirm();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var deleteIdInput = document.getElementById('deleteId');
        var deleteForm = document.getElementById('deleteForm');
        var duplicateIdInput = document.getElementById('duplicateId');
        var duplicateForm = document.getElementById('duplicateForm');
        var duplicateTemplateKeyInput = document.getElementById('duplicateTemplateKey');
        var duplicateTemplateForm = document.getElementById('duplicateTemplateForm');
        var deleteTemplateKeyInput = document.getElementById('deleteTemplateKey');
        var deleteTemplateForm = document.getElementById('deleteTemplateForm');

        document.querySelectorAll('.js-hub-sites-search-input').forEach(function (input) {
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

        document.querySelectorAll('.js-copy-hub-url').forEach(function (button) {
            button.addEventListener('click', function () {
                var publicUrl = button.getAttribute('data-hub-public-url') || '';
                if (!navigator.clipboard || typeof navigator.clipboard.writeText !== 'function') {
                    if (typeof cmsAlert === 'function') {
                        cmsAlert('Kopieren wird von diesem Browser leider nicht unterstützt.', 'warning');
                    }
                    return;
                }

                navigator.clipboard.writeText(publicUrl).then(function () {
                    if (typeof cmsAlert === 'function') {
                        cmsAlert('Public URL wurde in die Zwischenablage kopiert.', 'success');
                    }
                }).catch(function () {
                    if (typeof cmsAlert === 'function') {
                        cmsAlert('Public URL konnte nicht kopiert werden.', 'danger');
                    }
                });
            });
        });

        document.querySelectorAll('.js-duplicate-hub-site').forEach(function (button) {
            button.addEventListener('click', function () {
                submitHiddenForm(duplicateIdInput, duplicateForm, button.getAttribute('data-hub-site-id') || '0');
            });
        });

        document.querySelectorAll('.js-delete-hub-site').forEach(function (button) {
            button.addEventListener('click', function () {
                var siteId = button.getAttribute('data-hub-site-id') || '0';
                var siteName = button.getAttribute('data-hub-site-name') || '';
                showConfirm(
                    {
                        title: 'Routing / Hub Site löschen',
                        message: 'Routing / Hub Site <strong>' + siteName + '</strong> wirklich löschen?',
                        confirmText: 'Löschen',
                        confirmClass: 'btn-danger',
                    },
                    'Routing / Hub Site "' + siteName + '" wirklich löschen?',
                    function () {
                        submitHiddenForm(deleteIdInput, deleteForm, siteId);
                    }
                );
            });
        });

        document.querySelectorAll('.js-duplicate-hub-template').forEach(function (button) {
            button.addEventListener('click', function () {
                submitHiddenForm(duplicateTemplateKeyInput, duplicateTemplateForm, button.getAttribute('data-hub-template-key') || '');
            });
        });

        document.querySelectorAll('.js-delete-hub-template').forEach(function (button) {
            button.addEventListener('click', function () {
                var templateKey = button.getAttribute('data-hub-template-key') || '';
                var templateName = button.getAttribute('data-hub-template-name') || '';
                showConfirm(
                    {
                        title: 'Template löschen',
                        message: 'Template <strong>' + templateName + '</strong> wirklich löschen?',
                        confirmText: 'Löschen',
                        confirmClass: 'btn-danger',
                    },
                    'Template "' + templateName + '" wirklich löschen?',
                    function () {
                        submitHiddenForm(deleteTemplateKeyInput, deleteTemplateForm, templateKey);
                    }
                );
            });
        });
    });
})();