# Member-Bereich – Controller-Dokumentation

**Datum:** 18. Februar 2026

---

## 1. Basis-Controller: `MemberController`

**Datei:** `includes/class-member-controller.php`  
**Namespace:** `CMS\Member`  
**Pattern:** Direkte Instanziierung (kein Singleton – jede Seite erzeugt eine Instanz)

### Öffentliche Methoden

| Methode | Signatur | Beschreibung |
|---------|----------|-------------|
| `render` | `render(string $view, array $data = []): void` | Lädt member-menu.php (Funktionen) und die angegebene View mit extrahierten Daten |
| `redirect` | `redirect(string $url): void` | Leitet zu `SITE_URL . $url` weiter und beendet die Ausführung |
| `generateToken` | `generateToken(string $action): string` | Generiert CSRF-Token für eine Aktion |
| `verifyToken` | `verifyToken(string $token, string $action): bool` | Prüft CSRF-Token |
| `setSuccess` | `setSuccess(string $message): void` | Setzt `$_SESSION['success']` |
| `setError` | `setError(string $message): void` | Setzt `$_SESSION['error']` |
| `getPost` | `getPost(string $key, string $type = 'text', $default = ''): mixed` | Liest `$_POST[$key]` mit Sanitization |
| `isChecked` | `isChecked(string $key): bool` | Prüft ob Checkbox gesetzt ist |
| `getUser` | `getUser(): object` | Gibt den aktuellen User zurück |
| `handleSecurityActions` | `handleSecurityActions(): void` | Verarbeitet Passwort- und 2FA-Formulare |
| `handleNotificationActions` | `handleNotificationActions(): void` | Speichert alle 10 Benachrichtigungs-Präferenzen |
| `handlePrivacyActions` | `handlePrivacyActions(): void` | Datenschutz, Datenexport, Account-Löschung |

### Sanitization-Typen für `getPost()`

| Typ | Funktion | Beispiel |
|-----|----------|---------|
| `text` (Standard) | `sanitize_text_field()` | Namen, Felder |
| `email` | `sanitize_email()` | E-Mail-Adressen |
| `url` | `esc_url_raw()` | Webseiten-URLs |
| `textarea` | `sanitize_textarea_field()` | Mehrzeilige Texte |
| `int` | `(int) $value` | Numerische IDs |
| `bool` | `(bool) $value` | Boolean-Werte |

### Konstruktor-Ablauf

```php
public function __construct()
{
    if (!Auth::instance()->isLoggedIn())  → redirect('/login')
    if (Auth::instance()->isAdmin())       → redirect('/admin')

    $this->auth         = Auth::instance();
    $this->security     = Security::instance();
    $this->memberService = MemberService::getInstance();
    $this->db           = Database::instance();
    $this->user         = $this->auth->getCurrentUser();
}
```

---

## 2. Dashboard-Controller

**Datei:** `index.php`  
**URL:** `/member`

```php
require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

$controller    = new MemberController();
$memberService = MemberService::getInstance();

$dashboardData = $memberService->getMemberDashboardData($controller->getUser()->id);

$controller->render('dashboard-view', [
    'dashboardData' => $dashboardData
]);
```

### `getMemberDashboardData()` – erwartete Rückgabe

