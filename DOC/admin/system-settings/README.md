# 365CMS – System, Betrieb & Wartung

Kurzbeschreibung: Überblick über Konfiguration, Updates, Backups, Info-Ansichten und Diagnose im Admin-Bereich.

Letzte Aktualisierung: 2026-03-28 · Version 2.8.0 RC

Der Bereich „System" wurde in mehrere klar getrennte Einstiege aufgeteilt.

### System-Gruppe

| Route | Zweck |
|---|---|
| `/admin/settings` | allgemeine Systemeinstellungen inkl. SMTP-/Mail-Status und Testversand |
| `/admin/backups` | Backups erstellen, auflisten und löschen |
| `/admin/updates` | Core-, Theme- und Plugin-Updates |

### Info-Gruppe

| Route | Zweck |
|---|---|
| `/admin/info` | Systeminformationen und Betriebsübersicht |
| `/admin/documentation` | lokale Dokumentationsansicht im Admin |

### Diagnose-Gruppe

| Route | Zweck |
|---|---|
| `/admin/diagnose` | zentrale Diagnoseübersicht |
| `/admin/monitor-response-time` | Response-Time-Monitoring |
| `/admin/monitor-cron-status` | Cron-Status |
| `/admin/monitor-disk-usage` | Festplattennutzung |
| `/admin/monitor-scheduled-tasks` | geplante Aufgaben |
| `/admin/monitor-health-check` | Health-Checks |
| `/admin/monitor-email-alerts` | Mail-Benachrichtigungen |

### Performance-Gruppe

Siehe [../performance/PERFORMANCE.md](../performance/PERFORMANCE.md) für die vollständige Routenübersicht des Performance-Centers mit Cache, Medien, Datenbank, Settings und Sessions.

---

## Zugeordnete Fachdokumente

| Dokument | Schwerpunkt |
|---|---|
| [BACKUP.md](BACKUP.md) | Backup & Restore |
| [SYSTEM.md](SYSTEM.md) | Systeminfo & Diagnose-Übersicht |
| [UPDATES.md](UPDATES.md) | Core-, Theme- und Plugin-Updates |
| [../info/INFO.md](../info/INFO.md) | Systeminfo und Dokumentation |
| [../diagnose/DIAGNOSE.md](../diagnose/DIAGNOSE.md) | Diagnose & Monitoring |
| [../performance/PERFORMANCE.md](../performance/PERFORMANCE.md) | Performance-Center |
