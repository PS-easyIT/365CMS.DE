# Member Center - Dokumentation

## Überblick

Das Member Center ist ein vollständig ausgebautes Benutzer-Dashboard für CMS365 v2. Es bietet eine moderne, benutzerfreundliche Oberfläche für alle wichtigen Benutzer-Funktionen.

### Design-Philosophie

Das Member Center verwendet ein **eigenes, modernes Design** mit:
- **Lila/Pink Gradient-Farbschema** (667eea → 764ba2)
- Heller, freundlicher Hintergrund (#f7fafc)
- Moderne Karten mit Schatten & Hover-Effekten
- Klare Unterscheidung vom dunklen Admin-Bereich

## Architektur

### MVC-ähnliche Struktur

```
member/
├── index.php                    # Dashboard (Controller)
├── profile.php                  # Profil-Verwaltung (Controller)
├── subscription.php             # Abonnement-Verwaltung (Controller)
├── security.php                 # Sicherheitseinstellungen (Controller)
├── notifications.php            # Benachrichtigungen (Controller)
├── privacy.php                  # Datenschutz & DSGVO (Controller)
├── includes/
│   ├── class-member-controller.php   # Basis-Controller
│   └── class-member-service.php      # Backend-Logik
├── partials/
│   ├── member-menu.php          # Menü-System
│   ├── dashboard-view.php       # Dashboard-View
│   ├── profile-view.php         # Profil-View
│   ├── subscription-view.php    # Abo-View
│   ├── security-view.php        # Sicherheits-View
│   ├── notifications-view.php   # Benachrichtigungs-View
│   └── privacy-view.php         # Datenschutz-View
└── README.md                    # Diese Datei
```

### Controller-Pattern

Alle Member-Seiten folgen diesem Pattern:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/class-member-controller.php';

class PageNameController extends CMS\Member\MemberController {
    
    public function __construct() {
        parent::__construct(); // Auth-Check & CSRF-Setup
    }
    
    public function processlRequest(): void {
        // POST-Verarbeitung
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyToken()) {
                $this->setError('Security check failed');
                return;
            }
            
            // Daten verarbeiten
            $result = MemberService::instance()->someMethod($data);
            
            if ($result) {
                $this->setSuccess('Operation successful');
            }
        }
    }
    
    public function getData(): array {
        // View-Daten vorbereiten
        return [
            'key' => 'value'
        ];
    }
}

$controller = new PageNameController();
$controller->processRequest();

// View rendern
$controller->render('view-name', $controller->getData());
```

## Feature-Übersicht

### 1. Dashboard (index.php)
- **Account-Status:** Benutzerrolle, Mitglied seit, letzter Login
- **Aktivität:** Letzte Aktionen des Benutzers
- **Sicherheits-Score:** Visueller Security-Score mit Empfehlungen
- **Schnellaktionen:** Häufig genutzte Funktionen
- **Plugin-Widgets:** Hook für Plugins, eigene Widgets hinzuzufügen

```php
// Plugin-Widget hinzufügen
Hooks::addFilter('member_dashboard_widgets', function($widgets) {
    $widgets['my_widget'] = [
        'title' => 'Mein Widget',
        'content' => '<div>Widget-Inhalt</div>',
        'priority' => 10
    ];
    return $widgets;
});
```

### 2. Profil (profile.php)
- **Persönliche Daten:** Vorname, Nachname, E-Mail, Telefon
- **Avatar-Verwaltung:** Bild hochladen/ändern
- **Zusätzliche Felder:** Firma, Position, Website, Bio
- **Account-Informationen:** Registrierungsdatum, Benutzer-ID
- **User Meta:** Erweiterbare Meta-Daten

### 3. Abonnement (subscription.php)
- **Paket-Übersicht:** Aktuelles Abo (nur wenn Admin aktiviert)
- **Features:** Liste aller Funktionen des Pakets
- **Laufzeit:** Start-/Enddatum, Auto-Renewal-Status
- **Verfügbare Pakete:** Upgrade-/Downgrade-Optionen
- **Admin-Kontrolle:** Setting `member_subscription_visible`

### 4. Sicherheit (security.php)
- **Passwort ändern:** Mit Stärke-Anzeige
- **Zwei-Faktor-Auth:** QR-Code, Backup-Codes
- **Aktive Sessions:** Alle Geräte, Standorte, Revoke-Option
- **Login-Historie:** Letzte 50 Logins mit Status & IP
- **Sicherheits-Score:** Berechnete Sicherheitsstufe

### 5. Benachrichtigungen (notifications.php)
- **E-Mail-Präferenzen:** Granulare Kontrolle über E-Mails
- **Push-Benachrichtigungen:** Desktop & Mobile
- **Kategorie-Einstellungen:** Security, Account, System, Marketing
- **Benachrichtigungs-Historie:** Letzte 20 Benachrichtigungen
- **Als gelesen markieren:** Single & Bulk-Actions

### 6. Datenschutz (privacy.php)
- **DSGVO-Compliance:** EU-konforme Datenschutz-Tools
- **Datenübersicht:** Welche Daten gespeichert werden
- **Daten exportieren:** JSON/CSV-Export aller Daten
- **Profil verbergen:** Öffentliche Sichtbarkeit steuern
- **Account löschen:** Mit Sicherheits-Checkbox & Bestätigung

## Menü-System

Das Menü wird zentral in `partials/member-menu.php` verwaltet:

```php
require_once __DIR__ . '/member/partials/member-menu.php';

