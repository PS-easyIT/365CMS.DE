# Member-Bereich – Sicherheitsmodell

**Datum:** 18. Februar 2026

---

## Sicherheitsarchitektur

Der Member-Bereich implementiert ein **mehrschichtiges Sicherheitsmodell** (Defense in Depth):

```
Schicht 1: Routing-Schutz (Nur /member/* ist Member-Bereich)
     ↓
Schicht 2: Auth-Check im MemberController-Konstruktor
     ↓
Schicht 3: CSRF-Token-Validierung bei jedem POST
     ↓
Schicht 4: Input-Sanitization via getPost()
     ↓
Schicht 5: Output-Escaping in Views mit htmlspecialchars()
```

---

## 1. Authentifizierung & Autorisierung

### Zugriffsschutz (Konstruktor)

```php
// Jede Member-Seite prüft automatisch:
if (!Auth::instance()->isLoggedIn()) {
    redirect('/login');     // Nicht eingeloggt → Login-Seite
}
if (Auth::instance()->isAdmin()) {
    redirect('/admin');     // Admin → Admin-Panel
}
```

**Ergebnis:** Der Member-Bereich ist ausschließlich für Mitglieder zugänglich.  
- Nicht eingeloggte Besucher → `/login`
- Admins → `/admin`

### Session-Sicherheit

- Sessions werden nach dem Login regeneriert (`session_regenerate_id(true)`)
- Session-Cookies: `HttpOnly`, `Secure` (HTTPS), `SameSite=Lax`
- Session-Lifetime: Konfigurierbar über `config.php`

---

## 2. CSRF-Schutz

**Jedes Formular im Member-Bereich** verwendet CSRF-Token-Schutz.

### Token generieren (Controller)

```php
$csrfToken = $controller->generateToken('member_profile');
// Intern: Security::instance()->generateToken('member_profile')
// Speichert: $_SESSION['csrf_token_member_profile'] = random_bytes(32)
```

### Token in Form (View)

```html
<input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
```

### Token verifizieren (Controller)

```php
if (!$controller->verifyToken($controller->getPost('csrf_token'), 'member_profile')) {
    $controller->setError('Sicherheitsüberprüfung fehlgeschlagen.');
    // Kein weiterführender Code wird ausgeführt
}
```

### Token-Aktionsnamen

| Seite | Aktion | Token-Name |
|-------|--------|-----------|
| Profil | Profil speichern | `member_profile` |
| Sicherheit | Passwort ändern | `change_password` |
| Sicherheit | 2FA toggle | `toggle_2fa` |
| Benachrichtigungen | Präferenzen speichern | `member_notifications` |
| Datenschutz | Einstellungen speichern | `privacy_settings` |
| Datenschutz | Daten exportieren | `data_export` |
| Datenschutz | Account löschen | `account_delete` |

---

## 3. Input-Sanitization

### Sanitization-Typen (via `getPost()`)

```php
// Alle POST-Daten werden sanitized:
$email    = $controller->getPost('email',   'email');     // sanitize_email()
$url      = $controller->getPost('website', 'url');       // esc_url_raw()
$name     = $controller->getPost('name',    'text');      // sanitize_text_field()
$bio      = $controller->getPost('bio',     'textarea');  // sanitize_textarea_field()
$count    = $controller->getPost('count',   'int');       // (int)
```

### Direkte `$_POST`-Zugriffe

**Verboten in Views.** Alle POST-Daten **müssen** über `$controller->getPost()` gelesen werden.

---

## 4. Output-Escaping (XSS-Schutz)

### Regeln für Views

| Kontext | Funktion | Beispiel |
|---------|----------|---------|
| HTML-Inhalt | `htmlspecialchars()` | `<?php echo htmlspecialchars($user->username); ?>` |
| HTML-Attribut | `htmlspecialchars()` | `value="<?php echo htmlspecialchars($val); ?>"` |
| Style-Attribut | `htmlspecialchars()` | `style="background: <?php echo htmlspecialchars($color); ?>"` |
| URL | `htmlspecialchars()` | `href="<?php echo htmlspecialchars($url); ?>"` |
| Integer | `(int)` | `echo (int)$count;` |

### Kritische Stellen

```php
// ✅ Correct - alle Views escapen konsequent:
echo htmlspecialchars($securityData['score_message']);
echo htmlspecialchars($session['last_activity']);
echo htmlspecialchars($notification['color'] ?? '#667eea');  // in style-attr!
echo htmlspecialchars((string)($dataOverview['profile_records'] ?? 0));
```

---

## 5. Direktzugriff-Schutz

Alle View-Dateien sind durch `ABSPATH`-Guard geschützt:

```php
if (!defined('ABSPATH')) {
    exit;
}
```

Nur wenn die Anwendung korrekt gebootstrapped wurde (via `config.php`), ist `ABSPATH` definiert.

---

## 6. Passwort-Sicherheit

- Hashing: **BCrypt** mit Cost-Factor 12 (`PASSWORD_BCRYPT`)
- Validierung in `MemberService::changePassword()`:
  - Aktuelles Passwort muss korrekt sein
  - Neues Passwort: min. 8 Zeichen (clientseitig + serverseitig)
  - Bestätigungs-Match erforderlich
- Passwort-Stärke-Anzeige im Frontend (schwach/mittel/stark)

---

## 7. Account-Löschung (DSGVO Art. 17)

Die Löschung ist ein mehrstufiger, sicherer Prozess:

1. **Browser:** Doppelte `confirm()`-Dialoge
2. **Controller:** CSRF-Token-Prüfung
3. **Service:** `requestAccountDeletion()` markiert Account statt sofortiger Löschung
4. **Karenzzeit:** 30 Tage bis zur endgültigen Löschung
5. **Auslöser:** Cronjob prüft täglich ablaufende Löschanfragen

---

## 8. Bekannte Limitierungen

| Thema | Status | Hinweis |
|-------|--------|---------|
| `terminateSession()` | ⚠️ Platzhalter | Session-Terminierung via AJAX noch nicht implementiert |
| `markAllAsRead()` | ⚠️ Platzhalter | AJAX-Endpunkt für Benachrichtigungen fehlt noch |
| `selectPackage()` | ⚠️ Platzhalter | Payment-Plugin-Integration ausstehend |
| Rate Limiting | ⚠️ Empfohlen | Für POST-Endpunkte des Member-Bereichs empfohlen |

---

## 9. Security-Checkliste für Erweiterungen

Alle Plugins, die den Member-Bereich erweitern, müssen:

- [ ] Eigene Menü-URLs durch `isLoggedIn()` absichern
- [ ] Alle eigenen Formulare mit CSRF-Token schützen
- [ ] Alle POST-Eingaben sanitizen
- [ ] Alle Ausgaben mit `htmlspecialchars()` escapen
- [ ] Keine direkten DB-Queries ohne Prepared Statements
- [ ] Keine `$_SESSION`-Manipulation ohne vorherige Validierung
