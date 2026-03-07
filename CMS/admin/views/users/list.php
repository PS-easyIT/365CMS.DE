<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Users – Listenansicht
 *
 * Erwartet: $data, $alert, $csrfToken
 */

$users   = $data['users'] ?? [];
$stats   = $data['stats'] ?? [];
$filter  = $data['filter'] ?? [];
$total   = $data['total'] ?? 0;
$curPage = $data['page'] ?? 1;
$pages   = $data['pages'] ?? 1;
$siteUrl = defined('SITE_URL') ? SITE_URL : '';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">Benutzer & Gruppen</div>
                <h2 class="page-title">Benutzer</h2>
            </div>
            <div class="col-auto ms-auto">
                <a href="<?php echo htmlspecialchars($siteUrl); ?>/admin/users?action=edit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                    Neuer Benutzer
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <?php if (!empty($alert)): ?>
            <div class="alert alert-<?php echo $alert['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible mb-3" role="alert">
                <div><?php echo htmlspecialchars($alert['message']); ?></div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
            </div>
        <?php endif; ?>

        <!-- KPI-Karten -->
        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="bg-primary text-white avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/></svg>
                            </span></div>
                            <div class="col"><div class="font-weight-medium"><?php echo (int)($stats['total_users'] ?? 0); ?></div><div class="text-secondary">Gesamt</div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="bg-green text-white avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                            </span></div>
                            <div class="col"><div class="font-weight-medium"><?php echo (int)($stats['active_users'] ?? 0); ?></div><div class="text-secondary">Aktiv</div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="bg-yellow text-white avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4"/><path d="M12 16h.01"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/></svg>
                            </span></div>
                            <div class="col"><div class="font-weight-medium"><?php echo (int)($stats['inactive_users'] ?? 0); ?></div><div class="text-secondary">Inaktiv</div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="bg-red text-white avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12"/><path d="M6 6l12 12"/></svg>
                            </span></div>
                            <div class="col"><div class="font-weight-medium"><?php echo (int)($stats['banned_users'] ?? 0); ?></div><div class="text-secondary">Gesperrt</div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Tabelle -->
        <div class="card">
            <div class="card-header">
                <div class="row w-100 g-2 align-items-center">
                    <div class="col-auto">
                        <select class="form-select form-select-sm" onchange="window.location.href='<?php echo htmlspecialchars($siteUrl); ?>/admin/users?role='+this.value+'&status=<?php echo urlencode($filter['status']); ?>&q=<?php echo urlencode($filter['search']); ?>'">
                            <option value="">Alle Rollen</option>
                            <option value="admin" <?php if ($filter['role'] === 'admin') echo 'selected'; ?>>Admin</option>
                            <option value="editor" <?php if ($filter['role'] === 'editor') echo 'selected'; ?>>Editor</option>
                            <option value="author" <?php if ($filter['role'] === 'author') echo 'selected'; ?>>Autor</option>
                            <option value="member" <?php if ($filter['role'] === 'member') echo 'selected'; ?>>Mitglied</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select class="form-select form-select-sm" onchange="window.location.href='<?php echo htmlspecialchars($siteUrl); ?>/admin/users?status='+this.value+'&role=<?php echo urlencode($filter['role']); ?>&q=<?php echo urlencode($filter['search']); ?>'">
                            <option value="">Alle Status</option>
                            <option value="active" <?php if ($filter['status'] === 'active') echo 'selected'; ?>>Aktiv</option>
                            <option value="inactive" <?php if ($filter['status'] === 'inactive') echo 'selected'; ?>>Inaktiv</option>
                            <option value="banned" <?php if ($filter['status'] === 'banned') echo 'selected'; ?>>Gesperrt</option>
                        </select>
                    </div>
                    <div class="col">
                        <form method="get" action="<?php echo htmlspecialchars($siteUrl); ?>/admin/users" class="d-flex gap-2">
                            <input type="hidden" name="role" value="<?php echo htmlspecialchars($filter['role']); ?>">
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter['status']); ?>">
                            <input type="text" class="form-control form-control-sm" name="q" value="<?php echo htmlspecialchars($filter['search']); ?>" placeholder="Suchen…">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Suchen</button>
                        </form>
                    </div>
                    <div class="col-auto">
                        <span class="text-secondary"><?php echo $total; ?> Benutzer</span>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th class="w-1"><input type="checkbox" class="form-check-input" id="selectAll"></th>
                            <th>Benutzer</th>
                            <th>E-Mail</th>
                            <th>Rolle</th>
                            <th>Status</th>
                            <th>Registriert</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="7" class="text-center text-secondary py-4">Keine Benutzer gefunden.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input row-select" value="<?php echo (int)$u->id; ?>"></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-sm me-2 bg-<?php echo match($u->role ?? '') { 'admin' => 'red', 'editor' => 'blue', 'author' => 'green', default => 'secondary' }; ?>"><?php echo strtoupper(substr($u->username ?? '', 0, 2)); ?></span>
                                            <div>
                                                <a href="<?php echo htmlspecialchars($siteUrl); ?>/admin/users?action=edit&id=<?php echo (int)$u->id; ?>" class="text-reset"><?php echo htmlspecialchars($u->username ?? ''); ?></a>
                                                <?php if (!empty($u->meta['first_name']) || !empty($u->meta['last_name'])): ?>
                                                    <div class="text-secondary small"><?php echo htmlspecialchars(trim(($u->meta['first_name'] ?? '') . ' ' . ($u->meta['last_name'] ?? ''))); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-secondary"><?php echo htmlspecialchars($u->email ?? ''); ?></td>
                                    <td><span class="badge bg-<?php echo match($u->role ?? '') { 'admin' => 'red', 'editor' => 'blue', 'author' => 'green', default => 'secondary' }; ?>-lt"><?php echo htmlspecialchars(ucfirst($u->role ?? 'member')); ?></span></td>
                                    <td><span class="badge bg-<?php echo match($u->status ?? '') { 'active' => 'green', 'inactive' => 'yellow', 'banned' => 'red', default => 'secondary' }; ?>-lt"><?php echo htmlspecialchars(match($u->status ?? '') { 'active' => 'Aktiv', 'inactive' => 'Inaktiv', 'banned' => 'Gesperrt', default => $u->status ?? '' }); ?></span></td>
                                    <td class="text-secondary"><?php echo !empty($u->created_at) ? date('d.m.Y', strtotime($u->created_at)) : '–'; ?></td>
                                    <td>
                                        <div class="btn-list flex-nowrap">
                                            <a href="<?php echo htmlspecialchars($siteUrl); ?>/admin/users?action=edit&id=<?php echo (int)$u->id; ?>" class="btn btn-ghost-primary btn-icon btn-sm" title="Bearbeiten">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"/></svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pages > 1): ?>
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-secondary">Seite <?php echo $curPage; ?> von <?php echo $pages; ?></p>
                <ul class="pagination m-0 ms-auto">
                    <li class="page-item <?php echo $curPage <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo htmlspecialchars($siteUrl); ?>/admin/users?page=<?php echo $curPage - 1; ?>&role=<?php echo urlencode($filter['role']); ?>&status=<?php echo urlencode($filter['status']); ?>&q=<?php echo urlencode($filter['search']); ?>">Zurück</a>
                    </li>
                    <li class="page-item <?php echo $curPage >= $pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo htmlspecialchars($siteUrl); ?>/admin/users?page=<?php echo $curPage + 1; ?>&role=<?php echo urlencode($filter['role']); ?>&status=<?php echo urlencode($filter['status']); ?>&q=<?php echo urlencode($filter['search']); ?>">Weiter</a>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Bulk-Action Form -->
<form id="bulkForm" method="post" class="d-none">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action" value="bulk">
    <input type="hidden" name="bulk_action" id="bulkAction">
    <input type="hidden" name="ids[]" id="bulkIds">
</form>

<script>
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.row-select').forEach(function(cb) { cb.checked = this.checked; }.bind(this));
});
</script>
