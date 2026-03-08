# Plugin-Marketplace

Kurzbeschreibung: Beschreibt den aktuellen Plugin-Marketplace unter `/admin/plugin-marketplace`, seine Datenquellen und den Installationsablauf für katalogisierte Plugins.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

## Überblick

Der Plugin-Marketplace ist über `/admin/plugin-marketplace` erreichbar und wird durch `CMS/admin/plugin-marketplace.php` sowie `PluginMarketplaceModule` bereitgestellt.

Die Seite ergänzt die lokale Plugin-Verwaltung aus [PLUGINS.md](PLUGINS.md) um einen katalogbasierten Installationspfad.

## Datenquellen des Katalogs

Der Marketplace kann Einträge aus mehreren Quellen laden:

- einer konfigurierten Registry-URL über `plugin_registry_url`
- lokalen oder eingebauten Katalogdaten
- ergänzenden Plugin-Metadaten aus den gelisteten Einträgen

Zusätzlich erkennt das Modul, welche Slugs bereits installiert sind, damit der Marketplace verfügbare und bereits vorhandene Plugins unterscheiden kann.

## Unterstützte Aktion

Aktuell verarbeitet die Seite genau eine schreibende Aktion:

- `install`

Die Aktion nutzt den CSRF-Kontext `admin_plugin_mp`.

## Installationsablauf

Der Marketplace installiert Plugins auf Basis ihres Slugs. Vereinfacht läuft der Prozess wie folgt:

1. Katalogeintrag zum Slug ermitteln
2. Download-URL validieren
3. Plugin-Paket als ZIP laden
4. Archiv entpacken und in den Plugin-Pfad übernehmen
5. Ergebnis als Admin-Meldung zurückgeben

Die Download-Quelle muss HTTPS-basiert sein. Dadurch wird verhindert, dass Erweiterungen aus unsicheren Quellen still installiert werden.

## Sichtbarkeit im Admin

Der Marketplace erscheint in der Sidebar nur, wenn die entsprechende Funktion aktiviert ist. Maßgeblich ist die Einstellung `marketplace_enabled`, die beim Rendern der Sidebar ausgewertet wird.

## Unterschied zu Themes

Es gibt zwar ebenfalls Marketplace-Dokumentation im Design-Bereich, die Plugin-Seite hat jedoch eine eigene technische Rolle:

- Fokus auf Plugin-Katalog und ZIP-Installation
- Abgleich installierter Plugin-Slugs
- getrennte Admin-Route und eigener CSRF-Kontext

## Sicherheit

Die Marketplace-Seite verwendet:

- Admin-Zugriffsprüfung
- CSRF-Prüfung via `Security::instance()->verifyToken(..., 'admin_plugin_mp')`
- serverseitige Validierung des Slugs
- kontrollierte Download- und Installationslogik aus dem Modul

## Relevante Dateien

| Datei | Zweck |
|---|---|
| `CMS/admin/plugin-marketplace.php` | Entry-Point und POST-Handling |
| `CMS/admin/modules/plugins/PluginMarketplaceModule.php` | Katalog, Installation und Quellenverwaltung |
| `CMS/admin/views/plugins/marketplace.php` | Ausgabe des Marketplace |

## Verwandte Dokumente

- [PLUGINS.md](PLUGINS.md)
- [UPDATES.md](UPDATES.md)
- [../themes-design/MARKETPLACE.md](../themes-design/MARKETPLACE.md)
