# 365CMS – FILELIST

> Stand: 2026-04-07  
> Zweck: aktuelle, entwicklerfreundliche Strukturübersicht des Repositories und der produktiven CMS-Laufzeit

Diese Datei ist die lesbare Strukturkarte des Projekts. Sie ergänzt die technische Tiefendokumentation in `DOC/DEVLIST.md` und das detailreichere Inventar in `DOC/CMSFILESTRUCTUR.md`.

---

## 1. Gesamtbild des Repositories

### 1.1 Workspace- und Projektkontext

Aktiv relevant für das laufende CMS sind im Workspace insbesondere:

- `365CMS.DE/` – Hauptrepository und produktive Laufzeitbasis
- `365CMS.DE-THEME/` – separates Theme-Quellrepository
- `365CMS.DE-PLUGINS/` – separates Plugin-Quellrepository

**Wichtig:** Die Runtime des CMS lädt Themes und Plugins nicht direkt aus den separaten Quell-Repositories, sondern aus `365CMS.DE/CMS/themes/` und `365CMS.DE/CMS/plugins/`.

### 1.2 Top-Level-Struktur von `365CMS.DE/`

| Pfad | Zweck |
|---|---|
| `README.md` | Projektüberblick, Status, Einstieg |
| `Changelog.md` | Änderungsverlauf |
| `ASSETS/` | Entwicklungs-/Vendor-Quellbestand außerhalb der produktiven CMS-Laufzeit |
| `BACKUP/` | Sicherungen und Altstände |
| `CMS/` | produktive Anwendungslaufzeit |
| `DOC/` | Projektdokumentation |
| `IMAGES/` | Bild-/Ablagekontext |
| `STAGING/` | Staging-/Zwischenkontext |
| `tests/` | Testkontext |
| `var/` | Logs und Laufzeitartefakte außerhalb des Webroots |

---

## 2. Produktive Kernstruktur unter `CMS/`

### 2.1 Root von `CMS/`

| Pfad | Zweck |
|---|---|
| `.htaccess` | Rewrite- und Schutzregeln |
| `config.php` | Legacy-/Stub-Einstieg zur Konfiguration |
| `cron.php` | Einstieg für Cron-/Hintergrundverarbeitung |
| `index.php` | Frontend-Einstieg |
| `install.php` | Installer und Tabelleninitialisierung |
| `orders.php` | Bestell-/Legacy-Endpunkt |
| `update.json` | Release-/Update-Metadaten |
| `admin/` | Admin-Bereich |
| `assets/` | produktive Styles, Scripts, gebündelte Runtime-Libraries |
| `cache/` | Cache-Dateien / Laufzeit-Cachekontext |
| `config/` | zentrale Konfiguration |
| `core/` | Core-Klassen, Routing, Services, Security, Bootstrap |
| `db/` | DB-nahe Artefakte / SQL-Kontext |
| `includes/` | globale Hilfsfunktionen |
| `install/` | Installer-Services und Installationshilfen |
| `lang/` | Sprachdateien |
| `logs/` | Logschutz-/Placeholder-Kontext |
| `member/` | Member-Bereich |
| `plugins/` | produktiv geladene Plugins |
| `themes/` | produktiv geladene Themes |
| `uploads/` | Upload-Zielstruktur |

---

## 3. Inventarsicht des geprüften Runtime-Scopes

Das vorhandene Inventar `DOC/_cms_inventory_current.txt` dokumentiert einen verifizierten Prüfscope für `CMS/`.

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

### 3.2 Bedeutung des Prüfscope

Dieser Inventarstand ist besonders nützlich für:

- Audits
- strukturelle Änderungsvergleiche
- Refactoring mit Scope-Kontrolle
- Abschätzung von Wartungsflächen

---

## 4. `CMS/config/` – Konfiguration

| Pfad | Zweck |
|---|---|
| `config/.htaccess` | Schutz der Konfigurationsdateien |
| `config/app.php` | zentrale App-Konfiguration, Pfade, Security, DB, SMTP, LDAP, JWT |
| `config/media-meta.json` | Medien-Metadaten |
| `config/media-settings.json` | Medien-Konfiguration |

**Kernfakten:**

- definiert `ABSPATH`, `CORE_PATH`, `THEME_PATH`, `PLUGIN_PATH`, `UPLOAD_PATH`, `ASSETS_PATH`
- enthält Security-/Session-/Login-/Transportparameter
- setzt `DEFAULT_THEME` auf `cms-default`
- nutzt `var/logs/` als bevorzugten Log-Zielpfad

---

## 5. `CMS/core/` – technischer Kern

