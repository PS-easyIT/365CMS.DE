<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$transport = $data['transport'] ?? [];
$azure = $data['azure'] ?? [];
$graph = $data['graph'] ?? [];
$transportInfo = $data['transport_info'] ?? [];
$mailLogs = $data['mail_logs']['rows'] ?? [];
$mailStats = $data['mail_stats'] ?? [];
$queueStats = $data['queue_stats'] ?? [];
$currentTab = $currentTab ?? 'transport';
$mailBaseUrl = (defined('SITE_URL') ? SITE_URL : '') . '/admin/mail-settings';
?>
<div class="container-xl" data-mail-api-token="<?php echo htmlspecialchars((string) ($apiCsrfToken ?? '')); ?>">
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center g-3">
            <div class="col">
                <div class="page-pretitle">System</div>
                <h2 class="page-title">Mail &amp; Azure OAuth2</h2>
                <div class="text-secondary mt-1">Zentraler Mailversand, Microsoft 365 SMTP-XOAUTH2, Graph-Zugang und Versandprotokolle.</div>
            </div>
            <div class="col-auto">
                <span class="badge bg-<?php echo !empty($transportInfo['uses_smtp']) ? 'success' : 'warning'; ?>-lt">
                    <?php echo htmlspecialchars((string) ($transportInfo['transport_label'] ?? 'Mailversand')); ?>
                </span>
            </div>
        </div>
    </div>

    <?php if (!empty($alert)): ?>
        <div class="alert alert-<?php echo htmlspecialchars((string) ($alert['type'] ?? 'info')); ?> mb-4" role="alert">
            <?php echo htmlspecialchars((string) ($alert['message'] ?? '')); ?>
        </div>
    <?php endif; ?>

    <div class="mb-4">
        <ul class="nav nav-tabs">
            <?php foreach ([
                'transport' => 'Transport',
                'azure' => 'Azure OAuth2',
                'graph' => 'Microsoft Graph',
                'logs' => 'Logs',
                'queue' => 'Queue',
            ] as $tab => $label): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentTab === $tab ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($mailBaseUrl . '?tab=' . rawurlencode($tab)); ?>">
                        <?php echo htmlspecialchars($label); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php if ($currentTab === 'transport'): ?>
        <div class="row row-cards">
            <div class="col-12 col-xl-8">
                <form method="post" class="card">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="tab" value="transport">
                    <div class="card-header"><h3 class="card-title">Transport-Konfiguration</h3></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Versandmodus</label>
                                <select name="driver" class="form-select">
                                    <option value="mail" <?php echo (($transport['driver'] ?? 'mail') === 'mail') ? 'selected' : ''; ?>>PHP mail() Fallback</option>
                                    <option value="smtp" <?php echo (($transport['driver'] ?? 'mail') === 'smtp') ? 'selected' : ''; ?>>SMTP via Symfony Mailer</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Authentifizierung</label>
                                <select name="auth_mode" class="form-select">
                                    <option value="password" <?php echo (($transport['auth_mode'] ?? 'password') === 'password') ? 'selected' : ''; ?>>Benutzername + Passwort</option>
                                    <option value="oauth2" <?php echo (($transport['auth_mode'] ?? 'password') === 'oauth2') ? 'selected' : ''; ?>>Azure OAuth2 / XOAUTH2</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Test-E-Mail an</label>
                                <input type="email" name="test_recipient" class="form-control" value="<?php echo htmlspecialchars((string) ($transport['test_recipient'] ?? '')); ?>" placeholder="admin@example.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SMTP-Host</label>
                                <input type="text" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars((string) ($transport['smtp_host'] ?? '')); ?>" placeholder="smtp.office365.com">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Port</label>
                                <input type="number" name="smtp_port" min="1" max="65535" class="form-control" value="<?php echo (int) ($transport['smtp_port'] ?? 587); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Verschlüsselung</label>
                                <select name="smtp_encryption" class="form-select">
                                    <option value="tls" <?php echo (($transport['smtp_encryption'] ?? 'tls') === 'tls') ? 'selected' : ''; ?>>TLS / STARTTLS</option>
                                    <option value="ssl" <?php echo (($transport['smtp_encryption'] ?? '') === 'ssl') ? 'selected' : ''; ?>>SSL</option>
                                    <option value="" <?php echo (($transport['smtp_encryption'] ?? '') === '') ? 'selected' : ''; ?>>Keine</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SMTP-Benutzername</label>
                                <input type="text" name="smtp_username" class="form-control" value="<?php echo htmlspecialchars((string) ($transport['smtp_username'] ?? '')); ?>" placeholder="mailbox@tenant.tld">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SMTP-Passwort</label>
                                <input type="password" name="smtp_password" class="form-control" value="" placeholder="Leer lassen = vorhandenes Secret behalten">
                                <div class="form-hint">Aktuell gespeichert: <?php echo !empty($transport['secret_configured']) ? 'Ja' : 'Nein'; ?></div>
                                <label class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="clear_smtp_password" value="1">
                                    <span class="form-check-label">Gespeichertes Passwort löschen</span>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Absender-E-Mail</label>
                                <input type="email" name="from_email" class="form-control" value="<?php echo htmlspecialchars((string) ($transport['from_email'] ?? '')); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Absender-Name</label>
                                <input type="text" name="from_name" class="form-control" value="<?php echo htmlspecialchars((string) ($transport['from_name'] ?? '')); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex gap-2 justify-content-between flex-wrap">
                        <button type="submit" name="action" value="save_transport" class="btn btn-primary">Transport speichern</button>
                        <button type="submit" formaction="<?php echo htmlspecialchars($mailBaseUrl . '?tab=transport'); ?>" name="action" value="send_test_email" class="btn btn-outline-primary">Test-E-Mail senden</button>
                    </div>
                </form>
            </div>
            <div class="col-12 col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Aktive Laufzeit</h3></div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-5">Transport</dt><dd class="col-7"><?php echo htmlspecialchars((string) ($transportInfo['transport_label'] ?? '—')); ?></dd>
                            <dt class="col-5">Modus</dt><dd class="col-7"><?php echo htmlspecialchars((string) ($transportInfo['auth_mode_label'] ?? '—')); ?></dd>
                            <dt class="col-5">Host</dt><dd class="col-7"><?php echo htmlspecialchars((string) ($transportInfo['host'] ?? '—')); ?></dd>
                            <dt class="col-5">Port</dt><dd class="col-7"><?php echo htmlspecialchars((string) ($transportInfo['port'] ?? '—')); ?></dd>
                            <dt class="col-5">Username</dt><dd class="col-7 text-break"><?php echo htmlspecialchars((string) ($transportInfo['username'] ?? '—')); ?></dd>
                            <dt class="col-5">Absender</dt><dd class="col-7 text-break"><?php echo htmlspecialchars((string) ($transportInfo['from_email'] ?? '—')); ?></dd>
                        </dl>
                        <hr>
                        <div class="small text-secondary">
                            Für Microsoft 365 sollte in der Regel <code>smtp.office365.com</code>, Port <code>587</code> und <code>TLS</code> verwendet werden.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($currentTab === 'azure'): ?>
        <div class="row row-cards">
            <div class="col-12 col-xl-8">
                <form method="post" class="card">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="tab" value="azure">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Azure SMTP OAuth2</h3>
                        <span class="badge bg-<?php echo !empty($azure['configured']) ? 'success' : 'warning'; ?>-lt"><?php echo !empty($azure['configured']) ? 'Konfiguriert' : 'Unvollständig'; ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tenant-ID</label>
                                <input type="text" name="azure_tenant_id" class="form-control" value="<?php echo htmlspecialchars((string) ($azure['tenant_id'] ?? '')); ?>" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Client-ID</label>
                                <input type="text" name="azure_client_id" class="form-control" value="<?php echo htmlspecialchars((string) ($azure['client_id'] ?? '')); ?>" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Client-Secret</label>
                                <input type="password" name="azure_client_secret" class="form-control" value="" placeholder="Leer lassen = vorhandenes Secret behalten">
                                <div class="form-hint">Aktuell gespeichert: <?php echo !empty($azure['client_secret_configured']) ? 'Ja' : 'Nein'; ?></div>
                                <label class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="clear_azure_client_secret" value="1">
                                    <span class="form-check-label">Gespeichertes Client-Secret löschen</span>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mailbox / SMTP-User</label>
                                <input type="email" name="azure_mailbox" class="form-control" value="<?php echo htmlspecialchars((string) ($azure['mailbox'] ?? '')); ?>" placeholder="noreply@example.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Scope</label>
                                <input type="text" name="azure_scope" class="form-control" value="<?php echo htmlspecialchars((string) ($azure['scope'] ?? 'https://outlook.office365.com/.default')); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Token-Endpoint (optional)</label>
                                <input type="url" name="azure_token_endpoint" class="form-control" value="<?php echo htmlspecialchars((string) ($azure['token_endpoint'] ?? '')); ?>" placeholder="https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex gap-2 flex-wrap">
                        <button type="submit" name="action" value="save_azure" class="btn btn-primary">Azure speichern</button>
                        <button type="submit" name="action" value="clear_azure_cache" class="btn btn-outline-secondary">Token-Cache leeren</button>
                    </div>
                </form>
            </div>
            <div class="col-12 col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Hinweise</h3></div>
                    <div class="card-body">
                        <ul class="text-secondary small ps-3 mb-0">
                            <li class="mb-2">SMTP-Auth-Modus im Transport auf <strong>Azure OAuth2 / XOAUTH2</strong> stellen.</li>
                            <li class="mb-2">SMTP-Scope für App-only ist in der Regel <code>https://outlook.office365.com/.default</code>.</li>
                            <li class="mb-2">Exchange Online benötigt Admin-Consent und in vielen Setups einen Exchange-Service-Principal.</li>
                            <li>Die Access-Tokens werden verschlüsselt in der bestehenden <code>cms_settings</code>-Tabelle zwischengespeichert.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($currentTab === 'graph'): ?>
        <div class="row row-cards">
            <div class="col-12 col-xl-8">
                <form method="post" class="card">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="tab" value="graph">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Microsoft Graph</h3>
                        <span class="badge bg-<?php echo !empty($graph['configured']) ? 'success' : 'warning'; ?>-lt"><?php echo !empty($graph['configured']) ? 'Konfiguriert' : 'Unvollständig'; ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tenant-ID</label>
                                <input type="text" name="graph_tenant_id" class="form-control" value="<?php echo htmlspecialchars((string) ($graph['tenant_id'] ?? '')); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Client-ID</label>
                                <input type="text" name="graph_client_id" class="form-control" value="<?php echo htmlspecialchars((string) ($graph['client_id'] ?? '')); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Client-Secret</label>
                                <input type="password" name="graph_client_secret" class="form-control" value="" placeholder="Leer lassen = vorhandenes Secret behalten">
                                <div class="form-hint">Aktuell gespeichert: <?php echo !empty($graph['client_secret_configured']) ? 'Ja' : 'Nein'; ?></div>
                                <label class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="clear_graph_client_secret" value="1">
                                    <span class="form-check-label">Gespeichertes Client-Secret löschen</span>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Scope</label>
                                <input type="text" name="graph_scope" class="form-control" value="<?php echo htmlspecialchars((string) ($graph['scope'] ?? 'https://graph.microsoft.com/.default')); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Graph Base URL</label>
                                <input type="url" name="graph_base_url" class="form-control" value="<?php echo htmlspecialchars((string) ($graph['base_url'] ?? 'https://graph.microsoft.com/v1.0')); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Token-Endpoint (optional)</label>
                                <input type="url" name="graph_token_endpoint" class="form-control" value="<?php echo htmlspecialchars((string) ($graph['token_endpoint'] ?? '')); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex gap-2 flex-wrap">
                        <button type="submit" name="action" value="save_graph" class="btn btn-primary">Graph speichern</button>
                        <button type="submit" name="action" value="test_graph_connection" class="btn btn-outline-primary">Graph testen</button>
                        <button type="submit" name="action" value="clear_graph_cache" class="btn btn-outline-secondary">Token-Cache leeren</button>
                    </div>
                </form>
            </div>
            <div class="col-12 col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Lokal integrierte Basis</h3></div>
                    <div class="card-body">
                        <p class="text-secondary small mb-3">Die Graph-Anbindung läuft bewusst mit schlankem cURL-Client, damit die Deployment-Struktur von 365CMS ohne zusätzliche Composer-Abhängigkeiten stabil bleibt.</p>
                        <div class="small text-secondary">Empfohlenes Standard-Scope: <code>https://graph.microsoft.com/.default</code></div>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($currentTab === 'logs'): ?>
        <div class="row row-cards mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card"><div class="card-body"><div class="subheader">Versendet</div><div class="h1 mb-0 text-success"><?php echo (int) ($mailStats['sent'] ?? 0); ?></div></div></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card"><div class="card-body"><div class="subheader">Fehlgeschlagen</div><div class="h1 mb-0 text-danger"><?php echo (int) ($mailStats['failed'] ?? 0); ?></div></div></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card"><div class="card-body"><div class="subheader">Queue offen</div><div class="h1 mb-0"><?php echo (int) ($queueStats['pending'] ?? 0); ?></div></div></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card"><div class="card-body"><div class="subheader">Queue fehlgeschlagen</div><div class="h1 mb-0 text-warning"><?php echo (int) ($queueStats['failed'] ?? 0); ?></div></div></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Mail-Log</h3>
                <form method="post" class="m-0">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="tab" value="logs">
                    <button type="submit" name="action" value="clear_logs" class="btn btn-outline-danger btn-sm">Logs leeren</button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-striped">
                    <thead>
                        <tr>
                            <th>Zeit</th>
                            <th>Status</th>
                            <th>Empfänger</th>
                            <th>Betreff</th>
                            <th>Provider</th>
                            <th>Quelle</th>
                            <th>Fehler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mailLogs)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-secondary py-4">Noch keine Mail-Logs vorhanden.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($mailLogs as $row): ?>
                                <tr>
                                    <td class="text-nowrap"><?php echo htmlspecialchars((string) ($row->created_at ?? '')); ?></td>
                                    <td><span class="badge bg-<?php echo (($row->status ?? '') === 'sent') ? 'success' : 'danger'; ?>-lt"><?php echo htmlspecialchars((string) ($row->status ?? '')); ?></span></td>
                                    <td class="text-break"><?php echo htmlspecialchars((string) ($row->recipient ?? '')); ?></td>
                                    <td class="text-break"><?php echo htmlspecialchars((string) ($row->subject ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($row->provider ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($row->source ?? '')); ?></td>
                                    <td class="text-break text-secondary small"><?php echo htmlspecialchars((string) ($row->error_message ?? '—')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-secondary small">
                API: <code><?php echo htmlspecialchars((defined('SITE_URL') ? SITE_URL : '') . '/api/v1/admin/mail/logs'); ?></code>
            </div>
        </div>
    <?php else: ?>
        <div class="row row-cards">
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Mail-Queue</h3></div>
                    <div class="card-body">
                        <p class="text-secondary mb-3">Die Queue-Tabelle ist vorbereitet, damit zukünftige Cron-/Worker-Prozesse asynchronen Versand übernehmen können.</p>
                        <dl class="row mb-0">
                            <dt class="col-6">Offene Jobs</dt><dd class="col-6"><?php echo (int) ($queueStats['pending'] ?? 0); ?></dd>
                            <dt class="col-6">Fehlerhafte Jobs</dt><dd class="col-6"><?php echo (int) ($queueStats['failed'] ?? 0); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Nächste Schritte</h3></div>
                    <div class="card-body">
                        <ul class="text-secondary small ps-3 mb-0">
                            <li class="mb-2">Worker oder Cron-Prozess anlegen, der <code>cms_mail_queue</code> verarbeitet.</li>
                            <li class="mb-2">Retries und Backoff abhängig von SMTP-/OAuth2-Fehlern ergänzen.</li>
                            <li>Optional Webhooks oder Teams-/Slack-Benachrichtigungen auf Mail-Fehler aufsetzen.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
