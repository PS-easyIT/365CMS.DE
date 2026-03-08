# 365CMS – Diagnose & Monitoring
> **Stand:** 2026-03-08 | **Version:** 2.5.4 | **Status:** Aktuell

<!-- ADDED: 2026-03-08 -->

## Überblick

Der Diagnosebereich stellt systemnahe Monitoring-Werkzeuge für den laufenden Betrieb bereit.
Alle Unterseiten nutzen `SystemInfoModule` als gemeinsames Modul und teilen sich den
CSRF-Kontext `admin_system_info`. Der Einstieg erfolgt über `/admin/system-monitor`.

## Verfügbare Funktionen

| Funktion | Beschreibung |
|---|---|
| System-Monitor | Zentrale Einstiegsseite mit Statusübersicht |
| Cron-Status | Überwachung geplanter Aufgaben und Laufzeiten |
| Disk Usage | Speicherverbrauch nach Verzeichnissen und Medien |
| Health-Checks | Automatisierte Prüfungen kritischer Systemkomponenten |
| Error-Log | Einsicht in aktuelle Fehlermeldungen und Warnungen |
| PHP / MySQL Info | Laufzeitumgebung und Datenbankstatus |

## Benötigte Rechte

- Rolle **Admin** erforderlich
- CSRF-Kontext: `admin_system_info`

## Verwandte Dokumente

- [DIAGNOSE.md](DIAGNOSE.md)
- [../info/README.md](../info/README.md)
- [../performance/README.md](../performance/README.md)
