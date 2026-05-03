# 365CMS – Performance-Center

Kurzbeschreibung: Dokumentiert das Performance-Center mit seinen sechs Unterseiten für Cache, Medien, Datenbank, Settings und Sessions.

Letzte Aktualisierung: 2026-05-03 · Version 2.9.248

---

## Überblick

Performance ist ein eigenständiger Hauptbereich mit sechs Unterseiten. Die Navigation läuft über eigene Admin-Slugs und eine gemeinsame Subnavigation unter `CMS/admin/views/performance/subnav.php`; Aktionen verwenden weiterhin den CSRF-Kontext `admin_performance`.

| Baustein | Datei |
|---|---|
| Admin-Slugs | `performance`, `performance-cache`, `performance-media`, `performance-database`, `performance-settings`, `performance-sessions` |
| Routing | `CMS/core/Routing/AdminRouter.php` |
| Subnav | `CMS/admin/views/performance/subnav.php` |
| Views | `CMS/admin/views/performance/*.php` |

---

## Routen und Unterseiten

| Route | View | Zweck |
|---|---|---|
| `/admin/performance` | `views/performance/performance.php` | Gesamtübersicht mit Health-Score und KPIs |
| `/admin/performance-cache` | `views/performance/cache.php` | Cache-Statistiken, Bereinigung und Invalidierung |
| `/admin/performance-media` | `views/performance/media.php` | WebP-Konvertierung, Bildoptimierung und Größenanalyse |
| `/admin/performance-database` | `views/performance/database.php` | Revisionen bereinigen, Tabellen-Cleanup, Wartung |
| `/admin/performance-settings` | `views/performance/settings.php` | Globale Laufzeitoptionen und Optimierungsschalter |
| `/admin/performance-sessions` | `views/performance/sessions.php` | Session-Übersicht, Bereinigung abgelaufener Sessions |

---

## Cache-Verwaltung

Die Seite `/admin/performance-cache` steuert die CMS-internen Caches:

- Datei-Cache leeren
- Objekt-Cache-Status prüfen
- Cache-Statistiken einsehen
- selektive Invalidierung bei Bedarf

Die globalen Cache-Schalter wirken auf die Runtime-Header:

- Ist der HTML-/Seiten-Cache deaktiviert, sendet der öffentliche Router `public, no-cache, must-revalidate, max-age=0` statt weiterhin eine kurze positive TTL zu setzen.
- Ist Browser-Caching für Medien deaktiviert, verwendet die Medienauslieferung denselben Revalidierungsmodus statt fünf Minuten Public-Cache und liefert weiterhin `ETag`/`Last-Modified`, damit Browser nach HTTP-Best-Practice validieren statt blind erneut übertragen.
- Admin-, Member-, API- und Auth-Flows bleiben privat bzw. `no-store`.
- Bei Seiten- und Beitragsänderungen leert der Schalter „Cache bei Inhaltsänderungen automatisch leeren“ den CMS-Datei-/APCu-Cache, ohne OPcache oder Server-Caches unnötig zurückzusetzen.
- Die globale Einstellungsseite speichert nur ihre eigenen Felder; Medien-spezifische WebP-/EXIF-Schalter werden nicht mehr versehentlich beim Speichern der globalen Seite überschrieben.

---

## Medien-Optimierung

Die Seite `/admin/performance-media` bündelt bildspezifische Optimierungen:

- **Upload-WebP:** Der Performance-Schalter synchronisiert auf die echte Medienoption `auto_webp`; neue Uploads erzeugen bei unterstützten Formaten WebP-Begleitdateien.
- **EXIF-Entfernung:** Der Performance-Schalter synchronisiert auf `strip_exif`; neue JPG-/PNG-Uploads werden nur bei aktivem Schalter sauber per GD re-encodiert.
- **Lazy Loading:** Editor.js-/CMS-Medienausgaben respektieren `perf_lazy_loading`; die ersten konfigurierbaren Bilder bleiben eager/high-priority, damit Hero-/LCP-Medien nicht versehentlich lazy geladen werden.
- **WebP-Massenkonvertierung:** Geeignete Bilder in `uploads/` werden batchweise verarbeitet. Dry-Run, Batch-Limit, optionales Original-Ersetzen, Backup-Manifest und Rollback der letzten Konvertierung sind integriert.
- Bildgrößen-Analyse
- Thumbnail-Regenerierung

