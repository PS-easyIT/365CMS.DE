# 365CMS – System, Betrieb & Wartung

Kurzbeschreibung: Überblick über Konfiguration, Updates, Backups, Info-Ansichten, Diagnose und die inzwischen eingehängte AI-Steuerung im Admin-Bereich.

Letzte Aktualisierung: 2026-05-12 · Version 2.9.767

Der Bereich „System" wurde in mehrere klar getrennte Einstiege aufgeteilt.

Die Übersicht bezieht sich auf die aktuelle 2.9.767-Bedienstruktur. Für Runtime-Fragen zu Konfiguration, Bootstrap, Logs oder Dateisystempfaden ergänzen `DOC/INSTALLATION.md`, `DOC/core/ARCHITECTURE.md` und `DOC/FILELIST.md` diese Seite.

### System-Gruppe

| Route | Zweck |
|---|---|
| `/admin/settings` | allgemeine Systemeinstellungen inkl. SMTP-/Mail-Status und Testversand |
| `/admin/ai-services` | zentrale AI-Settings für Provider, Feature-Gates, Translation, Prompt-Vorlagen, Logging, Monitoring und Quotas |
| `/admin/backups` | Backups erstellen, auflisten, herunterladen, validieren, im Dry-Run prüfen, wiederherstellen und löschen |
| `/admin/updates` | Core-, Theme- und Plugin-Updates zentral prüfen, Vorabbedingungen validieren und installieren |
| `/admin/cms-logs` | konfigurierte CMS-Logdateien, PHP Error-Log, operatives Audit und Update-Historie |

Wichtig: Der Bereich **AI Services** ist inzwischen als **Admin-, Settings- und erste Runtime-Schicht** implementiert. Ollama und Azure AI sind als Live-Provider verdrahtet, Editor.js-Übersetzungen laufen mit verpflichtendem Preview-/Diff-Workflow, und Prompt-Vorlagen werden seit `2.9.703` je Bereich verwaltet.

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
| [AI-SERVICES.md](AI-SERVICES.md) | Admin-Einordnung und Routing-Kontext für den Bereich `AI Services` |
| [../../ai/AI-SERVICES.md](../../ai/AI-SERVICES.md) | kanonische Fach- und Architektur-Doku zu Provider-Scope, Editor.js-Translation und offenen Punkten |
| [../info/INFO.md](../info/INFO.md) | Systeminfo und Dokumentation |
| [../diagnose/DIAGNOSE.md](../diagnose/DIAGNOSE.md) | Diagnose & Monitoring |
| [../performance/PERFORMANCE.md](../performance/PERFORMANCE.md) | Performance-Center |
