# Menü-Verwaltung

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026 | **Datei:** `admin/menus.php`

Visual-Editor für die Navigationsstrukturen der 365CMS Website.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Menü erstellen](#2-menü-erstellen)
3. [Menüpunkte hinzufügen](#3-menüpunkte-hinzufügen)
4. [Struktur & Verschachtelung](#4-struktur--verschachtelung)
5. [Menü-Positionen](#5-menü-positionen)
6. [Erweiterte Optionen](#6-erweiterte-optionen)
7. [Technische Details](#7-technische-details)

---

## 1. Überblick

URL: `/admin/menus.php`

365CMS unterstützt beliebig viele Menüs, die verschiedenen Theme-Positionen (Header, Footer, Sidebar) zugewiesen werden können.

---

## 2. Menü erstellen

1. Menüname eingeben (nur intern, z.B. „Hauptnavigation")
2. **„Menü erstellen"** klicken
3. Das neue Menü erscheint im Menü-Editor

**Mehrere Menüs:** Für jede Theme-Position kann ein eigenes Menü erstellt werden.

---

## 3. Menüpunkte hinzufügen

### Seiten (Pages)
- Liste aller veröffentlichten Seiten mit Checkboxen
- Mehrfachauswahl möglich
- „Alle auswählen"-Button

### Beiträge (Posts)
- Suche nach Beitragstitel
- Einzelne Beiträge auswählen

### Kategorien
- Liste aller Kategorien
- Kategorie-Archiv-Link wird hinzugefügt

### Tags
- Wie Kategorien, für Tag-Archive

### Individuelle Links (Custom Links)
- **URL:** Beliebige URL (intern oder extern)
- **Text:** Anzeigetext des Menüpunkts

### Plugin-Inhalte
Installierte Plugins können eigene Menüpunkt-Typen anbieten:
- `cms-experts`: Experten-Verzeichnis
- `cms-events`: Veranstaltungskalender
- `cms-jobads`: Stellenbörse

---

## 4. Struktur & Verschachtelung

### Drag & Drop Editor

```
├── Startseite          (Ebene 1)
├── Über uns            (Ebene 1)
│   ├── Team            (Ebene 2 – Dropdown-Item)
│   └── Geschichte      (Ebene 2 – Dropdown-Item)
├── Leistungen          (Ebene 1)
│   ├── Consulting      (Ebene 2)
│   └── Entwicklung     (Ebene 2)
└── Kontakt             (Ebene 1)
```

**Verschachtelungstiefe:** Maximum empfohlen: 3 Ebenen (tiefere Ebenen werden von vielen Themes nicht unterstützt)

### Menüpunkt-Optionen (Einzelbearbeitung)
| Feld | Beschreibung |
|---|---|
| **Navigations-Label** | Angezeigter Text (überschreibt Seitentitel) |
| **Titel-Attribut** | Tooltip beim Hover (SEO-neutral, Barrierefreiheit) |
| **URL** | Ziel-URL (auto-befüllt, editierbar) |
| **CSS-Klassen** | Eigene CSS-Klassen hinzufügen (z.B. `btn-primary`) |
| **Rel-Attribut** | Link-Relation (z.B. `noopener noreferrer` für externe Links) |
| **In neuem Tab** | `target="_blank"` setzen (Checkbox) |

---

## 5. Menü-Positionen

Jedes aktive Theme registriert seine verfügbaren Positionen:

| Position | Slug | Typische Verwendung |
|---|---|---|
| **Header** | `primary` | Hauptnavigation oben |
| **Footer** | `footer` | Links im Footer |
| **Sidebar** | `sidebar` | Seitenleisten-Navigation |
| **Mobile** | `mobile` | Separate mobile Navigation |
| **Footer Links** | `footer_legal` | Impressum, Datenschutz |

**Zuweisung:** Checkboxen am Ende des Menü-Editors → Menü einer Position zuweisen.

---

## 6. Erweiterte Optionen

### Automatisch hinzufügen
- Neue Seiten der höchsten Ebene automatisch zu einem Menü hinzufügen (Checkbox in Menü-Einstellungen)
- Nützlich für Websites mit häufig neu angelegten Hauptseiten

### Menü-Icons
Wenn das aktive Theme Icons unterstützt, kann pro Menüpunkt ein Dashicon/FontAwesome-Icon zugewiesen werden.

### Rollenbasierte Sichtbarkeit (via Hooks)
```php
// Menüpunkte für eingeloggte Benutzer ausblenden/einblenden
add_filter('cms_menu_item_visible', function($visible, $menuItem, $user) {
    if ($menuItem->slug === 'member' && !$user->isLoggedIn()) {
        return false;
    }
    return $visible;
}, 10, 3);
```

---

## 7. Technische Details

**Datenbank:**
- Menüs gespeichert in `cms_menus` (Name, Slug)
- Menüpunkte in `cms_menu_items` (Menu-ID, Parent-ID, Order, Typ, URL, Label)

```php
// Menü im Template ausgeben
echo CMS\Nav::render('primary', [
    'depth'       => 3,
    'ul_class'    => 'nav-menu',
    'li_class'    => 'nav-item',
    'a_class'     => 'nav-link',
    'active_class'=> 'active',
]);
```

**Hooks:**
```php
do_action('cms_menu_saved', $menuId, $menuItems);
add_filter('cms_menu_item_classes', 'my_menu_classes', 10, 3);
add_filter('cms_menu_item_visible', 'role_based_visibility', 10, 3);
```

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
