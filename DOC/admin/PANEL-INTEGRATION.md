# 365CMS – Admin-Panel-Integration
> **Stand:** 2026-03-28 | **Version:** 2.8.0 RC | **Status:** Aktuell

## Inhaltsverzeichnis
- [Aktueller Integrationspfad](#aktueller-integrationspfad)
- [Verfügbare Helfer](#verfügbare-helfer)
- [Beispiel für Plugin-Menüregistrierung](#beispiel-für-plugin-menüregistrierung)
- [Eigene Admin-Seite](#eigene-admin-seite)
- [Sidebar-Rendering](#sidebar-rendering)
- [Sicherheitsanforderungen](#sicherheitsanforderungen)
- [Empfehlungen für Plugin-Autoren](#empfehlungen-für-plugin-autoren)
- [Relevante Dateien](#relevante-dateien)
- [Verwandte Dokumente](#verwandte-dokumente)

---
<!-- UPDATED: 2026-03-28 -->

Das Sidebar-Menü wird zentral in `CMS/admin/partials/sidebar.php` aufgebaut. Vor dem Rendern der Plugin-Menüs wird dort `CMS\Hooks::doAction('cms_admin_menu')` ausgeführt. Anschließend werden registrierte Einträge über `get_registered_admin_menus()` ausgelesen.

Frühere Dokumentationsstände mit einem Filter `admin_menu_items` sind veraltet und gelten nicht mehr als Referenz für neue Integrationen.

## Aktueller Integrationspfad

Plugins erweitern das Admin-Menü heute typischerweise in zwei Schritten:

1. Im Plugin einen Callback an `cms_admin_menu` hängen.
2. Innerhalb dieses Callbacks `add_menu_page()` und optional `add_submenu_page()` aufrufen.

Die Helferfunktionen liegen in `CMS/includes/functions.php`.

## Verfügbare Helfer

### `add_menu_page()`

Registriert einen Top-Level-Menüpunkt. Relevante Parameter sind:

- Seitentitel
- Menütitel
- Capability
- `menu_slug`
- optionales Render-Callback
- optionales Icon
- optionale Position
- optional `hidden = true`, wenn nur Routing ohne Sidebar-Eintrag gewünscht ist

### `add_submenu_page()`

Registriert einen Unterpunkt unter einem bestehenden Parent-Slug. Die URL wird dabei für Plugin-Seiten als `/admin/plugins/{parent_slug}/{menu_slug}` aufgebaut.

## Beispiel für Plugin-Menüregistrierung

```php
use CMS\Hooks;

Hooks::addAction('cms_admin_menu', function (): void {
    add_menu_page(
        'Mein Plugin',
        'Mein Plugin',
        'manage_options',
        'mein-plugin',
        '',
        'ri-puzzle-line',
        80
    );

    add_submenu_page(
        'mein-plugin',
        'Einstellungen',
        'Einstellungen',
        'manage_options',
        'settings'
    );
});
```

## Eigene Admin-Seite

Eine eigene Admin-Seite sollte sich am Standardmuster der Core-Seiten orientieren:

- `declare(strict_types=1);`
- `ABSPATH`-Guard
- Admin-Zugriffsprüfung mit `Auth::instance()->isAdmin()`
- CSRF-Absicherung bei POST-Requests
- Laden der gemeinsamen Layout-Teile über `partials/header.php`, `partials/sidebar.php`, `partials/footer.php`

Ein manueller HTML-Komplettaufbau mit eigener Sidebar ist für neue Seiten nicht mehr der empfohlene Weg.

## Sidebar-Rendering

Die Sidebar führt vereinfacht diesen Ablauf aus:

1. Core-Menüs rendern
2. `CMS\Hooks::doAction('cms_admin_menu')`
3. registrierte Plugin-Menüs aus `get_registered_admin_menus()` holen
4. Plugin-Menüs rendern

Damit ist klar: Die Menüintegration läuft über eine Aktion plus Registry, nicht über ein vorher zusammenkopiertes Array.

## Sicherheitsanforderungen

Für Admin-Erweiterungen gelten dieselben Mindestanforderungen wie für Core-Seiten:

- Zugriff nur für Administratoren
- CSRF-Tokens für alle schreibenden Aktionen
- serverseitige Sanitierung sämtlicher Eingaben
- Redirect nach POST statt direktem Rendern derselben Anfrage

## Empfehlungen für Plugin-Autoren

- Menüs nur registrieren, wenn das Plugin wirklich aktiv ist
- keine Slugs verwenden, die mit Core-Seiten kollidieren
- für größere Plugins Top-Level-Menü plus klar benannte Unterpunkte verwenden
- `hidden = true` nur nutzen, wenn eine Route bewusst ohne sichtbaren Sidebar-Eintrag gebraucht wird
- Optik und Layout an bestehende Admin-Komponenten anlehnen statt eigene Parallelstrukturen zu bauen

## Relevante Dateien

| Datei | Zweck |
|---|---|
| `CMS/admin/partials/sidebar.php` | rendert Core- und Plugin-Menüs |
| `CMS/includes/functions.php` | Registry und Menü-Helfer |
| `CMS/admin/plugins.php` | Beispiel für eine echte Plugin-Core-Seite |

## Verwandte Dokumente

- [plugins/PLUGINS.md](plugins/PLUGINS.md)
- [README.md](README.md)
- [../plugins/GUIDE.md](../plugins/GUIDE.md)
