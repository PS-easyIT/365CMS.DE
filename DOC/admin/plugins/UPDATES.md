# 365CMS – Projektdokumentation | Abschnitt: Admin – Updates
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

The admin update workflow is exposed at `/admin/updates` by `CMS/admin/updates.php`, `CMS/admin/modules/system/UpdatesModule.php`, and `CMS/admin/views/system/updates.php`.

Updates must be started from the authenticated admin screen. The runtime verifies the available update information and reports failures through the existing result and logging paths. Preserve `config/`, uploads, cache, logs, and backups when preparing or reviewing an update package.

## Deutsch

Der Admin-Update-Workflow ist unter `/admin/updates` erreichbar und wird durch `CMS/admin/updates.php`, `CMS/admin/modules/system/UpdatesModule.php` und `CMS/admin/views/system/updates.php` umgesetzt.

Updates werden ausschließlich über die authentifizierte Admin-Seite gestartet. Die Runtime prüft verfügbare Updateinformationen und meldet Fehler über die vorhandenen Ergebnis- und Logging-Pfade. Beim Vorbereiten oder Prüfen eines Updatepakets müssen `config/`, Uploads, Cache, Logs und Backups erhalten bleiben.
