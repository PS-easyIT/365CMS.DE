# System & Diagnose

**Datei:** `admin/system.php`

---

## Übersicht

Die Systemseite bietet detaillierte Informationen über die Server-Umgebung, PHP-Konfiguration, Datenbankverbindung und ermöglicht Wartungs-Operationen wie Cache leeren und Datenbank optimieren.

---

## System-Informationen

### Server-Umgebung

| Eigenschaft | Wert |
|-------------|------|
| PHP-Version | z.B. 8.3.6 |
| PHP SAPI | FPM / CLI / apache2handler |
| Web-Server | Apache 2.4 / nginx |
| Betriebssystem | Linux / Windows |
| Server-Software | Vollständige Serverkennung |
| Server-Protokoll | HTTP/2 |

### PHP-Konfiguration

| Einstellung | Ist-Wert | Empfehlung |
|-------------|---------|------------|
| `memory_limit` | 256M | ≥ 128M |
| `max_execution_time` | 30 | 30–60 |
| `upload_max_filesize` | 32M | ≥ 8M |
| `post_max_size` | 64M | ≥ upload + 2M |
| `max_input_vars` | 1000 | ≥ 1000 |
| `display_errors` | Off | Off (Produktion) |
| `opcache.enable` | On | On |

### PHP-Erweiterungen

| Erweiterung | Status | Benötigt für |
|-------------|--------|--------------|
| PDO / PDO_MySQL | ✅ | Datenbankzugriff |
| GD / Imagick | ✅ | Bildverarbeitung |
| mbstring | ✅ | Multibyte-String |
| JSON | ✅ | API-Kommunikation |
| cURL | ✅ | HTTP-Anfragen |
| OpenSSL | ✅ | Verschlüsselung |
| Zip | ✅ | Plugin/Theme-Upload |
| intl | ⚠️ | Internationalisierung |

---

## Datenbank-Status

| Info | Beschreibung |
|------|--------------|
| Verbindungsstatus | Verbunden / Fehler |
| MySQL-Version | z.B. 10.6.18-MariaDB |
| Datenbankgröße | Gesamt in MB |
| Anzahl Tabellen | Alle `cms_`-Tabellen |
| Tabellen mit Fehlern | MyISAM/InnoDB-Checks |
| Zeichensatz | utf8mb4_unicode_ci |

---

## Verzeichnis-Berechtigungen

| Verzeichnis | Berechtigung | Status |
|-------------|-------------|--------|
| `CMS/uploads/` | 755 | ✅ / ⚠️ |
| `CMS/cache/` | 755 | ✅ / ⚠️ |
| `CMS/logs/` | 700 | ✅ / ⚠️ |
| `config.php` | 600 | ✅ / ⚠️ |

---

## Wartungs-Aktionen

### Cache-Verwaltung

| Aktion | Beschreibung |
|--------|--------------|
| **Alle Caches leeren** | Löscht alle gecachten Daten |
| **Seiten-Cache leeren** | Nur gecachte HTML-Seiten |
| **Objekt-Cache leeren** | Datenbank-Query-Cache |
| **Opcode-Cache leeren** | PHP OPcache zurücksetzen |
| **Asset-Cache leeren** | CSS/JS-Minifizierungs-Cache |

### Datenbank-Wartung

| Aktion | Beschreibung |
|--------|--------------|
| **Tabellen optimieren** | OPTIMIZE TABLE für alle cms_-Tabellen |
| **Tabellen reparieren** | REPAIR TABLE bei MyISAM-Problemen |
| **Verwaiste Einträge** | Orphaned Records bereinigen |
| **Datenbank-Backup** | Sofort-Backup als SQL-Datei |

### Logs

| Log | Pfad | Beschreibung |
|-----|------|--------------|
| PHP-Error-Log | `CMS/logs/php.log` | PHP-Fehler und Warnings |
| Access-Log | `CMS/logs/access.log` | Admin-Zugriffe |
| Security-Log | `CMS/logs/security.log` | Firewall & Login-Ereignisse |
| Debug-Log | `CMS/logs/debug.log` | Debug-Modus-Ausgaben |

Logs können direkt auf der Systemseite eingesehen und heruntergeladen werden.

---

## Diagnose-Tools

### Verbindungstest
- Datenbank-Verbindung testen
- E-Mail-SMTP-Verbindung testen
- URL-Erreichbarkeit testen

### Installations-Check
Überprüft ob alle erforderlichen Dateien, Verzeichnisse und Datenbanktabellen vorhanden sind.

### Permissions-Fix
Automatische Korrektur falscher Verzeichnisberechtigungen mit einem Klick.

---

## Backup-System

### Manuelles Backup
1. Backup-Typ wählen: Datenbank / Dateien / Komplett
2. "Backup erstellen" klicken
3. ZIP/SQL-Datei wird in `BACKUP/` gespeichert
4. Download-Link erscheint sofort

### Automatisches Backup
Konfigurierbar über System-Einstellungen:
- **Frequenz:** Täglich / Wöchentlich / Monatlich
- **Aufbewahrung:** Letzte 3/5/10 Backups behalten
- **Ziel:** Lokaler Server / FTP / S3 (konfigurierbar)

---

## Update-Prüfung

| Komponente | Installiert | Verfügbar |
|------------|------------|-----------|
| CMS Core | 1.8.0 | 1.8.1 ⬆️ |
| Aktives Theme | 2.1.0 | 2.1.0 ✅ |
| Plugin: cms-experts | 1.2.0 | 1.3.0 ⬆️ |

---

## Verwandte Seiten

- [Security Audit](../legal-security/SECURITY-AUDIT.md)
- [Firewall](../legal-security/FIREWALL.md)
- [Backup](../../workflow/BACKUP-GUIDE.md)
- [Performance](../seo-performance/PERFORMANCE.md)
