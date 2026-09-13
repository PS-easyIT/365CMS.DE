# 365CMS – Projektdokumentation | Abschnitt: Theme customizer
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English
### Purpose
Theme appearance settings are managed through the theme editor route `/admin/theme-editor`.

### Implementation
- Entries: `CMS/admin/theme-editor.php`, `CMS/admin/theme-customizer.php`
- Module/service: `CMS/admin/modules/themes/ThemeEditorModule.php`, `CMS/core/Services/ThemeCustomizer.php`
- Routing: `CMS/core/Routing/AdminRouter.php`, `CMS/core/Router.php`

### Administration
Preview before publishing. Save only allowlisted settings with capability and CSRF/nonce protection, then verify the resulting frontend. A disabled theme or optional integration must fail closed.

## Deutsch
### Zweck
Theme-Darstellung wird über die Theme-Editor-Route `/admin/theme-editor` verwaltet.

### Implementierung
- Einstiege: `CMS/admin/theme-editor.php`, `CMS/admin/theme-customizer.php`
- Modul/Service: `CMS/admin/modules/themes/ThemeEditorModule.php`, `CMS/core/Services/ThemeCustomizer.php`
- Routing: `CMS/core/Routing/AdminRouter.php`, `CMS/core/Router.php`

### Administration
Vor dem Veröffentlichen die Vorschau prüfen. Nur Allowlists mit Capability- und CSRF-/Nonce-Schutz speichern und anschließend das Frontend prüfen. Deaktivierte Themes oder optionale Integrationen bleiben geschlossen.
