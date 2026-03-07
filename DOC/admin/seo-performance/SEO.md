# 365CMS – SEO-Center

Kurzbeschreibung: Überblick über die in 2.3.1 auf mehrere Unterseiten aufgeteilte SEO-Verwaltung.

Letzte Aktualisierung: 2026-03-07

---

## Überblick

SEO ist in 365CMS nicht mehr eine einzelne Seite, sondern ein mehrteiliges SEO-Center mit klar getrennten Verantwortlichkeiten.

| Route | Zweck |
|---|---|
| `/admin/seo-dashboard` | Gesamtüberblick, zentrale Kennzahlen und Schnellzugriffe |
| `/admin/analytics` | Traffic- und Seitenaufrufdaten |
| `/admin/seo-audit` | Audits, Befunde und Optimierungshinweise |
| `/admin/seo-meta` | Titel, Beschreibungen, Meta-Templates |
| `/admin/seo-social` | Open Graph, Social-Media-Metadaten |
| `/admin/seo-schema` | strukturierte Daten und Schema-Management |
| `/admin/seo-sitemap` | Sitemap- und `robots.txt`-Einstellungen |
| `/admin/seo-technical` | technische SEO-Aspekte |
| `/admin/redirect-manager` | 404-Logs und Weiterleitungen |

---

## Fachliche Aufteilung

Das Dashboard dient als Einstieg und verlinkt gezielt in die Spezialseiten. Meta-Daten, Social Media, strukturierte Daten, Sitemaps, technisches SEO und Redirects werden getrennt gepflegt.

---

## Wichtige technische Bezüge

- SEO-Unterseiten teilen sich eine gemeinsame Subnavigation
- das System arbeitet mit spezialisierten Entry-Points statt einem Monolithen
- Audit-Daten werden im aktuellen Arbeitsstand robuster normalisiert dargestellt

---

## Dokumentationshinweis

Alle älteren Verweise auf `/admin/seo.php` sind veraltet. In aktuellen Dokumenten soll immer die konkrete Unterseite genannt werden.
