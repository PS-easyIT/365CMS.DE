# 365CMS Changelog

> **Versionierungsschema:**
> - `x.MINOR.patch` — Minor-Sprung = neue Funktion · Patch-Sprung = Bugfix
> - `MAJOR.x.x` — Major-Sprung = signifikante Änderungen oder neues Release-Ziel
> - v0.1.0 – v0.4.99 = **Interne Entwicklung** (2025, nicht öffentlich)
> - v0.5.0+ = **Public Release** (2026, GitHub)

---

## Versionshistorie

| Version | Datum | Typ | Zusammenfassung |
|---------|-------|-----|-----------------|
| **0.21.3** | 21.02.2026 | 🐛 Fix | orders.php Design-Unifikation, Debug-Logging nach /logs |
| 0.21.2 | 21.02.2026 | 🐛 Fix | Aboverwaltung: Pakete/Zuweisungen/Einstellungen UI-Redesign, Version-Badge |
| 0.21.1 | 21.02.2026 | 🐛 Fix | Admin-Design vereinheitlicht (Benutzer, Gruppen, Aboverwaltung) |
| 0.21.0 | Feb 2026 | ✨ Feat | Settings & Systemverwaltung (admin/settings.php, admin/system.php) |
| 0.20.0 | Feb 2026 | ✨ Feat | Updates-Manager (admin/updates.php, GitHub API Integration) |
| 0.19.0 | Feb 2026 | ✨ Feat | Design-Tools: Dashboard-Widgets, Lokal-Fonts, Theme-Customizer & -Editor |
| 0.18.0 | Feb 2026 | ✨ Feat | Navigation & Menü-Verwaltung (admin/menus.php) |
| 0.17.0 | Feb 2026 | ✨ Feat | Performance & Cache-Tools (admin/performance.php, CacheManager) |
| 0.16.0 | Feb 2026 | ✨ Feat | Backup & Recovery (admin/backup.php, BackupService) |
| 0.15.0 | Feb 2026 | ✨ Feat | DSGVO-Suite: Cookies, Datenzugriff, Datenlöschung (admin/cookies.php etc.) |
| 0.14.0 | Feb 2026 | ✨ Feat | Landing Pages (admin/landing-page.php, LandingPageService) |
| 0.13.0 | Feb 2026 | ✨ Feat | Orders & Aboverwaltung (admin/orders.php, admin/subscriptions.php) |
| 0.12.0 | Jan 2026 | ✨ Feat | Analytics & Tracking (admin/analytics.php, AnalyticsService, TrackingService) |
| 0.11.0 | Jan 2026 | ✨ Feat | SEO-Verwaltung (admin/seo.php, SEOService, Meta-Tags, Sitemap) |
| 0.10.0 | Jan 2026 | ✨ Feat | Blog/Beiträge (admin/posts.php) |
| 0.9.0 | Jan 2026 | ✨ Feat | Seiten-Verwaltung (admin/pages.php, PageManager, Revisionen) |
| 0.8.0 | Jan 2026 | ✨ Feat | Media-Bibliothek (admin/media.php, MediaService, media-proxy.php) |
| 0.7.0 | Jan 2026 | ✨ Feat | Member-Benachrichtigungen (member/notifications.php) |
| 0.6.0 | Jan 2026 | ✨ Feat | Member-Dashboard + Profil (member/index.php, profile.php, MemberService) |
| **0.5.0** | Jan 2026 | 🚀 **Public** | Erstes öffentliches Release — Core stabil (Auth, DB, Router, Hooks, Security, Cache) |
| *(intern)* | | | |
| 0.4.1 | 2025 | Fix | Design-Unifikation Aboverwaltung |
| 0.4.0 | 2025 | Feat | Aboverwaltung-Umbau, Pakete-Editor, neue Einstellungen-Seite |
| 0.3.0 | 2025 | Feat | Bestellsystem, Admin-UI Neuaufbau (users.php, groups.php) |
| 0.2.0 | 2025 | Fix | Analytics-Fixes, install.php DB-Tabellen |
| 0.1.0 | 2025 | Init | Initiales CMS: Core, Auth, DB, Theme, Plugin-System |

---

## v0.21.3 — 21. Februar 2026

### 🐛 Bugfixes & Verbesserungen

#### Bestellungen: Admin-Design vereinheitlicht
- `admin/orders.php` vollständig auf `renderAdminLayoutStart('Bestellungen', 'orders')` umgestellt
- Einheitliche CSS-Klassen: `.posts-table`, `.posts-header`, `.status-badge`, `.pager`, `.btn-icon`
- Entfernt: Inline `<!DOCTYPE html>...<head>...renderAdminSidebar()...` Boilerplate
- Neues Bestell-Modal: modernes Design mit Grid-Layout, schlanker JS-Code
- `$message`/`$error` Variablen korrekt initialisiert

