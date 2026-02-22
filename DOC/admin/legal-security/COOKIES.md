# Cookie-Manager (DSGVO)

**Datei:** `admin/cookies.php`

---

## Übersicht

Der Cookie-Manager ermöglicht DSGVO-konforme Verwaltung aller eingesetzten Cookies und Tracking-Dienste. Er kategorisiert Cookies, verwaltet Benutzereinwilligungen und generiert automatisch eine Cookie-Richtlinie.

---

## Cookie-Kategorien

| Kategorie | Beschreibung | Einwilligung |
|-----------|--------------|--------------|
| **Notwendig** | Technisch erforderlich (Session, CSRF) | Immer aktiv |
| **Funktional** | Komfort-Cookies (gespeicherte Einstellungen) | Opt-in |
| **Analytik** | Besucher-Tracking, Statistiken | Opt-in |
| **Marketing** | Werbung, Retargeting, Social Media | Opt-in |

---

## Vorgefertigte Dienste-Bibliothek

Der Cookie-Manager enthält eine vorkonfigurierte Bibliothek häufig genutzter Dienste:

### Analytik-Dienste
- **Google Analytics 4** – Cookies: `_ga`, `_gid`, `_gat`
- **Matomo** – Cookies: `_pk_id`, `_pk_ses`, `_pk_ref`
- **Hotjar** – Cookies: `_hjid`, `_hjSessionUser`

### Marketing-Dienste
- **Meta Pixel (Facebook)** – Cookies: `_fbp`, `_fbc`, `datr`, `fr`
- **LinkedIn Insight Tag** – Cookies: `li_gc`, `bcookie`, `UserMatchHistory`
- **Google Ads / DoubleClick** – Cookies: `IDE`, `DSID`, `__gads`
- **X (Twitter) Pixel** – Cookies: `_twitter_sess`, `personalization_id`
- **Pinterest Tag** – Cookies: `_pinterest_sess`, `_pinterest_ct_ua`

---

## Cookie-Definition erstellen

Über das Formular neue Cookies erfassen:

| Feld | Beschreibung |
|------|--------------|
| **Name** | Anzeigename des Dienstes |
| **Cookie-Name(n)** | Technische Cookie-Bezeichnung(en) |
| **Kategorie** | Einordnung (notwendig/funktional/analytik/marketing) |
| **Anbieter** | Unternehmen (z.B. "Google LLC") |
| **Laufzeit** | Ablaufzeit (z.B. "2 Jahre", "Session") |
| **Beschreibung** | Erklärung des Zwecks |
| **Datenschutz-URL** | Link zur Datenschutzerklärung des Anbieters |

---

## Einwilligungs-Management

### Cookie-Banner Konfiguration
- **Banner-Position:** Unten / Oben / Modal
- **Granularität:** Kategorie-basiert oder Gesamt-Opt-in
- **Sprache:** Deutsch (Standard), English, weitere
- **Design:** Anpassbar an Theme-Farben

### Einwilligungs-Speicherung
- Einwilligungen in `cms_cookie_consents` gespeichert
- Per IP-Hash und Fingerprint identifiziert
- Ablaufdatum: 12 Monate (DSGVO-konform)
- Audit-Trail mit Zeitstempel, IP, gewählten Kategorien

---

## Cookie-Richtlinie generieren

**Admin → DSGVO → Cookie-Richtlinie generieren**

Automatisch generierter Text enthält:
- Liste aller definierten Cookies mit Beschreibungen
- Kategorisierung und Rechtsgrundlage (Art. 6 DSGVO)
- Opt-out Hinweise und Links
- Kontaktdaten des Datenschutzbeauftragten

---

## Sicherheitsprüfungen

Der Cookie-Manager prüft automatisch:
- ✅ Ob alle Tracking-Skripte vor Einwilligung blockiert werden
- ✅ Ob Consent-Cookies selbst notwendig und cookie-frei sind
- ✅ Ob die Cookie-Laufzeiten korrekt konfiguriert sind
- ⚠️ Fehlende Datenschutz-URLs
- ⚠️ Marketing-Cookies ohne Opt-in-Pflicht

---

## Verwandte Seiten

- [DSGVO-Datenzugriff & Löschung](DSGVO.md)
- [Rechtstexte & Legal-Sites](LEGAL.md)
- [AntiSpam](ANTISPAM.md)
