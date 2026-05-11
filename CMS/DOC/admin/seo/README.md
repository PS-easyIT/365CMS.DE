# 365CMS – SEO-Center
> **Stand:** 2026-05-11 | **Version:** 2.9.749 | **Status:** Aktuell

<!-- UPDATED: 2026-05-09 -->

## Überblick

Das SEO-Center ist eine mehrteilige Suite mit spezialisierten Unterseiten.
Die Fachlogik verteilt sich auf mehrere Admin- und Service-Pfade für Dashboard, Analytics, Audit, Redirects und klassische SEO-Konfiguration.

Der Einstieg erfolgt über die SEO-Gruppe der Sidebar, in der Praxis typischerweise über `/admin/seo-dashboard`.

## Verfügbare Funktionen

| Funktion | Beschreibung |
|---|---|
| SEO-Dashboard | Übersicht mit Score, Sparkline-Trends für SEO/404/Redirects und Handlungsempfehlungen |
| SEO-Audit | Automatisierte Prüfung aller Seiten mit Score-Karten |
| Meta-Daten | Title, Description und Canonical-Tags verwalten |
| Social Media | Open-Graph- und Twitter-Card-Einstellungen mit echten globalen Frontend-Fallbacks |
| Schema / JSON-LD | Strukturierte Daten für Suchmaschinen |
| Sitemap | XML-Sitemap-Konfiguration und -Generierung |
| Technical SEO | Robots.txt, Indexierung, technische Optimierungen und lokaler Broken-Link-Report |
| Redirects | 301/302-Weiterleitungen erstellen und verwalten |
| Analytics | Traffic-Daten, Quellen und Seitenstatistiken |

## Benötigte Rechte

- Rolle **Admin** erforderlich

## Verwandte Dokumente

- [SEO.md](SEO.md)
- [ANALYTICS.md](ANALYTICS.md)
- [REDIRECTS.md](REDIRECTS.md)

## Hinweise zum Trend-Dashboard

- Das Dashboard zeigt read-only Trendkarten für **Ø SEO-Score**, **404-Pfade** und **Redirect-Regeln**.
- Die Historie wird stündlich über den bestehenden Core-Cron-Hook `cms_cron_hourly` verdichtet.
- Im Dashboard selbst gibt es **keine** neue Schreibaktion und **keine** Sicherheitstoken in URLs.
- Solange nur wenige Snapshots vorliegen, ergänzt die Ansicht fail-soft einen Live-Fallback aus vorhandenen Zeitstempeln von Inhalten, Redirects und 404-Logs.
- Der SEO-Score-Anteil der Live-Berechnung nutzt die zentral begrenzte Audit-Datenquelle mit standardmäßig 1.000 zuletzt aktualisierten Datensätzen pro Inhaltstyp, damit Dashboard und Broken-Link-Prüfung nicht durch ungebremste Volltabellenanalysen blockieren.
