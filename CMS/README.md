<div align="center">

# 365CMS

### Das modulare PHP-CMS für DACH-Profis — sicher, schnell, KI-nativ.
### The modular PHP CMS for professionals — secure, fast, AI-native.

[![Version](https://img.shields.io/badge/version-3.3.47-2563eb.svg)](CHANGELOG.md)
[![Status](https://img.shields.io/badge/status-stable-16a34a.svg)](#)
[![PHP](https://img.shields.io/badge/PHP-8.4%2B-777bb4.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B%20%7C%20MariaDB%2010.6%2B-00758f.svg)](#)
[![DSGVO](https://img.shields.io/badge/DSGVO-ready-16a34a.svg)](#-recht--datenschutz--legal--privacy)
[![License](https://img.shields.io/badge/license-Source--Available%20(No--Resale)-orange.svg)](LICENSE)
[![Made in DACH](https://img.shields.io/badge/made%20in-DACH-d4af37.svg)](https://phinit.de)

**[Features](#-feature-überblick--feature-overview)** ·
**[Quick Start](#-schnellstart--quick-start)** ·
**[Sicherheit](#-sicherheit--security)** ·
**[Tech-Stack](#-tech-stack)** ·
**[Doku](#-dokumentation--documentation)** ·
**[English](#-english)**

</div>

---

> **🇩🇪 Deutsch zuerst** — die englische Kurzfassung steht [weiter unten](#-english).

## Was ist 365CMS?

**365CMS** ist ein modulares, eigenständiges Content-Management-System auf Basis von **PHP 8.4+**, **MySQL/MariaDB** und Vanilla JavaScript — gebaut für Admins, Agenturen und Unternehmen, die ein **schnelles, sicheres und wartbares** CMS ohne Framework-Ballast wollen.

Kein aufgeblähter Stack, keine 30 npm-Build-Schritte: 365CMS läuft auf klassischem PHP-Hosting genauso wie auf dedizierten Servern. Dahinter steckt eine saubere Architektur mit **Hook-/Event-System**, **Plugin- und Theme-Marktplatz**, einem **capability-basierten Rechtesystem** und einer durchgängig sicherheitsgehärteten Codebasis.

Der Fokus liegt bewusst auf dem **DACH-Markt**: DSGVO-konforme Bausteine (Cookie-Management, Auskunfts-/Löschanfragen, Legal-Sites), deutschsprachige Oberfläche und Dokumentation — plus optionale Mehrsprachigkeit (DE/EN).

```text
   ┌─────────────────────────────────────────────────────────────┐
   │  Block-Editor  ·  KI-Services  ·  SEO-Suite  ·  Mitglieder-  │
   │  bereich  ·  Subscriptions  ·  Media-Manager  ·  Marktplatz  │
   ├─────────────────────────────────────────────────────────────┤
   │            Core: Router · Hooks · RBAC · Security             │
   │              PHP 8.4   ·   MySQL / MariaDB   ·   PDO          │
   └─────────────────────────────────────────────────────────────┘
```

### Warum 365CMS?

| | |
|---|---|
| 🧩 **Modular & erweiterbar** | Hook-/Event-System, Plugins und Themes mit eigenem Marktplatz — ohne Core-Hacks. |
| 🔒 **Security by default** | Prepared Statements, CSRF, capability-RBAC, CSP/HSTS, 2FA/TOTP & Passkeys (WebAuthn), gehärtete Uploads, Audit-Log, Firewall, AntiSpam. |
| 🤖 **KI-nativ** | Integrierte AI-Services: Übersetzung, Content- & SEO-Generierung, mehrere Provider, Logging und Quotas. |
| ⚡ **Performance** | Cache-Layer (APCu + File mit HMAC), WebP/Thumbnail-Pipeline, DB-Optimierung, Critical-CSS. |
| 📈 **SEO-Suite** | Meta, Social/OG, Schema, Sitemap, Redirect-Manager, Broken-Link-Report, Live-Preview. |
| ⚖️ **DSGVO-ready** | Cookie-Manager, Auskunfts-/Löschanfragen mit Auditpflicht, Legal-Sites, self-hosted Matomo-Anbindung. |
| 🏢 **Enterprise-tauglich** | LDAP/Active-Directory (LdapRecord), Rollen & Gruppen, Backups, Update-Kanal, Betriebs-Audit. |
| 🇩🇪 **DACH-fokussiert** | Deutsche UI & Doku, EU-Compliance-Bausteine, optional zweisprachig. |

---

## 🚀 Feature-Überblick / Feature Overview

<table>
<tr>
<td width="33%" valign="top">

**📝 Inhalte & Editor**
- Block-Editor (EditorJS) mit Bild+Text-Layouts
- Seiten, Beiträge, Kommentare
- Taxonomien, Tags, Kategorien
- Tabellen & Inhaltsverzeichnis (TOC)
- Revisionsvergleich (read-only)
- Mehrsprachigkeit DE/EN

</td>
<td width="33%" valign="top">

**🤖 KI & SEO**
- AI-Übersetzung & Content-Creator
- AI-SEO-Creator, Prompt-Vorlagen
- Multi-Provider, Quotas, Logging
- SEO-Dashboard & Audit
- Meta · Social/OG · Schema · Sitemap
- Redirect-Manager & Broken-Link-Report

</td>
<td width="33%" valign="top">

**👥 Nutzer & Mitglieder**
- Capability-basiertes RBAC
- Rollen, Gruppen, Rechtematrix
- Mitgliederbereich (`/member`)
- Onboarding & Profilfelder
- Subscriptions, Pakete, Bestellungen
- CSV-Exporte

</td>
</tr>
<tr>
<td width="33%" valign="top">

**🎨 Design & Themes**
- Theme-Manager & Theme-Editor
- Menü-Editor, Landing-Pages
- Font-Manager mit Nutzungsanalyse
- Customizer & Design-Settings
- Theme-Marktplatz

</td>
<td width="33%" valign="top">

**🖼️ Medien & Performance**
- Media-Library mit Kategorien/Tags
- WebP- & Thumbnail-Jobs
- Orphan-Erkennung & Duplikat-Hash
- Cache-, DB- & Media-Performance
- Backups mit Download/Restore

</td>
<td width="33%" valign="top">

**🛡️ Sicherheit & Recht**
- Firewall, AntiSpam, Security-Audit
- 2FA/TOTP & Passkeys (WebAuthn)
- Audit-Log & Betriebs-Logs
- Cookie-Manager, DSGVO-Anfragen
- Legal-Sites, LDAP/AD-Anbindung

</td>
</tr>
</table>

> **Plugins & Marktplatz:** Erweitere 365CMS über ein request-idempotentes Plugin-System mit dynamischen Sidebar-/Submenü-Einträgen, Theme- und Plugin-Marktplatz sowie zentralem Update-Kanal.

---

## 📸 Screenshots

> _Platzhalter — Screenshots unter `DOC/assets/images/` ablegen und hier einbinden._

<div align="center">

| Dashboard | Block-Editor | SEO-Suite |
|:---:|:---:|:---:|
| _`docs-screenshot-dashboard.png`_ | _`docs-screenshot-editor.png`_ | _`docs-screenshot-seo.png`_ |

</div>

---

## ⚡ Schnellstart / Quick Start

### Voraussetzungen / Requirements

| Komponente | Minimum | Empfohlen |
|---|---|---|
| **PHP** | 8.4 | 8.4+ (mit `pdo_mysql`, `mbstring`, `json`, `openssl`, `fileinfo`) |
| **Datenbank** | MySQL 8.0 / MariaDB 10.6 | MariaDB 10.11+ |
| **Webserver** | Apache 2.4 (`mod_rewrite`) / Nginx 1.18 | aktuelle stabile Version |
| **Empfohlene Extensions** | — | `curl`, `gd`/`imagick`, `zip`, `intl` |
| **RAM** | 128 MB | 256 MB+ |

### Installation in 4 Schritten

```bash
# 1. Dateien auf den Server bringen (FTP / rsync / Deployment)
#    Die Runtime liegt unter CMS/

# 2. Web-Installer aufrufen
#    https://deine-domain.de/install.php
#    → prüft die Umgebung, fragt DB-, Site- und Admin-Daten ab,
#      schreibt CMS/config/app.php und legt das Schema an.

# 3. Nach der Installation install.php entfernen / sperren

# 4. Im Admin anmelden
#    https://deine-domain.de/admin
```

**Alternativ headless / CLI:**

```bash
php install.php \
  --db-host=localhost --db-name=cms_db \
  --db-user=cms_user --db-pass='****' \
  --admin-email=admin@example.com --admin-password='****'
```

➡️ Ausführliche Anleitung: **[DOC/INSTALLATION.md](DOC/INSTALLATION.md)**

---

## 🔒 Sicherheit / Security

365CMS ist auf **Security by default** ausgelegt. Auszug der durchgesetzten Kontrollen (durch interne Code-Audits bestätigt — siehe `DOC/AUDIT_*.md`):

- **Datenbank:** durchgängig Prepared Statements (PDO, native), Identifier-Whitelisting, kein roher User-Input in Queries.
- **Authentifizierung:** bcrypt (cost 12), 12-Zeichen-Passwort-Policy, Rate-Limiting, **2FA/TOTP** und **Passkeys/WebAuthn**.
- **Autorisierung:** capability-basiertes RBAC, zentrale Admin-Gates, `ABSPATH`-Guards gegen Direktzugriff.
- **Web:** CSRF-Tokens (timing-safe), CSP, HSTS, COOP/CORP, `X-Content-Type-Options`, sichere Sessions (`HttpOnly`, `Secure`, `SameSite=Strict`, `use_strict_mode`).
- **Inhalte:** DOM-basierter HTML-Sanitizer + HTMLPurifier gegen Stored-XSS, HEX-geflaggtes JSON in `<script>`.
- **Uploads:** Extension-Whitelist + Dangerous-Blacklist + MIME-Cross-Check + Bildvalidierung + `.htaccess`-Execution-Block; Zip-Slip-/Zip-Bomb-Schutz bei Theme-Uploads.
- **Betrieb:** Audit-Log, Firewall, AntiSpam, Backups, gehärtetes Fehler-Handling (keine Stack-Traces an den Client).

🔐 **Sicherheitslücke gefunden?** Bitte **nicht** über öffentliche Issues melden, sondern verantwortungsvoll an den Maintainer (siehe `SECURITY.md` / Kontakt). Details: [DOC/admin/security/](DOC/admin/security/README.md).

---

## 🧱 Tech-Stack

| Bereich | Eingesetzt |
|---|---|
| **Sprache / Laufzeit** | PHP 8.4+, PDO, Vanilla JavaScript |
| **Datenbank** | MySQL 8.0+ / MariaDB 10.6+ |
| **Admin-UI** | Tabler 1.4 |
| **Editor** | EditorJS 2.31.6 (Block-Editor), SunEditor (Legacy-WYSIWYG) |
| **Mail / i18n** | Symfony Mailer / Mime / Translation 8.0.8 |
| **Verzeichnisdienst** | LdapRecord 4.0.3 (LDAP / Active Directory) |
| **Suche** | TNTSearch 5.0.3 (Volltext) |
| **PDF / Bild** | dompdf, GD/Imagick, WebP-Pipeline |
| **Weitere** | Carbon 3.11.4, php-jwt, TwoFactorAuth (TOTP), HTMLPurifier, Grid.js, PhotoSwipe, FilePond |

➡️ Vollständige Asset-Übersicht: **[DOC/ASSET.md](DOC/ASSET.md)**

---

## 📚 Dokumentation / Documentation

Die komplette Dokumentation liegt unter **[`DOC/`](DOC/README.md)** — über 140 Fachdokumente.

| Einstieg | Inhalt |
|---|---|
| **[DOC/README.md](DOC/README.md)** | Doku-Hub & Navigation |
| **[DOC/INDEX.md](DOC/INDEX.md)** | Vollständiger Doku-Index |
| **[DOC/INSTALLATION.md](DOC/INSTALLATION.md)** | Installation & Produktions-Checkliste |
| **[DOC/admin/README.md](DOC/admin/README.md)** | Admin-Bereich (alle Menügruppen) |
| **[DOC/admin/security/](DOC/admin/security/README.md)** | Sicherheit: Firewall, AntiSpam, Audit |
| **[DOC/admin/seo/](DOC/admin/seo/README.md)** | SEO-Suite |
| **[DOC/admin/users-groups/RBAC.md](DOC/admin/users-groups/RBAC.md)** | Rollen & Rechte |
| **[DOC/ai/AI-SERVICES.md](DOC/ai/AI-SERVICES.md)** | KI-Services |
| **[CHANGELOG.md](CHANGELOG.md)** | Versionshistorie |

---

## 🗺️ Projektstruktur

```
CMS/
├── admin/         # Backend: Entry-Routen, Module, Views
├── core/          # Kern: Router, Hooks, RBAC, Security, Manager, Services
├── includes/      # Globale Helfer (Escaping, Roles, Redirects/Auth)
├── member/        # Mitgliederbereich (/member)
├── plugins/       # Plugins
├── themes/        # Themes
├── views/         # Auth-/Public-Templates
├── assets/        # Runtime-Bibliotheken (CSS/JS/PHP-Libs)
├── marketplace/   # Marktplatz-Manifeste (Plugins/Themes/Core)
├── uploads/       # Hochgeladene Dateien
├── DOC/           # Dokumentation
├── index.php      # Haupt-Einstiegspunkt
└── install.php    # Installer
```

---

## 🤝 Mitwirken / Contributing

Beiträge sind willkommen! Bitte beachte:

1. **Code-Stil:** PSR-12, `declare(strict_types=1)`, Type-Deklarationen, Ziel PHP 8.4.
2. **Sicherheit:** Keine rohen Queries, immer escapen/parametrisieren, CSRF auf zustandsändernden Aktionen.
3. **Doku:** Änderungen mit passenden `DOC/`-Einträgen und einem `CHANGELOG.md`-Eintrag begleiten.
4. **Branch/PR:** sprechender Branch-Name, kleine fokussierte PRs, Beschreibung des „Warum".

> Issues und Pull Requests laufen über das GitHub-Repository **[PS-easyIT/365CMS.DE](https://github.com/PS-easyIT/365CMS.DE)**.

---

## 📄 Lizenz / License

365CMS steht unter der **365CMS License (Source-Available, No-Resale)** — der Quellcode ist offen einsehbar und du darfst 365CMS **kostenlos nutzen, betreiben und anpassen** (auch kommerziell in eigenen Projekten und für Kunden). **Nicht erlaubt** ist, die Software — insbesondere unverändert und/oder unter eigenem Namen — zu **verkaufen oder weiterzuverkaufen**. Bezahlte Mehrwertleistungen rundherum (Hosting, Support, Integration, eigene Anpassungen) sind ok. Vollständiger Text: [`LICENSE`](LICENSE).

> Hinweis: Dies ist eine quelloffene, aber **nicht OSI-„Open-Source"-Lizenz** (da der Weiterverkauf eingeschränkt ist). Für eine separate Verkaufs-/Vertriebslizenz: [phinit.de](https://phinit.de).

© 2026 **PS-easyIT** · [phinit.de](https://phinit.de)

---

<div align="center">

# 🌍 English

</div>

## What is 365CMS?

**365CMS** is a modular, self-contained content management system built on **PHP 8.4+**, **MySQL/MariaDB** and vanilla JavaScript — made for admins, agencies and companies who want a **fast, secure and maintainable** CMS without framework bloat.

No heavy stack, no 30-step npm build: 365CMS runs on classic PHP hosting as well as on dedicated servers. Under the hood: a clean architecture with a **hook/event system**, a **plugin & theme marketplace**, **capability-based access control** and a consistently security-hardened codebase. While the UI and docs lead in German (DACH focus with GDPR building blocks), 365CMS ships **optional DE/EN bilingual** content.

### Highlights

- 🧩 **Modular & extensible** — hooks, plugins, themes, marketplace; no core hacks.
- 🔒 **Security by default** — prepared statements, CSRF, capability RBAC, CSP/HSTS, 2FA/TOTP & passkeys (WebAuthn), hardened uploads, audit log, firewall, anti-spam.
- 🤖 **AI-native** — built-in AI services: translation, content & SEO generation, multi-provider, quotas, logging.
- ⚡ **Performance** — APCu+file cache (HMAC-protected), WebP/thumbnail pipeline, DB optimization, critical CSS.
- 📈 **SEO suite** — meta, social/OG, schema, sitemap, redirect manager, broken-link report, live preview.
- ⚖️ **GDPR-ready** — cookie manager, data access/deletion requests with audit trail, legal sites, self-hosted Matomo.
- 🏢 **Enterprise-ready** — LDAP/Active Directory, roles & groups, backups, update channel, operational audit.

### Quick Start

**Requirements:** PHP 8.4+ · MySQL 8.0+/MariaDB 10.6+ · Apache 2.4 (`mod_rewrite`) or Nginx · extensions `pdo_mysql`, `mbstring`, `json`, `openssl`, `fileinfo`.

```bash
# 1. Upload files to your server (runtime lives in CMS/)
# 2. Open the web installer:  https://your-domain.com/install.php
# 3. Remove / lock install.php afterwards
# 4. Sign in:                 https://your-domain.com/admin
```

Headless / CLI:

```bash
php install.php --db-host=localhost --db-name=cms_db \
  --db-user=cms_user --db-pass='****' \
  --admin-email=admin@example.com --admin-password='****'
```

➡️ Full guide: **[DOC/INSTALLATION.md](DOC/INSTALLATION.md)** · Docs hub: **[DOC/README.md](DOC/README.md)** · Changelog: **[CHANGELOG.md](CHANGELOG.md)**

### Security

365CMS follows **security by default** — prepared statements, capability-based RBAC, CSRF, CSP/HSTS, secure sessions, DOM-based HTML sanitizing + HTMLPurifier, hardened file uploads with zip-slip/zip-bomb protection, 2FA/TOTP & WebAuthn passkeys, audit logging and a firewall. Internal code audits are documented under `DOC/AUDIT_*.md`.
**Found a vulnerability?** Please report it responsibly to the maintainer instead of opening a public issue.

### License

365CMS is released under the **365CMS License (Source-Available, No-Resale)** — the source is open to read and you may **use, run and modify** 365CMS for free (including commercially in your own projects and for clients). You may **not sell or resell** the Software, especially not verbatim and/or under your own name. Charging for genuine value-added services around it (hosting, support, integration, custom work) is fine. Full text: [`LICENSE`](LICENSE).

> Note: this is source-available but **not an OSI "open source" license** (resale is restricted). For a separate distribution/resale license, contact [phinit.de](https://phinit.de).

© 2026 **PS-easyIT** · [phinit.de](https://phinit.de)

---

<div align="center">

**365CMS** · v3.3.47 · Made with care in the Black Forest 🌲
[⬆ nach oben / back to top](#365cms)

</div>
