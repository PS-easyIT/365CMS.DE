# 365CMS – Projektdokumentation | Abschnitt: Admin – Medienverwaltung
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

### Scope

The media administration is available at `/admin/media`. It provides the authenticated admin UI for browsing media paths, searching and filtering files, assigning categories and tags, inspecting usage, and applying supported file actions.

### Runtime reference

| Area | Current implementation |
|---|---|
| Entry point | `CMS/admin/media.php` |
| Module | `CMS/admin/modules/media/MediaModule.php` |
| Performance view | `CMS/admin/performance-media.php` and `CMS/admin/views/performance/media.php` |
| Storage services | `CMS/core/Services/MediaService.php`, `CMS/core/Services/Media/MediaRepository.php` |
| Upload handling | `CMS/core/Services/FileUploadService.php` |

The repository maintains media metadata separately from the file system. Categories, tags, public/preview URLs, disk usage, path validation, and atomic metadata writes are handled by the media repository. The upload service validates and normalizes upload payloads before storage.

### Operational rules

- Use the supplied admin forms; do not construct file paths from untrusted input.
- Keep uploads and metadata within the configured media root.
- Treat failed, missing, or unavailable items as an error state and investigate the displayed diagnostic.
- Review usage before deleting or replacing a file.
- Do not expose upload credentials or internal storage paths in documentation, UI, or logs.

## Deutsch

### Umfang

Die Medienverwaltung ist unter `/admin/media` erreichbar. Sie stellt für angemeldete Administratoren das Durchsuchen von Medienpfaden, Suche und Filter, Kategorien und Tags, Nutzungsinformationen sowie unterstützte Dateiaktionen bereit.

### Runtime-Referenz

| Bereich | Aktuelle Implementierung |
|---|---|
| Einstieg | `CMS/admin/media.php` |
| Modul | `CMS/admin/modules/media/MediaModule.php` |
| Performance-Ansicht | `CMS/admin/performance-media.php` und `CMS/admin/views/performance/media.php` |
| Speicherdienste | `CMS/core/Services/MediaService.php`, `CMS/core/Services/Media/MediaRepository.php` |
| Upload-Verarbeitung | `CMS/core/Services/FileUploadService.php` |

Metadaten werden getrennt vom Dateisystem verwaltet. Kategorien, Tags, öffentliche Vorschau-URLs, Speicherverbrauch, Pfadvalidierung und atomare Metadaten-Schreibvorgänge liegen im Media-Repository. Der Upload-Service prüft und normalisiert Upload-Daten vor dem Speichern.

### Betriebsregeln

- Ausschließlich die vorhandenen Admin-Formulare verwenden; Dateipfade nie aus ungeprüften Eingaben zusammensetzen.
- Uploads und Metadaten im konfigurierten Medienverzeichnis belassen.
- Fehlende oder nicht verfügbare Dateien als Fehlerzustand behandeln und die Diagnose prüfen.
- Vor dem Löschen oder Ersetzen die Nutzung kontrollieren.
- Zugangsdaten und interne Speicherpfade weder dokumentieren noch in UI oder Logs ausgeben.