---

## Datenbank-Wartung

Unter `/admin/performance-database` stehen insbesondere zur Verfügung:

- Revisionsbereinigung
- verwaiste Datensätze entfernen
- Tabellen-Optimierung
- allgemeiner Datenbank-Cleanup

Hinweis: `OPTIMIZE TABLE` und Reparaturläufe arbeiten direkt auf den CMS-Tabellen. Je nach Storage Engine können Tabellen dabei gesperrt oder neu aufgebaut werden; produktive Läufe sollten daher außerhalb der Stoßzeiten und nach einem Backup erfolgen.

Die Tabellenwartung wird dynamisch aus den aktuellen CMS-Tabellen ermittelt statt aus einer festen Alt-Liste. `OPTIMIZE TABLE` läuft nur für unterstützte Engines wie InnoDB/MyISAM/ARCHIVE; `REPAIR TABLE` läuft bewusst nur für MyISAM/ARCHIVE/CSV und überspringt InnoDB, weil MySQL `REPAIR TABLE` dort nicht als normale Wartungsfunktion vorsieht.

---

## Performance-Einstellungen

Die Seite `/admin/performance-settings` verwaltet globale Optimierungsschalter wie:

- Lazy-Loading-Optionen für CMS-/Editor.js-Medien
- HTML- und Medien-Cache-Header samt TTLs
- automatische CMS-Cache-Invalidierung bei Content-Änderungen
- Session-Timeouts für Admin- und Member-Kontext
- lokale CSS-/JS-Minifizierung mit Cache-Dateien für unterstützte Theme-Assets

GZIP/Brotli-Kompression wird serverseitig über Apache-/Brotli-/Deflate-Konfiguration bereitgestellt; der Performance-Bereich zeigt den erkannten Status an, ersetzt aber keine Serverkonfiguration. Externe CDN-/Reverse-Proxy-Integrationen können Cache-Löschungen über die Hooks `performance_cache_purged` und `performance_cdn_purge_requested` anbinden.

### Bewusst verbleibende Grenzen

- CSS-/JS-Minifizierung arbeitet lokal für angebundene Assets und fällt bei Problemen auf Originaldateien zurück; komplexe Bundle-Optimierung, Tree-Shaking oder Quellkarten bleiben Aufgabe eines Build-Prozesses.
- WebP-Rollback bezieht sich auf die letzte manifestierte Performance-Konvertierung; vollständige Server-Backups bleiben für produktive Medienläufe weiterhin Best Practice.
- Above-the-fold-/LCP-Erkennung erfolgt heuristisch über die ersten CMS-/Editor.js-Bilder. Templates sollten besondere Hero-Bilder zusätzlich explizit mit passenden Attributen ausgeben.
- Google-Fonts bleiben als kontrollierter Opt-in-Fallback erlaubt, solange keine lokalen Fonts aktiv sind. Der Font Manager kann sie lokal spiegeln.
- Externe QR-Code-Provider der gebündelten 2FA-Bibliothek wurden entfernt; der Setup-Flow arbeitet mit lokaler OTP-URI bzw. lokalen QR-Providern.

---

## Session-Verwaltung

Unter `/admin/performance-sessions` werden aktive und abgelaufene Sessions verwaltet:

- Übersicht aktiver Sessions
- Bereinigung abgelaufener Einträge
- Session-Statistiken

---

## Sicherheit

Alle Performance-Seiten folgen dem Admin-Standardmuster:

- Zugriff nur für Administratoren
- CSRF-Prüfung via `Security::instance()->verifyToken(..., 'admin_performance')`
- POST-Ergebnis als Session-Alert, Redirect auf GET-Route
- Audit-Logging für sicherheitsrelevante Aktionen

---

## Dokumentationshinweis

Ältere Dokumentation mit nur einer Route `/admin/performance.php`, einem separaten `performance-page.php` oder `views/seo/performance.php` ist veraltet. Für aktuelle Arbeit immer die jeweilige Unterseite bzw. den zugehörigen Admin-Slug benennen.

---

## Verwandte Dokumente

- [../media/MEDIA.md](../media/MEDIA.md)
- [../system-settings/README.md](../system-settings/README.md)
- [../seo/SEO.md](../seo/SEO.md)
