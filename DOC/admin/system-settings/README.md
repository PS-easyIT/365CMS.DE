# 365CMS – System, Betrieb & Wartung

Kurzbeschreibung: Überblick über Konfiguration, Updates, Backups, Info-Ansichten, Diagnose und geplante AI-Steuerung im Admin-Bereich.

Letzte Aktualisierung: 2026-04-08 · Version 2.9.2

Der Bereich „System" wurde in mehrere klar getrennte Einstiege aufgeteilt.

Die Übersicht bezieht sich auf die aktuelle 2.9.2-Bedienstruktur. Für Runtime-Fragen zu Konfiguration, Bootstrap, Logs oder Dateisystempfaden ergänzen `DOC/INSTALLATION.md`, `DOC/core/ARCHITECTURE.md` und `DOC/FILELIST.md` diese Seite.

### System-Gruppe

| Route | Zweck |
|---|---|
| `/admin/settings` | allgemeine Systemeinstellungen inkl. SMTP-/Mail-Status und Testversand |
| `/admin/backups` | Backups erstellen, auflisten und löschen |
| `/admin/updates` | Core-, Theme- und Plugin-Updates |
| `/admin/cms-logs` | konfigurierte CMS-Logdateien, Kanal-Einträge und schnelle Laufzeitdiagnose |

### Geplante Erweiterung (noch nicht runtime-aktiv)

| Zielroute | Zweck |
|---|---|
| `/admin/ai-services` | zentrale Provider-, Feature- und Scope-Steuerung für künftige AI-Funktionen im Admin |

Wichtig: Der Bereich **AI Services** ist im aktuellen Stand **dokumentiert, aber noch nicht implementiert**. Geplant ist eine Einordnung unter **System / Einstellungen**, damit Provider-Scope, Limits, Datenschutz und redaktionelle KI-Helfer nicht über mehrere Fachmodule verteilt werden.

### Info-Gruppe

| Route | Zweck |
|---|---|
| `/admin/info` | Systeminformationen und Betriebsübersicht |
| `/admin/documentation` | lokale Dokumentationsansicht im Admin inklusive Logpfad- und Sync-Kontext |

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
| [AI-SERVICES.md](AI-SERVICES.md) | Admin-Einordnung und Routing-Kontext für den geplanten Bereich `AI Services` |
| [../../ai/AI-SERVICES.md](../../ai/AI-SERVICES.md) | kanonische Fach- und Architektur-Doku zu Provider-Scope, Editor.js-Translation und offenen Punkten |
| [../info/INFO.md](../info/INFO.md) | Systeminfo und Dokumentation |
| [../diagnose/DIAGNOSE.md](../diagnose/DIAGNOSE.md) | Diagnose & Monitoring |
| [../performance/PERFORMANCE.md](../performance/PERFORMANCE.md) | Performance-Center |
