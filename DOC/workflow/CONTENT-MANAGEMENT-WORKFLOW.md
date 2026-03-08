# Content-Management Workflow – 365CMS

> **Stand:** 2026-03-08 | **Version:** 2.5.4 | **Status:** Aktuell
>
> **Bereich:** Inhalte · **Version:** 2.5.4  
> **Services:** `EditorService`, `SEOService`, `MediaService`  
> **Admin-Seiten:** `admin/editor.php`, `admin/pages.php`, `admin/landing-page.php`

---
<!-- UPDATED: 2026-03-08 -->

## Übersicht: Content-Typen

| Typ | Admin-Seite | Service | Besonderheiten |
|---|---|---|---|
| **Seite** | `admin/pages.php` | EditorService | Statische Inhalte, Hierarchie |
| **Beitrag** | `admin/posts.php` | EditorService | Kategorien, Tags, Datum |
| **Landing Page** | `admin/landing-page.php` | LandingPageService | Sektionen, A/B-Tests |
| **Plugin-Inhalte** | Plugin-spezifisch | Plugin-Service | Vom jeweiligen Plugin |
| **Medien** | `admin/media.php` | MediaService | Upload, Bibliothek |

---

## Workflow 1: Seite erstellen / bearbeiten

### Via Admin-UI

1. Admin → `admin/pages.php` → "Neue Seite erstellen"
2. **Titel** eingeben – wird automatisch zu Slug umgewandelt
3. **Inhalt** via SunEditor (WYSIWYG): 
   - Formatierung: H2, H3, Fettschrift, Listen
   - Bilder einfügen: Aus Medien-Bibliothek
   - Keine direkten HTML-Skripte einfügen
4. **SEO-Felder** ausfüllen:
   - Meta-Title (55–65 Zeichen)
   - Meta-Description (150–160 Zeichen)
   - Canonical-URL (falls abweichend)
5. **Status** wählen: `Entwurf → Veröffentlicht`
6. Speichern → Vorschau öffnen

### Content-Validation (programmatisch)

```php
// EditorService::savePage() intern:
$title    = sanitize_text_field($_POST['title']);
$slug     = sanitize_title(strtolower($title)); // z.B. "ueber-uns"
$content  = wp_kses_post($_POST['content']);     // Erlaubt sicheres HTML
$metaDesc = sanitize_text_field($_POST['meta_description']);

// Duplikat-Slug prüfen:
if ($this->slugExists($slug, $excludeId)) {
    $slug .= '-' . date('YmdHis');
}
```

---

## Workflow 2: Landing Page erstellen

Landing Pages bestehen aus konfigurierbaren Sektionen.

### Verfügbare Sektionen (LandingPageService)

| Sektion | Konfigurierbar | Beschreibung |
|---|---|---|
| `hero` | Titel, Text, CTA, Hintergrundbild | Eingangsbanner |
| `features` | 3–6 Feature-Cards | Vorteils-Übersicht |
| `testimonials` | Kundenstimmen | Beweis-Sektion |
| `pricing` | Preistabelle | Angebote |
| `cta` | Call-to-Action | Konversions-Banner |
| `contact` | Kontaktformular | Lead-Generierung |

### Admin-Workflow

```
Admin → admin/landing-page.php → URL auswählen (Reiter oben)
↓
Tab "Header" → Logo, Nav-Links konfigurieren
Tab "Hero" → Headline, Subheadline, Button-Text, -URL
Tab "Features" → Features-Grid befüllen
Tab "Testimonials" → Bewertungen hinzufügen
Tab "Footer" → Links, Copyright-Text
↓
"Speichern" → Cache wird automatisch gelöscht
```

---

## Workflow 3: Inhalte mit SEO optimieren

