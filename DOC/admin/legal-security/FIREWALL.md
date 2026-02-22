# CMS-Firewall & Sicherheit

**Datei:** `admin/cms-firewall.php`

---

## Übersicht

Die CMS-Firewall bietet mehrschichtigen Schutz gegen Angriffe, Bots und unerwünschten Traffic. Sie kombiniert IP-Blocking, Request-Filterung, User-Agent-Analyse und Rate-Limiting zu einem integrierten Sicherheitssystem.

---

## Firewall-Regeln

### IP-Management

#### IP-Blacklist (manuell)
Einzelne IPs oder IP-Bereiche manuell sperren:
```
203.0.113.5              # Einzelne IP
192.168.1.0/24           # IP-Bereich (CIDR)
```

#### IP-Whitelist
Vertrauenswürdige IPs von allen Checks ausnehmen:
- Eigene Server-IPs
- Office-Netzwerk
- Monitoring-Dienste

#### Automatische IP-Sperre
Nach konfigurierbarer Anzahl fehlgeschlagener Login-Versuche wird die IP automatisch gesperrt. Konfiguration:
- **Schwellwert:** Standard 5 Versuche
- **Zeitraum:** Standard 15 Minuten
- **Sperrdauer:** Standard 60 Minuten (oder permanent)

### Request-Filterung

| Regel | Beschreibung |
|-------|--------------|
| **SQL-Injection** | Erkennt typische SQL-Injection-Muster in GET/POST |
| **XSS-Vektoren** | Blockiert Script-Injection-Versuche |
| **Path Traversal** | Verhindert `../` Directory-Traversal |
| **PHP-Injection** | Blockiert direkte PHP-Ausführungsversuche |
| **Null-Byte** | Filtert Null-Byte-Attacken |

### User-Agent-Blocking
Bekannte Malware-Scanner, Scraper und Bot-User-Agents blockieren:
- Automatische Aktualisierung der Blacklist
- Eigene User-Agent-Muster hinzufügen
- Whitelist für legitime Bots (Googlebot, Bingbot)

---

## Security-Audit

**Datei:** `admin/security-audit.php`

Der Security-Audit führt einen automatischen Scan durch und bewertet die Sicherheitslage mit einem Score (0–100):

### Prüfbereiche

| Bereich | Prüfungen |
|---------|-----------|
| **PHP-Konfiguration** | `display_errors`, `expose_php`, `allow_url_fopen` |
| **Verzeichnisse** | Schreibberechtigungen, `.htaccess` vorhanden |
| **Datenbank** | Prepared Statements, Standard-Präfix |
| **HTTPS** | SSL-Zertifikat, HSTS-Header |
| **Sessions** | `session.cookie_httponly`, `session.cookie_secure` |
| **Authentifizierung** | Passwort-Hashing, Brute-Force-Schutz |
| **Dateiberechtigungen** | `config.php` nicht web-zugreifbar |

### Score-Bewertung

| Score | Status |
|-------|--------|
| 90–100 | ✅ Ausgezeichnet |
| 70–89 | 🟡 Gut |
| 50–69 | 🟠 Verbesserungsbedarf |
| < 50 | 🔴 Kritisch |

---

## Rate Limiting

Konfigurierbare Anfragen-Limits:

| Bereich | Standard | Zeitraum |
|---------|---------|---------|
| Login-Versuche | 5 | 15 Minuten |
| API-Anfragen | 60 | 1 Minute |
| Formular-Submissions | 10 | 5 Minuten |
| Media-Uploads | 20 | 1 Stunde |

---

## Failed-Login Protokoll

Das Failed-Login-Log zeichnet auf:
- Zeitstempel
- IP-Adresse
- Versuchter Benutzername
- User-Agent
- Referrer

Automatische Bereinigung nach konfigurierbarem Zeitraum (Standard: 30 Tage).

---

## Blockierte IPs verwalten

**Admin → Firewall → Blockierte IPs**

| Aktion | Beschreibung |
|--------|--------------|
| **Entsperren** | IP manuell aus der Blacklist entfernen |
| **Details** | Grund der Sperre und Zeitstempel einsehen |
| **Exportieren** | Blacklist als CSV exportieren |
| **Importieren** | Externe Blacklist importieren |

---

## Datenbank-Tabellen

| Tabelle | Inhalt |
|---------|--------|
| `cms_blocked_ips` | IP-Sperrliste mit Grund und Ablauf |
| `cms_failed_logins` | Fehlgeschlagene Login-Versuche |
| `cms_login_attempts` | Alle Login-Versuche (für Rate Limiting) |
| `cms_activity_log` | Vollständiges Aktivitätsprotokoll |

---

## Verwandte Seiten

- [AntiSpam-Einstellungen](ANTISPAM.md)
- [Security-Audit](SECURITY-AUDIT.md)
- [DSGVO](DSGVO.md)
