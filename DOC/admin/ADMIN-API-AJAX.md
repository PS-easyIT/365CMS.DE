> **Website:** [365CMS.DE](https://365cms.de/) | **Version:** 3.4.00
> **Datum:** 2026-09-06 | **Status:** Abgeschlossen – **Zuletzt aktualisiert am:** 2026-09-06
> **Kurzbeschreibung:** Gemeinsame Referenz für Admin-Routing, POST-Formulare, AJAX/REST-Aktionen, CSRF, Allowlists und sichere Redirects.

# 365CMS Admin – API and AJAX

## English

### Administrator guide

Use the documented `/admin/...` route instead of calling PHP files directly. Submit changes through the visible form or module action, verify the redirect result, and inspect the relevant audit or operational log when the action changes permissions, content, media, settings, or system state.

### Technical reference

`CMS/core/Routing/AdminRouter.php` and `CMS/core/Routing/ApiRouter.php` separate browser admin routes from API routes. `CMS/core/Auth.php`, `AuthManager.php`, and `Security.php` enforce authentication, capabilities, nonce/CSRF validation, input normalization, output escaping, and internal redirect allowlists. AJAX handlers follow the same contract and must not rely on hidden UI controls as authorization.

CRUD and settings actions delegate to service classes and prepared database operations. Upload endpoints delegate to `FileUploadService` and media services. Errors are returned as bounded admin notices or structured API errors; secrets and raw request payloads are not logged.

## Deutsch

### Anwenderleitfaden

Verwenden Sie die dokumentierte `/admin/...`-Route und rufen Sie keine PHP-Dateien direkt auf. Änderungen erfolgen über Formulare oder Modulaktionen; nach der Weiterleitung wird der Zielzustand geprüft. Bei Berechtigungs-, Inhalts-, Medien-, Settings- oder Systemänderungen ist der passende Audit- oder Betriebslog zu kontrollieren.

### Technische Referenz

`CMS/core/Routing/AdminRouter.php` und `CMS/core/Routing/ApiRouter.php` trennen Browser-Adminrouten und API-Routen. `CMS/core/Auth.php`, `AuthManager.php` und `Security.php` erzwingen Authentifizierung, Capabilities, Nonce-/CSRF-Prüfung, Eingabenormalisierung, Escaping und interne Redirect-Allowlists. AJAX-Handler folgen demselben Vertrag; versteckte UI-Elemente sind keine Autorisierung.

CRUD- und Settings-Aktionen delegieren an Services und vorbereitete Datenbankoperationen. Upload-Endpunkte verwenden `FileUploadService` und Media-Services. Fehler werden als begrenzte Adminmeldung oder strukturierter API-Fehler ausgegeben; Secrets und rohe Requestdaten werden nicht geloggt.
