<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Update / Schema-Reparatur</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #10b981 0%, #059669 100%); min-height: 100vh; padding: 2rem; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; border-radius: 16px; padding: 3rem; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        h1 { color: #1e293b; font-size: 2rem; margin-bottom: 0.5rem; }
        .form-group { margin: 1.5rem 0; }
        label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: #334155; }
        input[type="text"], input[type="email"], input[type="url"] { width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem; }
        input[readonly] { background: #f8fafc; color: #94a3b8; cursor: not-allowed; }
        input:focus { outline: none; border-color: #10b981; }
        .help-text { font-size: 0.875rem; color: #64748b; margin-top: 0.25rem; }
        .error-box { background: #fef2f2; border-left: 4px solid #ef4444; padding: 1rem; margin: 1rem 0; border-radius: 4px; color: #991b1b; }
        .info-box { background: #f0fdf4; border-left: 4px solid #10b981; padding: 1rem; margin: 1rem 0; border-radius: 4px; }
        .btn { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 1rem 2rem; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; width: 100%; margin-top: 1rem; }
        .btn:hover { opacity: 0.9; }
        .btn-back { display: inline-block; color: #64748b; text-decoration: none; font-size: .875rem; margin-bottom: 1.5rem; }
        .checkbox-group { display: flex; align-items: center; gap: .5rem; }
        .section-divider { border: none; border-top: 2px solid #e2e8f0; margin: 2rem 0; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <a href="?step=1" class="btn-back">← Zurück zur Übersicht</a>
        <h1>🔄 CMS Update / Schema-Reparatur</h1>
        <p style="color: #64748b; margin-bottom: 1.5rem;">Fehlende Datenbank-Tabellen werden ergänzt. Bestehende Daten und Einstellungen <strong>bleiben vollständig erhalten</strong>.</p>

        <?php if (!empty($updateErrors)): ?>
        <div class="error-box"><strong>⚠️ Fehler:</strong><br><?php echo implode('<br>', array_map($escape, $updateErrors)); ?></div>
        <?php endif; ?>

        <div class="info-box">
            <strong>📋 Bestehende Datenbankverbindung:</strong><br>
            Datenbank: <code><?php echo $escape($existingConfig['db_name'] ?? ''); ?></code>
            @ <code><?php echo $escape($existingConfig['db_host'] ?? ''); ?></code>
            · Benutzer: <code><?php echo $escape($existingConfig['db_user'] ?? ''); ?></code><br>
            <small style="color: #065f46;">DB-Zugangsdaten und Security-Keys werden unverändert übernommen.</small>
        </div>

        <form method="post">
            <input type="hidden" name="run_update" value="1">
            <hr class="section-divider">
            <p style="font-weight: 700; color: #1e293b; margin-bottom: 1rem;">Site-Konfiguration prüfen / anpassen</p>

            <div class="form-group"><label>Site-Name</label><input type="text" name="site_name" value="<?php echo $fSiteName; ?>" required></div>
            <div class="form-group"><label>Site-URL</label><input type="url" name="site_url" value="<?php echo $fSiteUrl; ?>" required><p class="help-text">Ohne abschließenden Slash — wird aus bestehender Config übernommen.</p></div>
            <div class="form-group"><label>Admin E-Mail</label><input type="email" name="admin_email" value="<?php echo $fAdminEmail; ?>" required></div>
            <div class="form-group"><div class="checkbox-group"><input type="checkbox" name="debug_mode" id="debug_mode" <?php echo $fDebugMode ? 'checked' : ''; ?>><label for="debug_mode" style="margin: 0;">Debug-Modus aktivieren</label></div><p class="help-text">Nur für Entwicklung — in Produktion deaktivieren!</p></div>

            <hr class="section-divider">
            <p style="font-weight: 700; color: #1e293b; margin-bottom: .5rem;">Datenbankzugänge (schreibgeschützt)</p>
            <div class="form-group"><label>DB-Host</label><input type="text" value="<?php echo $escape($existingConfig['db_host'] ?? ''); ?>" readonly></div>
            <div class="form-group"><label>DB-Name</label><input type="text" value="<?php echo $escape($existingConfig['db_name'] ?? ''); ?>" readonly></div>
            <div class="form-group"><label>DB-Benutzer</label><input type="text" value="<?php echo $escape($existingConfig['db_user'] ?? ''); ?>" readonly></div>
            <button type="submit" class="btn">🚀 Update starten</button>
        </form>
    </div>
</div>
</body>
</html>
