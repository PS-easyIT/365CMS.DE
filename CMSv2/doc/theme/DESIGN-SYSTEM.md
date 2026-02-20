# Design System – CMS Default Theme v2.0.0

## Farbpalette (CSS Custom Properties)

### Brand Colors

| Variable | Wert | Verwendung |
|---|---|---|
| `--primary-color` | `#1e3a5f` | Header, Primär-Buttons |
| `--primary-dark` | `#0f2240` | Scrolled-Header, Hover |
| `--primary-light` | `#2563eb` | Links, Focus-Ringe |
| `--accent-color` | `#e8a838` | CTA-Buttons, Highlights, Footer-Akzente |
| `--accent-dark` | `#c8891e` | Accent-Hover |
| `--secondary-color` | `#64748b` | Sekundäre Elemente |

### Status Colors

| Variable | Wert | Verwendung |
|---|---|---|
| `--success-color` | `#10b981` | Erfolgsmeldungen |
| `--warning-color` | `#f59e0b` | Warnhinweise |
| `--error-color` | `#ef4444` | Fehlermeldungen |

### Backgrounds

| Variable | Wert |
|---|---|
| `--bg-primary` | `#ffffff` |
| `--bg-secondary` | `#f1f5f9` |
| `--bg-tertiary` | `#e2e8f0` |
| `--bg-dark` | `#0a0f1a` |
| `--bg-dark-alt` | `#0f172a` |
| `--bg-dark-card` | `#1e293b` |

### Text

| Variable | Wert |
|---|---|
| `--text-primary` | `#1e293b` |
| `--text-secondary` | `#64748b` |
| `--text-light` | `#94a3b8` |
| `--text-white` | `#f8fafc` |

---

## Typography

Schriftgröße wird mit `clamp()` fluid skaliert:

```css
h1 { font-size: clamp(1.8rem, 4vw, 2.75rem); }
h2 { font-size: clamp(1.4rem, 3vw, 2.25rem); }
h3 { font-size: clamp(1.1rem, 2.5vw, 1.75rem); }
```

**Basis-Font-Stack:**
```
-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif
```

---

## Spacing System

Basierend auf `rem`-Einheiten (1rem = 16px):

| Class | Padding |
|---|---|
| `.section` | `padding: 5rem 0` |
| `.section-sm` | `padding: 3rem 0` |
| `.section-lg` | `padding: 7rem 0` |
| `.container` | `padding: 0 2rem` |

**Max-Width Container:** `--container-width: 1280px`

---

## Shadows

| Variable | Wert |
|---|---|
| `--shadow-sm` | `0 1px 3px 0 rgb(0 0 0 / 0.07)` |
| `--shadow-md` | `0 4px 12px -1px rgb(0 0 0 / 0.12)` |
| `--shadow-lg` | `0 10px 30px -3px rgb(0 0 0 / 0.15)` |
| `--shadow-xl` | `0 20px 60px -5px rgb(0 0 0 / 0.2)` |
| `--shadow-glow` | `0 0 20px rgba(30, 58, 95, 0.2)` |

---

## Border Radius

| Variable | Wert | Verwendung |
|---|---|---|
| `--border-radius` | `8px` | Buttons, Inputs, kleine Elemente |
| `--border-radius-lg` | `16px` | Cards |
| `--border-radius-xl` | `24px` | Große Panels |

---

## Transitions

| Variable | Wert |
|---|---|
| `--transition` | `all 0.3s cubic-bezier(0.4, 0, 0.2, 1)` |
| `--transition-fast` | `all 0.15s ease` |
| `--transition-slow` | `all 0.5s ease` |

---

## Z-Index Scale

| Variable | Wert | Element |
|---|---|---|
| `--z-header` | `1000` | Sticky Header |
| `--z-mobile-menu` | `1100` | Mobile Menü Overlay |
| `--z-overlay` | `1200` | Modale Overlays |
| `--z-modal` | `1300` | Modals |
| `--z-toast` | `1400` | Toast-Nachrichten |

---

## Breakpoints

| Breakpoint | Breite | Wichtigste Änderung |
|---|---|---|
| Desktop | `> 1024px` | Volle Navigation, Desktop-Layout |
| Tablet | `≤ 1024px` | Reduzierter Padding |
| Mobile | `≤ 768px` | Burger-Menü aktiv, Desktop-Nav ausgeblendet |
| Small Mobile | `≤ 480px` | Minimierter Padding, vertikale Stack-Layouts |

---

## Dark Mode

Aktivierung via:
- System: `prefers-color-scheme: dark`
- Manuell: `.dark-mode` Klasse auf `body`
- Persistiert via `localStorage` Key: `cms365-theme`

Im Dark Mode werden überschrieben:
- `--bg-primary` → `#1e293b`
- `--bg-secondary` → `#0f172a`
- `--text-primary` → `#f1f5f9`
- `--border-color` → `#334155`

---

## Animation Utilities

```html
<!-- Fade Up beim Laden -->
<div class="anim-fade-up">...</div>

<!-- Fade Down beim Laden -->
<div class="anim-fade-down">...</div>

<!-- Einblenden beim Scrollen (JavaScript-gesteuert) -->
<div data-anim>...</div>
```

CSS-Klassen: `anim-fade-up`, `anim-fade-down`, `anim-fade`, `anim-slide-left`  
Scroll-Animation: Element bekommt `is-visible` Klasse via IntersectionObserver.
