# Member-Bereich – View-Dokumentation

**Version:** 2.0.2  
**Datum:** 18. Februar 2026

---

## Allgemeines

Alle Views liegen in `member/partials/` und werden **ausschließlich** durch `MemberController::render()` geladen. Direkter HTTP-Aufruf ist durch den `ABSPATH`-Guard verhindert.

### Immer verfügbare Variablen

Jede View erhält automatisch:

| Variable | Quelle | Beschreibung |
|----------|--------|-------------|
| `$user` | `MemberController::$user` | Aktueller eingeloggter User |
| *(alle data-Schlüssel)* | `extract($data)` | Vom jeweiligen Controller übergeben |

### Globale Funktionen (aus `member-menu.php`)

| Funktion | Beschreibung |
|----------|-------------|
| `renderMemberSidebar(string $currentPage)` | Rendert die komplette Sidebar-Navigation |
| `renderMemberSidebarStyles()` | Gibt Inline-CSS für die Sidebar aus |
| `getMemberMenuItems(string $currentPage)` | Gibt alle Menüpunkte als Array zurück |
| `getMemberMenuGrouped(string $currentPage)` | Gibt Menüpunkte gruppiert nach Kategorie zurück |
| `isMemberSubscriptionVisible()` | Prüft DB-Setting ob Abo-Menüpunkt angezeigt wird |

---

## 1. `dashboard-view.php`

**Seite:** Dashboard  
**Slug for Sidebar:** `'dashboard'`

### Variablen

| Variable | Typ | Inhalt |
|----------|-----|--------|
| `$dashboardData` | array | Alle Dashboard-Daten (siehe CONTROLLERS.md) |
| `$user` | object | Aktueller User |

### Featuren

- **Account-Status-Karte** – Rolle, Status, Mitglied seit, Abo
- **Aktivitäts-Karte** – Logins (30 Tage), Account-Alter
- **Sicherheits-Karte** – 2FA, Passwort-Datum, aktive Sessions
- **Schnellzugriff** – Links zu allen Hauptseiten
- **Plugin-Widget-Bereich** – Via `member_dashboard_widgets` Hook
- **Letzte Aktivitäten** – With `icon`, `text`, `time_ago`

### JavaScript

- Auto-Hide der Alerts nach 5 Sekunden

---

## 2. `profile-view.php`

**Seite:** Profil  
**Slug for Sidebar:** `'profile'`

### Variablen

| Variable | Typ | Inhalt |
|----------|-----|--------|
| `$csrfToken` | string | CSRF-Token für Profil-Formular |
| `$userMeta` | array | Zusätzliche Metadaten des Users |
| `$user` | object | Aktueller User |

### Felder im Formular

| Feld | Typ | Validierung (HTML) |
|------|-----|--------------------|
| `username` | text | required |
| `email` | email | required |
| `first_name` | text | - |
| `last_name` | text | - |
| `bio` | textarea | max 500 Zeichen (JS-Limit) |
| `phone` | tel | - |
| `website` | url | - |

### JavaScript

- Zeichen-Counter für Bio-Feld (max 500)
- Auto-Hide Alerts nach 5 Sekunden

---

## 3. `security-view.php`

**Seite:** Sicherheitseinstellungen  
**Slug for Sidebar:** `'security'`

### Variablen

| Variable | Typ | Inhalt |
|----------|-----|--------|
| `$securityData` | array | Sicherheits-Score, Empfehlungen, 2FA-Status, Login-Verlauf |
| `$activeSessions` | array | Alle aktiven Sessions des Users |
| `$csrfPassword` | string | CSRF-Token für Passwort-Änderung |
| `$csrf2FA` | string | CSRF-Token für 2FA Toggle |
| `$user` | object | Aktueller User |

### Bereiche

1. **Sicherheits-Score** – SVG-Kreis mit 0–100 Score + Empfehlungen
2. **Passwort ändern** – Formular `action=change_password`
3. **2FA-Verwaltung** – Toggle Button
4. **Aktive Sitzungen** – Liste mit Terminate-Funktion
5. **Login-Verlauf** – Erfolgreich/Fehlgeschlagen

### JavaScript

- Passwort-Stärke-Anzeige (schwach/mittel/stark)
- `terminateSession(sessionId)` – Platzhalter für AJAX
- `terminateAllSessions()` – Platzhalter für AJAX
- Auto-Hide Alerts

### `$securityData` Struktur

```php
[
    'score'            => 75,              // int 0-100
    'score_message'    => 'Gut geschützt', // string
    'password_changed' => '15.01.2026',    // string (formatiert)
    '2fa_enabled'      => true,            // bool
    'recommendations'  => [
        ['text' => 'Starkes Passwort', 'done' => true],
        ['text' => '2FA aktivieren',   'done' => false],
    ],
    'login_history'    => [
        ['success' => true,  'ip' => '192.168.1.1', 'time' => 'vor 2h'],
        ['success' => false, 'ip' => '10.0.0.5',    'time' => 'vor 1d'],
    ],
]
```

### `$activeSessions` Struktur

```php
[
    [
        'id'           => 'sess_abc123',
        'device'       => 'Chrome / Windows',
        'ip'           => '192.168.1.1',
        'location'     => 'München, DE',
        'last_activity' => 'vor 5 Minuten',
        'is_current'   => true,
        'device_icon'  => '💻',
    ],
]
```

---

## 4. `notifications-view.php`

**Seite:** Benachrichtigungen  
**Slug for Sidebar:** `'notifications'`

