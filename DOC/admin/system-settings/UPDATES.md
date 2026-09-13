# 365CMS – Projektdokumentation | Abschnitt: Updates
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English
### Purpose
Administrators review available updates and update status at `/admin/updates`.

### Implementation
- Entry: `CMS/admin/updates.php`
- Module/view: `CMS/admin/modules/system/UpdatesModule.php`, `CMS/admin/views/system/updates.php`
- Service: `CMS/core/Services/UpdateService.php`

### Safe update flow
Review the offered version, compatibility status, and backup state. Start an update only with the required capability and CSRF/nonce. Do not refresh during the operation; verify the final status and record failures through the operational log.

## Deutsch
### Zweck
Verfügbare Updates und ihr Status werden unter `/admin/updates` geprüft.

### Implementierung
- Einstieg: `CMS/admin/updates.php`
- Modul/View: `CMS/admin/modules/system/UpdatesModule.php`, `CMS/admin/views/system/updates.php`
- Service: `CMS/core/Services/UpdateService.php`

### Sicherer Update-Ablauf
Version, Kompatibilität und Backup-Zustand prüfen. Updates nur mit erforderlicher Capability und CSRF/Nonce starten. Während des Vorgangs nicht aktualisieren; Abschlussstatus prüfen und Fehler im Betriebslog festhalten.
