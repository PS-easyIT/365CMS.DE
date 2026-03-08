# 365CMS – Core-Klassen Referenz
> **Stand:** 2026-03-08 | **Version:** 2.5.4 | **Status:** Aktuell

## Inhaltsverzeichnis
- <a>Überblick</a>
- <a>Bootstrap & Container</a>
- <a>Router</a>
- <a>Database & Schema</a>
- <a>Auth & Security</a>
- <a>Hooks & Plugins</a>
- <a>Themes & Templates</a>
- <a>Cache</a>
- <a>Mail</a>
- <a>Upload & Assets</a>
- <a>Search</a>
- <a>Translation</a>
- <a>API</a>
- <a>Logging</a>
- <a>Subscription</a>
- <a>Hilfsklassen & Contracts</a>

---

## Überblick <!-- UPDATED: 2026-03-08 -->
Referenz der zentralen Core-Klassen (Namespaces `CMS\*` und `CMS\Services\*`). Jede Sektion nennt Pfad, Aufgabe, zentrale Public-Methoden und ein kurzes Anwendungsbeispiel.

---

## Bootstrap & Container <!-- UPDATED: 2026-03-08 -->
**Bootstrap** (`core/Bootstrap.php`, `CMS\Bootstrap`)
- Aufgabe: Betriebsmodus bestimmen (`web|admin|api|cli`), Konstanten sichern, Vendor-Autoload laden, Container befüllen, Migrationen ausführen, Plugins/Themes laden.
- Wichtige Methoden: `instance()`, `initializeCore()`, `loadDependencies()`, `detectMode()`.
- Beispiel:
```php
$bootstrap = \CMS\Bootstrap::instance();
$router = \CMS\Container::instance()->get(\CMS\Router::class);
```

**Container** (`core/Container.php`, `CMS\Container`)
- Aufgabe: DI-Container mit Singleton-/Instance-Bindings.
- Wichtige Methoden: `instance()`, `bindInstance($id, $obj)`, `singleton($id, callable)`, `get($id)`, `has($id)`.

---

## Router <!-- UPDATED: 2026-03-08 -->
**Router** (`core/Router.php`, `CMS\Router`)
- Aufgabe: URL-Routing für Web, Admin, API; Standardrouten registrieren; Dispatch mit Parameter-Matching.
- Wichtige Public-Methoden: `instance()`, `addRoute($method, $path, $callback)`, `dispatch()`.
- Hilfs-Privates (wichtig für Verhalten): `requireAdmin()`, `jsonAdminPosts/pages/users`, `render*`-Handler.
- Beispiel:
```php
$router = \CMS\Router::instance();
$router->addRoute('GET', '/health', fn() => print('ok'));
$router->dispatch();
```

---

## Database & Schema <!-- UPDATED: 2026-03-08 -->
**Database** (`core/Database.php`, `CMS\Database`)
- Aufgabe: PDO-Wrapper mit Helpern.
- Wichtige Methoden: `instance()`, `getPdo()`, `query($sql)`, `execute($sql, $params)`, `get_row()`, `get_results()`, `get_var()`, `insert($table, $data)`, `update($table, $data, $where)`, `delete()`, `transaction(callable)`, `getPrefix()`.
- Beispiel:
```php
$db = \CMS\Database::instance();
$user = $db->get_row("SELECT * FROM {$db->getPrefix()}users WHERE id = 1");
```

**SchemaManager** (`core/SchemaManager.php`, `CMS\SchemaManager`)
- Aufgabe: Basistabellen (SCHEMA_VERSION v14) via `createTables()`, Flag-Datei, Default-Admin, Content-Spalten ergänzen.
- Wichtige Methoden: `createTables()`, `clearFlag()`, `getFlagFile()`.

**MigrationManager** (`core/MigrationManager.php`, `CMS\MigrationManager`)
- Aufgabe: Inkrementelle Migrationen nach SCHEMA_VERSION; Tabellen/Spalten nachziehen (z. B. passkey_credentials, mail_log/-queue).
- Wichtige Methoden: `run()`.

---

## Auth & Security <!-- UPDATED: 2026-03-08 -->
**Auth** (`core/Auth.php`, `CMS\Auth`)
- Aufgabe: Login/Logout/Registration, Session-User laden, Passwort-Policy.
- Wichtige Methoden: `instance()`, `login($username,$password)`, `logout()`, `register($data)`, `changePassword($userId,$old,$new)`, `validatePasswordPolicy($pw)`, `currentUser()`, `isAdmin()`.
- Beispiel:
```php
$auth = \CMS\Auth::instance();
if ($auth->login('user', 'secret')) { /* ... */ }
```

**Security** (`core/Security.php`, `CMS\Security`)
- Aufgabe: CSRF-Token, Security-Header, Session-Härtung.
- Wichtige Methoden: `instance()`, `generateToken($action)`, `verifyToken($token,$action)`, `verifyPersistentToken($token,$action)`, `init()`, `sanitizeInput($data)`.
- Beispiel:
```php
$token = \CMS\Security::instance()->generateToken('admin_form');
if (!\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_form')) { die('forbidden'); }
```

