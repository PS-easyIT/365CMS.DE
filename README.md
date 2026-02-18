# 365CMS.DE

Ein sicheres, modulares und erweiterbares Content Management System mit Plugin- und Theme-Support.

## 🚀 Features

- ✅ **Modulare Architektur** - Saubere OOP-Struktur mit Singleton-Pattern
- ✅ **Plugin-System** - WordPress-ähnliches Hook-System für einfache Erweiterungen
- ✅ **Theme-Support** - Flexibles Template-System
- ✅ **Sicherheit** - CSRF-Schutz, XSS-Prevention, Rate Limiting, Prepared Statements
- ✅ **Performance** - Optimierte Datenbankabfragen, minimaler Bootstrap
- ✅ **Benutzerverwaltung** - Login, Register, Rollen (Admin/Member)
- ✅ **Responsive Design** - Mobile-First Ansatz
- ✅ **Admin-Backend** - Vollständiges Admin-Panel

## 📋 Systemanforderungen

- PHP 8.3+
- MySQL 5.7+ / MariaDB 10.3+
- Apache mit mod_rewrite
- PDO Extension

## 🔧 Installation

### 1. Dateien hochladen

Laden Sie alle Dateien in Ihr Webserver-Verzeichnis (z.B. `/htdocs/CMSv2/`)

### 2. Datenbank erstellen

```sql
CREATE DATABASE cms_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Konfiguration anpassen

Bearbeiten Sie `config.php`:

```php
// Datenbank-Zugangsdaten
define('DB_HOST', 'localhost');
define('DB_NAME', 'cms_v2');
define('DB_USER', 'root');
define('DB_PASS', 'ihr_passwort');

// WICHTIG: Ändern Sie die Security Keys!
define('AUTH_KEY', 'ihre-eindeutige-phrase-hier');
define('SECURE_AUTH_KEY', 'ihre-eindeutige-phrase-hier');
define('NONCE_KEY', 'ihre-eindeutige-phrase-hier');

// Site-URL anpassen
define('SITE_URL', 'http://localhost/CMSv2');
define('SITE_URL_PATH', '/CMSv2');
```

### 4. .htaccess anpassen

Wenn Ihr CMS in einem Unterverzeichnis liegt, passen Sie in `.htaccess` an:

```apache
RewriteBase /CMSv2/
```

### 5. Verzeichnis-Berechtigungen

```bash
chmod 755 uploads/
chmod 644 config.php
```

### 6. Installation aufrufen

Öffnen Sie im Browser: `http://localhost/CMSv2/`

Das CMS erstellt automatisch:
- Alle benötigten Datenbank-Tabellen
- Einen Admin-User (Username: `admin`, Passwort: `admin123`)

### 7. Erste Schritte

1. **Login**: Melden Sie sich mit `admin` / `admin123` an
2. **Passwort ändern**: WICHTIG - Ändern Sie sofort das Admin-Passwort!
3. **Admin-Bereich**: Gehen Sie zu `/admin`
4. **Plugins**: Aktivieren Sie das Beispiel-Plugin unter `/admin/plugins`

## 📁 Verzeichnisstruktur

```
CMSv2/
├── core/               # Kern-System
│   ├── Bootstrap.php   # System-Initialisierung
│   ├── Database.php    # Datenbank-Wrapper
│   ├── Security.php    # Sicherheitsfunktionen
│   ├── Auth.php        # Authentifizierung
│   ├── Router.php      # URL-Routing
│   ├── Hooks.php       # Hook-System
│   ├── PluginManager.php
│   └── ThemeManager.php
├── admin/              # Admin-Backend
│   ├── index.php       # Dashboard
│   ├── plugins.php     # Plugin-Verwaltung
│   ├── themes.php      # Theme-Verwaltung
│   ├── users.php       # Benutzer-Verwaltung
│   └── settings.php    # Einstellungen
├── member/             # Mitgliederbereich
│   └── index.php       # Member-Dashboard
├── themes/             # Themes
│   └── default/        # Standard-Theme
│       ├── style.css
│       ├── header.php
│       ├── footer.php
│       ├── home.php
│       ├── login.php
│       ├── register.php
│       └── functions.php
├── plugins/            # Plugins
│   └── example-plugin/
│       └── example-plugin.php
├── assets/             # CSS & JS
│   ├── css/
│   └── js/
├── includes/           # Helper-Funktionen
│   └── functions.php
├── uploads/            # User-Uploads
├── index.php           # Bootstrap-Datei
├── config.php          # Konfiguration
└── .htaccess           # Apache-Konfiguration
```

