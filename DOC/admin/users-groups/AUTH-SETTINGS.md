# 365CMS – Projektdokumentation | Abschnitt: Authentication settings
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English
### Purpose
Authentication and user defaults are managed at `/admin/user-settings`.

### Implementation
- Module/view: `CMS/admin/modules/users/UserSettingsModule.php`, `CMS/admin/views/users/settings.php`
- Core authentication: `CMS/core/Auth.php`, `CMS/core/Auth/AuthManager.php`
- MFA adapters: `CMS/core/Auth/MFA/TotpAdapter.php`, `CMS/core/Auth/MFA/BackupCodesManager.php`

### Administration
Apply the least-permissive setting compatible with the deployment. Changes require capability and CSRF/nonce validation. Never display or log passwords, secrets, backup codes, or tokens; verify the next login flow after saving.

## Deutsch
### Zweck
Authentifizierung und Benutzerstandards werden unter `/admin/user-settings` verwaltet.

### Implementierung
- Modul/View: `CMS/admin/modules/users/UserSettingsModule.php`, `CMS/admin/views/users/settings.php`
- Core-Authentifizierung: `CMS/core/Auth.php`, `CMS/core/Auth/AuthManager.php`
- MFA-Adapter: `CMS/core/Auth/MFA/TotpAdapter.php`, `CMS/core/Auth/MFA/BackupCodesManager.php`

### Administration
Die restriktivste passende Einstellung verwenden. Änderungen benötigen Capability und CSRF/Nonce. Passwörter, Geheimnisse, Backup-Codes und Tokens weder anzeigen noch protokollieren; den nächsten Login nach dem Speichern prüfen.
