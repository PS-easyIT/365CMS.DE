# Core-Klassen Referenz

**Namespace:** `CMS\` | **PHP:** 8.x | **Stand:** 2026-02-18

---

## Database

**Datei:** `core/Database.php` · **617 Zeilen** · **Singleton**

PDO-Wrapper mit prepared statements, Tabellen-Prefix-Management und Tabellen-Erstellung.

### Eigenschaften

| Property | Typ | Beschreibung |
|----------|-----|-------------|
| `$pdo` | `?PDO` | PDO-Verbindung |
| `$prefix` | `string` | Tabellenpräfix (Standard: `cms_`) |
| `$last_error` | `string` | Letzter SQL-Fehler |

### Wichtige Methoden

| Methode | Rückgabe | Beschreibung |
|---------|----------|-------------|
| `instance()` | `self` | Singleton-Instanz |
| `getPrefix()` | `string` | Tabellenpräfix (kanonisch) |
| `prefix()` ⚠️ | `string` | **@deprecated** → `getPrefix()` verwenden |
| `prepare(string $sql)` | `PDOStatement` | Statement vorbereiten |
| `execute(string $sql, array $params)` | `PDOStatement` | Statement ausführen |
| `insert(string $table, array $data)` | `int\|bool` | Fügt Prefix automatisch hinzu! |
| `update(string $table, array $data, array $where)` | `bool` | Fügt Prefix automatisch hinzu! |
| `delete(string $table, array $where)` | `bool` | Fügt Prefix automatisch hinzu! |
| `get_row(string $query, array $params)` | `?object` | Einzelne Zeile (kein Prefix!) |
| `get_var(string $query, array $params)` | `mixed` | Einzelner Wert (kein Prefix!) |
| `get_results(string $query, array $params)` | `array` | Mehrere Zeilen (kein Prefix!) |
| `repairTables()` | `void` | DB-Tabellen reparieren (Admin-Tool) |

> ⚠️ **Wichtig:** `insert()`, `update()`, `delete()` fügen den Prefix **automatisch** hinzu.  
> `get_row()`, `get_var()`, `get_results()` tun das **nicht** – hier muss `{$db->getPrefix()}table` verwendet werden.

### Performance-Optimierung

`createTables()` läuft **nicht** bei jedem Request. Eine Flag-Datei `cache/db_schema_v2.flag` steuert die einmalige Ausführung. `repairTables()` löscht die Flag-Datei vor der Ausführung.

---

## Auth

**Datei:** `core/Auth.php` · **~270 Zeilen** · **Singleton**

Authentifizierung: Login, Registrierung, Session-Management, Rollen-Prüfung.

### Methoden

| Methode | Rückgabe | Beschreibung |
|---------|----------|-------------|
| `login(string $username, string $password)` | `bool\|string` | Login mit Rate-Limiting |
| `register(array $data)` | `bool\|string` | Benutzer registrieren |
| `logout()` | `void` | Session + Cookie sicher löschen |
| `isLoggedIn()` | `bool` | Static: Prüft ob User eingeloggt |
| `isAdmin()` | `bool` | Static: Prüft Admin-Rolle |
| `hasRole(string $role)` | `bool` | Static: Rollenprüfung |
| `hasCapability(string $cap)` | `bool` | Erweiterte Fähigkeitenprüfung |
| `getCurrentUser()` | `?object` | Static: Aktueller User oder null |
| `currentUser()` | `?object` | Instance-Methode (identisch) |

### Session-Sicherheit

- `session_regenerate_id(true)` nach erfolgreichem Login
- `logout()`: `$_SESSION = []` → Cookie explizit abgelaufen setzen → `session_destroy()`
- Rate-Limiting via `Security::checkRateLimit()`

### Rollenhierarchie

| Rolle | Fähigkeiten |
|-------|-------------|
| `admin` | Alle |
| `editor` | read, edit_profile, view_content, edit_posts, publish_posts, manage_media |
| `author` | read, edit_profile, view_content, edit_own_posts |
| `member` | read, edit_profile, view_content |

---

## Security

**Datei:** `core/Security.php` · **258 Zeilen** · **Singleton**

CSRF-Schutz, XSS-Prevention, Input-Sanitisierung, Rate-Limiting.

### Methoden

| Methode | Art | Beschreibung |
|---------|-----|-------------|
| `init()` | Instance | Security-Headers + Session starten |
| `generateToken(string $action)` | Instance | CSRF-Token erzeugen |
| `verifyToken(string $token, string $action)` | Instance | CSRF-Token prüfen (1h Ablauf) |
| `generateNonceField(string $action)` | Instance | Hidden-Input ausgeben (WP-Kompatibilität) |
| `verifyNonce(string $token, string $action)` | Instance | Alias für verifyToken |
| `sanitize(string $input, string $type)` | Static | Typen: text, email, url, int, html, username |
| `escape(string $output)` | Static | htmlspecialchars UTF-8 |
| `validateEmail(string $email)` | Static | E-Mail validieren |
| `hashPassword(string $password)` | Static | bcrypt cost=12 |
| `verifyPassword(string $password, string $hash)` | Static | password_verify |
| `checkRateLimit(string $id, int $max, int $window)` | Static | Session-basiertes Rate-Limiting |
| `getClientIp()` | Static | Validierte Client-IP |

### Gesendete Security-Headers

```
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
Content-Security-Policy: default-src 'self'; ... (nur im Production-Modus)
```

---

## Hooks

**Datei:** `core/Hooks.php` · **~120 Zeilen** · **Rein-statisch**

WordPress-ähnliches Action/Filter-System.

### Methoden

| Methode | Beschreibung |
|---------|-------------|
| `addAction(string $tag, callable $cb, int $priority = 10)` | Action registrieren |
| `doAction(string $tag, ...$args)` | Action ausführen |
| `removeAction(string $tag, callable $cb, int $priority = 10)` | Action entfernen |
| `addFilter(string $tag, callable $cb, int $priority = 10)` | Filter registrieren |
| `applyFilters(string $tag, $value, ...$args)` | Filter anwenden |
| `removeFilter(string $tag, callable $cb, int $priority = 10)` | Filter entfernen |

### System-Hooks (Bootstrap)

| Hook | Typ | Zeitpunkt |
|------|-----|-----------|
| `cms_init` | Action | Nach Core-Init |
| `cms_before_route` | Action | Vor Routing |
| `register_routes` | Action | Router registriert Custom-Routes |
| `cms_after_route` | Action | Nach Routing |
| `theme_loaded` | Action | Nach Theme-Laden |
| `plugins_loaded` | Action | Alle Plugins geladen |
| `before_render` / `after_render` | Action | Theme-Rendering |

---

## Router

**Datei:** `core/Router.php` · **394 Zeilen** · **Singleton**

URL-Routing mit statischen und dynamischen Pattern-Routen.

### Standard-Routen

| Method | Route | Handler |
|--------|-------|---------|
| GET | `/` | renderHome |
| GET/POST | `/login` | renderLogin / handleLogin |
| GET/POST | `/register` | renderRegister / handleRegister |
| GET | `/logout` | handleLogout |
| GET | `/member` | renderMember (Auth-Check) |
| GET/POST | `/admin` | renderAdmin (Admin-Check) |
| GET/POST | `/admin/:page` | renderAdminPage (Admin-Check) |
| GET | `/api/v1/status` | JSON-Status |
| GET | `/api/v1/pages` | Seiten-API |
| GET | `/search` | Suche |
| GET | `/sitemap.xml` | SEOService::generateSitemap() |
| GET | `/robots.txt` | SEOService::generateRobotsTxt() |

### Custom-Routen (Plugins)

Via `Hooks::addAction('register_routes', function() use ($router) { ... })`.

---

## Bootstrap

**Datei:** `core/Bootstrap.php` · **150 Zeilen** · **Singleton**

System-Initialisierung in der richtigen Reihenfolge.

### Initialisierungsreihenfolge

1. `Security::instance()->init()` – Headers + Session
2. `Database::instance()` – DB-Verbindung + createTables
3. `Auth::instance()` – Session-User laden
4. `ThemeManager::instance()->loadTheme()` – Theme laden
5. `PluginManager::instance()->loadPlugins()` – Plugins laden
6. `Hooks::doAction('cms_init')`
7. `Router::instance()->dispatch()` – Request verarbeiten

---

## CacheManager

**Datei:** `core/CacheManager.php` · **262 Zeilen** · **Singleton**

Mehrstufiges Caching: Datei-Cache + OPcache + APCu + LiteSpeed-Integration.

### Methoden

| Methode | Beschreibung |
|---------|-------------|
| `get(string $key)` | Aus Cache lesen (null bei Miss/Expiry) |
| `set(string $key, mixed $value, int $ttl = 3600)` | In Cache schreiben |
| `delete(string $key)` | Einzelnen Eintrag löschen |
| `flush()` | Datei-Cache komplett leeren + LiteSpeed-Purge |
| `clearAll()` | Alle Cache-Typen leeren (Report zurückgeven) |
| `getStatus()` | Status aller Cache-Typen |
| `setCacheHeaders(int $ttl, bool $private)` | HTTP-Cache-Header setzen |

---

## PageManager

**Datei:** `core/PageManager.php` · **~180 Zeilen** · **Singleton**

CRUD für Seiten, Slug-Generierung, Volltextsuche.

### Methoden

| Methode | Beschreibung |
|---------|-------------|
| `createPage(string $title, string $content, string $status, int $authorId)` | Seite erstellen (eindeutiger Slug) |
| `updatePage(int $id, array $data)` | Felder: title, content, status, slug |
| `deletePage(int $id)` | Seite löschen |
| `getPage(int $id)` | Seite by ID (Array) |
| `getPageBySlug(string $slug)` | Seite by Slug (Array) |
| `listPages()` | Alle Seiten (Array) |
| `search(string $query)` | Volltextsuche (title + content, nur published) |

---

## PluginManager

**Datei:** `core/PluginManager.php` · **319 Zeilen** · **Singleton**

Laden, Aktivieren, Deaktivieren, Installieren und Löschen von Plugins.

### Plugin-Format

Plugin-Datei muss in `plugins/plugin-name/plugin-name.php` liegen mit Header-Kommentaren:

```php
/**
 * Plugin Name: Mein Plugin
 * Description: Kurze Beschreibung
 * Version: 1.0.0
 * Author: Name
 */
