# 365CMS – SEO-Center

Kurzbeschreibung: Dokumentiert die vollständige SEO-Suite mit Dashboard, Analytics, Audit, Meta-Daten, Social Media, Schema, Sitemap, technischem SEO und Redirect-Manager.

Letzte Aktualisierung: 2026-05-09 · Version 2.9.623

---

## Überblick

SEO ist in 365CMS als mehrteiliges SEO-Center mit spezialisierten Unterseiten organisiert. Die Fachlogik verteilt sich auf spezialisierte Admin-Einstiege und Module für Dashboard, Analytics, Audit, Meta, Social, Schema, Sitemap, Technical und Redirects.

| Baustein | Datei |
|---|---|
| Spezialisierte Entry Points | `CMS/admin/seo-dashboard.php`, `CMS/admin/seo-meta.php`, `CMS/admin/seo-social.php`, `CMS/admin/seo-schema.php`, `CMS/admin/seo-sitemap.php`, `CMS/admin/seo-technical.php`, `CMS/admin/seo-audit.php`, `CMS/admin/analytics.php`, `CMS/admin/redirect-manager.php` |
| Module | spezialisierte SEO-Module im Admin-/Service-Stack |
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

Der SEO-Audit prüft Seiten und Beiträge auf typische Optimierungspotenziale. Die aktuelle Implementierung arbeitet robuster mit unvollständigen Score- und Issue-Daten als ältere Dokumentationsstände.

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

- Open-Graph-Defaults für Typ und Fallback-Bild
- globale Twitter/X-Card-Defaults
- Brand-Name als globaler `og:site_name`-Fallback
- Social-Preview-Defaults im Admin bei gleichzeitiger echter Runtime-Nutzung der gespeicherten Social-Fallbacks im Frontend-Head

Der aktuelle Laufzeitvertrag ist dabei bewusst fail-soft: Wenn ein Beitrag oder eine Seite keine eigenen Social-Werte gespeichert hat, nutzt der Frontend-Head-Renderer die globalen SEO-Social-Defaults für `og:type`, `og:image`, `twitter:card` und `og:site_name`, statt auf fest codierte Werte zurückzufallen.

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

Diese Karten greifen auf die globalen SEO-Einstellungen zurück. Für die echte Frontend-Ausgabe gilt zusätzlich: globale Social-Defaults aus dem SEO-Center sind jetzt mit dem Head-Renderer verbunden und wirken als Fallback, solange kein inhaltsspezifischer Social-Meta-Wert vorhanden ist.

---

## Dokumentationshinweis

Alle älteren Verweise auf `/admin/seo.php` sind veraltet. In aktuellen Dokumenten immer die konkrete Unterseite verwenden.

---

## Verwandte Dokumente

- [ANALYTICS.md](ANALYTICS.md)
- [REDIRECTS.md](REDIRECTS.md)
- [../pages-posts/PAGES.md](../pages-posts/PAGES.md)
- [../performance/PERFORMANCE.md](../performance/PERFORMANCE.md)