### 5.1 Zentrale Root-Klassen in `core/`

| Datei | Zweck |
|---|---|
| `Api.php` | API-nahe Kernlogik |
| `AuditLogger.php` | Audit-Logging |
| `Auth.php` | Authentifizierung |
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
| `ThemeManager.php` | Theme-Laden, Rendering, Menüs |

### 5.2 Wichtige Unterordner in `core/`

| Pfad | Zweck |
|---|---|
| `core/Auth/` | AuthManager, LDAP, MFA, Passkeys |
| `core/Contracts/` | Interfaces für Kernkomponenten |
| `core/Http/` | HTTP-Client / Transportlogik |
| `core/Member/` | Member-nahe Kernregistrierung |
| `core/Routing/` | Admin-, Public-, Member-, API- und Theme-Router |
| `core/Services/` | Fachservices des Systems |

### 5.3 `core/Auth/`

| Datei/Ordner | Zweck |
|---|---|
| `AuthManager.php` | zentrale Auth-Steuerung |
| `LDAP/LdapAuthProvider.php` | LDAP-Anbindung |
| `MFA/BackupCodesManager.php` | MFA-Backupcodes |
| `MFA/TotpAdapter.php` | TOTP-Integration |
| `Passkey/WebAuthnAdapter.php` | Passkey-/WebAuthn-Unterstützung |

### 5.4 `core/Routing/`

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
| Mail | `MailService`, `MailQueueService`, `MailLogService`, `AzureMailTokenProvider` |
| SEO | `SEOService`, `SeoAnalysisService`, SEO-Unterservices, `RedirectService`, `IndexingService` |
| Suche | `SearchService` |
| Medien | `MediaService`, `MediaDeliveryService`, `FileUploadService`, `ImageService` |
| Editor | `EditorJsService`, `EditorJsRenderer`, `EditorService` |
| Landing | mehrere `Landing*`-Services |
| Betrieb | `BackupService`, `OpcacheWarmupService`, `CoreWebVitalsService`, `SystemService`, `StatusService`, `UpdateService` |
| Tracking | `AnalyticsService`, `TrackingService`, `FeatureUsageService` |
| Member | `MemberService`, `MessageService` |
| PDF | `PdfService` |

---

## 6. `CMS/admin/` – Backend-Struktur

### 6.1 Admin-Einstiegsdateien

Der Admin-Bereich enthält zahlreiche direkte Einstiegspunkte unter `CMS/admin/*.php`.

Wichtige Gruppen:

| Bereich | Beispiele |
|---|---|
| SEO | `seo-dashboard.php`, `seo-audit.php`, `redirect-manager.php`, `not-found-monitor.php` |
| Security | `security-audit.php`, `firewall.php`, `antispam.php` |
| Content | `pages.php`, `posts.php`, `comments.php`, `post-categories.php`, `post-tags.php` |
| Themes | `themes.php`, `theme-editor.php`, `theme-marketplace.php`, `theme-settings.php` |
| Plugins | `plugins.php`, `plugin-marketplace.php` |
| User | `users.php`, `groups.php`, `roles.php`, `user-settings.php` |
| System | `updates.php`, `support.php`, `system-info.php`, `documentation.php`, `diagnose.php` |
| Performance | `performance.php`, `performance-cache.php`, `performance-database.php`, `performance-media.php` |
| Monitoring | `monitor-cron-status.php`, `monitor-health-check.php`, `monitor-response-time.php` |
| Member-Konfiguration | `member-dashboard.php` und zugehörige Spezialseiten |

### 6.2 `admin/modules/`

Hier liegt die Domänenlogik der Admin-Funktionen.

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

### 6.3 `admin/views/`

Views sind nach Domänen gruppiert:

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

### 6.4 `admin/partials/`

Wichtige wiederverwendbare Admin-Bausteine:

| Datei | Zweck |
|---|---|
| `header.php` | Admin-Header |
| `footer.php` | Admin-Footer |
| `sidebar.php` | Navigation |
| `post-action-shell.php` | Post-/Form-Aktionsshell |
| `redirect-alias-shell.php` | Redirect-nahe Shell |
| `section-page-shell.php` | zentrale Section-/Page-Shell |

---

## 7. `CMS/assets/` – produktive Frontend-/Admin-Assets

### 7.1 CSS-Dateien des inventarisierten Scopes

