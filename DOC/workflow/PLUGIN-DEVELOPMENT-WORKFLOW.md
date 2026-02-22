# Plugin-Entwicklung Workflow – 365CMS

> **Bereich:** Plugin-System · **Version:** 1.6.14  
> **Referenzen:** [PLUGIN-AUDIT.md](../audits/PLUGIN-AUDIT.md) · [PLUGIN-LIST.MD](../feature/PLUGIN-LIST.MD)  
> **Ziel:** Vollständiger Workflow von Idee bis Deployment eines neuen Plugins

---

## Übersicht: Plugin-Lifecycle

```
1. Konzept & Planung     → Anforderungen, Scope, DB-Design
2. Dateistruktur         → Verzeichnisse + Boilerplate anlegen
3. Haupt-Plugin-Datei    → Header, Singleton, Init-Hook
4. Datenbank             → Tabellen, Migrations, Activation-Hook
5. Admin-Seite           → CRUD-Interface, nonces, CSRF
6. Frontend-Template     → Templates, CSS (Prefix!), JS
7. Hooks registrieren    → Actions, Filters, Member-Dashboard
8. Sicherheit            → Capability-Checks, Sanitize, Escape
9. Aktivierung testen    → Syntax-Check, DB-Check, Hook-Check
10. Dokumentation         → README.md, Changelog
```

---

## Schritt 1: Konzept & Planung

**Pflichtfragen vor dem Start:**
- [ ] Welches einzige Feature hat das Plugin? (Single Responsibility)
- [ ] Welche DB-Tabellen werden benötigt?
- [ ] Welche Hooks registriert das Plugin?
- [ ] Welche Admin-Seiten werden hinzugefügt?
- [ ] Welche Member-Dashboard-Integration ist nötig?
- [ ] Welche anderen Plugins werden benötigt? (`Requires:`)
- [ ] Welcher Liz enztyp? (Free / Premium)

---

## Schritt 2: Dateistruktur anlegen

```
plugins/
└── cms-meinplugin/
    ├── cms-meinplugin.php      ← Haupt-Plugin-Datei (PFLICHT)
    ├── update.json             ← Update-Manifest (Version, SHA-256)
    ├── README.md               ← Plugin-Dokumentation
    ├── includes/
    │   ├── class-install.php   ← Install/Uninstall/Migrate
    │   ├── class-admin.php     ← Admin-Seiten-Logik
    │   ├── class-frontend.php  ← Frontend-Ausgabe
    │   └── class-api.php       ← REST-Endpoints (optional)
    ├── admin/
    │   ├── list.php            ← Übersichtsseite
    │   └── edit.php            ← Bearbeitungsformular
    ├── templates/
    │   ├── archive.php         ← Frontend-Liste
    │   └── single.php          ← Frontend-Detailseite
    └── assets/
        ├── css/
        │   └── meinplugin.css  ← CSS (Prefix: cms-meinplugin-)
        └── js/
            └── meinplugin.js   ← JavaScript
```

**Quickstart via PowerShell:**
```powershell
$slug = "cms-meinplugin"
$base = "e:\00-WPwork\365CMS.DE\CMS\plugins\$slug"
New-Item -ItemType Directory -Path "$base\includes","$base\admin","$base\templates","$base\assets\css","$base\assets\js" -Force
```

---

## Schritt 3: Haupt-Plugin-Datei

**Datei:** `plugins/cms-meinplugin/cms-meinplugin.php`

```php
<?php
/**
 * Plugin Name:    CMS Mein Plugin
 * Description:    Kurze Beschreibung was dieses Plugin macht
 * Version:        1.0.0
 * Author:         PS-easyIT
 * Requires:       1.6.0
 * Requires:       cms-experts >= 1.0.0
 * License:        GPL-2.0+
 */

declare(strict_types=1);

if (!defined('ABSPATH')) exit;

// Konstanten
define('CMS_MEINPLUGIN_VERSION', '1.0.0');
define('CMS_MEINPLUGIN_DIR',     plugin_dir_path(__FILE__));
define('CMS_MEINPLUGIN_URL',     plugin_dir_url(__FILE__));
define('CMS_MEINPLUGIN_SLUG',    'cms-meinplugin');

// Abhängigkeiten laden
require_once CMS_MEINPLUGIN_DIR . 'includes/class-install.php';
require_once CMS_MEINPLUGIN_DIR . 'includes/class-admin.php';

// Initialisierung via Hook
use CMS\Hooks;

Hooks::addAction('plugins_loaded', function() {
    CmsMeinPlugin::instance()->init();
});

// Singleton-Pattern
class CmsMeinPlugin {
    private static ?self $instance = null;

    public static function instance(): self {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public function init(): void {
        // Admin-Seiten registrieren
        Hooks::addAction('admin_menu', [CmsMeinPluginAdmin::instance(), 'registerMenuPages']);

        // Member-Dashboard-Tab (optional)
        Hooks::addFilter('member_dashboard_tabs', [$this, 'registerDashboardTab']);

        // Frontend-Routes
        Hooks::addAction('init', [$this, 'registerRoutes']);
    }

    public function registerDashboardTab(array $tabs): array {
        $tabs['meinplugin'] = [
            'label'    => 'Mein Feature',
            'icon'     => 'dashicons-admin-tools',
            'callback' => [$this, 'renderDashboardTab'],
            'priority' => 60,
        ];
        return $tabs;
    }

    public function registerRoutes(): void {
        // Hooks::instance()->addRoute('GET', '/meinplugin', [$this, 'renderFrontend']);
    }
}
```

---

## Schritt 4: Datenbank & Activation-Hook

**Datei:** `plugins/cms-meinplugin/includes/class-install.php`

