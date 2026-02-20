# CMSv2 - Hooks & Filters Reference

**Version:** 2.0.0  
**Datum:** 17. Februar 2026

## 📌 Hook-System-Übersicht

CMSv2 verwendet ein WordPress-ähnliches Hook-System, das es Plugins ermöglicht, sich in den Core einzuhaken ohne den Core-Code zu modifizieren.

## 🎯 Hook-Typen

### Actions (Ausführungs-Hooks)
**Zweck:** Code an bestimmten Stellen ausführen  
**Return:** void (kein Rückgabewert)

```php
// Hook registrieren
CMS\Hooks::addAction('hook_name', function($arg1, $arg2) {
    // Dein Code hier
}, 10); // Priority (optional, default: 10)

// Hook ausführen
CMS\Hooks::doAction('hook_name', $arg1, $arg2);
```

### Filters (Modifikations-Hooks)
**Zweck:** Daten vor Ausgabe/Verwendung modifizieren  
**Return:** Modifizierter Wert

```php
// Filter registrieren
CMS\Hooks::addFilter('filter_name', function($value, $arg1) {
    return $modified_value;
}, 10);

// Filter anwenden
$value = CMS\Hooks::applyFilters('filter_name', $value, $arg1);
```

## 🔌 Core Actions

### System Lifecycle

#### `cms_init`
**Wann:** Nach vollständiger System-Initialisierung  
**Datei:** `core/Bootstrap.php`  
**Parameter:** Keine  
**Verwendung:** Plugin-Initialisierung

```php
CMS\Hooks::addAction('cms_init', function() {
    // Plugin initialisieren
    MyPlugin::init();
});
```

#### `cms_before_route`
**Wann:** Vor URL-Routing  
**Datei:** `core/Bootstrap.php`  
**Parameter:** Keine  
**Verwendung:** Route-Manipulation, Redirects

```php
CMS\Hooks::addAction('cms_before_route', function() {
    // Maintenance-Mode Check
    if (get_option('maintenance_mode') === '1') {
        header('HTTP/1.1 503 Service Unavailable');
        die('Site under maintenance');
    }
});
```

#### `cms_after_route`
**Wann:** Nach URL-Routing (vor Template-Rendering)  
**Datei:** `core/Bootstrap.php`  
**Parameter:** Keine  
**Verwendung:** Finale Datenverarbeitung

```php
CMS\Hooks::addAction('cms_after_route', function() {
    // Analytics-Tracking
    trackPageView();
});
```

### Route Registration

#### `register_routes`
**Wann:** Während Router-Initialisierung  
**Datei:** `core/Router.php`  
**Parameter:** `$router` (Router-Instanz)  
**Verwendung:** Custom Routes registrieren

```php
CMS\Hooks::addAction('register_routes', function($router) {
    $router->addRoute('/custom-page', function() {
        echo "Custom Page Content";
    });
});
```

### Theme Hooks

#### `theme_loaded`
**Wann:** Nach Theme-Aktivierung  
**Datei:** `core/ThemeManager.php`  
**Parameter:** `$theme_name` (String)  
**Verwendung:** Theme-spezifische Initialisierung

```php
CMS\Hooks::addAction('theme_loaded', function($theme) {
    // Theme-Assets laden
    loadThemeAssets($theme);
});
```

#### `before_render`
**Wann:** Vor Template-Rendering  
**Datei:** `core/ThemeManager.php`  
**Parameter:** `$template` (Template-Name)  
**Verwendung:** Template-Vorbereitung

```php
CMS\Hooks::addAction('before_render', function($template) {
    // SEO-Meta-Tags vorbereiten
    prepareSEOMeta($template);
});
```

#### `after_render`
**Wann:** Nach Template-Rendering  
**Datei:** `core/ThemeManager.php`  
**Parameter:** `$template` (Template-Name)  
**Verwendung:** Output-Manipulation

