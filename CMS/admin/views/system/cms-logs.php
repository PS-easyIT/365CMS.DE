<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_SYSTEM_VIEW')) {
    exit;
}

$logsData = is_array($data ?? null) ? $data : [];
$logDirectory = (string) ($logsData['log_directory'] ?? '');
$logDirectoryExists = !empty($logsData['log_directory_exists']);
$logDirectoryWritable = !empty($logsData['log_directory_writable']);
$errorLogFile = (string) ($logsData['error_log_file'] ?? '');
$errorLogExists = !empty($logsData['error_log_exists']);
$logFiles = is_array($logsData['files'] ?? null) ? $logsData['files'] : [];
$selectedFile = (string) ($logsData['selected_file'] ?? '');
$selectedEntries = is_array($logsData['selected_entries'] ?? null) ? $logsData['selected_entries'] : [];
$documentationEntries = is_array($logsData['documentation_entries'] ?? null) ? $logsData['documentation_entries'] : [];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Info &amp; Diagnose</div>
                <h2 class="page-title">CMS Logs</h2>
                <div class="text-secondary mt-1">Gebündelte Sicht auf die konfigurierten CMS-Logdateien aus <code>LOG_PATH</code> inklusive Doku-Sync-Channel.</div>
            </div>
            <div class="col-auto d-flex gap-2 flex-wrap">
                <form method="post" class="d-inline" data-confirm-message="PHP Error-Log wirklich leeren?" data-confirm-title="PHP Error-Log leeren" data-confirm-text="Leeren" data-confirm-class="btn-warning" data-confirm-status-class="bg-warning">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="clear_logs">
                    <button type="submit" class="btn btn-outline-warning"<?php echo !$errorLogExists ? ' disabled' : ''; ?>>PHP Error-Log leeren</button>
                </form>
                <form method="post" class="d-inline" data-confirm-message="Wirklich alle CMS-Logdateien löschen?" data-confirm-title="Alle Logs löschen" data-confirm-text="Löschen" data-confirm-class="btn-danger" data-confirm-status-class="bg-danger">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="clear_all_cms_logs">
                    <button type="submit" class="btn btn-outline-danger"<?php echo $logFiles === [] ? ' disabled' : ''; ?>>Alle CMS-Logs löschen</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php $alertData = $alert ?? []; $alertMarginClass = 'mb-4'; require __DIR__ . '/../partials/flash-alert.php'; ?>

        <?php require __DIR__ . '/subnav.php'; ?>

        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Log-Verzeichnis</div><div class="h1 mb-0 <?php echo $logDirectoryExists ? 'text-success' : 'text-danger'; ?>"><?php echo $logDirectoryExists ? 'OK' : 'Fehlt'; ?></div><div class="text-secondary small mt-1 text-break"><code><?php echo htmlspecialchars($logDirectory, ENT_QUOTES, 'UTF-8'); ?></code></div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Beschreibbar</div><div class="h1 mb-0 <?php echo $logDirectoryWritable ? 'text-success' : 'text-warning'; ?>"><?php echo $logDirectoryWritable ? 'Ja' : 'Nein'; ?></div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Logdateien</div><div class="h1 mb-0"><?php echo count($logFiles); ?></div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">PHP Error-Log</div><div class="h1 mb-0 <?php echo $errorLogExists ? 'text-success' : 'text-warning'; ?>"><?php echo $errorLogExists ? 'Vorhanden' : 'Fehlt'; ?></div><div class="text-secondary small mt-1 text-break"><code><?php echo htmlspecialchars($errorLogFile, ENT_QUOTES, 'UTF-8'); ?></code></div></div></div></div>
        </div>

        <div class="row row-cards mb-4">
            <div class="col-12 col-xl-5">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Logdateien</h3></div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-striped">
                            <thead>
                                <tr>
                                    <th>Datei</th>
                                    <th>Kanal</th>
                                    <th>Größe</th>
                                    <th>Geändert</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($logFiles === []): ?>
                                    <tr><td colspan="5" class="text-center text-secondary py-4">Keine Logdateien im konfigurierten Pfad gefunden.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($logFiles as $file): ?>
                                        <?php $filename = (string) ($file['filename'] ?? ''); ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo htmlspecialchars('/admin/cms-logs?log_file=' . rawurlencode($filename), ENT_QUOTES, 'UTF-8'); ?>" class="fw-semibold<?php echo $selectedFile === $filename ? ' text-primary' : ''; ?>"><?php echo htmlspecialchars($filename, ENT_QUOTES, 'UTF-8'); ?></a>
                                            </td>
                                            <td><code><?php echo htmlspecialchars((string)($file['channel'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td>
                                            <td><?php echo htmlspecialchars((string)($file['formatted_size'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-secondary small"><?php echo htmlspecialchars((string)($file['modified_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <form method="post" data-confirm-message="Logdatei <?php echo htmlspecialchars($filename, ENT_QUOTES, 'UTF-8'); ?> wirklich löschen?" data-confirm-title="Logdatei löschen" data-confirm-text="Löschen" data-confirm-class="btn-danger" data-confirm-status-class="bg-danger">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="action" value="clear_cms_log">
                                                    <input type="hidden" name="log_file" value="<?php echo htmlspecialchars($filename, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <button type="submit" class="btn btn-ghost-danger btn-sm">Löschen</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-7">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Log-Ansicht: <?php echo htmlspecialchars($selectedFile !== '' ? $selectedFile : '–', ENT_QUOTES, 'UTF-8'); ?></h3></div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-striped">
                            <thead>
                                <tr>
                                    <th>Zeit</th>
                                    <th>Level</th>
                                    <th>Kanal</th>
                                    <th>Meldung</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($selectedEntries === []): ?>
                                    <tr><td colspan="4" class="text-center text-secondary py-4">Für die ausgewählte Datei liegen keine lesbaren Logeinträge vor.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($selectedEntries as $entry): ?>
                                        <tr>
                                            <td class="text-nowrap"><?php echo htmlspecialchars((string)($entry['timestamp'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><span class="badge bg-secondary-lt"><?php echo htmlspecialchars((string)($entry['level'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                            <td><code><?php echo htmlspecialchars((string)($entry['channel'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td>
                                            <td class="small"><?php echo htmlspecialchars((string)($entry['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Doku-Sync / Channel <code>admin.documentation</code></h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-striped">
                    <thead>
                        <tr>
                            <th>Zeit</th>
                            <th>Datei</th>
                            <th>Level</th>
                            <th>Meldung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($documentationEntries === []): ?>
                            <tr><td colspan="4" class="text-center text-secondary py-4">Noch keine Einträge für <code>admin.documentation</code> gefunden. In dieser Workspace-Kopie existiert derzeit auch kein erzeugtes Logverzeichnis.</td></tr>
                        <?php else: ?>
                            <?php foreach ($documentationEntries as $entry): ?>
                                <tr>
                                    <td class="text-nowrap"><?php echo htmlspecialchars((string)($entry['timestamp'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><code><?php echo htmlspecialchars((string)($entry['file'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td>
                                    <td><span class="badge bg-secondary-lt"><?php echo htmlspecialchars((string)($entry['level'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td class="small"><?php echo htmlspecialchars((string)($entry['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
