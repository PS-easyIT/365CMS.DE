# CMS Default Theme v2.0.0 – Dokumentation

> **Letzte Aktualisierung:** 19. Februar 2026  
> **Version:** 2.0.0  
> **Vorbild:** WordPress-365Theme-v2

## Übersicht

Das Default Theme des CMSv2 wurde komplett nach dem Vorbild des **WordPress-365Theme-v2** neu gestaltet.  
Es bietet ein modernes, responsives Design mit Fokus auf Benutzerfreundlichkeit, Zugänglichkeit und Performance.

## Neue Features (v2.0.0)

| Feature | Beschreibung |
|---|---|
| **Sticky Header** | Fixierter Header mit Scroll-Effekt (kompakter + dunkler beim Scrollen) |
| **Burger-Menü** | Animiertes Hamburger-Icon → X-Animation auf Mobile |
| **Dark Mode** | System-Präferenz + manueller Toggle, persistiert via localStorage |
| **Scroll-Animationen** | IntersectionObserver-basiert, Performance-optimiert |
| **Zweibandiger Footer** | `footer-top` (Inhalte) + `footer-bottom` (Copyright) |
| **Navy/Gold Farbpalette** | Aligned mit WP-Theme: `#1e3a5f` (Navy) + `#e8a838` (Gold) |
| **Responsive-First** | Vollständig überarbeitet: 480 / 768 / 1024px Breakpoints |
| **Back-to-Top** | CSS-klassen-basiert (kein Inline-Style), AnimationClass `.visible` |

## Dateistruktur

```
themes/default/
├── style.css           # Komplett neu: Variablen, Animationen, alle Komponenten
├── header.php          # Sticky Header, Burger-Menü, Mobile-Navigation
├── footer.php          # Zweibandiger Footer, Back-to-Top, Script-Loading
├── functions.php       # Theme-Setup, Hooks
├── theme.json          # Konfiguration, Customizer-Einstellungen (v2.0.0)
├── js/
│   └── navigation.js   # NEU: Burger, DarkMode, Sticky, Animationen, Back-to-Top
├── home.php            # Startseite
├── page.php            # Standard-Seite
├── login.php           # Login-Seite
├── register.php        # Registrierung
├── 404.php             # 404-Fehlerseite
└── error.php           # Allgemeine Fehlerseite
```

## Dokumentations-Index

| Datei | Inhalt |
|---|---|
| [DESIGN-SYSTEM.md](DESIGN-SYSTEM.md) | Farbpalette, Tokens, Typografie, Spacing |
| [COMPONENTS.md](COMPONENTS.md) | HTML-Klassen-Referenz aller Komponenten |
| [JAVASCRIPT.md](JAVASCRIPT.md) | navigation.js Funktionen & Hooks |
| [CHANGELOG.md](CHANGELOG.md) | Vollständiger Änderungsverlauf |

## Schnell-Start

```php
// Im Theme-Template: Standard-Layout
<?php include_once THEME_PATH . '/header.php'; ?>
<main id="main-content">
    <div class="container page-content">
        <h1 class="page-title">Seitentitel</h1>
        <!-- Inhalt -->
    </div>
</main>
<?php include_once THEME_PATH . '/footer.php'; ?>
```

## Browser-Support

- Chrome 90+ / Edge 90+ / Firefox 88+ / Safari 14+
- IE11: nicht unterstützt (CSS Custom Properties, IntersectionObserver)
