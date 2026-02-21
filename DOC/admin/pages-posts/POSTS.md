# Beiträge & Blog (Posts)

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026 | **Datei:** `admin/posts.php`

Verwaltung dynamischer Inhalte für den Blog- oder News-Bereich des 365CMS.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Beitrag erstellen](#2-beitrag-erstellen)
3. [Kategorien & Tags](#3-kategorien--tags)
4. [Veröffentlichungs-Workflow](#4-veröffentlichungs-workflow)
5. [Kommentare](#5-kommentare)
6. [Technische Details](#6-technische-details)

---

## 1. Überblick

URL: `/admin/posts.php`

Beiträge sind chronologische Inhalte (neueste zuerst). Sie sind über:
- **Blog-Archiv-URL** (`/blog/` oder konfigurierbar)
- **Kategorie-Archive** (`/kategorie/name/`)
- **Tag-Archive** (`/tag/name/`)
- **RSS-Feed** (`/feed.xml`)
- **Suche** (`/suche/?q=...`)

erreichbar.

---

## 2. Beitrag erstellen

### Pflichtfelder
| Feld | Beschreibung |
|---|---|
| **Titel** | Überschrift des Beitrags |
| **Inhalt** | Vollständiger Text (SunEditor WYSIWYG) |

### Optionale Felder
| Feld | Beschreibung |
|---|---|
| **Auszug** | Kurzbeschreibung (auto-generiert wenn leer: erste 55 Wörter) |
| **Beitragsbild** | Featured Image (erscheint in Listenansichten und Social Sharing) |
| **Kategorien** | 1 oder mehrere Kategorien (hierarchisch) |
| **Tags** | Schlagworte (flach, durch Komma getrennt eingeben) |
| **Autor** | Kann auf anderen Admin/Editor geändert werden |
| **Lesezeit** | Auto-berechnet (ca. 200 Wörter/Minute) |

### Status-Optionen

| Status | Beschreibung |
|---|---|
| `published` | Sofort öffentlich sichtbar |
| `draft` | Entwurf, nur Admin sichtbar |
| `private` | Nur für eingeloggte Mitglieder |
| `scheduled` | Automatisch veröffentlichen zu gesetztem Datum |
| `trash` | Gelöscht (30 Tage im Papierkorb) |

---

## 3. Kategorien & Tags

### Kategorien (hierarchisch)
- Strukturierung in Oberkategorien und Unterkategorien
- Beispiel: „IT-Sicherheit" → „Verschlüsselung", „Firewalls", „Zero-Day"
- Jeder Beitrag sollte mindestens einer Kategorie zugewiesen sein
- Standard-Kategorie: „Allgemein" (konfigurierbar in `admin/settings.php`)

### Tags (flach)
- Querschneidende Schlagwörter ohne Hierarchie
- Freitext-Eingabe mit Autocomplete aus bestehenden Tags
- Best Practice: 3–8 Tags pro Beitrag

### Kategorie-Verwaltung
Kategorien und Tags direkt aus dem Beitrags-Editor hinzufügen oder über:
- `Admin → Inhalte → Kategorien` für Massenverwaltung
- Umbenennung, Slug-Änderung, Beschreibungen

---

## 4. Veröffentlichungs-Workflow

### Zeitplanung
```
Beitrag verfassen → Status: "scheduled" wählen
→ Datum/Uhrzeit setzen → Speichern
→ CRON-Job veröffentlicht automatisch zum gesetzten Zeitpunkt
```

**CRON-Konfiguration:** Entweder Server-Cron oder CMS-interner CRON-Simulator

### Revisionen
- Automatische Speicherung alle 2 Minuten (Autosave)
- Verlauf aller manuellen Speicherungen
- Rückgabe zu beliebiger Revision
- Max. Revisionen pro Beitrag: 20 (konfigurierbar)

### Workflow für mehrere Autoren (geplant)
- `submitted` – Autor hat eingereicht, wartet auf Freigabe
- `approved` – Redakteur hat freigegeben für Veröffentlichung
- E-Mail-Benachrichtigung an Redakteur bei Einreichung

---

## 5. Kommentare

Kommentare pro Beitrag aktivierbar/deaktivierbar:

**Moderations-Einstellungen:**
| Einstellung | Beschreibung |
|---|---|
| `comments_open` | Kommentare für diesen Beitrag erlauben |
| `comment_status` | `open`, `closed`, `moderated` |
| `auto_approve` | Kommentare von bekannten Nutzern ohne Moderation |
| `blacklist_words` | Kommentare mit diesen Wörtern werden gehalten |

**Anti-Spam:** Honeypot-Feld und Rate-Limiting (max. 5 Kommentare/Stunde per IP)

---

## 6. Technische Details

**Controller:** `CMS\Admin\PostsController`

```php
// Beitrag speichern
$post = new CMS\Models\Post([
    'type'       => 'post',
    'title'      => sanitize_text($title),
    'slug'       => sanitize_slug($slug),
    'content'    => wp_kses_post($content),
    'excerpt'    => sanitize_text($excerpt),
    'status'     => $status,
    'author_id'  => $currentUserId,
    'categories' => $categoryIds,
    'tags'       => $tagNames,
]);
$postId = $post->save();
```

**RSS-Feed URL:** `/feed.xml` oder `/feed/rss`

**Hooks:**
```php
do_action('cms_post_published', $postId, $authorId);
do_action('cms_post_scheduled', $postId, $publishDate);
add_filter('cms_post_excerpt_length', fn() => 55);
add_filter('cms_post_excerpt_more', fn() => '…');
```

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
