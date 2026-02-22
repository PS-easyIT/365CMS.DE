# Theme-Entwicklung für 365CMS

Komplette Anleitung zur Entwicklung von Themes für das 365CMS.

## 📋 Inhaltsverzeichnis

1. [Grundstruktur](#grundstruktur)
2. [Theme-Header](#theme-header)
3. [Template-Dateien](#template-dateien)
4. [Template-Hierarchie](#template-hierarchie)
5. [CSS & JavaScript](#css--javascript)
6. [Theme-Functions](#theme-functions)
7. [Best Practices](#best-practices)

## Grundstruktur

### Verzeichnis-Aufbau

```
themes/
└── mein-theme/
    ├── style.css          # Theme-Header & Haupt-CSS (PFLICHT)
    ├── functions.php      # Theme-Funktionen (Optional)
    ├── header.php         # Header-Template (PFLICHT)
    ├── footer.php         # Footer-Template (PFLICHT)
    ├── home.php           # Homepage (PFLICHT)
    ├── login.php          # Login-Seite
    ├── register.php       # Registrierungs-Seite
    ├── 404.php            # 404-Fehlerseite
    ├── error.php          # Generische Fehlerseite
    ├── assets/            # Zusätzliche Assets
    │   ├── css/
    │   ├── js/
    │   └── images/
    └── templates/         # Custom Templates
```

### Namenskonventionen

- **Verzeichnisname:** `mein-theme` (lowercase, mit Bindestrichen)
- **Haupt-CSS:** `style.css` (immer erforderlich!)
- **Templates:** `.php` Dateien
- **CSS-Klassen:** `theme-` Präfix verwenden

## Theme-Header

In `style.css` muss ein Header-Kommentar enthalten sein:

```css
/*
Theme Name: Mein Theme Name
Description: Kurze Beschreibung des Themes
Author: Dein Name
Author URI: https://example.com
*/
```

### Header-Felder

| Feld | Erforderlich | Beschreibung |
|------|--------------|--------------|
| `Theme Name` | ✅ Ja | Anzeigename im Admin |
| `Description` | ✅ Ja | Kurzbeschreibung |
| `Version` | ✅ Ja | Versionsnummer |
| `Author` | Nein | Theme-Entwickler |
| `Author URI` | Nein | Website des Entwicklers |

## Template-Dateien

### Haupt-Templates

#### header.php

Wird automatisch am Anfang jeder Seite eingebunden:

```php
<?php
/**
 * Theme Header
 */

use CMS\Security;
use CMS\Auth;

$security = Security::instance();
$auth = Auth::instance();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    
    <link rel="stylesheet" href="<?php echo CMS\ThemeManager::instance()->getThemeUrl(); ?>/style.css">
    
    <?php CMS\Hooks::doAction('head'); ?>
</head>
<body>
    
    <?php CMS\Hooks::doAction('body_start'); ?>
    
    <header class="site-header">
        <!-- Header-Content -->
    </header>
    
    <?php CMS\Hooks::doAction('after_header'); ?>
```

#### footer.php

Wird automatisch am Ende jeder Seite eingebunden:

```php
<?php
/**
 * Theme Footer
 */
?>

    <?php CMS\Hooks::doAction('before_footer'); ?>
    
    <footer class="site-footer">
        <!-- Footer-Content -->
    </footer>
    
    <?php CMS\Hooks::doAction('footer'); ?>
    <?php CMS\Hooks::doAction('body_end'); ?>
    
</body>
</html>
```

#### home.php

Homepage-Template:

```php
<?php
/**
 * Homepage Template
 */

use CMS\Security;

$security = Security::instance();
?>

<main>
    <section class="hero">
        <h1>Willkommen bei <?php echo SITE_NAME; ?></h1>
        <p>Ihre Homepage-Inhalte</p>
    </section>
    
    <?php CMS\Hooks::doAction('home_content'); ?>
</main>
```

#### login.php

Login-Seite:

```php
<?php
/**
 * Login Template
 */

use CMS\Security;

$security = Security::instance();
?>

<main>
    <div class="login-container">
        <h2>Anmelden</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo $security->escape($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo SITE_URL; ?>/login">
            <input type="hidden" name="csrf_token" value="<?php echo $security->generateToken('login'); ?>">
            
            <div class="form-group">
                <label for="username">Benutzername</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Passwort</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Anmelden</button>
        </form>
    </div>
</main>
```

#### register.php

Registrierungs-Seite (ähnlich wie login.php)

#### 404.php

404-Fehlerseite:

```php
<?php
/**
 * 404 Error Page
 */
?>

<main>
    <div class="error-page">
        <h1>404</h1>
        <h2>Seite nicht gefunden</h2>
        <p>Die angeforderte Seite existiert nicht.</p>
        <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">Zur Startseite</a>
    </div>
</main>
```

## Template-Hierarchie

Das CMS sucht Templates in dieser Reihenfolge:

1. **Custom Template** - `templates/{template}.php`
2. **Root Template** - `{template}.php`
3. **Index Fallback** - `index.php`

### Template laden

```php
// Im Theme-Manager
$themeManager = CMS\ThemeManager::instance();
$themeManager->render('home'); // Lädt home.php
```

## CSS & JavaScript

### Style.css Struktur

```css
/*
Theme Name: Mein Theme
Description: Beschreibung
Author: Name
*/

/* ==========================================================================
   Reset & Base
   ========================================================================== */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* ==========================================================================
   CSS Variables
   ========================================================================== */

:root {
    --primary-color: #2563eb;
    --secondary-color: #64748b;
    --text-color: #1e293b;
    --bg-color: #ffffff;
    
    --border-radius: 8px;
    --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* ==========================================================================
   Typography
   ========================================================================== */

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: var(--text-color);
    line-height: 1.6;
}

/* ... weitere Styles ... */
```

### JavaScript einbinden

In `functions.php`:

```php
<?php

CMS\Hooks::addAction('head', function() {
    $themeUrl = CMS\ThemeManager::instance()->getThemeUrl();
    echo '<script src="' . $themeUrl . '/assets/js/main.js"></script>';
});
```

Oder direkt in `header.php`:

```php
<script src="<?php echo CMS\ThemeManager::instance()->getThemeUrl(); ?>/assets/js/main.js"></script>
```

## Theme Functions

### functions.php

Diese Datei wird automatisch vom Theme-Manager geladen:

```php
<?php
/**
 * Theme Functions
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Setup
 */
function mein_theme_setup() {
    // Theme-Initialisierung
    
    // Navigation hinzufügen
    CMS\Hooks::addAction('main_nav', 'mein_theme_add_nav_items');
    
    // Footer-Widgets
    CMS\Hooks::addAction('footer_sections', 'mein_theme_footer_widgets');
    
    // Custom CSS
    CMS\Hooks::addAction('head', 'mein_theme_custom_css');
}

CMS\Hooks::addAction('theme_loaded', 'mein_theme_setup');

/**
 * Navigation Items hinzufügen
 */
function mein_theme_add_nav_items() {
    ?>
    <a href="<?php echo SITE_URL; ?>/about">Über uns</a>
    <a href="<?php echo SITE_URL; ?>/contact">Kontakt</a>
    <?php
}

/**
 * Footer Widgets
 */
function mein_theme_footer_widgets() {
    ?>
    <div class="footer-section">
        <h3>Support</h3>
        <p>Kontaktieren Sie uns bei Fragen.</p>
    </div>
    <?php
}

/**
 * Custom CSS
 */
function mein_theme_custom_css() {
    $primary = theme_get_option('primary_color', '#2563eb');
    ?>
    <style>
        :root {
            --primary-color: <?php echo $primary; ?>;
        }
    </style>
    <?php
}

/**
 * Theme-Option abrufen
 */
function theme_get_option($key, $default = '') {
    return get_option('theme_' . $key, $default);
}
```

## Best Practices

### 1. Responsive Design

Verwenden Sie Mobile-First CSS:

```css
/* Mobile (Default) */
.container {
    padding: 1rem;
}

/* Tablet */
@media (min-width: 768px) {
    .container {
        padding: 2rem;
    }
}

/* Desktop */
@media (min-width: 1024px) {
    .container {
        max-width: 1200px;
        margin: 0 auto;
    }
}
```

### 2. CSS-Variablen nutzen

```css
:root {
    --primary: #2563eb;
    --spacing-sm: 0.5rem;
    --spacing-md: 1rem;
    --spacing-lg: 2rem;
}

.button {
    background: var(--primary);
    padding: var(--spacing-md);
}
```

### 3. Security beim Output

Immer Ausgaben escapen:

```php
<?php echo esc_html($variable); ?>
<?php echo esc_url($url); ?>
<?php echo esc_attr($attribute); ?>
```

### 4. Hooks verwenden

Nutzen Sie Hooks für Erweiterbarkeit:

```php
<!-- Vor Header -->
<?php CMS\Hooks::doAction('before_header'); ?>

<!-- Navigation -->
<nav>
    <a href="/">Home</a>
    <?php CMS\Hooks::doAction('main_nav'); ?>
</nav>

<!-- Nach Header -->
<?php CMS\Hooks::doAction('after_header'); ?>
```

### 5. Accessibility

```html
<!-- Alt-Texte -->
<img src="image.jpg" alt="Beschreibung">

<!-- ARIA-Labels -->
<button aria-label="Menü öffnen">☰</button>

<!-- Semantisches HTML -->
<nav aria-label="Hauptnavigation">
    <ul>
        <li><a href="/">Home</a></li>
    </ul>
</nav>
```

### 6. Performance

- **CSS minimieren** in Production
- **Bilder optimieren** (WebP verwenden)
- **Lazy Loading** für Bilder
- **Critical CSS** inline im `<head>`

```html
<img src="image.jpg" loading="lazy" alt="...">
```

## Theme-Anpassungen

### Farben änderbar machen

```php
// In functions.php
function theme_customizer() {
    $primary = get_option('theme_primary_color', '#2563eb');
    ?>
    <style>
        :root {
            --primary-color: <?php echo esc_attr($primary); ?>;
        }
    </style>
    <?php
}
CMS\Hooks::addAction('head', 'theme_customizer');
```

### Custom Fonts

```php
CMS\Hooks::addAction('head', function() {
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
    </style>
    <?php
});
```

## Beispiel: Minimales Theme

### style.css
```css
/*
Theme Name: Minimal Theme
Description: Ein minimalistisches Theme
Author: Dein Name
*/

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    line-height: 1.6;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}
```

### header.php
```php
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo CMS\ThemeManager::instance()->getThemeUrl(); ?>/style.css">
</head>
<body>
    <header>
        <h1><?php echo SITE_NAME; ?></h1>
    </header>
```

### footer.php
```php
    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?></p>
    </footer>
</body>
</html>
```

## 🎨 Theme Customizer API (v0.26.13)

### Übersicht

Das 365CMS bietet einen vollständigen Theme Customizer mit 50+ Optionen in 8 Kategorien. Themes können diese Einstellungen über die API abrufen und in Templates verwenden.

### ThemeCustomizer Service

**Zugriff:**
```php
use CMS\Services\ThemeCustomizer;

$customizer = new ThemeCustomizer();
```

### Settings abrufen

#### Einzelnes Setting laden

```php
// Setting aus Kategorie laden
$primaryColor = $customizer->getSetting('colors', 'primary_color', '#2563eb');

// Fallback auf Default-Wert wenn nicht gesetzt
$fontSize = $customizer->getSetting('typography', 'font_size_base', '16');
```

#### Gesamte Kategorie laden

```php
// Alle Farb-Einstellungen
$colors = $customizer->getCategory('colors');

// Zugriff auf einzelne Werte
echo $colors['primary_color'];      // #2563eb
echo $colors['secondary_color'];    // #7c3aed
echo $colors['background_light'];   // #f8fafc
```

#### Alle Settings laden

```php
// Alle Theme-Einstellungen
$allSettings = $customizer->getAllSettings();

// Zugriff nach Kategorie
foreach ($allSettings as $category => $settings) {
    foreach ($settings as $key => $value) {
        echo "$category.$key = $value\n";
    }
}
```

### Settings speichern

```php
// Einzelnes Setting speichern
$customizer->setSetting('colors', 'primary_color', '#ff0000');

// Mehrere Settings auf einmal
$colorSettings = [
    'primary_color' => '#ff0000',
    'secondary_color' => '#00ff00',
    'success_color' => '#00ff00'
];

foreach ($colorSettings as $key => $value) {
    $customizer->setSetting('colors', $key, $value);
}
```

### CSS generieren

```php
// Automatische CSS-Generierung aus allen Settings
$css = $customizer->generateCSS();

// CSS in Datei speichern
file_put_contents(
    THEME_DIR . '/customizations.css',
    $css
);
```

**Generiertes CSS-Beispiel:**
```css
:root {
    --primary-color: #2563eb;
    --secondary-color: #7c3aed;
    --font-base: system-ui, -apple-system, sans-serif;
    --container-width: 1200px;
    --border-radius: 8px;
}

.container {
    max-width: var(--container-width);
}

.btn-primary {
    background-color: var(--primary-color);
}
```

### Import/Export

#### Export Settings

```php
// Alle Settings als JSON exportieren
$json = $customizer->exportSettings();

// JSON in Datei speichern
file_put_contents('theme-backup.json', $json);

// Für Download
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="theme-settings.json"');
echo $json;
```

#### Import Settings

```php
// JSON laden
$json = file_get_contents('theme-backup.json');

// Settings importieren
$result = $customizer->importSettings($json);

if ($result['success']) {
    echo "Importiert: {$result['imported']} Settings";
} else {
    echo "Fehler: {$result['message']}";
}
```

### Verfügbare Kategorien

#### 1. Colors (13 Optionen)
```php
$colors = $customizer->getCategory('colors');
// Keys: primary_color, secondary_color, success_color, warning_color,
//       error_color, background_light, background_medium, background_dark,
//       text_primary, text_secondary, text_light, border_color
```

#### 2. Typography (5 Optionen)
```php
$typography = $customizer->getCategory('typography');
// Keys: base_font, heading_font, font_size_base, line_height, heading_weight
```

**Verfügbare Fonts:**
- `system-ui` - System-Schrift (Standard)
- `inter` - Inter
- `roboto` - Roboto
- `open-sans` - Open Sans
- `lato` - Lato
- `montserrat` - Montserrat
- `poppins` - Poppins
- `raleway` - Raleway
- `source-sans-pro` - Source Sans Pro

#### 3. Layout (6 Optionen)
```php
$layout = $customizer->getCategory('layout');
// Keys: container_width, content_padding, border_radius,
//       section_spacing, sticky_header, back_to_top
```

#### 4. Header (5 Optionen)
```php
$header = $customizer->getCategory('header');
// Keys: background_color, text_color, height, logo_max_height, shadow
```

#### 5. Footer (5 Optionen)
```php
$footer = $customizer->getCategory('footer');
// Keys: background_color, text_color, link_color, widgets, columns
```

#### 6. Buttons (5 Optionen)
```php
$buttons = $customizer->getCategory('buttons');
// Keys: border_radius, padding_x, padding_y, font_weight, text_transform
```

#### 7. Performance (3 Optionen)
```php
$performance = $customizer->getCategory('performance');
// Keys: lazy_loading, minify_css, preload_fonts
```

#### 8. Advanced
```php
$advanced = $customizer->getCategory('advanced');
// Keys: custom_css, custom_js, debug_mode
```

### Theme-Integration

#### In header.php

```php
<?php
use CMS\Services\ThemeCustomizer;
$customizer = new ThemeCustomizer();

// Google Font laden
$baseFont = $customizer->getSetting('typography', 'base_font', 'system-ui');
$headingFont = $customizer->getSetting('typography', 'heading_font', 'system-ui');

if ($baseFont !== 'system-ui') {
    $fontUrl = "https://fonts.googleapis.com/css2?family=" . 
               str_replace(' ', '+', ucwords(str_replace('-', ' ', $baseFont))) . 
               ":wght@300;400;600;700&display=swap";
    echo "<link rel='stylesheet' href='$fontUrl'>\n";
}

// Generiertes CSS laden
if (file_exists(THEME_DIR . '/customizations.css')) {
    echo "<link rel='stylesheet' href='" . THEME_URL . "/customizations.css'>\n";
}

// Custom CSS
$customCSS = $customizer->getSetting('advanced', 'custom_css', '');
if (!empty($customCSS)) {
    echo "<style>\n$customCSS\n</style>\n";
}
?>
```

#### In footer.php

```php
<?php
use CMS\Services\ThemeCustomizer;
$customizer = new ThemeCustomizer();

// Custom JavaScript
$customJS = $customizer->getSetting('advanced', 'custom_js', '');
if (!empty($customJS)) {
    echo "<script>\n$customJS\n</script>\n";
}

// Back-to-Top Button
$backToTop = $customizer->getSetting('layout', 'back_to_top', '1');
if ($backToTop === '1') {
    echo '<a href="#top" class="back-to-top" aria-label="Nach oben">↑</a>';
}
?>
```

#### In functions.php

```php
<?php
use CMS\Services\ThemeCustomizer;
use CMS\Hooks;

// Theme-Support für Customizer
function theme_setup() {
    $customizer = new ThemeCustomizer();
    
    // Defaults setzen falls keine Settings vorhanden
    $defaults = [
        'colors' => [
            'primary_color' => '#2563eb',
            'secondary_color' => '#7c3aed'
        ],
        'typography' => [
            'base_font' => 'system-ui',
            'font_size_base' => '16'
        ],
        'layout' => [
            'container_width' => '1200',
            'border_radius' => '8'
        ]
    ];
    
    foreach ($defaults as $category => $settings) {
        foreach ($settings as $key => $value) {
            if ($customizer->getSetting($category, $key) === null) {
                $customizer->setSetting($category, $key, $value);
            }
        }
    }
}

Hooks::addAction('cms_init', 'theme_setup');
```

### Helper-Funktionen

```php
/**
 * Theme-Setting abrufen (Shorthand)
 */
function theme_get_setting($category, $key, $default = null) {
    $customizer = new CMS\Services\ThemeCustomizer();
    return $customizer->getSetting($category, $key, $default);
}

/**
 * CSS-Variable generieren
 */
function theme_css_var($name, $category, $key, $default = null) {
    $value = theme_get_setting($category, $key, $default);
    return "--{$name}: {$value};";
}

// Verwendung in Template:
// <div style="<?php echo theme_css_var('primary', 'colors', 'primary_color'); ?>">
```

### Admin Theme-Editor Zugriff

**URL:** `/admin/theme-editor.php`

**Features:**
- ✅ Live-Preview aller Einstellungen
- ✅ Tab-basierte Navigation (8 Kategorien)
- ✅ Color Picker für Farben
- ✅ Range Slider für Zahlen
- ✅ Dropdown für Fonts
- ✅ Textarea für Custom CSS/JS
- ✅ Export-Button (JSON Download)
- ✅ Import-Button (JSON Upload)
- ✅ CSS generieren Button
- ✅ Reset auf Defaults

### Best Practices

1. **Fallback-Werte:** Immer Default-Wert angeben
   ```php
   $color = $customizer->getSetting('colors', 'primary_color', '#2563eb');
   ```

2. **CSS-Variablen:** In Kombination mit CSS Custom Properties
   ```css
   :root {
       --primary: <?php echo theme_get_setting('colors', 'primary_color'); ?>;
   }
   ```

3. **Performance:** Settings cachen wenn möglich
   ```php
   static $colors = null;
   if ($colors === null) {
       $colors = $customizer->getCategory('colors');
   }
   ```

4. **Validierung:** User-Input validieren
   ```php
   // Farben validieren
   if (!preg_match('/^#[0-9a-f]{6}$/i', $color)) {
       $color = '#2563eb'; // Fallback
   }
   ```

5. **Dokumentation:** Theme-Defaults dokumentieren
   ```php
   /**
    * Theme Default Colors
    * 
    * - Primary: #2563eb (Blue)
    * - Secondary: #7c3aed (Purple)
    * - Success: #10b981 (Green)
    */
   ```

### home.php
```php
<main class="container">
    <h2>Willkommen</h2>
    <p>Dies ist die Homepage.</p>
</main>
```

## Checkliste für Theme-Release

- [ ] style.css mit vollständigem Header
- [ ] header.php erstellt
- [ ] footer.php erstellt
- [ ] home.php erstellt
- [ ] login.php erstellt
- [ ] register.php erstellt
- [ ] 404.php erstellt
- [ ] Responsive Design getestet
- [ ] Cross-Browser getestet
- [ ] Accessibility geprüft
- [ ] Performance optimiert
- [ ] Hooks für Erweiterungen vorhanden
- [ ] Dokumentation erstellt

## Verfügbare Helper

### Theme-Manager-Methoden

```php
$theme = CMS\ThemeManager::instance();

// Theme-Pfad
$path = $theme->getThemePath();

// Theme-URL
$url = $theme->getThemeUrl();

// Template rendern
$theme->render('home', ['data' => 'value']);

// Header/Footer
$theme->getHeader();
$theme->getFooter();
```

### Template-Helper

```php
// Security
$security = CMS\Security::instance();
echo $security->escape($text);

// Auth
$auth = CMS\Auth::instance();
if ($auth->isLoggedIn()) { }

// User
$user = current_user();
echo $user->display_name;
```
