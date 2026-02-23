<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fehler – IT Expert Network</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%);
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .error-card {
            background: #1e293b;
            border-radius: 1rem;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
            padding: 3rem;
            width: 100%;
            max-width: 600px;
            text-align: center;
        }
        .error-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
        }
        h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 1rem;
        }
        p {
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 2rem;
            background: #3b82f6;
            color: white;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: background 0.2s ease;
        }
        .btn:hover { background: #2563eb; }
        code {
            display: block;
            background: #0f172a;
            border-radius: 0.5rem;
            padding: 1.25rem;
            font-size: 0.875rem;
            color: #94a3b8;
            text-align: left;
            overflow: auto;
            white-space: pre-wrap;
            word-break: break-word;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon">⚠️</div>
        <h1>Es ist ein Fehler aufgetreten</h1>
        <p>Ein interner Fehler hat die Verarbeitung deiner Anfrage verhindert. Bitte versuche es später erneut oder kontaktiere den Administrator.</p>
        <a href="/" class="btn">Zur Startseite</a>
    </div>
</body>
</html>