**Totp** (`core/Totp.php`, `CMS\Totp`)
- Aufgabe: TOTP 2FA (shared secret, QR, verify).
- Wichtige Methoden: `generateSecret()`, `getQrCodeUrl($label,$secret)`, `verifyCode($secret,$code)`.

---

## Hooks & Plugins <!-- UPDATED: 2026-03-08 -->
**Hooks** (`core/Hooks.php`, `CMS\Hooks`)
- Aufgabe: Action/Filter-System ähnlich WP.
- Wichtige Methoden: `addAction($hook,$cb,$priority=10)`, `doAction($hook,...$args)`, `addFilter($hook,$cb,$priority=10)`, `applyFilters($hook,$value,...$args)`.

**PluginManager** (`core/PluginManager.php`, `CMS\PluginManager`)
- Aufgabe: Plugins laden/aktivieren, Hooks registrieren, Plugin-Routen für Admin/Member.
- Wichtige Methoden: `instance()`, `loadPlugins()`, `activate($slug)`, `deactivate($slug)`, `getActivePlugins()`.

---

## Themes & Templates <!-- UPDATED: 2026-03-08 -->
**ThemeManager** (`core/ThemeManager.php`, `CMS\ThemeManager`)
- Aufgabe: Aktives Theme laden, Templates/Assets bereitstellen (nicht im API/CLI-Modus).
- Wichtige Methoden: `instance()`, `loadTheme()`, `getActiveTheme()`, `renderTemplate($template,$data=[])`.

**PageManager** (`core/PageManager.php`, `CMS\PageManager`)
- Aufgabe: Seiten/Beiträge laden, Slugs auflösen, Breadcrumb/TOC-Hilfen.
- Wichtige Methoden: `getPageBySlug($slug)`, `getPostBySlug($slug)`, `search($term,$type)`.

---

## Cache <!-- UPDATED: 2026-03-08 -->
**CacheManager** (`core/CacheManager.php`, `CMS\CacheManager`)
- Aufgabe: Key/Value-Cache mit optionalen Treibern.
- Wichtige Methoden: `instance()`, `get($key,$default=null)`, `set($key,$value,$ttl=null)`, `remember($key,$ttl,callable)`, `delete($key)`, `clear()`.
- Beispiel:
```php
$cache = \CMS\CacheManager::instance();
$posts = $cache->remember('latest_posts', 300, fn() => fetchPosts());
```

---

## Mail <!-- UPDATED: 2026-03-08 -->
**MailService** (`core/Services/MailService.php`, `CMS\Services\MailService`)
- Aufgabe: Versand über Symfony Mailer/SMTP/OAuth2, Templates, Fallbacks.
- Wichtige Methoden: `getInstance()`, `send($to,$subject,$body,$opts=[])`, `sendBackendTestEmail($recipient)`.

**MailQueueService** (`core/Services/MailQueueService.php`)
- Aufgabe: Queue mit Status/Retry/Backoff.
- Wichtige Methoden: `enqueue($message)`, `dequeueBatch($limit)`, `markSent($id)`, `markFailed($id,$error)`.

**MailLogService** (`core/Services/MailLogService.php`)
- Aufgabe: Log-Abfrage fürs Admin-Grid; schreibt/liest `mail_log`.
- Wichtige Methoden: `log(array $data)`, `getRecent($limit,$page,$search,$status)`.

**AzureMailTokenProvider** (`core/Services/AzureMailTokenProvider.php`)
- Aufgabe: XOAUTH2-Token-Caching für Microsoft 365 SMTP.
- Wichtige Methoden: `getInstance()`, `getAccessToken()`, `clearToken()`.

---

## Upload & Assets <!-- UPDATED: 2026-03-08 -->
**FileUploadService** (`core/Services/FileUploadService.php`)
- Aufgabe: FilePond-kompatible Upload-Verarbeitung inkl. CSRF/Auth.
- Wichtige Methoden: `getInstance()`, `handleUploadRequest()`.

**ImageService** (`core/Services/ImageService.php`)
- Aufgabe: Bildbearbeitung/Thumbnails (GD).
- Wichtige Methoden: `getInstance()`, `resize($path,$w,$h,$crop=false)`, `optimize($path)`.

**Asset-Ladepfad**
- Externe Libraries werden über `CMS/assets/autoload.php` geladen; Frontend-Assets via Hooks `head`/`body_end` (PhotoSwipe, CookieConsent, Fonts).

---

## Search <!-- UPDATED: 2026-03-08 -->
**SearchService** (`core/Services/SearchService.php`)
- Aufgabe: TNTSearch-Volltextsuche; Indexierung/Abfrage.
- Wichtige Methoden: `getInstance()`, `indexContent($entity,$data)`, `search($query,$limit=20)`, `reindexAll()`.

---

## Translation <!-- UPDATED: 2026-03-08 -->
**TranslationService** (`core/Services/TranslationService.php`)
- Aufgabe: Symfony-Translation gestützt, Sprachdateien laden.
- Wichtige Methoden: `getInstance()`, `setLocale($locale)`, `trans($key,array $params=[],?string $locale=null)`, `addResource($locale,$domain,$path)`.

---

## API <!-- UPDATED: 2026-03-08 -->
**Api** (`core/Api.php`, `CMS\Api`)
- Aufgabe: REST-Handler für v1-Endpunkte (Pages, Status); ruft Services und Renderer.
- Wichtige Methoden: `instance()`, `handleRequest($resource,$slug=null)`.
- Beispiel:
```php
\CMS\Api::instance()->handleRequest('pages', 'welcome');
```

