# 365CMS – Projektdokumentation | Abschnitt: Admin – Plugins
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

Plugin administration is available at `/admin/plugins`. The entry point is `CMS/admin/plugins.php` and the current module is `CMS/admin/modules/plugins/PluginsModule.php`.

The screen lists installed extensions and exposes only the actions implemented and validated by the module. Plugin state changes require the authenticated admin workflow; do not edit plugin files or configuration values from the browser.

## Deutsch

Die Pluginverwaltung ist unter `/admin/plugins` erreichbar. Der Einstieg liegt in `CMS/admin/plugins.php`; das aktuelle Modul ist `CMS/admin/modules/plugins/PluginsModule.php`.

Die Seite listet installierte Erweiterungen und bietet ausschließlich die vom Modul implementierten und geprüften Aktionen. Zustandsänderungen benötigen den authentifizierten Admin-Workflow; Plugin-Dateien oder Konfigurationen dürfen nicht aus dem Browser heraus direkt verändert werden.
