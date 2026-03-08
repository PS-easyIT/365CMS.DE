# Medien-Upload Workflow – 365CMS

> **Bereich:** Medien-Verwaltung · **Version:** 2.3.1  
> **Service:** `core/Services/MediaService.php`  
> **Admin-Seite:** `admin/media.php`

---

## Übersicht: Medien-Pipeline

```
Upload → Validierung → Virencheck* → EXIF-Strip → WebP-Konvertierung → Speichern → Thumbnail-Generierung → CDN-Sync*
                                                                                    (* = zukünftig)
```

---

## Workflow 1: Datei-Upload (Frontend / Admin)

### Sicherheits-Validierung (PFLICHT – in dieser Reihenfolge)

```php
class MediaUploadHandler {

    private const ALLOWED_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'application/pdf',
        'text/plain',
    ];

    private const DANGEROUS_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'phtml', 'phar',
        'exe', 'bat', 'sh', 'cmd', 'jar',
        'js', 'html', 'htm', 'xml',
    ];

    public function handle(array $file): string {

        // 1. PHP-Upload-Fehler prüfen
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload-Fehler: ' . $file['error']);
        }

        // 2. Dateigröße prüfen (max. 20 MB)
        $maxBytes = 20 * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            throw new \RuntimeException('Datei zu groß (max. 20 MB)');
        }

        // 3. MIME-Typ via finfo (NICHT $_FILES['type'] – leicht fälschbar!)
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, self::ALLOWED_TYPES, true)) {
            throw new \RuntimeException('Dateityp nicht erlaubt: ' . $mimeType);
        }

        // 4. Dateiendung prüfen
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, self::DANGEROUS_EXTENSIONS, true)) {
            throw new \RuntimeException('Dateiendung verboten: .' . $ext);
        }

        // 5. PHP-Tags in Bildern suchen (GIFAR-Angriff)
        $content = file_get_contents($file['tmp_name']);
        if (preg_match('/<\?php|<\?=/i', $content)) {
            throw new \RuntimeException('Datei enthält PHP-Code – Upload verweigert');
        }

        // 6. Dateinamen sanitieren
        $safeName = $this->sanitizeFilename($file['name']);
        $destPath = $this->getUploadPath($safeName);

        // 7. Datei verschieben
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new \RuntimeException('Temporäre Datei konnte nicht verschoben werden');
        }

        // 8. EXIF-Daten entfernen (bei Bildern)
        if (str_starts_with($mimeType, 'image/') && $mimeType !== 'image/svg+xml') {
            $this->stripExifData($destPath, $mimeType);
        }

        return $destPath;
    }

    private function sanitizeFilename(string $name): string {
        // Pfad-Traversal entfernen
        $name = basename($name);
        // Sonderzeichen ersetzen
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '-', $name);
        // Doppelte Bindestriche reduzieren
        $name = preg_replace('/-+/', '-', $name);
        // Dateinamen-Kollision verhindern
        return uniqid('', true) . '_' . $name;
    }
}
```

---

## Workflow 2: EXIF-Daten entfernen

**Warum:** EXIF-Daten enthalten GPS-Koordinaten, Kameramodell, Seriennummern.

```php
private function stripExifData(string $filePath, string $mimeType): void {
    if (!function_exists('imagecreatefromjpeg')) return; // GD nicht verfügbar

    match($mimeType) {
        'image/jpeg' => $this->reEncodeJpeg($filePath),
        'image/png'  => $this->reEncodePng($filePath),
        default      => null, // GIF, WebP: keine EXIF
    };
}

private function reEncodeJpeg(string $path): void {
    $img = @imagecreatefromjpeg($path);
    if ($img === false) return;
    imagejpeg($img, $path, 85); // Qualität 85%, keine EXIF
    imagedestroy($img);
}
```

---

## Workflow 3: WebP-Konvertierung (Performance)

```php
// Bei JPEG/PNG automatisch WebP-Kopie erstellen:
private function createWebpVariant(string $sourcePath): ?string {
    if (!function_exists('imagewebp')) return null;

    $ext   = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
    $img   = match($ext) {
        'jpg', 'jpeg' => @imagecreatefromjpeg($sourcePath),
        'png'         => @imagecreatefrompng($sourcePath),
        default       => false,
    };

    if ($img === false) return null;

    $webpPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $sourcePath);
    imagewebp($img, $webpPath, 80); // Qualität 80%
    imagedestroy($img);

    return $webpPath;
}
```

