# Member-Bereich – Controller-Dokumentation

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

# 365CMS – Member-Controller

Kurzbeschreibung: Übersicht über den zentralen `MemberController` und die wichtigsten Request-Dateien des Mitgliederbereichs.

Letzte Aktualisierung: 2026-03-07

---

## Basis-Controller `MemberController`

**Datei:** `CMS/member/includes/class-member-controller.php`

Die Basisklasse bündelt Authentifizierung, Formularschutz, Redirects, Rendering und mehrere POST-Handler.

### Öffentliche Methoden

| Methode | Zweck |
|---|---|
| `redirect(string $url)` | Weiterleitung auf relative Zielroute |
| `generateToken(string $action)` | CSRF-Token erzeugen |
| `verifyToken(string $token, string $action)` | CSRF-Token prüfen |
| `setSuccess(string $message)` | Erfolgsmeldung in Session setzen |
| `setError(string $message)` | Fehlermeldung in Session setzen |
| `getPost(string $key, string $type = 'text', $default = '')` | POST-Werte sanitizen |
| `isChecked(string $key)` | Checkbox-Status prüfen |
| `render(string $view, array $data = [])` | Partial rendern |
| `getUser()` | aktuellen Benutzer zurückgeben |
| `handleSecurityActions()` | Passwort- und Security-POSTs verarbeiten |
| `handleNotificationActions()` | Notification-Präferenzen speichern |
| `handlePrivacyActions()` | Datenschutzaktionen ausführen |

---

## Konstruktor-Verhalten

Beim Erzeugen des Controllers werden aktuell folgende Schritte ausgeführt:

1. Login-Prüfung via `Auth::instance()->isLoggedIn()`
2. Initialisierung von `Auth`, `Security`, `MemberService` und `Database`
3. Laden des aktuellen Users
4. Initialisierung der `PluginDashboardRegistry`

Wichtig: Der aktuelle Stand leitet Administratoren **nicht** automatisch in den Admin-Bereich um.

---

## Sanitizing über `getPost()`

| Typ | Verarbeitung |
|---|---|
| `text` | `sanitize_text_field()` |
| `email` | `sanitize_email()` |
| `url` | `esc_url_raw()` |
| `textarea` | `sanitize_textarea_field()` |
| `int` | `(int)` |
| `bool` | boolescher Cast |

---

## Wichtige Request-Dateien

| Datei | Route | Schwerpunkt |
|---|---|---|
| `index.php` | `/member` | Dashboard laden |
| `profile.php` | `/member/profile` | Profil bearbeiten |
| `security.php` | `/member/security` | Passwort, 2FA, Sessions |
| `notifications.php` | `/member/notifications` | Präferenzen und Verlauf |
| `privacy.php` | `/member/privacy` | Datenschutz, Export, Löschung |
| `subscription.php` | `/member/subscription` | Paket- und Rechteansicht |
| `media.php` | `/member/media` | Medienübersicht |
| `messages.php` | `/member/messages` | Konversationen |
| `favorites.php` | `/member/favorites` | Merkliste |
| `order_public.php` | `/member/orders` | Bestellhistorie |

---

## Relevante POST-Handler

### `handleSecurityActions()`

Unterstützte Aktionen im Controller:

- `change_password`
- `toggle_2fa`

Wichtig: Die aktuelle Security-View nutzt für 2FA zusätzlich dedizierte `/mfa-*`-Routen.

### `handleNotificationActions()`

Speichert u. a.:

- `email_notifications`
- `email_marketing`
- `email_updates`
- `email_security`
- `browser_notifications`
- `desktop_notifications`
- `mobile_notifications`
- `notify_new_features`
- `notify_promotions`
- `notification_frequency`

Vor dem Speichern wird der Datensatz über `member_notification_preferences` filterbar gemacht.

### `handlePrivacyActions()`

Unterstützte Aktionen:

- `update_privacy`
- `export_data`
- `delete_account`

Aktuell verlässlich dokumentierte Standardfelder für `update_privacy`:

- `profile_visibility`
- `show_email`
- `show_activity`

---

## Dashboard-spezifische Hinweise

Im Dashboard greifen neben `MemberService` auch Settings aus der Datenbank, z. B.:

- `member_dashboard_show_welcome`
- `member_dashboard_show_quickstart`
- `member_dashboard_show_stats`
- `member_dashboard_show_custom_widgets`
- `member_dashboard_show_plugin_widgets`
- `member_dashboard_show_notifications_panel`
- `member_dashboard_show_onboarding_panel`
- `member_dashboard_plugin_order`

---

## Verwandte Dokumente

- [README.md](README.md)
- [VIEWS.md](VIEWS.md)
- [HOOKS.md](HOOKS.md)
- [SECURITY.md](SECURITY.md)
