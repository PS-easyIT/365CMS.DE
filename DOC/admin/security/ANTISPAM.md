# 365CMS – Projektdokumentation | Abschnitt: Admin – Antispam
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

Antispam administration is exposed at `/admin/antispam`. The entry point is `CMS/admin/antispam.php`; the current implementation is `CMS/admin/modules/security/AntispamModule.php` with `CMS/admin/views/security/antispam.php`.

Use the module's controls to review and change antispam settings. Requests must remain authenticated, capability-checked, CSRF-protected, and validated before settings are persisted. Review warnings and recent security events after a change.

## Deutsch

Die Antispam-Verwaltung ist unter `/admin/antispam` erreichbar. Der Einstieg liegt in `CMS/admin/antispam.php`; die aktuelle Implementierung besteht aus `CMS/admin/modules/security/AntispamModule.php` und `CMS/admin/views/security/antispam.php`.

Verwenden Sie die Steuerungen des Moduls zum Prüfen und Ändern der Antispam-Einstellungen. Anfragen müssen authentifiziert, capability-geprüft, CSRF-geschützt und vor der Speicherung validiert werden. Nach Änderungen Warnungen und aktuelle Sicherheitsereignisse prüfen.
