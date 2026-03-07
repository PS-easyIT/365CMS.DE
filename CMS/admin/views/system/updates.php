<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var array $data Update-Daten
 * @var string $csrfToken CSRF-Token
 */

$core     = $data['core'];
$plugins  = $data['plugins'];
$theme    = $data['theme'];
$history  = $data['history'];
$hasUpdates = $data['has_updates'];
?>

<div class="container-xl">
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-refresh me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/></svg>
                    Updates
                </h2>
            </div>
            <div class="col-auto">
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="check_updates">
                    <button type="submit" class="btn btn-outline-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/></svg>
                        Jetzt prüfen
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php if (!$hasUpdates): ?>
        <div class="alert alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
            <div>Alles ist auf dem neuesten Stand!</div>
        </div>
    <?php endif; ?>

    <!-- CMS Core -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">CMS Core</h3>
            <div class="card-actions">
                <span class="badge bg-blue-lt">v<?php echo htmlspecialchars($core['current_version'] ?? '?.?.?'); ?></span>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($core['update_available'])): ?>
                <div class="alert alert-warning mb-3">
                    <div class="d-flex align-items-center">
                        <div>
                            <strong>Neues Update verfügbar:</strong> Version <?php echo htmlspecialchars($core['latest_version'] ?? ''); ?>
                            <?php if (!empty($core['changelog'])): ?>
                                <div class="mt-1 text-secondary small"><?php echo htmlspecialchars(mb_substr($core['changelog'], 0, 200)); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="ms-auto">
                            <form method="post" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="action" value="install_core">
                                <button type="submit" class="btn btn-warning" onclick="return confirm('Core-Update jetzt installieren?')">
                                    Update installieren
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                    CMS ist aktuell (v<?php echo htmlspecialchars($core['current_version'] ?? ''); ?>)
                </div>
            <?php endif; ?>
            <?php if (!empty($core['error'])): ?>
                <div class="alert alert-danger mt-2">Fehler bei der Update-Prüfung: <?php echo htmlspecialchars($core['error']); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Plugin-Updates -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Plugins</h3>
            <div class="card-actions">
                <span class="badge bg-blue-lt"><?php echo count($plugins); ?> installiert</span>
            </div>
        </div>
        <?php if (!empty($plugins)): ?>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Plugin</th>
                            <th>Aktuelle Version</th>
                            <th>Neue Version</th>
                            <th>Status</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($plugins as $slug => $plugin): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($plugin['name'] ?? $slug); ?></strong></td>
                                <td><?php echo htmlspecialchars($plugin['current_version'] ?? '-'); ?></td>
                                <td>
                                    <?php if (!empty($plugin['new_version'])): ?>
                                        <span class="text-warning"><?php echo htmlspecialchars($plugin['new_version']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($plugin['new_version'])): ?>
                                        <span class="badge bg-warning-lt">Update verfügbar</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-lt">Aktuell</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($plugin['new_version'])): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                            <input type="hidden" name="action" value="install_plugin">
                                            <input type="hidden" name="plugin_slug" value="<?php echo htmlspecialchars($slug); ?>">
                                            <button type="submit" class="btn btn-sm btn-warning">Update</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="card-body text-muted">Keine Plugins installiert oder Prüfung fehlgeschlagen.</div>
        <?php endif; ?>
    </div>

    <!-- Theme -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Theme</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($theme['update_available'])): ?>
                <div class="alert alert-warning mb-0">
                    <strong>Theme-Update verfügbar:</strong>
                    <?php echo htmlspecialchars($theme['current_version'] ?? '-'); ?> → <?php echo htmlspecialchars($theme['latest_version'] ?? '-'); ?>
                </div>
            <?php else: ?>
                <div class="text-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                    Theme ist aktuell
                    <?php if (!empty($theme['current_version'])): ?>
                        (v<?php echo htmlspecialchars($theme['current_version']); ?>)
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($theme['error'])): ?>
                <div class="alert alert-danger mt-2">Fehler: <?php echo htmlspecialchars($theme['error']); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Update-Historie -->
    <?php if (!empty($history)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Update-Historie</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Typ</th>
                            <th>Name</th>
                            <th>Version</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $entry): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($entry['date'] ?? $entry['timestamp'] ?? '-'); ?></td>
                                <td><span class="badge"><?php echo htmlspecialchars($entry['type'] ?? '-'); ?></span></td>
                                <td><?php echo htmlspecialchars($entry['name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($entry['version'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
