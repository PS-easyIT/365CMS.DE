# CMS Core – Übersicht

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026

Das `core/`-Verzeichnis enthält alle Kernklassen des 365CMS.  
Alle Klassen folgen dem **Singleton-Pattern** und sind über PSR-4 autogeladen.

---

## Verzeichnisstruktur

```
core/
├── autoload.php              PSR-4 Autoloader (CMS\ → /core/, CMS\Services\ → /core/Services/)
├── Api.php                   REST API Controller v1
├── Auth.php                  Authentifizierung, Session, Rollen
├── Bootstrap.php             System-Initialisierung
├── CacheManager.php          Datei-Cache, OPcache, APCu, LiteSpeed
├── Database.php              PDO-Wrapper mit prepared statements
├── Debug.php                 Debug-Logging, HTML-Ausgabe
├── Hooks.php                 WordPress-ähnliches Action/Filter-System
├── PageManager.php           Seitenverwaltung (CRUD, Suche, Revisions)
├── PluginManager.php         Plugin-Laden, Aktivieren, Deaktivieren
├── Router.php                URL-Routing und Request-Dispatching
├── Security.php              CSRF, XSS, Sanitize, Rate-Limiting
├── SubscriptionManager.php   Abo-Pakete, Gruppen, Nutzungsgrenzen
├── ThemeManager.php          Theme-Laden, Template-Rendering
├── WP_Error.php              WordPress-kompatible Fehlerklasse
└── Services/
    ├── AnalyticsService.php   Besucherstatistiken
    ├── BackupService.php      Datenbank-/Datei-Backups
    ├── DashboardService.php   Dashboard-Statistiken
    ├── EditorService.php      Seiten-Editor Logik
    ├── LandingPageService.php Landing Pages (Sections)
    ├── SEOService.php         Sitemap, Robots.txt, Meta-Tags
    ├── StatusService.php      System-Health-Checks, Reparatur
    ├── SystemService.php      System-Infos, DB-Status
    ├── ThemeCustomizer.php    Theme-Einstellungen (Farben, Fonts)
    ├── TrackingService.php    Page-View-Tracking
    ├── UpdateService.php      CMS-Update-Prüfung
    └── UserService.php        Benutzer-CRUD für Admin
```

---

## Wichtige Muster

### Singleton-Aufruf

```php
$db   = Database::instance();
$auth = Auth::instance();
$sec  = Security::instance();
```

Services nutzen `getInstance()` (historische Abweichung, funktional identisch):

```php
$dashboard = DashboardService::getInstance();
$user      = UserService::getInstance();
```

### Konstanten

| Konstante          | Bedeutung                         |
|--------------------|-----------------------------------|
| `ABSPATH`          | Absoluter Serverpfad zum CMS-Root |
| `CORE_PATH`        | `ABSPATH . 'core/'`               |
| `SITE_URL`         | Öffentliche Base-URL              |
| `DB_PREFIX`        | Datenbank-Tabellenpräfix (Standard: `cms_`) |
| `CMS_VERSION`      | Aktuelle CMS-Version              |
| `CMS_DEBUG`        | Debug-Modus (bool)                |
| `MAX_LOGIN_ATTEMPTS`| Rate-Limit Login                 |
| `LOGIN_TIMEOUT`    | Rate-Limit Zeitfenster (Sekunden) |

---

## Dokumentation

| Datei                    | Inhalt                                        |
|--------------------------|-----------------------------------------------|
| [CORE-CLASSES.md](CORE-CLASSES.md) | Detailreferenz aller 15 Core-Klassen  |
| [SERVICES.md](SERVICES.md)         | Alle 12 Service-Klassen dokumentiert  |
| [SECURITY-ARCHITECTURE.md](SECURITY-ARCHITECTURE.md) | Sicherheitsmodell |
| [../ARCHITECTURE.md](../ARCHITECTURE.md) | Gesamt-Systemarchitektur      |
| [../DATABASE-SCHEMA.md](../DATABASE-SCHEMA.md)   | Alle DB-Tabellen          |
| [../HOOKS-REFERENCE.md](../HOOKS-REFERENCE.md)   | Action/Filter-Referenz    |
