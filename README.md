# 365CMS.DE

<div align="center">

[![Version](https://img.shields.io/badge/version-2.5.0-blue.svg)](Changelog.md)
![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white)
![MariaDB](https://img.shields.io/badge/MariaDB-10.3%2B-003545?logo=mariadb&logoColor=white)
![Status](https://img.shields.io/badge/status-aktiv-success)

**Sicheres, modulares und erweiterbares Content-Management-System mit eigenem Admin-Center, Theme-System, Plugin-Ökosystem und umfassender Projektdokumentation.**

[Website](https://365cms.de) • [Dokumentationsindex](DOC/INDEX.md) • [Changelog](Changelog.md) • [Theme-Doku](DOC/theme/README.md) • [Plugin-Doku](DOC/plugins/GUIDE.md)

</div>

---

## ✨ Warum 365CMS?

365CMS verbindet klassischen CMS-Content, Mitgliederbereiche, SEO-/Performance-Werkzeuge, Theme-Customizing und Plugin-Erweiterbarkeit in einer konsistenten PHP-Architektur. Der Fokus liegt auf **betriebssicherem Self-Hosting**, **klarer Admin-Struktur**, **lokal dokumentierten Workflows** und **sauber integrierbaren Erweiterungen**.

| Bereich | Highlights |
|---|---|
| **Core** | OOP-/Singleton-Architektur, Hook-System, Router, Services, REST-nahe API-Bausteine |
| **Admin** | Dashboard, Benutzer & Rollen, Inhalte, Medien, SEO, Performance, System, Updates |
| **Content** | Seiten, Beiträge, Landing-Pages, TOC, Featured Images, Revisionen |
| **Business** | Aboverwaltung, Orders, Checkout, Gruppen, Legal-Sites, DSGVO-Workflows |
| **Extensibility** | Theme-System, Theme-Customizer, Plugin-Marketplace, Hooks & Filters |
| **Operations** | Monitoring, Cron-Checks, Cache-Management, Audit-Logs, Backups, Update-Workflows |

## 🧩 Funktionsumfang im Überblick

### Core & Plattform

- Modulare Kernarchitektur mit Services, Hooks, Theme- und Plugin-Management
- PDO-basierte Datenbankzugriffe mit Prepared Statements
- Admin-Center mit klar getrennten Hauptbereichen für SEO, Performance, System, Info und Diagnose
- REST-/API-nahe Integrationen für Frontend, Medien und interne Prozesse
- Debug- und Audit-Logging für Entwicklung, Betrieb und sicherheitsrelevante Aktionen

### Content & Redaktion

- Seitenverwaltung mit SEO-Feldern, Slugs, Revisionen und Lösch-Workflow
- Beitragssystem mit Kategorien, Tags, Featured Images und Blog-Routing
- Landing-Page-Builder mit Sektionslogik
- Inhaltsverzeichnis über `[cms_toc]`
- Featured-Image- und Medienauswahl aus der integrierten Media-Library

### Medien, SEO & Performance

- Medienverwaltung mit Upload, Suche, Kategorien, Listen-/Grid-Ansicht und Media-Proxy
- WebP-Massenkonvertierung mit Referenz-Updates in bekannten Datenquellen
- SEO-Suite für Audit, Meta, Social, Schema, Sitemap und technisches SEO
- Performance-Center für Cache, Datenbank, Sessions, Medien und Laufzeitanalyse
- Monitoring-Unterseiten für Response-Time, Disk-Usage, Cron, Health-Check und E-Mail-Alerts

### Benutzer, Security & DSGVO

- Dynamische Rollen- und Rechteverwaltung (RBAC)
- Login-/Registrierungslogik, Failed-Login-Tracking, Rate Limiting, CSRF-Schutz
- Firewall, AntiSpam, Security-Audit und blockierte IPs
- DSGVO-Module für Cookie-Management, Datenauskunft und Datenlöschung
- Legal-Sites-Generator mit Auto-Verknüpfungen zu Cookie-, Checkout- und Abo-Einstellungen

### Themes, Plugins & Erweiterbarkeit

- Theme-System mit Live-Customizer, Code-Editor und lokalen Fonts
- Theme- und Plugin-Marketplace
- Eigene Theme-Repositories und Plugin-Repositories im Workspace
- Hook-System für Admin, Frontend, Member und Systemprozesse
- Dokumentierte Workflows für Erweiterungen, Deployments und Integrationen

## 🏗️ Architektur & Workspace

Die aktuelle Arbeitsumgebung ist in drei Repositories aufgeteilt:

| Repository | Zweck |
|---|---|
| `365CMS.DE/` | Haupt-Repository mit Core, Admin, Member, Runtime-Assets und zentraler Dokumentation |
| `365CMS.DE-THEME/` | Theme-Repository mit allen ausgelieferten Themes und Theme-spezifischer Doku (noch Private)|
| `365CMS.DE-PLUGINS/` | Plugin-Repository mit eigenständigen Plugins und Plugin-Dokumentation (noch Private)|

### Hauptstruktur des Kern-Repositories

```text
365CMS.DE/
├── CMS/                 # Laufzeit-Core, Admin, Services, Assets, Routing
├── DOC/                 # Zentrale lokale Projektdokumentation
├── ASSETS/              # Staging-/Quell-Assets
├── TEST/                # Test- und Audit-Artefakte
├── README.md            # Projektüberblick
└── Changelog.md         # Release-Historie
```

### Wichtige Laufzeitbereiche

| Pfad | Inhalt |
|---|---|
| `CMS/core/` | Core-Klassen, Router, Security, Database, Services |
| `CMS/admin/` | Admin-Seiten, Module, Partials, Diagnose, UI |
| `CMS/member/` | Mitgliederbereich und zugehörige Views/Controller |
| `CMS/assets/` | Produktive Vendor-Bundles und Runtime-Assets |
| `DOC/` | Admin-, Core-, Theme-, Member-, Workflow- und Audit-Dokumentation |

## 🚀 Schnellstart

### Voraussetzungen

- PHP `8.3+`
- MySQL `5.7+` oder MariaDB `10.3+`
- Apache `2.4+` mit `mod_rewrite`
- PHP-Erweiterungen: `PDO`, `pdo_mysql`, `mbstring`, `json`

### Installation

1. Projekt auf den Webserver kopieren.
2. Datenbank-Zugang in `config.php` bzw. der Installationsroutine hinterlegen.
3. Browser öffnen und `install.php` aufrufen.
4. Admin-Konto anlegen.
5. Nach der Installation `install.php` entfernen.

### Empfehlenswerte Nacharbeiten

- HTTPS und Rewrite-Konfiguration prüfen
- Cache-/Upload-/Log-Verzeichnisse beschreibbar machen
- Mail-Transport konfigurieren
- LDAP optional in `CMS/config/app.php` aktivieren
- Theme auswählen und Theme-Customizer prüfen

## 📚 Dokumentation

Der schnellste Einstieg ist der lokale Dokumentationsindex unter [`DOC/INDEX.md`](DOC/INDEX.md).

| Thema | Dokument |
|---|---|
| Gesamtüberblick | [`DOC/README.md`](DOC/README.md) |
| Installation | [`DOC/INSTALLATION.md`](DOC/INSTALLATION.md) |
| Core-Architektur | [`DOC/core/ARCHITECTURE.md`](DOC/core/ARCHITECTURE.md) |
| Services | [`DOC/core/SERVICES.md`](DOC/core/SERVICES.md) |
| Hooks | [`DOC/core/HOOKS-REFERENCE.md`](DOC/core/HOOKS-REFERENCE.md) |
| Admin-Bereich | [`DOC/admin/README.md`](DOC/admin/README.md) |
| Medien-Workflow | [`DOC/workflow/MEDIA-UPLOAD-WORKFLOW.md`](DOC/workflow/MEDIA-UPLOAD-WORKFLOW.md) |
| Update/Deployment | [`DOC/workflow/UPDATE-DEPLOYMENT-WORKFLOW.md`](DOC/workflow/UPDATE-DEPLOYMENT-WORKFLOW.md) |
| API-Integration | [`DOC/workflow/API-INTEGRATION-WORKFLOW.md`](DOC/workflow/API-INTEGRATION-WORKFLOW.md) |
| Theme-Entwicklung | [`DOC/theme/THEME-DEVELOPMENT.md`](DOC/theme/THEME-DEVELOPMENT.md) |
| Plugin-Entwicklung | [`DOC/plugins/PLUGIN-DEVELOPMENT.md`](DOC/plugins/PLUGIN-DEVELOPMENT.md) |
| Asset-Übersicht | [`DOC/ASSET.md`](DOC/ASSET.md) |

## 🔌 Erweiterbarkeit

365CMS ist nicht als statischer Monolith gedacht, sondern als erweiterbare Plattform:

- **Themes** können Templates, Partials, JavaScript und Design-Tokens komplett überschreiben.
- **Plugins** hängen sich über `CMS\Hooks` in Admin, Frontend, Member und Systemprozesse ein.
- **Services** kapseln Querschnittsfunktionen wie Mail, Search, Cookie Consent, Translation oder SEO.
- **Dokumentierte Workflows** helfen dabei, Features konsistent in Betrieb und Entwicklung einzubetten.

## 🛡️ Security, Betrieb & Wartung

| Bereich | Stand |
|---|---|
| **Security** | CSRF, XSS-Escaping, Prepared Statements, Login-Limits, Audit-Log, Firewall |
| **Operations** | Cache-Management, DB-Checks, Cron-Monitoring, Disk-Usage, Health-Check |
| **Mail** | Symfony Mailer/Mime, Queue-Verarbeitung, Retry-Backoff, Transportdiagnose |
| **Auth** | Rollen, Capabilities, Member-Dashboard, LDAP-Optionen, Login-Protokolle |
| **Compliance** | Cookie-Consent, Datenexport, Löschanfragen, Legal-Sites |

## 🧪 Technologie-Stack & wichtige Bundles

| Bereich | Eingesetzte Komponenten |
|---|---|
| **Backend** | PHP 8.3+, PDO, MySQL/MariaDB |
| **Mail** | Symfony Mailer, Symfony Mime, Mail Queue, Microsoft-365/XOAuth2-Vorbereitung |
| **Security/Auth** | HTMLPurifier, JWT, WebAuthn, TwoFactorAuth, LdapRecord |
| **Editor & UI** | SunEditor, Editor.js, Tabler, Grid.js |
| **Media** | FilePond, elFinder, PhotoSwipe, WebP-Workflows |
| **SEO & Content** | Sitemap, Schema, Canonicals, Robots, TOC, Analytics-Bausteine |

Die vollständige Asset-Dokumentation liegt in [`DOC/ASSET.md`](DOC/ASSET.md) sowie unter [`DOC/assets/`](DOC/assets/).

## 👨‍💻 Maintainer, Support & Lizenz

- **Entwicklung:** Andreas Hepp
- **Web:** [365CMS.DE](https://365cms.de) · [PhinIT.DE](https://phinit.de)
- **Support:** GitHub Issues / projektspezifische Dokumentation im `DOC/`-Baum
- **Lizenz:** Freie Verwendung für private und geschäftliche Projekte

---

**Kurz gesagt:** `365CMS.DE` ist die Core- und Betriebszentrale, `365CMS.DE-THEME` liefert das Design, `365CMS.DE-PLUGINS` die Fachmodule — zusammen ergibt das eine angenehm modulare CMS-Werkbank. 🛠️