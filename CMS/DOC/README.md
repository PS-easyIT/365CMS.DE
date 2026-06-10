# 365CMS – Dokumentation

> **Stand:** 2026-06-10 | **Version:** 3.3.47 | **Status:** Aktuell

Willkommen in der Dokumentation von **365CMS** — dem modularen PHP-CMS für DACH-Profis.
Diese Datei ist der zentrale **Doku-Hub**: Sie verlinkt in alle Fachbereiche. Für das
Projekt-Aushängeschild (Pitch, Quick Start, Tech-Stack) siehe das
[Root-`README.md`](../README.md), für die Versionshistorie den [`CHANGELOG.md`](../CHANGELOG.md).

## Inhaltsverzeichnis
- [Beschreibung](#beschreibung)
- [Schnelleinstieg](#schnelleinstieg)
- [Systemvoraussetzungen](#systemvoraussetzungen)
- [Dokumentationsstruktur](#dokumentationsstruktur)
- [Verzeichnisstruktur des CMS](#verzeichnisstruktur-des-cms)
- [Sicherheit & Audits](#sicherheit--audits)
- [Siehe auch](#siehe-auch)

---

## Beschreibung

365CMS ist ein modulares, eigenständiges Content-Management-System auf Basis von
**PHP 8.4+**, **MySQL/MariaDB** und Vanilla JavaScript. Die Architektur trennt klar
zwischen schlanken Entry-Routen, Fachmodulen und Views, bietet ein **Hook-/Event-System**
für Plugins, ein **capability-basiertes Rechtesystem**, einen **Plugin-/Theme-Marktplatz**
sowie integrierte **KI-Services**, eine **SEO-Suite**, einen **Mitgliederbereich** und
**DSGVO-Bausteine**.

---

## Schnelleinstieg

| Ich möchte … | Dokument |
|---|---|
| 365CMS installieren | [INSTALLATION.md](INSTALLATION.md) |
| den Admin-Bereich verstehen | [admin/README.md](admin/README.md) |
| Rollen & Rechte einrichten | [admin/users-groups/RBAC.md](admin/users-groups/RBAC.md) |
| KI-Services nutzen | [ai/AI-SERVICES.md](ai/AI-SERVICES.md) |
| SEO konfigurieren | [admin/seo/README.md](admin/seo/README.md) |
| Sicherheit absichern | [admin/security/README.md](admin/security/README.md) |
| die Dateistruktur nachvollziehen | [CMSFILESTRUCTUR.md](CMSFILESTRUCTUR.md) · [FILELIST.md](FILELIST.md) |
| Assets/Bibliotheken nachschlagen | [ASSET.md](ASSET.md) |

---

## Systemvoraussetzungen

| Komponente | Minimum | Empfohlen |
|---|---|---|
| PHP | 8.4 | 8.4+ |
| MySQL / MariaDB | 8.0 / 10.6 | 8.0+ / 10.11+ |
| Webserver | Apache 2.4 (`mod_rewrite`) / Nginx 1.18 | aktuelle stabile Version |
| PHP-Erweiterungen | `pdo_mysql`, `mbstring`, `json`, `openssl`, `fileinfo` | + `curl`, `gd`/`imagick`, `zip`, `intl` |
| Arbeitsspeicher | 128 MB | 256 MB+ |

Details: [INSTALLATION.md → Systemvoraussetzungen](INSTALLATION.md#systemvoraussetzungen).

---

## Dokumentationsstruktur

### 🛠️ Administration — [`admin/`](admin/README.md)

| Bereich | Einstieg | Themen |
|---|---|---|
| Dashboard | [admin/dashboard/](admin/dashboard/README.md) | KPI-Übersicht, Widgets, Favoriten |
| Seiten & Beiträge | [admin/pages-posts/](admin/pages-posts/README.md) | Seiten, Beiträge, Kommentare, Tabellen, TOC, Settings, Hub-Sites |
| Medien | [admin/media/](admin/media/README.md) | Bibliothek, WebP/Thumbnails, Orphans, Duplikate |
| Benutzer & Gruppen | [admin/users-groups/](admin/users-groups/README.md) | Users, Groups, [RBAC](admin/users-groups/RBAC.md), Auth-Settings |
| Mitgliederbereich | [admin/member/](admin/member/README.md) | Member-Dashboard-Konfiguration |
| Aboverwaltung | [admin/subscription/](admin/subscription/README.md) | Pakete, Bestellungen, Subscription-System |
| Themes & Design | [admin/themes-design/](admin/themes-design/README.md) | Theme-Editor, Menüs, Fonts, Landing-Pages, Customizer, Login-Page |
| SEO | [admin/seo/](admin/seo/README.md) | SEO, Analytics, Redirects |
| Performance | [admin/performance/](admin/performance/README.md) | Cache, DB, Media, Sessions |
| Recht & Datenschutz | [admin/legal/](admin/legal/README.md) | Cookies, DSGVO, Löschanfragen |
| Sicherheit | [admin/security/](admin/security/README.md) | Firewall, AntiSpam, Security-Audit |
| Plugins | [admin/plugins/](admin/plugins/README.md) | Plugins, Marktplatz, Updates |
| System | [admin/system-settings/](admin/system-settings/README.md) | Settings, Backup, Updates, AI-Services, System |
| Info & Diagnose | [admin/info/](admin/info/README.md) · [admin/diagnose/](admin/diagnose/README.md) | Systeminfo, Monitoring |
| Landing-Page | [admin/landing-page/](admin/landing-page/README.md) | Landing-Page-Builder |

Weitere Admin-Referenzen: [Panel-Integration](admin/PANEL-INTEGRATION.md) · [Dateistruktur](admin/FILESTRUCTURE.md) · [Guide](admin/GUIDE.md) · [Prüf-Checkliste](admin/PRUEF-CHECKLISTE.md).

### 🤖 KI — [`ai/`](ai/AI-SERVICES.md)
Provider, Übersetzung, Content-/SEO-Generierung, Prompt-Vorlagen, Quotas, Logging.

### 📦 Assets — [`assets/`](ASSET.md)
Runtime-Bibliotheken (CSS/JS/PHP-Libs), Synchronisations- und Build-Regeln.
Übersicht: [ASSET.md](ASSET.md) · [ASSETS_NEW.md](ASSETS_NEW.md) · [ASSETS_OwnAssets.md](ASSETS_OwnAssets.md).

### 🗂️ Struktur & Referenz
[CMSFILESTRUCTUR.md](CMSFILESTRUCTUR.md) · [FILELIST.md](FILELIST.md) · [DEVLIST.md](DEVLIST.md) · [INDEX.md](INDEX.md).

---

## Verzeichnisstruktur des CMS

```
CMS/
├── admin/         # Backend: Entry-Routen, Module (admin/modules), Views (admin/views)
├── core/          # Kern: Router, Hooks, RBAC, Security, Manager, Services, Auth
├── includes/      # Globale Helfer (Escaping, Roles, Redirects/Auth, Options-Runtime)
├── member/        # Mitgliederbereich (/member)
├── plugins/       # Plugins
├── themes/        # Themes
├── views/         # Auth-/Public-Templates
├── assets/        # Runtime-Bibliotheken
├── config/        # Konfiguration (app.php) + Schutz-.htaccess
├── marketplace/   # Marktplatz-Manifeste (core/plugins/themes)
├── uploads/       # Hochgeladene Dateien
├── vendor/        # gebündelte Abhängigkeiten (u. a. dompdf)
├── DOC/           # Diese Dokumentation
├── index.php      # Haupt-Einstiegspunkt
├── install.php    # Installer
└── cron.php       # geplante Aufgaben
```

Ausführlich: [CMSFILESTRUCTUR.md](CMSFILESTRUCTUR.md).

---

## Sicherheit & Audits

365CMS folgt dem Prinzip **Security by default**. Interne Code-Audits sind dokumentiert:

- [AUDIT_core_2026-06-10.md](AUDIT_core_2026-06-10.md) — Kern-Framework
- [AUDIT_admin_2026-06-10.md](AUDIT_admin_2026-06-10.md) — Admin-Bereich
- [AUDIT_member-includes-views_2026-06-10.md](AUDIT_member-includes-views_2026-06-10.md) — Mitgliederbereich, Helfer, Auth-View

Ergebnis über alle Läufe: **0 kritische Funde**, alle gefundenen Defense-in-Depth-Punkte behoben.
Sicherheitsthemen im Betrieb: [admin/security/](admin/security/README.md).

---

## Siehe auch

- [Root-README](../README.md) — Projektüberblick (DE/EN)
- [CHANGELOG](../CHANGELOG.md) — Versionshistorie
- [INSTALLATION.md](INSTALLATION.md) — Installation & Produktions-Checkliste
- [INDEX.md](INDEX.md) — vollständiger Doku-Index
