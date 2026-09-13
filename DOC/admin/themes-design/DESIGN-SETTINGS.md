# 365CMS – Projektdokumentation | Abschnitt: Design settings
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English
### Purpose
Global design settings are maintained at `/admin/design-settings`.

### Implementation
- Entry: `CMS/admin/design-settings.php`
- View: `CMS/admin/views/themes/settings.php`
- Services: `CMS/core/Services/SettingsService.php`, `CMS/core/Services/ThemeCustomizer.php`

### Administration
Use the current form and its allowlisted fields. Capability, CSRF/nonce, validation, and output escaping apply to every change; preview and verify the affected frontend surfaces.

## Deutsch
### Zweck
Globale Design-Einstellungen werden unter `/admin/design-settings` gepflegt.

### Implementierung
- Einstieg: `CMS/admin/design-settings.php`
- View: `CMS/admin/views/themes/settings.php`
- Services: `CMS/core/Services/SettingsService.php`, `CMS/core/Services/ThemeCustomizer.php`

### Administration
Das aktuelle Formular und seine Feld-Allowlist verwenden. Für jede Änderung gelten Capability, CSRF/Nonce, Validierung und Escaping; betroffene Frontend-Bereiche vor und nach dem Speichern prüfen.
