# System & Diagnose – Dokumentation

## 📋 Übersicht

Die System & Diagnose-Seite bietet umfassende Tools zur Überwachung, Wartung und Fehlerbehebung des CMS-Systems.

## 🎯 Features

### 1. Übersicht-Tab
Zeigt wichtige Systemmetriken auf einen Blick:

#### System-Informationen
- PHP Version
- MySQL Version
- Server-Software
- Betriebssystem & Architektur
- Memory Limit
- Max Execution Time
- Upload/POST Limits

#### Datenbank-Status
- Verbindungsstatus
- Datenbankname
- Anzahl Tabellen (Gesamt/CMS)
- Datenbank-Größe in MB

#### CMS-Statistiken
- Gesamte Benutzer
- Aktive Benutzer
- Anzahl Seiten
- Aktive Sitzungen
- Cache-Einträge
- Fehlgeschlagene Logins (Heute)

#### Verzeichnis-Größen
- uploads/
- cache/
- logs/
- assets/

### 2. Datenbank-Tab
Detaillierte Übersicht aller CMS-Tabellen:

**Informationen pro Tabelle:**
- Status (Vorhanden/Fehlt)
- Anzahl Einträge
- Tabellengröße
- Integritätsprüfung (CHECK TABLE)

**Überwachte Tabellen:**
1. `cms_users` - Benutzer
2. `cms_user_meta` - Benutzer-Metadaten
3. `cms_roles` - Rollen
4. `cms_sessions` - Sitzungen
5. `cms_settings` - Einstellungen
6. `cms_pages` - Seiten
7. `cms_page_revisions` - Seiten-Revisionen
8. `cms_landing_sections` - Landing Sections
9. `cms_activity_log` - Aktivitätslog
10. `cms_cache` - Cache
11. `cms_failed_logins` - Fehlgeschlagene Logins
12. `cms_login_attempts` - Login-Versuche
13. `cms_blocked_ips` - Blockierte IPs
14. `cms_media` - Media-Bibliothek
15. `cms_plugins` - Plugins
16. `cms_plugin_meta` - Plugin-Metadaten
17. `cms_theme_customizations` - Theme-Anpassungen

### 3. Dateisystem-Tab
Überprüfung kritischer Verzeichnisse:

**Geprüfte Verzeichnisse:**
- `uploads/` - Hochgeladene Dateien
- `cache/` - Cache-Dateien
- `logs/` - Log-Dateien
- `config/` - Konfigurationsdateien
- `assets/css/` - Stylesheets
- `assets/js/` - JavaScript-Dateien
- `assets/images/` - Bilder

**Prüfungen pro Verzeichnis:**
- Existiert?
- Lesbar?
- Schreibbar?
- Unix-Permissions (z.B. 0755)

### 4. Sicherheit-Tab
Sicherheitsanalyse des Systems:

**Geprüfte Einstellungen:**
- Debug Mode Status
- Display Errors (sollte OFF sein in Produktion)
- Session Cookie Secure (HTTPS)
- Session Cookie HTTPOnly
- Session Cookie SameSite
- HTTPS Status
- Upload/Memory Limits

**Empfehlungen:**
- ⚠️ Warnungen für unsichere Konfigurationen
- 🔴 Kritische Fehler (z.B. aktiver Debug-Modus in Produktion)

### 5. Tools-Tab
Wartungs- und Troubleshooting-Tools:

#### 🗑️ Cache leeren
Löscht alle Cache-Einträge aus Datenbank und Dateisystem.

**Verwendung:**
- Bei Frontend-Darstellungsproblemen
- Nach Theme-/Plugin-Updates
- Bei veralteten Inhalten

#### 🔄 Alte Sitzungen löschen
Entfernt abgelaufene Session-Einträge aus der Datenbank.

**Verwendung:**
- Automatische Bereinigung nach Zeitablauf
- Bei Performance-Problemen durch zu viele Sessions