### Variablen

| Variable | Typ | Inhalt |
|----------|-----|--------|
| `$preferences` | array | Alle gespeicherten Präferenzen |
| `$recentNotifications` | array | Letzte 10 Benachrichtigungen |
| `$csrfToken` | string | CSRF-Token für Formular |
| `$user` | object | Aktueller User |

### Bereiche

1. **Letzte Benachrichtigungen** (nur wenn `$recentNotifications` nicht leer)
2. **E-Mail-Einstellungen** – 4 Toggle-Switches
3. **Browser/App-Einstellungen** – 3 Toggle-Switches
4. **Inhaltspräferenzen** – 2 Toggle-Switches
5. **Häufigkeit** – Select-Box (`immediate`, `hourly`, `daily`, `weekly`)
6. **Plugin-Erweiterungen** – Via `member_notification_settings_sections` Hook

### `$recentNotifications` Struktur

```php
[
    [
        'read'    => false,
        'color'   => '#667eea',      // WICHTIG: wird mit htmlspecialchars() escaped
        'icon'    => '🔔',
        'title'   => 'Neue Nachricht',
        'message' => 'Du hast eine neue Nachricht erhalten.',
        'time_ago' => 'vor 5 Minuten',
    ],
]
```

### JavaScript

- `markAllAsRead()` – AJAX-Platzhalter
- `testNotification()` – Browser-Push-Test via Notification API
- Auto-Hide Alerts

---

## 5. `privacy-view.php`

**Seite:** Datenschutz & Privatsphäre  
**Slug for Sidebar:** `'privacy'`

### Variablen

| Variable | Typ | Inhalt |
|----------|-----|--------|
| `$privacySettings` | array | Alle Datenschutz-Einstellungen |
| `$dataOverview` | array | Anzahl gespeicherter Datensätze |
| `$csrfPrivacy` | string | CSRF für `update_privacy` |
| `$csrfExport` | string | CSRF für `export_data` |
| `$csrfDelete` | string | CSRF für `account_delete` |
| `$user` | object | Aktueller User |

### Bereiche

1. **Privatsphäre-Einstellungen** – Profil-Sichtbarkeit + Toggle-Switches
2. **Datenfreigabe** – 3 Toggle-Switches
3. **Daten-Übersicht** – DSGVO Art. 15 Transparenz
4. **Daten exportieren** – JSON-Download (DSGVO Art. 20)
5. **Account löschen** – Doppelte Bestätigung (DSGVO Art. 17)

### `$privacySettings` Struktur

```php
[
    'profile_visibility'    => 'private', // 'public'|'members'|'private'
    'show_email'            => false,
    'show_activity'         => false,
    'allow_contact'         => true,
    'data_sharing'          => false,
    'analytics_tracking'    => true,
    'third_party_cookies'   => false,
]
```

### `$dataOverview` Struktur

```php
[
    'profile_records' => 1,
    'activities'      => 45,
    'logins'          => 12,
    'settings'        => 8,
    'files'           => 3,
    'total_size'      => '2.4 MB',
    'sessions'        => 2,
]
```

### JavaScript

- `confirmAccountDeletion()` – Doppelte confirm()-Dialoge vor Form-Submit
- Auto-Hide Alerts

---

## 6. `subscription-view.php`

**Seite:** Mein Abonnement  
**Slug for Sidebar:** `'subscription'`

### Variablen

| Variable | Typ | Inhalt |
|----------|-----|--------|
| `$subscription` | array\|null | Aktives Abo oder `null` |
| `$availablePackages` | array | Buchbare Pakete |
| `$permissions` | array | Berechtigungs-Strings des Users |
| `$statusBadges` | array | Status → CSS-Klassen-Map |
| `$user` | object | Aktueller User |

### `$statusBadges` Werte

```php
[
    'active'    => 'success',
    'expired'   => 'danger',
    'pending'   => 'warning',
    'cancelled' => 'secondary',
]
```

### Bereiche

1. **Aktives Abonnement** – Details, Features, Verwaltungs-Buttons (wenn `$subscription`)
2. **Kein Abo** – Empty-State (wenn `$subscription === null`)
3. **Verfügbare Pakete** – Package-Grid mit „Auswählen"-Buttons
4. **Rollen & Berechtigungen** – User-Rolle + Permissions-Liste

### JavaScript

- `selectPackage(packageId)` – Platzhalter (Payment-Plugin-Integration)

---

## 7. `member-menu.php`

**Typ:** Funktions-Bibliothek (kein eigenes Template)  
**Wird von:** `MemberController::render()` als erstes included

### Menü-Kategorien

| Kategorie | Label | Enthält |
|-----------|-------|---------|
| `main` | Hauptmenü | Dashboard, Profil |
| `account` | Mein Account | Abonnement (wenn sichtbar), Sicherheit |
| `settings` | Einstellungen | Benachrichtigungen, Datenschutz |
| `plugins` | Erweiterungen | Vom Plugin-System hinzugefügte Items |

### Menü-Item Struktur

```php
[
    'slug'     => 'security',          // Eindeutiger Bezeichner
    'label'    => 'Sicherheit',        // Anzeigetext
    'icon'     => '🔒',               // Emoji-Icon
    'url'      => '/member/security',  // Relative URL
    'active'   => true|false,          // Aktiver Status
    'category' => 'account',           // Kategorie-Zuordnung
    'visible'  => true,                // Optional: für bedingte Sichtbarkeit
]
```
