<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var array $data System-Daten
 * @var string $csrfToken CSRF-Token
 */

$system      = $data['system'];
$database    = $data['database'];
$tables      = $data['tables'];
$permissions = $data['permissions'];
$directories = $data['directories'];
$statistics  = $data['statistics'];
$security    = $data['security'];
?>

<div class="container-xl">
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-info-circle me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/><path d="M12 9h.01"/><path d="M11 12h1v4h1"/></svg>
                    System-Info & Diagnose
                </h2>
            </div>
            <div class="col-auto d-flex gap-2">
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="clear_cache">
                    <button type="submit" class="btn btn-outline-warning">Cache leeren</button>
                </form>
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="optimize_db">
                    <button type="submit" class="btn btn-outline-primary">DB optimieren</button>
                </form>
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="clear_logs">
                    <button type="submit" class="btn btn-outline-danger">Logs leeren</button>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Server-Info -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Server-Informationen</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <tbody>
                            <tr><td class="text-muted w-25">CMS-Version</td><td><strong><?php echo htmlspecialchars(defined('CMS_VERSION') ? CMS_VERSION : '-'); ?></strong></td></tr>
                            <tr><td class="text-muted">PHP-Version</td><td><?php echo htmlspecialchars($system['php_version'] ?? PHP_VERSION); ?></td></tr>
                            <tr><td class="text-muted">Webserver</td><td><?php echo htmlspecialchars($system['server_software'] ?? ($_SERVER['SERVER_SOFTWARE'] ?? '-')); ?></td></tr>
                            <tr><td class="text-muted">Betriebssystem</td><td><?php echo htmlspecialchars($system['os'] ?? PHP_OS); ?></td></tr>
                            <tr><td class="text-muted">Speicherlimit</td><td><?php echo htmlspecialchars($system['memory_limit'] ?? ini_get('memory_limit')); ?></td></tr>
                            <tr><td class="text-muted">Max. Upload</td><td><?php echo htmlspecialchars($system['upload_max_filesize'] ?? ini_get('upload_max_filesize')); ?></td></tr>
                            <tr><td class="text-muted">Max. POST</td><td><?php echo htmlspecialchars($system['post_max_size'] ?? ini_get('post_max_size')); ?></td></tr>
                            <tr><td class="text-muted">Max. Laufzeit</td><td><?php echo htmlspecialchars(($system['max_execution_time'] ?? ini_get('max_execution_time')) . 's'); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Datenbank -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Datenbank</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <tbody>
                            <tr>
                                <td class="text-muted w-25">Status</td>
                                <td>
                                    <?php if (!empty($database['connected'])): ?>
                                        <span class="badge bg-success-lt">Verbunden</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-lt">Nicht verbunden</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr><td class="text-muted">DB-Größe</td><td><?php echo htmlspecialchars((string)($database['database_size'] ?? '-')); ?></td></tr>
                            <tr><td class="text-muted">Tabellen (gesamt)</td><td><?php echo htmlspecialchars((string)($database['total_tables'] ?? '-')); ?></td></tr>
                            <tr><td class="text-muted">CMS-Tabellen</td><td><?php echo htmlspecialchars((string)($database['cms_tables'] ?? '-')); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Statistiken -->
        <?php if (!empty($statistics)): ?>
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">CMS-Statistiken</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <tbody>
                            <?php foreach ($statistics as $key => $value): ?>
                                <tr>
                                    <td class="text-muted w-50"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key))); ?></td>
                                    <td><strong><?php echo htmlspecialchars((string)$value); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Sicherheit -->
        <?php if (!empty($security)): ?>
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Sicherheitsstatus</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <tbody>
                            <?php foreach ($security as $key => $value): ?>
                                <tr>
                                    <td class="text-muted w-50"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key))); ?></td>
                                    <td>
                                        <?php if (is_bool($value) || $value === 'true' || $value === 'false'): ?>
                                            <?php $boolVal = is_bool($value) ? $value : $value === 'true'; ?>
                                            <span class="badge bg-<?php echo $boolVal ? 'success' : 'danger'; ?>-lt"><?php echo $boolVal ? 'Ja' : 'Nein'; ?></span>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars((string)$value); ?>
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

        <!-- Dateiberechtigungen -->
        <?php if (!empty($permissions)): ?>
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Dateiberechtigungen</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Verzeichnis</th><th>Lesbar</th><th>Schreibbar</th></tr></thead>
                        <tbody>
                            <?php foreach ($permissions as $perm): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)($perm['path'] ?? $perm['full_path'] ?? '')); ?></td>
                                    <td>
                                        <?php $readable = is_array($perm) ? ($perm['readable'] ?? false) : false; ?>
                                        <span class="badge bg-<?php echo $readable ? 'success' : 'danger'; ?>-lt"><?php echo $readable ? 'Ja' : 'Nein'; ?></span>
                                    </td>
                                    <td>
                                        <?php $writable = is_array($perm) ? ($perm['writable'] ?? false) : false; ?>
                                        <span class="badge bg-<?php echo $writable ? 'success' : 'danger'; ?>-lt"><?php echo $writable ? 'Ja' : 'Nein'; ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Verzeichnisgrößen -->
        <?php if (!empty($directories)): ?>
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Verzeichnisgrößen</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Verzeichnis</th><th>Größe</th></tr></thead>
                        <tbody>
                            <?php foreach ($directories as $dir => $info): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($dir); ?></td>
                                    <td><?php echo htmlspecialchars(is_array($info) ? ($info['formatted'] ?? ($info['size'] ?? '-')) : (string)$info); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Datenbank-Tools -->
        <?php
            $missingCount = 0;
            $errorCount = 0;
            if (!empty($tables)) {
                foreach ($tables as $tInfo) {
                    if (is_array($tInfo)) {
                        if (empty($tInfo['exists'])) { $missingCount++; }
                        $st = $tInfo['status'] ?? 'OK';
                        if ($st !== 'OK' && $st !== 'ok' && $st !== 'Missing') { $errorCount++; }
                    }
                }
            }
        ?>
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-tool me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 10h3v-3l-3.5 -3.5a6 6 0 0 1 8 8l6 6a2 2 0 0 1 -3 3l-6 -6a6 6 0 0 1 -8 -8l3.5 3.5"/></svg>
                        Datenbank-Tools
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-auto">
                            <?php if ($missingCount > 0): ?>
                                <span class="badge bg-danger-lt fs-5"><?php echo $missingCount; ?> fehlende Tabelle(n)</span>
                            <?php else: ?>
                                <span class="badge bg-success-lt fs-5">Alle Tabellen vorhanden</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($errorCount > 0): ?>
                        <div class="col-auto">
                            <span class="badge bg-warning-lt fs-5"><?php echo $errorCount; ?> Tabelle(n) mit Problemen</span>
                        </div>
                        <?php endif; ?>
                        <div class="col"></div>
                        <div class="col-auto d-flex gap-2">
                            <form method="post" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="action" value="create_tables">
                                <button type="submit" class="btn btn-outline-success" <?php echo $missingCount === 0 ? 'disabled' : ''; ?>>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-database-plus me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 6c0 1.657 3.582 3 8 3s8 -1.343 8 -3s-3.582 -3 -8 -3s-8 1.343 -8 3"/><path d="M4 6v6c0 1.657 3.582 3 8 3c1.075 0 2.1 -.08 3.037 -.224"/><path d="M20 12v-6"/><path d="M4 12v6c0 1.657 3.582 3 8 3c.166 0 .331 -.002 .495 -.006"/><path d="M16 19h6"/><path d="M19 16v6"/></svg>
                                    Fehlende Tabellen erstellen
                                </button>
                            </form>
                            <form method="post" class="d-inline" onsubmit="return confirm('Tabellen-Reparatur starten?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="action" value="repair_tables">
                                <button type="submit" class="btn btn-outline-warning">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-first-aid-kit me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 8v-2a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v2"/><path d="M4 8m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"/><path d="M10 14h4"/><path d="M12 12v4"/></svg>
                                    Tabellen reparieren
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Datenbank-Tabellen -->
        <?php if (!empty($tables)): ?>
        <div class="col-12 mb-4">
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
                            <?php foreach ($tables as $name => $info): ?>
                                <?php if (!is_array($info)) continue; ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars(is_string($name) ? $name : ($info['name'] ?? '')); ?></code></td>
                                    <td>
                                        <?php $exists = $info['exists'] ?? false; ?>
                                        <span class="badge bg-<?php echo $exists ? 'success' : 'danger'; ?>-lt"><?php echo $exists ? 'Ja' : 'Nein'; ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars((string)($info['rows'] ?? '-')); ?></td>
                                    <td><?php echo htmlspecialchars((string)($info['size'] ?? '-')); ?></td>
                                    <td>
                                        <?php $status = $info['status'] ?? 'unknown'; ?>
                                        <span class="badge bg-<?php echo $status === 'ok' || $status === 'OK' ? 'success' : 'warning'; ?>-lt"><?php echo htmlspecialchars($status); ?></span>
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