#### 🚫 Fehllogins löschen
Löscht fehlgeschlagene Login-Versuche älter als 24 Stunden.

**Verwendung:**
- Regelmäßige Wartung
- Reduzierung der Datenbankgröße

#### 🔧 Tabellen reparieren
Führt `REPAIR TABLE` auf allen CMS-Tabellen aus.

**Verwendung:**
- Nach Serverabstürzen
- Bei beschädigten Tabellenindizes
- Bei "Table is marked as crashed" Fehlern

**Hinweis:** Kann einige Zeit dauern!

#### ⚡ Tabellen optimieren
Führt `OPTIMIZE TABLE` auf allen CMS-Tabellen aus.

**Vorteile:**
- Defragmentiert Tabellen
- Reduziert Tabellengröße
- Verbessert Query-Performance

**Verwendung:**
- Monatliche Wartung empfohlen
- Nach großen Löschvorgängen

**Hinweis:** Kann einige Zeit dauern!

#### 📋 Logs leeren
Löscht alle Einträge aus der Fehler-Log-Datei.

**Verwendung:**
- Nach Behebung von Fehlern
- Bei zu großer Log-Datei

**Achtung:** Unwiderruflich!

### 6. Logs-Tab
Fehler-Log-Anzeige (letzte 50 Einträge):

**Log-Typen:**
- ERROR (Rot) - Kritische Fehler
- WARNING (Gelb) - Warnungen
- INFO (Blau) - Informationen
- UNKNOWN (Grau) - Unbekannte Einträge

**Informationen pro Log:**
- Timestamp
- Log-Typ
- Fehlermeldung

**Log-Format:**
```
[YYYY-MM-DD HH:MM:SS] TYPE: Message
```

## 🔧 SystemService API

Der `SystemService` bietet folgende Methoden:

### Informations-Methoden

```php
// System-Informationen abrufen
$systemInfo = SystemService::instance()->getSystemInfo();

// Datenbank-Status prüfen
$dbStatus = SystemService::instance()->getDatabaseStatus();

// Alle Tabellen prüfen
$tables = SystemService::instance()->checkDatabaseTables();

// Datei-Berechtigungen prüfen
$permissions = SystemService::instance()->checkFilePermissions();

// Verzeichnis-Größen ermitteln
$sizes = SystemService::instance()->getDirectorySizes();

// CMS-Statistiken abrufen
$stats = SystemService::instance()->getCMSStatistics();

// Sicherheitsstatus prüfen
$security = SystemService::instance()->getSecurityStatus();

// Komplette System-Prüfung
$fullCheck = SystemService::instance()->runSystemCheck();
```

### Wartungs-Methoden

```php
// Cache leeren
$success = SystemService::instance()->clearCache();

// Alte Sitzungen löschen
$success = SystemService::instance()->clearOldSessions();

// Alte Fehllogins löschen
$success = SystemService::instance()->clearOldFailedLogins();

// Tabellen reparieren
$results = SystemService::instance()->repairTables();

// Tabellen optimieren
$results = SystemService::instance()->optimizeTables();

// Error-Logs abrufen
$logs = SystemService::instance()->getErrorLogs(100); // Limit: 100

// Error-Logs löschen
$success = SystemService::instance()->clearErrorLogs();
```

### Utility-Methoden

```php
// Bytes formatieren (human readable)
$formatted = SystemService::instance()->formatBytes(1048576); // "1.00 MB"

// Verzeichnisgröße berechnen (private, intern genutzt)
$size = SystemService::instance()->getDirectorySize('/path/to/dir');
```

## 🔒 Sicherheit

### Zugriffskontrolle
- Nur für eingeloggte Administratoren zugänglich
- CSRF-Schutz für alle POST-Aktionen
- Session-basierte Authentifizierung

### CSRF-Token
Alle Tools verwenden CSRF-Tokens:

```php
<input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
```

