# 365CMS.DE | V0.8.24
 ---
## Sicheres, modulares und erweiterbares Content Management System
---
## [WWW.365CMS.DE](HTTPS://365CMS.DE).

> **Status:** `Stable` · Öffentliches Release seit v0.5.0 (Januar 2026) · Versionierung nach [SemVer 0.x](https://semver.org/)
> Interne Entwicklungsversionen (0.1.0–0.4.x, 2025) sind nicht öffentlich verfügbar.

Ein sicheres, modulares und erweiterbares Content Management System — **Stable Release**.

## 🚀 Features

### Core-System
- ✅ **Modulare Architektur** - OOP-Struktur mit Singleton-Pattern und Namespaces
- ✅ **Plugin-System** - WordPress-ähnliches Hook-System für einfache Erweiterungen
- ✅ **Theme-System** - Flexibles Template-System mit Live-Customization
- ✅ **Theme-Editor** - Vollständiger visueller Theme-Customizer mit 50+ Optionen
- ✅ **Sicherheit** - CSRF-Schutz, XSS-Prevention, Rate Limiting, Prepared Statements
- ✅ **Performance** - Optimierte PDO-Abfragen, Query-Caching, CacheManager
- ✅ **Debug-System** - Logging nach `/logs/debug-YYYY-MM-DD.log` (nur bei `CMS_DEBUG=true`)
- ✅ **Admin-Dashboard** - Vollständiges Backend mit Version-Badge und Schnellzugriff

### Admin-Backend
- ✅ **Benutzerverwaltung** - Stat-Cards, Rollen-Tabs, Suche, Bulk-Aktionen, Gruppen-Zuordnung
- ✅ **Gruppen & Rollen** - 8 Capability-Checkboxen, Mitgliederlisten, Rollen-Verwaltung
- ✅ **Seiten** - WYSIWYG-Editor, SEO-Felder, Revisionen
- ✅ **Blog/Beiträge** - Post-Verwaltung mit Kategorien und Tags
- ✅ **Media-Bibliothek** - Upload, Galerie, Media-Proxy, MIME-Filterung
- ✅ **Navigation** - Menü-Verwaltung mit Sortierung
- ✅ **SEO** - Meta-Tags, Open Graph, Sitemap, Robots.txt
- ✅ **Analytics** - Besucherstatistiken, Top-Seiten, System-Health, Cache-Stats
- ✅ **Landing Pages** - Visueller Landing-Page-Builder
- ✅ **Backup** - DB/Dateisystem-Backup, E-Mail-Versand, S3-Support
- ✅ **Performance-Tools** - Cache leeren, Optimierungen, Laufzeitmetriken
- ✅ **Updates** - Core- und Plugin-Update-Prüfung via GitHub API
- ✅ **Design-Tools** - Dashboard-Widgets, Lokale Fonts
- ✅ **Settings & System** - CMS-Konfiguration, Diagnose, PHP/DB-Info

### Abo-Verwaltung
- 💳 **Pakete** - Übersicht mit inline Edit/Delete, Neues-Paket-Modal, Feature-Limits
- ⚙️ **Einstellungen** - Abo-Toggle, Währung, Zahlungsmethoden, Rechtliche Seiten, Bestellnummern-Format
- 🔗 **Zuweisungen** - Benutzer-Abos & Gruppen-Pakete in einer Ansicht
- 🛒 **Bestellungen** - Order-Management mit Status-Tracking (Bestätigt/Storniert/Erstattet)

### DSGVO-Suite
- 🍪 **Cookie-Verwaltung** - Cookie-Kategorien, Consent-Management
- 📥 **Datenzugriff** - Automatisierte Datenauskunfts-Anfragen
- 🗑 **Datenlöschung** - DSGVO-konforme Löschanträge
- 🔒 **Datenschutz** - Member-seitige Datenschutz-Einstellungen

### Member-Bereich
- 👤 **Profil** - Profilbearbeitung, Avatar, Bio, Social Links
- 💬 **Nachrichten** - Privates Messaging-System
- 🔔 **Benachrichtigungen** - System- und User-Benachrichtigungen
- ❤️ **Favoriten** - Inhalte als Favoriten markieren
- 🖼️ **Medien** - Eigene Medien verwalten und hochladen
- 🛡️ **Sicherheit** - Passwortänderung, Login-Protokoll
- 💰 **Mitgliedschaft** - Abo-Übersicht, Upgrade/Downgrade, Checkout

### Theme-System Features
- 🎨 **Live Theme Customization** - Über 50 Anpassungsoptionen in 8 Kategorien
- 🎨 **CSS-Generator** - Automatische CSS-Generierung aus Einstellungen
- 🎨 **Import/Export** - Theme-Einstellungen sichern und teilen
- 🎨 **Google Fonts** - 8 integrierte Webfonts mit Auto-Loading
- 🎨 **Custom CSS/JS** - Eigene Styles und Scripts hinzufügen
- 🎨 **Responsive Design** - Mobile-First Ansatz mit Dark Mode Support
- 🎨 **Performance-Optionen** - Lazy Loading, Minifikation, Preloading

### System & Diagnose
- 🔧 **System-Monitoring** - Echtzeit-Status von PHP, MySQL, Dateisystem
- 🔧 **Datenbank-Tools** - Reparatur, Optimierung, Backup-Funktionen
- 🔧 **Cache-Management** - Intelligent caching mit Auto-Clearing
- 🔧 **Security-Audit** - Sicherheitsüberprüfung und Failed-Login-Tracking
- 🔧 **Activity-Log** - Vollständige Aktivitätsverfolgung

## 📋 Systemanforderungen

- **PHP:** 8.3+ (strict typing, return types)
- **MySQL:** 5.7+ / MariaDB 10.3+
- **Webserver:** Apache 2.4+ mit mod_rewrite
- **PHP Extensions:** PDO, PDO_MySQL, mbstring, JSON
- **Speicher:** Minimum 256MB RAM
- **Disk Space:** 100MB+ für CMS + Uploads

## 🔧 Installation

### 1. Dateien hochladen

Laden Sie alle Dateien in Ihr Webserver-Verzeichnis:
```bash
# Beispiel-Struktur
/var/www/html/365CMS/
# oder
C:/xampp/htdocs/365CMS/
```

**Wenn CMS in Unterverzeichnis:**
```apache
# In .htaccess
RewriteBase /365CMS/
```

**Für HTTPS-Redirect (Produktion):**
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### Verzeichnis-Berechtigungen

```bash
# Linux/Mac
chmod 755 uploads/
chmod 755 cache/
chmod 755 logs/
chmod 644 config.php
chmod 644 .htaccess

# Oder via FTP
uploads/ → 755
cache/ → 755
logs/ → 755
config.php → 644
```

### Installation durchführen

1. **Browser öffnen:** `https://ihre-domain.de/install.php`
2. **Datenbank-Tabellen werden automatisch erstellt**
3. **Admin-User anlegen**
4. **Weiterleitung zum Dashboard**

### Erste Schritte (WICHTIG!)
✅ **install.php löschen:** Nach erfolgreicher Installation!

## 📁 Verzeichnisstruktur

```
365CMS/
├── core/                      # Kern-System (PSR-4)
│   ├── Bootstrap.php          # System-Initialisierung
│   ├── Database.php           # PDO-Wrapper mit prepared statements
│   ├── Security.php           # CSRF, XSS, Rate Limiting
│   ├── Auth.php               # Authentifizierung & Sessions
│   ├── Router.php             # URL-Routing
│   ├── Hooks.php              # WordPress-like Hook-System
│   ├── PluginManager.php      # Plugin-Verwaltung
│   ├── ThemeManager.php       # Theme-Verwaltung
│   ├── PageManager.php        # Seiten-Management
│   ├── Api.php                # REST API Endpoints
│   └── Services/              # Service-Layer
│       ├── SystemService.php  # System-Diagnose
│       ├── ThemeCustomizer.php # Theme-Anpassungen
│       └── LandingPageService.php
├── admin/                     # Admin-Backend
│   ├── index.php              # Dashboard
│   ├── plugins.php            # Plugin-Verwaltung
│   ├── theme-editor.php       # Theme Editor (NEU!)
│   ├── users.php              # Benutzer-Verwaltung
│   ├── system.php             # System & Diagnose
│   └── settings.php           # Globale Einstellungen
├── member/                    # Mitgliederbereich
│   └── index.php              # Member-Dashboard
├── themes/                    # Theme-Verzeichnis
│   └── default/               # Standard-Theme
│       ├── theme.json         # Theme-Konfiguration (NEU!)
│       ├── style.css          # Basis-Styles
│       ├── customizations.css # Generiertes CSS (auto)
│       ├── README.md          # Theme-Dokumentation
│       ├── header.php         # Header-Template
│       ├── footer.php         # Footer-Template
│       ├── home.php           # Homepage
│       ├── page.php           # Standard-Seite
│       ├── login.php          # Login-Seite
│       ├── register.php       # Registrierung
│       ├── 404.php            # 404-Fehlerseite
│       ├── error.php          # Fehlerseite
│       └── functions.php      # Theme-Funktionen
├── plugins/                   # Plugin-Verzeichnis
│   ├── cms-booking/           # Buchungssystem
│   ├── cms-events/            # Event-Management
│   ├── cms-experts/           # Experten-Verzeichnis
│   ├── cms-companies/         # Firmen-Verzeichnis
│   ├── cms-speakers/          # Referenten-Verwaltung
│   ├── cms-projects/          # Projekt-Management
│   ├── cms-seo/               # SEO-Optimierung
│   └── cms-contact/           # Kontaktformular
├── assets/                    # Statische Assets
│   ├── css/
│   │   └── admin.css          # Admin-Styles (1850+ Zeilen)
│   ├── js/
│   │   ├── admin.js           # Admin-JavaScript
│   │   └── theme.js           # Theme-JavaScript
│   └── images/                # Bilder
├── includes/                  # Helper-Funktionen
│   └── functions.php          # Global functions
├── uploads/                   # User-Uploads
├── cache/                     # Cache-Verzeichnis
├── logs/                      # Log-Dateien
├── config/                    # Konfigurationen
├── doc/                       # Dokumentation
│   ├── INDEX.md               # Dokumentations-Übersicht
│   ├── INSTALLATION.md        # Installations-Guide
│   ├── ARCHITECTURE.md        # System-Architektur
│   ├── DATABASE-SCHEMA.md     # Datenbank-Schema
│   ├── HOOKS-REFERENCE.md     # Hook-Referenz
│   ├── SECURITY.md            # Sicherheits-Guide
│   ├── THEME-DEVELOPMENT.md   # Theme-Entwicklung
│   ├── admin/                 # Admin-Dokumentation
│   ├── plugins/               # Plugin-Dokumentation
│   └── workflow/              # Workflow-Guides
├── index.php                  # Bootstrap-Datei
├── config.php                 # Konfiguration (gitignored!)
├── config.sample.php          # Config-Vorlage
├── install.php                # Installations-Skript
├── .htaccess                  # Apache-Konfiguration
└── README.md                  # Diese Datei
```

## 🎨 Theme-Editor verwenden

### Zugriff

Admin-Panel → **Theme Editor** (🎨 Icon)

### Verfügbare Kategorien

1. **Farben** - Vollständige Farbpalette (13 Optionen)
   - Primär-, Sekundär-, Erfolgs-, Warn-, Fehlerfarben
   - Hintergrundfarben (Hell, Mittel, Dunkel)
   - Textfarben (Primär, Sekundär, Hell)
   - Rahmenfarbe

2. **Typografie** - Schriften und Text (5 Optionen)
   - Basis-Schriftart (System + 8 Google Fonts)
   - Überschriften-Schriftart
   - Schriftgröße (12-20px)
   - Zeilenhöhe (1.2-2.0)
   - Font-Gewicht für Überschriften

3. **Layout** - Seitenlayout (6 Optionen)
   - Container-Breite (960-1600px)
   - Inhalts-Padding
   - Ecken-Radius (Border-Radius)
   - Sektions-Abstände
   - Sticky Header (Ein/Aus)
   - Back-to-Top Button (Ein/Aus)

4. **Header** - Header-Bereich (5 Optionen)
   - Hintergrundfarbe
   - Textfarbe
   - Header-Höhe (60-120px)
   - Logo Max-Höhe (30-80px)
   - Schatten Ein/Aus

5. **Footer** - Footer-Bereich (5 Optionen)
   - Hintergrundfarbe
   - Textfarbe
   - Link-Farbe
   - Footer-Widgets Ein/Aus
   - Spaltenanzahl (1-4)

6. **Buttons** - Button-Styling (5 Optionen)
   - Border-Radius
   - Padding (X/Y)
   - Schriftstärke
   - Text-Transformation

7. **Performance** - Optimierungen (3 Optionen)
   - Lazy Loading
   - CSS Minifikation
   - Font Preloading

8. **Erweitert** - Advanced Features
   - Custom CSS Editor
   - Custom JavaScript
   - Debug-Modus

### Workflow

1. **Kategorie wählen** - Tab anklicken
2. **Einstellungen anpassen** - Color Picker, Slider, Dropdown
3. **Speichern** - "Änderungen Speichern" Button
4. **CSS generieren** - Optional für Performance
5. **Exportieren** - Backup erstellen (JSON)

## 🔌 Plugin erstellen

### Basis-Struktur

```
plugins/
└── mein-plugin/
    └── mein-plugin.php
```

### Minimales Plugin

```php
<?php
/**
 * Plugin Name: Mein Plugin
 * Description: Plugin-Beschreibung
 * Version: 1.0.0
 * Author: Ihr Name
 */

declare(strict_types=1);

namespace MeinPlugin;

class MeinPlugin {
    private static ?self $instance = null;
    
    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->initHooks();
    }
    
    private function initHooks(): void {
        // Action Hooks
        \CMS\Hooks::addAction('cms_init', [$this, 'init'], 10);
        \CMS\Hooks::addAction('home_content', [$this, 'addContent'], 10);
        
        // Filter Hooks
        \CMS\Hooks::addFilter('template_name', [$this, 'modifyTemplate'], 10);
    }
    
    public function init(): void {
        // Plugin-Initialisierung
    }
    
    public function addContent(): void {
        echo '<div class="my-plugin-content">Plugin Content</div>';
    }
    
    public function modifyTemplate(string $template): string {
        return $template;
    }
}

// Plugin instanziieren
MeinPlugin::instance();
```

### Mit Datenbank-Tabellen

```php
private function createTables(): void {
    $db = \CMS\Database::instance();
    $pdo = $db->getConnection();
    $prefix = $db->getPrefix();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$prefix}mein_plugin_data (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content LONGTEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_title (title)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql);
}
```

### Verfügbare Hooks

**Core Actions:**
- `cms_init` - System initialisiert
- `cms_before_route` - Vor Routing
- `cms_after_route` - Nach Routing
- `theme_loaded` - Theme geladen
- `admin_init` - Admin-Bereich initialisiert
- `member_init` - Member-Bereich initialisiert

**Template Actions:**
- `head` - HTML `<head>` Bereich
- `body_start` - Nach `<body>` Tag
- `body_end` - Vor `</body>` Tag
- `before_header` - Vor Header
- `after_header` - Nach Header
- `before_footer` - Vor Footer
- `after_footer` - Nach Footer
- `main_nav` - Hauptnavigation
- `footer_sections` - Footer-Sektionen
- `home_content` - Homepage-Content

**Admin Actions:**
- `admin_menu` - Admin-Menü
- `admin_dashboard_content` - Dashboard-Content
- `admin_head` - Admin `<head>`

**Filter Hooks:**
- `template_name` - Template-Name ändern
- `page_content` - Seiten-Content modifizieren
- `theme_color_*` - Theme-Farben anpassen
- `admin_menu_items` - Admin-Menü erweitern

Vollständige Hook-Referenz: [doc/HOOKS-REFERENCE.md](doc/HOOKS-REFERENCE.md)

## 🔒 Sicherheit

- ✅ **CSRF Protection:** Token-basiert für alle State-Changes
- ✅ **XSS Prevention:** Input-Sanitization + Output-Escaping
- ✅ **SQL Injection Prevention:** Prepared Statements (PDO)
- ✅ **Rate Limiting:** Login-Versuche, API-Calls
- ✅ **Password Hashing:** BCrypt mit Salt
- ✅ **Session Security:** HTTP-Only Cookies, Secure Flag
- ✅ **Input Validation:** Type-safe mit PHP 8.3
- ✅ **Failed Login Tracking:** Automatische IP-Blockierung
- ✅ **Activity Logging:** Vollständige Audit-Trails

## 🛠️ Entwicklung

### Debug-Modus

```php
// config.php
define('CMS_DEBUG', true);
```

**Zeigt:**
- PHP-Fehler und Warnings
- SQL-Queries mit Ausführungszeit
- Hook-Aufrufe
- Plugin-Loading-Reihenfolge

### Helper-Funktionen

```php
// Ausgabe escapen
echo esc_html($text);
echo esc_url($url);
echo esc_attr($attribute);

// Sanitization
$clean = sanitize_text($input);
$email = sanitize_email($email);

// Optionen
$value = get_option('key', 'default');
update_option('key', $value);

// User-Checks
if (is_logged_in()) { }
if (is_admin()) { }
$user = current_user();

// Theme-Customizer
$color = theme_get_setting('colors', 'primary_color');
$fonts = get_theme_customizer()->getCategory('typography');

// Debug
dd($variable); // Dump & Die (nur mit CMS_DEBUG)
```

### System-Anforderungen prüfen

Admin → **System & Diagnose**

**Übersicht:**
- PHP-Version, MySQL-Version
- Datenbank-Verbindung
- Speicher-Nutzung
- Aktive Sessions

**Datenbank:**
- Tabellen-Status (17 Kern-Tabellen)
- Datenbank-Größe
- Tabellen-Checks

**Dateisystem:**
- Verzeichnis-Berechtigungen
- Schreib-Tests
- Disk-Space

**Sicherheit:**
- HTTPS-Status
- Failed Logins (24h)
- Security-Score

**Tools:**
- Cache leeren
- Alte Sessions löschen
- Tabellen reparieren/optimieren
- Logs leeren
- Fehlende Tabellen erstellen

## 🆘 Troubleshooting

### Weißer Bildschirm (WSOD)

```bash
# PHP-Fehlerlog prüfen
tail -f /var/log/apache2/error.log

# In config.php
define('CMS_DEBUG', true);
```

**Häufige Ursachen:**
- PHP-Version < 8.3
- Fehlende PDO Extension
- Datenbank-Verbindungsfehler
- Syntax-Fehler in config.php

### Plugins werden nicht geladen

```bash
# Berechtigungen prüfen
ls -la plugins/

# Sollte sein:
drwxr-xr-x plugins/
-rw-r--r-- plugin-name/plugin-name.php
```

**Checkliste:**
- Plugin-Dateiname = Verzeichnisname
- Plugin-Header korrekt (siehe Beispiel oben)
- Keine PHP-Syntax-Fehler
- Namespace korrekt deklariert

### 404-Fehler bei allen Seiten

**Apache:**
```bash
# mod_rewrite aktiviert?
a2enmod rewrite
systemctl restart apache2
```

**.htaccess:**
```apache
# RewriteBase korrekt?
RewriteBase /    # Für Root
RewriteBase /365CMS/    # Für Unterverzeichnis
```

**Nginx:**
```nginx
location / {
    try_files $uri $uri/ /index.php?$args;
}
```

### Theme-Editor Tabs funktionieren nicht

**Browser-Konsole öffnen:**
- F12 → Console Tab
- Auf JavaScript-Fehler prüfen

**Cache leeren:**
- Browser-Cache: Strg+Shift+R
- CMS-Cache: Admin → System → Cache leeren

### Datenbank-Fehler

```sql
-- Manuell Tabellen prüfen
SHOW TABLES LIKE 'cms_%';

-- Oder via Admin:
Admin → System & Diagnose → Tools → Fehlende Tabellen erstellen
```

## 📊 Datenbank-Schema

### Kern-Tabellen (17)

| Tabelle | Beschreibung | Datensätze (ca.) |
|---------|--------------|------------------|
| `cms_users` | Benutzer | 1-10000 |
| `cms_user_meta` | User-Metadaten | 10-50000 |
| `cms_roles` | Rollen & Berechtigungen | 3-10 |
| `cms_sessions` | Aktive Sessions | 10-1000 |
| `cms_settings` | Globale Einstellungen | 20-100 |
| `cms_pages` | Seiten-Content | 10-1000 |
| `cms_page_revisions` | Seiten-Versionen | 50-5000 |
| `cms_landing_sections` | Landing-Page Sektionen | 5-50 |
| `cms_activity_log` | Aktivitäts-Log | 1000-100000 |
| `cms_cache` | Query-Cache | 100-10000 |
| `cms_failed_logins` | Fehlgeschlagene Logins | 0-10000 |
| `cms_login_attempts` | Login-Versuche (Security) | 0-1000 |
| `cms_blocked_ips` | Blockierte IPs | 0-100 |
| `cms_media` | Media-Library | 0-10000 |
| `cms_plugins` | Installierte Plugins | 0-50 |
| `cms_plugin_meta` | Plugin-Metadaten | 0-500 |
| `cms_theme_customizations` | Theme-Anpassungen | 0-200 |

Vollständiges Schema: [doc/DATABASE-SCHEMA.md](doc/DATABASE-SCHEMA.md)

## 📚 Weitere Dokumentation

- **[Installations-Guide](doc/INSTALLATION.md)** - Detaillierte Installations-Anleitung
- **[Architektur](doc/ARCHITECTURE.md)** - System-Architektur und Design-Patterns
- **[API-Referenz](doc/API-REFERENCE.md)** - REST API Endpunkte
- **[Hook-Referenz](doc/HOOKS-REFERENCE.md)** - Alle verfügbaren Hooks
- **[Datenbank-Schema](doc/DATABASE-SCHEMA.md)** - Komplettes DB-Schema mit SQL
- **[Sicherheit](doc/SECURITY.md)** - Security Best Practices & Audit
- **[Theme-Entwicklung](doc/THEME-DEVELOPMENT.md)** - Theme-Entwicklungs-Guide
- **[Plugin-Entwicklung](doc/plugins/PLUGIN-DEVELOPMENT.md)** - Plugin-Entwicklungs-Guide
- **[Admin-Guide](doc/admin/ADMIN-GUIDE.md)** - Admin-Panel Nutzung
- **[Changelog](doc/CHANGELOG.md)** - Versions-Historie

## 🔄 Versions-Historie

### v0.8.24 (Februar 2026) - AKTUELL

**Neu:**
- ✅ Theme-Editor mit Live-Customization
- ✅ Theme-Customizer Service (643 Zeilen)
- ✅ Automatische CSS-Generierung
- ✅ 50+ Theme-Optionen in 8 Kategorien
- ✅ Import/Export für Theme-Einstellungen
- ✅ Google Fonts Integration (8 Fonts)
- ✅ Custom CSS/JS Editor
- ✅ theme_customizations Datenbank-Tabelle
- ✅ Database::getPrefix() Methode
- ✅ Umfassende Theme-Dokumentation

**Verbessert:**
- ✅ Database.php: prefix() + getPrefix() Methoden
- ✅ SystemService: Alle Methoden nutzen getPrefix()
- ✅ Theme-Templates: Customization-Integration
- ✅ Admin-Sidebar: Theme Editor Menü
- ✅ Fehlerbehandlung in ThemeCustomizer

**Behoben:**
- ✅ "Cannot redeclare prefix()" Fatal Error
- ✅ Tab-Wechsel im Theme-Editor
- ✅ CSS !important für Tab-Switching

### v0.4.0 (Januar 2025 - Februar 2026)

**Initial Release - only INTERNAL used:**
- ✅ Core-System mit PDO
- ✅ Plugin-System
- ✅ Theme-System
- ✅ Admin-Panel
- ✅ System & Diagnose
- ✅ Benutzer-Verwaltung
- ✅ 8 Core-Plugins

## 📄 Lizenz

Freie Verwendung für private Projekte.
Kostenpflichtig für Geschäftliche Projekte.

## 👨‍💻 Support & Community

- **Issue Tracker:** GitHub Issues
- **Email:** support@365cms.de

## 🙏 Credits

- **Entwicklung:** Andreas Hepp
- **Website:** PhinIT.DE & 365CMS.DE
- **Icons:** Dashicons
- **Fonts:** Google Fonts with Local Font Manager
- **Editor:** Suneditor is based on pure JavaScript, no dependencies. 
- **Inspiration:** WordPress, Laravel, Symfony
