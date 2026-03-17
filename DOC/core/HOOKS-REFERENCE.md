<!-- UPDATED: 2026-03-16 -->
# 365CMS – Hooks-Referenz
> **Stand:** 2026-03-16 | **Version:** 2.6.0 | **Status:** Aktuell

Vollständige Referenz des Action-/Filter-Systems (`CMS\Hooks`) für Plugins und Themes.
Das Hook-System ermöglicht die Erweiterung des CMS **ohne Core-Dateien zu verändern**.

---

## Inhaltsverzeichnis

1. [Grundkonzept](#1-grundkonzept)
2. [API-Funktionen](#2-api-funktionen)
3. [Core-Hooks](#3-core-hooks)
4. [Content-Hooks](#4-content-hooks)
5. [User-Hooks](#5-user-hooks)
6. [Admin-Hooks](#6-admin-hooks)
7. [Plugin-Hooks](#7-plugin-hooks)
8. [Theme-Hooks](#8-theme-hooks)
9. [Media-Hooks](#9-media-hooks)
10. [SEO-Hooks](#10-seo-hooks)
11. [Best Practices für eigene Hooks](#11-best-practices-für-eigene-hooks)

---

## 1. Grundkonzept

### Actions vs. Filters

Das Hook-System kennt zwei Typen:

| Merkmal | Action | Filter |
|---------|--------|--------|
| Zweck | Code bei Ereignis **ausführen** | Wert **verändern** und zurückgeben |
| Auslösung | `Hooks::doAction('name', ...)` | `Hooks::applyFilters('name', $value, ...)` |
| Registrierung | `Hooks::addAction('name', $cb)` | `Hooks::addFilter('name', $cb)` |
| Rückgabewert | keiner (`void`) | veränderter Wert (`mixed`) |

```php
// Action – wird ausgeführt, wenn das Ereignis eintritt
CMS\Hooks::addAction('user_registered', function (int $userId): void {
    // Willkommens-Mail senden …
});

// Filter – nimmt einen Wert, verändert ihn und gibt ihn zurück
CMS\Hooks::addFilter('page_title', function (string $title): string {
    return $title . ' | Meine Website';
});
```

### Prioritäten-System

Jeder Callback erhält eine **Priorität** (Standard: `10`). Niedrigere Werte werden zuerst ausgeführt.
Bei gleicher Priorität gilt die Registrierungs-Reihenfolge.

```php
CMS\Hooks::addAction('cms_init', $early,   5);   // läuft zuerst
CMS\Hooks::addAction('cms_init', $default, 10);  // Standard
CMS\Hooks::addAction('cms_init', $late,    20);  // läuft zuletzt
```

Die Callbacks werden intern in einem sortierten Array gehalten und bei `doAction` / `applyFilters`
in aufsteigender Priorität durchlaufen.

---

## 2. API-Funktionen

### addAction

Registriert einen Callback für ein Ereignis.

```php
CMS\Hooks::addAction(string $tag, callable $callback, int $priority = 10): void;
```

```php
CMS\Hooks::addAction('plugins_loaded', function (): void {
    require_once __DIR__ . '/includes/init.php';
}, 5);
```

### addFilter

Registriert einen Callback, der einen Wert filtern kann. Der Callback **muss** den (ggf. veränderten) Wert zurückgeben.

```php
CMS\Hooks::addFilter(string $tag, callable $callback, int $priority = 10): void;
```

```php
CMS\Hooks::addFilter('search_results', function (array $results, string $query): array {
    // Ergebnisse nachfiltern
    return array_filter($results, fn ($r) => $r->score > 0.5);
}, 10);
```

### doAction

Löst eine Action aus und ruft alle registrierten Callbacks in Prioritäts-Reihenfolge auf.

```php
CMS\Hooks::doAction(string $tag, mixed ...$args): void;
```

```php
// Im Core (Bootstrap.php):
CMS\Hooks::doAction('cms_init');
```

### applyFilters

Wendet alle registrierten Filter auf einen Wert an und gibt das Ergebnis zurück.

```php
CMS\Hooks::applyFilters(string $tag, mixed $value, mixed ...$args): mixed;
```

```php
// Im Core (ThemeManager.php):
$template = CMS\Hooks::applyFilters('template_name', $template);
```

---

## 3. Core-Hooks

Bootstrap- und System-Hooks, ausgelöst während der Initialisierungsphase in `Bootstrap.php` und `Router.php`.

<!-- UPDATED: 2026-03-08 -->
| Hook-Name | Typ | Parameter | Return | Wann ausgelöst | Beispiel |
|-----------|-----|-----------|--------|----------------|----------|
| `cms_init` | Action | – | – | Nach Grundinitialisierung (Config, DB, Security) | `addAction('cms_init', fn () => defineConstants())` |
| `cms_init_{mode}` | Action | – | – | Direkt nach `cms_init`; `{mode}` = `web` oder `api` | `addAction('cms_init_api', fn () => setupApiRoutes())` |
| `cms_before_route` | Action | – | – | Bevor der Router die URL auflöst | `addAction('cms_before_route', fn () => startProfiling())` |
| `register_routes` | Action | `Router $router` | – | Während der Route-Registrierung | `addAction('register_routes', fn (Router $r) => $r->get('/custom', $cb))` |
| `cms_after_route` | Action | – | – | Nachdem der Router die URL aufgelöst hat | `addAction('cms_after_route', fn () => stopProfiling())` |
| `local_font_slugs` | Filter | `array $slugs` | `array` | Beim Laden lokaler Font-Dateien | `addFilter('local_font_slugs', fn ($s) => [...$s, 'inter'])` |

---

## 4. Content-Hooks

Hooks rund um Seiten, Blog-Posts und Inhalte. Die Content-Hooks werden in `PageManager.php` und verwandten Services ausgelöst.

<!-- UPDATED: 2026-03-08 -->
| Hook-Name | Typ | Parameter | Return | Wann ausgelöst | Beispiel |
|-----------|-----|-----------|--------|----------------|----------|
| `page_saved` | Action | `int $pageId`, `object $page` | – | Seite gespeichert (Entwurf oder veröffentlicht) | `addAction('page_saved', fn (int $id, object $p) => clearCache($id))` |
| `page_published` | Action | `int $pageId` | – | Seite auf „veröffentlicht" gesetzt | `addAction('page_published', fn (int $id) => notifySubscribers($id))` |
| `page_deleted` | Action | `int $pageId` | – | Seite gelöscht | `addAction('page_deleted', fn (int $id) => removeSitemap($id))` |
| `post_published` | Action | `int $postId` | – | Blog-Post veröffentlicht | `addAction('post_published', fn (int $id) => pingSearchEngines())` |
| `post_deleted` | Action | `int $postId` | – | Blog-Post gelöscht | `addAction('post_deleted', fn (int $id) => cleanOrphans($id))` |
| `page_content` | Filter | `string $html` | `string` | Vor Ausgabe des Seiteninhalts | `addFilter('page_content', fn (string $h) => addToc($h))` |
| `post_content` | Filter | `string $html` | `string` | Vor Ausgabe des Blog-Post-Inhalts | `addFilter('post_content', fn (string $h) => lazyLoadImages($h))` |
| `excerpt` | Filter | `string $text` | `string` | Vor Ausgabe des Auszugs | `addFilter('excerpt', fn (string $t) => mb_substr($t, 0, 160))` |
| `page_title` | Filter | `string $title` | `string` | Vor Ausgabe des HTML-Titels | `addFilter('page_title', fn (string $t) => $t . ' – Site')` |

---

## 5. User-Hooks

Hooks für Authentifizierung, Registrierung und Benutzerverwaltung. Ausgelöst in `Auth.php`, `Auth/AuthManager.php` und `SubscriptionManager.php`.

<!-- UPDATED: 2026-03-08 -->
| Hook-Name | Typ | Parameter | Return | Wann ausgelöst | Beispiel |
|-----------|-----|-----------|--------|----------------|----------|
| `user_logged_in` | Action | `int $userId` | – | Erfolgreicher Login (nach MFA, falls aktiv) | `addAction('user_logged_in', fn (int $id) => updateLastLogin($id))` |
| `user_registered` | Action | `int $userId` | – | Neuer Benutzer angelegt | `addAction('user_registered', fn (int $id) => sendWelcomeMail($id))` |
| `user_updated` | Action | `int $userId`, `array $data` | – | Benutzerdaten geändert | `addAction('user_updated', fn (int $id) => syncCrm($id))` |
| `user_deleted` | Action | `int $userId` | – | Benutzer gelöscht | `addAction('user_deleted', fn (int $id) => cleanupData($id))` |
| `password_reset` | Action | `int $userId` | – | Passwort wurde zurückgesetzt | `addAction('password_reset', fn (int $id) => logReset($id))` |
| `subscription_assigned` | Action | `int $userId`, `int $planId` | – | Abonnement zugewiesen | `addAction('subscription_assigned', fn (int $u, int $p) => grantAccess($u, $p))` |
| `user_display_name` | Filter | `string $name` | `string` | Vor Anzeige des Benutzernamens | `addFilter('user_display_name', fn (string $n) => ucfirst($n))` |
| `member_capabilities` | Filter | `array $caps` | `array` | Beim Laden der Member-Berechtigungen | `addFilter('member_capabilities', fn (array $c) => [...$c, 'export'])` |

---

## 6. Admin-Hooks

Hooks im Admin-Bereich, ausgelöst in `Router.php` und den Admin-Partials (`header.php`, `footer.php`).

<!-- UPDATED: 2026-03-08 -->
| Hook-Name | Typ | Parameter | Return | Wann ausgelöst | Beispiel |
|-----------|-----|-----------|--------|----------------|----------|
| `cms_admin_menu` | Action | – | – | Admin-Menü wird aufgebaut | `addAction('cms_admin_menu', fn () => registerMenuItem())` |
| `admin_head` | Action | – | – | Im `<head>` des Admin-Layouts | `addAction('admin_head', fn () => echo '<link …>')` |
| `admin_body_end` | Action | – | – | Vor `</body>` im Admin-Layout | `addAction('admin_body_end', fn () => echo '<script …>')` |
| `member_dashboard_init` | Action | `PluginDashboardRegistry $reg` | – | Member-Dashboard wird initialisiert | `addAction('member_dashboard_init', fn ($r) => $r->addWidget(…))` |
| `admin_menu_items` | Filter | `array $items` | `array` | Admin-Menü-Einträge vor Ausgabe | `addFilter('admin_menu_items', fn (array $i) => [...$i, $custom])` |
| `nav_menu_items` | Filter | `array $items` | `array` | Frontend-Navigation vor Ausgabe | `addFilter('nav_menu_items', fn (array $i) => filterByRole($i))` |

---

## 7. Plugin-Hooks

Lifecycle-Hooks für Plugins, ausgelöst in `PluginManager.php`.

<!-- UPDATED: 2026-03-08 -->
| Hook-Name | Typ | Parameter | Return | Wann ausgelöst | Beispiel |
|-----------|-----|-----------|--------|----------------|----------|
| `plugin_loaded` | Action | `string $slug` | – | Ein einzelnes Plugin wurde geladen | `addAction('plugin_loaded', fn (string $s) => logLoad($s))` |
| `plugins_loaded` | Action | – | – | Alle aktiven Plugins wurden geladen | `addAction('plugins_loaded', fn () => initMyPlugin())` |
| `plugin_activated` | Action | `string $slug` | – | Plugin wurde aktiviert | `addAction('plugin_activated', fn (string $s) => runMigrations($s))` |
| `plugin_deactivated` | Action | `string $slug` | – | Plugin wurde deaktiviert | `addAction('plugin_deactivated', fn (string $s) => cleanCache($s))` |
| `plugin_before_delete` | Action | `string $slug` | – | Unmittelbar vor dem Löschen eines Plugins | `addAction('plugin_before_delete', fn (string $s) => backupData($s))` |
| `plugin_deleted` | Action | `string $slug` | – | Plugin-Dateien wurden gelöscht | `addAction('plugin_deleted', fn (string $s) => dropTables($s))` |
| `plugin_installed` | Action | – | – | Ein neues Plugin wurde installiert | `addAction('plugin_installed', fn () => flushRoutes())` |

---

## 8. Theme-Hooks

Rendering- und Lifecycle-Hooks für Themes, ausgelöst in `ThemeManager.php`.

<!-- UPDATED: 2026-03-08 -->
| Hook-Name | Typ | Parameter | Return | Wann ausgelöst | Beispiel |
|-----------|-----|-----------|--------|----------------|----------|
| `theme_loaded` | Action | `string $themeSlug` | – | Aktives Theme wurde geladen | `addAction('theme_loaded', fn (string $t) => loadChildAssets($t))` |
| `before_render` | Action | `string $template` | – | Vor dem Rendern eines Templates | `addAction('before_render', fn (string $t) => startBuffer())` |
| `after_render` | Action | `string $template` | – | Nach dem Rendern eines Templates | `addAction('after_render', fn (string $t) => endBuffer())` |
| `before_header` | Action | – | – | Vor Ausgabe des `<header>`-Bereichs | `addAction('before_header', fn () => echo '<div class="banner">')` |
| `after_header` | Action | – | – | Nach Ausgabe des `<header>`-Bereichs | `addAction('after_header', fn () => echo '</div>')` |
| `before_footer` | Action | – | – | Vor Ausgabe des `<footer>`-Bereichs | `addAction('before_footer', fn () => echo '<div class="pre-footer">')` |
| `after_footer` | Action | – | – | Nach Ausgabe des `<footer>`-Bereichs | `addAction('after_footer', fn () => echo trackingPixel())` |
| `body_end` | Action | – | – | Vor `</body>` im Frontend | `addAction('body_end', fn () => echo analyticsSnippet())` |
| `template_name` | Filter | `string $template` | `string` | Erlaubt Umbenennung/Override des Templates | `addFilter('template_name', fn (string $t) => $t === 'home' ? 'landing' : $t)` |
| `register_menu_locations` | Filter | `array $locations` | `array` | Registrierung der Menü-Positionen | `addFilter('register_menu_locations', fn ($l) => [...$l, 'sidebar'])` |

---

## 9. Media-Hooks

Hooks für Datei-Uploads und Medienverwaltung. DSGVO-Hooks für Datenexport/-löschung werden in den Legal-Modulen ausgelöst.

<!-- UPDATED: 2026-03-08 -->
| Hook-Name | Typ | Parameter | Return | Wann ausgelöst | Beispiel |
|-----------|-----|-----------|--------|----------------|----------|
| `dsgvo_delete_data` | Action | `int $userId`, `string $email` | – | DSGVO-Löschanfrage wird verarbeitet | `addAction('dsgvo_delete_data', fn (int $u, string $e) => deleteExtData($u))` |
| `dsgvo_export_data` | Action | `int $userId`, `string $email` | – | DSGVO-Datenexport wird verarbeitet | `addAction('dsgvo_export_data', fn (int $u, string $e) => exportExtData($u))` |
| `allowed_file_types` | Filter | `array $types` | `array` | Erlaubte Upload-Dateitypen werden geladen | `addFilter('allowed_file_types', fn (array $t) => [...$t, 'svg'])` |
| `max_upload_size` | Filter | `int $bytes` | `int` | Maximale Upload-Größe wird ermittelt | `addFilter('max_upload_size', fn () => 50 * 1024 * 1024)` |

---

## 10. SEO-Hooks

Hooks für Suchmaschinen-Optimierung und Suche, ausgelöst in `Services/SearchService.php` und `Services/LandingPageService.php`.

<!-- UPDATED: 2026-03-08 -->
| Hook-Name | Typ | Parameter | Return | Wann ausgelöst | Beispiel |
|-----------|-----|-----------|--------|----------------|----------|
| `search_register_indices` | Action | `SearchService $service` | – | Suchindizes werden registriert | `addAction('search_register_indices', fn ($s) => $s->addIndex('products', $cb))` |
| `search_results` | Filter | `array $results`, `string $query`, `int $limit` | `array` | Suchergebnisse vor Rückgabe an den Client | `addFilter('search_results', fn (array $r) => boostFeatured($r))` |
| `landing_page_plugins` | Filter | `array $plugins` | `array` | Plugin-Blöcke für Landing-Pages registrieren | `addFilter('landing_page_plugins', fn ($p) => [...$p, myBlock()])` |
| `email_subject` | Filter | `string $subject` | `string` | E-Mail-Betreff vor dem Versand | `addFilter('email_subject', fn (string $s) => '[Site] ' . $s)` |
| `email_body` | Filter | `string $body` | `string` | E-Mail-Inhalt vor dem Versand | `addFilter('email_body', fn (string $b) => appendFooter($b))` |

---

## 11. Best Practices für eigene Hooks

### Anonyme Funktionen für einfache Hooks

```php
CMS\Hooks::addAction('cms_init', function (): void {
    define('MY_PLUGIN_LOADED', true);
});
```

### Klassen-Methoden für komplexe Logik

```php
class MeinPlugin
{
    public function boot(): void
    {
        CMS\Hooks::addAction('user_registered', [$this, 'onRegister']);
        CMS\Hooks::addFilter('page_title', [$this, 'addSiteName']);
    }

    public function onRegister(int $userId): void
    {
        $db   = CMS\Database::instance();
        $user = $db->get_row("SELECT email FROM cms_users WHERE id = ?", [$userId]);
        if ($user) {
            mail($user->email, 'Willkommen', 'Danke für die Registrierung.');
        }
    }

    public function addSiteName(string $title): string
    {
        return $title . ' | ' . SITE_NAME;
    }
}
```

### Namenskonventionen für eigene Hooks

Eigene Hooks sollten mit einem eindeutigen Prefix versehen werden, um Kollisionen zu vermeiden:

```php
// Plugin: "shop"
CMS\Hooks::doAction('shop_order_placed', $orderId);
CMS\Hooks::doAction('shop_payment_received', $orderId, $amount);

$price = CMS\Hooks::applyFilters('shop_product_price', $price, $productId);
```

### Hooks in der Plugin-Hauptdatei registrieren

```php
// plugins/mein-plugin/mein-plugin.php
CMS\Hooks::addAction('plugins_loaded', function (): void {
    require_once __DIR__ . '/includes/class-mein-plugin.php';
    MeinPlugin::instance()->boot();
});
```

### Core-Dateien niemals direkt bearbeiten

```php
// FALSCH: CMS/core/Auth.php bearbeiten
// RICHTIG: Hook verwenden
CMS\Hooks::addAction('user_logged_in', function (int $userId): void {
    // Eigene Logik nach Login
});
```

### Vollständiges Plugin-Beispiel

```php
<?php
/**
 * Plugin Name: Mein Plugin
 * Version: 1.0.0
 * Author: Max Mustermann
 */
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class MeinPlugin
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function boot(): void
    {
        // Actions
        CMS\Hooks::addAction('cms_init',          [$this, 'setup']);
        CMS\Hooks::addAction('user_registered',   [$this, 'onUserRegistered']);
        CMS\Hooks::addAction('cms_admin_menu',    [$this, 'addAdminMenu']);

        // Filters
        CMS\Hooks::addFilter('page_title',        [$this, 'filterTitle']);
        CMS\Hooks::addFilter('nav_menu_items',    [$this, 'addNavItem']);
    }

    public function setup(): void
    {
        define('MEIN_PLUGIN_VERSION', '1.0.0');
    }

    public function onUserRegistered(int $userId): void
    {
        $db   = CMS\Database::instance();
        $user = $db->get_row(
            "SELECT email, display_name FROM cms_users WHERE id = ?",
            [$userId]
        );
        if ($user) {
            mail(
                $user->email,
                'Willkommen bei ' . SITE_NAME,
                'Hallo ' . $user->display_name . '! Danke für die Registrierung.'
            );
        }
    }

    public function addAdminMenu(): void
    {
        CMS\Hooks::doAction('register_admin_page', [
            'title'    => 'Mein Plugin',
            'slug'     => 'mein-plugin',
            'callback' => [$this, 'renderAdminPage'],
            'icon'     => 'dashicons-star-filled',
        ]);
    }

    public function filterTitle(string $title): string
    {
        return $title . ' | Mein Plugin';
    }

    public function addNavItem(array $items): array
    {
        $items[] = ['label' => 'Mein Feature', 'url' => '/mein-feature'];
        return $items;
    }

    public function renderAdminPage(): void
    {
        echo '<h1>Mein Plugin – Admin</h1>';
    }
}

CMS\Hooks::addAction('plugins_loaded', function (): void {
    MeinPlugin::instance()->boot();
});
```
