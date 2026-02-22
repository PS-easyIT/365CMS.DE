<?php
/**
 * CMS Firewall
 *
 * Schutz gegen Brute-Force, IP-Blocking und Login-Angriffe.
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Security;
use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$security = Security::instance();
$db       = Database::instance();
$prefix   = $db->getPrefix();

// ═══════════════════════════════════════════════════════════════════════════
// AUTO-MIGRATION: Tabellen & Spalten sicherstellen
// ═══════════════════════════════════════════════════════════════════════════
$migrations = [
    "CREATE TABLE IF NOT EXISTS {$prefix}blocked_ips (
        id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ip_address  VARCHAR(45)  NOT NULL UNIQUE,
        reason      VARCHAR(255) DEFAULT NULL,
        blocked_by  VARCHAR(20)  DEFAULT 'manual',
        expires_at  DATETIME     DEFAULT NULL,
        permanent   TINYINT(1)   NOT NULL DEFAULT 0,
        created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_ip      (ip_address),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS {$prefix}failed_logins (
        id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username     VARCHAR(60)  DEFAULT NULL,
        ip_address   VARCHAR(45)  DEFAULT NULL,
        user_agent   VARCHAR(255) DEFAULT NULL,
        attempted_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_ip       (ip_address),
        INDEX idx_time     (attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS {$prefix}firewall_settings (
        id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        setting_key  VARCHAR(100) NOT NULL UNIQUE,
        setting_val  TEXT         DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];
foreach ($migrations as $sql) {
    try { $db->execute($sql, []); } catch (\Throwable $e) { /* exists */ }
}

// ═══════════════════════════════════════════════════════════════════════════
// FIREWALL-EINSTELLUNGEN laden
// ═══════════════════════════════════════════════════════════════════════════
$fwDefaults = [
    'enabled'            => '1',
    'max_attempts'       => '5',
    'lockout_minutes'    => '30',
    'auto_block'         => '1',
    'whitelist_ips'      => '',
    'block_empty_ua'     => '0',
    'notify_admin'       => '0',
    'log_retention_days' => '30',
];

$fwSettings = $fwDefaults;
$rows = $db->execute("SELECT setting_key, setting_val FROM {$prefix}firewall_settings")->fetchAll();
foreach ($rows as $row) {
    $fwSettings[$row->setting_key] = $row->setting_val;
}

// Whitelist als Array
$whitelistArr = array_filter(array_map('trim', explode("\n", $fwSettings['whitelist_ips'])));

