# 365CMS – Projektdokumentation | Abschnitt: System

## English
### Purpose
System maintenance and system information are administered at `/admin/settings`.

### Implementation
- Entries: `CMS/admin/info.php`, `CMS/admin/system-info.php`
- Modules: `CMS/admin/modules/system/SystemInfoModule.php`, `CMS/admin/modules/system/DocumentationSyncFilesystem.php`
- Core: `CMS/core/Services/SystemService.php`, `CMS/core/MigrationManager.php`, `CMS/core/Routing/AdminRouter.php`

### Rules
Read the current status before maintenance. Repair or migration actions require explicit capability and CSRF/nonce validation, normalized inputs, and audit logging. Keep the admin shell usable when optional diagnostics fail.

## Deutsch
### Zweck
Systemwartung und Systeminformationen werden unter `/admin/settings` verwaltet.

### Implementierung
- Einstiege: `CMS/admin/info.php`, `CMS/admin/system-info.php`
- Module: `CMS/admin/modules/system/SystemInfoModule.php`, `CMS/admin/modules/system/DocumentationSyncFilesystem.php`
- Core: `CMS/core/Services/SystemService.php`, `CMS/core/MigrationManager.php`, `CMS/core/Routing/AdminRouter.php`

### Regeln
Vor Wartungen den aktuellen Status lesen. Reparatur- und Migrationsaktionen benötigen Capability, CSRF/Nonce, normalisierte Eingaben und Audit-Protokollierung. Bei optionalen Diagnosefehlern bleibt die Admin-Shell nutzbar.
