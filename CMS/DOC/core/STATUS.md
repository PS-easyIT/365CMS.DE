# 365CMS – Systemstatus
> **Stand:** 2026-06-10 | **Version:** 3.3.47 | **Status:** Aktuell

## Inhaltsverzeichnis
- [Versionsstand](#versionsstand)
- [Core- und Plattformstatus](#core--und-plattformstatus)
- [Datenbankschema](#datenbankschema)
- [Aktuelle Admin-Architektur](#aktuelle-admin-architektur)
- [Wichtige Feature-Stände](#wichtige-feature-stände-im-aktuellen-stand-3344)
- [Bekannte Grenzen](#bekannte-grenzen)
- [Nächste geplante Features](#nächste-geplante-features)
- [Deprecations](#deprecations)
- [Verwandte Dokumente](#verwandte-dokumente)

---

## Versionsstand

| Eigenschaft | Wert |
|---|---|
| CMS-Version | `3.3.47` |
| Code-Referenz | `CMS/core/Version.php` |
| Update-Metadaten | `CMS/update.json` |
| Release-Datum | `2026-06-05` |
| Projektstandard PHP | `8.4+` |
| Update-Metadaten `min_php` | `8.4` |
| Datenbank | MySQL 8.0+ / MariaDB 10.6+ |
| Letztes Code-Audit | `2026-06-10` — 0 kritische Funde ([AUDIT_core](../AUDIT_core_2026-06-10.md)) |

---

## Core- und Plattformstatus

| Bereich | Status | Hinweis |
|---|---|---|
| Bootstrap | ✅ produktiv | lädt Konfiguration, Autoloader, Container und Kernservices und validiert gebündelte PHP-Plattformanforderungen vor der Initialisierung |
| Datenbank | ✅ produktiv | PDO-basierter Zugriff mit Helpern, Prepare-/Execute-Flow und SchemaManager |
| Routing | ✅ produktiv | Frontend-, Admin-, Member- und Systemrouten aktiv |
| Sicherheit | ✅ produktiv | CSRF, Escaping, Rate-Limits, Audit- und Firewall-Integration |
| Theme-System | ✅ produktiv | ThemeManager, Theme-Editor, Theme-Explorer, Customizer-Anbindung |
| Plugin-System | ✅ produktiv | Hook-System, Plugin-Registry, Plugin-Marketplace, kollisionsfreie Admin-Menüpositionen und robuste Admin-Einbindung |
| Member-Bereich | ✅ produktiv | Dashboard, Profil, Privacy, Notifications, Security, Subscription |
| Update-System | ✅ produktiv | GitHub-basierte Core-/Plugin-/Theme-Prüfung |

---

## Datenbankschema

Der aktuelle Core-Stand arbeitet mit:

- **30 Basistabellen** aus `SchemaManager`
- zusätzlichen Modultabellen für SEO, Redirects, Cookies, Privacy, Firewall, Menüs und Rollenrechte

Maßgebliche Referenz: [DATABASE-SCHEMA.md](DATABASE-SCHEMA.md)

---

## Aktuelle Admin-Architektur

Die frühere Monolith-Struktur gilt nicht mehr als führende Referenz. Der Admin-Bereich ist heute in spezialisierte Einstiege aufgeteilt.

### Zentrale Gruppen

| Gruppe | Aktuelle Routen |
|---|---|
| Dashboard | `/admin` |
| Seiten & Beiträge | `/admin/pages`, `/admin/posts`, `/admin/comments`, `/admin/table-of-contents`, `/admin/site-tables` |
| Medien | `/admin/media` |
| Benutzer & Gruppen | `/admin/users`, `/admin/groups`, `/admin/roles` |
| Member Dashboard | `/admin/member-dashboard` und Folgeseiten |
| Aboverwaltung | `/admin/packages`, `/admin/orders`, `/admin/subscription-settings` |
| Themes & Design | `/admin/themes`, `/admin/theme-editor`, `/admin/theme-explorer`, `/admin/menu-editor`, `/admin/landing-page`, `/admin/font-manager` |
| SEO | `/admin/seo-dashboard`, `/admin/analytics`, `/admin/seo-audit`, `/admin/seo-meta`, `/admin/seo-social`, `/admin/seo-schema`, `/admin/seo-sitemap`, `/admin/seo-technical`, `/admin/redirect-manager` |
| Performance | `/admin/performance`, `/admin/performance-cache`, `/admin/performance-media`, `/admin/performance-database`, `/admin/performance-settings`, `/admin/performance-sessions` |
| Recht | `/admin/legal-sites`, `/admin/cookie-manager`, `/admin/data-requests` |
| Sicherheit | `/admin/antispam`, `/admin/firewall`, `/admin/security-audit` |
| Plugins | `/admin/plugins`, optional `/admin/plugin-marketplace` |
| System | `/admin/settings`, `/admin/backups`, `/admin/updates` |
| Info | `/admin/info`, `/admin/documentation` |
| Diagnose | `/admin/diagnose`, `/admin/monitor-*` |

Maßgebliche Referenz: `CMS/admin/partials/sidebar.php`

---

## Wichtige Feature-Stände im aktuellen Stand 3.3.47 <!-- UPDATED: 2026-06-10 -->

| Bereich | Stand |
|---|---|
| SEO | ✅ eigenes SEO-Center mit Dashboard, Audit, Meta, Social, Schema, Sitemap und Technical |
| Performance | ✅ eigenes Performance-Center mit Cache-, Medien-, Datenbank-, Settings- und Sessions-Unterseiten |
| Monitoring | ✅ Response-Time, Cron-Status, Disk-Usage, Scheduled Tasks, Health-Check und E-Mail-Alerts |
| Medien | ✅ Listen-/Grid-Ansicht, native Uploads, Rename/Move-Modale, Admin-Bulk-Aktionen, stabile Member-Root-Grenzen, korrigierte Systempfad-Semantik und `ArtikelRahmen_`-gefilterte Coverbild-Auswahl für Seiten/Beiträge |
| Tabellen | ✅ eigene Tabellen-Display-Defaults mit wählbaren Stil-Presets, sicherer Inline-HTML-Formatierung in Zellen, gehärteten Link-Attributen und locale-bewussten Site-Table-Zeilen aus ausgewählten Seiten/Beiträgen oder Kategorie-Filtern |
| Post-Taxonomien | ✅ Admin-Einstiege für Beitrags-Kategorien und Beitrags-Tags inklusive CRUD |
| Fehlerreports | ✅ persistente Admin-Fehlerreports mit Audit-Logging und Redirect-kompatiblen Payloads |
| Fonts | ✅ lokales Self-Hosting, Download-Fallbacks, Audit-Logging |
| WebP | ✅ Massenkonvertierung und Referenz-Umbiegung |
| Legal/Privacy | ✅ Sammelroute `/admin/data-requests`, Legal-Sites-Autofill, nativer Cookie-Consent-Flow via `CookieConsentService` + `cookieconsent-init.js` |
| Rollen & Rechte | ✅ dynamische Rollen, `role_permissions`, DB-basierte Capability-Prüfung |
| Editor.js | ✅ Block-basierter Content-Editor als primärer Editor |
| Mehrsprachige Inhalte | ✅ getrennte DE-/EN-Editorseiten mit sprachisoliertem Speichern und strengem `/en/...`-Public-Prefix-Vertrag |
| Admin-Struktur | ✅ klarere Hauptbereiche für Hub-Sites, TOC, Beitrags-Kategorien/-Tags, Font Manager, Theme-Marketplace und gruppierte Member-Dashboard-Unterseiten; seit `3.3.44` zusätzlich scrollbar lange Plugin-Menüs, natürliche Plugin-Label-Sortierung, kollisionsfreie Menüpositionen und automatische Plugin-Content-Wrapper |
| Übersetzungen | ✅ `TranslationService` mit Symfony Translation und eigenem Fallback-Katalog; seit `3.3.44` werden flache `default:`-YAML-Keys auch hinter Symfony zuverlässig aufgelöst und Detailseiten-Keys für Speaker, Experts, Companies und Events bereitgestellt |
| WebAuthn/Passkey | ✅ FIDO2-Authentifizierung als alternative Login-Methode |
| PDF-Export | ✅ DomPDF-Integration für Seiten- und Beitragsexport |
| Permalinks | ✅ zentraler `PermalinkService` für Beitrags-URL-Strukturen, Slug-Extraktion und Migrationspfade |
| Feeds | ✅ RSS-/Atom-Verarbeitung nativ über `FeedService` mit DOM/XML, abgesichertem Fetch und Dateicache |
| Legacy-Assets | ✅ FilePond, elFinder, CookieConsent-Vendor-Runtime und SimplePie sind nur noch dokumentierte Altbestände, nicht mehr aktive Laufzeitabhängigkeiten |

---

## Bekannte Grenzen

| Thema | Einordnung |
|---|---|
| SMTP | konfigurierbar, aber nicht als vollständig entkoppelter Mail-Produktbaukasten dokumentiert |
| REST-Authentifizierung | vorwiegend sessionnah; kein voll ausgebautes OAuth2-/API-Key-Konzept als Core-Standard |
| Dokumentation alter Alt-Routen | in Restbeständen einzelner Legacy-Dokumente noch nachziehbar |

## Plattform-Notiz <!-- ADDED: 2026-03-09 -->

- Die offizielle Mindestplattform des Projekts ist PHP `8.4+`.
- Hintergrund sind die produktiv gebündelten Symfony-Komponenten in `CMS/assets/mailer`, `CMS/assets/mime` und `CMS/assets/translation`, deren Composer-Metadaten PHP 8.4 voraussetzen.
- Diese Vorgabe wird nicht nur dokumentiert, sondern zur Laufzeit auch über `CMS/config.php`, `CMS/core/Bootstrap.php`, `CMS/core/Services/StatusService.php`, `CMS/core/Services/UpdateService.php` und `CMS/install.php` aktiv geprüft bzw. signalisiert.

## Release-Notiz 2.9.248 <!-- ADDED: 2026-05-02 -->

- Beiträge und Seiten behalten beim Speichern der getrennten DE-/EN-Editoransichten die jeweils inaktive Sprachfassung aus dem bestehenden Datensatz bei.
- Public-Seiten prüfen vor der Auslieferung, ob die angefragte Sprachvariante wirklich Inhalt besitzt; EN bleibt damit strikt unter `/en/...`, DE ohne Sprachprefix.
- Die Coverbild-Auswahl für Beiträge und Seiten listet nur noch `ArtikelRahmen_*`-Dateien, während normale Editor-Medienlisten unverändert bleiben.
- Site Tables erlauben in Tabellenzellen nur sichere Formatierungs-Tags für Fett, Kursiv, Unterstreichung und Links; Link-Attribute werden sanitizer- und fallbackseitig auf sichere Schemata/Tabnabbing-Schutz gehärtet, aktivierte Seiten-/Beitragsquellen erzeugen Tabellen nur aus ausgewählten Inhalten oder einem Kategorie-Filter statt aus freier Eingabe.

## Release-Notiz 3.3.47 <!-- ADDED: 2026-06-05 -->

- EditorJS-Bild+Text-Blöcke speichern eine Vertikal-Ausrichtung (oben/mittig/unten) für Text relativ zum Bild; Admin-Vorschau, Sanitizer, Normalizer, Public-Renderer und Critical-CSS ziehen die Ausrichtung konsistent durch.

## Sicherheits-Notiz Code-Audit <!-- ADDED: 2026-06-10 -->

- Interne Audits von `core/`, `admin/` und `member/`+`includes/` ergaben **0 kritische Funde**. Übernommene Defense-in-Depth-Härtungen: `MailService` (Header-Injection im `mail()`-Fallback, Anhangsnamen), `Bootstrap` (`hardenErrorReporting()` — keine Stack-Traces an den Client), `esc_js()` (Script-Breakout), `MemberController::redirect()` (Open-Redirect) und der Featured-Image-Picker (HEX-geflaggtes JSON).
- Reports: [AUDIT_core](../AUDIT_core_2026-06-10.md) · [AUDIT_admin](../AUDIT_admin_2026-06-10.md) · [AUDIT_member](../AUDIT_member-includes-views_2026-06-10.md).

## Release-Notiz 3.3.44 <!-- ADDED: 2026-05-31 -->

- Admin-Pluginmenüs überschreiben sich bei gleichen numerischen Positionen nicht mehr; belegte Positionen werden auf die nächste freie Position verschoben.
- Lange Plugin-Menülisten bleiben in der Sidebar sichtbar und scrollbar, während Header, Footer und Dropdowns im normalen Layoutfluss bleiben.
- Plugin-Admincallbacks ohne vollständiges Layout erhalten automatisch den gemeinsamen Core-Content-Wrapper; vollständige Layouts werden nicht doppelt eingebettet.
- Knowledgebase- und ausgewählte M365-Adminrouten besitzen zusätzliche Core-Fallbacks für direkte verschachtelte Plugin-URLs.
- Der TranslationService nutzt den eigenen Fallback-Katalog als zweite Stufe hinter Symfony Translation und die Sprachdateien enthalten neue Netzwerk-Detailseiten-Keys für Speaker, Experts, Companies und Events.

---

## Verwandte Dokumente

- [ARCHITECTURE.md](ARCHITECTURE.md)
- [DATABASE-SCHEMA.md](DATABASE-SCHEMA.md)
- [SERVICES.md](SERVICES.md)
- [SECURITY.md](SECURITY.md)

---

## Nächste geplante Features <!-- ADDED: 2026-03-08 -->

| Feature | Priorität | Status |
|---|---|---|
| OAuth2-Provider für API | Hoch | 🔄 In Planung |
| Plugin-Sandbox-Modus | Mittel | ❌ Ausstehend |
| Multi-Site-Unterstützung | Niedrig | ❌ Ausstehend |
| Vollständiger CLI-Modus | Mittel | 🔄 In Arbeit |

---

## Deprecations <!-- ADDED: 2026-03-08 -->

| Element | Ersetzt durch | Entfernung geplant |
|---|---|---|
| SunEditor (Legacy WYSIWYG) | Editor.js (Block-Editor) | bleibt als Editor außerhalb der Bereiche "Beträge & Seiten" erhalten |
| historische Mailer-Sonderpfade | `MailService` + `CMS/assets/mailer/` | v3.0 |
| `WP_Error` Kompatibilitätsklasse | Native Exceptions | v3.0 |
