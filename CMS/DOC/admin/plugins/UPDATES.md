# Update-Manager

Kurzbeschreibung: Ordnet die Plugin-bezogene Update-Verwaltung in die zentrale Seite `/admin/updates` ein und beschreibt die aktuellen Aktionen für Core- und Plugin-Updates.

Letzte Aktualisierung: 2026-03-28 · Version 2.8.0 RC

## Überblick

Plugin-Updates werden nicht über eine separate Plugin-Sonderseite gepflegt, sondern im zentralen Update-Manager unter `/admin/updates`. Der Entry-Point `CMS/admin/updates.php` arbeitet mit `UpdatesModule` und bündelt Core-, Plugin- und Theme-Informationen inklusive Staging-/Rollback-orientiertem Update-Flow.

## Unterstützte Aktionen

Die Seite verarbeitet derzeit diese POST-Aktionen:

- `check_updates`
- `install_core`
- `install_plugin`

Für Plugin-Updates wird zusätzlich `plugin_slug` übergeben. Der CSRF-Kontext lautet `admin_updates`.

## Datenbereiche der Update-Seite

Das Modul liefert strukturierte Daten für mehrere Bereiche:

- `core`
- `plugins`
- `theme`
- `history`
- `requirements`

Dadurch ist die Plugin-Update-Logik technisch Teil einer größeren Systemseite und nicht mehr nur ein isolierter Anhang der Plugin-Verwaltung.

## Plugin-Updates

Plugin-Updates werden slug-basiert installiert. Die Admin-Seite bereinigt den Slug serverseitig, übergibt ihn an `installPluginUpdate()` und meldet das Ergebnis anschließend als Session-Alert zurück.

Welche Update-Quellen oder Paketinformationen verwendet werden, hängt von den im System oder in den Plugin-Metadaten hinterlegten Updateinformationen ab.

## Core-Updates und Gesamtprüfung

Neben Plugin-Updates unterstützt dieselbe Seite:

- eine Gesamtabfrage verfügbarer Aktualisierungen
- die Installation eines Core-Updates

Die Plugin-Dokumentation verweist deshalb bewusst auf die System-Doku, weil die technische Zuständigkeit zentralisiert ist.

## Sicherheit

Der Update-Manager nutzt:

- Admin-Zugriffsschutz
- CSRF-Prüfung via `Security::instance()->verifyToken(..., 'admin_updates')`
- serverseitige Sanitierung von `plugin_slug`
- Redirect nach jeder schreibenden Aktion

## Relevante Dateien

| Datei | Zweck |
|---|---|
| `CMS/admin/updates.php` | Entry-Point und Aktions-Dispatch |
| `CMS/admin/modules/system/UpdatesModule.php` | Prüfen und Installieren von Updates |
| `CMS/admin/views/system/updates.php` | Ausgabe der zentralen Update-Seite |

## Verwandte Dokumente

- [../system-settings/UPDATES.md](../system-settings/UPDATES.md)
- [../system-settings/BACKUP.md](../system-settings/BACKUP.md)
- [PLUGINS.md](PLUGINS.md)