```php
CMS\Hooks::addAction('after_render', function($template) {
    // Performance-Metrics ausgeben
    if (CMS_DEBUG) {
        echo "<!-- Rendered: $template -->";
    }
});
```

#### `before_header`
**Wann:** Vor Header-Template  
**Datei:** `core/ThemeManager.php`  
**Parameter:** Keine  
**Verwendung:** Pre-Header-Content

```php
CMS\Hooks::addAction('before_header', function() {
    // Skip-to-Content Link (Accessibility)
    echo '<a href="#main" class="skip-link">Skip to content</a>';
});
```

#### `after_header`
**Wann:** Nach Header-Template  
**Datei:** `core/ThemeManager.php` & `themes/default/header.php`  
**Parameter:** Keine  
**Verwendung:** Post-Header-Content (z.B. Breadcrumbs)

```php
CMS\Hooks::addAction('after_header', function() {
    // Breadcrumb-Navigation
    echo '<nav class="breadcrumb">Home > Current Page</nav>';
});
```

#### `before_footer`
**Wann:** Vor Footer-Template  
**Datei:** `core/ThemeManager.php` & `themes/default/footer.php`  
**Parameter:** Keine  
**Verwendung:** Pre-Footer-Content (z.B. Newsletter)

```php
CMS\Hooks::addAction('before_footer', function() {
    // Newsletter-Anmeldung
    include 'newsletter-form.php';
});
```

#### `after_footer`
**Wann:** Nach Footer-Template  
**Datei:** `core/ThemeManager.php` & `themes/default/footer.php`  
**Parameter:** Keine  
**Verwendung:** Post-Footer-Content

```php
CMS\Hooks::addAction('after_footer', function() {
    // Cookie-Banner
    include 'cookie-consent.php';
});
```

### Template Hooks

#### `head`
**Wann:** In `<head>`-Section  
**Datei:** `themes/default/header.php`  
**Parameter:** Keine  
**Verwendung:** Meta-Tags, CSS, Scripts

```php
CMS\Hooks::addAction('head', function() {
    echo '<meta name="description" content="My site">';
    echo '<link rel="stylesheet" href="/custom.css">';
});
```

#### `body_start`
**Wann:** Direkt nach `<body>`-Tag  
**Datei:** `themes/default/header.php`  
**Parameter:** Keine  
**Verwendung:** Tracking-Codes, noscript-Tags

```php
CMS\Hooks::addAction('body_start', function() {
    // Google Tag Manager noscript
    echo '<noscript><!-- GTM --></noscript>';
});
```

#### `body_end`
**Wann:** Vor `</body>`-Tag  
**Datei:** `themes/default/footer.php`  
**Parameter:** Keine  
**Verwendung:** Footer-Scripts, Analytics

```php
CMS\Hooks::addAction('body_end', function() {
    echo '<script src="/analytics.js"></script>';
});
```

#### `main_nav`
**Wann:** In Haupt-Navigation  
**Datei:** `themes/default/header.php`  
**Parameter:** Keine  
**Verwendung:** Navigation-Items hinzufügen

```php
CMS\Hooks::addAction('main_nav', function() {
    echo '<li><a href="/custom">Custom Page</a></li>';
});
```

### Content Hooks

#### `home_content`
**Wann:** Auf Homepage (nach Hero)  
**Datei:** `themes/default/home.php`  
**Parameter:** Keine  
**Verwendung:** Custom Homepage-Widgets

```php
CMS\Hooks::addAction('home_content', function() {
    echo '<section class="latest-posts">';
    echo '<h2>Latest Posts</h2>';
    // Posts ausgeben
    echo '</section>';
});
```

#### `admin_dashboard_content`
**Wann:** Im Admin-Dashboard  
**Datei:** `admin/index.php`  
**Parameter:** Keine  
**Verwendung:** Dashboard-Widgets

