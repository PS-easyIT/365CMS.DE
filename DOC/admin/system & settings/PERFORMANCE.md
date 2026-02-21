# Performance Tuning
**Datei:** `admin/performance.php`

Optimierung der Ladezeiten und Server-Ressourcen.

## Caching

### 1. Page Cache
- Speichert fertig gerenderte HTML-Seiten.
- Umgeht PHP- und Datenbank-Verarbeitung für nachfolgende Aufrufe.
- **TTL:** Time To Live (Gültigkeitsdauer).
- Auto-Clear bei Inhaltsänderungen.

### 2. Object Cache
- Speichert Datenbank-Ergebnisse und teure Berechnungen.
- Unterstützt: Filesystem (Standard), Redis, Memcached.

### 3. Browser Cache
- Setzt optimale HTTP-Header (`Cache-Control`, `Expires`) für statische Assets (CSS, JS, Bilder).

## Optimierungen

- **CSS/JS Minify:** Komprimiert Quellcode (entfernt Leerzeichen/Kommentare).
- **Lazy Loading:** Lädt Bilder erst, wenn sie in den sichtbaren Bereich scrollen.
- **Database Cleaner:** Entfernt Revisionen, Spam-Kommentare und Transients.
