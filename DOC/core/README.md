> **Website:** [365CMS](https://365cms.de/)  
> **Version:** 3.4.00  
> **Datum:** 2026-09-06  
> **Status:** Aktuell · **Zuletzt aktualisiert am: 2026-09-06**  
> **Kurzbeschreibung:** Einstieg in Aufbau, Zuständigkeiten und Betriebsweise des 365CMS-Core. Die Übersicht wurde mit dem vollständigen Verzeichnis `CMS/core/` abgeglichen.

## English

### User-friendly
The core contains the stable runtime of 365CMS: startup, routing, storage, security, authentication, plugins, themes and reusable services.

### Technical
The verified tree contains root classes and the `Auth`, `Contracts`, `Http`, `Member`, `Routing` and `Services` namespaces, including AI, Editor.js, Landing, Media, SEO and SiteTable modules. The complete detailed reference is retained in the German technical section below.

## Deutsch

### Anwenderfreundlich
Der Core enthält die stabile Laufzeit des 365CMS: Start, Routing, Speicherung, Sicherheit, Anmeldung, Plugins, Themes und Services.

### Technisch
Die nachfolgende vollständige Übersicht wurde mit dem gesamten Baum `CMS/core/` abgeglichen.

# CMS Core – Übersicht
> **Stand:** 2026-09-06 | **Version:** 3.4.00 | **Status:** Aktuell

## Inhaltsverzeichnis
- [Verzeichnisstruktur](#verzeichnisstruktur)
- [Wichtige Muster](#wichtige-muster)
- [Dokumentation](#dokumentation)

<!-- UPDATED: 2026-09-06 -->

Das `core/`-Verzeichnis enthält alle Kernklassen des 365CMS.  
Die meisten Klassen folgen dem **Singleton-Pattern** und sind über PSR-4 autogeladen.

---

## Verzeichnisstruktur

```
core/
├── autoload.php              PSR-4 Autoloader (CMS\ → /core/, CMS\Services\ → /core/Services/)
├── Api.php                   REST API Controller v1
├── AuditLogger.php           Sicherheits-Audit-Log (audit_log-Tabelle)
├── Auth.php                  Authentifizierung, Session, Rollen
├── Bootstrap.php             System-Initialisierung
├── CacheManager.php          Datei-Cache, OPcache, APCu, LiteSpeed
├── Container.php             Dependency-Injection-Container
├── Database.php              PDO-Wrapper mit prepared statements
├── Debug.php                 Debug-Logging, HTML-Ausgabe (statisch)
├── Hooks.php                 WordPress-ähnliches Action/Filter-System
├── Json.php                  Null-sichere JSON-Helfer für Settings und Runtime-Pfade
├── Logger.php                PSR-3-kompatibles Logging mit Channel-Support
├── MigrationManager.php      Inkrementelle ALTER-TABLE-Migrationen
├── PageManager.php           Seitenverwaltung (CRUD, Suche, Revisions)
├── PluginManager.php         Plugin-Laden, Aktivieren, Deaktivieren
├── Router.php                URL-Routing und Request-Dispatching
├── SchemaManager.php         CREATE TABLE – 44 Basis-Tabellen
├── Security.php              CSRF, XSS, Sanitize, Rate-Limiting
├── SubscriptionManager.php   Abo-Pakete, Gruppen, Nutzungsgrenzen
├── TableOfContents.php       TOC-Widget, Anker-IDs, [cms_toc]-Shortcode
├── ThemeManager.php          Theme-Laden, Template-Rendering
├── Totp.php                  TOTP 2FA (RFC 6238, Google Authenticator)
├── VendorRegistry.php        Registry für produktive Bundles und Plattformprüfung
├── Version.php               Zentrale Release-Konstanten (Version, Datum, Status)
├── WP_Error.php              WordPress-kompatible Fehlerklasse
├── Contracts/
│   ├── CacheInterface.php    PSR-16-ähnlicher Cache-Contract
│   ├── DatabaseInterface.php Datenbank-Abstraktions-Contract
│   └── LoggerInterface.php   PSR-3-kompatibler Logger-Contract
├── Http/
│   └── Client.php            SSRF-gehärteter HTTP-Client für Remote-Pfade
├── Member/
│   └── PluginDashboardRegistry.php  Plugin-Bereiche im Member-Dashboard
├── Routing/
│   ├── AdminRouter.php       Teilrouter für Admin- und AJAX-Einstiege
│   ├── ApiRouter.php         API-/Upload-/Medien-Routen
│   ├── MemberRouter.php      Member-Dashboard- und Plugin-Routen
│   ├── PublicRouter.php      Public-Routen inkl. Archive, Kommentare und Sitemaps
│   └── ThemeRouter.php       Theme-spezifische Frontend-Dispatching-Hilfe
└── Services/
    ├── AnalyticsService.php       Besucherstatistiken
    ├── BackupService.php          Datenbank-/Datei-Backups
    ├── CommentService.php         Kommentar-Verwaltung
    ├── ContentLocalizationService.php Lokalisierte Basis-URIs und Sprachpfade
    ├── CoreWebVitalsService.php   Feldmessung für Web Vitals
    ├── CookieConsentService.php   Cookie-Consent-Banner
    ├── DashboardService.php       Dashboard-Statistiken
    ├── ErrorReportService.php     Persistente Fehlerreports mit Audit-Logging
    ├── EditorJsRenderer.php       Editor.js Block-Rendering
    ├── EditorJsService.php        Editor.js Integration
    ├── EditorService.php          Seiten-Editor Logik
    ├── FeatureUsageService.php    Datensparsame Nutzungsmetriken für Admin/Member
    ├── FeedService.php            RSS-/Atom-Feed-Generierung
    ├── FileUploadService.php      Datei-Upload-Verarbeitung
    ├── ImageService.php           Bildverarbeitung (Resize, WebP)
    ├── LandingPageService.php     Landing Pages (Sections)
    ├── MailService.php            E-Mail-Versand (SMTP/Symfony Mailer)
    ├── MediaDeliveryService.php   Kontrollierte Auslieferung privater Uploads
    ├── MediaService.php           Medienbibliothek & Upload
    ├── MemberService.php          Member-Dashboard-Logik
    ├── MessageService.php         Internes Nachrichten-System
    ├── OpcacheWarmupService.php   Warmup der größten PHP-Dateien
    ├── PdfService.php             PDF-Generierung (DomPDF)
    ├── PermalinkService.php       Beitrags-URL-Strukturen und Slug-Migration
    ├── PurifierService.php        HTML-Bereinigung (HTMLPurifier)
    ├── RedirectService.php        URL-Weiterleitungen
    ├── SearchService.php          Volltextsuche (TNTSearch)
    ├── SeoAnalysisService.php     SEO-Analyse & Scoring
    ├── SEOService.php             Sitemap, Robots.txt, Meta-Tags
    ├── SiteTableService.php       Tabellen-Verwaltung
    ├── StatusService.php          System-Health-Checks, Reparatur
    ├── SystemService.php          System-Infos, DB-Status
    ├── ThemeCustomizer.php        Theme-Einstellungen (Farben, Fonts)
    ├── TrackingService.php        Page-View-Tracking
    ├── TranslationService.php     Übersetzungssystem (i18n)
    ├── UpdateService.php          CMS-Update-Prüfung
    └── UserService.php            Benutzer-CRUD für Admin
```

Im Stand `3.4.00` dokumentiert [STRUCTURE.md](STRUCTURE.md) zusätzlich den aktuellen Release-Snapshot des Core-/Admin-Scopes inklusive neuer Service- und Admin-Einstiege. Für die aktuelle Gesamtstruktur der Runtime ergänzt [../FILELIST.md](../FILELIST.md) diesen Core-Blick um Assets, Member, Plugins, Themes und weitere Runtime-Zonen.

Die Referenz wurde am `2026-09-06` gegen `CMS/core/Version.php`,
`CMS/core/Bootstrap.php`, `CMS/core/Routing/ApiRouter.php` und den vollständigen
Service-Bestand geprüft. Verbindliche Codewerte bleiben Version `3.4.00`,
Release-Datum `2026-09-05` und PHP `8.4+`.

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
| [STRUCTURE.md](STRUCTURE.md)         | Release-Snapshot für `CMS/core`, `CMS/admin`, `CMS/config` |
| [CORE-CLASSES.md](CORE-CLASSES.md) | Detailreferenz aller 22 Core-Klassen  |
| [SERVICES.md](SERVICES.md)         | Alle 30 Service-Klassen dokumentiert  |
| [SECURITY.md](SECURITY.md)         | Sicherheitsmodell                     |
| [../ARCHITECTURE.md](../ARCHITECTURE.md) | Gesamt-Systemarchitektur      |
| [../DATABASE-SCHEMA.md](../DATABASE-SCHEMA.md)   | Alle DB-Tabellen          |
| [../HOOKS-REFERENCE.md](../HOOKS-REFERENCE.md)   | Action/Filter-Referenz    |
