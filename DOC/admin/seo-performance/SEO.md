# Suchmaschinenoptimierung (SEO)


Globale SEO-Einstellungen und Tools zur Optimierung der Sichtbarkeit in Suchmaschinen.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Meta-Tags (Global)](#2-meta-tags-global)
3. [Indexierungssteuerung](#3-indexierungssteuerung)
4. [XML-Sitemap](#4-xml-sitemap)
5. [Robots.txt](#5-robotstxt)
6. [Open Graph & Social Sharing](#6-open-graph--social-sharing)
7. [Strukturierte Daten (Schema.org)](#7-strukturierte-daten-schemaorg)
8. [Technische Details](#8-technische-details)

---

## 1. Überblick

URL: `/admin/seo.php`

Die globalen SEO-Einstellungen gelten für alle Seiten und Beiträge. Einzelne Seiten/Beiträge können diese Werte überschreiben (Einstellung direkt im Seiten-/Beitrags-Editor).

**Priorität der Einstellungen:** Seite/Beitrag > Kategorie > Global

---

## 2. Meta-Tags (Global)

### Titel-Format

Das Titel-Format definiert den `<title>` Tag für alle Seiten:

| Platzhalter | Wert |
|---|---|
| `%title%` | Titel der Seite/des Beitrags |
| `%site_name%` | Website-Name aus den allgemeinen Einstellungen |
| `%page%` | Seitenzahl bei paginierten Archiven |
| `%sep%` | Trennzeichen (Standard: ` | `) |

**Beispiele:**

| Seitentyp | Format | Ergebnis |
|---|---|---|
| Einzelseite | `%title% %sep% %site_name%` | „Über uns \| MeineCMS" |
| Blog-Archiv | `Blog %sep% %site_name%` | „Blog \| MeineCMS" |
| Kategorie | `%category% %sep% %site_name%` | „IT-Sicherheit \| MeineCMS" |
| Startseite | `%site_name% – %tagline%` | „MeineCMS – Ihr Partner" |

### Standard-Meta-Description
- Wird verwendet wenn keine individuelle Description gesetzt ist
- Max. 160 Zeichen
- Platzhalter `%excerpt%` für automatischen Auszug

### Keywords
- Legacy-Feld (wird von Google nicht mehr bewertet)
- Kann für interne Zwecke oder andere Suchmaschinen noch verwendet werden

---

## 3. Indexierungssteuerung

### Seitentypen-spezifische `noindex`-Einstellungen:

| Seitentyp | Standard | Empfehlung |
|---|---|---|
| Einzelne Seiten | index | ✅ index |
| Einzelne Beiträge | index | ✅ index |
| Tag-Archive | noindex | ✅ noindex |
| Autoren-Archiv | noindex | ✅ noindex |
| Suche-Seite | noindex | ✅ noindex |
| Login/Register | noindex | ✅ noindex |
| Admin-Bereich | noindex | ✅ noindex |
| 404-Seite | noindex | ✅ noindex |

**Canonical URLs:**
- Automatische `<link rel="canonical">` für alle öffentlichen Seiten
- Verhindert Duplicate Content (z.B. bei paginierten Archiven)

---

## 4. XML-Sitemap

**Sitemap-URL:** `/sitemap.xml`

**Enthält standardmäßig:**
- Alle veröffentlichten Seiten
- Alle veröffentlichten Beiträge
- Kategorie-Archive (optional)
- Tag-Archive (optional)

**Konfigurierbar:**
| Einstellung | Beschreibung |
|---|---|
| Seiten einschließen | ✅ Standard |
| Beiträge einschließen | ✅ Standard |
| Bilder einschließen | ✅ Standard |
| Änderungsfrequenz | `always`, `hourly`, `daily`, `weekly`, `monthly`, `never` |
| Priorität | 0.1 bis 1.0 (Standard: 0.5, Startseite: 1.0) |
| Max. URLs pro Sitemap | 50.000 (bei mehr: Split in Teil-Sitemaps) |

**Automatische Aktualisierung:** Sitemap wird bei jeder Veröffentlichung/Änderung neu generiert und gecacht.

**Google Search Console:** Sitemap-URL für schnellere Indexierung eintragen.

---

## 5. Robots.txt

Editor für `/robots.txt` direkt im Admin:

```
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /member/
Disallow: /install.php
Disallow: /config.php
Disallow: /cache/

Sitemap: https://www.meine-website.de/sitemap.xml
```

**⚠️ Achtung:** Fehler in `robots.txt` können die gesamte Website aus dem Google-Index ausschließen!

---

## 6. Open Graph & Social Sharing

Konfiguration der Social-Media-Vorschau-Cards:

| Typ | Plattformen | Keys |
|---|---|---|
| **Open Graph** | Facebook, LinkedIn, Discord | `og:title`, `og:description`, `og:image`, `og:type` |
| **Twitter Cards** | Twitter/X | `twitter:card`, `twitter:title`, `twitter:image` |

**Empfohlene OG-Bild-Größe:** 1200×630 px, max. 8 MB  
**Twitter Card Typ:** `summary_large_image` (Großbild-Vorschau)

**Standard-OG-Bild:** Wird verwendet wenn kein seitenspezifisches Bild gesetzt ist.

---

## 7. Strukturierte Daten (Schema.org)

Automatisch generierte JSON-LD Snippets:

| Schema-Typ | Für |
|---|---|
| `WebSite` | Startseite (mit Sitelinks-Suchbox) |
| `BlogPosting` | Blog-Beiträge |
| `WebPage` | Statische Seiten |
| `Organization` | Über-uns-Seite (wenn konfiguriert) |
| `BreadcrumbList` | Breadcrumb-Navigation |

Zusätzliche Schemas können via Plugin oder Hook hinzugefügt werden.

---

## 8. Technische Details

**Service:** `CMS\Services\SEOService`

```php
// Meta-Tags für eine Seite rendern
$seo = SEOService::instance();

echo $seo->renderMetaTags($postId);
// Gibt <title>, <meta description>, <link canonical>, OG-Tags aus

// Sitemap generieren
$seo->generateSitemap();
// Schreibt /sitemap.xml und invalidiert Cache

// Strukturierte Daten
echo $seo->renderStructuredData($postId);
// Gibt JSON-LD <script> Block aus
```

**Hooks:**
```php
add_filter('cms_seo_title', 'my_title_modifier', 10, 2);
add_filter('cms_seo_meta_tags', 'my_meta_tags', 10, 2);
add_filter('cms_seo_structured_data', 'my_schema_extend', 10, 2);
do_action('cms_sitemap_generated', $sitemapPath);
```

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
