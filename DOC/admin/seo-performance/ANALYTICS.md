# Analytics

Kurzbeschreibung: Dokumentiert die SEO-Analytics-Seite mit internem Tracking, Referrer-Auswertung und Tracking-Einstellungen.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

---

## Route und technische Einordnung

- Route: `/admin/analytics`
- Entry Point: `CMS/admin/analytics.php`
- View: `CMS/admin/views/seo/analytics.php`
- Einordnung in der Navigation: Bereich **SEO**

Die Seite gehört funktional zur SEO-Suite und nicht zu einem eigenständigen, vom restlichen System getrennten Statistik-Modul.

---

## Was die Seite aktuell zeigt

Die View rendert aktuell folgende Bereiche:

### KPI-Karten

- Seitenaufrufe 30 Tage
- Unique Visitors
- Absprungrate
- durchschnittliche Sessiondauer

### Verlauf und Top-Seiten

- Tageswerte für Views über 30 Tage
- Top-Seiten nach Aufrufen

### Technische Zusatzwerte

- Core Web Vitals (Schätzung)
- Backlink-/Referrer-Monitoring
- Internal-Linking-Ideen

### Tracking-Einstellungen

Pflegbare Felder:

- Google Search Console Property
- GA4 ID
- GTM ID
- Matomo URL
- Matomo Site ID
- Meta Pixel ID
- Admins ausschließen
- Do Not Track respektieren
- IP anonymisieren

---

## Internes Page-View-Tracking

Wenn die zugrunde liegende Tracking-Tabelle fehlt, zeigt die Oberfläche einen Hinweis, dass das interne Page-View-Tracking noch nicht aktiv ist.

Die Seite arbeitet also mit zwei Zuständen:

1. **Tracking-Daten vorhanden** → Statistiken und Trends werden ausgegeben
2. **Tracking-Daten fehlen** → Info-Hinweis mit weiterhin erreichbarer Einstellungsoberfläche

---

## Datenschutzbezug

Die Seite ist eng mit dem Datenschutz- und Cookie-Management verknüpft. Relevante Einstellungen wie:

- DNT-Respektierung
- IP-Anonymisierung
- Aktivierung externer Tracking-Dienste

werden auch vom Cookie- und Legal-Kontext ausgewertet.

Das ist wichtig, weil Analytics in 365CMS nicht isoliert betrachtet wird, sondern als Teil des Consent- und Compliance-Flows.

---

## Was diese Seite nicht ist

- kein vollständiges BI- oder Marketing-Attributions-Tool
- keine externe SaaS-Reporting-Konsole
- keine reine Datenbankansicht historischer Rohdaten

Die Seite ist eine kompakte operative Steuerzentrale für internes Tracking und angebundene SEO-/Marketing-IDs.

---

## Verwandte Seiten

- [SEO](SEO.md)
- [Cookies](../legal-security/COOKIES.md)
- [Performance](../system-settings/PERFORMANCE.md)
