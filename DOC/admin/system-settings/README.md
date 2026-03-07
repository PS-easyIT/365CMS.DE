# 365CMS – System, Betrieb & Wartung

Kurzbeschreibung: Überblick über Konfiguration, Updates, Backups, Info-Ansichten und Diagnose im Admin-Bereich.

Letzte Aktualisierung: 2026-03-07

---

## Überblick

Der frühere, monolithische Bereich „System“ wurde in mehrere klar getrennte Einstiege aufgeteilt.

### System-Gruppe

| Route | Zweck |
|---|---|
| `/admin/settings` | allgemeine Systemeinstellungen |
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

| Route | Zweck |
|---|---|
| `/admin/performance` | Gesamtübersicht |
| `/admin/performance-cache` | Cache-Verwaltung |
| `/admin/performance-media` | Medien-Optimierung |
| `/admin/performance-database` | Datenbank-Wartung |
| `/admin/performance-settings` | Laufzeit-Optionen |
| `/admin/performance-sessions` | Session-Bereinigung und Status |