| Datei | Zweck |
|---|---|
| `assets/css/admin.css` | allgemeines Admin-Styling |
| `assets/css/admin-tabler.css` | Admin-/Tabler-Anpassungen |
| `assets/css/admin-hub-site-edit.css` | Hub-Site-Editor |
| `assets/css/admin-hub-template-edit.css` | Hub-Template-Editor |
| `assets/css/admin-hub-template-editor.css` | weitere Hub-Template-Stile |
| `assets/css/cms-cookie-consent.css` | Consent-Banner |
| `assets/css/hub-sites.css` | Hub-Site-Darstellung |
| `assets/css/main.css` | allgemeines Hauptstyling |
| `assets/css/member-dashboard.css` | Member-Dashboard |

### 7.2 JavaScript-Dateien des inventarisierten Scopes

| Datei | Zweck |
|---|---|
| `assets/js/admin.js` | globales Admin-JavaScript |
| `assets/js/admin-content-editor.js` | Live-Content-Editor-Interaktion |
| `assets/js/admin-pages.js` | Seitenverwaltung |
| `assets/js/admin-comments.js` | Kommentare |
| `assets/js/admin-menu-editor.js` | Menü-Editor |
| `assets/js/admin-users.js` | Benutzerverwaltung |
| `assets/js/admin-user-groups.js` | Gruppen-/User-Zuordnung |
| `assets/js/admin-plugins.js` | Plugin-Verwaltung |
| `assets/js/admin-plugin-marketplace.js` | Plugin-Marketplace |
| `assets/js/admin-theme-explorer.js` | Theme-Explorer |
| `assets/js/admin-theme-marketplace.js` | Theme-Marketplace |
| `assets/js/admin-seo-editor.js` | SEO-Editor |
| `assets/js/admin-seo-redirects.js` | Redirect-/404-Dialoglogik |
| `assets/js/admin-grid.js` | Tabellen-/Grid-Hilfen |
| `assets/js/admin-site-tables.js` | Site-Tabellenverwaltung |
| `assets/js/admin-cookie-manager.js` | Consent-/Cookie-Verwaltung |
| `assets/js/admin-data-requests.js` | Datenschutzanfragen |
| `assets/js/admin-font-manager.js` | Font-Manager |
| `assets/js/admin-media-integrations.js` | Medienintegration |
| `assets/js/admin-hub-sites.js` | Hub-Sites |
| `assets/js/admin-hub-site-edit.js` | Hub-Site-Editor |
| `assets/js/admin-hub-template-edit.js` | Hub-Template-Bearbeitung |
| `assets/js/admin-hub-template-editor.js` | erweiterte Hub-Template-Funktionen |
| `assets/js/admin-legal-sites.js` | Rechtsseiten |
| `assets/js/editor-init.js` | Editor-Initialisierung |
| `assets/js/gridjs-init.js` | Grid.js-Initialisierung |
| `assets/js/cookieconsent-init.js` | Consent-Initialisierung |
| `assets/js/member-dashboard.js` | Member-Dashboard-Interaktion |
| `assets/js/photoswipe-init.js` | Lightbox-Initialisierung |
| `assets/js/web-vitals.js` | Web-Vitals-Erfassung |

### 7.3 Gebündelte Bibliotheken in `CMS/assets/`

Neben CSS/JS liegen dort weitere Runtime-Bibliotheken, z. B. für:

- Mailer/Mime/Translation
- HTMLPurifier
- Dompdf / TCPDF
- Editor.js
- SunEditor
- PhotoSwipe
- JWT
- LDAP-nahe Abhängigkeiten
- WebAuthn
- TNTSearch

---

## 8. `CMS/member/` – Benutzerbereich

Typische Kernseiten:

| Datei | Zweck |
|---|---|
| `dashboard.php` | Member-Startseite |
| `profile.php` | Profilverwaltung |
| `security.php` | Passwort, MFA, Sessions |
| `subscription.php` | Abo-/Paketkontext |
| `media.php` | persönliche Medien |
| `messages.php` | Nachrichten |
| `notifications.php` | Benachrichtigungen |
| `privacy.php` | Datenschutz |
| `favorites.php` | Favoriten |

Der Member-Bereich ist funktional eng mit Auth, Medien, Messaging, Privatsphäre und Subscription-Logik gekoppelt.

---

## 9. `CMS/plugins/` – produktiv geladene Plugins

`CMS/plugins/` ist der einzige produktive Plugin-Ladepfad.

### 9.1 Ladelogik

Der `PluginManager` sucht Bootstrap-Dateien nach dem Muster:

- `plugins/<slug>/<slug>.php`

### 9.2 Besonderheit

Aktive Plugins werden in `cms_settings` verwaltet. Fehlende Runtime-Dateien führen zur automatischen Deaktivierung des betroffenen Plugins.

### 9.3 Protected Plugin

- `cms-importer` ist als geschütztes Kern-Plugin markiert.

---