---

## Logging <!-- UPDATED: 2026-03-08 -->
**Logger** (`core/Logger.php`, `CMS\Logger`)
- Aufgabe: PSR-3-kompatibles Logging, Tagesrotation, Channels.
- Wichtige Methoden: `instance()`, `log($level,$message,array $context=[])`, `withChannel($channel)`, Helfer `cms_log()` (global).

**AuditLogger** (`core/AuditLogger.php`, `CMS\AuditLogger`)
- Aufgabe: Sicherheitsrelevante Events in `audit_log` erfassen.
- Wichtige Methoden: `instance()`, `log($category,$action,$entityType,$entityId,$severity='info',$meta=[])`.

---

## Subscription <!-- UPDATED: 2026-03-08 -->
**SubscriptionManager** (`core/SubscriptionManager.php`, `CMS\SubscriptionManager`)
- Aufgabe: Pläne/Bestellungen/Status prüfen.
- Wichtige Methoden: `instance()`, `getUserPlan($userId)`, `checkLimit($userId,$resource)`, `hasFeature($userId,$feature)`.

---

## Hilfsklassen & Contracts <!-- UPDATED: 2026-03-08 -->
- **Hooks/SEO/Redirects**: `Services\SEOService`, `Services\RedirectService` (Meta/Redirect-Tabellen & Rendering).
- **CookieConsentService** (`core/Services/CookieConsentService.php`): Banner/Consent-API, Public-Page-Renderer.
- **EditorService / EditorJsService / EditorJsRenderer**: WYSIWYG/Block-Rendering.
- **FeedService**: SimplePie RSS/Atom.
- **MessageService / MemberService / UserService / StatusService / DashboardService / LandingPageService / AnalyticsService / TrackingService / BackupService / SystemService / ThemeCustomizer / UpdateService / PdfService**: jeweilige Fachservices, als Singletons via Container registriert.
- **Contracts**: `core/Contracts/LoggerInterface.php` (PSR-3 ähnlich), `core/Contracts/CacheInterface.php` (falls vorhanden), werden von Logger/CacheManager erfüllt.

---

## Quick Reference (Grouped) <!-- UPDATED: 2026-03-08 -->
| Gruppe | Klassen (Pfad) | Kern-Methoden |
|---|---|---|
| Router | `core/Router.php` | `instance()`, `addRoute()`, `dispatch()` |
| Database | `core/Database.php` | `query()`, `execute()`, `get_row()`, `get_results()`, `insert()`, `update()` |
| Schema | `core/SchemaManager.php`, `core/MigrationManager.php` | `createTables()`, `run()` |
| Auth/Security | `core/Auth.php`, `core/Security.php`, `core/Totp.php` | `login()`, `logout()`, `register()`, `verifyToken()`, `generateToken()`, `verifyCode()` |
| Hooks/Plugins | `core/Hooks.php`, `core/PluginManager.php` | `addAction()`, `doAction()`, `addFilter()`, `applyFilters()`, `loadPlugins()` |
| Themes/Templates | `core/ThemeManager.php`, `core/PageManager.php` | `loadTheme()`, `renderTemplate()`, `getPageBySlug()` |
| Cache | `core/CacheManager.php` | `get()`, `set()`, `remember()`, `delete()`, `clear()` |
| Mail | `core/Services/MailService.php`, `MailQueueService.php`, `MailLogService.php`, `AzureMailTokenProvider.php` | `send()`, `enqueue()`, `getRecent()`, `getAccessToken()` |
| Upload/Assets | `core/Services/FileUploadService.php`, `ImageService.php`, Autoload in `CMS/assets/autoload.php` | `handleUploadRequest()`, `resize()` |
| Search | `core/Services/SearchService.php` | `search()`, `indexContent()`, `reindexAll()` |
| Translation | `core/Services/TranslationService.php` | `setLocale()`, `trans()`, `addResource()` |
| API | `core/Api.php` | `handleRequest()` |
| Logging | `core/Logger.php`, `core/AuditLogger.php` | `log()`, `withChannel()`, `log()` (audit) |
| Subscription | `core/SubscriptionManager.php` | `getUserPlan()`, `checkLimit()`, `hasFeature()` |
// oder WordPress-Stil:
$db = CMS\Database::get_instance();
```

**Wichtige Methoden:**

| Methode | Rückgabe | Beschreibung |
|---------|----------|--------------|
| `instance()` | `Database` | Singleton-Instanz |
| `prepare(string $sql)` | `PDOStatement` | Prepared Statement erstellen |
| `query(string $sql)` | `PDOStatement` | Direktes Query (nur für SELECT ohne Parameter) |
| `insert(string $table, array $data)` | `int` | Zeile einfügen, gibt Insert-ID zurück |
| `update(string $table, array $data, array $where)` | `int` | Zeilen aktualisieren |
| `delete(string $table, array $where)` | `int` | Zeilen löschen |
| `get_row(string $sql, array $params)` | `object\|null` | Eine Zeile lesen |
| `get_results(string $sql, array $params)` | `array` | Mehrere Zeilen lesen |
| `get_var(string $sql, array $params)` | `mixed` | Einzelnen Wert lesen |
| `getPrefix()` | `string` | Tabellen-Prefix (`cms_`) |
| `last_insert_id()` | `int` | Letzte Insert-ID |

**Verwendungsbeispiele:**

```php
$db = CMS\Database::instance();

