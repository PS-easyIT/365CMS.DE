# 365CMS – Projektdokumentation | Abschnitt: Workflow – Update und Deployment
> **Stand:** 2026-09-14 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-14

## English

The runtime update flow is exposed through `CMS/admin/modules/system/UpdatesModule.php` and `CMS/admin/updates.php`. Installation and schema work are handled by `CMS/install.php`, `CMS/install/`, and `CMS/core/SchemaManager.php`. Before an update, protect installation-specific `config/`, uploads, cache, logs, and backups; verify the package and follow the admin result and recovery information.

## Deutsch

Der Runtime-Update-Workflow wird durch `CMS/admin/modules/system/UpdatesModule.php` und `CMS/admin/updates.php` bereitgestellt. Installation und Schema-Änderungen werden durch `CMS/install.php`, `CMS/install/` und `CMS/core/SchemaManager.php` behandelt. Vor einem Update sind installationsspezifische `config/`, Uploads, Cache, Logs und Backups zu schützen; Paketprüfung sowie Ergebnis- und Recovery-Hinweise der Administration sind zu beachten.
