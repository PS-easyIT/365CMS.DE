# 365CMS Changelog

## Versionshistorie

| Version | Datum | Zusammenfassung |
|---------|-------|-----------------|
| 0.4.1 | 22.02.2026 | Design-Unifikation Aboverwaltung: einheitliches Layout wie Benutzer & Gruppen |
| 0.4.0 | 21.02.2026 | Aboverwaltung-Umbau, Pakete-Editor in Übersicht, neue Einstellungen-Seite, Version-Badge |
| 0.3.0 | 20.02.2026 | Bestellsystem, Admin-UI Neuaufbau (users.php, groups.php), Menü-Strukturierung |
| 0.2.0 | 18.02.2026 | Analytics-Fixes, install.php Datenbank-Tabellen |
| 0.1.0 | 01.02.2026 | Initiales CMS-System (Core, Auth, DB, Theme, Plugin-System) |

---

## v0.4.1 — 22. Februar 2026

### 🎨 Design & UI

#### Aboverwaltung: Admin-Design vereinheitlicht
- **Layout-Basis:** `renderAdminLayoutStart` / `renderAdminLayoutEnd` als einheitliches Wrapper-System (wie `users.php`, `groups.php`)
- **Pakete-Tab:** Karten mit `.sub-plans-grid` + `.plan-card`; Aktions-Buttons (Bearbeiten, Löschen) als `.btn-sm .btn-secondary` / `.btn-sm .btn-danger`
- **Einstellungen-Tab:** Zweispalten-Grid (`.settings-grid-2col`) mit 5 `.post-card`-Sections: Abo-System, Zahlungsmethoden, Rechtliche Seiten, Rechnungsabsender, Bestellnummern
  - Responsive: auf Mobilgeräten (< 900px) einspaltig
- **Zuweisungen-Tab:** Formular als `.post-card`, Benutzerliste als `.usr-adm-grid`, Gruppen-Tabelle als einheitliche `.posts-table`
- **Notices:** `.notice`, `.notice-success`, `.notice-error` lokal definiert (analog zu users.php)
- **Alle Inline-Styles entfernt** – vollständig durch CSS-Klassen ersetzt

---

## v0.4.0 — 21. Februar 2026

### 🚀 Features

#### Aboverwaltung komplett überarbeitet
- **Pakete-Tab:** Jede Plan-Card hat jetzt direkte ✏️ Edit- und 🗑 Löschen-Buttons. Neues-Paket-Button im Header.
  Plan-Editor-Modal wird jetzt von `?tab=plans` aus gesteuert (nicht mehr `?tab=settings`).
- **Einstellungen-Tab (NEU):** Komplett neue Seite für Abo-System-Einstellungen:
  - Toggle: Abo-System aktiv/inaktiv (wenn aus → Unlimited-Modus für alle Benutzer)
  - Währung (EUR / USD / CHF)
  - Zahlungsmethoden: Bankverbindung, PayPal, allgemeine Hinweise
  - Rechtliche Seiten: AGB-URL, Impressum-URL, Widerruf-URL
  - Rechnungsabsender: Unternehmensname, Adresse
  - Bestellnummern-Format (Platzhalter: `{Y}`, `{M}`, `{D}`, `{ID}`, `{R}`)
- **Zuweisungen-Tab:** Benutzer-Zuweisungen und Gruppen-Zuweisungen jetzt auf einer Seite ohne interne Sub-Tabs
- **Payments-Tab entfernt** – Zahlungseinstellungen sind in Einstellungen integriert

#### Dashboard
- **Version-Badge** im Dashboard-Header: Zeigt aktuelle CMS-Version als blaues Badge neben dem Seitentitel.
  Zieht Wert automatisch aus `CMS_VERSION`-Konstante.

#### Technisches
- `CMS_VERSION` auf `0.4.0` geändert (0.x Versionierung)
- `update_payments` POST-Handler durch `update_settings` ersetzt (speichert alle Abo-Einstellungen)
- `admin-menu.php`: Aboverwaltung hat 4 statt 5 Unterpunkte

---

## v0.3.0 — 20. Februar 2026

### 🚀 Neue Funktionen

#### Subscription & Checkout System
**Neu:** Vollständiges Bestellsystem für Mitgliedschaften implementiert.
- **Datenbank:** Neue Tabelle `cms_orders` für Bestellungen und Transaktionen.
- **Frontend:** Öffentliche Checkout-Seite (`member/order_public.php`) mit Vorausfüllung von Benutzerdaten.
- **Backend:** Admin-Oberfläche (`admin/orders.php`) zur Verwaltung von Bestellungen (Status ändern, Details einsehen).
- **Logik:** Automatische Generierung von Bestellnummern (`BST...`) und Status-Tracking.

#### Admin-UI Neuaufbau (users.php, groups.php)
- `admin/users.php` komplett neugebaut: Stat-Cards, Rollen-Tabs, Suche, Bulk-Aktionen, Edit mit Gruppen-Checkboxes, sicheres Delete-Pattern
- `admin/groups.php` komplett neugebaut: Gruppen-Tab + Rollen & Rechte-Tab, Member-Listen, 8 Capability-Checkboxen
- Menü: `Rollen & Rechte` als eigenständiger Unterpunkt; Menü-Kollision zwischen Gruppen und Aboverwaltung behoben
- Aboverwaltung: Von 5 auf 4 Unterpunkte reduziert (Zuweisungen kombiniert)

### 🐛 Fehlerbehebungen

#### Admin Orders UI
**Problem:** Fatal Error in `admin/orders.php` durch fehlende Include-Dateien.
**Lösung:** Eigenständige HTML-Struktur, Integration von `admin-menu.php`, entfernte ungültige Requires.

#### Checkout Frontend
**Problem:** `TypeError` in `htmlspecialchars()` bei leeren Benutzerdaten.
**Lösung:** Null Coalescing, `array_walk`-Säuberung, Korrektur User-Email-Zugriff.

---

## v0.2.0 — 18. Februar 2026

### 🐛 Fehlerbehebungen

#### Analytics Admin Panel
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
