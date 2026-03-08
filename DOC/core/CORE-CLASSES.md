# 365CMS – Core-Klassen-Referenz
<!-- UPDATED: 2026-03-08 -->

> **Stand:** 2026-03-08 | **Version:** 2.5.4 | **Status:** Aktuell

Dieses Dokument beschreibt die zentralen PHP-Klassen des 365CMS-Kerns. Alle Klassen
befinden sich im Namespace `CMS\` (Kern) bzw. `CMS\Services\` (Service-Schicht) und
liegen unter `CMS/core/`.

---

## Inhaltsverzeichnis

| Nr. | Gruppe | Klassen |
|-----|--------|---------|
| 1 | [Bootstrap & Container](#1-bootstrap--container) | `Bootstrap`, `Container` |
| 2 | [Router](#2-router) | `Router` |
| 3 | [Database](#3-database) | `Database`, `SchemaManager`, `MigrationManager` |
| 4 | [Auth & Session](#4-auth--session) | `Auth`, `AuthManager`, `Totp` |
| 5 | [Security](#5-security) | `Security` |
| 6 | [Cache](#6-cache) | `CacheManager` |
| 7 | [Hooks](#7-hooks) | `Hooks` |
| 8 | [Logger & Audit](#8-logger--audit) | `Logger`, `AuditLogger`, `Debug` |
| 9 | [Seiten & Inhalte](#9-seiten--inhalte) | `PageManager`, `EditorJsRenderer` |
| 10 | [Theme & Template](#10-theme--template) | `ThemeManager`, `ThemeCustomizer` |
| 11 | [Plugin-System](#11-plugin-system) | `PluginManager` |
| 12 | [API](#12-api) | `Api` |
| 13 | [Mail](#13-mail) | `MailService` |
| 14 | [Suche](#14-suche) | `SearchService` |
| 15 | [Übersetzung](#15-übersetzung) | `TranslationService` |
| 16 | [Upload & Medien](#16-upload--medien) | `FileUploadService`, `MediaService`, `ImageService` |
| 17 | [Settings](#17-settings) | `SettingsService` |
| 18 | [Backup](#18-backup) | `BackupService` |
| 19 | [Contracts (Interfaces)](#19-contracts-interfaces) | `DatabaseInterface`, `CacheInterface`, `LoggerInterface` |

---

## 1. Bootstrap & Container
<!-- UPDATED: 2026-03-08 -->

### 1.1 Bootstrap

| | |
|---|---|
| **Namespace** | `CMS\Bootstrap` |
| **Pfad** | `CMS/core/Bootstrap.php` |
| **Pattern** | Singleton |

Zentraler Einstiegspunkt des CMS. Initialisiert alle Kernkomponenten (Security,
Database, Auth, Router, Plugins, Themes), erkennt den Betriebsmodus und startet
den Request-Lebenszyklus.

**Betriebsmodi** (automatisch erkannt):

| Modus | Trigger |
|-------|---------|
| `cli` | `PHP_SAPI === 'cli'` |
| `api` | Request-URI beginnt mit `/api/` |
| `admin` | Request-URI beginnt mit `/admin/` |
| `web` | Alle anderen Anfragen (Standard) |

**Wichtige Methoden:**

```php
public static function instance(): self       // Singleton-Zugriff
public function run(): void                    // Startet den kompletten Request-Zyklus
public function db(): Database                 // Gibt die Database-Instanz zurück
public function auth(): Auth                   // Gibt die Auth-Instanz zurück
public function security(): Security           // Gibt die Security-Instanz zurück
public function container(): Container         // Gibt den DI-Container zurück
```

**Beispiel:**

```php
// CMS starten (index.php)
$app = \CMS\Bootstrap::instance();
$app->run();

// Aus einem Plugin auf Kernkomponenten zugreifen
$db = \CMS\Bootstrap::instance()->db();
```

---

### 1.2 Container

| | |
|---|---|
| **Namespace** | `CMS\Container` |
| **Pfad** | `CMS/core/Container.php` |
| **Pattern** | Singleton, Dependency-Injection-Container |

Einfacher DI-Container mit Lazy-Auflösung, Singleton-Registrierung und
transientem Binding. Ermöglicht schrittweise Migration der bestehenden
Singleton-Klassen.

**Wichtige Methoden:**

```php
public static function instance(): self
public function bind(string $abstract, \Closure $factory): void       // Transiente Factory
public function singleton(string $abstract, \Closure $factory): void  // Singleton-Factory
public function bindInstance(string $abstract, mixed $resolved): void // Bereits existierende Instanz
public function make(string $abstract): mixed                         // Service auflösen
public function get(string $abstract): mixed                          // Alias für make()
public function has(string $abstract): bool                           // Prüft ob Service registriert
public function registered(): array                                   // Alle registrierten Keys
public function forget(string $abstract): void                        // Service entfernen
public function flush(): void                                         // Alle Services entfernen
```

**Beispiel:**

```php
$container = \CMS\Container::instance();

// Singleton registrieren
$container->singleton('db', fn() => \CMS\Database::instance());

// Service auflösen (gibt stets dieselbe Instanz zurück)
$db = $container->make('db');

