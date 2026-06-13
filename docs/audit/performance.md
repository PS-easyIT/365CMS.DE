# Performance

**Bereichsscore:** 86/100

## Kurzfazit
Caching- und Monitoring-Module sind vorhanden. Die zuvor teure Homepage-Tagcloud wurde auf gecachte Aggregation mit Frische-Schlüssel umgestellt. Backup-Dumps schreiben inzwischen chunkweise/streamend mit Laufzeitlimit, und synchrone DB-Wartung wird im Admin-Request begrenzt. Verbleibende Haupttreiber sind ein fehlendes vollständiges Queue/Resume-Modell und das hohe Repository-/Dependency-Gewicht.

## Score-Begründung
- Startwert 100
- PERF-001: -4 (teilweise reduziert)
- PERF-002: -3 (teilweise reduziert)
- PERF-003: -3 (teilweise reduziert)
- PERF-004: -4 (niedrig)

## Findings-Tabelle
| ID | Modul | Feature | Funktion | Schweregrad | Auswirkung | Fundstelle | Quelle |
|---|---|---|---|---|---:|---|---|
| PERF-001 | Backup | Datenbankdump | Chunked/streaming Dump, Queue offen | mittel | -4 | CMS\core\Services\BackupService.php (Chunking/Runtime-Guard) | Codefund |
| PERF-002 | Theme | Tagcloud | Homepage Sidebar | niedrig | -3 | CMS\themes\cms-default\home.php (Tagcloud-Cache mit Frische-Schlüssel) | Codefund |
| PERF-003 | System | DB Wartung/Status | Inline-Wartungslimits | niedrig | -3 | CMS\core\Services\SystemService.php (Inline-Limits/Skip großer Tabellen) | Codefund |
| PERF-004 | Assets/Repo | Dependencies | Vendored Assets | niedrig | -4 | ASSETS, CMS\assets, CMS\vendor; 45.985 PHP-Dateien im Repo-Kontext | Heuristik |

## Umsetzungsschritte

### step-001
- **Ziel:** Backup-Performance skalierbar machen.
- **Befund:** 🟨 teilweise umgesetzt.
- **Risiko:** reduziert; vollständiges Queue/Resume-Modell bleibt offen.
- **Technische Ursache:** Dump-Pfad wurde bereits auf Chunks, Writer-Streaming und Laufzeitlimit gehärtet.
- **Lösungsweg:** Vorhanden: `DATABASE_DUMP_SELECT_CHUNK_SIZE`, `DATABASE_DUMP_MAX_RUNTIME_SECONDS`, `writeDatabaseDumpToWriter()` und streamendes Schreiben. Offen: persistierter Fortschritt/Resume über Scheduler/CLI.
- **Betroffene Dateien:** CMS\core\Services\BackupService.php.
- **Priorität:** P1
- **Aufwand:** M
- **Abhängigkeiten:** Scheduler/CLI.

### step-002
- **Ziel:** Tagcloud ohne Vollscan erzeugen.
- **Befund:** ✅ weitgehend umgesetzt.
- **Risiko:** deutlich reduziert.
- **Technische Ursache:** durch Cache-Layer entschärft.
- **Lösungsweg:** Gecachte Tag-Aggregation in `home.php` umgesetzt, inkl. Frische-Schlüssel aus `COUNT(*)` + `MAX(updated_at/published_at/created_at)` und TTL.
- **Betroffene Dateien:** CMS\themes\cms-default\home.php:111-121.
- **Priorität:** P2
- **Aufwand:** M
- **Abhängigkeiten:** Datenmodell/Cache-Invalidierung.

## Umsetzungsstand (2026-06-13)
- 🟨 **PERF-001** teilweise reduziert (Chunking, Writer-Streaming und Laufzeit-Guard vorhanden; vollständiges Queue/Resume-Modell offen)
- ✅ **PERF-002** umgesetzt (Tagcloud-Caching mit Frische-Schlüssel in Theme-Homepage)
- 🟨 **PERF-003** teilweise reduziert (Inline-Tabellenlimit und Skip großer Tabellen aktiv; Job-Auslagerung offen)
- 🟨 **PERF-004** offen (Dependency-/Repo-Gewicht unverändert hoch)

### step-003
- **Ziel:** Systemdiagnosen begrenzen.
- **Befund:** 🟨 teilweise umgesetzt.
- **Risiko:** reduziert; große/zu viele Tabellen werden nicht mehr blind inline gewartet.
- **Technische Ursache:** durch Inline-Limits entschärft, vollständige Job-Auslagerung noch offen.
- **Lösungsweg:** `SystemService` begrenzt `REPAIR/OPTIMIZE` auf maximal 10 Tabellen pro Request und überspringt Tabellen über 100.000 geschätzten Zeilen oder 256 MiB mit Job-/CLI-Hinweis.
- **Betroffene Dateien:** CMS\core\Services\SystemService.php, Admin-Systemmodule.
- **Priorität:** P2
- **Aufwand:** M
- **Abhängigkeiten:** Job-/Cron-Infrastruktur.

## Update 2026-06-13
- `CMS\core\Services\SystemService.php` begrenzt synchrone DB-Wartung im Admin-Request über `MAX_INLINE_MAINTENANCE_TABLES`, `MAX_INLINE_MAINTENANCE_ROWS` und `MAX_INLINE_MAINTENANCE_BYTES`.
- `REPAIR TABLE`/`OPTIMIZE TABLE` überspringen große Tabellen fail-soft mit Job-/CLI-Hinweis statt Lastspitzen zu erzeugen.
- Backup-Befund wurde mit dem aktuellen Code abgeglichen: Chunking, Writer-Streaming und Runtime-Guard sind vorhanden; Queue/Resume bleibt Restarbeit.

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
