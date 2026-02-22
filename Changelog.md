# 365CMS.DE  [![Generic badge](https://img.shields.io/badge/VERSION-1.6.9-blue.svg)](https://shields.io/)

# 365CMS Changelog

> **Versionierungsschema:**
> - `CMSv1` = `0.1.xx – 0.4.99` — Interne Entwicklung (2025, nicht öffentlich)
> - `CMSv2` = `0.5.xx+` — Public Release (2026, GitHub)
> - `x.MINOR.patch` — Minor-Sprung = neue Funktion · Patch-Sprung = Bugfix/Refactor
> - Plugins & Themes werden separat versioniert und sind hier **nicht** enthalten

---

## 🗓️ Übersicht (letzte Einträge)

| Version | Datum | Typ | Bereich | Beschreibung |
|---------|-------|-----|---------|-------------|
| **1.6.9** | 22.02.2026 | 🟢 feat | Cookie | Cookie-Verwaltung mit Dienstbibliothek und Sicherheitsprüfungen erweitert |
| 1.6.8 | 21.02.2026 | 🔵 docs | Core | Dokumentation und Skripte für 365CMS aktualisiert |
| 1.6.7 | 21.02.2026 | ⬜ chore | Docs | Veraltete Sicherheitsarchitektur-Dokumentation entfernt |
| 1.6.6 | 21.02.2026 | 🔵 docs | README | README-Dateien mit neuen Versionsinformationen aktualisiert |
| 1.6.5 | 21.02.2026 | 🟢 feat | Admin | Site-Tables-Management mit CRUD-Operationen und Import/Export |
| 1.6.4 | 21.02.2026 | 🟡 refactor | Legal | Rechtstexte-Generierung bereinigt und optimiert |
| 1.6.3 | 21.02.2026 | 🟢 feat | Cookie | Cookie-Richtlinie mit dynamischem Zustimmungsstatus |
| 1.6.2 | 21.02.2026 | 🟢 feat | Cookie | Cookie-Richtlinie-Generierung in Rechtstexte-Generator integriert |
| 1.6.1 | 21.02.2026 | 🟢 feat | Legal | AntiSpam-Einstellungsseite und Rechtstexte-Generator implementiert |
| 1.6.0 | 21.02.2026 | 🟢 feat | Cache | Cache-Clearing-Funktionalität und Asset-Regenerierung hinzugefügt |

---

## 📋 Legende

| Symbol | Typ | Bedeutung |
|--------|-----|-----------|
| 🟢 | `feat` | Neues Feature |
| 🔴 | `fix` | Bugfix |
| 🟡 | `refactor` | Code-Umbau ohne Funktionsänderung |
| 🟠 | `perf` | Performance-Verbesserung |
| 🔵 | `docs` | Dokumentation |
| ⬜ | `chore` | Wartungsarbeit / CI/CD |
| 🎨 | `style` | Design- / UI-Änderungen |

---

## 📜 Vollständige Versionshistorie

---

### v1.6.x — 21.–22. Februar 2026 · Cookie-Manager & Legal-Suite

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **1.6.9** | 🟢 feat | Cookie | Cookie-Verwaltung mit Dienstbibliothek und Sicherheitsprüfungen erweitert |
| 1.6.8 | 🔵 docs | Core | Dokumentation und Skripte für 365CMS aktualisiert |
| 1.6.7 | ⬜ chore | Docs | Veraltete Sicherheitsarchitektur-Dokumentation entfernt |
| 1.6.6 | 🔵 docs | README | README-Dateien mit neuen Versionsinformationen und verbesserten Beschreibungen aktualisiert |
| 1.6.5 | 🟢 feat | Admin | Site-Tables-Management mit CRUD-Operationen und Import/Export; neue Menüeinträge |
| 1.6.4 | 🟡 refactor | Legal | Generierung von Rechtstexten bereinigt und optimiert; Menübezeichnung aktualisiert |
| 1.6.3 | 🟢 feat | Cookie | Cookie-Richtlinie mit dynamischem Zustimmungsstatus und optimierter Darstellung |
| 1.6.2 | 🟢 feat | Cookie | Cookie-Richtlinie-Generierung in Rechtstexte-Generator integriert |
| 1.6.1 | 🟢 feat | Legal | AntiSpam-Einstellungsseite und Rechtstexte-Generator implementiert |
| 1.6.0 | 🟢 feat | Cache | Cache-Clearing-Funktionalität und Asset-Regenerierung hinzugefügt |

