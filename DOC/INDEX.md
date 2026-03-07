# 365CMS – Dokumentationsindex

Kurzbeschreibung: Zentraler Einstiegspunkt in die Projektdokumentation des Haupt-Repositorys `365CMS.DE`.

Letzte Aktualisierung: 2026-03-07

Die Dokumentation beschreibt den aktuell verifizierten Stand **365CMS 2.3.1** mit SEO-Center, Performance-Center, Monitoring-Unterseiten, Legal-/Security-Modulen, WebP-Massenkonvertierung und gehärtetem Font-Self-Hosting.

---

## Schnellstart

| Ziel | Dokument |
|---|---|
| Projektüberblick | [README.md](README.md) |
| Installation | [INSTALLATION.md](INSTALLATION.md) |
| Root-README | [../README.md](../README.md) |
| Changelog | [../Changelog.md](../Changelog.md) |

---

## Kernsystem

| Dokument | Zweck |
|---|---|
| [core/README.md](core/README.md) | Einstieg in die Core-Dokumentation |
| [core/ARCHITECTURE.md](core/ARCHITECTURE.md) | Bootstrap, Routing, Services, Module |
| [core/CORE-CLASSES.md](core/CORE-CLASSES.md) | zentrale Kernklassen |
| [core/DATABASE-SCHEMA.md](core/DATABASE-SCHEMA.md) | Basisschema und Modultabellen |
| [core/HOOKS-REFERENCE.md](core/HOOKS-REFERENCE.md) | Actions, Filter und Integrationspunkte |
| [core/API-REFERENCE.md](core/API-REFERENCE.md) | technische Referenzen und Schnittstellen |
| [core/SERVICES.md](core/SERVICES.md) | Service-Layer |
| [core/SECURITY.md](core/SECURITY.md) | Sicherheitsmodell im Core |
| [core/STATUS.md](core/STATUS.md) | Implementierungs- und Betriebsstatus |

---

## Admin-Bereich

| Dokument | Zweck |
|---|---|
| [admin/README.md](admin/README.md) | Überblick über Navigation und Bereiche |
| [admin/GUIDE.md](admin/GUIDE.md) | operativer Leitfaden für Administratoren |
| [admin/FILESTRUCTURE.md](admin/FILESTRUCTURE.md) | Admin-Dateistruktur und Routing |
| [admin/PANEL-INTEGRATION.md](admin/PANEL-INTEGRATION.md) | Integration eigener Admin-Seiten |

### Wichtige Teilbereiche

| Bereich | Dokument |
|---|---|
| Dashboard | [admin/dashboard/README.md](admin/dashboard/README.md) |
| Seiten & Beiträge | [admin/pages-posts/README.md](admin/pages-posts/README.md) |
| Medien | [admin/media/README.md](admin/media/README.md) |
| Benutzer & Gruppen | [admin/users-groups/README.md](admin/users-groups/README.md) |
| Themes & Design | [admin/themes-design/README.md](admin/themes-design/README.md) |
| Plugins | [admin/plugins/PLUGINS.md](admin/plugins/PLUGINS.md) |
| SEO | [admin/seo/SEO.md](admin/seo/SEO.md) |
| Recht | [admin/legal/README.md](admin/legal/README.md) |
| Sicherheit | [admin/security/README.md](admin/security/README.md) |
| Performance | [admin/performance/PERFORMANCE.md](admin/performance/PERFORMANCE.md) |
| System, Betrieb & Wartung | [admin/system-settings/README.md](admin/system-settings/README.md) |
| Aboverwaltung | [admin/subscription/SUBSCRIPTION-SYSTEM.md](admin/subscription/SUBSCRIPTION-SYSTEM.md) |

---

## Mitgliederbereich

