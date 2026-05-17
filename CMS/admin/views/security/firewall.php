<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/** @var array $data */
$d        = $data ?? [];
$rules    = $d['rules'] ?? [];
$stats    = $d['stats'] ?? [];
$settings = $d['settings'] ?? [];
$simulation = $d['simulation'] ?? [];
$recentBlocks = is_array($d['recent_blocks'] ?? null) ? $d['recent_blocks'] : [];
$baseline = is_array($d['baseline'] ?? null) ? $d['baseline'] : [];
$baselineProfiles = is_array($baseline['profiles'] ?? null) ? $baseline['profiles'] : [];
$firewallDiagnostics = is_array($baseline['diagnostics'] ?? null) ? $baseline['diagnostics'] : [];
$typeLabels = [
    'block_ip'      => 'IP blockieren',
    'block_range'   => 'IP-Bereich blockieren',
    'allow_ip'      => 'IP erlauben',
    'block_ua'      => 'User-Agent blockieren',
    'block_country' => 'Land blockieren',
];
$typeBadges = [
    'block_ip'      => 'bg-danger',
    'block_range'   => 'bg-danger',
    'allow_ip'      => 'bg-success',
    'block_ua'      => 'bg-warning',
    'block_country' => 'bg-orange',
];
$modeLabels = [
    'simulate' => 'Simulation',
    'enforce' => 'Scharf',
];
$modeBadges = [
    'simulate' => 'bg-warning text-dark',
    'enforce' => 'bg-danger',
];
$simulationWindowHours = (int)($simulation['window_hours'] ?? 24);
$recentSimulationHits = is_array($simulation['recent_hits'] ?? null) ? $simulation['recent_hits'] : [];
?>

