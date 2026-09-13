# 365CMS – Projektdokumentation | Abschnitt: Admin – API und AJAX
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

Browser administration is routed by `CMS/core/Routing/AdminRouter.php`; API requests use `CMS/core/Routing/ApiRouter.php`. Both paths must enforce authentication, capabilities, normalized input, and context-appropriate output handling.

AJAX is not an authorization boundary. Handlers must validate the request server-side, including CSRF protection for state-changing browser requests. Services and repositories own persistence, uploads use `FileUploadService`, and failures return an explicit admin notice or structured API error.

## Deutsch

Die Browser-Administration wird durch `CMS/core/Routing/AdminRouter.php` geroutet; API-Anfragen verwendet `CMS/core/Routing/ApiRouter.php`. Beide Wege müssen Authentifizierung, Capabilities, normalisierte Eingaben und kontextgerechte Ausgabe erzwingen.

AJAX ist keine Autorisierungsgrenze. Handler prüfen Anfragen serverseitig einschließlich CSRF-Schutz bei zustandsändernden Browseranfragen. Services und Repositories besitzen die Persistenz, Uploads laufen über `FileUploadService`, und Fehler werden als explizite Adminmeldung oder strukturierter API-Fehler ausgegeben.
