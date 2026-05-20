# 365CMS.DE

**Version:** [3.0.14](Changelog.md) · **Status:** Stable · **PHP:** 8.4+ · **MySQL:** 5.7+ · **MariaDB:** 10.3+

<div align="center">

### Content. Members. EditorJS. SEO. Themes. Operations.

**365CMS ist eine selbst gehostete CMS- und Portalplattform für Projekte, die Inhalte, Mitglieder, Design, Sicherheit, Datenschutz und Betrieb nicht als Flickenteppich betreiben möchten.**

**365CMS is a self-hosted CMS and portal platform for projects that want content, members, design, security, privacy and operations in one coherent system.**

`Self-hosted` · `WordPress-like EditorJS` · `Members built in` · `Themes` · `Plugins` · `SEO` · `GDPR` · `Operations`

[🌐 Website](https://365cms.de) · [🚀 Installation](CMS/DOC/INSTALLATION.md) · [📚 Dokumentation](CMS/DOC/INDEX.md) · [🎨 Themes](CMS/DOC/theme/README.md) · [🔌 Plugins](CMS/DOC/plugins/GUIDE.md)

</div>

---

## 🇩🇪 Deutsch

### Warum 365CMS?

365CMS bündelt die wichtigsten Bausteine für moderne Websites, Portale und geschützte Plattformen in einem System. Redaktion, Medien, Mitglieder, Pakete, Rollen, SEO, Themes, Datenschutz und Betrieb liegen nicht verstreut in mehreren Tools, sondern greifen über eine gemeinsame Admin-Oberfläche ineinander.

Das Ziel ist ein CMS, das sich im Alltag professionell anfühlt: klar strukturiert, sicher, erweiterbar, performant und ohne dekorative Oberfläche, die echte Arbeit behindert.

### Für welche Projekte?

365CMS eignet sich besonders für:

- Unternehmenswebsites mit Redaktion, SEO und Rollenmodell
- Content-Hubs, Magazine, Wissensplattformen und Portale
- Mitgliederbereiche mit Profilen, Benachrichtigungen und geschützten Funktionen
- Agentur- und Kundenprojekte mit Theme-, Plugin- und Branding-Anforderungen
- Self-hosted Setups mit Datenschutz-, Sicherheits- und Betriebsanforderungen
- Plattformen mit Paketen, Abos, Gruppen oder rollenabhängigen Zugriffen

Für reine Mini-Onepager ohne Ausbauperspektive ist 365CMS bewusst mehr Plattform als nötig. Für langfristige Projekte mit Wachstum, Betrieb und redaktionellem Alltag ist genau das der Punkt.

### WordPress-ähnlicher EditorJS

Der Seiten- und Beitragseditor basiert auf EditorJS und wurde für einen WordPress-ähnlichen Redaktionsfluss erweitert. Ziel ist ein Block-Editor, der Inhalte strukturiert speichert, aber im Admin vertraut bedienbar bleibt.

Das Bedienverhalten orientiert sich bewusst an einem Gutenberg-/WordPress-Canvas: Redakteurinnen und Redakteure fügen Blöcke über einen gruppierten Inserter oder eine Commandbar ein, verschieben Blöcke per Drag & Drop, nutzen Undo/Redo für schnelle Korrekturen und wählen je nach Inhalt Text-, Medien-, Layout- oder Spezialblöcke aus. Der Editor bleibt dabei datengetrieben: gespeichert wird strukturiertes EditorJS-JSON, nicht ungeprüftes HTML.

Wichtige Eigenschaften:

- Blockbasierter Editor für Seiten und Beiträge
- Text, Überschriften, Listen, Checklisten, Zitate, Tabellen, Code, Trennlinien und Abstände
- Abstandsblöcke mit normalisierten Presets oder Pixelwerten, die im Public-Rendering erhalten bleiben
- Bildblöcke mit Upload, Live-Vorschau, Ausrichtung, Breite, Rahmen, Rundung, Hintergrund und Schatten
- Mehrbild-Galerien mit Upload, Vorschau, Sortierung und Spaltensteuerung
- Inline-Formatierungen wie fett, kursiv, unterstrichen, Code, Links und Spoiler
- WordPress-ähnlichere Admin-Bedienung mit Block-Inserter, Commandbar, Drag & Drop, Undo/Redo und Read-only-kompatiblen Tool-Kontexten
- Blockkarten und Schnellaktionen für typische Redaktionsmuster statt technischer JSON-Bearbeitung
- Theme-nahe Editor-Vorschau durch Auswertung des aktiven Themes
- Public-Rendering über einen eigenen Sanitizer/Renderer statt ungeprüfter HTML-Ausgabe
- Entwurfsanzeige im Public-Bereich für angemeldete Autoren und berechtigte Admins

Die gespeicherten EditorJS-Daten werden serverseitig sanitisiert und im Frontend als sauberes HTML gerendert. So bleiben Struktur, Formatierungen und Blöcke auch auf Live- und Entwurfsseiten erhalten, ohne die Sicherheitsgrenzen des CMS aufzugeben.

### Kernbereiche

| Bereich | Was 365CMS liefert |
|---|---|
| **Content & Publishing** | Seiten, Beiträge, Kategorien, Tags, Revisionen, Entwürfe, Public-Preview und EditorJS-Blockinhalte |
| **Medien** | Medienbibliothek, Uploads, Ordner, Kategorien, Bildverwendung, Beitrags-/Seitenbilder und kontrollierte Medienauslieferung |
| **Mitglieder** | Member-Dashboard, Profile, Nachrichten, Benachrichtigungen, Favoriten und geschützte Bereiche |
| **Benutzer & Rollen** | Rollen, Capabilities, Gruppen, Rechteprüfung und sichere Auth-Flows |
| **Business & Pakete** | Pakete, Bestellungen, Abos, Limits, Gruppenlogik und Zugriffskontrolle |
| **SEO** | Meta-Daten, Social-Daten, Sitemap, Robots, Redirects, 404-Monitoring, IndexNow und strukturierte Daten |
| **Themes & Design** | Theme-System, Theme-Auswahl, Customizer, Menüs, lokale Fonts, Loginpage und Branding-Pfade |
| **Plugins** | Hook-System, Plugin-Verwaltung, Erweiterungspunkte und modulare Integrationen |
| **Datenschutz** | Legal-Seiten, Cookie-/Consent-Funktionen, DSGVO-Datenexporte und Löschprozesse |
| **Betrieb** | Cache, Performance, Logs, Cron, Monitoring, Backups, Updates und Diagnose |

### Admin-Erlebnis

Der Admin-Bereich folgt einem klassischen professionellen Backend-Flow: klare Header, nachvollziehbare Toolbars, ruhige Tabellen, stabile Sidebars und reduzierte UI. 365CMS priorisiert Arbeitsgeschwindigkeit, Lesbarkeit und robuste Bedienung auf Desktop und Mobile.

Typische Workflows:

- Inhalte mit EditorJS erstellen, prüfen, als Entwurf ansehen und veröffentlichen
- Medien hochladen, organisieren und in Inhalten wiederverwenden
- Rollen, Benutzer und Gruppen verwalten
- SEO, Social Preview und technische Sichtbarkeit optimieren
- Datenschutzseiten und Consent-Prozesse pflegen
- Themes, Menüs und lokale Fonts anpassen
- Plugins aktivieren und projektbezogene Funktionen ergänzen
- Systemzustand, Performance, Backups und Logs kontrollieren

### Sicherheit und Betrieb

365CMS ist auf produktive Self-hosted-Umgebungen ausgelegt. Dazu gehören CSRF-Schutz, serverseitige Sanitizer, rollenbasierte Berechtigungen, sichere Upload-Pfade, kontrollierte Medienauslieferung, Datenschutzfunktionen, Diagnosewerkzeuge und klare Update-Pfade.

Performance wird nicht als Deko-Schalter verstanden, sondern als Zusammenspiel aus Cache, Medienauslieferung, schlanken Assets, Cron, Monitoring, Datenbankpflege und sauberem Frontend-Rendering.

### Schnellstart

#### Voraussetzungen

- PHP `8.4+`
- MySQL `5.7+` oder MariaDB `10.3+`
- Apache `2.4+` mit `mod_rewrite`
- PHP-Erweiterungen: `PDO`, `pdo_mysql`, `mbstring`, `json`, `dom`

#### Installation in Kürze

1. Projekt auf den Webserver kopieren
2. Datenbank und Schreibrechte vorbereiten
3. `install.php` im Browser öffnen
4. Admin-Konto und Grundeinstellungen anlegen
5. `install.php` nach Abschluss entfernen
6. Theme, Mail, SEO, Datenschutz, Rollen und Medienpfade konfigurieren

### Dokumentation

| Thema | Dokument |
|---|---|
| Dokumentationsindex | [`CMS/DOC/INDEX.md`](CMS/DOC/INDEX.md) |
| Installation | [`CMS/DOC/INSTALLATION.md`](CMS/DOC/INSTALLATION.md) |
| Admin-Bereich | [`CMS/DOC/admin/README.md`](CMS/DOC/admin/README.md) |
| Member-Bereich | [`CMS/DOC/member/README.md`](CMS/DOC/member/README.md) |
| Core & Services | [`CMS/DOC/core/ARCHITECTURE.md`](CMS/DOC/core/ARCHITECTURE.md), [`CMS/DOC/core/SERVICES.md`](CMS/DOC/core/SERVICES.md) |
| Medien-Workflow | [`CMS/DOC/workflow/MEDIA-UPLOAD-WORKFLOW.md`](CMS/DOC/workflow/MEDIA-UPLOAD-WORKFLOW.md) |
| Theme-System | [`CMS/DOC/theme/README.md`](CMS/DOC/theme/README.md) |
| Plugin-Einstieg | [`CMS/DOC/plugins/GUIDE.md`](CMS/DOC/plugins/GUIDE.md) |
| Performance | [`CMS/DOC/admin/performance/PERFORMANCE.md`](CMS/DOC/admin/performance/PERFORMANCE.md) |
| Updates & Deployment | [`CMS/DOC/workflow/UPDATE-DEPLOYMENT-WORKFLOW.md`](CMS/DOC/workflow/UPDATE-DEPLOYMENT-WORKFLOW.md) |

### Support und Lizenz

- **Entwicklung:** Andreas Hepp
- **Web:** [365CMS.DE](https://365cms.de) · [PhinIT.DE](https://phinit.de)
- **Support:** GitHub Issues, Kontaktformular und lokale Projektdokumentation
- **Lizenz:** Freie Verwendung für private und geschäftliche Projekte

---

## 🇬🇧 English

### What is 365CMS?

365CMS is a self-hosted CMS and portal platform that combines content, media, members, packages, roles, SEO, themes, privacy and operations in one administration system.

It is designed for projects that need more than a basic page editor: editorial workflows, protected member areas, branding, extension points, security and predictable operations.

### WordPress-like EditorJS

The page and post editor is built on EditorJS and extended towards a WordPress-like block editing experience.

The editing flow intentionally behaves like a Gutenberg-style canvas: authors insert blocks through grouped block cards or a command bar, reorder content via drag & drop, use undo/redo for quick corrections and choose between text, media, layout and special-purpose blocks. The stored format remains structured EditorJS JSON rather than unchecked HTML.

Highlights:

- block-based editing for pages and posts
- paragraphs, headings, lists, checklists, quotes, tables, code, delimiters and spacers
- image blocks with upload, live preview, alignment, width, borders, rounding, background and shadow
- multi-image galleries with preview and column control
- inline formatting such as bold, italic, underline, code, links and spoiler text
- grouped block inserter, commandbar, drag & drop, undo/redo and width modes
- block cards and quick actions for editorial workflows instead of raw JSON editing
- editor preview aligned with the active theme where possible
- sanitized server-side rendering for live and draft pages
- public draft preview for logged-in authors and authorized admins

### Main capabilities

| Area | Capabilities |
|---|---|
| **Content & Publishing** | Pages, posts, categories, tags, revisions, drafts, public preview and EditorJS blocks |
| **Media** | Media library, uploads, folders, categories, usage tracking and controlled media delivery |
| **Members** | Member dashboard, profiles, notifications, messages, favorites and protected areas |
| **Users & Roles** | Users, groups, roles, capabilities and secure authentication flows |
| **Business & Packages** | Packages, orders, subscriptions, limits, groups and access logic |
| **SEO** | Metadata, social previews, sitemap, robots, redirects, 404 monitoring, IndexNow and structured data |
| **Themes & Design** | Theme system, customization, menus, local fonts, login page and branding paths |
| **Plugins** | Hooks, plugin management, extension points and modular integrations |
| **Privacy** | Legal pages, cookie/consent features, GDPR exports and deletion workflows |
| **Operations** | Cache, performance, logs, cron, monitoring, backups, updates and diagnostics |

### Quick start

Requirements:

- PHP `8.4+`
- MySQL `5.7+` or MariaDB `10.3+`
- Apache `2.4+` with `mod_rewrite`
- PHP extensions: `PDO`, `pdo_mysql`, `mbstring`, `json`, `dom`

Install flow:

1. Copy the project to your web server
2. Prepare database credentials and writable directories
3. Open `install.php` in the browser
4. Create the admin account and base configuration
5. Remove `install.php` after installation
6. Configure theme, mail, SEO, privacy, roles and media paths

### Documentation

| Topic | Document |
|---|---|
| Documentation index | [`CMS/DOC/INDEX.md`](CMS/DOC/INDEX.md) |
| Installation | [`CMS/DOC/INSTALLATION.md`](CMS/DOC/INSTALLATION.md) |
| Admin area | [`CMS/DOC/admin/README.md`](CMS/DOC/admin/README.md) |
| Member area | [`CMS/DOC/member/README.md`](CMS/DOC/member/README.md) |
| Core & services | [`CMS/DOC/core/ARCHITECTURE.md`](CMS/DOC/core/ARCHITECTURE.md), [`CMS/DOC/core/SERVICES.md`](CMS/DOC/core/SERVICES.md) |
| Media workflow | [`CMS/DOC/workflow/MEDIA-UPLOAD-WORKFLOW.md`](CMS/DOC/workflow/MEDIA-UPLOAD-WORKFLOW.md) |
| Themes | [`CMS/DOC/theme/README.md`](CMS/DOC/theme/README.md) |
| Plugins | [`CMS/DOC/plugins/GUIDE.md`](CMS/DOC/plugins/GUIDE.md) |
| Performance | [`CMS/DOC/admin/performance/PERFORMANCE.md`](CMS/DOC/admin/performance/PERFORMANCE.md) |
| Updates & deployment | [`CMS/DOC/workflow/UPDATE-DEPLOYMENT-WORKFLOW.md`](CMS/DOC/workflow/UPDATE-DEPLOYMENT-WORKFLOW.md) |

### Support and license

- **Development:** Andreas Hepp
- **Web:** [365CMS.DE](https://365cms.de) · [PhinIT.DE](https://phinit.de)
- **Support:** GitHub Issues, contact form and local project documentation
- **License:** Free to use for private and commercial projects

---

## Bereit? / Ready?

Wenn du Content, Mitglieder, SEO, Datenschutz, Design und Betrieb in einem klaren Self-hosted-System zusammenführen willst, ist 365CMS genau dafür gebaut.

If you want content, members, SEO, privacy, design and operations in one clear self-hosted system, 365CMS is built for exactly that.

[🚀 Installation starten](CMS/DOC/INSTALLATION.md) · [📚 Dokumentation öffnen](CMS/DOC/INDEX.md) · [🎨 Themes erkunden](CMS/DOC/theme/README.md) · [🔌 Plugins ansehen](CMS/DOC/plugins/GUIDE.md)

[🚀 Start installation](CMS/DOC/INSTALLATION.md) · [📚 Open documentation](CMS/DOC/INDEX.md) · [🎨 Explore themes](CMS/DOC/theme/README.md) · [🔌 Browse plugins](CMS/DOC/plugins/GUIDE.md)
