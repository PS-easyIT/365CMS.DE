# Theme-Entwicklung Workflow – 365CMS

> **Bereich:** Theme-System · **Version:** 1.6.14  
> **Referenz:** [THEME-AUDIT.md](../audits/THEME-AUDIT.md)  
> **Ziel:** Theme von Null bis Produktion – sicher, strukturiert, wartbar

---

## Übersicht: Theme-Lifecycle

```
1. Konzept & Scope      → Zweck, Zielgruppe, Design-System
2. Dateistruktur        → Pflichtdateien + theme.json anlegen
3. theme.json           → Metadaten, Customizer-Schema
4. index.php            → Fallback-Template (PFLICHT)
5. functions.php        → Hooks registrieren, Assets einbinden
6. Templates            → header, footer, archive, single, 404
7. CSS-Variablen        → Design-Tokens + Responsive System
8. Customizer-Felder    → Farbschemata, Logos, Texte konfigurierbar
9. Sicherheits-Check    → kein eval(), path-sichere Includes
10. Aktivierung          → Syntax-Check, Pflichtdatei-Check, Rollback
```

---

## Schritt 1: Dateistruktur

```
themes/
└── mein-theme/
    ├── index.php          ← PFLICHT – Template-Fallback
    ├── functions.php      ← PFLICHT – Theme-Initialisierung
    ├── theme.json         ← PFLICHT – Metadaten + Customizer
    ├── style.css          ← PFLICHT – Root-CSS (immer laden)
    ├── screenshot.jpg     ← Vorschau (400×300px) für Admin-UI
    ├── templates/
    │   ├── header.php     ← Seitenkopf (Nav, Logo)
    │   ├── footer.php     ← Seitenende (Links, Copyright)
    │   ├── home.php       ← Startseite
    │   ├── page.php       ← Einzelseite
    │   ├── archive.php    ← Listen-Ansicht
    │   ├── single.php     ← Detail-Ansicht
    │   └── 404.php        ← Fehlerseite
    ├── partials/
    │   ├── nav.php        ← Navigation (wiederverwendbar)
    │   └── hero.php       ← Hero-Section
    └── assets/
        ├── css/
        │   ├── variables.css  ← Design-Tokens (CSS Custom Properties)
        │   └── theme.css      ← Haupt-CSS
        ├── js/
        │   └── theme.js       ← Theme-JavaScript
        └── images/
            └── logo.svg
```

**Quickstart:**
```powershell
$slug = "mein-theme"
$base = "e:\00-WPwork\365CMS.DE\CMS\themes\$slug"
New-Item -ItemType Directory -Path "$base\templates","$base\partials","$base\assets\css","$base\assets\js","$base\assets\images" -Force
```

---

## Schritt 2: theme.json

```json
{
    "name":            "Mein Theme",
    "slug":            "mein-theme",
    "version":         "1.0.0",
    "description":     "Kurze Beschreibung des Themes",
    "author":          "PS-easyIT",
    "min_cms_version": "1.6.0",
    "supports":        ["customizer", "widgets", "menus", "dark-mode"],
    "customizer": {
        "primary_color":   {"type": "color",  "default": "#3b82f6",   "label": "Primärfarbe"},
        "secondary_color": {"type": "color",  "default": "#1e40af",   "label": "Sekundärfarbe"},
        "font_family":     {"type": "select", "default": "system",    "label": "Schriftart",
                            "options": {"system": "Systemschrift", "roboto": "Roboto", "inter": "Inter"}},
        "logo_url":        {"type": "image",  "default": "",          "label": "Logo-URL"},
        "hero_text":       {"type": "text",   "default": "Willkommen","label": "Hero-Überschrift"},
        "hero_subtext":    {"type": "text",   "default": "",          "label": "Hero-Untertext"},
        "show_hero":       {"type": "toggle", "default": true,        "label": "Hero anzeigen"},
        "footer_text":     {"type": "text",   "default": "© 2026",   "label": "Footer-Text"}
    }
}
```

---

## Schritt 3: functions.php Boilerplate

```php
<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

use CMS\Hooks;
use CMS\Services\ThemeCustomizer;

// Konstanten
define('THEME_DIR', get_theme_directory());
define('THEME_URL', get_theme_url());

// Theme-Initialisierung
Hooks::addAction('theme_loaded', function(string $slug) {
    if ($slug !== 'mein-theme') return;

    // CSS + JS laden
    Hooks::addAction('head', 'meinTheme_enqueueStyles');
    Hooks::addAction('footer', 'meinTheme_enqueueScripts');

    // CSS-Variablen aus Customizer
    Hooks::addFilter('theme_css_variables', 'meinTheme_cssVariables');

    // Body-Klassen
    Hooks::addFilter('body_class', 'meinTheme_bodyClass');
});

function meinTheme_enqueueStyles(): void {
    $v = defined('CMS_VERSION') ? CMS_VERSION : '1.0';
    echo '<link rel="stylesheet" href="' . esc_url(THEME_URL . '/assets/css/variables.css') . '?v=' . $v . '">' . PHP_EOL;
    echo '<link rel="stylesheet" href="' . esc_url(THEME_URL . '/assets/css/theme.css') . '?v=' . $v . '">' . PHP_EOL;
}

function meinTheme_enqueueScripts(): void {
    $v = defined('CMS_VERSION') ? CMS_VERSION : '1.0';
    echo '<script src="' . esc_url(THEME_URL . '/assets/js/theme.js') . '?v=' . $v . '" defer></script>' . PHP_EOL;
}

function meinTheme_cssVariables(array $vars): array {
    $c = ThemeCustomizer::instance();
    $vars['--color-primary']   = $c->get('primary_color', '#3b82f6');
    $vars['--color-secondary'] = $c->get('secondary_color', '#1e40af');
    return $vars;
}

function meinTheme_bodyClass(array $classes): array {
    $classes[] = 'theme-mein-theme';
    return $classes;
}
```

