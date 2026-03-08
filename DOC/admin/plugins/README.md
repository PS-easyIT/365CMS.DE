# 365CMS – Plugin-Verwaltung
> **Stand:** 2026-03-08 | **Version:** 2.5.4 | **Status:** Aktuell

<!-- ADDED: 2026-03-08 -->

## Überblick

Die Plugin-Verwaltung unter `/admin/plugins` ermöglicht Installation, Aktivierung,
Deaktivierung und Löschung von Erweiterungen. Der Entry-Point `CMS/admin/plugins.php`
delegiert an `PluginsModule`, das installierte Plugins erkennt und Verwaltungsaktionen ausführt.

Ergänzt wird der Bereich durch den Marketplace für neue Erweiterungen und ein
zentrales Update-System.

## Verfügbare Funktionen

| Funktion | Route | Beschreibung |
|---|---|---|
| Plugin-Liste | `/admin/plugins` | Installierte Plugins verwalten, aktivieren, deaktivieren |
| Marketplace | `/admin/marketplace` | Neue Erweiterungen aus dem Plugin-Katalog installieren |
| Updates | `/admin/updates` | Verfügbare Updates prüfen und einspielen |

## Benötigte Rechte

- Rolle **Admin** erforderlich

## Verwandte Dokumente

- [PLUGINS.md](PLUGINS.md)
- [MARKETPLACE.md](MARKETPLACE.md)
- [UPDATES.md](UPDATES.md)
