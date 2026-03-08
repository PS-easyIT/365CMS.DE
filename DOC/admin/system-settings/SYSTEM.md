# 365CMS – Systeminfo & Diagnose

Kurzbeschreibung: Aktuelle Betriebs-, Monitoring- und Diagnoseoberflächen im Admin-Bereich.

Letzte Aktualisierung: 2026-03-08 · Version 2.3.1

Die systemnahen Werkzeuge sind heute auf mehrere Oberflächen verteilt statt in einer einzigen Seite gebündelt.

| Route | Zweck |
|---|---|
| `/admin/info` | Systeminformationen, technische Übersicht |
| `/admin/diagnose` | Diagnosefokus und Prüfungen |
| `/admin/monitor-response-time` | Antwortzeiten beobachten |
| `/admin/monitor-cron-status` | Cron- und Job-Status |
| `/admin/monitor-mail-queue` | Mail-Queue, Retry-Gründe und stale Locks |
| `/admin/monitor-disk-usage` | Speicher- und Verzeichnisnutzung |
| `/admin/monitor-scheduled-tasks` | geplante Aufgaben |
| `/admin/monitor-health-check` | allgemeine Health-Checks |
| `/admin/monitor-email-alerts` | Status und Ziele von Mail-Benachrichtigungen |

---

## Legacy-Verhalten

Die frühere Legacy-Route `/admin/system-info` leitet im aktuellen Stand auf `/admin/info` um. Dokumentation und Support sollten daher immer mit `/admin/info` und `/admin/diagnose` arbeiten.
