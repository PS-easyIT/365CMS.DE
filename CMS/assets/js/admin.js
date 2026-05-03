/**
 * Admin JavaScript
 * 
 * @package CMSv2\Assets
 */

// ── Modal Utilities (Tabler Bootstrap) ───────────────────────────────────────
function openModal(id) {
    var el = document.getElementById(id);
    if (el && typeof bootstrap !== 'undefined') {
        bootstrap.Modal.getOrCreateInstance(el).show();
    }
}
function closeModal(id) {
    var el = document.getElementById(id);
    if (el && typeof bootstrap !== 'undefined') {
        var inst = bootstrap.Modal.getInstance(el);
        if (inst) inst.hide();
    }
}

function initPostCategoryDeleteFlow() {
    var modalElement = document.getElementById('deleteCategoryModal');
    var deleteCategoryForms = document.querySelectorAll('.js-delete-category-form');
    var categoryIdInput = document.getElementById('deleteCategoryId');
    var categoryNameEl = document.getElementById('deleteCategoryName');
    var reassignWrap = document.getElementById('deleteCategoryReassignWrap');
    var replacementSelect = document.getElementById('replacementCategoryId');
    var hintEl = document.getElementById('deleteCategoryHint');
    var questionEl = document.getElementById('deleteCategoryQuestion');
    var submitButton = document.getElementById('deleteCategorySubmit');

    if (!deleteCategoryForms.length || !modalElement || !categoryIdInput || !categoryNameEl || !reassignWrap || !replacementSelect || !hintEl || !questionEl || !submitButton) {
        return;
    }

    var modal = typeof bootstrap !== 'undefined' && bootstrap.Modal
        ? bootstrap.Modal.getOrCreateInstance(modalElement)
        : null;

    function configureReplacementOptions(categoryId, preferredReplacementId) {
        var availableOptions = 0;
        var firstAvailableValue = '0';
        var preferredValue = String(preferredReplacementId || '0');
        var preferredAvailable = false;
        Array.prototype.forEach.call(replacementSelect.options, function(option) {
            var isPlaceholder = option.value === '0';
            option.disabled = !isPlaceholder && option.value === categoryId;
            option.selected = isPlaceholder;
            if (!option.disabled && !isPlaceholder) {
                availableOptions += 1;
                if (firstAvailableValue === '0') {
                    firstAvailableValue = option.value;
                }
                if (option.value === preferredValue) {
                    preferredAvailable = true;
                }
            }
        });
        replacementSelect.value = preferredAvailable ? preferredValue : firstAvailableValue;
        return {
            availableOptions: availableOptions,
            firstAvailableValue: firstAvailableValue,
        };
    }

    deleteCategoryForms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            var categoryId = String(form.getAttribute('data-category-id') || '0');
            var categoryName = String(form.getAttribute('data-category-name') || '');
            var assignedPosts = parseInt(form.getAttribute('data-assigned-posts') || '0', 10);
            var preferredReplacementId = parseInt(form.getAttribute('data-default-replacement-category-id') || '0', 10);

            if (!modal) {
                if (assignedPosts > 0 && preferredReplacementId <= 0) {
                    event.preventDefault();
                    window.alert('Für diese Kategorie muss zuerst eine Ersatzkategorie gewählt werden. Bitte Seite neu laden und erneut versuchen.');
                    return;
                }

                var fallbackMessage = 'Kategorie „' + categoryName + '“ wirklich löschen?';
                if (assignedPosts > 0 && preferredReplacementId > 0) {
                    fallbackMessage += ' ' + assignedPosts + ' Beitrag/Beiträge werden automatisch in die hinterlegte Ersatzkategorie verschoben.';
                } else {
                    fallbackMessage += ' Unterkategorien werden zu Hauptkategorien.';
                }

                if (!window.confirm(fallbackMessage)) {
                    event.preventDefault();
                }
                return;
            }

            event.preventDefault();

            categoryIdInput.value = categoryId;
            categoryNameEl.textContent = categoryName;
            var replacementState = configureReplacementOptions(categoryId, String(preferredReplacementId || '0'));
            submitButton.disabled = false;
            submitButton.textContent = 'Kategorie löschen';
            questionEl.classList.add('d-none');

            if (assignedPosts > 0) {
                reassignWrap.classList.remove('d-none');
                replacementSelect.required = true;
                questionEl.classList.remove('d-none');
                if (replacementState.availableOptions <= 0) {
                    replacementSelect.required = false;
                    replacementSelect.disabled = true;
                    submitButton.disabled = true;
                    hintEl.textContent = 'Es gibt aktuell keine andere Kategorie als Ersatz. Bitte lege zuerst eine weitere Kategorie an.';
                } else {
                    replacementSelect.disabled = false;
                    hintEl.textContent = assignedPosts + ' Beitrag/Beiträge sind dieser Kategorie zugeordnet und werden in die ausgewählte Ersatzkategorie verschoben.';
                    submitButton.textContent = 'Verschieben & löschen';
                }
            } else {
                reassignWrap.classList.add('d-none');
                replacementSelect.required = false;
                replacementSelect.disabled = false;
                hintEl.textContent = 'Es sind keine Beiträge direkt oder zusätzlich dieser Kategorie zugeordnet. Unterkategorien werden zu Hauptkategorien.';
            }

            modal.show();
        });
    });

    modalElement.addEventListener('hidden.bs.modal', function() {
        categoryIdInput.value = '0';
        categoryNameEl.textContent = '';
        reassignWrap.classList.add('d-none');
        replacementSelect.required = false;
        replacementSelect.disabled = false;
        submitButton.disabled = false;
        submitButton.textContent = 'Löschen bestätigen';
        questionEl.classList.add('d-none');
        configureReplacementOptions('0');
        hintEl.textContent = 'Unterkategorien werden dabei zu Hauptkategorien.';
    });
}

