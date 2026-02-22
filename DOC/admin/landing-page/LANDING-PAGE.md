# Landing Page Editor


Der Landing Page Editor ermöglicht die visuelle Bearbeitung der Startseite ohne Programmierkenntnisse. Er bietet einen Sektions-basierten Aufbau mit vorkonfigurierten Blöcken.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Sektionen verwalten](#2-sektionen-verwalten)
3. [Hero Section](#3-hero-section)
4. [Features-Sektion](#4-features-sektion)
5. [Testimonials](#5-testimonials)
6. [CTA-Sektion](#6-cta-sektion)
7. [Footer-Einstellungen](#7-footer-einstellungen)
8. [Technische Details](#8-technische-details)

---

## 1. Überblick

URL: `/admin/landing-page.php`

Die Landing Page ist die **öffentliche Startseite** der Website (Wurzel-URL `/`). Der Editor speichert alle Inhalte in der `cms_settings`-Tabelle als JSON-Objekt unter dem Key `landing_page_config`.

**Voraussetzung:** In `admin/settings.php` → „Startseite" muss „Landing Page" ausgewählt sein (nicht „Letzter Blog-Beitrag" oder eine statische Seite).

---

## 2. Sektionen verwalten

Die Landing Page besteht aus konfigurierbaren Sektionen:

| Sektion | Aktiv by default | Deaktivierbar |
|---|---|---|
| Hero | ✅ | ❌ Nein |
| Features | ✅ | ✅ Ja |
| Testimonials | ❌ | ✅ Ja |
| CTA-Banner | ✅ | ✅ Ja |
| Statistiken | ❌ | ✅ Ja |
| Footer | ✅ | ❌ Nein |

**Reihenfolge:** Sektionen können per Drag & Drop umsortiert werden.

---

## 3. Hero Section

Die Hero-Sektion ist die große Willkommensansicht oben auf der Startseite.

**Einstellbare Felder:**

| Feld | Beschreibung | Max. Länge |
|---|---|---|
| `hero_title` | Hauptüberschrift (H1) | 80 Zeichen |
| `hero_subtitle` | Untertitel/Beschreibungstext | 200 Zeichen |
| `hero_cta_primary_text` | Text des primären Buttons | 30 Zeichen |
| `hero_cta_primary_url` | Link des primären Buttons | — |
| `hero_cta_secondary_text` | Text des sekundären Buttons | 30 Zeichen |
| `hero_cta_secondary_url` | Link des sekundären Buttons | — |
| `hero_background_type` | `color` oder `image` | — |
| `hero_background_value` | Hex-Farbe oder Bild-URL | — |
| `hero_text_color` | Textfarbe (`light` oder `dark`) | — |

---

## 4. Features-Sektion

Stellt bis zu **6 Feature-Karten** in einem Grid dar (3 oder 4 Spalten).

**Pro Karte:**
- **Icon:** FontAwesome 6 Klassen-Name (z.B. `fa-solid fa-rocket`)
- **Titel:** Kurze Bezeichnung (max. 40 Zeichen)
- **Text:** Beschreibung (max. 150 Zeichen)
- **Link:** Optionaler Link für „Mehr erfahren"

**Layout-Optionen:**
- Hintergrundfarbe der Sektion
- Karten-Stil: `card`, `icon-only`, `list`

---

## 5. Testimonials

Kundenmeinungen/Referenzen anzeigen:

**Pro Testimonial:**
- **Avatar:** Bild-Upload oder Gravatar via E-Mail
- **Name:** Kundenname
- **Position:** Berufsbezeichnung und Firma
- **Zitat:** Kundenmeinung (max. 280 Zeichen)
- **Bewertung:** 1–5 Sterne (optional)

**Darstellung:** Karussell (mit Auto-Play, konfigurierbar) oder statisches Grid

---

## 6. CTA-Sektion

Call-to-Action Banner zwischen Sektionen:

- **Überschrift:** max. 60 Zeichen
- **Text:** max. 120 Zeichen
- **Button:** Text + URL
- **Hintergrund:** Vollfarbe oder Gradient (Start- und Endfarbe)

---

## 7. Footer-Einstellungen

Der Footer wird ebenfalls hier konfiguriert:

**Spalten (1–4 Spalten):**
- Pro Spalte: Titel, Inhalt (WYSIWYG, Links möglich)
- Typische Struktur: Logo + Beschreibung | Links | Kontakt | Social

**Social-Media-Links:**
| Plattform | Key |
|---|---|
| Twitter/X | `social_twitter` |
| LinkedIn | `social_linkedin` |
| GitHub | `social_github` |
| Instagram | `social_instagram` |
| YouTube | `social_youtube` |

**Copyright-Zeile:**
- Frei konfigurierbarer Text
- Automatisches Jahr-Platzhalter: `{year}`

---

## 8. Technische Details

**Speicherung:** Alle Werte als JSON in `cms_settings`:
```php
// Wert speichern
$db->update('cms_settings',
    ['option_value' => json_encode($landingConfig)],
    ['option_name'  => 'landing_page_config']
);

// Wert laden
$config = json_decode(
    $db->get_var("SELECT option_value FROM cms_settings WHERE option_name = 'landing_page_config'"),
    true
);
```

**Template:** `themes/{active_theme}/templates/landing-page.php`

**Hook:**
```php
do_action('cms_landing_page_saved', $newConfig, $oldConfig);
add_filter('cms_landing_page_sections', 'my_plugin_add_section');
```

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