// ═══════════════════════════════════════════════════════════════════════════
// POST-Handler
// ═══════════════════════════════════════════════════════════════════════════
$success = null;
$error   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'cms_firewall')) {
        $error = 'Sicherheitsüberprüfung fehlgeschlagen.';
    } else {
        $action = $_POST['fw_action'] ?? '';

        // --- IP manuell blockieren ---
        if ($action === 'block_ip') {
            $ip        = trim($_POST['block_ip_address'] ?? '');
            $reason    = trim($_POST['block_reason'] ?? 'Manuell blockiert');
            $permanent = isset($_POST['block_permanent']) ? 1 : 0;
            $duration  = max(1, (int)($_POST['block_duration'] ?? 60));
            $expiresAt = $permanent ? null : date('Y-m-d H:i:s', time() + $duration * 60);

            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                $error = 'Ungültige IP-Adresse.';
            } elseif (in_array($ip, $whitelistArr, true)) {
                $error = "IP {$ip} steht auf der Whitelist und kann nicht blockiert werden.";
            } else {
                try {
                    $db->execute(
                        "INSERT INTO {$prefix}blocked_ips (ip_address, reason, blocked_by, expires_at, permanent)
                         VALUES (?, ?, 'manual', ?, ?)
                         ON DUPLICATE KEY UPDATE reason=VALUES(reason), blocked_by='manual',
                             expires_at=VALUES(expires_at), permanent=VALUES(permanent), updated_at=NOW()",
                        [$ip, $reason, $expiresAt, $permanent]
                    );
                    $success = "IP <strong>{$ip}</strong> wurde erfolgreich blockiert.";
                } catch (\Throwable $e) {
                    $error = 'Fehler beim Blockieren: ' . $e->getMessage();
                }
            }
        }

        // --- IP entsperren ---
        if ($action === 'unblock_ip') {
            $ip = trim($_POST['unblock_ip'] ?? '');
            if ($ip) {
                $db->execute("DELETE FROM {$prefix}blocked_ips WHERE ip_address = ?", [$ip]);
                $success = "IP <strong>{$ip}</strong> wurde entsperrt.";
            }
        }

        // --- Alle abgelaufenen IPs bereinigen ---
        if ($action === 'clean_expired') {
            $deleted = $db->execute(
                "DELETE FROM {$prefix}blocked_ips WHERE permanent = 0 AND expires_at IS NOT NULL AND expires_at < NOW()"
            )->rowCount();
            $success = "{$deleted} abgelaufene Sperr-Eintrag/Einträge bereinigt.";
        }

        // --- Login-Log leeren ---
        if ($action === 'clear_logs') {
            $days = (int)$fwSettings['log_retention_days'];
            $deleted = $db->execute(
                "DELETE FROM {$prefix}failed_logins WHERE attempted_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
                [$days]
            )->rowCount();
            $success = "{$deleted} Log-Einträge gelöscht (älter als {$days} Tage).";
        }

        // --- Auto-Block: IPs mit zu vielen Versuchen blockieren ---
        if ($action === 'run_autoblock') {
            $maxAttempts = (int)$fwSettings['max_attempts'];
            $lockoutMin  = (int)$fwSettings['lockout_minutes'];
            $candidates  = $db->execute(
                "SELECT ip_address, COUNT(*) AS attempts
                 FROM {$prefix}failed_logins
                 WHERE attempted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                 GROUP BY ip_address
                 HAVING attempts >= ?",
                [$maxAttempts]
            )->fetchAll();

            $blocked = 0;
            foreach ($candidates as $c) {
                if (in_array($c->ip_address, $whitelistArr, true)) continue;
                $db->execute(
                    "INSERT INTO {$prefix}blocked_ips (ip_address, reason, blocked_by, expires_at, permanent)
                     VALUES (?, ?, 'auto', DATE_ADD(NOW(), INTERVAL ? MINUTE), 0)
                     ON DUPLICATE KEY UPDATE reason=VALUES(reason), blocked_by='auto',
                         expires_at=VALUES(expires_at), updated_at=NOW()",
                    [$c->ip_address, "Auto-Block: {$c->attempts} fehlgeschlagene Versuche", $lockoutMin]
                );
                $blocked++;
            }
            $success = "{$blocked} IP(s) automatisch blockiert.";
        }

        // --- Einstellungen speichern ---
        if ($action === 'save_settings') {
            $newSettings = [
                'enabled'            => isset($_POST['fw_enabled']) ? '1' : '0',
                'max_attempts'       => (string)max(1, (int)($_POST['max_attempts'] ?? 5)),
                'lockout_minutes'    => (string)max(1, (int)($_POST['lockout_minutes'] ?? 30)),
                'auto_block'         => isset($_POST['auto_block']) ? '1' : '0',
                'whitelist_ips'      => trim($_POST['whitelist_ips'] ?? ''),
                'block_empty_ua'     => isset($_POST['block_empty_ua']) ? '1' : '0',
                'notify_admin'       => isset($_POST['notify_admin']) ? '1' : '0',
                'log_retention_days' => (string)max(1, (int)($_POST['log_retention_days'] ?? 30)),
            ];
            foreach ($newSettings as $k => $v) {
                $db->execute(
                    "INSERT INTO {$prefix}firewall_settings (setting_key, setting_val)
                     VALUES (?, ?)
                     ON DUPLICATE KEY UPDATE setting_val = VALUES(setting_val)",
                    [$k, $v]
                );
            }
            $fwSettings  = array_merge($fwSettings, $newSettings);
            $whitelistArr = array_filter(array_map('trim', explode("\n", $fwSettings['whitelist_ips'])));
            $success = 'Firewall-Einstellungen gespeichert.';
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// DATEN für Anzeige
// ═══════════════════════════════════════════════════════════════════════════

// Aktiver Tab
$tab = $_GET['tab'] ?? 'overview';

// Statistiken
$statBlockedTotal  = (int)$db->execute("SELECT COUNT(*) FROM {$prefix}blocked_ips")->fetchColumn();
$statBlockedActive = (int)$db->execute(
    "SELECT COUNT(*) FROM {$prefix}blocked_ips WHERE permanent=1 OR expires_at IS NULL OR expires_at > NOW()"
)->fetchColumn();
$statFailed24h     = (int)$db->execute(
    "SELECT COUNT(*) FROM {$prefix}failed_logins WHERE attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
)->fetchColumn();
$statFailed7d      = (int)$db->execute(
    "SELECT COUNT(*) FROM {$prefix}failed_logins WHERE attempted_at > DATE_SUB(NOW(), INTERVAL 7 DAY)"
)->fetchColumn();

// Top attacking IPs (last 24h)
$topAttackers = $db->execute(
    "SELECT ip_address, COUNT(*) AS attempts, MAX(attempted_at) AS last_attempt
     FROM {$prefix}failed_logins
     WHERE attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
     GROUP BY ip_address ORDER BY attempts DESC LIMIT 8"
)->fetchAll();

// Top targeted usernames
$topUsernames = $db->execute(
    "SELECT username, COUNT(*) AS attempts
     FROM {$prefix}failed_logins
     WHERE attempted_at > DATE_SUB(NOW(), INTERVAL 7 DAY) AND username IS NOT NULL
     GROUP BY username ORDER BY attempts DESC LIMIT 8"
)->fetchAll();

// Blockierte IPs (paginated)
$ipPage    = max(1, (int)($_GET['page'] ?? 1));
$ipPerPage = 20;
$ipOffset  = ($ipPage - 1) * $ipPerPage;
$ipTotal   = (int)$db->execute("SELECT COUNT(*) FROM {$prefix}blocked_ips")->fetchColumn();
$ipPages   = (int)ceil($ipTotal / $ipPerPage);
$blockedIps = $db->execute(
    "SELECT * FROM {$prefix}blocked_ips ORDER BY created_at DESC LIMIT {$ipPerPage} OFFSET {$ipOffset}"
)->fetchAll();

// Fehlgeschlagene Logins (last 100)
$failedLogins = $db->execute(
    "SELECT * FROM {$prefix}failed_logins ORDER BY attempted_at DESC LIMIT 100"
)->fetchAll();

// CSRF
$csrfToken = $security->generateToken('cms_firewall');

require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firewall – <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=20260222b">
    <?php renderAdminSidebarStyles(); ?>
    <style>
        /* ── Firewall Page Styles ─────────────────────────────── */
        .fw-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }
        .fw-stat {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1.25rem 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }
        .fw-stat__label {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
        }
        .fw-stat__value {
            font-size: 2rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1;
        }
        .fw-stat__sub {
            font-size: 0.8rem;
            color: #94a3b8;
        }
        .fw-stat--danger  .fw-stat__value { color: #dc2626; }
        .fw-stat--warning .fw-stat__value { color: #d97706; }
        .fw-stat--ok      .fw-stat__value { color: #10b981; }

        /* Tab Nav */
        .fw-tabs {
            display: flex;
            gap: 0.25rem;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 1.75rem;
        }
        .fw-tab {
            padding: 0.6rem 1.25rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: #64748b;
            text-decoration: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            border-radius: 4px 4px 0 0;
            transition: color .15s, border-color .15s;
        }
        .fw-tab:hover { color: #1e293b; }
        .fw-tab.active { color: var(--admin-primary); border-bottom-color: var(--admin-primary); }

        /* Tables */
        .fw-table-wrap { overflow-x: auto; }
        .fw-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }
        .fw-table thead th {
            background: #f8fafc;
            padding: 0.75rem 1rem;
            text-align: left;
            font-weight: 600;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }
        .fw-table tbody td {
            padding: 0.75rem 1rem;
            color: #1e293b;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .fw-table tbody tr:hover td { background: #f8fafc; }
        .fw-table .ip-mono {
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            font-weight: 600;
            color: #1e40af;
        }

        /* Block type badge */
        .fw-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .fw-badge--auto    { background: #fef3c7; color: #92400e; }
        .fw-badge--manual  { background: #dbeafe; color: #1e40af; }
        .fw-badge--perm    { background: #fee2e2; color: #991b1b; }
        .fw-badge--expired { background: #f1f5f9; color: #94a3b8; }

        /* Block-IP form */
        .fw-block-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }
        @media (max-width: 900px) {
            .fw-block-form { grid-template-columns: 1fr; }
        }
        .fw-block-form .form-group { margin-bottom: 0; }

        /* Attacker card mini grid */
        .fw-attacker-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        @media (max-width: 800px) {
            .fw-attacker-grid { grid-template-columns: 1fr; }
        }
        .fw-top-list { list-style: none; margin: 0; padding: 0; }
        .fw-top-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.55rem 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.88rem;
        }
        .fw-top-list li:last-child { border-bottom: none; }
        .fw-top-list .count-pill {
            background: #eff6ff;
            color: #1d4ed8;
            border-radius: 99px;
            padding: 0.15rem 0.6rem;
            font-size: 0.78rem;
            font-weight: 700;
        }
        .fw-top-list .count-pill.high {
            background: #fee2e2;
            color: #b91c1c;
        }

        /* Settings form */
        .fw-settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.75rem;
        }
        @media (max-width: 900px) {
            .fw-settings-grid { grid-template-columns: 1fr; }
        }
        .fw-toggle-row {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 0.9rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .fw-toggle-row:last-child { border-bottom: none; padding-bottom: 0; }
        .fw-toggle-row:first-child { padding-top: 0; }
        .fw-toggle-row .toggle-info strong { display: block; font-size: 0.93rem; color: #1e293b; margin-bottom: 0.15rem; }
        .fw-toggle-row .toggle-info p { margin: 0; font-size: 0.82rem; color: #64748b; }
        .fw-toggle-row .toggle-switch { flex-shrink: 0; margin-top: 3px; }

        /* Status banner */
        .fw-status-banner {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.75rem;
            font-weight: 600;
            font-size: 0.92rem;
        }
        .fw-status-banner.active   { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .fw-status-banner.inactive { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
    </style>
</head>
<body class="admin-body">

    <?php renderAdminSidebar('cms-firewall'); ?>

    <div class="admin-content">

        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h2>🔥 Firewall</h2>
                <p>Schutz gegen Brute-Force-Angriffe, IP-Blockierung und Login-Überwachung</p>
            </div>
            <div class="header-actions">
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="fw_action" value="run_autoblock">
                    <button type="submit" class="btn btn-secondary">⚡ Auto-Block jetzt ausführen</button>
                </form>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Firewall Status Banner -->
        <?php if ($fwSettings['enabled'] === '1'): ?>
            <div class="fw-status-banner active">🛡️ Firewall ist aktiv und schützt dein System.</div>
        <?php else: ?>
            <div class="fw-status-banner inactive">⚠️ Firewall ist deaktiviert! Gehe zu Einstellungen um sie zu aktivieren.</div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="fw-stats-grid">
            <div class="fw-stat <?php echo $statBlockedActive > 0 ? 'fw-stat--warning' : 'fw-stat--ok'; ?>">
                <span class="fw-stat__label">Aktive Sperren</span>
                <span class="fw-stat__value"><?php echo number_format($statBlockedActive); ?></span>
                <span class="fw-stat__sub"><?php echo number_format($statBlockedTotal); ?> gesamt</span>
            </div>
            <div class="fw-stat <?php echo $statFailed24h > 20 ? 'fw-stat--danger' : ($statFailed24h > 5 ? 'fw-stat--warning' : 'fw-stat--ok'); ?>">
                <span class="fw-stat__label">Fehlversuche (24h)</span>
                <span class="fw-stat__value"><?php echo number_format($statFailed24h); ?></span>
                <span class="fw-stat__sub"><?php echo number_format($statFailed7d); ?> in 7 Tagen</span>
            </div>
            <div class="fw-stat">
                <span class="fw-stat__label">Max. Versuche</span>
                <span class="fw-stat__value"><?php echo htmlspecialchars($fwSettings['max_attempts']); ?></span>
                <span class="fw-stat__sub">dann <?php echo htmlspecialchars($fwSettings['lockout_minutes']); ?> Min blockiert</span>
            </div>
            <div class="fw-stat">
                <span class="fw-stat__label">Auto-Block</span>
                <span class="fw-stat__value" style="font-size:1.1rem;line-height:2;">
                    <?php echo $fwSettings['auto_block'] === '1'
                        ? '<span style="color:#10b981;">Aktiv</span>'
                        : '<span style="color:#ef4444;">Inaktiv</span>'; ?>
                </span>
                <span class="fw-stat__sub">Whitelist: <?php echo count($whitelistArr); ?> IP(s)</span>
            </div>
        </div>

        <!-- Tab Navigation -->
        <nav class="fw-tabs">
            <a href="?tab=overview"  class="fw-tab <?php echo $tab === 'overview'  ? 'active' : ''; ?>">📊 Übersicht</a>
            <a href="?tab=blocked"   class="fw-tab <?php echo $tab === 'blocked'   ? 'active' : ''; ?>">🚫 Geblockte IPs <span style="background:#fee2e2;color:#991b1b;border-radius:99px;padding:.1rem .5rem;font-size:.75rem;margin-left:.3rem;"><?php echo $statBlockedActive; ?></span></a>
            <a href="?tab=loginlog"  class="fw-tab <?php echo $tab === 'loginlog'  ? 'active' : ''; ?>">🔐 Login-Log</a>
            <a href="?tab=settings"  class="fw-tab <?php echo $tab === 'settings'  ? 'active' : ''; ?>">⚙️ Einstellungen</a>
        </nav>

        <?php if ($tab === 'overview'): ?>
        <!-- ── ÜBERSICHT ─────────────────────────────────────────────── -->

        <!-- Manuell IP blockieren -->
        <div class="admin-card">
            <h3>🚫 IP manuell blockieren</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="fw_action" value="block_ip">
                <div class="fw-block-form">
                    <div class="form-group">
                        <label class="form-label" for="block_ip_address">IP-Adresse <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="block_ip_address" name="block_ip_address"
                               class="form-control" placeholder="z.B. 192.168.1.100" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="block_reason">Grund</label>
                        <input type="text" id="block_reason" name="block_reason"
                               class="form-control" value="Manuell blockiert" placeholder="Grund angeben">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="block_duration">Dauer (Minuten)</label>
                        <input type="number" id="block_duration" name="block_duration"
                               class="form-control" value="60" min="1">
                    </div>
                </div>
                <div style="display:flex;gap:1rem;align-items:center;margin-top:1rem;">
                    <label style="display:flex;align-items:center;gap:.4rem;font-size:.9rem;cursor:pointer;">
                        <input type="checkbox" name="block_permanent" value="1"> Permanent blockieren
                    </label>
                    <button type="submit" class="btn btn-danger">🚫 IP blockieren</button>
                </div>
            </form>
        </div>

        <!-- Top Angreifer -->
        <div class="fw-attacker-grid">
            <div class="admin-card">
                <h3>🎯 Top angreifende IPs <small style="font-weight:400;color:#64748b;font-size:.8rem;">(letzte 24h)</small></h3>
                <?php if (empty($topAttackers)): ?>
                    <div class="empty-state" style="padding:2rem 0;">
                        <p style="font-size:1.5rem;margin:0;">✅</p>
                        <p><strong>Keine Angriffe in den letzten 24 Stunden</strong></p>
                    </div>
                <?php else: ?>
                    <ul class="fw-top-list">
                        <?php foreach ($topAttackers as $att): ?>
                            <li>
                                <div>
                                    <span class="ip-mono" style="font-family:monospace;font-weight:700;color:#1e40af;">
                                        <?php echo htmlspecialchars($att->ip_address); ?>
                                    </span>
                                    <br>
                                    <small style="color:#94a3b8;">
                                        zuletzt: <?php echo htmlspecialchars($att->last_attempt); ?>
                                    </small>
                                </div>
                                <div style="display:flex;align-items:center;gap:.5rem;">
                                    <span class="count-pill <?php echo $att->attempts >= 10 ? 'high' : ''; ?>" style="background:<?php echo $att->attempts >= 10 ? '#fee2e2' : '#eff6ff'; ?>;color:<?php echo $att->attempts >= 10 ? '#b91c1c' : '#1d4ed8'; ?>;border-radius:99px;padding:.15rem .6rem;font-size:.78rem;font-weight:700;">
                                        <?php echo $att->attempts; ?>×
                                    </span>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="fw_action" value="block_ip">
                                        <input type="hidden" name="block_ip_address" value="<?php echo htmlspecialchars($att->ip_address, ENT_QUOTES); ?>">
                                        <input type="hidden" name="block_reason" value="Auto-Block aus Übersicht">
                                        <input type="hidden" name="block_duration" value="<?php echo htmlspecialchars($fwSettings['lockout_minutes']); ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Blockieren">🚫</button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="admin-card">
                <h3>👤 Meist angegriffene Benutzer <small style="font-weight:400;color:#64748b;font-size:.8rem;">(letzte 7 Tage)</small></h3>
                <?php if (empty($topUsernames)): ?>
                    <div class="empty-state" style="padding:2rem 0;">
                        <p style="font-size:1.5rem;margin:0;">✅</p>
                        <p><strong>Keine Angriffsziele erkannt</strong></p>
                    </div>
                <?php else: ?>
                    <ul class="fw-top-list">
                        <?php foreach ($topUsernames as $u): ?>
                            <li>
                                <span><?php echo htmlspecialchars($u->username ?? '(leer)'); ?></span>
                                <span class="count-pill<?php echo $u->attempts >= 5 ? ' high' : ''; ?>"
                                      style="background:<?php echo $u->attempts >= 5 ? '#fee2e2' : '#eff6ff'; ?>;
                                             color:<?php echo $u->attempts >= 5 ? '#b91c1c' : '#1d4ed8'; ?>;
                                             border-radius:99px;padding:.15rem .6rem;font-size:.78rem;font-weight:700;">
                                    <?php echo $u->attempts; ?>×
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($tab === 'blocked'): ?>
        <!-- ── GEBLOCKTE IPs ─────────────────────────────────────────── -->

        <div class="admin-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                <h3 style="margin:0;">🚫 Geblockte IP-Adressen</h3>
                <div style="display:flex;gap:.6rem;">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="fw_action" value="clean_expired">
                        <button type="submit" class="btn btn-sm btn-secondary">🧹 Abgelaufene bereinigen</button>
                    </form>
                </div>
            </div>

            <?php if (empty($blockedIps)): ?>
                <div class="empty-state">
                    <p style="font-size:2rem;margin:0;">🔓</p>
                    <p><strong>Keine IPs blockiert</strong></p>
                    <p class="text-muted">Aktuell sind keine IP-Adressen gesperrt.</p>
                </div>
            <?php else: ?>
                <div class="fw-table-wrap">
                    <table class="fw-table">
                        <thead>
                            <tr>
                                <th>IP-Adresse</th>
                                <th>Grund</th>
                                <th>Typ</th>
                                <th>Blockiert am</th>
                                <th>Läuft ab</th>
                                <th>Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($blockedIps as $bip):
                                $isExpired  = !$bip->permanent && $bip->expires_at && strtotime($bip->expires_at) < time();
                                $isPermanent = (bool)$bip->permanent;
                            ?>
                            <tr style="<?php echo $isExpired ? 'opacity:.55;' : ''; ?>">
                                <td><span class="ip-mono"><?php echo htmlspecialchars($bip->ip_address); ?></span></td>
                                <td><?php echo htmlspecialchars($bip->reason ?? '–'); ?></td>
                                <td>
                                    <?php if ($isPermanent): ?>
                                        <span class="fw-badge fw-badge--perm">🔴 Permanent</span>
                                    <?php elseif ($isExpired): ?>
                                        <span class="fw-badge fw-badge--expired">Abgelaufen</span>
                                    <?php elseif ($bip->blocked_by === 'auto'): ?>
                                        <span class="fw-badge fw-badge--auto">🤖 Auto</span>
                                    <?php else: ?>
                                        <span class="fw-badge fw-badge--manual">👤 Manuell</span>
                                    <?php endif; ?>
                                </td>
                                <td style="white-space:nowrap;font-size:.82rem;color:#64748b;">
                                    <?php echo htmlspecialchars($bip->created_at); ?>
                                </td>
                                <td style="white-space:nowrap;font-size:.82rem;color:<?php echo $isExpired ? '#94a3b8' : '#1e293b'; ?>;">
                                    <?php if ($isPermanent): ?>
                                        <span style="color:#dc2626;">∞ Permanent</span>
                                    <?php elseif ($bip->expires_at): ?>
                                        <?php echo htmlspecialchars($bip->expires_at); ?>
                                    <?php else: ?>
                                        –
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="fw_action" value="unblock_ip">
                                        <input type="hidden" name="unblock_ip" value="<?php echo htmlspecialchars($bip->ip_address, ENT_QUOTES); ?>">
                                        <button type="submit" class="btn btn-sm btn-secondary"
                                                onclick="return confirm('IP <?php echo htmlspecialchars($bip->ip_address, ENT_QUOTES); ?> entsperren?')">
                                            🔓 Entsperren
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($ipPages > 1): ?>
                <div style="display:flex;gap:.5rem;justify-content:center;margin-top:1.5rem;">
                    <?php if ($ipPage > 1): ?>
                        <a href="?tab=blocked&page=<?php echo $ipPage - 1; ?>" class="btn btn-secondary btn-sm">← Zurück</a>
                    <?php endif; ?>
                    <span style="padding:.375rem .875rem;color:#64748b;font-size:.875rem;">
                        Seite <?php echo $ipPage; ?> von <?php echo $ipPages; ?>
                    </span>
                    <?php if ($ipPage < $ipPages): ?>
                        <a href="?tab=blocked&page=<?php echo $ipPage + 1; ?>" class="btn btn-secondary btn-sm">Weiter →</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php elseif ($tab === 'loginlog'): ?>
        <!-- ── LOGIN LOG ─────────────────────────────────────────────── -->

        <div class="admin-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                <h3 style="margin:0;">🔐 Fehlgeschlagene Login-Versuche</h3>
                <div style="display:flex;gap:.6rem;align-items:center;">
                    <span style="font-size:.85rem;color:#64748b;"><?php echo number_format(count($failedLogins)); ?> Einträge (max. 100)</span>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="fw_action" value="clear_logs">
                        <button type="submit" class="btn btn-sm btn-danger"
                                onclick="return confirm('Logs älter als <?php echo htmlspecialchars($fwSettings['log_retention_days']); ?> Tage löschen?')">
                            🗑️ Alte Logs löschen
                        </button>
                    </form>
                </div>
            </div>

            <?php if (empty($failedLogins)): ?>
                <div class="empty-state">
                    <p style="font-size:2rem;margin:0;">✅</p>
                    <p><strong>Keine fehlgeschlagenen Login-Versuche</strong></p>
                </div>
            <?php else: ?>
                <div class="fw-table-wrap">
                    <table class="fw-table">
                        <thead>
                            <tr>
                                <th>IP-Adresse</th>
                                <th>Benutzername</th>
                                <th>Zeitpunkt</th>
                                <th>User-Agent</th>
                                <th>Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($failedLogins as $fl): ?>
                            <tr>
                                <td>
                                    <span class="ip-mono"><?php echo htmlspecialchars($fl->ip_address ?? '–'); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($fl->username ?? '–'); ?></td>
                                <td style="white-space:nowrap;font-size:.82rem;color:#64748b;"><?php echo htmlspecialchars($fl->attempted_at); ?></td>
                                <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.8rem;color:#94a3b8;"
                                    title="<?php echo htmlspecialchars($fl->user_agent ?? ''); ?>">
                                    <?php echo htmlspecialchars($fl->user_agent ?? '–'); ?>
                                </td>
                                <td>
                                    <?php if ($fl->ip_address): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="fw_action" value="block_ip">
                                        <input type="hidden" name="block_ip_address" value="<?php echo htmlspecialchars($fl->ip_address, ENT_QUOTES); ?>">
                                        <input type="hidden" name="block_reason" value="Aus Login-Log blockiert">
                                        <input type="hidden" name="block_duration" value="<?php echo htmlspecialchars($fwSettings['lockout_minutes']); ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="IP blockieren">🚫</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <?php elseif ($tab === 'settings'): ?>
        <!-- ── EINSTELLUNGEN ─────────────────────────────────────────── -->

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="fw_action" value="save_settings">

            <div class="fw-settings-grid">

                <!-- Allgemein -->
                <div class="admin-card">
                    <h3>🛡️ Allgemein</h3>

                    <div class="fw-toggle-row">
                        <label class="toggle-switch">
                            <input type="checkbox" name="fw_enabled" value="1" <?php echo $fwSettings['enabled'] === '1' ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                        <div class="toggle-info">
                            <strong>Firewall aktiviert</strong>
                            <p>Schaltet die gesamte Firewall-Funktion ein oder aus.</p>
                        </div>
                    </div>

                    <div class="fw-toggle-row">
                        <label class="toggle-switch">
                            <input type="checkbox" name="auto_block" value="1" <?php echo $fwSettings['auto_block'] === '1' ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                        <div class="toggle-info">
                            <strong>Auto-Block</strong>
                            <p>IPs automatisch sperren, die das Limit überschreiten.</p>
                        </div>
                    </div>

                    <div class="fw-toggle-row">
                        <label class="toggle-switch">
                            <input type="checkbox" name="block_empty_ua" value="1" <?php echo $fwSettings['block_empty_ua'] === '1' ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                        <div class="toggle-info">
                            <strong>Leere User-Agents blockieren</strong>
                            <p>Anfragen ohne User-Agent direkt ablehnen (hohe False-Positive Rate).</p>
                        </div>
                    </div>

                    <div class="fw-toggle-row">
                        <label class="toggle-switch">
                            <input type="checkbox" name="notify_admin" value="1" <?php echo $fwSettings['notify_admin'] === '1' ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                        <div class="toggle-info">
                            <strong>Admin-Benachrichtigung</strong>
                            <p>E-Mail bei Auto-Block an Admin-Adresse senden.</p>
                        </div>
                    </div>
                </div>

                <!-- Schwellenwerte -->
                <div class="admin-card">
                    <h3>📏 Schwellenwerte & Log</h3>

                    <div class="form-group">
                        <label class="form-label" for="max_attempts">Maximale Fehlversuche vor Sperre</label>
                        <input type="number" id="max_attempts" name="max_attempts"
                               class="form-control" min="1" max="100"
                               value="<?php echo htmlspecialchars($fwSettings['max_attempts']); ?>">
                        <small class="form-text">Nach dieser Anzahl fehlgeschlagener Logins wird die IP blockiert.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="lockout_minutes">Sperrdauer (Minuten)</label>
                        <input type="number" id="lockout_minutes" name="lockout_minutes"
                               class="form-control" min="1"
                               value="<?php echo htmlspecialchars($fwSettings['lockout_minutes']); ?>">
                        <small class="form-text">Wie lange eine IP nach dem Auto-Block gesperrt bleibt.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="log_retention_days">Log-Aufbewahrung (Tage)</label>
                        <input type="number" id="log_retention_days" name="log_retention_days"
                               class="form-control" min="1" max="365"
                               value="<?php echo htmlspecialchars($fwSettings['log_retention_days']); ?>">
                        <small class="form-text">Login-Logs älter als dieser Wert werden beim nächsten Bereinigen gelöscht.</small>
                    </div>
                </div>

                <!-- Whitelist -->
                <div class="admin-card" style="grid-column: 1 / -1;">
                    <h3>✅ IP-Whitelist</h3>
                    <div class="form-group">
                        <label class="form-label" for="whitelist_ips">Whitelist-IPs (eine pro Zeile)</label>
                        <textarea id="whitelist_ips" name="whitelist_ips"
                                  class="form-control" rows="6"
                                  placeholder="192.168.1.1&#10;10.0.0.1&#10;..."><?php echo htmlspecialchars($fwSettings['whitelist_ips']); ?></textarea>
                        <small class="form-text">Diese IPs werden niemals automatisch blockiert. Eigene Server-IP und Büro-IP hier eintragen!</small>
                    </div>

                    <?php if (!empty($whitelistArr)): ?>
                        <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.75rem;">
                            <?php foreach ($whitelistArr as $wip): ?>
                                <span style="background:#d1fae5;color:#065f46;border-radius:6px;padding:.25rem .65rem;font-size:.83rem;font-family:monospace;font-weight:600;">
                                    ✅ <?php echo htmlspecialchars($wip); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <div style="margin-top:1rem;">
                <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
            </div>
        </form>

        <?php endif; // end tabs ?>

    </div><!-- /.admin-content -->

    <script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
</body>
</html>
