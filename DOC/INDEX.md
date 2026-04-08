# 365CMS – Dokumentationsindex
> **Stand:** 2026-04-08 | **Version:** 2.9.2 | **Status:** Aktuell

## Inhaltsverzeichnis
- [Schnellstart](#schnellstart)
- [Kernsystem](#kernsystem)
- [Admin-Bereich](#admin-bereich)
- [Mitgliederbereich](#mitgliederbereich)
- [Themes und Plugins](#themes-und-plugins)
- [Assets, Workflows und Audits](#assets-workflows-und-audits)
- [Direktlinks für häufige Aufgaben](#direktlinks-für-häufige-aufgaben)

---
<!-- UPDATED: 2026-04-08 -->

## Schnellstart

| Ziel | Dokument |
|---|---|
| Projektüberblick | [README.md](README.md) |
| Entwickler-Referenz | [DEVLIST.md](DEVLIST.md) |
| Datei- & Strukturübersicht | [FILELIST.md](FILELIST.md) |
| Historischer Scope-Snapshot | [CMSFILESTRUCTUR.md](CMSFILESTRUCTUR.md) |
| Installation | [INSTALLATION.md](INSTALLATION.md) |
| Root-README | [../README.md](../README.md) |
| Changelog | [../Changelog.md](../Changelog.md) |
| Audit-Stand | [audit/BEWERTUNG.md](audit/BEWERTUNG.md) |

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
| [assets/VENDOR-NETWORK-PATHS.md](assets/VENDOR-NETWORK-PATHS.md) | separat überwachte Vendor-/Drittpfad-Netzwerklogik |

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
| Auth-Einstellungen | [admin/users-groups/AUTH-SETTINGS.md](admin/users-groups/AUTH-SETTINGS.md) |
| Themes & Design | [admin/themes-design/README.md](admin/themes-design/README.md) |
| CMS Loginpage | [admin/themes-design/CMS-LOGINPAGE.md](admin/themes-design/CMS-LOGINPAGE.md) |
| Plugins | [admin/plugins/PLUGINS.md](admin/plugins/PLUGINS.md) |
| SEO | [admin/seo/SEO.md](admin/seo/SEO.md) |
| Recht | [admin/legal/README.md](admin/legal/README.md) |
| Sicherheit | [admin/security/README.md](admin/security/README.md) |
| Performance | [admin/performance/PERFORMANCE.md](admin/performance/PERFORMANCE.md) |
| System, Betrieb & Wartung | [admin/system-settings/README.md](admin/system-settings/README.md) |
| AI Services (Konzept) | [admin/system-settings/AI-SERVICES.md](admin/system-settings/AI-SERVICES.md) |
| Diagnose & Monitoring | [admin/diagnose/DIAGNOSE.md](admin/diagnose/DIAGNOSE.md) |
| Systeminformationen | [admin/info/INFO.md](admin/info/INFO.md) |
| Landing-Pages | [admin/landing-page/LANDING-PAGE.md](admin/landing-page/LANDING-PAGE.md) |
| Aboverwaltung | [admin/subscription/SUBSCRIPTION-SYSTEM.md](admin/subscription/SUBSCRIPTION-SYSTEM.md) |

---

## Mitgliederbereich

| Dokument | Zweck |
|---|---|
| [member/README.md](member/README.md) | Gesamtüberblick über `/member` inklusive Medien-, Sicherheits- und Datenschutzpfade |

> Hinweis: Die Member-Dokumentation ist im aktuellen Stand bewusst in `member/README.md` gebündelt. Veraltete Verweise auf nicht mehr vorhandene Teil-Dokumente gelten nicht mehr als führend.

---

## Themes und Plugins

| Dokument | Zweck |
|---|---|
| [theme/README.md](theme/README.md) | Theme-System und verfügbare Leitfäden |
| [theme/THEME-DEVELOPMENT.md](theme/THEME-DEVELOPMENT.md) | Theme-Erstellung |
| [plugins/GUIDE.md](plugins/GUIDE.md) | schneller Plugin-Einstieg |
| [plugins/PLUGIN-DEVELOPMENT.md](plugins/PLUGIN-DEVELOPMENT.md) | vollständiger Plugin-Leitfaden |

---

## Assets, Workflows und Audits

| Bereich | Dokumente |
|---|---|
| Assets | [ASSET.md](ASSET.md), [assets/README.md](assets/README.md), [ASSETS_NEW.md](ASSETS_NEW.md), [ASSETS_OwnAssets.md](ASSETS_OwnAssets.md) |
| Audits | [audit/BEWERTUNG.md](audit/BEWERTUNG.md), [audit/PRÜFUNG.MD](audit/PRÜFUNG.MD), [audit/ToDoPrüfung.md](audit/ToDoPrüfung.md) |
| Workflows | `DOC/workflow/*.md` |

Diese Dokumente enthalten teils Live-Stände, Auditfortschritte oder operative Rezepte. Für aktuelle technische Aussagen haben `DEVLIST.md`, `FILELIST.md` und die jeweils bereichsnahen Core-/Admin-/Asset-Dokumente Vorrang. `CMSFILESTRUCTUR.md` bleibt bewusst ein engerer, historisch verifizierter Snapshot- und Prüfscope-Kontext.

### Wichtige Workflows

| Datei | Beschreibung |
|-------|-------------|
| [CONTENT-MANAGEMENT-WORKFLOW.md](workflow/CONTENT-MANAGEMENT-WORKFLOW.md) | Inhalte erstellen, SEO, Publish-Prozess |
| [MEDIA-UPLOAD-WORKFLOW.md](workflow/MEDIA-UPLOAD-WORKFLOW.md) | native Upload-Pipeline, Member-Root, Bulk-/Rename-/Move-Kontext |
| [UPDATE-DEPLOYMENT-WORKFLOW.md](workflow/UPDATE-DEPLOYMENT-WORKFLOW.md) | CMS-Update, SHA-256-Verifikation, Deployment und Beta-Smoke-Abnahme |
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
| Admin-Überblick | [admin/README.md](admin/README.md) |
| Medienverwaltung verstehen | [admin/media/README.md](admin/media/README.md) |
| Member-Medien prüfen | [member/README.md](member/README.md) |
| CMS Login/Reset/Registrierung steuern | [admin/themes-design/CMS-LOGINPAGE.md](admin/themes-design/CMS-LOGINPAGE.md) |
| Sichere Uploads nachvollziehen | [workflow/MEDIA-UPLOAD-WORKFLOW.md](workflow/MEDIA-UPLOAD-WORKFLOW.md) |
| Plugin entwickeln | [plugins/PLUGIN-DEVELOPMENT.md](plugins/PLUGIN-DEVELOPMENT.md) |
| Theme entwickeln | [theme/THEME-DEVELOPMENT.md](theme/THEME-DEVELOPMENT.md) |
| Hooks nutzen | [core/HOOKS-REFERENCE.md](core/HOOKS-REFERENCE.md) |
| Sicherheit (CSRF/XSS) | [core/SECURITY.md](core/SECURITY.md) |
| Asset-Stand prüfen | [assets/README.md](assets/README.md) |
| Neue Asset-Kandidaten bewerten | [ASSETS_NEW.md](ASSETS_NEW.md) |
| AI-Services-Konzept prüfen | [admin/system-settings/AI-SERVICES.md](admin/system-settings/AI-SERVICES.md) |
| Vendor-Assets ersetzen | [ASSETS_OwnAssets.md](ASSETS_OwnAssets.md) |
| Audit-Stand prüfen | [audit/BEWERTUNG.md](audit/BEWERTUNG.md) |
| Systemstatus prüfen | [core/STATUS.md](core/STATUS.md) |
