<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/** @var array $data */
$d         = $data ?? [];
$blacklist = $d['blacklist'] ?? [];
$settings  = $d['settings'] ?? [];
$stats     = $d['stats'] ?? [];
$typeLabels = ['word' => 'Wort', 'email' => 'E-Mail', 'ip' => 'IP-Adresse', 'domain' => 'Domain'];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Sicherheit</div>
                <h2 class="page-title">AntiSpam</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <?php if (!empty($alert)): ?>
            <?php $alertData = $alert; $alertMarginClass = 'mb-4'; require __DIR__ . '/../partials/flash-alert.php'; ?>
        <?php endif; ?>

        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="subheader">Spam-Kommentare</div>
                        <div class="h1 mb-0 text-danger"><?php echo (int)($stats['spam_comments'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="subheader">Blacklist: Wörter</div>
                        <div class="h1 mb-0"><?php echo (int)($stats['words'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="subheader">Blacklist: E-Mails</div>
                        <div class="h1 mb-0"><?php echo (int)($stats['emails'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="subheader">Blacklist: IPs</div>
                        <div class="h1 mb-0"><?php echo (int)($stats['ips'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-deck row-cards mb-4">
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">AntiSpam-Einstellungen</h3>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                            <input type="hidden" name="action" value="save_settings">

                            <label class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="antispam_enabled" value="1" <?php echo ($settings['antispam_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <span class="form-check-label">AntiSpam aktiv</span>
                            </label>

                            <label class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="antispam_honeypot" value="1" <?php echo ($settings['antispam_honeypot'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <span class="form-check-label">Honeypot-Feld aktivieren</span>
                            </label>

                            <label class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="antispam_block_empty_ua" value="1" <?php echo ($settings['antispam_block_empty_ua'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <span class="form-check-label">Leere User-Agents blockieren</span>
                            </label>

                            <div class="mb-3">
                                <label class="form-label">Min. Ausfüllzeit (Sekunden)</label>
                                <input type="number" name="antispam_min_time" class="form-control" min="0" max="60" value="<?php echo (int)($settings['antispam_min_time'] ?: 3); ?>">
                                <small class="form-hint">Formulare die schneller ausgefüllt werden, gelten als Spam.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Max. Links pro Beitrag</label>
                                <input type="number" name="antispam_max_links" class="form-control" min="0" max="50" value="<?php echo (int)($settings['antispam_max_links'] ?: 3); ?>">
                            </div>

                            <div class="alert alert-info mb-3">
                                Externe CAPTCHA-Dienste werden in der Public-Runtime nicht geladen. Der Schutz erfolgt lokal über Honeypot, Mindestzeit, Linklimit, User-Agent-Prüfung und Blacklist.
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Einstellungen speichern</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Blacklist-Eintrag hinzufügen</h3>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                            <input type="hidden" name="action" value="add_blacklist">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <select name="bl_type" class="form-select">
                                        <?php foreach ($typeLabels as $v => $l): ?>
                                            <option value="<?php echo $v; ?>"><?php echo $l; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-7">
                                    <input type="text" name="bl_value" class="form-control" placeholder="Wert eingeben..." required>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">Hinzufügen</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Blacklist (<?php echo count($blacklist); ?> Einträge)</h3>
                    </div>
                    <div class="table-responsive" style="max-height:500px; overflow-y:auto">
                        <table class="table table-vcenter card-table table-striped">
                            <thead>
                                <tr>
                                    <th>Typ</th>
                                    <th>Wert</th>
                                    <th>Erstellt</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($blacklist)): ?>
                                <?php
                                $emptyStateColspan = 4;
                                $emptyStateMessage = 'Keine Einträge vorhanden.';
                                $emptyStateSubtitle = 'Fügen Sie Wörter, Domains, E-Mails oder IP-Adressen zur Blacklist hinzu.';
                                $emptyStateIcon = 'shield';
                                require __DIR__ . '/../partials/empty-table-row.php';
                                ?>
                                <?php else: ?>
                                <?php foreach ($blacklist as $item): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($typeLabels[$item['type']] ?? $item['type']); ?></span></td>
                                    <td><code><?php echo htmlspecialchars($item['value']); ?></code></td>
                                    <td class="text-secondary"><?php echo htmlspecialchars($item['created_at'] ?? ''); ?></td>
                                    <td>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                                            <input type="hidden" name="action" value="delete_blacklist">
                                            <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                            <button type="button" class="btn btn-ghost-danger btn-sm" aria-label="Eintrag löschen" onclick="cmsConfirm({title:'Eintrag löschen?',message:'Der Blacklist-Eintrag wird unwiderruflich entfernt.',confirmText:'Löschen',confirmClass:'btn-danger',onConfirm:()=>this.closest('form').submit()})">×</button>
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
        </div>

    </div>
</div>
