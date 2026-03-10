<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_SYSTEM_VIEW')) {
    exit;
}

$database    = $data['database'] ?? [];
$tables      = $data['tables'] ?? [];
$permissions = $data['permissions'] ?? [];
$runtime     = $data['runtime'] ?? [];

$missingCount = 0;
$errorCount = 0;
foreach ($tables as $tableInfo) {
    if (!is_array($tableInfo)) {
        continue;
    }

    if (empty($tableInfo['exists'])) {
        $missingCount++;
    }

    $status = (string)($tableInfo['status'] ?? 'OK');
    if (!in_array(strtolower($status), ['ok', 'missing'], true)) {
        $errorCount++;
    }
}
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Info &amp; Diagnose</div>
                <h2 class="page-title">Diagnose</h2>
                <div class="text-secondary mt-1">Wartungsaktionen, Tabellenprüfung und technische Diagnosewerkzeuge für das System.</div>
            </div>
            <div class="col-auto d-flex gap-2 flex-wrap">
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                    <input type="hidden" name="action" value="clear_cache">
                    <button type="submit" class="btn btn-outline-warning">Cache leeren</button>
                </form>
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                    <input type="hidden" name="action" value="optimize_db">
                    <button type="submit" class="btn btn-outline-primary">DB optimieren</button>
                </form>
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                    <input type="hidden" name="action" value="clear_logs">
                    <button type="submit" class="btn btn-outline-danger">Logs leeren</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if (!empty($alert)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert['type'] ?? 'info'); ?> mb-4" role="alert">
                <?php echo htmlspecialchars($alert['message'] ?? ''); ?>
            </div>
        <?php endif; ?>

        <?php require __DIR__ . '/subnav.php'; ?>

        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">DB-Status</div><div class="h1 mb-0 <?php echo !empty($database['connected']) ? 'text-success' : 'text-danger'; ?>"><?php echo !empty($database['connected']) ? 'Online' : 'Offline'; ?></div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Fehlende Tabellen</div><div class="h1 mb-0 text-danger"><?php echo $missingCount; ?></div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Tabellen mit Problemen</div><div class="h1 mb-0 text-warning"><?php echo $errorCount; ?></div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">CMS-Tabellen</div><div class="h1 mb-0"><?php echo htmlspecialchars((string)($database['cms_tables'] ?? '-')); ?></div></div></div></div>
        </div>

        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Runtime-Telemetrie</div><div class="h1 mb-0 <?php echo !empty($runtime['enabled']) ? 'text-success' : 'text-muted'; ?>"><?php echo !empty($runtime['enabled']) ? 'Debug aktiv' : 'Inaktiv'; ?></div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Request-Laufzeit</div><div class="h1 mb-0"><?php echo htmlspecialchars((string)($runtime['elapsed_time_ms'] ?? '0')); ?> <span class="fs-5 text-secondary">ms</span></div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">SQL-Queries</div><div class="h1 mb-0"><?php echo htmlspecialchars((string)($runtime['query']['count'] ?? 0)); ?></div><div class="text-secondary small mt-1"><?php echo htmlspecialchars((string)($runtime['query']['total_time_ms'] ?? 0)); ?> ms gesamt</div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Messpunkte</div><div class="h1 mb-0"><?php echo htmlspecialchars((string)count($runtime['checkpoints'] ?? [])); ?></div><div class="text-secondary small mt-1">Peak <?php echo htmlspecialchars((string)($runtime['memory_peak_mb'] ?? 0)); ?> MB</div></div></div></div>
        </div>

        <div class="row row-cards">
            <div class="col-12 col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Datenbank-Tools</h3></div>
                    <div class="card-body d-flex flex-column gap-3">
                        <div>
                            <div class="fw-semibold">Schema prüfen</div>
                            <div class="text-secondary small">Erkennt fehlende Tabellen und stößt bei Bedarf die Schema-Erstellung erneut an.</div>
                        </div>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                            <input type="hidden" name="action" value="create_tables">
                            <button type="submit" class="btn btn-outline-success w-100" <?php echo $missingCount === 0 ? 'disabled' : ''; ?>>Fehlende Tabellen erstellen</button>
                        </form>
                        <form method="post" onsubmit="return confirm('Tabellen-Reparatur starten?');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                            <input type="hidden" name="action" value="repair_tables">
                            <button type="submit" class="btn btn-outline-warning w-100">Tabellen reparieren</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Dateiberechtigungen</h3></div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead><tr><th>Pfad</th><th>Lesbar</th><th>Schreibbar</th></tr></thead>
                            <tbody>
                                <?php if (empty($permissions)): ?>
                                    <tr><td colspan="3" class="text-center text-secondary py-4">Keine Berechtigungsdaten verfügbar.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($permissions as $permission): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars((string)($permission['path'] ?? $permission['full_path'] ?? '')); ?></td>
                                            <td><span class="badge bg-<?php echo !empty($permission['readable']) ? 'success' : 'danger'; ?>-lt"><?php echo !empty($permission['readable']) ? 'Ja' : 'Nein'; ?></span></td>
                                            <td><span class="badge bg-<?php echo !empty($permission['writable']) ? 'success' : 'danger'; ?>-lt"><?php echo !empty($permission['writable']) ? 'Ja' : 'Nein'; ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header"><h3 class="card-title">Runtime- &amp; Query-Telemetrie</h3></div>
                    <div class="card-body">
                        <?php if (empty($runtime['enabled'])): ?>
                            <div class="text-secondary">Debug-Modus ist derzeit nicht aktiv. Die zusätzlichen Query- und Laufzeitdaten werden nur bei aktiviertem `CMS_DEBUG` gesammelt.</div>
                        <?php else: ?>
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <div class="table-responsive">
                                        <table class="table table-vcenter card-table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Messpunkt</th>
                                                    <th>Zeit</th>
                                                    <th>RAM</th>
                                                    <th>Kontext</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (($runtime['checkpoints'] ?? []) as $checkpoint): ?>
                                                    <tr>
                                                        <td><code><?php echo htmlspecialchars((string)($checkpoint['label'] ?? '')); ?></code></td>
                                                        <td><?php echo htmlspecialchars((string)($checkpoint['time_ms'] ?? '0')); ?> ms</td>
                                                        <td><?php echo htmlspecialchars((string)($checkpoint['memory_mb'] ?? '0')); ?> MB</td>
                                                        <td>
                                                            <?php if (!empty($checkpoint['context']) && is_array($checkpoint['context'])): ?>
                                                                <code><?php echo htmlspecialchars(json_encode($checkpoint['context'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '', ENT_QUOTES); ?></code>
                                                            <?php else: ?>
                                                                <span class="text-secondary">—</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3 text-secondary small">
                                        Langsame Queries ab <?php echo htmlspecialchars((string)($runtime['query']['slow_threshold_ms'] ?? 0)); ?> ms: <?php echo htmlspecialchars((string)($runtime['query']['slow_count'] ?? 0)); ?>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-vcenter card-table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Dauer</th>
                                                    <th>SQL</th>
                                                    <th>Parameter</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (($runtime['query']['queries'] ?? []) as $queryInfo): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars((string)($queryInfo['execution_time_ms'] ?? '0')); ?> ms</td>
                                                        <td><code><?php echo htmlspecialchars((string)($queryInfo['sql'] ?? '')); ?></code></td>
                                                        <td>
                                                            <?php if (array_key_exists('params', $queryInfo) && $queryInfo['params'] !== null): ?>
                                                                <code><?php echo htmlspecialchars(json_encode($queryInfo['params'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '', ENT_QUOTES); ?></code>
                                                            <?php else: ?>
                                                                <span class="text-secondary">—</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Datenbank-Tabellen</h3></div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-striped">
                            <thead>
                                <tr>
                                    <th>Tabelle</th>
                                    <th>Existiert</th>
                                    <th>Zeilen</th>
                                    <th>Größe</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tables)): ?>
                                    <tr><td colspan="5" class="text-center text-secondary py-4">Keine Tabelleninformationen verfügbar.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($tables as $name => $info): ?>
                                        <?php if (!is_array($info)) continue; ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars(is_string($name) ? $name : (string)($info['name'] ?? '')); ?></code></td>
                                            <td><span class="badge bg-<?php echo !empty($info['exists']) ? 'success' : 'danger'; ?>-lt"><?php echo !empty($info['exists']) ? 'Ja' : 'Nein'; ?></span></td>
                                            <td><?php echo htmlspecialchars((string)($info['rows'] ?? '-')); ?></td>
                                            <td><?php echo htmlspecialchars((string)($info['size'] ?? '-')); ?></td>
                                            <td>
                                                <?php $status = (string)($info['status'] ?? 'unknown'); ?>
                                                <span class="badge bg-<?php echo strtolower($status) === 'ok' ? 'success' : 'warning'; ?>-lt"><?php echo htmlspecialchars($status); ?></span>
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
    </div>
</div>
