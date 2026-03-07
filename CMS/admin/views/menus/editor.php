<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * View: Menü Editor
 *
 * @var array  $data
 * @var string $csrfToken
 * @var array|null $alert
 */

$menus       = $data['menus'] ?? [];
$currentMenu = $data['currentMenu'] ?? null;
$menuItems   = $data['menuItems'] ?? [];
$locations   = $data['locations'] ?? [];
$pages       = $data['pages'] ?? [];
?>

<div class="container-xl">
    <!-- Header -->
    <div class="page-header d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="page-title">Menü Editor</h2>
            <div class="text-muted mt-1">Navigationsmenüs verwalten</div>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#menuModal">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
            Neues Menü
        </button>
    </div>

    <?php if ($alert): ?>
        <div class="alert alert-<?php echo htmlspecialchars($alert['type']); ?> alert-dismissible" role="alert">
            <?php echo htmlspecialchars($alert['message']); ?>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="Close"></a>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Menu List -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Menüs</h3>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($menus as $menu): ?>
                        <a href="<?php echo SITE_URL; ?>/admin/menu-editor?menu=<?php echo (int)$menu->id; ?>"
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center<?php echo ($currentMenu && (int)$currentMenu->id === (int)$menu->id) ? ' active' : ''; ?>">
                            <div>
                                <div><?php echo htmlspecialchars($menu->name); ?></div>
                                <small class="<?php echo ($currentMenu && (int)$currentMenu->id === (int)$menu->id) ? 'text-white-50' : 'text-muted'; ?>">
                                    <?php echo (int)$menu->item_count; ?> Items
                                    <?php if (!empty($menu->location)): ?>
                                        · <?php echo htmlspecialchars($locations[$menu->location] ?? $menu->location); ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                    <?php if (empty($menus)): ?>
                        <div class="list-group-item text-muted text-center py-3">Noch keine Menüs erstellt</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Menu Items Editor -->
        <div class="col-md-9">
            <?php if ($currentMenu): ?>
                <!-- Menu Settings -->
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h3 class="card-title mb-0"><?php echo htmlspecialchars($currentMenu->name); ?></h3>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#menuModal"
                                    onclick="document.getElementById('editMenuId').value='<?php echo (int)$currentMenu->id; ?>'; document.getElementById('editMenuName').value='<?php echo htmlspecialchars($currentMenu->name, ENT_QUOTES); ?>'; document.getElementById('editMenuLocation').value='<?php echo htmlspecialchars($currentMenu->location ?? '', ENT_QUOTES); ?>';">
                                Einstellungen
                            </button>
                            <form method="post" class="d-inline" id="deleteMenuForm">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="action" value="delete_menu">
                                <input type="hidden" name="menu_id" value="<?php echo (int)$currentMenu->id; ?>">
                                <button type="button" class="btn btn-outline-danger btn-sm" id="btnDeleteMenu">Löschen</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Add Item Card -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Item hinzufügen</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="text" id="newItemTitle" class="form-control" placeholder="Titel">
                            </div>
                            <div class="col-md-4">
                                <input type="text" id="newItemUrl" class="form-control" placeholder="URL (z.B. /seite)">
                            </div>
                            <div class="col-md-2">
                                <select id="newItemTarget" class="form-select">
                                    <option value="_self">Gleich</option>
                                    <option value="_blank">Neu</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-primary w-100" id="btnAddItem">Hinzufügen</button>
                            </div>
                        </div>
                        <?php if (!empty($pages)): ?>
                            <div class="mt-3">
                                <label class="form-label small text-muted">Oder Seite wählen:</label>
                                <div class="d-flex gap-2 flex-wrap">
                                    <?php foreach ($pages as $page): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary add-page-btn"
                                                data-title="<?php echo htmlspecialchars($page->title, ENT_QUOTES); ?>"
                                                data-url="/<?php echo htmlspecialchars($page->slug); ?>">
                                            <?php echo htmlspecialchars($page->title); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Menu Items List -->
                <form method="post" id="saveItemsForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="save_items">
                    <input type="hidden" name="menu_id" value="<?php echo (int)$currentMenu->id; ?>">
                    <input type="hidden" name="items" id="menuItemsJson" value="">

                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">Menü-Items</h3>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-device-floppy" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2"/><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M14 4l0 4l-6 0l0 -4"/></svg>
                                Speichern
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div id="menuItemsList" class="list-group list-group-flush">
                                <?php if (empty($menuItems)): ?>
                                    <div class="list-group-item text-center text-muted py-5" id="emptyState">
                                        Noch keine Items. Füge oben ein Item hinzu.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>

            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-menu-2 mb-3" width="48" height="48" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.3;"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 6l16 0"/><path d="M4 12l16 0"/><path d="M4 18l16 0"/></svg>
                        <h3>Wähle ein Menü aus</h3>
                        <p class="text-muted">Wähle ein Menü aus der Liste oder erstelle ein neues.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Menu Create/Edit Modal -->