---

### v1.5.x — 21. Februar 2026 · Support-System & DSGVO

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 1.5.9 | 🔴 fix | Database | Tabellenbezeichnungen von `usermeta` zu `user_meta` in mehreren Dateien aktualisiert |
| 1.5.8 | 🔴 fix | SEO | Einstellungsname für benutzerdefinierten robots.txt-Inhalt korrigiert |
| 1.5.7 | 🟢 feat | GDPR | DSGVO-konforme Datenlöschung und Security-Audit-Seite hinzugefügt |
| 1.5.6 | 🔵 docs | Docs | INDEX.md in Dokumentationsliste priorisiert; Dokumentationsindex bereinigt |
| 1.5.5 | 🔵 docs | Docs | Dokumentation für Content-Management, SEO, Performance, Backup und User-Management |
| 1.5.4 | 🟡 refactor | Support | Übersichtsseiten je Bereich mit GitHub-Links statt Markdown-Rendering |
| 1.5.3 | 🔴 fix | Support | Timeout auf 4/6s reduziert; 5-min-Datei-Cache für Dok-Liste; Refresh-Link |
| 1.5.2 | 🔴 fix | Support | fetchDocContent auf GitHub Contents-API umgestellt; CDN entfernt, Markdown serverseitig gerendert |
| 1.5.1 | 🔴 fix | Support | cURL-basierter GitHub-API-Client; Debug-Modus; DOC/admin-Ordner umbenannt |
| 1.5.0 | 🟡 refactor | Support | Support.php komplett neu: Docs ausschließlich via GitHub API + raw.githubusercontent.com |

---

### v1.4.x — 21. Februar 2026 · Admin-Erweiterungen & Logging

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 1.4.9 | 🟢 feat | Docs/Support | Dokumentationsabruf mit rekursivem Directory-Traversal; Sidebar-Gruppierung |
| 1.4.8 | 🟡 refactor | Core | File-Struktur bereinigt; Code-Struktur für bessere Lesbarkeit optimiert |
| 1.4.7 | 🟢 feat | Admin | Plugin- und Theme-Marketplace-Seiten mit Settings-Management hinzugefügt |
| 1.4.6 | 🟢 feat | Landing | Landing-Page-Management erweitert |
| 1.4.5 | 🔴 fix | Logging | Logs werden nur noch bei `CMS_DEBUG=true` in `/logs` geschrieben |
| 1.4.4 | 🎨 style | Orders | Admin-Design für Bestellverwaltung vereinheitlicht (Benutzer & Gruppen) |
| 1.4.3 | 🔵 docs | Changelog | Versionierung auf 0.x umgestellt; Changelog + README aktualisiert |
| 1.4.2 | 🟢 feat | Subscriptions | Admin-Subscriptions-UI mit verbesserter Navigation und Labels |
| 1.4.1 | 🟡 refactor | Subscriptions | Pakete-Editor in Übersicht integriert; neue Einstellungen-Seite; Sub-Tabs entfernt |
| 1.4.0 | 🟢 feat | Dashboard | Version-Badge im Admin Dashboard-Header |

---

### v1.3.x — 20. Februar 2026 · Public Release & Blog/Subscriptions

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 1.3.6 | ⬜ chore | CI/CD | PHP-Composer-Workflow-Konfiguration hinzugefügt |
| 1.3.5 | 🟢 feat | Subscriptions | Subscription- und Checkout-System implementiert |
| 1.3.4 | 🟢 feat | Pages | Page-Management-UI mit Success/Error-Messages und verbessertem Layout |
| 1.3.3 | 🔴 fix | Security | CSRF-Token-Handling in User- und Post-Management-Formularen verbessert |
| 1.3.2 | 🟢 feat | Blog | Blog-Routen für Post-Listing und Single-Post-Detailansicht hinzugefügt |
| 1.3.1 | 🟢 feat | Database | Datenbankschema auf Version 3 aktualisiert; Blog-Post-Tabellen hinzugefügt |
| **1.3.0** | 🟢 feat | **Release** | **First Public Release – 365CMS.DE veröffentlicht** |

