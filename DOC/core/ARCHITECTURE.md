# 365CMS – Architektur
> **Stand:** 2026-03-08 | **Version:** 2.5.4 | **Status:** Aktuell

## Inhaltsverzeichnis
- [Überblick](#überblick)
- [Systemschichten](#systemschichten)
- [Request-Lifecycle](#request-lifecycle)
- [Dependency Injection / Service Container](#dependency-injection--service-container)
- [Autoloading (PSR-4, Composer)](#autoloading-psr-4-composer)
- [Konfigurationsmanagement](#konfigurationsmanagement)
- [Fehlerbehandlung und Logging](#fehlerbehandlung-und-logging)

---

## Überblick <!-- UPDATED: 2026-03-08 -->

365CMS ist ein modular aufgebautes Content-Management-System, das auf **PHP 8.3+** basiert und für den Betrieb auf Shared- und Managed-Hosting optimiert ist. Die Architektur trennt sechs Schichten klar voneinander: Core, Services, Plugins, Themes, Admin und Member/API.

**Zentrale Designprinzipien:**

| Prinzip | Umsetzung |
|---|---|
| Singleton-basierte Kernklassen | `Bootstrap`, `Container`, `Router`, `Database`, `Auth`, `Security`, `Logger` – alle über `::instance()` erreichbar |
| Dependency Injection | Zentraler `Container` mit Lazy-Singletons und transient Bindings |
| Modusabhängiger Bootstrap | `CMS_MODE` (`web`, `admin`, `api`, `cli`) steuert, welche Subsysteme initialisiert werden |
| Hook-System | `Hooks`-Klasse ermöglicht lose Kopplung zwischen Core, Plugins, Themes und Services |
| Kein Composer-Runtime | Vendor-Libraries liegen entpackt in `CMS/assets/`; eigener PSR-4-Autoloader via `spl_autoload_register()` |

```text
┌─────────────────────────────────────────────────────────────────────┐
│                        365CMS v2.5.4                                │
│                                                                     │
│  Presentation     → Themes (Frontend), Admin-Views, Member-Templates│
│  Application      → Router, Hooks, PluginManager, ThemeManager      │
│  Business Logic   → Auth, Security, PageManager, Modul-Logik        │
│  Services         → Mail, Search, Translation, SEO, PDF, Editor … │
│  Persistence      → Database, SchemaManager, MigrationManager       │
│  Configuration    → config/app.php (aktiv), config.php (Stub)       │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Systemschichten <!-- UPDATED: 2026-03-08 -->

Die Architektur ist in sechs logische Schichten gegliedert. Jede Schicht hat klar definierte Verantwortlichkeiten und kommuniziert nur über festgelegte Interfaces (Container, Hooks) mit benachbarten Schichten.

```text
┌──────────────────────────────────────────────────────────────┐
│  6 │ Admin / Member          Admin-Module, Member-Dashboard  │
├──────────────────────────────────────────────────────────────┤
│  5 │ Themes                  Template-Rendering, Assets      │
├──────────────────────────────────────────────────────────────┤
│  4 │ Plugins                 Hooks, eigene Routen, DB-Tabellen│
├──────────────────────────────────────────────────────────────┤
│  3 │ Services                Mail, Search, SEO, PDF, Editor …│
├──────────────────────────────────────────────────────────────┤
│  2 │ Core                    Bootstrap, Container, Router,   │
│    │                         Auth, Security, Database, Logger│
├──────────────────────────────────────────────────────────────┤
│  1 │ Configuration           config/app.php, Konstanten, DB  │
└──────────────────────────────────────────────────────────────┘
```

### Schicht 2 – Core

Kernklassen unter `CMS/core/`, Namespace `CMS\*`. Werden beim Bootstrap direkt geladen.

| Klasse | Datei | Aufgabe |
|---|---|---|
| `Bootstrap` | `core/Bootstrap.php` | Entry-Point, Modus-Erkennung, Container-Befüllung |
| `Container` | `core/Container.php` | DI-Container (`bind`, `singleton`, `make`, `get`, `has`) |
| `Router` | `core/Router.php` | URL-Routing mit `:param`-Pattern, CSRF-Middleware |
| `Database` | `core/Database.php` | PDO-Wrapper, Query-Builder |
| `Auth` | `core/Auth.php` | Login, Session, MFA (TOTP/WebAuthn), JWT |
| `Security` | `core/Security.php` | HTTP-Header, CSRF-Token, Session-Härtung |
| `Logger` | `core/Logger.php` | PSR-3-kompatibles Logging mit Tagesrotation |
| `AuditLogger` | `core/AuditLogger.php` | Sicherheitskritische Events (Login, Rollen, Firewall) |
| `Hooks` | `core/Hooks.php` | Event-System (`add_action`, `do_action`, `add_filter`) |
| `CacheManager` | `core/CacheManager.php` | Dateisystem-/Redis-Cache, Hook-basierte Invalidierung |
| `PageManager` | `core/PageManager.php` | CRUD für Seiten und Beiträge |
| `PluginManager` | `core/PluginManager.php` | Plugin-Lifecycle, Hook-/Routen-Registrierung |
| `ThemeManager` | `core/ThemeManager.php` | Theme-Laden, Template-Hierarchie (nur `web`/`admin`) |
| `SchemaManager` | `core/SchemaManager.php` | Initiales DB-Schema (`createTables()`) |
| `MigrationManager` | `core/MigrationManager.php` | Inkrementelle Migrationen, `SCHEMA_VERSION`-basiert |

### Schicht 3 – Services

Lazy-Singletons unter `CMS/core/Services/`, Namespace `CMS\Services\*`. Werden erst bei erstem Container-Zugriff instanziiert.

| Service | Container-Alias | Bibliothek |
|---|---|---|
| `MailService` | `mail` | Symfony Mailer / SMTP / MS Graph |
| `MailQueueService` | `mail.queue` | Cron-Worker mit Retry-Backoff |
| `MailLogService` | `mail.logs` | Versandhistorie |
| `AzureMailTokenProvider` | `mail.azure` | XOAUTH2 für Microsoft 365 SMTP |
| `GraphApiService` | `graph` | Microsoft Graph Client-Credentials |
| `SearchService` | `search` | TNTSearch Volltextsuche |
| `TranslationService` | `translation` | Symfony Translation (I18n) |
| `SEOService` | `seo` | Meta-Tags, Schema.org, Sitemap |
| `PdfService` | `pdf` | Dompdf-basierte PDF-Erzeugung |
| `EditorService` | `editor` | SunEditor WYSIWYG |
| `EditorJsService` | `editorjs` | Editor.js Block-Editor |
| `EditorJsRenderer` | `editorjs.renderer` | Editor.js → HTML-Rendering |
| `FileUploadService` | `fileupload` | FilePond-Upload |
| `ImageService` | `image` | GD-basierte Bildbearbeitung |
| `FeedService` | `feed` | SimplePie RSS/Atom |
| `CookieConsentService` | `cookieconsent` | Frontend Consent-Banner (DSGVO) |
| `CommentService` | `comments` | Kommentarsystem |
| `MemberService` | `member` | Mitgliederverwaltung |
| `MessageService` | `messages` | Internes Nachrichtensystem |
| `PurifierService` | `purifier` | HTMLPurifier (XSS-Schutz) |
| `SettingsService` | `settings` | Gruppierte/verschlüsselte DB-Settings |

### Schicht 4 – Plugins

Plugins liegen unter `CMS/plugins/<slug>/` und werden über `PluginManager->loadPlugins()` geladen. Jedes Plugin kann:

- Hooks registrieren (Actions und Filter)
- Eigene Routen über `Router::addRoute()` hinzufügen
- Eigene Assets (CSS/JS) über `head`/`body_end`-Hooks einbinden
- Optionale Datenbank-Tabellen anlegen
- Cron-Jobs über `cms_cron_*`-Hooks definieren

### Schicht 5 – Themes

Themes unter `CMS/themes/<name>/` steuern das Frontend-Rendering. `ThemeManager` wird **nur** im Modus `web` und `admin` geladen (nicht in `api`/`cli`). Hooks `head` und `body_end` ermöglichen Asset-Injection durch Plugins und Services.

### Schicht 6 – Admin / Member

- **Admin**: Entry-Points unter `CMS/admin/*.php`, Module unter `CMS/admin/modules/*`, Views unter `CMS/admin/views/*`, Partials unter `CMS/admin/partials/*`. Hooks `admin_head`/`admin_body_end` sowie generische `head`/`body_end`.
- **Member**: Geschützter Bereich unter `/member/*`, Dashboard, Plugin-Integration über `/member/plugin/:slug/:action`.
- **API**: Routen unter `/api/v1/*` ohne Theme-Ausgabe; Auth via JWT/API-Key/Session.
- **CLI/Cron**: `cron.php` triggert `cms_cron_*`-Hooks; CLI-Skripte nutzen Container ohne Theme (`CMS_MODE=cli`).

---

## Request-Lifecycle <!-- UPDATED: 2026-03-08 -->

Jeder HTTP-Request durchläuft eine festgelegte Pipeline von Entry-Point bis zur gerenderten Ausgabe.

```text
  HTTP Request
       │
       ▼
┌─────────────────┐
│  1. index.php   │  Lädt config.php → config/app.php
│     admin/*.php │  Definiert ABSPATH, DB-Konstanten
│     api/*.php   │
└────────┬────────┘
         ▼
┌─────────────────┐
│  2. Bootstrap   │  ensureConstants() → CMS_VERSION, SITE_URL, Pfade
│     ::instance()│  detectMode() → CMS_MODE (web|admin|api|cli)
└────────┬────────┘
         ▼
┌─────────────────┐
│  3. loadDepen-  │  require_once Core-Klassen (Container, Database, …)
│     dencies()   │  require_once CMS/assets/autoload.php (Vendor)
└────────┬────────┘
         ▼
┌─────────────────┐
│  4. initialize- │  Container::instance()
│     Core()      │  Database → Security → Auth → Logger → Cache
│                 │  Lazy-Singletons: Mail, Search, SEO, PDF, …
│                 │  MigrationManager->run() (idempotent)
└────────┬────────┘
         ▼
┌─────────────────┐
│  5. Plugins /   │  PluginManager->loadPlugins()  → Hooks, Routen
│     Themes      │  ThemeManager->loadTheme()     → nur web/admin
└────────┬────────┘
         ▼
┌─────────────────┐
│  6. Router      │  Redirect-Prüfung (RedirectService)
│     ::dispatch()│  CSRF-Middleware (außer API/Admin/Member)
│                 │  Route-Matching: exakt → :param-Pattern
│                 │  → Controller-Callback → View/JSON/Redirect
└────────┬────────┘
         ▼
┌─────────────────┐
│  7. Rendering   │  Hooks (head, body_end, admin_head, …)
│                 │  Theme-Template oder JSON-Response
│                 │  → HTTP Response an Client
└─────────────────┘
```

### Bootstrap-Initialisierung im Detail

```php
// CMS/core/Bootstrap.php – initializeCore() (vereinfacht)

// 1. Konstanten absichern
$this->ensureConstants();

// 2. Container und Modus
$this->container = Container::instance();
$this->mode = self::detectMode();
defined('CMS_MODE') || define('CMS_MODE', $this->mode);

// 3. Kern-Instanzen (immer geladen)
$this->db = Database::instance();
$this->container->bindInstance(Database::class, $this->db);

(new MigrationManager($this->db))->run();  // idempotent

$this->security = Security::instance();
$this->container->bindInstance(Security::class, $this->security);
if ($this->mode !== 'cli') {
    $this->security->init();  // HTTP-Header, CSRF, Session
}

$this->auth = Auth::instance();
$this->container->bindInstance(Auth::class, $this->auth);

// 4. Lazy-Services (erst bei Nutzung instanziiert)
$this->container->singleton('mail', fn() => Services\MailService::getInstance());
$this->container->singleton('search', fn() => Services\SearchService::getInstance());
// … weitere Services …
```

### Modus-Erkennung

`Bootstrap::detectMode()` bestimmt den Betriebsmodus anhand der Request-URI:

| Modus | Trigger | Besonderheiten |
|---|---|---|
| `cli` | `PHP_SAPI === 'cli'` | Kein Theme, keine Security-Header |
| `api` | URI beginnt mit `/api/` | Kein Theme, JSON-Responses |
| `admin` | URI beginnt mit `/admin/` oder exakt `/admin` | Admin-Layout, Admin-Hooks |
| `web` | Alle anderen Requests (Standard) | Volles Theme-Rendering |

### Routing-Mechanismus

Der `Router` verwaltet Routen als assoziatives Array `$routes[$method][$path] = $callback` und unterstützt `:param`-Platzhalter.

```php
// Routen-Registrierung (CMS/core/Router.php)
$this->addRoute('GET',  '/',          [$this, 'renderHome']);
$this->addRoute('GET',  '/login',     [$this, 'renderLogin']);
$this->addRoute('POST', '/login',     [$this, 'handleLogin']);
$this->addRoute('GET',  '/mfa-challenge', [$this, 'renderMfaChallenge']);
$this->addRoute('POST', '/mfa-challenge', [$this, 'handleMfaChallenge']);

// Dynamische Routen mit Parametern
$this->addRoute('GET',  '/member/plugin/:slug/:action/:id', $callback);
$this->addRoute('GET',  '/admin/:page',                     $callback);
$this->addRoute('GET',  '/api/v1/pages',                    $callback);
```

**CSRF-Middleware** (im `dispatch()`-Ablauf):
- Prüft `POST`/`PUT`/`DELETE`-Requests auf gültiges CSRF-Token
- Ausnahmen: `/api/`-Prefixed Routen, `/admin/`-Routen (eigene CSRF-Logik), `/contact/`
- Bei Verstoß: HTTP 403

---

## Dependency Injection / Service Container <!-- UPDATED: 2026-03-08 -->

Der `CMS\Container` ist der zentrale DI-Container des Systems. Er verwaltet Service-Instanzen und ermöglicht Lazy-Loading von Dependencies.

### Container-API

```php
// CMS/core/Container.php – Namespace CMS

class Container
{
    // Singleton-Zugang
    public static function instance(): self;

    // Registrierung
    public function bind(string $abstract, \Closure $factory): void;      // Transient
    public function singleton(string $abstract, \Closure $factory): void; // Lazy-Singleton
    public function bindInstance(string $abstract, mixed $resolved): void; // Sofort-Singleton

    // Auflösung
    public function make(string $abstract): mixed;  // Löst auf, wirft RuntimeException
    public function get(string $abstract): mixed;   // Alias für make()
    public function has(string $abstract): bool;    // Prüft Registrierung
}
```

### Interne Datenstrukturen

| Eigenschaft | Typ | Beschreibung |
|---|---|---|
| `$bindings` | `array<string, \Closure>` | Registrierte Factory-Closures |
| `$resolved` | `array<string, mixed>` | Gecachte Singleton-Instanzen |
| `$singletons` | `array<string, bool>` | Markierung, welche Bindings Singletons sind |

### Auflösungslogik

1. Ist `$abstract` bereits in `$resolved` → sofort zurückgeben (Singleton-Cache)
2. Ist `$abstract` in `$bindings` → Factory ausführen; bei Singleton → Ergebnis in `$resolved` cachen
3. Weder gebunden noch aufgelöst → `RuntimeException`

```php
// Verwendungsbeispiele
$container = Container::instance();

// Kern-Instanz (bereits via Bootstrap gebunden)
$db = $container->get(Database::class);
$db = $container->get('db');                  // Alias

// Lazy-Service (Factory wird erst hier ausgeführt)
$mailer = $container->make('mail');           // → MailService::getInstance()
$search = $container->make('search');         // → SearchService::getInstance()
```

### Registrierungsmuster im Bootstrap

Der Bootstrap unterscheidet zwei Registrierungsarten:

**Sofort-Instanzen** (`bindInstance`) – Kernklassen, die immer benötigt werden:

```php
$this->container->bindInstance(Database::class, $this->db);
$this->container->bindInstance(Security::class, $this->security);
$this->container->bindInstance(Auth::class,     $this->auth);
$this->container->bindInstance(Logger::class,   $logger);
$this->container->bindInstance(CacheManager::class, $cache);
```

**Lazy-Singletons** (`singleton`) – Services, die erst bei Bedarf geladen werden:

```php
$this->container->singleton(Services\MailService::class,
    fn() => Services\MailService::getInstance());
$this->container->singleton('mail',
    fn() => Services\MailService::getInstance());
```

Jeder Service wird unter seinem FQCN **und** einem Kurzalias registriert, sodass sowohl `$container->get(Services\MailService::class)` als auch `$container->get('mail')` funktionieren.

---

## Autoloading (PSR-4, Composer) <!-- UPDATED: 2026-03-08 -->

365CMS verwendet **keinen globalen Composer-Autoloader**. Stattdessen werden Vendor-Libraries entpackt unter `CMS/assets/` gepflegt und über einen eigenen Autoloader geladen.

### Autoloader-Hierarchie

```text
1. Core-Klassen       → require_once in Bootstrap::loadDependencies()
2. Vendor-Autoloader  → CMS/assets/autoload.php  (Produktion)
3. Dev-Fallback       → ASSETS/autoload.php       (nur lokale Entwicklung)
```

```php
// CMS/core/Bootstrap.php – loadDependencies() (Auszug)
$vendorAutoload = ABSPATH . 'assets/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
} else {
    $devAutoload = dirname(ABSPATH) . '/ASSETS/autoload.php';
    if (file_exists($devAutoload)) {
        require_once $devAutoload;
    }
}
```

### Namespace-Mapping in `CMS/assets/autoload.php`

Jede Library wird über einen dedizierten `spl_autoload_register()`-Aufruf mit PSR-4-Mapping registriert:

| Namespace | Verzeichnis unter `CMS/assets/` | Library |
|---|---|---|
| `Melbahja\Seo\` | `melbahja-seo/src/` | Sitemap, IndexNow, Schema.org |
| `SimplePie\` | `simplepiesrc/` | RSS/Atom-Parsing (PSR-4) |
| `SimplePie` (Legacy) | `simplepielibrary/` | RSS/Atom-Parsing (Legacy-Klasse) |
| `TeamTNT\TNTSearch\` | `tntsearchsrc/` | Volltextsuche |
| `Carbon\` | `Carbon/` | Datum/Zeit-Handling |
| `Symfony\Component\Translation\` | `translation/` | I18n-Framework |
| `Symfony\Component\Mime\` | `mime/` | MIME-Handling (E-Mail) |
| `Symfony\Component\Mailer\` | `mailer/` | E-Mail-Versand |
| `lbuchs\WebAuthn\` | `webauthn/` | Passkey / FIDO2 |
| `RobThree\Auth\` | `twofactorauth/` | TOTP-2FA |
| `LdapRecord\` | `ldaprecord/` | LDAP-Anbindung |
| `Firebase\JWT\` | `php-jwt/` | JSON Web Token |
| `Psr\Log\` | `psr/Log/` | PSR-3 Logger-Interface |
| `Psr\EventDispatcher\` | `psr/EventDispatcher/` | PSR-14 Event-Interface |
| – (HTMLPurifier) | `htmlpurifier/` | XSS-Sanitizer (eigener Autoloader) |
| `elFinder*` | `elfinder/php/` | Dateimanager (Klassen-basiert) |

### Autoloader-Pattern

Alle PSR-4-Autoloader in `autoload.php` folgen demselben Pattern:

```php
spl_autoload_register(function (string $class): void {
    $prefix  = 'Vendor\\Namespace\\';
    $baseDir = CMS_VENDOR_PATH . 'verzeichnis' . DIRECTORY_SEPARATOR;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;  // Nicht zuständig → nächster Autoloader
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
```

### Lazy-Loading-Strategie

Services werden im Container als Lazy-Singletons registriert. Die zugehörige Vendor-Library wird erst geladen, wenn der Service zum ersten Mal über `$container->make()` aufgelöst wird. Dadurch wird z. B. TNTSearch nur bei tatsächlicher Suche, Dompdf nur bei PDF-Erzeugung initialisiert.

---

## Konfigurationsmanagement <!-- UPDATED: 2026-03-08 -->

Die Konfiguration ist dreistufig aufgebaut: statische Datei, Bootstrap-Konstanten und Laufzeit-Settings aus der Datenbank.

### Konfigurationshierarchie

```text
Priorität   Quelle                    Beschreibung
─────────   ────────────────────────  ─────────────────────────────────
1 (höchste) config/app.php            Datenbank, Mail, JWT, LDAP, 2FA,
                                      WebAuthn, Cache, Pfade
2           config.php (Stub)         Abwärtskompatibilität; ruft
                                      config/app.php auf
3           Bootstrap::ensure-        Fallback-Defaults für fehlende
            Constants()               Konstanten
4           DB-Tabelle `settings`     Laufzeit-konfigurierbare Optionen
                                      über SettingsService
```

### Statische Konstanten (Bootstrap)

`Bootstrap::ensureConstants()` definiert Defaults, falls `config/app.php` Werte nicht gesetzt hat:

```php
// CMS/core/Bootstrap.php – ensureConstants()
defined('CMS_VERSION')   || define('CMS_VERSION',   '2.5.4');
defined('SITE_NAME')     || define('SITE_NAME',     'CMS');
defined('SITE_URL')      || define('SITE_URL',      '');
defined('ADMIN_EMAIL')   || define('ADMIN_EMAIL',   '');
defined('CORE_PATH')     || define('CORE_PATH',     ABSPATH . 'core/');
defined('THEME_PATH')    || define('THEME_PATH',    ABSPATH . 'themes/');
defined('PLUGIN_PATH')   || define('PLUGIN_PATH',   ABSPATH . 'plugins/');
defined('UPLOAD_PATH')   || define('UPLOAD_PATH',   ABSPATH . 'uploads/');
defined('ASSETS_PATH')   || define('ASSETS_PATH',   ABSPATH . 'assets/');
```

### Erwartete Schlüssel in `config/app.php`

| Konstante | Zweck | Beispielwert |
|---|---|---|
| `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` | MySQL/MariaDB-Verbindung | `localhost`, `cms_db` |
| `SITE_URL` | Basis-URL der Installation | `https://example.com` |
| `CMS_VERSION` | Aktuelle Version | `2.5.4` |
| `LOG_PATH` | Verzeichnis für Log-Dateien | `ABSPATH . 'logs/'` |
| `LOG_LEVEL` | Minimaler Log-Level | `WARNING` (oder `DEBUG` bei `CMS_DEBUG=true`) |
| `CMS_DEBUG` | Debug-Modus | `false` |
| `JWT_SECRET` | Schlüssel für API-Token | – |
| `LDAP_*` | LDAP-Verbindungsparameter | – |
| `MAIL_*` | SMTP-/Mailer-Konfiguration | – |

### Laufzeit-Settings (SettingsService)

Der `SettingsService` (Container-Alias `settings`) kapselt die DB-Tabelle `settings` und bietet:

- Gruppierte Einstellungen (z. B. `mail`, `seo`, `security`)
- Verschlüsselte Werte für sensible Daten
- Caching der gelesenen Werte innerhalb eines Requests

```php
$settings = Container::instance()->get('settings');
$siteName = $settings->get('general', 'site_name', 'Mein CMS');
```

---

## Fehlerbehandlung und Logging <!-- UPDATED: 2026-03-08 -->

365CMS verwendet ein zweistufiges Logging-System: den allgemeinen `Logger` für Anwendungs-Events und den `AuditLogger` für sicherheitsrelevante Aktionen.

### Logger (`CMS\Logger`)

Der Logger implementiert das **PSR-3 `LoggerInterface`** und schreibt rotierende Tageslogs.

**Log-Levels** (gewichtet, PSR-3-konform):

| Konstante | Gewicht | Verwendung |
|---|---|---|
| `EMERGENCY` | 7 | System unbenutzbar |
| `ALERT` | 6 | Sofortige Maßnahme erforderlich |
| `CRITICAL` | 5 | Kritische Fehler (DB-Ausfall, …) |
| `ERROR` | 4 | Laufzeitfehler |
| `WARNING` | 3 | Warnungen (Standard-Minimum-Level) |
| `NOTICE` | 2 | Normale, aber bemerkenswerte Events |
| `INFO` | 1 | Informationsmeldungen |
| `DEBUG` | 0 | Detail-Informationen (nur bei `CMS_DEBUG`) |

**Datei-Muster:** `{LOG_PATH}{channel}-YYYY-MM-DD.log`
- Standard-Channel: `cms` → `logs/cms-2026-03-08.log`
- Benutzerdefinierter Channel: `audit` → `logs/audit-2026-03-08.log`

**Tagesrotation:** Die Rotation ergibt sich automatisch durch das Datumsformat im Dateinamen. Jeder Tag erzeugt eine neue Datei. Schreibzugriff erfolgt mit `FILE_APPEND | LOCK_EX` für Prozesssicherheit.

**Channel-Support:**

```php
// Neuen Logger-Klon mit anderem Channel erzeugen
$auditLog = Logger::instance()->withChannel('audit');
$auditLog->warning('Permission denied', ['user' => $userId]);

// withChannel() klont die Instanz – der Original-Logger bleibt unverändert
public function withChannel(string $channel): self
{
    $clone          = clone $this;
    $clone->channel = $channel;
    return $clone;
}
```

**Globale Hilfsfunktion:**

```php
// includes/functions.php
cms_log('info', 'Page rendered', ['path' => $_SERVER['REQUEST_URI'] ?? '/']);
```

### AuditLogger (`CMS\AuditLogger`)

Der `AuditLogger` erfasst sicherheitsrelevante Ereignisse in strukturierter Form und speichert sie in der Datenbank.

**Event-Kategorien:**

| Konstante | Kategorie | Beispiele |
|---|---|---|
| `CAT_AUTH` | Authentifizierung | Login, Logout, Passwort-Reset |
| `CAT_USER` | Benutzerverwaltung | Erstellung, Rollenänderung, Löschung |
| `CAT_SETTING` | Einstellungen | Admin-Konfigurationsänderungen |
| `CAT_PLUGIN` | Plugins | Aktivierung, Deaktivierung, Installation |
| `CAT_THEME` | Themes | Aktivierung, Löschung, Code-Bearbeitung |
| `CAT_MEDIA` | Medien | Upload, Löschung |
| `CAT_SYSTEM` | System | Backups, Updates, Cache-Flush |
| `CAT_SECURITY` | Sicherheit | CSP-Verstöße, Firewall, IP-Blocking |

```php
// Audit-Event loggen
AuditLogger::instance()->log(
    category:    AuditLogger::CAT_AUTH,
    action:      'login_success',
    description: 'User logged in via MFA',
    entityType:  'user',
    entityId:    $userId,
    metadata:    ['ip' => $_SERVER['REMOTE_ADDR'], 'method' => 'totp']
);
```

### Error Handling

- **Security-Klasse** setzt HTTP-Security-Header (CSP, HSTS, X-Frame-Options), CSRF-Token-Validierung und Session-Härtung – **nicht** im `cli`-Modus
- **Exceptions** werden in Admin-Views abgefangen und über den Logger protokolliert
- **Kritische Fehler** werden zusätzlich im AuditLog (Kategorie `CAT_SYSTEM`) festgehalten
- **SystemService** im Admin bietet einen Log-Viewer mit Rotationsverwaltung

### Log-Verzeichnis

```bash
CMS/logs/
├── cms-2026-03-08.log          # Allgemeines Anwendungslog
├── cms-2026-03-07.log          # Vorheriger Tag
├── audit-2026-03-08.log        # Security-Audit-Channel
└── ...                         # Automatische Tagesrotation
```