// Vorhandene Instanz einbinden
$container->bindInstance('logger', $myLogger);
```

---

## 2. Router
<!-- UPDATED: 2026-03-08 -->

| | |
|---|---|
| **Namespace** | `CMS\Router` |
| **Pfad** | `CMS/core/Router.php` |
| **Pattern** | Singleton |

Zentrales URL-Routing- und Request-Dispatching-System. Registriert automatisch
Standard-Routen (Login, Register, Logout, Admin, Sitemap usw.) und unterstützt
benutzerdefinierte Routen über `addRoute()`.

**Wichtige Methoden:**

```php
public static function instance(): self
public function addRoute(string $method, string $path, callable $callback): void
public function dispatch(): void
public function redirect(string $url): void
public function serveSitemap(): void
```

**Vordefinierte Routen (Auszug):**

| Methode | Pfad | Handler |
|---------|------|---------|
| GET | `/` | `renderHome()` |
| GET/POST | `/login` | `renderLogin()` / `handleLogin()` |
| GET/POST | `/register` | `renderRegister()` / `handleRegister()` |
| GET | `/logout` | `handleLogout()` |
| GET | `/admin/*` | `renderAdmin()` / `renderAdminPage()` |
| GET | `/member/*` | `renderMember()` / `renderMemberPage()` |

**Beispiel:**

```php
$router = \CMS\Router::instance();

// Eigene Route registrieren
$router->addRoute('GET', '/kontakt', function () {
    // Kontaktseite rendern
});

// Request verarbeiten
$router->dispatch();
```

---

## 3. Database
<!-- UPDATED: 2026-03-08 -->

### 3.1 Database

| | |
|---|---|
| **Namespace** | `CMS\Database` |
| **Pfad** | `CMS/core/Database.php` |
| **Pattern** | Singleton |
| **Implements** | `CMS\Contracts\DatabaseInterface` |

PDO-basierter Datenbank-Wrapper mit Prepared Statements und WordPress-kompatibler API.
Stellt sowohl OOP- als auch statische Convenience-Methoden bereit. Beim ersten
Instanziieren wird automatisch `SchemaManager::createTables()` aufgerufen.

**Wichtige Methoden:**

```php
// Singleton
public static function instance(): self
public static function get_instance(): self  // WordPress-kompatibler Alias

// Query-Ausführung
public function prepare(string $sql): \PDOStatement|false
public function query(string $sql): \PDOStatement
public function execute(string $sql, array $params = []): \PDOStatement

// CRUD
public function insert(string $table, array $data): int|bool
public function update(string $table, array $data, array $where): bool
public function delete(string $table, array $where): bool

// Daten abrufen
public function get_row(string $query, array $params = []): ?object
public function get_var(string $query, array $params = []): mixed
public function get_results(string $query, array $params = []): array
public function get_col(string $query, array $params = []): array

// Hilfsmethoden
public function affected_rows(): int
public function insert_id(): int
public function prefix(string $table = ''): string
public function getPrefix(): string
public function getPdo(): \PDO
public function getConnection(): \PDO
public function repairTables(): void

// Statische Convenience-Methoden
public static function fetchAll(string $query, array $params = []): array
public static function fetchOne(string $query, array $params = []): ?array
public static function exec(string $query, array $params = []): bool
```

**Beispiel:**

```php
$db = \CMS\Database::instance();

// Einfügen
$db->insert('pages', [
    'title'   => 'Neue Seite',
    'slug'    => 'neue-seite',
    'content' => '<p>Inhalt</p>',
    'status'  => 'published',
]);

$newId = $db->insert_id();

// Abfrage mit Prepared Statement
$page = $db->get_row(
    "SELECT * FROM {$db->getPrefix()}pages WHERE id = ?",
    [$newId]
);

// Statische Nutzung
$all = \CMS\Database::fetchAll(
    "SELECT * FROM cms_pages WHERE status = ?",
    ['published']
);
```

### 3.2 SchemaManager

| | |
|---|---|
| **Namespace** | `CMS\SchemaManager` |
| **Pfad** | `CMS/core/SchemaManager.php` |

Erstellt und aktualisiert die Datenbanktabellen des CMS. Wird automatisch von
`Database::__construct()` aufgerufen.

```php
public function __construct(Database $db)
public function createTables(): void        // Erstellt alle CMS-Tabellen
public function getFlagFile(): string       // Pfad zur Schema-Flag-Datei
public function clearFlag(): void           // Flag zurücksetzen (erzwingt erneutes Prüfen)
```

### 3.3 MigrationManager

| | |
|---|---|
| **Namespace** | `CMS\MigrationManager` |
| **Pfad** | `CMS/core/MigrationManager.php` |

Führt Datenbankmigrationen aus und repariert Tabellenstrukturen.

```php
public function __construct(Database $db)
public function run(): void                 // Ausstehende Migrationen ausführen
public function repairTables(): void        // Tabellenstruktur reparieren
```

---

## 4. Auth & Session
<!-- UPDATED: 2026-03-08 -->

### 4.1 Auth

| | |
|---|---|
| **Namespace** | `CMS\Auth` |
| **Pfad** | `CMS/core/Auth.php` |
| **Pattern** | Singleton |

Kernklasse für Authentifizierung und Session-Verwaltung. Unterstützt
rollenbasierte Zugangskontrolle, Passwort-Richtlinien und MFA (TOTP).

**Session-Lebenszeiten:**

| Rolle | Dauer |
|-------|-------|
| Admin | 8 Stunden |
| Member | 30 Tage |

**Wichtige Methoden:**

```php
// Singleton & Status
public static function instance(): self
public static function isLoggedIn(): bool
public static function hasRole(string $role): bool
public static function isAdmin(): bool
public static function getCurrentUser(): ?object
public function currentUser(): ?object
public function hasCapability(string $cap): bool

// Login / Logout / Registrierung
public function login(string $username, string $password): bool|string
public function logout(): void
public function register(array $data): bool|string
public static function validatePasswordPolicy(string $password): true|string

// MFA (TOTP)
public function isMfaEnabled(int $userId): bool
public function setupMfaSecret(int $userId): array
public function confirmMfaSetup(int $userId, string $code): bool
public function verifyMfaCode(int $userId, string $code): bool
public function disableMfa(int $userId): void
```

**Beispiel:**

```php
$auth = \CMS\Auth::instance();

// Login
$result = $auth->login('admin', 'geheim123');
if ($result === true) {
    echo 'Angemeldet!';
} else {
    echo 'Fehler: ' . $result;  // Fehlermeldung als String
}

// Zugriffsschutz
if (!\CMS\Auth::isAdmin()) {
    header('Location: /login');
    exit;
}

// Aktuellen Benutzer abrufen
$user = \CMS\Auth::getCurrentUser();
echo $user->username;
```

### 4.2 AuthManager

| | |
|---|---|
| **Namespace** | `CMS\Auth\AuthManager` |
| **Pfad** | `CMS/core/Auth/AuthManager.php` |
| **Pattern** | Singleton |

Zentraler Authentifizierungs-Dispatcher, der verschiedene Auth-Provider
koordiniert: Session, Passkey/WebAuthn, LDAP und MFA (TOTP + Backup-Codes).

**Wichtige Methoden:**

```php
public static function instance(): self
public function passkey(): WebAuthnAdapter
public function ldap(): LdapAuthProvider
public function totp(): TotpAdapter
public function backupCodes(): BackupCodesManager
public function login(string $username, string $password): bool|string
public function authenticateViaPasskey(/* ... */): bool|string
public function authenticateViaLdap(string $username, string $password): bool|string
public function verifyMfa(string $code): bool|string
public function getAvailableProviders(): array
public function isPasskeyAvailable(): bool
public function isLdapEnabled(): bool
```

**Beispiel:**

```php
$authMgr = \CMS\Auth\AuthManager::instance();

