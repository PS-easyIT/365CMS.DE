<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
if (!defined('CMS_ADMIN_PERFORMANCE_VIEW')) exit;

$cache = $data['cache'] ?? [];
$media = $data['media'] ?? [];
$database = $data['database'] ?? [];
$sessions = $data['sessions'] ?? [];
$php = $data['php_info'] ?? [];
$history = is_array($data['history'] ?? null) ? $data['history'] : [];
$historyEntries = is_array($history['entries'] ?? null) ? $history['entries'] : [];
$historySummary = is_array($history['summary'] ?? null) ? $history['summary'] : [];
$historyUnavailable = !empty($history['unavailable']);
$historyNote = (string)($history['note'] ?? '');

$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2, ',', '.') . ' KB';
    return $bytes . ' B';
};
$formatDuration = static function (?int $durationMs): string {
    if ($durationMs === null) {
        return '–';
    }
    if ($durationMs >= 1000) {
        return number_format($durationMs / 1000, 2, ',', '.') . ' s';
    }

    return number_format($durationMs, 0, ',', '.') . ' ms';
};
$normalizeScore = static function (mixed $score): int {
    return max(0, min(100, (int) $score));
};
$resolveScoreClass = static function (int $score): string {
    if ($score >= 80) {
        return 'is-success';
    }
    if ($score >= 60) {
        return 'is-warning';
    }

    return 'is-danger';
};
$cacheScore = $normalizeScore($cache['health_score'] ?? 0);
$mediaScore = $normalizeScore($media['health_score'] ?? 0);
$databaseScore = $normalizeScore($database['health_score'] ?? 0);
$sessionScore = $normalizeScore($sessions['health_score'] ?? 0);
?>

<div class="page-header d-print-none admin-redesign-header">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Performance</div>
                <h2 class="page-title">Performance-Übersicht</h2>
                <div class="text-secondary mt-1">Die neue Zentrale für Cache, Medien, Datenbank, Sessions und technische Laufzeitwerte.</div>
            </div>
        </div>
    </div>
</div>

