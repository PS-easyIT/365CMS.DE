# Komponenten-Referenz – CMS Default Theme v2.0.0

## Header

### Sticky Header
Automatisch aktiviert. Beim Scrollen > 60px: `.scrolled` Klasse → kompakter + dunkler.

```html
<header class="site-header" id="site-header">
    <div class="header-container">
        <a href="/" class="site-logo">
            <span class="logo-icon">⚡</span>
            <span class="logo-text">Seitenname</span>
        </a>
        <nav class="main-nav">...</nav>
        <div class="header-actions">
            <button class="theme-toggle" id="theme-toggle">🌙</button>
            <button class="mobile-menu-toggle" id="burger-toggle">...</button>
        </div>
    </div>
</header>
```

### Burger-Menü Button

```html
<button class="mobile-menu-toggle" id="burger-toggle"
        aria-controls="mobile-menu"
        aria-expanded="false"
        aria-label="Menü öffnen">
    <span class="hamburger-line"></span>
    <span class="hamburger-line"></span>
    <span class="hamburger-line"></span>
</button>
```

Zustände:
- `Default`: ☰ drei Linien
- `.is-active`: ✕ gekreuzt (CSS-Transform)

### Mobile Menü

```html
<nav class="mobile-menu" id="mobile-menu" aria-hidden="true">
    <div class="mobile-menu-nav">
        <a href="/" class="active">Home</a>
        <a href="/about">Über uns</a>
        <div class="mobile-menu-divider"></div>
        <a href="/login">Anmelden</a>
        <a href="/register" class="mobile-btn-primary">Registrieren</a>
    </div>
</nav>
```

Aktivierung: `.is-active` + `aria-hidden="false"` via JavaScript.

---

## Navigation

### Desktop Nav

```html
<nav class="main-nav">
    <a href="/" class="active">Home</a>
    <a href="/about">Über uns</a>
    <a href="/register" class="btn-nav-primary">Registrieren</a>
</nav>
```

Klassen: `.active` (aktuell), `.btn-nav-primary` (Gold CTA-Button).

---

## Footer

### Zweibandige Struktur

```html
<footer class="site-footer">
    <!-- Oberes Band -->
    <div class="footer-top">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Über uns</h3>
                <p>Beschreibungstext...</p>
            </div>
            <div class="footer-section">
                <h3>Links</h3>
                <a href="/">Home</a>
                <a href="/login">Anmelden</a>
            </div>
        </div>
    </div>
    <!-- Unteres Band -->
    <div class="footer-bottom">
        <div class="footer-bottom-inner">
            <p>&copy; 2026 Seitenname.</p>
            <span class="footer-badge">⚡ CMSv2</span>
        </div>
    </div>
</footer>
```

---

## Buttons

```html
<!-- Primär (Navy) -->
<a href="#" class="btn btn-primary">Primär</a>

<!-- Akzent (Gold) - für CTAs -->
<a href="#" class="btn btn-accent">Jetzt starten</a>

<!-- Sekundär -->
<a href="#" class="btn btn-secondary">Sekundär</a>

<!-- Outline (auf dunklem Hintergrund) -->
<a href="#" class="btn btn-outline">Outline</a>

<!-- Outline (auf hellem Hintergrund) -->
<a href="#" class="btn btn-outline-dark">Outline</a>

<!-- Größen -->
<a href="#" class="btn btn-primary btn-sm">Klein</a>
<a href="#" class="btn btn-primary btn-lg">Groß</a>
```

---

## Cards

```html
<!-- Standard Card -->
<div class="card">
    <h3>Titel</h3>
    <p>Inhalt...</p>
</div>

<!-- Card mit Accent-Border oben -->
<div class="card card-accent">...</div>

<!-- Dark Card -->
<div class="card card-dark">...</div>

<!-- Card mit Header -->
<div class="card">
    <div class="card-header">
        <h3>Titel</h3>
    </div>
    <p>Inhalt...</p>
</div>
```

---

## Badges & Tags

```html
<span class="badge badge-primary">IT-Experte</span>
<span class="badge badge-accent">Sponsor</span>
<span class="badge badge-success">Verifiziert</span>
<span class="badge badge-warning">Ausstehend</span>
<span class="badge badge-error">Abgelehnt</span>
```

---

## Grid-Layouts

```html
<!-- 2 Spalten (auto-fit min 300px) -->
<div class="grid grid-2">
    <div>...</div>
    <div>...</div>
</div>

<!-- 3 Spalten (auto-fit min 240px) -->
<div class="grid grid-3">...</div>

<!-- 4 Spalten (auto-fit min 200px) -->
<div class="grid grid-4">...</div>
```

---

## Formulare

```html
<div class="form-group">
    <label class="form-label" for="email">E-Mail-Adresse</label>
    <input type="email" id="email" name="email"
           class="form-input" placeholder="name@beispiel.de">
</div>

<div class="form-group">
    <label class="form-label" for="msg">Nachricht</label>
    <textarea id="msg" name="msg" class="form-textarea"></textarea>
</div>

<div class="form-group">
    <label class="form-label" for="type">Typ</label>
    <select id="type" class="form-select">
        <option>Option 1</option>
    </select>
</div>
```

---

## Alerts / Meldungen

```html
<div class="alert alert-success">✓ Erfolgreich gespeichert.</div>
<div class="alert alert-error">✗ Ein Fehler ist aufgetreten.</div>
<div class="alert alert-warning">⚠ Bitte prüfen Sie Ihre Eingabe.</div>
<div class="alert alert-info">ℹ Hinweis: Ihre Daten werden verarbeitet.</div>

<!-- Auto-dismiss nach 5 Sekunden -->
<div class="alert alert-success" data-auto-dismiss="5000">Gespeichert!</div>
```

---

## Seitenstruktur

```html
<!-- Standard Page Content -->
<main id="main-content">
    <div class="container page-content">

        <!-- Optional: Breadcrumb -->
        <nav class="breadcrumb">
            <a href="/">Home</a>
            <span class="breadcrumb-separator">/</span>
            <span>Aktuelle Seite</span>
        </nav>

        <h1 class="page-title">Seitentitel</h1>
        
        <!-- Inhalt -->
    </div>
</main>
```

---

## Hero Section

```html
<section class="hero">
    <div class="container">
        <h1>Willkommen im IT-Netzwerk</h1>
        <p class="hero-sub">Die Plattform für IT-Profis.</p>
        <div class="hero-actions">
            <a href="/register" class="btn btn-accent btn-lg">Jetzt registrieren</a>
            <a href="/about" class="btn btn-outline btn-lg">Mehr erfahren</a>
        </div>
    </div>
</section>
```

---

## Back to Top

Automatisch aktiv. Wird als CSS-Klasse `.visible` gesteuert (kein Inline-Style).

```html
<!-- In footer.php –automatisch vorhanden -->
<button id="back-to-top" aria-label="Nach oben scrollen">↑</button>
```

---

## Scroll Animations

```html
<!-- Element wird animiert wenn in Viewport -->
<div class="card" data-anim>
    <h3>Animierter Inhalt</h3>
</div>
```

Beim Sichtbarwerden wird `.is-visible` hinzugefügt. Eigene CSS definieren:

```css
[data-anim] { opacity: 0; transform: translateY(20px); transition: opacity 0.6s ease, transform 0.6s ease; }
[data-anim].is-visible { opacity: 1; transform: translateY(0); }
```
