<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$settings = $data['settings'] ?? [];
$roles = $data['roles'] ?? [];
$stats = $data['stats'] ?? [];
$providers = $data['providers'] ?? [];
$ldap = $data['ldap'] ?? [];
$jwt = $data['jwt'] ?? [];
$passkey = $data['passkey'] ?? [];
$security = $data['security'] ?? [];
$memberDashboardGeneralUrl = '/admin/member-dashboard-general';

$providerLabels = [
    'session' => 'Session-Login',
    'passkey' => 'Passkeys / WebAuthn',
    'ldap' => 'LDAP',
    'totp' => 'TOTP / MFA',
    'backup' => 'Backup-Codes',
];

$renderStatusBadge = static function (bool $enabled, string $enabledLabel = 'Aktiv', string $disabledLabel = 'Inaktiv'): string {
    $class = $enabled ? 'success' : 'secondary';
    $label = $enabled ? $enabledLabel : $disabledLabel;

    return '<span class="badge bg-' . $class . '-lt">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
};
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Benutzer &amp; Gruppen</div>
                <h2 class="page-title">Einstellungen</h2>
                <div class="text-muted mt-1">Zentrale Steuerung für Registrierung, Authentifizierung und technische Login-Provider.</div>
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

        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="font-weight-medium"><?php echo (int)($stats['total_users'] ?? 0); ?></div>
                                <div class="text-secondary">Benutzer gesamt</div>
                            </div>
                            <span class="badge bg-blue-lt">Konten</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="font-weight-medium"><?php echo (int)($stats['mfa_users'] ?? 0); ?></div>
                                <div class="text-secondary">MFA aktiviert</div>
                            </div>
                            <span class="badge bg-green-lt">TOTP</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="font-weight-medium"><?php echo (int)($stats['passkey_credentials'] ?? 0); ?></div>
                                <div class="text-secondary">Passkeys registriert</div>
                            </div>
                            <span class="badge bg-purple-lt">WebAuthn</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="font-weight-medium"><?php echo (int)($stats['backup_code_users'] ?? 0); ?></div>
                                <div class="text-secondary">Backup-Code-Sets</div>
                            </div>
                            <span class="badge bg-azure-lt">Recovery</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="save">

            <div class="row row-cards">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0">Registrierung &amp; Konten</h3>
                            <span class="badge bg-primary-lt">DB-gestützt</span>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input type="checkbox" name="registration_enabled" class="form-check-input" value="1" <?php echo !empty($settings['registration_enabled']) ? 'checked' : ''; ?>>
                                    <span class="form-check-label">Globale Benutzerregistrierung aktivieren</span>
                                </label>
                                <div class="form-hint">Früher unter <code>/admin/settings</code>. Der Schalter bleibt erhalten, ist jetzt aber logisch bei Benutzer &amp; Auth verortet.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input type="checkbox" name="member_registration_enabled" class="form-check-input" value="1" <?php echo !empty($settings['member_registration_enabled']) ? 'checked' : ''; ?>>
                                    <span class="form-check-label">Registrierung im Member-Bereich erlauben</span>
                                </label>
                                <div class="form-hint">Steuert den registrierungsbezogenen Einstieg im Member-Dashboard-Kontext.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input type="checkbox" name="member_email_verification" class="form-check-input" value="1" <?php echo !empty($settings['member_email_verification']) ? 'checked' : ''; ?>>
                                    <span class="form-check-label">E-Mail-Verifizierung für neue Mitglieder erzwingen</span>
                                </label>
                                <div class="form-hint">Sinnvoll für Community-, Netzwerk- und Portal-Setups mit sensiblen Profilen.</div>
                            </div>

                            <div>
                                <label class="form-label" for="member_default_role">Standardrolle für neue Registrierungen</label>
                                <select id="member_default_role" name="member_default_role" class="form-select">
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo htmlspecialchars((string)$role); ?>" <?php echo (($settings['member_default_role'] ?? 'member') === $role) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(ucfirst(str_replace(['-', '_'], ' ', (string)$role))); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-hint">Die Auswahl wirkt jetzt direkt auf öffentliche Registrierungen. Aus Sicherheitsgründen werden hier nur registrierungsgeeignete, nicht-administrative Rollen angeboten. Willkommens- und Dashboard-Texte bleiben weiterhin unter <a href="<?php echo htmlspecialchars($memberDashboardGeneralUrl, ENT_QUOTES, 'UTF-8'); ?>">Member Dashboard → Allgemein</a>.</div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0">Login-Schutz &amp; Passwort-Policy</h3>
                            <span class="badge bg-secondary-lt">config/app.php</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Max. Login-Versuche</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars((string)($security['max_login_attempts'] ?? 5)); ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Sperrzeit</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars((string)round(((int)($security['login_timeout_seconds'] ?? 300)) / 60)); ?> Minuten" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Admin-Session</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars((string)($security['admin_session_hours'] ?? 8)); ?> Stunden" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Member-Session</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars((string)($security['member_session_days'] ?? 30)); ?> Tage" readonly>
                                </div>
                            </div>

                            <hr>

                            <div class="mb-0">
                                <div class="form-label">Passwortanforderungen</div>
                                <ul class="mb-0 text-secondary ps-3">
                                    <li>Mindestens <?php echo (int)($security['password_min_length'] ?? 12); ?> Zeichen</li>
                                    <li>Mindestens ein Großbuchstabe</li>
                                    <li>Mindestens ein Kleinbuchstabe</li>
                                    <li>Mindestens eine Ziffer</li>
                                    <li>Mindestens ein Sonderzeichen</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0">Auth-Provider</h3>
                            <span class="badge bg-secondary-lt">Statusübersicht</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <?php foreach ($providerLabels as $key => $label): ?>
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100 d-flex justify-content-between align-items-center gap-3">
                                            <div>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($label); ?></div>
                                                <div class="text-secondary small">Provider: <?php echo htmlspecialchars($key); ?></div>
                                            </div>
                                            <?php echo $renderStatusBadge(!empty($providers[$key])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row row-cards">
                        <div class="col-lg-6">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h3 class="card-title mb-0">LDAP</h3>
                                    <?php echo $renderStatusBadge(!empty($ldap['enabled']), 'Aktiv', 'Nicht aktiv'); ?>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Host</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars((string)($ldap['host'] ?? '')); ?>" readonly>
                                    </div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Port</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars((string)($ldap['port'] ?? 389)); ?>" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Standardrolle</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars((string)($ldap['default_role'] ?? 'member')); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Base DN</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars((string)($ldap['base_dn'] ?? '')); ?>" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Erstsynchronisierung</label>
                                        <div class="d-grid gap-2">
                                            <button type="submit" name="action" value="sync_ldap" class="btn btn-outline-primary" <?php echo (empty($ldap['configured']) || empty($ldap['extension_loaded'])) ? 'disabled' : ''; ?>>LDAP-Initial-Sync starten</button>
                                        </div>
                                        <div class="form-hint">Importiert bis zu <?php echo (int)($ldap['sync_limit'] ?? 250); ?> LDAP-Einträge in lokale CMS-Benutzerkonten oder aktualisiert bestehende Zuordnungen.</div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php echo $renderStatusBadge(!empty($ldap['configured']), 'LDAP konfiguriert', 'LDAP unvollständig'); ?>
                                        <?php echo $renderStatusBadge(!empty($ldap['extension_loaded']), 'PHP-LDAP geladen', 'PHP-LDAP fehlt'); ?>
                                        <?php echo $renderStatusBadge(!empty($ldap['use_ssl']), 'SSL', 'SSL aus'); ?>
                                        <?php echo $renderStatusBadge(!empty($ldap['use_tls']), 'TLS', 'TLS aus'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h3 class="card-title mb-0">JWT &amp; API</h3>
                                    <?php echo $renderStatusBadge(!empty($jwt['configured']), 'Konfiguriert', 'Fallback aktiv'); ?>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Secret-Quelle</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars((string)($jwt['secret_source'] ?? 'AUTH_KEY (Fallback)')); ?>" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Issuer</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars((string)($jwt['issuer'] ?? '')); ?>" readonly>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label">TTL</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars((string)($jwt['ttl'] ?? 3600)); ?> Sekunden" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card sticky-top" style="top: 1rem;">
                        <div class="card-header">
                            <h3 class="card-title">Speichern &amp; Hinweise</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="small text-uppercase text-secondary mb-1">Passkeys</div>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php echo $renderStatusBadge(!empty($passkey['available']), 'Verfügbar', 'Nicht verfügbar'); ?>
                                    <?php echo $renderStatusBadge(!empty($passkey['openssl_available']), 'OpenSSL ok', 'OpenSSL fehlt'); ?>
                                    <?php echo $renderStatusBadge(!empty($passkey['site_url_configured']), 'SITE_URL ok', 'SITE_URL fehlt'); ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="small text-uppercase text-secondary mb-1">Aktive Konten</div>
                                <div class="fs-3 fw-bold"><?php echo (int)($stats['active_users'] ?? 0); ?></div>
                                <div class="text-secondary small">von <?php echo (int)($stats['total_users'] ?? 0); ?> Benutzerkonten sind aktiv.</div>
                            </div>

                            <div class="alert alert-secondary" role="alert">
                                Technische Provider-Parameter wie LDAP-, JWT- oder Rate-Limit-Werte werden aktuell aus <code>CMS/config/app.php</code> gelesen und hier bewusst nur angezeigt.
                            </div>

                            <div class="text-secondary small mb-3">Der LDAP-Erstsync nutzt die aktuelle LDAP-Konfiguration und legt fehlende lokale Konten automatisch mit der konfigurierten Standardrolle an.</div>

                            <button type="submit" class="btn btn-primary w-100 mb-2">Einstellungen speichern</button>
                            <a href="<?php echo htmlspecialchars($memberDashboardGeneralUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary w-100">Member-Dashboard öffnen</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>