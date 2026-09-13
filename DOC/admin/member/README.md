# 365CMS – Projektdokumentation | Abschnitt: Admin – Member Dashboard
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

The member-dashboard administration is implemented by `CMS/admin/member-dashboard.php` and the related compatibility entry points `CMS/admin/member-dashboard-*.php`. The current module is `CMS/admin/modules/member/MemberDashboardModule.php`.

The module exposes section-based administration for dashboard settings, design, notifications, onboarding, profile fields, widgets, and plugin widgets. Each section is capability-gated by the module; a missing section is an authorization or feature-state result, not an indication that the route should be bypassed.

Changes must be submitted through the admin forms. The module normalizes settings, records failures through the existing logging path, and returns bounded fallback data when optional dashboard sources are unavailable.

## Deutsch

Die Member-Dashboard-Administration wird durch `CMS/admin/member-dashboard.php` und die Kompatibilitätseinstiege `CMS/admin/member-dashboard-*.php` bereitgestellt. Das aktuelle Modul ist `CMS/admin/modules/member/MemberDashboardModule.php`.

Das Modul verwaltet bereichsbezogen Dashboard-Einstellungen, Design, Benachrichtigungen, Onboarding, Profilfelder, Widgets und Plugin-Widgets. Jeder Bereich ist durch das Modul capability-geschützt; ein fehlender Bereich ist ein Berechtigungs- oder Feature-Status und kein Anlass, die Route zu umgehen.

Änderungen werden ausschließlich über die Admin-Formulare gespeichert. Das Modul normalisiert Einstellungen, protokolliert Fehler über den vorhandenen Logging-Pfad und liefert bei nicht verfügbaren optionalen Quellen begrenzte Fallback-Daten.
