<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/** @var array $data */
$d       = $data ?? [];
$plugins = $d['plugins'] ?? [];
$stats   = $d['stats'] ?? [];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Plugins</div>
                <h2 class="page-title">Plugin-Verwaltung</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

<?php if (!empty($alert)): ?>
<div class="alert alert-<?php echo htmlspecialchars((string)($alert['type'] ?? 'info')); ?> alert-dismissible mb-4" role="alert">
    <div><?php echo htmlspecialchars((string)($alert['message'] ?? '')); ?></div>
    <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
</div>
<?php endif; ?>

<!-- KPI-Karten -->
<div class="row row-deck row-cards mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Plugins gesamt</div>
                <div class="h1 mb-0"><?php echo (int)($stats['total'] ?? 0); ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Aktiv</div>
                <div class="h1 mb-0 text-success"><?php echo (int)($stats['active'] ?? 0); ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Inaktiv</div>
                <div class="h1 mb-0 text-secondary"><?php echo (int)($stats['inactive'] ?? 0); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Plugin-Liste -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Installierte Plugins</h3>
        <div class="card-actions">
            <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/plugin-marketplace" class="btn btn-primary btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                Plugin installieren
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Plugin</th>
                    <th>Version</th>
                    <th>Autor</th>
                    <th>Status</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($plugins)): ?>
                <tr><td colspan="5" class="text-muted text-center">Keine Plugins installiert</td></tr>
                <?php else: ?>
                <?php foreach ($plugins as $p): ?>
                <tr>
                    <td>
                        <div class="fw-bold"><?php echo htmlspecialchars($p['name']); ?></div>
                        <?php if (!empty($p['description'])): ?>
                        <div class="text-muted small"><?php echo htmlspecialchars($p['description']); ?></div>
                        <?php endif; ?>
                        <div class="text-muted small"><code><?php echo htmlspecialchars($p['slug']); ?></code></div>
                    </td>
                    <td><?php echo htmlspecialchars($p['version'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($p['author'] ?? '-'); ?></td>
                    <td>
                        <div class="d-flex flex-column gap-1 align-items-start">
                            <span class="badge <?php echo $p['active'] ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $p['active'] ? 'Aktiv' : 'Inaktiv'; ?></span>
                            <span class="text-secondary small"><?php echo $p['active'] ? 'Im System geladen und einsatzbereit.' : 'Deaktiviert – kann per Schieberegler aktiviert werden.'; ?></span>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2 justify-content-end">
                            <form method="post" class="m-0">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                                <input type="hidden" name="action" value="<?php echo $p['active'] ? 'deactivate' : 'activate'; ?>">
                                <input type="hidden" name="slug" value="<?php echo htmlspecialchars($p['slug']); ?>">
                                <label class="form-check form-switch m-0" title="<?php echo $p['active'] ? 'Plugin deaktivieren' : 'Plugin aktivieren'; ?>">
                                    <input class="form-check-input" type="checkbox" <?php echo $p['active'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                                </label>
                            </form>
                            <?php if (!$p['active']): ?>
                                <form method="post" class="m-0">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="slug" value="<?php echo htmlspecialchars($p['slug']); ?>">
                                    <button class="btn btn-ghost-danger btn-sm" onclick="return confirm('Plugin endgültig löschen?')">Löschen</button>
                                </form>
                            <?php endif; ?>
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
