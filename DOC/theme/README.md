# 365CMS – Theme-System
> **Stand:** 2026-03-28 | **Version:** 2.8.0 RC | **Status:** Aktuell

## Inhaltsverzeichnis
- [Überblick](#überblick)
- [Typische Theme-Struktur](#typische-theme-struktur)
- [Theme-Metadaten](#theme-metadaten)
- [Verfügbare Themes im Workspace](#verfügbare-themes-im-workspace)
- [Theme-Verwaltung im Admin](#theme-verwaltung-im-admin)
- [Theme anpassen](#theme-anpassen)
- [Einstieg in die Entwicklung](#einstieg-in-die-entwicklung)

---
<!-- UPDATED: 2026-03-28 -->

## Überblick

Themes steuern das visuelle Rendering des Frontends. Der aktuelle Stand basiert auf:

- Theme-Verzeichnissen unter `CMS/themes/<slug>/`
- Metadaten in `theme.json`
- Theme-Rendering über `CMS\ThemeManager`
- optionaler Theme-spezifischer Admin-Oberfläche über `admin/customizer.php`

---

## Typische Theme-Struktur

```text
CMS/themes/mein-theme/
├── theme.json
├── update.json
├── style.css
├── functions.php
├── header.php
├── footer.php
├── home.php
├── index.php
├── page.php
├── 404.php
├── admin/
│   └── customizer.php
├── js/
│   ├── navigation.js
│   └── theme.js
└── screenshot.png
```

Die Dateien `style.css`, `functions.php`, `header.php`, `footer.php`, `home.php` und `index.php` bilden im aktuellen Projekt die übliche Mindestbasis.

---

## Theme-Metadaten

`theme.json` enthält mindestens:

```json
{
  "name": "Mein Theme",
  "slug": "mein-theme",
  "version": "1.0.0",
  "author": "Euer Name",
  "description": "Kurzbeschreibung",
  "templates": {
    "home": "home.php",
    "page": "page.php",
    "404": "404.php"
  },
  "partials": {
    "header": "header.php",
    "footer": "footer.php"
  }
}
```

---

## Verfügbare Themes im Workspace

| Theme | Slug |
|---|---|
| 365Network | `365Network` |
| academy365 | `academy365` |
| buildbase | `buildbase` |
| business | `business` |
| cms-default | `cms-default` |
| cms-newspaper | `cms-newspaper` |
| cms-phinit | `cms-phinit` |
| logilink | `logilink` |
| medcarepro | `medcarepro` |
| personalflow | `personalflow` |
| technexus | `technexus` |

---

## Theme-Verwaltung im Admin

Wichtige aktuelle Routen:

| Route | Zweck |
|---|---|
| `/admin/themes` | installierte Themes anzeigen und aktivieren |
| `/admin/theme-editor` | Theme-spezifischen Customizer laden |
| `/admin/theme-explorer` | Theme-Dateien und Struktur prüfen |
| `/admin/menu-editor` | Menüs bearbeiten |
| `/admin/font-manager` | lokale Schriften verwalten |

Wichtig: Der frühere Begriff **Theme-Customizer** ist im aktuellen Core kein eigener separater Standard-Einstieg mehr. Die führende Oberfläche ist `/admin/theme-editor`.

---

## Theme anpassen

### Ohne Code

Wenn das aktive Theme eine Datei `admin/customizer.php` bereitstellt, lädt `/admin/theme-editor` genau diese Oberfläche.

### Mit Theme-Dateien

Theme-spezifisches CSS und JavaScript liegen üblicherweise in:

- `style.css`
- `js/navigation.js`
- `js/theme.js`

### Theme-Customization-Daten

Gespeicherte Anpassungen landen im Regelfall in `theme_customizations` und werden durch Theme- oder Core-Logik als CSS- oder Template-Werte ausgegeben.

---

## Einstieg in die Entwicklung

Für neue Themes gibt es zwei vertiefende Dokumente:

- [THEME-DEVELOPMENT.md](THEME-DEVELOPMENT.md) – Schritt-für-Schritt-Einstieg
- [DEVELOPMENT.md](DEVELOPMENT.md) – ausführlichere Entwicklerreferenz

Weitere themennahe Referenzen:

- [COMPONENTS.md](COMPONENTS.md)
- [DESIGN-SYSTEM.md](DESIGN-SYSTEM.md)
- [JAVASCRIPT.md](JAVASCRIPT.md)

