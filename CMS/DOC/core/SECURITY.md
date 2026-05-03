<!-- UPDATED: 2026-05-02 -->
# 365CMS – Sicherheitsarchitektur
> **Stand:** 2026-05-02 | **Version:** 2.9.248 | **Status:** Aktuell

Umfassende Dokumentation der Sicherheitsmechanismen, Authentifizierungsverfahren und
Secure-Coding-Grundsätze im 365CMS.

Die Datei beschreibt die aktuell beabsichtigte Sicherheitsarchitektur der 2.9.0-Laufzeit. Für Incident-Analyse und harte Wahrheit gelten immer zusätzlich die produktiven Entry-Points, Router-Prüfungen, Services und Admin-Handler der laufenden Installation.

---

## Inhaltsverzeichnis

1. [Übersicht – Defense in Depth](#1-übersicht--defense-in-depth)
2. [Authentifizierung](#2-authentifizierung)
3. [CSRF-Schutz](#3-csrf-schutz)
4. [XSS-Schutz](#4-xss-schutz)
5. [SQL-Injection-Schutz](#5-sql-injection-schutz)
6. [Dateiupload-Sicherheit](#6-dateiupload-sicherheit)
7. [Session-Sicherheit](#7-session-sicherheit)
8. [Security-Headers](#8-security-headers)
9. [Sicherheitsrelevante Konfiguration](#9-sicherheitsrelevante-konfiguration)
10. [Known Limitations](#10-known-limitations)

---

## 1. Übersicht – Defense in Depth

<!-- UPDATED: 2026-03-08 -->

Das 365CMS setzt auf **mehrere unabhängige Sicherheitsschichten**. Jede Schicht schützt
eigenständig – fällt eine aus, greifen die übrigen.

```
Request
  │
  ▼
Security::init()          ← Security-Headers, CSP-Nonce, Session-Start
  │
  ▼
SecurityRuntimeService    ← Firewall-Regeln, Rate-Limits, temporäre Sperren
    │
    ▼
Auth::checkSession()      ← Session-Validierung, Lifetime, MFA-Status
  │
  ▼
Router::dispatch()        ← URL-Validierung, rollenbasierte Prüfung
  │
  ▼
Controller / Admin        ← CSRF-Token prüfen, Capabilities prüfen
  │
  ▼
Service / DB-Layer        ← Input-Sanitization, Prepared Statements
  │
  ▼
AuditLogger               ← Alle sicherheitsrelevanten Aktionen protokollieren
```

**Adressierte Standards:**

| Standard | Abdeckung |
|----------|-----------|
| OWASP Top 10 (2021) | A01–A10 adressiert |
| PHP 8.3 `strict_types` | In allen Core-Dateien |
| PDO Native Prepared Statements | Kein emuliertes Prepare |
| Bcrypt (cost 12) | Passwort-Hashing |
| CSP Level 3 + Trusted Types | Nonce-basiert, Report-Only im Debug |
| HSTS Preload | max-age 1 Jahr, includeSubDomains |
| Runtime-Asset-Policy | keine externen Embed-CDNs; Google Fonts nur als optionaler Fallback für Themes, lokale Fonts haben Vorrang |

### 1.1 Zweitprüfung 2026-05-02

Die Security-Zweitprüfung für `2.9.248` hat folgende Punkte gehärtet:

- Default-Theme lädt Google-Fonts nur noch optional, wenn lokale Fonts nicht aktiv sind und der Theme-Customizer den Fallback erlaubt.
- PHP-CSP und Apache-Fallback erlauben genehmigte Google-Font-Hosts wieder als optionalen Fallback; `img-src` bleibt auf `self`, `data:` und `blob:` begrenzt.
- Der Font Manager kann erkannte Google-Fonts weiterhin lokal spiegeln; aktivierte lokale Fonts unterdrücken anschließend den Remote-Fallback im Frontend.
- Editor.js lädt die externen `embed.umd.js`- und `columns.umd.js`-Bundles nicht mehr; bestehende Embed-Blöcke rendern nur noch als sicherer Link statt als Iframe.
- AntiSpam bewirbt keine externen CAPTCHA-Dienste mehr und bleibt lokal: Honeypot, Mindestzeit, Linklimit, User-Agent und Blacklist.
- Security-Audit prüft zusätzlich Firewall-Runtime, AntiSpam-Runtime und Fremdasset-Indikatoren.

**Noch offen:** Kontaktformulare nutzen weiterhin eigene lokale Honeypot-Logik statt des zentralen AntiSpam-Services; `security_log` ist gleichzeitig Rate-Limit-Zähler und Audit-Tabelle, was bei sehr hohem Traffic später aggregiert werden sollte.

---

## 2. Authentifizierung

<!-- UPDATED: 2026-03-08 -->

Das 365CMS unterstützt mehrere Authentifizierungsverfahren. Der zentrale Dispatcher ist
`CMS\Auth\AuthManager` (Datei `CMS/core/Auth/AuthManager.php`).

### 2.1 Session-basierte Authentifizierung

**Datei:** `CMS/core/Auth.php`

Der Standard-Login-Flow mit Username/Passwort, Rate-Limiting und optionalem MFA-Gate:

```php
public function login(string $username, string $password): bool|string
{
    $security = Security::instance();
    $ip       = $security->getClientIp();

    // 1. Rate-Limiting (DB-basiert)
    if (!$security->checkDbRateLimit($ip, 'login', MAX_LOGIN_ATTEMPTS, LOGIN_TIMEOUT)) {
        AuditLogger::instance()->log(AuditLogger::CAT_SECURITY, 'login_blocked',
            'Brute-Force block for IP: ' . $ip);
        return 'Too many login attempts. Wait ' . (LOGIN_TIMEOUT / 60) . ' minutes.';
    }

    // 2. Prepared Statement – SQL-Injection ausgeschlossen
    $stmt = $db->prepare(
        "SELECT * FROM {$db->getPrefix()}users WHERE username = ? OR email = ? LIMIT 1"
    );
    $stmt->execute([$username, $username]);
    $user = $stmt->fetchObject();

    // 3. Passwort-Verifikation (Bcrypt)
    if (!$security->verifyPassword($password, $user->password)) {
        AuditLogger::instance()->loginFailed($username);
        return 'Invalid credentials';
    }

    // 4. MFA-Gate
    if ($this->isMfaEnabled((int) $user->id)) {
        $_SESSION['mfa_pending_user_id'] = (int) $user->id;
        return 'MFA_REQUIRED';
    }

    // 5. Session aufbauen
    $_SESSION['user_id']            = $user->id;
    $_SESSION['user_role']          = $user->role;
    $_SESSION['session_start_time'] = time();
    session_regenerate_id(true);

    CMS\Hooks::doAction('user_logged_in', (int) $user->id);
    return true;
}
```

**Session-Lebensdauer (rollenbasiert):**

| Rolle | Lebensdauer | Konstante |
|-------|-------------|-----------|
| Admin | 8 Stunden | `SESSION_LIFETIME_ADMIN = 28_800` |
| Member | 30 Tage | `SESSION_LIFETIME_MEMBER = 2_592_000` |

### 2.2 JWT (`firebase/php-jwt` 7.0.3)

**Datei:** `CMS/core/Services/JwtService.php`

Stateless Token-Authentifizierung für API-Endpunkte.

```php
// Token erzeugen
$token = $jwtService->generateToken($userId, ['role' => 'admin']);

// Token validieren
$payload = $jwtService->validateToken($token);
// → stdClass { sub: 42, iss: "https://example.com", exp: … }

// Refresh-Token (30 Tage Gültigkeit)
$refresh = $jwtService->generateRefreshToken($userId);
```

| Eigenschaft | Wert |
|-------------|------|
| Algorithmus | HS256 (HMAC-SHA256) |
| Standard-TTL | 3 600 s (1 Stunde) |
| Refresh-TTL | 2 592 000 s (30 Tage) |
| Secret | `JWT_SECRET` Konstante (Fallback: `AUTH_KEY`) |
| Issuer | `JWT_ISSUER` Konstante (Fallback: `SITE_URL`) |
| JWT-ID (`jti`) | 128-Bit-Zufall (`bin2hex(random_bytes(16))`) |

### 2.3 TOTP / 2FA (`robthree/twofactorauth` 3.0.3)

**Datei:** `CMS/core/Auth/MFA/TotpAdapter.php`

Zeitbasierte Einmalpasswörter gemäß RFC 6238.

```php
// Setup starten – QR-Code generieren
$setup = $totpAdapter->startSetup($userId, 'user@example.com');
// → ['secret' => 'JBSWY3DP…', 'qr_data_uri' => 'data:image/png;base64,…']

// Code bestätigen – MFA aktivieren
$ok = $totpAdapter->confirmSetup($userId, '485293');

// Login-Verifizierung (6-stellig, Zeitfenster ±1)
$valid = $totpAdapter->verifyCodeWithSecret($secret, $code);
```

**Backup-Codes** werden über `BackupCodesManager::instance()` verwaltet und als Einmal-Codes in
`cms_user_meta` gespeichert.

### 2.4 WebAuthn / Passkeys (`lbuchs/webauthn` 2.2.0)

**Datei:** `CMS/core/Auth/Passkey/WebAuthnAdapter.php`

FIDO2-basierte passwortlose Authentifizierung.

```php
// Registrierung – Challenge erzeugen
$options = $webAuthn->getRegistrationOptions($userId, $userName, $displayName);
// Client führt navigator.credentials.create() aus

// Login – Assertion prüfen
$assertion = $webAuthn->getLoginOptions($userId);
// Client führt navigator.credentials.get() aus
```

| Sicherheitsmerkmal | Beschreibung |
|--------------------|--------------|
| Challenge-Response | 60-Sekunden-Timeout |
| Signatur-Counter | Erkennt geklonte Authenticators |
| Exclude-List | Verhindert Doppel-Registrierung |
| Credential-Speicher | `cms_passkey_credentials` Tabelle |

### 2.5 LDAP / Active Directory (`directorytree/ldaprecord` 4.0.2)

**Datei:** `CMS/core/Auth/LDAP/LdapAuthProvider.php`

Integration mit bestehenden Verzeichnisdiensten.

```php
$ldap = LdapAuthProvider::instance();
$user = $ldap->authenticate($username, $password);

if ($user !== null) {
    // Lokalen CMS-Benutzer synchronisieren
    $localId = $ldap->syncLocalUser($user);
}
```

**Konfiguration** (`config/app.php`):

| Konstante | Beschreibung |
|-----------|--------------|
| `LDAP_HOST` | LDAP-Server-Adresse |
| `LDAP_PORT` | Port (Standard: 389 / 636 für SSL) |
| `LDAP_BASE_DN` | Basis-DN für die Suche |
| `LDAP_USERNAME` | Service-Account DN |
| `LDAP_PASSWORD` | Service-Account Passwort |
| `LDAP_USE_SSL` | SSL-Verbindung aktivieren |
| `LDAP_USE_TLS` | STARTTLS aktivieren |
| `LDAP_FILTER` | LDAP-Suchfilter |
| `LDAP_DEFAULT_ROLE` | Standardrolle für synchronisierte Benutzer |

---

## 3. CSRF-Schutz

<!-- UPDATED: 2026-03-08 -->

**Datei:** `CMS/core/Security.php` – Methoden `generateToken()`, `verifyToken()`,
`verifyPersistentToken()`.

### Token-Generierung

```php
$security = CMS\Security::instance();

// Token für eine spezifische Aktion erzeugen
$token = $security->generateToken('profil_speichern');
// → 64-Zeichen-Hex-String (256 Bit, random_bytes(32))

echo '<form method="POST">';
echo '<input type="hidden" name="_nonce" value="' . esc_attr($token) . '">';
echo '<button type="submit">Speichern</button>';
echo '</form>';
```

### Token-Validierung

```php
if (!$security->verifyToken($_POST['_nonce'] ?? '', 'profil_speichern')) {
    http_response_code(403);
    die('Sicherheitscheck fehlgeschlagen.');
}
```

### Funktionsweise

| Schritt | Detail |
|---------|--------|
| Erzeugung | `bin2hex(random_bytes(32))` → 256-Bit-Token |
| Speicherung | `$_SESSION['csrf_tokens'][$action]` mit Zeitstempel |
| Vergleich | `hash_equals()` – Timing-Attack-sicher |
| Gültigkeit | 1 Stunde (TTL) |
| Einmalverwendung | Token wird nach erfolgreicher Prüfung gelöscht |

**Persistente Tokens** (`verifyPersistentToken()`) werden für Multi-Request-Flows wie
Dateimanager-Connectoren verwendet – der Token bleibt nach Prüfung erhalten.

**WordPress-kompatible Aliase:** `generateNonceField()`, `verifyNonce()`, `createNonce()`.

---

## 4. XSS-Schutz

<!-- UPDATED: 2026-03-08 -->

Das 365CMS kombiniert drei Verteidigungslinien gegen Cross-Site Scripting.

### 4.1 HTMLPurifier (`htmlpurifier` 4.19.0)

**Datei:** `CMS/core/Services/PurifierService.php`
**Bibliothek:** `CMS/assets/htmlpurifier/`

Serverseitige HTML-Bereinigung mit drei Profilen:

| Profil | Erlaubte Elemente | Einsatz |
|--------|-------------------|---------|
| `default` | `p, a, strong, em, ul, ol, li, h1–h6, img, table, video, audio, details, …` | WYSIWYG-Editor-Inhalt |
| `strict` | `p, a, strong, em, br, ul, ol, li, blockquote, code, pre` | Kommentare, eingeschränkter HTML-Input |
| `minimal` | `strong, em, br, code, a` | Kurzbeschreibungen, Meta-Felder |

```php
$purifier = CMS\Services\PurifierService::instance();

// Standard-Profil
$safe = $purifier->purify($dirtyHtml);

// Striktes Profil
$safe = $purifier->purify($dirtyHtml, 'strict');
```

**Konfiguration:**

```php
$config->set('Core.Encoding', 'UTF-8');
$config->set('HTML.Nofollow', true);          // rel="nofollow" an externe Links
$config->set('HTML.TargetBlank', true);       // target="_blank" an externe Links
$config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
$config->set('AutoFormat.RemoveEmpty', true);
```

**Fallback:** Steht HTMLPurifier nicht zur Verfügung, wird `strip_tags()` mit Whitelist verwendet.

### 4.2 Output-Escaping

Globale Hilfsfunktionen für kontextabhängiges Escaping:

```php
// HTML-Text (kein HTML erlaubt)
echo esc_html($userInput);
// → htmlspecialchars($input, ENT_QUOTES, 'UTF-8')

// HTML-Attribut
echo '<input value="' . esc_attr($value) . '">';

// URL (blockiert javascript:, data:, vbscript:, file:)
echo '<a href="' . esc_url($url) . '">';

// Textarea-Inhalt
echo '<textarea>' . esc_textarea($content) . '</textarea>';

// JavaScript-String
echo '<script>var name = "' . esc_js($name) . '";</script>';
```

### 4.3 Content Security Policy (Nonce-basiert)

Die CSP wird mit einem pro-Request generierten Nonce ausgeliefert (siehe [Abschnitt 8](#8-security-headers)).

```php
// Im Template:
<script <?= Security::instance()->nonceAttr() ?>>
    // Dieses Script wird ausgeführt
</script>

// Ohne Nonce: Script wird vom Browser blockiert
```

**Goldene Regel:**
> Input → Sanitize → Speichern → Escape → Ausgabe. Niemals ungefilterter User-Input direkt ausgeben.

---

## 5. SQL-Injection-Schutz

<!-- UPDATED: 2026-03-08 -->

**Datei:** `CMS/core/Database.php`

### Native Prepared Statements

Die PDO-Verbindung ist so konfiguriert, dass **nur native Prepared Statements** verwendet werden:

```php
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
    PDO::ATTR_EMULATE_PREPARES   => false,   // Native PS, nicht emuliert
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
];
$this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
```

### Korrekte Verwendung

```php
$db = CMS\Database::instance();

// RICHTIG: Platzhalter – Werte werden separat übermittelt
$user = $db->get_row(
    "SELECT * FROM cms_users WHERE username = ? AND status = ?",
    [$username, 'active']
);

// FALSCH – niemals Werte direkt in SQL einsetzen:
// $db->query("SELECT * FROM cms_users WHERE username = '$username'");
```

| Schutzmerkmal | Implementierung |
|---------------|-----------------|
| Emulation deaktiviert | `ATTR_EMULATE_PREPARES = false` |
| Parameter-Binding | Alle Werte als Platzhalter (`?`) |
| Exception-Modus | Fehler werden sofort als Exception geworfen |
| Charset | UTF-8 über `SET NAMES` korrekt gesetzt |

---

## 6. Dateiupload-Sicherheit

<!-- UPDATED: 2026-03-08 -->

**Dateien:** `CMS/core/Services/FileUploadService.php`, `CMS/core/Services/MediaService.php`

Der Upload-Prozess durchläuft mehrere Validierungsschichten:

### Validierungs-Pipeline

```
Upload-Request
  │
  ├─ 1. Authentifizierung     → isAdmin() / isLoggedIn()
  ├─ 2. CSRF-Token            → verifyToken($token, 'media_action')
  ├─ 3. Pfad-Sanitierung      → sanitizePath() entfernt ".." und "\"
  ├─ 4. Autorisierung         → Members nur in eigenem Verzeichnis
  ├─ 5. Dateigröße            → max_upload_size (Standard: 64 MB)
  ├─ 6. Extension-Whitelist   → Gruppenbasiert (image, document, video, audio)
  ├─ 7. Dangerous-Extensions  → php, exe, bat, sh etc. blockiert
  ├─ 8. MIME-Validierung      → finfo_file() vs. Extension-Mapping
  └─ 9. Dateiname bereinigen  → preg_replace('/[^a-zA-Z0-9._-]/', '_', …)
```

### Pfad-Traversal-Schutz

```php
private function sanitizePath(string $path): string
{
    $clean = str_replace(['..', '\\'], ['', '/'], trim($path));
    return trim(preg_replace('#/+#', '/', $clean) ?? '', '/');
}
```

### MIME-Type-Validierung

```php
$finfo        = finfo_open(FILEINFO_MIME_TYPE);
$detectedMime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

// Prüfung gegen erlaubte MIME-Types für die Extension
if (!in_array($detectedMime, $allowedMimeMap[$ext] ?? [], true)) {
    return new WP_Error('mime_mismatch', 'MIME type mismatch for extension: ' . $ext);
}
```

### Member-Isolation

Nicht-Admin-Benutzer dürfen nur in ihr eigenes Verzeichnis hochladen:

```php
$memberRoot = 'member/user-' . $userId;
if (!str_starts_with($targetPath, $memberRoot . '/')) {
    return ['success' => false, 'status' => 403];
}
```

### PHP-Ausführung im Upload-Verzeichnis

```apache
# uploads/.htaccess
<Files "*.php">
    Deny from all
</Files>
```

---

## 7. Session-Sicherheit

<!-- UPDATED: 2026-03-08 -->

**Datei:** `CMS/core/Security.php` – Methode `startSession()`

### Session-Konfiguration

```php
private function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');    // Kein JavaScript-Zugriff
        ini_set('session.cookie_secure', '1');      // Nur über HTTPS
        ini_set('session.use_strict_mode', '1');    // Session-Fixation-Schutz
        session_start();

        if (!isset($_SESSION['initialized'])) {
            session_regenerate_id(true);            // Neue ID, alte Session gelöscht
            $_SESSION['initialized'] = true;
        }
    }
}
```

### Sicherheitsmerkmale

| Merkmal | Konfiguration | Schutz gegen |
|---------|---------------|-------------|
| HTTPOnly-Cookie | `cookie_httponly = 1` | XSS-basiertes Session-Stealing |
| Secure-Flag | `cookie_secure = 1` | Übertragung über unverschlüsselte Verbindung |
| Strict Mode | `use_strict_mode = 1` | Session-Fixation |
| ID-Regeneration | `session_regenerate_id(true)` nach Login | Session-Fixation |
| Rollenbasierte Lifetime | Admin: 8 h, Member: 30 d | Langzeit-Session-Hijacking |
| Session-Ablauf | Prüfung gegen `session_start_time` | Unbegrenzter Zugriff |

### Session-Ablauf-Prüfung

```php
if ((time() - $_SESSION['session_start_time']) > $maxLifetime) {
    $this->forceExpireSession();  // Session zerstören, Redirect zum Login
}
```

### Passwort-Sicherheit

```php
// Hashing (Bcrypt, cost 12)
$hash = Security::hashPassword('MeinSicheresPasswort!');

// Verifikation (timing-safe)
$valid = Security::verifyPassword($input, $hash);
```

**Passwort-Policy** (`Auth::validatePasswordPolicy()`):

| Anforderung | Minimum |
|-------------|---------|
| Länge | 12 Zeichen |
| Großbuchstabe | ≥ 1 |
| Kleinbuchstabe | ≥ 1 |
| Ziffer | ≥ 1 |
| Sonderzeichen | ≥ 1 |

### Rate-Limiting

Doppelte Absicherung gegen Brute-Force-Angriffe:

| Methode | Speicher | Standard |
|---------|----------|----------|
| `checkDbRateLimit()` | `cms_login_attempts` Tabelle | 5 Versuche / 5 Min pro IP+Aktion |
| `checkRateLimit()` | `$_SESSION` (Fallback) | 5 Versuche / 5 Min |

```php
// Primär: DB-basiert (überlebt Session-Wechsel)
if (!$security->checkDbRateLimit($ip, 'login', 5, 300)) {
    http_response_code(429);
    die('Zu viele Versuche. Bitte 5 Minuten warten.');
}
```

Alte Einträge werden probabilistisch bereinigt (1:20-Chance bei jedem Request).

---

## 8. Security-Headers

<!-- UPDATED: 2026-03-08 -->

**Datei:** `CMS/core/Security.php` – Methode `setSecurityHeaders()`

### Automatisch gesetzte Header

| Header | Wert | Schutz gegen |
|--------|------|--------------|
| `X-Frame-Options` | `SAMEORIGIN` | Clickjacking |
| `X-Content-Type-Options` | `nosniff` | MIME-Sniffing |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Referrer-Leakage |
| `Permissions-Policy` | `geolocation=(), microphone=(), camera=()` | Sensor-Zugriff |
| `Cross-Origin-Opener-Policy` | `same-origin` | Fenster-/Browsing-Context-Isolation |
| `Cross-Origin-Resource-Policy` | `same-site` | ungewollte Cross-Origin-Ressourcennutzung |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains; preload` | Downgrade-Angriffe |
| `Content-Security-Policy` | Nonce-basiert (siehe unten) | XSS |

> Hinweis: Der App-Code setzt bewusst **keinen** veralteten `X-XSS-Protection`-Header mehr. Falls er auf einem Zielsystem trotzdem auftaucht, kommt er aus vorgeschalteter Server-/Hosting-Konfiguration und sollte dort entfernt werden.

### Content Security Policy

```
default-src 'self';
script-src  'self' 'nonce-{RANDOM}';
style-src   'self' 'nonce-{RANDOM}';
img-src     'self' data: https: blob:;
font-src    'self' data: https:;
connect-src 'self';
media-src   'self' data: blob:;
object-src  'none';
frame-ancestors 'none';
base-uri    'self';
form-action 'self';
trusted-types cms365 default sanitize-html dompurify;
require-trusted-types-for 'script';
```

### Debug- vs. Produktionsmodus

| Merkmal | `CMS_DEBUG = true` | `CMS_DEBUG = false` |
|---------|--------------------|--------------------|
| CSP | Report-Only | Enforced |
| Trusted Types | Report-Only | Enforced |
| HSTS | Deaktiviert | Aktiv (1 Jahr, Preload) |

### CSP-Nonce im Template

```php
<?php $sec = CMS\Security::instance(); ?>

<!-- Erlaubt: Script mit Nonce -->
<script <?= $sec->nonceAttr() ?>>
    console.log('Dieses Script wird ausgeführt');
</script>

<!-- Blockiert: Script ohne Nonce -->
<script>
    console.log('Wird vom Browser blockiert');
</script>
```

---

## 9. Sicherheitsrelevante Konfiguration

<!-- UPDATED: 2026-03-08 -->

### Vor dem Launch

| Prüfpunkt | Beschreibung |
|-----------|--------------|
| `CMS_DEBUG` | Auf `false` setzen – aktiviert CSP-Enforcement und HSTS |
| Sicherheitsschlüssel | `AUTH_KEY`, `JWT_SECRET` in `config.php` mit `bin2hex(random_bytes(32))` generieren |
| `install.php` | Datei nach Installation löschen |
| HTTPS | TLS 1.2+ aktivieren, HTTP → HTTPS Redirect einrichten |
| Verzeichnisschutz | `logs/`, `cache/`, `config/` via `.htaccess` sperren |
| Upload-Ordner | PHP-Ausführung deaktiviert (`.htaccess`) |
| Admin-Passwörter | Mindestens 12 Zeichen mit Passwort-Policy |
| DB-Benutzer | Nur notwendige Berechtigungen (kein `GRANT`, kein `DROP`) |

### Im Betrieb

| Prüfpunkt | Beschreibung |
|-----------|--------------|
| Backups | Tägliche automatische Backups |
| Audit-Log | Regelmäßig auf verdächtige Aktivitäten prüfen |
| Login-Attempts | Tabelle wird automatisch bereinigt |
| Updates | CMS-Updates zeitnah einspielen |
| Plugins | Nur aus vertrauenswürdigen Quellen installieren |
| MFA | Für alle Admin-Konten aktivieren |
| `security.txt` | Unter `/.well-known/security.txt` oder `/security.txt` bereitstellen und nach Deployments extern prüfen |

### Audit-Logging

Alle sicherheitsrelevanten Aktionen werden in `cms_audit_log` protokolliert:

```php
AuditLogger::instance()->log(
    AuditLogger::CAT_SECURITY,
    'login_blocked',
    'Brute-Force block for IP: ' . $ip,
    '',         // entityType
    null,       // entityId
    [],         // metadata
    'warning'   // severity
);
```

| Kategorie | Beispiel-Events |
|-----------|----------------|
| `auth` | Login-Erfolg/-Fehler, MFA-Setup, Passwort-Reset |
| `security` | Rate-Limit-Blocks, CSP-Verstöße |
| `user` | Benutzer-CRUD, Rollen-Änderungen |
| `plugin` | Aktivierung, Deaktivierung, Löschung |
| `theme` | Theme-Wechsel, Code-Änderungen |
| `media` | Datei-Upload, Datei-Löschung |
| `setting` | Admin-Einstellungen geändert |
| `system` | Backups, Cache-Bereinigung, Updates |

### Input-Sanitierung

```php
$clean = Security::sanitize($input, 'email');     // FILTER_SANITIZE_EMAIL
$clean = Security::sanitize($input, 'url');        // FILTER_SANITIZE_URL
$clean = Security::sanitize($input, 'int');        // FILTER_SANITIZE_NUMBER_INT
$clean = Security::sanitize($input, 'html');       // htmlspecialchars()
$clean = Security::sanitize($input, 'username');   // nur [a-zA-Z0-9_]
$clean = Security::sanitize($input, 'text');       // strip_tags() + trim()
```

---

## 10. Known Limitations

<!-- UPDATED: 2026-03-08 -->

| Einschränkung | Beschreibung | Empfehlung |
|---------------|-------------|------------|
| Session-Rate-Limiting | Fallback `checkRateLimit()` ist an die PHP-Session gebunden und kann durch Session-Wechsel umgangen werden | Immer `checkDbRateLimit()` verwenden (Standard) |
| CSP im Debug-Modus | Im Debug-Modus wird CSP nur als Report-Only gesendet | `CMS_DEBUG = false` in Produktion sicherstellen |
| HSTS ohne HTTPS | HSTS-Header wird nur bei HTTPS-Requests gesendet | HTTPS auf Server-Ebene erzwingen |
| HTMLPurifier-Fallback | Ohne HTMLPurifier fällt der Sanitizer auf `strip_tags()` zurück | HTMLPurifier-Bibliothek immer mitliefern |
| JWT Shared Secret | HS256 nutzt einen symmetrischen Schlüssel | Für Multi-Service-Setups RS256 evaluieren |
| LDAP-Klartext-Passwort | Service-Account-Passwort steht in `config/app.php` | Datei mit `chmod 600` schützen, Environment-Variablen bevorzugen |
| Upload MIME-Bypass | MIME-Erkennung via `finfo` ist nicht 100 % zuverlässig bei exotischen Formaten | Zusätzlich Extension-Whitelist aktiv halten |

