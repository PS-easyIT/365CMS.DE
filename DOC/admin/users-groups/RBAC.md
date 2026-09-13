# 365CMS – Projektdokumentation | Abschnitt: RBAC
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English
### Purpose
Role and capability assignments are reviewed at `/admin/rbac`.

### Implementation
- Entry: `CMS/admin/rbac.php`
- Core authorization: `CMS/core/Auth.php`, `CMS/core/Auth/AuthManager.php`, `CMS/core/Security.php`
- Audit: `CMS/core/AuditLogger.php`

### Administration
Grant the smallest capability set needed and check inheritance before saving. Protect changes with capability and CSRF/nonce checks, validate identifiers server-side, and record the actor, target, and result in the audit trail without secrets.

## Deutsch
### Zweck
Rollen- und Capability-Zuweisungen werden unter `/admin/rbac` geprüft.

### Implementierung
- Einstieg: `CMS/admin/rbac.php`
- Core-Autorisierung: `CMS/core/Auth.php`, `CMS/core/Auth/AuthManager.php`, `CMS/core/Security.php`
- Audit: `CMS/core/AuditLogger.php`

### Administration
Die kleinstmögliche erforderliche Capability-Menge vergeben und Vererbung vor dem Speichern prüfen. Änderungen mit Capability und CSRF/Nonce schützen, IDs serverseitig validieren und Akteur, Ziel und Ergebnis ohne Geheimnisse auditieren.