// Automatische Sidebar mit aktiver Seite
renderMemberSidebar(basename($_SERVER['PHP_SELF']));

// Nur Styles
renderMemberSidebarStyles();

// Menü-Items holen
$items = getMemberMenuItems();
```

### Menü-Items erweitern (via Plugin)

```php
Hooks::addFilter('member_menu_items', function($items) {
    $items['myplugin'] = [
        'label' => 'My Plugin',
        'url' => '/member/myplugin.php',
        'icon' => '🔌',
        'order' => 50,
        'category' => 'plugins'
    ];
    return $items;
});
```

### Menü-Kategorien

- **account:** Kern-Account-Funktionen (Profil, Sicherheit)
- **subscription:** Abo & Zahlungen
- **communication:** Nachrichten & Benachrichtigungen
- **privacy:** Datenschutz & DSGVO
- **plugins:** Plugin-spezifische Seiten (werden automatisch gruppiert)

## Service-Layer

Die Business-Logik ist in `MemberService` gekapselt:

```php
use CMS\Member\MemberService;

$service = MemberService::instance();

// Profil aktualisieren
$result = $service->updateProfile($userId, [
    'first_name' => 'Max',
    'last_name' => 'Mustermann'
]);

// User Meta
$service->saveUserMeta($userId, 'company', 'ACME GmbH');
$company = $service->getUserMeta($userId, 'company');

// Dashboard-Daten
$dashboardData = $service->getMemberDashboardData($userId);

// Benachrichtigungen
$notifications = $service->getRecentNotifications($userId, 20);
$service->markNotificationAsRead($notificationId);

// Sicherheit
$score = $service->calculateSecurityScore($userId);
$sessions = $service->getActiveSessions($userId);
$service->revokeSession($sessionId);
```

## CSS-Organisation

Die Member-Styles sind komplett getrennt vom Admin:

```html
<!-- In allen Member-Pages -->
<link rel="stylesheet" href="/assets/css/member.css">
```

### CSS-Variablen

```css
:root {
    --member-primary: #667eea;
    --member-secondary: #764ba2;
    --member-gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --member-bg: #f7fafc;
    --member-surface: #ffffff;
    /* ... weitere */
}
```

### Komponenten

- `.member-card` - Basis-Karte mit Hover
- `.member-btn-primary` - Gradient-Button
- `.member-badge-*` - Status-Badges
- `.member-form-*` - Formulare mit Fokus-Effekten
- `.member-alert-*` - Success/Error/Warning/Info-Alerts
- `.member-toggle` - iOS-style Toggle
- `.security-score` - Kreis-Diagramm für Score
- `.notification-item` - Benachrichtigungs-Karte
- `.subscription-meta-grid` - Abo-Informationen

## Sicherheit

### CSRF-Schutz

Jedes Formular hat automatisch CSRF-Schutz:

```php
// In View
<input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

// In Controller
if (!$this->verifyToken()) {
    $this->setError('Security check failed');
    return;
}
```

### Authentication

Automatische Auth-Checks in `MemberController::__construct()`:

```php
if (!$this->auth->isLoggedIn()) {
    header('Location: /login.php');
    exit;
}
```

### Input-Sanitization

Helper-Methoden im Base-Controller:

```php
$name = $this->getPost('name');           // Automatisch sanitized
$email = $this->getPost('email', '');     // Mit Default
$checked = $this->isChecked('newsletter'); // Checkbox-Helper
```

## Hooks & Filter

### Dashboard-Hooks

```php
// Widgets hinzufügen
Hooks::applyFilters('member_dashboard_widgets', []);

// Aktivitäten hinzufügen
Hooks::applyFilters('member_recent_activity', []);

// Schnellaktionen erweitern
Hooks::applyFilters('member_quick_actions', []);
```

### Profil-Hooks

```php
// Zusätzliche Profilfelder
Hooks::applyFilters('member_profile_fields', []);

// Nach Profil-Update
Hooks::doAction('member_profile_updated', $userId, $data);
```

### Benachrichtigungs-Hooks

```php
// Benachrichtigungs-Kategorien
Hooks::applyFilters('member_notification_categories', []);

