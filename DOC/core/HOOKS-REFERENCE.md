# 365CMS – Hook-System Referenz

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026

Das Hook-System des 365CMS ist von WordPress inspiriert und erlaubt es Plugins und Themes, das Verhalten des CMS zu erweitern **ohne Core-Dateien zu verändern**.

---

## Inhaltsverzeichnis

1. [Grundkonzept](#1-grundkonzept)
2. [Actions (Ereignis-Hooks)](#2-actions-ereignis-hooks)
3. [Filters (Wert-Hooks)](#3-filters-wert-hooks)
4. [Alle verfügbaren Hooks](#4-alle-verfügbaren-hooks)
5. [Best Practices](#5-best-practices)
6. [Vollständiges Plugin-Beispiel](#6-vollständiges-plugin-beispiel)

---

## 1. Grundkonzept

### Was ist ein Hook?
Ein Hook ist ein definierter Punkt im Code, an dem externe Code-Teile (Plugins) eingehängt werden können.

**Zwei Arten von Hooks:**
- **Action**: Code wird *ausgeführt* wenn ein Ereignis eintritt
- **Filter**: Ein *Wert wird verändert* bevor er verwendet wird

```php
// Action: IF das Ereignis eintritt, THEN führe Funktion aus
CMS\Hooks::addAction('user_registered', function($userId) {
    // Wird ausgeführt, wenn ein User sich registriert
});

// Filter: NEHME den Wert, VERÄNDERE ihn, GEBE ihn zurück
CMS\Hooks::addFilter('page_title', function($title) {
    return $title . ' | Meine Website'; // Veränderter Wert
});
```

### Prioritäten
Niedrigere Zahl = wird früher ausgeführt (Standard: 10):
```php
CMS\Hooks::addAction('init', $callbackA, 5);  // Wird zuerst ausgeführt
CMS\Hooks::addAction('init', $callbackB, 10); // Standard
CMS\Hooks::addAction('init', $callbackC, 20); // Wird zuletzt ausgeführt
```

---

## 2. Actions (Ereignis-Hooks)

### addAction

```php
/**
 * @param string   $tag       Hook-Name
 * @param callable $callback  Funktion die ausgeführt werden soll
 * @param int      $priority  Reihenfolge (Standard: 10)
 */
CMS\Hooks::addAction(string $tag, callable $callback, int $priority = 10): void;
```

### doAction

```php
/**
 * @param string $tag   Hook-Name
 * @param mixed  ...$args  Optionale Parameter
 */
CMS\Hooks::doAction(string $tag, ...$args): void;
```

---

## 3. Filters (Wert-Hooks)

### addFilter

```php
/**
 * @param string   $tag       Hook-Name
 * @param callable $callback  Funktion die Wert verändert (muss Wert zurückgeben!)
 * @param int      $priority  Reihenfolge (Standard: 10)
 */
CMS\Hooks::addFilter(string $tag, callable $callback, int $priority = 10): void;
```

### applyFilters

```php
/**
 * @param string $tag    Hook-Name
 * @param mixed  $value  Ursprünglicher Wert
 * @param mixed  ...$args  Optionale Zusatz-Parameter
 * @return mixed  Evtl. veränderter Wert
 */
CMS\Hooks::applyFilters(string $tag, mixed $value, ...$args): mixed;
```

---

## 4. Alle verfügbaren Hooks

### System-Actions (Bootstrap-Phase)

| Hook | Wann | Parameter |
|------|------|-----------|
| `init` | Nach Grundinitialisierung | – |
| `plugins_loaded` | Alle Plugins geladen | – |
| `theme_loaded` | Theme aktiviert | `string $themeSlug` |
| `shutdown` | Vor Antwort-Ende | – |

### Benutzer-Actions

| Hook | Wann | Parameter |
|------|------|-----------|
| `user_registered` | Neuer User angelegt | `int $userId` |
| `user_login` | Erfolgreicher Login | `int $userId, string $username` |
| `user_logout` | Logout | `int $userId` |
| `user_updated` | User-Daten geändert | `int $userId, array $data` |
| `user_deleted` | User gelöscht | `int $userId` |
| `password_reset` | Passwort zurückgesetzt | `int $userId` |

### Inhalts-Actions

| Hook | Wann | Parameter |
|------|------|-----------|
| `page_saved` | Seite gespeichert | `int $pageId, object $page` |
| `page_published` | Seite veröffentlicht | `int $pageId` |
| `page_deleted` | Seite gelöscht | `int $pageId` |
| `post_published` | Blog-Post veröffentlicht | `int $postId` |
| `post_deleted` | Blog-Post gelöscht | `int $postId` |

### Plugin-Actions

| Hook | Wann | Parameter |
|------|------|-----------|
| `plugin_activated` | Plugin aktiviert | `string $slug` |
| `plugin_deactivated` | Plugin deaktiviert | `string $slug` |
| `plugin_loaded` | Einzelnes Plugin geladen | `string $slug` |

### Admin-Actions

| Hook | Wann | Parameter |
|------|------|-----------|
| `admin_init` | Admin-Bereich geladen | – |
| `admin_menu` | Admin-Menü aufgebaut | – |
| `before_admin_page` | Vor Admin-Seite | `string $page` |
| `after_admin_page` | Nach Admin-Seite | `string $page` |
| `routes_registered` | Router läuft | – |

### Abo-Actions

| Hook | Wann | Parameter |
|------|------|-----------|
| `subscription_created` | Neues Abo | `int $userId, int $planId` |
| `subscription_cancelled` | Abo gekündigt | `int $userId` |
| `subscription_expired` | Abo abgelaufen | `int $userId` |

---

### Verfügbare Filters

| Filter | Wert-Typ | Beschreibung |
|--------|----------|--------------|
| `page_title` | `string` | HTML-Titel der Seite |
| `page_content` | `string` | Seiten-Inhalt (HTML) |
| `post_content` | `string` | Blog-Post-Inhalt |
| `excerpt` | `string` | Auszug-Text |
| `user_display_name` | `string` | Anzeigename des Users |
| `nav_menu_items` | `array` | Navigation-Menü-Items |
| `admin_menu_items` | `array` | Admin-Menü-Items |
| `allowed_file_types` | `array` | Erlaubte Upload-Dateitypen |
| `max_upload_size` | `int` | Max. Upload-Größe in Bytes |
| `member_capabilities` | `array` | Member-Berechtigungen |
| `search_results` | `array` | Suchergebnisse vor Ausgabe |
| `email_subject` | `string` | E-Mail-Betreff |
| `email_body` | `string` | E-Mail-Inhalt |

---

## 5. Best Practices

### DO: Anonyme Funktion für einfache Hooks

```php
CMS\Hooks::addAction('init', function() {
    // Einfache Initialisierung
    define('MY_PLUGIN_LOADED', true);
});
```

### DO: Klassen-Methode für komplexe Logik

```php
class MyPlugin {
    public function init(): void {
        CMS\Hooks::addAction('user_registered', [$this, 'sendWelcomeEmail']);
        CMS\Hooks::addFilter('page_title', [$this, 'addSiteName']);
    }

    public function sendWelcomeEmail(int $userId): void {
        $user = CMS\Database::instance()->get_row(
            "SELECT * FROM cms_users WHERE id = ?", [$userId]
        );
        // E-Mail senden...
    }

    public function addSiteName(string $title): string {
        return $title . ' | ' . SITE_NAME;
    }
}

$plugin = new MyPlugin();
$plugin->init();
```

### DON'T: Niemals Core-Dateien direkt bearbeiten

```php
// FALSCH: core/Auth.php direkt bearbeiten
// RICHTIG: Hook nutzen
CMS\Hooks::addAction('user_login', function($userId) {
    // Eigene Logik nach Login
});
```

### DO: Hooks in Plugin-Hauptdatei registrieren

```php
// plugins/mein-plugin/mein-plugin.php
CMS\Hooks::addAction('plugins_loaded', function() {
    // Erst wenn ALLE Plugins geladen sind
    require_once PLUGIN_PATH . 'mein-plugin/includes/class-mein-plugin.php';
    MeinPlugin::instance()->init();
});
```

---

## 6. Vollständiges Plugin-Beispiel

```php
<?php
/**
 * Plugin Name: Mein Plugin
 * Version: 1.0.0
 * Author: Max Mustermann
 */

declare(strict_types=1);

if (!defined('ABSPATH')) exit;

class MeinPlugin {
    private static ?self $instance = null;

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        // Actions
        CMS\Hooks::addAction('init',             [$this, 'setup']);
        CMS\Hooks::addAction('user_registered',  [$this, 'onUserRegistered']);
        CMS\Hooks::addAction('admin_menu',       [$this, 'addAdminMenu']);

        // Filters
        CMS\Hooks::addFilter('page_title',       [$this, 'filterTitle']);
        CMS\Hooks::addFilter('nav_menu_items',   [$this, 'addNavItem']);
    }

    public function setup(): void {
        // Plugin-Konstanten, Datenbank-Tabellen etc.
        define('MEIN_PLUGIN_VERSION', '1.0.0');
    }

    public function onUserRegistered(int $userId): void {
        $db = CMS\Database::instance();
        $user = $db->get_row(
            "SELECT email, display_name FROM cms_users WHERE id = ?",
            [$userId]
        );
        if ($user) {
            // Willkommens-E-Mail
            mail(
                $user->email,
                'Willkommen bei ' . SITE_NAME,
                'Hallo ' . $user->display_name . '! Danke für deine Registrierung.'
            );
        }
    }

    public function addAdminMenu(): void {
        // Admin-Menüpunkt hinzufügen
        CMS\Hooks::doAction('register_admin_page', [
            'title'    => 'Mein Plugin',
            'slug'     => 'mein-plugin',
            'callback' => [$this, 'renderAdminPage'],
            'icon'     => 'dashicons-star-filled',
        ]);
    }

    public function filterTitle(string $title): string {
        return $title; // Veränderter oder unveränderter Titel
    }

    public function addNavItem(array $items): array {
        $items[] = ['label' => 'Mein Feature', 'url' => '/mein-feature'];
        return $items;
    }

    public function renderAdminPage(): void {
        echo '<h1>Mein Plugin - Admin</h1>';
    }
}

// Plugin starten, sobald alle anderen Plugins geladen sind
CMS\Hooks::addAction('plugins_loaded', function() {
    MeinPlugin::instance()->init();
});
```

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
