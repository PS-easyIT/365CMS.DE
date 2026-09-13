# 365CMS – Projektdokumentation | Abschnitt: Admin – Security Audit
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

Security-audit information is available at `/admin/security-audit`. The related entries are `CMS/admin/security-audit.php` and `CMS/admin/logs-security-audit.php`; the current module is `CMS/admin/modules/security/SecurityAuditModule.php`. Audit views are under `CMS/admin/views/security/` and `CMS/admin/views/logs/`.

The module reads bounded audit data, sanitizes displayed details, and reports operational failures without exposing secrets. Use the audit view for review; it is not a substitute for access control or a complete external security assessment.

## Deutsch

Sicherheits-Audit-Informationen sind unter `/admin/security-audit` verfügbar. Zugehörige Einstiege sind `CMS/admin/security-audit.php` und `CMS/admin/logs-security-audit.php`; das aktuelle Modul ist `CMS/admin/modules/security/SecurityAuditModule.php`. Audit-Ansichten liegen unter `CMS/admin/views/security/` und `CMS/admin/views/logs/`.

Das Modul liest begrenzte Audit-Daten, bereinigt angezeigte Details und meldet Betriebsfehler ohne Geheimnisse offenzulegen. Die Audit-Ansicht dient der Prüfung; sie ersetzt weder Zugriffskontrolle noch eine vollständige externe Sicherheitsbewertung.
