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
- **Lazy Loading:** Editor.js-/CMS-Medienausgaben respektieren `perf_lazy_loading` und setzen `loading="lazy"` nur bei aktivem Schalter.
- **WebP-Massenkonvertierung:** Geeignete Bilder in `uploads/` als WebP konvertieren und zugehörige Referenzen im CMS-Bestand nachziehen. Diese Aktion kann Originale ersetzen; vor Live-Läufen ist ein Backup Pflicht.
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
- Minify-Markierungen für Theme-/Build-Pfade

GZIP/Brotli-Kompression wird serverseitig über Apache-/Brotli-/Deflate-Konfiguration bereitgestellt; der Performance-Bereich dokumentiert und speichert die Zielstrategie, ersetzt aber keine Serverkonfiguration.

### Noch bewusst nicht automatisiert

- CSS-/JS-Minifizierung ist aktuell kein Core-Runtime-Minifier, sondern ein gespeicherter Strategie-Schalter für Theme-/Build-Prozesse.
- Die WebP-Massenkonvertierung ist eine direkte Wartungsaktion ohne Dry-Run; Backups bleiben erforderlich.
- Above-the-fold-/LCP-Bilder werden vom Core nicht automatisch erkannt; `perf_lazy_loading` wirkt aktuell pauschal auf CMS-/Editor.js-Medien. Templates sollten wichtige Hero-/LCP-Bilder bei Bedarf explizit eager ausgeben.
- Google-Fonts bleiben als kontrollierter Opt-in-Fallback erlaubt, solange keine lokalen Fonts aktiv sind. Der Font Manager kann sie lokal spiegeln.
- Externe QR-Code-Provider der gebündelten 2FA-Bibliothek werden im MFA-Setup nicht mehr aktiv abgefragt; der Setup-Flow gibt die lokale OTP-URI zurück.

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
