# 365CMS – Projektdokumentation | Abschnitt: Admin – Panel-Integration
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

Admin panels use the shared router, authentication, navigation, flash-message, and view-shell conventions. Feature modules provide their own data and actions; they should not duplicate the global admin shell.

The integration boundary is `CMS/core/Routing/AdminRouter.php`, `CMS/admin/partials/`, `CMS/admin/modules/`, and `CMS/admin/views/`. A module must keep authorization in the server-side handler and return an explicit error or bounded fallback when a dependency is unavailable.

## Deutsch

Admin-Panels verwenden die gemeinsamen Konventionen für Routing, Authentifizierung, Navigation, Flash-Meldungen und View-Shells. Fachmodule liefern ihre Daten und Aktionen selbst und duplizieren die globale Admin-Shell nicht.

Die Integrationsgrenze bilden `CMS/core/Routing/AdminRouter.php`, `CMS/admin/partials/`, `CMS/admin/modules/` und `CMS/admin/views/`. Autorisierung bleibt im serverseitigen Handler; bei fehlenden Abhängigkeiten liefert ein Modul einen expliziten Fehler oder begrenzten Fallback.