function initPostTagDeleteFlow() {
    var modalElement = document.getElementById('deleteTagModal');
    var deleteTagForms = document.querySelectorAll('.js-delete-tag-form');
    var tagIdInput = document.getElementById('deleteTagId');
    var tagNameEl = document.getElementById('deleteTagName');
    var reassignWrap = document.getElementById('deleteTagReassignWrap');
    var replacementSelect = document.getElementById('replacementTagId');
    var hintEl = document.getElementById('deleteTagHint');
    var questionEl = document.getElementById('deleteTagQuestion');
    var submitButton = document.getElementById('deleteTagSubmit');

    if (!deleteTagForms.length || !modalElement || !tagIdInput || !tagNameEl || !reassignWrap || !replacementSelect || !hintEl || !questionEl || !submitButton) {
        return;
    }

    var modal = typeof bootstrap !== 'undefined' && bootstrap.Modal
        ? bootstrap.Modal.getOrCreateInstance(modalElement)
        : null;

    function configureReplacementOptions(tagId) {
        var availableOptions = 0;
        var firstAvailableValue = '0';
        Array.prototype.forEach.call(replacementSelect.options, function(option) {
            var isPlaceholder = option.value === '0';
            option.disabled = !isPlaceholder && option.value === tagId;
            option.selected = isPlaceholder;
            if (!option.disabled && !isPlaceholder) {
                availableOptions += 1;
                if (firstAvailableValue === '0') {
                    firstAvailableValue = option.value;
                }
            }
        });
        replacementSelect.value = firstAvailableValue;
        return {
            availableOptions: availableOptions,
            firstAvailableValue: firstAvailableValue,
        };
    }

    deleteTagForms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            var tagId = String(form.getAttribute('data-tag-id') || '0');
            var tagName = String(form.getAttribute('data-tag-name') || '');
            var assignedPosts = parseInt(form.getAttribute('data-assigned-posts') || '0', 10);

            if (!modal) {
                if (assignedPosts > 0) {
                    event.preventDefault();
                    window.alert('Für diesen Tag muss zuerst ein Ersatztag gewählt werden. Bitte Seite neu laden und erneut versuchen.');
                    return;
                }

                if (!window.confirm('Tag wirklich löschen? Zugeordnete Beziehungen werden entfernt.')) {
                    event.preventDefault();
                }
                return;
            }

            event.preventDefault();

            tagIdInput.value = tagId;
            tagNameEl.textContent = tagName;
            var replacementState = configureReplacementOptions(tagId);
            submitButton.disabled = false;
            submitButton.textContent = 'Tag löschen';
            questionEl.classList.add('d-none');

            if (assignedPosts > 0) {
                reassignWrap.classList.remove('d-none');
                replacementSelect.required = true;
                questionEl.classList.remove('d-none');
                if (replacementState.availableOptions <= 0) {
                    replacementSelect.required = false;
                    replacementSelect.disabled = true;
                    submitButton.disabled = true;
                    hintEl.textContent = 'Es gibt aktuell keinen anderen Tag als Ersatz. Bitte lege zuerst einen weiteren Tag an.';
                } else {
                    replacementSelect.disabled = false;
                    hintEl.textContent = assignedPosts + ' Beitrag/Beiträge nutzen diesen Tag und werden auf den ausgewählten Ersatztag umgestellt.';
                    submitButton.textContent = 'Verschieben & löschen';
                }
            } else {
                reassignWrap.classList.add('d-none');
                replacementSelect.required = false;
                replacementSelect.disabled = false;
                hintEl.textContent = 'Es sind keine Beiträge mit diesem Tag verknüpft. Zugeordnete Beziehungen werden entfernt.';
            }

            modal.show();
        });
    });

    modalElement.addEventListener('hidden.bs.modal', function() {
        tagIdInput.value = '0';
        tagNameEl.textContent = '';
        reassignWrap.classList.add('d-none');
        replacementSelect.required = false;
        replacementSelect.disabled = false;
        submitButton.disabled = false;
        submitButton.textContent = 'Löschen bestätigen';
        questionEl.classList.add('d-none');
        configureReplacementOptions('0');
        hintEl.textContent = 'Zugeordnete Beziehungen werden dabei entfernt.';
    });
}