// Einzelne Zeile lesen
$user = $db->get_row(
    "SELECT * FROM {$db->getPrefix()}users WHERE id = ?",
    [$userId]
);

// Mehrere Zeilen
$posts = $db->get_results(
    "SELECT * FROM {$db->getPrefix()}posts WHERE status = ? ORDER BY created_at DESC",
    ['published']
);

// Einfügen
$newId = $db->insert('cms_posts', [
    'title'   => 'Mein Post',
    'content' => 'Inhalt...',
    'user_id' => $userId,
    'status'  => 'draft',
]);

// Aktualisieren
$db->update(
    'cms_posts',
    ['status' => 'published'],
    ['id' => $postId]
);

// Prepared Statement (für komplexe Queries)
$stmt = $db->prepare(
    "SELECT * FROM {$db->getPrefix()}users WHERE role = ? AND active = ?"
);
$stmt->execute(['member', 1]);
$members = $stmt->fetchAll(PDO::FETCH_OBJ);
```

**Tabellen-Prefix:**  
Alle CMS-Tabellen nutzen das Prefix `cms_`. Immer `$db->getPrefix()` nutzen!

---

## 3. Security

**Datei:** `core/Security.php`  
**Namespace:** `CMS`  
**Aufgabe:** CSRF-Schutz, XSS-Prävention, Input-Sanitization, Rate-Limiting.

```php
$security = CMS\Security::instance();
```

**Wichtige Methoden:**

| Methode | Rückgabe | Beschreibung |
|---------|----------|--------------|
| `init()` | `void` | Security-Header setzen, Session starten |
| `generateNonce(string $action)` | `string` | CSRF-Token erzeugen |
| `verifyNonce(string $nonce, string $action)` | `bool` | CSRF-Token prüfen |
| `sanitize(string $input)` | `string` | HTML-Tags entfernen |
| `sanitizeHtml(string $input)` | `string` | Nur sichere HTML-Tags erlauben |
| `hashPassword(string $password)` | `string` | Sicherer Password-Hash (bcrypt) |
| `verifyPassword(string $password, string $hash)` | `bool` | Passwort prüfen |
| `checkRateLimit(string $key, int $max, int $window)` | `bool` | Rate-Limit prüfen |
| `getClientIp()` | `string` | IP-Adresse des Clients |
| `escapeOutput(string $data)` | `string` | HTML-Ausgabe escapen |

**CSRF-Schutz in Formularen:**

```php
// In einem Formular (Ausgabe):
$security = CMS\Security::instance();
$nonce = $security->generateNonce('save_profile');
echo '<input type="hidden" name="_nonce" value="' . $nonce . '">';

// Beim Verarbeiten (Eingabe prüfen):
if (!$security->verifyNonce($_POST['_nonce'] ?? '', 'save_profile')) {
    http_response_code(403);
    die('Sicherheitscheck fehlgeschlagen');
}
```

**Rate Limiting:**

```php
// Max. 5 Login-Versuche in 5 Minuten
if (!$security->checkRateLimit('login_' . $security->getClientIp(), 5, 300)) {
    die('Zu viele Versuche. Bitte 5 Minuten warten.');
}
```

---

## 4. Auth

**Datei:** `core/Auth.php`  
**Namespace:** `CMS`  
**Aufgabe:** Benutzer-Authentifizierung, Session-Verwaltung, Rechte-Prüfung.

```php
$auth = CMS\Auth::instance();
```

**Wichtige Methoden:**

| Methode | Rückgabe | Beschreibung |
|---------|----------|--------------|
| `login(string $user, string $pass)` | `bool\|string` | Login – true oder Fehlermeldung |
| `logout()` | `void` | Session beenden |
| `isLoggedIn()` | `bool` | Ist User eingeloggt? |
| `isAdmin()` | `bool` | Hat User Admin-Rechte? |
| `getCurrentUser()` | `object\|null` | Aktuell eingeloggter User |
| `getUserById(int $id)` | `object\|null` | User nach ID laden |
| `hasRole(string $role)` | `bool` | Hat User bestimmte Rolle? |
| `register(array $data)` | `int\|string` | Neuen User anlegen |

**Verwendungsbeispiele:**

```php
$auth = CMS\Auth::instance();

// Login-Check am Anfang jeder geschützten Seite
if (!$auth->isLoggedIn()) {
    header('Location: /login');
    exit;
}

// Admin-Check
if (!$auth->isAdmin()) {
    header('Location: /');
    exit;
}

// Aktuellen User holen
$user = $auth->getCurrentUser();
echo 'Hallo, ' . htmlspecialchars($user->username);

