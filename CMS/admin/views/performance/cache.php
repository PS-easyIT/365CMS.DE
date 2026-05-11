<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
if (!defined('CMS_ADMIN_PERFORMANCE_VIEW')) exit;

$cache = $data['cache'] ?? [];
$safety = $data['safety']['cache'] ?? [];
$fileCache = $cache['file_cache'] ?? [];
$apcu = $cache['apcu'] ?? [];
$opcache = $cache['opcache'] ?? [];
$warmup = $opcache['warmup'] ?? [];
$dbCache = $cache['db_cache'] ?? [];
$preview = $cache['purge_preview'] ?? [];
$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2, ',', '.') . ' KB';
    return $bytes . ' B';
};
$formatAge = static function (?int $seconds): string {
    if ($seconds === null) return '–';
    if ($seconds >= 86400) return number_format($seconds / 86400, 1, ',', '.') . ' Tage';
    if ($seconds >= 3600) return number_format($seconds / 3600, 1, ',', '.') . ' Stunden';
    if ($seconds >= 60) return number_format($seconds / 60, 1, ',', '.') . ' Minuten';
    return $seconds . ' Sek.';
};
$hasRollback = !empty($safety['available']) && empty($safety['is_expired']) && empty($safety['rolled_back_at']);
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Performance</div>
                <h2 class="page-title">Cache-Verwaltung</h2>
                <div class="text-secondary mt-1">Datei-Cache, APCu, OPcache und DB-Cache mit gezielten Bereinigungsaktionen.</div>
            </div>
        </div>
    </div>
