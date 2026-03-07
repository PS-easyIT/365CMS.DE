<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/** @var array $data */
$d        = $data ?? [];
$rules    = $d['rules'] ?? [];
$stats    = $d['stats'] ?? [];
$settings = $d['settings'] ?? [];
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
?>

<!-- KPI-Karten -->
<div class="row row-deck row-cards mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Regeln gesamt</div>
                <div class="h1 mb-0"><?php echo (int)($stats['total'] ?? 0); ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Aktive Regeln</div>
                <div class="h1 mb-0 text-primary"><?php echo (int)($stats['active'] ?? 0); ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Blockierte IPs</div>
                <div class="h1 mb-0 text-danger"><?php echo (int)($stats['blocked_ips'] ?? 0); ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Erlaubte IPs</div>
                <div class="h1 mb-0 text-success"><?php echo (int)($stats['allowed_ips'] ?? 0); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row row-deck row-cards mb-4">
    <!-- Einstellungen -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Firewall-Einstellungen</h3>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                    <input type="hidden" name="action" value="save_settings">

                    <label class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="firewall_enabled" value="1" <?php echo ($settings['firewall_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <span class="form-check-label">Firewall aktiv</span>
                    </label>

                    <div class="mb-3">
                        <label class="form-label">Rate Limit (Anfragen)</label>
                        <input type="number" name="firewall_rate_limit" class="form-control" min="10" max="1000" value="<?php echo (int)($settings['firewall_rate_limit'] ?: 60); ?>">
                        <small class="form-hint">Max. Anfragen pro Zeitfenster</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Zeitfenster (Sekunden)</label>
                        <input type="number" name="firewall_rate_window" class="form-control" min="60" max="3600" value="<?php echo (int)($settings['firewall_rate_window'] ?: 60); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Sperrdauer (Sekunden)</label>
                        <input type="number" name="firewall_block_duration" class="form-control" min="60" max="86400" value="<?php echo (int)($settings['firewall_block_duration'] ?: 3600); ?>">
                    </div>

                    <label class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="firewall_log_enabled" value="1" <?php echo ($settings['firewall_log_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <span class="form-check-label">Zugriffs-Logging aktiv</span>
                    </label>

                    <button type="submit" class="btn btn-primary w-100">Einstellungen speichern</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Neue Regel -->
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Neue Regel hinzufügen</h3>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                    <input type="hidden" name="action" value="add_rule">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Typ</label>
                            <select name="rule_type" class="form-select">
                                <?php foreach ($typeLabels as $v => $l): ?>
                                    <option value="<?php echo $v; ?>"><?php echo $l; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Wert (IP / Pattern)</label>
                            <input type="text" name="rule_value" class="form-control" required placeholder="192.168.1.1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Grund</label>
                            <input type="text" name="rule_reason" class="form-control" placeholder="Optional">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Ablauf</label>
                            <input type="datetime-local" name="expires_at" class="form-control">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">+</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Regeln-Liste -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Aktive Regeln</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Typ</th>
                            <th>Wert</th>
                            <th>Grund</th>
                            <th>Ablauf</th>
                            <th>Status</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rules)): ?>
                        <tr><td colspan="6" class="text-muted text-center">Keine Regeln vorhanden</td></tr>
                        <?php else: ?>
                        <?php foreach ($rules as $rule): ?>
                        <tr>
                            <td><span class="badge <?php echo $typeBadges[$rule['rule_type']] ?? 'bg-secondary'; ?>"><?php echo htmlspecialchars($typeLabels[$rule['rule_type']] ?? $rule['rule_type']); ?></span></td>
                            <td><code><?php echo htmlspecialchars($rule['value']); ?></code></td>
                            <td><?php echo htmlspecialchars($rule['reason'] ?? '-'); ?></td>
                            <td><?php echo $rule['expires_at'] ? htmlspecialchars($rule['expires_at']) : '<span class="text-muted">Permanent</span>'; ?></td>
                            <td>
                                <?php if ((int)$rule['is_active']): ?>
                                    <span class="badge bg-success">Aktiv</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inaktiv</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                                        <input type="hidden" name="action" value="toggle_rule">
                                        <input type="hidden" name="id" value="<?php echo (int)$rule['id']; ?>">
                                        <button type="submit" class="btn btn-ghost-secondary btn-sm"><?php echo (int)$rule['is_active'] ? 'Deaktivieren' : 'Aktivieren'; ?></button>
                                    </form>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                                        <input type="hidden" name="action" value="delete_rule">
                                        <input type="hidden" name="id" value="<?php echo (int)$rule['id']; ?>">
                                        <button type="submit" class="btn btn-ghost-danger btn-sm" onclick="return confirm('Regel löschen?')">Löschen</button>
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