<div class="page-body admin-redesign-page">
<div class="container-xl admin-redesign-shell">
    <?php if (!empty($alert)): ?>
        <div class="alert alert-<?php echo htmlspecialchars($alert['type'] ?? 'info'); ?> mb-4" role="alert">
            <?php echo htmlspecialchars($alert['message'] ?? ''); ?>
        </div>
    <?php endif; ?>

    <?php require dirname(__DIR__) . '/performance/subnav.php'; ?>

    <div class="row row-deck row-cards mb-4">
        <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Cache-Health</div><div class="h1 mb-0"><?php echo $cacheScore; ?></div><div class="performance-health-progress <?php echo $resolveScoreClass($cacheScore); ?>" style="--performance-score: <?php echo $cacheScore; ?>"><span class="performance-health-progress__fill"></span></div><div class="text-secondary"><?php echo (int)($cache['file_cache']['files'] ?? 0); ?> Dateien</div></div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Medien-Health</div><div class="h1 mb-0"><?php echo $mediaScore; ?></div><div class="performance-health-progress <?php echo $resolveScoreClass($mediaScore); ?>" style="--performance-score: <?php echo $mediaScore; ?>"><span class="performance-health-progress__fill"></span></div><div class="text-secondary"><?php echo (int)($media['library']['missing_alt'] ?? 0); ?> fehlende Alt-Texte</div></div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">DB-Health</div><div class="h1 mb-0"><?php echo $databaseScore; ?></div><div class="performance-health-progress <?php echo $resolveScoreClass($databaseScore); ?>" style="--performance-score: <?php echo $databaseScore; ?>"><span class="performance-health-progress__fill"></span></div><div class="text-secondary"><?php echo htmlspecialchars($formatBytes((int)($database['total_overhead_bytes'] ?? 0))); ?> Overhead</div></div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Session-Health</div><div class="h1 mb-0"><?php echo $sessionScore; ?></div><div class="performance-health-progress <?php echo $resolveScoreClass($sessionScore); ?>" style="--performance-score: <?php echo $sessionScore; ?>"><span class="performance-health-progress__fill"></span></div><div class="text-secondary"><?php echo (int)($sessions['active_sessions'] ?? 0); ?> aktiv</div></div></div></div>
    </div>

    <div class="row row-cards mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title">Bereiche mit Handlungsbedarf</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Bereich</th><th>Status</th><th>Hinweis</th><th></th></tr></thead>
                        <tbody>
                            <tr><td>Cache</td><td><span class="badge bg-<?php echo (int)($cache['health_score'] ?? 0) >= 80 ? 'success' : 'warning'; ?>-lt"><?php echo (int)($cache['health_score'] ?? 0); ?>/100</span></td><td><?php echo (int)($cache['db_cache']['expired_entries'] ?? 0); ?> abgelaufene DB-Cache-Einträge</td><td><a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars('/admin/performance-cache'); ?>">Öffnen</a></td></tr>
                            <tr><td>Medien</td><td><span class="badge bg-<?php echo (int)($media['health_score'] ?? 0) >= 80 ? 'success' : 'warning'; ?>-lt"><?php echo (int)($media['health_score'] ?? 0); ?>/100</span></td><td><?php echo (int)($media['oversized_images'] ?? 0); ?> große Bilder in der Top-Liste</td><td><a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars('/admin/performance-media'); ?>">Öffnen</a></td></tr>
                            <tr><td>Datenbank</td><td><span class="badge bg-<?php echo (int)($database['health_score'] ?? 0) >= 80 ? 'success' : 'warning'; ?>-lt"><?php echo (int)($database['health_score'] ?? 0); ?>/100</span></td><td><?php echo (int)($database['revision_count'] ?? 0); ?> Revisionen gespeichert</td><td><a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars('/admin/performance-database'); ?>">Öffnen</a></td></tr>
                            <tr><td>Sessions</td><td><span class="badge bg-<?php echo (int)($sessions['health_score'] ?? 0) >= 80 ? 'success' : 'warning'; ?>-lt"><?php echo (int)($sessions['health_score'] ?? 0); ?>/100</span></td><td><?php echo (int)($sessions['expired_sessions'] ?? 0); ?> abgelaufen</td><td><a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars('/admin/performance-sessions'); ?>">Öffnen</a></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title">Laufzeit-Snapshot</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-sm">
                        <tbody>
                            <tr><td class="text-muted">PHP</td><td><?php echo htmlspecialchars((string)($php['version'] ?? '-')); ?></td></tr>
                            <tr><td class="text-muted">Memory Limit</td><td><?php echo htmlspecialchars((string)($php['memory_limit'] ?? '-')); ?></td></tr>
                            <tr><td class="text-muted">Upload-Limit</td><td><?php echo htmlspecialchars((string)($php['upload_max'] ?? '-')); ?></td></tr>
                            <tr><td class="text-muted">OPcache</td><td><?php echo !empty($php['opcache_enabled']) ? 'Aktiv' : 'Inaktiv'; ?></td></tr>
                            <tr><td class="text-muted">GZIP / zlib</td><td><?php echo !empty($php['gzip_enabled']) ? 'Verfügbar' : 'Nicht verfügbar'; ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-deck row-cards mb-4">
        <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Maßnahmen · 30 Einträge</div><div class="h1 mb-0"><?php echo (int)($historySummary['total'] ?? 0); ?></div><div class="text-secondary">read-only Audit-Historie</div></div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Erfolgreich</div><div class="h1 mb-0 text-success"><?php echo (int)($historySummary['success_count'] ?? 0); ?></div><div class="text-secondary">teilweise: <?php echo (int)($historySummary['partial_count'] ?? 0); ?></div></div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Fehlgeschlagen</div><div class="h1 mb-0 text-danger"><?php echo (int)($historySummary['error_count'] ?? 0); ?></div><div class="text-secondary">inkl. harter Abbrüche</div></div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Ø Dauer</div><div class="h1 mb-0"><?php echo htmlspecialchars($formatDuration(isset($historySummary['avg_duration_ms']) ? (is_numeric($historySummary['avg_duration_ms']) ? (int)$historySummary['avg_duration_ms'] : null) : null)); ?></div><div class="text-secondary">nur sofern protokolliert</div></div></div></div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div>
                <h3 class="card-title mb-0">Historie der Performance-Maßnahmen</h3>
                <div class="text-secondary small mt-1">Zeitpunkt, Maßnahme, Auslöser, Ergebnis und Laufzeit auf Basis des bestehenden <code>audit_log</code>.</div>
            </div>
        </div>
        <div class="card-body border-bottom py-3">
            <?php if ($historyNote !== ''): ?>
                <div class="text-secondary small"><?php echo htmlspecialchars($historyNote); ?></div>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-striped">
                <thead>
                    <tr>
                        <th>Zeitpunkt</th>
                        <th>Bereich</th>
                        <th>Maßnahme</th>
                        <th>Auslöser</th>
                        <th>Ergebnis</th>
                        <th>Dauer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($historyUnavailable): ?>
                        <tr><td colspan="6" class="text-center text-secondary py-4">Die Performance-Historie ist aktuell nicht verfügbar.</td></tr>
                    <?php elseif ($historyEntries === []): ?>
                        <tr><td colspan="6" class="text-center text-secondary py-4">Noch keine Performance-Maßnahmen protokolliert.</td></tr>
                    <?php else: ?>
                        <?php foreach ($historyEntries as $entry): ?>
                            <tr>
                                <td class="text-secondary small"><?php echo htmlspecialchars((string)($entry['created_at'] ?? '—')); ?></td>
                                <td>
                                    <span class="badge bg-blue-lt"><?php echo htmlspecialchars((string)($entry['section_label'] ?? 'Performance')); ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars((string)($entry['action_label'] ?? 'Performance-Maßnahme')); ?></div>
                                    <?php if (!empty($entry['message'])): ?>
                                        <div class="text-secondary small"><?php echo htmlspecialchars((string)$entry['message']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars((string)($entry['trigger_label'] ?? 'Admin-Aktion')); ?></div>
                                    <div class="text-secondary small"><?php echo htmlspecialchars((string)($entry['user_label'] ?? 'System')); ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo htmlspecialchars((string)($entry['result_badge'] ?? 'secondary')); ?>-lt"><?php echo htmlspecialchars((string)($entry['result_label'] ?? 'unbekannt')); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($formatDuration(isset($entry['duration_ms']) && is_numeric($entry['duration_ms']) ? (int)$entry['duration_ms'] : null)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
