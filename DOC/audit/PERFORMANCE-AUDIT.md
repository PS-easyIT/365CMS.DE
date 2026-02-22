# PERFORMANCE AUDIT – 365CMS (`/CMS`)

## Scope
- Verzeichnis: `CMS/`
- Stichproben: `core/Database.php`, `core/CacheManager.php`, `core/Bootstrap.php`, `admin/performance.php`, `admin/index.php`
- Fokus: Laufzeit, Datenbank, Caching, I/O, Skalierung im Admin- und Core-Betrieb

## Positiv bewertet
- **Prepared Statements & PDO-Layer** in `CMS/core/Database.php` reduzieren Query-Risiken und vereinheitlichen DB-Zugriffe.
- **Mehrstufiges Caching-Konzept** in `CMS/core/CacheManager.php` (Datei-Cache, OPcache/APCu/LiteSpeed-Handling).
- **Schema-Flag-Mechanismus** (`cache/db_schema_v3.flag`) verhindert vollständige Schema-Checks auf jedem Request.
- **Performance-Adminseite** (`CMS/admin/performance.php`) bietet operative Schalter für Lazy Loading, Minify, Defer, Browser-Cache.

## Kritische / relevante Findings
1. **Auto-Setup im `Database`-Konstruktor**
   - `createTables()`/Migrationen werden beim Instanziieren initial geprüft.
   - Risiko: unnötiger I/O auf stark frequentierten Setups, wenn Flag fehlt oder gelöscht wurde.

2. **Datei-Cache mit `serialize()`/`unserialize()`**
   - Performance ok für kleine Datenmengen, aber nicht optimal für große Volumina.
   - Kein dedizierter Namespace für Cache-Key-Typen oder versionierte Keys.

3. **N+1 Settings-Zugriffe auf Admin-Performance-Seite**
   - In `CMS/admin/performance.php` werden Settings pro Key einzeln gelesen/geschrieben.
   - Risiko: unnötige DB-Last bei wachsender Einstellungszahl.

4. **Inline-Styles in Admin-Oberflächen**
   - Sichtbar u. a. in `admin/index.php` und `admin/performance.php`.
   - Primär ein Maintainability-Thema; beeinflusst aber Render-/Cache-Strategien indirekt.

## Empfehlungen (priorisiert)
### P1 – kurzfristig
- Settings-Bulk-Load einführen (`WHERE option_name IN (...)`) statt Einzelabfragen.
- Für Cache-Objekte JSON-basierte, typisierte Speicherung prüfen (wo möglich).
- Metriken für Cache-Hit/Miss, Query-Dauer, Admin-Page-Load erfassen und im Dashboard anzeigen.

### P2 – mittelfristig
- DB-Schema-Checks strikt auf Install/Upgrade-Phasen begrenzen.
- Cache-Key-Strategie versionieren (`feature:entity:id:vN`) und dokumentieren.
- Kritische Admin-Seiten auf zentrale CSS-Klassen umstellen (weniger Inline-Stile, bessere Wiederverwendung).

### P3 – langfristig
- Optionalen Object-Cache-Adapter (Redis/Memcached) als Backend neben File-Cache anbieten.
- Performance-Budget für Kernseiten definieren (TTFB, SQL-Queries, Memory Peak).

## Ergebnis (Ampel)
- **Gesamtstatus:** �� **Solide Basis, aber Optimierungspotenzial bei Skalierung**
- **Betriebsreife:** Gut für kleine/mittlere Instanzen, mit klaren Hebeln für Enterprise-Lasten.
