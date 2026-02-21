# Seitenverwaltung (Pages)

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026 | **Datei:** `admin/pages.php`

Verwaltung statischer Inhaltsseiten wie „Über uns", „Impressum", „Kontakt" oder Landing Pages.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Seite erstellen](#2-seite-erstellen)
3. [Seitenoptionen](#3-seitenoptionen)
4. [Seitenhierarchie](#4-seitenhierarchie)
5. [Revisionen](#5-revisionen)
6. [Technische Details](#6-technische-details)

---

## 1. Überblick

URL: `/admin/pages.php`

Seiten unterscheiden sich von Beiträgen:
- **Seiten:** Statische Inhalte, keine Kategorien/Tags, hierarchisch strukturierbar
- **Beiträge:** Dynamische Inhalte (Blog/News), chronologisch, mit Kategorien/Tags

Seiten erscheinen nicht im RSS-Feed und sind typischerweise über das Menü erreichbar, nicht über Archive.

---

## 2. Seite erstellen

### Schritt 1: Grunddaten
| Feld | Pflicht | Beschreibung |
|---|---|---|
| **Titel** | Ja | Anzeigename der Seite, erscheint als `<h1>` und `<title>` |
| **Slug** | Ja | URL-Pfad (auto-generiert aus Titel, z.B. `ueber-uns`) |
| **Inhalt** | Nein | Vollständiger Seiteninhalt (SunEditor WYSIWYG) |
| **Auszug** | Nein | Kurzbeschreibung für Meta-Description und Cards |

### Schritt 2: Status wählen

| Status | Sichtbarkeit | Beschreibung |
|---|---|---|
| `published` | Öffentlich | Sofort auf der Website sichtbar |
| `draft` | Nur Admin | In Bearbeitung, nicht veröffentlicht |
| `private` | Nur eingeloggt | Für registrierte Mitglieder |
| `scheduled` | Zu Datum | Automatisch veröffentlichen (Datum setzen) |

### Schritt 3: Weitere Optionen
- **Bild** (Featured Image): Wird in Teaser-Cards und Social-Sharing verwendet
- **Sichtbarkeit** des Titels: `show_title = false` versteckt die H1-Überschrift auf der Seite
- **Inhaltsverzeichnis:** Auto-Generierung aus H2/H3-Tags (aktivierbar)

---

## 3. Seitenoptionen

### Template-Auswahl
Jedes Theme kann mehrere Seiten-Templates anbieten:

| Template | Beschreibung |
|---|---|
| `default` | Standard mit Sidebar (wenn im Theme konfiguriert) |
| `full-width` | Volle Breite, kein Content-Container |
| `landing` | Spezial-Layout ohne Header-Navigation |
| `no-sidebar` | Vollbreite-Inhalt ohne Seitenleiste |

Template wird via `page_template` Meta-Feld gespeichert.

### SEO-Einstellungen (pro Seite)
Überschreiben die globalen SEO-Einstellungen (aus `admin/seo.php`):

| Feld | Key | Beschreibung |
|---|---|---|
| Meta-Titel | `seo_title` | Individueller `<title>` (max. 60 Zeichen) |
| Meta-Description | `seo_description` | Custom Meta-Description (max. 160 Zeichen) |
| Robots | `seo_robots` | `index,follow` oder `noindex` |
| OG-Bild | `seo_og_image` | Social-Sharing-Bild (min. 1200×630 px) |

### Passwortschutz (optional)
- Seite kann mit einem Passwort geschützt werden
- Passwort-Eingabe erscheint dem Besucher vor dem Inhalt
- Passwort wird gehasht in `cms_post_meta` gespeichert

---

## 4. Seitenhierarchie

Seiten können verschachtelt werden (bis 5 Ebenen tief):

```
/ (Root)
├── über-uns           (Elternseite)
│   ├── team           (Kindseite)
│   └── geschichte     (Kindseite)
├── leistungen
│   ├── consulting
│   └── entwicklung
└── kontakt
```

**Elternseite zuweisen:**
- Dropdown „Elternseite" in den Seitenoptionen
- URL wird automatisch verschachtelt: `/ueber-uns/team/`

**Sortierung:** Numerisches Feld `menu_order` (Standard: 0, niedrigere Zahl = höhere Position)

---

## 5. Revisionen

Alle Speicherungen werden als Revisionen archiviert:
- Maximal 10 Revisionen pro Seite (konfigurierbar)
- Rückgabe zu jeder gespeicherten Version möglich
- Diff-Ansicht: Zeigt Unterschiede zwischen zwei Revisionen
- Älteste Revisionen werden automatisch gelöscht (FIFO)

---

## 6. Technische Details

**Controller:** `CMS\Admin\PagesController`

```php
// Seite speichern
$page = new CMS\Models\Post([
    'type'        => 'page',
    'title'       => sanitize_text($title),
    'slug'        => sanitize_slug($slug),
    'content'     => wp_kses_post($content),
    'status'      => $status,
    'author_id'   => $currentUserId,
    'parent_id'   => $parentId,
    'template'    => $template,
    'menu_order'  => $menuOrder,
]);
$pageId = $page->save();
```

**Datenbank:** Tabelle `cms_posts` mit Feldern:
`id, type, title, slug, content, excerpt, status, author_id, parent_id, template, menu_order, created_at, updated_at, published_at`

**Hooks:**
```php
do_action('cms_page_saved', $pageId, $pageData, $isNew);
do_action('cms_page_deleted', $pageId);
add_filter('cms_page_slug', 'my_slug_modifier', 10, 2);
```

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
