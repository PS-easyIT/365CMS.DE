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
    <title>Installer deaktiviert</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .card {
            max-width: 720px;
            width: 100%;
            background: white;
            border-radius: 16px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.35);
        }
        h1 { color: #991b1b; font-size: 2rem; margin-bottom: 1rem; }
        p { color: #475569; margin-bottom: 1rem; line-height: 1.6; }
        .hint {
            background: #f8fafc;
            border-left: 4px solid #2563eb;
            padding: 1rem;
            border-radius: 8px;
            color: #1e293b;
        }
        code {
            background: #eff6ff;
            color: #1d4ed8;
            padding: 0.1rem 0.35rem;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔒 Installer deaktiviert</h1>
        <p>Für bestehende Installationen ist <code>install.php</code> öffentlich gesperrt.</p>
        <p>Der Zugriff ist nur noch für bereits angemeldete Administratoren oder über die CLI vorgesehen.</p>
        <div class="hint">
            Wenn Wartung nötig ist, melden Sie sich zuerst im Admin-Bereich an oder entfernen Sie den Installer vollständig aus dem öffentlichen Deployment.
        </div>
    </div>
</body>
</html>