| Dokument | Zweck |
|---|---|
| [member/README.md](member/README.md) | Gesamtüberblick über `/member` |
| [member/CONTROLLERS.md](member/CONTROLLERS.md) | Controller und Einstiegspunkte |
| [member/VIEWS.md](member/VIEWS.md) | Views, Partials und View-Daten |
| [member/HOOKS.md](member/HOOKS.md) | Member-Hooks für Plugins |
| [member/SECURITY.md](member/SECURITY.md) | Zugriff, Datenschutz, Sessions |

---

## Theme- und Plugin-Entwicklung

| Dokument | Zweck |
|---|---|
| [theme/README.md](theme/README.md) | Theme-System und verfügbare Leitfäden |
| [theme/THEME-DEVELOPMENT.md](theme/THEME-DEVELOPMENT.md) | Theme-Erstellung |
| [plugins/GUIDE.md](plugins/GUIDE.md) | schneller Plugin-Einstieg |
| [plugins/PLUGIN-DEVELOPMENT.md](plugins/PLUGIN-DEVELOPMENT.md) | vollständiger Plugin-Leitfaden |

---

## Audits, Feature-Dokumente und Workflows

| Bereich | Dokumente |
|---|---|
| Audits | `DOC/audits/*.md` |
| Feature-Konzepte | `DOC/feature/*.md` |
| Workflows | `DOC/workflow/*.md` |

Diese Dokumente enthalten teils Planungs- oder Bewertungsstände. Für aktuelle technische Aussagen haben Core-, Admin- und Member-Dokumente Vorrang.

| [PERFORMANCE-AUDIT.md](audits/PERFORMANCE-AUDIT.md) | Performance-Analyse (Score: 5.8/10) |
| [SECURITY-AUDIT.md](audits/SECURITY-AUDIT.md) | Security-Analyse (Score: 5.2/10, 7 P1-Findings) |
| [CORE-AUDIT.md](audits/CORE-AUDIT.md) | Core-Architektur-Audit (Score: 5.4/10) |
| [FEATURE-AUDIT.md](audits/FEATURE-AUDIT.md) | Feature- und Produktreife-Audit (Score: 6.1/10) |
| [PLUGIN-AUDIT.md](audits/PLUGIN-AUDIT.md) | Plugin-System-Audit (Score: 5.1/10) |
| [THEME-AUDIT.md](audits/THEME-AUDIT.md) | Theme-System-Audit (Score: 4.7/10) |

---

## workflow/ – Operative Workflows

| Datei | Beschreibung |
|-------|-------------|
| [PLUGIN-REGISTRATION-WORKFLOW.MD](workflow/PLUGIN-REGISTRATION-WORKFLOW.MD) | Plugin-Registrierung & Member-Dashboard (1022 Zeilen) |
| [SECURITY-HARDENING-WORKFLOW.md](workflow/SECURITY-HARDENING-WORKFLOW.md) | System-Härtung Phase 0–3 |
| [PLUGIN-DEVELOPMENT-WORKFLOW.md](workflow/PLUGIN-DEVELOPMENT-WORKFLOW.md) | Plugin von Null bis Deployment |
| [THEME-DEVELOPMENT-WORKFLOW.md](workflow/THEME-DEVELOPMENT-WORKFLOW.md) | Theme-Entwicklung Schritt für Schritt |
| [CONTENT-MANAGEMENT-WORKFLOW.md](workflow/CONTENT-MANAGEMENT-WORKFLOW.md) | Inhalte erstellen, SEO, Publish-Prozess |
| [MEDIA-UPLOAD-WORKFLOW.md](workflow/MEDIA-UPLOAD-WORKFLOW.md) | Upload-Pipeline: MIME, EXIF, WebP |
| [USER-MANAGEMENT-WORKFLOW.md](workflow/USER-MANAGEMENT-WORKFLOW.md) | Benutzer, Rollen, RBAC, DSGVO |
| [BACKUP-RESTORE-WORKFLOW.md](workflow/BACKUP-RESTORE-WORKFLOW.md) | Backup erstellen, Restore, Rollback |
| [UPDATE-DEPLOYMENT-WORKFLOW.md](workflow/UPDATE-DEPLOYMENT-WORKFLOW.md) | CMS-Update, SHA-256-Verifikation, Deployment |
| [MARKETPLACE-WORKFLOW.md](workflow/MARKETPLACE-WORKFLOW.md) | Plugin/Theme aus Marketplace installieren |
| [API-INTEGRATION-WORKFLOW.md](workflow/API-INTEGRATION-WORKFLOW.md) | REST-API, Webhooks, externe Integrationen |
| **Plugin-Konzept-Workflows** | | 
| [NEWSLETTER-PLUGIN-WORKFLOW.md](workflow/NEWSLETTER-PLUGIN-WORKFLOW.md) | Double-Opt-In, Kampagnen, DSGVO |
| [FORUM-PLUGIN-WORKFLOW.md](workflow/FORUM-PLUGIN-WORKFLOW.md) | Threads, Moderation, Volltext-Suche |

