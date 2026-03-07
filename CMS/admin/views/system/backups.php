<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var array $data Backup-Daten
 * @var string $csrfToken CSRF-Token
 */

$backups = $data['backups'];
$history = $data['history'];
?>

<div class="container-xl">
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-database-export me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 6c0 1.657 3.582 3 8 3s8 -1.343 8 -3s-3.582 -3 -8 -3s-8 1.343 -8 3"/><path d="M4 6v6c0 1.657 3.582 3 8 3c1.118 0 2.183 -.086 3.15 -.241"/><path d="M20 12v-6"/><path d="M4 12v6c0 1.657 3.582 3 8 3c.157 0 .312 -.002 .466 -.005"/><path d="M16 19h6"/><path d="M19 16l3 3l-3 3"/></svg>
                    Backup & Restore
                </h2>
            </div>
            <div class="col-auto d-flex gap-2">
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="create_db">
                    <button type="submit" class="btn btn-outline-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 6c0 1.657 3.582 3 8 3s8 -1.343 8 -3s-3.582 -3 -8 -3s-8 1.343 -8 3"/><path d="M4 6v6c0 1.657 3.582 3 8 3s8 -1.343 8 -3"/><path d="M4 12v6c0 1.657 3.582 3 8 3s8 -1.343 8 -3"/></svg>
                        DB-Backup
                    </button>
                </form>
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="create_full">
                    <button type="submit" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/><path d="M7 11l5 5l5 -5"/><path d="M12 4l0 12"/></svg>
                        Vollständiges Backup
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Vorhandene Backups -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Vorhandene Backups</h3>
            <div class="card-actions">
                <span class="badge bg-blue-lt"><?php echo count($backups); ?> Backup(s)</span>
            </div>
        </div>
        <?php if (!empty($backups)): ?>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Dateiname</th>
                            <th>Größe</th>
                            <th>Datum</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $backup): ?>
                            <?php
                            $name = is_array($backup) ? ($backup['name'] ?? $backup['filename'] ?? '') : (string)$backup;
                            $size = is_array($backup) ? ($backup['size'] ?? $backup['size_formatted'] ?? '-') : '-';
                            $date = is_array($backup) ? ($backup['date'] ?? $backup['created'] ?? '-') : '-';
                            if (is_numeric($size)) {
                                $size = round($size / 1024 / 1024, 2) . ' MB';
                            }
                            ?>
                            <tr>
                                <td>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1 text-muted" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/></svg>
                                    <?php echo htmlspecialchars($name); ?>
                                </td>
                                <td class="text-muted"><?php echo htmlspecialchars((string)$size); ?></td>
                                <td class="text-muted"><?php echo htmlspecialchars((string)$date); ?></td>
                                <td>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="backup_name" value="<?php echo htmlspecialchars($name); ?>">
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                onclick="cmsConfirm({title:'Backup löschen?',message:'<?php echo htmlspecialchars($name); ?> wird unwiderruflich gelöscht.',confirmText:'Löschen',confirmClass:'btn-danger',onConfirm:()=>this.closest('form').submit()})">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="card-body">
                <div class="empty">
                    <p class="empty-title">Keine Backups vorhanden</p>
                    <p class="empty-subtitle text-secondary">Erstellen Sie ein Backup mit den Buttons oben.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Backup-Historie -->
    <?php if (!empty($history)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Backup-Historie</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Typ</th>
                            <th>Status</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $entry): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($entry['date'] ?? $entry['timestamp'] ?? '-'); ?></td>
                                <td><span class="badge"><?php echo htmlspecialchars($entry['type'] ?? '-'); ?></span></td>
                                <td>
                                    <?php if (!empty($entry['success'])): ?>
                                        <span class="badge bg-success-lt">Erfolgreich</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-lt">Fehlgeschlagen</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted"><?php echo htmlspecialchars($entry['message'] ?? $entry['name'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
