# Changelog – CMS Default Theme

Alle wichtigen Änderungen werden in dieser Datei dokumentiert.  
Format folgt [Keep a Changelog](https://keepachangelog.com/de/1.0.0/).

---

## [2.0.0] – 2026-02-19

### Komplett-Redesign nach WordPress-365Theme-v2-Vorbild

#### Hinzugefügt
- **Sticky Header** – `position: sticky`, kompaktiert beim Scrollen via `.scrolled`-Klasse (height 72→60px, Hintergrund dunkler)
- **Burger-Menü** – Hamburger-Icon (3 Linien) → X-Animation `.is-active`, vollständige ARIA-Accessibility
- **Mobile Menü Overlay** – Slide-in von rechts, Gradient-Hintergrund (Navy → Dunkel), Link-Click schließt Menü
- **Dark Mode Toggle** – Button im Header, System-Präferenz erkannt, `localStorage` Persistenz
- **Scroll-Animationen** – IntersectionObserver für `[data-anim]` Elemente, `.is-visible` CSS-Klasse
- **`navigation.js`** – Neue JavaScript-Datei (`js/navigation.js`) mit allen Interaktionen
  - `initStickyHeader()` – Scroll-Listener (passive)
  - `initBurgerMenu()` – Toggle, Outside-Click, Escape-Key
  - `initDarkMode()` – System-Präferenz + manueller Toggle
  - `initBackToTop()` – CSS-Klasse basiert (`.visible`)
  - `initScrollAnimations()` – IntersectionObserver
  - `initActiveNav()` – Automatisches Active-Marking
  - `initFlashMessages()` – Auto-dismiss mit `data-auto-dismiss`
- **Zweibandiger Footer** – `footer-top` (Inhalte/Sektionen) + `footer-bottom` (Copyright, Badge)
- **Footer Badge** – `⚡ CMSv2` Gold-Badge im Footer-Bottom
- **Footer Section Headings** – Goldener Unterstrich-Akzent
- **Back-to-Top Button** – Neuimplementierung via CSS-Klasse `.visible`
- **Card Varianten** – `.card-accent` (Gold-Rand oben), `.card-dark` (Dark-Background)
- **Badge-System** – `.badge`, `.badge-primary`, `.badge-accent`, `.badge-success`, `.badge-warning`, `.badge-error`
- **Button Varianten** – `.btn-accent` (Gold), `.btn-outline-dark`, `.btn-sm`, `.btn-lg`
- **Hero Section** – Background-Gradient + Radial-Accent, `clamp()`-Typo, Eingangs-Animationen
- **Animations** – 5 Keyframe-Animationen: `fadeInDown`, `fadeInUp`, `fadeIn`, `slideInLeft`, `pulse`
- **Utility Classes** – `.anim-fade-up`, `.anim-fade-down`, `.anim-fade`, `.anim-slide-left`
- **Responsive** – Überarbeitet mit 3 Breakpoints (1024/768/480px)
- **Page Content** – `.page-content`, `.page-title`, `.breadcrumb` Klassen
- **CSS Custom Properties Erweiterung** – `--accent-color`, `--accent-dark`, `--primary-light`, `--bg-tertiary`, `--bg-dark-alt`, `--bg-dark-card`, `--text-white`, `--border-dark`, `--border-radius-lg`, `--border-radius-xl`, `--header-height`, `--container-width`, Z-Index Scale
- **Dokumentation** – Neue `/doc/theme/` Sektion mit README, DESIGN-SYSTEM, COMPONENTS, JAVASCRIPT, CHANGELOG
- **theme.json v2.0.0** – Version, Beschreibung, neue Supports (`sticky_header`, `burger_menu`, `scroll_animations`, `two_band_footer`), neue Customizer-Einstellungen, aktualisierte Standardfarben

#### Geändert
- **Farbpalette** – `#2563eb` → `#1e3a5f` als primäre Farbe (Navy wie WP-Theme)
- **Header** – Von White-Header zu Dark-Navy-Header
- **Logo** – Text + Icon (`logo-icon`), hover-Akzent in Gold
- **Navigation Links** – Weißer Text auf dunklem Header, Gold-Hover
- **`body`** – `display: flex; flex-direction: column` für sticky Footer
- **`main`** – `flex: 1` damit Footer immer am Ende bleibt
- **Back-to-Top** – Von Inline-Style zu CSS-Klasse `.visible`
- **Footer** – Vollständig neu strukturiert (zweibändig)
- **`header.php`** – Burger-Menü, Mobile-Menü HTML, Dark-Mode-Button, Active-Link-Highlighting
- **`footer.php`** – Neue Struktur, Script-Loading via `<script defer>`, kein Inline-Script mehr
- **Schaltflächen** – Vollständiges Redesign mit `display: inline-flex`, `gap`, `border: 2px`
- **Cards** – Border + Shadow statt nur Shadow, `border-radius-lg`
- **Formulare** – `form-select` und `form-textarea` hinzugefügt
- **Alerts** – `display: flex` mit `gap`

#### Entfernt
- Inline-Styles im `footer.php` (Back-to-Top)
- Inline-`onclick` Handler
- Altes `<script>` Block für Scroll-Handler im Footer
- Generische `.card` Doppelung im CSS

---

## [1.0.0] – 2025-xx-xx

### Initiale Version
- Basis-Layout mit Header, Navigation, Footer
- CSS Custom Properties für Farben, Shadows, Transitions
- Responsive Grid-System
- Formular-Komponenten
- Alert-System
- Sticky Header (einfach)
- Back-to-Top Button (Inline-Style)