```php
CMS\Hooks::addAction('admin_dashboard_content', function() {
    echo '<div class="widget">';
    echo '<h3>My Plugin Stats</h3>';
    // Stats anzeigen
    echo '</div>';
});
```

#### `member_dashboard_content`
**Wann:** Im Member-Dashboard  
**Datei:** `member/index.php`  
**Parameter:** Keine  
**Verwendung:** Member-Widgets

```php
CMS\Hooks::addAction('member_dashboard_content', function() {
    echo '<div class="user-stats">';
    echo '<h3>Your Activity</h3>';
    // User-Aktivität anzeigen
    echo '</div>';
});
```

#### `footer_sections`
**Wann:** In Footer-Bereich  
**Datei:** `themes/default/footer.php`  
**Parameter:** Keine  
**Verwendung:** Footer-Widgets

```php
CMS\Hooks::addAction('footer_sections', function() {
    echo '<div class="footer-widget">';
    echo '<h4>Social Media</h4>';
    // Social Links
    echo '</div>';
});
```

#### `footer`
**Wann:** Im Footer (vor Copyright)  
**Datei:** `themes/default/footer.php`  
**Parameter:** Keine  
**Verwendung:** Footer-Content

```php
CMS\Hooks::addAction('footer', function() {
    echo '<p>Powered by CMSv2</p>';
});
```

### Admin Hooks

#### `admin_menu`
**Wann:** Bei Admin-Menü-Generierung  
**Datei:** `admin/layout/header.php`  
**Parameter:** Keine  
**Verwendung:** Admin-Menü-Items hinzufügen

```php
CMS\Hooks::addAction('admin_menu', function() {
    echo '<li><a href="/admin/my-plugin">My Plugin</a></li>';
});
```

### Plugin Hooks

#### `plugins_loaded`
**Wann:** Nach Laden aller Plugins  
**Datei:** `core/PluginManager.php`  
**Parameter:** Keine  
**Verwendung:** Plugin-Interaktion

```php
CMS\Hooks::addAction('plugins_loaded', function() {
    // Prüfe ob anderes Plugin aktiv ist
    if (function_exists('other_plugin_function')) {
        // Integration
    }
});
```

#### `plugin_loaded`
**Wann:** Nach Laden eines einzelnen Plugins  
**Datei:** `core/PluginManager.php`  
**Parameter:** `$plugin` (Plugin-Name)  
**Verwendung:** Plugin-spezifische Actions

```php
CMS\Hooks::addAction('plugin_loaded', function($plugin) {
    if ($plugin === 'my-plugin') {
        // Plugin-spezifische Initialisierung
    }
});
```

#### `plugin_activated`
**Wann:** Nach Plugin-Aktivierung  
**Datei:** `core/PluginManager.php`  
**Parameter:** `$plugin` (Plugin-Name)  
**Verwendung:** Aktivierungs-Logik

```php
CMS\Hooks::addAction('plugin_activated', function($plugin) {
    // Datenbank-Tabellen erstellen
    // Standard-Optionen setzen
});
```

#### `plugin_deactivated`
**Wann:** Nach Plugin-Deaktivierung  
**Datei:** `core/PluginManager.php`  
**Parameter:** `$plugin` (Plugin-Name)  
**Verwendung:** Deaktivierungs-Logik

```php
CMS\Hooks::addAction('plugin_deactivated', function($plugin) {
    // Caches leeren
    // Optionen zurücksetzen (nicht löschen!)
});
```

### User Hooks

#### `user_registered`
**Wann:** Nach erfolgreicher Registrierung  
**Datei:** `core/Auth.php`  
**Parameter:** `$user_id` (Integer)  
**Verwendung:** Post-Registrierungs-Actions

```php
CMS\Hooks::addAction('user_registered', function($user_id) {
    // Willkommens-E-Mail senden
    sendWelcomeEmail($user_id);
    
    // Standard-Meta-Daten setzen
    updateUserMeta($user_id, 'onboarding_completed', '0');
});
```

## 🎨 Core Filters

