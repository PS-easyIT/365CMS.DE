# 365CMS – Diagnose & Monitoring

Kurzbeschreibung: Dokumentiert die Diagnose-Oberflächen, Monitoring-Werkzeuge und die zentrale Logzentrale für den laufenden Betrieb von 365CMS.

Letzte Aktualisierung: 2026-05-12 · Version 2.9.770

---

## Überblick

Der Diagnosebereich umfasst eine zentrale Einstiegsseite, mehrere spezialisierte Monitoring-Oberflächen und die gemeinsame Logzentrale. Alle Seiten nutzen `SystemInfoModule` als gemeinsames Modul und teilen sich den CSRF-Kontext `admin_system_info`.

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
| `/admin/cms-logs` | `views/system/cms-logs.php` | CMS-Dateilogs, PHP Error-Log, operatives Audit und Update-Historie |

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

Bündelt übergreifende Gesundheitsprüfungen: Datenbank, beschreibbare Betriebsverzeichnisse, Response-Time, Disk-Auslastung und einen real geprüften lokalen Health-Endpunkt. Der konfigurierte Pfad wird dabei auf host-lokale relative Pfade normalisiert und dann tatsächlich gegen die eigene Installation geprüft; ein gesetzter Schalter allein gilt nicht mehr als „gesund“.

### E-Mail-Benachrichtigungen

Konfiguriert Zieladressen und Schwellwerte für Monitoring-Benachrichtigungen per E-Mail und erlaubt einen direkten Testversand aus dem Backend über die zentrale Mail-Implementierung.

Seit 2.9.763 verwaltet dieselbe Seite zusätzlich die Security-Alarmierung für Login-Brute-Force, AntiSpam-Spitzen und Firewall-Blocks. Die Auslösung läuft ausschließlich read-only über den bestehenden stündlichen Core-Cron-Hook `cms_cron_hourly`, verwendet die vorhandene Mail-Queue bzw. Mail-Pipeline weiter und ergänzt im Admin eine kleine Statusübersicht mit aktuellem Zählfenster sowie letztem Lauf-/Versandzeitpunkt.

### Logs & Protokolle

`/admin/cms-logs` bündelt nicht mehr nur CMS-Dateilogs und das PHP Error-Log, sondern auch ein operatives Betriebs-Audit aus dem zentralen `audit_log`. Dadurch werden System-, Backup-, Monitoring-, Cron-/Queue- und Performance-Aktionen direkt im Diagnosekontext sichtbar. Ergänzend zeigt die Seite die persistierte Update-Historie des Update-Services, sodass erfolgreiche Core-, Theme- und Plugin-Updates nicht nur auf `/admin/updates`, sondern auch in der Diagnose-Logzentrale nachvollziehbar bleiben.

Seit `2.9.769` löst dieselbe Update-Historie Benutzer-IDs serverseitig auf sprechende Labels aus `display_name` plus Rollenbezeichnung auf. Fehlende oder gelöschte Konten führen dabei nicht zu Fehlern oder leeren Zellen, sondern bleiben als `User #ID` fail-soft sichtbar.

Seit `2.9.770` können `/admin/diagnose` und `/admin/cms-logs` zusätzlich einen Diagnosebericht als ZIP exportieren. Der Export bleibt im bestehenden Admin-Vertrag bewusst ein POST-/CSRF-geschützter Download, bündelt Systeminfo, Health-Check, Asset-Status, Cron-Status, geplante Tasks sowie begrenzte Logauszüge und redigiert sensible Werte wie Tokens, Passwörter, Secrets und Credentials serverseitig vor dem Schreiben ins Archiv.

---

## Sicherheit

Alle Diagnoseseiten folgen dem Admin-Standardmuster:

- Zugriff nur für Administratoren
- CSRF-Prüfung via `Security::instance()->verifyToken(..., 'admin_system_info')`
- POST-Ergebnis als Session-Alert, Redirect auf GET-Route

Ausnahme: Der Diagnosebericht-Export streamt nach erfolgreicher CSRF-Prüfung direkt den ZIP-Download zurück, ohne eine neue GET-Download-Route oder Token-URL einzuführen.

---

## Verwandte Dokumente

- [../system-settings/SYSTEM.md](../system-settings/SYSTEM.md)
- [../system-settings/README.md](../system-settings/README.md)
- [../../audit/AUDIT_FACHBEREICHE.md](../../audit/AUDIT_FACHBEREICHE.md)