<div class="page-header d-print-none admin-redesign-header">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Sicherheit</div>
                <h2 class="page-title">Firewall</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body admin-redesign-page">
    <div class="container-xl admin-redesign-shell">

        <?php
        $alertData = $alert ?? [];
        $alertDismissible = true;
        $alertMarginClass = 'mb-3';
        require __DIR__ . '/../partials/flash-alert.php';
        ?>

        <div class="card admin-redesign-toolbar-card">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div class="admin-redesign-info-box flex-fill">
                    <p class="admin-redesign-info-box__title">Rollout-Logik</p>
                    <p class="admin-redesign-info-box__text">Neue Regeln erst simulieren, Treffer prüfen, danach gezielt scharfschalten und im Runtime-Log kontrollieren.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="/admin/antispam" class="btn btn-outline-secondary btn-sm">AntiSpam</a>
                    <a href="/admin/security-audit" class="btn btn-outline-primary btn-sm">Audit</a>
                </div>
            </div>
        </div>

        <div class="security-page-intro">
            <div class="security-page-intro__text">
                <p class="security-page-intro__title">Regeln kontrolliert ausrollen und nachvollziehbar scharfschalten</p>
                <p class="security-page-intro__copy">Die Firewall kombiniert klassische Blockregeln, Rate-Limit-Schutz, Härtungsprofile und einen Simulationsmodus für sichere Rollouts. Tabellen, Vorschauen und Diagnosewerte sind read-only; Änderungen laufen ausschließlich POST-/CSRF-geschützt.</p>
            </div>
        </div>

        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card security-kpi-card h-100">
                    <div class="card-body">
                        <div class="subheader">Regeln gesamt</div>
                        <div class="h1 mb-0"><?php echo (int)($stats['total'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card security-kpi-card is-success h-100">
                    <div class="card-body">
                        <div class="subheader">Aktive Regeln</div>
                        <div class="h1 mb-0 text-primary"><?php echo (int)($stats['active'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card security-kpi-card is-danger h-100">
                    <div class="card-body">
                        <div class="subheader">Scharfe Regeln</div>
                        <div class="h1 mb-0 text-danger"><?php echo (int)($stats['enforced_rules'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card security-kpi-card is-warning h-100">
                    <div class="card-body">
                        <div class="subheader">Simulationsregeln</div>
                        <div class="h1 mb-0 text-warning"><?php echo (int)($stats['simulated_rules'] ?? 0); ?></div>
                        <div class="security-kpi-note">Allow-Regeln: <?php echo (int)($stats['allowed_ips'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-deck row-cards mb-4">
            <div class="col-12">
                <div class="card security-panel-card">
                    <div class="card-header">
                        <div class="card-title-wrap">
                            <p class="card-eyebrow">Härtung</p>
                            <h3 class="card-title">Sicherheitsbaseline & Firewall-Diagnose</h3>
                            <div class="card-title-meta">Profile für Entwicklung, Staging und Produktion mit read-only Diff gegen den aktuellen Zustand. Anwendung ist optional und POST-geschützt.</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-4">
                            <?php foreach ($firewallDiagnostics as $diagnostic): ?>
                                <?php
                                $status = (string)($diagnostic['status'] ?? 'warning');
                                $badgeClass = match ($status) {
                                    'ok' => 'bg-success',
                                    'critical' => 'bg-danger',
                                    default => 'bg-warning text-dark',
                                };
                                ?>
                                <div class="col-md-6 col-xl-4">
                                    <div class="security-mini-stat h-100">
                                        <div class="d-flex justify-content-between gap-2 align-items-start mb-2">
                                            <div class="security-mini-stat__label"><?php echo htmlspecialchars((string)($diagnostic['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                            <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                        <div class="text-secondary small"><?php echo htmlspecialchars((string)($diagnostic['detail'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (empty($baselineProfiles)): ?>
                            <div class="alert alert-warning mb-0" role="alert">Keine Härtungsprofile verfügbar. Die Firewall-Regeln bleiben davon unberührt.</div>
                        <?php else: ?>
                            <div class="accordion" id="firewallBaselineProfiles">
                                <?php foreach ($baselineProfiles as $profileKey => $profile): ?>
                                    <?php
                                    $profileId = 'baseline-profile-' . preg_replace('/[^a-z0-9_-]/i', '-', (string)$profileKey);
                                    $diffRows = is_array($profile['diff'] ?? null) ? $profile['diff'] : [];
                                    $diffCount = (int)($profile['diff_count'] ?? 0);
                                    ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="<?php echo htmlspecialchars($profileId, ENT_QUOTES, 'UTF-8'); ?>-header">
                                            <button class="accordion-button <?php echo $profileKey === 'production' ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo htmlspecialchars($profileId, ENT_QUOTES, 'UTF-8'); ?>" aria-expanded="<?php echo $profileKey === 'production' ? 'true' : 'false'; ?>" aria-controls="<?php echo htmlspecialchars($profileId, ENT_QUOTES, 'UTF-8'); ?>">
                                                <span class="fw-semibold me-2"><?php echo htmlspecialchars((string)($profile['label'] ?? $profileKey), ENT_QUOTES, 'UTF-8'); ?></span>
                                                <span class="badge <?php echo $diffCount === 0 ? 'bg-success' : 'bg-warning text-dark'; ?>"><?php echo $diffCount === 0 ? 'konform' : $diffCount . ' Abweichungen'; ?></span>
                                            </button>
                                        </h2>
                                        <div id="<?php echo htmlspecialchars($profileId, ENT_QUOTES, 'UTF-8'); ?>" class="accordion-collapse collapse <?php echo $profileKey === 'production' ? 'show' : ''; ?>" aria-labelledby="<?php echo htmlspecialchars($profileId, ENT_QUOTES, 'UTF-8'); ?>-header" data-bs-parent="#firewallBaselineProfiles">
                                            <div class="accordion-body">
                                                <p class="text-secondary small mb-3"><?php echo htmlspecialchars((string)($profile['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>

                                                <div class="table-responsive security-table-wrap mb-3">
                                                    <table class="table table-sm table-vcenter security-table-modern mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>Einstellung</th>
                                                                <th>Aktuell</th>
                                                                <th>Profil</th>
                                                                <th>Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($diffRows as $row): ?>
                                                                <tr>
                                                                    <td><?php echo htmlspecialchars((string)($row['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                                    <td><?php echo htmlspecialchars((string)($row['current'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                                    <td><?php echo htmlspecialchars((string)($row['recommended'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                                    <td>
                                                                        <?php if (!empty($row['matches'])): ?>
                                                                            <span class="badge bg-success">OK</span>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-warning text-dark">Abweichend</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <form method="post" class="d-flex justify-content-end">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="action" value="apply_baseline_profile">
                                                    <input type="hidden" name="baseline_profile" value="<?php echo htmlspecialchars((string)$profileKey, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="applyFirewallBaseline(this.form, <?php echo htmlspecialchars(json_encode((string)($profile['label'] ?? $profileKey), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?: '"Profil"', ENT_QUOTES, 'UTF-8'); ?>)">Profil anwenden</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-deck row-cards mb-4">
            <div class="col-lg-6">
                <div class="card security-panel-card h-100">
                    <div class="card-header">
                        <div class="card-title-wrap">
                            <p class="card-eyebrow">Runtime</p>
                            <h3 class="card-title">Firewall-Einstellungen</h3>
                            <div class="card-title-meta">Globale Limits, Logging und das Vorschaufenster für Simulationsregeln.</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="save_settings">

                            <label class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="firewall_enabled" value="1" <?php echo ($settings['firewall_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <span class="form-check-label">Firewall aktiv</span>
                            </label>

                            <div class="mb-3">
                                <label class="form-label">Rate Limit (Anfragen)</label>
                                <input type="number" name="firewall_rate_limit" class="form-control" min="10" max="1000" value="<?php echo (int)($settings['firewall_rate_limit'] ?? 60); ?>">
                                <small class="form-hint">Max. Anfragen pro Zeitfenster</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Zeitfenster (Sekunden)</label>
                                <input type="number" name="firewall_rate_window" class="form-control" min="60" max="3600" value="<?php echo (int)($settings['firewall_rate_window'] ?? 60); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Sperrdauer (Sekunden)</label>
                                <input type="number" name="firewall_block_duration" class="form-control" min="60" max="86400" value="<?php echo (int)($settings['firewall_block_duration'] ?? 3600); ?>">
                            </div>

                            <label class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="firewall_log_enabled" value="1" <?php echo ($settings['firewall_log_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <span class="form-check-label">Zugriffs-Logging aktiv</span>
                            </label>

                            <div class="mb-3">
                                <label class="form-label">Vorschaufenster für Simulation (Stunden)</label>
                                <input type="number" name="firewall_simulation_preview_hours" class="form-control" min="1" max="168" value="<?php echo (int)($settings['firewall_simulation_preview_hours'] ?? 24); ?>">
                                <small class="form-hint">Read-only Auswertung der simulierten Treffer vor dem Scharfschalten.</small>
                            </div>

                            <div class="alert alert-warning" role="alert">
                                Neue Block-Regeln können zuerst im <strong>Simulationsmodus</strong> laufen. Treffer werden dann protokolliert, aber nicht geblockt.
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Einstellungen speichern</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card security-panel-card h-100">
                    <div class="card-header">
                        <div class="card-title-wrap">
                            <p class="card-eyebrow">Regelpflege</p>
                            <h3 class="card-title">Neue Regel hinzufügen</h3>
                            <div class="card-title-meta">Block- und Allow-Regeln mit sauber getrenntem Modus für Simulation oder Enforce.</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="add_rule">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Typ</label>
                                    <select name="rule_type" class="form-select">
                                        <?php foreach ($typeLabels as $value => $label): ?>
                                            <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Wert (IP / Pattern)</label>
                                    <input type="text" name="rule_value" class="form-control" required placeholder="192.168.1.1">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Modus</label>
                                    <select name="rule_mode" class="form-select">
                                        <option value="simulate" selected>Simulation</option>
                                        <option value="enforce">Scharf</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Grund</label>
                                    <input type="text" name="rule_reason" class="form-control" placeholder="Optional">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Ablauf</label>
                                    <input type="datetime-local" name="expires_at" class="form-control">
                                </div>
                            </div>
                            <div class="mt-3 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Regel anlegen</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-deck row-cards mb-4">
            <div class="col-lg-6">
                <div class="card security-panel-card h-100">
                    <div class="card-header">
                        <div class="card-title-wrap">
                            <p class="card-eyebrow">Vorschau</p>
                            <h3 class="card-title mb-0">Treffervorschau für Simulationsregeln</h3>
                            <div class="card-title-meta">Read-only Auswertung der letzten <?php echo $simulationWindowHours; ?> Stunden.</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (($simulation['available'] ?? true) !== true): ?>
                            <div class="alert alert-warning mb-0" role="alert">
                                Die Simulationsvorschau konnte gerade nicht geladen werden. Die Firewall-Regeln bleiben davon unberührt.
                            </div>
                        <?php else: ?>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <div class="security-mini-stat">
                                        <div class="security-mini-stat__label">Treffer gesamt</div>
                                        <div class="security-mini-stat__value"><?php echo (int)($simulation['total_hits'] ?? 0); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="security-mini-stat">
                                        <div class="security-mini-stat__label">Regeln mit Treffern</div>
                                        <div class="security-mini-stat__value"><?php echo (int)($simulation['rules_with_hits'] ?? 0); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="security-mini-stat">
                                        <div class="security-mini-stat__label">Simulationsregeln aktiv</div>
                                        <div class="security-mini-stat__value"><?php echo (int)($stats['simulated_rules'] ?? 0); ?></div>
                                    </div>
                                </div>
                            </div>

                            <?php if (empty($recentSimulationHits)): ?>
                                <div class="text-secondary">Im gewählten Vorschaufenster wurden noch keine simulierten Treffer protokolliert.</div>
                            <?php else: ?>
                                <div class="table-responsive security-table-wrap">
                                    <table class="table table-sm table-vcenter security-table-modern">
                                        <thead>
                                            <tr>
                                                <th>Zeitpunkt</th>
                                                <th>Regel</th>
                                                <th>IP</th>
                                                <th>Pfad</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentSimulationHits as $hit): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars((string)($hit['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td>
                                                        <div><?php echo htmlspecialchars((string)($hit['rule_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                                        <div class="text-secondary small"><code><?php echo htmlspecialchars((string)($hit['rule_value'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></div>
                                                    </td>
                                                    <td><code><?php echo htmlspecialchars((string)($hit['ip_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td>
                                                    <td><code><?php echo htmlspecialchars((string)($hit['request_uri'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card security-panel-card h-100">
                    <div class="card-header">
                        <div class="card-title-wrap">
                            <p class="card-eyebrow">Runtime-Log</p>
                            <h3 class="card-title">Letzte geblockte Requests</h3>
                            <div class="card-title-meta">Geblockte oder rate-limitierte Requests als schnelle Betriebsübersicht.</div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recentBlocks)): ?>
                            <div class="p-3 text-secondary">Noch keine geblockten oder rate-limitierten Requests protokolliert.</div>
                        <?php else: ?>
                            <div class="table-responsive security-table-wrap">
                                <table class="table table-sm table-vcenter mb-0 security-table-modern">
                                    <thead>
                                        <tr>
                                            <th>Zeitpunkt</th>
                                            <th>Aktion</th>
                                            <th>IP</th>
                                            <th>Regel</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentBlocks as $entry): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars((string)($entry['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><span class="badge <?php echo (($entry['action'] ?? '') === 'rate_limited') ? 'bg-warning text-dark' : 'bg-danger'; ?>"><?php echo htmlspecialchars((string)($entry['action'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                                <td><code><?php echo htmlspecialchars((string)($entry['ip_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td>
                                                <td><code><?php echo htmlspecialchars((string)($entry['rule_matched'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-deck row-cards mb-4">
            <div class="col-12">
                <div class="card security-panel-card">
                    <div class="card-header">
                        <div class="card-title-wrap">
                            <p class="card-eyebrow">Regelbestand</p>
                            <h3 class="card-title">Aktive Regeln</h3>
                            <div class="card-title-meta">Modus, Treffer, Ablauf und direkte Aktionen pro Firewall-Regel.</div>
                        </div>
                    </div>
                    <div class="table-responsive security-table-wrap">
                        <table class="table table-vcenter card-table security-table-modern">
                            <thead>
                                <tr>
                                    <th>Typ</th>
                                    <th>Wert</th>
                                    <th>Modus</th>
                                    <th>Treffer (<?php echo $simulationWindowHours; ?>h)</th>
                                    <th>Grund</th>
                                    <th>Ablauf</th>
                                    <th>Status</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rules)): ?>
                                    <?php
                                    $emptyStateColspan = 8;
                                    $emptyStateMessage = 'Keine Regeln vorhanden.';
                                    $emptyStateSubtitle = 'Legen Sie eine neue Firewall-Regel an oder aktivieren Sie vorhandene Schutzpfade.';
                                    $emptyStateIcon = 'shield';
                                    require __DIR__ . '/../partials/empty-table-row.php';
                                    ?>
                                <?php else: ?>
                                    <?php foreach ($rules as $rule): ?>
                                        <tr>
                                            <td><span class="badge <?php echo $typeBadges[$rule['rule_type']] ?? 'bg-secondary'; ?>"><?php echo htmlspecialchars($typeLabels[$rule['rule_type']] ?? $rule['rule_type'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                            <td><code><?php echo htmlspecialchars((string)$rule['value'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                            <td>
                                                <span class="badge <?php echo $modeBadges[$rule['rule_mode']] ?? 'bg-secondary'; ?>"><?php echo htmlspecialchars($modeLabels[$rule['rule_mode']] ?? $rule['rule_mode'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                <?php if (($rule['rule_type'] ?? '') === 'allow_ip'): ?>
                                                    <div class="text-secondary small mt-1">Allow-Regeln wirken immer direkt.</div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (($rule['rule_mode'] ?? '') === 'simulate'): ?>
                                                    <div class="fw-semibold"><?php echo (int)($rule['simulation_hits'] ?? 0); ?></div>
                                                    <div class="text-secondary small">
                                                        <?php if (!empty($rule['simulation_last_hit'])): ?>
                                                            Letzter Treffer: <?php echo htmlspecialchars((string)$rule['simulation_last_hit'], ENT_QUOTES, 'UTF-8'); ?>
                                                        <?php else: ?>
                                                            Noch keine Treffer
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-secondary">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars((string)($rule['reason'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <?php if (!empty($rule['expires_at'])): ?>
                                                    <?php echo htmlspecialchars((string)$rule['expires_at'], ENT_QUOTES, 'UTF-8'); ?>
                                                <?php else: ?>
                                                    <span class="text-secondary">Permanent</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ((int)$rule['is_active']): ?>
                                                    <span class="badge bg-success">Aktiv</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inaktiv</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="table-actions">
                                                <div class="security-table-actions">
                                                    <?php if (($rule['rule_type'] ?? '') !== 'allow_ip' && ($rule['reason'] ?? '') !== 'Automatisches Rate-Limit'): ?>
                                                        <form method="post" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                            <input type="hidden" name="action" value="set_rule_mode">
                                                            <input type="hidden" name="id" value="<?php echo (int)$rule['id']; ?>">
                                                            <input type="hidden" name="target_mode" value="<?php echo ($rule['rule_mode'] ?? '') === 'simulate' ? 'enforce' : 'simulate'; ?>">
                                                            <?php if (($rule['rule_mode'] ?? '') === 'simulate'): ?>
                                                                <button type="button" class="btn btn-warning btn-sm" onclick="changeFirewallRuleMode(this.form, 'enforce')">Scharfschalten</button>
                                                            <?php else: ?>
                                                                <button type="button" class="btn btn-ghost-secondary btn-sm" onclick="changeFirewallRuleMode(this.form, 'simulate')">Nur simulieren</button>
                                                            <?php endif; ?>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                        <input type="hidden" name="action" value="toggle_rule">
                                                        <input type="hidden" name="id" value="<?php echo (int)$rule['id']; ?>">
                                                        <button type="submit" class="btn btn-ghost-secondary btn-sm"><?php echo (int)$rule['is_active'] ? 'Deaktivieren' : 'Aktivieren'; ?></button>
                                                    </form>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                        <input type="hidden" name="action" value="delete_rule">
                                                        <input type="hidden" name="id" value="<?php echo (int)$rule['id']; ?>">
                                                        <button type="button" class="btn btn-ghost-danger btn-sm" onclick="deleteFirewallRule(this.form)">Löschen</button>
                                                    </form>
                                                </div>
                                            </td>
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

<script>
function submitWithFallback(form) {
    if (window.cmsSubmitFormSafely) {
        window.cmsSubmitFormSafely(form);
        return;
    }

    if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
        return;
    }

    var fallbackSubmitter = document.createElement('button');
    fallbackSubmitter.type = 'submit';
    fallbackSubmitter.hidden = true;
    form.appendChild(fallbackSubmitter);
    fallbackSubmitter.click();
    fallbackSubmitter.remove();
}

function deleteFirewallRule(form) {
    cmsConfirm({
        title: 'Firewall-Regel löschen',
        message: 'Diese Firewall-Regel wirklich löschen?',
        confirmText: 'Löschen',
        confirmClass: 'btn-danger',
        onConfirm: function() {
            submitWithFallback(form);
        }
    });
}

function changeFirewallRuleMode(form, targetMode) {
    if (targetMode !== 'enforce') {
        submitWithFallback(form);
        return;
    }

    cmsConfirm({
        title: 'Firewall-Regel scharfschalten',
        message: 'Diese Regel soll Treffer künftig aktiv blockieren. Wirklich scharfschalten?',
        confirmText: 'Scharfschalten',
        confirmClass: 'btn-danger',
        onConfirm: function() {
            submitWithFallback(form);
        }
    });
}

function applyFirewallBaseline(form, profileLabel) {
    cmsConfirm({
        title: 'Härtungsprofil anwenden',
        message: 'Das Profil „' + profileLabel + '“ überschreibt die Firewall-Basiseinstellungen. Regeln werden nicht gelöscht oder neu angelegt. Fortfahren?',
        confirmText: 'Profil anwenden',
        confirmClass: 'btn-primary',
        onConfirm: function() {
            submitWithFallback(form);
        }
    });
}
</script>
