# 365CMS – Admin-Panel Dokumentation

Das Admin-Panel ist das Herz des 365CMS. Hier verwaltet ihr alles: Benutzer, Inhalte, Themes, Plugins und Systemeinstellungen.

---

## Dokumentations-Index

### Nach Bereich

| Bereich | Dokument | Beschreibung |
|---------|----------|--------------|
| **Dashboard** | [dashboard/DASHBOARD.md](dashboard/DASHBOARD.md) | KPIs, Widgets, Schnellzugriff |
| **Benutzer** | [users-groups/USERS.md](users-groups/USERS.md) | Benutzerverwaltung komplett |
| **Benutzer** | [users-groups/RBAC.md](users-groups/RBAC.md) | Rollen- & Rechtesystem |
| **Mitglieder** | [member/README.md](member/README.md) | Member-Dashboard-Verwaltung |
| **Seiten & Beiträge** | [pages-posts/PAGES.md](pages-posts/PAGES.md) | Statische Seiten |
| **Seiten & Beiträge** | [pages-posts/POSTS.md](pages-posts/POSTS.md) | Blog-Beiträge |
| **Landing Page** | [landing-page/LANDING-PAGE.md](landing-page/LANDING-PAGE.md) | Landing Page Builder |
| **Medien** | [media/MEDIA.md](media/MEDIA.md) | Medienbibliothek |
| **Design** | [themes-design/CUSTOMIZER.md](themes-design/CUSTOMIZER.md) | Theme-Customizer |
| **Design** | [themes-design/MARKETPLACE.md](themes-design/MARKETPLACE.md) | Theme & Plugin Marketplace |
| **Plugins** | [plugins/PLUGINS.md](plugins/PLUGINS.md) | Plugin-Verwaltung |
| **SEO** | [seo-performance/SEO.md](seo-performance/SEO.md) | SEO-Einstellungen |
| **Analytics** | [seo-performance/ANALYTICS.md](seo-performance/ANALYTICS.md) | Besucherstatistiken |
| **Performance** | [system-settings/PERFORMANCE.md](system-settings/PERFORMANCE.md) | Cache & Speed |
| **Abonnements** | [subscription/SUBSCRIPTIONS.md](subscription/SUBSCRIPTIONS.md) | Abo-Pläne |
| **Bestellungen** | [subscription/ORDERS.md](subscription/ORDERS.md) | Bestellverwaltung |
| **Support** | [support/README.md](support/README.md) | Support-Ticket-System |
| **Cookies** | [legal-security/COOKIES.md](legal-security/COOKIES.md) | Cookie-Manager, Consent |
| **DSGVO** | [legal-security/DSGVO.md](legal-security/DSGVO.md) | Art. 15 & 17 DSGVO |
| **Rechtstexte** | [legal-security/LEGAL.md](legal-security/LEGAL.md) | Impressum, AGB, Datenschutz |
| **Firewall** | [legal-security/FIREWALL.md](legal-security/FIREWALL.md) | IP-Sperren, Request-Filter |
| **AntiSpam** | [legal-security/ANTISPAM.md](legal-security/ANTISPAM.md) | Spam-Schutz |
| **Security Audit** | [legal-security/SECURITY-AUDIT.md](legal-security/SECURITY-AUDIT.md) | Score 0–100, Härtung |
| **System** | [system-settings/SYSTEM.md](system-settings/SYSTEM.md) | Server, Diagnose, Wartung |
| **Backup** | [system-settings/BACKUP.md](system-settings/BACKUP.md) | Backup-System |
| **Updates** | [system-settings/UPDATES.md](system-settings/UPDATES.md) | Update-Manager |

---

## Inhaltsverzeichnis

