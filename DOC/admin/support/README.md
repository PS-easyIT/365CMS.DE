# 365CMS – Support / Dokumentation

Kurzbeschreibung: Einordnung der früheren Support-Seite im aktuellen 365CMS-Stand.

Letzte Aktualisierung: 2026-03-07

---

## Aktueller Status

Die frühere Route `/admin/support` ist im aktuellen Core **keine eigenständige Support-Ticket-Oberfläche mehr**.

`CMS/admin/support.php` fungiert derzeit nur noch als **Legacy-Weiterleitung** auf:

- `/admin/documentation`

---

## Was das konkret bedeutet

- In der Sidebar gibt es keinen führenden Support-Bereich mehr.
- Bestehende Altlinks auf `/admin/support` bleiben nutzbar, landen aber in der lokalen Dokumentationsansicht.
- Aussagen über ein aktiv gepflegtes Core-Ticket-System gelten für den aktuellen Stand nicht mehr als Referenz.

---

## Empfohlene aktuelle Einstiege

| Route | Zweck |
|---|---|
| `/admin/documentation` | lokale Projektdokumentation im Admin |
| `/admin/info` | Betriebs- und Systeminformationen |
| `/admin/diagnose` | technische Diagnose und Prüfungen |

---

## Historischer Kontext

Ältere Dokumentationsstände beschrieben ein internes Support-Ticket-System mit eigenen Tabellen und Statuslogik. Diese Dokumentation ist für den verifizierten Stand 2.3.1 nicht mehr maßgeblich, solange die aktuelle Core-Implementierung nur eine Weiterleitung bereitstellt.

---

## Verwandte Dokumente

- [../README.md](../README.md)
- [../system-settings/README.md](../system-settings/README.md)