## 10. `CMS/themes/` – produktiv geladene Themes

`CMS/themes/` ist der einzige produktive Theme-Ladepfad.

### 10.1 Ladelogik

Der `ThemeManager` liest das aktive Theme aus `cms_settings` und baut den Pfad:

- `themes/<slug>/`

### 10.2 Erwartete Theme-Dateien

Mindestens relevant sind typischerweise:

- `style.css`
- `functions.php`
- `index.php`
- ggf. `header.php`, `footer.php`, weitere Templates

### 10.3 Fallback und Schutz

- Pfadvalidierung per `realpath()`
- Syntax-/Sicherheitsprüfung
- Rollback auf `DEFAULT_THEME` bei Problemen

---

## 11. `CMS/includes/`, `CMS/lang/`, `CMS/logs/`, `CMS/uploads/`

### 11.1 `CMS/includes/`

Globale Hilfsdateien und Laufzeitfunktionen, die vom Bootstrap bei Bedarf nachgeladen werden.

### 11.2 `CMS/lang/`

Sprachdateien für Lokalisierung und Übersetzungen.

### 11.3 `CMS/logs/`

Schutz-/Platzhalterstruktur im Webkontext; die eigentlichen Logs sollen bevorzugt in `var/logs/` landen.

### 11.4 `CMS/uploads/`

Upload-Zielstruktur für Benutzer- und Inhaltsdateien. Sicherheits- und Zugriffsregeln sind hier besonders wichtig.

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
| `DOC/_cms_inventory_current.txt` | maschinennahe Dateiliste / Bestandszahlen |
| `DOC/INSTALLATION.md` | Installation |
| `DOC/ASSET.md` | Asset-Kontext |
| `DOC/ASSETS_OwnAssets.md` | Asset-/Austauschkontext |

### 12.2 Unterbereiche in `DOC/`

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

---

## 13. Externe, aber angrenzende Repositories im Workspace

### 13.1 `365CMS.DE-THEME/`

Enthält Theme-Quellen, Theme-Metadaten, CSS, JS, Templates und Doku für die Pflege außerhalb der produktiven `CMS/themes/`-Runtime.

### 13.2 `365CMS.DE-PLUGINS/`

Enthält Plugin-Quellen mit eigenen `README`, `CHANGELOG`, `update.json`, `assets`, `includes`, `templates` usw.

### 13.3 Praktische Konsequenz

Diese Repositories sind für Entwicklung und Pflege wichtig, aber **nicht** automatisch der aktive Laufzeitort. Für echte Runtime-Wirkung müssen Inhalte in die Core-Laufzeit des CMS deployt/kopiert/installiert werden.

---

## 14. Schnelle Orientierung nach Aufgabenart

| Wenn du … | dann beginne meist bei … |
|---|---|
| Core-Bootstrap verstehen willst | `CMS/core/Bootstrap.php`, `CMS/config/app.php` |
| Auth/MFA/Passkeys suchst | `CMS/core/Auth.php`, `CMS/core/Auth/` |
| Theme-Probleme untersuchst | `CMS/core/ThemeManager.php`, `CMS/themes/` |
| Plugin-Ladeprobleme suchst | `CMS/core/PluginManager.php`, `CMS/plugins/` |
| Redirect-/404-Probleme analysierst | `CMS/admin/modules/seo/`, `CMS/assets/js/admin-seo-redirects.js` |
| Editor-Probleme suchst | `CMS/assets/js/admin-content-editor.js`, `CMS/core/Services/EditorJs/` |
| Member-Funktionen prüfst | `CMS/member/`, `CMS/core/Routing/MemberRouter.php` |
| Admin-UI anfasst | `CMS/admin/modules/`, `CMS/admin/views/`, `CMS/assets/js/admin*.js` |
| Sicherheitskonstanten suchst | `CMS/config/app.php`, `CMS/core/Security.php` |
| Doku aktualisierst | `DOC/INDEX.md`, `DOC/DEVLIST.md`, `DOC/FILELIST.md` |

---

## 15. Kurzfazit

Die Datei- und Ordnerstruktur von 365CMS ist breit, aber logisch:

- `CMS/config/` definiert die Regeln
- `CMS/core/` liefert das Fundament
- `CMS/admin/` bildet das operative Backend
- `CMS/member/` den Benutzerbereich
- `CMS/assets/` die produktiven UI-/Runtime-Assets
- `CMS/plugins/` und `CMS/themes/` die echte Erweiterungs- und Template-Laufzeit
- `DOC/` erklärt das Ganze hoffentlich so, dass zukünftiges Debugging weniger nach Archäologie und mehr nach Engineering aussieht
