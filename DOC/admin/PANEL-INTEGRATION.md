# Admin Panel - Entwickler-Dokumentation

## Übersicht

Das Admin Panel bietet ein vollständiges Verwaltungsinterface mit Sidebar-Navigation und Content-Bereich. Plugins können eigene Menüpunkte und Seiten hinzufügen.

## Struktur

```
admin/
├── index.php       # Dashboard (Standardseite)
├── pages.php       # Seitenverwaltung
├── users.php       # Benutzerverwaltung
└── settings.php    # Systemeinstellungen
```

## Zugriff

Das Admin Panel ist über `/admin` erreichbar und nur für Administratoren zugänglich.

## Plugin-Integration

### 1. Menüpunkte hinzufügen

Plugins können über den `admin_menu_items` Filter eigene Menüpunkte hinzufügen:

```php
<?php
// In Ihrer Plugin-Datei
use CMS\Hooks;

Hooks::addFilter('admin_menu_items', function($items) {
    // Neuen Menüpunkt hinzufügen
    $items[] = [
        'slug' => 'my-plugin',
        'label' => 'Mein Plugin',
        'icon' => '🔌',
        'url' => '/admin/my-plugin',
        'active' => false
    ];
    
    return $items;
}, 10);
```

### 2. Admin-Seite erstellen

Erstellen Sie eine Datei `admin/my-plugin.php` im Hauptverzeichnis:

```php
<?php
declare(strict_types=1);

use CMS\Auth;
use CMS\Hooks;

if (!defined('ABSPATH')) {
    exit;
}

// Security check
if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

// Menü-Items mit aktivem Tab
$menuItems = [
    ['slug' => 'dashboard', 'label' => 'Dashboard', 'icon' => '📊', 'url' => '/admin', 'active' => false],
    // ... weitere Standard-Items
    ['slug' => 'my-plugin', 'label' => 'Mein Plugin', 'icon' => '🔌', 'url' => '/admin/my-plugin', 'active' => true]
];

$menuItems = Hooks::applyFilter('admin_menu_items', $menuItems);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mein Plugin - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
</head>
<body class="admin-body">
    
    <!-- Admin Sidebar -->
    <div class="admin-sidebar">
        <h1><?php echo htmlspecialchars(SITE_NAME); ?></h1>
        
        <nav class="admin-nav">
            <?php foreach ($menuItems as $item): ?>
                <a href="<?php echo htmlspecialchars($item['url']); ?>" 
                   class="nav-item <?php echo $item['active'] ? 'active' : ''; ?>">
                    <span class="nav-icon"><?php echo $item['icon']; ?></span>
                    <?php echo htmlspecialchars($item['label']); ?>
                </a>
            <?php endforeach; ?>
            
            <hr>
            
            <a href="<?php echo SITE_URL; ?>" class="nav-item">
                <span class="nav-icon">🏠</span>
                Zur Website
            </a>
            
            <a href="<?php echo SITE_URL; ?>/logout" class="nav-item">
                <span class="nav-icon">🚪</span>
                Abmelden
            </a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="admin-content">
        
        <div class="admin-page-header">
            <h2>Mein Plugin</h2>
        </div>
        
        <!-- Ihr Plugin-Inhalt hier -->
        <div class="admin-section">
            <h3>Plugin-Einstellungen</h3>
            <p>Hier kommt der Inhalt Ihres Plugins.</p>
        </div>
        
    </div>
    
    <script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
    
</body>
</html>
```

### 3. Dashboard-Widgets hinzufügen

Plugins können Widgets zum Dashboard hinzufügen:

```php
Hooks::addAction('admin_dashboard_widgets', function() {
    ?>
    <div class="admin-section">
        <h3>🔌 Mein Plugin Widget</h3>
        <p>Zusätzliche Informationen von meinem Plugin.</p>
    </div>
    <?php
}, 10);
```

### 4. Einstellungen-Seite erweitern

```php
Hooks::addAction('admin_settings_page', function() {
    ?>
    <div class="admin-section">
        <h3>Mein Plugin Einstellungen</h3>
        <form method="POST" class="settings-form">
            <div class="form-group">
                <label for="plugin_option">
                    <strong>Plugin-Option</strong>
                </label>
                <input type="text" 
                       id="plugin_option" 
                       name="plugin_option" 
                       class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Speichern</button>
        </form>
    </div>
    <?php
}, 10);
```

