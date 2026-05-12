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
$selectedFileInfo = is_array($logsData['selected_file_info'] ?? null) ? $logsData['selected_file_info'] : [];
$selectedEntries = is_array($logsData['selected_entries'] ?? null) ? $logsData['selected_entries'] : [];
$errorLogEntries = is_array($logsData['error_log_entries'] ?? null) ? $logsData['error_log_entries'] : [];
$documentationEntries = is_array($logsData['documentation_entries'] ?? null) ? $logsData['documentation_entries'] : [];
$operationalAuditEntries = is_array($logsData['operational_audit_entries'] ?? null) ? $logsData['operational_audit_entries'] : [];
$operationalAuditSummary = is_array($logsData['operational_audit_summary'] ?? null) ? $logsData['operational_audit_summary'] : [];
$updateHistoryEntries = is_array($logsData['update_history_entries'] ?? null) ? $logsData['update_history_entries'] : [];
$hasClearableCmsProtocolData = $logFiles !== [] || $operationalAuditEntries !== [] || $updateHistoryEntries !== [];
$channelSummary = [];

foreach ($logFiles as $file) {
    $channel = trim((string) ($file['channel'] ?? ''));
    if ($channel === '') {
        $channel = 'unbekannt';
    }

    if (!isset($channelSummary[$channel])) {
        $channelSummary[$channel] = [
            'channel' => $channel,
            'files' => 0,
            'size' => 0,
            'last_modified' => '',
        ];
    }

    $channelSummary[$channel]['files']++;
    $channelSummary[$channel]['size'] += (int) ($file['size'] ?? 0);
    $modifiedAt = (string) ($file['modified_at'] ?? '');
    if ($modifiedAt !== '' && strcmp($modifiedAt, (string) $channelSummary[$channel]['last_modified']) > 0) {
        $channelSummary[$channel]['last_modified'] = $modifiedAt;
    }
}

