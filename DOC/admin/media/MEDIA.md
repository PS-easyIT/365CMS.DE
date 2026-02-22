# Medien-Bibliothek

**Datei:** `admin/media.php`

---

## Übersicht

Die Medien-Bibliothek verwaltet alle hochgeladenen Dateien des CMS. Sie unterstützt Bilder, Dokumente und Videos mit vollständiger CRUD-Funktionalität, Suche, Filterung und einem Media-Proxy-System für sichere Auslieferung.

---

## Ansichten

### Grid-Ansicht
- Visuelles Kachel-Layout mit Thumbnail-Vorschau
- Hover zeigt Dateiname und Aktions-Icons
- Ideal für Bilder-Bibliotheken

### Listen-Ansicht
- Tabellarische Darstellung mit allen Metadaten
- Sortierbar nach Name, Datum, Größe, Typ
- Ideal für Dokument-Bibliotheken

---

## Upload

### Drag-&-Drop Upload
- Dateien direkt in den Upload-Bereich ziehen
- Mehrfach-Upload möglich (bis zu 10 Dateien gleichzeitig)
- Fortschrittsbalken während des Uploads

### Erlaubte Dateitypen (konfigurierbar)

| Kategorie | Typen |
|-----------|-------|
| **Bilder** | JPG, JPEG, PNG, GIF, WebP, SVG |
| **Dokumente** | PDF, DOC, DOCX, XLS, XLSX, PPT |
| **Videos** | MP4, WebM, OGG |
| **Audio** | MP3, WAV, OGG |
| **Archive** | ZIP, RAR, 7Z |

### Größenlimits
- Standard: 10 MB pro Datei
- Konfigurierbar in `admin/settings.php`
- PHP-Limits (`upload_max_filesize`, `post_max_size`) beachten

---

## Datei-Verwaltung

### Metadaten bearbeiten
- **Dateiname** – Umbenennen ohne Pfad-Änderung
- **Alt-Text** – Wichtig für Barrierefreiheit und SEO
- **Beschreibung** – Optionale interne Beschreibung
- **Kategorie** – Eigene Medienkategorien zuweisen

### Thumbnail-Generierung
Für hochgeladene Bilder werden automatisch generiert:
- `thumb_` – 150×150px (Quadrat, beschnitten)
- `medium_` – 800px Breite (proportional)
- Original – unverändert

---

## Media-Proxy

Der Media-Proxy (`media-proxy.php`) liefert Dateien sicher aus:

```
GET /media-proxy.php?file=uploads/2026/02/bild.jpg
```

**Vorteile:**
- Zugriffsschutz für nicht-öffentliche Uploads
- Automatisches Logging von Media-Zugriffen
- Cache-Control Header werden gesetzt
- MIME-Typ-Validierung vor Auslieferung

---

## Suche & Filter

| Filter | Optionen |
|--------|---------|
| **Dateityp** | Alle, Bild, Dokument, Video, Audio |
| **Upload-Datum** | Heute, Diese Woche, Dieser Monat, Benutzerdefiniert |
| **Uploader** | Alle Benutzer oder bestimmter Benutzer |
| **Suche** | Volltextsuche in Dateiname und Alt-Text |

---

## Statistiken

Oben auf der Seite werden angezeigt:
- **Gesamtgröße** – Gesamter Speicherverbrauch
- **Dateien gesamt** – Anzahl aller Dateien
- **Bilder** – Anzahl Bilddateien
- **Dokumente** – Anzahl Dokument-Dateien

---

## Datenbank-Tabelle `cms_media`

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | INT | Primärschlüssel |
| `filename` | VARCHAR | Dateiname (gespeichert) |
| `original_name` | VARCHAR | Originaler Upload-Name |
| `file_path` | VARCHAR | Relativer Pfad ab `/uploads/` |
| `file_type` | VARCHAR | MIME-Typ |
| `file_size` | INT | Größe in Bytes |
| `alt_text` | VARCHAR | Alt-Text |
| `description` | TEXT | Beschreibung |
| `uploaded_by` | INT | Benutzer-ID |
| `created_at` | TIMESTAMP | Upload-Zeitpunkt |

---

## Sicherheit

- Alle AJAX-Anfragen mit CSRF-Token
- MIME-Typ-Validierung serverseitig (nicht nur Dateiendung)
- Keine ausführbaren Dateien (.php, .js etc.) erlaubt
- Upload-Verzeichnis liegt außerhalb von `CMS/` Core-Dateien

---

## Verwandte Funktionen

```php
// Datei hochladen
$media = new \CMS\Media();
$result = $media->upload($_FILES['file'], $userId);

// Datei-URL erhalten
$url = get_media_url($mediaId);

// Thumbnail-URL
$thumb = get_thumbnail_url($mediaId, 'medium');
```