---

### v1.2.x — 18.–20. Februar 2026 · Media & Member-Erweiterungen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 1.2.7 | 🔵 docs | Projekt | Initial Commit 365CMS.DE Repository; README mit CMS-Beschreibung und Website-Link |
| 1.2.6 | 🟢 feat | Subscriptions | Zahlungsarten-Update implementiert; Benutzerabonnements-Abfrage verbessert |
| 1.2.5 | 🟢 feat | Member | Member-Menü überarbeitet; Favoriten- und Nachrichten-Funktionalität hinzugefügt |
| 1.2.4 | 🟡 refactor | Error | Fehlerbehandlung überarbeitet; Media-Upload-Struktur für mehr Robustheit verbessert |
| 1.2.3 | 🟡 refactor | Media | Media-View und AJAX-Handling für bessere UX und Fehlerbehandlung überarbeitet |
| 1.2.2 | 🔴 fix | AJAX | AJAX-URL-Handling für mehr Robustheit und Debugging verbessert |
| 1.2.1 | 🟢 feat | Media | Media-Proxy und AJAX-Handling für verbesserte Medienoperationen implementiert |
| 1.2.0 | 🟢 feat | Media | Medien-AJAX-Handling und Authentifizierung verbessert; robustere Fehlerbehandlung |

---

### v1.1.x — 10.–18. Februar 2026 · Member-System & Plugins

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 1.1.9 | 🟢 feat | Member | Member-Medien-Management implementiert (Upload, Verwaltung) |
| 1.1.8 | 🟢 feat | Admin | Dashboard-Funktionalität um Logo-Upload erweitert; Widget-Anzahl auf 4 erhöht |
| 1.1.7 | 🔵 docs | Themes | Umfassende Dokumentation für Theme-Entwicklung in CMSv2 erstellt |
| 1.1.6 | 🟢 feat | Member | Member-Service hinzugefügt; CMS-Speakers-Plugin refaktoriert |
| 1.1.5 | 🟢 feat | Events | CMS-Experts und Events-Management erweitert |
| 1.1.4 | 🟢 feat | Experts | Expert-Management: Status-Updates, Skill-Presets und Plugin-Einstellungen |
| 1.1.3 | 🟡 refactor | Core | Code-Struktur für bessere Lesbarkeit und Wartbarkeit refaktoriert |
| 1.1.2 | 🟢 feat | Landing | Landing-Page-Service um Footer-Management erweitert |
| 1.1.1 | 🟢 feat | Cookie | Cookie-Scanning-Funktionalität mit serverseitigen und Content-Heuristik-Prüfungen |
| 1.1.0 | 🟢 feat | Admin | Landing-Page und Theme-Management-Funktionalität im Admin hinzugefügt |

---

### v1.0.x — 01.–09. Februar 2026 · Stabilisierung & AJAX-Architektur

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 1.0.9 | 🔴 fix | Dashboard | Escaped Backslash-Dollar in SQL-Prefix-Interpolation entfernt |
| 1.0.8 | 🔴 fix | Subscriptions | Fehlendes PHP-Schlusstag `?>` in create-plan-Modal (Zeile 521) ergänzt |
| 1.0.7 | 🔴 fix | Subscriptions | Price-Felder zu Float gecastet vor `number_format()` |
| 1.0.6 | 🔴 fix | Core | Sicherheits-Fixes in Core-Klassen |
| 1.0.5 | 🔴 fix | Core | Datenbank-Prefix-Methoden und Session-Logout-Handling verbessert |
| 1.0.4 | 🟢 feat | Admin | Vollständiger Admin-Bereich: AJAX-Architektur für 12 Dateien (Services + AJAX + Views-Trennung) |
| 1.0.3 | 🔵 docs | Core | Core-Bereich vollständig dokumentiert |
| 1.0.2 | 🟡 refactor | Services | Prefix-Property + hardkodierte Tabellennamen eliminiert |
| 1.0.1 | 🟠 perf | Core | `createTables()` Performance Guards in Database + SubscriptionManager |
| 1.0.0 | 🔴 fix | Admin | Konsistenz + Performance-Fixes im Admin-Bereich |

