# JavaScript – navigation.js Referenz

**Datei:** `themes/default/js/navigation.js`  
**Version:** 2.0.0  
**Ladeweise:** `<script defer>` im Footer

---

## Module-Übersicht

Alle Module werden in einem IIFE (`(function(){...})()`) gekapselt und nach DOMContentLoaded initialisiert.

### 1. `initStickyHeader()`

Fügt `.scrolled` Klasse zum `#site-header` hinzu wenn `window.scrollY > 60`.

```css
/* CSS-Effekt */
.site-header.scrolled {
    height: 60px;
    background: var(--primary-dark);
    box-shadow: 0 4px 24px rgba(0,0,0,0.3);
}
```

**Event:** `scroll` (passive Listener)

---

### 2. `initBurgerMenu()`

Steuert das Mobile-Menü über `#burger-toggle` (Button) und `#mobile-menu` (Navigation).

**Aktionen:**
- Click auf Toggle → Menü öffnen/schließen
- Click außerhalb → Menü schließen
- `Escape` Taste → Menü schließen, Focus auf Toggle zurück
- Link-Click im Menü → Menü schließen

**Zustände:**
```js
// Öffnen
toggle.setAttribute('aria-expanded', 'true');
toggle.classList.add('is-active');
mobileNav.classList.add('is-active');
mobileNav.setAttribute('aria-hidden', 'false');
body.classList.add('mobile-menu-open');

// Schließen
toggle.setAttribute('aria-expanded', 'false');
mobileNav.classList.remove('is-active');
mobileNav.setAttribute('aria-hidden', 'true');
body.classList.remove('mobile-menu-open');
```

`body.mobile-menu-open` → `overflow: hidden` (kein Scrollen im Hintergrund).

---

### 3. `initDarkMode()`

Verwaltet den Dark Mode mit System-Präferenz und manuellem Toggle.

**Reihenfolge:**
1. `localStorage.getItem('cms365-theme')` prüfen
2. Falls nicht gesetzt: `prefers-color-scheme: dark` prüfen
3. Entsprechend `.dark-mode` auf `body` setzen

**Storage-Key:** `cms365-theme` (Werte: `'dark'` | `'light'`)

```js
// Manuell aktivieren
document.body.classList.toggle('dark-mode');
localStorage.setItem('cms365-theme', 'dark');
```

---

### 4. `initBackToTop()`

Zeigt `#back-to-top` Button wenn `window.scrollY > 400`.

Verwendet CSS-Klasse `.visible` statt Inline-Style:
```css
#back-to-top.visible { display: flex; animation: fadeInUp 0.3s ease both; }
```

---

### 5. `initScrollAnimations()`

Verwendet `IntersectionObserver` für Performance-optimierte Scroll-Animationen.

- Selector: `[data-anim]`
- Threshold: `0.12` (12% sichtbar)
- Fügt bei Sichtbarkeit `.is-visible` hinzu
- `unobserve()` nach erstem Trigger (einmalig)

**Graceful Degradation:** Kein Observer → Elemente bleiben normal sichtbar.

---

### 6. `initActiveNav()`

Markiert den aktuellen Nav-Link mit `.active` anhand `window.location.pathname`.

Gilt für `.main-nav a` und `.mobile-menu-nav a`.

---

### 7. `initFlashMessages()`

Auto-dismiss für Alerts mit `data-auto-dismiss` Attribut.

```html
<div class="alert alert-success" data-auto-dismiss="4000">Nachricht</div>
```

Nach `delay` ms: Fade-out + maximale Höhe auf 0, dann `.remove()`.

---

## Custom Events

Eigene Module können auf Theme-Events lauschen (geplant v2.1.0):

```js
// Burger-Menü geöffnet
document.addEventListener('cms365:menuOpen', function(e) { ... });

// Dark Mode geändert  
document.addEventListener('cms365:themeChange', function(e) {
    console.log(e.detail.isDark); // boolean
});
```

---

## Eigenes JavaScript erweitern

Über den Theme Customizer (Admin → Design → Erweitert → Eigenes JavaScript) oder via PHP-Hook:

```php
// In theme functions.php oder Plugin
CMS\Hooks::addAction('body_end', function() {
    echo '<script src="/path/to/custom.js" defer></script>';
});
```
