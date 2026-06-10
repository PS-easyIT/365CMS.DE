# CMS Core – Übersicht
> **Stand:** 2026-06-10 | **Version:** 3.3.47 | **Status:** Aktuell

## Inhaltsverzeichnis
- [Verzeichnisstruktur](#verzeichnisstruktur)
- [Wichtige Muster](#wichtige-muster)
- [Dokumentation](#dokumentation)

<!-- UPDATED: 2026-06-10 -->

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
├── Auth/
│   ├── AuthManager.php              Orchestriert Login-Strategien (Passwort, LDAP, MFA, Passkey)
│   ├── LDAP/
│   │   └── LdapAuthProvider.php     LDAP / Active Directory (LdapRecord)
│   ├── MFA/
│   │   ├── BackupCodesManager.php   Einmal-Backup-Codes für 2FA
│   │   └── TotpAdapter.php          TOTP-Adapter (RFC 6238)
│   └── Passkey/
│       └── WebAuthnAdapter.php      Passkeys / WebAuthn (passwortlose Anmeldung)
├── Contracts/
│   ├── CacheInterface.php    PSR-16-ähnlicher Cache-Contract
│   ├── DatabaseInterface.php Datenbank-Abstraktions-Contract
│   └── LoggerInterface.php   PSR-3-kompatibler Logger-Contract
├── Http/
│   └── Client.php            SSRF-gehärteter HTTP-Client für Remote-Pfade
├── Member/
│   └── PluginDashboardRegistry.php  Plugin-Bereiche im Member-Dashboard
├── Routing/
│   ├── AdminRouter.php           Teilrouter für Admin- und AJAX-Einstiege
│   ├── ApiRouter.php             API-/Upload-/Medien-Routen
│   ├── MemberRouter.php          Member-Dashboard- und Plugin-Routen
│   ├── PublicRouter.php          Public-Routen inkl. Archive, Kommentare und Sitemaps
│   ├── ThemeArchiveRepository.php Archiv-/Listen-Abfragen für Theme-Frontend
│   └── ThemeRouter.php           Theme-spezifische Frontend-Dispatching-Hilfe
└── Services/                     (~60 Service-Klassen; Auszug nach Themen)
    │  # Inhalte & Editor
    ├── EditorJsRenderer.php · EditorJsService.php · EditorService.php · SiteTableService.php
    ├── PermalinkService.php · ContentLocalizationService.php · ContentMediaPlacementService.php
    │  # Medien
    ├── MediaService.php · MediaDeliveryService.php · MediaUsageService.php · ImageService.php
    ├── FileUploadService.php · AssetOptimizerService.php · OpcacheWarmupService.php
    │  # SEO & Feeds
    ├── SEOService.php · SeoAnalysisService.php · SeoBrokenLinkService.php · SeoTrendService.php
    ├── SitemapService.php · IndexingService.php · FeedService.php · PermalinkService.php
    │  # Mail & Azure/Graph
    ├── MailService.php · MailQueueService.php · MailLogService.php
    ├── AzureMailTokenProvider.php · GraphApiService.php · JwtService.php
    │  # Sicherheit & Recht
    ├── SecurityRuntimeService.php · SecurityAlertService.php · AntispamService.php
    ├── CookieConsentService.php · PurifierService.php
    │  # System, Monitoring & Updates
    ├── StatusService.php · SystemService.php · MonitoringTrendService.php
    ├── PerformanceSafetyNetService.php · CoreWebVitalsService.php · BackupService.php
    ├── UpdateService.php · SettingsService.php · CoreModuleService.php · ErrorReportService.php
    ├── CronRunnerService.php · CronExpressionAdapter.php
    │  # Mitglieder, Nutzer & Nachrichten
    ├── MemberService.php · UserService.php · MessageService.php · CommentService.php
    │  # Dashboard, Analytics & Tracking
    ├── DashboardService.php · AnalyticsService.php · TrackingService.php · FeatureUsageService.php
    │  # Design, Suche, Auth-Seiten, i18n, PDF, Redirects, Landing
    ├── ThemeCustomizer.php · LandingPageService.php · SearchService.php · CmsAuthPageService.php
    ├── TranslationService.php · PdfService.php · RedirectService.php
    │  # Unterordner mit weiterer Fachlogik
    └── AI/  ·  EditorJs/  ·  Landing/  ·  Media/  ·  SEO/  ·  SiteTable/
```

> Die Service-Schicht ist seit den 2.9-Ständen deutlich gewachsen (u. a. Mail-Queue/-Log,
> Azure-/Graph-Anbindung, JWT, Cron-Runner, Security-Runtime, Sitemap-/Broken-Link-/Trend-Dienste).
> Maßgeblich ist immer der reale Verzeichnisstand unter `CMS/core/Services/`.

[STRUCTURE.md](STRUCTURE.md) dokumentiert zusätzlich den Release-Snapshot des Core-/Admin-Scopes inklusive Service- und Admin-Einstiege. Für die aktuelle Gesamtstruktur der Runtime ergänzt [../FILELIST.md](../FILELIST.md) diesen Core-Blick um Assets, Member, Plugins, Themes und weitere Runtime-Zonen.

> **Sicherheits-Hinweis:** Die Kernschicht wurde zuletzt am 2026-06-10 auditiert — siehe [../AUDIT_core_2026-06-10.md](../AUDIT_core_2026-06-10.md). Ergebnis: 0 kritische Funde, Defense-in-Depth-Härtungen in `MailService` und `Bootstrap` (Fehler-Handling) übernommen.

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
| [CORE-CLASSES.md](CORE-CLASSES.md) | Detailreferenz der Core-Klassen (24 Top-Level + Auth/MFA/LDAP/Passkey) |
| [SERVICES.md](SERVICES.md)         | Service-Schicht (~60 Klassen inkl. AI/EditorJs/Landing/Media/SEO/SiteTable) |
| [SECURITY.md](SECURITY.md)         | Sicherheitsmodell                     |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Gesamt-Systemarchitektur      |
| [DATABASE-SCHEMA.md](DATABASE-SCHEMA.md)   | Alle DB-Tabellen          |
| [HOOKS-REFERENCE.md](HOOKS-REFERENCE.md)   | Action/Filter-Referenz    |
| [API-REFERENCE.md](API-REFERENCE.md) | REST-API v1 Referenz |
| [STATUS.md](STATUS.md) | Implementierungs- und Betriebsstatus |
