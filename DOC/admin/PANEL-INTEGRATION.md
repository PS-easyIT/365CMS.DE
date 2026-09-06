# 365CMS – Admin-Panel-Integration

> **Stand:** 2026-09-06 | **Version:** 3.4.00 | **Status:** Aktuelle Integrationsreferenz

Dieses Dokument beschreibt die Integration von Plugins und Erweiterungen in das 365CMS-Adminpanel.

## Inhaltsverzeichnis

- [Aktueller Integrationspfad](#aktueller-integrationspfad)
- [Menüregistrierung](#menüregistrierung)
- [Untermenüs und URLs](#untermenüs-und-urls)
- [Adminseite und Layout](#adminseite-und-layout)
- [Sidebar-Verarbeitung](#sidebar-verarbeitung)
- [Requests und Sicherheit](#requests-und-sicherheit)
- [Plugin-Empfehlungen](#plugin-empfehlungen)
- [Relevante Dateien](#relevante-dateien)

## Aktueller Integrationspfad

Plugin-Menüs werden über den zentralen Action-Hook `cms_admin_menu` registriert. Die Sidebar löst den Hook aus und liest danach die Registry über `get_registered_admin_menus()`.

```php
use CMS\Hooks;

Hooks::addAction('cms_admin_menu', static function (): void {
    add_menu_page(
        'Mein Plugin',
        'Mein Plugin',
        'manage_options',
        'mein-plugin',
        '',
        'ri-puzzle-line',
        80
    );
});
```

Die Registry ist request-idempotent. Wird der Hook im selben Request mehrmals ausgelöst, entstehen keine doppelten Einträge. Gleiche numerische Positionen werden kollisionsfrei auf die nächste freie Position verschoben.

## Menüregistrierung

### `add_menu_page()`

Ein Top-Level-Menüpunkt unterstützt:

- Seitentitel;
- sichtbaren Menütitel;
- Capability;
- stabilen `menu_slug`;
- optionales Render-Callback;
- Icon;
- optionale Position;
- `hidden = true` für bewusst unsichtbare Routingseiten.

Slugs müssen stabil, eindeutig und URL-tauglich sein. Keine Core-Slugs überschreiben.

### `add_submenu_page()`

Unterpunkte werden unter einem Parent-Slug registriert. Für Plugin-Seiten erzeugt der Core die Route:

```text
/admin/plugins/{parent_slug}/{menu_slug}
```

Für Gruppen ohne expliziten Übersichts-Unterpunkt ergänzt die Sidebar einen Übersichtslink. Unterpunkte dürfen nicht auf eine nicht registrierte fremde Parentgruppe zeigen.

## Untermenüs und URLs

```php
Hooks::addAction('cms_admin_menu', static function (): void {
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

Der resultierende Unterpunkt ist unter `/admin/plugins/mein-plugin/settings` erreichbar.

## Adminseite und Layout

Neue Seiten sollen:

- `declare(strict_types=1);` verwenden;
- direkten Zugriff blockieren;
- Adminstatus und Capability prüfen;
- Fachlogik in ein Modul oder einen Service auslagern;
- POST-Aktionen mit CSRF sichern;
- nach erfolgreichem POST umleiten;
- die gemeinsamen Header-/Sidebar-/Footer-Teile verwenden oder den Core-Wrapper nutzen;
- nur vorbereitete Daten an Views übergeben.

Ein Callback, das nur Seiteninhalt ausgibt, wird automatisch in:

```html
<div class="page-body">
  <div class="container-xl cms-plugin-admin-content">…</div>
</div>
```

eingebettet. Vollständige Layouts oder bereits gewrappte `page-body`-Strukturen werden nicht nochmals eingebettet.

## Sidebar-Verarbeitung

Die Sidebar arbeitet vereinfacht so:

1. aktiven Slug normalisieren;
2. Core-Menüs vorbereiten;
3. `CMS\Hooks::doAction('cms_admin_menu')`;
4. Registry lesen;
5. versteckte Einträge auslassen;
6. Gruppen und Labels natürlich sortieren;
7. Positionskollisionen auflösen;
8. Gruppen und Unterpunkte rendern;
9. aktive Seite markieren.

Plugin-Menüs sind von tatsächlich geladenen und aktivierten Plugins abhängig. Ein Quell-Plugin in einem separaten Repository erzeugt keinen Menüpunkt, solange es nicht unter `CMS/plugins/<slug>/` installiert und geladen ist.

## Requests und Sicherheit

Für jede schreibende Pluginseite:

- kein GET-Mutator;
- CSRF-Token pro Action;
- serverseitige Capability-Prüfung;
- Sanitization und Typprüfung;
- vorbereitete Datenbankabfragen;
- kontextbezogenes Escaping;
- sichere interne Redirects;
- PRG nach POST;
- keine rohen Provider-, SQL- oder Exceptiondetails in der UI.

Für AI-Integrationen zusätzlich:

- Nutzungscapability getrennt von Providerverwaltung;
- zentrale AI-Policy und `AiExecutionService` verwenden;
- Quotas und Retry/Fallback nicht im Plugin umgehen;
- externe Datenweitergabe sichtbar und bewusst behandeln;
- keine API-Keys im Plugin-View speichern;
- Prompt- und Ausgabeformat serverseitig begrenzen;
- Review vor Speicherung oder Veröffentlichung.

## Plugin-Empfehlungen

- Menüs nur bei aktivem Plugin registrieren.
- eindeutige Slugs verwenden.
- bei größeren Plugins Top-Level plus klare Unterpunkte nutzen.
- sichtbare Labels für natürliche Sortierung sinnvoll wählen.
- `hidden = true` nur für bewusst unsichtbare technische Routes einsetzen.
- keine eigene Sidebar und kein eigenes vollständiges Layout ohne Notwendigkeit bauen.
- keine Inline-Skripte, wenn CSP-kompatible externe Assets möglich sind.
- Wrapper- und View-Kontext respektieren.
- Fachdokumentation bei Route-, Capability- oder Requeständerungen aktualisieren.

## Relevante Dateien

| Datei | Zweck |
|---|---|
| `CMS/admin/partials/sidebar.php` | Core- und Plugin-Sidebar |
| `CMS/includes/functions.php` | Menühelfer und Registry |
| `CMS/admin/plugins.php` | Core-Pluginübersicht |
| `CMS/admin/ai-page.php` | Beispiel für sectionbasierte Admin-Shell |
| `CMS/assets/js/admin-ai-services.js` | Beispiel für externes CSP-konformes Admin-JavaScript |
| `CMS/core/Services/AI/AiExecutionService.php` | zentraler AI-Ausführungsvertrag |

Weiterführend: [README.md](README.md), [FILESTRUCTURE.md](FILESTRUCTURE.md), [../ai/AI-SERVICES.md](../ai/AI-SERVICES.md).
