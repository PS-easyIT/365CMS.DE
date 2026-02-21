# 365CMS – Theme-Entwicklung (Anfänger-Guide)

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026

Dieser Guide führt euch Schritt für Schritt durch die Erstellung eines eigenen 365CMS-Themes.

---

## Inhaltsverzeichnis

1. [Voraussetzungen](#1-voraussetzungen)
2. [Theme-Grundstruktur erstellen](#2-theme-grundstruktur-erstellen)
3. [theme.json konfigurieren](#3-themejson-konfigurieren)
4. [Template-Dateien](#4-template-dateien)
5. [CSS & JavaScript](#5-css--javascript)
6. [Theme-Einstellungen nutzbar machen](#6-theme-einstellungen-nutzbar-machen)
7. [Hooks in Templates](#7-hooks-in-templates)
8. [Theme testen](#8-theme-testen)
9. [Best Practices](#9-best-practices)

---

## 1. Voraussetzungen

- PHP-Grundkenntnisse (Variablen ausgeben, `if`, `foreach`)
- HTML & CSS Kenntnisse
- Zugriff auf `themes/`-Ordner des CMS

---

## 2. Theme-Grundstruktur erstellen

```bash
# Im CMS-Root-Ordner:
mkdir themes/mein-theme
mkdir themes/mein-theme/assets
mkdir themes/mein-theme/assets/css
mkdir themes/mein-theme/assets/js
```

Mindest-Dateien:
```
themes/mein-theme/
├── theme.json      ← PFLICHT
├── index.php       ← PFLICHT (Startseite)
├── header.php      ← PFLICHT (Navigation + Head)
├── footer.php      ← PFLICHT (Footer + Scripts)
├── page.php        ← Empfohlen (Einzel-Seite)
├── 404.php         ← Empfohlen (Fehlerseite)
└── assets/
    ├── css/style.css
    └── js/main.js
```

---

## 3. theme.json konfigurieren

```json
{
  "name":        "Mein Theme",
  "slug":        "mein-theme",
  "version":     "1.0.0",
  "author":      "Euer Name",
  "description": "Beschreibung des Themes",
  "screenshot":  "screenshot.png",
  "settings": {
    "colors": {
      "primary":   { "label": "Primärfarbe",   "default": "#3498db", "type": "color" },
      "text":      { "label": "Textfarbe",     "default": "#333333", "type": "color" },
      "background":{ "label": "Hintergrund",   "default": "#ffffff", "type": "color" }
    },
    "typography": {
      "font_body": { "label": "Schriftart",    "default": "Arial, sans-serif", "type": "font" }
    }
  }
}
```

---

## 4. Template-Dateien

### header.php

```php
<?php
// Variablen vom ThemeManager verfügbar machen
$themeManager = CMS\ThemeManager::instance();
$pageManager  = CMS\PageManager::instance();

// Theme-Einstellungen
$primaryColor = $themeManager->getSetting('color_primary', '#3498db');
$siteName     = SITE_NAME;
$siteUrl      = SITE_URL;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageManager->getTitle()); ?></title>

    <!-- Theme-CSS -->
    <link rel="stylesheet" href="<?php echo $themeManager->getAssetUrl('css/style.css'); ?>">

    <!-- CSS-Variablen (aus Customizer) -->
    <style>
        :root {
            --color-primary: <?php echo htmlspecialchars($primaryColor); ?>;
        }
    </style>

    <!-- Plugins können hier CSS einfügen -->
    <?php CMS\Hooks::doAction('wp_head'); ?>
</head>
<body>
<nav>
    <a href="<?php echo $siteUrl; ?>"><?php echo htmlspecialchars($siteName); ?></a>
    <!-- Navigation hier -->
</nav>
```

### footer.php

```php
<footer>
    <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_NAME); ?></p>
</footer>

<!-- Theme-JavaScript -->
<script src="<?php echo CMS\ThemeManager::instance()->getAssetUrl('js/main.js'); ?>"></script>

<!-- Plugins können hier Scripts einfügen -->
<?php CMS\Hooks::doAction('wp_footer'); ?>
</body>
</html>
```

### index.php (Startseite)

```php
<?php
// Variablen verfügbar: $themeManager, $auth, $db (aus Bootstrap)
include __DIR__ . '/header.php';
?>

<main>
    <section class="hero">
        <h1><?php echo htmlspecialchars(SITE_NAME); ?></h1>
        <p>Willkommen auf unserer Website!</p>
    </section>

    <section class="latest-posts">
        <h2>Neueste Beiträge</h2>
        <?php
        $db = CMS\Database::instance();
        $posts = $db->get_results(
            "SELECT * FROM cms_posts WHERE status = 'published' ORDER BY published_at DESC LIMIT 6",
            []
        );
        foreach ($posts as $post): ?>
            <article>
                <h3>
                    <a href="/post/<?php echo htmlspecialchars($post->slug); ?>">
                        <?php echo htmlspecialchars($post->title); ?>
                    </a>
                </h3>
                <p><?php echo htmlspecialchars($post->excerpt ?? ''); ?></p>
            </article>
        <?php endforeach; ?>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
```

### page.php (Einzel-Seite)

```php
<?php include __DIR__ . '/header.php'; ?>

<main>
    <?php if ($page): ?>
        <?php if (!$page->hide_title): ?>
            <h1><?php echo htmlspecialchars($page->title); ?></h1>
        <?php endif; ?>
        <div class="page-content">
            <?php echo $page->content; // HTML bereits gespeichert – kein Escaping! ?>
        </div>
    <?php else: ?>
        <h1>Seite nicht gefunden</h1>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>
```

### 404.php

```php
<?php http_response_code(404); ?>
<?php include __DIR__ . '/header.php'; ?>
<main>
    <h1>404 – Seite nicht gefunden</h1>
    <p>Die gesuchte Seite existiert nicht.</p>
    <a href="<?php echo SITE_URL; ?>">Zurück zur Startseite</a>
</main>
<?php include __DIR__ . '/footer.php'; ?>
```

---

## 5. CSS & JavaScript

### assets/css/style.css

```css
/* CSS-Variablen (werden vom Customizer überschrieben) */
:root {
    --color-primary:    #3498db;
    --color-text:       #333333;
    --color-background: #ffffff;
    --font-body:        Arial, sans-serif;
    --container-width:  1200px;
}

* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: var(--font-body); color: var(--color-text); background: var(--color-background); }

.container { max-width: var(--container-width); margin: 0 auto; padding: 0 1rem; }
nav { background: var(--color-primary); padding: 1rem; }
nav a { color: white; text-decoration: none; font-size: 1.2rem; }

.hero { padding: 4rem 2rem; text-align: center; }
.hero h1 { font-size: 2.5rem; margin-bottom: 1rem; }

footer { background: #222; color: white; text-align: center; padding: 2rem; margin-top: 4rem; }
```

### assets/js/main.js

```javascript
// Kein jQuery erforderlich – reines JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Euer Theme-JavaScript
    console.log('Theme geladen: ' + document.title);
});
```

---

## 6. Theme-Einstellungen nutzbar machen

Der `ThemeManager` liest Einstellungen aus der Datenbank:

```php
$themeManager = CMS\ThemeManager::instance();

// Einzelne Einstellung mit Fallback
$primaryColor = $themeManager->getSetting('color_primary', '#3498db');
$fontBody     = $themeManager->getSetting('font_body', 'Arial, sans-serif');

// CSS-Variablen dynamisch ausgeben (in header.php):
$settings = $themeManager->getSettings();
echo '<style>:root {';
foreach ($settings as $key => $value) {
    echo '--' . htmlspecialchars($key) . ':' . htmlspecialchars($value) . ';';
}
echo '}</style>';
```

---

## 7. Hooks in Templates

```php
// Im Header – für Plugins, die CSS/Meta-Tags hinzufügen wollen
CMS\Hooks::doAction('wp_head');

// Im Footer – für Plugins, die Scripts hinzufügen wollen
CMS\Hooks::doAction('wp_footer');

// Vor dem Haupt-Inhalt
CMS\Hooks::doAction('before_content', $page);

// Nach dem Haupt-Inhalt
CMS\Hooks::doAction('after_content', $page);

// Filter: Inhalt vor Ausgabe
$content = CMS\Hooks::applyFilters('the_content', $page->content);
echo $content;
```

---

## 8. Theme testen

**Checkliste:**
- [ ] Startseite lädt ohne Fehler
- [ ] Einzel-Seite (`/seiten-slug`) lädt korrekt
- [ ] 404-Seite erscheint bei ungültigen URLs
- [ ] Login-Seite (`/login`) sieht gut aus
- [ ] Registrierungs-Seite (`/register`) funktioniert
- [ ] Theme wechseln im Admin und zurück möglich
- [ ] Customizer-Änderungen (Farbe) werden sofort angezeigt
- [ ] Mobile-Ansicht (responsive Design) korrekt

---

## 9. Best Practices

**DO:**
- CSS-Variablen für alle Farben und Fonts nutzen (Customizer-kompatibel)
- Alle Ausgaben mit `htmlspecialchars()` escapen
- `CMS\Hooks::doAction('wp_head')` im `<head>` aufrufen
- `CMS\Hooks::doAction('wp_footer')` vor `</body>` aufrufen
- Theme über Admin aktivieren (nicht manuell in DB schreiben)

**DON'T:**
- Core-PHP-Dateien aus dem Theme aufrufen (`require_once CORE_PATH ...` ist OK, aber Core-Klassen nicht verändern)
- Direkt `$_POST`/`$_GET` in Templates ausgeben (immer escapen!)
- Kritische Logik im Theme (gehört in Plugins oder Services)

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