### Template Filters

#### `template_name`
**Wann:** Bei Template-Auswahl  
**Datei:** `core/ThemeManager.php`  
**Parameter:** `$template` (Template-Name)  
**Return:** String (Template-Name)  
**Verwendung:** Template-Override

```php
CMS\Hooks::addFilter('template_name', function($template) {
    // Nutze custom Template für bestimmte Bedingungen
    if (is_mobile()) {
        return 'mobile-' . $template;
    }
    return $template;
});
```

### Theme Filters

#### `theme_color_*`
**Wann:** Bei Theme-Farb-Abfrage  
**Datei:** `themes/default/functions.php`  
**Parameter:** `$color` (Hex-Farbcode)  
**Return:** String (Hex-Farbcode)  
**Verwendung:** Theme-Farben anpassen

```php
CMS\Hooks::addFilter('theme_color_primary', function($color) {
    return '#ff5733'; // Custom Primary Color
});

CMS\Hooks::addFilter('theme_color_secondary', function($color) {
    return '#33c4ff'; // Custom Secondary Color
});
```

**Verfügbare Farb-Filter:**
- `theme_color_primary`
- `theme_color_secondary`
- `theme_color_accent`
- `theme_color_text`
- `theme_color_background`

#### `featured_content`
**Wann:** Bei Abfrage von Featured Content  
**Datei:** `themes/default/functions.php`  
**Parameter:** `$content` (Array)  
**Return:** Array  
**Verwendung:** Featured Content definieren

```php
CMS\Hooks::addFilter('featured_content', function($content) {
    return [
        [
            'title' => 'Feature 1',
            'description' => 'Description',
            'icon' => 'icon-name'
        ],
        // ...
    ];
});
```

### Content Filters

#### `page_content`
**Wann:** Vor Ausgabe von Seiteninhalten  
**Parameter:** `$content` (HTML-String)  
**Return:** String  
**Verwendung:** Content-Manipulation

```php
CMS\Hooks::addFilter('page_content', function($content) {
    // Auto-Links für URLs
    $content = preg_replace(
        '/(https?:\/\/[^\s]+)/',
        '<a href="$1">$1</a>',
        $content
    );
    return $content;
});
```

#### `excerpt_length`
**Wann:** Bei Excerpt-Generierung  
**Parameter:** `$length` (Integer)  
**Return:** Integer  
**Verwendung:** Excerpt-Länge anpassen

```php
CMS\Hooks::addFilter('excerpt_length', function($length) {
    return 50; // 50 Wörter statt default
});
```

## 🔧 Hook-Prioritäten

Hooks werden nach Priorität sortiert ausgeführt (niedrig → hoch):

```
Priority 1-5:   Sehr früh
Priority 10:    Standard (Default)
Priority 15-20: Nach Standard
Priority 99:    Sehr spät
```

**Beispiel:**
```php
// Wird zuerst ausgeführt
CMS\Hooks::addAction('cms_init', 'earlyFunction', 5);

// Standard-Priorität
CMS\Hooks::addAction('cms_init', 'normalFunction', 10);

// Wird zuletzt ausgeführt
CMS\Hooks::addAction('cms_init', 'lateFunction', 99);
```

## 📝 Best Practices

### 1. Eindeutige Funktionsnamen
```php
// ❌ Schlecht
CMS\Hooks::addAction('cms_init', 'init');

// ✅ Gut
CMS\Hooks::addAction('cms_init', 'my_plugin_init');
```

### 2. Klassen-Methoden nutzen
```php
class MyPlugin {
    public function __construct() {
        CMS\Hooks::addAction('cms_init', [$this, 'init']);
    }
    
    public function init(): void {
        // Plugin-Init
    }
}
```

### 3. Hooks entfernen wenn nötig
```php
$callback = function() { /* ... */ };

// Hinzufügen
CMS\Hooks::addAction('cms_init', $callback);

// Entfernen (wenn nötig)
CMS\Hooks::removeAction('cms_init', $callback);
```

