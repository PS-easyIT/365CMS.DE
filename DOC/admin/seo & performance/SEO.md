# Search Engine Optimization (SEO)
**Datei:** `admin/seo.php`

Tools zur Optimierung der Sichtbarkeit in Suchmaschinen.

## Global Settings

### 1. Meta Tags
- **Titel-Format:** Definiert den Aufbau des `<title>` Tags (z.B. `%title% | %site_name%`).
- **Beschreibung:** Standard-Meta-Description, falls leer.
- **Keywords:** (Deprecated, aber unterstützt).

### 2. Indexierung
- `noindex` für bestimmte Seitentypen oder Archive setzen.
- `robots.txt` Editor für Crawler-Steuerung.

### 3. Sitemap
- Generierung einer XML-Sitemap (`sitemap.xml`).
- Automatische Aktualisierung bei neuen Inhalten.
- Prioritäten-Steuerung (0.1 - 1.0).
