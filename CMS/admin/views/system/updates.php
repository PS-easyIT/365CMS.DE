<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_SYSTEM_VIEW')) {
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
        <?php
        $alertData = ['type' => 'success', 'message' => 'Alles ist auf dem neuesten Stand!'];
        $alertDismissible = false;
        $alertMarginClass = 'mb-4';
        require __DIR__ . '/../partials/flash-alert.php';
        ?>
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
                <?php
                $coreUpdateDetails = [];
                if (!empty($core['changelog'])) {
                    $coreUpdateDetails[] = cms_truncate_text((string) $core['changelog'], 200, '');
                }
                $alertData = [
                    'type' => 'warning',
                    'message' => 'Neues Update verfügbar: Version ' . (string) ($core['latest_version'] ?? ''),
                    'details' => $coreUpdateDetails,
                ];
                $alertDismissible = false;
                $alertMarginClass = 'mb-3';
                require __DIR__ . '/../partials/flash-alert.php';
                ?>
                <div class="text-end">
                    <form method="post" class="d-inline" data-confirm-message="Core-Update jetzt installieren?" data-confirm-title="Core-Update installieren" data-confirm-text="Installieren" data-confirm-class="btn-warning" data-confirm-status-class="bg-warning">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="install_core">
                        <button type="submit" class="btn btn-warning">
                            Update installieren
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <?php
                $alertData = [
                    'type' => 'success',
                    'message' => 'CMS ist aktuell (v' . (string) ($core['current_version'] ?? '') . ')',
                ];
                $alertDismissible = false;
                $alertMarginClass = 'mb-0';
                require __DIR__ . '/../partials/flash-alert.php';
                ?>
            <?php endif; ?>
            <?php if (!empty($core['error'])): ?>
                <?php
                $alertData = [
                    'type' => 'danger',
                    'message' => 'Fehler bei der Update-Prüfung: ' . (string) $core['error'],
                ];
                $alertDismissible = false;
                $alertMarginClass = 'mt-2 mb-0';
                require __DIR__ . '/../partials/flash-alert.php';
                ?>
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
                                        <?php if (!empty($plugin['requires_cms']) || !empty($plugin['requires_php'])): ?>
                                            <div class="text-muted small mt-1">
                                                <?php if (!empty($plugin['requires_cms'])): ?>365CMS ab <?php echo htmlspecialchars((string) $plugin['requires_cms']); ?><?php endif; ?>
                                                <?php if (!empty($plugin['requires_cms']) && !empty($plugin['requires_php'])): ?> · <?php endif; ?>
                                                <?php if (!empty($plugin['requires_php'])): ?>PHP ab <?php echo htmlspecialchars((string) $plugin['requires_php']); ?><?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($plugin['new_version'])): ?>
                                        <?php if (!empty($plugin['install_supported'])): ?>
                                            <span class="badge bg-warning-lt">Update verfügbar</span>
                                        <?php elseif (!empty($plugin['purchase_url'])): ?>
                                            <span class="badge bg-orange-lt">Anfrage erforderlich</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-lt">Manuell</span>
                                        <?php endif; ?>
                                        <?php if (!empty($plugin['manual_reason'])): ?>
                                            <div class="text-muted small mt-1"><?php echo htmlspecialchars((string) $plugin['manual_reason']); ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-success-lt">Aktuell</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($plugin['new_version']) && !empty($plugin['install_supported'])): ?>
                                        <form method="post" class="d-inline" data-confirm-message="Plugin-Update für <?php echo htmlspecialchars((string) ($plugin['name'] ?? $slug), ENT_QUOTES); ?> jetzt installieren?" data-confirm-title="Plugin-Update installieren" data-confirm-text="Installieren" data-confirm-class="btn-warning" data-confirm-status-class="bg-warning">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                            <input type="hidden" name="action" value="install_plugin">
                                            <input type="hidden" name="plugin_slug" value="<?php echo htmlspecialchars($slug); ?>">
                                            <button type="submit" class="btn btn-sm btn-warning">Update</button>
                                        </form>
                                    <?php elseif (!empty($plugin['purchase_url'])): ?>
                                        <a href="<?php echo htmlspecialchars((string) $plugin['purchase_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">Anfragen / Kaufen</a>
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
                <?php
                $themeUpdateDetails = [];
                if (!empty($theme['requires_cms']) || !empty($theme['requires_php'])) {
                    $themeRequirementParts = [];
                    if (!empty($theme['requires_cms'])) {
                        $themeRequirementParts[] = '365CMS ab ' . (string) $theme['requires_cms'];
                    }
                    if (!empty($theme['requires_php'])) {
                        $themeRequirementParts[] = 'PHP ab ' . (string) $theme['requires_php'];
                    }
                    $themeUpdateDetails[] = implode(' · ', $themeRequirementParts);
                }
                if (!empty($theme['manual_reason'])) {
                    $themeUpdateDetails[] = (string) $theme['manual_reason'];
                }
                $alertData = [
                    'type' => 'warning',
                    'message' => 'Theme-Update verfügbar: ' . (string) ($theme['current_version'] ?? '-') . ' → ' . (string) ($theme['latest_version'] ?? '-'),
                    'details' => $themeUpdateDetails,
                ];
                $alertDismissible = false;
                $alertMarginClass = 'mb-0';
                require __DIR__ . '/../partials/flash-alert.php';
                ?>
                <?php if (!empty($theme['purchase_url'])): ?>
                    <div class="mt-3">
                        <a href="<?php echo htmlspecialchars((string) $theme['purchase_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">Anfragen / Kaufen</a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <?php
                $themeMessage = 'Theme ist aktuell';
                if (!empty($theme['current_version'])) {
                    $themeMessage .= ' (v' . (string) $theme['current_version'] . ')';
                }
                $alertData = ['type' => 'success', 'message' => $themeMessage];
                $alertDismissible = false;
                $alertMarginClass = 'mb-0';
                require __DIR__ . '/../partials/flash-alert.php';
                ?>
            <?php endif; ?>
            <?php if (!empty($theme['error'])): ?>
                <?php
                $alertData = [
                    'type' => 'danger',
                    'message' => 'Fehler: ' . (string) $theme['error'],
                ];
                $alertDismissible = false;
                $alertMarginClass = 'mt-2 mb-0';
                require __DIR__ . '/../partials/flash-alert.php';
                ?>
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
