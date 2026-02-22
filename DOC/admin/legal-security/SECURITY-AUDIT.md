# Security Audit & Härtungsassistent

**Datei:** `admin/security-audit.php`

---

## Übersicht

Der Security Audit führt automatisierte Sicherheitsprüfungen durch und bewertet das CMS anhand von 7 Prüfkategorien mit einem Score von 0–100. Basierend auf den Ergebnissen werden konkrete Härtungsmaßnahmen empfohlen.

---

## Score-Berechnung

| Bereich | Max. Punkte | Gewichtung |
|---------|-------------|------------|
| Zugriffskontrolle | 20 | Hoch |
| Passwort-Sicherheit | 15 | Hoch |
| HTTPS / SSL | 15 | Hoch |
| Dateisystem-Berechtigungen | 15 | Mittel |
| PHP-Konfiguration | 15 | Mittel |
| Datenbank-Sicherheit | 10 | Mittel |
| Header-Sicherheit | 10 | Niedrig |
| **Gesamt** | **100** | |

### Score-Bewertung

| Score | Farbe | Bewertung |
|-------|-------|-----------|
| 90–100 | 🟢 Grün | Sehr sicher |
| 75–89 | 🟡 Gelb-Grün | Gut |
| 50–74 | 🟠 Orange | Verbesserungsbedarf |
| < 50 | 🔴 Rot | Kritische Risiken |

---

## Prüfkategorien im Detail

### 1. Zugriffskontrolle (20 Punkte)

| Check | Punkte | Beschreibung |
|-------|--------|--------------|
| Admin-Login MFA aktiv | 5 | Zwei-Faktor-Authentifizierung |
| Starkes Passwort-Policy | 5 | Min. 12 Zeichen, Sonderzeichen |
| Login-Rate-Limiting aktiv | 5 | Max. 5 Fehlversuche → Sperrung |
| Session-Timeout konfiguriert | 3 | Max. 8h Inaktivität |
| Admin-URL nicht standard | 2 | Nicht `/admin/` als URL |

### 2. HTTPS / SSL (15 Punkte)

| Check | Punkte |
|-------|--------|
| HTTPS aktiv (alle Seiten) | 5 |
| SSL-Zertifikat gültig (> 14 Tage) | 5 |
| HSTS-Header gesetzt | 3 |
| HTTP→HTTPS Redirect aktiv | 2 |

### 3. PHP-Konfiguration (15 Punkte)

| Check | Empfehlung |
|-------|-----------|
| `display_errors` | Off |
| `expose_php` | Off |
| `allow_url_fopen` | Off |
| `allow_url_include` | Off |
| PHP-Version | ≥ 8.2 |

### 4. Dateisystem-Berechtigungen (15 Punkte)

| Pfad | Empfohlene Rechte |
|------|------------------|
| `config.php` | 600 |
| `CMS/uploads/` | 755 |
| `CMS/cache/` | 755 |
| `CMS/logs/` | 700 |
| `.htaccess` | 644 |

### 5. HTTP Security Headers (10 Punkte)

| Header | Wert |
|--------|------|
| `X-Frame-Options` | DENY |
| `X-Content-Type-Options` | nosniff |
| `X-XSS-Protection` | 1; mode=block |
| `Referrer-Policy` | strict-origin-when-cross-origin |
| `Content-Security-Policy` | directives... |
| `Permissions-Policy` | geolocation=(), camera=() |

### 6. Datenbank-Sicherheit (10 Punkte)

| Check | Beschreibung |
|-------|--------------|
| DB-Passwort-Stärke | Mindestlänge und Komplexität |
| Tabellen-Präfix | Nicht Standard `cms_` → custom prefix |
| DB-Benutzer-Berechtigungen | Minimal-Rechte (kein SUPER, FILE) |
| Prepared Statements | Alle Queries parameterisiert |

---

## Härtungsassistent

Nach dem Audit werden konkrete Empfehlungen angezeigt:

### Kritische Maßnahmen (🔴)
- Direkt beheben – Sicherheitsrisiko
- Ein-Klick-Fix wo möglich (z.B. HTTP-Header automatisch setzen)

### Wichtige Maßnahmen (🟠)
- Innerhalb 7 Tage angehen
- Schritt-für-Schritt-Anleitung verfügbar

### Empfehlungen (🟡)
- Best Practices
- Optionale Härtungsmaßnahmen

---

## Audit-History

| Spalte | Beschreibung |
|--------|--------------|
| Datum | Zeitpunkt des Audits |
| Score | Erreichter Gesamt-Score |
| Kritische Findings | Anzahl kritischer Probleme |
| Durchgeführt von | Admin-Name oder "System (Auto)" |

Automatische Audits können täglich geplant werden:
- Einstellungen → Cron-Jobs → Security-Audit-Frequenz

---

## Export

- **PDF-Report** – Vollständiger Bericht für Dokumentation
- **JSON** – Maschinenlesbares Format für externe Tools

---

## Verwandte Seiten

- [Firewall & Sicherheit](FIREWALL.md)
- [AntiSpam](ANTISPAM.md)
- [DSGVO](DSGVO.md)
- [Einstellungen](../system-settings/README.md)
