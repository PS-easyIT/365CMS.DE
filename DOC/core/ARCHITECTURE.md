# 365CMS – Systemarchitektur
> **Stand:** 2026-03-08 | **Version:** 2.5.4 | **Status:** Aktuell

## Inhaltsverzeichnis
- <a>Überblick</a>
- <a>Systemschichten</a>
- <a>Request-Lifecycle</a>
- <a>Dependency Injection & Container</a>
- <a>Autoloading</a>
- <a>Konfigurationsmanagement</a>
- <a>Fehlerbehandlung und Logging</a>
- <a>Datenhaltung und Migration</a>
- <a>Admin- und Frontend-Ebene</a>
- <a>Bekannte Architektur-Änderungen</a>

---

## Überblick <!-- UPDATED: 2026-03-08 -->
365CMS ist ein modular aufgebautes PHP-8.3+-CMS mit klar getrennten Ebenen für Routing, Services, Admin-Module, Themes und Plugins. Der Bootstrap steuert den Betriebsmodus (web, admin, api, cli), initialisiert den Service-Container und lädt Plugins/Themes abhängig vom Kontext.

```text
Presentation      → Themes (Frontend), Admin-Views, Public/Member Templates
Application       → Router, Hooks, PluginManager, ThemeManager
Business Logic    → Auth, Security, PageManager, Module-spezifische Logik
Services          → Mail, Search, Translation, Backup, Update, SEO, etc.
Persistence       → Database, SchemaManager, MigrationManager
Configuration     → config/app.php (aktiv), config.php (Stub)
```

---

## Systemschichten <!-- UPDATED: 2026-03-08 -->
- **Core**: Bootstrap, Container, Router, Auth, Security, Hooks, Cache, PageManager.
- **Services**: MailService, SearchService (TNTSearch), TranslationService, SEOService, BackupService, UpdateService, CookieConsentService, Editor/EditorJs Services u. a.
- **Plugins**: Erweiterungen mit Hooks, eigenen Routen, Assets und optionalen DB-Tabellen.
- **Themes**: Rendering-Schicht für Frontend, Template-Hierarchie und Assets; ThemeManager lädt je nach CMS_MODE nicht im API/CLI.
- **Admin**: Eigenständige Module unter `CMS/admin/modules/*` mit Views/Partials; nutzt Hooks `admin_head`, `admin_body_end` sowie generische `head/body_end`.
- **API/CLI**: API-Routen unter `/api/*`, CLI-Tasks erkennen `PHP_SAPI === 'cli'` und nutzen Container-Services ohne Theme.

---

## Request-Lifecycle <!-- UPDATED: 2026-03-08 -->
1. **Entry-Point** (`index.php`, `admin/*.php`, `api/*.php`, `cron.php`, CLI-Skripte) lädt `config.php` → `config/app.php`.
2. **Bootstrap** (`CMS/core/Bootstrap.php`) stellt Konstanten sicher (z. B. `CMS_VERSION 2.5.4`, `ASSETS_PATH`, `THEME_PATH`) und ermittelt `CMS_MODE` (`web|admin|api|cli`).
3. **Autoloading**: `CMS/assets/autoload.php` (Produktiv) oder Fallback `ASSETS/autoload.php` lädt externe Libraries.
4. **Container-Befüllung**: Database, Security (Header/CSRF), Auth, Logger, Cache; anschließend Service-Singletons (Mail, Search, Translation, SEO, Upload, PDF, CookieConsent, etc.).
5. **Migrationen**: `MigrationManager` wird pro Request aufgerufen (idempotent, prüft `SCHEMA_VERSION`).
6. **Plugins/Themes**: `PluginManager->loadPlugins()` lädt Hooks/Routen; `ThemeManager->loadTheme()` nur für `web/admin`.
7. **Routing**: `Router::instance()` löst Frontend-, Admin- oder API-Routen; gibt View/JSON/Redirect aus.
8. **Hooks/Rendering**: Hooks injizieren Assets (z. B. PhotoSwipe/CookieConsent über `head`/`body_end`), Templates rendern Ausgabe; Responses werden gesendet.

```php
// stark verkürzt
$bootstrap = \CMS\Bootstrap::instance();
$container = \CMS\Container::instance();
$router    = $container->get(\CMS\Router::class);
echo $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
```

---

## Dependency Injection & Container <!-- UPDATED: 2026-03-08 -->
- **Container**: `CMS/core/Container.php` als Singleton, unterstützt `bindInstance` und `singleton` mit Lazy-Factories.
- **Registrierung**: Bootstrap bindet Kerninstanzen (Database, Security, Auth, Router, PluginManager, ThemeManager) sowie zahlreiche Services (Mail, Search, Translation, SEO, FileUpload, Editor, EditorJsRenderer, Pdf, Backup, Update, Customizer, CookieConsent).
- **Modusabhängig**: ThemeManager wird in `api/cli` nicht geladen; Security-Header werden im CLI-Modus nicht gesetzt.
- **Hooks/Services**: Services werden meist über den Container oder statische `getInstance()`-Methoden genutzt, Hooks verknüpfen Service-Aufrufe mit Lifecycle-Ereignissen.

| Namespace/Pfad | Typ | Zweck |
|---|---|---|
| `CMS\Container` (`CMS/core/Container.php`) | DI-Container | Verwaltet Singleton/Instances |
| `CMS\Router` (`CMS/core/Router.php`) | Routing | Frontend/Admin/API-Routing, Hook-Auslösung |
| `CMS\PluginManager` (`CMS/core/PluginManager.php`) | Plugin-Lader | Registriert Hooks, Assets, Cron |
| `CMS\ThemeManager` (`CMS/core/ThemeManager.php`) | Theme-Lader | Lädt aktives Theme, Templates, Assets |

