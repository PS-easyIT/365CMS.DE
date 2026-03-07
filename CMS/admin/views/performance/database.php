<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$db = $data['database'] ?? [];
$tables = $db['top_tables'] ?? [];
$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2, ',', '.') . ' KB';
    return $bytes . ' B';
};
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
        <div class="col-md-6"><div class="card h-100"><div class="card-header"><h3 class="card-title">Wartungsaktionen</h3></div><div class="card-body d-flex flex-column gap-3"><form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>"><input type="hidden" name="action" value="optimize_database"><button type="submit" class="btn btn-primary w-100">Tabellen optimieren</button></form><form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>"><input type="hidden" name="action" value="repair_tables"><button type="submit" class="btn btn-outline-warning w-100">Tabellen reparieren</button></form></div></div></div>
        <div class="col-md-6"><div class="card h-100"><div class="card-header"><h3 class="card-title">Signals</h3></div><div class="card-body"><div class="list-group list-group-flush"><div class="list-group-item px-0 d-flex justify-content-between"><span>Abgelaufene Cache-Einträge</span><strong><?php echo (int)($db['expired_cache_entries'] ?? 0); ?></strong></div><div class="list-group-item px-0 d-flex justify-content-between"><span>Fehlgeschlagene Logins (24h)</span><strong><?php echo (int)($db['failed_logins_last_24h'] ?? 0); ?></strong></div><div class="list-group-item px-0 d-flex justify-content-between"><span>CMS-Tabellen</span><strong><?php echo (int)($db['table_count'] ?? 0); ?></strong></div></div></div></div></div>
    </div>

    <div class="card"><div class="card-header"><h3 class="card-title">Größte Tabellen</h3></div><div class="table-responsive"><table class="table table-vcenter card-table table-striped"><thead><tr><th>Tabelle</th><th>Zeilen</th><th>Größe</th><th>Overhead</th></tr></thead><tbody><?php if (empty($tables)): ?><tr><td colspan="4" class="text-center text-secondary py-4">Keine Tabelleninformationen verfügbar.</td></tr><?php else: ?><?php foreach ($tables as $table): ?><tr><td><code><?php echo htmlspecialchars((string)$table['name']); ?></code></td><td><?php echo (int)$table['rows']; ?></td><td><?php echo htmlspecialchars($formatBytes((int)$table['size'])); ?></td><td><?php echo htmlspecialchars($formatBytes((int)$table['overhead'])); ?></td></tr><?php endforeach; ?><?php endif; ?></tbody></table></div></div>
</div></div>
