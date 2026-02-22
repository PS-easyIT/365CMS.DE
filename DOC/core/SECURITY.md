# 365CMS – Sicherheits-Dokumentation


---

## Inhaltsverzeichnis

1. [Sicherheits-Architektur](#1-sicherheits-architektur)
2. [CSRF-Schutz](#2-csrf-schutz)
3. [XSS-Prävention](#3-xss-prävention)
4. [SQL-Injection-Schutz](#4-sql-injection-schutz)
5. [Passwort-Sicherheit](#5-passwort-sicherheit)
6. [Rate Limiting](#6-rate-limiting)
7. [Security-Header](#7-security-header)
8. [Datei-Upload-Sicherheit](#8-datei-upload-sicherheit)
9. [Session-Sicherheit](#9-session-sicherheit)
10. [Sicherheits-Checkliste](#10-sicherheits-checkliste)

---

## 1. Sicherheits-Architektur

Das 365CMS implementiert **Defense in Depth** – mehrere unabhängige Sicherheitsebenen:

```
Request
   │
   ▼
Security::init()    ← Security-Header, Session-Start
   │
   ▼
Auth::checkSession() ← Session-Validierung
   │
   ▼
Router::dispatch()  ← URL-Validierung
   │
   ▼
Controller/Admin    ← CSRF-Nonce prüfen
   │
   ▼
Service/DB Layer    ← Input-Sanitization, Prepared Statements
```

**Implementierte Sicherheitsstandards:**
- OWASP Top 10 (2021) adressiert
- PHP 8.3 strict_types für Typ-Sicherheit
- PDO Prepared Statements (kein dynamisches SQL)
- Bcrypt für Passwort-Hashing (cost: 12)
- Rate Limiting für Login und API

---

## 2. CSRF-Schutz

Cross-Site Request Forgery (CSRF) verhindert, dass externe Seiten im Namen eines eingeloggten Nutzers Aktionen ausführen.

### Nonce in Formularen

```php
// Nonce im Formular erzeugen
$security = CMS\Security::instance();

echo '<form method="POST">';
echo '<input type="hidden" name="_nonce" value="' 
     . $security->generateNonce('profil_speichern') . '">';
echo '<input type="text" name="username">';
echo '<button type="submit">Speichern</button>';
echo '</form>';
```

### Nonce beim Verarbeiten prüfen

```php
// Am Anfang jedes POST-Handlers
$security = CMS\Security::instance();

if (!$security->verifyNonce($_POST['_nonce'] ?? '', 'profil_speichern')) {
    http_response_code(403);
    die('Sicherheitscheck fehlgeschlagen. Bitte die Seite neu laden.');
}

// Jetzt erst POST-Daten verarbeiten...
```

### Wie Nonces funktionieren

1. `generateNonce('aktion')` erzeugt einen zufälligen Token, der mit der Aktion und der Session verknüpft ist
2. Der Token wird im Formular als verstecktes Feld übermittelt
3. `verifyNonce()` prüft: Stimmt der Token? Ist er noch gültig (TTL)? Passt die Aktion?
4. Bei falscher Nonce → 403 Forbidden

---

## 3. XSS-Prävention

Cross-Site Scripting (XSS) verhindert, dass Angreifer JavaScript in eure Seite einschleusen.

### Ausgabe escapen (PFLICHT)

```php
$security = CMS\Security::instance();

// Einfacher Text (kein HTML erlaubt)
echo $security->escapeOutput($userInput);
// Entspricht: htmlspecialchars($input, ENT_QUOTES, 'UTF-8')

// Attribut-Wert
echo '<input value="' . $security->escapeOutput($value) . '">';

// URL
echo '<a href="' . $security->escapeUrl($url) . '">';
```

### Input sanitizen (beim Speichern)

```php
$security = CMS\Security::instance();

// Einfacher Text (kein HTML)
$name = $security->sanitize($_POST['name'] ?? '');

// Mit erlaubtem HTML (z.B. Editor-Inhalt)
$content = $security->sanitizeHtml($_POST['content'] ?? '');

// E-Mail
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

// URL
$url = filter_var($_POST['url'] ?? '', FILTER_SANITIZE_URL);
```

### Goldene Regel

> **Niemals ungefilterter User-Input direkt ausgeben!**
>
> Immer: Input → Sanitize → Speichern → Escapen → Ausgabe

---

## 4. SQL-Injection-Schutz

SQL-Injection ist unmöglich, wenn **immer Prepared Statements** genutzt werden.

```php
$db = CMS\Database::instance();

// RICHTIG: Prepared Statement mit Platzhaltern
$user = $db->get_row(
    "SELECT * FROM cms_users WHERE username = ? AND status = ?",
    [$username, 'active']  // Werte separat – werden niemals direkt in SQL eingefügt
);

// FALSCH: Dynamisches SQL – NIEMALS so!
// $user = $db->query("SELECT * FROM cms_users WHERE username = '$username'");
```

**Warum sind Prepared Statements sicher?**
Die Datenbankengine trennt SQL-Struktur (Abfrage) von Daten (Parameter) – Benutzerdaten können niemals als SQL interpretiert werden.

---

## 5. Passwort-Sicherheit

### Hashing

```php
$security = CMS\Security::instance();

// Passwort hashen (bcrypt, cost 12)
$hash = $security->hashPassword('MeinSicheresPasswort!');

// Passwort verifizieren
if ($security->verifyPassword($input, $hash)) {
    // Korrekt
}
```

**⚠️ Passwörter niemals im Klartext speichern oder loggen!**

### Passwort-Anforderungen (empfohlen)

```php
function validatePassword(string $password): bool {
    return strlen($password) >= 12               // Mindestlänge
        && preg_match('/[A-Z]/', $password)      // Großbuchstabe
        && preg_match('/[a-z]/', $password)      // Kleinbuchstabe
        && preg_match('/[0-9]/', $password)      // Zahl
        && preg_match('/[^A-Za-z0-9]/', $password); // Sonderzeichen
}
```

---

## 6. Rate Limiting

Verhindert Brute-Force-Angriffe und API-Missbrauch.

```php
$security = CMS\Security::instance();

// Login: Max. 5 Versuche in 5 Minuten pro IP
if (!$security->checkRateLimit(
    'login_' . $security->getClientIp(),
    5,    // Max. Versuche
    300   // Zeitfenster in Sekunden
)) {
    http_response_code(429);
    die('Zu viele Versuche. Bitte 5 Minuten warten.');
}

// API: Max. 30 Requests pro Minute
if (!$security->checkRateLimit('api_' . $userId, 30, 60)) {
    http_response_code(429);
    die('API-Rate-Limit überschritten.');
}
```

**Gespeichert in:** `cms_login_attempts` (für Logins) + Session-Daten

---

## 7. Security-Header

Folgende HTTP-Header werden automatisch von `Security::setSecurityHeaders()` gesetzt:

| Header | Wert | Schutz gegen |
|--------|------|--------------|
| `X-Frame-Options` | `SAMEORIGIN` | Clickjacking |
| `X-Content-Type-Options` | `nosniff` | MIME-Sniffing |
| `X-XSS-Protection` | `1; mode=block` | Reflected XSS |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Daten-Leakage |
| `Permissions-Policy` | `geolocation=(), microphone=(), camera=()` | Sensor-Zugriff |
| `Content-Security-Policy` | `default-src 'self'` | XSS (nur in Produktion) |

**Hinweis:** CSP wird nur wenn `CMS_DEBUG = false` gesetzt (in Produktion).

---

## 8. Datei-Upload-Sicherheit

```php
// Erlaubte Dateitypen (via Filter erweiterbar)
$allowedTypes = CMS\Hooks::applyFilters('allowed_file_types', [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'application/pdf',
]);

// Sicherer Upload-Check
function validateUpload(array $file): bool|string {
    // MIME-Type prüfen (nicht nur Dateiendung!)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes)) {
        return 'Dateityp nicht erlaubt: ' . $mimeType;
    }

    // Dateigröße prüfen
    $maxSize = CMS\Hooks::applyFilters('max_upload_size', 10 * 1024 * 1024); // 10 MB
    if ($file['size'] > $maxSize) {
        return 'Datei zu groß (Max: ' . ($maxSize / 1024 / 1024) . ' MB)';
    }

    // Dateinamen bereinigen
    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);

    return true;
}
```

**⚠️ Uploads niemals direkt ausführbar ablegen** – der `uploads/`-Ordner sollte PHP-Ausführung verbieten:

```apache
# In uploads/.htaccess
<Files "*.php">
    Deny from all
</Files>
```

---

## 9. Session-Sicherheit

```php
// Security::startSession() konfiguriert:
session_set_cookie_params([
    'lifetime' => 0,           // Sitzungscookie (kein Ablauf)
    'path'     => '/',
    'domain'   => '',
    'secure'   => !CMS_DEBUG, // HTTPS in Produktion
    'httponly' => true,        // Kein JavaScript-Zugriff
    'samesite' => 'Strict',   // CSRF-Schutz
]);
```

**Session-Regeneration nach Login:**  
Nach erfolgreichem Login wird die Session-ID erneuert (`session_regenerate_id(true)`), um Session-Fixation zu verhindern.

---

## 10. Sicherheits-Checkliste

### Vor dem Launch

- [ ] `CMS_DEBUG` auf `false` gesetzt
- [ ] Sicherheitsschlüssel in `config.php` geändert (`bin2hex(random_bytes(32))`)
- [ ] `install.php` gelöscht
- [ ] HTTPS aktiviert (TLS 1.2+)
- [ ] `logs/`, `cache/` via `.htaccess` gesperrt
- [ ] Upload-Ordner: PHP-Ausführung deaktiviert
- [ ] Starke Admin-Passwörter (12+ Zeichen)
- [ ] DB-Benutzer: nur notwendige Berechtigungen (kein GRANT, kein DROP)

### Im Betrieb

- [ ] Regelmäßige Backups (täglich)
- [ ] Activity-Log bei verdächtiger Aktivität prüfen
- [ ] Login-Attempts-Tabelle regelmäßig bereinigen
- [ ] CMS-Updates einspielen sobald verfügbar
- [ ] Plugin-Uploads: nur vertrauenswürdige Quellen

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