// Manueller Login
$result = $auth->login('username', 'password');
if ($result === true) {
    header('Location: /member');
} else {
    echo 'Fehler: ' . $result; // Fehlermeldung
}
```

**Rollen:**
- `admin` – Voller Zugriff auf alle Funktionen
- `member` – Normales Mitglied, Zugriff auf Member-Bereich
- `subscriber` – Einfacher Leser

---

## 5. Router

**Datei:** `core/Router.php`  
**Namespace:** `CMS`  
**Aufgabe:** URL-Routing – mappt URLs auf Controller/Callbacks.

```php
$router = CMS\Router::instance();
```

**Wichtige Methoden:**

| Methode | Rückgabe | Beschreibung |
|---------|----------|--------------|
| `addRoute(string $method, string $path, callable $callback)` | `void` | Route registrieren |
| `dispatch()` | `void` | Aktuelle URL auflösen und Handler aufrufen |
| `redirect(string $url, int $code)` | `void` | HTTP-Redirect |
| `current()` | `string` | Aktuelle URL |

**Routen in Plugins registrieren:**

```php
// In eurem Plugin:
CMS\Hooks::addAction('routes_registered', function() {
    $router = CMS\Router::instance();
    $router->addRoute('GET', '/mein-plugin', function() {
        // Template rendern
        include PLUGIN_PATH . 'mein-plugin/templates/index.php';
    });
    $router->addRoute('POST', '/mein-plugin/save', function() {
        // Daten verarbeiten
    });
});
```

---

## 6. Hooks

**Datei:** `core/Hooks.php`  
**Namespace:** `CMS`  
**Aufgabe:** WordPress-ähnliches Action/Filter-System für Plugin-Erweiterbarkeit.

```php
// Statische Klasse – kein instance() nötig!
CMS\Hooks::addAction(...);
CMS\Hooks::addFilter(...);
```

**Alle Methoden:**

| Methode | Beschreibung |
|---------|--------------|
| `addAction(string $tag, callable $cb, int $priority = 10)` | Action-Hook registrieren |
| `doAction(string $tag, ...$args)` | Action-Hook feuern |
| `addFilter(string $tag, callable $cb, int $priority = 10)` | Filter-Hook registrieren |
| `applyFilters(string $tag, mixed $value, ...$args)` | Filter anwenden und Wert zurückgeben |
| `removeAction(string $tag, callable $cb)` | Action-Hook entfernen |
| `removeFilter(string $tag, callable $cb)` | Filter-Hook entfernen |
| `hasAction(string $tag)` | Prüfen ob Hook registriert |

**Vollständiges Beispiel:**

```php
// In eurem Plugin (mein-plugin.php):

// 1. Action: Code ausführen wenn Event eintritt
CMS\Hooks::addAction('user_registered', function(int $userId) {
    // Willkommens-E-Mail senden
    $user = CMS\Database::instance()->get_row(
        "SELECT * FROM cms_users WHERE id = ?", [$userId]
    );
    mail($user->email, 'Willkommen!', 'Danke für deine Registrierung.');
}, 10);

// 2. Filter: Wert verändern
CMS\Hooks::addFilter('page_title', function(string $title) {
    return $title . ' | 365 Network';
}, 10);

// 3. Filter mit mehreren Parametern
CMS\Hooks::addFilter('post_content', function(string $content, int $postId) {
    // Shortcodes ersetzen
    return str_replace('[datum]', date('d.m.Y'), $content);
}, 10);
```

→ Vollständige Hook-Liste: [HOOKS-REFERENCE.md](HOOKS-REFERENCE.md)

---

## 7. PluginManager

**Datei:** `core/PluginManager.php`  
**Namespace:** `CMS`  
**Aufgabe:** Verwaltet das Laden, Aktivieren und Deaktivieren von Plugins.

```php
$pm = CMS\PluginManager::instance();
```

**Wichtige Methoden:**

| Methode | Rückgabe | Beschreibung |
|---------|----------|--------------|
| `loadPlugins()` | `void` | Alle aktiven Plugins laden |
| `getActivePlugins()` | `array` | Liste aktiver Plugin-Slugs |
| `isPluginActive(string $slug)` | `bool` | Ist Plugin aktiv? |
| `activatePlugin(string $slug)` | `bool` | Plugin aktivieren |
| `deactivatePlugin(string $slug)` | `bool` | Plugin deaktivieren |
| `getPluginInfo(string $slug)` | `array\|null` | Plugin-Metadaten |

---

## 8. ThemeManager

**Datei:** `core/ThemeManager.php`  
**Namespace:** `CMS`  
**Aufgabe:** Lädt das aktive Theme und rendert Templates.

```php
$tm = CMS\ThemeManager::instance();
```

**Wichtige Methoden:**

| Methode | Rückgabe | Beschreibung |
|---------|----------|--------------|
| `getActiveTheme()` | `string` | Slug des aktiven Themes |
| `getThemePath()` | `string` | Absoluter Pfad zum aktiven Theme |
| `render(string $template, array $data)` | `void` | Template rendern |
| `getTemplatePart(string $part)` | `string` | Teil-Template laden |
| `getSetting(string $key, mixed $default)` | `mixed` | Theme-Einstellung lesen |
| `getAssetUrl(string $file)` | `string` | URL zu Theme-Asset |
| `getAllThemes()` | `array` | Alle installierten Themes |

---

## 9. CacheManager

**Datei:** `core/CacheManager.php`  
**Namespace:** `CMS`  
**Aufgabe:** Datei-basiertes Caching für DB-Queries und Template-Fragmente.

```php
$cache = CMS\CacheManager::instance();
```

**Wichtige Methoden:**

| Methode | Rückgabe | Beschreibung |
|---------|----------|--------------|
| `get(string $key)` | `mixed\|null` | Cache-Eintrag lesen (null = kein Cache) |
| `set(string $key, mixed $data, int $ttl = 3600)` | `bool` | Cache-Eintrag speichern |
| `delete(string $key)` | `bool` | Cache-Eintrag löschen |
| `flush()` | `bool` | Gesamten Cache leeren |
| `remember(string $key, callable $fn, int $ttl)` | `mixed` | Cache-oder-Callback-Pattern |

**Cache-oder-Callback Pattern (empfohlen):**

```php
$cache = CMS\CacheManager::instance();

