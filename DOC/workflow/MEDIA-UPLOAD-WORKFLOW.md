# 365CMS – Projektdokumentation | Abschnitt: Workflow – Medien-Upload
> **Stand:** 2026-09-14 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-14

## English

Media administration uses `CMS/admin/modules/media/MediaModule.php`, `CMS/core/Services/Media/MediaRepository.php`, `CMS/core/Services/MediaService.php`, and `CMS/core/Services/FileUploadService.php`. Upload payloads are normalized and validated before metadata is written. Use the module and service boundary; never persist upload fields directly from a view.

## Deutsch

Die Medienverwaltung verwendet `CMS/admin/modules/media/MediaModule.php`, `CMS/core/Services/Media/MediaRepository.php`, `CMS/core/Services/MediaService.php` und `CMS/core/Services/FileUploadService.php`. Upload-Daten werden vor dem Schreiben der Metadaten normalisiert und validiert. Verwenden Sie die Modul- und Service-Grenze und speichern Sie Upload-Felder niemals direkt aus einer View.