// Verfügbare Auth-Methoden prüfen
$providers = $authMgr->getAvailableProviders();

// Login mit LDAP
if ($authMgr->isLdapEnabled()) {
    $result = $authMgr->authenticateViaLdap('user@firma.de', 'password');
}
```

### 4.3 Totp

| | |
|---|---|
| **Namespace** | `CMS\Totp` |
| **Pfad** | `CMS/core/Totp.php` |
| **Pattern** | Singleton |

TOTP-Implementierung (RFC 6238) für Zwei-Faktor-Authentifizierung.

```php
public static function instance(): self
public function generateSecret(): string
public function generateCode(string $secret, ?int $timestamp = null): string
public function verifyCode(string $secret, string $code): bool
public function getOtpAuthUri(string $secret, string $account, string $issuer): string
public function getQrCodeUrl(string $secret, string $account, string $issuer): string
```

---

## 5. Security
<!-- UPDATED: 2026-03-08 -->

| | |
|---|---|
| **Namespace** | `CMS\Security` |
| **Pfad** | `CMS/core/Security.php` |
| **Pattern** | Singleton |

Zentrale Sicherheitsklasse für CSRF-Schutz, XSS-Prävention, Input-Sanitization,
CSP-Nonce-Generierung, Rate-Limiting und Security-Header.

**Wichtige Methoden:**

```php
// Singleton & Init
public static function instance(): self
public function init(): void                   // Session starten, Nonce erzeugen, Security-Headers

// CSP Nonce (H-03)
public function getNonce(): string
public function nonceAttr(): string            // Gibt nonce="..." Attribut zurück

// CSRF-Token
public function generateNonceField(string $action = 'default'): void
public function createNonce(string $action = 'default'): string
public function generateToken(string $action = 'default'): string
public function verifyNonce(string $token, string $action = 'default'): bool
public function verifyToken(string $token, string $action = 'default'): bool
public function verifyPersistentToken(string $token, string $action = 'default'): bool

// Sanitization & Validation
public static function sanitize(string $input, string $type = 'text'): string
public static function escape(string|int $output): string
public static function validateEmail(string $email): bool
public static function validateUrl(string $url): bool

// Passwort-Hashing
public static function hashPassword(string $password): string
public static function verifyPassword(string $password, string $hash): bool

// Rate-Limiting
public static function checkRateLimit(string $identifier, int $maxAttempts = 5,
                                      int $timeWindow = 300): bool
public static function checkDbRateLimit(string $ip, string $action,
                                        int $max, int $window): bool
public static function getClientIp(): string

// Security-Header
public function getSecurityHeaderProfile(): array
```

**Beispiel:**

```php
$sec = \CMS\Security::instance();

// CSRF-Token in Formularen
echo '<form method="POST">';
$sec->generateNonceField('edit_page');
echo '<button type="submit">Speichern</button></form>';

// Token serverseitig prüfen
if (!$sec->verifyNonce($_POST['_nonce'] ?? '', 'edit_page')) {
    die('Ungültiges CSRF-Token');
}

// Input bereinigen
$title = \CMS\Security::sanitize($_POST['title'], 'text');
$email = \CMS\Security::sanitize($_POST['email'], 'email');

// CSP-Nonce in Templates
echo '<script ' . $sec->nonceAttr() . '>console.log("safe");</script>';
```

---

## 6. Cache
<!-- UPDATED: 2026-03-08 -->

| | |
|---|---|
| **Namespace** | `CMS\CacheManager` |
| **Pfad** | `CMS/core/CacheManager.php` |
| **Pattern** | Singleton |
| **Implements** | `CMS\Contracts\CacheInterface` |

Zweistufiges Caching-System mit APCu als L1 (In-Memory) und File-basiertem L2-Cache.
Unterstützt LiteSpeed-Integration und HMAC-gesicherte Cache-Dateien.

**Cache-Architektur:**

| Ebene | Backend | Geschwindigkeit |
|-------|---------|-----------------|
| L1 | APCu (wenn verfügbar) | Sub-Millisekunde |
| L2 | Dateisystem | Millisekunden |

**Wichtige Methoden:**

```php
public static function instance(): self

// Einzelwerte
public function get(string $key, mixed $default = null): mixed
public function set(string $key, mixed $value, ?int $ttl = null): bool
public function has(string $key): bool
public function delete(string $key): bool

// Batch-Operationen
public function getMultiple(array $keys, mixed $default = null): array
public function setMultiple(array $values, ?int $ttl = null): bool
public function deleteMultiple(array $keys): bool

// Cache leeren
public function flush(): bool
public function clear(): bool
public function clearAll(): array   // Alle Cache-Schichten leeren, gibt Status zurück

// HTTP-Cache-Header
public function setCacheHeaders(int $ttl = 300, bool $private = false): void

// Status & Info
public function getStatus(): array
```

**Beispiel:**

```php
$cache = \CMS\CacheManager::instance();

// Wert setzen (TTL: 600 Sekunden)
$cache->set('menu_main', $menuData, 600);

// Wert lesen
$menu = $cache->get('menu_main');

// Cache-Aside-Pattern
$pages = $cache->get('all_pages');
if ($pages === null) {
    $pages = \CMS\Database::fetchAll(
        "SELECT * FROM cms_pages WHERE status = 'published'"
    );
    $cache->set('all_pages', $pages, 3600);
}

// Kompletten Cache leeren
$result = $cache->clearAll();
// Gibt ['apcu' => true, 'file' => true, 'litespeed' => false] zurück
```

---

## 7. Hooks
<!-- UPDATED: 2026-03-08 -->

| | |
|---|---|
| **Namespace** | `CMS\Hooks` |
| **Pfad** | `CMS/core/Hooks.php` |
| **Pattern** | Statische Klasse (kein Singleton) |

WordPress-kompatibles Action/Filter-System für die Plugin-Architektur.
Alle Methoden sind statisch aufrufbar.

**Wichtige Methoden:**

```php
// Actions (Seiteneffekte auslösen)
public static function addAction(string $tag, callable $callback,
                                 int $priority = 10): void
public static function doAction(string $tag, ...$args): void
public static function removeAction(string $tag, callable $callback,
                                    int $priority = 10): bool

