# 365CMS – System-Architektur

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [System-Schichten](#2-system-schichten)
3. [Request-Lifecycle](#3-request-lifecycle)
4. [Core-Klassen](#4-core-klassen)
5. [Design-Patterns](#5-design-patterns)
6. [Verzeichnis-Struktur](#6-verzeichnis-struktur)
7. [Erweiterbarkeit](#7-erweiterbarkeit)

---

## 1. Überblick

Das 365CMS folgt einer **mehrschichtigen Architektur** (Layered Architecture) mit klarer Trennung der Verantwortlichkeiten:

```
┌──────────────────────────────────────────────────────────┐
│  PRESENTATION LAYER – Themes, Templates, Admin-UI        │
├──────────────────────────────────────────────────────────┤
│  APPLICATION LAYER – Router, Hooks, PluginManager        │
├──────────────────────────────────────────────────────────┤
│  BUSINESS LOGIC LAYER – Auth, Security, PageManager, API │
├──────────────────────────────────────────────────────────┤
│  SERVICE LAYER – 11 Service-Klassen                      │
├──────────────────────────────────────────────────────────┤
│  DATA ACCESS LAYER – Database, CacheManager              │
├──────────────────────────────────────────────────────────┤
│  INFRASTRUCTURE – Bootstrap, config.php, Session         │
└──────────────────────────────────────────────────────────┘
```

**Architektur-Prinzipien:**
- **Modularität**: Jede Klasse hat eine Verantwortung (Single Responsibility)
- **Sicherheit**: Security-First – Validierung auf jeder Ebene
- **Erweiterbarkeit**: Hook-System für Plugins ohne Core-Eingriffe
- **Performance**: Lazy Loading, Query-Caching, komprimierte Assets

---

## 2. System-Schichten

### Infrastructure Layer

**`config.php`** – Startpunkt. Definiert alle Konstanten:
- `ABSPATH` – Absoluter Pfad zum CMS-Root
- `CMS_VERSION` – Aktuelle Version (0.26.13)
- `DB_*` – Datenbank-Zugangsdaten
- `SITE_URL` – Basis-URL ohne Trailing Slash
- `CMS_DEBUG` – Debug-Modus (in Produktion: false)

**`core/autoload.php`** – Lädt alle Core-Klassen automatisch.

### Data Access Layer

**`Database`** – PDO-Wrapper mit:
- Prepared Statements (SQL-Injection-Schutz)
- Connection Pooling (Singleton)
- WordPress-kompatibler API (`get_row`, `get_results`, etc.)
- Automatische Tabellen-Erstellung beim ersten Start

**`CacheManager`** – Datei-basierter Cache für:
- DB-Query-Ergebnisse
- Block-Fragmente (Template-Caching)
- Konfigurierbare TTL per Cache-Eintrag

### Business Logic Layer

**`Auth`** – Login, Logout, Registrierung, Session-Verwaltung  
**`Security`** – CSRF, XSS, Rate-Limiting, Security-Header  
**`PageManager`** – Seiten-Rendering, Meta-Tags  
**`Api`** – REST-API-Handler

### Application Layer

**`Router`** – URL-Routing (GET/POST/PUT/DELETE):
- Statische Routen für Admin und Member
- Dynamische Plugin-Routen
- Fehlerseiten (403, 404, 500)

**`Hooks`** – WordPress-ähnliches Event-System:
```php
// Action: Code ausführen wenn Ereignis eintritt
CMS\Hooks::addAction('user_registered', function($userId) { ... });

// Filter: Wert verändern
CMS\Hooks::addFilter('page_title', function($title) {
    return $title . ' | MySite';
});
```

**`PluginManager`** – Lädt aktive Plugins aus Datenbank + Dateisystem  
**`ThemeManager`** – Aktiviert Theme, rendert Templates

### Service Layer

11 spezialisierte Service-Klassen für Geschäftslogik:
`AnalyticsService`, `BackupService`, `DashboardService`, `EditorService`,
`LandingPageService`, `MediaService`, `MemberService`, `SEOService`,
`StatusService`, `ThemeCustomizer`, `UserService`

### Presentation Layer

- **Themes** in `themes/{theme-name}/` – vollständige Templates
- **Admin-UI** in `admin/` – PHP-Dateien pro Seite
- **Member-UI** in `member/` – mit separaten Controller-Klassen

---

## 3. Request-Lifecycle

Jede HTTP-Anfrage durchläuft folgende Schritte:

```
Browser-Request
     │
     ▼
index.php / admin/index.php
     │
     ▼
config.php  ← Konstanten laden
     │
     ▼
core/autoload.php  ← Klassen registrieren
     │
     ▼
Bootstrap::instance()
  ├─ Database::instance()     ← DB verbinden
  ├─ Security::instance()     ← Security-Header setzen, Session starten
  ├─ Auth::instance()         ← Session prüfen, User laden
  ├─ Hooks::doAction('init')  ← Plugins initialisieren
  ├─ PluginManager::loadPlugins()  ← Aktive Plugins laden
  ├─ ThemeManager::instance() ← Theme aktivieren
  └─ Router::dispatch()       ← URL auflösen
        │
        ▼
   Template/Controller
        │
        ▼
   Response → Browser
```

---

## 4. Core-Klassen

Übersicht aller 11 Core-Klassen (Details → [CORE-CLASSES.md](CORE-CLASSES.md)):

| Klasse | Datei | Aufgabe |
|--------|-------|---------|
| `Bootstrap` | `core/Bootstrap.php` | System-Initialisierung |
| `Database` | `core/Database.php` | PDO-Datenbankzugriff |
| `Security` | `core/Security.php` | CSRF, XSS, Headers |
| `Auth` | `core/Auth.php` | Login, Session, Rechte |
| `Router` | `core/Router.php` | URL-Routing |
| `Hooks` | `core/Hooks.php` | Actions & Filters |
| `PluginManager` | `core/PluginManager.php` | Plugin-Verwaltung |
| `ThemeManager` | `core/ThemeManager.php` | Theme-Rendering |
| `CacheManager` | `core/CacheManager.php` | Datei-Cache |
| `PageManager` | `core/PageManager.php` | Seiten-Rendering |
| `SubscriptionManager` | `core/SubscriptionManager.php` | Abo-System |

---

## 5. Design-Patterns

### Singleton-Pattern
Alle Core-Klassen implementieren das Singleton-Pattern:
```php
class MyClass {
    private static ?self $instance = null;

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Initialisierung
    }
}
```
**Warum?** Jede Klasse soll nur einmal im System existieren – keine doppelten DB-Verbindungen, kein mehrfaches Laden von Configs.

### Observer-Pattern (Hook-System)
Plugins registrieren sich für Ereignisse, ohne Core-Dateien zu ändern:
```php
// Plugin registriert Hook
CMS\Hooks::addAction('before_content', function() {
    echo '<div class="my-plugin-banner">...</div>';
});

// Core feuert Hook
CMS\Hooks::doAction('before_content');
```

### Service-Layer-Pattern
Geschäftslogik ist aus Controllern in Services ausgelagert:
```php
// In einem Controller/Admin-File:
$userService = UserService::getInstance();
$users = $userService->getAllUsers(['role' => 'member']);
```

---

## 6. Verzeichnis-Struktur

```
CMS/                         ← Root des CMS
├── config.php               ← Konfiguration (DB, URLs, Schlüssel)
├── index.php                ← Frontend-Einstiegspunkt
├── install.php              ← Einmaliger Installations-Wizard
├── .htaccess                ← Apache-Rewrite-Regeln
│
├── core/                    ← 11 Core-Klassen + Services
│   ├── Bootstrap.php
│   ├── Database.php
│   ├── Security.php
│   ├── Auth.php
│   ├── Router.php
│   ├── Hooks.php
│   ├── PluginManager.php
│   ├── ThemeManager.php
│   ├── CacheManager.php
│   ├── PageManager.php
│   ├── SubscriptionManager.php
│   ├── autoload.php
│   ├── WP_Error.php         ← WordPress-kompatible Error-Klasse
│   ├── Member/
│   │   └── PluginDashboardRegistry.php
│   └── Services/            ← 11 Service-Klassen
│
├── admin/                   ← Admin-Panel (24 PHP-Dateien)
├── member/                  ← Mitglieder-Bereich
├── themes/                  ← Themes (1 aktiv: cms-default)
├── plugins/                 ← Installierte Plugins
├── assets/                  ← CSS, JS, Bilder
├── uploads/                 ← Hochgeladene Dateien
├── cache/                   ← Cache-Dateien (auto-generiert)
├── logs/                    ← Fehler-Logs
├── includes/                ← Helper-Funktionen
└── db/migrations/           ← SQL-Migrations-Dateien
```

---

## 7. Erweiterbarkeit

Das CMS ist auf drei Ebenen erweiterbar:

### 1. Plugins
Neue Funktionen über das Plugin-System hinzufügen:
- Datei: `plugins/mein-plugin/mein-plugin.php`
- Hooks nutzen um Core-Behavior zu erweitern
- Eigene Admin-Seiten registrieren
- Eigene DB-Tabellen erstellen
→ Details: [../plugins/PLUGIN-DEVELOPMENT.md](../plugins/PLUGIN-DEVELOPMENT.md)

### 2. Themes
Visuelles Design anpassen:
- Eigene Theme-Templates in `themes/mein-theme/`
- CSS-Variablen-System für Design-Tokens
- Hook-Punkte in Templates
→ Details: [../theme/THEME-DEVELOPMENT.md](../theme/THEME-DEVELOPMENT.md)

### 3. Services
Business-Logik erweitern durch eigene Service-Klassen:
- Neue Klasse in `core/Services/` oder `plugins/mein-plugin/services/`
- Singleton-Pattern nutzen
- Via Hooks exposrieren

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