```php
<?php
declare(strict_types=1);

class CmsMeinPluginInstall {

    public static function activate(): void {
        self::createTables();
        self::insertDefaults();
    }

    public static function deactivate(): void {
        // Nur Caches leeren – Daten behalten
        \CMS\CacheManager::instance()->deleteByPrefix('meinplugin_');
    }

    public static function uninstall(): void {
        global $wpdb; // oder CMS\Database::instance()
        $db = \CMS\Database::instance();
        $db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "meinplugin");
        $db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "meinplugin_meta");
        delete_option('meinplugin_options');
    }

    private static function createTables(): void {
        $db = \CMS\Database::instance();
        $db->query("
            CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "meinplugin (
                id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title       VARCHAR(255) NOT NULL,
                content     LONGTEXT,
                status      ENUM('draft','published','archived') DEFAULT 'draft',
                user_id     INT UNSIGNED,
                created_at  DATETIME DEFAULT NOW(),
                updated_at  DATETIME DEFAULT NOW() ON UPDATE NOW(),
                INDEX idx_status  (status),
                INDEX idx_user    (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private static function insertDefaults(): void {
        // Standardwerte in cms_settings speichern
        update_option('meinplugin_version', CMS_MEINPLUGIN_VERSION);
    }
}

// Lifecycle-Hooks registrieren
\CMS\Hooks::addAction('plugin_activated_cms-meinplugin', ['CmsMeinPluginInstall', 'activate']);
\CMS\Hooks::addAction('plugin_deactivated_cms-meinplugin', ['CmsMeinPluginInstall', 'deactivate']);
```

---

## Schritt 5: Admin-Seite

**Datei:** `plugins/cms-meinplugin/includes/class-admin.php`

```php
<?php
declare(strict_types=1);

class CmsMeinPluginAdmin {
    private static ?self $instance = null;
    public static function instance(): self {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public function registerMenuPages(): void {
        // Integration in Admin-Sidebar via Hook
        \CMS\Hooks::addFilter('admin_nav_items', function(array $items) {
            $items['meinplugin'] = [
                'label' => 'Mein Plugin',
                'icon'  => '🔧',
                'url'   => '/admin/meinplugin.php',
                'cap'   => 'manage_options',
            ];
            return $items;
        });
    }
}
```

**Admin-Seite Boilerplate** (`admin/meinplugin-list.php`):

```php
<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL . '/admin/');
    exit;
}

// CSRF-Token
$csrf = Security::instance()->generateToken('meinplugin_list');

// POST-Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'meinplugin_list')) {
        $error = 'Sicherheitscheck fehlgeschlagen';
    } else {
        // Aktion verarbeiten
        $title = sanitize_text_field($_POST['title'] ?? '');
        // DB-Insert...
        $success = 'Eintrag gespeichert';
    }
}
```

---

## Schritt 6: CSS-Konventionen

```
PFLICHTREGELN:
✅ Plugin-spezifisches Präfix: .cms-meinplugin-
✅ Eigene CSS-Datei: assets/css/meinplugin.css
✅ CSS-Custom-Properties für Farben
✅ Mobile-First (@media-Queries)

VERBOTEN:
❌ Inline-Styles für Layout/Design
❌ Generic class names: .card, .button, .grid
❌ Fremdimport fremder CSS-Dateien
❌ !important (nur für .border-partner etc.)
```

---

## Schritt 7: Sicherheits-Checkliste

```
VOR AKTIVIERUNG (Code-Review):
[ ] PHP-Syntax: php -l plugins/cms-meinplugin/cms-meinplugin.php
[ ] Keine gefährlichen Funktionen: eval, exec, system, passthru
[ ] isAdmin() oder hasCapability() in ALLEN Admin-Callbacks
[ ] CSRF-Token in ALLEN Formularen
[ ] Alle $_POST/$_GET via sanitize_text_field() / filter_var()
[ ] Alle HTML-Ausgaben via htmlspecialchars() / esc_html()
[ ] Alle DB-Queries via Prepared Statements ($db->prepare())
[ ] Datei-Uploads: MIME-Prüfung via finfo() + PHP-Tag-Scan

NACH AKTIVIERUNG:
[ ] DB-Tabellen erstellt? (SELECT * FROM cms_meinplugin LIMIT 1)
[ ] Admin-Menüeintrag sichtbar?
[ ] Member-Dashboard-Tab sichtbar (wenn registriert)?
[ ] Frontend-Route erreichbar?
[ ] Deaktivieren und Reaktivieren ohne Fehler?
```

---

## Schritt 8: update.json

```json
{
    "slug":            "cms-meinplugin",
    "name":            "CMS Mein Plugin",
    "version":         "1.0.0",
    "min_cms_version": "1.6.0",
    "download_url":    "https://github.com/PS-easyIT/365cms-meinplugin/releases/download/v1.0.0/cms-meinplugin.zip",
    "sha256":          "HASH_HIER_EINTRAGEN_NACH_ZIP-BUILD",
    "requires":        {},
    "changelog":       "Initiale Version"
}
```

**SHA-256 generieren:**
```powershell
Get-FileHash "e:\builds\cms-meinplugin.zip" -Algorithm SHA256 | Select-Object Hash
```

---

## Referenzen

- [PLUGIN-AUDIT.md](../audits/PLUGIN-AUDIT.md) – Sicherheitsanforderungen
- [PLUGIN-LIST.MD](../feature/PLUGIN-LIST.MD) – Alle 60 geplanten Plugins
- [HOOKS-REFERENCE.md](../core/HOOKS-REFERENCE.md) – Verfügbare Hooks
- [PLUGIN-REGISTRATION-WORKFLOW.MD](PLUGIN-REGISTRATION-WORKFLOW.MD) – Member-Workflow