```php
$seo = \CMS\Services\SEOService::instance();

// Meta-Tags setzen (via Hook im Template):
\CMS\Hooks::addFilter('page_meta_tags', function(array $meta, int $pageId) use ($seo) {
    $data = $seo->getMetaData($pageId);
    
    $meta[] = '<title>' . esc_html($data['title']) . '</title>';
    $meta[] = '<meta name="description" content="' . esc_attr($data['description']) . '">';
    $meta[] = '<link rel="canonical" href="' . esc_url($data['canonical']) . '">';
    
    // Open Graph:
    $meta[] = '<meta property="og:title" content="' . esc_attr($data['og_title'] ?: $data['title']) . '">';
    $meta[] = '<meta property="og:description" content="' . esc_attr($data['description']) . '">';
    $meta[] = '<meta property="og:image" content="' . esc_url($data['og_image']) . '">';
    
    return $meta;
}, 10, 2);
```

---

## Workflow 4: Inhalte veröffentlichen / Entwurf

**Status-Flow:**
```
Neu erstellen
     ↓
  [Entwurf]  ← Bearbeitbar, nicht öffentlich
     ↓
 [Vorschau]  ← Direktlink mit Token (nur für Admin)
     ↓
[Veröffentlicht] ← Öffentlich erreichbar
     ↓
[Archiviert]  ← Nicht mehr öffentlich, aber erhalten
```

**Status-Änderung:**
```php
$editor = \CMS\Services\EditorService::instance();
$editor->updateStatus($pageId, 'published');

// Hook wird gefeuert:
// Hooks::doAction('content_published', $pageId, $type)
// → Cache-Invalidierung
// → SEO-Sitemap-Update
// → ggf. Social-Share-Trigger
```

---

## Workflow 5: Revision / Versionsverlauf

```php
// Beim Speichern wird automatisch eine Revision erstellt:
// → Tabelle cms_content_revisions (geplant via M-08)

// Revision wiederherstellen:
$editor->restoreRevision($pageId, $revisionId);
```

**Status:** Revisions-System ist als Roadmap-Item M-08 geplant.

---

## Workflow 6: Cache-Verwaltung

Inhalte werden gecacht in `/cms/cache/`.

**Cache wird automatisch invalidiert bei:**
- Seite/Beitrag speichern
- Status-Änderung
- Theme-Customizer speichern

**Manueller Cache-Clear:**
```
Admin → admin/settings.php → "Cache leeren"
```

```php
// Programmatisch:
\CMS\CacheManager::instance()->delete('page_' . $pageId);
\CMS\CacheManager::instance()->deleteByPrefix('sitemap_');
```

---

## SEO-Checkliste (vor Veröffentlichung)

```
PFLICHTFELDER:
[ ] Meta-Title: 55–65 Zeichen
[ ] Meta-Description: 150–160 Zeichen (kein Keyword-Stuffing)
[ ] H1 im Inhalt vorhanden (genau einmal)
[ ] Bild-ALT-Texte: Alle Bilder mit aussagekräftigem Alt-Text

EMPFOHLEN:
[ ] Canonical-URL gesetzt (Standard: aktuelle URL)
[ ] Open-Graph-Bild: 1200×630px
[ ] Interne Verlinkung: Mindestens 1 relevanter interner Link
[ ] Mobil-Ansicht getestet

TECHNISCH:
[ ] URL / Slug ist sprechend (kein /page-1234/)
[ ] Keine doppelten Inhalte (Canonical korrekt?)
[ ] Sitemap aktualisiert nach Veröffentlichung
```

---

## Referenzen

- [admin/editor.php](../../CMS/admin/editor.php) – WYSIWYG-Editor
- [admin/pages.php](../../CMS/admin/pages.php) – Seitenverwaltung
- [core/Services/EditorService.php](../../CMS/core/Services/EditorService.php) – Editor-Service
- [core/Services/SEOService.php](../../CMS/core/Services/SEOService.php) – SEO-Service
- [FEATURE-AUDIT.md](../audits/FEATURE-AUDIT.md) – Content-Feature-Anforderungen