$topPosts = $cache->remember('top_posts', function() {
    // Diese DB-Query wird nur ausgeführt, wenn kein Cache existiert
    $db = CMS\Database::instance();
    return $db->get_results(
        "SELECT * FROM cms_posts ORDER BY views DESC LIMIT 10",
        []
    );
}, 1800); // 30 Minuten cachen
```

---

## 10. PageManager

**Datei:** `core/PageManager.php`  
**Namespace:** `CMS`  
**Aufgabe:** Verwaltet statische Seiten, Meta-Tags und Head-Ausgabe.

```php
$pm = CMS\PageManager::instance();
```

**Wichtige Methoden:**

| Methode | Rückgabe | Beschreibung |
|---------|----------|--------------|
| `getPage(int $id)` | `object\|null` | Seite nach ID |
| `getPageBySlug(string $slug)` | `object\|null` | Seite nach URL-Slug |
| `setTitle(string $title)` | `void` | HTML-Title setzen |
| `setMeta(string $name, string $content)` | `void` | Meta-Tag setzen |
| `getMeta(string $name)` | `string` | Meta-Tag lesen |
| `renderHead()` | `void` | `<head>`-Bereich ausgeben |

---

## 11. SubscriptionManager

**Datei:** `core/SubscriptionManager.php`  
**Namespace:** `CMS`  
**Aufgabe:** Abo-System – Pläne, Benutzer-Abos, Feature-Gating.

```php
$sm = CMS\SubscriptionManager::instance();
```

**Wichtige Methoden:**

| Methode | Rückgabe | Beschreibung |
|---------|----------|--------------|
| `getUserSubscription(int $userId)` | `object\|null` | Aktives Abo des Users |
| `hasFeature(int $userId, string $feature)` | `bool` | Darf User Feature nutzen? |
| `getPlans()` | `array` | Alle Abo-Pläne |
| `subscribe(int $userId, int $planId)` | `bool` | User subscriben |
| `cancelSubscription(int $userId)` | `bool` | Abo kündigen |
| `isExpired(int $userId)` | `bool` | Ist Abo abgelaufen? |

```php
// Feature-Prüfung im Template:
$sm = CMS\SubscriptionManager::instance();
if ($sm->hasFeature($user->id, 'premium_content')) {
    echo $premiumContent;
} else {
    echo '<a href="/subscribe">Premium-Zugang freischalten</a>';
}
```

---

## 12. Api

**Datei:** `core/Api.php`  
**Namespace:** `CMS`  
**Aufgabe:** REST API v1 Controller. Handhabt `/api/v1/{endpoint}/{id}` mit Rate-Limiting (60 req/60 s pro IP).

```php
$api = CMS\Api::instance();
```

**Wichtige Methoden:**

| Methode | Rückgabe | Beschreibung |
|---------|----------|--------------|
| `instance()` | `Api` | Singleton-Instanz |
| `handleRequest(string $endpoint, ?string $id)` | `void` | Request an internen Handler dispatchen |

Private Handler: `handlePages()`, `handleUsers()`, `sendResponse()`, `sendError()`.

---

## 13. Debug

**Datei:** `core/Debug.php`  
**Namespace:** `CMS`  
**Aufgabe:** Debug-System für Admin-Bereiche. Zeigt Fehler, Timing und Speicherverbrauch. Schreibt nach `logs/debug-YYYY-MM-DD.log`.

> Rein **statische** Klasse – kein `instance()`.

```php
CMS\Debug::enable();
CMS\Debug::log('Query dauert lange', 'warning', $queryData);
```

**Alle Methoden (static):**

| Methode | Rückgabe | Beschreibung |
|---------|----------|--------------|
| `enable(bool $enable = true)` | `void` | Debug-Modus an/aus |
| `isEnabled()` | `bool` | Ist Debug aktiv? |
| `startTimer()` | `void` | Zeitmessung starten |
| `getElapsedTime()` | `float` | Vergangene Zeit in Sekunden |
| `log(string $message, string $type, mixed $data)` | `void` | Nachricht loggen |

---

## 14. WP_Error

**Datei:** `core/WP_Error.php`  
**Namespace:** `CMS`  
**Aufgabe:** WordPress-kompatible Fehlerklasse – einfacher Error-Container mit Code, Message und Data.

```php
$error = new CMS\WP_Error('not_found', 'Seite nicht gefunden.');
if (is_wp_error($error)) {
    echo $error->get_error_message();
}
```

**Methoden:**

| Methode | Rückgabe | Beschreibung |
|---------|----------|--------------|
| `__construct($code, $message, $data)` | — | Fehler erstellen |
| `get_error_code()` | `string` | Fehlercode lesen |
| `get_error_message()` | `string` | Fehlermeldung lesen |
| `get_error_data()` | `array` | Zusatzdaten lesen |
| `add(string $code, string $message, mixed $data)` | `void` | Weiteren Fehler hinzufügen |

**Globale Funktion:** `is_wp_error(mixed $thing): bool`

---

## 15. AuditLogger

**Datei:** `core/AuditLogger.php`  
**Namespace:** `CMS`  
**Aufgabe:** Zentrales Sicherheits-Audit-Log – protokolliert sicherheitsrelevante Aktionen (Theme-Wechsel, Plugin-Aktivierung, Admin-Login, Rollenwechsel) in `{prefix}audit_log`.

```php
$audit = CMS\AuditLogger::instance();
$audit->log('security', 'ip_blocked', '5 fehlgeschlagene Logins', 'ip', null, ['ip' => $ip]);
```

**Kategorien-Konstanten:** `CAT_AUTH`, `CAT_THEME`, `CAT_PLUGIN`, `CAT_USER`, `CAT_SETTING`, `CAT_MEDIA`, `CAT_SYSTEM`, `CAT_SECURITY`

| Methode | Rückgabe | Beschreibung |
|---------|----------|--------------|
| `log(string $category, string $action, string $desc, ...)` | `void` | Audit-Eintrag schreiben |
| `themeSwitch(string $from, string $to)` | `void` | Theme-Wechsel protokollieren |
| `themeDelete(string $folder)` | `void` | Theme-Löschung protokollieren |
| `themeFileEdit(string $theme, string $file)` | `void` | Theme-Datei-Bearbeitung |
| `pluginAction(string $action, string $slug)` | `void` | Plugin aktivieren/deaktivieren |
| `loginSuccess(string $username)` | `void` | Erfolgreicher Login |
| `loginFailed(string $username)` | `void` | Fehlgeschlagener Login |
| `userRoleChange(int $userId, string $old, string $new)` | `void` | Rollenwechsel |
| `backupAction(string $action, string $file)` | `void` | Backup-Aktion |
| `getRecent(int $limit, string $category)` | `array` | Letzte Einträge abrufen |

---

## 16. Container

**Datei:** `core/Container.php`  
**Namespace:** `CMS`  
**Aufgabe:** Einfacher Dependency-Injection-Container mit Factory-Bindings, Singletons und Lazy-Auflösung.

```php
$container = CMS\Container::instance();
$container->singleton('mailer', fn() => new MailService());
$mailer = $container->make('mailer');
```

| Methode | Rückgabe | Beschreibung |
|---------|----------|--------------|
| `bind(string $abstract, Closure $factory)` | `void` | Factory registrieren (jeder Aufruf = neue Instanz) |
| `singleton(string $abstract, Closure $factory)` | `void` | Factory als Singleton registrieren |
| `bindInstance(string $abstract, mixed $resolved)` | `void` | Fertige Instanz direkt binden |
| `make(string $abstract)` | `mixed` | Auflösen (Factory oder Singleton) |
| `has(string $abstract)` | `bool` | Prüfen ob Binding existiert |
| `forget(string $abstract)` | `void` | Binding entfernen |
| `flush()` | `void` | Alle Bindings leeren |

---

## 17. Logger

**Datei:** `core/Logger.php`  
**Namespace:** `CMS`  
**Implements:** `CMS\Contracts\LoggerInterface`  
**Aufgabe:** PSR-3-kompatibles Logging-System mit täglicher Dateirotation, Channel-Support, Sensitive-Data-Filterung und automatischer AuditLogger-Spiegelung ab CRITICAL.

```php
$log = CMS\Logger::instance();           // Standard-Channel 'cms'
$log->info('Seite gespeichert', ['id' => 42]);

