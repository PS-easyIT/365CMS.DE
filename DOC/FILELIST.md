# 365CMS – FILELIST

> Stand: 2026-04-07  
> Zweck: aktuelle, entwicklerfreundliche Strukturübersicht des Repositories, der produktiven Runtime und der wichtigsten Wartungsflächen

Diese Datei ist die **lesbare Strukturkarte** von 365CMS. Sie ergänzt:

- `DOC/DEVLIST.md` als technische Tiefenreferenz
- `DOC/CMSFILESTRUCTUR.md` als ausführlichere Struktur-/Audit-Dokumentation
- `DOC/_cms_inventory_current.txt` als verifizierte Inventarquelle für den dokumentierten Prüfscope

Der Fokus dieser Datei liegt auf drei Dingen:

1. **Orientierung** – wo liegt was?
2. **Runtime-Wahrheit** – was wird vom laufenden CMS tatsächlich verwendet?
3. **Wartungswirkung** – welche Pfade sind bei Änderungen wirklich relevant?

---

## Inhaltsverzeichnis

- [1. Gesamtbild des Repositories](#1-gesamtbild-des-repositories)
- [2. Produktive Kernstruktur unter `CMS/`](#2-produktive-kernstruktur-unter-cms)
- [3. Inventarsicht des geprüften Runtime-Scopes](#3-inventarsicht-des-geprüften-runtime-scopes)
- [4. `CMS/config/` – Konfiguration](#4-cmsconfig--konfiguration)
- [5. `CMS/core/` – technischer Kern](#5-cmscore--technischer-kern)
- [6. `CMS/admin/` – Backend-Struktur](#6-cmsadmin--backend-struktur)
- [7. `CMS/assets/` – produktive Frontend-/Admin-Assets](#7-cmsassets--produktive-frontend-admin-assets)
- [8. `CMS/member/` – Benutzerbereich](#8-cmsmember--benutzerbereich)
- [9. `CMS/plugins/` – produktiv geladene Plugins](#9-cmsplugins--produktiv-geladene-plugins)
- [10. `CMS/themes/` – produktiv geladene Themes](#10-cmsthemes--produktiv-geladene-themes)
- [11. Weitere Runtime-Bereiche unter `CMS/`](#11-weitere-runtime-bereiche-unter-cms)
- [12. `DOC/` – Dokumentationsbaum](#12-doc--dokumentationsbaum)
- [13. Externe, aber angrenzende Repositories im Workspace](#13-externe-aber-angrenzende-repositories-im-workspace)
- [14. Schnelle Orientierung nach Aufgabenart](#14-schnelle-orientierung-nach-aufgabenart)
- [15. Kurzfazit](#15-kurzfazit)

---

## 1. Gesamtbild des Repositories

### 1.1 Workspace- und Projektkontext

Im aktuellen Workspace sind vor allem drei Repositories relevant:

- `365CMS.DE/` – Hauptrepository und **produktive Laufzeitbasis**
- `365CMS.DE-THEME/` – separates Theme-Quellrepository
- `365CMS.DE-PLUGINS/` – separates Plugin-Quellrepository

**Wichtig:** Die laufende Runtime des CMS lädt Themes und Plugins **nicht direkt** aus den separaten Quell-Repositories, sondern ausschließlich aus:

- `365CMS.DE/CMS/themes/`
- `365CMS.DE/CMS/plugins/`

Damit gilt für jede technische Analyse:

- Quellpflege kann in separaten Repositories stattfinden,
- die **kanonische Runtime-Wahrheit** liegt aber unter `365CMS.DE/CMS/`.

### 1.2 Aktuell verifizierte Top-Level-Struktur von `365CMS.DE/`

| Pfad | Typ | Zweck |
|---|---|---|
| `ASSETS/` | Quell-/Vendor-Kontext | externer Asset-/Bibliotheksbestand außerhalb der Runtime |
| `Changelog.md` | Dokumentation | Release- und Änderungsverlauf |
| `CMS/` | Runtime | **produktive Anwendungslaufzeit** |
| `DOC/` | Dokumentation | Projektwissen, Audits, Struktur- und Betriebshandbücher |
| `README.md` | Dokumentation | Einstieg und Projektüberblick |

### 1.3 Praktische Bedeutung der Root-Struktur

Die wichtigste Unterscheidung auf Root-Ebene lautet:

- `CMS/` = das, was das System tatsächlich bootet
- `DOC/` = das, was das System erklärt
- `ASSETS/`, Theme-Repo, Plugin-Repo = Entwicklungs-, Pflege- oder Quellkontext, aber **nicht automatisch Runtime**

Wer diese Trennung sauber im Kopf behält, vermeidet viele typische Debugging-Irrtümer.

### 1.4 Welche Root-Bereiche im Alltag am häufigsten relevant werden

Für die tägliche Arbeit sind typischerweise diese Root-Pfade am wichtigsten:

- `CMS/` bei jeder echten Laufzeitänderung
- `DOC/` bei Struktur-, Audit- und Entwicklerdokumentation
- `Changelog.md` bei Regressions- oder Release-Abgleich
- `ASSETS/` nur dann, wenn bewusst am Quell-/Vendor-Kontext außerhalb der Runtime gearbeitet wird

Die wichtigste Arbeitsregel lautet daher: **Immer zuerst klären, ob man gerade Runtime, Doku oder Quellkontext bearbeitet.**

---

## 2. Produktive Kernstruktur unter `CMS/`

### 2.1 Root von `CMS/`

| Pfad | Zweck |
|---|---|
| `.htaccess` | Rewrite-, Schutz- und Webserverregeln |
| `admin/` | Backend-Einstieg, Module, Views, Partials |
| `assets/` | produktive Styles, Scripts, Runtime-Bibliotheken |
| `cache/` | Cache- und Laufzeitpuffer |
| `config/` | zentrale Konfiguration |
| `config.php` | Legacy-/Stub-Einstieg für Konfigkontext |
| `core/` | Bootstrap, Security, Routing, Services, Manager |
| `cron.php` | Einstieg für Cron-/Hintergrundverarbeitung |
| `db/` | DB-nahe Artefakte / SQL-Kontext |
| `includes/` | globale Hilfsfunktionen und Kompatibilität |
| `index.php` | zentraler Public-Entry |
| `install/` | Installer-Services und Install-Views |
| `install.php` | Installer-Einstieg |
| `lang/` | Sprachdateien |
| `logs/` | Schutz-/Placeholder-Struktur für Webkontext |
| `member/` | Member-Bereich |
| `orders.php` | Bestell-/Legacy-Endpunkt |
| `plugins/` | produktiv geladene Plugins |
| `themes/` | produktiv geladene Themes |
| `update.json` | Release-/Update-Metadaten |
| `uploads/` | Upload-Zielstruktur |
| `vendor/` | zusätzliche Runtime-Abhängigkeiten außerhalb von `assets/` |
| `views/` | ergänzende View-Strukturen außerhalb von `admin/` und `member/` |

### 2.2 Warum die `CMS/`-Root so wichtig ist

Die `CMS/`-Struktur bildet die **eigentliche Betriebsoberfläche** des Projekts. Änderungen in diesem Bereich haben unmittelbar Einfluss auf:

- Routing und Erreichbarkeit
- Auth und Sessions
- Admin- und Member-Verhalten
- Asset-Laden und UI
- Plugin-/Theme-Runtime
- Logging, Diagnose, Cron und Updates

### 2.3 Typische Analyseperspektive auf `CMS/`

Wenn eine Aufgabe unklar ist, hilft meist diese Einteilung:

- **Entry-Dateien**: `index.php`, `cron.php`, `install.php`, `orders.php`
- **Regeln/Parameter**: `config/`
- **Framework-Kern**: `core/`
- **Bedienoberflächen**: `admin/`, `member/`, `views/`
- **Erweiterungen**: `plugins/`, `themes/`
- **UI- und Runtime-Assets**: `assets/`
- **Datei-/Speicherkontext**: `uploads/`, `cache/`, `logs/`

### 2.4 Häufige Runtime-Hotspots innerhalb von `CMS/`

Bestimmte Pfade tauchen überdurchschnittlich oft bei Debugging, Audits oder Regressionen auf:

- `core/Bootstrap.php`
- `core/Security.php`
- `core/ThemeManager.php`
- `core/PluginManager.php`
- `admin/`
- `assets/js/`
- `member/includes/class-member-controller.php`

Diese Pfade sind besonders wirkungsstark, weil sie viele andere Teile des Systems indirekt beeinflussen.

---

## 3. Inventarsicht des geprüften Runtime-Scopes

Die Datei `DOC/_cms_inventory_current.txt` dokumentiert einen verifizierten Prüfscope für Teile der Runtime.

### 3.1 Bestandszahlen des dokumentierten Prüfscope

| Bereich | Dateien |
|---|---:|
| Root-Entrypoints | 7 |
| `assets/css/` | 9 |
| `assets/js/` | 30 |
| `admin/` | 243 |
| `config/` | 4 |
| `core/` | 118 |
| `includes/` | 10 |
| `lang/` | 2 |
| `logs/` | 2 |
| `member/` | 17 |
| `plugins/` | 14 |
| `uploads/` | 2 |
| **Gesamt** | **467** |

### 3.2 Bedeutung dieses Prüfscope

Diese Zahlen sind besonders nützlich für:

- Audit-Vergleiche
- Scope-Abschätzungen vor Refactorings
- Strukturänderungs-Tracking
- Identifikation ungewöhnlicher Wachstumszonen

### 3.3 Wichtiger Hinweis zum Scope

Der Inventarscope ist hilfreich, aber nicht mit einer vollständigen semantischen Architekturkarte gleichzusetzen. Einige Runtime-Ordner wie `vendor/`, `views/` oder die tiefere Asset-Bibliotheksfläche haben eigene Relevanz, auch wenn sie nicht in jeder kompakten Zählung prominent auftauchen.

### 3.4 Inventar, FILELIST und DEVLIST spielen unterschiedliche Rollen

Die drei Dokumenttypen im Zusammenspiel:

- `DOC/_cms_inventory_current.txt` → maschinennahe Bestandsliste
- `DOC/FILELIST.md` → lesbare Strukturkarte
- `DOC/DEVLIST.md` → technische Tiefen- und Betriebsreferenz

Gerade bei größeren Umbauten sollte man diese drei Perspektiven zusammendenken statt nur eine davon zu benutzen.

---

## 4. `CMS/config/` – Konfiguration

### 4.1 Verifizierte Dateien unter `CMS/config/`

| Pfad | Zweck |
|---|---|
| `config/.htaccess` | Schutz der Konfigurationsdateien vor direktem Webzugriff |
| `config/app.php` | zentrale App-Konfiguration für Pfade, Security, DB, SMTP, LDAP, JWT, Runtime-Flags |
| `config/media-meta.json` | Medien-Metadaten |
| `config/media-settings.json` | Medien-Konfiguration |

### 4.2 Strukturrolle von `config/app.php`

`config/app.php` definiert zentrale Konstanten und Betriebsparameter wie:

- `ABSPATH`
- `CORE_PATH`
- `THEME_PATH`
- `PLUGIN_PATH`
- `UPLOAD_PATH`
- `ASSETS_PATH`
- Logpfade
- Security-/Session-Parameter
- Login-/Rate-Limit-Parameter
- Mail-/LDAP-/JWT-Kontext
- `DEFAULT_THEME`

### 4.3 Warum `config/` ein Hochrisiko-Bereich ist

Fehler in `config/` wirken selten lokal. Sie beeinflussen oft gleichzeitig:

- Bootstrap-Verhalten
- Routing und HTTPS-Kontext
- Sessions und Security Headers
- Theme- und Plugin-Pfade
- Mail-, LDAP- und JWT-Funktion
- Logging und Diagnose

### 4.4 Typische Nachbarbereiche von `config/`

Wenn `config/` geändert wird, sind meist auch diese Bereiche mitzudenken:

- `core/Bootstrap.php`
- `core/Security.php`
- `core/Auth.php`
- `core/Services/*` mit Mail-, Media- oder Integrationsbezug
- `admin/`-Seiten für System-, Mail-, Security- oder Performance-Einstellungen

---

## 5. `CMS/core/` – technischer Kern

### 5.1 Zentrale Root-Klassen in `core/`

| Datei | Zweck |
|---|---|
| `Api.php` | API-nahe Kernlogik |
| `AuditLogger.php` | Audit-Logging |
| `Auth.php` | Authentifizierung |
| `autoload.php` | Core-Autoloading |
| `Bootstrap.php` | Systemstart und Initialisierung |
| `CacheManager.php` | Cache-Schicht |
| `Container.php` | Dependency Injection / Service-Container |
| `Database.php` | PDO-/DB-Zugriff |
| `Debug.php` | Debug-/Profiling-Hilfen |
| `Hooks.php` | Event-/Hook-System |
| `Json.php` | JSON-Helfer |
| `Logger.php` | Application Logging |
| `MigrationManager.php` | inkrementelle Migrationen |
| `PageManager.php` | Seitennahe Kernlogik |
| `PluginManager.php` | Laden/Aktivieren von Plugins |
| `Router.php` | Routing-Zentrale |
| `SchemaManager.php` | Schema-Initialisierung |
| `Security.php` | CSRF, Sessions, Security Headers |
| `SubscriptionManager.php` | Subscription-/Abo-Kontext |
| `TableOfContents.php` | Inhaltsverzeichnis-Logik |
| `ThemeManager.php` | Theme-Laden, Rendering, Menüs |
| `Totp.php` | TOTP-Unterstützung |
| `VendorRegistry.php` | Registry-/Abhängigkeitskontext |
| `Version.php` | Versionsverwaltung |
| `WP_Error.php` | WordPress-kompatible Fehlerstruktur |

### 5.2 Wichtige Unterordner in `core/`

| Pfad | Zweck |
|---|---|
| `core/Auth/` | AuthManager, LDAP, MFA, Passkeys |
| `core/Contracts/` | Interfaces für Cache, DB, Logger u. a. |
| `core/Http/` | HTTP-Client / Transportlogik |
| `core/Member/` | Member-nahe Kernregistrierung |
| `core/Routing/` | Admin-, Public-, Member-, API- und Theme-Router |
| `core/Services/` | Fachservices des Systems |

### 5.3 `core/Auth/` im Überblick

| Datei/Ordner | Zweck |
|---|---|
| `AuthManager.php` | zentrale Auth-Steuerung |
| `LDAP/LdapAuthProvider.php` | LDAP-Anbindung |
| `MFA/BackupCodesManager.php` | MFA-Backupcodes |
| `MFA/TotpAdapter.php` | TOTP-Integration |
| `Passkey/WebAuthnAdapter.php` | Passkey-/WebAuthn-Unterstützung |

### 5.4 `core/Routing/` im Überblick

| Datei | Zweck |
|---|---|
| `AdminRouter.php` | Admin-Routen |
| `ApiRouter.php` | API-Routen |
| `MemberRouter.php` | Member-Routen |
| `PublicRouter.php` | öffentliche Routen inkl. Core-Auth-Seiten |
| `ThemeArchiveRepository.php` | Archiv-/Theme-bezogene Auswahlhilfe |
| `ThemeRouter.php` | Template-Auswahl |

### 5.5 `core/Services/` – große Servicefamilien

| Bereich | Beispiele |
|---|---|
| Mail & Kommunikation | `MailService`, `MailQueueService`, `MailLogService`, `AzureMailTokenProvider`, `GraphApiService` |
| SEO & Sichtbarkeit | `SEOService`, `SeoAnalysisService`, `RedirectService`, `IndexingService`, `SitemapService` |
| Suche | `SearchService` |
| Medien & Upload | `MediaService`, `MediaDeliveryService`, `FileUploadService`, `ImageService` |
| Editor & Inhalt | `EditorJsService`, `EditorJsRenderer`, `EditorService`, `CommentService` |
| Landing | `LandingPageService` plus mehrere `Landing*`-Services |
| Performance & Betrieb | `BackupService`, `OpcacheWarmupService`, `CoreWebVitalsService`, `SystemService`, `StatusService`, `UpdateService` |
| Tracking | `AnalyticsService`, `TrackingService`, `FeatureUsageService` |
| Member & Messaging | `MemberService`, `MessageService` |
| PDF & Ausleitung | `PdfService` |
| Sanitizing & Schutz | `PurifierService`, `EditorJsSanitizer`, `EditorJsRequestGuard` |

### 5.6 Wichtige Unterstrukturen innerhalb von `core/Services/`

#### Editor.js-Unterstruktur

| Pfad | Zweck |
|---|---|
| `core/Services/EditorJs/EditorJsAssetService.php` | Asset-/Toolbar-Kontext |
| `core/Services/EditorJs/EditorJsImageLibraryService.php` | Bilderbibliothek |
| `core/Services/EditorJs/EditorJsMediaService.php` | Medienanbindung |
| `core/Services/EditorJs/EditorJsRemoteMediaService.php` | Remote-Medienpfad |
| `core/Services/EditorJs/EditorJsRequestGuard.php` | Request-Schutz |
| `core/Services/EditorJs/EditorJsSanitizer.php` | Sanitizing |
| `core/Services/EditorJs/EditorJsUploadService.php` | Upload-Handling |

#### Landing-Unterstruktur

| Pfad | Zweck |
|---|---|
| `LandingDefaultsProvider.php` | Standardwerte |
| `LandingFeatureService.php` | Feature-Verwaltung |
| `LandingHeaderService.php` | Header-/Topbereich |
| `LandingPluginService.php` | Plugin-Integration |
| `LandingRepository.php` | Persistenz-/Datenzugriff |
| `LandingSanitizer.php` | Sanitizing |
| `LandingSectionProfileService.php` | Abschnittsprofile |
| `LandingSectionService.php` | Abschnittslogik |

#### SEO-Unterstruktur

| Pfad | Zweck |
|---|---|
| `SEO/SeoAnalyticsRenderer.php` | Analytics-Rendering |
| `SEO/SeoAuditService.php` | SEO-Prüfung |
| `SEO/SeoHeadRenderer.php` | Head-Ausgabe |
| `SEO/SeoMetaRepository.php` | Meta-Datenzugriff |
| `SEO/SeoMetaService.php` | Meta-Fachlogik |
| `SEO/SeoSchemaRenderer.php` | Schema.org-Ausgabe |
| `SEO/SeoSettingsStore.php` | SEO-Settings |
| `SEO/SeoSitemapService.php` | Sitemap-Generierung |

#### Site-Table-Unterstruktur

| Pfad | Zweck |
|---|---|
| `SiteTable/SiteTableDisplaySettings.php` | Anzeigeeinstellungen |
| `SiteTable/SiteTableHubRenderer.php` | Hub-Rendering |
| `SiteTable/SiteTableRepository.php` | Datenzugriff |
| `SiteTable/SiteTableTableRenderer.php` | Tabellen-Rendering |
| `SiteTable/SiteTableTemplateRegistry.php` | Template-Registry |

### 5.7 Warum `core/` die wichtigste Leseschicht ist

Wenn man verstehen will, **wie** 365CMS arbeitet, führt fast immer ein Weg über `core/`. Wenn man verstehen will, **wo** etwas sichtbar wird, kommen meist `admin/`, `member/`, `themes/` oder `assets/` hinzu.

### 5.8 Typische Lese-Reihenfolge in `core/`

Bei unbekannten Problemen ist diese Reihenfolge oft hilfreich:

1. `Bootstrap.php`
2. betroffener Manager oder Router
3. betroffener Service
4. zugehörige Auth-/Security-/Hook-Komponente
5. erst danach View-, Modul- oder Asset-Schicht

So beginnt man mit Ursache statt mit Symptomen.

---

## 6. `CMS/admin/` – Backend-Struktur

### 6.1 Admin-Einstiegsdateien unter `CMS/admin/*.php`

Der Admin-Bereich enthält zahlreiche direkte Einstiege. Typische Gruppen sind:

| Bereich | Beispiele |
|---|---|
| Analytics & SEO | `analytics.php`, `seo-dashboard.php`, `seo-audit.php`, `redirect-manager.php`, `not-found-monitor.php`, `seo-meta.php`, `seo-schema.php`, `seo-sitemap.php`, `seo-social.php`, `seo-technical.php` |
| Security | `security-audit.php`, `firewall.php`, `antispam.php`, `error-report.php` |
| Content | `pages.php`, `posts.php`, `comments.php`, `post-categories.php`, `post-tags.php`, `landing-page.php`, `hub-sites.php` |
| Themes | `themes.php`, `theme-editor.php`, `theme-explorer.php`, `theme-marketplace.php`, `theme-settings.php`, `design-settings.php`, `font-manager.php` |
| Plugins | `plugins.php`, `plugin-marketplace.php`, `modules.php` |
| User | `users.php`, `groups.php`, `roles.php`, `user-settings.php` |
| System | `updates.php`, `support.php`, `system-info.php`, `documentation.php`, `diagnose.php`, `mail-settings.php`, `backups.php`, `info.php` |
| Performance | `performance.php`, `performance-cache.php`, `performance-database.php`, `performance-media.php`, `performance-page.php`, `performance-sessions.php`, `performance-settings.php` |
| Monitoring | `monitor-cron-status.php`, `monitor-disk-usage.php`, `monitor-email-alerts.php`, `monitor-health-check.php`, `monitor-response-time.php`, `monitor-scheduled-tasks.php`, `system-monitor-page.php` |
| Member-Konfiguration | `member-dashboard.php` plus spezialisierte Dashboard-Unterseiten |
| Recht & DSGVO | `cookie-manager.php`, `data-requests.php`, `deletion-requests.php`, `privacy-requests.php`, `legal-sites.php` |
| Tabellen & Navigation | `menu-editor.php`, `site-tables.php`, `table-of-contents.php` |
| Commerce/Subscriptions | `orders.php`, `packages.php`, `subscription-settings.php` |

### 6.2 `admin/modules/` – Domänenlogik des Backends

| Modulordner | Kernzweck |
|---|---|
| `comments/` | Kommentarverwaltung |
| `dashboard/` | Dashboard-Logik |
| `hub/` | Hub-Sites und Template-Profile |
| `landing/` | Landing-Page-Logik |
| `legal/` | Cookies, Datenschutz, Lösch- und Auskunftsprozesse |
| `media/` | Medienverwaltung |
| `member/` | Member-Dashboard-Konfiguration |
| `menus/` | Menü-Editor |
| `pages/` | Seitenverwaltung |
| `plugins/` | Plugin-Verwaltung und Marketplace |
| `posts/` | Beitragsverwaltung |
| `security/` | Audit, Firewall, Antispam |
| `seo/` | SEO-Dashboard, Redirects, Analytics, Performance |
| `settings/` | allgemeine Einstellungen |
| `subscriptions/` | Orders, Packages, Subscription Settings |
| `system/` | Doku, Updates, Mail-Settings, Support, Module |
| `tables/` | Tabellen-/Site-Tabellenlogik |
| `themes/` | Theme-Editor, Fonts, Design, Marketplace |
| `toc/` | Inhaltsverzeichnis-/ToC-Funktion |
| `users/` | Benutzer, Gruppen, Rollen, User-Settings |

### 6.3 `admin/views/` – gerenderte Oberflächen

Views sind nach fachlichen Bereichen gruppiert:

- `comments/`
- `dashboard/`
- `hub/`
- `landing/`
- `legal/`
- `media/`
- `member/`
- `menus/`
- `pages/`
- `performance/`
- `plugins/`
- `posts/`
- `security/`
- `seo/`
- `settings/`
- `subscriptions/`
- `system/`
- `tables/`
- `themes/`
- `toc/`
- `users/`
- `partials/`

Besonders wichtige Teilviews und Partials sind u. a.:

- `admin/views/partials/flash-alert.php`
- `admin/views/partials/featured-image-picker.php`
- `admin/views/partials/content-advanced-seo-panel.php`
- `admin/views/partials/content-preview-card.php`
- `admin/views/partials/content-readability-card.php`
- `admin/views/partials/content-seo-score-panel.php`

### 6.4 `admin/partials/` – wiederverwendbare Shells

| Datei | Zweck |
|---|---|
| `header.php` | Admin-Header |
| `footer.php` | Admin-Footer |
| `sidebar.php` | Admin-Navigation |
| `post-action-shell.php` | Post-/Form-Aktionsshell |
| `redirect-alias-shell.php` | Redirect-nahe Shell |
| `section-page-shell.php` | zentrale Section-/Page-Shell |

### 6.5 Warum `admin/` strukturell wichtig ist

Der Admin-Bereich ist einer der größten Wartungsräume des Systems. Er enthält:

- viele Einzelseiten,
- viele gemeinsam genutzte Verträge,
- zahlreiche Asset-abhängige Interaktionen,
- und einen hohen Anteil an fachkritischen Mutationen.

Deshalb lohnt sich in `admin/` fast immer der Blick auf **Entry + Modul + View + Asset** zusammen.

### 6.6 Besonders häufig berührte Admin-Zonen

In der Praxis besonders relevant und oft regressionsanfällig sind:

- `admin/views/posts/`
- `admin/views/users/`
- `admin/views/seo/`
- `admin/views/themes/`
- `admin/modules/seo/`
- `admin/modules/themes/`
- `admin/modules/users/`
- `admin/partials/section-page-shell.php`

Diese Zonen profitieren besonders von sauberer Dokumentation in der FILELIST, weil sie sowohl strukturell groß als auch fachlich kritisch sind.

---

## 7. `CMS/assets/` – produktive Frontend-/Admin-Assets

Dies ist der wichtigste Abschnitt dieser FILELIST für UI-, Runtime- und Bibliotheksverständnis.

### 7.1 Verifizierte Top-Level-Struktur von `CMS/assets/`

| Pfad | Typ | Zweck |
|---|---|---|
| `autoload.php` | Datei | Runtime-Autoloading für Asset-/Vendor-Kontext |
| `Carbon/` | Bibliothek | Datum-/Zeit-Helfer |
| `css/` | Ordner | produktive CSS-Dateien |
| `editorjs/` | Bibliothek | Editor.js-Runtime |
| `gridjs/` | Bibliothek | Tabellen-/Grid-Bibliothek |
| `htmlpurifier/` | Bibliothek | HTML-Sanitizing |
| `images/` | Ordner | Bilder, UI-Grafiken, Asset-nahe Medien |
| `js/` | Ordner | produktive JavaScript-Dateien |
| `ldaprecord/` | Bibliothek | LDAP-nahe Funktionalität |
| `mailer/` | Bibliothek | Mailer-Komponenten |
| `melbahja-seo/` | Bibliothek | SEO-Helfer / Drittkomponente |
| `mime/` | Bibliothek | MIME-Verarbeitung |
| `msgraph/` | Bibliothek | Microsoft-Graph-Kontext |
| `photoswipe/` | Bibliothek | Lightbox-/Galerie-Bibliothek |
| `php-jwt/` | Bibliothek | JWT-Unterstützung |
| `psr/` | Bibliothek | PSR-nahe Interfaces / Infrastruktur |
| `simplepielibrary/` | Bibliothek | Feed-/Syndication-Kontext |
| `suneditor/` | Bibliothek | WYSIWYG-Editor |
| `tabler/` | Bibliothek | Admin-UI-/Komponentenbasis |
| `tntsearchhelper/` | Bibliothek | Hilfen für Search-/Index-Kontext |
| `tntsearchsrc/` | Bibliothek | TNTSearch-Quellbestand |
| `translation/` | Bibliothek | Übersetzungsunterstützung |
| `twofactorauth/` | Bibliothek | 2FA-/TOTP-Kontext |
| `webauthn/` | Bibliothek | Passkey-/WebAuthn-Unterstützung |

### 7.1.1 Was in `CMS/assets/` nicht übersehen werden darf

Der Ordner bündelt drei Ebenen zugleich:

- First-Party-Assets (`css/`, `js/`, `images/`)
- direkt gebündelte Runtime-Libraries
- Infrastruktur für Suche, Sicherheit, Mail, Editoren und UI-Komponenten

Genau deshalb ist `CMS/assets/` nicht nur eine Frontend-Ablage, sondern eine echte Laufzeit- und Integrationsfläche.

### 7.2 `CMS/assets/css/` – produktive CSS-Dateien

| Datei | Zweck |
|---|---|
| `assets/css/admin.css` | allgemeines Admin-Styling |
| `assets/css/admin-tabler.css` | Admin-/Tabler-Anpassungen |
| `assets/css/admin-hub-site-edit.css` | Styling für Hub-Site-Editor |
| `assets/css/admin-hub-template-edit.css` | Styling für Hub-Template-Bearbeitung |
| `assets/css/admin-hub-template-editor.css` | weitere Hub-Template-Editor-Stile |
| `assets/css/cms-cookie-consent.css` | Consent-/Cookie-Banner |
| `assets/css/hub-sites.css` | Hub-Site-Darstellung |
| `assets/css/main.css` | allgemeines Hauptstyling / Frontend-Basis |
| `assets/css/member-dashboard.css` | Member-Dashboard-Styling |

### 7.2.1 CSS-Struktur nach Einsatzbereichen

Die vorhandenen CSS-Dateien lassen sich grob so lesen:

- **Admin-Grundlayout**: `admin.css`, `admin-tabler.css`
- **Hub-/Landing-nahe Spezialflächen**: `admin-hub-*`, `hub-sites.css`
- **Frontend-/Allgemeinbasis**: `main.css`
- **Member-Bereich**: `member-dashboard.css`
- **Recht/Consent**: `cms-cookie-consent.css`

### 7.3 `CMS/assets/js/` – produktive JavaScript-Dateien

| Datei | Zweck |
|---|---|
| `assets/js/admin.js` | globales Admin-JavaScript |
| `assets/js/admin-comments.js` | Kommentarverwaltung |
| `assets/js/admin-content-editor.js` | Live-Content-Editor-Interaktion |
| `assets/js/admin-cookie-manager.js` | Consent-/Cookie-Verwaltung |
| `assets/js/admin-data-requests.js` | Datenschutzanfragen |
| `assets/js/admin-font-manager.js` | Font-Manager |
| `assets/js/admin-grid.js` | Tabellen-/Grid-Hilfen |
| `assets/js/admin-hub-site-edit.js` | Hub-Site-Editor |
| `assets/js/admin-hub-sites.js` | Hub-Sites |
| `assets/js/admin-hub-template-edit.js` | Hub-Template-Bearbeitung |
| `assets/js/admin-hub-template-editor.js` | erweiterte Hub-Template-Funktionen |
| `assets/js/admin-legal-sites.js` | Rechtsseiten-Interaktion |
| `assets/js/admin-media-integrations.js` | Medienintegration |
| `assets/js/admin-menu-editor.js` | Menü-Editor |
| `assets/js/admin-pages.js` | Seitenverwaltung |
| `assets/js/admin-plugin-marketplace.js` | Plugin-Marketplace |
| `assets/js/admin-plugins.js` | Plugin-Verwaltung |
| `assets/js/admin-seo-editor.js` | SEO-Editor |
| `assets/js/admin-seo-redirects.js` | Redirect-/404-Dialoglogik |
| `assets/js/admin-site-tables.js` | Site-Tabellenverwaltung |
| `assets/js/admin-system-cron.js` | Cron-/Systemmonitor-nahe Admin-Interaktion |
| `assets/js/admin-theme-explorer.js` | Theme-Explorer |
| `assets/js/admin-theme-marketplace.js` | Theme-Marketplace |
| `assets/js/admin-user-groups.js` | Gruppen-/User-Zuordnung |
| `assets/js/admin-users.js` | Benutzerverwaltung |
| `assets/js/cookieconsent-init.js` | Consent-Initialisierung |
| `assets/js/editor-init.js` | Editor-Initialisierung |
| `assets/js/gridjs-init.js` | Grid.js-Initialisierung |
| `assets/js/member-dashboard.js` | Member-Dashboard-Interaktion |
| `assets/js/photoswipe-init.js` | Lightbox-Initialisierung |
| `assets/js/web-vitals.js` | Web-Vitals-Erfassung |

### 7.3.1 JavaScript nach Verantwortungszonen gruppiert

Die produktiven Scripts sind funktional breit verteilt:

- **globale Admin-Basis**: `admin.js`
- **Content-/Editor-Fläche**: `admin-content-editor.js`, `editor-init.js`
- **SEO-/Redirect-Fläche**: `admin-seo-editor.js`, `admin-seo-redirects.js`
- **Benutzer-/Rollen-/Gruppenfläche**: `admin-users.js`, `admin-user-groups.js`
- **Theme-/Plugin-Fläche**: `admin-theme-*`, `admin-plugin-*`
- **Hub-/Landing-Fläche**: `admin-hub-*`
- **Datenschutz-/Legal-Fläche**: `admin-cookie-manager.js`, `admin-data-requests.js`, `admin-legal-sites.js`
- **Monitoring-/Betriebsfläche**: `admin-system-cron.js`, `web-vitals.js`
- **Member-/Frontend-Ergänzungen**: `member-dashboard.js`, `photoswipe-init.js`, `cookieconsent-init.js`

### 7.4 `CMS/assets/images/` – Runtime-Bildbestand

Aktuelle Beispiele:

- `365CMS-DASHBOARD-Admin-100px.png`
- `365CMS-DASHBOARD-Member-100px.png`
- `LOGO_365CMS-100px.png`
- `LOGO_365CMS-120px.png`
- `LOGO_365CMS-125px.png`
- `LOGO_365CMS-150px.png`
- `LOGO_365CMS-75px.png`
- `LOGO_365CMS-onlyText-125px.png`
- `LOGO_365CMS-onlyText-160px.png`
- `LOGO_365CMS-onlyText-200px.png`
- `LOGO_365CMS-wo_Text-150px.png`
- `LOGO_365CMS-wo_Text-50px.png`
- `LOGO_365CMS-wo_Text-75px.png`

### 7.5 Tiefere Bibliotheksstruktur in `CMS/assets/`

#### 7.5.1 `assets/editorjs/`

Verifizierte Dateien umfassen u. a.:

- `editorjs.umd.js`, `editorjs.mjs`
- Block-/Tool-Dateien wie `header.umd.js`, `paragraph.umd.js`, `table.umd.js`, `quote.umd.js`, `warning.umd.js`
- Medien- und UX-Tools wie `image.umd.js`, `image-gallery.umd.js`, `drag-drop.umd.js`, `drawing-tool.umd.js`, `undo.umd.js`
- Zusatzdateien wie `cropper-tune.css`, `cropper-tune.umd.js`

Damit liegt hier nicht nur der Editor-Kern, sondern ein ganzer produktiver Tool-Baukasten für den Blockeditor.

#### 7.5.2 `assets/gridjs/`

Aktuell verifiziert:

- `gridjs.umd.js`
- `mermaid.min.css`

Das zeigt, dass Grid-/Tabellenfunktionalität und Diagramm-/Darstellungsbezug direkt in der Runtime mitgebündelt werden.

#### 7.5.3 `assets/htmlpurifier/`

Verifiziert sind u. a.:

- `HTMLPurifier.php`
- `HTMLPurifier.auto.php`
- `HTMLPurifier.autoload.php`
- `HTMLPurifier.includes.php`
- `HTMLPurifier.safe-includes.php`
- Ordner `HTMLPurifier/`

Dieser Bereich ist sicherheitskritisch, weil er die HTML-Reinigungsbasis für mehrere Senken im System unterstützt.

#### 7.5.4 `assets/photoswipe/`

Verifiziert:

- `photoswipe.css`
- `photoswipe.esm.min.js`
- `photoswipe-lightbox.esm.min.js`

Das ist die gebündelte Galerie-/Lightbox-Runtime.

#### 7.5.5 `assets/suneditor/`

Verifizierte Unterstruktur:

- `assets/`
- `css/`
- `lang/`
- `lib/`
- `plugins/`
- `suneditor.js`
- `suneditor.min.js`
- `suneditor_build.js`
- Typdefinitionen wie `suneditor.d.ts`, `options.d.ts`

Damit ist `suneditor/` deutlich mehr als eine Einzeldatei und bringt eigene Plugin-, Sprach- und Asset-Struktur mit.

#### 7.5.6 `assets/tabler/`

Verifizierte Unterstruktur:

- `css/`
- `img/`
- `js/`

`tabler/` stellt damit die UI-Basisbibliothek für Teile des Admin-Designs bereit.

### 7.6 Asset-Gruppen nach Funktion

| Gruppe | Typische Pfade |
|---|---|
| globales Admin-UI | `admin.css`, `admin-tabler.css`, `admin.js` |
| Content-/Editor-Fläche | `admin-content-editor.js`, `editor-init.js`, `editorjs/`, `suneditor/` |
| SEO-/Redirect-Fläche | `admin-seo-editor.js`, `admin-seo-redirects.js`, `melbahja-seo/` |
| Hub-/Landing-Fläche | `admin-hub-*.js`, `admin-hub-*.css` |
| Medien-/Galerie-Fläche | `admin-media-integrations.js`, `photoswipe/`, `photoswipe-init.js`, `images/` |
| Member-Fläche | `member-dashboard.css`, `member-dashboard.js` |
| Tabellen-/Grid-Fläche | `gridjs/`, `gridjs-init.js`, `admin-grid.js` |
| Sicherheits-/Auth-nahe Bibliotheken | `htmlpurifier/`, `php-jwt/`, `twofactorauth/`, `webauthn/`, `ldaprecord/` |
| Mail-/Integrationsfläche | `mailer/`, `mime/`, `translation/`, `msgraph/` |
| Such-/Feed-Kontext | `tntsearchhelper/`, `tntsearchsrc/`, `simplepielibrary/` |

### 7.7 Asset-Bibliotheken mit besonders hoher Querschnittswirkung

Einige Bibliotheken unter `CMS/assets/` betreffen nicht nur eine Einzelfunktion, sondern mehrere Systembereiche gleichzeitig:

- `editorjs/` → Content, Admin, Sanitizing, Medien
- `htmlpurifier/` → Sicherheit, Rendering, Content-Senken
- `webauthn/` und `twofactorauth/` → Auth und Sicherheit
- `tabler/` → Admin-UI-Grundlage
- `tntsearch*` → Suche und Indexing
- `mailer/`, `mime/`, `msgraph/` → Mail- und Integrationspfade

Diese Bereiche sollte man bei Änderungen mit besonderer Vorsicht behandeln.

### 7.8 Was `CMS/assets/` praktisch bedeutet

`CMS/assets/` ist **nicht nur CSS und JS**. Dieser Ordner ist in 365CMS eine Mischzone aus:

- produktiven First-Party-Assets,
- direkt mitlaufenden Bibliotheken,
- UI-Bausteinen,
- Sicherheits- und Auth-Abhängigkeiten,
- Such-, Feed-, Mail- und Rendering-Komponenten.

Darum hat dieser Ordner gleichzeitig Relevanz für:

- Frontend
- Admin
- Member
- Security
- Performance
- Suche
- Mail
- Medien

### 7.9 Wichtige Abgrenzung

Die produktive Asset-Wahrheit liegt unter `CMS/assets/`. Das separate Root-Verzeichnis `ASSETS/` im Hauptrepo ist dagegen ein Quell-/Vendor-/Pflegekontext außerhalb der unmittelbaren Runtime.

---

## 8. `CMS/member/` – Benutzerbereich

### 8.1 Verifizierte Kernseiten

| Datei | Zweck |
|---|---|
| `dashboard.php` | Member-Startseite |
| `favorites.php` | Favoriten |
| `media.php` | persönliche Medien |
| `messages.php` | Nachrichten |
| `notifications.php` | Benachrichtigungen |
| `plugin-section.php` | Plugin-Integration im Member-Bereich |
| `privacy.php` | Datenschutz |
| `profile.php` | Profilverwaltung |
| `security.php` | Passwort, MFA, Sessions |
| `subscription.php` | Abo-/Paketkontext |

### 8.2 Ergänzende Member-Unterstruktur

| Pfad | Zweck |
|---|---|
| `member/includes/bootstrap.php` | Member-Bootstrap |
| `member/includes/class-member-controller.php` | zentrale Member-Steuerung |
| `member/partials/alerts.php` | Alerts |
| `member/partials/header.php` | Header |
| `member/partials/footer.php` | Footer |
| `member/partials/sidebar.php` | Navigation |
| `member/partials/plugin-not-found.php` | Fallback für Plugin-Sektionen |

### 8.3 Strukturelle Rolle des Member-Bereichs

`CMS/member/` ist funktional eng gekoppelt mit:

- Auth und Sessions
- Medien und Datenschutz
- Messaging
- Subscription-/Package-Logik
- Plugin-Integration im persönlichen Bereich

### 8.4 Typische Nachbarbereiche des Member-Bereichs

Wer an `CMS/member/` arbeitet, berührt häufig indirekt auch:

- `core/Routing/MemberRouter.php`
- `core/Services/MemberService.php`
- `core/Auth/`
- `assets/js/member-dashboard.js`
- `assets/css/member-dashboard.css`

---

## 9. `CMS/plugins/` – produktiv geladene Plugins

`CMS/plugins/` ist der **einzige produktive Plugin-Ladepfad**.

### 9.1 Ladelogik

Der `PluginManager` sucht Bootstrap-Dateien nach dem Muster:

- `plugins/<slug>/<slug>.php`

### 9.2 Aktuell verifizierter Runtime-Plugin-Kontext

Im dokumentierten Inventarscope ist insbesondere `cms-importer` sichtbar.

#### Verifizierte Struktur von `plugins/cms-importer/`

| Pfad | Zweck |
|---|---|
| `cms-importer.php` | Plugin-Bootstrap |
| `update.json` | Update-/Versionsmetadaten |
| `readme.txt` | Plugin-Doku |
| `admin/log.php` | Admin-Log-/Report-Seite |
| `admin/page.php` | Admin-Einstieg |
| `assets/css/importer.css` | Plugin-CSS |
| `assets/js/importer.js` | Plugin-JS |
| `includes/class-admin.php` | Admin-Logik |
| `includes/class-importer.php` | Importkern |
| `includes/class-xml-parser.php` | XML-Verarbeitung |
| `includes/trait-admin-cleanup.php` | Admin-Bereinigung |
| `includes/trait-importer-preview.php` | Vorschau |
| `includes/trait-importer-reporting.php` | Reporting |
| `reports/EXAMPLE_meta-report.md` | Beispielreport |

### 9.3 Betriebsrelevante Plugin-Regeln

- aktive Plugins werden in `cms_settings` verwaltet
- fehlende Runtime-Dateien führen zur Deaktivierung bzw. Bereinigung des aktiven Satzes
- `cms-importer` ist als geschütztes Plugin markiert

### 9.4 Praktische Plugin-Lesehilfe

Für Plugin-Debugging ist meist diese Reihenfolge sinnvoll:

1. Bootstrap-Datei des Plugins
2. plugin-spezifische Includes/Klassen
3. plugin-spezifische Admin-Seiten
4. plugin-eigene Assets
5. Interaktion mit `PluginManager` und Settings

---

## 10. `CMS/themes/` – produktiv geladene Themes

`CMS/themes/` ist der **einzige produktive Theme-Ladepfad**.

### 10.1 Ladelogik

Der `ThemeManager` liest das aktive Theme aus `cms_settings` und verwendet:

- `themes/<slug>/`

### 10.2 Erwartete Theme-Dateien

Typischerweise relevant sind:

- `style.css`
- `functions.php`
- `index.php`
- `header.php`
- `footer.php`
- weitere Templates wie `page.php`, `home.php`, `404.php`, `search.php` usw.

### 10.3 Schutz- und Fallback-Logik

- Pfadvalidierung per `realpath()`
- Syntax-/Sicherheitsprüfung
- Rollback auf `DEFAULT_THEME` bei Problemen

### 10.4 Wichtig für Wartung

`365CMS.DE-THEME/` ist wichtig für Pflege und Entwicklung, aber **nicht automatisch** der Ort, aus dem die laufende Site rendert. Runtime zählt nur, was unter `CMS/themes/` liegt.

### 10.5 Praktische Theme-Lesehilfe

Für Theme-Analyse ist meist diese Reihenfolge nützlich:

1. `core/ThemeManager.php`
2. aktiver Theme-Slug aus Settings
3. tatsächliche Dateien unter `CMS/themes/<slug>/`
4. zugehörige Assets und Templates
5. erst danach Vergleich mit Theme-Quellrepo

---

## 11. Weitere Runtime-Bereiche unter `CMS/`

### 11.1 `CMS/includes/`

Globale Hilfsdateien und Laufzeitfunktionen.

Verifizierte Beispiele:

- `includes/functions.php`
- `includes/functions/admin-menu.php`
- `includes/functions/escaping.php`
- `includes/functions/mail.php`
- `includes/functions/options-runtime.php`
- `includes/functions/redirects-auth.php`
- `includes/functions/roles.php`
- `includes/functions/translation.php`
- `includes/functions/wordpress-compat.php`
- `includes/subscription-helpers.php`

### 11.2 `CMS/lang/`

Sprachdateien für Lokalisierung und Übersetzungen.

Verifiziert:

- `lang/de.yaml`
- `lang/en.yaml`

### 11.3 `CMS/logs/`

Schutz-/Placeholder-Struktur im Webkontext.

Verifiziert:

- `logs/.gitignore`
- `logs/.htaccess`

Wichtig: Die eigentlichen Logs sollen bevorzugt in `var/logs/` landen.

### 11.4 `CMS/uploads/`

Upload-Zielstruktur für Benutzer- und Inhaltsdateien.

Verifiziert:

- `uploads/.htaccess`

### 11.5 `CMS/cache/`

Laufzeit- und Cache-Kontext. Dieser Bereich ist für Performance relevant und kann bei Diagnose oder Debugging eine wichtige Nebenrolle spielen.

### 11.6 `CMS/db/`

DB-nahe Artefakte und SQL-Kontext. Dieser Bereich ist meist weniger sichtbar als `core/`, bleibt aber für Setup, Migration oder Analyse relevant.

### 11.7 `CMS/install/`

Installationslogik und Install-Views.

Verifiziert:

| Pfad | Zweck |
|---|---|
| `InstallerController.php` | Controller für Installationsfluss |
| `InstallerService.php` | Installationslogik |
| `views/admin.php` | Install-Admin-Ansicht |
| `views/blocked.php` | Blockiert-Ansicht |
| `views/database.php` | DB-Konfiguration |
| `views/site.php` | Site-Konfiguration |
| `views/success.php` | Erfolg |
| `views/update.php` | Update-Ansicht |
| `views/welcome.php` | Einstieg |

### 11.8 `CMS/vendor/`

Zusätzlicher Vendor-Kontext außerhalb von `CMS/assets/`.

Aktuell verifiziert:

- `vendor/dompdf/`

### 11.9 `CMS/views/`

Ergänzende View-Struktur außerhalb von `admin/` und `member/`.

Aktuell verifiziert:

- `views/auth/`

### 11.10 Warum diese „kleineren“ Ordner trotzdem wichtig sind

Ordner wie `includes/`, `lang/`, `install/`, `views/` oder `vendor/` wirken auf den ersten Blick kleiner als `core/` oder `admin/`, entscheiden aber oft darüber, ob:

- Auth-Seiten korrekt laden,
- Installationspfade funktionieren,
- Übersetzungen konsistent sind,
- Hilfsfunktionen und Kompatibilität stabil bleiben,
- oder zusätzliche Vendor-Funktionen korrekt zur Verfügung stehen.

---

## 12. `DOC/` – Dokumentationsbaum

### 12.1 Zentrale Doku-Dateien

| Datei | Zweck |
|---|---|
| `DOC/INDEX.md` | Dokumentationsindex |
| `DOC/README.md` | Doku-Überblick |
| `DOC/DEVLIST.md` | technische Entwicklerreferenz |
| `DOC/FILELIST.md` | diese Strukturübersicht |
| `DOC/CMSFILESTRUCTUR.md` | auditnahe Vollinventarsicht |
| `DOC/INSTALLATION.md` | Installation |
| `DOC/ASSET.md` | Asset-Kontext |
| `DOC/ASSETS_OwnAssets.md` | Asset-/Austauschkontext |

### 12.2 Wichtige Unterbereiche in `DOC/`

| Ordner | Zweck |
|---|---|
| `DOC/admin/` | Admin-Dokumentation |
| `DOC/assets/` | Assets- und Vendor-Kontext |
| `DOC/audit/` | Prüfstände, Bewertungen, ToDos |
| `DOC/core/` | Kernsystem-Dokumentation |
| `DOC/img/` | Doku-Bilder |
| `DOC/member/` | Member-Dokumentation |
| `DOC/plugins/` | Plugin-Dokumentation |
| `DOC/theme/` | Theme-Dokumentation |
| `DOC/workflow/` | Workflows und operative Rezepte |

### 12.3 Funktion von `DOC/` im Projekt

Die Dokumentation ist in diesem Projekt kein Beiwerk, sondern Teil des Betriebsmodells. Da Repo-Stand, Runtime und FTP-Upload bewusst eng gekoppelt sein sollen, müssen Strukturdoku und Technikdoku möglichst nah an der echten Runtime bleiben.

### 12.4 Wichtigste Doku-Kombination für Entwickler

In der Praxis ergänzen sich meist diese drei Dateien am besten:

- `DOC/FILELIST.md` für Struktur und Einstieg
- `DOC/DEVLIST.md` für Architektur, Verträge und Betriebsdenken

---

## 13. Externe, aber angrenzende Repositories im Workspace

### 13.1 `365CMS.DE-THEME/`

Enthält Theme-Quellen, Theme-Metadaten, CSS, JS, Templates und Doku für die Pflege außerhalb der produktiven `CMS/themes/`-Runtime.

### 13.2 `365CMS.DE-PLUGINS/`

Enthält Plugin-Quellen mit eigenen `README`, `CHANGELOG`, `update.json`, `assets`, `includes`, `templates` usw.

### 13.3 Praktische Konsequenz

Diese Repositories sind für Entwicklung und Pflege wichtig, aber **nicht** automatisch der aktive Laufzeitort. Für echte Runtime-Wirkung müssen Inhalte in die Core-Laufzeit des CMS deployt, kopiert, installiert oder synchron übernommen werden.

### 13.4 Typischer Denkfehler an dieser Stelle

Wenn etwas im Theme- oder Plugin-Quellrepo korrekt aussieht, ist das noch kein Beweis dafür, dass dieselbe Änderung bereits unter `CMS/` aktiv ist. Genau diese Verwechslung erzeugt in großen Workspaces besonders zähe Fehlersuchen.

---

## 14. Schnelle Orientierung nach Aufgabenart

| Wenn du … | dann beginne meist bei … |
|---|---|
| Core-Bootstrap verstehen willst | `CMS/core/Bootstrap.php`, `CMS/config/app.php` |
| Auth/MFA/Passkeys suchst | `CMS/core/Auth.php`, `CMS/core/Auth/`, `CMS/views/auth/` |
| Theme-Probleme untersuchst | `CMS/core/ThemeManager.php`, `CMS/themes/` |
| Plugin-Ladeprobleme suchst | `CMS/core/PluginManager.php`, `CMS/plugins/` |
| Redirect-/404-Probleme analysierst | `CMS/admin/modules/seo/`, `CMS/admin/views/seo/`, `CMS/assets/js/admin-seo-redirects.js` |
| Editor-Probleme suchst | `CMS/assets/js/admin-content-editor.js`, `CMS/core/Services/EditorJs/`, `CMS/assets/editorjs/` |
| Member-Funktionen prüfst | `CMS/member/`, `CMS/core/Routing/MemberRouter.php`, `CMS/core/Services/MemberService.php` |
| Admin-UI anfasst | `CMS/admin/modules/`, `CMS/admin/views/`, `CMS/assets/js/admin*.js`, `CMS/assets/css/admin*.css` |
| Asset-/Library-Probleme suchst | `CMS/assets/`, `CMS/assets/css/`, `CMS/assets/js/`, zugehörige Bibliotheksordner |
| Sicherheitskonstanten suchst | `CMS/config/app.php`, `CMS/core/Security.php`, `CMS/assets/htmlpurifier/`, `CMS/assets/webauthn/`, `CMS/assets/php-jwt/` |
| Doku aktualisierst | `DOC/INDEX.md`, `DOC/DEVLIST.md`, `DOC/FILELIST.md`

---

## 15. Kurzfazit

Die Datei- und Ordnerstruktur von 365CMS ist breit, aber logisch, wenn man sie entlang der Runtime liest:

- `CMS/config/` definiert die Regeln
- `CMS/core/` liefert das Fundament
- `CMS/admin/` bildet das operative Backend
- `CMS/member/` den Benutzerbereich
- `CMS/assets/` die produktiven UI-, Bibliotheks- und Runtime-Assets
- `CMS/plugins/` und `CMS/themes/` die echte Erweiterungs- und Template-Laufzeit
- `DOC/` erklärt das Ganze und hält Wissen für Wartung, Audit und Weiterentwicklung fest

Die wichtigste Strukturregel bleibt dabei:

**Nicht der hübscheste Quellpfad ist entscheidend, sondern der tatsächlich geladene Runtime-Pfad unter `CMS/`.**

Gerade für dieses Projekt gilt zusätzlich: Repo-Stand, Runtime-Datei und FTP-Upload sollen bewusst dicht beieinander liegen. Deshalb ist eine gute `FILELIST.md` nicht nur Ordnungsliebe, sondern ein Werkzeug gegen Fehlersuche am falschen Ort.

Die Datei soll damit nicht nur eine Liste von Ordnern sein, sondern eine praktische Strukturkarte, die zeigt:

- **wo** 365CMS liegt,
- **welche Pfade wirklich geladen werden**,
- **welche Nachbarbereiche bei Änderungen mitzudenken sind**,
- und **warum `CMS/assets/` in diesem System eine zentrale technische Fläche ist**.
