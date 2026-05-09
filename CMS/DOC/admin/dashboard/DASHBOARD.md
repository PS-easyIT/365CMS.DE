# Admin-Dashboard

Kurzbeschreibung: Beschreibt die aktuelle Startseite des Admin-Bereichs inklusive Kennzahlen, Schnellzugriffen, Warnhinweisen und segmentweisem Fail-Soft-Verhalten.

Letzte Aktualisierung: 2026-05-09 · Version 2.9.701

---

## Überblick

- Route: `/admin`
- Entry Point: `CMS/admin/index.php`
- Logik: `CMS/admin/modules/dashboard/DashboardModule.php`

Das Admin-Dashboard ist die zentrale Einstiegsseite für Redakteure und Administratoren. Es zeigt einen kuratierten Überblick über Systemzustand, Inhalte, Aktivität und – falls aktiv – Bestellungen. Seit `2.9.701` können Admins optionale Blöcke pro Benutzer ein- oder ausblenden; kritische Alerts und die zentrale Arbeitsübersicht bleiben dabei bewusst sichtbar.

Seit `2.9.615` wird jeder Statistikblock einzeln geladen. Fällt z. B. die Sicherheits-, Sessions- oder Orders-Datenquelle aus, bleibt die Startseite renderbar und arbeitet für den betroffenen Block mit neutralen Fallback-Werten statt mit einem Full-Page-Fatal.

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

## Benutzerbezogene Sichtbarkeit

Die Dashboard-Personalisierung speichert sichtbare Bereiche pro Admin-Benutzer in `settings` unter `admin_dashboard_preferences_user_<id>`.

Der Server akzeptiert nur bekannte Bereichsschlüssel aus `DashboardModule::DASHBOARD_SECTION_DEFINITIONS`. Eingaben werden normalisiert, Pflichtbereiche bleiben sichtbar, und die Option wird mit `autoload = 0` abgelegt.

Der Speichern-Flow:

1. POST auf `/admin` mit Action `save_dashboard_preferences`
2. CSRF-Prüfung über die gemeinsame Section-Shell (`admin_dashboard`)
3. Allowlist-Normalisierung der gewählten Bereiche
4. Persistenz in `settings`
5. Audit-Eintrag `dashboard.preferences.save`

Ausblendbar sind optionale Bereiche wie Aufmerksamkeit, Systemstatus, Sicherheit & Performance, Bestellungen und letzte Aktivitäten. Nicht ausblendbar sind kritische Alerts sowie die zentrale Arbeitsübersicht.

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

### Fallback-Warnung bei degradierten Statistikquellen

Kann ein einzelnes Dashboard-Segment nicht geladen werden, ergänzt `DashboardModule` einen zusätzlichen `warning`-Hinweis mit Deep-Link auf `/admin/cms-logs`.

Damit wird der degradierte Zustand sichtbar, ohne den übrigen Dashboard-Renderpfad zu blockieren.

---

## Begrenzungen der Seite

- Es gibt aktuell keine frei konfigurierbaren Rollen-Widgetsets für die Admin-Startseite; umgesetzt ist eine benutzerbezogene Sichtbarkeit optionaler Core-Blöcke.
- Die frühere Dokumentation zu einem separaten „Admin Dashboard Widgets“-Designer ist nicht mehr aktuell.
- Konfigurierbare Widgets betreffen heute primär das **Member Dashboard**, nicht die Admin-Startseite.
- Live-Plausibilitätsprüfungen der Kennzahlen bleiben weiterhin Aufgabe des Betriebs-/QA-Durchlaufs gegen eine reale Datenbank, nicht der statischen Doku.

---

## Verwandte Seiten

- [Member-Dashboard-Widgets](../themes-design/DASHBOARD-WIDGETS.md)
- [Analytics](../seo/ANALYTICS.md)
- [Bestellungen & Zuweisung](../subscription/ORDERS.md)
