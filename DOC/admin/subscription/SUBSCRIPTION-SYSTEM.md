# 365CMS – Projektdokumentation | Abschnitt: Subscription Settings
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English
### Purpose
Subscription defaults and feature controls are administered at `/admin/subscription-settings`.

### Implementation
- Module: `CMS/admin/modules/subscriptions/SubscriptionSettingsModule.php`
- View: `CMS/admin/views/subscriptions/settings.php`
- Persistence: `CMS/core/Services/SettingsService.php`
- Routing and shell: `CMS/core/Routing/AdminRouter.php`, `CMS/core/Router.php`, `CMS/admin/partials/`

### Operating rules
Only authorized administrators may save settings. Validate each submitted value against the module allowlist, protect the request with the shared CSRF/nonce contract, and verify the resulting settings after redirect. Do not place secrets in documentation, forms, or logs.

## Deutsch
### Zweck
Abonnement-Standards und Feature-Steuerung werden unter `/admin/subscription-settings` verwaltet.

### Implementierung
- Modul: `CMS/admin/modules/subscriptions/SubscriptionSettingsModule.php`
- View: `CMS/admin/views/subscriptions/settings.php`
- Persistenz: `CMS/core/Services/SettingsService.php`
- Routing und Shell: `CMS/core/Routing/AdminRouter.php`, `CMS/core/Router.php`, `CMS/admin/partials/`

### Betriebsregeln
Nur berechtigte Administratoren dürfen Einstellungen speichern. Jede Eingabe wird gegen die Modul-Allowlist validiert, die Anfrage nutzt den gemeinsamen CSRF-/Nonce-Vertrag und der Zustand wird nach der Weiterleitung geprüft. Geheimnisse gehören nicht in Dokumentation, Formulare oder Logs.
