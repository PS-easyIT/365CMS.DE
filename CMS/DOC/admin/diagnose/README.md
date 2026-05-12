# 365CMS – Diagnose & Monitoring
> **Stand:** 2026-05-12 | **Version:** 2.9.770 | **Status:** Aktuell

<!-- UPDATED: 2026-05-12 -->

## Überblick

Der Diagnosebereich stellt systemnahe Monitoring-Werkzeuge für den laufenden Betrieb bereit.
Die Monitor-Seiten folgen dem üblichen Admin-Flow mit geschütztem Zugriff und serverseitiger Datenaufbereitung. Der zentrale Einstieg erfolgt über `/admin/diagnose`; ergänzend existieren dedizierte Monitor-Unterseiten.

## Verfügbare Funktionen

| Funktion | Beschreibung |
|---|---|
| System-Monitor | Zentrale Einstiegsseite mit Statusübersicht |
| Cron-Status | Überwachung geplanter Aufgaben und Laufzeiten |
| Disk Usage | Speicherverbrauch nach Verzeichnissen und Medien |
| Health-Checks | Automatisierte Prüfungen kritischer Systemkomponenten inklusive realem lokalem Health-Endpunkt-Check |
| Security-Alerts | Schwellenwert-Mails für Login-Brute-Force, AntiSpam-Spitzen und Firewall-Blocks über die bestehende Mail-Queue |
| Logs & Protokolle | CMS-Dateilogs, PHP Error-Log, operatives Audit für System/Backups/Performance sowie Update-Historie mit sprechender Benutzeranzeige |
| Diagnosebericht-Export | POST-/CSRF-geschützter ZIP-Export mit Systeminfo, Health-Check, Asset-/Cron-Status, geplanten Tasks und redigierten Log-Auszügen |
| PHP / MySQL Info | Laufzeitumgebung und Datenbankstatus |

## Benötigte Rechte

- Rolle **Admin** erforderlich
- CSRF-Kontext: `admin_system_info` für alle Shared-Monitoring-Aktionen

## Verwandte Dokumente

- [DIAGNOSE.md](DIAGNOSE.md)
- [../info/README.md](../info/README.md)
- [../performance/README.md](../performance/README.md)
