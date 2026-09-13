# 365CMS – Projektdokumentation | Abschnitt: Admin – Plugin Marketplace
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

The plugin marketplace is exposed at `/admin/plugin-marketplace`. Its current implementation is `CMS/admin/modules/plugins/PluginMarketplaceModule.php` with the entry point `CMS/admin/plugin-marketplace.php` and the marketplace view under `CMS/admin/views/plugins/marketplace.php`.

Marketplace operations must use the module's validated forms and provider integration. Review package identity, source, compatibility, and the displayed result before enabling or installing anything. A failed provider operation is an explicit error, not a successful installation.

## Deutsch

Der Plugin Marketplace ist unter `/admin/plugin-marketplace` erreichbar. Die aktuelle Implementierung ist `CMS/admin/modules/plugins/PluginMarketplaceModule.php`; der Einstieg liegt in `CMS/admin/plugin-marketplace.php`, die Ansicht unter `CMS/admin/views/plugins/marketplace.php`.

Marketplace-Aktionen müssen die geprüften Formulare des Moduls und die Provider-Integration verwenden. Prüfen Sie Paketidentität, Quelle, Kompatibilität und Ergebnis, bevor etwas aktiviert oder installiert wird. Ein fehlgeschlagener Provider-Vorgang ist ein Fehler und keine erfolgreiche Installation.