// Neue Benachrichtigung
Hooks::doAction('member_notification_created', $notificationId, $userId);
```

## Abonnement-Admin-Kontrolle

Der Menü-Punkt "Abonnement" wird nur angezeigt, wenn:

```php
// Admin-Bereich: Settings > Member Settings
'member_subscription_visible' => true  // Default: false
```

Das Feature ermöglicht zentrale Kontrolle über Abo-Sichtbarkeit:

```php
// In getMemberMenuItems()
if (!Settings::get('member_subscription_visible', false)) {
    unset($items['subscription']);
}
```

## Plugin-Integration

Plugins können eigene Seiten & Features hinzufügen:

### 1. Eigene Member-Seite

```php
// myplugin/member/mypage.php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../../member/includes/class-member-controller.php';

class MyPluginController extends CMS\Member\MemberController {
    // ... Controller-Logik
}

$controller = new MyPluginController();
$controller->processRequest();
$controller->render('myplugin-view', $controller->getData());
```

### 2. Menü-Item registrieren

```php
// In Plugin-Init
Hooks::addFilter('member_menu_items', function($items) {
    $items['myplugin'] = [
        'label' => 'My Feature',
        'url' => '/plugins/myplugin/member/mypage.php',
        'icon' => '🔧',
        'order' => 100,
        'category' => 'plugins'
    ];
    return $items;
});
```

### 3. Dashboard-Widget

```php
Hooks::addFilter('member_dashboard_widgets', function($widgets) {
    $widgets['myplugin_stats'] = [
        'title' => 'Meine Stats',
        'content' => MyPlugin::renderStatsWidget(),
        'priority' => 20
    ];
    return $widgets;
});
```

## Best Practices

### View-Entwicklung

1. **Keine Logik in Views:** Nur Darstellung, keine Business-Logik
2. **Escaping:** Immer `htmlspecialchars()` für User-Content
3. **Konsistente Klassen:** Nur `.member-*` Präfix verwenden

### Controller-Entwicklung

1. **POST-Verarbeitung zuerst:** Vor Daten-Abruf
2. **CSRF immer prüfen:** Bei jeder State-Änderung
3. **Errors vs. Success:** Klare Trennung via `setError()` / `setSuccess()`

### Service-Entwicklung

1. **Singleton-Pattern:** Über `::instance()` instanziieren
2. **Return-Types:** Immer typisieren (`bool`, `array`, `?object`)
3. **Database-Escaping:** Prepared Statements verwenden

## Erweiterung

### Neue Seite hinzufügen

1. **Controller erstellen** (`member/mynewpage.php`)
2. **View erstellen** (`member/partials/mynewpage-view.php`)
3. **Service-Methoden** (in `MemberService`)
4. **Menü-Item** (via Filter `member_menu_items`)
5. **CSS-Styles** (in `assets/css/member.css`)

### Neues Feature in bestehende Seite

1. **Service-Methode** für Backend-Logik
2. **Controller-Methode** für Datenverarbeitung
3. **View-Abschnitt** für UI
4. **Hook** für Plugin-Erweiterbarkeit

## Testing

### Checklist für neue Features

- [ ] CSRF-Schutz implementiert
- [ ] Input sanitization aktiv
- [ ] Output escaping in View
- [ ] Error-Handling vorhanden
- [ ] Success-Messages definiert
- [ ] Responsive Design getestet
- [ ] Hooks für Plugin-Integration
- [ ] Dokumentation aktualisiert

## Troubleshooting

### Menü-Item erscheint nicht

1. Check `getMemberMenuItems()` Output
2. Verify `category` ist gültig
3. Prüfe `order` für Sortierung
4. Bei Abo-Item: `member_subscription_visible` Setting

### View wird nicht geladen

1. Check View-Dateiname in `render()` stimmt
2. Verify Pfad: `partials/{name}-view.php`
3. Check `$data` Array enthält alle benötigten Keys

### CSRF-Fehler

1. Verify `$csrfToken` in View vorhanden
2. Check `name="csrf_token"` im Form
3. `verifyToken()` vor Datenverarbeitung

### Styling-Probleme

1. Check `member.css` ist eingebunden
2. Verify Klassen-Präfix `.member-*`
3. Browser-Cache leeren
4. DevTools Console auf Fehler prüfen

## Version History

- **v1.0.0** (2024-01-XX)
  - Initiales Release
  - 6 Kern-Seiten (Dashboard, Profil, Abo, Sicherheit, Benachrichtigungen, Datenschutz)
  - MVC-Architektur
  - Plugin-System via Hooks
  - Eigenes Design-System
  - Vollständige DSGVO-Compliance

## Roadmap

- [ ] Avatar-Upload-Funktionalität
- [ ] E-Mail-Verifizierung
- [ ] Two-Factor-Auth Backend
- [ ] Export als PDF
- [ ] Dark-Mode Toggle
- [ ] Favoriten/Lesezeichen
- [ ] Erweiterte Aktivitäts-Timeline

## Support & Contribution

Bei Fragen oder Verbesserungsvorschlägen:
- Dokumentation lesen
- Bestehenden Code als Vorlage nutzen
- Hook-System für Erweiterungen verwenden
- Code-Standards einhalten (PSR-12, Strict Types)

---

**CMS365 v2 Member Center** - Modernes, erweiterbares Benutzer-Dashboard
