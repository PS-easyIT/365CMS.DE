> **Website:** [365CMS.DE](https://365cms.de/) | **Version:** 3.4.00
> **Datum:** 2026-09-06 | **Status:** Abgeschlossen – **Zuletzt aktualisiert am:** 2026-09-06
> **Kurzbeschreibung:** Bedienungs- und Technikreferenz für Diagnose, Betriebslogs, Healthchecks, Cron, Assets, Speicher und Warnungen im Admin.

# 365CMS Admin – Monitoring

## English

### Administrator guide

Start at `/admin/diagnose` or `/admin/cms-logs`. Use the dedicated monitoring routes for health, response time, cron status, scheduled tasks, disk usage, assets, email alerts, and warnings. Treat warnings as operational signals: verify the affected service, review the related log entry, and record the remediation.

Monitoring is read-only from the operator perspective. Do not expose tokens, credentials, raw prompts, or unnecessary personal data in screenshots or exported reports.

### Technical reference

Diagnostic views are under `CMS/admin/views/system/` and `CMS/admin/views/logs/`. `CMS/core/Logger.php`, `CMS/core/AuditLogger.php`, `CMS/core/StatusService.php`, `CMS/core/MonitoringTrendService.php`, and `CMS/core/Services/CronRunnerService.php` provide the core interfaces. Operational, PHP-error, security-audit, and channel logs are separated by the corresponding log module and view.

The admin router and capability checks protect every monitoring route. Optional data sources use bounded fallbacks so one failing probe does not make the admin shell fatal. Log filters and exports must be server-validated and escaped before rendering.

## Deutsch

### Anwenderleitfaden

Beginnen Sie unter `/admin/diagnose` oder `/admin/cms-logs`. Für Health, Antwortzeit, Cron, geplante Aufgaben, Speicher, Assets, E-Mail-Warnungen und allgemeine Warnungen gibt es eigene Monitoring-Routen. Warnungen sind Betriebssignale: betroffenen Dienst prüfen, Logeintrag kontrollieren und die Behebung dokumentieren.

Monitoring ist aus Bedienersicht lesend. Tokens, Zugangsdaten, Rohprompts und unnötige personenbezogene Daten dürfen nicht in Screenshots oder Exporten erscheinen.

### Technische Referenz

Diagnose-Views liegen unter `CMS/admin/views/system/`, Log-Views unter `CMS/admin/views/logs/`. `CMS/core/Logger.php`, `CMS/core/AuditLogger.php`, `CMS/core/StatusService.php`, `CMS/core/MonitoringTrendService.php` und `CMS/core/Services/CronRunnerService.php` bilden die Core-Schnittstellen. Betriebs-, PHP-Fehler-, Security-Audit- und Channel-Logs sind durch Module und Views getrennt.

Admin-Router und Capability-Prüfungen schützen jede Monitoring-Route. Optionale Datenquellen verwenden begrenzte Fallbacks, damit ein fehlerhafter Probe-Check nicht die Admin-Shell beendet. Filter und Exporte werden serverseitig geprüft und vor der Ausgabe escaped.