---

## Autoloading <!-- UPDATED: 2026-03-08 -->
- **PSR-4 Core**: Klassen unter `CMS/core` folgen dem Namespace `CMS\*`; Loader in `bootstrap.php` und `Container`.
- **Vendor Libraries**: Primärer Autoloader `CMS/assets/autoload.php`; Fallback `ASSETS/autoload.php` (dev-only). Enthält HTMLPurifier, SimplePie, TNTSearch, elFinder, Carbon, Symfony Translation, php-jwt, WebAuthn u. a.
- **Composer**: Repository nutzt keinen globalen Composer-Autoloader; Bibliotheken werden entpackt unter `CMS/assets/` gepflegt.
- **Lazy Loading**: Services registrieren sich lazy im Container, wodurch Bibliotheken erst bei Nutzung geladen werden.

---

## Konfigurationsmanagement <!-- UPDATED: 2026-03-08 -->
- **Aktive Datei**: `CMS/config/app.php` (Datenbank, Mail, JWT, LDAP, 2FA, WebAuthn, Cache, Pfade).
- **Stub**: `CMS/config.php` bleibt für Abwärtskompatibilität und ruft `app.php` auf.
- **Konstanten-Fallback**: `Bootstrap::ensureConstants()` definiert Defaults (`CMS_VERSION`, `SITE_URL`, `ASSETS_PATH`, etc.), falls in `app.php` nicht gesetzt.
- **Laufzeit-Settings**: `SettingsService` und DB-Tabelle `settings` kapseln konfigurierbare Optionen (verschlüsselte Gruppen möglich).

---

## Fehlerbehandlung und Logging <!-- UPDATED: 2026-03-08 -->
- **Logger**: `CMS\Logger` (PSR-3 kompatibel) schreibt rotierende Tageslogs nach `CMS/logs/cms-YYYY-MM-DD.log`; Channel-Unterstützung via `withChannel()`. AuditLogger erfasst sicherheitskritische Events.
- **Error Handling**: Security setzt Security-Header, CSRF-Token und Session-Härtung; Exceptions werden in Admin-Views abgefangen und geloggt.
- **Tracing**: SystemService bietet Log-Viewer/Rotierer im Admin; kritische Fehler werden zusätzlich im Audit-Log festgehalten.

```php
cms_log('info', 'Page rendered', ['path' => $_SERVER['REQUEST_URI'] ?? '/']);
Logger::instance()->withChannel('audit')->warning('Permission denied', ['user' => $userId]);
```

---

## Datenhaltung und Migration <!-- UPDATED: 2026-03-08 -->
- **Basisschema**: `SchemaManager::createTables()` legt Kern-Tabellen an (`users`, `roles`, `pages`, `posts`, `menus`, `orders`, `subscriptions`, `seo_meta`, `redirect_rules`, `cookie_categories`, `privacy_requests`, `firewall_rules`, `spam_blacklist`, `role_permissions`, `menu_items`, u. a.).
- **Migrationen**: `MigrationManager` prüft `SCHEMA_VERSION` (v8) und führt inkrementelle Anpassungen aus; idempotent pro Request.
- **Installer**: `CMS/install.php` hält eigene `createDatabaseTables()`-Liste (muss mit SchemaManager synchron bleiben).
- **Service-spezifische Tabellen**: SEOService, RedirectService, CookieConsentService, Firewall/Antispam-Module, MenusModule, RolesModule ergänzen Spezialtabellen bei Bedarf.
- **Caching**: CacheManager unterstützt Treiber (Dateisystem, ggf. Redis/Memcached – abhängig von Config); Cache-Invalidierung über Services und Hooks.

---

## Admin- und Frontend-Ebene <!-- UPDATED: 2026-03-08 -->
- **Admin**: Entry-Points unter `CMS/admin/*.php`, Module unter `CMS/admin/modules/*`, Views unter `CMS/admin/views/*`, Partials unter `CMS/admin/partials/*`. Hooks `admin_head`/`admin_body_end` plus generische `head/body_end` erlauben Theme/Consent-Einbindung in Admin-Layouts.
- **Frontend**: Theme-Templates unter `CMS/themes/<theme>/`; Hooks `head`/`body_end` für Assets (z. B. PhotoSwipe, CookieConsent). Router liefert Seiten/Beiträge, Landing-Pages, Member-Bereiche.
- **API**: `/api/*` Routen ohne Theme-Ausgabe, nutzen Router + Services; Auth via JWT/API-Key/Session.
- **CLI/Cron**: `cron.php` triggert `cms_cron_*` Hooks; CLI-Skripte nutzen Container ohne Theme.

---

## Bekannte Architektur-Änderungen <!-- UPDATED: 2026-03-08 -->
- Konfiguration liegt in `config/app.php`, `config.php` nur Stub.
- Admin-Bereiche sind modularisiert (Diagnose, Legal, SEO, Performance etc. als einzelne Module).
- Vendor-Libraries werden aus `CMS/assets/` geladen; `ASSETS/` ist nur Staging.
- Hooks `head`/`body_end` werden auch im Admin gerendert, um eingebettete Seiten/Editoren mit Frontend-Assets zu versorgen.
- Schema- und Installer-Tabellen müssen synchron gehalten werden (SchemaManager vs. install.php).