1. [Zugang & Login](#1-zugang--login)
2. [Dashboard](#2-dashboard)
3. [Benutzer & Gruppen](#3-benutzer--gruppen)
4. [Inhalte](#4-inhalte)
5. [Design & Themes](#5-design--themes)
6. [Plugins](#6-plugins)
7. [SEO & Analyse](#7-seo--analyse)
8. [Abos & Bestellungen](#8-abos--bestellungen)
9. [Legal & Sicherheit](#9-legal--sicherheit)
10. [System & Backup](#10-system--backup)
11. [Support](#11-support)
12. [Admin-Seiten Übersicht](#12-admin-seiten-übersicht)

---

## 1. Zugang & Login

**URL:** `/admin` oder `/admin/index.php`

Nur Benutzer mit der Rolle `admin` haben Zugang zum Admin-Panel. Bei unberechtigtem Zugriff erfolgt eine automatische Weiterleitung zur Startseite.

---

## 2. Dashboard

**Datei:** `admin/index.php`  
**URL:** `/admin`

Das Dashboard zeigt auf einen Blick:
- Anzahl Benutzer, Seiten, Beiträge
- Aktive Plugins & Theme
- Aktuelle Anmeldungen
- System-Status (PHP, DB, Cache)
- Top-Seiten (Aufrufe)
- Letzte Aktivitäten

**Widgets können angepasst werden:** Admin → Design → Dashboard-Widgets

---

## 3. Benutzer & Gruppen

### Benutzer verwalten
**Datei:** `admin/users.php`  
**URL:** `/admin/users.php`

**Was ihr hier tun könnt:**
- Neue Benutzer anlegen
- Bestehende Benutzer bearbeiten (Name, E-Mail, Rolle, Status)
- Passwörter zurücksetzen
- Benutzer löschen oder sperren (`status = banned`)
- Benutzer nach Rolle/Status filtern
- Benutzer suchen

**Benutzer-Felder:**
| Feld | Pflicht | Beschreibung |
|------|---------|--------------|
| `username` | ✅ | Eindeutiger Anmeldename |
| `email` | ✅ | E-Mail (eindeutig) |
| `password` | ✅ (neu) | Bcrypt-gehashed |
| `display_name` | ✅ | Anzeigename |
| `role` | ✅ | admin / editor / author / member |
| `status` | ✅ | active / inactive / banned |

### Gruppen verwalten
**Datei:** `admin/groups.php`  
**URL:** `/admin/groups.php`

Gruppen ermöglichen es, Benutzer zusammenzufassen und ihnen gemeinsam Abo-Pläne zuzuweisen.

---

## 4. Inhalte

### Seiten
**Datei:** `admin/pages.php`  
**URL:** `/admin/pages.php`

Statische Seiten des CMS (z.B. Startseite, Über uns, Impressum).

**Seiten-Workflow:**
1. Neue Seite erstellen (Titel + Slug)
2. Inhalt mit Editor (SunEditor) bearbeiten
3. Status: `draft` (Entwurf) oder `published` (Veröffentlicht)
4. Optional: Titel auf Seite ausblenden (`hide_title`)

**SunEditor** ist der integrierte Rich-Text-Editor – ähnlich wie TinyMCE oder CKEditor:
- Textformatierung (Fett, Kursiv, Listen)
- Bilder einbetten
- HTML-Modus für direktes Code-Editing
- Code-Blöcke

### Beiträge (Blog)
**Datei:** `admin/posts.php`  
**URL:** `/admin/posts.php`

Blog-Beiträge mit Kategorien, Tags, Featured Image und SEO-Metadaten.

### Landing Page
**Datei:** `admin/landing-page.php`  
**URL:** `/admin/landing-page.php`

Visueller Builder für die Startseite. Verfügbare Sektions-Typen:
- `hero` – Haupt-Banner mit Titel und Call-to-Action
- `features` – Feature-Kacheln (3 oder 6 Stück)
- `cta` – Call-to-Action-Bereich
- `testimonials` – Kundenstimmen

### Medien
**Datei:** `admin/media.php`  
**URL:** `/admin/media.php`

Zentrale Medienbibliothek für alle hochgeladenen Dateien.
- Bilder: JPEG, PNG, GIF, WebP
- Dokumente: PDF
- Max. Upload-Größe: konfigurierbar (Standard: 10 MB)

### Menüs
**Datei:** `admin/menus.php`  
**URL:** `/admin/menus.php`

Navigation des Frontends konfigurieren.

---

## 5. Design & Themes

### Theme-Auswahl
**Datei:** `admin/themes.php`  
**URL:** `/admin/themes.php`

Alle installierten Themes anzeigen und aktivieren. 8 Themes verfügbar.

### Theme-Customizer
**Datei:** `admin/theme-customizer.php`  
**URL:** `/admin/theme-customizer.php`

Visueller Editor für Theme-Einstellungen ohne Code:
- **Farben**: Primärfarbe, Sekundärfarbe, Text, Hintergrund
- **Typografie**: Überschriften-Font, Fließtext-Font, Schriftgrößen
- **Layout**: Container-Breite, Abstände

### Theme-Editor
**Datei:** `admin/theme-editor.php`  
**URL:** `/admin/theme-editor.php`

Direktes Bearbeiten von Theme-Dateien (CSS, PHP, JavaScript) in der Admin-Oberfläche.

**⚠️ Vorsicht:** Syntax-Fehler im Theme-Editor können die Website unzugänglich machen!

### Theme-Marktplatz
**Datei:** `admin/theme-marketplace.php`  
**URL:** `/admin/theme-marketplace.php`

Neue Themes installieren (aus dem offiziellen 365CMS-Marktplatz).

### Dashboard-Widgets anpassen
**Datei:** `admin/design-dashboard-widgets.php`  
**URL:** `/admin/design-dashboard-widgets.php`

---

## 6. Plugins

### Plugin-Verwaltung
**Datei:** `admin/plugins.php`  
**URL:** `/admin/plugins.php`

Alle installierten Plugins anzeigen, aktivieren und deaktivieren.

**Verfügbare Plugins:**
| Plugin | Slug | Beschreibung |
|--------|------|--------------|
| CMS Companies | `cms-companies` | Firmen-Profile verwalten |
| CMS Events | `cms-events` | Veranstaltungen |
| CMS Experts | `cms-experts` | Experten-Profile |
| CMS Job Ads | `cms-jobads` | Stellenanzeigen |
| CMS Speakers | `cms-speakers` | Speaker-Profile |

### Plugin-Marktplatz
**Datei:** `admin/plugin-marketplace.php`  
**URL:** `/admin/plugin-marketplace.php`

Neue Plugins entdecken und installieren.

---

## 7. SEO & Analyse

### SEO-Einstellungen
**Datei:** `admin/seo.php`  
**URL:** `/admin/seo.php`

- Standard-Meta-Title und -Description
- Open Graph Einstellungen
- Robots.txt konfigurieren
- Sitemap generieren (`/sitemap.xml`)
- Canonical-URLs

### Analytics
**Datei:** `admin/analytics.php`  
**URL:** `/admin/analytics.php`

Besucher-Statistiken ohne externe Tools (datenschutzkonform):
- Tägliche/Monatliche Besucherzahlen
- Top-Seiten
- Einstiegsseiten
- Browser/Gerät-Statistiken

### Performance
**Datei:** `admin/performance.php`  
**URL:** `/admin/performance.php`

- Cache-Status und Cache leeren
- PHP-Speicher-Nutzung
- Datenbank-Query-Statistiken
- Optimierungsempfehlungen

---

## 8. Abos & Bestellungen

### Abo-Pläne
**Datei:** `admin/subscriptions.php`  
**URL:** `/admin/subscriptions.php`

Verfügbare Abo-Pläne verwalten (Free, Starter, Pro, Enterprise).

### Abo-Einstellungen
**Datei:** `admin/subscription-settings.php`  
**URL:** `/admin/subscription-settings.php`

Globale Abo-Konfiguration:
- Zahlungsmethoden
- Test-Modus
- Währung

### Bestellungen
**Datei:** `admin/orders.php`  
**URL:** `/admin/orders.php`

Alle Bestellungen einsehen, Status ändern, Rechnungen herunterladen.

---

## 9. Legal & Sicherheit

Vollständige Dokumentation: [legal-security/README.md](legal-security/README.md)

| Admin-Seite | Datei | Dokumentation |
|-------------|-------|---------------|
| Cookie-Manager | `admin/cookies.php` | [COOKIES.md](legal-security/COOKIES.md) |
| DSGVO Datenzugriff | `admin/data-access.php` | [DSGVO.md](legal-security/DSGVO.md) |
| DSGVO Datenlöschung | `admin/data-deletion.php` | [DSGVO.md](legal-security/DSGVO.md) |
| Rechtstexte | `admin/legal-sites.php` | [LEGAL.md](legal-security/LEGAL.md) |
| Firewall | `admin/firewall.php` | [FIREWALL.md](legal-security/FIREWALL.md) |
| AntiSpam | `admin/antispam.php` | [ANTISPAM.md](legal-security/ANTISPAM.md) |
| Security Audit | `admin/security-audit.php` | [SECURITY-AUDIT.md](legal-security/SECURITY-AUDIT.md) |

---

## 10. System & Backup

### System-Status
**Datei:** `admin/system.php`  
**URL:** `/admin/system.php`

Systemübersicht:
- PHP-Version & Extensions
- Datenbank-Status
- Festplatten-Auslastung
- CMS-Version & Update-Check
- Server-Informationen

### Einstellungen
**Datei:** `admin/settings.php`  
**URL:** `/admin/settings.php`

Allgemeine CMS-Einstellungen:
- Website-Name und -URL
- Admin-E-Mail
- Zeitzone
- Standard-Sprache

### Backup
**Datei:** `admin/backup.php`  
**URL:** `/admin/backup.php`

- Datenbank-Backup erstellen und herunterladen
- Datei-Backup (uploads, themes)
- Automatische tägliche Backups konfigurieren
- Backups wiederherstellen

### Updates
**Datei:** `admin/updates.php`  
**URL:** `/admin/updates.php`

CMS-Updates und Plugin-Updates einspielen.

### Support
**Datei:** `admin/support.php`  
**URL:** `/admin/support.php`

Vollständige Dokumentation: [support/README.md](support/README.md)

Ticket-Verwaltung für Support-Anfragen von Mitgliedern mit Prioritäten und Status-Tracking.

---

## 11. Support

→ Siehe [support/README.md](support/README.md) für vollständige Dokumentation.

---

## 12. Admin-Seiten Übersicht

| Seite | URL | Dokumentation |
|-------|-----|---------------|
| Dashboard | `/admin` | [DASHBOARD.md](dashboard/DASHBOARD.md) |
| Benutzer | `/admin/users.php` | [USERS.md](users-groups/USERS.md) |
| Gruppen | `/admin/groups.php` | [RBAC.md](users-groups/RBAC.md) |
| Mitglieder | `/admin/members.php` | [member/README.md](member/README.md) |
| Seiten | `/admin/pages.php` | [PAGES.md](pages-posts/PAGES.md) |
| Beiträge | `/admin/posts.php` | [POSTS.md](pages-posts/POSTS.md) |
| Medien | `/admin/media.php` | [MEDIA.md](media/MEDIA.md) |
| Menüs | `/admin/menus.php` | [pages-posts/README.md](pages-posts/README.md) |
| Landing Page | `/admin/landing-page.php` | [LANDING-PAGE.md](landing-page/LANDING-PAGE.md) |
| Themes | `/admin/themes.php` | [themes-design/README.md](themes-design/README.md) |
| Customizer | `/admin/theme-customizer.php` | [CUSTOMIZER.md](themes-design/CUSTOMIZER.md) |
| Theme-Editor | `/admin/theme-editor.php` | [themes-design/EDITOR.md](themes-design/EDITOR.md) |
| Theme-Marktplatz | `/admin/theme-marketplace.php` | [MARKETPLACE.md](themes-design/MARKETPLACE.md) |
| Dashboard-Widgets | `/admin/design-dashboard-widgets.php` | [DASHBOARD-WIDGETS.md](themes-design/DASHBOARD-WIDGETS.md) |
| Fonts | `/admin/fonts-local.php` | [FONTS.md](themes-design/FONTS.md) |
| Plugins | `/admin/plugins.php` | [PLUGINS.md](plugins/PLUGINS.md) |
| Plugin-Marktplatz | `/admin/plugin-marketplace.php` | [MARKETPLACE.md](themes-design/MARKETPLACE.md) |
| SEO | `/admin/seo.php` | [SEO.md](seo-performance/SEO.md) |
| Analytics | `/admin/analytics.php` | [ANALYTICS.md](seo-performance/ANALYTICS.md) |
| Performance | `/admin/performance.php` | [PERFORMANCE.md](system-settings/PERFORMANCE.md) |
| Abos | `/admin/subscriptions.php` | [SUBSCRIPTIONS.md](subscription/SUBSCRIPTIONS.md) |
| Abo-Einstellungen | `/admin/subscription-settings.php` | [ORDERS.md](subscription/ORDERS.md) |
| Bestellungen | `/admin/orders.php` | [ORDERS.md](subscription/ORDERS.md) |
| Support | `/admin/support.php` | [support/README.md](support/README.md) |
| Cookie-Manager | `/admin/cookies.php` | [COOKIES.md](legal-security/COOKIES.md) |
| DSGVO Datenzugriff | `/admin/data-access.php` | [DSGVO.md](legal-security/DSGVO.md) |
| DSGVO Datenlöschung | `/admin/data-deletion.php` | [DSGVO.md](legal-security/DSGVO.md) |
| Rechtstexte | `/admin/legal-sites.php` | [LEGAL.md](legal-security/LEGAL.md) |
| Firewall | `/admin/firewall.php` | [FIREWALL.md](legal-security/FIREWALL.md) |
| AntiSpam | `/admin/antispam.php` | [ANTISPAM.md](legal-security/ANTISPAM.md) |
| Security Audit | `/admin/security-audit.php` | [SECURITY-AUDIT.md](legal-security/SECURITY-AUDIT.md) |
| System | `/admin/system.php` | [SYSTEM.md](system-settings/SYSTEM.md) |
| Einstellungen | `/admin/settings.php` | [system-settings/README.md](system-settings/README.md) |
| Backup | `/admin/backup.php` | [BACKUP.md](system-settings/BACKUP.md) |
| Updates | `/admin/updates.php` | [UPDATES.md](system-settings/UPDATES.md) |

