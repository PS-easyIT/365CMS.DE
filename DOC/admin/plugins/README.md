# 365CMS – Plugin-Verwaltung
> **Stand:** 2026-04-07 | **Version:** 2.9.0 | **Status:** Aktuell

<!-- UPDATED: 2026-04-07 -->

## Überblick

Die Plugin-Verwaltung unter `/admin/plugins` ermöglicht Installation, Aktivierung,
Deaktivierung und Löschung von Erweiterungen. Der Entry-Point `CMS/admin/plugins.php`
delegiert an `PluginsModule`, das installierte Plugins erkennt und Verwaltungsaktionen ausführt.

Ergänzt wird der Bereich durch den Marketplace für neue Erweiterungen und ein
zentrales Update-System.

Maßgeblich für den Live-Betrieb ist dabei immer `CMS/plugins/`. Ein Plugin, das nur im separaten Repository `365CMS.DE-PLUGINS/` existiert, taucht in dieser Verwaltung erst nach realer Installation bzw. Deployment in die Runtime-Struktur auf.

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
