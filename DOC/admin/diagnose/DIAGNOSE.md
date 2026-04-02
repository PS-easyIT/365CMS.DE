# 365CMS – Diagnose & Monitoring

Kurzbeschreibung: Dokumentiert die Diagnose-Oberflächen und Monitoring-Werkzeuge für den laufenden Betrieb von 365CMS.

Letzte Aktualisierung: 2026-03-28 · Version 2.8.0 RC

---

## Überblick

Der Diagnosebereich umfasst eine zentrale Einstiegsseite und sechs spezialisierte Monitoring-Oberflächen. Alle Seiten nutzen `SystemInfoModule` als gemeinsames Modul und teilen sich den CSRF-Kontext `admin_system_info`.

**Gemeinsamer technischer Aufbau:**

| Baustein | Datei |
|---|---|
| Shared Entry Point | `CMS/admin/system-monitor-page.php` |
| Modul | `CMS/admin/modules/system/SystemInfoModule.php` |
| Subnav | `CMS/admin/views/system/subnav.php` |

---

## Routen und Zuständigkeiten

| Route | View | Zweck |
|---|---|---|
| `/admin/diagnose` | `views/system/diagnose.php` | Datenbank-Diagnose, Tabellenprüfung |
| `/admin/monitor-response-time` | `views/system/response-time.php` | Antwortzeiten-Monitoring |
| `/admin/monitor-cron-status` | `views/system/cron-status.php` | Cron-Job-Übersicht und -Status |
| `/admin/monitor-disk-usage` | `views/system/disk-usage.php` | Speicher- und Verzeichnisnutzung |
| `/admin/monitor-scheduled-tasks` | `views/system/scheduled-tasks.php` | Geplante Aufgaben und deren Ausführungsstatus |
| `/admin/monitor-health-check` | `views/system/health-check.php` | Allgemeine Systemgesundheitsprüfungen |
| `/admin/monitor-email-alerts` | `views/system/email-alerts.php` | E-Mail-Benachrichtigungen konfigurieren und Status |

---

## Diagnose-Datenbank

Die Einstiegsseite `/admin/diagnose` fokussiert sich auf die Prüfung der Datenbankintegrität:

- Tabellenstatus und -größen
- fehlende oder beschädigte Indizes
- allgemeine Konsistenzprüfungen

---

## Monitoring-Werkzeuge

### Response-Time Monitoring

Misst und protokolliert Antwortzeiten des Systems, um Engpässe frühzeitig zu erkennen.

### Cron-Job Status

Zeigt den aktuellen Status von Cron-Jobs, letzte Ausführung und eventuelle Fehler.

Zusätzlich wird geprüft, ob eine zentrale Datei `cron.php` im CMS-Webroot vorhanden ist. Darüber kann u. a. der Hook `cms_cron_mail_queue` für die Mail-Queue-Verarbeitung per Web-Cron oder CLI ausgelöst werden.

### Disk-Usage

Schlüsselt den Speicherverbrauch nach Verzeichnissen auf und warnt bei kritischen Schwellwerten.

### Scheduled Tasks

Listet alle geplanten Aufgaben mit Status, Intervall und letzter Ausführung.

### Health-Check

Bündelt übergreifende Gesundheitsprüfungen: PHP-Version, Speicher, Erreichbarkeit externer Dienste.

### E-Mail-Benachrichtigungen

Konfiguriert Zieladressen und Schwellwerte für Monitoring-Benachrichtigungen per E-Mail und erlaubt einen direkten Testversand aus dem Backend über die zentrale Mail-Implementierung.

---

## Sicherheit

Alle Diagnoseseiten folgen dem Admin-Standardmuster:

- Zugriff nur für Administratoren
- CSRF-Prüfung via `Security::instance()->verifyToken(..., 'admin_system_info')`
- POST-Ergebnis als Session-Alert, Redirect auf GET-Route

---

## Verwandte Dokumente

- [../system-settings/SYSTEM.md](../system-settings/SYSTEM.md)
- [../system-settings/README.md](../system-settings/README.md)
- [../../audit/AUDIT_FACHBEREICHE.md](../../audit/AUDIT_FACHBEREICHE.md)
