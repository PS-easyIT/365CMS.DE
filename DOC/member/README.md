# 365CMS – Mitglieder-Bereich

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026

Der Mitglieder-Bereich ist der persönliche Bereich für eingeloggte Benutzer. Er ist vollständig von Admin-Panel und Frontend getrennt.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Zugang & Routing](#2-zugang--routing)
3. [Member-Seiten](#3-member-seiten)
4. [Architektur](#4-architektur)
5. [Plugin-Integration](#5-plugin-integration)
6. [Entwickler-Guide](#6-entwickler-guide)

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
/member                 → Dashboard (member/index.php)
/member/profile         → Profil-Verwaltung
/member/media           → Medienbibliothek
/member/messages        → Nachrichten
/member/notifications   → Benachrichtigungen
/member/subscription    → Abo & Upgrade
/member/favorites       → Favoriten-Liste
/member/privacy         → Datenschutz & DSGVO
/member/security        → Sicherheitseinstellungen
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
Direktnachrichten-System:
- Konversationsliste
- Neue Konversation starten
- Nachrichten lesen und senden
- Benachrichtigung bei neuen Nachrichten

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

---

## 4. Architektur

### Dateistruktur

```
member/
├── index.php           ← Dashboard-Controller
├── profile.php         ← Profil-Controller
├── media.php           ← Medien-Controller
├── media-ajax.php      ← AJAX-Handler für Medien
├── media_handler.php   ← Datei-Upload-Logik
├── messages.php        ← Nachrichten-Controller
├── notifications.php   ← Benachrichtigungs-Controller
├── favorites.php       ← Favoriten-Controller
├── subscription.php    ← Abo-Controller
├── privacy.php         ← Datenschutz-Controller
├── security.php        ← Sicherheits-Controller
├── order_public.php    ← Bestellungen-Controller
├── plugin-section.php  ← Plugin-Integrations-Renderer
├── debug_snippet.php   ← Debug-Hilfsmittel (nur dev)
├── includes/           ← Wiederverwendbare Klassen
│   └── class-member-controller.php
└── partials/           ← Template-Fragmente (Header, Footer, Nav)
    ├── header.php
    ├── footer.php
    └── navigation.php
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
CMS\Hooks::addFilter('member_nav_items', function(array $items): array {
    $items[] = [
        'label' => 'Mein Feature',
        'url'   => '/member/mein-feature',
        'icon'  => 'icon-star',
    ];
    return $items;
});
```

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
