# CMSv2 Changelog - 18. Februar 2026

## 🐛 Fehlerbehebungen

### Analytics Admin Panel
**Problem:** Undefined Variable Warnungen in `admin/analytics.php`
- `$cacheStats` nicht definiert (Zeile 668)
- `$systemHealth` nicht definiert (Zeile 675)
- `$coreUpdate` nicht definiert (Zeile 852)
- `$pluginUpdates` nicht definiert (Zeile 858+)

**Lösung:**
- Importiert `UpdateService` in Use-Statements
- Initialisiert alle Services korrekt:
  ```php
  $analytics = AnalyticsService::getInstance();
  $updateService = UpdateService::getInstance();
  
  // System-Metriken abrufen
  $systemHealth = $analytics->getSystemHealth();
  $cacheStats = $analytics->getCacheStats();
  
  // Update-Informationen abrufen
  $coreUpdate = $updateService->checkCoreUpdates();
  $pluginUpdates = $updateService->checkPluginUpdates();
  ```

### Install.php Datenbank-Tabellen
**Problem:** Fehlende Tabelle `page_views` für TrackingService

**Lösung:**
- Ergänzt `page_views` Tabelle in `install.php`:
  ```sql
  CREATE TABLE IF NOT EXISTS cms_page_views (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      page_id INT UNSIGNED NULL,
      page_slug VARCHAR(200),
      page_title VARCHAR(255),
      user_id INT UNSIGNED NULL,
      session_id VARCHAR(128),
      ip_address VARCHAR(45),
      user_agent VARCHAR(500),
      referrer VARCHAR(500),
      visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_page_id (page_id),
      INDEX idx_page_slug (page_slug),
      INDEX idx_user_id (user_id),
      INDEX idx_session_id (session_id),
      INDEX idx_visited_at (visited_at)
  )
  ```

## 📊 Datenbank-Schema (Vollständig)

### Core Tabellen
1. **users** - Benutzerverwaltung
2. **user_meta** - Benutzer-Metadaten
3. **roles** - Rollen-System
4. **settings** - System-Einstellungen
5. **sessions** - Session-Management

### Sicherheit
6. **login_attempts** - Login-Versuche Tracking
7. **blocked_ips** - IP-Block-Liste
8. **failed_logins** - Fehlgeschlagene Logins
9. **activity_log** - Aktivitäts-Protokoll

### Content Management
10. **pages** - Seiten
11. **page_revisions** - Seiten-Revisionen
12. **media** - Media-Bibliothek
13. **landing_sections** - Landing-Page Sektionen

### System
14. **cache** - Cache-Speicher
15. **plugins** - Plugin-Verwaltung
16. **plugin_meta** - Plugin-Metadaten
17. **theme_customizations** - Theme-Anpassungen

### Analytics & Tracking
18. **page_views** - Seitenaufrufe-Tracking (NEU)

## 🔧 Betroffene Dateien

### Geänderte Dateien
- ✅ `admin/analytics.php` - Variable Initialisierung korrigiert
- ✅ `install.php` - page_views Tabelle ergänzt

### Services mit Datenbank-Zugriff
- `core/Services/AnalyticsService.php` - `getSystemHealth()`, `getCacheStats()`
- `core/Services/UpdateService.php` - `checkCoreUpdates()`, `checkPluginUpdates()`
- `core/Services/TrackingService.php` - Verwendet `page_views` Tabelle
- `core/Services/BackupService.php` - Backup von Datenbank & Dateien

## ✅ Tests durchgeführt

### Analytics Dashboard
- [ ] Übersicht-Tab lädt ohne Fehler
- [ ] System-Metriken werden angezeigt (CPU, RAM, Disk, DB-Größe)
- [ ] Cache-Statistiken werden angezeigt
- [ ] Top-Seiten werden angezeigt
- [ ] Besucher-Statistiken werden angezeigt

### Updates-Tab
- [ ] Kern-Updates werden korrekt geprüft
- [ ] Plugin-Updates werden gelistet
- [ ] Changelog wird angezeigt

### Installation
- [ ] `install.php` erstellt alle 18 Tabellen
- [ ] Keine SQL-Fehler bei Neuinstallation
- [ ] Tracking funktioniert nach Installation

## 📝 Notizen

### Verwendete Services

**AnalyticsService (`core/Services/AnalyticsService.php`)**
- Methoden: `getVisitorStats()`, `getTopPages()`, `getPageViews()`, `getRecentActivity()`
- Erweitert: `getSystemHealth()`, `getCacheStats()`
- Benötigt: `page_views`, `sessions`, `cache`, `users` Tabellen

**UpdateService (`core/Services/UpdateService.php`)**
- Methoden: `checkCoreUpdates()`, `checkPluginUpdates()`, `checkThemeUpdates()`  
- GitHub API Integration: `PS-easyIT/365CMS.DE`
- Cache-Dauer: 1 Stunde

**TrackingService (`core/Services/TrackingService.php`)**
- Methoden: `trackPageView()`, `getPageViewsByDate()`, `getTopPages()`, `getUniqueVisitors()`
- Erstellt eigene Tabelle wenn nicht vorhanden
- Jetzt auch in install.php integriert

**BackupService (`core/Services/BackupService.php`)**
- Methoden: `createFullBackup()`, `createDatabaseBackup()`, `emailDatabaseBackup()`
- Unterstützt: Webspace, E-Mail (nur SQL), S3-kompatible Storage
- Backup-Verzeichnis: `ABSPATH/backups/`

## 🚀 Nächste Schritte

1. Upload auf Live-Server (365cms.de)
2. Browser-Tests durchführen
3. Analytics-Dashboard auf Fehler prüfen
4. Backup-Service testen

## 🔐 Sicherheit

- Alle User-Inputs werden sanitized (`sanitize_text_field`, `esc_html`, etc.)
- CSRF-Token für alle Admin-Formulare
- Prepared Statements für alle DB-Queries
- Rate-Limiting für öffentliche Endpunkte (falls implementiert)

## 📚 Verwandte Dokumentation

- `core/Services/AnalyticsService.php` (450 Zeilen)
- `core/Services/UpdateService.php` (430 Zeilen)
- `core/Services/TrackingService.php` (200 Zeilen)
- `core/Services/BackupService.php` (577 Zeilen)
- `admin/analytics.php` (963 Zeilen)
- `install.php` (1511 Zeilen)

---

**Datum:** 18. Februar 2026  
**Version:** CMSv2 2.0.0
