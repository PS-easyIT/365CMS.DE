# 365CMS – Projektdokumentation | Abschnitt: Menus
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English
### Purpose
Menu structure and item ordering are managed at `/admin/menu-editor`.

### Implementation
- Entry: `CMS/admin/menu-editor.php`
- Module: `CMS/admin/modules/menus/MenuEditorModule.php`
- Core: `CMS/core/PageManager.php`, `CMS/core/Services/PermalinkService.php`

### Administration
Use only existing menu targets and registered item types. Validate labels, targets, nesting, and order server-side; require capability and CSRF/nonce; escape labels and URLs for their context.

## Deutsch
### Zweck
Menüstruktur und Reihenfolge werden unter `/admin/menu-editor` verwaltet.

### Implementierung
- Einstieg: `CMS/admin/menu-editor.php`
- Modul: `CMS/admin/modules/menus/MenuEditorModule.php`
- Core: `CMS/core/PageManager.php`, `CMS/core/Services/PermalinkService.php`

### Administration
Nur vorhandene Ziele und registrierte Elementtypen verwenden. Labels, Ziele, Verschachtelung und Reihenfolge serverseitig validieren; Capability und CSRF/Nonce verlangen; Labels und URLs kontextgerecht escapen.
