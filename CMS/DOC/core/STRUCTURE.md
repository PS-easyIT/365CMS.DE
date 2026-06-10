# 365CMS – Core-/Admin-Struktur-Snapshot
> **Stand:** 2026-06-10 | **Version:** 3.3.47 | **Status:** Aktuell

Dieser Snapshot umfasst `CMS/core/`, `CMS/admin/`, `CMS/config/` sowie versionsrelevante Core-Metadaten. Nicht vollständig enthalten sind `/plugins/`, `/themes/` und die tiefe Struktur von `/CMS/assets/`.

Für die aktuelle lesbare Vollstruktur gelten zusätzlich:

- `DOC/FILELIST.md` für den gesamten Runtime-Baum
- `DOC/ASSET.md` und `DOC/assets/README.md` für die Asset-Fläche

---

## Aktueller Verzeichnisbaum zum Release-Zeitpunkt

```text
CMS/
├── admin/
│   ├── analytics.php
│   ├── antispam.php
│   ├── backups.php
│   ├── comments.php
│   ├── cookie-manager.php
│   ├── data-requests.php
│   ├── deletion-requests.php
│   ├── design-settings.php
│   ├── diagnose.php
│   ├── documentation.php
│   ├── error-report.php
│   ├── firewall.php
│   ├── font-manager.php
│   ├── groups.php
│   ├── hub-sites.php
│   ├── info.php
│   ├── landing-page.php
│   ├── legal-sites.php
│   ├── mail-settings.php
│   ├── media.php
│   ├── member-dashboard*.php
│   ├── menu-editor.php
│   ├── monitor-*.php
│   ├── orders.php
│   ├── packages.php
│   ├── pages.php
│   ├── performance*.php
│   ├── plugin-marketplace.php
│   ├── plugins.php
│   ├── post-categories.php
│   ├── post-tags.php
│   ├── posts.php
│   ├── privacy-requests.php
│   ├── redirect-manager.php
│   ├── roles.php
│   ├── security-audit.php
│   ├── seo-*.php
│   ├── settings.php
│   ├── site-tables.php
│   ├── subscription-settings.php
│   ├── support.php
│   ├── system-info.php
│   ├── system-monitor-page.php
│   ├── table-of-contents.php
│   ├── theme-*.php
│   ├── updates.php
│   ├── user-settings.php
│   ├── users.php
│   ├── modules/
│   │   ├── comments/
│   │   ├── dashboard/
│   │   ├── hub/
│   │   ├── landing/
│   │   ├── legal/
│   │   ├── media/
│   │   ├── member/
│   │   ├── menus/
│   │   ├── pages/
│   │   ├── plugins/
│   │   ├── posts/
│   │   ├── security/
│   │   ├── seo/
│   │   ├── settings/
│   │   ├── subscriptions/
│   │   ├── system/
│   │   ├── tables/
│   │   ├── themes/
│   │   ├── toc/
│   │   └── users/
│   ├── partials/
│   └── views/
│       ├── comments/
│       ├── dashboard/
│       ├── hub/
│       ├── landing/
│       ├── legal/
│       ├── media/
│       ├── member/
│       ├── menus/
│       ├── pages/
│       ├── partials/
│       ├── performance/
│       ├── plugins/
│       ├── posts/
│       ├── security/
│       ├── seo/
│       ├── settings/
│       ├── subscriptions/
│       ├── system/
│       ├── tables/
│       ├── themes/
│       ├── toc/
│       └── users/
├── config/
│   ├── .htaccess
│   ├── app.php
│   ├── media-meta.json
│   └── media-settings.json
└── core/
    ├── Api.php
    ├── AuditLogger.php
    ├── Auth/
    ├── Auth.php
    ├── autoload.php
    ├── Bootstrap.php
    ├── CacheManager.php
    ├── Container.php
    ├── Contracts/
    ├── Database.php
    ├── Debug.php
    ├── Hooks.php
    ├── Http/
    ├── Json.php
    ├── Logger.php
    ├── Member/
    ├── MigrationManager.php
    ├── PageManager.php
    ├── PluginManager.php
    ├── Router.php
    ├── Routing/
    ├── SchemaManager.php
    ├── Security.php
    ├── Services/
    │   ├── EditorJs/
    │   ├── Landing/
    │   ├── Media/
    │   ├── SEO/
    │   ├── SiteTable/
    │   ├── ErrorReportService.php
    │   ├── FeatureUsageService.php
    │   ├── MediaDeliveryService.php
    │   ├── OpcacheWarmupService.php
    │   ├── PermalinkService.php
    │   └── ... weitere Service-Fassaden und Runtime-Helfer
    ├── SubscriptionManager.php
    ├── TableOfContents.php
    ├── ThemeManager.php
    ├── Totp.php
    ├── VendorRegistry.php
    ├── Version.php
    └── WP_Error.php
```

---

## Neue Dateien seit `2.5.30`

| Pfad | Kurzbeschreibung |
|---|---|
| `CMS/admin/error-report.php` | POST-Entry-Point für Admin-Fehlerreports mit CSRF-Prüfung und normalisiertem Redirect-Flow. |
| `CMS/admin/post-categories.php` | Eigenständige Admin-Seite zum Verwalten von Beitrags-Kategorien. |
| `CMS/admin/post-tags.php` | Eigenständige Admin-Seite zum Verwalten von Beitrags-Tags. |
| `CMS/admin/views/posts/categories.php` | View für Kategorienübersicht, Formular und Tabellenliste. |
| `CMS/admin/views/posts/tags.php` | View für Tag-Übersicht, Formular und Tabellenliste. |
| `CMS/admin/views/tables/settings.php` | Admin-View für globale Tabellen-Defaults und Stil-Presets. |
| `CMS/core/Services/ErrorReportService.php` | Persistiert Fehlerreports, bereitet `WP_Error`-Payloads auf und schreibt Audit-Logs. |
| `CMS/core/Services/PermalinkService.php` | Zentralisiert Beitrags-Permalinks, Slug-Extraktion und URL-Migrationspfade. |
| `CMS/core/Services/SiteTable/SiteTableDisplaySettings.php` | Zentrale Default- und Preset-Verwaltung für Tabellen-Anzeigeoptionen. |

---

## Entfernte Dateien seit `2.5.30`

Im ausgewerteten Scope wurden seit `2.5.30` **keine Dateien entfernt**.

---

## Einordnung der Scope-Änderungen

- **SemVer-Treiber:** ausschließlich additive Features und Erweiterungen → `MINOR`.
- **Architektur-Richtung:** neue Services kapseln Permalinks, Fehlerreports und Tabellen-Defaults, statt diese Logik weiter in bestehenden Großmodulen zu belassen.
- **Admin-Richtung:** Beitrags-Taxonomien und Fehlerreporting haben nun eigene, klar erkennbare Einstiege statt versteckter Nebenpfade.

## Einordnung dieses Dokuments im Doku-System

`core/STRUCTURE.md` ist bewusst **kein Ersatz** für die allgemeine Strukturkarte. Seine Aufgabe ist ein enger, releasesensibler Struktur-Snapshot des Core-/Admin-/Config-Kontexts. Für Runtime-Wahrheit außerhalb dieses engeren Scopes gelten die aktualisierten Querreferenzen in `FILELIST.md`.