# 365CMS – Projektdokumentation | Abschnitt: Monitoring
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English
### Purpose
The diagnostic and monitoring screen is available at `/admin/diagnose`. It presents bounded health, performance, scheduled-task, and warning information.

### Implementation
- Entry: `CMS/admin/system-monitor-page.php`
- Services: `CMS/core/Services/MonitoringTrendService.php`, `CMS/core/Services/StatusService.php`, `CMS/core/Services/CronRunnerService.php`
- Logs: `CMS/core/Logger.php`, `CMS/core/AuditLogger.php`

### Interpretation
Treat a degraded dependency as a warning or fallback, not as permission to retry destructive actions. Do not publish secrets or personal data from diagnostics. Capture the displayed timestamp and warning category when escalating an incident.

## Deutsch
### Zweck
Die Diagnose- und Monitoring-Seite ist unter `/admin/diagnose` verfügbar. Sie zeigt begrenzte Gesundheits-, Performance-, Aufgaben- und Warninformationen.

### Implementierung
- Einstieg: `CMS/admin/system-monitor-page.php`
- Services: `CMS/core/Services/MonitoringTrendService.php`, `CMS/core/Services/StatusService.php`, `CMS/core/Services/CronRunnerService.php`
- Logs: `CMS/core/Logger.php`, `CMS/core/AuditLogger.php`

### Auswertung
Eine gestörte Abhängigkeit ist als Warnung oder Fallback zu behandeln und rechtfertigt keine destruktiven Wiederholungen. Geheimnisse und personenbezogene Daten nicht aus Diagnosen veröffentlichen. Bei Eskalationen Zeitstempel und Warnkategorie festhalten.
