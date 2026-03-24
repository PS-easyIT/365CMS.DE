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
    <title>CMS Installation - Schritt 1</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #669fea 0%, #4b5fa2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; border-radius: 16px; padding: 3rem; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        h1 { color: #1e293b; font-size: 2.5rem; margin-bottom: 0.5rem; }
        .subtitle { color: #64748b; margin-bottom: 2rem; }
        .check-item { display: flex; align-items: center; padding: 1rem; margin: 0.5rem 0; background: #f8fafc; border-radius: 8px; }
        .check-icon { font-size: 1.5rem; margin-right: 1rem; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .info-box { background: #eff6ff; border-left: 4px solid #3b82f6; padding: 1rem; margin: 2rem 0; border-radius: 4px; }
        .btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem 2rem; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; margin-top: 2rem; width: 100%; }
        .btn:hover { opacity: 0.9; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🚀 CMS Installation</h1>
            <p class="subtitle">Willkommen! Wir richten Ihr CMS in wenigen Schritten ein.</p>

            <h2 style="margin: 2rem 0 1rem; color: #475569;">System-Überprüfung</h2>

            <div class="check-item">
                <span class="check-icon <?php echo $phpCompatible ? 'success' : 'error'; ?>"><?php echo $phpCompatible ? '✓' : '✗'; ?></span>
                <div>
                    <strong>PHP Version</strong><br>
                    <small>Version <?php echo $escape($phpVersion); ?> (erforderlich: <?php echo $escape($requiredPhpVersion); ?>+)</small>
                </div>
            </div>

            <div class="check-item">
                <span class="check-icon <?php echo $mysqlAvailable ? 'success' : 'error'; ?>"><?php echo $mysqlAvailable ? '✓' : '✗'; ?></span>
                <div>
                    <strong>MySQL/PDO Extension</strong><br>
                    <small><?php echo $mysqlAvailable ? 'Verfügbar' : 'NICHT verfügbar - Installation nicht möglich!'; ?></small>
                </div>
            </div>

            <div class="check-item">
                <span class="check-icon <?php echo $writePermission ? 'success' : 'error'; ?>"><?php echo $writePermission ? '✓' : '✗'; ?></span>
                <div>
                    <strong>Schreibrechte</strong><br>
                    <small><?php echo $writePermission ? 'Verzeichnis ist beschreibbar (config/app.php kann geschrieben werden)' : 'KEINE Schreibrechte – config/app.php kann nicht erstellt werden!'; ?></small>
                </div>
            </div>

            <?php if ($isReinstall && is_array($existingConfig)): ?>
            <div class="info-box" style="background: #fef3c7; border-left-color: #f59e0b;">
                <strong>⚠️ Bestehende Installation erkannt!</strong><br>
                Vorhandene Konfiguration (<code>config/app.php</code>):
                <ul style="margin: 0.5rem 0 0.5rem 1.5rem;">
                    <li><strong>Datenbank:</strong> <?php echo $escape($existingConfig['db_name'] ?? 'N/A'); ?> @ <?php echo $escape($existingConfig['db_host'] ?? 'N/A'); ?></li>
                    <li><strong>Site:</strong> <?php echo $escape($existingConfig['site_name'] ?? 'N/A'); ?></li>
                    <li><strong>URL:</strong> <?php echo $escape($existingConfig['site_url'] ?? 'N/A'); ?></li>
                </ul>
            </div>
            <?php endif; ?>

            <div class="info-box">
                <strong>🌐 Automatisch erkannte Domain:</strong><br>
                <code style="font-size: 1.1rem; color: #3b82f6;"><?php echo $escape($autoUrl); ?></code><br>
                <small style="color: #64748b;">Diese wird im nächsten Schritt verwendet.</small>
            </div>

            <?php if ($isReinstall): ?>
            <form method="post" action="?step=update" style="margin-top: 1.5rem;">
                <button type="submit" class="btn" style="background: linear-gradient(135deg,#10b981 0%,#059669 100%);" <?php echo (!$phpCompatible || !$mysqlAvailable || !$writePermission) ? 'disabled' : ''; ?>>
                    🔄 Update / Schema-Reparatur (bestehende Daten bleiben erhalten)
                </button>
            </form>
            <details style="margin-top: 1rem; border: 2px solid #fca5a5; border-radius: 8px; overflow: hidden;">
                <summary style="padding: .75rem 1rem; background: #fef2f2; color: #991b1b; font-weight: 600; cursor: pointer; list-style: none;">
                    ⚠️ Komplett-Neuinstallation (alle Daten löschen) – hier aufklappen
                </summary>
                <div style="padding: 1rem; background: #fff;">
                    <p style="color: #7f1d1d; margin-bottom: 1rem; font-size: .9rem;">
                        <strong>ACHTUNG:</strong> Löscht <strong>alle Datenbank-Tabellen</strong> unwiderruflich! Bitte vorher ein Backup erstellen.
                    </p>
                    <form method="post" action="?step=2">
                        <input type="hidden" name="reinstall" value="1">
                        <button type="submit" class="btn" style="background: linear-gradient(135deg,#ef4444 0%,#b91c1c 100%);" <?php echo (!$phpCompatible || !$mysqlAvailable || !$writePermission) ? 'disabled' : ''; ?>>
                            ❌ Ja, komplett neu installieren (alle Daten löschen)
                        </button>
                    </form>
                </div>
            </details>
            <?php else: ?>
            <form method="post" action="?step=2" style="margin-top: 1.5rem;">
                <button type="submit" class="btn" <?php echo (!$phpCompatible || !$mysqlAvailable || !$writePermission) ? 'disabled' : ''; ?>>
                    Weiter zu Schritt 2 →
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