---

## Direktlinks für häufige Aufgaben

| Aufgabe | Dokument |
|---------|----------|
| Erstinstallation | [INSTALLATION.md](INSTALLATION.md) |
| Admin-Login | [admin/README.md#1-zugang--login](admin/README.md) |
| Neuen User anlegen | [admin/GUIDE.md](admin/GUIDE.md) |
| Plugin 10min Quickstart | [plugins/GUIDE.md](plugins/GUIDE.md) |
| Plugin entwickeln (vollständig) | [workflow/PLUGIN-DEVELOPMENT-WORKFLOW.md](workflow/PLUGIN-DEVELOPMENT-WORKFLOW.md) |
| Theme erstellen | [workflow/THEME-DEVELOPMENT-WORKFLOW.md](workflow/THEME-DEVELOPMENT-WORKFLOW.md) |
| Hooks nutzen | [core/HOOKS-REFERENCE.md](core/HOOKS-REFERENCE.md) |
| DB-Queries schreiben | [core/CORE-CLASSES.md](core/CORE-CLASSES.md#2-database) |
| Sicherheit (CSRF/XSS) | [core/SECURITY.md](core/SECURITY.md) |
| System härten (Produktion) | [workflow/SECURITY-HARDENING-WORKFLOW.md](workflow/SECURITY-HARDENING-WORKFLOW.md) |
| Backup erstellen | [workflow/BACKUP-RESTORE-WORKFLOW.md](workflow/BACKUP-RESTORE-WORKFLOW.md) |
| CMS updaten | [workflow/UPDATE-DEPLOYMENT-WORKFLOW.md](workflow/UPDATE-DEPLOYMENT-WORKFLOW.md) |
| Medien hochladen (sicher) | [workflow/MEDIA-UPLOAD-WORKFLOW.md](workflow/MEDIA-UPLOAD-WORKFLOW.md) |
| Benutzer verwalten | [workflow/USER-MANAGEMENT-WORKFLOW.md](workflow/USER-MANAGEMENT-WORKFLOW.md) |
| Plugin aus Marketplace | [workflow/MARKETPLACE-WORKFLOW.md](workflow/MARKETPLACE-WORKFLOW.md) |
| API-Endpunkt registrieren | [workflow/API-INTEGRATION-WORKFLOW.md](workflow/API-INTEGRATION-WORKFLOW.md) |
| Projektstatus prüfen | [feature/PROJEKT-STATUS.md](feature/PROJEKT-STATUS.md) |
| Roadmap lesen | [feature/ROADMAP_FEB2026.md](feature/ROADMAP_FEB2026.md) |
| Alle Features im Überblick | [feature/CORE-CMS-FEATURES.md](feature/CORE-CMS-FEATURES.md) |
| Neue Plugin-Ideen | [feature/NEW-PLUGIN-CONCEPTS.md](feature/NEW-PLUGIN-CONCEPTS.md) |
| Systemstatus prüfen | [core/STATUS.md](core/STATUS.md) |
