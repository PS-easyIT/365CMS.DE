# 365CMS – Projektdokumentation | Abschnitt: Admin – Analytics
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

Analytics administration is provided by `CMS/admin/modules/seo/AnalyticsModule.php` and the view `CMS/admin/views/seo/analytics.php`. The module exposes aggregated analytics data through `getData()`; the exact UI route is the admin page registered by the current bootstrap.

Analytics values are diagnostic data. Review source availability and warning states before interpreting an empty result. Do not treat the admin view as a public analytics endpoint.

## Deutsch

Die Analytics-Administration wird durch `CMS/admin/modules/seo/AnalyticsModule.php` und die View `CMS/admin/views/seo/analytics.php` bereitgestellt. Das Modul liefert über `getData()` aggregierte Analytics-Daten; die konkrete UI-Route wird durch den aktuellen Bootstrap als Admin-Seite registriert.

Analytics-Werte dienen der Diagnose. Vor der Interpretation leerer Ergebnisse müssen Quellenverfügbarkeit und Warnungen geprüft werden. Die Admin-Ansicht ist kein öffentlicher Analytics-Endpunkt.
