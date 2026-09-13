# 365CMS – Projektdokumentation | Abschnitt: Editor
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English
### Purpose
The editor screen at `/admin/theme-editor` coordinates theme editing, menu editing, and Editor.js integrations.

### Implementation
- Entries: `CMS/admin/theme-editor.php`, `CMS/admin/menu-editor.php`, `CMS/admin/ai-translate-editorjs.php`
- Modules: `CMS/admin/modules/themes/ThemeEditorModule.php`, `CMS/admin/modules/menus/MenuEditorModule.php`
- Core editor services: `CMS/core/Services/EditorService.php`, `CMS/core/Services/EditorJs/EditorJsRequestGuard.php`

### Administration
Use the page controls and preview changes. Editor requests require capability, CSRF/nonce, request guards, sanitization, and context-appropriate escaping. Do not paste secrets into editor content.

## Deutsch
### Zweck
Die Editor-Seite unter `/admin/theme-editor` verbindet Theme-, Menü- und Editor.js-Bearbeitung.

### Implementierung
- Einstiege: `CMS/admin/theme-editor.php`, `CMS/admin/menu-editor.php`, `CMS/admin/ai-translate-editorjs.php`
- Module: `CMS/admin/modules/themes/ThemeEditorModule.php`, `CMS/admin/modules/menus/MenuEditorModule.php`
- Core-Editor-Services: `CMS/core/Services/EditorService.php`, `CMS/core/Services/EditorJs/EditorJsRequestGuard.php`

### Administration
Seitensteuerung und Vorschau verwenden. Editor-Anfragen benötigen Capability, CSRF/Nonce, Request-Guards, Sanitizing und kontextgerechtes Escaping. Keine Geheimnisse in Editor-Inhalte einfügen.
