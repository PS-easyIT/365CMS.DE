# Plugin-Verwaltung

Kurzbeschreibung: Dokumentiert die aktuelle Plugin-Verwaltung unter `/admin/plugins` mit Aktivierung, Deaktivierung, Löschung und den Datenquellen für installierte Erweiterungen.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

## Überblick

Die zentrale Plugin-Verwaltung ist über `/admin/plugins` erreichbar. Der Entry-Point `CMS/admin/plugins.php` delegiert an `PluginsModule`, das installierte Plugins erkennt, aktive Zustände ermittelt und Verwaltungsaktionen ausführt.

Die Seite ist für lokal installierte Plugins zuständig. Die Installation neuer Erweiterungen aus einem Katalog ist in [MARKETPLACE.md](MARKETPLACE.md) dokumentiert.

## Datenquellen

Die aktuelle Implementierung kombiniert mehrere Quellen:

- Plugin-Verzeichnisse im lokalen Plugin-Pfad
- Metadaten aus der jeweiligen Hauptdatei bzw. `update.json`
- aktive Plugins aus den Settings oder aus dem `PluginManager`

Damit beschreibt die Liste den realen Dateisystemzustand und nicht nur einen separaten Datenbankindex.

## Unterstützte Aktionen

`CMS/admin/plugins.php` verarbeitet aktuell diese POST-Aktionen:

- `activate`
- `deactivate`
- `delete`

Alle Aktionen verwenden den CSRF-Kontext `admin_plugins`.

## Aktivieren und Deaktivieren

Beim Aktivieren oder Deaktivieren ruft das Modul die entsprechende Plugin-Logik an und aktualisiert den aktiven Zustand. Welche Initialisierungs- oder Cleanup-Schritte intern stattfinden, hängt vom jeweiligen Plugin und vom Plugin-Manager ab.

Wichtig ist: Die aktuelle Admin-Seite dokumentiert keine generische Pflicht-API mit `register()`, `install()`, `deactivate()` und `uninstall()` als harte Voraussetzung. Solche Muster können vorkommen, sind aber nicht die alleinige Wahrheit der Live-Implementierung.

## Löschen

Das Löschen entfernt ein Plugin aus dem lokalen Bestand. Dabei prüft die Verwaltung den Ziel-Slug serverseitig und verarbeitet die Aktion nur nach gültigem CSRF-Token. Vor produktiven Löschungen sollte geprüft werden, ob das Plugin eigene Daten, Tabellen oder abhängige Erweiterungen besitzt.

## Metadaten im Listing

Die Oberfläche zeigt je Plugin typischerweise:

- Namen
- Slug
- Beschreibung
- Version
- Autor
- Aktivstatus
- verfügbare Update-Informationen, sofern Metadaten vorliegen

Wenn eine `update.json` vorhanden ist, kann sie zusätzliche Versions- und Updateinformationen liefern.

## Audit und Rückmeldungen

Aktionen liefern ihr Ergebnis als Session-basierte Admin-Meldung zurück. Dadurch werden Erfolgs- oder Fehlerzustände nach Redirect auf `/admin/plugins` eingeblendet.

## Sicherheit

Die Plugin-Verwaltung folgt dem aktuellen Admin-Muster:

- Zugriff nur für Administratoren
- CSRF-Prüfung via `Security::instance()->verifyToken(..., 'admin_plugins')`
- serverseitige Bereinigung des Plugin-Slugs
- Redirect nach jeder schreibenden Aktion

## Relevante Dateien

| Datei | Zweck |
|---|---|
| `CMS/admin/plugins.php` | Entry-Point und Aktions-Dispatch |
| `CMS/admin/modules/plugins/PluginsModule.php` | Laden, Aktivieren, Deaktivieren und Löschen |
| `CMS/admin/views/plugins/list.php` | Ausgabe der Plugin-Liste |

## Verwandte Dokumente

- [MARKETPLACE.md](MARKETPLACE.md)
- [UPDATES.md](UPDATES.md)
- [../../plugins/GUIDE.md](../../plugins/GUIDE.md)
