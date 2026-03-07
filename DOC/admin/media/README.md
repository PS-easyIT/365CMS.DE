# 365CMS – Medienverwaltung

Kurzbeschreibung: Überblick über Medienbibliothek, Upload-Workflows, Schutzbereiche und zugehörige Dokumente.

Letzte Aktualisierung: 2026-03-07

---

## Überblick

Die Medienverwaltung ist unter `/admin/media` erreichbar und steuert Upload, Suche, Filter, Dateioperationen und Vorschaulogik für den globalen Medienbestand.

Wesentliche Merkmale im aktuellen Stand:

- Standardmäßig **Listenansicht**
- Such- und Kategorien-Filter
- Datei- und Ordnerlöschung
- robusterer Redirect nach Aktionen
- URL-sichere Vorschaulinks auch bei Leerzeichen und Umlauten
- geschützter `member`-Ordner mit zusätzlicher Bestätigung

---

## Dokumente in diesem Bereich

| Dokument | Schwerpunkt |
|---|---|
| [MEDIA.md](MEDIA.md) | Funktionen, Datenmodell und Sicherheitsaspekte |

---

## Verknüpfte Bereiche

| Bereich | Bezug |
|---|---|
| Seiten & Beiträge | Featured Images und Einbettungen |
| Media-Proxy | kontrollierte Auslieferung |
| Performance | Bildgrößen, WebP und Medienoptimierung |
| Member-Bereich | geschützter Medienordner |

| Thumbnail | Kleines Vorschaubild |
| Dateiname | Original-Dateiname mit Link zur Bearbeitungsseite |
| Typ | MIME-Type |
| Größe | Dateigröße in KB/MB |
| Abmessungen | Breite × Höhe (nur für Bilder) |
| Hochgeladen | Datum und Uhrzeit |
| Benutzer | Wer hat die Datei hochgeladen |
| Aktionen | Bearbeiten, Löschen, URL kopieren |

---

## 3. Dateien hochladen

### Drag & Drop (Standard)
- Dateien in den Upload-Bereich ziehen
- Mehrere Dateien gleichzeitig möglich
- Fortschrittsanzeige pro Datei

### Browser-Upload
- Klassischer Datei-Dialog
- Mehrfachauswahl mit `Strg+Klick` oder `⌘+Klick`

### Upload-Grenzwerte

| Einschränkung | Standard | Konfigurierbar |
|---|---|---|
| Max. Dateigröße | 10 MB | ✅ `admin/settings.php` |
| Max. Bildbreite | 2400 px | ✅ Auto-Resize |
| Erlaubte Typen | Alle oben | ✅ Whitelist |
| Speicherquote gesamt | Unbegrenzt | ✅ Pro Benutzer |

**WebP-Konvertierung (optional):**
- Aktivierbar unter `admin/performance.php`
- Erstellt automatisch WebP-Kopie von JPG/PNG
- Original wird behalten, WebP bevorzugt ausgeliefert

**Automatische Thumbnails:**
Für jedes hochgeladene Bild werden automatisch generiert:
- `thumbnail`: 150×150 px (crop center)
- `medium`: 300×300 px (max, kein Crop)
- `large`: 1024×1024 px (max, kein Crop)

---

## 4. Bild-Bearbeitung

Basis-Bild-Editor direkt im Browser:

| Funktion | Beschreibung |
|---|---|
| **Zuschneiden** | Freie Auswahl oder vordefinierte Verhältnisse (1:1, 16:9, 4:3) |
| **Drehen** | ±90°, ±180° |
| **Spiegeln** | Horizontal / Vertikal |
| **Skalieren** | Breite und Höhe (proportional oder frei) |
| **Helligkeit/Kontrast** | Slider-Anpassung |

⚠️ Bearbeitung überschreibt das Original – **vorher Backup sichern**!

---

## 5. Metadaten verwalten

Pro Mediendatei bearbeitbar:

| Feld | SEO-Relevanz | Beispiel |
|---|---|---|
| **Titel** | Mittel | „Website-Launch-Event-2026" |
| **Alt-Text** | Hoch (Bilder) | „Teilnehmer beim IT-Netzwerk-Event" |
| **Beschreibung** | Niedrig | Längerer Beschreibungstext |
| **Bildunterschrift** | Niedrig | Wird unter dem Bild angezeigt |

**Best Practice:** Alt-Text immer ausfüllen – wichtig für SEO und Barrierefreiheit (WCAG 2.1).

---

## 6. Suche & Filter

| Filter | Optionen |
|---|---|
| **Dateityp** | Bilder, Dokumente, Videos, Audio, Alle |
| **Datum** | Monat/Jahr-Auswahl |
| **Benutzer** | Nur eigene / Alle (Admin only) |
| **Suche** | Dateiname, Titel, Alt-Text (Volltext) |

---

## 7. Technische Details

**Service:** `CMS\Services\MediaService`

```php
// Datei hochladen
$media = MediaService::instance();
$result = $media->upload($_FILES['file'], [
    'allowed_types' => ['image/jpeg', 'image/png', 'image/webp'],
    'max_size'      => 10 * 1024 * 1024,  // 10 MB
    'owner_id'      => $currentUserId,
    'webp_convert'  => true,
]);
// $result: ['id' => 42, 'url' => '/uploads/2026/02/bild.jpg', ...]

// Metadaten lesen
$meta = $media->getMeta($mediaId);
// ['title' => '...', 'alt' => '...', 'width' => 1920, 'height' => 1080]
```

**Datenbank-Tabelle: `cms_media`**

| Spalte | Typ | Beschreibung |
|---|---|---|
| `id` | INT | Auto-Increment Primary Key |
| `filename` | VARCHAR(255) | Original-Dateiname |
| `filepath` | VARCHAR(500) | Relativer Pfad ab `/uploads/` |
| `mime_type` | VARCHAR(100) | MIME-Type |
| `filesize` | INT | Größe in Bytes |
| `width` | INT | Bildbreite (null für Nicht-Bilder) |
| `height` | INT | Bildhöhe (null für Nicht-Bilder) |
| `alt_text` | TEXT | Alt-Attribut |
| `title` | VARCHAR(255) | Mediendatei-Titel |
| `user_id` | INT | Erstellt von |
| `created_at` | DATETIME | Upload-Zeitstempel |

**Hooks:**
```php
do_action('cms_media_uploaded', $mediaId, $filePath, $userId);
do_action('cms_media_deleted', $mediaId, $filePath);
add_filter('cms_media_allowed_types', 'my_plugin_allow_svg');
```
