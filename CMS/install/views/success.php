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
    <title><?php echo !empty($success['is_update']) ? 'CMS Update erfolgreich' : 'CMS Installation erfolgreich'; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #10b981 0%, #059669 100%); min-height: 100vh; padding: 2rem; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; border-radius: 16px; padding: 3rem; box-shadow: 0 20px 60px rgba(0,0,0,0.3); text-align: center; }
        .icon { font-size: 4rem; margin-bottom: 1rem; }
        h1 { color: #1e293b; font-size: 2rem; margin-bottom: 0.5rem; }
        p { color: #64748b; margin-bottom: 1rem; }
        .summary { text-align: left; background: #f8fafc; border-radius: 12px; padding: 1.5rem; margin: 2rem 0; }
        .summary dt { font-weight: 700; color: #1e293b; }
        .summary dd { margin: 0 0 1rem 0; color: #475569; }
        .btn-group { display: flex; gap: 1rem; margin-top: 2rem; justify-content: center; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 1rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600; }
        .btn-primary { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="icon">✅</div>
        <h1><?php echo !empty($success['is_update']) ? 'Update erfolgreich abgeschlossen' : 'Installation erfolgreich abgeschlossen'; ?></h1>
        <p><?php echo !empty($success['is_update']) ? '365CMS wurde aktualisiert und ist wieder bereit für den nächsten Einsatz.' : '365CMS ist eingerichtet und bereit für den ersten Login.'; ?></p>

        <dl class="summary">
            <dt>Website</dt>
            <dd><?php echo $escape($success['site_url'] ?? ''); ?></dd>
            <?php if (!empty($success['username'])): ?>
            <dt>Administrator</dt>
            <dd><?php echo $escape($success['username']); ?></dd>
            <?php endif; ?>
            <?php if (!empty($success['tables_created'])): ?>
            <dt>Neu angelegte Tabellen</dt>
            <dd><?php echo $escape(implode(', ', $success['tables_created'])); ?></dd>
            <?php endif; ?>
            <dt>Nächster Schritt</dt>
            <dd>Melden Sie sich im Backend an und prüfen Sie Einstellungen, Theme und Mailversand.</dd>
        </dl>

        <div class="btn-group">
            <a href="<?php echo $escape($success['site_url'] ?? 'index.php'); ?>" class="btn btn-primary">Zur Website</a>
            <a href="<?php echo $escape(rtrim((string) ($success['site_url'] ?? ''), '/')); ?>/admin" class="btn btn-secondary">Zum Admin-Bereich</a>
        </div>
    </div>
</div>
</body>
</html>
