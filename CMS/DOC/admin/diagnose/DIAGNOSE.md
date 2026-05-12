# 365CMS – Diagnose & Monitoring

Kurzbeschreibung: Dokumentiert die Diagnose-Oberflächen, Monitoring-Werkzeuge und die zentrale Logzentrale für den laufenden Betrieb von 365CMS.

Letzte Aktualisierung: 2026-05-12 · Version 2.9.777

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
| `/admin/monitor-warnings` | `views/system/warnings.php` | Zentrale Sammelansicht aktiver Warnungen mit Ignore-/Wiedervorlage-Status |
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

### Warnzentrale

`/admin/monitor-warnings` bündelt aktive Hinweise aus den bereits vorhandenen Modulen für Performance, Security, Diagnose, Updates und Recht. Die Seite selbst bleibt read-only im GET-Pfad; sie liest ausschließlich bestehende Datenquellen zusammen und erzeugt keine neuen Scan- oder Schreibpfade beim bloßen Öffnen.

Pro Warnung gibt es:

- einen direkten Link in den zuständigen Adminbereich (`Lösen / öffnen`)
- eine POST-/CSRF-geschützte Ignore-Aktion mit Begründung
- eine POST-/CSRF-geschützte Wiedervorlage (`Später erinnern`)

Die Unterdrückungszustände werden serverseitig klein in `SettingsService` gespeichert und nur für aktuell bekannte Warn-IDs berücksichtigt. Fallen Teilquellen aus oder sind einzelne Module temporär nicht lesbar, arbeitet die Warnzentrale fail-soft weiter und blendet nur die restlichen Quellen ein.

### Response-Time Monitoring

Misst und protokolliert Antwortzeiten des Systems, um Engpässe frühzeitig zu erkennen.

Seit `2.9.773` ergänzt die Seite eine Trendhistorie mit `24 h`, `7 d` und `30 d` inklusive Sparkline, Min-/Max-/Ø-Werten und Delta zum letzten Punkt. Die aktuelle Kennzahl wird weiterhin live gegen `SITE_URL` gemessen; die Historie kommt aus stündlichen Snapshots der Tabelle `monitoring_trends`.

### Cron-Job Status

Zeigt den aktuellen Status von Cron-Jobs, letzte Ausführung und eventuelle Fehler.

Zusätzlich wird geprüft, ob eine zentrale Datei `cron.php` im CMS-Webroot vorhanden ist. Darüber kann u. a. der Hook `cms_cron_mail_queue` für die Mail-Queue-Verarbeitung per Web-Cron oder CLI ausgelöst werden.

Seit `2.9.773` visualisiert dieselbe Seite zusätzlich den `Cron-Lag`, also den Abstand zum zuletzt dokumentierten stündlichen Core-Cron-Lauf. Dadurch bleibt sichtbar, ob der Hourly-Takt stabil läuft oder ob der nächste Lauf überfällig wird. Auch hier gibt es `24 h`-, `7 d`- und `30 d`-Verläufe mit Sparkline und Statistik.

### Disk-Usage

Schlüsselt den Speicherverbrauch nach Verzeichnissen auf und warnt bei kritischen Schwellwerten.

Seit `2.9.773` besitzt auch die Disk-Usage-Seite eine Trendhistorie für die Dateisystem-Auslastung in Prozent. Die Live-Werte für Gesamt-/Frei-Speicher und Verzeichnisgrößen bleiben davon getrennt und werden nicht über die Trendansicht mutiert.

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

Seit `2.9.777` sind die Bereinigungsaktionen der Logzentrale robuster: Das PHP-Error-Log wird sicher geleert, CMS-Dateilogs werden bevorzugt entfernt und bei gesperrten Dateien sicher geleert, leere Dateien tauchen nicht mehr als scheinbar ungelöschte Logdateien auf, und Fehlzustände werden als Fehler zurückgegeben statt als Erfolgsmeldung. Die Sammelaktion räumt zusätzlich operative Diagnose-Protokolle und Update-Historie weiter im bestehenden POST-/CSRF-Vertrag auf. `/admin/diagnose` ergänzt außerdem eine eigene POST-/CSRF-geschützte Aktion zum Löschen gespeicherter Fehlerreports.

### Technischer Vertrag der Trendhistorie

Die Monitoring-Trendhistorie basiert auf einem kleinen, eigenständigen Service `MonitoringTrendService`.

- Snapshot-Takt: über den bestehenden Hook `cms_cron_hourly`
- Persistenz: eigene Tabelle `monitoring_trends`
- Live-Pfad: read-only, ohne Tabellenanlage und ohne Schreibzugriff im GET-Request
- Fallback: fehlen Snapshots oder optionale Daten, bleiben die Seiten über Live-Werte und neutrale Hinweise bedienbar
- Keine Token in URLs, keine neue GET-Mutation, keine zusätzliche öffentliche Route

Für Cron wird bewusst nicht eine starre Hook-Anzahl getrendet, sondern der zeitliche Abstand zum letzten dokumentierten stündlichen Lauf. Das liefert im Betrieb die aussagekräftigere Metrik.

---

## Sicherheit

Alle Diagnoseseiten folgen dem Admin-Standardmuster:

- Zugriff nur für Administratoren
- CSRF-Prüfung via `Security::instance()->verifyToken(..., 'admin_system_info')`
- POST-Ergebnis als Session-Alert, Redirect auf GET-Route
- Unterdrückte Warnungen werden ausschließlich per POST gespeichert; Links zum eigentlichen Lösungsbereich bleiben tokenfrei und rein navigierend

Ausnahme: Der Diagnosebericht-Export streamt nach erfolgreicher CSRF-Prüfung direkt den ZIP-Download zurück, ohne eine neue GET-Download-Route oder Token-URL einzuführen.

---

## Verwandte Dokumente

- [../system-settings/SYSTEM.md](../system-settings/SYSTEM.md)
- [../system-settings/README.md](../system-settings/README.md)
- [../../audit/AUDIT_FACHBEREICHE.md](../../audit/AUDIT_FACHBEREICHE.md)