// Filters (Werte transformieren)
public static function addFilter(string $tag, callable $callback,
                                 int $priority = 10): void
public static function applyFilters(string $tag, $value, ...$args): mixed
public static function removeFilter(string $tag, callable $callback,
                                    int $priority = 10): bool
```

**Priorisierung:** Niedrigere Werte werden zuerst ausgeführt (Standard: 10).

**Beispiel:**

```php
// Action registrieren (z. B. in einem Plugin)
\CMS\Hooks::addAction('plugin_loaded', function (string $plugin) {
    error_log("Plugin geladen: $plugin");
});

// Filter registrieren: Seitentitel anpassen
\CMS\Hooks::addFilter('page_title', function (string $title) {
    return $title . ' | Meine Seite';
}, 20);

// Action auslösen
\CMS\Hooks::doAction('after_page_save', $pageId, $pageData);

// Filter anwenden
$title = \CMS\Hooks::applyFilters('page_title', $rawTitle);
```

---

## 8. Logger & Audit
<!-- UPDATED: 2026-03-08 -->

### 8.1 Logger

| | |
|---|---|
| **Namespace** | `CMS\Logger` |
| **Pfad** | `CMS/core/Logger.php` |
| **Pattern** | Singleton |
| **Implements** | `CMS\Contracts\LoggerInterface` |

PSR-3-kompatibles Logging-System mit Level-basierter Filterung und optionaler
AuditLogger-Integration ab Level `CRITICAL`.

**Log-Level (aufsteigend):**

| Level | Konstante | Bedeutung |
|-------|-----------|-----------|
| debug | `Logger::DEBUG` | Detaillierte Debugging-Informationen |
| info | `Logger::INFO` | Informationsmeldungen |
| notice | `Logger::NOTICE` | Normale, aber beachtenswerte Ereignisse |
| warning | `Logger::WARNING` | Warnungen (Standard-Minimum) |
| error | `Logger::ERROR` | Laufzeitfehler |
| critical | `Logger::CRITICAL` | Kritische Bedingung → AuditLogger |
| alert | `Logger::ALERT` | Sofortige Maßnahme erforderlich |
| emergency | `Logger::EMERGENCY` | System ist unbrauchbar |

**Konfiguration:**

| Konstante | Standard | Beschreibung |
|-----------|----------|--------------|
| `LOG_PATH` | `ABSPATH . 'logs/'` | Verzeichnis für Log-Dateien |
| `LOG_LEVEL` | `'warning'` | Minimaler Log-Level |
| `CMS_DEBUG` | `false` | `true` → Debug-Level aktiv, Ausgabe auf STDERR |

**Wichtige Methoden:**

```php
public static function instance(string $channel = 'cms'): self
public function log(string $level, string $message, array $context = []): void
public function emergency(string $message, array $context = []): void
public function alert(string $message, array $context = []): void
public function critical(string $message, array $context = []): void
public function error(string $message, array $context = []): void
public function warning(string $message, array $context = []): void
public function notice(string $message, array $context = []): void
public function info(string $message, array $context = []): void
public function debug(string $message, array $context = []): void
public function isLevelEnabled(string $level): bool
public function withChannel(string $channel): self   // Neuer Logger mit anderem Channel
```

**Beispiel:**

```php
$log = \CMS\Logger::instance();

$log->info('Seite gespeichert', ['page_id' => 42]);
$log->error('Upload fehlgeschlagen', [
    'file'  => $name,
    'error' => $e->getMessage(),
]);

// Channel-spezifisches Logging
$pluginLog = $log->withChannel('mein-plugin');
$pluginLog->warning('Deprecated API-Aufruf');
```

### 8.2 AuditLogger

| | |
|---|---|
| **Namespace** | `CMS\AuditLogger` |
| **Pfad** | `CMS/core/AuditLogger.php` |
| **Pattern** | Singleton |

Protokolliert sicherheitsrelevante Aktionen in der Tabelle `{prefix}audit_log`.

**Kategorien:**

| Konstante | Beschreibung |
|-----------|-------------|
| `CAT_AUTH` | Login, Logout, Passwort-Reset |
| `CAT_THEME` | Theme aktivieren, löschen, Code-Edit |
| `CAT_PLUGIN` | Plugin aktivieren, deaktivieren, installieren |
| `CAT_USER` | Benutzer erstellen, Rollen, löschen |
| `CAT_SETTING` | Admin-Einstellungen |
| `CAT_MEDIA` | Upload, löschen |
| `CAT_SYSTEM` | Backup, Updates, Cache-Flush |
| `CAT_SECURITY` | CSP, Firewall, IP-Sperren |

**Wichtige Methoden:**

```php
public static function instance(): self
public function log(string $category, string $action, string $message,
                    string $entity = '', ?int $entityId = null,
                    array $context = [], string $severity = 'info'): void

// Convenience-Methoden
public function themeSwitch(string $from, string $to): void
public function themeDelete(string $folder): void
public function themeFileEdit(string $theme, string $file): void
public function pluginAction(string $action, string $slug): void
public function loginSuccess(string $username): void
public function loginFailed(string $username): void
public function userRoleChange(int $userId, string $oldRole, string $newRole): void
public function backupAction(string $action, string $file): void
public function getRecent(int $limit = 50, string $category = ''): array
```

**Beispiel:**

```php
$audit = \CMS\AuditLogger::instance();

// Allgemeiner Eintrag
$audit->log('setting', 'setting.changed', 'SMTP-Passwort aktualisiert');

// Convenience
$audit->loginFailed('hacker@evil.com');
$audit->pluginAction('activate', 'cms-companies');
```

### 8.3 Debug

| | |
|---|---|
| **Namespace** | `CMS\Debug` |
| **Pfad** | `CMS/core/Debug.php` |
| **Pattern** | Statische Klasse |

Entwickler-Debug-Werkzeuge mit Timer und Log-Sammlung.

```php
public static function enable(bool $enable = true): void
public static function isEnabled(): bool
public static function startTimer(): void
public static function getElapsedTime(): float
public static function log(string $message, string $type = 'info', mixed $data = null): void
public static function success(string $message, mixed $data = null): void
public static function warning(string $message, mixed $data = null): void
public static function error(string $message, mixed $data = null): void
public static function exception(\Throwable $e, string $context = ''): void
public static function getLogs(): array
```

---

## 9. Seiten & Inhalte
<!-- UPDATED: 2026-03-08 -->

### 9.1 PageManager

| | |
|---|---|
| **Namespace** | `CMS\PageManager` |
| **Pfad** | `CMS/core/PageManager.php` |
| **Pattern** | Singleton |

Verwaltung von Seiten, Inhalten, Revisionen und Seitensuche. Führt beim
Start automatische Schema-Migrationen durch (z. B. `hide_title`, `featured_image`,
`meta_title`, `meta_description`).

**Wichtige Methoden:**

```php
public static function instance(): self
public function createPage(string $title, string $content, string $status,
                           int $authorId, int $hideTitle = 0): int