$pluginLog = CMS\Logger::instance('plugins');  // Eigener Channel
$pluginLog->warning('Plugin veraltet', ['slug' => 'cms-forum']);
```

**Log-Level:** `EMERGENCY` > `ALERT` > `CRITICAL` > `ERROR` > `WARNING` > `NOTICE` > `INFO` > `DEBUG`

| Methode | Rückgabe | Beschreibung |
|---------|----------|--------------|
| `instance(string $channel = 'cms')` | `Logger` | Singleton pro Channel |
| `emergency/alert/critical/error/warning/notice/info/debug()` | `void` | Nachricht im jeweiligen Level loggen |
| `log(string $level, string $message, array $context)` | `void` | Nachricht mit beliebigem Level |
| `isLevelEnabled(string $level)` | `bool` | Ist Log-Level aktiv? |
| `withChannel(string $channel)` | `Logger` | Geklonte Instanz mit anderem Channel |

---

## 18. SchemaManager

**Datei:** `core/SchemaManager.php`  
**Namespace:** `CMS`  
**Aufgabe:** Erstellt alle 30 CMS-Basis-Tabellen via `CREATE TABLE IF NOT EXISTS`. Legt Standard-Admin-Account an. Idempotent über Flag-Datei.

> Kein Singleton – wird mit `new SchemaManager($db)` instanziiert.

**Schema-Version:** `v10`

```php
$schema = new CMS\SchemaManager(CMS\Database::instance());
$schema->createTables();
```

| Methode | Rückgabe | Beschreibung |
|---------|----------|--------------|
| `createTables()` | `void` | Alle 30 Tabellen erstellen |
| `getFlagFile()` | `string` | Pfad zur Flag-Datei |
| `clearFlag()` | `void` | Flag zurücksetzen (erzwingt Neu-Erstellung) |

---

## 19. MigrationManager

**Datei:** `core/MigrationManager.php`  
**Namespace:** `CMS`  
**Aufgabe:** Inkrementelle Spalten-Migrationen (ALTER TABLE). Wird nach SchemaManager ausgeführt.

> Kein Singleton – wird mit `new MigrationManager($db)` instanziiert.

**Schema-Version:** `v7` (gespeichert in `cms_settings` als `db_schema_version`)

```php
$migrator = new CMS\MigrationManager(CMS\Database::instance());
$migrator->run();
```

| Methode | Rückgabe | Beschreibung |
|---------|----------|--------------|
| `run()` | `void` | Alle ausstehenden Migrationen ausführen |
| `repairTables()` | `void` | Fehlende Spalten nachträglich ergänzen |

---

## 20. TableOfContents

**Datei:** `core/TableOfContents.php`  
**Namespace:** `CMS`  
**Aufgabe:** Parst HTML-Content, fügt Anker-IDs in Überschriften ein und erzeugt ein TOC-Widget. Unterstützt `[cms_toc]`-Shortcode und Auto-Insert.

```php
$toc = CMS\TableOfContents::instance();
$result = $toc->process($htmlContent, 'post', $postId);
echo $result['toc'];     // TOC-HTML
echo $result['content'];  // Content mit Anker-IDs
```

| Methode | Rückgabe | Beschreibung |
|---------|----------|--------------|
| `getSetting(string $key, mixed $default)` | `mixed` | TOC-Einstellung aus DB |
| `process(string $content, string $type, int $id)` | `array` | Content verarbeiten, TOC generieren |
| `renderFromContent(string $content)` | `string` | TOC direkt aus Content rendern |

---

## 21. Totp

**Datei:** `core/Totp.php`  
**Namespace:** `CMS`  
**Aufgabe:** Pure-PHP TOTP-Implementierung (RFC 6238/4226/4648). Kompatibel mit Google Authenticator, Authy, MS Authenticator. 30-Sekunden-Intervall, 6 Ziffern, ±1 Fenster.

```php
$totp = CMS\Totp::instance();
$secret = $totp->generateSecret();
$uri = $totp->getOtpAuthUri($secret, 'user@example.com', '365CMS');

