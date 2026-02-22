# Performance-Optimierung


Tools zur Optimierung der Ladezeiten und effizienteren Nutzung der Server-Ressourcen im 365CMS.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Page Cache](#2-page-cache)
3. [Object Cache](#3-object-cache)
4. [Browser Cache](#4-browser-cache)
5. [Asset-Optimierung](#5-asset-optimierung)
6. [Datenbank-Optimierung](#6-datenbank-optimierung)
7. [Bild-Optimierung](#7-bild-optimierung)
8. [Technische Details](#8-technische-details)

---

## 1. Überblick

URL: `/admin/performance.php`

**Empfohlene Performance-Ziele:**
- Time to First Byte (TTFB): < 200 ms
- Largest Contentful Paint (LCP): < 2,5 s
- Lighthouse Performance-Score: > 90

---

## 2. Page Cache

Der Page Cache speichert vollständig gerenderte HTML-Seiten und liefert sie direkt ohne PHP/Datenbank-Verarbeitung aus.

**Einstellungen:**

| Einstellung | Standard | Beschreibung |
|---|---|---|
| `page_cache_enabled` | `false` | Page Cache aktivieren |
| `page_cache_ttl` | `3600` | Cache-Lebensdauer in Sekunden |
| `cache_logged_in_users` | `false` | Eingeloggte Nutzer auch cachen |
| `exclude_urls` | `/admin/, /member/` | Seiten vom Cache ausschließen |
| `auto_clear_on_save` | `true` | Cache bei Inhaltsänderungen leeren |

**Cache leeren:**
- **Alles leeren:** Entfernt alle gecachten Seiten
- **Einzelne Seite:** URL eingeben → nur diese Seite wird geleert
- **Automatisch:** Bei Veröffentlichung/Bearbeitung von Inhalten

---

## 3. Object Cache

Speichert Datenbank-Ergebnisse, teure Berechnungen und API-Antworten im Arbeitsspeicher.

**Unterstützte Backends:**

| Backend | Installation | Eignung |
|---|---|---|
| **Filesystem** | Standard (immer verfügbar) | Shared Hosting |
| **Redis** | `pecl install redis` | VPS/Dedicated |
| **Memcached** | `pecl install memcached` | VPS/Dedicated |
| **APCu** | `pecl install apcu` | Entwicklung |

**Konfiguration (config.php):**
```php
define('CACHE_DRIVER', 'redis');
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);
define('REDIS_PREFIX', 'cms_');
```

---

## 4. Browser Cache

Optimale HTTP-Header für statische Assets:

```
Cache-Control: public, max-age=31536000, immutable  # 1 Jahr für Assets mit Hash
Cache-Control: public, max-age=86400                 # 1 Tag für Bilder
ETag: "abc123"                                        # Validierung
Vary: Accept-Encoding                                 # Gzip-Varianten
```

**Assets mit Versionshash:** CSS/JS-Dateien erhalten automatischen Query-String (`?v=abc123`) bei Änderungen.

---

## 5. Asset-Optimierung

### CSS/JS Minification

| Option | Beschreibung |
|---|---|
| `minify_css` | Entfernt Whitespace und Kommentare aus CSS |
| `minify_js` | Entfernt Whitespace und Kommentare aus JS |
| `combine_css` | Mehrere CSS-Dateien zu einer zusammenfassen |
| `combine_js` | Mehrere JS-Dateien zu einer zusammenfassen |
| `defer_js` | `defer`-Attribut für nicht-kritisches JS |

**Ausnahmen definieren:**
Bestimmte Dateien vom Minify/Combine ausschließen (z.B. für Plugins die spezifische Ladereihenfolge benötigen):
```
# Ausnahmen (eine URL pro Zeile)
/assets/js/plugins/chart.min.js
/assets/js/plugins/datepicker.js
```

### Lazy Loading

- **Bilder:** `loading="lazy"` automatisch auf alle Nicht-Above-the-Fold-Bilder
- **iFrames:** `loading="lazy"` für eingebettete Inhalte
- **Videos:** Poster-Bild bis Klick, dann Video laden

---

## 6. Datenbank-Optimierung

### Datenbank-Cleaner

| Aktion | Beschreibung |
|---|---|
| **Revisionen bereinigen** | Beitrags-Revisionen über Limit löschen |
| **Spam-Kommentare** | Alle als Spam markierten Kommentare löschen |
| **Transients** | Abgelaufene temporäre Datenbankeinträge löschen |
| **Tabellen optimieren** | `OPTIMIZE TABLE` für alle CMS-Tabellen ausführen |
| **Orphan-Meta** | Verwaiste post_meta- und user_meta-Einträge löschen |

**Zeitplan:** Wöchentliche automatische Bereinigung empfohlen.

### Slow-Query-Log
Bei aktiviertem `WP_DEBUG`: Queries > 0,1 Sekunden werden in `/logs/slow-queries.log` protokolliert.

---

## 7. Bild-Optimierung

| Option | Beschreibung |
|---|---|
| `webp_convert` | Automatische WebP-Versionen bei Upload erstellen |
| `optimize_on_upload` | Bilder bei Upload komprimieren (verlustfrei) |
| `strip_exif` | EXIF-Daten (GPS, Kamera-Info) aus Bildern entfernen |
| `max_upload_width` | Max. Breite bei Upload (Auto-Resize, z.B. 2400 px) |
| `jpeg_quality` | JPEG-Qualitätsstufe (1–100, Standard: 85) |

---

## 8. Technische Details

**Service:** `CMS\Services\CacheService`

```php
$cache = CacheService::instance();

// Value cachen
$cache->set('user_count', $count, 3600); // 1 Stunde

// Cached Value lesen
$count = $cache->get('user_count');

// Cache leeren
$cache->flush();                    // Alles
$cache->delete('user_count');       // Einzelner Key
$cache->flushGroup('posts');        // Gruppe

// Page Cache leeren
$cache->clearPageCache('/blog/');   // Spezifische URL
$cache->clearAllPageCache();        // Alle Seiten
```

**Hooks:**
```php
do_action('cms_cache_cleared', $type, $target);
add_filter('cms_cache_exclude_urls', 'my_exclude_urls');
add_filter('cms_cache_ttl', fn($ttl, $url) => 7200, 10, 2);
```

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
