# CMSv2 - Sicherheits-Leitfaden

Best Practices und Sicherheitsstandards für das CMSv2.

## 🔒 Übersicht

Das CMSv2 wurde nach den **OWASP Top 10 (2021)** Security-Standards entwickelt und implementiert umfassende Sicherheitsmaßnahmen.

## 🛡️ Security Features

### Implementierte Schutzmaßnahmen

| Bedrohung | Schutz | Implementierung | Status |
|-----------|--------|-----------------|--------|
| SQL Injection | Prepared Statements | 100% aller DB-Queries | ✅ |
| XSS | Input/Output Escaping | Alle User-Inputs | ✅ |
| CSRF | Token-Validierung | Alle Formulare | ✅ |
| Brute Force | Rate Limiting | Login, Forms | ✅ |
| Session Hijacking | Secure Cookies | HTTP-Only, Regeneration | ✅ |
| Password Attacks | BCrypt Hashing | Cost 12 | ✅ |
| Directory Traversal | .htaccess Rules | PHP Execution Block | ✅ |
| Information Disclosure | Error Handling | Custom Error Pages | ✅ |

## 🚨 OWASP Top 10 Compliance

### 1. Broken Access Control ✅

**Implementierung:**
```php
// Jede Admin-Seite prüft Berechtigung
if (!CMS\Auth::instance()->isAdmin()) {
    CMS\Router::instance()->redirect('/');
    exit;
}
```

**Best Practice:**
- Immer `isAdmin()` oder `hasRole()` prüfen
- Kein direkter Datenbankzugriff ohne Auth-Check
- Role-Based Access Control (RBAC)

### 2. Cryptographic Failures ✅

**Implementierung:**
```php
// BCrypt mit hohem Cost-Factor
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Sichere Zufallsstrings
$token = bin2hex(random_bytes(32));
```

**Best Practice:**
- HTTPS in Production (SSL/TLS)
- Keine Passwörter in Klartext speichern
- Security Keys regelmäßig rotieren

### 3. Injection ✅

**SQL Injection Prevention:**
```php
// ❌ NIEMALS:
$sql = "SELECT * FROM users WHERE id = " . $_GET['id'];

// ✅ IMMER:
$stmt = $db->prepare("SELECT * FROM {$db->prefix()}users WHERE id = ?");
$stmt->execute([$id]);
```

**XSS Prevention:**
```php
// Output immer escapen
echo CMS\Security::instance()->escape($userInput);
echo esc_html($text);
echo esc_url($url);
```

### 4. Insecure Design ✅

**Secure Design Patterns:**
- Singleton für zentrale Services
- Prepared Statements als Standard
- Hook-System für sichere Erweiterungen
- Template-Hierarchie ohne Code-Injection

### 5. Security Misconfiguration ✅

**Apache Security Headers:**
```apache
# .htaccess
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-XSS-Protection "1; mode=block"
Header always set X-Content-Type-Options "nosniff"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

**PHP Configuration:**
```ini
; php.ini
display_errors = Off
log_errors = On
expose_php = Off
session.cookie_httponly = 1
session.cookie_secure = 1
```

### 6. Vulnerable Components ✅

**Dependency Management:**
- Minimale externe Abhängigkeiten
- PHP 8.0+ erforderlich (Security-Updates)
- Regelmäßige Updates planen

### 7. Authentication Failures ✅

**Sichere Authentifizierung:**
```php
// Rate Limiting
if (!$security->checkRateLimit('login_' . $ip, 5, 300)) {
    die('Zu viele Login-Versuche');
}

// Session Regeneration
session_regenerate_id(true);

// Sichere Session-Konfiguration
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '1');
```

### 8. Software and Data Integrity ✅

**Code Integrity:**
- `declare(strict_types=1)` in allen Dateien
- Type Hinting für alle Parameter
- Input-Validierung vor Verarbeitung

### 9. Security Logging ✅

**Implementierung:**
```php
// Login-Versuche loggen
$db->insert('login_attempts', [
    'username' => $username,
    'ip_address' => $security->getClientIp(),
    'attempted_at' => date('Y-m-d H:i:s')
]);

