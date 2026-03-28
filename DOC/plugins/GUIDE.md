# 365CMS – Plugin-Schnellstart
> **Stand:** 2026-03-28 | **Version:** 2.8.0 RC | **Status:** Aktuell

## Inhaltsverzeichnis
- [Ziel](#ziel)
- [Schritt 1: Ordner und Hauptdatei anlegen](#schritt-1-ordner-und-hauptdatei-anlegen)
- [Schritt 2: Plugin-Klasse erstellen](#schritt-2-plugin-klasse-erstellen)
- [Schritt 3: Plugin aktivieren](#schritt-3-plugin-aktivieren)
- [Schritt 4: Datenbankzugriff ergänzen](#schritt-4-datenbankzugriff-ergänzen)
- [Referenz-Plugins im Workspace](#referenz-plugins-im-workspace)
- [Typische nächste Schritte](#typische-nächste-schritte)
- [Weiterführende Dokumente](#weiterführende-dokumente)

---
<!-- UPDATED: 2026-03-28 -->

## Ziel

Am Ende dieses Guides habt ihr:

- ein minimales Plugin im Ordner `CMS/plugins/hallo-welt/`
- eine Hauptklasse im Singleton-Stil
- einen Frontend-Hook
- einen Admin-Einstieg über den Plugin-Bereich

---

## Schritt 1: Ordner und Hauptdatei anlegen

```text
CMS/plugins/
└── hallo-welt/
    ├── hallo-welt.php
    └── includes/
        └── class-hallo-welt.php
```

Datei `CMS/plugins/hallo-welt/hallo-welt.php`:

```php
<?php
/**
 * Plugin Name: Hallo Welt
 * Description: Minimales Beispielplugin für 365CMS.
 * Version: 1.0.0
 * Author: Euer Name
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('HALLO_WELT_VERSION', '1.0.0');
define('HALLO_WELT_PATH', PLUGIN_PATH . 'hallo-welt/');

require_once HALLO_WELT_PATH . 'includes/class-hallo-welt.php';

HalloWelt::instance();
```

---

## Schritt 2: Plugin-Klasse erstellen

Datei `CMS/plugins/hallo-welt/includes/class-hallo-welt.php`:

```php
<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class HalloWelt
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->initHooks();
    }

    private function initHooks(): void
    {
        \CMS\Hooks::addAction('cms_init', [$this, 'registerRuntimeHooks'], 10);
        \CMS\Hooks::addAction('cms_admin_menu', [$this, 'registerAdminMenu'], 10);
    }

    public function registerRuntimeHooks(): void
    {
        \CMS\Hooks::addAction('after_header', [$this, 'renderBanner'], 10);
    }

    public function renderBanner(): void
    {
        echo '<div class="cms-alert cms-alert-info">Hallo Welt – mein erstes Plugin ist aktiv.</div>';
    }

    public function registerAdminMenu(): void
    {
        if (function_exists('register_admin_menu')) {
            register_admin_menu([
                'menu_title' => 'Hallo Welt',
                'menu_slug'  => 'hallo-welt',
                'callback'   => [$this, 'renderAdminPage'],
            ]);
        }
    }

    public function renderAdminPage(): void
    {
        echo '<div class="container-xl"><h1>Hallo Welt</h1><p>Das Plugin läuft.</p></div>';
    }
}
```

---

## Schritt 3: Plugin aktivieren

1. `/admin/plugins` öffnen
2. das Plugin `Hallo Welt` suchen
3. aktivieren

Danach erscheint das Banner im Frontend und – falls registriert – der Plugin-Einstieg im Plugin-Bereich der Admin-Sidebar.

---

## Schritt 4: Datenbankzugriff ergänzen

Für parametrisierte Zugriffe gilt in 365CMS:

- `query()` nur für rohe SQL-Ausführung ohne Bindings
- `prepare()+execute()` oder Helper wie `get_var()` für Werte mit Parametern

Beispiel:

```php
public function ensureTable(): void
{
    $db = \CMS\Database::instance();
    $p  = $db->getPrefix();

    $db->query("CREATE TABLE IF NOT EXISTS {$p}hallo_welt_clicks (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED DEFAULT NULL,
        clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

public function getClickCount(): int
{
    $db = \CMS\Database::instance();
    $p  = $db->getPrefix();

    return (int) $db->get_var("SELECT COUNT(*) FROM {$p}hallo_welt_clicks");
}
```

---

## Referenz-Plugins im Workspace

| Plugin | Ordner | Schwerpunkt |
|---|---|---|
| `cms-companies` | `365CMS.DE-PLUGINS/cms-companies/` | Firmenprofile |
| `cms-events` | `365CMS.DE-PLUGINS/cms-events/` | Events |
| `cms-experts` | `365CMS.DE-PLUGINS/cms-experts/` | Expertenprofile |
| `cms-feed` | `365CMS.DE-PLUGINS/cms-feed/` | Feed-/Inhaltsintegration |
| `cms-importer` | `365CMS.DE-PLUGINS/cms-importer/` | Importe |
| `cms-speakers` | `365CMS.DE-PLUGINS/cms-speakers/` | Speaker-Verwaltung |

---

## Typische nächste Schritte

- Admin-Ansicht mit CSRF-geschütztem Formular bauen
- Template-Datei unter `templates/` ergänzen
- eigene Hooks dokumentieren
- Plugin-Assets über `head` oder `body_end` einbinden

---

## Weiterführende Dokumente

- [PLUGIN-DEVELOPMENT.md](PLUGIN-DEVELOPMENT.md)
- [../core/HOOKS-REFERENCE.md](../core/HOOKS-REFERENCE.md)
- [../core/CORE-CLASSES.md](../core/CORE-CLASSES.md)