public function updatePage(int $id, array $data): bool
public function deletePage(int $id): bool
public function getPage(int $id): ?array
public function getPageBySlug(string $slug): ?array
public function listPages(): array
public function search(string $query): array
public function generateSlug(string $title): string
public function getRevisions(int $pageId): array
```

**Beispiel:**

```php
$pm = \CMS\PageManager::instance();

// Seite erstellen
$pageId = $pm->createPage('Impressum', '<p>...</p>', 'published', 1);

// Seite per Slug laden
$page = $pm->getPageBySlug('impressum');

// Seite aktualisieren
$pm->updatePage($pageId, [
    'title'   => 'Impressum & Datenschutz',
    'content' => '<p>Aktualisierter Inhalt</p>',
]);

// Suche
$results = $pm->search('Datenschutz');
```

### 9.2 EditorJsRenderer

| | |
|---|---|
| **Namespace** | `CMS\Services\EditorJsRenderer` |
| **Pfad** | `CMS/core/Services/EditorJsRenderer.php` |

Rendert EditorJS-JSON-Daten in HTML.

```php
public static function getInstance(): self
public function render(string|array $data): string
```

**Beispiel:**

```php
$html = \CMS\Services\EditorJsRenderer::getInstance()->render($editorJsJson);
```

---

## 10. Theme & Template
<!-- UPDATED: 2026-03-08 -->

### 10.1 ThemeManager

| | |
|---|---|
| **Namespace** | `CMS\ThemeManager` |
| **Pfad** | `CMS/core/ThemeManager.php` |
| **Pattern** | Singleton |

Verwaltet das Laden, Rendern und Wechseln von Themes. Lazy Loading für
Theme-Settings (H-22) – DB-Zugriff erst beim ersten echten Zugriff.
Im API-/CLI-Modus wird ThemeManager nicht geladen (H-12).

**Wichtige Methoden:**

```php
public static function instance(): self

// Laden & Rendern
public function loadTheme(): void
public function render(string $template, array $data = []): void
public function getHeader(): void
public function getFooter(): void
public function renderCustomStyles(): void

// Theme-Informationen
public function getActiveThemeSlug(): string
public function getThemePath(): string
public function getThemeUrl(): string
public function getCurrentTheme(): array
public function getAvailableThemes(): array

// Theme-Verwaltung
public function switchTheme(string $theme): bool|string
public function deleteTheme(string $folder): bool|string
public function healthCheckTheme(string $theme): bool|string

// Seiten-Meta
public function getSiteTitle(): string
public function getSiteDescription(): string

// Menü-Verwaltung
public function getSiteMenu(): array
public function getMenu(string $location): array
public function saveMenu(string $location, array $items): bool
public function getMenuLocations(): array
public function saveCustomMenuLocations(array $locations): bool
```

**Beispiel:**

```php
$theme = \CMS\ThemeManager::instance();

// Seite rendern
$theme->render('page', [
    'title'   => $page['title'],
    'content' => $page['content'],
]);

// Theme wechseln
$result = $theme->switchTheme('starter-developer');
if ($result !== true) {
    echo 'Fehler: ' . $result;
}

// Menü abrufen
$mainMenu = $theme->getMenu('primary');
```

### 10.2 ThemeCustomizer

| | |
|---|---|
| **Namespace** | `CMS\Services\ThemeCustomizer` |
| **Pfad** | `CMS/core/Services/ThemeCustomizer.php` |
| **Pattern** | Singleton |

Verwaltet Theme-Anpassungen (Farben, Schriften, Layout) mit Export/Import-Funktion
und dynamischer CSS-Generierung.

**Wichtige Methoden:**

```php
public static function instance(): ThemeCustomizer
public function setTheme(string $themeSlug): void
public function getTheme(): string
public function getThemeConfig(): array
public function getThemeMetadata(): array
public function getCustomizationOptions(): array
public function get(string $category, string $key, $default = null): mixed
public function getCategory(string $category): array
public function set(string $category, string $key, $value, ?int $userId = null): bool
public function setMultiple(array $settings, ?int $userId = null): bool
public function reset(string $category, string $key, ?int $userId = null): bool
public function resetAll(?int $userId = null): bool
public function generateCSS(): string
public function export(?int $userId = null): array
public function import(array $data, ?int $userId = null): bool
```

**Beispiel:**

```php
$customizer = \CMS\Services\ThemeCustomizer::instance();

// Farbe setzen
$customizer->set('colors', 'primary', '#3b82f6');

// Alle Farben abrufen
$colors = $customizer->getCategory('colors');

// CSS generieren
$css = $customizer->generateCSS();

// Einstellungen exportieren / importieren
$backup = $customizer->export();
$customizer->import($backup);
```

---

## 11. Plugin-System
<!-- UPDATED: 2026-03-08 -->

| | |
|---|---|
| **Namespace** | `CMS\PluginManager` |
| **Pfad** | `CMS/core/PluginManager.php` |
| **Pattern** | Singleton |

Verwaltet den Lebenszyklus von Plugins: Laden, Aktivieren, Deaktivieren,
Installieren und Löschen. Fehlerhafte Plugins werden automatisch deaktiviert
(C-07/H-25 – Crash-Schutz).

**Wichtige Methoden:**

```php
public static function instance(): self
public function loadPlugins(): void                         // Alle aktiven Plugins laden
public function isPluginActive(string $slug): bool          // Prüft ob Plugin aktiv
public function getAvailablePlugins(): array                // Alle installierten Plugins
public function getActivePlugins(): array                   // Aktive Plugin-Slugs
public function activatePlugin(string $plugin): bool|string
public function deactivatePlugin(string $plugin): bool|string
public function deletePlugin(string $plugin): bool|string
public function installPlugin(array $file): bool|string     // Upload & Installation
```

**Beispiel:**

```php
$pm = \CMS\PluginManager::instance();

