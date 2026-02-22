# Analytics & Besucherstatistiken

**Datei:** `admin/analytics.php`

---

## Übersicht

Das Analytics-Dashboard zeigt Besucherstatistiken, Traffic-Quellen und Inhalts-Performance ohne externe Tracking-Dienste. Alle Daten werden lokal in der CMS-Datenbank gespeichert.

---

## Dashboard-Übersicht

### Zeitraum-Filter
- Heute
- Letzte 7 Tage
- Letzte 30 Tage
- Dieser Monat
- Benutzerdefinierter Zeitraum

### Haupt-KPIs (Stat-Cards)

| KPI | Beschreibung |
|-----|--------------|
| **Seitenaufrufe** | Gesamte Page-Views im Zeitraum |
| **Unique Visitors** | Eindeutige Besucher (per IP-Hash) |
| **Ø Sitzungsdauer** | Durchschnittliche Besuchszeit |
| **Bounce Rate** | Absprungrate |

---

## Traffic-Diagramm

Zeitreihen-Chart (Liniendiagramm) zeigt:
- Seitenaufrufe pro Tag
- Unique Visitors pro Tag
- Vergleich zum Vorperiod

---

## Top-Seiten

Tabelle der meistbesuchten Seiten:

| Spalte | Beschreibung |
|--------|--------------|
| **Seite** | URL/Titel der Seite |
| **Aufrufe** | Absolute Seitenaufrufe |
| **Unique Views** | Eindeutige Aufrufe |
| **Ø Zeit** | Durchschnittliche Verweildauer |
| **Absprünge** | Absprungrate der Seite |

---

## Referrer-Analyse

| Kanal | Beschreibung |
|-------|--------------|
| **Direkt** | Direkte URL-Eingabe oder Lesezeichen |
| **Organisch** | Suchmaschinen (Google, Bing, etc.) |
| **Social** | Facebook, LinkedIn, Twitter/X, etc. |
| **Referral** | Externe Links auf anderen Websites |
| **E-Mail** | Klicks aus E-Mail-Kampagnen |

---

## Geräte & Browser

### Gerätetypen
- Desktop
- Tablet
- Mobile

### Browser-Verteilung
- Chrome, Firefox, Safari, Edge, Other

### Betriebssysteme
- Windows, macOS, Linux, iOS, Android

---

## System-Health Widget

Das Analytics-Dashboard enthält auch einen System-Health-Bereich:

| Check | Status |
|-------|--------|
| PHP-Version | ✅/⚠️ |
| MySQL-Version | ✅/⚠️ |
| Freier Speicher | ✅/⚠️ |
| Cache-Hit-Rate | ✅/⚠️ |
| Aktive Sessions | Anzahl |
| Fehlerrate (24h) | Anzahl |

---

## Cache-Statistiken

| Metrik | Beschreibung |
|--------|--------------|
| **Cache-Einträge** | Anzahl gecachter Queries |
| **Cache-Größe** | Gespeicherter Overhead |
| **Hit-Rate** | Anteil treffsicherer Cache-Anfragen |
| **Miss-Rate** | Anteil nicht gecachter Anfragen |

---

## Datenschutz & DSGVO

- **IP-Adressen** werden nur als Hash gespeichert (keine Rückverfolgbarkeit)
- **Keine Cookies** für Analytics notwendig (erster Schritt zu Cookie-freiem Tracking)
- **Daten-Retention:** Konfigurierbar (Standard: 12 Monate)
- **Opt-out:** Benutzer können im Member-Bereich Analytics ablehnen

---

## Datenbank

Pageviews werden in `cms_analytics` (oder `cms_activity_log`) gespeichert:

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | INT | Primärschlüssel |
| `page_url` | VARCHAR | Aufgerufene URL |
| `ip_hash` | VARCHAR | Hash der IP-Adresse |
| `user_agent` | VARCHAR | Browser/Gerät |
| `referrer` | VARCHAR | Herkunfts-URL |
| `session_id` | VARCHAR | Session-Hash |
| `created_at` | TIMESTAMP | Zeitstempel |

---

## Daten exportieren

Analytics-Daten exportierbar als:
- **CSV** – Für Excel/Spreadsheets
- **JSON** – Für weitere Verarbeitung
- **PDF** – Für Reports

---

## Verwandte Seiten

- [SEO-Einstellungen](SEO.md)
- [Performance-Tools](../system-settings/PERFORMANCE.md)
- [System & Diagnose](../system-settings/README.md)