</div>
<div class="page-body"><div class="container-xl">
    <?php if (!empty($alert)): ?><div class="alert alert-<?php echo htmlspecialchars($alert['type'] ?? 'info'); ?> mb-4"><?php echo htmlspecialchars($alert['message'] ?? ''); ?></div><?php endif; ?>
    <?php require __DIR__ . '/subnav.php'; ?>

    <div class="row row-deck row-cards mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="subheader">Datei-Cache</div><div class="h1 mb-0"><?php echo (int)($fileCache['files'] ?? 0); ?></div><div class="text-secondary"><?php echo htmlspecialchars((string)($fileCache['size'] ?? '0 B')); ?></div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="subheader">APCu Hit-Rate</div><div class="h1 mb-0"><?php echo $apcu['hit_ratio'] !== null ? htmlspecialchars((string)$apcu['hit_ratio']) . '%' : '–'; ?></div><div class="text-secondary"><?php echo (int)($apcu['hits'] ?? 0); ?> Hits / <?php echo (int)($apcu['misses'] ?? 0); ?> Misses</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="subheader">OPcache Scripts</div><div class="h1 mb-0"><?php echo (int)($opcache['cached_scripts'] ?? 0); ?></div><div class="text-secondary"><?php echo !empty($opcache['enabled']) ? 'Aktiv' : 'Inaktiv'; ?></div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="subheader">DB-Cache</div><div class="h1 mb-0"><?php echo (int)($dbCache['active_entries'] ?? 0); ?></div><div class="text-secondary"><?php echo (int)($dbCache['expired_entries'] ?? 0); ?> abgelaufen</div></div></div></div>
    </div>

    <div class="row row-cards mb-4">
        <div class="col-lg-4">
            <div class="card h-100"><div class="card-header"><h3 class="card-title">Schnellaktionen</h3></div><div class="card-body d-flex flex-column gap-3">
                <form method="post" data-confirm-title="Alle Cache-Layer leeren" data-confirm-message="Wirklich Datei-Cache plus flüchtige Runtime-Caches leeren? Vorab wird automatisch ein Datei-Cache-Snapshot für ein zeitlich begrenztes Rollback angelegt." data-confirm-text="Jetzt leeren" data-confirm-class="btn-warning" data-confirm-status-class="bg-warning"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>"><input type="hidden" name="action" value="clear_all_cache"><button type="submit" class="btn btn-primary w-100">Alle Cache-Layer leeren</button></form>
                <form method="post" data-confirm-title="Datei-Cache leeren" data-confirm-message="Datei-Cache wirklich leeren? Vorab wird automatisch ein Snapshot für ein zeitlich begrenztes Rollback angelegt." data-confirm-text="Datei-Cache leeren" data-confirm-class="btn-warning" data-confirm-status-class="bg-warning"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>"><input type="hidden" name="action" value="clear_file_cache"><button type="submit" class="btn btn-outline-primary w-100">Nur Datei-Cache leeren</button></form>
                <form method="post" data-confirm-title="OPcache zurücksetzen" data-confirm-message="OPcache wirklich zurücksetzen? Kompilierte PHP-Skripte werden neu aufgebaut und der Warmup startet anschließend erneut." data-confirm-text="OPcache leeren" data-confirm-class="btn-warning" data-confirm-status-class="bg-warning"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>"><input type="hidden" name="action" value="clear_opcache"><button type="submit" class="btn btn-outline-warning w-100">OPcache zurücksetzen</button></form>
                <form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>"><input type="hidden" name="action" value="warmup_opcache"><button type="submit" class="btn btn-outline-success w-100">Top-30 PHP-Dateien vorwärmen</button></form>
                <?php if ($hasRollback): ?>
                    <form method="post" data-confirm-title="Letzten Cache-Snapshot zurückrollen" data-confirm-message="Wirklich den letzten Datei-Cache-Snapshot zurückspielen? APCu und OPcache bleiben flüchtig und werden nicht aus dem Snapshot wiederhergestellt." data-confirm-text="Rollback starten" data-confirm-class="btn-danger" data-confirm-status-class="bg-danger"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>"><input type="hidden" name="action" value="rollback_cache_cleanup"><button type="submit" class="btn btn-outline-danger w-100">Letzte Cache-Bereinigung zurückrollen</button></form>
                <?php endif; ?>
            </div></div>
        </div>
        <div class="col-lg-8">
            <div class="card h-100"><div class="card-header"><h3 class="card-title">Cache-Status</h3></div><div class="table-responsive"><table class="table table-vcenter card-table"><tbody>
                <tr><td class="text-muted w-50">Cache-Verzeichnis</td><td class="text-break"><?php echo htmlspecialchars((string)($fileCache['directory'] ?? '')); ?></td></tr>
                <tr><td class="text-muted">Beschreibbar</td><td><span class="badge bg-<?php echo !empty($fileCache['writable']) ? 'success' : 'danger'; ?>-lt"><?php echo !empty($fileCache['writable']) ? 'Ja' : 'Nein'; ?></span></td></tr>
                <tr><td class="text-muted">Älteste Cache-Datei</td><td><?php echo htmlspecialchars($formatAge($fileCache['oldest_age'] ?? null)); ?></td></tr>
                <tr><td class="text-muted">Jüngste Cache-Datei</td><td><?php echo htmlspecialchars($formatAge($fileCache['newest_age'] ?? null)); ?></td></tr>
                <tr><td class="text-muted">OPcache Speicher</td><td><?php echo htmlspecialchars($formatBytes((int)($opcache['used_memory'] ?? 0))); ?> genutzt / <?php echo htmlspecialchars($formatBytes((int)($opcache['free_memory'] ?? 0))); ?> frei</td></tr>
                <tr><td class="text-muted">Warmup-Status</td><td><span class="badge bg-<?php echo !empty($warmup['is_current']) ? 'success' : 'warning'; ?>-lt"><?php echo !empty($warmup['is_current']) ? 'Aktuell' : 'Offen'; ?></span><?php if (!empty($warmup['last_generated_at'])): ?> <span class="text-secondary">letzter Lauf: <?php echo htmlspecialchars((string)$warmup['last_generated_at']); ?></span><?php endif; ?></td></tr>
                <tr><td class="text-muted">Warmup-Kandidaten</td><td><?php echo (int)($warmup['candidate_count'] ?? 0); ?> Dateien / zuletzt kompiliert: <?php echo (int)($warmup['last_compiled'] ?? 0); ?> / Fehlversuche: <?php echo (int)($warmup['last_failed_count'] ?? 0); ?></td></tr>
            </tbody></table></div></div>
        </div>
    </div>

    <div class="row row-cards mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title">Dry-Run / Auswirkungs-Vorschau</h3></div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-0 d-flex justify-content-between"><span>Datei-Cache-Dateien</span><strong><?php echo (int)($preview['file_count'] ?? 0); ?></strong></div>
                        <div class="list-group-item px-0 d-flex justify-content-between"><span>Wiederherstellbare Dateigröße</span><strong><?php echo htmlspecialchars($formatBytes((int)($preview['file_size_bytes'] ?? 0))); ?></strong></div>
                        <div class="list-group-item px-0 d-flex justify-content-between"><span>APCu-Reset bei „Alle Cache-Layer“</span><strong><?php echo !empty($preview['apcu_reset']) ? 'Ja' : 'Nein'; ?></strong></div>
                        <div class="list-group-item px-0 d-flex justify-content-between"><span>OPcache-Reset bei „Alle Cache-Layer“</span><strong><?php echo !empty($preview['opcache_reset']) ? 'Ja' : 'Nein'; ?></strong></div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0">
                        Vor jedem Datei-/Gesamt-Purge wird jetzt automatisch ein Snapshot der aktuell vorhandenen Datei-Cache-Dateien erzeugt. APCu und OPcache bleiben absichtlich flüchtig und werden nach einem Rollback regulär neu aufgebaut.
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title">Rollback-Fenster</h3></div>
                <div class="card-body">
                    <?php if (!empty($safety['available'])): ?>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item px-0 d-flex justify-content-between"><span>Snapshot-ID</span><code><?php echo htmlspecialchars((string)($safety['snapshot_id'] ?? '')); ?></code></div>
                            <div class="list-group-item px-0 d-flex justify-content-between"><span>Erstellt am</span><strong><?php echo htmlspecialchars((string)($safety['created_at'] ?? '–')); ?></strong></div>
                            <div class="list-group-item px-0 d-flex justify-content-between"><span>Rollback bis</span><strong><?php echo htmlspecialchars((string)($safety['expires_at'] ?? '–')); ?></strong></div>
                            <div class="list-group-item px-0 d-flex justify-content-between"><span>Snapshot-Dateien</span><strong><?php echo (int)($safety['file_count'] ?? 0); ?></strong></div>
                            <div class="list-group-item px-0 d-flex justify-content-between"><span>Snapshot-Größe</span><strong><?php echo htmlspecialchars($formatBytes((int)($safety['file_size_bytes'] ?? 0))); ?></strong></div>
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
                    <?php else: ?>
                        <p class="text-secondary mb-0">Noch kein Snapshot aus einer Cache-Bereinigung vorhanden.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div></div>
