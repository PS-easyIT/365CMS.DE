# 365CMS – Projektdokumentation | Abschnitt: Fonts
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English
### Purpose
Local font configuration is managed at `/admin/font-manager`.

### Implementation
- Entry/view: `CMS/admin/fonts-local.php`, `CMS/admin/views/themes/fonts.php`
- Services: `CMS/core/Services/AssetOptimizerService.php`, `CMS/core/Services/FileUploadService.php`

### Administration
Use supported font formats and the displayed size limits. Uploads require ownership, MIME, size, destination, capability, and CSRF/nonce checks. Verify generated assets and remove unused files through the supplied controls.

## Deutsch
### Zweck
Lokale Font-Konfiguration wird unter `/admin/font-manager` verwaltet.

### Implementierung
- Einstieg/View: `CMS/admin/fonts-local.php`, `CMS/admin/views/themes/fonts.php`
- Services: `CMS/core/Services/AssetOptimizerService.php`, `CMS/core/Services/FileUploadService.php`

### Administration
Unterstützte Font-Formate und angezeigte Größenlimits einhalten. Uploads benötigen Besitz-, MIME-, Größen-, Ziel-, Capability- und CSRF/Nonce-Prüfungen. Erzeugte Assets prüfen und ungenutzte Dateien über die angebotenen Steuerelemente entfernen.