// Prüfung
if ($totp->verifyCode($secret, $_POST['code'])) {
    echo '2FA erfolgreich';
}
```

| Methode | Rückgabe | Beschreibung |
|---------|----------|--------------|
| `generateSecret()` | `string` | Base32-Secret erzeugen (32 Zeichen) |
| `generateCode(string $secret, ?int $ts)` | `string` | 6-stelligen Code generieren |
| `verifyCode(string $secret, string $code)` | `bool` | Code validieren (±1 Zeitfenster) |
| `getOtpAuthUri(string $secret, string $account, string $issuer)` | `string` | OTP-Auth-URI für QR-Code |
| `base32Encode(string $data)` | `string` | Base32-Kodierung |
| `base32Decode(string $data)` | `string` | Base32-Dekodierung |

---

## 22. Contracts (Interfaces)

**Verzeichnis:** `core/Contracts/`

Drei Interfaces für Dependency Injection und Testbarkeit:

| Interface | Datei | Zweck |
|-----------|-------|-------|
| `CacheInterface` | `CacheInterface.php` | PSR-16-ähnlicher Cache-Contract |
| `DatabaseInterface` | `DatabaseInterface.php` | Datenbank-Abstraktionsschicht |
| `LoggerInterface` | `LoggerInterface.php` | PSR-3-kompatibler Logger |

---

## 23. Member – PluginDashboardRegistry

**Datei:** `core/Member/PluginDashboardRegistry.php`  
**Namespace:** `CMS\Member`  
**Aufgabe:** Zentrale Registrierung für Plugin-Bereiche im Member-Dashboard. Plugins registrieren sich via `member_dashboard_init`-Hook.

```php
$registry = CMS\Member\PluginDashboardRegistry::instance();

// Plugin registriert sich:
$registry->register([
    'slug'      => 'mein-plugin',
    'label'     => 'Mein Plugin',
    'icon'      => 'fas fa-star',
    'callback'  => [$this, 'renderMemberPage'],
]);
```

| Methode | Rückgabe | Beschreibung |
|---------|----------|--------------|
| `register(array $config)` | `void` | Plugin-Bereich registrieren |
| `getSection(string $slug)` | `?array` | Registrierten Bereich abrufen |
| `getAll()` | `array` | Alle Registrierungen |
| `getMenuItems(string $current)` | `array` | Menüeinträge für Sidebar |
| `getDashboardWidgets(object $user)` | `array` | Dashboard-Widgets sammeln |
| `handleRoute(string $slug, array $params)` | `void` | Route an Plugin dispatchen |
