# 365CMS – Performance-Center

Kurzbeschreibung: Dokumentiert das Performance-Center mit seinen sechs Unterseiten für Cache, Medien, Datenbank, Settings und Sessions.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

---

## Überblick

Performance ist seit 2.2.0 ein eigenständiger Hauptbereich mit sechs Unterseiten. Alle teilen sich das `PerformanceModule` und den CSRF-Kontext `admin_performance`.

| Baustein | Datei |
|---|---|
| Shared Entry Point | `CMS/admin/performance-page.php` |
| Modul | `CMS/admin/modules/seo/PerformanceModule.php` |
| Subnav | `CMS/admin/views/performance/subnav.php` |

---

## Routen und Unterseiten

| Route | View | Zweck |
|---|---|---|
| `/admin/performance` | `views/seo/performance.php` | Gesamtübersicht mit Health-Score und KPIs |
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

- **WebP-Massenkonvertierung** (seit 2.3.1): Geeignete Bilder in `uploads/` als WebP konvertieren und Referenzen in Medien-, Seiten-, Beitrags- und SEO-Daten automatisch aktualisieren
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

Ältere Dokumentation mit nur einer Route `/admin/performance.php` ist unvollständig. Für aktuelle Arbeit immer die jeweilige Unterseite benennen.

---

## Verwandte Dokumente

- [../media/MEDIA.md](../media/MEDIA.md)
- [../system-settings/README.md](../system-settings/README.md)
- [../seo/SEO.md](../seo/SEO.md)
