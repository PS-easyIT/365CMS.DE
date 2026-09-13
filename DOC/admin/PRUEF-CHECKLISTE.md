# 365CMS – Projektdokumentation | Abschnitt: Admin – Prüf-Checkliste
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

Use this checklist when reviewing an admin change:

- [ ] The documented route resolves through `CMS/core/Routing/AdminRouter.php`.
- [ ] The entry file, module, and view referenced by the documentation exist.
- [ ] Read operations remain read-only.
- [ ] State changes validate authentication, capability, CSRF token, action, and input.
- [ ] Database writes use the existing service/repository boundary and prepared statements.
- [ ] Errors are visible and bounded; no silent success fallback is introduced.
- [ ] Secrets and unnecessary personal data are excluded from output and logs.

## Deutsch

Diese Checkliste dient zur Prüfung einer Admin-Änderung:

- [ ] Die dokumentierte Route wird über `CMS/core/Routing/AdminRouter.php` aufgelöst.
- [ ] Verlinkter Einstieg, Modul und View existieren.
- [ ] Leseoperationen bleiben schreibgeschützt.
- [ ] Änderungen prüfen Authentifizierung, Capability, CSRF-Token, Aktion und Eingaben.
- [ ] Datenbankschreibvorgänge bleiben an der vorhandenen Service-/Repository-Grenze und nutzen vorbereitete Statements.
- [ ] Fehler werden sichtbar und begrenzt behandelt; kein stiller Erfolgs-Fallback.
- [ ] Secrets und unnötige personenbezogene Daten fehlen in Ausgabe und Logs.
