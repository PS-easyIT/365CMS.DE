# 365CMS – Projektdokumentation | Abschnitt: Admin – Inhalts-Einstellungen
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

Content-related settings are handled through the settings modules and `CMS/core/Services/SettingsService.php`. Current admin entry points include `CMS/admin/ai-settings.php`, `CMS/admin/design-settings.php`, and `CMS/admin/mail-settings.php`; related modules live below `CMS/admin/modules/`.

The settings service is the persistence boundary. Forms must submit normalized, allowlisted values with the shared admin CSRF contract. Feature-specific settings remain unavailable when their module is disabled.

## Deutsch

Inhaltsbezogene Einstellungen werden durch die Settings-Module und `CMS/core/Services/SettingsService.php` verarbeitet. Aktuelle Admin-Einstiege sind unter anderem `CMS/admin/ai-settings.php`, `CMS/admin/design-settings.php` und `CMS/admin/mail-settings.php`; die zugehörigen Module liegen unter `CMS/admin/modules/`.

Der Settings-Service bildet die Persistenzgrenze. Formulare müssen normalisierte, erlaubte Werte mit dem gemeinsamen Admin-CSRF-Vertrag senden. Fachspezifische Einstellungen bleiben bei deaktiviertem Modul nicht verfügbar.
