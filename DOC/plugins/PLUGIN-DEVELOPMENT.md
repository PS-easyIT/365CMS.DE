# 365CMS – Plugin-Entwicklung
> **Stand:** 2026-03-28 | **Version:** 2.8.0 RC | **Status:** Aktuell

## Inhaltsverzeichnis
- [Grundprinzipien](#grundprinzipien)
- [Empfohlene Struktur](#empfohlene-struktur)
- [Plugin-Hauptdatei](#plugin-hauptdatei)
- [Hauptklasse](#hauptklasse)
- [Wichtige Hooks im aktuellen Stand](#wichtige-hooks-im-aktuellen-stand)
- [Datenbankzugriff](#datenbankzugriff)
- [Admin-Seiten integrieren](#admin-seiten-integrieren)
- [Formulare sicher verarbeiten](#formulare-sicher-verarbeiten)
- [Member-Bereich erweitern](#member-bereich-erweitern)
- [Eigene Routen](#eigene-routen)
- [Sicherheits-Checkliste](#sicherheits-checkliste)
- [Release-Checkliste](#release-checkliste)
- [Verwandte Dokumente](#verwandte-dokumente)

---
<!-- UPDATED: 2026-03-28 -->

## Grundprinzipien

- Core-Dateien nicht anfassen
- Einstieg immer über eine Plugin-Hauptdatei im Ordner `CMS/plugins/<slug>/`
- Hauptklasse als Singleton oder gleichwertig klarer Bootstrap
- Hooks über `CMS\Hooks`
- Datenbankzugriffe mit vorbereiteten Statements oder Core-Helpern
- Admin- und Member-Ausgaben konsequent escapen

---

## Empfohlene Struktur

```text
CMS/plugins/mein-plugin/
├── mein-plugin.php
├── includes/
│   ├── class-plugin.php
│   ├── class-admin.php
│   └── class-member.php
├── admin/
│   └── page.php
├── templates/
│   ├── frontend.php
│   └── member-widget.php
└── assets/
    ├── css/plugin.css
    └── js/plugin.js
```

---

## Plugin-Hauptdatei

```php
<?php
/**
 * Plugin Name: Mein Plugin
 * Description: Kurze Beschreibung des Plugins.
 * Version: 1.0.0
 * Author: Entwicklername
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('MEIN_PLUGIN_VERSION', '1.0.0');
define('MEIN_PLUGIN_PATH', PLUGIN_PATH . 'mein-plugin/');
define('MEIN_PLUGIN_URL', SITE_URL . '/plugins/mein-plugin/');

require_once MEIN_PLUGIN_PATH . 'includes/class-plugin.php';

MeinPlugin::instance();
```

---

## Hauptklasse

```php
<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class MeinPlugin
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
        \CMS\Hooks::addAction('cms_init', [$this, 'boot'], 10);
        \CMS\Hooks::addAction('cms_admin_menu', [$this, 'registerAdminMenu'], 10);
        \CMS\Hooks::addFilter('member_menu_items', [$this, 'extendMemberMenu'], 10);
    }

    public function boot(): void
    {
        $this->ensureTables();
        \CMS\Hooks::addAction('after_header', [$this, 'renderBanner'], 10);
        \CMS\Hooks::addAction('body_end', [$this, 'enqueueScripts'], 10);
    }
}
```

---

## Wichtige Hooks im aktuellen Stand

| Hook | Typ | Zweck |
|---|---|---|
| `cms_init` | Action | Plugin initialisieren |
| `cms_admin_menu` | Action | Admin-Menüs oder Plugin-Seiten registrieren |
| `head` | Action | Styles oder Meta-Ausgaben ergänzen |
| `after_header` | Action | Frontend-Content früh einhängen |
| `before_footer` | Action | Footer-nahe Inhalte ergänzen |
| `body_end` | Action | Scripts oder Modals ausgeben |
| `member_menu_items` | Filter | Member-Navigation erweitern |
| `member_dashboard_widgets` | Filter | Member-Dashboard-Widgets einhängen |

Maßgebliche Referenz: [../core/HOOKS-REFERENCE.md](../core/HOOKS-REFERENCE.md)

---

## Datenbankzugriff

### Tabellen anlegen

```php
private function ensureTables(): void
{
    $db = \CMS\Database::instance();
    $p  = $db->getPrefix();

    $db->query("CREATE TABLE IF NOT EXISTS {$p}mein_plugin_daten (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        content TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
```

### Parametrisierte Zugriffe

```php
public function getDataForUser(int $userId): array
{
    $db = \CMS\Database::instance();
    $p  = $db->getPrefix();

    return $db->get_results(
        "SELECT * FROM {$p}mein_plugin_daten WHERE user_id = ? ORDER BY created_at DESC",
        [$userId]
    ) ?? [];
}
```

Wichtig:

- `query()` **nicht** mit Parameterarrays mischen
- für Bindings `prepare()/execute()`, `get_row()`, `get_results()` oder `get_var()` verwenden

---

## Admin-Seiten integrieren

Die Admin-Sidebar sammelt Plugin-Menüs über `cms_admin_menu` und die Hilfsfunktion `register_admin_menu()`.

```php
public function registerAdminMenu(): void
{
    if (!function_exists('register_admin_menu')) {
        return;
    }

    register_admin_menu([
        'menu_title' => 'Mein Plugin',
        'menu_slug'  => 'mein-plugin',
        'callback'   => [$this, 'renderAdminPage'],
        'children'   => [
            [
                'menu_title' => 'Einstellungen',
                'menu_slug'  => 'settings',
                'callback'   => [$this, 'renderSettingsPage'],
            ],
        ],
    ]);
}
```

Die URL wird dann unter `/admin/plugins/mein-plugin/mein-plugin` bzw. `/admin/plugins/mein-plugin/settings` eingebunden.

---

## Formulare sicher verarbeiten

```php
public function renderSettingsPage(): void
{
    $security = \CMS\Security::instance();
    $message = null;

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'mein_plugin_settings')) {
            $message = 'Sicherheitscheck fehlgeschlagen.';
        } else {
            $value = sanitize_text_field($_POST['my_setting'] ?? '');
            $message = 'Gespeichert.';
        }
    }

    $csrfToken = $security->generateToken('mein_plugin_settings');
    include MEIN_PLUGIN_PATH . 'admin/page.php';
}
```

---

## Member-Bereich erweitern

### Menüeintrag

```php
public function extendMemberMenu(array $items): array
{
    $items[] = [
        'slug' => 'mein-plugin',
        'label' => 'Mein Plugin',
        'icon' => '🔌',
        'url' => '/member/mein-plugin',
        'category' => 'plugins',
    ];

    return $items;
}
```

### Dashboard-Widget

```php
\CMS\Hooks::addFilter('member_dashboard_widgets', function (array $widgets): array {
    $widgets[] = [
        'title' => 'Mein Plugin',
        'callback' => function (object $user): void {
            echo '<div class="member-card"><p>Hallo ' . htmlspecialchars($user->username) . '</p></div>';
        },
    ];

    return $widgets;
});
```

---

## Eigene Routen

Für Frontend- oder API-Routen nutzt ihr die Router-Registrierung an einer geeigneten Stelle des Bootstraps. Dabei immer Auth- und CSRF-Anforderungen getrennt betrachten.

---

## Sicherheits-Checkliste

- `declare(strict_types=1)` verwenden
- `ABSPATH`-Guard setzen
- alle Formulare mit CSRF schützen
- Benutzereingaben sanitizen
- HTML-Ausgaben escapen
- nur vorbereitete Datenbankzugriffe verwenden
- Plugin-Menüs nur für berechtigte Rollen anzeigen

---

## Release-Checkliste

- [ ] Plugin-Header vollständig
- [ ] Hauptklasse sauber bootstrapped
- [ ] Hook-Namen an aktuelle Core-Hooks angepasst
- [ ] SQL-Statements ohne unsichere Interpolation von Userdaten
- [ ] Admin- und Member-Ausgaben escaped
- [ ] Dokumentation im Plugin-Ordner ergänzt

---

## Verwandte Dokumente

- [GUIDE.md](GUIDE.md)
- [../core/HOOKS-REFERENCE.md](../core/HOOKS-REFERENCE.md)
- [../core/SECURITY.md](../core/SECURITY.md)
- [../member/HOOKS.md](../member/HOOKS.md)

