# 365CMS – Systeminfo & Dokumentation

Kurzbeschreibung: Dokumentiert die Admin-Seiten für Systemübersicht und lokale Dokumentationsansicht.

Letzte Aktualisierung: 2026-03-28 · Version 2.8.0 RC

---

## Überblick

Der Bereich **Info** bündelt zwei Seiten für Betriebsinformationen und die integrierte Dokumentation.

| Route | View | Zweck |
|---|---|---|
| `/admin/info` | `views/system/info.php` | Systeminformationen, PHP-Version, MySQL-Status, Speicher, Extensions |
| `/admin/documentation` | `views/system/documentation.php` | Lokale Dokumentationsansicht direkt im Admin |

Beide Seiten nutzen denselben technischen Aufbau wie der Diagnosebereich über `CMS/admin/system-monitor-page.php` und den section-basierten Monitor-Flow.

---

## Systeminfo (`/admin/info`)

Die Systeminfo-Seite zeigt eine kompakte Betriebsübersicht:

- CMS-Version
- PHP-Version und wichtige Extensions
- MySQL/MariaDB-Version
- Server-Software und Betriebssystem
- Speicherlimits und Upload-Grenzen
- aktives Theme und Theme-Version
- installierte Plugins mit aktivem Status

Die Legacy-Route `/admin/system-info` leitet auf `/admin/info` um.

---

## Dokumentation (`/admin/documentation`)

Die integrierte Dokumentationsseite rendert das DOC-Verzeichnis des Projekts direkt im Admin-Backend. Damit haben Administratoren Zugriff auf Fachdokumente, ohne das Dateisystem konsultieren zu müssen.

---

## Technische Einordnung

| Baustein | Datei |
|---|---|
| Shared Entry Point | `CMS/admin/system-monitor-page.php` |
| Modul | `CMS/admin/modules/system/SystemInfoModule.php` |
| Info Entry Point | `CMS/admin/info.php` |
| Doku Entry Point | `CMS/admin/documentation.php` |

---

## Verwandte Dokumente

- [../diagnose/DIAGNOSE.md](../diagnose/DIAGNOSE.md)
- [../system-settings/README.md](../system-settings/README.md)