## 🔌 Plugin erstellen

### Basis-Struktur

```
plugins/
└── mein-plugin/
    └── mein-plugin.php
```

### Plugin-Code

```php
<?php
/**
 * Plugin Name: Mein Plugin
 * Description: Plugin-Beschreibung
 * Version: 1.0.0
 * Author: Ihr Name
 */

declare(strict_types=1);

class Mein_Plugin {
    private static ?self $instance = null;
    
    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Action Hook - Fügt Content ein
        CMS\Hooks::addAction('home_content', [$this, 'add_content'], 10);
        
        // Filter Hook - Modifiziert Daten
        CMS\Hooks::addFilter('template_name', [$this, 'modify_template'], 10);
    }
    
    public function add_content(): void {
        echo '<div>Mein Plugin-Content</div>';
    }
    
    public function modify_template(string $template): string {
        return $template;
    }
}

Mein_Plugin::instance();
```

### Verfügbare Hooks

**Actions:**
- `cms_init` - System initialisiert
- `cms_before_route` - Vor Routing
- `cms_after_route` - Nach Routing
- `before_header` - Vor Header
- `after_header` - Nach Header
- `before_footer` - Vor Footer
- `after_footer` - Nach Footer
- `head` - In HTML `<head>`
- `body_start` - Nach `<body>`
- `body_end` - Vor `</body>`
- `home_content` - Auf Homepage
- `admin_menu` - Admin-Menü
- `admin_dashboard_content` - Admin-Dashboard
- `member_dashboard_content` - Member-Dashboard

**Filters:**
- `template_name` - Template-Name ändern
- `theme_color_*` - Theme-Farben

## 🎨 Theme erstellen

### Struktur

```
themes/
└── mein-theme/
    ├── style.css       # Theme-Header & Styles
    ├── header.php      # Header-Template
    ├── footer.php      # Footer-Template
    ├── home.php        # Homepage
    ├── login.php       # Login-Seite
    ├── register.php    # Registrierung
    ├── 404.php         # 404-Seite
    └── functions.php   # Theme-Funktionen
```

### style.css Header

```css
/*
Theme Name: Mein Theme
Description: Theme-Beschreibung
Version: 1.0.0
Author: Ihr Name
*/
```

## 🔒 Sicherheit

### Produktiv-Umgebung

Bevor Sie live gehen:

1. **Debug deaktivieren** in `config.php`:
   ```php
   define('CMS_DEBUG', false);
   ```

2. **Security Keys ändern** - Generieren Sie neue eindeutige Schlüssel

3. **HTTPS aktivieren** in `.htaccess`:
   ```apache
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

4. **config.php schützen** - Verschieben Sie außerhalb des Web-Root wenn möglich

5. **Regelmäßige Updates** - Halten Sie PHP und MySQL aktuell

## 🛠️ Entwicklung

### Debug-Modus

```php
// In config.php
define('CMS_DEBUG', true);
```

### Helper-Funktionen

```php
// Ausgabe escapen
echo esc_html($text);
echo esc_url($url);
echo esc_attr($attribute);

// Sanitierung
$clean = sanitize_text($input);
$email = sanitize_email($email);

// Optionen
$value = get_option('key', 'default');
update_option('key', $value);

// User-Checks
if (is_logged_in()) { }
if (is_admin()) { }
$user = current_user();

// Debug
dd($variable); // Dump & Die (nur wenn CMS_DEBUG = true)
```

## 🆘 Troubleshooting

### Seite zeigt nur weißen Bildschirm

- PHP-Fehlerlog prüfen
- Debug-Modus aktivieren
- PHP-Version prüfen (min. 8.3)

### Plugins werden nicht geladen

- Verzeichnis-Berechtigungen prüfen
- Plugin-Dateiname muss mit Verzeichnis übereinstimmen
- Plugin-Header korrekt?

### 404-Fehler

- `.htaccess` korrekt?
- `RewriteBase` angepasst?
- mod_rewrite aktiv?

## 📄 Lizenz

Freie Verwendung für private Projekte.
Kostenpflichtig für Geschäftliche Projekte.

## 👨‍💻 Support

Bei Fragen oder Problemen erstellen Sie ein Issue im Repository.

---

**Entwickelt mit ❤️ für moderne IT-Netzwerke**
