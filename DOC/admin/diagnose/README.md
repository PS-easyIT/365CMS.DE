# 365CMS – Diagnose & Monitoring
> **Stand:** 2026-03-28 | **Version:** 2.8.0 RC | **Status:** Aktuell

<!-- UPDATED: 2026-03-28 -->

## Überblick

Der Diagnosebereich stellt systemnahe Monitoring-Werkzeuge für den laufenden Betrieb bereit.
Die Monitor-Seiten folgen dem üblichen Admin-Flow mit geschütztem Zugriff und serverseitiger Datenaufbereitung. Der zentrale Einstieg erfolgt über `/admin/diagnose`; ergänzend existieren dedizierte Monitor-Unterseiten.

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
- CSRF-Kontext: abhängig von der jeweiligen Diagnose-Aktion

## Verwandte Dokumente

- [DIAGNOSE.md](DIAGNOSE.md)
- [../info/README.md](../info/README.md)
- [../performance/README.md](../performance/README.md)
