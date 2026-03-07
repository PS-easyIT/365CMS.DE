<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/** @var array $data */
$d        = $data ?? [];
$checks   = $d['checks'] ?? [];
$stats    = $d['stats'] ?? [];
$auditLog = $d['audit_log'] ?? [];

$statusIcon = [
    'ok'       => '<svg xmlns="http://www.w3.org/2000/svg" class="icon text-success" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>',
    'warning'  => '<svg xmlns="http://www.w3.org/2000/svg" class="icon text-warning" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v2m0 4v.01"/><path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/></svg>',
    'critical' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon text-danger" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 9v4"/><path d="M12 16v.01"/></svg>',
];
$statusBadge = ['ok' => 'bg-success', 'warning' => 'bg-warning', 'critical' => 'bg-danger'];
?>

<!-- KPI-Karten -->
<div class="row row-deck row-cards mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Prüfungen gesamt</div>
                <div class="h1 mb-0"><?php echo (int)($stats['total'] ?? 0); ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Bestanden</div>
                <div class="h1 mb-0 text-success"><?php echo (int)($stats['passed'] ?? 0); ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Warnungen</div>
                <div class="h1 mb-0 text-warning"><?php echo (int)($stats['warning'] ?? 0); ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Kritisch</div>
                <div class="h1 mb-0 text-danger"><?php echo (int)($stats['critical'] ?? 0); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Aktionen -->
<div class="d-flex gap-2 mb-4">
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
        <input type="hidden" name="action" value="run_audit">
        <button type="submit" class="btn btn-primary">Audit erneut ausführen</button>
    </form>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
        <input type="hidden" name="action" value="clear_log">
        <button type="submit" class="btn btn-outline-secondary">Alte Logs bereinigen</button>
    </form>
</div>

<!-- Sicherheitsprüfungen -->
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Sicherheitsprüfungen</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th style="width:40px"></th>
                    <th>Prüfung</th>
                    <th>Status</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checks as $check): ?>
                <tr>
                    <td><?php echo $statusIcon[$check['status']] ?? ''; ?></td>
                    <td class="fw-bold"><?php echo htmlspecialchars($check['name']); ?></td>
                    <td><span class="badge <?php echo $statusBadge[$check['status']] ?? 'bg-secondary'; ?>"><?php echo htmlspecialchars(ucfirst($check['status'])); ?></span></td>
                    <td class="text-muted"><?php echo htmlspecialchars($check['detail']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Audit-Log -->
<?php if (!empty($auditLog)): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Audit-Log (letzte 50 Einträge)</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-striped">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Aktion</th>
                    <th>Benutzer</th>
                    <th>Details</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($auditLog as $entry): ?>
                <tr>
                    <td class="text-nowrap"><?php echo htmlspecialchars($entry['created_at'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($entry['action'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars((string)($entry['user_id'] ?? '-')); ?></td>
                    <td><?php echo htmlspecialchars($entry['details'] ?? ''); ?></td>
                    <td><code><?php echo htmlspecialchars($entry['ip_address'] ?? '-'); ?></code></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-body text-center text-muted">
        <p>Kein Audit-Log vorhanden. Log-Einträge werden automatisch bei sicherheitsrelevanten Ereignissen erstellt.</p>
    </div>
</div>
<?php endif; ?>