| Schlüssel | Typ | Beschreibung |
|-----------|-----|-------------|
| `last_login_formatted` | string | Formatiertes Datum des letzten Logins |
| `last_login_relative` | string | Relativer Zeitstring (z.B. „vor 2 Stunden") |
| `login_count_30d` | int | Anzahl Logins der letzten 30 Tage |
| `account_age_days` | int | Account-Alter in Tagen |
| `two_factor_enabled` | bool | 2FA aktiv? |
| `password_changed_relative` | string | Relativer Zeitstring der letzten PW-Änderung |
| `active_sessions` | int | Anzahl aktiver Sessions |
| `subscription` | array\|null | Aktives Abo-Objekt oder null |
| `subscription_visible` | bool | Abo-Menüpunkt sichtbar? |
| `recent_activities` | array | Letzte Aktivitäten mit `icon`, `text`, `time_ago` |

---

## 3. Profil-Controller

**Datei:** `profile.php`  
**URL:** `/member/profile`

### Formularfelder (POST)

| Feld | Typ | Sanitization |
|------|-----|-------------|
| `username` | string | `text` |
| `email` | string | `email` |
| `first_name` | string | `text` |
| `last_name` | string | `text` |
| `bio` | string | `textarea` |
| `phone` | string | `text` |
| `website` | string | `url` |

### Daten an View

| Variable | Beschreibung |
|----------|-------------|
| `$csrfToken` | CSRF-Token `'member_profile'` |
| `$userMeta` | Array mit `first_name`, `last_name`, `bio`, `phone`, `website` |

---

## 4. Sicherheits-Controller

**Datei:** `security.php`  
**URL:** `/member/security`

### Aktionen (POST)

| `action` Wert | CSRF-Token | Beschreibung |
|--------------|------------|-------------|
| `change_password` | `change_password` | Passwort ändern |
| `toggle_2fa` | `toggle_2fa` | 2FA aktivieren/deaktivieren |

### Daten an View

| Variable | Beschreibung |
|----------|-------------|
| `$securityData` | Objekt mit `score` (0-100), `score_message`, `password_changed`, `recommendations[]`, `2fa_enabled`, `login_history[]` |
| `$activeSessions` | Array von Sessions mit `id`, `device`, `ip`, `location`, `last_activity`, `is_current`, `device_icon` |
| `$csrfPassword` | CSRF-Token `'change_password'` |
| `$csrf2FA` | CSRF-Token `'toggle_2fa'` |

---

## 5. Benachrichtigungen-Controller

**Datei:** `notifications.php`  
**URL:** `/member/notifications`

### Gespeicherte Präferenzen (POST → `handleNotificationActions`)

| Feld | Typ | Standard |
|------|-----|---------|
| `email_notifications` | bool | true |
| `email_marketing` | bool | false |
| `email_updates` | bool | true |
| `email_security` | bool | true |
| `browser_notifications` | bool | false |
| `desktop_notifications` | bool | false |
| `mobile_notifications` | bool | false |
| `notify_new_features` | bool | true |
| `notify_promotions` | bool | false |
| `notification_frequency` | string | `'immediate'` |

### Daten an View

| Variable | Beschreibung |
|----------|-------------|
| `$preferences` | Alle gespeicherten Präferenzen als Array |
| `$recentNotifications` | Max. 10 neueste Benachrichtigungen |
| `$csrfToken` | CSRF-Token `'member_notifications'` |

---

## 6. Datenschutz-Controller

**Datei:** `privacy.php`  
**URL:** `/member/privacy`

### Aktionen (POST)

| `action` Wert | CSRF-Token | Beschreibung |
|--------------|------------|-------------|
| `update_privacy` | `privacy_settings` | Privatsphäre-Einstellungen speichern |
| `export_data` | `data_export` | JSON-Export aller User-Daten (DSGVO Art. 20) |
| `delete_account` | `account_delete` | Account zur Löschung markieren (30 Tage) |

### Daten an View

| Variable | Beschreibung |
|----------|-------------|
| `$privacySettings` | Array mit `profile_visibility`, `show_email`, `show_activity`, `allow_contact`, `data_sharing`, `analytics_tracking`, `third_party_cookies` |
| `$dataOverview` | Array mit `profile_records`, `activities`, `logins`, `settings`, `files`, `total_size`, `sessions` |
| `$csrfPrivacy` | CSRF-Token `'privacy_settings'` |
| `$csrfExport` | CSRF-Token `'data_export'` |
| `$csrfDelete` | CSRF-Token `'account_delete'` |

---

## 7. Abonnement-Controller

**Datei:** `subscription.php`  
**URL:** `/member/subscription`  
**Sichtbarkeit:** Nur wenn `cms_settings.member_subscription_visible = '1'`

### Daten an View

| Variable | Beschreibung |
|----------|-------------|
| `$subscription` | Array mit aktivem Abo (`package_name`, `price`, `billing_cycle`, `start_date`, `end_date`, `status`, `auto_renew`, `features[]`, `package_id`) oder `null` |
| `$availablePackages` | Array von Paketen mit `id`, `name`, `price`, `billing_cycle`, `description`, `features[]`, `featured` |
| `$permissions` | Array von Berechtigungs-Strings des Users |
| `$statusBadges` | `['active'=>'success', 'expired'=>'danger', 'pending'=>'warning', 'cancelled'=>'secondary']` |