### 4. Type Hints verwenden
```php
CMS\Hooks::addFilter('template_name', function(string $template): string {
    return $template . '-custom';
});
```

### 5. Filter IMMER returnen
```php
// ❌ Vergisst Return
CMS\Hooks::addFilter('content', function($content) {
    $content = strtoupper($content);
    // FEHLER: Kein return!
});

// ✅ Korrektes Return
CMS\Hooks::addFilter('content', function($content) {
    return strtoupper($content);
});
```

## 🔌 Plugin-Beispiel

### Vollständiges Plugin mit Hooks

```php
<?php
/**
 * Plugin Name: Example Hook Plugin
 * Description: Demonstrates hook system
 * Version: 1.0.0
 */

declare(strict_types=1);

class Example_Hook_Plugin {
    private static ?self $instance = null;
    
    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Actions
        CMS\Hooks::addAction('cms_init', [$this, 'init']);
        CMS\Hooks::addAction('head', [$this, 'addMetaTags']);
        CMS\Hooks::addAction('home_content', [$this, 'addWidget']);
        
        // Filters
        CMS\Hooks::addFilter('template_name', [$this, 'overrideTemplate']);
        CMS\Hooks::addFilter('theme_color_primary', [$this, 'customColor']);
    }
    
    public function init(): void {
        // Plugin initialisieren
    }
    
    public function addMetaTags(): void {
        echo '<meta name="custom" content="value">';
    }
    
    public function addWidget(): void {
        echo '<div class="custom-widget">Custom Widget</div>';
    }
    
    public function overrideTemplate(string $template): string {
        if ($template === 'home') {
            return 'custom-home';
        }
        return $template;
    }
    
    public function customColor(string $color): string {
        return '#ff6b6b';
    }
}

Example_Hook_Plugin::instance();
```

## 🐛 Debugging Hooks

### Liste aller registrierten Hooks

```php
// In functions.php oder Plugin
if (CMS_DEBUG) {
    CMS\Hooks::addAction('cms_init', function() {
        // Reflection verwenden um Hooks zu listen
        $reflection = new ReflectionClass('CMS\Hooks');
        $actions = $reflection->getStaticPropertyValue('actions');
        $filters = $reflection->getStaticPropertyValue('filters');
        
        echo '<pre>';
        echo "Actions:\n";
        print_r(array_keys($actions));
        echo "\nFilters:\n";
        print_r(array_keys($filters));
        echo '</pre>';
    }, 999);
}
```

### Hook-Ausführung tracken

```php
// Wrapper für Debug-Logging
if (CMS_DEBUG) {
    $original_doAction = [CMS\Hooks::class, 'doAction'];
    
    CMS\Hooks::addAction('cms_init', function() {
        error_log('Hook executed: cms_init');
    }, 1);
}
```

## 📚 Weiterführende Dokumentation

- [Plugin Development Guide](PLUGIN-DEVELOPMENT.md)
- [Theme Development Guide](THEME-DEVELOPMENT.md)
- [API Reference](API-REFERENCE.md)
- [Architecture Guide](ARCHITECTURE.md)

## 🔗 Quick Reference

### Häufigste Hooks

**Frontend:**
- `head` - Meta-Tags, CSS
- `body_end` - Analytics, Scripts
- `home_content` - Homepage-Widgets

**Admin:**
- `admin_menu` - Admin-Menü
- `admin_dashboard_content` - Dashboard-Widgets

**System:**
- `cms_init` - Plugin-Init
- `register_routes` - Custom Routes
- `user_registered` - Nach Registrierung

### Häufigste Filter

- `template_name` - Template-Override
- `theme_color_*` - Farben anpassen
- `featured_content` - Content-Daten

---

**Letzte Aktualisierung:** 17. Februar 2026  
**Version:** 2.0.0  
**Hook-System-Version:** 2.0.0
