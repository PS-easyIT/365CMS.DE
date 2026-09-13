# 365CMS – Projektdokumentation | Abschnitt: Marketplace
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English
### Purpose
The plugin/theme marketplace is available at `/admin/plugin-marketplace`.

### Implementation
- Entry/view: `CMS/admin/plugin-marketplace.php`, `CMS/admin/views/plugins/marketplace.php`
- Modules: `CMS/admin/modules/plugins/PluginMarketplaceModule.php`, `CMS/admin/modules/themes/ThemeMarketplaceModule.php`
- Core: `CMS/core/PluginManager.php`, `CMS/core/ThemeManager.php`

### Administration
Review publisher, version, compatibility, and permissions before installing or updating. Marketplace actions require capability and CSRF/nonce checks; verify the resulting module state and audit security-relevant changes.

## Deutsch
### Zweck
Der Plugin-/Theme-Marketplace ist unter `/admin/plugin-marketplace` verfügbar.

### Implementierung
- Einstieg/View: `CMS/admin/plugin-marketplace.php`, `CMS/admin/views/plugins/marketplace.php`
- Module: `CMS/admin/modules/plugins/PluginMarketplaceModule.php`, `CMS/admin/modules/themes/ThemeMarketplaceModule.php`
- Core: `CMS/core/PluginManager.php`, `CMS/core/ThemeManager.php`

### Administration
Vor Installation oder Update Herausgeber, Version, Kompatibilität und Berechtigungen prüfen. Marketplace-Aktionen benötigen Capability und CSRF/Nonce; Modulstatus nach der Aktion prüfen und sicherheitsrelevante Änderungen auditieren.
