# 365CMS Member Dashboard Administration
> **Website:** 365CMS.DE | **Version:** 3.4.00 | **Date:** 2026-09-06 | **Status:** Current | **Last updated:** 2026-09-06

This document describes the administrator controls for the member dashboard. It distinguishes configuration and preview from the private member runtime.

## User guide

Administrators open `/admin/member-dashboard` to configure the member experience. The sections cover general settings, widgets, profile fields, design, frontend modules, notifications, onboarding, and plugin widgets.

The preview at `/admin/member-dashboard?preview=1` is read-only. It uses the same normalized settings as the member runtime but renders example data and does not create a write action or token in the URL.

## Technical reference

The entry point is `CMS/admin/member-dashboard.php`; shared section handling is in `CMS/admin/member-dashboard-page.php`. Access requires an administrator plus `manage_settings` or `manage_users` for the overview. Section-specific capabilities are allowlisted.

Writes use action `save` and CSRF context `admin_member_dashboard`. Settings are normalized server-side, including widget order, colors, plugin visibility, profile-field definitions, and action URLs. The admin UI supports drag-and-drop ordering with button fallbacks; GET requests never mutate settings.

---

# 365CMS-Administration des Member-Dashboards
> **Website:** 365CMS.DE | **Version:** 3.4.00 | **Datum:** 2026-09-06 | **Status:** Aktuell | **Zuletzt aktualisiert:** 2026-09-06

Dieses Dokument beschreibt die Administrationssteuerung des Member-Dashboards. Es trennt Konfiguration und Vorschau sauber von der privaten Member-Runtime.

## Anwenderleitfaden

Administratoren öffnen `/admin/member-dashboard`, um den Mitgliederbereich zu konfigurieren. Die Bereiche umfassen Allgemeines, Widgets, Profilfelder, Design, Frontend-Module, Benachrichtigungen, Onboarding und Plugin-Widgets.

Die Vorschau unter `/admin/member-dashboard?preview=1` ist schreibgeschützt. Sie verwendet dieselben normalisierten Einstellungen wie die Runtime, zeigt aber Beispieldaten und erzeugt weder Schreibaktionen noch Tokens in der URL.

## Technische Referenz

Der Einstieg liegt in `CMS/admin/member-dashboard.php`; die gemeinsame Abschnittsverarbeitung in `CMS/admin/member-dashboard-page.php`. Für die Übersicht benötigt ein Administrator `manage_settings` oder `manage_users`; die Bereichsrechte sind als Allowlist definiert.

Schreibvorgänge verwenden die Aktion `save` und den CSRF-Kontext `admin_member_dashboard`. Einstellungen wie Widget-Reihenfolge, Farben, Plugin-Sichtbarkeit, Profilfelddefinitionen und Aktions-URLs werden serverseitig normalisiert. Die Oberfläche unterstützt Drag-and-drop mit Pfeilbuttons als Fallback; GET-Aufrufe ändern niemals Einstellungen.
