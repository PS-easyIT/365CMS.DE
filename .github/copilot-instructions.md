# 365CMS Admin-Bereich – GitHub Copilot Design-Richtlinien

> Gilt für **alle Admin-Seiten** in `CMS/admin/` – ausgenommen sind Plugin-spezifische Frontends und Theme-Editor-Seiten.
> Pfade beziehen sich immer auf das Projektverzeichnis `365CMS.DE/`.

---

## 1. Allgemeine Architektur

### 1.1 Datei-Boilerplate (jede Admin-Seite)

```php
<?php
/**
 * [Seitenname]
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Security;

if (!defined('ABSPATH')) {
    exit;
}

// Nur Admins dürfen diese Seite sehen
if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

// CSRF-Token erzeugen (eindeutiger Action-Bezeichner pro Seite)
$csrfToken = Security::instance()->generateToken('meine_seite');

// Admin-Menü einbinden
require_once __DIR__ . '/partials/admin-menu.php';
?>
```

### 1.2 HTML-Grundgerüst

```html
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seitenname – <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=20260222b">
    <?php renderAdminSidebarStyles(); ?>
</head>
<body class="admin-body">

    <?php renderAdminSidebar('sidebar-slug'); ?>

    <div class="admin-content">

        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h2>🔧 Seitenname</h2>
                <p>Kurze Beschreibung was hier verwaltet wird</p>
            </div>
            <div class="header-actions">
                <!-- Optionale Aktionsbuttons rechts oben -->
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Inhalt hier -->

    </div><!-- /.admin-content -->

    <script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
</body>
</html>
```

**Regeln:**
- `admin.css` immer mit Cache-Busting-Parameter (`?v=YYYYMMDD`) einbinden
- `renderAdminSidebarStyles()` und `renderAdminSidebar('slug')` sind **immer** aufzurufen
- Der `sidebar-slug` entspricht dem aktiven Menüpunkt (z. B. `'dashboard'`, `'users'`, `'media-library'`)
- JavaScript-Dateien kommen ans **Ende** des `<body>`

---

## 2. Layout & CSS-Dateien

### 2.1 CSS-Architektur

| Datei | Zweck |
|---|---|
| `assets/css/admin.css` | Haupt-Stylesheet für alle Admin-Seiten; importiert automatisch `admin-sidebar.css` |
| `assets/css/admin-sidebar.css` | Sidebar-Navigation (nie direkt ändern, nur indirekt über `admin.css`) |
| `assets/css/main.css` | Wenige globale Tokens (Formulare, Basiselemente); auch im Admin eingebunden |
| `assets/css/member.css` | **Nicht** im Admin verwenden |

**Niemals** Inline-`style`-Attribute für Layout-/Design-Eigenschaften verwenden, die sich in `admin.css` bereits befinden. Ausnahmen: dynamische Werte (Farben aus DB, berechnete Breiten via PHP).

### 2.2 Haupt-Layout

```
┌───────────────────────────────────────────────────────┐
│ .admin-sidebar (260 px, fixed, dark: #0c1526)         │
│   .admin-sidebar-header (Logo)                        │
│   nav.admin-nav (Menüpunkte)                          │
│   .admin-sidebar-footer (Logout)                      │
├───────────────────────────────────────────────────────┤
│ .admin-content (margin-left: 260 px, padding: 2 rem)  │
│   .admin-page-header                                  │
│   Alerts                                              │
│   Hauptinhalt (Cards, Tabellen, Formulare …)          │
└───────────────────────────────────────────────────────┘
```

### 2.3 CSS Custom Properties (Design Tokens)

| Variable | Wert | Verwendung |
|---|---|---|
| `--admin-primary` | `#3b82f6` | Akzentfarbe, aktive Elemente, Links |
| `--admin-primary-dark` | `#2563eb` | Hover-Zustand der primären Farbe |
| `--admin-primary-light` | `#eff6ff` | Hintergrund bei aktiven/markierten Elementen |
| `--admin-sidebar-width` | `260px` | Sidebar-Breite (Alias: `--sidebar-width`) |
| `--card-radius` | `10px` | Border-Radius für alle Cards |
| `--card-border` | `1px solid #e2e8f0` | Standard-Card-Border |
| `--card-shadow` | `0 1px 4px rgba(0,0,0,.06)` | Subtiler Card-Schatten |
| `--card-shadow-hover` | `0 4px 12px rgba(0,0,0,.1)` | Card-Schatten bei Hover |
| `--card-bg` | `#fff` | Card-Hintergrund |
| `--card-header-bg` | `#f8fafc` | Kopfbereich innerhalb einer Card |

