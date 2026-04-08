# 365CMS – Performance-Center

Kurzbeschreibung: Dokumentiert das Performance-Center mit seinen sechs Unterseiten für Cache, Medien, Datenbank, Settings und Sessions.

Letzte Aktualisierung: 2026-04-07 · Version 2.9.0

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

---

## Medien-Optimierung

Die Seite `/admin/performance-media` bündelt bildspezifische Optimierungen:

- **WebP-Massenkonvertierung:** Geeignete Bilder in `uploads/` als WebP konvertieren und zugehörige Referenzen im CMS-Bestand nachziehen
- Bildgrößen-Analyse
- Thumbnail-Regenerierung

---

## Datenbank-Wartung

Unter `/admin/performance-database` stehen insbesondere zur Verfügung:

- Revisionsbereinigung
- verwaiste Datensätze entfernen
- Tabellen-Optimierung
- allgemeiner Datenbank-Cleanup

---

## Performance-Einstellungen

Die Seite `/admin/performance-settings` verwaltet globale Optimierungsschalter wie:

- Lazy-Loading-Optionen
- Minifizierungs-Einstellungen
- Query-Cache-Steuerung
- Asset-Bündelung

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
