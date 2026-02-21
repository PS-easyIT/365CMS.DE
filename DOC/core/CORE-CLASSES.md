# 365CMS – Core-Klassen Referenz

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026

Alle 11 Core-Klassen des 365CMS im Detail – Aufgaben, wichtigste Methoden und Verwendungsbeispiele.

---

## Inhaltsverzeichnis

1. [Bootstrap](#1-bootstrap)
2. [Database](#2-database)
3. [Security](#3-security)
4. [Auth](#4-auth)
5. [Router](#5-router)
6. [Hooks](#6-hooks)
7. [PluginManager](#7-pluginmanager)
8. [ThemeManager](#8-thememanager)
9. [CacheManager](#9-cachemanager)
10. [PageManager](#10-pagemanager)
11. [SubscriptionManager](#11-subscriptionmanager)

---

## 1. Bootstrap

**Datei:** `core/Bootstrap.php`  
**Namespace:** `CMS`  
**Aufgabe:** Orchestriert die Initialisierung aller anderen Core-Klassen.

```php
// Verwendung (wird automatisch in index.php aufgerufen)
$bootstrap = CMS\Bootstrap::instance();
```

**Wichtige Methoden:**

| Methode | Rückgabe | Beschreibung |
|---------|----------|--------------|
| `instance()` | `Bootstrap` | Singleton-Instanz holen |
| `loadDependencies()` | `void` | Lädt alle Core-PHP-Dateien |
| `initializeCore()` | `void` | Initialisiert DB, Security, Auth |
| `loadPlugins()` | `void` | Aktive Plugins laden |
| `loadTheme()` | `void` | Aktives Theme laden |
| `route()` | `void` | Request an Router übergeben |

**Lifecycle:**
1. `config.php` ist bereits geladen
2. `Bootstrap::instance()` startet den Prozess
3. `loadDependencies()` lädt alle Core-Klassen
4. `initializeCore()` verbindet DB, setzt Security-Header, startet Session
5. `loadPlugins()` lädt aktive Plugins aus DB
6. `loadTheme()` aktiviert das eingestellte Theme
7. `route()` entscheidet, welcher Controller antwortet

---

## 2. Database

**Datei:** `core/Database.php`  
**Namespace:** `CMS`  
**Aufgabe:** Sicherer PDO-Wrapper für alle Datenbankzugriffe.

```php
$db = CMS\Database::instance();
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

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
