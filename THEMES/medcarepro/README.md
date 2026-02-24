# MedCare Pro – Theme für 365 CMS

**Version:** 1.0.1  
**Autor:** PHINIT.DE / Andreas Hepp  
**Lizenz:** Proprietär – nur für 365CMS.DE  
**Zielgruppe:** Arztpraxen, Kliniken, Therapeuten, Pflegedienste, Gesundheitsportale

---

## Übersicht

MedCare Pro ist ein spezialisiertes CMS-Theme für medizinische Websites. Es folgt dem Grundsatz:
**Vertrauen durch Klarheit** – helles Blau für Reinheit, viel Weißraum, DSGVO-konforme Defaults und integrierte Barrierefreiheits-Werkzeuge.

### Farbpalette

| Token | Wert | Verwendung |
|---|---|---|
| `--primary-color` | `#0ea5e9` | Hauptfarbe, Buttons, Links |
| `--primary-dark` | `#0284c7` | Hover-Zustand |
| `--secondary-color` | `#0c4a6e` | Überschriften, Footer-Hintergrund |
| `--accent-color` | `#10b981` | Grün für Gesundheit & Bestätigungen |
| `--warning-color` | `#ef4444` | Notfall- und Fehlermeldungen |

---

## Dateistruktur

```
medcarepro/
├── functions.php          # Theme-Klasse, Hook-Registrierungen, Helper-Funktionen
├── header.php             # DOCTYPE, <head>, sticky Header mit Suche + Barrierefreiheits-Toggle
├── footer.php             # Footer (4 Spalten), Disclaimer, Legal-Nav, Copyright
├── home.php               # Landing Page: Hero + Suche, Fachgebiete, Ärzte, Booking-CTA, Trust
├── index.php              # Blog-Listing (Fallback)
├── page.php               # Statische Seiten (Prosa-Inhalt)
├── 404.php                # 404-Fehlerseite
├── error.php              # Generische Fehlerseite (50x)
├── blog.php               # Blog-/Ratgeber-Übersicht mit Paginierung
├── blog-single.php        # Einzelner Blog-/Gesundheitsartikel mit medizinischem Hinweis
├── search.php             # Suchergebnis-Seite (Ärzte + Artikel)
├── login.php              # Anmeldeformular mit CSRF
├── register.php           # Registrierung (Patient / Arzt) mit CSRF + Honeypot
├── style.css              # Alle Styles inkl. responsiver Breakpoints
├── theme.json             # Theme-Manifest, Customizer-Schema, Templates-Registry
├── update.json            # Update-Feed
├── js/
│   └── navigation.js      # Mobile-Menü, Suche-Panel, Font-Size- & Kontrast-Toggle, Scroll
└── partials/
    └── doctor-card.php    # Wiederverwendbare Arzt-Karte (Avatar, Fachgebiet, Termin-Button)
```

---

## Template-Referenz

| Template | Aufruf durch CMS | Erwartet |
|---|---|---|
| `home.php` | Route `/` | – (liest aus Customizer) |
| `page.php` | Jede statische CMS-Seite | `$page` (object) |
| `blog.php` | Route `/blog`, `/ratgeber` | `$posts`, `$total`, `$currentPage`, `$totalPages` |
| `blog-single.php` | Einzelner Blog-Beitrag | `$post` (object) |
| `search.php` | Route `/search?q=…` | GET-Parameter `q`, optional `$results` |
| `login.php` | Route `/login` | – |
| `register.php` | Route `/register?type=patient\|doctor` | – |
| `404.php` | Seite nicht gefunden | – |
| `error.php` | Serverfehler | `$errorCode`, `$errorMessage` |

---

## Helper-Funktionen (`functions.php`)

| Funktion | Rückgabe | Beschreibung |
|---|---|---|
| `theme_is_logged_in()` | `bool` | Prüft ob der Nutzer eingeloggt ist |
| `theme_nav_menu(string $location)` | `void` | Rendert ein `<ul>`-Navigationsmenü |
| `get_header()` | `void` | Bindet `header.php` ein |
| `get_footer()` | `void` | Bindet `footer.php` ein |
| `mc_get_setting(string $section, string $key, mixed $default)` | `mixed` | Liest einen Customizer-Wert mit Fallback |
| `mc_get_flash()` | `array\|null` | Liest und löscht eine Session-Flash-Nachricht |
| `mc_set_flash(string $type, string $message)` | `void` | Speichert eine Flash-Nachricht in der Session |

---

## Customizer-Sektionen (`theme.json`)

| Sektion | Beschreibung |
|---|---|
| `colors` | Primär-, Sekundär-, Akzent-, Spezialfarben, Fachgebiets-Farben |
| `typography` | Schriftfamilien, Schriftgrößen, Zeilenhöhe |
| `layout` | Container-Breite, Padding, Eckenradius, Sticky Header |
| `header` | Logo, Notfall-Banner, Telefonnummer |
| `footer` | Footer-Text, Disclaimer, Copyright |
| `buttons` | Radius, Padding, Schriftgewicht |
| `medical_hero` | Hero-Texte, CTA-Labels & URLs, Statistik-Leiste |
| `medical_content` | Arzt-Profilsektion, Buchungssektion, Versicherungs-Labels, Notfall-Info |
| `dsgvo_medical` | DSGVO-Modus, Cookie-Banner, Datenschutz-Formulartexte |
| `accessibility` | Schriftgrößen-Toggle, Hochkontrast-Toggle |
| `advanced` | Custom CSS |

