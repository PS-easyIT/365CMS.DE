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
            console.error('admin-menu-editor: Konfiguration konnte nicht gelesen werden.', error);
            return null;
        }
    }

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(String(value || '')));
        return div.innerHTML;
    }

    function getModalInstance(modalElement) {
        if (!modalElement || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return null;
        }

        return bootstrap.Modal.getOrCreateInstance(modalElement);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var config = parseConfig('menu-editor-config') || { items: [] };
        var menuItems = Array.isArray(config.items) ? config.items.slice() : [];
        var listElement = document.getElementById('menuItemsList');
        var jsonInput = document.getElementById('menuItemsJson');
        var newItemTitle = document.getElementById('newItemTitle');
        var newItemUrl = document.getElementById('newItemUrl');
        var newItemParent = document.getElementById('newItemParent');
        var newItemTarget = document.getElementById('newItemTarget');
        var addItemButton = document.getElementById('btnAddItem');
        var pagePickerSelect = document.getElementById('pagePickerSelect');
        var addPageItemButton = document.getElementById('btnAddPageItem');
        var deleteMenuButton = document.getElementById('btnDeleteMenu');
        var deleteMenuForm = document.getElementById('deleteMenuForm');
        var saveItemsForm = document.getElementById('saveItemsForm');
        var menuModal = document.getElementById('menuModal');
        var menuModalInstance = getModalInstance(menuModal);
        var menuModalTitle = document.getElementById('menuModalTitle');
        var editMenuId = document.getElementById('editMenuId');
        var editMenuName = document.getElementById('editMenuName');
        var editMenuLocation = document.getElementById('editMenuLocation');
        var tempIdCounter = 0;

        function nextTempId() {
            tempIdCounter += 1;
            return 'tmp-' + tempIdCounter;
        }

        function normalizeParentId(value) {
            return value && value !== '0' ? String(value) : '0';
        }

        function normalizeUrl(value) {
            return String(value || '').trim();
        }

        function isNoOpMenuUrl(value) {
            return /^javascript:\s*(?:void\(0\)|;?)\s*;?$/i.test(normalizeUrl(value));
        }

        function itemHasChildren(itemId) {
            var normalizedId = String(itemId || '');

            if (normalizedId === '') {
                return false;
            }

            return menuItems.some(function (candidate) {
                return normalizeParentId(candidate.parent_id) === normalizedId;
            });
        }

        function normalizeMenuUrlForStorage(value, allowEmptyPlaceholder) {
            var normalized = normalizeUrl(value);
            var parser;

            if (normalized === '') {
                return allowEmptyPlaceholder ? '#' : '';
            }

            if (isNoOpMenuUrl(normalized)) {
                return '#';
            }

            if (/^(?:\/(?!\/)|#|\?|mailto:|tel:|https?:\/\/)/i.test(normalized)) {
                return normalized;
            }

            if (/^(?!\/\/)(?![a-z][a-z0-9+\-.]*:)/i.test(normalized)) {
                parser = document.createElement('a');
                parser.href = 'https://menu-editor.local/' + normalized.replace(/^\/+/, '');

                if (parser.pathname && parser.pathname !== '/') {
                    return parser.pathname + (parser.search || '') + (parser.hash || '');
                }
            }

            return normalized;
        }

        function isValidMenuUrl(value) {
            var normalized = normalizeMenuUrlForStorage(value, false);

            if (normalized === '') {
                return false;
            }

            return /^(?:\/(?!\/)|#|\?|mailto:|tel:|https?:\/\/)/i.test(normalized);
        }

        function setFieldError(field, message) {
            if (!field) {
                return;
            }

            field.setCustomValidity(message || '');
            field.reportValidity();
        }

        function clearFieldError(field) {
            if (!field) {
                return;
            }

            field.setCustomValidity('');
        }

        function validateItemData(item, options) {
            options = options || {};
            var normalizedUrl;

            if (!item || String(item.title || '').trim() === '') {
                return 'Bitte einen Titel angeben.';
            }

            normalizedUrl = normalizeMenuUrlForStorage(item.url, itemHasChildren(item.id));
            if (normalizedUrl === '' && options.allowEmptyUrl === true) {
                item.url = '';
                return '';
            }

            if (!isValidMenuUrl(normalizedUrl)) {
                return 'Bitte eine gültige URL, einen internen Pfad oder mailto:/tel: verwenden.';
            }

            item.url = normalizedUrl;

            return '';
        }

        function getItemById(id) {
            return menuItems.find(function (item) {
                return String(item.id) === String(id);
            }) || null;
        }

        function getItemDepth(item, visited) {
            visited = visited || [];
            var parentId = normalizeParentId(item.parent_id);
            if (parentId === '0' || visited.indexOf(parentId) !== -1) {
                return 0;
            }

            var parent = getItemById(parentId);
            if (!parent) {
                return 0;
            }

            return 1 + getItemDepth(parent, visited.concat([parentId]));
        }

        function collectDescendantIds(itemId, bucket) {
            bucket = bucket || [];
            menuItems.forEach(function (candidate) {
                if (normalizeParentId(candidate.parent_id) === String(itemId) && bucket.indexOf(String(candidate.id)) === -1) {
                    bucket.push(String(candidate.id));
                    collectDescendantIds(candidate.id, bucket);
                }
            });

            return bucket;
        }

        function buildParentOptions(currentId, selectedParentId) {
            var descendants = currentId ? collectDescendantIds(currentId, []) : [];
            var options = '<option value="0">Hauptebene</option>';

            menuItems.forEach(function (candidate) {
                var candidateId = String(candidate.id);
                if (currentId && (candidateId === String(currentId) || descendants.indexOf(candidateId) !== -1)) {
                    return;
                }

                var depth = getItemDepth(candidate);
                var prefix = depth > 0 ? Array(depth + 1).join('↳ ') : '';
                var selected = normalizeParentId(selectedParentId) === candidateId ? ' selected' : '';
                options += '<option value="' + escapeHtml(candidateId) + '"' + selected + '>' + escapeHtml(prefix + candidate.title) + '</option>';
            });

            return options;
        }

        function syncJsonInput() {
            if (jsonInput) {
                jsonInput.value = JSON.stringify(menuItems);
            }
        }

        function prepareMenuItemsForSubmit() {
            menuItems.forEach(function (item) {
                if (!item) {
                    return;
                }

                item.url = normalizeMenuUrlForStorage(item.url, itemHasChildren(item.id));
            });
        }

        function validateMenuItems() {
            var index;

            for (index = 0; index < menuItems.length; index += 1) {
                var validationError = validateItemData(menuItems[index]);
                if (validationError !== '') {
                    return {
                        valid: false,
                        index: index,
                        error: validationError,
                    };
                }
            }

            return {
                valid: true,
                index: -1,
                error: '',
            };
        }

        function refreshParentSelects() {
            if (newItemParent) {
                newItemParent.innerHTML = buildParentOptions(null, '0');
            }

            if (!listElement) {
                return;
            }

            listElement.querySelectorAll('.item-parent').forEach(function (select) {
                var index = parseInt(select.dataset.index || '', 10);
                var item = menuItems[index];
                if (!item) {
                    return;
                }

                select.innerHTML = buildParentOptions(item.id, item.parent_id);
            });
        }

        function sortMenuItemsByTree() {
            var grouped = {};
            var ordered = [];

            menuItems.forEach(function (item) {
                var parentId = normalizeParentId(item.parent_id);
                if (!grouped[parentId]) {
                    grouped[parentId] = [];
                }
                grouped[parentId].push(item);
            });

            function appendBranch(parentId, trail) {
                (grouped[parentId] || []).forEach(function (item) {
                    var itemId = String(item.id);
                    if (trail.indexOf(itemId) !== -1) {
                        return;
                    }

                    ordered.push(item);
                    appendBranch(itemId, trail.concat([itemId]));
                });
            }

            appendBranch('0', []);

            menuItems.forEach(function (item) {
                if (ordered.indexOf(item) === -1) {
                    ordered.push(item);
                }
            });

            menuItems = ordered;
        }

        function getSubtreeEndIndex(startIndex) {
            var startItem = menuItems[startIndex];
            var startDepth;
            var index;

            if (!startItem) {
                return startIndex;
            }

            startDepth = getItemDepth(startItem);

            for (index = startIndex + 1; index < menuItems.length; index += 1) {
                if (getItemDepth(menuItems[index]) <= startDepth) {
                    return index - 1;
                }
            }

            return menuItems.length - 1;
        }

        function moveItem(index, direction) {
            var item = menuItems[index];
            var parentId;
            var itemDepth;
            var siblingStarts = [];
            var siblingPosition;
            var startIndex;
            var endIndex;
            var blockLength;
            var block;
            var prevStart;
            var nextStart;
            var nextEnd;
            var insertAt;
            var scanIndex;

            if (!item) {
                return;
            }

            parentId = normalizeParentId(item.parent_id);
            itemDepth = getItemDepth(item);

            for (scanIndex = 0; scanIndex < menuItems.length; scanIndex += 1) {
                if (normalizeParentId(menuItems[scanIndex].parent_id) === parentId && getItemDepth(menuItems[scanIndex]) === itemDepth) {
                    siblingStarts.push(scanIndex);
                }
            }

            siblingPosition = siblingStarts.indexOf(index);
            if (siblingPosition === -1) {
                return;
            }

            startIndex = index;
            endIndex = getSubtreeEndIndex(startIndex);
            blockLength = endIndex - startIndex + 1;
            block = menuItems.splice(startIndex, blockLength);

            if (direction === 'up') {
                prevStart = siblingStarts[siblingPosition - 1];
                if (typeof prevStart !== 'number') {
                    menuItems.splice.apply(menuItems, [startIndex, 0].concat(block));
                    return;
                }

                menuItems.splice.apply(menuItems, [prevStart, 0].concat(block));
                return;
            }

            nextStart = siblingStarts[siblingPosition + 1];
            if (typeof nextStart !== 'number') {
                menuItems.splice.apply(menuItems, [startIndex, 0].concat(block));
                return;
            }

            nextEnd = getSubtreeEndIndex(nextStart - blockLength);
            insertAt = nextEnd + 1;
            menuItems.splice.apply(menuItems, [insertAt, 0].concat(block));
        }

        function removeItemAndChildren(itemId) {
            var descendants = collectDescendantIds(itemId, []);
            var idsToRemove = [String(itemId)].concat(descendants);
            menuItems = menuItems.filter(function (item) {
                return idsToRemove.indexOf(String(item.id)) === -1;
            });
        }

        function renderItems() {
            if (!listElement) {
                return;
            }

            sortMenuItemsByTree();

            if (menuItems.length === 0) {
                listElement.innerHTML = '<div class="list-group-item text-center text-muted py-5" id="emptyState">Noch keine Items. Füge oben ein Item hinzu.</div>';
                syncJsonInput();
                refreshParentSelects();
                return;
            }

            var html = '';
            menuItems.forEach(function (item, idx) {
                var depth = getItemDepth(item);
                var prefix = depth > 0 ? Array(depth + 1).join('↳ ') : '';
                html += '<div class="list-group-item d-flex align-items-start gap-3" data-index="' + idx + '">';
                html += '<span class="cursor-grab text-muted">☰</span>';
                html += '<div class="flex-fill">';
                if (depth > 0) {
                    html += '<div class="small text-muted mb-2">' + escapeHtml(prefix + 'Unterpunkt') + '</div>';
                }
                html += '<div class="row g-2">';
                html += '<div class="col-md-4"><label class="form-label small text-muted mb-1">Text</label><input type="text" class="form-control form-control-sm item-title" data-index="' + idx + '" value="' + escapeHtml(item.title) + '" placeholder="Menütext"></div>';
                html += '<div class="col-md-5"><label class="form-label small text-muted mb-1">URL</label><input type="text" class="form-control form-control-sm item-url" data-index="' + idx + '" value="' + escapeHtml(item.url) + '" placeholder="slug, /pfad oder https://..."></div>';
                html += '<div class="col-md-3"><label class="form-label small text-muted mb-1">Ziel</label><select class="form-select form-select-sm item-target" data-index="' + idx + '"><option value="_self"' + (item.target === '_self' ? ' selected' : '') + '>Gleiches Fenster</option><option value="_blank"' + (item.target === '_blank' ? ' selected' : '') + '>Neuer Tab</option></select></div>';
                html += '</div>';
                html += '<div class="mt-2"><label class="form-label small text-muted mb-1">Unterpunkt von</label><select class="form-select form-select-sm item-parent" data-index="' + idx + '">' + buildParentOptions(item.id, item.parent_id) + '</select></div>';
                html += '</div>';
                html += '<div class="d-flex flex-column gap-2"><button type="button" class="btn btn-sm btn-outline-secondary move-item-up" data-index="' + idx + '" aria-label="Item nach oben verschieben">↑</button><button type="button" class="btn btn-sm btn-outline-secondary move-item-down" data-index="' + idx + '" aria-label="Item nach unten verschieben">↓</button><button type="button" class="btn btn-sm btn-outline-danger remove-item" data-index="' + idx + '">×</button></div>';
                html += '</div>';
            });
            listElement.innerHTML = html;
            syncJsonInput();
            refreshParentSelects();
        }

        document.querySelectorAll('.js-menu-modal-trigger').forEach(function (button) {
            button.addEventListener('click', function () {
                if (editMenuId) {
                    editMenuId.value = button.getAttribute('data-menu-id') || '0';
                }
                if (editMenuName) {
                    editMenuName.value = button.getAttribute('data-menu-name') || '';
                }
                if (editMenuLocation) {
                    editMenuLocation.value = button.getAttribute('data-menu-location') || '';
                }
                if (menuModalTitle) {
                    menuModalTitle.textContent = button.getAttribute('data-menu-modal-title') || 'Menü erstellen / bearbeiten';
                }
                if (menuModalInstance) {
                    menuModalInstance.show();
                }
            });
        });

        if (addItemButton) {
            addItemButton.addEventListener('click', function () {
                var title = newItemTitle ? newItemTitle.value.trim() : '';
                var url = newItemUrl ? normalizeMenuUrlForStorage(newItemUrl.value, false) : '';
                var target = newItemTarget ? newItemTarget.value : '_self';
                var parentId = newItemParent ? normalizeParentId(newItemParent.value) : '0';
                var validationError;

                clearFieldError(newItemTitle);
                clearFieldError(newItemUrl);

                validationError = validateItemData({ title: title, url: url }, { allowEmptyUrl: true });
                if (validationError !== '') {
                    if (title === '') {
                        setFieldError(newItemTitle, validationError);
                    } else {
                        setFieldError(newItemUrl, validationError);
                    }
                    return;
                }

                menuItems.push({ id: nextTempId(), title: title, url: url, target: target, icon: '', parent_id: parentId });
                if (newItemTitle) {
                    newItemTitle.value = '';
                }
                if (newItemUrl) {
                    newItemUrl.value = '';
                }
                if (newItemParent) {
                    newItemParent.value = '0';
                }
                renderItems();
            });
        }

        if (addPageItemButton && pagePickerSelect) {
            addPageItemButton.addEventListener('click', function () {
                var selectedOption = pagePickerSelect.options[pagePickerSelect.selectedIndex];

                clearFieldError(pagePickerSelect);
                if (!selectedOption || !selectedOption.value) {
                    setFieldError(pagePickerSelect, 'Bitte zuerst eine Seite auswählen.');
                    return;
                }

                menuItems.push({
                    id: nextTempId(),
                    title: selectedOption.getAttribute('data-title') || selectedOption.text || '',
                    url: selectedOption.value || '',
                    target: '_self',
                    icon: '',
                    parent_id: '0',
                });

                pagePickerSelect.value = '';
                renderItems();
            });
        }

        if (listElement) {
            listElement.addEventListener('click', function (event) {
                var removeButton = event.target.closest('.remove-item');
                if (removeButton) {
                    var removeIndex = parseInt(removeButton.dataset.index || '', 10);
                    if (!Number.isNaN(removeIndex) && menuItems[removeIndex]) {
                        removeItemAndChildren(menuItems[removeIndex].id);
                        renderItems();
                    }
                    return;
                }

                var moveUpButton = event.target.closest('.move-item-up');
                if (moveUpButton) {
                    var moveUpIndex = parseInt(moveUpButton.dataset.index || '', 10);
                    if (!Number.isNaN(moveUpIndex)) {
                        moveItem(moveUpIndex, 'up');
                        renderItems();
                    }
                    return;
                }

                var moveDownButton = event.target.closest('.move-item-down');
                if (moveDownButton) {
                    var moveDownIndex = parseInt(moveDownButton.dataset.index || '', 10);
                    if (!Number.isNaN(moveDownIndex)) {
                        moveItem(moveDownIndex, 'down');
                        renderItems();
                    }
                }
            });

            listElement.addEventListener('input', function (event) {
                var titleInput = event.target.closest('.item-title');
                if (titleInput) {
                    var titleIndex = parseInt(titleInput.dataset.index || '', 10);
                    if (!Number.isNaN(titleIndex) && menuItems[titleIndex]) {
                        menuItems[titleIndex].title = titleInput.value;
                        clearFieldError(titleInput);
                        syncJsonInput();
                    }
                    return;
                }

                var urlInput = event.target.closest('.item-url');
                if (urlInput) {
                    var urlIndex = parseInt(urlInput.dataset.index || '', 10);
                    if (!Number.isNaN(urlIndex) && menuItems[urlIndex]) {
                        menuItems[urlIndex].url = normalizeMenuUrlForStorage(urlInput.value, itemHasChildren(menuItems[urlIndex].id));
                        clearFieldError(urlInput);
                        urlInput.value = menuItems[urlIndex].url;
                        syncJsonInput();
                    }
                }
            });

            listElement.addEventListener('change', function (event) {
                var targetSelect = event.target.closest('.item-target');
                if (targetSelect) {
                    var targetIndex = parseInt(targetSelect.dataset.index || '', 10);
                    if (!Number.isNaN(targetIndex) && menuItems[targetIndex]) {
                        menuItems[targetIndex].target = targetSelect.value === '_blank' ? '_blank' : '_self';
                        syncJsonInput();
                    }
                    return;
                }

                var parentSelect = event.target.closest('.item-parent');
                if (parentSelect) {
                    var parentIndex = parseInt(parentSelect.dataset.index || '', 10);
                    if (!Number.isNaN(parentIndex) && menuItems[parentIndex]) {
                        menuItems[parentIndex].parent_id = normalizeParentId(parentSelect.value);
                        renderItems();
                    }
                }
            });
        }

        if (deleteMenuButton && deleteMenuForm) {
            deleteMenuButton.addEventListener('click', function () {
                var submitDelete = function () {
                    deleteMenuForm.submit();
                };

                if (typeof cmsConfirm === 'function') {
                    cmsConfirm({
                        title: 'Menü löschen',
                        message: 'Soll dieses Menü und alle seine Items gelöscht werden?',
                        confirmText: 'Löschen',
                        confirmClass: 'btn-danger',
                        onConfirm: submitDelete,
                    });
                    return;
                }

                if (window.confirm('Soll dieses Menü und alle seine Items gelöscht werden?')) {
                    submitDelete();
                }
            });
        }

        if (saveItemsForm) {
            saveItemsForm.addEventListener('submit', function (event) {
                prepareMenuItemsForSubmit();
                var validation = validateMenuItems();
                var itemNode;
                var titleField;
                var urlField;

                if (!validation.valid) {
                    event.preventDefault();
                    itemNode = listElement ? listElement.querySelector('[data-index="' + validation.index + '"]') : null;
                    titleField = itemNode ? itemNode.querySelector('.item-title') : null;
                    urlField = itemNode ? itemNode.querySelector('.item-url') : null;

                    if (menuItems[validation.index] && String(menuItems[validation.index].title || '').trim() === '') {
                        setFieldError(titleField, validation.error);
                        if (titleField) {
                            titleField.focus();
                        }
                        return;
                    }

                    setFieldError(urlField, validation.error);
                    if (urlField) {
                        urlField.focus();
                    }
                    return;
                }

                syncJsonInput();
            });
        }

        renderItems();
    });
})();