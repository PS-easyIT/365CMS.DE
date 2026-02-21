# Security Architecture

**Stand:** 2026-02-18 | **Standard:** OWASP Top 10 (2021)

---

## Überblick

| Schicht | Mechanismus | Klasse |
|---------|-------------|--------|
| Transport | HTTPS erzwingen (empfohlen via Webserver) | – |
| Session | Strict-Mode, `HttpOnly`, `SameSite=Strict` | `Security::init()` |
| CSRF | Einweg-Token pro Action, 1h Ablauf | `Security::generateToken()` |
| Eingabe | `sanitize()`, `sanitizeUrl()`, `sanitizeHtml()` | `Security` |
| Ausgabe | `escape()`, `htmlspecialchars()` | `Security` |
| Auth | bcrypt cost=12, Session-Regeneration nach Login | `Auth` |
| Rate-Limiting | Session-basiert (AJAX/Login/API) | `Security::checkRateLimit()` |
| Debug | `Debug::$enabled = false` in Production | `Debug` |
| HTTP-Headers | CSP, X-Frame-Options, HSTS uvm. | `Security::setHeaders()` |

---

## 1. Session-Sicherheit

```php
// Security::init() setzt vor session_start():
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', '3600'); // 1h Inaktivitäts-Timeout
```

Nach **Login** wird die Session-ID regeneriert:
```php
session_regenerate_id(true); // in Auth::login() nach erfolgreichem Check
```

**Logout** setzt Session + Cookie explizit zurück:
```php
// Auth::logout()
session_unset();
session_destroy();
setcookie(session_name(), '', time() - 42000, '/');
```

---

## 2. CSRF-Schutz

### Flow

```
[Server] generateToken($action)
         → speichert Token in $_SESSION['csrf_tokens'][$action]
         → gibt Token zurück
         
[HTML-Form] <input type="hidden" name="csrf_token" value="<?= $token ?>">

[POST-Handler] verifyToken($token, $action)
               → prüft Existenz + Übereinstimmung + Ablauf (< 3600s)
               → löscht Token nach Validierung (Einweg)
```

### Ablauf

```
1h (3600 Sekunden) nach Generierung wird der Token als abgelaufen markiert.
Token mit Ablauf: $_SESSION['csrf_tokens'][$action] = ['token' => $hash, 'time' => time()]
```

### Verwendung

```php
// In Template/Controller:
$csrf = Security::generateToken('create_page');
echo '<input type="hidden" name="csrf_token" value="' . $csrf . '">';

// In POST-Handler:
if (!Security::verifyToken($_POST['csrf_token'] ?? '', 'create_page')) {
    wp_send_json_error(['message' => 'Ungültiger CSRF-Token'], 403);
}
```

---

## 3. Passwort-Sicherheit

```php
// Hashing – bcrypt, cost=12
$hash = Security::hashPassword($plaintext);
// → password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])

// Verifikation
$ok = Security::verifyPassword($plaintext, $hash);
// → password_verify($password, $hash)

// Stärke-Check
$result = Security::checkPasswordStrength($password);
// Prüft: Länge ≥8, Uppercase, Lowercase, Ziffern, Sonderzeichen
// Gibt zurück: ['score' => 0-5, 'strength' => 'weak|fair|strong|very_strong', 'issues' => [...]]
```

---

## 4. Rate-Limiting

**Session-basiert** – ohne externe Datenbank.

```php
// Signature
Security::checkRateLimit(string $action, int $limit, int $window): bool

// Beispiele
Security::checkRateLimit('login', 5, 300);    // 5 Login-Versuche / 5 Min
Security::checkRateLimit('ajax', 30, 60);     // 30 Requests / 1 Min
Security::checkRateLimit('api_request', 100, 60); // 100 API-Calls / 1 Min
```

### Integration in Endpoints

```php
// In AJAX-Handler
if (!Security::checkRateLimit('ajax_handler', 30, 60)) {
    wp_send_json_error(['message' => 'Rate limit exceeded'], 429);
}
```

---

## 5. Eingabe-Sanitisierung

