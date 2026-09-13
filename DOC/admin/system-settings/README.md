# 365CMS – Projektdokumentation | Abschnitt: System settings
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English
### Scope
This section covers AI, backups, monitoring, system information, and updates. Pages are capability-aware and may be hidden when a module is disabled.

### Screens
| Area | Route | Source |
|---|---|---|
| AI services | `/admin/ai-services` | `CMS/admin/ai-services.php`, `CMS/admin/views/system/ai-services.php` |
| Backups | `/admin/backups` | `CMS/admin/backups.php`, `CMS/admin/modules/system/BackupsModule.php` |
| Monitoring | `/admin/diagnose` | `CMS/admin/system-monitor-page.php`, `CMS/core/Services/MonitoringTrendService.php` |
| System | `/admin/settings` | `CMS/admin/system.php`, `CMS/admin/system-info.php` |
| Updates | `/admin/updates` | `CMS/admin/updates.php`, `CMS/admin/modules/system/UpdatesModule.php` |

### Common controls
Read status and warnings first. State-changing requests require authentication, capability, CSRF/nonce validation, normalized input, and server-side allowlists. Use `CMS/core/AuditLogger.php` for security events and `CMS/core/Logger.php` for operational diagnostics. GET is read-only; verify the post-redirect result.

## Deutsch
### Umfang
Dieser Abschnitt behandelt KI, Backups, Monitoring, Systeminformationen und Updates. Seiten sind capability-gesteuert und können bei deaktiviertem Modul fehlen.

### Seiten
| Bereich | Route | Quelle |
|---|---|---|
| KI-Dienste | `/admin/ai-services` | `CMS/admin/ai-services.php`, `CMS/admin/views/system/ai-services.php` |
| Backups | `/admin/backups` | `CMS/admin/backups.php`, `CMS/admin/modules/system/BackupsModule.php` |
| Monitoring | `/admin/diagnose` | `CMS/admin/system-monitor-page.php`, `CMS/core/Services/MonitoringTrendService.php` |
| System | `/admin/settings` | `CMS/admin/system.php`, `CMS/admin/system-info.php` |
| Updates | `/admin/updates` | `CMS/admin/updates.php`, `CMS/admin/modules/system/UpdatesModule.php` |

### Gemeinsame Regeln
Status und Warnungen zuerst lesen. Zustandsändernde Anfragen benötigen Authentifizierung, Capability, CSRF/Nonce, normalisierte Eingaben und serverseitige Allowlists. `CMS/core/AuditLogger.php` protokolliert Sicherheitsereignisse, `CMS/core/Logger.php` Betriebsdiagnosen. GET bleibt lesend; das Ergebnis nach der Weiterleitung prüfen.
