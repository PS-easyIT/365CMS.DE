# Content Management – Übersicht

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026

Verwaltung aller inhaltlichen Elemente der 365CMS Website.

---

## Inhaltsverzeichnis

1. [Module](#1-module)
2. [Inhaltstypen im Überblick](#2-inhaltstypen-im-überblick)
3. [Editor (SunEditor)](#3-editor-suneditor)
4. [Medien in Inhalten](#4-medien-in-inhalten)

---

## 1. Module

| Modul | Datei | Dokumentation |
|---|---|---|
| **Landing Page** | `admin/landing-page.php` | [LANDING-PAGE.md](../landing-page/LANDING-PAGE.md) |
| **Seiten** | `admin/pages.php` | [PAGES.md](PAGES.md) |
| **Beiträge** | `admin/posts.php` | [POSTS.md](POSTS.md) |
| **SEO** | `admin/seo.php` | [SEO.md](../seo-performance/SEO.md) |
| **Medien** | `admin/media.php` | [media/README.md](../media/README.md) |

---

## 2. Inhaltstypen im Überblick

| Typ | Hierarchisch | RSS-Feed | Kategorien | URL-Muster |
|---|---|---|---|---|
| **Page** | ✅ Ja | ❌ Nein | ❌ Nein | `/slug/` |
| **Post** | ❌ Nein | ✅ Ja | ✅ Ja | `/blog/slug/` |
| **Landing Page** | — | ❌ Nein | — | `/` |
| **Plugin-CPT** | Variabel | Variabel | Variabel | konfigurierbar |

**Plugin Custom Post Types (CPT):**  
Plugins können eigene Inhaltstypen registrieren (z.B. `cms-experts` → Experten-Profile, `cms-events` → Veranstaltungen).

---

## 3. Editor (SunEditor)

365CMS verwendet **SunEditor 2.47.x** als WYSIWYG-Editor.

**Verfügbare Toolbar-Elemente:**
```
Überschriften (H1–H6) | Fett | Kursiv | Unterstrichen | Durchgestrichen
Aufzählung | Nummerierung | Zitat | Horizontale Linie | Link | Bild | Video
Tabelle | Code-Block | HTML-Quellcode | Vollbild
```

**Bilder einfügen:**
- Aus der Medienbibliothek wählen (Pop-up)
- URL direkt eingeben
- Upload per Drag & Drop direkt in den Editor

**HTML-Modus aktivieren:**
- Klick auf `</>` in der Toolbar
- Roher HTML-Code bearbeitbar
- SunEditor filtert automatisch unsicheres HTML

---

## 4. Medien in Inhalten

### Beitragsbild (Featured Image)
- Wird in Listen-Ansichten, Social-Sharing und RSS-Feed verwendet
- Empfohlene Größe: 1200×630 px (16:10 Verhältnis)
- Wird automatisch auf verschiedene Größen skaliert

### Medien-Shortcodes
```php
// Bild einbetten
[cms_image id="42" size="medium" align="center"]

// Galerie
[cms_gallery ids="42,43,44" columns="3"]

// Dokument-Download
[cms_download id="55" label="Whitepaper herunterladen"]
```

### Externe Medien
Externe YouTube/Vimeo-URLs werden automatisch als embed erkannt und in Responsive-Player umgewandelt.

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