// Plugin aktivieren
$result = $pm->activatePlugin('cms-companies');
if ($result !== true) {
    echo 'Fehler: ' . $result;
}

// Prüfen ob Plugin aktiv
if ($pm->isPluginActive('cms-companies')) {
    // Plugin-spezifische Logik
}

// Alle Plugins auflisten
$plugins = $pm->getAvailablePlugins();
```

---

## 12. API
<!-- UPDATED: 2026-03-08 -->

| | |
|---|---|
| **Namespace** | `CMS\Api` |
| **Pfad** | `CMS/core/Api.php` |
| **Pattern** | Singleton |

REST-API-Controller (V1) mit integriertem Rate-Limiting (M-19: max. 60 Anfragen
pro 60 Sekunden pro IP).

**Endpunkte:** `/api/v1/{endpoint}/{id}`

| Endpoint | Beschreibung |
|----------|-------------|
| `status` | Systemstatus und Version |
| `pages` | Seitenverwaltung (CRUD) |
| `users` | Benutzerverwaltung |

**Wichtige Methoden:**

```php
public static function instance(): self
public function handleRequest(string $endpoint, ?string $id = null): void
```

**Beispiel:**

```php
// Wird normalerweise vom Router aufgerufen:
$api = \CMS\Api::instance();
$api->handleRequest('pages', '42');

// Antwort (JSON):
// {"id": 42, "title": "Startseite", "slug": "startseite", ...}
```

---

## 13. Mail
<!-- UPDATED: 2026-03-08 -->

| | |
|---|---|
| **Namespace** | `CMS\Services\MailService` |
| **Pfad** | `CMS/core/Services/MailService.php` |
| **Pattern** | Singleton |

Zentraler Mail-Service mit Unterstützung für PHP `mail()`, klassisches SMTP
und Microsoft 365 SMTP via Azure OAuth2/XOAUTH2. Konfiguration über
`{prefix}settings` oder `config/app.php`.

**Wichtige Methoden:**

```php
public static function getInstance(): self

// Einfache API (gibt bool zurück)
public function send(string $to, string $subject, string $htmlBody,
                     array $headers = []): bool
public function sendPlain(string $to, string $subject, string $plainBody,
                          array $headers = []): bool
public function sendWithAttachment(string $to, string $subject,
                                   string $htmlBody, string $attachmentPath,
                                   string $attachmentName,
                                   array $headers = []): bool

// Detaillierte API (gibt Status-Array zurück)
public function sendDetailed(string $to, string $subject,
                             string $htmlBody, array $headers = []): array
public function sendPlainDetailed(string $to, string $subject,
                                  string $plainBody, array $headers = []): array
public function sendWithAttachmentDetailed(/* ... */): array

// Queue & Test
public function queueBackendTestEmail(string $to, string $source = 'admin-queue'): array
public function queueWithAttachment(/* ... */): array
public function sendBackendTestEmail(string $to, string $source = 'admin'): array

// Diagnose
public function getTransportInfo(): array
```

**Beispiel:**

```php
$mail = \CMS\Services\MailService::getInstance();

// Einfache HTML-Mail
$mail->send('user@example.com', 'Willkommen!', '<h1>Hallo!</h1>');

// Detailliert mit Fehlerbehandlung
$result = $mail->sendDetailed('admin@example.com', 'Bericht', $html);
if (!$result['success']) {
    echo 'Fehler: ' . $result['error'];
}
```

---

## 14. Suche
<!-- UPDATED: 2026-03-08 -->

| | |
|---|---|
| **Namespace** | `CMS\Services\SearchService` |
| **Pfad** | `CMS/core/Services/SearchService.php` |
| **Pattern** | Singleton (`final` class) |

Volltextsuche via TNTSearch (SQLite-Engine) mit GermanStemmer. Unterstützt
fuzzy Search, Highlighting, Snippets und erweiterbare Index-Definitionen
über Hooks.

**Wichtige Methoden:**

```php
public static function getInstance(): self
public function isAvailable(): bool
public function getUnavailableReason(): string

// Indexverwaltung
public function registerIndex(string $name, string $query,
                              string $primaryKey = 'id'): void
public function buildIndex(string $name): bool
public function rebuildAllIndices(): array
public function getIndexDefinitions(): array

// Suche
public function search(string $query, string $indexName = 'pages',
                       int $limit = 50, bool $fuzzy = false): array
public function searchAll(string $query, int $limit = 50,
                          bool $fuzzy = false): array

// Darstellung
public function highlight(string $text, string $query,
                          string $tag = 'mark'): string
public function snippet(string $text, string $query, int $length = 200): string

// Hooks für Live-Updates
public function onPageSaved(int $pageId): void
public function onPageDeleted(int $pageId): void
public function onPostSaved(int $postId): void
public function onPostDeleted(int $postId): void
```

**Beispiel:**

```php
$search = \CMS\Services\SearchService::getInstance();

if ($search->isAvailable()) {
    // Suche über alle Indizes
    $results = $search->searchAll('Datenschutz', 20, fuzzy: true);

    foreach ($results as $hit) {
        $snippet = $search->snippet($hit['content'], 'Datenschutz');
        echo $search->highlight($snippet, 'Datenschutz');
    }
}
```

---

## 15. Übersetzung
<!-- UPDATED: 2026-03-08 -->

| | |
|---|---|
| **Namespace** | `CMS\Services\TranslationService` |
| **Pfad** | `CMS/core/Services/TranslationService.php` |
| **Pattern** | Singleton (`final` class) |

Internationalisierung über Symfony Translation (wenn verfügbar) mit Fallback-Katalog
für Shared-Hosting-Umgebungen.

**Wichtige Methoden:**

```php
public static function getInstance(): self
public function getLocale(): string
public function getAvailableLocales(): array
public function translate(string $message, string $domain = 'default',
                          array $parameters = []): string
public function translatePlural(string $single, string $plural, int $number,
                                string $domain = 'default'): string
```

**Beispiel:**

```php
$t = \CMS\Services\TranslationService::getInstance();

echo $t->translate('welcome_message');
echo $t->translate('greeting', 'default', ['%name%' => 'Max']);

// Plural
echo $t->translatePlural('%count% Seite', '%count% Seiten', 5);

