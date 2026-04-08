# CMS Core – Übersicht
> **Stand:** 2026-04-07 | **Version:** 2.9.0 | **Status:** Aktuell

## Inhaltsverzeichnis
- [Verzeichnisstruktur](#verzeichnisstruktur)
- [Wichtige Muster](#wichtige-muster)
- [Dokumentation](#dokumentation)

<!-- UPDATED: 2026-04-07 -->

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
├── SchemaManager.php         CREATE TABLE – 30 Basis-Tabellen
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

Im Stand `2.9.0` dokumentiert [STRUCTURE.md](STRUCTURE.md) zusätzlich den aktuellen Release-Snapshot des Core-/Admin-Scopes inklusive neuer Service- und Admin-Einstiege. Für die aktuelle Gesamtstruktur der Runtime ergänzt [../FILELIST.md](../FILELIST.md) diesen Core-Blick um Assets, Member, Plugins, Themes und weitere Runtime-Zonen.

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
