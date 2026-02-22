# Theme-Customizer

**Datei:** `admin/theme-customizer.php`

---

## Übersicht

Der Theme-Customizer ermöglicht visuelle Anpassung des aktiven Themes mit Live-Vorschau. Über 50 Einstellungen in 8 Kategorien steuern das gesamte Design ohne CSS-Kenntnisse.

---

## Kategorien

### 1. 🎨 Farben (13 Optionen)

| Einstellung | CSS-Variable | Beschreibung |
|-------------|-------------|--------------|
| Primärfarbe | `--color-primary` | Hauptfarbe (Buttons, Links, Akzente) |
| Sekundärfarbe | `--color-secondary` | Ergänzungsfarbe |
| Erfolg | `--color-success` | Grün-Töne für Erfolgs-Meldungen |
| Warnung | `--color-warning` | Orange für Warnungen |
| Fehler | `--color-danger` | Rot für Fehlermeldungen |
| Hintergrund-Hell | `--bg-light` | Seiten-Hintergrundfarbe |
| Hintergrund-Mittel | `--bg-medium` | Card-Hintergrundfarbe |
| Hintergrund-Dunkel | `--bg-dark` | Dunkelbereich-Farbe |
| Text-Primär | `--text-primary` | Haupt-Textfarbe |
| Text-Sekundär | `--text-secondary` | Subtext-Farbe |
| Text-Hell | `--text-light` | Heller Text (auf dunklem Hintergrund) |
| Rahmen | `--border-color` | Standard-Border-Farbe |
| Link | `--link-color` | Link-Farbe |

### 2. ✍️ Typografie (5 Optionen)

| Einstellung | Optionen |
|-------------|---------|
| Basis-Schriftart | System-Font, Roboto, Open Sans, Lato, Montserrat, Poppins, Playfair Display, Inter, Nunito |
| Überschriften-Schriftart | Wie Basis-Schriftart |
| Schriftgröße | 12–20px (Slider) |
| Zeilenhöhe | 1.2–2.0 (Slider) |
| Überschriften-Gewicht | 400, 500, 600, 700, 800, 900 |

### 3. 📐 Layout (6 Optionen)

| Einstellung | Standard | Bereich |
|-------------|---------|---------|
| Container-Breite | 1200px | 960–1600px |
| Inhalts-Padding | 2rem | 0.5–4rem |
| Border-Radius | 8px | 0–20px |
| Sektions-Abstände | 4rem | 1–8rem |
| Sticky Header | Ein | Ein/Aus |
| Back-to-Top | Ein | Ein/Aus |

### 4. 🗺️ Header (5 Optionen)

| Einstellung | Beschreibung |
|-------------|--------------|
| Hintergrundfarbe | Header-Hintergrund |
| Textfarbe | Menü-Text und Logo-Farbe |
| Höhe | 60–120px |
| Logo Max-Höhe | 30–80px |
| Schatten | Box-Shadow Ein/Aus |

### 5. 🏠 Footer (5 Optionen)

| Einstellung | Beschreibung |
|-------------|--------------|
| Hintergrundfarbe | Footer-Hintergrund |
| Textfarbe | Footer-Text |
| Link-Farbe | Links im Footer |
| Footer-Widgets | Mehrspaltiger Widget-Bereich |
| Spaltenanzahl | 1–4 Spalten |

### 6. 🔘 Buttons (5 Optionen)

| Einstellung | Beschreibung |
|-------------|--------------|
| Border-Radius | Ecken-Rundung |
| Padding X | Horizontaler Innenabstand |
| Padding Y | Vertikaler Innenabstand |
| Schriftstärke | 400–700 |
| Text-Transformation | Normal, Uppercase, Capitalize |

### 7. ⚡ Performance (3 Optionen)

| Einstellung | Standard | Beschreibung |
|-------------|---------|--------------|
| Lazy Loading | Ein | Bilder verzögert laden |
| CSS-Minifikation | Aus | Generierten CSS minifizieren |
| Font-Preloading | Ein | Schriften vorladen |

### 8. 🔧 Erweitert

| Einstellung | Beschreibung |
|-------------|--------------|
| Custom CSS | Direkter CSS-Editor für eigene Stile |
| Custom JavaScript | Eigener JS-Code (head/footer) |
| Debug-Modus | Erweiterte Debug-Ausgaben im Frontend |

---

## Workflow

1. **Kategorie wählen** – Tab in der linken Sidebar
2. **Einstellung anpassen** – Color Picker, Slider, Dropdown, Toggle
3. **Live-Vorschau** – Rechts automatisch aktualisiert
4. **Speichern** – "Änderungen speichern" Button oben
5. **CSS generieren** – Optional für Performance
6. **Exportieren** – Als JSON-Backup

---

## CSS-Generierung

Beim Speichern wird automatisch `customizations.css` im Theme-Verzeichnis generiert:

```css
:root {
    --color-primary: #3b82f6;
    --color-secondary: #64748b;
    --font-base: 'Roboto', sans-serif;
    /* ... alle Customizer-Werte */
}
```

---

## Import / Export

### Export
- Alle Customizer-Einstellungen als JSON-Datei
- Verwendbar für Theme-Backup oder Übertragung

### Import
- JSON-Datei hochladen
- Einstellungen werden sofort angewendet
- Vorherige Einstellungen werden überschrieben (Warnung!)

---

## Datenbank

Einstellungen in `cms_theme_customizations`:

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | INT | Primärschlüssel |
| `theme_name` | VARCHAR | Theme-Name |
| `category` | VARCHAR | Einstellungs-Kategorie |
| `setting_key` | VARCHAR | Einstellungs-Schlüssel |
| `setting_value` | TEXT | Wert |
| `updated_at` | TIMESTAMP | Letzte Änderung |

---

## Verwandte Seiten

- [Theme-Verwaltung](README.md)
- [Theme-Editor (Code)](EDITOR.md)
- [Lokale Fonts](FONTS.md)
- [Theme-Marketplace](MARKETPLACE.md)
