# API-Integration Workflow – 365CMS

> **Bereich:** REST-API & externe Integrationen · **Version:** 2.3.1  
> **Core-Klasse:** `core/Api.php`  
> **Base-URL:** `https://domain.de/api/v1/`

---

## Übersicht: API-Architektur

```
Client → Request → Router → Api.php → Auth-Check → Rate-Limit
                                             ↓
                                        Permission-Check
                                             ↓
                                        Endpoint-Handler
                                             ↓
                                        Response (JSON)
```

---

## Workflow 1: Neuen API-Endpunkt registrieren

### In Core (`core/Api.php`)

```php
// Api.php – in registerRoutes():
$this->router->addRoute('GET', '/api/v1/pages', [PageController::class, 'index'], [
    'auth'       => false,       // Öffentlicher Endpunkt
    'rate_limit' => 60,          // 60 Requests/Minute pro IP
]);

$this->router->addRoute('POST', '/api/v1/pages', [PageController::class, 'store'], [
    'auth'       => true,        // Authentifizierung erforderlich
    'capability' => 'edit_posts',// Zusätzliche Capability
    'rate_limit' => 20,
]);

$this->router->addRoute('DELETE', '/api/v1/pages/{id}', [PageController::class, 'destroy'], [
    'auth'       => true,
    'capability' => 'delete_posts',
    'rate_limit' => 10,
]);
```

### In Plugin (via Hooks)

```php
// In Plugin-Datei:
\CMS\Hooks::addFilter('api_routes', function(array $routes) {
    $routes[] = [
        'method'     => 'GET',
        'path'       => '/api/v1/meinplugin/items',
        'handler'    => [\MeinPluginApiController::class, 'index'],
        'auth'       => false,
        'rate_limit' => 30,
    ];
    $routes[] = [
        'method'     => 'POST',
        'path'       => '/api/v1/meinplugin/items',
        'handler'    => [\MeinPluginApiController::class, 'store'],
        'auth'       => true,
        'capability' => 'manage_options',
        'rate_limit' => 10,
    ];
    return $routes;
});
```

---

## Workflow 2: API-Authentifizierung

### Methode 1: API-Key (Server-zu-Server)

```php
// API-Key generieren (Admin → admin/api-keys.php):
$key = bin2hex(random_bytes(32)); // 64-Zeichen hex-String
// → Gespeichert als bcrypt-Hash in cms_api_keys

// Request-Header:
// Authorization: Bearer <api-key>

// Validation in Auth-Middleware:
$header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
    $submitted = $m[1];
    // Timing-safe Vergleich!
    if ($this->validateApiKey($submitted)) {
        // Authenticated
    }
}
```

### Methode 2: Session-Cookie (für eigene Frontend-Requests)

```javascript
// Für AJAX-Requests vom eigenen Frontend:
fetch('/api/v1/user/profile', {
    method: 'GET',
    credentials: 'include', // Session-Cookie mitsenden
    headers: {
        'X-CSRF-Token': document.querySelector('[name=csrf_token]')?.value,
        'Accept': 'application/json',
    }
})
.then(r => r.json())
.then(data => console.log(data));
```

### ⚠️ Verboten: `permission_callback => '__return_true'`

```php
// NIEMALS so:
'permission_callback' => '__return_true',

// IMMER so:
'auth' => false,   // Nur für wirklich öffentliche Endpoints
// oder:
'auth' => true, 'capability' => 'read',
```

---

## Workflow 3: Standardisiertes API-Response-Format

```php
// Erfolg:
class ApiResponse {
    public static function success(mixed $data, int $status = 200): never {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'data'    => $data,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function error(string $message, int $status = 400, string $code = 'error'): never {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => [
                'code'    => $code,
                'message' => $message,
            ],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Verwendung:
ApiResponse::success(['items' => $items, 'total' => $count]);
ApiResponse::error('Nicht gefunden', 404, 'not_found');
ApiResponse::error('Keine Berechtigung', 403, 'forbidden');
ApiResponse::error('Server-Fehler', 500, 'internal_error');
```

---

## Workflow 4: Webhooks konfigurieren

**Webhooks senden POST-Requests wenn CMS-Events auftreten.**

### Webhook registrieren
```php
// Admin → admin/webhooks.php (geplant: M-18):

// Oder programmatisch:
$webhook = [
    'event'   => 'user_registered',  // CMS-Event
    'url'     => 'https://externe-app.de/webhook/cms-user',
    'secret'  => bin2hex(random_bytes(20)), // HMAC-Geheimnis
    'active'  => true,
];
```

### Webhook-Signatur prüfen (empfangende Seite)

```php
// Externe App verifiziert Payload:
$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_CMS_SIGNATURE'] ?? '';
$expected  = 'sha256=' . hash_hmac('sha256', $payload, $webhookSecret);

if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    exit('Ungültige Signatur');
}

$data = json_decode($payload, true);
```

### Webhook-Events (verfügbar)

| Event | Wann ausgelöst |
|---|---|
| `user_registered` | Neuer Benutzer erstellt |
| `page_published` | Seite veröffentlicht |
| `plugin_activated` | Plugin aktiviert |
| `backup_completed` | Backup fertig |
| `update_available` | CMS/Plugin-Update verfügbar |

---

## Workflow 5: Externe API-Integration

### Beispiel: Zahlungsanbieter (Stripe)

```php
// In Plugin cms-payments (geplant):
class StripeIntegration {
    private string $apiKey;

    public function __construct() {
        $this->apiKey = get_option('stripe_secret_key');
    }

    public function createCheckoutSession(array $items): array {
        $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERPWD        => $this->apiKey . ':',
            CURLOPT_POSTFIELDS     => http_build_query([
                'mode'        => 'payment',
                'success_url' => SITE_URL . '/danke',
                'cancel_url'  => SITE_URL . '/warenkorb',
                // ...items
            ]),
        ]);
        $response = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200) {
            throw new \RuntimeException('Stripe-API-Fehler: ' . $code);
        }

        return json_decode($response, true);
    }
}
```

---

## Checkliste: API-Sicherheit

```
AUTHENTIFIZIERUNG:
[ ] Alle schreibenden Endpunkte: auth: true
[ ] API-Keys: Hash in DB, nie im Klartext
[ ] Keine API-Keys in Code oder .env.example

RATE-LIMITING:
[ ] Öffentliche GET-Endpunkte: max. 60/min
[ ] POST/PUT/DELETE: max. 20/min
[ ] Auth-Endpunkte: max. 5/min (Brute-Force-Schutz)

EINGABE:
[ ] Alle Parameter validiert und sanitiert
[ ] Typen geprüft: (int)$_POST['id'], filter_var() für E-Mails
[ ] SQL via Prepared Statements

AUSGABE:
[ ] Keine sensiblen Daten in Responses (Passwort-Hashes, API-Keys)
[ ] json_encode() mit JSON_THROW_ON_ERROR
[ ] Content-Type: application/json gesetzt
```

---

## Referenzen

- [core/Api.php](../../CMS/core/Api.php) – API-Core
- [core/Router.php](../../CMS/core/Router.php) – Routing
- [SECURITY-HARDENING-WORKFLOW.md](SECURITY-HARDENING-WORKFLOW.md) – API-Sicherheit
- [ROADMAP_FEB2026.md](../feature/ROADMAP_FEB2026.md) – API-Roadmap-Items
