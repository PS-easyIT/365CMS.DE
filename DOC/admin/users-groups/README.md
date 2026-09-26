# 365CMS – Projektdokumentation | Abschnitt: Users and groups

## English
### Scope
This section covers users, groups, RBAC, and authentication settings. All screens are capability-aware and fail closed when access or an optional provider is unavailable.

### Screens
| Area | Route | Source |
|---|---|---|
| Users | `/admin/users` | `CMS/admin/users.php`, `CMS/admin/modules/users/UsersModule.php` |
| Groups | `/admin/groups` | `CMS/admin/groups.php`, `CMS/admin/modules/users/GroupsModule.php` |
| RBAC | `/admin/roles` | `CMS/admin/roles.php` |
| Authentication | `/admin/user-settings` | `CMS/admin/modules/users/UserSettingsModule.php`, `CMS/admin/views/users/settings.php` |

### Security baseline
Use the current form controls, not handcrafted requests. Every change requires authenticated capability checks, CSRF/nonce validation, normalized input, allowlisted fields, and audit logging. Passwords, MFA secrets, recovery codes, and tokens must never appear in UI or logs.

## Deutsch
### Umfang
Dieser Abschnitt behandelt Benutzer, Gruppen, RBAC und Authentifizierungs-Einstellungen. Alle Seiten sind capability-gesteuert und bleiben bei fehlendem Zugriff oder Provider geschlossen.

### Seiten
| Bereich | Route | Quelle |
|---|---|---|
| Benutzer | `/admin/users` | `CMS/admin/users.php`, `CMS/admin/modules/users/UsersModule.php` |
| Gruppen | `/admin/groups` | `CMS/admin/groups.php`, `CMS/admin/modules/users/GroupsModule.php` |
| RBAC | `/admin/roles` | `CMS/admin/roles.php` |
| Authentifizierung | `/admin/user-settings` | `CMS/admin/modules/users/UserSettingsModule.php`, `CMS/admin/views/users/settings.php` |

### Sicherheitsstandard
Aktuelle Formulare statt eigener Requests verwenden. Jede Änderung benötigt authentifizierte Capability-Prüfung, CSRF/Nonce, normalisierte Eingaben, Feld-Allowlist und Audit-Protokollierung. Passwörter, MFA-Geheimnisse, Wiederherstellungscodes und Tokens niemals in UI oder Logs ausgeben.