#### Debug-Logging: Nur bei aktivem CMS_DEBUG
- **config.php:** Log-Konfiguration umgekehrt
  - `CMS_DEBUG=true` → `log_errors=1`, `error_log = logs/error.log` *(Logs in /logs)*
  - `CMS_DEBUG=false` → `log_errors=0` *(keine Logdateien in Produktion)*
- **core/Debug.php:** `log()` schreibt jetzt direkt nach `/logs/debug-YYYY-MM-DD.log`
  - Tagesbasierte Rotation: eine Datei pro Tag
  - Eigene `writeToFile()` Methode (privat), kein `error_log()` mehr
  - Verzeichnis wird automatisch erstellt wenn nicht vorhanden
- **logs/.htaccess:** HTTP-Zugriff auf Log-Dateien gesperrt (`Require all denied`)
- **logs/.gitignore:** `*.log` Dateien werden nicht in Git versioniert

---

## v0.21.2 — 21. Februar 2026

### 🐛 Fehlerbehebungen & UI-Improvements

#### Aboverwaltung: Pakete-Editor in Übersicht, neue Einstellungen-Seite, Version-Badge
- **Pakete-Tab:** Jede Plan-Card hat direkte ✏️ Edit- und 🗑 Löschen-Buttons
- **Einstellungen-Tab:** Toggle Abo-System, Währung, Zahlungsmethoden, Rechtliche Seiten, Rechnungsabsender, Bestellnummern-Format
- **Zuweisungen-Tab:** Benutzer- und Gruppen-Zuweisungen auf einer Seite
- **Dashboard:** Version-Badge mit `CMS_VERSION`-Konstante

---

## v0.21.1 — 21. Februar 2026

### 🎨 Design & UI

#### Admin-Design vereinheitlicht (Benutzer & Gruppen → Aboverwaltung)
- `renderAdminLayoutStart`/`renderAdminLayoutEnd` als einheitliches Wrapper-System
- Pakete, Zuweisungen: `.sub-plans-grid`, `.plan-card`, `.plan-actions`, `.btn-sm`
- Einstellungen: 2-Spalten-Grid (`.settings-grid-2col`) mit `.post-card`-Sections
- Notices: `.notice`, `.notice-success`, `.notice-error` lokal definiert

---

## v0.5.0 — Januar 2026 (Erstes öffentliches Release)

### 🚀 Public Release

Alle internen Entwicklungsversionen (0.1.0–0.4.x) wurden konsolidiert.
Core-System gilt als stabil und wurde auf GitHub veröffentlicht.

**Enthaltene Systeme beim ersten Public Release:**
- Core: Auth, DB (PDO), Router, Hooks, Security, CacheManager, Bootstrap
- Admin: Dashboard, Benutzerverwaltung, Gruppen, vollständiges Plugin- & Theme-System
- Member: Dashboard, Profil, Nachrichten, Benachrichtigungen, Medien, Favoriten
- DSGVO: Cookies, Datenzugriff, Datenlöschung, Datenschutz
- Features: Seiten, Posts, Media, SEO, Analytics, Orders, Subscriptions, Landing Pages
- Tools: Backup, Performance, Menus, Updates, Design-Werkzeuge

---

## (Intern) v0.3.0 — 2025

### 🚀 Neue Funktionen

#### Subscription & Checkout System
- **Datenbank:** Neue Tabelle `cms_orders`
- **Frontend:** Öffentliche Checkout-Seite (`member/order_public.php`)
- **Backend:** Admin-Oberfläche (`admin/orders.php`), Status-Tracking

#### Admin-UI Neuaufbau
- `admin/users.php`: Stat-Cards, Rollen-Tabs, Suche, Bulk-Aktionen
- `admin/groups.php`: Gruppen + Rollen & Rechte-Tab, 8 Capability-Checkboxen
- Menü: `Rollen & Rechte` als eigenständiger Unterpunkt

---

## (Intern) v0.2.0 — 2025

### 🐛 Fehlerbehebungen

#### Analytics Admin Panel
- `$cacheStats`, `$systemHealth`, `$coreUpdate`, `$pluginUpdates` nicht initialisiert → korrigiert
- `UpdateService` in Use-Statements ergänzt

#### Install.php Datenbank-Tabellen
- Fehlende Tabelle `page_views` für TrackingService ergänzt

---

## (Intern) v0.1.0 — 2025

### 🎉 Initiales Release

Grundstruktur des CMS aufgebaut:
- Core: Auth, Database (PDO), Router, Hooks, Security, CacheManager, Bootstrap, Debug
- Admin: Grundlegendes Admin-Panel
- Theme-System: cms-default Theme
- Plugin-System: Hook-basiertes Erweiterungssystem
- Install-Wizard: Automatische DB-Tabellen-Erstellung