// Locale
echo $t->getLocale(); // 'de'
```

---

## 16. Upload & Medien
<!-- UPDATED: 2026-03-08 -->

### 16.1 FileUploadService

| | |
|---|---|
| **Namespace** | `CMS\Services\FileUploadService` |
| **Pfad** | `CMS/core/Services/FileUploadService.php` |
| **Pattern** | Singleton (`final` class) |

FilePond-kompatibler Upload-Endpunkt für Datei-Uploads.

```php
public static function getInstance(): self
public function handleUploadRequest(): array
// Gibt ['success' => bool, 'status' => int, 'data' => [...]] zurück
```

### 16.2 MediaService

| | |
|---|---|
| **Namespace** | `CMS\Services\MediaService` |
| **Pfad** | `CMS/core/Services/MediaService.php` |
| **Pattern** | Multiton (pro Root-Verzeichnis eine Instanz) |

Umfassende Medienverwaltung mit Kategorien, Ordnerstruktur und Einstellungen.

**Wichtige Methoden:**

```php
public static function getInstance(string $customRoot = ''): self

// Dateiverwaltung
public function getItems(string $path = ''): array|\CMS\WP_Error
public function uploadFile(array $file, string $targetPath = ''): string|\CMS\WP_Error
public function deleteItem(string $path): bool|\CMS\WP_Error
public function renameItem(string $oldPath, string $newName): bool|\CMS\WP_Error
public function createFolder(string $name, string $parentPath = ''): bool|\CMS\WP_Error

// Kategorien
public function getCategories(): array
public function addCategory(string $name, string $slug = ''): bool|\CMS\WP_Error
public function deleteCategory(string $slug): bool|\CMS\WP_Error
public function assignCategory(string $filePath,
                               string $categorySlug): bool|\CMS\WP_Error

// Einstellungen & Info
public function getSettings(): array
public function saveSettings(array $settings): bool|\CMS\WP_Error
public function getDiskUsage(): array
public function formatSize(int $bytes): string
```

**Beispiel:**

```php
$media = \CMS\Services\MediaService::getInstance();

// Datei hochladen
$result = $media->uploadFile($_FILES['avatar'], 'member/avatars');
if ($result instanceof \CMS\WP_Error) {
    echo 'Fehler: ' . $result->get_error_message();
}

// Ordnerinhalt auflisten
$items = $media->getItems('images');

// Speicherverbrauch
$usage = $media->getDiskUsage();
echo $media->formatSize($usage['total']); // z. B. "1.2 GB"
```

### 16.3 ImageService

| | |
|---|---|
| **Namespace** | `CMS\Services\ImageService` |
| **Pfad** | `CMS/core/Services/ImageService.php` |
| **Pattern** | Singleton (`final` class) |

GD-basierte Bildbearbeitung mit WebP-/AVIF-Konvertierung und Wasserzeichen.

**Wichtige Methoden:**

```php
public static function getInstance(): self
public function isAvailable(): bool
public function getInfo(): array

// Skalierung
public function resize(string $source, string $dest, int $maxWidth,
                       int $maxHeight, bool $keepAspect = true,
                       int $quality = 0): bool
public function createThumbnail(string $sourcePath, string $destPath,
                                int $width, int $height): bool
public function createSquareThumbnail(string $sourcePath,
                                     string $destPath, int $size): bool
public function createAllThumbnails(string $sourcePath,
                                   ?array $sizes = null): array

// Bearbeitung
public function crop(string $source, string $dest, int $x, int $y,
                     int $width, int $height): bool
public function rotate(string $source, string $dest, float $angle): bool
public function autoOrient(string $sourcePath): bool

// Konvertierung
public function convertToWebP(string $source, string $dest,
                              int $quality = 82): bool
public function convertToAvif(string $source, string $dest,
                              int $quality = 82): bool

// Extras
public function addWatermark(string $source, string $dest, string $watermark,
                             string $position = 'bottom-right',
                             int $opacity = 50): bool
public function getDimensions(string $path): ?array
public function setDefaultQuality(int $quality): void
```

**Beispiel:**

```php
$img = \CMS\Services\ImageService::getInstance();

if ($img->isAvailable()) {
    // Thumbnail erstellen
    $img->createThumbnail(
        '/uploads/foto.jpg',
        '/uploads/thumb/foto_sm.jpg',
        300, 300
    );

    // WebP-Konvertierung
    $img->convertToWebP('/uploads/foto.jpg', '/uploads/foto.webp');

    // Alle Standardgrößen erzeugen
    $thumbs = $img->createAllThumbnails('/uploads/foto.jpg');
}
```

---

## 17. Settings
<!-- UPDATED: 2026-03-08 -->

| | |
|---|---|
| **Namespace** | `CMS\Services\SettingsService` |
| **Pfad** | `CMS/core/Services/SettingsService.php` |
| **Pattern** | Singleton |

Zentrale Settings-Abstraktion mit Gruppen-/Key-Struktur und optionaler
AES-256-CBC-Verschlüsselung für Secrets.

**Wichtige Methoden:**

```php
public static function getInstance(): self

// Lesen
public function get(string $group, string $key,
                    mixed $default = null): mixed
public function getString(string $group, string $key,
                          string $default = ''): string
public function getInt(string $group, string $key, int $default = 0): int
public function getBool(string $group, string $key,
                        bool $default = false): bool
public function getGroup(string $group): array

// Schreiben
public function set(string $group, string $key, mixed $value,
                    bool $encrypted = false, int $autoload = 0): bool
public function setMany(string $group, array $values,
                        array $encryptedKeys = [], int $autoload = 0): bool
public function forget(string $group, string $key): bool
```

**Beispiel:**

```php
$settings = \CMS\Services\SettingsService::getInstance();

// Einfacher Zugriff
$siteName = $settings->getString('site', 'name', '365CMS');
$perPage  = $settings->getInt('site', 'posts_per_page', 10);

// Verschlüsseltes Secret speichern
$settings->set('smtp', 'password', 'geheim', encrypted: true);

