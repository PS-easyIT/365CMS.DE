<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
if (!defined('CMS_ADMIN_PERFORMANCE_VIEW')) exit;

$settings = $data['settings'] ?? [];
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Performance</div>
                <h2 class="page-title">Performance-Einstellungen</h2>
                <div class="text-secondary mt-1">Frontend-Auslieferung, Fonts, Minifizierung, Lazy Loading, Caching und Session-Lebensdauer zentral steuern.</div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if (!empty($alert)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert['type'] ?? 'info'); ?> mb-4"><?php echo htmlspecialchars($alert['message'] ?? ''); ?></div>
        <?php endif; ?>

        <?php require __DIR__ . '/subnav.php'; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
            <input type="hidden" name="action" value="save_settings">

            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-header">
                            <h3 class="card-title">Frontend-Auslieferung</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="perf_lazy_loading" value="1" <?php echo ($settings['perf_lazy_loading'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                        <span class="form-check-label">Lazy Loading für Medien aktivieren</span>
                                    </label>
                                    <label class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="perf_minify_css" value="1" <?php echo ($settings['perf_minify_css'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                        <span class="form-check-label">CSS-Minifizierung aktivieren</span>
                                    </label>
                                    <label class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="perf_minify_js" value="1" <?php echo ($settings['perf_minify_js'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                        <span class="form-check-label">JS-Minifizierung aktivieren</span>
                                    </label>
                                    <label class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" name="perf_gzip" value="1" <?php echo ($settings['perf_gzip'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                        <span class="form-check-label">GZIP/Brotli-Auslieferung vorbereiten</span>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-info mb-0">
                                        <div class="fw-semibold mb-1">Fonts & DSGVO</div>
                                        <div class="text-secondary">
                                            Schriftarten werden separat im <a href="<?php echo htmlspecialchars(SITE_URL . '/admin/font-manager'); ?>">Font Manager</a> verwaltet. Dort kannst du externe Theme-Fonts scannen und lokal self-hosten.
                                        </div>
                                    </div>
                                    <div class="form-text mt-3">
                                        Empfehlung: Lokale Fonts + Lazy Loading + Minifizierung kombinieren, um Ladezeit und externe Requests sichtbar zu reduzieren.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h3 class="card-title">Empfohlener Start</h3>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0 text-secondary">
                                <li class="mb-2">• Browser-Caching aktivieren</li>
                                <li class="mb-2">• HTML-/Seiten-Cache mit kurzer TTL nutzen</li>
                                <li class="mb-2">• Bilder per Lazy Loading verzögert laden</li>
                                <li class="mb-2">• CSS/JS nur nach Test minifizieren</li>
                                <li class="mb-0">• Externe Fonts möglichst lokal hosten</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h3 class="card-title">Caching</h3>
                        </div>
                        <div class="card-body">
                            <label class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="perf_page_cache" value="1" <?php echo ($settings['perf_page_cache'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <span class="form-check-label">Seiten-Cache aktivieren</span>
                            </label>
                            <label class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="perf_browser_cache" value="1" <?php echo ($settings['perf_browser_cache'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <span class="form-check-label">Browser-Caching-Header aktivieren</span>
                            </label>
                            <label class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" name="perf_auto_clear_content_cache" value="1" <?php echo ($settings['perf_auto_clear_content_cache'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <span class="form-check-label">Cache bei Inhaltsänderungen automatisch leeren</span>
                            </label>

                            <div class="mb-3">
                                <label class="form-label">Browser-Cache TTL (Sekunden)</label>
                                <input type="number" min="0" class="form-control" name="perf_browser_cache_ttl" value="<?php echo htmlspecialchars((string)($settings['perf_browser_cache_ttl'] ?? '604800')); ?>">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">HTML-/Seiten-Cache TTL (Sekunden)</label>
                                <input type="number" min="0" class="form-control" name="perf_html_cache_ttl" value="<?php echo htmlspecialchars((string)($settings['perf_html_cache_ttl'] ?? '300')); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h3 class="card-title">Sessions</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Admin-Session Timeout (Sekunden)</label>
                                <input type="number" min="0" class="form-control" name="perf_session_timeout_admin" value="<?php echo htmlspecialchars((string)($settings['perf_session_timeout_admin'] ?? '28800')); ?>">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Member-Session Timeout (Sekunden)</label>
                                <input type="number" min="0" class="form-control" name="perf_session_timeout_member" value="<?php echo htmlspecialchars((string)($settings['perf_session_timeout_member'] ?? '2592000')); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Einstellungen speichern</button>
            </div>
        </form>
    </div>
</div>
