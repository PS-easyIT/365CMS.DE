<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
if (!defined('CMS_ADMIN_PERFORMANCE_VIEW')) exit;

$db = $data['database'] ?? [];
$safety = $data['safety']['database'] ?? [];
$tables = $db['top_tables'] ?? [];
$maintenancePlan = $db['maintenance_plan'] ?? [];
$preview = $db['maintenance_preview'] ?? [];
$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2, ',', '.') . ' KB';
    return $bytes . ' B';
};
$hasRollback = !empty($safety['available']) && empty($safety['is_expired']) && empty($safety['rolled_back_at']);
?>
<div class="page-header d-print-none"><div class="container-xl"><div class="row g-2 align-items-center"><div class="col"><div class="page-pretitle">Performance</div><h2 class="page-title">Datenbank-Wartung</h2><div class="text-secondary mt-1">Tabellengrößen, Overhead, Revisionen und Wartungsaktionen für die Datenbank.</div></div></div></div></div>
<div class="page-body"><div class="container-xl">
    <?php if (!empty($alert)): ?><div class="alert alert-<?php echo htmlspecialchars($alert['type'] ?? 'info'); ?> mb-4"><?php echo htmlspecialchars($alert['message'] ?? ''); ?></div><?php endif; ?>
    <?php require __DIR__ . '/subnav.php'; ?>

    <div class="row row-deck row-cards mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="subheader">DB-Größe</div><div class="h1 mb-0"><?php echo htmlspecialchars($formatBytes((int)($db['total_size_bytes'] ?? 0))); ?></div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="subheader">Overhead</div><div class="h1 mb-0 text-warning"><?php echo htmlspecialchars($formatBytes((int)($db['total_overhead_bytes'] ?? 0))); ?></div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="subheader">Revisionen</div><div class="h1 mb-0"><?php echo (int)($db['revision_count'] ?? 0); ?></div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="subheader">Abgelaufene Sitzungen</div><div class="h1 mb-0"><?php echo (int)($db['expired_sessions'] ?? 0); ?></div></div></div></div>
    </div>

    <div class="row row-cards mb-4">
        <div class="col-md-6"><div class="card h-100"><div class="card-header"><h3 class="card-title">Wartungsaktionen</h3></div><div class="card-body d-flex flex-column gap-3"><div class="alert alert-warning mb-0">Tabellenwartung kann je nach Engine Tabellen sperren oder neu aufbauen. Vor jedem OPTIMIZE-/REPAIR-Lauf wird automatisch ein eigenständiges Datenbank-Backup für ein zeitlich begrenztes Rollback erzeugt. Nicht unterstützte Engines werden übersprungen; REPAIR läuft bewusst nicht auf InnoDB.</div><form method="post" data-confirm-title="Tabellen optimieren" data-confirm-message="Datenbanktabellen wirklich optimieren? Vorab wird automatisch ein DB-Backup für ein zeitlich begrenztes Rollback erstellt." data-confirm-text="Optimieren" data-confirm-class="btn-warning" data-confirm-status-class="bg-warning"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>"><input type="hidden" name="action" value="optimize_database"><button type="submit" class="btn btn-primary w-100">Tabellen optimieren</button></form><form method="post" data-confirm-title="Tabellen reparieren" data-confirm-message="Tabellen wirklich prüfen und reparieren? Vorab wird automatisch ein DB-Backup für ein zeitlich begrenztes Rollback erstellt." data-confirm-text="Prüfen & reparieren" data-confirm-class="btn-warning" data-confirm-status-class="bg-warning"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>"><input type="hidden" name="action" value="repair_tables"><button type="submit" class="btn btn-outline-warning w-100">Tabellen reparieren</button></form><?php if ($hasRollback): ?><form method="post" data-confirm-title="Letzten DB-Snapshot zurückrollen" data-confirm-message="Wirklich das letzte vor der Wartung erzeugte DB-Backup wiederherstellen? Vor dem Restore wird automatisch ein weiterer Sicherungs-Snapshot angelegt." data-confirm-text="Rollback starten" data-confirm-class="btn-danger" data-confirm-status-class="bg-danger"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>"><input type="hidden" name="action" value="rollback_database_maintenance"><button type="submit" class="btn btn-outline-danger w-100">Letzte DB-Wartung zurückrollen</button></form><?php endif; ?></div></div></div>
        <div class="col-md-6"><div class="card h-100"><div class="card-header"><h3 class="card-title">Signals</h3></div><div class="card-body"><div class="list-group list-group-flush"><div class="list-group-item px-0 d-flex justify-content-between"><span>Abgelaufene Cache-Einträge</span><strong><?php echo (int)($db['expired_cache_entries'] ?? 0); ?></strong></div><div class="list-group-item px-0 d-flex justify-content-between"><span>Fehlgeschlagene Logins (24h)</span><strong><?php echo (int)($db['failed_logins_last_24h'] ?? 0); ?></strong></div><div class="list-group-item px-0 d-flex justify-content-between"><span>CMS-Tabellen</span><strong><?php echo (int)($db['table_count'] ?? 0); ?></strong></div><div class="list-group-item px-0 d-flex justify-content-between"><span>Tabellen mit Overhead</span><strong><?php echo (int)($preview['tables_with_overhead'] ?? 0); ?></strong></div></div></div></div></div>
    </div>

    <div class="row row-cards mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title">Dry-Run / Auswirkungs-Vorschau</h3></div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-0 d-flex justify-content-between"><span>OPTIMIZE-geeignete Tabellen</span><strong><?php echo (int)($preview['optimize_supported_count'] ?? 0); ?></strong></div>
                        <div class="list-group-item px-0 d-flex justify-content-between"><span>REPAIR-geeignete Tabellen</span><strong><?php echo (int)($preview['repair_supported_count'] ?? 0); ?></strong></div>
                        <div class="list-group-item px-0 d-flex justify-content-between"><span>Bei OPTIMIZE übersprungen</span><strong><?php echo (int)($preview['optimize_skipped_count'] ?? 0); ?></strong></div>
                        <div class="list-group-item px-0 d-flex justify-content-between"><span>Bei REPAIR übersprungen</span><strong><?php echo (int)($preview['repair_skipped_count'] ?? 0); ?></strong></div>
                        <div class="list-group-item px-0 d-flex justify-content-between"><span>Rollback-Fenster</span><strong><?php echo (int) round(((int)($preview['rollback_window_seconds'] ?? 0)) / 60); ?> Min.</strong></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title">Letzter Wartungs-Snapshot</h3></div>
                <div class="card-body">
                    <?php if (!empty($safety['available'])): ?>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item px-0 d-flex justify-content-between"><span>Snapshot-ID</span><code><?php echo htmlspecialchars((string)($safety['snapshot_id'] ?? '')); ?></code></div>
                            <div class="list-group-item px-0 d-flex justify-content-between"><span>DB-Backup</span><strong><?php echo htmlspecialchars((string)($safety['backup_name'] ?? '–')); ?></strong></div>
                            <div class="list-group-item px-0 d-flex justify-content-between"><span>Erstellt am</span><strong><?php echo htmlspecialchars((string)($safety['created_at'] ?? '–')); ?></strong></div>
                            <div class="list-group-item px-0 d-flex justify-content-between"><span>Rollback bis</span><strong><?php echo htmlspecialchars((string)($safety['expires_at'] ?? '–')); ?></strong></div>
                        </div>
                        <div class="mt-3">
                            <?php if (!empty($safety['rolled_back_at'])): ?>
                                <span class="badge bg-success-lt">Bereits zurückgerollt am <?php echo htmlspecialchars((string)$safety['rolled_back_at']); ?></span>
                            <?php elseif (!empty($safety['is_expired'])): ?>
                                <span class="badge bg-secondary-lt">Rollback-Fenster abgelaufen</span>
                            <?php else: ?>
                                <span class="badge bg-warning-lt">Rollback aktiv</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($safety['rollback_backup'])): ?>
                            <div class="text-secondary small mt-2">Vor dem Restore erzeugter Sicherungs-Snapshot: <code><?php echo htmlspecialchars((string)$safety['rollback_backup']); ?></code></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-secondary mb-0">Noch kein Datenbank-Snapshot aus einer Wartungsaktion vorhanden.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4"><div class="card-header"><h3 class="card-title">Größte Tabellen</h3></div><div class="table-responsive"><table class="table table-vcenter card-table table-striped"><thead><tr><th>Tabelle</th><th>Zeilen</th><th>Größe</th><th>Overhead</th></tr></thead><tbody><?php if (empty($tables)): ?><tr><td colspan="4" class="text-center text-secondary py-4">Keine Tabelleninformationen verfügbar.</td></tr><?php else: ?><?php foreach ($tables as $table): ?><tr><td><code><?php echo htmlspecialchars((string)$table['name']); ?></code></td><td><?php echo (int)$table['rows']; ?></td><td><?php echo htmlspecialchars($formatBytes((int)$table['size'])); ?></td><td><?php echo htmlspecialchars($formatBytes((int)$table['overhead'])); ?></td></tr><?php endforeach; ?><?php endif; ?></tbody></table></div></div>

    <div class="card"><div class="card-header"><h3 class="card-title">Wartungsplan nach Engine</h3></div><div class="table-responsive"><table class="table table-vcenter card-table table-striped"><thead><tr><th>Tabelle</th><th>Engine</th><th>Größe</th><th>Overhead</th><th>OPTIMIZE</th><th>REPAIR</th></tr></thead><tbody><?php if (empty($maintenancePlan)): ?><tr><td colspan="6" class="text-center text-secondary py-4">Kein Wartungsplan verfügbar.</td></tr><?php else: ?><?php foreach ($maintenancePlan as $table): ?><tr><td><code><?php echo htmlspecialchars((string)$table['name']); ?></code></td><td><?php echo htmlspecialchars((string)$table['engine']); ?></td><td><?php echo htmlspecialchars($formatBytes((int)$table['size'])); ?></td><td><?php echo htmlspecialchars($formatBytes((int)$table['overhead'])); ?></td><td><span class="badge bg-<?php echo !empty($table['optimize_supported']) ? 'success' : 'secondary'; ?>-lt"><?php echo !empty($table['optimize_supported']) ? 'Ja' : 'Nein'; ?></span></td><td><span class="badge bg-<?php echo !empty($table['repair_supported']) ? 'warning' : 'secondary'; ?>-lt"><?php echo !empty($table['repair_supported']) ? 'Ja' : 'Nein'; ?></span></td></tr><?php endforeach; ?><?php endif; ?></tbody></table></div></div>
</div></div>
