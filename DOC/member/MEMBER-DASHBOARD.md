# 365CMS Member Dashboard
> **Website:** 365CMS.DE | **Version:** 3.4.00 | **Date:** 2026-09-06 | **Status:** Current | **Last updated:** 2026-09-06

This document describes the dashboard settings consumed by the member runtime. It separates the member-facing experience from the administrator configuration.

## User guide

The dashboard can show a greeting, quick start, statistics, core widgets, custom information widgets, plugin widgets, onboarding, notifications, profile completion, and subscription information. Administrators can reorder supported sections and hide optional modules.

## Technical reference

Runtime settings include `dashboard_enabled`, `widgets`, `profile_fields`, `required_profile_fields`, `custom_profile_fields`, `dashboard_columns`, `dashboard_greeting`, `show_welcome`, `subscription_visible`, `custom_widgets`, `custom_widget_order`, `frontend_modules`, `notifications`, `onboarding`, and `plugin_widget_order`.

The controller applies defaults, validates colors and links, removes unknown ordering values, and keeps missing optional widgets fail-soft. Plugin widgets are supplied through `PluginDashboardRegistry` and may be filtered with `member_dashboard_widgets`. Menu extensions use `member_menu_items`.

---

# 365CMS Member-Dashboard
> **Website:** 365CMS.DE | **Version:** 3.4.00 | **Datum:** 2026-09-06 | **Status:** Aktuell | **Zuletzt aktualisiert:** 2026-09-06

Dieses Dokument beschreibt die vom Member-Frontend verwendeten Dashboard-Einstellungen. Es trennt die Mitgliederansicht von der Administrationskonfiguration.

## Anwenderleitfaden

Das Dashboard kann Begrüßung, Schnellstart, Statistiken, Kern-Widgets, eigene Info-Widgets, Plugin-Widgets, Onboarding, Benachrichtigungen, Profilfortschritt und Abonnementinformationen anzeigen. Administratoren können unterstützte Bereiche sortieren und optionale Module ausblenden.

## Technische Referenz

Die Runtime-Einstellungen umfassen die im englischen Abschnitt genannten Schlüssel. Der Controller ergänzt Defaults, validiert Farben und Links, entfernt unbekannte Sortierwerte und behandelt fehlende optionale Widgets fail-soft. Plugin-Widgets werden über `PluginDashboardRegistry` geliefert und können mit `member_dashboard_widgets` gefiltert werden; Menüerweiterungen verwenden `member_menu_items`.
