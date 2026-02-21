# 365CMS – Admin-Panel Dokumentation

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026

Das Admin-Panel ist das Herz des 365CMS. Hier verwaltet ihr alles: Benutzer, Inhalte, Themes, Plugins und Systemeinstellungen.

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
9. [System & Backup](#9-system--backup)
10. [Admin-Seiten Übersicht](#10-admin-seiten-übersicht)

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

## 9. System & Backup

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

Direkter Zugang zur Online-Dokumentation (diese Docs!) – verlinkt zum GitHub-Repository.

---

## 10. Admin-Seiten Übersicht

| Seite | URL | Beschreibung |
|-------|-----|--------------|
| Dashboard | `/admin` | Übersicht & Widgets |
| Benutzer | `/admin/users.php` | User-Verwaltung |
| Gruppen | `/admin/groups.php` | Gruppen verwalten |
| Seiten | `/admin/pages.php` | Statische Seiten |
| Beiträge | `/admin/posts.php` | Blog |
| Medien | `/admin/media.php` | Dateien & Bilder |
| Menüs | `/admin/menus.php` | Navigation |
| Landing Page | `/admin/landing-page.php` | Startseiten-Builder |
| Themes | `/admin/themes.php` | Theme-Auswahl |
| Customizer | `/admin/theme-customizer.php` | Theme-Design |
| Theme-Editor | `/admin/theme-editor.php` | Code-Editor |
| Theme-Marktplatz | `/admin/theme-marketplace.php` | Themes installieren |
| Dashboard-Widgets | `/admin/design-dashboard-widgets.php` | Widgets konfigurieren |
| Fonts | `/admin/fonts-local.php` | Lokale Fonts |
| Plugins | `/admin/plugins.php` | Plugins verwalten |
| Plugin-Marktplatz | `/admin/plugin-marketplace.php` | Plugins installieren |
| SEO | `/admin/seo.php` | SEO-Einstellungen |
| Analytics | `/admin/analytics.php` | Besucherstatistiken |
| Performance | `/admin/performance.php` | Cache & Speed |
| Abos | `/admin/subscriptions.php` | Abo-Pläne |
| Abo-Einstellungen | `/admin/subscription-settings.php` | Abo-Config |
| Bestellungen | `/admin/orders.php` | Käufe & Rechnungen |
| System | `/admin/system.php` | Server-Status |
| Einstellungen | `/admin/settings.php` | CMS-Einstellungen |
| Backup | `/admin/backup.php` | Sicherungen |
| Updates | `/admin/updates.php` | System-Updates |
| Support | `/admin/support.php` | Dokumentation |

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