**Seitenfarben:**
- Body-Hintergrund: `#f1f5f9` (gesetzt via `.admin-body`)
- Primärtext: `#1e293b`
- Sekundärtext / Labels: `#475569`
- Gedämpfter Text / Hints: `#64748b`, `#94a3b8`
- Trennlinien / Borders: `#e2e8f0`, `#f1f5f9`

---

## 3. Page Header

Jede Admin-Seite beginnt direkt nach dem Öffnen von `.admin-content` mit einem `.admin-page-header`.

```html
<div class="admin-page-header">
    <div>
        <h2>📄 Seitenname</h2>
        <p>Kurze erklärende Beschreibung</p>
    </div>
    <div class="header-actions">
        <a href="..." class="btn btn-primary">➕ Neu erstellen</a>
    </div>
</div>
```

**Regeln:**
- `h2` enthält immer ein vorangestelltes Emoji als visuelle Orientierung
- `p` ist optional, aber empfohlen – max. 1 Satz, Farbe `#64748b`
- `header-actions` enthält maximal 3–4 Buttons; bei mehr → Dropdown
- Kein zusätzliches Margin/Padding via Inline-Style; `admin-page-header` bringt eigene Styles

---

## 4. Cards & Sektionen

### 4.1 Standard-Card

```html
<div class="admin-card">
    <h3>🗂️ Abschnittstitel</h3>
    <!-- Inhalt -->
</div>
```

- `.admin-card` und `.admin-section` sind **synonym** – beide referenzieren dieselben CSS-Regeln
- `h3` innerhalb einer Card: `font-size: 1.1rem`, `font-weight: 700`, Trennlinie darunter (`border-bottom: 1px solid #f1f5f9`)
- Margin-Bottom der Card: `1.5rem`
- Padding: `1.5rem`

### 4.2 Stat-Card (Dashboard-Kacheln)

```html
<div class="dashboard-grid">
    <div class="stat-card">
        <h3>Benutzer</h3>
        <div class="stat-number"><?php echo $stats['users']; ?></div>
        <div class="stat-label">Registrierte Mitglieder</div>
    </div>
</div>
```

- Grid: `repeat(auto-fit, minmax(250px, 1fr))`, Gap `1.5rem`
- `stat-number`: `font-size: 2rem`, Farbe `--admin-primary`
- Nur für numerische KPIs auf Dashboard-Seiten verwenden

### 4.3 Info-Card (zweispaltige Info-Übersichten)

```html
<div class="info-grid">
    <div class="info-card">
        <h4>Details</h4>
        <ul class="info-list">
            <li><strong>Status:</strong> Aktiv</li>
            <li><strong>Erstellt:</strong> 2026-01-01</li>
        </ul>
    </div>
</div>
```

---

## 5. Formulare

### 5.1 Formular-Struktur

```html
<form method="POST" class="admin-form">
    <input type="hidden" name="action_name" value="meine_aktion">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

    <div class="admin-card">
        <h3>✏️ Grunddaten</h3>

        <div class="form-group">
            <label for="title" class="form-label">
                Titel <span style="color:#ef4444;">*</span>
            </label>
            <input type="text" id="title" name="title" class="form-control"
                   value="<?php echo htmlspecialchars($data['title'] ?? ''); ?>" required>
            <small class="form-text">Wird als Seitentitel angezeigt.</small>
        </div>

        <div class="form-group">
            <label for="description" class="form-label">Beschreibung</label>
            <textarea id="description" name="description" class="form-control">
                <?php echo htmlspecialchars($data['description'] ?? ''); ?>
            </textarea>
        </div>
    </div>

    <!-- Sticky Save Bar -->
    <div class="admin-card form-actions-card">
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Speichern</button>
            <span class="form-actions__hint">Änderungen werden sofort übernommen</span>
        </div>
    </div>
</form>
```

### 5.2 Formular-Elemente

