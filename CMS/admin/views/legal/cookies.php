<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/** @var array $data */
$d          = $data ?? [];
$categories = $d['categories'] ?? [];
$settings   = $d['settings'] ?? [];
?>

<div class="row row-deck row-cards mb-4">
    <!-- Banner-Einstellungen -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Cookie-Banner Einstellungen</h3>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                    <input type="hidden" name="action" value="save_settings">

                    <label class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="cookie_banner_enabled" value="1" <?php echo ($settings['cookie_banner_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <span class="form-check-label">Cookie-Banner aktiv</span>
                    </label>

                    <div class="mb-3">
                        <label class="form-label">Position</label>
                        <select name="cookie_banner_position" class="form-select">
                            <?php foreach (['bottom' => 'Unten', 'top' => 'Oben', 'center' => 'Zentriert (Modal)'] as $v => $l): ?>
                                <option value="<?php echo $v; ?>" <?php echo ($settings['cookie_banner_position'] ?? 'bottom') === $v ? 'selected' : ''; ?>><?php echo $l; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Stil</label>
                        <select name="cookie_banner_style" class="form-select">
                            <?php foreach (['dark' => 'Dunkel', 'light' => 'Hell', 'custom' => 'Benutzerdefiniert'] as $v => $l): ?>
                                <option value="<?php echo $v; ?>" <?php echo ($settings['cookie_banner_style'] ?? 'dark') === $v ? 'selected' : ''; ?>><?php echo $l; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Banner-Text</label>
                        <textarea name="cookie_banner_text" class="form-control" rows="3"><?php echo htmlspecialchars($settings['cookie_banner_text'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Cookie-Laufzeit (Tage)</label>
                        <input type="number" name="cookie_lifetime_days" class="form-control" min="1" max="365" value="<?php echo (int)($settings['cookie_lifetime_days'] ?: 30); ?>">
                    </div>

                    <button type="submit" class="btn btn-primary">Einstellungen speichern</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Vorschau -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Übersicht Cookie-Kategorien</h3>
                <div class="card-actions">
                    <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="resetCategoryForm()">
                        Kategorie hinzufügen
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Pflicht</th>
                            <th>Status</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="5" class="text-muted text-center">Keine Kategorien vorhanden</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cat['name']); ?></td>
                            <td><code><?php echo htmlspecialchars($cat['slug']); ?></code></td>
                            <td>
                                <?php if ((int)$cat['is_required']): ?>
                                    <span class="badge bg-blue">Pflicht</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Optional</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int)$cat['is_active']): ?>
                                    <span class="badge bg-success">Aktiv</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inaktiv</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-ghost-secondary btn-icon btn-sm" data-bs-toggle="dropdown">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/></svg>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a href="#" class="dropdown-item" onclick='editCategory(<?php echo json_encode($cat, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>Bearbeiten</a>
                                        <?php if (!(int)$cat['is_required']): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                                            <input type="hidden" name="action" value="delete_category">
                                            <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>">
                                            <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Wirklich löschen?')">Löschen</button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Kategorie Modal -->
<div class="modal modal-blur fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                <input type="hidden" name="action" value="save_category">
                <input type="hidden" name="category_id" id="catId" value="0">
                <div class="modal-header">
                    <h5 class="modal-title" id="catModalTitle">Kategorie hinzufügen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="category_name" id="catName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="category_slug" id="catSlug" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Beschreibung</label>
                        <textarea name="category_description" id="catDesc" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Scripts (werden bei Zustimmung geladen)</label>
                        <textarea name="category_scripts" id="catScripts" class="form-control" rows="3" placeholder="<script src='...'></script>"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reihenfolge</label>
                        <input type="number" name="sort_order" id="catOrder" class="form-control" value="0">
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_required" id="catRequired">
                                <span class="form-check-label">Pflicht</span>
                            </label>
                        </div>
                        <div class="col-6">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="catActive" checked>
                                <span class="form-check-label">Aktiv</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetCategoryForm() {
    document.getElementById('catModalTitle').textContent = 'Kategorie hinzufügen';
    document.getElementById('catId').value = '0';
    document.getElementById('catName').value = '';
    document.getElementById('catSlug').value = '';
    document.getElementById('catDesc').value = '';
    document.getElementById('catScripts').value = '';
    document.getElementById('catOrder').value = '0';
    document.getElementById('catRequired').checked = false;
    document.getElementById('catActive').checked = true;
}
function editCategory(cat) {
    document.getElementById('catModalTitle').textContent = 'Kategorie bearbeiten';
    document.getElementById('catId').value = cat.id;
    document.getElementById('catName').value = cat.name || '';
    document.getElementById('catSlug').value = cat.slug || '';
    document.getElementById('catDesc').value = cat.description || '';
    document.getElementById('catScripts').value = cat.scripts || '';
    document.getElementById('catOrder').value = cat.sort_order || '0';
    document.getElementById('catRequired').checked = !!parseInt(cat.is_required);
    document.getElementById('catActive').checked = !!parseInt(cat.is_active);
    new bootstrap.Modal(document.getElementById('categoryModal')).show();
}
</script>