---

## Schritt 4: CSS-Variablen-System

**Datei:** `assets/css/variables.css`

```css
:root {
    /* Customizer-Variablen (werden via PHP gesetzt) */
    --color-primary:     #3b82f6;
    --color-secondary:   #1e40af;
    --color-accent:      #f59e0b;

    /* Neutrale Farben */
    --color-bg:          #f8fafc;
    --color-surface:     #ffffff;
    --color-border:      #e2e8f0;
    --color-text:        #1e293b;
    --color-text-muted:  #64748b;

    /* Typografie */
    --font-family:       -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    --font-size-base:    1rem;
    --font-size-lg:      1.125rem;
    --font-size-sm:      0.875rem;
    --line-height:       1.6;

    /* Spacing */
    --spacing-xs:        0.25rem;
    --spacing-sm:        0.5rem;
    --spacing-md:        1rem;
    --spacing-lg:        1.5rem;
    --spacing-xl:        2rem;
    --spacing-2xl:       3rem;

    /* Layout */
    --container-max:     1200px;
    --border-radius:     0.5rem;
    --border-radius-lg:  1rem;
    --shadow-sm:         0 1px 3px rgba(0,0,0,.08);
    --shadow-md:         0 4px 12px rgba(0,0,0,.1);
    --shadow-lg:         0 8px 24px rgba(0,0,0,.12);

    /* Transitions */
    --transition:        0.2s ease;
}

/* Dark Mode */
@media (prefers-color-scheme: dark) {
    :root {
        --color-bg:      #0f172a;
        --color-surface: #1e293b;
        --color-border:  #334155;
        --color-text:    #f1f5f9;
    }
}

/* Responsive Breakpoints */
/* Mobile: < 768px | Tablet: 768-1024px | Desktop: > 1024px */
```

---

## Schritt 5: index.php (Fallback-Template)

```php
<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

// Sicherer Template-Include via ThemeManager
get_template_part('templates/header');
?>

<main class="site-main" role="main">
    <div class="container">
        <?php if (have_posts()): ?>
            <?php while (have_posts()): the_post(); ?>
                <article>
                    <h2><a href="<?php echo esc_url(get_permalink()); ?>"><?php echo esc_html(get_the_title()); ?></a></h2>
                    <div><?php the_excerpt(); ?></div>
                </article>
            <?php endwhile; ?>
        <?php else: ?>
            <p><?php echo esc_html__('Keine Inhalte gefunden.'); ?></p>
        <?php endif; ?>
    </div>
</main>

<?php get_template_part('templates/footer'); ?>
```

---

## Schritt 6: Sicherheits-Checkliste

```
VOR AKTIVIERUNG:
[ ] php -l index.php; php -l functions.php; php -l templates/*.php
[ ] theme.json: json_decode() fehlerfrei
[ ] Keine eval(), exec(), system() im Theme
[ ] Alle Ausgaben: esc_html(), esc_url(), esc_attr()
[ ] theme.json vorhanden + gültig
[ ] index.php vorhanden
[ ] functions.php vorhanden
[ ] screenshot.jpg vorhanden (400×300px)

NACH AKTIVIERUNG:
[ ] Startseite lädt ohne Fehler
[ ] Admin-Ajax: Customizer-Einstellungen speicherbar
[ ] Rollback getestet (vorheriges Theme reaktivieren)
[ ] Mobile-Ansicht (DevTools, <= 768px) korrekt
```

---

## Wichtige Theme-Hooks

| Hook | Typ | Wann |  
|---|---|---|
| `theme_loaded` | Action | Nach Theme-Aktivierung |
| `before_render` | Action | Vor jedem Template-Rendering |
| `head` | Action | Innerhalb `<head>` |
| `footer` | Action | Vor `</body>` |
| `theme_css_variables` | Filter | CSS Custom Properties setzen |
| `body_class` | Filter | CSS-Klassen am `<body>` |
| `nav_menu_items` | Filter | Menü-Einträge modifizieren |

---

## Referenzen

- [THEME-AUDIT.md](../audits/THEME-AUDIT.md) – Sicherheitsanforderungen für Themes
- [theme/DESIGN-SYSTEM.md](../theme/DESIGN-SYSTEM.md) – CSS-Design-Tokens
- [theme/THEME-DEVELOPMENT.md](../theme/THEME-DEVELOPMENT.md) – Ausführlicher Leitfaden
- [ROADMAP_FEB2026.md](../feature/ROADMAP_FEB2026.md) – Theme-Roadmap-Items
