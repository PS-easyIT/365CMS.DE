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

    if (!deleteCategoryForms.length || !modalElement || !categoryIdInput || !categoryNameEl || !reassignWrap || !replacementSelect || !hintEl) {
        return;
    }

    var modal = typeof bootstrap !== 'undefined' && bootstrap.Modal
        ? bootstrap.Modal.getOrCreateInstance(modalElement)
        : null;

    function configureReplacementOptions(categoryId) {
        Array.prototype.forEach.call(replacementSelect.options, function(option) {
            var isPlaceholder = option.value === '0';
            option.disabled = !isPlaceholder && option.value === categoryId;
            option.selected = isPlaceholder;
        });
        replacementSelect.value = '0';
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
            configureReplacementOptions(categoryId);

            if (assignedPosts > 0) {
                reassignWrap.classList.remove('d-none');
                replacementSelect.required = true;
                hintEl.textContent = assignedPosts + ' Beitrag/Beiträge sind dieser Kategorie zugeordnet. Bitte wähle eine Ersatzkategorie.';
            } else {
                reassignWrap.classList.add('d-none');
                replacementSelect.required = false;
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
        configureReplacementOptions('0');
        hintEl.textContent = 'Unterkategorien werden dabei zu Hauptkategorien.';
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
    
});