| Element | Klasse(n) | Regeln |
|---|---|---|
| Eingabefeld | `.form-control` | Volle Breite, `border: 2px solid #e2e8f0`, Focus: Primärfarbe + Box-Shadow |
| Label | `.form-label` | Block-Element, `font-weight` mind. 500, über dem Input |
| Hilfetext | `.form-text` | `font-size: .875rem`, Farbe `#64748b`, unter dem Input |
| Pflichtfeld-Marker | `<span style="color:#ef4444;">*</span>` | Immer direkt nach dem Label-Text |
| Textarea | `.form-control` + `resize: vertical` | `min-height: 120px` |
| Select | `.form-control` | Wie Input behandeln |
| Checkbox/Radio | `.checkbox-label` / `.radio-label` | Flex-Container mit Gap `.5rem`; Hover: `background: #f1f5f9` |

### 5.3 Zweispaltige Formular-Zeile

```html
<div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
    <div class="form-group">...</div>
    <div class="form-group">...</div>
</div>
```

Alternativ: `lp-form-row` für Landing-Page-spezifische Layouts.

### 5.4 CSRF-Pflicht

**Jedes** Formular und **jede** AJAX-POST-Anfrage muss einen `csrf_token` enthalten und serverseitig prüfen:

```php
if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'meine_seite')) {
    $error = 'Sicherheitscheck fehlgeschlagen';
} else {
    // Verarbeitung
}
```

### 5.5 Eingabe-Sanitierung (PHP, Serverseite)

```php
$title       = sanitize_text_field($_POST['title'] ?? '');
$email       = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$url         = filter_var($_POST['url'] ?? '', FILTER_VALIDATE_URL) ?: '';
$html        = strip_tags($_POST['content'] ?? '', '<p><a><strong><em><ul><ol><li>');
$intVal      = (int) ($_POST['count'] ?? 0);
```

### 5.6 Ausgabe-Escaping (PHP, HTML)

```php
echo htmlspecialchars($value);               // Text-Nodes
echo htmlspecialchars($value, ENT_QUOTES);   // Attribute
// Bei vertrauenswürdigem HTML (z. B. aus eigenem WYSIWYG):
echo $safeHtml; // Nur nach Sanitierung erlaubt
```

---

## 6. Buttons

### 6.1 Button-Varianten

```html
<!-- Primär (Hauptaktion) -->
<button type="submit" class="btn btn-primary">💾 Speichern</button>

<!-- Sekundär (Nebenaktionen) -->
<button type="button" class="btn btn-secondary">↩️ Zurück</button>

<!-- Gefährlich (Löschen, Deaktivieren) -->
<button type="button" class="btn btn-danger">🗑️ Löschen</button>

<!-- Als Link -->
<a href="/admin/page.php" class="btn btn-primary">➕ Neu</a>

<!-- Klein -->
<button type="button" class="btn btn-primary btn-sm">Bearbeiten</button>

<!-- Outline / Ghost Button -->
<button type="button" class="btn btn-outline">Abbrechen</button>
```

### 6.2 Button-Regeln

- **Primäre Aktionen** (Speichern, Erstellen): `.btn-primary` (Blau `#3b82f6`)
- **Sekundäre Aktionen** (Zurück, Abbrechen): `.btn-secondary` (Grau `#64748b`)
- **Destruktive Aktionen** (Löschen, Entfernen): `.btn-danger` (Rot `#ef4444`)
- **Nie** mehr als 1 primären Button in einem sichtbaren Bereich
- Buttons in Header-Aktionen: rechts im `.header-actions`-Container
- Abstandsregel zwischen Buttons: `gap: 0.6rem` in Flex-Containern
- `.btn` hat `padding: 0.75rem 1.5rem`, `.btn-sm` hat `padding: 0.375rem 0.875rem`

---

## 7. Alerts & Benachrichtigungen

```html
<!-- Erfolg -->
<div class="alert alert-success">✅ Einstellungen wurden gespeichert.</div>

<!-- Fehler -->
<div class="alert alert-error">❌ Beim Speichern ist ein Fehler aufgetreten.</div>

<!-- Info (kann per Inline-Style ergänzt werden) -->
<div class="alert" style="background:#dbeafe;color:#1e40af;border-left:4px solid #3b82f6;">
    ℹ️ Hinweis-Text
</div>
```

**Platzierung:** Direkt nach `.admin-page-header`, vor dem eigentlichen Inhalt.

PHP-Pattern:
```php
<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
```

---

## 8. Tabellen