**Ausgabe im Template:**
```html
<picture>
    <source srcset="<?= esc_url($media->getWebpUrl()) ?>" type="image/webp">
    <img src="<?= esc_url($media->getUrl()) ?>" 
         alt="<?= esc_attr($media->getAlt()) ?>"
         loading="lazy"
         width="<?= (int)$media->getWidth() ?>"
         height="<?= (int)$media->getHeight() ?>">
</picture>
```

---

## Workflow 4: Thumbnails generieren

```php
$media = \CMS\Services\MediaService::instance();

// Thumbnails für Standard-Größen:
$media->generateThumbnails($destPath, [
    'small'  => [150, 150, 'crop'],   // Quadrat, beschnitten
    'medium' => [300, 300, 'fit'],    // Max-Breite/Höhe, proportional
    'large'  => [800, 600, 'fit'],    // Großes Format
    'thumb'  => [80,  80,  'crop'],   // Admin-Vorschau
]);
```

---

## Workflow 5: Uploads-Verzeichnis absichern

```apache
# /cms/uploads/.htaccess – PHP-Ausführung DEAKTIVIEREN
<Files "*.php">
    Require all denied
</Files>

# PHP-Datei-Ausführung via FastCGI unterbinden (LiteSpeed/Apache)
Options -ExecCGI
RemoveHandler .php .php3 .php4 .php5 .phtml .phar

# SVG-Direktabruf absichern (XSS via SVG)
<FilesMatch "\.svg$">
    Header set Content-Security-Policy "default-src 'none'; style-src 'unsafe-inline'"
    Header set Content-Type "image/svg+xml"
</FilesMatch>
```

---

## Workflow 6: Medien löschen

**⚠️ Nicht gelöschte Dateien = Speicherplatz-Verlust + potenzielle Datenlecks**

```php
$media = \CMS\Services\MediaService::instance();

// Löscht Datei + alle Thumbnails + DB-Eintrag:
$result = $media->deleteMedia($mediaId);
if (!$result) {
    // DB-Eintrag trotzdem löschen (Orphan-Cleanup):
    $media->deleteMediaRecord($mediaId);
}

// Orphan-Check starten (Medien ohne Content-Referenz):
// Admin → admin/media.php → "Nicht verwendete Medien"
$orphans = $media->findOrphans();
// → Zeigt Dateien an, die in keinem Inhalt referenziert werden
```

---

## Checkliste: Medien-Sicherheit

```
UPLOAD-SICHERHEIT:
[ ] finfo()-MIME-Check aktiv (NICHT $_FILES['type'])
[ ] PHP-Tag-Scan in Bilddaten aktiv
[ ] Dateiname sanitiert (keine Pfad-Traversal-Zeichen)
[ ] move_uploaded_file() verwendet (NICHT copy/rename)

SPEICHER-SICHERHEIT:
[ ] uploads/.htaccess: PHP-Ausführung deaktiviert
[ ] uploads/ nicht über Repository getrackt (.gitignore)
[ ] Backup: uploads/ wöchentlich gesichert

EXIF-DATENSCHUTZ:
[ ] EXIF wird bei JPEG/PNG entfernt
[ ] Keine GPS-Daten in veröffentlichten Bildern

PERFORMANCE:
[ ] WebP-Variante bei JPEG/PNG erstellt
[ ] Thumbnails on-demand oder beim Upload generiert
[ ] Lazy-Loading im Template: loading="lazy"
```

---

## Referenzen

- [core/Services/MediaService.php](../../CMS/core/Services/MediaService.php) – Service-Klasse
- [admin/media.php](../../CMS/admin/media.php) – Admin-UI
- [SECURITY-AUDIT.md](../audits/SECURITY-AUDIT.md) – S-07: Datei-Upload-Sicherheit
- [PERFORMANCE-AUDIT.md](../audits/PERFORMANCE-AUDIT.md) – P-03: WebP-Konvertierung
