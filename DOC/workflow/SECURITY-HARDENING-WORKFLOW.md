# Security-Hardening Workflow – 365CMS

> **Bereich:** Sicherheit · **Version:** 1.6.14  
> **Grundlage:** [SECURITY-AUDIT.md](../audits/SECURITY-AUDIT.md)  
> **Ziel:** Schritt-für-Schritt-Härtung des Systems vor Produktionsfreigabe

---

## Übersicht: Hardening-Phasen

```
Phase 0: Sofort (< 30 min)   → Kritische Konfigurationsfehler
Phase 1: Kurzfristig (< 1 d) → Code-Fixes in Core-Dateien
Phase 2: Mittelfristig (1 Wo) → Architektur-Verbesserungen
Phase 3: Langfristig (1 Mo)  → Strukturelle Sicherheitsfeatures
```

---

## Phase 0 – Sofort (< 30 Minuten)

### 0.1 Security-Keys setzen

**Datei:** `config.php`  
**Problem:** Standardwerte `'put-your-unique-phrase-here...'` sind bekannt.

```php
// ERSETZEN DURCH (Zufallsgenerator):
define('AUTH_KEY',         bin2hex(random_bytes(32)));
define('SECURE_AUTH_KEY',  bin2hex(random_bytes(32)));
define('LOGGED_IN_KEY',    bin2hex(random_bytes(32)));
define('NONCE_KEY',        bin2hex(random_bytes(32)));
```

> **Schnellgenerator (Bash):** `php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;" `

**Prüfung:** `grep "put-your" config.php` → muss leer sein.

---

### 0.2 config.php aus Webroot sichern

```apache
# .htaccess – Direktzugriff auf config.php sperren
<Files "config.php">
    Require all denied
</Files>

# Alternativ: config.php ein Verzeichnis oberhalb von public_html ablegen
# und in index.php via: require_once dirname(__DIR__) . '/config.php';
```

---

### 0.3 Debug-Modus deaktivieren

```php
// config.php:
define('CMS_DEBUG', false);          // ← Produktionswert
define('CMS_DEBUG_LOG', true);       // ← Logs schreiben, aber nicht anzeigen
```

---

### 0.4 install.php löschen / sperren

```powershell
# Windows:
Remove-Item "e:\00-WPwork\365CMS.DE\CMS\install.php"

# Oder via .htaccess sperren:
# <Files "install.php">
#     Require all denied
# </Files>
```

---

## Phase 1 – Kurzfristig (< 1 Tag)

### 1.1 CacheManager: unserialize() → JSON

**Datei:** `core/CacheManager.php`  
**Risiko:** PHP Object Injection via unserialize() auf Filesystem

```php
// VORHER (unsicher):
$data = unserialize(file_get_contents($cacheFile));
file_put_contents($cacheFile, serialize($data));

// NACHHER (sicher + schneller):
$raw = file_get_contents($cacheFile);
$envelope = json_decode($raw, true);

// HMAC-Verifikation:
$expectedHmac = hash_hmac('sha256', $envelope['data'], AUTH_KEY);
if (!hash_equals($expectedHmac, $envelope['hmac'])) {
    unlink($cacheFile); // Manipulierter Cache
    return null;
}
$data = json_decode($envelope['data'], true);

// Speichern:
$jsonData = json_encode($value);
$envelope = json_encode([
    'data' => $jsonData,
    'hmac' => hash_hmac('sha256', $jsonData, AUTH_KEY),
    'expires' => time() + $ttl,
]);
file_put_contents($cacheFile, $envelope, LOCK_EX);
```

---

### 1.2 Theme-Editor: Path-Traversal-Schutz

**Datei:** `admin/theme-editor.php`  
**Risiko:** `file=../../../config.php` → Lesen/Schreiben beliebiger Dateien

```php
function validateThemeEditorPath(string $requestedFile, string $themeDir): string {
    $base = realpath($themeDir);
    if ($base === false) throw new \RuntimeException('Theme-Verzeichnis nicht gefunden');

    $full = realpath($base . DIRECTORY_SEPARATOR . ltrim($requestedFile, '/\\'));
    if ($full === false || !str_starts_with($full, $base . DIRECTORY_SEPARATOR)) {
        // Sicherheits-Log schreiben
        error_log('[SECURITY] Path-Traversal-Versuch: ' . $requestedFile . ' von IP: ' . $_SERVER['REMOTE_ADDR']);
        throw new \RuntimeException('Zugriff verweigert');
    }

    $allowed = ['php', 'css', 'js', 'html', 'json', 'twig'];
    $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        throw new \RuntimeException('Dateityp nicht erlaubt: .' . $ext);
    }

    return $full;
}
```

---

### 1.3 Plugin-Load: Try-Catch-Wrapper

**Datei:** `core/PluginManager.php`

