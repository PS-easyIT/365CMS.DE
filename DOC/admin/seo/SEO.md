# 365CMS – SEO-Center

Kurzbeschreibung: Dokumentiert die vollständige SEO-Suite mit Dashboard, Analytics, Audit, Meta-Daten, Social Media, Schema, Sitemap, technischem SEO und Redirect-Manager.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

---

## Überblick

SEO ist in 365CMS seit 2.3.0 ein mehrteiliges SEO-Center mit neun spezialisierten Unterseiten. Die Fachlogik liegt im `SeoSuiteModule` (für Meta, Social, Schema, Sitemap, Technical) sowie in spezialisierten Modulen für Dashboard, Analytics, Audit und Redirects.

| Baustein | Datei |
|---|---|
| Shared Entry Point | `CMS/admin/seo-page.php` |
| Suite-Modul | `CMS/admin/modules/seo/SeoSuiteModule.php` |
| Dashboard-Modul | `CMS/admin/modules/seo/SeoDashboardModule.php` |
| Analytics-Modul | `CMS/admin/modules/seo/AnalyticsModule.php` |
| Redirect-Modul | `CMS/admin/modules/seo/RedirectManagerModule.php` |
| Subnav | `CMS/admin/views/seo/subnav.php` |

CSRF-Kontext: `admin_seo_suite`

---

## Routen und Unterseiten

| Route | View | Zweck |
|---|---|---|
| `/admin/seo-dashboard` | `views/seo/dashboard.php` | Gesamtüberblick, zentrale Kennzahlen und Schnellzugriffe |
| `/admin/analytics` | `views/seo/analytics.php` | Traffic, Seitenaufrufe, Tracking-Einstellungen |
| `/admin/seo-audit` | `views/seo/audit.php` | SEO-Audits, Befunde und Optimierungshinweise |
| `/admin/seo-meta` | `views/seo/meta.php` | Titel, Beschreibungen, Meta-Templates |
| `/admin/seo-social` | `views/seo/social.php` | Open Graph, Social-Media-Metadaten |
| `/admin/seo-schema` | `views/seo/schema.php` | Strukturierte Daten und Schema-Management |
| `/admin/seo-sitemap` | `views/seo/sitemap.php` | XML-Sitemaps und `robots.txt`-Einstellungen |
| `/admin/seo-technical` | `views/seo/technical.php` | Technische SEO-Aspekte und Crawling-Steuerung |
| `/admin/redirect-manager` | `views/seo/redirects.php` | 404-Logs und Weiterleitungsregeln |

---

## SEO-Dashboard

Das Dashboard dient als Einstieg und zeigt:

- SEO-Score und Health-Kennzahlen
- Zusammenfassung offener Optimierungspunkte
- Schnellzugriffe auf alle Unterseiten

---

## Analytics

Siehe [ANALYTICS.md](ANALYTICS.md) für die detaillierte Dokumentation der Tracking-Einstellungen, internen Page-View-Statistiken und Datenschutzbezüge.

---

## SEO-Audit

Der SEO-Audit prüft Seiten und Beiträge auf typische Optimierungspotenziale. Seit 2.3.1 werden fehlende Scores und Issue-Daten robuster normalisiert, sodass Notices und Warnings bei unvollständigen Datensätzen vermieden werden.

---

## Meta-Daten

Die Meta-Seite verwaltet globale und seitenspezifische SEO-Vorlagen:

- Title-Templates für verschiedene Inhaltstypen
- Meta-Description-Vorlagen
- allgemeine Meta-Keywords
- Trennzeichen und Formatierung

---

## Social Media

Verwaltung der Social-Media-Metadaten:

- Open Graph-Defaults (Typ, Bild, Titel)
- Twitter/X-Card-Einstellungen
- Facebook App ID
- Social-Preview-Defaults

---

## Strukturierte Daten

Schema-Management für maschinenlesbare Auszeichnungen:

- Organsiation/Person-Schema
- Breadcrumb-Konfiguration
- Artikel- und Seitentypen
- benutzerdefinierte Schema-Ergänzungen

---

## Sitemap & robots.txt

Steuerung der Sichtbarkeit für Suchmaschinen:

- XML-Sitemap-Regenerierung
- Bild- und News-Sitemaps
- robots.txt-Editor mit Vorlagen
- Ping an Suchmaschinen nach Sitemap-Update

---

## Technisches SEO

Erweiterte technische Steuerung:

- kanonische URL-Vorgaben
- Index/NoIndex-Defaults
- HTTP-Header-Steuerung für SEO
- Crawling-Budget-Optimierung

---

## Redirect-Manager

Verwaltung von Weiterleitungen und 404-Protokollierung:

- 404-Fehlerlog einsehen
- 301/302-Weiterleitungen anlegen
- Bulk-Operationen für häufige 404-URLs
- Umleitungsregeln pflegen

---

## SEO-Editor-Integration

In Seiten- und Beitragseditoren stehen drei SEO-Karten zur Verfügung:

- **Score-Karte**: SEO-Bewertung und Verbesserungsvorschläge
- **Readability-Karte**: Lesbarkeitsanalyse
- **Preview-Karte**: SERP- und Social-Media-Vorschau

Diese Karten greifen auf die globalen SEO-Einstellungen zurück.

---

## Dokumentationshinweis

Alle älteren Verweise auf `/admin/seo.php` sind veraltet. In aktuellen Dokumenten immer die konkrete Unterseite verwenden.

---

## Verwandte Dokumente

- [ANALYTICS.md](ANALYTICS.md)
- [REDIRECTS.md](REDIRECTS.md)
- [../pages-posts/PAGES.md](../pages-posts/PAGES.md)
- [../performance/PERFORMANCE.md](../performance/PERFORMANCE.md)