```html
<div class="users-table-container">
    <table class="users-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>E-Mail</th>
                <th>Status</th>
                <th>Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td><?php echo htmlspecialchars($item['email']); ?></td>
                <td>
                    <span class="status-badge <?php echo $item['active'] ? 'active' : 'inactive'; ?>">
                        <?php echo $item['active'] ? 'Aktiv' : 'Inaktiv'; ?>
                    </span>
                </td>
                <td>
                    <div style="display:flex;gap:.5rem;">
                        <a href="?edit=<?php echo (int)$item['id']; ?>" class="btn btn-sm btn-secondary">✏️</a>
                        <button class="btn btn-sm btn-danger" onclick="deleteItem(<?php echo (int)$item['id']; ?>)">🗑️</button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
```

**Regeln:**
- Tabellen-Container (`.users-table-container`) sorgt für horizontales Scrollen auf kleinen Bildschirmen
- `thead` hat Hintergrund `#f8fafc`
- `th` hat `padding: 1rem`, `font-weight: 600`, Farbe `#475569`
- `td` hat `padding: 1rem`, Farbe `#1e293b`
- Zeilentrennlinie: `border-bottom: 1px solid #e2e8f0`
- Aktionen-Spalte: immer letzte Spalte, Buttons in Flex-Container

---

## 9. Badges & Status-Anzeigen

```html
<!-- Status-Badge (aktiv/inaktiv) -->
<span class="status-badge active">Aktiv</span>
<span class="status-badge inactive">Inaktiv</span>

<!-- Rollen-Badge -->
<span class="role-badge admin">Admin</span>
<span class="role-badge member">Mitglied</span>

<!-- Zahl-Badge (für Menü-Zähler) -->
<span class="nav-badge">5</span>
```

**Farb-Schema der Badges:**

| Variante | Hintergrund | Textfarbe |
|---|---|---|
| `active` | `#d1fae5` | `#065f46` (Grün) |
| `inactive` | `#f1f5f9` | `#64748b` (Grau) |
| `admin` | `#fef3c7` | `#92400e` (Amber) |
| `member` | `#dbeafe` | `#1e40af` (Blau) |
| `danger/error` | `#fee2e2` | `#991b1b` (Rot) |

---

## 10. Tab-Navigation

### 10.1 URL-basierte Tabs (GET-Parameter)

Wird für Haupt-Seiten-Abschnitte verwendet (wie auf der Landing Page: `?section=header`).

```html
<nav class="lp-section-nav">
    <a href="?section=overview"
       class="lp-section-nav__item <?php echo $activeSection === 'overview' ? 'active' : ''; ?>">
        <span class="lp-section-nav__icon">📊</span> Übersicht
    </a>
    <a href="?section=settings"
       class="lp-section-nav__item <?php echo $activeSection === 'settings' ? 'active' : ''; ?>">
        <span class="lp-section-nav__icon">⚙️</span> Einstellungen
    </a>
</nav>
```

### 10.2 JavaScript-basierte Tabs (gleiche Seite)

```html
<div class="tabs">
    <button class="tab-btn active" onclick="switchTab('tab-general', this)">Allgemein</button>
    <button class="tab-btn" onclick="switchTab('tab-advanced', this)">Erweitert</button>
</div>

<div id="tab-general" class="tab-content active">...</div>
<div id="tab-advanced" class="tab-content">...</div>
```

```javascript
function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    btn.classList.add('active');
}
```

**Wann welche Variante:**
- URL-basierte Tabs (`lp-section-nav`): wenn jeder Tab eine eigene, verlinkbare URL haben soll
- JS-Tabs (`tab-btn`): wenn die Tabs innerhalb derselben Seite ohne Reload wechseln sollen

---

## 11. Modals

```html
<!-- Trigger -->
<button class="btn btn-primary" onclick="openModal('myModal')">➕ Neu erstellen</button>

<!-- Modal -->
<div id="myModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Neues Element</h3>
            <button class="modal-close" onclick="closeModal('myModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="modalForm">
                <!-- Formular-Felder -->
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('myModal')">Abbrechen</button>
            <button type="submit" form="modalForm" class="btn btn-primary">💾 Speichern</button>
        </div>
    </div>
</div>
```

```javascript
function openModal(id) {
    document.getElementById(id).style.display = 'flex';
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}
// Schließen per Klick außerhalb
window.addEventListener('click', function(e) {
    document.querySelectorAll('.modal').forEach(m => {
        if (e.target === m) closeModal(m.id);
    });
});
```