```php
foreach ($this->activePlugins as $slug) {
    $file = PLUGIN_PATH . $slug . '/' . $slug . '.php';
    if (!file_exists($file)) continue;

    try {
        require_once $file;
        Hooks::doAction('plugin_loaded', $slug);
    } catch (\ParseError $e) {
        error_log("[Plugin-ParseError] $slug: " . $e->getMessage());
        $this->disablePlugin($slug); // Auto-Disable
    } catch (\Throwable $e) {
        error_log("[Plugin-Error] $slug: " . $e->getMessage());
        if (defined('CMS_DEBUG') && CMS_DEBUG) throw $e;
        $this->disablePlugin($slug);
    }
}
```

---

### 1.4 Medien-Manager CSRF reaktivieren

**Datei:** `admin/media.php`  
Entferne den Kommentar bei CSRF-Prüfung für Upload/Delete-Aktionen.

---

### 1.5 extract() absichern

**Datei:** `core/ThemeManager.php`

```php
// VORHER:
extract($data);

// NACHHER:
extract($data, EXTR_SKIP); // Existierende Variablen NICHT überschreiben
```

---

## Phase 2 – Mittelfristig (1 Woche)

### 2.1 CSP: unsafe-inline entfernen

**Datei:** `core/Security.php`  
Nonce-basierte CSP einführen:

```php
$nonce = base64_encode(random_bytes(16));
$_REQUEST['_csp_nonce'] = $nonce;

$csp = "default-src 'self'; "
     . "script-src 'self' 'nonce-{$nonce}'; "
     . "style-src 'self' 'nonce-{$nonce}'; "
     . "img-src 'self' data: https:; "
     . "connect-src 'self'; "
     . "frame-ancestors 'none'";

header("Content-Security-Policy: $csp");
```

---

### 2.2 HSTS-Header hinzufügen

```php
// core/Security.php:
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
```

---

### 2.3 Audit-Log-Tabelle anlegen

```sql
CREATE TABLE IF NOT EXISTS cms_audit_log (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED,
    user_name   VARCHAR(100),
    action      VARCHAR(100) NOT NULL,
    target_type VARCHAR(50),
    target_id   INT UNSIGNED,
    detail      TEXT,
    ip          VARCHAR(45),
    created_at  DATETIME DEFAULT NOW(),
    INDEX idx_user    (user_id),
    INDEX idx_action  (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

### 2.4 HTTP-Client ersetzen

**Datei:** `core/Services/UpdateService.php`

```php
// VORHER (unsicher, blockierend):
$response = @file_get_contents($url);

// NACHHER (sicher, mit Timeout):
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_FOLLOWLOCATION => false, // SSRF-Schutz
    CURLOPT_USERAGENT      => '365CMS/' . CMS_VERSION,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($response === false || $httpCode !== 200) return null;
```

---

## Phase 3 – Langfristig (1 Monat)

### 3.1 MFA für Admin-Accounts

- TOTP (Time-based One-Time Password) via `chillerlan/php-qrcode` + RFC 6238
- Backup-Codes generieren bei Erst-Einrichtung
- Admin-Pflicht: MFA muss vor Admin-Zugang aktiv sein

### 3.2 Rate-Limiting auf DB-Basis

```sql
-- Ersetze Session-basiertes Rate-Limiting:
CREATE TABLE cms_rate_limits (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(100) NOT NULL,  -- IP oder user_id
    action     VARCHAR(50)  NOT NULL,
    attempts   INT DEFAULT 1,
    window_end DATETIME NOT NULL,
    INDEX idx_ident_action (identifier, action)
);
```

### 3.3 Composer Security Audit einrichten

```bash
# composer.json anlegen und regelmäßig prüfen:
composer audit
# → Zeigt bekannte CVEs in Abhängigkeiten
```

---

## Checkliste: Vor Produktionsstart

```
KONFIGURATION:
[ ] Security-Keys gesetzt (kein Standardwert)
[ ] config.php außerhalb Webroot oder per .htaccess gesperrt
[ ] CMS_DEBUG = false
[ ] install.php gelöscht oder gesperrt
[ ] Admin-Passwort geändert (kein Standardpasswort)

CODE:
[ ] CacheManager: json_decode statt unserialize
[ ] ThemeManager: extract($data, EXTR_SKIP)
[ ] Theme-Editor: realpath()-Guard aktiv
[ ] Plugin-Load: try/catch in loadPlugins()
[ ] Media CSRF reaktiviert

SERVER:
[ ] HTTPS aktiv + gültiges TLS-Zertifikat
[ ] HSTS-Header gesetzt
[ ] uploads/ Verzeichnis: PHP-Ausführung deaktiviert
[ ] Fehlerausgabe im Browser deaktiviert
[ ] Backup-Prozess eingerichtet
```

---

## Referenzen

- [SECURITY-AUDIT.md](../audits/SECURITY-AUDIT.md) – Vollständiger Audit mit CVSS-Werten
- [ROADMAP_FEB2026.md](../feature/ROADMAP_FEB2026.md) – Alle 81 Maßnahmen priorisiert
- [OWASP Top 10 2021](https://owasp.org/Top10/)
- [BSI Grundschutz PHP](https://www.bsi.bund.de)
