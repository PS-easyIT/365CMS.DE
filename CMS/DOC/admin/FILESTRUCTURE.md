# 365CMS – Admin-Dateistruktur
> **Stand:** 2026-04-07 | **Version:** 2.9.0 | **Status:** Aktuell

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
<!-- UPDATED: 2026-04-07 -->

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
├── hub-sites.php
├── table-of-contents.php
├── post-categories.php
├── post-tags.php
├── packages.php
├── orders.php
├── subscription-settings.php
├── themes.php
├── theme-editor.php
├── theme-explorer.php
├── theme-marketplace.php
├── theme-settings.php
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
├── media.php
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

Die Dateien in diesen Unterordnern sind **keine eigenständigen Entry-Points**. Sie werden ausschließlich über ihre Eltern-Wrapper, Section-Shells oder Modul-Entrys geladen.

### `CMS/admin/partials/`

Gemeinsame Oberflächenbausteine wie Header, Sidebar und Footer. Die Sidebar-Datei ist die maßgebliche Quelle für die sichtbare Admin-Navigation.

---

## Routing und Slugs

Für die Dokumentation sind die aktiven Slugs entscheidend, nicht alte Dateinamen aus älteren Versionen.

| Bereich | Aktueller Slug / Route |
|---|---|
| Hub-Sites | `/admin/hub-sites` |
| Inhaltsverzeichnis | `/admin/table-of-contents` |
| Beitrags-Kategorien | `/admin/post-categories` |
| Beitrags-Tags | `/admin/post-tags` |
| Fonts | `/admin/font-manager` |
| Backups | `/admin/backups` |
| Cookie-Management | `/admin/cookie-manager` |
| SEO-Start | `/admin/seo-dashboard` |
| Theme-Marketplace | `/admin/theme-marketplace` |
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
- In der aktuellen Sidebar-Struktur existieren mehrere gruppierte Navigationsbereiche mit Unterpunkten, z. B. Seiten & Beiträge, Benutzer & Gruppen, Member Dashboard, Themes & Design, SEO, Performance, Recht, Sicherheit, Plugins und System.

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

### Wrapper-gebundene Sub-Views

- Views und Subnavigationen unter `CMS/admin/views/seo/`, `views/performance/`, `views/member/` und `views/system/` dürfen nur aus ihrem jeweiligen Wrapper geladen werden.
- Wrapper setzen dafür explizite Kontext-Konstanten (`CMS_ADMIN_SEO_VIEW`, `CMS_ADMIN_PERFORMANCE_VIEW`, `CMS_ADMIN_MEMBER_VIEW`, `CMS_ADMIN_SYSTEM_VIEW`).
- Jede neue Sub-View prüft neben `ABSPATH` auch ihre Wrapper-Konstante und beendet sich bei Direktaufruf sofort mit `exit;`.
- Direkte Business-Logik, Auth-Prüfungen und CSRF-Verifikation bleiben in den Entry-Points bzw. Wrappern; Sub-Views rendern nur die bereits vorbereiteten Daten.

---

## 🧭 Navigations-Reihenfolge (Sidebar)

```
📊 Dashboard          /admin
📄 Seiten & Beiträge  /admin/pages, /admin/posts, /admin/post-categories, /admin/post-tags, /admin/comments, /admin/hub-sites, /admin/table-of-contents
👥 Benutzer & Gruppen /admin/users, /admin/groups, /admin/roles, /admin/user-settings
🧑 Mitglieder         /admin/member-dashboard*
🎨 Themes & Design    /admin/themes, /admin/theme-editor, /admin/theme-explorer, /admin/theme-marketplace, /admin/font-manager
🖼 Medien              /admin/media
💳 Abos               /admin/packages, /admin/orders, /admin/subscription-settings
🔌 Plugins            /admin/plugins
🔍 SEO                /admin/seo-dashboard
⚡ Performance         /admin/performance
⚖️ Recht              /admin/legal-sites, /admin/cookie-manager, /admin/data-requests
🛡 Sicherheit         /admin/security-audit, /admin/firewall, /admin/antispam
🔧 System             /admin/settings, /admin/backups, /admin/updates, /admin/documentation, /admin/support
ℹ️ Info & Diagnose    /admin/info, /admin/diagnose, /admin/monitor-*
── Zur Website        /
🚪 Abmelden           /logout
```