function initReplacementCategoryBulkDeleteFlow() {
    var bulkDeleteForms = document.querySelectorAll('.js-delete-replacement-categories-form');

    if (!bulkDeleteForms.length) {
        return;
    }

    bulkDeleteForms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            var deleteCount = parseInt(form.getAttribute('data-delete-count') || '0', 10);
            var previewRaw = String(form.getAttribute('data-delete-preview') || '');
            var previewItems = previewRaw === ''
                ? []
                : previewRaw.split('|').map(function(item) {
                    return item.trim();
                }).filter(function(item) {
                    return item !== '';
                });

            var message = 'Sollen wirklich ' + deleteCount + ' Kategorien mit hinterlegter Ersatzkategorie gelöscht werden?';

            if (previewItems.length > 0) {
                message += '\n\nBetroffene Kategorien:\n- ' + previewItems.join('\n- ');
            }

            if (deleteCount > previewItems.length) {
                message += '\n- … und ' + (deleteCount - previewItems.length) + ' weitere';
            }

            message += '\n\nDie zugeordneten Beiträge werden automatisch in die jeweilige Ersatzkategorie verschoben.';

            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
}

function initConfirmForms() {
    var confirmForms = document.querySelectorAll('form[data-confirm-message]');

    if (!confirmForms.length) {
        return;
    }

    confirmForms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (form.dataset.confirmAccepted === '1') {
                form.dataset.confirmAccepted = '0';
                return;
            }

            event.preventDefault();

            var message = form.dataset.confirmMessage || '';
            var title = form.dataset.confirmTitle || 'Bitte bestätigen';
            var confirmText = form.dataset.confirmText || 'Bestätigen';
            var confirmClass = form.dataset.confirmClass || 'btn-danger';
            var statusClass = form.dataset.confirmStatusClass || 'bg-danger';

            if (typeof cmsConfirm === 'function') {
                cmsConfirm({
                    title: title,
                    message: message,
                    confirmText: confirmText,
                    confirmClass: confirmClass,
                    statusClass: statusClass,
                    onConfirm: function() {
                        form.dataset.confirmAccepted = '1';
                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit();
                            return;
                        }
                        form.submit();
                    }
                });
                return;
            }

            if (window.confirm(message)) {
                form.dataset.confirmAccepted = '1';
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                    return;
                }
                form.submit();
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    
    // Confirmation for destructive actions
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    
    // Active nav highlighting
    const navItems = document.querySelectorAll('.nav-item');
    const currentPath = window.location.pathname;
    
    navItems.forEach(item => {
        if (item.getAttribute('href') === currentPath) {
            item.classList.add('active');
        }
    });

    initPostCategoryDeleteFlow();
    initPostTagDeleteFlow();
    initReplacementCategoryBulkDeleteFlow();
    initConfirmForms();
    
});