### Berechtigungen
Alle Aktionen überprüfen:
1. Benutzer ist eingeloggt (`Auth::instance()->isLoggedIn()`)
2. Benutzer ist Administrator (`Auth::instance()->isAdmin()`)
3. CSRF-Token ist gültig (`Security::instance()->verifyToken()`)

## 📊 Status-Indikatoren

### Farbcodierung

**Grün (Erfolgreich):**
- Datenbank verbunden
- Tabelle OK
- Verzeichnis beschreibbar
- Sicherheitseinstellung korrekt

**Gelb (Warnung):**
- Deprecated PHP-Version
- Unsichere Einstellung
- Read-Only Verzeichnis

**Rot (Fehler):**
- Datenbank nicht verbunden
- Tabelle fehlt
- Verzeichnis nicht vorhanden
- Kritische Sicherheitslücke

### Icons
- ✓ = OK
- ✗ = Fehler
- ⚠️ = Warnung
- 🔴 = Kritisch

## 🎨 Responsive Design

Die System-Seite passt sich automatisch an verschiedene Bildschirmgrößen an:

**Desktop (>1200px):**
- 2-spaltige System-Grid
- 3-spaltige Tools-Grid

**Tablet (768-1200px):**
- 1-spaltige System-Grid
- 2-spaltige Tools-Grid

**Mobile (<768px):**
- Alle Grids 1-spaltig
- Info-Rows vertikal gestapelt

## 🚀 Performance-Tips

### Cache-Management
- Leeren Sie den Cache regelmäßig, aber nicht zu oft
- Nach Updates immer Cache leeren

### Datenbank-Wartung
- Optimieren Sie Tabellen monatlich
- Reparieren Sie nur bei Bedarf
- Löschen Sie alte Sessions wöchentlich

### Log-Verwaltung
- Überwachen Sie Logs täglich
- Leeren Sie Logs nach Behebung von Fehlern
- Implementieren Sie Log-Rotation für große Systeme

## 🐛 Troubleshooting

### Problem: "Datenbank-Verbindung fehlgeschlagen"
**Lösung:**
1. Prüfe `config.php` - Sind DB-Credentials korrekt?
2. Ist MySQL-Server gestartet?
3. Stimmt der DB_HOST (localhost vs. 127.0.0.1)?

### Problem: "Tabelle fehlt"
**Lösung:**
1. Führe Installation erneut aus
2. Prüfe `install.php` - wurden Tabellen erstellt?
3. Importiere SQL-Backup falls vorhanden

### Problem: "Verzeichnis nicht beschreibbar"
**Lösung:**
```bash
# Windows (PowerShell)
icacls "C:\\path\\to\\dir" /grant Users:F

# Linux/Mac
chmod 755 /path/to/dir
chown www-data:www-data /path/to/dir
```

### Problem: "Zu viele Fehler in Logs"
**Lösung:**
1. Aktiviere Debug-Modus temporär
2. Reproduziere Fehler
3. Analysiere Log-Einträge
4. Behebe Ursache
5. Deaktiviere Debug-Modus
6. Leere Logs

## 📝 Changelog

### Version 1.0.0 (2026-02-18)
- ✅ Initial Release
- ✅ Übersicht-Tab mit System-Informationen
- ✅ Datenbank-Tab mit Tabellen-Status
- ✅ Dateisystem-Tab mit Berechtigungsprüfung
- ✅ Sicherheit-Tab mit Empfehlungen
- ✅ Tools-Tab mit 6 Wartungs-Tools
- ✅ Logs-Tab mit Error-Log-Anzeige
- ✅ Responsive Design
- ✅ CSRF-Schutz
- ✅ Vollständige API-Dokumentation

## 📚 Siehe auch

- [SystemService.php](../core/Services/SystemService.php) - Service-Implementierung
- [admin.css](../assets/css/admin.css) - Styling
- [Security.php](../core/Security.php) - CSRF-Schutz
- [Database.php](../core/Database.php) - Datenbankverbindung
