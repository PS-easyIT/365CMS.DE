# 365CMS – Projektdokumentation | Abschnitt: INDEX
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## Table of contents | Inhaltsverzeichnis

- [Quick start](#quick-start--schnellstart)
- [Core](#core--kernsystem)
- [Admin](#admin--admin-bereich)
- [Member](#member--mitgliederbereich)
- [Themes and plugins](#themes-and-plugins--themes-und-plugins)
- [Assets, workflows and audits](#assets-workflows-and-audits--assets-workflows-und-audits)
- [Task shortcuts](#task-shortcuts--direktlinks-für-häufige-aufgaben)

---

## Quick start | Schnellstart

**English**

Use this index to reach the canonical documents. Product version in code is `3.4.00`. Runtime lives in [`CMS/`](../CMS/). Documentation lives in [`DOC/`](./). Audit files live in [`AUDIT/audit/`](../AUDIT/audit/), not under `DOC/audit/`.

**Deutsch**

Dieser Index führt zu den kanonischen Dokumenten. Die Produktversion im Code ist `3.4.00`. Die Runtime liegt in [`CMS/`](../CMS/). Die Dokumentation liegt in [`DOC/`](./). Audit-Dateien liegen in [`AUDIT/audit/`](../AUDIT/audit/), nicht unter `DOC/audit/`.

| Goal / Ziel | Document / Dokument |
|---|---|
| Project overview | [README.md](README.md) |
| Developer reference | [DEVLIST.md](DEVLIST.md) |
| Runtime structure map | [FILELIST.md](FILELIST.md) |
| Repository layout | [FILESTRUCTUR.md](FILESTRUCTUR.md) |
| CMS runtime inventory | [CMSFILESTRUCTUR.md](CMSFILESTRUCTUR.md) |
| Installation | [INSTALLATION.md](INSTALLATION.md) |
| Root README | [../README.md](../README.md) |
| Changelog | [../Changelog.md](../Changelog.md) |
| Audit status | [../AUDIT/audit/BEWERTUNG.md](../AUDIT/audit/BEWERTUNG.md) |

---

## Core | Kernsystem

**English**

Core documents describe bootstrap, routing, schema, services, hooks, APIs, and security.

**Deutsch**

Die Core-Dokumente beschreiben Bootstrap, Routing, Schema, Services, Hooks, APIs und Sicherheit.

| Document / Dokument | Purpose / Zweck |
|---|---|
| [core/README.md](core/README.md) | Core documentation entry |
| [core/ARCHITECTURE.md](core/ARCHITECTURE.md) | Bootstrap, routing, services, modules |
| [core/CORE-CLASSES.md](core/CORE-CLASSES.md) | Central core classes |
| [core/DATABASE-SCHEMA.md](core/DATABASE-SCHEMA.md) | Base schema and module tables (`SCHEMA_VERSION = v22`) |
| [core/HOOKS-REFERENCE.md](core/HOOKS-REFERENCE.md) | Actions, filters, integration points |
| [core/API-REFERENCE.md](core/API-REFERENCE.md) | Technical interfaces |
| [core/SERVICES.md](core/SERVICES.md) | Service layer |
| [core/SECURITY.md](core/SECURITY.md) | Core security model |
| [core/STATUS.md](core/STATUS.md) | Implementation and operations status |
| [core/STRUCTURE.md](core/STRUCTURE.md) | Core structure notes |
| [assets/VENDOR-NETWORK-PATHS.md](assets/VENDOR-NETWORK-PATHS.md) | Vendor / third-party network path logic |

---

## Admin | Admin-Bereich

**English**

Admin docs follow `CMS/admin/` (entry PHP files, `modules/`, `views/`, `partials/`). Login branding is `/admin/cms-loginpage`. Runtime logs are `/admin/cms-logs`.

**Deutsch**

Die Admin-Dokumente folgen `CMS/admin/` (PHP-Einstiege, `modules/`, `views/`, `partials/`). Auth-Branding liegt unter `/admin/cms-loginpage`. Laufzeitlogs liegen unter `/admin/cms-logs`.

| Document / Dokument | Purpose / Zweck |
|---|---|
| [admin/README.md](admin/README.md) | Navigation and area overview |
| [admin/GUIDE.md](admin/GUIDE.md) | Operator guide |
| [admin/FILESTRUCTURE.md](admin/FILESTRUCTURE.md) | Admin file structure and routing |
| [admin/PANEL-INTEGRATION.md](admin/PANEL-INTEGRATION.md) | Custom admin page integration |
| [admin/ADMIN-API-AJAX.md](admin/ADMIN-API-AJAX.md) | Admin API / AJAX |
| [admin/PRUEF-CHECKLISTE.md](admin/PRUEF-CHECKLISTE.md) | Admin review checklist |

### Admin areas | Wichtige Teilbereiche

| Area / Bereich | Document / Dokument |
|---|---|
| Dashboard | [admin/dashboard/README.md](admin/dashboard/README.md) |
| Pages & posts | [admin/pages-posts/README.md](admin/pages-posts/README.md) |
| Media | [admin/media/README.md](admin/media/README.md) |
| Users & groups | [admin/users-groups/README.md](admin/users-groups/README.md) |
| Auth settings | [admin/users-groups/AUTH-SETTINGS.md](admin/users-groups/AUTH-SETTINGS.md) |
| Themes & design | [admin/themes-design/README.md](admin/themes-design/README.md) |
| CMS login page | [admin/themes-design/CMS-LOGINPAGE.md](admin/themes-design/CMS-LOGINPAGE.md) |
| Plugins | [admin/plugins/PLUGINS.md](admin/plugins/PLUGINS.md) |
| SEO | [admin/seo/SEO.md](admin/seo/SEO.md) |
| Legal | [admin/legal/README.md](admin/legal/README.md) |
| Security | [admin/security/README.md](admin/security/README.md) |
| Performance | [admin/performance/PERFORMANCE.md](admin/performance/PERFORMANCE.md) |
| System & maintenance | [admin/system-settings/README.md](admin/system-settings/README.md) |
| AI services (admin) | [admin/system-settings/AI-SERVICES.md](admin/system-settings/AI-SERVICES.md) |
| Diagnostics | [admin/diagnose/DIAGNOSE.md](admin/diagnose/DIAGNOSE.md) |
| System info | [admin/info/INFO.md](admin/info/INFO.md) |
| Landing pages | [admin/landing-page/LANDING-PAGE.md](admin/landing-page/LANDING-PAGE.md) |
| Subscriptions | [admin/subscription/SUBSCRIPTION-SYSTEM.md](admin/subscription/SUBSCRIPTION-SYSTEM.md) |
| Member dashboard (admin) | [admin/member/README.md](admin/member/README.md) |

### AI

**English**

Canonical AI concept documentation, including provider scope and Editor.js translation, is [ai/AI-SERVICES.md](ai/AI-SERVICES.md). Related: [ai/AI-ASSETS.md](ai/AI-ASSETS.md).

**Deutsch**

Die kanonische AI-Konzeptdokumentation mit Provider-Scope und Editor.js-Übersetzung ist [ai/AI-SERVICES.md](ai/AI-SERVICES.md). Ergänzend: [ai/AI-ASSETS.md](ai/AI-ASSETS.md).

---

## Member | Mitgliederbereich

**English**

Member documentation is **not** limited to a single README. These files exist:

**Deutsch**

Die Member-Dokumentation ist **nicht** nur in einer README gebündelt. Diese Dateien existieren:

| Document / Dokument | Purpose / Zweck |
|---|---|
| [member/README.md](member/README.md) | `/member` overview |
| [member/MEMBER-DASHBOARD.md](member/MEMBER-DASHBOARD.md) | Member dashboard |
| [member/MEMBER-ROUTES.md](member/MEMBER-ROUTES.md) | Member routes |
| [member/MEMBER-SECURITY.md](member/MEMBER-SECURITY.md) | Member security |

Runtime files are under [`CMS/member/`](../CMS/member/).

---

## Themes and plugins | Themes und Plugins

**English**

Runtime currently loads themes from `CMS/themes/` (`cms-default`) and plugins from `CMS/plugins/` (`cms-importer`). Separate workspace repos `365CMS.DE-THEME` and `365CMS.DE-PLUGINS` are source trees, not automatic runtime paths.

**Deutsch**

Die Runtime lädt Themes aus `CMS/themes/` (`cms-default`) und Plugins aus `CMS/plugins/` (`cms-importer`). Die separaten Workspace-Repos `365CMS.DE-THEME` und `365CMS.DE-PLUGINS` sind Quellbäume, keine automatischen Runtime-Pfade.

| Document / Dokument | Purpose / Zweck |
|---|---|
| [theme/README.md](theme/README.md) | Theme system |
| [theme/THEME-DEVELOPMENT.md](theme/THEME-DEVELOPMENT.md) | Theme creation |
| [theme/DEVELOPMENT.md](theme/DEVELOPMENT.md) | Theme development notes |
| [theme/DESIGN-SYSTEM.md](theme/DESIGN-SYSTEM.md) | Design system |
| [theme/COMPONENTS.md](theme/COMPONENTS.md) | Components |
| [theme/JAVASCRIPT.md](theme/JAVASCRIPT.md) | Theme JavaScript |
| [plugins/GUIDE.md](plugins/GUIDE.md) | Plugin quick start |
| [plugins/PLUGIN-DEVELOPMENT.md](plugins/PLUGIN-DEVELOPMENT.md) | Full plugin guide |
| [plugins/PLUGIN-MARKETPLACE.md](plugins/PLUGIN-MARKETPLACE.md) | Plugin marketplace |

---

## Assets, workflows and audits | Assets, Workflows und Audits

**English**

Asset docs live under [`assets/`](assets/), not in the `DOC/` root. Audit docs live under [`../AUDIT/audit/`](../AUDIT/audit/). For technical facts, prefer `DEVLIST.md`, `FILELIST.md`, and the nearest core/admin/asset document.

**Deutsch**

Asset-Dokumente liegen unter [`assets/`](assets/), nicht in der `DOC/`-Wurzel. Audit-Dokumente liegen unter [`../AUDIT/audit/`](../AUDIT/audit/). Für technische Aussagen haben `DEVLIST.md`, `FILELIST.md` und die jeweils bereichsnahen Core-/Admin-/Asset-Dokumente Vorrang.

| Area / Bereich | Documents / Dokumente |
|---|---|
| Assets | [assets/ASSET.md](assets/ASSET.md), [assets/README.md](assets/README.md), [assets/ASSETS_NEW.md](assets/ASSETS_NEW.md), [assets/ASSETS_OwnAssets.md](assets/ASSETS_OwnAssets.md), [assets/VENDOR-NETWORK-PATHS.md](assets/VENDOR-NETWORK-PATHS.md) |
| Audits | [BEWERTUNG.md](../AUDIT/audit/BEWERTUNG.md), [ToDoPrüfung.md](../AUDIT/audit/ToDoPrüfung.md), [Audit-Backlog.md](../AUDIT/audit/Audit-Backlog.md), [Audit-Content-Platform.md](../AUDIT/audit/Audit-Content-Platform.md), [Audit-Users-Commerce.md](../AUDIT/audit/Audit-Users-Commerce.md), [Audit-Design-Media.md](../AUDIT/audit/Audit-Design-Media.md), [Audit-System-Security.md](../AUDIT/audit/Audit-System-Security.md), [Audit-Live-External.md](../AUDIT/audit/Audit-Live-External.md) |
| Workflows | [workflow/](workflow/) |

### Workflows

| File / Datei | Description / Beschreibung |
|---|---|
| [CONTENT-MANAGEMENT-WORKFLOW.md](workflow/CONTENT-MANAGEMENT-WORKFLOW.md) | Create content, SEO, publish |
| [MEDIA-UPLOAD-WORKFLOW.md](workflow/MEDIA-UPLOAD-WORKFLOW.md) | Native upload pipeline, member root, bulk / rename / move |
| [UPDATE-DEPLOYMENT-WORKFLOW.md](workflow/UPDATE-DEPLOYMENT-WORKFLOW.md) | CMS update, SHA-256 verification, deployment |
| [MARKETPLACE-WORKFLOW.md](workflow/MARKETPLACE-WORKFLOW.md) | Install plugin / theme from marketplace |
| [API-INTEGRATION-WORKFLOW.md](workflow/API-INTEGRATION-WORKFLOW.md) | REST API, webhooks, external integrations |
| [NEWSLETTER-PLUGIN-WORKFLOW.md](workflow/NEWSLETTER-PLUGIN-WORKFLOW.md) | Double opt-in, campaigns, GDPR (plugin concept) |
| [FORUM-PLUGIN-WORKFLOW.md](workflow/FORUM-PLUGIN-WORKFLOW.md) | Threads, moderation, full-text search (plugin concept) |

---

## Task shortcuts | Direktlinks für häufige Aufgaben

**English**

Common tasks and the document that should be opened first.

**Deutsch**

Häufige Aufgaben und das Dokument, das zuerst geöffnet werden sollte.

| Task / Aufgabe | Document / Dokument |
|---|---|
| First install | [INSTALLATION.md](INSTALLATION.md) |
| Admin overview | [admin/README.md](admin/README.md) |
| Media management | [admin/media/README.md](admin/media/README.md) |
| Member area | [member/README.md](member/README.md) |
| Login / reset / registration branding | [admin/themes-design/CMS-LOGINPAGE.md](admin/themes-design/CMS-LOGINPAGE.md) |
| Secure uploads | [workflow/MEDIA-UPLOAD-WORKFLOW.md](workflow/MEDIA-UPLOAD-WORKFLOW.md) |
| Plugin development | [plugins/PLUGIN-DEVELOPMENT.md](plugins/PLUGIN-DEVELOPMENT.md) |
| Theme development | [theme/THEME-DEVELOPMENT.md](theme/THEME-DEVELOPMENT.md) |
| Hooks | [core/HOOKS-REFERENCE.md](core/HOOKS-REFERENCE.md) |
| Security (CSRF / XSS) | [core/SECURITY.md](core/SECURITY.md) |
| Asset status | [assets/README.md](assets/README.md) |
| New asset candidates | [assets/ASSETS_NEW.md](assets/ASSETS_NEW.md) |
| AI services concept | [ai/AI-SERVICES.md](ai/AI-SERVICES.md) |
| Replace vendor assets | [assets/ASSETS_OwnAssets.md](assets/ASSETS_OwnAssets.md) |
| Audit status | [../AUDIT/audit/BEWERTUNG.md](../AUDIT/audit/BEWERTUNG.md) |
| System status | [core/STATUS.md](core/STATUS.md) |
