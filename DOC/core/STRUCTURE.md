> **Website:** [365CMS](https://365cms.de/)  
> **Version:** 3.4.00  
> **Datum:** 2026-09-06  
> **Status:** Aktuell · **Zuletzt aktualisiert am: 2026-09-06**  
> **Kurzbeschreibung:** Release-Snapshot der Core-, Admin- und Konfigurationsstruktur mit den wichtigsten Einstiegen und neuen Dateien. Der Baum wurde gegen den aktuellen Inhalt von `CMS/core/`, `CMS/admin/` und `CMS/config/` geprüft.

## English

### User-friendly
This snapshot shows where administrators, configuration and core runtime code live. It supports navigation, deployment reviews and troubleshooting; the broader runtime tree remains in `DOC/FILELIST.md`.

### Technical
The verified core includes root classes plus `Auth`, `Contracts`, `Http`, `Member`, `Routing` and service subtrees. Plugins, themes and assets are intentionally not fully included. The complete detailed structure is retained in the German technical section below.

## Deutsch

### Anwenderfreundlich
Dieser Snapshot zeigt, wo Admin-Einstiege, Konfiguration und Core-Laufzeit liegen. Er unterstützt Navigation, Deployment-Prüfungen und Fehlersuche; der vollständige Runtime-Baum steht in `DOC/FILELIST.md`.

### Technisch
Der geprüfte Core umfasst Root-Klassen sowie `Auth`, `Contracts`, `Http`, `Member`, `Routing` und Service-Unterbereiche. Plugins, Themes und Assets sind bewusst nicht vollständig enthalten.

# 365CMS – Core-/Admin-Struktur-Snapshot
> **Stand:** 2026-09-06 | **Version:** 3.4.00 | **Status:** Release-Snapshot / enger Strukturscope

Dieser Snapshot umfasst `CMS/core/`, `CMS/admin/`, `CMS/config/` sowie versionsrelevante Core-Metadaten. Nicht vollständig enthalten sind `/plugins/`, `/themes/` und die tiefe Struktur von `/CMS/assets/`.

Der Snapshot wurde gegen den Release-Code `3.4.00` geprüft. Maßgebliche
Runtime-Quellen sind `CMS/core/Version.php`, `CMS/core/Bootstrap.php`,
`CMS/core/Routing/ApiRouter.php` und `CMS/core/SchemaManager.php`; der dort
vermerkte Release-Termin ist `2026-09-05`.

Für die aktuelle lesbare Vollstruktur gelten zusätzlich:

- `DOC/FILELIST.md` für den gesamten Runtime-Baum
- `DOC/ASSET.md` und `DOC/assets/README.md` für die Asset-Fläche

---

## Aktueller Verzeichnisbaum zum Release-Zeitpunkt `3.4.00`

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