## Verfügbare CSS-Klassen

### Layout
- `.admin-body` - Body-Klasse
- `.admin-sidebar` - Sidebar-Navigation
- `.admin-content` - Haupt-Content-Bereich
- `.admin-page-header` - Seiten-Header

### Komponenten
- `.admin-section` - Content-Sektion
- `.dashboard-grid` - Dashboard-Grid (4 Spalten)
- `.stat-card` - Statistik-Karte
- `.info-grid` - Info-Grid (3 Spalten)
- `.info-card` - Info-Karte

### Tabellen
- `.users-table-container` - Tabellen-Container
- `.users-table` - Tabelle
- `.status-badge` - Status-Badge
- `.role-badge` - Rollen-Badge

### Formulare
- `.settings-form` - Formular
- `.form-group` - Formular-Gruppe
- `.form-control` - Input-Feld
- `.form-text` - Hilfetext

### Buttons
- `.btn` - Basis-Button
- `.btn-primary` - Primary Button (blau)
- `.btn-secondary` - Secondary Button (grau)

### Alerts
- `.alert` - Alert-Box
- `.alert-success` - Erfolgs-Nachricht (grün)
- `.alert-error` - Fehler-Nachricht (rot)

## Routing

Der Router erkennt automatisch Admin-Seiten:
- `/admin` → `admin/index.php`
- `/admin/pages` → `admin/pages.php`
- `/admin/my-plugin` → `admin/my-plugin.php`

## Sicherheit

### 1. Admin-Zugriff prüfen

```php
if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}
```

### 2. CSRF-Schutz

```php
use CMS\Security;

// Token generieren
$csrfToken = Security::instance()->generateToken('action_name');

// In Form:
<input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

// Validieren:
if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'action_name')) {
    $_SESSION['error'] = 'Sicherheitsüberprüfung fehlgeschlagen.';
    header('Location: /admin');
    exit;
}
```

### 3. Input-Sanitierung

```php
// Immer User-Input escapen:
$title = htmlspecialchars($_POST['title'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
```

## Hooks-Übersicht

### Filter
- `admin_menu_items` - Menüpunkte modifizieren/hinzufügen

### Actions
- `admin_dashboard_widgets` - Dashboard-Widgets hinzufügen
- `admin_settings_page` - Einstellungen-Seite erweitern

## Best Practices

1. **Konsistente Icons** - Verwenden Sie Emojis oder einheitliche Icon-Sets
2. **CSRF-Schutz** - Immer bei Formularen verwenden
3. **Eingabe-Validierung** - Alle User-Inputs validieren und sanitizen
4. **Responsive Design** - Die vorhandenen CSS-Klassen sind bereits responsive
5. **Error Handling** - Fehler über `$_SESSION['error']` zurückgeben
6. **Success Messages** - Erfolge über `$_SESSION['success']` mitteilen

## Beispiel: Vollständige Plugin-Integration

```php
<?php
/**
 * My Plugin
 */
declare(strict_types=1);

namespace MyPlugin;

use CMS\Hooks;
use CMS\PluginManager;

class MyPlugin {
    private static ?self $instance = null;
    
    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->registerHooks();
    }
    
    private function registerHooks(): void {
        // Admin-Menü erweitern
        Hooks::addFilter('admin_menu_items', [$this, 'addAdminMenu'], 10);
        
        // Dashboard-Widget
        Hooks::addAction('admin_dashboard_widgets', [$this, 'dashboardWidget'], 10);
    }
    
    public function addAdminMenu(array $items): array {
        $items[] = [
            'slug' => 'my-plugin',
            'label' => 'Mein Plugin',
            'icon' => '🔌',
            'url' => '/admin/my-plugin',
            'active' => false
        ];
        return $items;
    }
    
    public function dashboardWidget(): void {
        ?>
        <div class="admin-section">
            <h3>🔌 Mein Plugin Status</h3>
            <p>Plugin läuft erfolgreich!</p>
        </div>
        <?php
    }
}

// Plugin initialisieren
MyPlugin::instance();
```

## Support

Bei Fragen zur Admin Panel Integration:
- Siehe `doc/admin/ADMIN-GUIDE.md`
- Hook-System: `doc/HOOKS-REFERENCE.md`
- Sicherheit: `doc/SECURITY.md`