// Error Logging
if (CMS_DEBUG) {
    error_log("Security issue: " . $message);
}
```

### 10. Server-Side Request Forgery ✅

**URL-Validierung:**
```php
// URLs validieren
if (!$security->validateUrl($url)) {
    throw new Exception('Ungültige URL');
}

// Externe Requests einschränken
// (keine curl/file_get_contents ohne Whitelist)
```

## 🔐 Input-Validierung

### Sanitization-Typen

```php
$security = CMS\Security::instance();

// Text
$clean = $security->sanitize($input, 'text');
// Entfernt HTML, SQL-Zeichen

// E-Mail
$email = $security->sanitize($input, 'email');
// Validiert E-Mail-Format

// URL
$url = $security->sanitize($input, 'url');
// Validiert und bereinigt URLs

// Integer
$number = $security->sanitize($input, 'int');
// Konvertiert zu Integer

// HTML (erlaubt sichere Tags)
$html = $security->sanitize($input, 'html');
// Erlaubt: <p>, <br>, <strong>, <em>, <a>
```

### Output-Escaping

```php
// HTML-Context
<h1><?php echo esc_html($title); ?></h1>

// Attribut-Context
<input value="<?php echo esc_attr($value); ?>">

// URL-Context
<a href="<?php echo esc_url($link); ?>">Link</a>

// JavaScript-Context
<script>
    var data = <?php echo json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
</script>
```

## 🛡️ CSRF-Protection

### Formular-Absicherung

**HTML:**
```php
<form method="POST" action="/save">
    <?php
    $token = CMS\Security::instance()->generateToken('save_form');
    ?>
    <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
    
    <!-- Weitere Felder -->
    <button type="submit">Speichern</button>
</form>
```

**Verarbeitung:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $security = CMS\Security::instance();
    
    if (!$security->verifyToken($_POST['csrf_token'], 'save_form')) {
        die('CSRF-Token ungültig');
    }
    
    // Formular verarbeiten
}
```

### AJAX-Requests

**JavaScript:**
```javascript
fetch('/api/endpoint', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': document.querySelector('[name="csrf_token"]').value
    },
    body: JSON.stringify(data)
});
```

**PHP:**
```php
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!$security->verifyToken($token, 'ajax_action')) {
    http_response_code(403);
    die(json_encode(['error' => 'Invalid token']));
}
```

## 🔒 Password-Security

### Hashing-Richtlinien

```php
// Passwort hashen
$hash = CMS\Security::instance()->hashPassword($password);

// Intern: BCrypt mit Cost 12
password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Verifizieren
if ($security->verifyPassword($input, $storedHash)) {
    // Password korrekt
}
```

### Password-Policy (empfohlen)

```php
function validate_password($password) {
    // Mindestlänge
    if (strlen($password) < 8) {
        return 'Mindestens 8 Zeichen';
    }
    
    // Mindestens ein Großbuchstabe
    if (!preg_match('/[A-Z]/', $password)) {
        return 'Mindestens ein Großbuchstabe erforderlich';
    }
    
    // Mindestens eine Zahl
    if (!preg_match('/[0-9]/', $password)) {
        return 'Mindestens eine Zahl erforderlich';
    }
    
    // Mindestens ein Sonderzeichen
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return 'Mindestens ein Sonderzeichen erforderlich';
    }
    
    return true;
}
```

## 🚫 Rate Limiting

### Login-Protection

```php
$security = CMS\Security::instance();
$identifier = 'login_' . $security->getClientIp();

// Max 5 Versuche in 5 Minuten
if (!$security->checkRateLimit($identifier, 5, 300)) {
    $_SESSION['error'] = 'Zu viele Login-Versuche. Bitte warten Sie 5 Minuten.';
    CMS\Router::instance()->redirect('/login');
    exit;
}

// Login-Versuch verarbeiten
```

### Custom Rate Limits

```php
// API-Endpoint: 30 Requests pro Minute
if (!$security->checkRateLimit('api_' . $userId, 30, 60)) {
    http_response_code(429);
    die(json_encode(['error' => 'Rate limit exceeded']));
}
```

## 📁 File Upload Security

### Upload-Validierung