```

---

## ThemeManager

**Datei:** `core/ThemeManager.php` · **399 Zeilen** · **Singleton**

Theme-Laden, Template-Rendering, Custom-Styles, Tracking-Integration.

### Template-Hierarchie

1. `themes/{active-theme}/{template}.php`
2. `themes/{active-theme}/index.php`

### Rendering

```php
ThemeManager::instance()->render('home');
ThemeManager::instance()->render('page', ['page' => $pageArray]);
```

---

## SubscriptionManager

**Datei:** `core/SubscriptionManager.php` · **574 Zeilen** · **Singleton**

Verwaltet 6 vordefinierte Pläne (Free → Enterprise), User-Abos, Gruppen, Nutzungsgrenzen.

### Tabellen (werden bei erster Ausführung erstellt)

- `{prefix}subscription_plans` – Pakete
- `{prefix}user_subscriptions` – User-Abo-Zuweisungen
- `{prefix}user_groups` – Gruppen
- `{prefix}user_group_members` – Gruppen-Mitglieder
- `{prefix}subscription_usage` – Nutzungszähler

### Methoden

| Methode | Beschreibung |
|---------|-------------|
| `getUserSubscription(int $userId)` | Aktives Abo (mit Gruppen-Fallback und Free-Plan-Fallback) |
| `canAccessPlugin(int $userId, string $pluginSlug)` | Plugin-Zugriffsprüfung |
| `checkLimit(int $userId, string $resourceType)` | Limit-Prüfung (-1 = unbegrenzt) |
| `updateUsage(int $userId, string $resourceType, int $count)` | Nutzungszähler setzen |
| `assignSubscription(int $userId, int $planId, string $billingCycle)` | Abo zuweisen |
| `seedDefaultPlans()` | Standardpläne anlegen |

---

## Api

**Datei:** `core/Api.php` · **~130 Zeilen** · **Singleton**

REST API v1 Controller.

### Endpoints

| Route | Auth | Beschreibung |
|-------|------|-------------|
| `GET /api/v1/status` | Nein | { status: "ok", version: "2.0.0" } |
| `GET /api/v1/pages` | Login erforderlich | Suche `?q=query` |
| `GET /api/v1/pages/:slug` | Login erforderlich | Einzelne Seite |
| `GET /api/v1/users` | Admin erforderlich | User-Liste (max. 50) |
| `GET /api/v1/users/:id` | Admin erforderlich | Einzelner User |

---

## Debug

**Datei:** `core/Debug.php` · **232 Zeilen** · **Rein-statisch**

Debug-Logging für Admin-Bereiche. **Standard: deaktiviert.**

```php
// Aktivieren (typischerweise in config.php oder Bootstrap)
Debug::enable(CMS_DEBUG);

Debug::log('Schritt X abgeschlossen', 'info', $data);
Debug::success('Erfolgreich');
Debug::warning('Achtung: ...');
Debug::error('Fehler aufgetreten', $error);
Debug::exception($throwable, 'Kontext');

// HTML-Panel rendern (z.B. am Ende einer Admin-Seite)
echo Debug::renderHtml();

// AJAX-Response erweitern
$response = Debug::enhanceAjaxResponse($response);
```

---

## WP_Error

**Datei:** `core/WP_Error.php` · **103 Zeilen** · **Standalone-Klasse**

WordPress-kompatible Fehlerklasse. Ermöglicht die Nutzung von `is_wp_error()`.

```php
$result = someFunction();
if (is_wp_error($result)) {
    echo $result->get_error_message();
}
```

---

## autoload.php

**Datei:** `core/autoload.php` · **PSR-4 Autoloader**

Mappt Namespaces auf Verzeichnisse:

| Namespace | Verzeichnis |
|-----------|-------------|
| `CMS\` | `core/` |
| `CMS\Services\` | `core/Services/` |

> ⚠️ Security-Headers und `session_start()` werden **nicht** hier gesetzt.  
> Das übernimmt `Security::init()` im Bootstrap.