**Modal-Struktur-Regeln:**
- Max-Breite: `600px` (Standard), `900px` (große Formulare)
- `modal-header` enthält Titel + Schließen-Button
- `modal-body` enthält den Inhalt / das Formular
- `modal-footer` enthält Aktionsbuttons (Abbrechen links, Primäraktion rechts)
- Hintergrund: `rgba(0,0,0,0.7)`
- Animation: `modalSlideIn` (bereits in `admin.css` definiert)

---

## 12. Empty States

Wenn eine Liste oder Tabelle keine Einträge hat:

```html
<div class="empty-state">
    <p style="font-size:2.5rem;margin:0;">📭</p>
    <p><strong>Noch keine Einträge vorhanden</strong></p>
    <p class="text-muted">Erstelle deinen ersten Eintrag über den Button oben rechts.</p>
    <a href="?action=new" class="btn btn-primary" style="margin-top:1rem;">➕ Jetzt erstellen</a>
</div>
```

**Regeln:**
- Immer ein großes Emoji als visuelles Signal
- Mindestens eine erklärende Zeile (`text-muted`)
- Optional: direkter CTA-Button zum Erstellen
- **Niemals** Platzhalter-Daten oder Demo-Einträge anzeigen

---

## 13. Responsive Design

Breakpoints (aus `admin.css`):

| Breakpoint | Verhalten |
|---|---|
| `> 960px` | Standard-Layout: Sidebar fix links, Content mit `margin-left: 260px` |
| `≤ 960px` | Sidebar eingeklappt (Toggle via `.sidebar-open`), Content volle Breite |

**Pflicht-Responsive-Pattern:**
- Alle Grids nutzen `auto-fit` oder `auto-fill` mit `minmax()`
- Tabellen immer in `.users-table-container` (overflow-x: auto)
- `.header-actions` erlaubt `flex-wrap: wrap`
- Buttons in Actions-Bereichen werden auf mobil zu `width: 100%`

---

## 14. Typografie

| Element | Größe | Gewicht | Farbe |
|---|---|---|---|
| `h2` (Page Title) | `1.375rem` | 700 | `#1e293b` |
| `h3` (Card Title) | `1.1rem` | 700 | `#1e293b` |
| `h4` (Sub-Section) | `1rem` | 700 | `#1e293b` |
| Body-Text | `1rem` | 400 | `#1e293b` |
| Beschreibungstext | `0.85rem` | 400 | `#64748b` |
| Kleintext / Hints | `0.875rem` | 400 | `#64748b` |
| Sehr kleiner Text | `0.79–0.82rem` | 400–600 | `#94a3b8` |

**Schriftfamilie:** `-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif`

---

## 15. Icons & Emojis

- Emojis werden als primäre Icons verwendet – **kein** Icon-Font
- Emojis stehen **immer vor** dem Text: `✏️ Bearbeiten`, `🗑️ Löschen`, `💾 Speichern`
- Section-Navigationen (`lp-section-nav`) nutzen `.lp-section-nav__icon`-Wrapper
- Für Aktionsbuttons in Tabellen: nur Emoji ohne Text (z. B. `✏️` oder `🗑️`) bei Platzmangel

---

## 16. PHP-Patterns

### 16.1 POST-Handler (oben auf der Seite, vor dem HTML)

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_name'])) {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'meine_seite')) {
        $error = 'Sicherheitscheck fehlgeschlagen';
    } else {
        switch ($_POST['action_name']) {
            case 'save_item':
                $title = sanitize_text_field($_POST['title'] ?? '');
                if (empty($title)) {
                    $error = 'Titel ist erforderlich';
                    break;
                }
                // Verarbeitung...
                $success = 'Erfolgreich gespeichert';
                break;

            case 'delete_item':
                $id = (int)($_POST['item_id'] ?? 0);
                // Löschen...
                $success = 'Eintrag gelöscht';
                break;
        }
    }
}
```

### 16.2 AJAX-Antwortformat

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    try {
        // Verarbeitung
        echo json_encode(['success' => true, 'data' => $result]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
```

### 16.3 Leere Inhalte verbergen

```php
<?php if (!empty(trim($description))): ?>
    <div class="form-group">
        <p><?php echo nl2br(htmlspecialchars($description)); ?></p>
    </div>
<?php endif; ?>
```

**Niemals** leere Platzhaltertexte wie „Keine Beschreibung vorhanden" anzeigen – Abschnitt komplett ausblenden.

