# 365CMS – Projektdokumentation | Abschnitt: Users
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English
### Purpose
User records, status, and membership are managed at `/admin/users`.

### Implementation
- Entry: `CMS/admin/users.php`
- Module: `CMS/admin/modules/users/UsersModule.php`
- Core: `CMS/core/Services/UserService.php`, `CMS/core/Auth/AuthManager.php`

### Administration
Confirm identity before changing or deleting a record. User actions require capability, CSRF/nonce, normalized input, server-side validation, prepared persistence, and escaped output. Avoid exposing personal data and verify the resulting status after redirect.

## Deutsch
### Zweck
Benutzerkonten, Status und Mitgliedschaften werden unter `/admin/users` verwaltet.

### Implementierung
- Einstieg: `CMS/admin/users.php`
- Modul: `CMS/admin/modules/users/UsersModule.php`
- Core: `CMS/core/Services/UserService.php`, `CMS/core/Auth/AuthManager.php`

### Administration
Identität vor Änderung oder Löschung bestätigen. Benutzeraktionen benötigen Capability, CSRF/Nonce, normalisierte Eingaben, serverseitige Validierung, vorbereitete Persistenz und Escaping. Personenbezogene Daten nicht unnötig offenlegen und den Status nach der Weiterleitung prüfen.
