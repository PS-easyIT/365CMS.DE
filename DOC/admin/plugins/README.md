# 365CMS – Plugin-Verwaltung
> **Stand:** 17.05.2026 | **Version:** 3.0.11 | **Status:** Aktuell

<!-- UPDATED: 2026-05-17 -->

## Überblick

Die Plugin-Verwaltung unter `/admin/plugins` ermöglicht Installation, Aktivierung,
Deaktivierung und Löschung von Erweiterungen. Der Entry-Point `CMS/admin/plugins.php`
delegiert an `PluginsModule`, das installierte Plugins erkennt und Verwaltungsaktionen ausführt.

Ergänzt wird der Bereich durch den Marketplace für neue Erweiterungen und ein
zentrales Update-System.

Maßgeblich für den Live-Betrieb ist dabei immer `CMS/plugins/`. Ein Plugin, das nur im separaten Repository `365CMS.DE-PLUGINS/` existiert, taucht in dieser Verwaltung erst nach realer Installation bzw. Deployment in die Runtime-Struktur auf.

## Seitenstruktur

Die Plugin-Verwaltung ist klar in Seitenkopf, Kennzahlen und Arbeitsbereich gegliedert:

- Kopfbereich mit Kurzstatus und direktem Installationszugriff
- KPI-Block für Gesamt-, Aktiv- und Inaktiv-Zahlen
- Haupttabelle mit Status-/Aktionsspalte
- ergänzende Kurzhilfe mit persistenten fachlichen Hinweisen

Aktivieren, Deaktivieren und Löschen bleiben unverändert.

## Verfügbare Funktionen

| Funktion | Route | Beschreibung |
|---|---|---|
| Plugin-Liste | `/admin/plugins` | Installierte Plugins verwalten, aktivieren, deaktivieren |
| Marketplace | `/admin/plugin-marketplace` | Neue Erweiterungen aus dem Plugin-Katalog installieren |
| Updates | `/admin/updates` | Verfügbare Updates prüfen und einspielen |

## Benötigte Rechte

- Rolle **Admin** erforderlich

## Verwandte Dokumente

- [PLUGINS.md](PLUGINS.md)
- [MARKETPLACE.md](MARKETPLACE.md)
- [UPDATES.md](UPDATES.md)
