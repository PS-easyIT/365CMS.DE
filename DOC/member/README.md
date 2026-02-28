# 365CMS – Mitglieder-Bereich

Der Mitglieder-Bereich ist der persönliche Bereich für eingeloggte Benutzer. Er ist vollständig von Admin-Panel und Frontend getrennt.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Zugang & Routing](#2-zugang--routing)
3. [Member-Seiten](#3-member-seiten)
4. [Architektur](#4-architektur)
5. [Plugin-Integration](#5-plugin-integration)
6. [Entwickler-Guide](#6-entwickler-guide)
7. [Weiterführende Dokumentation](#7-weiterführende-dokumentation)

---

## 1. Überblick

Der Member-Bereich bietet eingeloggten Nutzern:
- **Persönliches Dashboard** mit personalisierten Widgets
- **Profil-Verwaltung** (Name, Kontaktdaten, Avatar)
- **Medien-Verwaltung** (eigene Dateien hochladen, verwalten)
- **Nachrichten** (Direktnachrichten zwischen Mitgliedern)
- **Benachrichtigungen** (System- und User-Benachrichtigungen)
- **Abo-Verwaltung** (Plan ansehen, upgraden/kündigen)
- **Bestellhistorie** (vergangene Käufe)
- **Datenschutz-Center** (DSGVO, Datenlöschung)
- **Plugin-Inhalte** (von aktivierten Plugins, z.B. Expertenprofile)

---

## 2. Zugang & Routing

**Basis-URL:** `/member`

```
/member                 → Dashboard     (member/index.php)
/member/profile         → Profil        (member/profile.php)
/member/media           → Mediathek     (member/media.php)
/member/messages        → Nachrichten   (member/messages.php)
/member/notifications   → Benachrichtigungen (member/notifications.php)
/member/subscription    → Abo & Upgrade (member/subscription.php)
/member/favorites       → Favoriten     (member/favorites.php)
/member/privacy         → Datenschutz   (member/privacy.php)
/member/security        → Sicherheit    (member/security.php)
/member/orders          → Bestellungen  (member/order_public.php)
```

**Zugangskontrolle:**
```php
// Alle member/ Dateien prüfen ob User eingeloggt ist
$auth = CMS\Auth::instance();
if (!$auth->isLoggedIn()) {
    header('Location: /login?redirect=/member');
    exit;
}
```

---

## 3. Member-Seiten

### Dashboard (`member/index.php`)
Personalisiertes Dashboard mit:
- Willkommensnachricht mit aktuellem Abo-Plan
- Schnellzugriff auf die wichtigsten Funktionen
- Neueste Benachrichtigungen (max. 5)
- Aktuelle Aktivitäten im System
- Plugin-Widgets (von aktivierten Plugins injiziert)

### Profil (`member/profile.php`)
Profilbearbeitung:
- Profilbild hochladen/ändern
- Persönliche Daten (Vorname, Nachname, Bio)
- Kontaktdaten (Telefon, Website)
- Social-Media-Links
- E-Mail ändern (mit Bestätigungs-E-Mail)
- Passwort ändern

### Medien (`member/media.php`)
Persönliche Medienbibliothek:
- Dateien hochladen
- Eigene Bilder und Dokumente verwalten
- Speicherplatz-Anzeige (basierend auf Abo-Limit)

### Nachrichten (`member/messages.php`)
Vollständiges Member-to-Member-Messaging über `MessageService`:
- **Posteingang** – Empfangene Nachrichten mit Ungelesen-Badge
- **Gesendet** – Gesendete Nachrichten-Übersicht
- **Verfassen** – Neue Nachricht mit Empfänger-Autocomplete (AJAX-Suche)
- **Thread-Ansicht** – Chat-Bubble-Layout mit Antwort-Funktion
- **Soft-Delete** – Nachrichten einzeln löschen (physisch gelöscht wenn beide Parteien löschen)
- Pagination und CSRF-geschützte Formulare
- Datenbank: `cms_messages` (Schema v8, mit parent_id für Threads)

### Abo (`member/subscription.php`)
Abo-Verwaltung:
- Aktuellen Plan anzeigen
- Verfügbare Upgrades
- Abrechnungshistorie
- Abo kündigen (mit 30-Tage-Kündigungsfrist)

### Datenschutz (`member/privacy.php`)
DSGVO-konformes Datenschutz-Center:
- Gespeicherte Daten herunterladen
- Einzelne Datenkategorien löschen
- Account komplett löschen anfordern
- Cookie-Einstellungen

### Sicherheit (`member/security.php`)
Sicherheitseinstellungen:
- Aktive Sessions anzeigen und beenden
- Login-Verlauf
- Passwort-Stärke-Indikator
- Zwei-Faktor-Authentifizierung (2FA) via TOTP

### Favoriten (`member/favorites.php`)
Merkliste für gespeicherte Inhalte:
- Experten, Firmen und Events als Favoriten speichern
- Schnellzugriff auf gespeicherte Profile

### Bestellungen (`member/order_public.php`)
Bestellhistorie:
- Alle bisherigen Abo-Käufe mit Status
- Rechnungen als PDF abrufbar (wo verfügbar)

---

## 4. Architektur

### Dateistruktur

```
member/
├── index.php              ← Dashboard-Controller
├── profile.php            ← Profil-Controller
├── media.php              ← Medien-Controller
├── media-ajax.php         ← AJAX-Handler für Medien
├── media_handler.php      ← Datei-Upload-Logik
├── messages.php           ← Nachrichten-Controller
├── notifications.php      ← Benachrichtigungs-Controller
├── favorites.php          ← Favoriten-Controller
├── subscription.php       ← Abo-Controller
├── privacy.php            ← Datenschutz-Controller
├── security.php           ← Sicherheits-Controller
├── order_public.php       ← Bestellungen-Controller
├── plugin-section.php     ← Plugin-Integrations-Renderer
├── debug_snippet.php      ← Debug-Hilfsmittel (nur dev)
├── includes/
│   └── class-member-controller.php  ← Basis-Controller-Klasse
└── partials/              ← View-Templates
    ├── member-menu.php            ← Sidebar-Navigation + Hilfsfunktionen
    ├── dashboard-view.php         ← Dashboard-Template
    ├── profile-view.php           ← Profil-Template
    ├── security-view.php          ← Sicherheits-Template
    ├── notifications-view.php     ← Benachrichtigungs-Template
    ├── privacy-view.php           ← Datenschutz-Template
    └── subscription-view.php      ← Abo-Template
```

### MemberController

Der `MemberController` ist die Basisklasse aller Member-Seiten:

```php
class MemberController {
    protected object $user;

    public function __construct() {
        // Automatischer Auth-Check
        $auth = CMS\Auth::instance();
        if (!$auth->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
        $this->user = $auth->getCurrentUser();
    }

    public function getUser(): object {
        return $this->user;
    }

    public function render(string $view, array $data = []): void {
        extract($data);
        include __DIR__ . '/partials/header.php';
        include __DIR__ . '/' . $view . '.php';
        include __DIR__ . '/partials/footer.php';
    }
}
```

---

## 5. Plugin-Integration

Plugins können Member-Bereiche hinzufügen über `PluginDashboardRegistry`:

```php
// In eurem Plugin
CMS\Hooks::addAction('member_dashboard_sections', function($registry) {
    $registry->register('my-plugin-section', [
        'title'    => 'Mein Plugin',
        'icon'     => 'icon-my-plugin',
        'callback' => function($userId) {
            include PLUGIN_PATH . 'mein-plugin/member/dashboard-section.php';
        },
        'priority' => 20,
    ]);
});
```

Das Plugin `plugin-section.php` rendert dann alle registrierten Sektionen im Member-Dashboard.

---

## 6. Entwickler-Guide

### Eigene Member-Seite über Plugin hinzufügen

```php
// 1. Route registrieren
CMS\Hooks::addAction('routes_registered', function() {
    CMS\Router::instance()->addRoute('GET', '/member/mein-feature', function() {
        $auth = CMS\Auth::instance();
        if (!$auth->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
        include PLUGIN_PATH . 'mein-plugin/member/mein-feature.php';
    });
});

// 2. Navigation-Link hinzufügen
CMS\Hooks::addFilter('member_menu_items', function(array $items): array {
    $items[] = [
        'label' => 'Mein Feature',
        'url'   => '/member/mein-feature',
        'icon'  => 'icon-star',
    ];
    return $items;
});
```

---

## 7. Weiterführende Dokumentation

| Dokument | Inhalt |
|----------|--------|
| [CONTROLLERS.md](CONTROLLERS.md) | Alle Controller-Klassen, Formularfelder, View-Daten |
| [VIEWS.md](VIEWS.md) | Template-Variablen, Bereiche, JS-Funktionen |
| [HOOKS.md](HOOKS.md) | Alle verfügbaren Actions & Filter für Plugins |
| [SECURITY.md](SECURITY.md) | Sicherheitsmodell, CSRF, Sanitization, Output-Escaping |
| [general/DASHBOARD.md](general/DASHBOARD.md) | Dashboard-Widgets im Detail |
| [general/PROFILE.md](general/PROFILE.md) | Profil-Felder, Avatar, Social-Links |
| [general/SECURITY.md](general/SECURITY.md) | 2FA, Session-Verwaltung, Login-History |
| [general/NOTIFICATIONS.md](general/NOTIFICATIONS.md) | Benachrichtigungstypen, Filter, E-Mail-Settings |
| [general/PRIVACY.md](general/PRIVACY.md) | DSGVO, Datenexport, Account-Löschung |
| [general/SUBSCRIPTION.md](general/SUBSCRIPTION.md) | Pakete, Upgrade-Prozess, Zahlungsoptionen |

---

*Letzte Aktualisierung: 22. Februar 2026 – Version 1.8.0*
