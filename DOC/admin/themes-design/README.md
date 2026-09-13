# 365CMS – Projektdokumentation | Abschnitt: Themes and design
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English
### Scope
This section documents the CMS login page, theme editor/customizer, menus, fonts, dashboard widgets, design settings, and marketplace.

### Screens
| Area | Route | Source |
|---|---|---|
| CMS login page | `/admin/cms-loginpage` | `CMS/admin/cms-loginpage.php`, `CMS/admin/views/themes/cms-loginpage.php` |
| Editor/customizer | `/admin/theme-editor` | `CMS/admin/theme-editor.php`, `CMS/admin/theme-customizer.php` |
| Menus | `/admin/menu-editor` | `CMS/admin/menu-editor.php`, `CMS/admin/modules/menus/MenuEditorModule.php` |
| Fonts | `/admin/font-manager` | `CMS/admin/fonts-local.php`, `CMS/admin/views/themes/fonts.php` |
| Widgets/design | `/admin/member-dashboard-widgets`, `/admin/design-settings` | `CMS/admin/member-dashboard-widgets.php`, `CMS/admin/design-settings.php` |
| Marketplace | `/admin/plugin-marketplace` | `CMS/admin/plugin-marketplace.php`, `CMS/admin/modules/plugins/PluginMarketplaceModule.php` |

### Common controls
Use only controls rendered by the current page. Changes require capability, CSRF/nonce, allowlisted values, validation, and escaped output. Uploaded media is handled by `CMS/core/Services/FileUploadService.php`; audit security-sensitive changes with `CMS/core/AuditLogger.php`.

## Deutsch
### Umfang
Dieser Abschnitt beschreibt CMS-Loginseite, Theme-Editor/Customizer, Menüs, Fonts, Dashboard-Widgets, Design-Einstellungen und Marketplace.

### Seiten
| Bereich | Route | Quelle |
|---|---|---|
| CMS-Loginseite | `/admin/cms-loginpage` | `CMS/admin/cms-loginpage.php`, `CMS/admin/views/themes/cms-loginpage.php` |
| Editor/Customizer | `/admin/theme-editor` | `CMS/admin/theme-editor.php`, `CMS/admin/theme-customizer.php` |
| Menüs | `/admin/menu-editor` | `CMS/admin/menu-editor.php`, `CMS/admin/modules/menus/MenuEditorModule.php` |
| Fonts | `/admin/font-manager` | `CMS/admin/fonts-local.php`, `CMS/admin/views/themes/fonts.php` |
| Widgets/Design | `/admin/member-dashboard-widgets`, `/admin/design-settings` | `CMS/admin/member-dashboard-widgets.php`, `CMS/admin/design-settings.php` |
| Marketplace | `/admin/plugin-marketplace` | `CMS/admin/plugin-marketplace.php`, `CMS/admin/modules/plugins/PluginMarketplaceModule.php` |

### Gemeinsame Regeln
Nur die Steuerelemente der aktuellen Seite verwenden. Änderungen benötigen Capability, CSRF/Nonce, Allowlists, Validierung und Escaping. Uploads behandelt `CMS/core/Services/FileUploadService.php`; sicherheitsrelevante Änderungen über `CMS/core/AuditLogger.php` prüfen.