---

### v0.9.x — Januar 2026 · Member-Bereich & Admin-Neugestaltung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 0.9.9 | 🔴 fix | Admin | Kritische Sicherheits-Fixes – groups.php, subscriptions.php, theme-editor.php |
| 0.9.8 | 🔵 docs | Admin | README.md aktualisiert und ADMIN-FILESTRUCTURE.md zur vollständigen Dokumentation erstellt |
| 0.9.7 | 🔴 fix | Subscriptions | Redundante statusBadges in subscription-view entfernt |
| 0.9.6 | 🔴 fix | Member | Critical Bug Fixes: Method-Visibility, Config-Loading, XSS, Escaping |
| 0.9.5 | 🟢 feat | Member | Member-Profil, Security, Subscription und Datenschutz-Views und Controller hinzugefügt |
| 0.9.4 | 🟢 feat | Subscriptions | Subscription-Management Admin-Seite mit Plan-Erstellung und Zuweisung |
| 0.9.3 | 🟢 feat | Admin | Updates-, Backup- und Tracking-Services hinzugefügt |
| 0.9.2 | 🟢 feat | Admin | Backup-Management-Seite mit Backup-Funktionalitäten implementiert |
| 0.9.1 | 🟢 feat | Admin | Komplett neuer Admin-Bereich – Modern & Friendly |
| 0.9.0 | 🔴 fix | Assets | CSS/JS-Pfade auf absolute Server-Root-Pfade geändert + Test-Datei |

---

### v0.8.x — Januar 2026 · Sicherheits-Patches & Dashboard

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 0.8.9 | 🔴 fix | Admin | Admin-CSS- und JS-Pfade korrigiert (global → admin/assets) |
| 0.8.8 | 🟢 feat | Dashboard | Dashboard mit moderner AJAX-Architektur ersetzt; DashboardService-Datenbankfehler behoben |
| 0.8.7 | 🟢 feat | Cache | Umfassende Cache-Clearing-Funktion implementiert |
| 0.8.6 | 🔴 fix | Services | Service-Fehler behoben; fehlende `use CMS\Security` in landing-get.php ergänzt |
| 0.8.5 | 🔴 fix | Settings | Settings-Tabelle Spaltennamen korrigiert (`setting_key/value` → `option_name/value`) |
| 0.8.4 | 🟢 feat | Database | Automatische DB-Bereinigung bei Neuinstallation implementiert |
| 0.8.3 | 🔴 fix | Install | install-schema.php HTTP 500 durch falsche Database-Methoden behoben |
| 0.8.2 | 🔴 fix | Namespaces | Namespace-Regressionen in Services und Datenbank-Schema behoben |
| 0.8.1 | 🔴 fix | Core | Session-Management in autoload.php zentralisiert; `Auth::getCurrentUser()` hinzugefügt |
| **0.8.0** | 🔴 **fix** | **Core** | **KRITISCH: 7 Sicherheitsprobleme behoben** |

---

