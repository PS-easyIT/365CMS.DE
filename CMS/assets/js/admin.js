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

function cmsSubmitFormSafely(form, submitter) {
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    if (submitter && typeof form.requestSubmit === 'function') {
        try {
            form.requestSubmit(submitter);
            return;
        } catch (error) {
            // Fällt auf einen generischen nativen Submitter zurück.
        }
    }

    if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
        return;
    }

    var fallbackSubmitter = document.createElement('button');
    fallbackSubmitter.type = 'submit';
    fallbackSubmitter.hidden = true;

    if (submitter && submitter.name) {
        fallbackSubmitter.name = submitter.name;
        fallbackSubmitter.value = submitter.value;
    }

    form.appendChild(fallbackSubmitter);
    fallbackSubmitter.click();
    fallbackSubmitter.remove();
}

window.cmsSubmitFormSafely = cmsSubmitFormSafely;

function formatSeconds(value) {
    var numericValue = Number(value);
    if (!Number.isFinite(numericValue)) {
        numericValue = 0;
    }

    var seconds = Math.max(0, Math.floor(numericValue));
    var unit = 'Sekunden';
    var displayValue = seconds;

    if (seconds >= 86400) {
        unit = 'Tage';
        displayValue = seconds / 86400;
    } else if (seconds >= 3600) {
        unit = 'Stunden';
        displayValue = seconds / 3600;
    } else if (seconds >= 60) {
        unit = 'Minuten';
        displayValue = seconds / 60;
    }

    var formattedValue = Number.isInteger(displayValue)
        ? String(displayValue)
        : displayValue.toLocaleString('de-DE', { minimumFractionDigits: 1, maximumFractionDigits: 1 });

    return formattedValue + ' ' + unit;
}

window.formatSeconds = formatSeconds;

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

    function submitConfirmedForm(form, submitter) {
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (submitter && typeof form.requestSubmit === 'function') {
            try {
                form.requestSubmit(submitter);
                return;
            } catch (error) {
                // Fällt auf generischen Submit zurück.
            }
        }

        cmsSubmitFormSafely(form, submitter);
    }

    confirmForms.forEach(function(form) {
        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function(submitButton) {
            submitButton.addEventListener('click', function() {
                form.__cmsConfirmSubmitter = submitButton;
            });
        });

        form.addEventListener('submit', function(event) {
            var submitter = event.submitter || form.__cmsConfirmSubmitter || null;

            if (form.dataset.confirmAccepted === '1') {
                form.dataset.confirmAccepted = '0';
                form.__cmsConfirmSubmitter = submitter;
                return;
            }

            event.preventDefault();

            var message = form.dataset.confirmMessage || '';
            var title = form.dataset.confirmTitle || 'Bitte bestätigen';
            var confirmText = form.dataset.confirmText || 'Bestätigen';
            var cancelClass = form.dataset.confirmCancelClass || '';
            var confirmClass = form.dataset.confirmClass || 'btn-danger';
            var statusClass = form.dataset.confirmStatusClass || 'bg-danger';

            try {
                if (typeof cmsConfirm === 'function') {
                    cmsConfirm({
                        title: title,
                        message: message,
                        confirmText: confirmText,
                        cancelClass: cancelClass,
                        confirmClass: confirmClass,
                        statusClass: statusClass,
                        onConfirm: function() {
                            form.dataset.confirmAccepted = '1';
                            submitConfirmedForm(form, submitter);
                        }
                    });
                    return;
                }
            } catch (error) {
                console.warn('Bestätigungsdialog fehlgeschlagen, Fallback auf window.confirm wird verwendet.', error);
            }

            if (window.confirm(message)) {
                form.dataset.confirmAccepted = '1';
                submitConfirmedForm(form, submitter);
            }
        });
    });
}

function initResponsiveTableDropdowns() {
    var dropdowns = document.querySelectorAll('.table-responsive .dropdown');

    if (!dropdowns.length) {
        return;
    }

    function setTableOverflow(dropdownElement, open) {
        if (!(dropdownElement instanceof HTMLElement)) {
            return;
        }

        var container = dropdownElement.closest('.table-responsive');
        if (!(container instanceof HTMLElement)) {
            return;
        }

        container.style.overflow = open ? 'visible' : '';
    }

    dropdowns.forEach(function(dropdown) {
        dropdown.addEventListener('show.bs.dropdown', function() {
            setTableOverflow(dropdown, true);
        });

        dropdown.addEventListener('hidden.bs.dropdown', function() {
            setTableOverflow(dropdown, false);
        });
    });
}

function initGlobalEmptyTablePattern() {
    var emptyRowCandidates = document.querySelectorAll('table tbody tr td[colspan]');
    if (!emptyRowCandidates.length) {
        return;
    }

    function toEntityLabel(rawText) {
        var normalized = String(rawText || '').replace(/\s+/g, ' ').trim();
        var match = normalized.match(/^(?:Noch\s+)?Keine\s+(.+?)\s+(?:vorhanden|angelegt|registriert|konfiguriert|protokolliert|gefunden|verfügbar|vorliegend)\.?$/i);
        if (!match || !match[1]) {
            return 'Einträge';
        }
        return match[1].trim();
    }

    emptyRowCandidates.forEach(function(cell) {
        if (!(cell instanceof HTMLElement)) {
            return;
        }
        if (cell.querySelector('.admin-empty-table-state, .empty')) {
            return;
        }

        var text = (cell.textContent || '').replace(/\s+/g, ' ').trim();
        if (!/^(noch\s+)?keine\b/i.test(text)) {
            return;
        }

        var entity = toEntityLabel(text);
        cell.classList.remove('text-center', 'text-secondary', 'py-3', 'py-4');
        cell.classList.add('py-4');
        cell.innerHTML = '' +
            '<div class="admin-empty-table-state text-center">' +
                '<svg xmlns="http://www.w3.org/2000/svg" class="icon text-secondary mb-2" width="32" height="32" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">' +
                    '<path stroke="none" d="M0 0h24v24H0z" fill="none"></path>' +
                    '<path d="M9 6l11 0"></path>' +
                    '<path d="M9 12l11 0"></path>' +
                    '<path d="M9 18l11 0"></path>' +
                    '<path d="M5 6l0 .01"></path>' +
                    '<path d="M5 12l0 .01"></path>' +
                    '<path d="M5 18l0 .01"></path>' +
                '</svg>' +
                '<div class="fw-bold">Keine ' + entity + ' vorhanden.</div>' +
                '<div class="text-secondary small">Sobald neue Daten vorliegen, erscheinen sie hier automatisch.</div>' +
            '</div>';
    });
}

function cleanupStaleGlobalOverlays() {
    var activeBlockingOverlay = document.querySelector('.cms-modal.active, .cms-unsaved-modal.is-open, .cms-editor-image-picker.is-open');
    var activeBootstrapModal = document.querySelector('.modal.show');

    if (activeBlockingOverlay || activeBootstrapModal) {
        return;
    }

    document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
        backdrop.remove();
    });

    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('padding-right');
}

document.addEventListener('DOMContentLoaded', function() {
    cleanupStaleGlobalOverlays();
    
    // Confirmation for destructive actions
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
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
    initResponsiveTableDropdowns();
    initGlobalEmptyTablePattern();
    
});
