<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tables – Listenansicht
 *
 * Erwartet: $data (aus TablesModule::getListData())
 *           $alert, $csrfToken
 */

$tables = $data['tables'] ?? [];
$total  = $data['total'] ?? 0;
$search = $data['search'] ?? '';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">Seiten &amp; Beiträge</div>
                <h2 class="page-title">Tabellen</h2>
            </div>
            <div class="col-auto ms-auto">
                <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/site-tables?action=edit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                    Neue Tabelle
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <?php
        $alertData = $alert ?? [];
        $alertDismissible = true;
        $alertMarginClass = 'mb-3';
        require __DIR__ . '/../partials/flash-alert.php';
        ?>

        <!-- KPI -->
        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-primary text-white avatar">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 10h18"/><path d="M10 3v18"/><path d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2z"/></svg>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium"><?php echo (int)$total; ?> Tabellen</div>
                                <div class="text-secondary">Gesamt</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toolbar + Tabelle -->
        <div class="card">
            <div class="card-header">
                <div class="row w-100 g-2 align-items-center">
                    <div class="col">
                        <span class="text-secondary">Shortcodes: <code>[site-table id="X"]</code> oder <code>[table id=X /]</code></span>
                    </div>
                    <div class="col-auto">
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="10" cy="10" r="7"/><path d="M21 21l-6 -6"/></svg>
                            </span>
                            <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Suchen…"
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   onkeydown="if(event.key==='Enter'){var q=this.value.trim();window.location.href='<?php echo htmlspecialchars(SITE_URL); ?>/admin/site-tables'+(q?'?q='+encodeURIComponent(q):'');}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Beschreibung</th>
                            <th>Spalten</th>
                            <th>Zeilen</th>
                            <th>Aktualisiert</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($tables)): ?>
                        <?php
                        $emptyStateColspan = 7;
                        $emptyStateMessage = 'Keine Tabellen vorhanden.';
                        $emptyStateSubtitle = 'Legen Sie Ihre erste Tabelle an oder passen Sie die Suche an.';
                        $emptyStateIcon = 'table';
                        require __DIR__ . '/../partials/empty-table-row.php';
                        ?>
                    <?php else: ?>
                        <?php foreach ($tables as $t): ?>
                            <tr>
                                <td class="text-secondary"><?php echo (int)$t['id']; ?></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/site-tables?action=edit&id=<?php echo (int)$t['id']; ?>" class="text-reset font-weight-medium">
                                        <?php echo htmlspecialchars($t['table_name']); ?>
                                    </a>
                                    <div class="text-secondary small">
                                        <code>[site-table id="<?php echo (int)$t['id']; ?>"]</code>
                                        <span aria-hidden="true">·</span>
                                        <code>[table id=<?php echo (int)$t['id']; ?> /]</code>
                                    </div>
                                </td>
                                <td class="text-secondary text-truncate" style="max-width:200px;"><?php echo htmlspecialchars($t['description'] ?? ''); ?></td>
                                <td><?php echo (int)($t['col_count'] ?? 0); ?></td>
                                <td><?php echo (int)($t['row_count'] ?? 0); ?></td>
                                <td class="text-secondary"><?php echo date('d.m.Y', strtotime($t['updated_at'] ?? $t['created_at'])); ?></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-ghost-secondary btn-icon btn-sm" data-bs-toggle="dropdown">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/><circle cx="12" cy="5" r="1"/></svg>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/site-tables?action=edit&id=<?php echo (int)$t['id']; ?>">
                                                Bearbeiten
                                            </a>
                                            <button class="dropdown-item" onclick="duplicateTable(<?php echo (int)$t['id']; ?>)">Duplizieren</button>
                                            <div class="dropdown-divider"></div>
                                            <button class="dropdown-item text-danger" onclick="deleteTable(<?php echo (int)$t['id']; ?>, '<?php echo htmlspecialchars(addslashes($t['table_name']), ENT_QUOTES); ?>')">Löschen</button>
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

<!-- Hidden Forms -->
<form id="deleteForm" method="post" class="d-none">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>
<form id="duplicateForm" method="post" class="d-none">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action" value="duplicate">
    <input type="hidden" name="id" id="duplicateId">
</form>

<script>
function deleteTable(id, name) {
    cmsConfirm({
        title: 'Tabelle löschen',
        message: 'Tabelle <strong>' + name + '</strong> wirklich löschen?',
        confirmText: 'Löschen',
        confirmClass: 'btn-danger',
        onConfirm: function() {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').submit();
        }
    });
}
function duplicateTable(id) {
    document.getElementById('duplicateId').value = id;
    document.getElementById('duplicateForm').submit();
}
</script>