### 16.4 Paginierung

```php
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;
$total   = $service->getCount();
$pages   = (int)ceil($total / $perPage);
$items   = $service->getAll($offset, $perPage);
```

```html
<?php if ($pages > 1): ?>
<div class="pagination" style="display:flex;gap:.5rem;justify-content:center;margin-top:1.5rem;">
    <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary btn-sm">← Zurück</a>
    <?php endif; ?>
    <span style="padding:.375rem .875rem;color:#64748b;font-size:.875rem;">
        Seite <?php echo $page; ?> von <?php echo $pages; ?>
    </span>
    <?php if ($page < $pages): ?>
        <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary btn-sm">Weiter →</a>
    <?php endif; ?>
</div>
<?php endif; ?>
```

---

## 17. JavaScript-Konventionen

- Globale Admin-Helfer (Modal, Sidebar-Toggle) befinden sich in `assets/js/admin.js`
- Seitenspezifisches JS kommt als `<script>` am Ende der PHP-Datei (vor `</body>`)
- **Kein** jQuery – nur Vanilla JavaScript (ES2020+)
- AJAX-Calls via `fetch()`:

```javascript
async function saveData(formData) {
    try {
        const res = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showAlert('success', 'Gespeichert!');
        } else {
            showAlert('error', data.error ?? 'Unbekannter Fehler');
        }
    } catch (e) {
        showAlert('error', 'Netzwerkfehler: ' + e.message);
    }
}
```

- Bestätigungsdialoge **nicht** mit `window.confirm()` – stattdessen eigenes Modal
- Keine externen JS-Bibliotheken laden, außer bereits im Projekt vorhandenen (SunEditor für WYSIWYG)

---

## 18. Do's & Don'ts

### ✅ DO

- `.admin-card` für jeden inhaltlichen Abschnitt verwenden
- Alle Texte mit `htmlspecialchars()` escapen
- CSRF-Token in jedem Formular
- Emojis als Icons vor Texten
- `admin.css` über `link rel="stylesheet"` mit `?v=`-Parameter
- `declare(strict_types=1)` am Anfang jeder PHP-Datei
- Leere Abschnitte komplett ausblenden
- Responsive-Layouts via CSS-Grid mit `auto-fit`/`minmax`
- `btn btn-danger` für destruktive Aktionen mit Bestätigungs-Modal

### ❌ DON'T

- Inline-Styles für wiederkehrende Design-Eigenschaften
- `window.confirm()` für Bestätigungen
- jQuery einbinden
- Neue CSS-Dateien für Admin-Seiten erstellen (in `admin.css` ergänzen)
- Platzhaltertexte / Demo-Daten in leeren Zuständen
- `__return_true` oder fehlende Auth-Checks bei REST-Endpoints
- Generic class names wie `.card`, `.button`, `.grid` ohne Namespace
- `echo $_POST['...']` ohne vorherige Sanitierung
- Externe CDN-Links für CSS/JS (außer bereits vorhandene)

---

## 19. Datei-Namenskonvention (Admin-Seiten)

| Zweck | Dateiname |
|---|---|
| Neue Admin-Seite | `CMS/admin/mein-bereich.php` |
| Wiederverwendbares Admin-Partial | `CMS/admin/partials/mein-partial.php` |
| Helpers/Includes | `CMS/admin/includes/class-mein-helper.php` |
| Admin-spezifischer Service | `CMS/core/Services/MeinService.php` |

- Dateinamen: `kebab-case`
- Klassen: `PascalCase`
- Methoden: `camelCase`
- Konstanten: `UPPER_SNAKE_CASE`

---

## 20. Checkliste vor dem Commit (Admin-Seiten)

- [ ] `declare(strict_types=1)` vorhanden
- [ ] `Auth::instance()->isAdmin()` Check vorhanden
- [ ] CSRF-Token in jedem Formular / AJAX-Request
- [ ] Alle `$_POST`/`$_GET`-Werte sanitiert
- [ ] Alle HTML-Ausgaben per `htmlspecialchars()` escaped
- [ ] Leere Zustände via `.empty-state` abgedeckt
- [ ] Kein `window.confirm()` verwendet
- [ ] `admin.css` mit `?v=` Cache-Parameter
- [ ] `renderAdminSidebar()` und `renderAdminSidebarStyles()` aufgerufen
- [ ] Responsive getestet (≤ 960px)
