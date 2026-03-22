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
    var submitButton = document.getElementById('deleteCategorySubmit');

    if (!deleteCategoryForms.length || !modalElement || !categoryIdInput || !categoryNameEl || !reassignWrap || !replacementSelect || !hintEl || !submitButton) {
        return;
    }

    var modal = typeof bootstrap !== 'undefined' && bootstrap.Modal
        ? bootstrap.Modal.getOrCreateInstance(modalElement)
        : null;

    function configureReplacementOptions(categoryId) {
        var availableOptions = 0;
        Array.prototype.forEach.call(replacementSelect.options, function(option) {
            var isPlaceholder = option.value === '0';
            option.disabled = !isPlaceholder && option.value === categoryId;
            option.selected = isPlaceholder;
            if (!option.disabled && !isPlaceholder) {
                availableOptions += 1;
            }
        });
        replacementSelect.value = '0';
        return availableOptions;
    }

    deleteCategoryForms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            var categoryId = String(form.getAttribute('data-category-id') || '0');
            var categoryName = String(form.getAttribute('data-category-name') || '');
            var assignedPosts = parseInt(form.getAttribute('data-assigned-posts') || '0', 10);

            if (!modal) {
                if (assignedPosts > 0) {
                    event.preventDefault();
                    window.alert('Für diese Kategorie muss zuerst eine Ersatzkategorie gewählt werden. Bitte Seite neu laden und erneut versuchen.');
                    return;
                }

                if (!window.confirm('Kategorie wirklich löschen? Unterkategorien werden zu Hauptkategorien.')) {
                    event.preventDefault();
                }
                return;
            }

            event.preventDefault();

            categoryIdInput.value = categoryId;
            categoryNameEl.textContent = categoryName;
            var availableOptions = configureReplacementOptions(categoryId);
            submitButton.disabled = false;

            if (assignedPosts > 0) {
                reassignWrap.classList.remove('d-none');
                replacementSelect.required = true;
                if (availableOptions <= 0) {
                    replacementSelect.required = false;
                    replacementSelect.disabled = true;
                    submitButton.disabled = true;
                    hintEl.textContent = 'Es gibt aktuell keine andere Kategorie als Ersatz. Bitte lege zuerst eine weitere Kategorie an.';
                } else {
                    replacementSelect.disabled = false;
                    hintEl.textContent = assignedPosts + ' Beitrag/Beiträge sind dieser Kategorie zugeordnet. Bitte wähle eine Ersatzkategorie.';
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
    var submitButton = document.getElementById('deleteTagSubmit');

    if (!deleteTagForms.length || !modalElement || !tagIdInput || !tagNameEl || !reassignWrap || !replacementSelect || !hintEl || !submitButton) {
        return;
    }

    var modal = typeof bootstrap !== 'undefined' && bootstrap.Modal
        ? bootstrap.Modal.getOrCreateInstance(modalElement)
        : null;

    function configureReplacementOptions(tagId) {
        var availableOptions = 0;
        Array.prototype.forEach.call(replacementSelect.options, function(option) {
            var isPlaceholder = option.value === '0';
            option.disabled = !isPlaceholder && option.value === tagId;
            option.selected = isPlaceholder;
            if (!option.disabled && !isPlaceholder) {
                availableOptions += 1;
            }
        });
        replacementSelect.value = '0';
        return availableOptions;
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
            var availableOptions = configureReplacementOptions(tagId);
            submitButton.disabled = false;

            if (assignedPosts > 0) {
                reassignWrap.classList.remove('d-none');
                replacementSelect.required = true;
                if (availableOptions <= 0) {
                    replacementSelect.required = false;
                    replacementSelect.disabled = true;
                    submitButton.disabled = true;
                    hintEl.textContent = 'Es gibt aktuell keinen anderen Tag als Ersatz. Bitte lege zuerst einen weiteren Tag an.';
                } else {
                    replacementSelect.disabled = false;
                    hintEl.textContent = assignedPosts + ' Beitrag/Beiträge nutzen diesen Tag. Bitte wähle einen Ersatztag.';
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
        configureReplacementOptions('0');
        hintEl.textContent = 'Zugeordnete Beziehungen werden dabei entfernt.';
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
    
});