<div class="modal modal-blur fade" id="menuModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="save_menu">
            <input type="hidden" name="menu_id" id="editMenuId" value="0">
            <div class="modal-header">
                <h5 class="modal-title">Menü erstellen / bearbeiten</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="menu_name" id="editMenuName" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Position</label>
                    <select name="menu_location" id="editMenuLocation" class="form-select">
                        <option value="">– Keine –</option>
                        <?php foreach ($locations as $key => $label): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn me-auto" data-bs-dismiss="modal">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Speichern</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initiale Items aus PHP
    var menuItems = <?php echo json_encode(array_map(function($item) {
        return [
            'title'     => $item->title ?? '',
            'url'       => $item->url ?? '#',
            'target'    => $item->target ?? '_self',
            'icon'      => $item->icon ?? '',
            'parent_id' => (int)($item->parent_id ?? 0),
        ];
    }, $menuItems)); ?>;

    var listEl = document.getElementById('menuItemsList');
    var jsonInput = document.getElementById('menuItemsJson');

    function renderItems() {
        if (!listEl) return;

        if (menuItems.length === 0) {
            listEl.innerHTML = '<div class="list-group-item text-center text-muted py-5" id="emptyState">Noch keine Items. Füge oben ein Item hinzu.</div>';
        } else {
            var html = '';
            menuItems.forEach(function(item, idx) {
                html += '<div class="list-group-item d-flex align-items-center gap-3" data-index="' + idx + '">';
                html += '<span class="cursor-grab text-muted">☰</span>';
                html += '<div class="flex-fill">';
                html += '<strong>' + escapeHtml(item.title) + '</strong>';
                html += ' <span class="text-muted small">(' + escapeHtml(item.url) + ')</span>';
                if (item.target === '_blank') html += ' <span class="badge bg-azure">extern</span>';
                html += '</div>';
                html += '<button type="button" class="btn btn-sm btn-outline-danger remove-item" data-index="' + idx + '">×</button>';
                html += '</div>';
            });
            listEl.innerHTML = html;
        }

        if (jsonInput) {
            jsonInput.value = JSON.stringify(menuItems);
        }
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Add Item
    var btnAdd = document.getElementById('btnAddItem');
    if (btnAdd) {
        btnAdd.addEventListener('click', function() {
            var title  = document.getElementById('newItemTitle').value.trim();
            var url    = document.getElementById('newItemUrl').value.trim();
            var target = document.getElementById('newItemTarget').value;
            if (!title || !url) return;

            menuItems.push({ title: title, url: url, target: target, icon: '', parent_id: 0 });
            document.getElementById('newItemTitle').value = '';
            document.getElementById('newItemUrl').value = '';
            renderItems();
        });
    }

    // Add Page Button
    document.querySelectorAll('.add-page-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            menuItems.push({
                title: this.dataset.title,
                url: this.dataset.url,
                target: '_self',
                icon: '',
                parent_id: 0
            });
            renderItems();
        });
    });

    // Remove Item (delegated)
    if (listEl) {
        listEl.addEventListener('click', function(e) {
            var btn = e.target.closest('.remove-item');
            if (btn) {
                var idx = parseInt(btn.dataset.index);
                menuItems.splice(idx, 1);
                renderItems();
            }
        });
    }

    // Delete Menu
    var btnDelete = document.getElementById('btnDeleteMenu');
    if (btnDelete) {
        btnDelete.addEventListener('click', function() {
            cmsConfirm({
                title: 'Menü löschen',
                message: 'Soll dieses Menü und alle seine Items gelöscht werden?',
                confirmText: 'Löschen',
                confirmClass: 'btn-danger',
                onConfirm: function() { document.getElementById('deleteMenuForm').submit(); }
            });
        });
    }

    renderItems();
});
</script>
