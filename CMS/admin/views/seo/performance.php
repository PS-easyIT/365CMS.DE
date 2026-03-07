<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/** @var array $data */
$d = $data ?? [];
$settings = $d['settings'] ?? [];
$php = $d['php_info'] ?? [];

$formatBytes = function(int $bytes): string {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576)    return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)       return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
};
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">SEO &amp; Performance</div>
                <h2 class="page-title">Performance</h2>
                <div class="text-secondary mt-1">Cache, Sessions, Bildanalyse und Performance-Einstellungen für das CMS.</div>
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

<!-- KPI-Karten -->
<div class="row row-deck row-cards mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Cache</div>
                </div>
                <div class="h1 mb-0"><?php echo $formatBytes((int)($d['cache_size'] ?? 0)); ?></div>
                <div class="text-muted"><?php echo (int)($d['cache_files'] ?? 0); ?> Dateien</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Sessions</div>
                </div>
                <div class="h1 mb-0"><?php echo $formatBytes((int)($d['session_size'] ?? 0)); ?></div>
                <div class="text-muted"><?php echo (int)($d['session_files'] ?? 0); ?> Dateien</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Uploads</div>
                </div>
                <div class="h1 mb-0"><?php echo $formatBytes((int)($d['upload_size'] ?? 0)); ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Datenbank</div>
                </div>
                <div class="h1 mb-0"><?php echo $formatBytes((int)($d['db_size'] ?? 0)); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Aktionen -->
<div class="row row-deck row-cards mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-primary mb-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19 20h-10.5l-4.21 -4.3a1 1 0 0 1 0 -1.41l10 -10a1 1 0 0 1 1.41 0l5 5a1 1 0 0 1 0 1.41l-9.2 9.3"/><path d="M18 13.3l-6.3 -6.3"/></svg>
                <h3 class="mb-1">Cache leeren</h3>
                <p class="text-muted"><?php echo (int)($d['cache_files'] ?? 0); ?> Dateien im Cache</p>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                    <input type="hidden" name="action" value="clear_cache">
                    <button type="submit" class="btn btn-primary">Cache leeren</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-warning mb-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 7v5l3 3"/></svg>
                <h3 class="mb-1">Sessions bereinigen</h3>
                <p class="text-muted">Abgelaufene Sessions (> 24h) löschen</p>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                    <input type="hidden" name="action" value="clear_expired_sessions">
                    <button type="submit" class="btn btn-warning">Sessions bereinigen</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-info mb-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8h.01"/><path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z"/><path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l5 5"/><path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0l3 3"/></svg>
                <h3 class="mb-1">Bild-Analyse</h3>
                <p class="text-muted">Große Bilder (> 500 KB) identifizieren</p>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                    <input type="hidden" name="action" value="optimize_images">
                    <button type="submit" class="btn btn-info">Bilder analysieren</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Performance-Einstellungen -->
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Performance-Einstellungen</h3>
    </div>
    <div class="card-body">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
            <input type="hidden" name="action" value="save_settings">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="perf_lazy_loading" value="1" <?php echo ($settings['perf_lazy_loading'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <span class="form-check-label">Lazy Loading für Bilder</span>
                    </label>
                    <label class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="perf_minify_css" value="1" <?php echo ($settings['perf_minify_css'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <span class="form-check-label">CSS minimieren</span>
                    </label>
                    <label class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="perf_minify_js" value="1" <?php echo ($settings['perf_minify_js'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <span class="form-check-label">JavaScript minimieren</span>
                    </label>
                </div>
                <div class="col-md-6">
                    <label class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="perf_gzip" value="1" <?php echo ($settings['perf_gzip'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <span class="form-check-label">GZIP-Komprimierung</span>
                    </label>
                    <label class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="perf_browser_cache" value="1" <?php echo ($settings['perf_browser_cache'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <span class="form-check-label">Browser-Caching</span>
                    </label>
                    <label class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="perf_page_cache" value="1" <?php echo ($settings['perf_page_cache'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <span class="form-check-label">Seiten-Cache</span>
                    </label>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Einstellungen speichern</button>
            </div>
        </form>
    </div>
</div>

<!-- PHP-Umgebung -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Server-Umgebung</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <tbody>
                <tr>
                    <td class="text-muted" style="width:40%">PHP-Version</td>
                    <td><?php echo htmlspecialchars($php['version'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Memory Limit</td>
                    <td><?php echo htmlspecialchars($php['memory_limit'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Max Execution Time</td>
                    <td><?php echo htmlspecialchars($php['max_execution'] ?? '-'); ?>s</td>
                </tr>
                <tr>
                    <td class="text-muted">Upload-Limit</td>
                    <td><?php echo htmlspecialchars($php['upload_max'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Post Max Size</td>
                    <td><?php echo htmlspecialchars($php['post_max'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td class="text-muted">OPcache</td>
                    <td>
                        <?php if (!empty($php['opcache_enabled'])): ?>
                            <span class="badge bg-success">Aktiv</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inaktiv</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="text-muted">GZIP (zlib)</td>
                    <td>
                        <?php if (!empty($php['gzip_enabled'])): ?>
                            <span class="badge bg-success">Verfügbar</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Nicht verfügbar</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