```php
function secure_file_upload($file) {
    // 1. Dateigröße prüfen (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Datei zu groß');
    }
    
    // 2. MIME-Type prüfen
    $allowed = ['image/jpeg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    
    if (!in_array($mime, $allowed)) {
        throw new Exception('Dateityp nicht erlaubt');
    }
    
    // 3. Extension prüfen
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
        throw new Exception('Ungültige Dateiendung');
    }
    
    // 4. Zufälliger Dateiname
    $newName = bin2hex(random_bytes(16)) . '.' . $ext;
    
    // 5. Upload in uploads/ (PHP-Execution blockiert!)
    $path = ABSPATH . '/uploads/' . $newName;
    move_uploaded_file($file['tmp_name'], $path);
    
    return $newName;
}
```

### .htaccess in uploads/

```apache
# uploads/.htaccess (BEREITS VORHANDEN)
<Files *.php>
    deny from all
</Files>
```

## 🔍 Security Auditing

### Security-Checkliste

#### Installation
- [ ] `install.php` gelöscht
- [ ] Security Keys geändert
- [ ] Admin-Passwort geändert
- [ ] `CMS_DEBUG` auf `false`

#### Konfiguration
- [ ] HTTPS aktiviert
- [ ] Security Headers gesetzt
- [ ] PHP-Errors nicht angezeigt
- [ ] FileInfo-Extension aktiv

#### Dateirechte
- [ ] Alle Dateien 644 (rw-r--r--)
- [ ] Alle Verzeichnisse 755 (rwxr-xr-x)
- [ ] `uploads/` 775 mit Web-User als Owner
- [ ] `config.php` nicht öffentlich lesbar

#### Code
- [ ] Alle Inputs sanitized
- [ ] Alle Outputs escaped
- [ ] Prepared Statements verwendet
- [ ] CSRF-Tokens bei Forms
- [ ] Rate Limiting aktiv

#### Datenbank
- [ ] DB-User hat minimale Rechte
- [ ] Kein Root-User
- [ ] Sichere Passwörter
- [ ] Nur localhost-Zugriff

### Penetration Testing

**Empfohlene Tools:**
- **OWASP ZAP** - Automatischer Scanner
- **Burp Suite** - Manuel Pen-Testing
- **SQLMap** - SQL-Injection Testing
- **XSSer** - XSS-Vulnerability Scanner

### Logging & Monitoring

```php
// Custom Security Logger
class SecurityLogger {
    public static function log($event, $severity = 'info') {
        $entry = sprintf(
            "[%s] [%s] %s - IP: %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($severity),
            $event,
            CMS\Security::instance()->getClientIp()
        );
        
        error_log($entry, 3, ABSPATH . '/logs/security.log');
    }
}

// Verwendung
SecurityLogger::log('Failed login attempt for user: ' . $username, 'warning');
SecurityLogger::log('CSRF token mismatch', 'critical');
```

## 🚨 Incident Response

### Bei Sicherheitsvorfall

1. **Sofortmaßnahmen:**
   - Site offline nehmen
   - Alle Sessions invalidieren
   - Admin-Passwörter ändern
   - DB-Backup erstellen

2. **Analyse:**
   - Logs prüfen
   - Betroffene Daten identifizieren
   - Ursprung des Angriffs finden

3. **Bereinigung:**
   - Backdoors entfernen
   - Infizierte Dateien ersetzen
   - Sicherheitslücke schließen

4. **Recovery:**
   - Sauberes Backup einspielen
   - Security-Updates installieren
   - Monitoring verschärfen

5. **Post-Incident:**
   - Incident dokumentieren
   - Lessons Learned
   - Security-Review durchführen

## 📚 Security Resources

### Leseempfehlungen
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [Web Security Academy](https://portswigger.net/web-security)

### Online-Tools
- [SSL Labs](https://www.ssllabs.com/ssltest/) - SSL-Test
- [Security Headers](https://securityheaders.com/) - Header-Check
- [Have I Been Pwned](https://haveibeenpwned.com/) - Breach-Check

### Updates & Patches
- Abonnieren Sie PHP Security Mailingliste
- Überwachen Sie CVE-Datenbanken
- Planen Sie monatliche Security-Updates
