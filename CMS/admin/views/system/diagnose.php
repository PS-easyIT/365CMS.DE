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
$bootstrapProfile = is_array($runtime['bootstrap'] ?? null) ? $runtime['bootstrap'] : [];
$vendorRegistry = $data['vendor_registry'] ?? [];
$registrySummary = is_array($vendorRegistry['summary'] ?? null) ? $vendorRegistry['summary'] : [];
$autoloadDiagnostics = is_array($vendorRegistry['autoload'] ?? null) ? $vendorRegistry['autoload'] : [];
$managedPackages = is_array($vendorRegistry['packages'] ?? null) ? $vendorRegistry['packages'] : [];
$bundledLibraries = is_array($vendorRegistry['bundles'] ?? null) ? $vendorRegistry['bundles'] : [];
$platformDiagnostics = is_array($vendorRegistry['platform'] ?? null) ? $vendorRegistry['platform'] : [];

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
            <?php
            $runtimeActive = !empty($runtime['enabled']) || !empty($runtime['profile_active']) || !empty($bootstrapProfile['active']);
            $runtimeLabel = !empty($runtime['enabled']) ? 'Debug aktiv' : ($runtimeActive ? 'Profil aktiv' : 'Inaktiv');
            ?>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Runtime-Telemetrie</div><div class="h1 mb-0 <?php echo $runtimeActive ? 'text-success' : 'text-muted'; ?>"><?php echo htmlspecialchars($runtimeLabel); ?></div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Request-Laufzeit</div><div class="h1 mb-0"><?php echo htmlspecialchars((string)($runtime['elapsed_time_ms'] ?? '0')); ?> <span class="fs-5 text-secondary">ms</span></div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">SQL-Queries</div><div class="h1 mb-0"><?php echo htmlspecialchars((string)($runtime['query']['count'] ?? 0)); ?></div><div class="text-secondary small mt-1"><?php echo htmlspecialchars((string)($runtime['query']['total_time_ms'] ?? 0)); ?> ms gesamt</div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Bootstrap-Modus</div><div class="h1 mb-0"><?php echo htmlspecialchars((string)($bootstrapProfile['mode'] ?? '—')); ?></div><div class="text-secondary small mt-1"><?php echo htmlspecialchars((string)($bootstrapProfile['bootstrap_ready_ms'] ?? 0)); ?> ms bis ready</div></div></div></div>
        </div>

        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Registry-Pakete</div><div class="h1 mb-0"><?php echo htmlspecialchars((string)($registrySummary['managed_loaded'] ?? 0)); ?><span class="fs-5 text-secondary"> / <?php echo htmlspecialchars((string)($registrySummary['managed_total'] ?? 0)); ?></span></div><div class="text-secondary small mt-1">geladen via VendorRegistry</div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Assets-Autoloader</div><div class="h1 mb-0 <?php echo !empty($autoloadDiagnostics['loaded']) ? 'text-success' : 'text-danger'; ?>"><?php echo !empty($autoloadDiagnostics['loaded']) ? 'Aktiv' : 'Inaktiv'; ?></div><div class="text-secondary small mt-1"><?php echo htmlspecialchars((string)($autoloadDiagnostics['active_path'] ?? 'Kein aktiver Pfad')); ?></div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Runtime-Bundles</div><div class="h1 mb-0"><?php echo htmlspecialchars((string)($registrySummary['bundle_ready'] ?? 0)); ?><span class="fs-5 text-secondary"> / <?php echo htmlspecialchars((string)($registrySummary['bundle_total'] ?? 0)); ?></span></div><div class="text-secondary small mt-1">auflösbare Asset-Libraries</div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Plattform-Warnungen</div><div class="h1 mb-0 <?php echo ((int)($registrySummary['platform_warning_count'] ?? 0) > 0) ? 'text-warning' : 'text-success'; ?>"><?php echo htmlspecialchars((string)($registrySummary['platform_warning_count'] ?? 0)); ?></div><div class="text-secondary small mt-1">Composer-Manifeste gegen CMS/PHP</div></div></div></div>
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
                    <div class="card-header"><h3 class="card-title">Vendor- &amp; Asset-Registry</h3></div>
                    <div class="card-body">
                        <?php if (!empty($vendorRegistry['error'])): ?>
                            <div class="alert alert-warning" role="alert">
                                <?php echo htmlspecialchars((string)$vendorRegistry['error']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="row g-4">
                            <div class="col-12 col-xl-4">
                                <div class="border rounded-3 h-100 p-3">
                                    <div class="fw-semibold mb-2">Autoloader-Kandidaten</div>
                                    <div class="text-secondary small mb-3">Zeigt, welcher produktive oder lokale Fallback-Autoloader aktuell gefunden wurde.</div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-vcenter mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Pfad</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($autoloadDiagnostics['candidates']) || !is_array($autoloadDiagnostics['candidates'])): ?>
                                                    <tr><td colspan="2" class="text-center text-secondary py-3">Keine Kandidaten ermittelt.</td></tr>
                                                <?php else: ?>
                                                    <?php foreach ($autoloadDiagnostics['candidates'] as $candidate): ?>
                                                        <tr>
                                                            <td><code><?php echo htmlspecialchars((string)($candidate['path'] ?? '')); ?></code></td>
                                                            <td>
                                                                <?php if (!empty($candidate['active'])): ?>
                                                                    <span class="badge bg-success-lt">aktiv</span>
                                                                <?php elseif (!empty($candidate['exists'])): ?>
                                                                    <span class="badge bg-primary-lt">gefunden</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-danger-lt">fehlt</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-xl-4">
                                <div class="border rounded-3 h-100 p-3">
                                    <div class="fw-semibold mb-2">Registrierte Produktivpakete</div>
                                    <div class="text-secondary small mb-3">Zentrale Registry-Einträge, die Bundle-Sonderpfade und den Assets-Autoloader kapseln.</div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-vcenter mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Paket</th>
                                                    <th>Verfügbar</th>
                                                    <th>Geladen</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($managedPackages === []): ?>
                                                    <tr><td colspan="3" class="text-center text-secondary py-3">Keine Registry-Pakete vorhanden.</td></tr>
                                                <?php else: ?>
                                                    <?php foreach ($managedPackages as $package): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="fw-semibold"><?php echo htmlspecialchars((string)($package['label'] ?? $package['package'] ?? '')); ?></div>
                                                                <div class="text-secondary small"><code><?php echo htmlspecialchars((string)($package['path'] ?? '')); ?></code></div>
                                                            </td>
                                                            <td><span class="badge bg-<?php echo !empty($package['available']) ? 'success' : 'danger'; ?>-lt"><?php echo !empty($package['available']) ? 'Ja' : 'Nein'; ?></span></td>
                                                            <td><span class="badge bg-<?php echo !empty($package['loaded']) ? 'success' : 'secondary'; ?>-lt"><?php echo !empty($package['loaded']) ? 'Ja' : 'Nein'; ?></span></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-xl-4">
                                <div class="border rounded-3 h-100 p-3">
                                    <div class="fw-semibold mb-2">Gebündelte Runtime-Libraries</div>
                                    <div class="text-secondary small mb-3">Direkt aus `CMS/assets/autoload.php` abgeleitete Asset-/Vendor-Bundles mit Verfügbarkeits- und Laufzeitstatus.</div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-vcenter mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Library</th>
                                                    <th>Datei</th>
                                                    <th>Runtime</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($bundledLibraries === []): ?>
                                                    <tr><td colspan="3" class="text-center text-secondary py-3">Keine Asset-Libraries erkannt.</td></tr>
                                                <?php else: ?>
                                                    <?php foreach ($bundledLibraries as $library): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="fw-semibold"><?php echo htmlspecialchars((string)($library['label'] ?? $library['package'] ?? '')); ?></div>
                                                                <div class="text-secondary small"><?php echo htmlspecialchars((string)($library['notes'] ?? '')); ?></div>
                                                            </td>
                                                            <td><span class="badge bg-<?php echo !empty($library['available']) ? 'success' : 'danger'; ?>-lt"><?php echo !empty($library['available']) ? 'vorhanden' : 'fehlt'; ?></span></td>
                                                            <td><span class="badge bg-<?php echo !empty($library['runtime_ready']) ? 'success' : 'secondary'; ?>-lt"><?php echo !empty($library['runtime_ready']) ? 'auflösbar' : 'nicht aufgelöst'; ?></span></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4 mt-1">
                            <div class="col-12">
                                <div class="border rounded-3 p-3">
                                    <div class="fw-semibold mb-2">Bundle-Plattformprüfung</div>
                                    <div class="text-secondary small mb-3">Vergleicht die Composer-Manifeste der Symfony-Bundles mit der offiziellen CMS-Mindestplattform und der aktiven PHP-Runtime.</div>
                                    <div class="table-responsive">
                                        <table class="table table-vcenter table-striped mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Paket</th>
                                                    <th>Manifest</th>
                                                    <th>Bundle-PHP</th>
                                                    <th>CMS-Minimum</th>
                                                    <th>Runtime</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($platformDiagnostics === []): ?>
                                                    <tr><td colspan="6" class="text-center text-secondary py-3">Keine Manifestdaten vorhanden.</td></tr>
                                                <?php else: ?>
                                                    <?php foreach ($platformDiagnostics as $entry): ?>
                                                        <?php
                                                        $cmsCompatible = $entry['cms_compatible'] ?? null;
                                                        $runtimeCompatible = $entry['runtime_compatible'] ?? null;
                                                        $statusClass = ($cmsCompatible === false || $runtimeCompatible === false) ? 'warning' : 'success';
                                                        $statusLabel = ($cmsCompatible === false || $runtimeCompatible === false) ? 'Prüfen' : 'OK';
                                                        if (empty($entry['exists'])) {
                                                            $statusClass = 'secondary';
                                                            $statusLabel = 'Fehlt';
                                                        }
                                                        ?>
                                                        <tr>
                                                            <td><code><?php echo htmlspecialchars((string)($entry['package'] ?? '')); ?></code></td>
                                                            <td><code><?php echo htmlspecialchars((string)($entry['manifest'] ?? '')); ?></code></td>
                                                            <td><?php echo htmlspecialchars((string)($entry['required_php'] ?? '—')); ?></td>
                                                            <td><?php echo htmlspecialchars((string)($entry['cms_required_php'] ?? '—')); ?></td>
                                                            <td><?php echo htmlspecialchars((string)($entry['runtime_php'] ?? '—')); ?></td>
                                                            <td><span class="badge bg-<?php echo $statusClass; ?>-lt"><?php echo htmlspecialchars($statusLabel); ?></span></td>
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
            </div>

            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header"><h3 class="card-title">Runtime- &amp; Query-Telemetrie</h3></div>
                    <div class="card-body">
                        <?php if (empty($runtime['enabled']) && empty($bootstrapProfile['active'])): ?>
                            <div class="text-secondary">Debug-Modus ist derzeit nicht aktiv. Ohne Profil- oder Debug-Laufzeitdaten können hier keine Bootstrap- oder Query-Messwerte gezeigt werden.</div>
                        <?php else: ?>
                            <div class="card border-0 shadow-none mb-4">
                                <div class="card-header px-0 pt-0"><h3 class="card-title">Bootstrap-Profil</h3></div>
                                <div class="card-body px-0 pb-0">
                                    <div class="row row-deck row-cards mb-3">
                                        <div class="col-md-3"><div class="card"><div class="card-body"><div class="subheader">Kaltstart bis Ready</div><div class="h2 mb-0"><?php echo htmlspecialchars((string)($bootstrapProfile['bootstrap_ready_ms'] ?? '0')); ?> <span class="fs-5 text-secondary">ms</span></div></div></div></div>
                                        <div class="col-md-3"><div class="card"><div class="card-body"><div class="subheader">Nach Bootstrap</div><div class="h2 mb-0"><?php echo htmlspecialchars((string)($bootstrapProfile['post_bootstrap_ms'] ?? '0')); ?> <span class="fs-5 text-secondary">ms</span></div></div></div></div>
                                        <div class="col-md-3"><div class="card"><div class="card-body"><div class="subheader">Cold-Path-Anteil</div><div class="h2 mb-0"><?php echo htmlspecialchars((string)($bootstrapProfile['cold_path_share_percent'] ?? '0')); ?> <span class="fs-5 text-secondary">%</span></div></div></div></div>
                                        <div class="col-md-3"><div class="card"><div class="card-body"><div class="subheader">Langsame Phasen</div><div class="h2 mb-0"><?php echo htmlspecialchars((string)($bootstrapProfile['slow_phase_count'] ?? 0)); ?></div><div class="text-secondary small mt-1">ab <?php echo htmlspecialchars((string)($bootstrapProfile['slow_phase_threshold_ms'] ?? 0)); ?> ms</div></div></div></div>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-lg-4">
                                            <div class="border rounded-3 h-100 p-3">
                                                <div class="fw-semibold mb-2">Profil-Kontext</div>
                                                <div class="small text-secondary mb-2">Getrennte Sicht auf CLI/API/Admin/Web statt reiner Bauchgefühl-Optimierung.</div>
                                                <dl class="row mb-0">
                                                    <dt class="col-5 text-secondary">Modus</dt><dd class="col-7 mb-2"><?php echo htmlspecialchars((string)($bootstrapProfile['mode'] ?? '—')); ?></dd>
                                                    <dt class="col-5 text-secondary">Methode</dt><dd class="col-7 mb-2"><?php echo htmlspecialchars((string)($bootstrapProfile['request_method'] ?? '—')); ?></dd>
                                                    <dt class="col-5 text-secondary">SAPI</dt><dd class="col-7 mb-2"><?php echo htmlspecialchars((string)($bootstrapProfile['sapi'] ?? '—')); ?></dd>
                                                    <dt class="col-5 text-secondary">Pfad</dt><dd class="col-7 mb-0 text-break"><code><?php echo htmlspecialchars((string)($bootstrapProfile['request_uri'] ?? '—')); ?></code></dd>
                                                </dl>
                                            </div>
                                        </div>
                                        <div class="col-lg-8">
                                            <div class="border rounded-3 h-100 p-3">
                                                <div class="fw-semibold mb-2">Teuerste Bootstrap-Phasen</div>
                                                <div class="text-secondary small mb-3">Zeigt die größten Segmente zwischen den Bootstrap-Checkpoints und macht Cold-Path-Kosten transparent.</div>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-vcenter mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>Von</th>
                                                                <th>Nach</th>
                                                                <th>Dauer</th>
                                                                <th>RAM</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if (empty($bootstrapProfile['top_phases'])): ?>
                                                                <tr><td colspan="4" class="text-center text-secondary py-3">Noch keine Bootstrap-Phasen gemessen.</td></tr>
                                                            <?php else: ?>
                                                                <?php foreach (($bootstrapProfile['top_phases'] ?? []) as $phase): ?>
                                                                    <tr>
                                                                        <td><code><?php echo htmlspecialchars((string)($phase['from'] ?? '')); ?></code></td>
                                                                        <td><code><?php echo htmlspecialchars((string)($phase['to'] ?? '')); ?></code></td>
                                                                        <td><?php echo htmlspecialchars((string)($phase['delta_ms'] ?? '0')); ?> ms</td>
                                                                        <td><?php echo htmlspecialchars((string)($phase['memory_mb'] ?? '0')); ?> MB</td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="table-responsive mb-4">
                                        <table class="table table-vcenter card-table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Von</th>
                                                    <th>Nach</th>
                                                    <th>Dauer</th>
                                                    <th>Gesamtzeit</th>
                                                    <th>RAM</th>
                                                    <th>Kontext</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($bootstrapProfile['phases'])): ?>
                                                    <tr><td colspan="6" class="text-center text-secondary py-4">Keine Bootstrap-Phasen vorhanden.</td></tr>
                                                <?php else: ?>
                                                    <?php foreach (($bootstrapProfile['phases'] ?? []) as $phase): ?>
                                                        <tr>
                                                            <td><code><?php echo htmlspecialchars((string)($phase['from'] ?? '')); ?></code></td>
                                                            <td><code><?php echo htmlspecialchars((string)($phase['to'] ?? '')); ?></code></td>
                                                            <td><?php echo htmlspecialchars((string)($phase['delta_ms'] ?? '0')); ?> ms</td>
                                                            <td><?php echo htmlspecialchars((string)($phase['time_ms'] ?? '0')); ?> ms</td>
                                                            <td><?php echo htmlspecialchars((string)($phase['memory_mb'] ?? '0')); ?> MB</td>
                                                            <td>
                                                                <?php if (!empty($phase['context']) && is_array($phase['context'])): ?>
                                                                    <code><?php echo htmlspecialchars(json_encode($phase['context'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '', ENT_QUOTES); ?></code>
                                                                <?php else: ?>
                                                                    <span class="text-secondary">—</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <?php if (empty($runtime['enabled'])): ?>
                                        <div class="alert alert-info" role="alert">
                                            Debug ist aktuell aus. Das Bootstrap-Profil läuft leichtgewichtig weiter, detaillierte SQL-Queries und Debug-Logs erscheinen jedoch nur mit aktiviertem <code>CMS_DEBUG</code>.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (!empty($runtime['enabled'])): ?>
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
