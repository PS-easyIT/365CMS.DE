# 365CMS – Admin-Dateistruktur
> **Stand:** 2026-03-08 | **Version:** 2.5.4 | **Status:** Aktuell

## Inhaltsverzeichnis
- [Hauptstruktur](#hauptstruktur)
- [Rollen der Verzeichnisse](#rollen-der-verzeichnisse)
- [Routing und Slugs](#routing-und-slugs)
- [Typischer Aufbau eines Entry-Points](#typischer-aufbau-eines-entry-points)
- [Beziehungen zu Core-Komponenten](#beziehungen-zu-core-komponenten)
- [🔒 Sicherheitsmuster](#sicherheitsmuster)
- [🧭 Navigations-Reihenfolge (Sidebar)](#navigations-reihenfolge-sidebar)

Der Admin-Bereich ist in 365CMS kein flaches Sammelbecken einzelner „Alles-in-einer-Datei“-Seiten mehr, sondern eine modulare Struktur aus:

- **Entry-Points** unter `CMS/admin/`
- **Modulen** unter `CMS/admin/modules/`
- **Views** unter `CMS/admin/views/`
- **Partials** wie Header, Sidebar und Footer

---
<!-- UPDATED: 2026-03-08 -->

## Hauptstruktur

```text
CMS/admin/
├── index.php
├── pages.php
├── posts.php
├── media.php
├── users.php
├── groups.php
├── roles.php
├── member-dashboard*.php
├── packages.php
├── orders.php
├── subscription-settings.php
├── themes.php
├── theme-editor.php
├── theme-explorer.php
├── menu-editor.php
├── landing-page.php
├── font-manager.php
├── seo-dashboard.php
├── analytics.php
├── seo-audit.php
├── seo-meta.php
├── seo-social.php
├── seo-schema.php
├── seo-sitemap.php
├── seo-technical.php
├── redirect-manager.php
├── performance*.php
├── legal-sites.php
├── cookie-manager.php
├── data-requests.php
├── antispam.php
├── firewall.php
├── security-audit.php
├── plugins.php
├── plugin-marketplace.php
├── settings.php
├── backups.php
├── updates.php
├── info.php
├── documentation.php
├── diagnose.php
├── monitor-*.php
├── modules/
├── views/
└── partials/
```

---

## Rollen der Verzeichnisse

### `CMS/admin/*.php`

Diese Dateien sind die Entry-Points. Sie prüfen Zugriff, laden das passende Modul, verarbeiten Aktionen, setzen `$pageTitle` und `$activePage` und rendern anschließend die Oberfläche.

### `CMS/admin/modules/`

Hier liegt die Fachlogik je Bereich, zum Beispiel für Legal, Security, System, SEO oder Themes.

### `CMS/admin/views/`

Dieses Verzeichnis enthält die eigentliche Ausgabe. Typische Unterordner sind `views/legal/`, `views/seo/`, `views/system/` und `views/performance/`.

### `CMS/admin/partials/`

Gemeinsame Oberflächenbausteine wie Header, Sidebar und Footer. Die Sidebar-Datei ist die maßgebliche Quelle für die sichtbare Admin-Navigation.

---

## Routing und Slugs

Für die Dokumentation sind die aktiven Slugs entscheidend, nicht alte Dateinamen aus älteren Versionen.

| Bereich | Aktueller Slug / Route |
|---|---|
| Fonts | `/admin/font-manager` |
| Backups | `/admin/backups` |
| Cookie-Management | `/admin/cookie-manager` |
| SEO-Start | `/admin/seo-dashboard` |
| SEO-Meta | `/admin/seo-meta` |
| Diagnose | `/admin/diagnose` |
| Systeminfo | `/admin/info` |

Veraltete Bezeichnungen wie `seo.php`, `backup.php`, `cookies.php`, `fonts-local.php` oder `theme-customizer.php` gelten nicht mehr als Referenz.

---

## Typischer Aufbau eines Entry-Points

1. `declare(strict_types=1)`
2. `ABSPATH`-Guard
3. Admin-Check via `CMS\Auth`
4. Modul instanziieren
5. POST-Daten mit `CMS\Security::verifyToken()` prüfen
6. Ergebnis als Session-Alert ablegen
7. Redirect auf die GET-Route
8. View rendern

---

## Beziehungen zu Core-Komponenten

Der Admin-Bereich nutzt zentral:

- `CMS\Auth`
- `CMS\Security`
- `CMS\Database`
- `CMS\Hooks`
- spezialisierte Services wie `UpdateService`, `BackupService` oder `ThemeCustomizer`

**Menüstruktur mit Children (Sub-Menü):**
- Das `children`-Array ermöglicht verschachtelte Menüpunkte
- Aktuell nutzt `settings` → `updates` als Sub-Menüpunkt

**Hooks:**
- `admin_menu_items` (Filter) – Erlaubt Plugins eigene Menüpunkte hinzuzufügen

### `includes/sidebar.php` – Legacy-Sidebar (Deprecated)

> ⚠️ **DEPRECATED** – Diese Datei wird nicht mehr aktiv eingebunden.  
> Verwende `partials/admin-menu.php` mit `renderAdminSidebar()`.

Wurde früher von `groups.php` und `subscriptions.php` direkt includiert und renderte
die Sidebar als Inline-HTML. Seit dem Sicherheits-Audit vom 18.02.2026 sind beide
Dateien auf `admin-menu.php` umgestellt.

---

## 🔒 Sicherheitsmuster

Alle Admin-Dateien folgen diesem Sicherheits-Bootstrap:

```php
<?php
declare(strict_types=1);

// Schritt 1: Konfiguration
require_once dirname(__DIR__) . '/config.php';

// Schritt 2: Autoloader (CMS\* Klassen)
require_once CORE_PATH . 'autoload.php';

// Schritt 3: Helper-Funktionen (sanitize_text, esc_html, …)
require_once ABSPATH . 'includes/functions.php';

use CMS\Auth;
use CMS\Security;

if (!defined('ABSPATH')) {
    exit;
}

// Schritt 4: Admin-Zugriff
if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

// Schritt 5: CSRF-Token generieren
$csrfToken = Security::instance()->generateToken('my_action_name');

// Schritt 6: Admin-Menü laden
require_once __DIR__ . '/partials/admin-menu.php';
```

**CSRF-Verifikation beim POST:**
```php
if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'my_action_name')) {
    $error = 'Sicherheitsüberprüfung fehlgeschlagen';
}
```

**Ein Token pro Seite** (nicht mehrere `generateToken()` in Formularen!):
```php
// ✅ Richtig – Token einmalig generieren, in allen Formularen verwenden
$csrfToken = Security::instance()->generateToken('my_action');

// ❌ Falsch – überschreibt den Session-Token bei jedem Aufruf
<input ... value="<?php echo Security::instance()->generateToken(); ?>">
```

---

## 🧭 Navigations-Reihenfolge (Sidebar)

```
📊 Dashboard          /admin
📄 Seiten             /admin/pages
👥 Benutzer           /admin/users
💳 Abos               /admin/subscriptions
🔌 Plugins            /admin/plugins
🎨 Design             /admin/theme-editor
🔍 SEO                /admin/seo
⚡ Performance         /admin/performance
📈 Analytics          /admin/analytics
💾 Backups            /admin/backup
⚙️ Einstellungen      /admin/settings
   └ 🔄 Updates       /admin/updates
🔧 System & Diagnose  /admin/system
── Zur Website        /
🚪 Abmelden           /logout
```
