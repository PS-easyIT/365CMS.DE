# 365CMS – Projektdokumentation | Abschnitt: Admin – Einstieg
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

The 365CMS administration starts at `/admin`. `CMS/core/Routing/AdminRouter.php` registers the dashboard, page, log-section, and plugin-page route patterns. Every admin request requires an authenticated administrator.

### Runtime layout

| Responsibility | Location |
|---|---|
| Router registration and dispatch | `CMS/core/Routing/AdminRouter.php`, `CMS/core/Router.php` |
| Admin entry points and compatibility shims | `CMS/admin/` |
| Feature modules | `CMS/admin/modules/` |
| Views and shared shells | `CMS/admin/views/`, `CMS/admin/partials/` |
| Authentication and capabilities | `CMS/core/Auth.php`, `CMS/core/Auth/AuthManager.php` |
| Settings and domain services | `CMS/core/Services/` |

Use the friendly route and the supplied forms. Do not call PHP entry files directly or treat hidden UI controls as authorization. State-changing requests must pass the module's authentication, capability, CSRF, and input-validation checks.

## Deutsch

Die 365CMS-Administration beginnt unter `/admin`. `CMS/core/Routing/AdminRouter.php` registriert Dashboard-, Seiten-, Log-Abschnitts- und Plugin-Seiten-Muster. Jede Admin-Anfrage erfordert einen authentifizierten Administrator.

### Runtime-Struktur

| Zuständigkeit | Pfad |
|---|---|
| Routing und Dispatch | `CMS/core/Routing/AdminRouter.php`, `CMS/core/Router.php` |
| Admin-Einstiege und Kompatibilität | `CMS/admin/` |
| Fachmodule | `CMS/admin/modules/` |
| Views und gemeinsame Shells | `CMS/admin/views/`, `CMS/admin/partials/` |
| Authentifizierung und Capabilities | `CMS/core/Auth.php`, `CMS/core/Auth/AuthManager.php` |
| Settings und Fachdienste | `CMS/core/Services/` |

Verwenden Sie die sprechenden Routen und vorhandenen Formulare. PHP-Einstiege dürfen nicht direkt aufgerufen werden; ausgeblendete UI-Elemente sind keine Autorisierung. Zustandsändernde Anfragen müssen Authentifizierung, Capability-, CSRF- und Eingabeprüfungen des jeweiligen Moduls passieren.
