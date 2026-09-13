# 365CMS – Projektdokumentation | Abschnitt: Backups
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English
### Purpose
Administrators review and run supported backup operations at `/admin/backups`.

### Implementation
- Entry: `CMS/admin/backups.php`
- Module/view: `CMS/admin/modules/system/BackupsModule.php`, `CMS/admin/views/system/backups.php`
- Service: `CMS/core/Services/BackupService.php`

### Safe operation
Check destination, scope, available space, and the last successful run before starting. Backup and restore actions require capability and CSRF/nonce checks. Treat generated archives as sensitive, do not expose their contents in logs, and verify completion through the page status.

## Deutsch
### Zweck
Administratoren prüfen und starten unterstützte Backup-Vorgänge unter `/admin/backups`.

### Implementierung
- Einstieg: `CMS/admin/backups.php`
- Modul/View: `CMS/admin/modules/system/BackupsModule.php`, `CMS/admin/views/system/backups.php`
- Service: `CMS/core/Services/BackupService.php`

### Sicherer Betrieb
Vor dem Start Ziel, Umfang, Speicherplatz und letzten erfolgreichen Lauf prüfen. Backup- und Restore-Aktionen benötigen Capability sowie CSRF/Nonce. Archive sind vertraulich; Inhalte nicht in Logs ausgeben und Abschluss über den Seitenstatus prüfen.
