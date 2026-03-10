<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
if (!defined('CMS_ADMIN_PERFORMANCE_VIEW')) exit;

$cache = $data['cache'] ?? [];
$media = $data['media'] ?? [];
$database = $data['database'] ?? [];
$sessions = $data['sessions'] ?? [];
$php = $data['php_info'] ?? [];

$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2, ',', '.') . ' KB';
    return $bytes . ' B';
};
?>

<div class="page-header d-print-none">
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

<div class="page-body">
<div class="container-xl">
    <?php if (!empty($alert)): ?>
        <div class="alert alert-<?php echo htmlspecialchars($alert['type'] ?? 'info'); ?> mb-4" role="alert">
            <?php echo htmlspecialchars($alert['message'] ?? ''); ?>
        </div>
    <?php endif; ?>

    <?php require dirname(__DIR__) . '/performance/subnav.php'; ?>

    <div class="row row-deck row-cards mb-4">
        <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Cache-Health</div><div class="h1 mb-0"><?php echo (int)($cache['health_score'] ?? 0); ?></div><div class="text-secondary"><?php echo (int)($cache['file_cache']['files'] ?? 0); ?> Dateien</div></div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Medien-Health</div><div class="h1 mb-0"><?php echo (int)($media['health_score'] ?? 0); ?></div><div class="text-secondary"><?php echo (int)($media['library']['missing_alt'] ?? 0); ?> fehlende Alt-Texte</div></div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">DB-Health</div><div class="h1 mb-0"><?php echo (int)($database['health_score'] ?? 0); ?></div><div class="text-secondary"><?php echo htmlspecialchars($formatBytes((int)($database['total_overhead_bytes'] ?? 0))); ?> Overhead</div></div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Session-Health</div><div class="h1 mb-0"><?php echo (int)($sessions['health_score'] ?? 0); ?></div><div class="text-secondary"><?php echo (int)($sessions['active_sessions'] ?? 0); ?> aktiv</div></div></div></div>
    </div>

    <div class="row row-cards mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title">Bereiche mit Handlungsbedarf</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Bereich</th><th>Status</th><th>Hinweis</th><th></th></tr></thead>
                        <tbody>
                            <tr><td>Cache</td><td><span class="badge bg-<?php echo (int)($cache['health_score'] ?? 0) >= 80 ? 'success' : 'warning'; ?>-lt"><?php echo (int)($cache['health_score'] ?? 0); ?>/100</span></td><td><?php echo (int)($cache['db_cache']['expired_entries'] ?? 0); ?> abgelaufene DB-Cache-Einträge</td><td><a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars(SITE_URL . '/admin/performance-cache'); ?>">Öffnen</a></td></tr>
                            <tr><td>Medien</td><td><span class="badge bg-<?php echo (int)($media['health_score'] ?? 0) >= 80 ? 'success' : 'warning'; ?>-lt"><?php echo (int)($media['health_score'] ?? 0); ?>/100</span></td><td><?php echo (int)($media['oversized_images'] ?? 0); ?> große Bilder in der Top-Liste</td><td><a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars(SITE_URL . '/admin/performance-media'); ?>">Öffnen</a></td></tr>
                            <tr><td>Datenbank</td><td><span class="badge bg-<?php echo (int)($database['health_score'] ?? 0) >= 80 ? 'success' : 'warning'; ?>-lt"><?php echo (int)($database['health_score'] ?? 0); ?>/100</span></td><td><?php echo (int)($database['revision_count'] ?? 0); ?> Revisionen gespeichert</td><td><a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars(SITE_URL . '/admin/performance-database'); ?>">Öffnen</a></td></tr>
                            <tr><td>Sessions</td><td><span class="badge bg-<?php echo (int)($sessions['health_score'] ?? 0) >= 80 ? 'success' : 'warning'; ?>-lt"><?php echo (int)($sessions['health_score'] ?? 0); ?>/100</span></td><td><?php echo (int)($sessions['expired_sessions'] ?? 0); ?> abgelaufen</td><td><a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars(SITE_URL . '/admin/performance-sessions'); ?>">Öffnen</a></td></tr>
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
</div>
</div>
