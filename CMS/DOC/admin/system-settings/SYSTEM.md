# 365CMS – Systeminfo & Diagnose

Kurzbeschreibung: Aktuelle Betriebs-, Monitoring- und Diagnoseoberflächen im Admin-Bereich.

Letzte Aktualisierung: 2026-05-09 · Version 2.9.630

Die systemnahen Werkzeuge sind heute auf mehrere Oberflächen verteilt statt in einer einzigen Seite gebündelt.

| Route | Zweck |
|---|---|
| `/admin/info` | Systeminformationen, technische Übersicht |
| `/admin/diagnose` | Diagnosefokus und Prüfungen |
| `/admin/monitor-response-time` | Antwortzeiten beobachten |
| `/admin/monitor-cron-status` | Cron- und Job-Status |
| `/admin/monitor-disk-usage` | Speicher- und Verzeichnisnutzung |
| `/admin/monitor-scheduled-tasks` | geplante Aufgaben |
| `/admin/monitor-health-check` | allgemeine Health-Checks inklusive realem lokalem Endpoint-Probe |
| `/admin/monitor-email-alerts` | Status und Ziele von Mail-Benachrichtigungen |
| `/admin/cms-logs` | CMS-Dateilogs, PHP Error-Log, operatives Audit und Update-Historie |

---

## Legacy-Verhalten

Die Logzentrale `/admin/cms-logs` verbindet Dateilogs inzwischen mit dem operativen Audit aus `audit_log` und der persistenten Update-Historie des Update-Services. Dadurch lassen sich System-, Backup-, Monitoring-, Cron-/Queue- und Performance-Aktionen direkt im Diagnosekontext nachvollziehen, statt sie nur über verstreute Einzeloberflächen oder rohe Dateilogs rekonstruieren zu müssen.

Die frühere Legacy-Route `/admin/system-info` leitet im aktuellen Stand auf `/admin/info` um. Dokumentation und Support sollten daher immer mit `/admin/info` und `/admin/diagnose` arbeiten.