```php
// Texte (Strip Tags + Sonderzeichen)
$clean = Security::sanitize($input);

// URLs
$url = Security::sanitizeUrl($input);

// HTML (erlaubte Tags bleiben erhalten)
$html = Security::sanitizeHtml($input);

// Integer
$id = (int) $input;

// E-Mail
$email = filter_var($input, FILTER_SANITIZE_EMAIL);
```

---

## 6. Ausgabe-Escaping

```php
// HTML-Ausgabe (XSS-Schutz)
echo Security::escape($text);
// → htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8')

// Attribute
echo htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

// CSS-Werte, z.B. font-family
echo htmlspecialchars($theme['font_family'], ENT_QUOTES, 'UTF-8');
// → wurde in ThemeManager.php als XSS-Fix nachgerüstet
```

---

## 7. HTTP Security-Headers

Gesetzt in `Security::setHeaders()` (aufgerufen via `Bootstrap::run()`):

| Header | Wert | Zweck |
|--------|------|-------|
| `X-Content-Type-Options` | `nosniff` | MIME-Sniffing unterbinden |
| `X-Frame-Options` | `SAMEORIGIN` | Clickjacking-Schutz |
| `X-XSS-Protection` | `1; mode=block` | Legacy-Browser XSS-Filter |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Referrer begrenzen |
| `Content-Security-Policy` | `default-src 'self'; script-src 'self' 'unsafe-inline'; ...` | XSS/Injection |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` | HSTS (nur HTTPS) |
| `Permissions-Policy` | `geolocation=(), microphone=(), camera=()` | Browser-APIs sperren |

---

## 8. Rollen-Modell

| Rolle | Level | Capabilities |
|-------|-------|-------------|
| `admin` | 4 | Alles |
| `editor` | 3 | Content + Medien + User lesen |
| `author` | 2 | Eigene Pages + Medien |
| `member` | 1 | Lesen + eigenes Profil bearbeiten |
| (anonym) | 0 | Nur öffentliche Seiten |

### Capability-Check

```php
// Auth-Helper
Auth::isLoggedIn();           // Session aktiv?
Auth::hasRole('admin');       // Exakte Rolle
Auth::hasMinRole('editor');   // Mindest-Level (editor, admin)

// Admin-Bereich
if (!Auth::isLoggedIn()) {
    header('Location: /login'); exit;
}
if (!Auth::hasMinRole('editor')) {
    header('Location: /403'); exit;
}
```

---

## 9. Login-Schutz

### Brute-Force-Abwehr (Auth::login)

```php
// Schritt 1: Session-Rate-Limit (5 Fehlversuche / 5 Min)
Security::checkRateLimit('login', 5, 300)

// Schritt 2: Fehlversuch in DB speichern
$db->insert('login_attempts', ['ip' => $ip, 'username' => $username, ...])

// Schritt 3: Nach 10 Fehlversuchen in 15 Min → IP temporär gesperrt
// (via Login-Attempts-Count-Query)
```

> Tabelle: `{prefix}login_attempts` (nicht `failed_logins` – wurde entfernt)

---

## 10. Debug-Modus

```php
// Production: Debug immer deaktiviert
private static bool $enabled = false;

// Activierung nur explizit:
Debug::enable(defined('CMS_DEBUG') && CMS_DEBUG);

// AJAX: Debug-Infos werden nur angehängt wenn aktiviert
Debug::enhanceAjaxResponse($response);
```

**Niemals `CMS_DEBUG = true` in Production-`config.php`!**

---

## 11. Bekannte Fixes (diese Session)

| Fix | Datei | Beschreibung |
|-----|-------|-------------|
| Logout Cookie | Auth.php | `setcookie(session_name(), '', time()-42000, '/')` hinzugefügt |
| Auth-Check API | Api.php | Auskommentierten `sendError(401)` reaktiviert |
| Security-Headers Reihenfolge | autoload.php | Doppelte `header()`-Aufrufe entfernt – `Security::init()` übernimmt |
| XSS font_family | ThemeManager.php | `htmlspecialchars()` auf Theme-CSS-Ausgabe |
| Debug in Production | Debug.php | `$enabled` Default auf `false` gesetzt |
