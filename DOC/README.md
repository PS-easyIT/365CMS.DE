# 365CMS – Projektdokumentation | Abschnitt: README
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## Table of contents | Inhaltsverzeichnis

- [Where to start](#where-to-start--womit-ihr-anfangen-solltet)
- [Release focus 3.4.00](#release-focus-3400--release-fokus-3400)
- [Documentation areas](#documentation-areas--dokumentationsbereiche)
- [Important notes](#important-notes--wichtige-hinweise)
- [Related entry points](#related-entry-points--verwandte-einstiege)

---

## Where to start | Womit ihr anfangen solltet

**English**

This folder is the public documentation tree for 365CMS. Runtime code lives under [`CMS/`](../CMS/). The core version constant is `3.4.00` ([`CMS/core/Version.php`](../CMS/core/Version.php), [`CMS/update.json`](../CMS/update.json), released 2026-09-05, status `stable`). PHP 8.4+ is required.

| If you want to … | start here |
|---|---|
| install a new system | [INSTALLATION.md](INSTALLATION.md) |
| understand the current runtime layout | [FILELIST.md](FILELIST.md) |
| read the technical developer reference | [DEVLIST.md](DEVLIST.md) |
| see the repository layout | [FILESTRUCTUR.md](FILESTRUCTUR.md) |
| inspect the CMS runtime inventory | [CMSFILESTRUCTUR.md](CMSFILESTRUCTUR.md) |
| study architecture | [core/ARCHITECTURE.md](core/ARCHITECTURE.md) |
| check core status | [core/STATUS.md](core/STATUS.md) |
| use the admin panel | [admin/README.md](admin/README.md) |
| configure the CMS login page | [admin/themes-design/CMS-LOGINPAGE.md](admin/themes-design/CMS-LOGINPAGE.md) |
| work on the member area | [member/README.md](member/README.md) |
| review media handling | [admin/media/README.md](admin/media/README.md) |
| check asset / vendor state | [assets/README.md](assets/README.md) |
| evaluate new asset candidates | [assets/ASSETS_NEW.md](assets/ASSETS_NEW.md) |
| review AI / translation scope | [ai/AI-SERVICES.md](ai/AI-SERVICES.md) |
| replace third-party assets step by step | [assets/ASSETS_OwnAssets.md](assets/ASSETS_OwnAssets.md) |
| develop plugins | [plugins/PLUGIN-DEVELOPMENT.md](plugins/PLUGIN-DEVELOPMENT.md) |
| develop themes | [theme/THEME-DEVELOPMENT.md](theme/THEME-DEVELOPMENT.md) |

**Deutsch**

Dieser Ordner ist der öffentliche Dokumentationsbaum von 365CMS. Der Laufzeitcode liegt unter [`CMS/`](../CMS/). Die Core-Versionskonstante ist `3.4.00` ([`CMS/core/Version.php`](../CMS/core/Version.php), [`CMS/update.json`](../CMS/update.json), veröffentlicht 2026-09-05, Status `stable`). PHP 8.4+ ist erforderlich.

| Wenn ihr … | dann startet hier |
|---|---|
| das System neu aufsetzt | [INSTALLATION.md](INSTALLATION.md) |
| die Runtime-Struktur aktuell verstehen wollt | [FILELIST.md](FILELIST.md) |
| die technische Gesamtsicht braucht | [DEVLIST.md](DEVLIST.md) |
| die Repository-Struktur verstehen wollt | [FILESTRUCTUR.md](FILESTRUCTUR.md) |
| das CMS-Runtime-Inventar braucht | [CMSFILESTRUCTUR.md](CMSFILESTRUCTUR.md) |
| die Architektur verstehen wollt | [core/ARCHITECTURE.md](core/ARCHITECTURE.md) |
| einen Release-Snapshot des Core wollt | [core/STATUS.md](core/STATUS.md) |
| das Admin-Panel nutzt | [admin/README.md](admin/README.md) |
| die CMS-Loginpage steuern wollt | [admin/themes-design/CMS-LOGINPAGE.md](admin/themes-design/CMS-LOGINPAGE.md) |
| den Member-Bereich betreut | [member/README.md](member/README.md) |
| den Medienbereich nachvollziehen wollt | [admin/media/README.md](admin/media/README.md) |
| Asset-/Vendor-Stände prüfen wollt | [assets/README.md](assets/README.md) |
| neue Asset-Kandidaten bewerten wollt | [assets/ASSETS_NEW.md](assets/ASSETS_NEW.md) |
| das AI-/Translate-Zielbild prüfen wollt | [ai/AI-SERVICES.md](ai/AI-SERVICES.md) |
| Fremd-Assets schrittweise ersetzen wollt | [assets/ASSETS_OwnAssets.md](assets/ASSETS_OwnAssets.md) |
| Plugins entwickelt | [plugins/PLUGIN-DEVELOPMENT.md](plugins/PLUGIN-DEVELOPMENT.md) |
| Themes entwickelt | [theme/THEME-DEVELOPMENT.md](theme/THEME-DEVELOPMENT.md) |

---

## Release focus 3.4.00 | Release-Fokus 3.4.00

**English**

Shipped core version: **`3.4.00`**. Source of truth:

- [`CMS/core/Version.php`](../CMS/core/Version.php) — `CURRENT = '3.4.00'`, `RELEASE_DATE = '2026-09-05'`, `STATUS = 'stable'`
- [`CMS/update.json`](../CMS/update.json) and [`CMS/marketplace/core/365cms/update.json`](../CMS/marketplace/core/365cms/update.json)
- Schema version: `SchemaManager::SCHEMA_VERSION = 'v22'`

Release notes recorded in `CMS/update.json`:

- AI Services in admin enforce provider policy, HTTPS / Ollama egress protection, atomic quotas, retry / fallback, health checks, and CSP-compliant admin assets
- There are no public AI routes and no automatic publication of AI output
- Installer and updater show installed vs. target core and schema versions
- Core update swaps preserve `config/`, uploads, cache, logs, and backups

**Deutsch**

Ausgelieferte Core-Version: **`3.4.00`**. Quellen:

- [`CMS/core/Version.php`](../CMS/core/Version.php) — `CURRENT = '3.4.00'`, `RELEASE_DATE = '2026-09-05'`, `STATUS = 'stable'`
- [`CMS/update.json`](../CMS/update.json) und [`CMS/marketplace/core/365cms/update.json`](../CMS/marketplace/core/365cms/update.json)
- Schema-Version: `SchemaManager::SCHEMA_VERSION = 'v22'`

Release-Hinweise aus `CMS/update.json`:

- AI Services erzwingen im Adminbereich Provider-Policy, HTTPS-/Ollama-Egress-Schutz, atomare Quotas, Retry/Fallback, Healthchecks und CSP-konforme Admin-Assets
- Es gibt keine öffentlichen AI-Routen und keine automatische Veröffentlichung von AI-Ergebnissen
- Installer und Updater zeigen installierte und angestrebte Core- und Schema-Versionen
- Core-Update-Swaps bewahren `config/`, Uploads, Cache, Logs und Backups

---

## Documentation areas | Dokumentationsbereiche

**English**

### Core

Documents under [`core/`](core/) describe bootstrap, routing, data model, services, hooks, and security.

### Admin

Documents under [`admin/`](admin/) follow the current sidebar and module layout in `CMS/admin/`. That includes the **CMS Loginpage** (`/admin/cms-loginpage`) and **CMS Logs** (`/admin/cms-logs`).

### Member

Documents under [`member/`](member/) describe the personal member area under `/member`, including dashboard, routes, and security.

### Theme and plugins

[`theme/`](theme/) and [`plugins/`](plugins/) contain development guides. The runtime currently ships [`CMS/themes/cms-default/`](../CMS/themes/cms-default/) and [`CMS/plugins/cms-importer/`](../CMS/plugins/cms-importer/).

### Assets, workflows, audits

- Assets: [`assets/`](assets/)
- Workflows: [`workflow/`](workflow/)
- Audits live in the repository root at [`../AUDIT/audit/`](../AUDIT/audit/), not under `DOC/audit/`

**Deutsch**

### Core

Die Kernsystem-Dokumente unter [`core/`](core/) beschreiben Bootstrap, Routing, Datenmodell, Services, Hooks und Sicherheit.

### Admin

Die Admin-Dokumente unter [`admin/`](admin/) orientieren sich an der aktuellen Sidebar- und Modulstruktur aus `CMS/admin/`. Dazu gehören die **CMS Loginpage** (`/admin/cms-loginpage`) und **CMS Logs** (`/admin/cms-logs`).

### Member

Die Dokumente unter [`member/`](member/) beschreiben den persönlichen Mitgliederbereich unter `/member`, einschließlich Dashboard, Routen und Sicherheit.

### Theme und Plugins

[`theme/`](theme/) und [`plugins/`](plugins/) enthalten Entwicklungsleitfäden. In der Runtime liegen derzeit [`CMS/themes/cms-default/`](../CMS/themes/cms-default/) und [`CMS/plugins/cms-importer/`](../CMS/plugins/cms-importer/).

### Assets, Workflows, Audits

- Assets: [`assets/`](assets/)
- Workflows: [`workflow/`](workflow/)
- Audits liegen im Repository-Root unter [`../AUDIT/audit/`](../AUDIT/audit/), nicht unter `DOC/audit/`

---

## Important notes | Wichtige Hinweise

**English**

- Installation and configuration: `CMS/config.php` is the stub; `CMS/config/app.php` is the real configuration file.
- Admin routes: use `CMS/admin/` plus the sidebar / module map in the admin docs.
- Database statements: [core/DATABASE-SCHEMA.md](core/DATABASE-SCHEMA.md) is authoritative. Schema version in code is `v22`.
- Release changes: [../Changelog.md](../Changelog.md) is the leading changelog file.
- Media and uploads: [admin/media/README.md](admin/media/README.md), [admin/media/MEDIA.md](admin/media/MEDIA.md), [workflow/MEDIA-UPLOAD-WORKFLOW.md](workflow/MEDIA-UPLOAD-WORKFLOW.md).
- Quality / audit snapshots: [`../AUDIT/audit/`](../AUDIT/audit/).
- Current structure map: [FILELIST.md](FILELIST.md).
- CMS runtime inventory: [CMSFILESTRUCTUR.md](CMSFILESTRUCTUR.md).
- Repository layout: [FILESTRUCTUR.md](FILESTRUCTUR.md).

**Deutsch**

- Für Installations- und Konfigurationsfragen gelten `CMS/config.php` als Stub und `CMS/config/app.php` als eigentliche Konfigurationsdatei.
- Für aktuelle Admin-Routen gelten `CMS/admin/` sowie Sidebar- und Modulstruktur in der Admin-Dokumentation.
- Für Datenbankaussagen ist [core/DATABASE-SCHEMA.md](core/DATABASE-SCHEMA.md) maßgeblich. Die Schema-Version im Code ist `v22`.
- Für Release-Änderungen ist [../Changelog.md](../Changelog.md) die führende Datei.
- Für Medien- und Upload-Aussagen gelten [admin/media/README.md](admin/media/README.md), [admin/media/MEDIA.md](admin/media/MEDIA.md) und [workflow/MEDIA-UPLOAD-WORKFLOW.md](workflow/MEDIA-UPLOAD-WORKFLOW.md).
- Für laufende Qualitätsstände ist [`../AUDIT/audit/`](../AUDIT/audit/) die erste Anlaufstelle.
- Für aktuelle Strukturfragen ist [FILELIST.md](FILELIST.md) die führende lesbare Strukturkarte.
- Für das CMS-Runtime-Inventar gilt [CMSFILESTRUCTUR.md](CMSFILESTRUCTUR.md).
- Für die Repository-Struktur gilt [FILESTRUCTUR.md](FILESTRUCTUR.md).

---

## Related entry points | Verwandte Einstiege

**English**

- [Documentation index](INDEX.md)
- [Root README](../README.md)
- [Project changelog](../Changelog.md)
- [Audit evaluation](../AUDIT/audit/BEWERTUNG.md)
- [CMS runtime README](../CMS/README.md)

**Deutsch**

- [Dokumentationsindex](INDEX.md)
- [Root-README](../README.md)
- [Projekt-Changelog](../Changelog.md)
- [Audit-Bewertung](../AUDIT/audit/BEWERTUNG.md)
- [CMS-Runtime-README](../CMS/README.md)


