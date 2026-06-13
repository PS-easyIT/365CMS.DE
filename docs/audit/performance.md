# Performance

**Bereichsscore:** 74/100

## Kurzfazit
Caching- und Monitoring-Module sind vorhanden, dennoch zeigen konkrete Codefunde teure Volltabellen-/Vollbestandsoperationen in Backup, Systemdiagnose und Theme-Tagcloud. Zusätzlich ist das Repository durch vendored Assets und große Historien/Backups schwergewichtig.

## Score-Begründung
- Startwert 100
- PERF-001: -9 (hoch)
- PERF-002: -7 (mittel)
- PERF-003: -6 (mittel)
- PERF-004: -4 (niedrig)

## Findings-Tabelle
| ID | Modul | Feature | Funktion | Schweregrad | Auswirkung | Fundstelle | Quelle |
|---|---|---|---|---|---:|---|---|
| PERF-001 | Backup | Datenbankdump | Full dump | hoch | -9 | CMS\core\Services\BackupService.php:278-303 | Codefund |
| PERF-002 | Theme | Tagcloud | Homepage Sidebar | mittel | -7 | CMS\themes\cms-default\home.php:111-121 | Codefund |
| PERF-003 | System | DB Wartung/Status | Table scans | mittel | -6 | CMS\core\Services\SystemService.php:600-617 | Codefund |
| PERF-004 | Assets/Repo | Dependencies | Vendored Assets | niedrig | -4 | ASSETS, CMS\assets, CMS\vendor; 45.985 PHP-Dateien im Repo-Kontext | Heuristik |

## Umsetzungsschritte

### step-001
- **Ziel:** Backup-Performance skalierbar machen.
- **Befund:** Backup liest Tabellen und Zeilen vollständig über PHP.
- **Risiko:** Timeouts, hohe DB-Last und blockierende Admin-Aktionen.
- **Technische Ursache:** Kein Chunking/Queue-Modell im Dump-Pfad.
- **Lösungsweg:** Tabellen zeilenweise in Chunks exportieren, Kompression streamen, Fortschritt persistieren, Max-Laufzeit respektieren.
- **Betroffene Dateien:** CMS\core\Services\BackupService.php.
- **Priorität:** P1
- **Aufwand:** M
- **Abhängigkeiten:** Scheduler/CLI.

### step-002
- **Ziel:** Tagcloud ohne Vollscan erzeugen.
- **Befund:** Theme-Homepage liest alle veröffentlichten Tag-Felder und aggregiert in PHP.
- **Risiko:** Langsame Startseite bei vielen Posts.
- **Technische Ursache:** Tags sind kommasepariert gespeichert und nicht aggregierbar indiziert.
- **Lösungsweg:** Normalisierte Tag-Tabelle oder gecachte Tag-Aggregation einführen.
- **Betroffene Dateien:** CMS\themes\cms-default\home.php:111-121.
- **Priorität:** P2
- **Aufwand:** M
- **Abhängigkeiten:** Datenmodell/Cache-Invalidierung.

### step-003
- **Ziel:** Systemdiagnosen begrenzen.
- **Befund:** Systemdienste führen Tabellenchecks und Maintenance direkt aus.
- **Risiko:** Lastspitzen im Adminbereich.
- **Technische Ursache:** Operative DB-Kommandos synchron im Request-Kontext.
- **Lösungsweg:** Wartung in Jobs verschieben, Rate-Limits und Fortschrittsanzeige ergänzen.
- **Betroffene Dateien:** CMS\core\Services\SystemService.php, Admin-Systemmodule.
- **Priorität:** P2
- **Aufwand:** M
- **Abhängigkeiten:** Job-/Cron-Infrastruktur.

### step-004
- **Ziel:** Repository-/Build-Gewicht reduzieren.
- **Befund:** Viele Drittbibliotheken liegen vendored in ASSETS/CMS assets/vendor.
- **Risiko:** Langsame Scans, Releases und Deployments; schwer nachvollziehbare Dependency-Updates.
- **Technische Ursache:** Kein zentrales Composer-/NPM-Projektmanifest auf Root/CMS-Ebene gefunden.
- **Lösungsweg:** Dependency-Management konsolidieren, Release-Artefakte von Quellcode trennen.
- **Betroffene Dateien:** ASSETS\*, CMS\assets\*, CMS\vendor\*.
- **Priorität:** P3
- **Aufwand:** L
- **Abhängigkeiten:** Release-Prozess.
