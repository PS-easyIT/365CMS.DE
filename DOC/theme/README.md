# 365CMS – Theme-System

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026

Themes steuern das visuelle Erscheinungsbild des 365CMS. Eigene Themes können ohne Core-Anpassungen erstellt werden.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Theme-Struktur](#2-theme-struktur)
3. [Verfügbare Themes](#3-verfügbare-themes)
4. [Theme wechseln](#4-theme-wechseln)
5. [Theme anpassen](#5-theme-anpassen)
6. [Eigenes Theme erstellen](#6-eigenes-theme-erstellen)

---

## 1. Überblick

Das Theme-System des 365CMS:
- **Themes** liegen in `themes/{theme-slug}/`
- Der `ThemeManager` lädt das aktive Theme
- Themes können CSS-Variablen via `theme.json` definieren
- Der Admin-Customizer überschreibt Theme-Defaults via `cms_theme_customizations`
- Themes haben Zugriff auf alle Core-Klassen über die Theme-Funktionen

---

## 2. Theme-Struktur

```
themes/mein-theme/
├── theme.json          ← Pflicht: Theme-Metadaten & Design-Tokens
├── index.php           ← Frontend-Template (Startseite)
├── header.php          ← HTML-Head + Navigation
├── footer.php          ← Footer + Scripts
├── page.php            ← Einzel-Seite Template
├── post.php            ← Blog-Post Template
├── archive.php         ← Blog-Archiv Template
├── 404.php             ← Fehlerseite
├── login.php           ← Login-Seite
├── register.php        ← Registrierungs-Seite
├── assets/
│   ├── css/
│   │   └── style.css   ← Haupt-CSS
│   └── js/
│       └── main.js     ← Haupt-JavaScript
└── screenshot.png      ← Vorschaubild (400×300 px)
```

### theme.json (Pflicht)

```json
{
  "name": "Mein Theme",
  "slug": "mein-theme",
  "version": "1.0.0",
  "author": "Euer Name",
  "description": "Mein erstes 365CMS Theme",
  "screenshot": "screenshot.png",
  "settings": {
    "colors": {
      "primary":    { "label": "Primärfarbe",   "default": "#007bff", "type": "color" },
      "secondary":  { "label": "Sekundärfarbe", "default": "#6c757d", "type": "color" },
      "background": { "label": "Hintergrund",   "default": "#ffffff", "type": "color" },
      "text":       { "label": "Textfarbe",     "default": "#212529", "type": "color" }
    },
    "typography": {
      "font_heading": { "label": "Überschriften-Font", "default": "Georgia, serif",        "type": "font" },
      "font_body":    { "label": "Fließtext-Font",     "default": "Arial, sans-serif",     "type": "font" },
      "font_size":    { "label": "Basis-Schriftgröße", "default": "16px",                  "type": "text" }
    },
    "layout": {
      "container_width": { "label": "Container-Breite", "default": "1200px", "type": "text" }
    }
  }
}
```

---

## 3. Verfügbare Themes

| Theme | Slug | Charakteristik |
|-------|------|----------------|
| 365 Network | `365Network` | Networking-Platform Stil |
| Academy 365 | `academy365` | Lernplattform / Kurswebseite |
| Build Base | `buildbase` | Minimalist, Builder-freundlich |
| Business | `business` | Professionell für Firmen |
| LogiLink | `logilink` | IT & Logistik |
| MedCarePro | `medcarepro` | Medizin & Gesundheit |
| PersonalFlow | `personalflow` | Persönliches Portfolio |
| TechNexus | `technexus` | Technologie & Startup |
| **CMS Default** | `cms-default` | **Aktiv** – neutrales Basis-Theme |

---

## 4. Theme wechseln

**Via Admin-Panel:** Admin → Themes → Theme anklicken → "Aktivieren"

**Via Code (für Entwickler):**
```php
$db = CMS\Database::instance();
$db->query(
    "INSERT INTO cms_settings (option_name, option_value) VALUES ('active_theme', ?)
     ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)",
    ['technexus']
);
```

---

## 5. Theme anpassen

### Ohne Code: Theme-Customizer

Admin → Theme-Customizer → Farben, Schriften, Layout anpassen → Speichern

Änderungen werden in `cms_theme_customizations` gespeichert und beim Laden des Themes als CSS-Variablen ausgegeben.

### Mit CSS: Theme-Editor

Admin → Theme-Editor → `style.css` auswählen → Anpassen

### Eigene CSS-Variablen nutzen

```css
/* In eurem Theme-CSS */
:root {
  --color-primary:   var(--cms-primary,    #007bff);
  --color-secondary: var(--cms-secondary,  #6c757d);
  --font-heading:    var(--cms-font-heading, 'Georgia, serif');
  --container-width: var(--cms-container-width, 1200px);
}

.button { background-color: var(--color-primary); }
.hero h1 { font-family: var(--font-heading); }
```

Das CMS injiziert die Customizer-Werte als `--cms-*` Variablen, euer Theme nutzt sie via `var()`.

---

## 6. Eigenes Theme erstellen

→ Vollständige Anleitung: [THEME-DEVELOPMENT.md](THEME-DEVELOPMENT.md)

Kurzversion:
1. Ordner `themes/mein-theme/` erstellen
2. `theme.json` mit Metadaten anlegen
3. `index.php`, `header.php`, `footer.php` kopieren vom cms-default
4. CSS in `assets/css/style.css` anpassen
5. `screenshot.png` erstellen (400×300 px)
6. Im Admin aktivieren

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