### v0.7.x — Januar 2026 · Sicherheit, E-Mail & PWA

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 0.7.8 | 🔴 fix | Security | CORS-Konfiguration und SEO-External-Code-Embedding gesichert |
| 0.7.7 | 🔴 fix | Security | 5 kritische Sicherheitsprobleme im Core-System behoben |
| 0.7.6 | 🟢 feat | Admin | Phase 1.1: Admin-Core-Migration – Admin.php mit erweiterten Features erstellt |
| 0.7.5 | 🟢 feat | Core | Phase 1.3: Job-Queue-System mit Scheduling, Worker-Management und Monitoring |
| 0.7.4 | 🟢 feat | Email | Phase 1.2: E-Mail-System mit Templates, Queue und Tracking vollständig implementiert |
| 0.7.3 | 🔴 fix | Security | SQL-Injection- und Credential-Exposure-Schwachstellen behoben |
| 0.7.2 | 🟢 feat | Cache | LiteSpeed-Cache-Integration und Performance-Optimierungen implementiert |
| 0.7.1 | 🟢 feat | PWA | Phase 1.5 PWA-Support implementiert – Phase 1 Implementierung 100 % abgeschlossen |
| **0.7.0** | 🟢 feat | Security | Phase 1.4 Sicherheits-Enhancements: MFA, OAuth, Social Login, Intrusion Detection, GDPR |

---

### v0.6.x — Januar 2026 · Bugfixes, Bookings & Multi-Tenancy

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 0.6.9 | 🟢 feat | Core | Multi-Tenancy-Foundation (Tenant.php) implementiert – Phase 2 Core-Start |
| 0.6.8 | 🟠 perf | Bookings | Datenbankindex-Optimierung für 75 % Abfrage-Performance-Verbesserung |
| 0.6.7 | 🟢 feat | Bookings | Konflikt-Erkennung mit Pufferzeiten, Urlaubssperrung und Concurrency-Limits erweitert |
| 0.6.6 | 🔴 fix | Admin | Admin-Panel Plugin-Management gefixt; Subdirectory-Support hinzugefügt |
| 0.6.5 | 🔴 fix | Database | Merge-Konflikte, Schema-Doppelpräfix und Konfig-Struktur behoben |
| 0.6.4 | 🔴 fix | Core | Fehlende Helper-Funktionen ergänzt: `has_action`, `has_filter`, `trailingslashit` |
| 0.6.3 | 🔴 fix | Database | Schema.sql bereinigt: Plugin-Tabellen entfernt, cms_users-Felder korrigiert |
| 0.6.2 | 🟡 refactor | Core | Modulare Architektur: index.php von 258 auf 72 Zeilen reduziert |
| 0.6.1 | 🔴 fix | Database | Datenbank-Prefix-Doppelpräfix-Bugs im gesamten Codebase behoben |
| 0.6.0 | 🔴 fix | Core | Kritische Routing- und Datenbank-Prefix-Bugs im CMS-Core behoben |

---

### v0.5.x — Januar 2026 · CMSv2 Initial · Interner Release

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 0.5.9 | 🔵 docs | Docs | ADMIN-GUIDE.md in `doc/admin/`-Unterverzeichnis reorganisiert |
| 0.5.8 | 🔵 docs | Admin | Umfassende ADMIN-GUIDE.md + Security/Performance-Admin-Seiten erstellt |
| 0.5.7 | 🟢 feat | Core | PluginManager: getActivePlugins angepasst; getCurrentTheme; time_ago erweitert; clear-cache.php |
| 0.5.6 | 🟢 feat | Admin | System-Status-Seite hinzugefügt; User-Erstellungsformular verbessert |
| 0.5.5 | 🟢 feat | Admin | User-Management mit CRUD-Operationen, Rollenverwaltung und Bulk-Aktionen |
| 0.5.4 | 🟢 feat | Admin | Vollständiger Admin-Bereich implementiert |
| 0.5.3 | 🔵 docs | Docs | Vollständige Dokumentation für CMS365-Phasen und Security-Audit hinzugefügt |
| 0.5.2 | 🟢 feat | Security | Security-Layer implementiert; 5 kritische Sicherheitsprobleme im Core behoben |
| 0.5.1 | 🟢 feat | Core | Install.php, Updater.php und erweitertes index.php mit Full-Routing hinzugefügt |
| **0.5.0** | 🟢 feat | **Core** | **CMSv2 Initial: Core-System mit Hooks, Datenbank, Auth und Routing implementiert** |

---

> *CMSv1 (0.1.xx – 0.4.99) – Interne Entwicklungsphase 2024-2025, nicht öffentlich verfügbar*
