# Admin-Dashboard

Kurzbeschreibung: Beschreibt die aktuelle Startseite des Admin-Bereichs inklusive Kennzahlen, Schnellzugriffen und Warnhinweisen.

Letzte Aktualisierung: 2026-03-28 · Version 2.8.0 RC

---

## Überblick

- Route: `/admin`
- Entry Point: `CMS/admin/index.php`
- Logik: `CMS/admin/modules/dashboard/DashboardModule.php`

Das Admin-Dashboard ist die zentrale Einstiegsseite für Redakteure und Administratoren. Es zeigt keine beliebig konfigurierbare Widget-Landschaft, sondern einen kuratierten Überblick über Systemzustand, Inhalte, Aktivität und – falls aktiv – Bestellungen.

---

## KPI-Karten

Die Haupt-KPIs werden in `DashboardModule::buildKpis()` aufgebaut.

Aktuell sind vorgesehen:

| KPI | Quelle | Link |
|---|---|---|
| Benutzer | Benutzerstatistik | `/admin/users` |
| Seiten | Seitenstatistik | `/admin/pages` |
| Medien | Medienstatistik | `/admin/media` |
| Umsatz (30T) | nur bei aktivem Abo-System | `/admin/orders` |

Die Umsatz-Kachel erscheint nur, wenn das Abo-/Bestellsystem aktiv ist.

---

## Highlight-Karten

Zusätzlich werden hervorgehobene Kennzahlen ausgegeben, unter anderem:

- neue Benutzer heute
- Entwürfe und private Seiten
- Uploads gesamt
- offene Bestellungen inkl. 30-Tage-Umsatz

Diese Informationen stammen aus `DashboardModule::getData()` und sind als kompakte Management-Sicht gedacht.

---

## Letzte Aktivitäten

Die Aktivitätsliste greift auf die Tabelle `audit_log` zu und zeigt die jüngsten Einträge chronologisch.

Dargestellt werden bis zu acht Einträge aus:

- Aktionen im Admin
- Systemprozessen
- workflow-relevanten Änderungen

---

## Schnellzugriffe

Der Bereich „Schnellzugriffe“ enthält derzeit feste Links auf:

- neue Seite
- neuer Beitrag
- Medien hochladen
- Einstellungen

Diese Links werden zentral in `DashboardModule::getQuickLinks()` definiert.

---

## Warnungen und Aufmerksamkeitspunkte

Das Dashboard zeigt zwei unterschiedliche Arten von Hinweisen:

### Alerts

Direkte Warnmeldungen aus `DashboardModule::getAlerts()`:

- Kommentare in Moderation
- erhöhte Zahl fehlgeschlagener Logins

### Attention Items

Zusätzliche Systemhinweise aus `DashboardService::getAttentionItems()`.

Diese zweite Ebene bündelt situationsabhängige Punkte, die besondere Aufmerksamkeit brauchen.

---

## Begrenzungen der Seite

- Es gibt aktuell keine frei konfigurierbaren Rollen-Widgetsets für die Admin-Startseite.
- Die frühere Dokumentation zu einem separaten „Admin Dashboard Widgets“-Designer ist nicht mehr aktuell.
- Konfigurierbare Widgets betreffen heute primär das **Member Dashboard**, nicht die Admin-Startseite.

---

## Verwandte Seiten

- [Member-Dashboard-Widgets](../themes-design/DASHBOARD-WIDGETS.md)
- [Analytics](../seo/ANALYTICS.md)
- [Bestellungen & Zuweisung](../subscription/ORDERS.md)