// Mehrere Werte auf einmal
$settings->setMany('smtp', [
    'host' => 'smtp.office365.com',
    'port' => 587,
    'user' => 'noreply@firma.de',
], encryptedKeys: ['password']);
```

---

## 18. Backup
<!-- UPDATED: 2026-03-08 -->

| | |
|---|---|
| **Namespace** | `CMS\Services\BackupService` |
| **Pfad** | `CMS/core/Services/BackupService.php` |
| **Pattern** | Singleton |

Backup-Service für Datenbank und vollständige Systemsicherungen mit
E-Mail-Versand und optionalem S3-Upload.

**Wichtige Methoden:**

```php
public static function getInstance(): self
public function createFullBackup(): array
public function createDatabaseBackup(?string $targetDir = null): string
public function emailDatabaseBackup(string $email): bool
public function uploadToS3(string $backupPath, array $s3Config): bool
public function getBackupHistory(int $limit = 20): array
public function listBackups(): array
public function deleteBackup(string $backupName): bool
```

**Beispiel:**

```php
$backup = \CMS\Services\BackupService::getInstance();

// Datenbank-Backup erstellen
$path = $backup->createDatabaseBackup();

// Backup per E-Mail versenden
$backup->emailDatabaseBackup('admin@example.com');

// Backup-Verlauf anzeigen
$history = $backup->getBackupHistory(10);
```

---

## 19. Contracts (Interfaces)
<!-- UPDATED: 2026-03-08 -->

Die Contracts ermöglichen Dependency Injection, Mocking in Tests und alternative
Implementierungen.

### 19.1 DatabaseInterface

| | |
|---|---|
| **Namespace** | `CMS\Contracts\DatabaseInterface` |
| **Pfad** | `CMS/core/Contracts/DatabaseInterface.php` |
| **Implementiert von** | `CMS\Database` |

Definiert die CRUD-Operationen (`insert`, `update`, `delete`, `get_row`,
`get_results` usw.).

### 19.2 CacheInterface

| | |
|---|---|
| **Namespace** | `CMS\Contracts\CacheInterface` |
| **Pfad** | `CMS/core/Contracts/CacheInterface.php` |
| **Implementiert von** | `CMS\CacheManager` |

PSR-16-orientiertes Interface (`get`, `set`, `delete`, `has`, `flush`).

### 19.3 LoggerInterface

| | |
|---|---|
| **Namespace** | `CMS\Contracts\LoggerInterface` |
| **Pfad** | `CMS/core/Contracts/LoggerInterface.php` |
| **Implementiert von** | `CMS\Logger` |

PSR-3-kompatibles Interface mit allen acht Log-Level-Methoden.

---

## Weitere Kern-Services (Kurzübersicht)
<!-- UPDATED: 2026-03-08 -->

Die folgenden Services im Namespace `CMS\Services\` bieten spezialisierte
Funktionalität:

| Service | Pfad | Beschreibung |
|---------|------|-------------|
| `SitemapService` | `Services/SitemapService.php` | XML-Sitemap-Generierung (Pages, Posts, Images, News) |
| `SeoAnalysisService` | `Services/SeoAnalysisService.php` | SEO-Analyse und Optimierungsvorschläge |
| `SEOService` | `Services/SEOService.php` | SEO-Meta-Tags und Open-Graph-Verwaltung |
| `AnalyticsService` | `Services/AnalyticsService.php` | Analyse und Statistik-Auswertung |
| `TrackingService` | `Services/TrackingService.php` | Besucher-Tracking |
| `JwtService` | `Services/JwtService.php` | JWT-Token-Verwaltung für API-Authentifizierung |
| `UserService` | `Services/UserService.php` | Benutzerverwaltung und Profil-Operationen |
| `MemberService` | `Services/MemberService.php` | Mitgliederbereich-Logik |
| `CommentService` | `Services/CommentService.php` | Kommentarsystem |
| `FeedService` | `Services/FeedService.php` | RSS-/Atom-Feed-Generierung |
| `PdfService` | `Services/PdfService.php` | PDF-Generierung |
| `EditorService` | `Services/EditorService.php` | Code-Editor-Backend |
| `EditorJsService` | `Services/EditorJsService.php` | EditorJS-Integration und -Verwaltung |
| `PurifierService` | `Services/PurifierService.php` | HTML-Purifier (XSS-Schutz für Inhalte) |
| `CookieConsentService` | `Services/CookieConsentService.php` | Cookie-Consent-Verwaltung (DSGVO) |
| `RedirectService` | `Services/RedirectService.php` | URL-Redirect-Verwaltung (301/302) |
| `IndexingService` | `Services/IndexingService.php` | Suchindex-Verwaltung |
| `StatusService` | `Services/StatusService.php` | Systemstatus und Gesundheitsprüfungen |
| `SystemService` | `Services/SystemService.php` | Systeminformationen und -wartung |
| `UpdateService` | `Services/UpdateService.php` | CMS-Update-Verwaltung |
| `DashboardService` | `Services/DashboardService.php` | Admin-Dashboard-Widgets und -Daten |
| `MailQueueService` | `Services/MailQueueService.php` | Asynchrone Mail-Queue |
| `MailLogService` | `Services/MailLogService.php` | Mail-Versand-Protokollierung |
| `LandingPageService` | `Services/LandingPageService.php` | Landing-Page-Builder |
| `SiteTableService` | `Services/SiteTableService.php` | Mehrstufige Seitentabellen-Verwaltung |
| `ElfinderService` | `Services/ElfinderService.php` | elFinder-Dateimanager-Integration |
| `GraphApiService` | `Services/GraphApiService.php` | Microsoft Graph API-Anbindung |
| `AzureMailTokenProvider` | `Services/AzureMailTokenProvider.php` | Azure AD OAuth2-Token für SMTP |

---

## Auth-Subsystem
<!-- UPDATED: 2026-03-08 -->

| Klasse | Pfad | Beschreibung |
|--------|------|-------------|
| `Auth\AuthManager` | `Auth/AuthManager.php` | Zentraler Auth-Dispatcher (siehe [4.2](#42-authmanager)) |
| `Auth\MFA\TotpAdapter` | `Auth/MFA/TotpAdapter.php` | TOTP-Adapter für MFA-Integration |
| `Auth\MFA\BackupCodesManager` | `Auth/MFA/BackupCodesManager.php` | Verwaltung von MFA-Backup-Codes |
| `Auth\LDAP\LdapAuthProvider` | `Auth/LDAP/LdapAuthProvider.php` | LDAP/Active-Directory-Authentifizierung |
| `Auth\Passkey\WebAuthnAdapter` | `Auth/Passkey/WebAuthnAdapter.php` | WebAuthn/Passkey-Authentifizierung |

---

*Generiert am 2026-03-08 – Diese Dokumentation basiert auf dem aktuellen Stand des
365CMS-Quellcodes (Version 2.5.4).*
