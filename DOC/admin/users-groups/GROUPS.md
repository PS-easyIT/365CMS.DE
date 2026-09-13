# 365CMS – Projektdokumentation | Abschnitt: Groups
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English
### Purpose
Groups and their membership are managed at `/admin/groups`.

### Implementation
- Entry: `CMS/admin/groups.php`
- Module/view: `CMS/admin/modules/users/GroupsModule.php`, `CMS/admin/views/users/groups.php`
- Core: `CMS/core/Services/UserService.php`, `CMS/core/Auth/AuthManager.php`

### Administration
Confirm the group and affected members before saving. Capability, CSRF/nonce, membership validation, and audit logging are required; verify effective access after a change.

## Deutsch
### Zweck
Gruppen und ihre Mitgliedschaften werden unter `/admin/groups` verwaltet.

### Implementierung
- Einstieg: `CMS/admin/groups.php`
- Modul/View: `CMS/admin/modules/users/GroupsModule.php`, `CMS/admin/views/users/groups.php`
- Core: `CMS/core/Services/UserService.php`, `CMS/core/Auth/AuthManager.php`

### Administration
Gruppe und betroffene Mitglieder vor dem Speichern bestätigen. Capability, CSRF/Nonce, Mitgliedschaftsvalidierung und Audit-Protokollierung sind erforderlich; effektive Berechtigungen nach der Änderung prüfen.