ksort($channelSummary);
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Diagnose</div>
                <h2 class="page-title">Logs &amp; Protokolle</h2>
                <div class="text-secondary mt-1">Zentrale Logzentrale für CMS-Dateilogs, PHP Error-Log, operative Audit-Spuren aus Backup/Update/Performance sowie wichtige Kanalspuren wie <code>admin.documentation</code>.</div>
            </div>
            <div class="col-auto d-flex gap-2 flex-wrap">
                <form method="post" class="d-inline" data-confirm-message="PHP Error-Log wirklich leeren?" data-confirm-title="PHP Error-Log leeren" data-confirm-text="Leeren" data-confirm-class="btn-warning" data-confirm-status-class="bg-warning">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="clear_logs">
                    <button type="submit" class="btn btn-outline-warning"<?php echo !$errorLogExists ? ' disabled' : ''; ?>>PHP Error-Log leeren</button>
                </form>
                <form method="post" class="d-inline" data-confirm-message="Wirklich alle CMS-Logs, das operative Diagnose-Audit und die Update-Historie bereinigen?" data-confirm-title="Logs &amp; Protokolle bereinigen" data-confirm-text="Bereinigen" data-confirm-class="btn-danger" data-confirm-status-class="bg-danger">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="clear_all_cms_logs">
                    <button type="submit" class="btn btn-outline-danger"<?php echo !$hasClearableCmsProtocolData ? ' disabled' : ''; ?>>CMS-Logs &amp; Protokolle bereinigen</button>
                </form>
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="export_diagnostic_report">
                    <button type="submit" class="btn btn-outline-success">Diagnosebericht exportieren</button>
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
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Kanäle</div><div class="h1 mb-0"><?php echo count($channelSummary); ?></div><div class="text-secondary small mt-1"><?php echo $errorLogExists ? 'PHP Error-Log vorhanden' : 'PHP Error-Log fehlt'; ?></div></div></div></div>
        </div>

        <div class="row row-cards mb-4">
            <div class="col-12 col-xl-5">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Aktive Datei</h3></div>
                    <div class="card-body">
                        <?php if ($selectedFileInfo === []): ?>
                            <div class="text-secondary">Keine Logdatei ausgewählt oder im aktuellen Pfad vorhanden.</div>
                        <?php else: ?>
                            <dl class="row mb-0">
                                <dt class="col-sm-4 text-secondary">Datei</dt>
                                <dd class="col-sm-8"><code><?php echo htmlspecialchars((string) ($selectedFileInfo['filename'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></dd>
                                <dt class="col-sm-4 text-secondary">Kanal</dt>
                                <dd class="col-sm-8"><code><?php echo htmlspecialchars((string) ($selectedFileInfo['channel'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></dd>
                                <dt class="col-sm-4 text-secondary">Größe</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars((string) ($selectedFileInfo['formatted_size'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd>
                                <dt class="col-sm-4 text-secondary">Zuletzt geändert</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars((string) ($selectedFileInfo['modified_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd>
                                <dt class="col-sm-4 text-secondary">Einträge</dt>
                                <dd class="col-sm-8"><?php echo count($selectedEntries); ?> ausgelesen</dd>
                                <dt class="col-sm-4 text-secondary">PHP Error-Log</dt>
                                <dd class="col-sm-8 text-break"><code><?php echo htmlspecialchars($errorLogFile, ENT_QUOTES, 'UTF-8'); ?></code></dd>
                            </dl>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-7">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Kanal-Überblick</h3></div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-striped">
                            <thead>
                                <tr>
                                    <th>Kanal</th>
                                    <th>Dateien</th>
                                    <th>Volumen</th>
                                    <th>Zuletzt geändert</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($channelSummary === []): ?>
                                    <tr><td colspan="4" class="text-center text-secondary py-4">Noch keine CMS-Logkanäle im konfigurierten Pfad gefunden.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($channelSummary as $summary): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars((string) $summary['channel'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                            <td><?php echo (int) $summary['files']; ?></td>
                                            <td><?php echo htmlspecialchars(\CMS\Services\SystemService::instance()->formatBytes((int) $summary['size']), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-secondary small"><?php echo htmlspecialchars((string) $summary['last_modified'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-cards mb-4">
            <div class="col-12 col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Operative Bereiche</h3></div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-striped">
                            <thead>
                                <tr>
                                    <th>Bereich</th>
                                    <th>Einträge</th>
                                    <th>Zuletzt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($operationalAuditSummary === []): ?>
                                    <tr><td colspan="3" class="text-center text-secondary py-4">Noch keine operativen Audit-Spuren für System, Backups, Updates oder Performance gefunden.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($operationalAuditSummary as $summary): ?>
                                        <tr>
                                            <td><span class="badge bg-blue-lt"><?php echo htmlspecialchars((string) ($summary['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                            <td><?php echo (int) ($summary['count'] ?? 0); ?></td>
                                            <td class="text-secondary small"><?php echo htmlspecialchars((string) ($summary['last_created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Betriebs-Audit</h3></div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-striped">
                            <thead>
                                <tr>
                                    <th>Zeit</th>
                                    <th>Bereich</th>
                                    <th>Level</th>
                                    <th>Aktion</th>
                                    <th>Meldung</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($operationalAuditEntries === []): ?>
                                    <tr><td colspan="5" class="text-center text-secondary py-4">Keine operativen Audit-Einträge gefunden.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($operationalAuditEntries as $entry): ?>
                                        <?php
                                        $severity = (string) ($entry['severity'] ?? 'info');
                                        $severityClass = match ($severity) {
                                            'critical', 'error' => 'bg-danger-lt text-danger',
                                            'warning' => 'bg-warning-lt text-warning',
                                            default => 'bg-success-lt text-success',
                                        };
                                        ?>
                                        <tr>
                                            <td class="text-nowrap"><?php echo htmlspecialchars((string) ($entry['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><span class="badge bg-blue-lt"><?php echo htmlspecialchars((string) ($entry['group_label'] ?? $entry['group'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                            <td><span class="badge <?php echo $severityClass; ?>"><?php echo htmlspecialchars($severity, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                            <td><code><?php echo htmlspecialchars((string) ($entry['action'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td>
                                            <td class="small"><?php echo htmlspecialchars((string) ($entry['details'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Update-Historie im Diagnose-Kontext</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-striped">
                    <thead>
                        <tr>
                            <th>Zeit</th>
                            <th>Typ</th>
                            <th>Komponente</th>
                            <th>Version</th>
                            <th>Benutzer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($updateHistoryEntries === []): ?>
                            <tr><td colspan="5" class="text-center text-secondary py-4">Noch keine Update-Historie in den Systemsettings gespeichert.</td></tr>
                        <?php else: ?>
                            <?php foreach ($updateHistoryEntries as $entry): ?>
                                <?php
                                $type = (string) ($entry['type'] ?? 'update');
                                $typeLabel = match ($type) {
                                    'core' => 'Core',
                                    'plugin' => 'Plugin',
                                    'theme' => 'Theme',
                                    default => ucfirst($type),
                                };
                                ?>
                                <tr>
                                    <td class="text-nowrap"><?php echo htmlspecialchars((string) ($entry['timestamp'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><span class="badge bg-azure-lt"><?php echo htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td><?php echo htmlspecialchars((string) ($entry['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><code><?php echo htmlspecialchars((string) ($entry['version'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td>
                                    <td><?php echo htmlspecialchars((string) ($entry['user'] ?? 'System'), ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
            <div class="card-header"><h3 class="card-title">Protokollvorschau</h3></div>
            <div class="card-body">
                <div class="row row-cards">
                    <div class="col-12 col-xl-6">
                        <div class="card h-100">
                            <div class="card-header"><h3 class="card-title">PHP Error-Log</h3></div>
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Zeit</th>
                                            <th>Typ</th>
                                            <th>Meldung</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($errorLogEntries === []): ?>
                                            <tr><td colspan="3" class="text-center text-secondary py-4">Keine lesbaren Einträge im PHP Error-Log gefunden.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($errorLogEntries as $entry): ?>
                                                <tr>
                                                    <td class="text-nowrap"><?php echo htmlspecialchars((string) ($entry['timestamp'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><span class="badge bg-secondary-lt"><?php echo htmlspecialchars((string) ($entry['type'] ?? 'RAW'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                                    <td class="small"><?php echo htmlspecialchars((string) ($entry['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="card h-100">
                            <div class="card-header"><h3 class="card-title">Kanal <code>admin.documentation</code></h3></div>
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
                                            <tr><td colspan="4" class="text-center text-secondary py-4">Noch keine Einträge für <code>admin.documentation</code> gefunden.</td></tr>
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
            </div>
        </div>
    </div>
</div>
