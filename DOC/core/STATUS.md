# 365CMS – Systemstatus
> **Stand:** 2026-03-08 | **Version:** 2.5.4 | **Status:** Aktuell

## Inhaltsverzeichnis
- [Versionsstand](#versionsstand)
- [Core- und Plattformstatus](#core--und-plattformstatus)
- [Datenbankschema](#datenbankschema)
- [Aktuelle Admin-Architektur](#aktuelle-admin-architektur)
- [Wichtige Feature-Stände](#wichtige-feature-stände-in-254)
- [Bekannte Grenzen](#bekannte-grenzen)
- [Nächste geplante Features](#nächste-geplante-features)
- [Deprecations](#deprecations)
- [Verwandte Dokumente](#verwandte-dokumente)

---

## Versionsstand

| Eigenschaft | Wert |
|---|---|
| CMS-Version | `2.5.4` |
| Code-Referenz | `CMS/config/app.php` |
| Update-Metadaten | `CMS/update.json` |
| Release-Datum | `2026-03-08` |
| Projektstandard PHP | `8.3+` |
| Update-Metadaten `min_php` | `8.2` |
| Datenbank | MySQL 5.7+ / MariaDB 10.3+ |

---

## Core- und Plattformstatus

| Bereich | Status | Hinweis |
|---|---|---|
| Bootstrap | ✅ produktiv | lädt Konfiguration, Autoloader, Container und Kernservices |
| Datenbank | ✅ produktiv | PDO-basierter Zugriff mit Helpern, Prepare-/Execute-Flow und SchemaManager |
| Routing | ✅ produktiv | Frontend-, Admin-, Member- und Systemrouten aktiv |
| Sicherheit | ✅ produktiv | CSRF, Escaping, Rate-Limits, Audit- und Firewall-Integration |
| Theme-System | ✅ produktiv | ThemeManager, Theme-Editor, Theme-Explorer, Customizer-Anbindung |
| Plugin-System | ✅ produktiv | Hook-System, Plugin-Registry, Plugin-Marketplace und Admin-Einbindung |
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

## Wichtige Feature-Stände in 2.5.4 <!-- UPDATED: 2026-03-08 -->

| Bereich | Stand |
|---|---|
| SEO | ✅ eigenes SEO-Center mit Dashboard, Audit, Meta, Social, Schema, Sitemap und Technical |
| Performance | ✅ eigenes Performance-Center mit Cache-, Medien-, Datenbank-, Settings- und Sessions-Unterseiten |
| Monitoring | ✅ Response-Time, Cron-Status, Disk-Usage, Scheduled Tasks, Health-Check und E-Mail-Alerts |
| Medien | ✅ Standard-Listenansicht, Schutzlogik für Member-Ordner, stabile Delete- und Preview-Flows |
| Fonts | ✅ lokales Self-Hosting, Download-Fallbacks, Audit-Logging |
| WebP | ✅ Massenkonvertierung und Referenz-Umbiegung |
| Legal/Privacy | ✅ Sammelroute `/admin/data-requests`, Legal-Sites-Autofill, Cookie-Manager-Hydration |
| Rollen & Rechte | ✅ dynamische Rollen, `role_permissions`, DB-basierte Capability-Prüfung |
| Editor.js | ✅ Block-basierter Content-Editor als primärer Editor |
| WebAuthn/Passkey | ✅ FIDO2-Authentifizierung als alternative Login-Methode |
| PDF-Export | ✅ DomPDF-Integration für Seiten- und Beitragsexport |

---

## Bekannte Grenzen

| Thema | Einordnung |
|---|---|
| SMTP | konfigurierbar, aber nicht als vollständig entkoppelter Mail-Produktbaukasten dokumentiert |
| REST-Authentifizierung | vorwiegend sessionnah; kein voll ausgebautes OAuth2-/API-Key-Konzept als Core-Standard |
| Dokumentation alter Alt-Routen | in Restbeständen einzelner Legacy-Dokumente noch nachziehbar |

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
| SunEditor (Legacy WYSIWYG) | Editor.js (Block-Editor) | v3.0 |
| `mailer/` (Legacy Mailer) | Symfony Mailer (`symfony-mailer/`) | v3.0 |
| `WP_Error` Kompatibilitätsklasse | Native Exceptions | v3.0 |