---

## Responsive Breakpoints

| Breakpoint | Verhalten |
|---|---|
| `> 960px` | Vollansicht: Buchungs-CTA zweispaltig, 4-spaltige Trust-Grid |
| `≤ 960px` (Tablet landscape) | Buchungs-CTA einspaltig, Aktionen in Zeile |
| `≤ 768px` (Tablet portrait) | Header-Buttons ausgeblendet (nur Menü-Toggle), Grids zweispaltig |
| `≤ 480px` (Mobile) | Hero-Suche vertikal, alles einspaltig, Trust-Grid einspaltig |

---

## Barrierefreiheit (WCAG 2.2 AA)

- Skip-Link (`Zum Inhalt springen`) im Header
- `role="banner"`, `role="navigation"`, `role="main"`, `role="contentinfo"` auf allen Landmarks
- Alle `<img>`-Elemente mit `alt`-Attribut
- Hochkontrast-Toggle und Schriftgrößen-Toggle (persistiert via `localStorage`)
- `aria-label` und `aria-pressed` auf allen interaktiven Elementen
- `aria-live="polite"` auf dem Notfall-Info-Banner
- `prefers-reduced-motion` deaktiviert alle Animationen

---

## DSGVO-Notizen

- **Notfall-Banner** (Header): Wird nur angezeigt wenn `header.show_emergency_banner = true`
- **Footer-Disclaimer**: Pflichttext für medizinische Websites; konfigurierbar in `footer.footer_disclaimer`
- **Cookie-Banner**: Ausgabe via `before_footer`-Hook; Text in `dsgvo_medical.cookie_banner_text`
- **Formulare**: Alle Formulare (Login, Register) haben CSRF-Token und Honeypot-Felder
- **Social-Sharing**: Standardmäßig deaktiviert (`dsgvo_medical.disable_social_sharing = true`)
- **Arztprofil**: Impressum-Pflichtangabe in `dsgvo_medical.imprint_doctor_title`

---

## Entwicklung

### Neue Admin-Seite erstellen

```php
<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/CMS/config.php';
require_once CORE_PATH . 'autoload.php';
use CMS\Auth;
use CMS\Security;
if (!defined('ABSPATH')) exit;
if (!Auth::instance()->isAdmin()) { header('Location: ' . SITE_URL); exit; }
```

### Customizer-Wert lesen

```php
// Empfohlen (mit Fehlerbehandlung):
$value = mc_get_setting('colors', 'primary_color', '#0ea5e9');

// Direkt (in templates mit bereits initialisiertem $c):
$value = $c?->get('colors', 'primary_color', '#0ea5e9') ?? '#0ea5e9';
```

### Arzt-Karte einbinden

```php
<?php
$doctor = [
    'id'           => 1,
    'name'         => 'Mustermann',
    'title'        => 'Dr. med.',
    'specialty'    => 'Allgemeinmedizin',
    'specialty_slug' => 'allgemein',
    'gkv'          => true,
    'pkv'          => true,
    'rating'       => 4.7,
    'review_count' => 23,
    'location'     => 'München',
    'verified'     => true,
];
include THEME_PATH . 'medcarepro/partials/doctor-card.php';
?>
```

---

## Changelog

### 1.0.1 (2026-02-23)
- **Fix:** `theme_is_logged_in()`, `theme_nav_menu()`, `get_header()`, `get_footer()` implementiert (vorher Fatal Error)
- **Fix:** Falscher Customizer-Key `show_font_size_toggle` → `enable_font_size_toggle`
- **Fix:** Falscher Customizer-Key `show_contrast_toggle` → `enable_high_contrast_toggle`
- **Fix:** Key-Mismatch `emergency_info` → `emergency_info_text` in `home.php`
- **Fix:** Key-Mismatch `doctor_profiles_title` → `doctor_section_title`
- **Fix:** Key-Mismatch heroStat-Felder in `medical_hero` zu `theme.json` ergänzt
- **Neu:** Optionaler Notfall-Banner im Header mit dynamischer Header-Höhe via JS
- **Neu:** Footer erhält 4. Spalte "Rechtliches" + Disclaimer-Anzeige + Legal-NavBar
- **Neu:** `home.php` komplett überarbeitet: Hero-Suchform, Fachgebiete-Grid, Versicherungsfilter, Termin-CTA, Trust-Grid, Registrierungs-CTA
- **Neu:** `style.css` – neue Komponenten (Hero-Suche, Specialties-Grid, Insurance-Filter, Booking-CTA, Trust-Grid, Prose, Form-Styles, Search-Results) + vollständige Responsive-Breakpoints (960 / 768 / 480 px)
- **Neu:** `blog.php` – Blog-Übersicht mit Paginierung
- **Neu:** `blog-single.php` – Einzelartikel mit Breadcrumb, medizinischem Hinweis
- **Neu:** `search.php` – Suchergebnisse (Ärzte + Artikel)
- **Neu:** `login.php` – CSRF-geschütztes Anmeldeformular
- **Neu:** `register.php` – Registrierung (Patient / Arzt) mit CSRF + Honeypot
- **Neu:** `partials/doctor-card.php` – Wiederverwendbare Arzt-Karte
- **Neu:** `js/navigation.js` – dynamisches Content-Padding für Emergency-Banner
- **Neu:** `theme.json` – Templates-Registry, fehlende `medical_hero`-Keys ergänzt

### 1.0.0
- Initiales Release
