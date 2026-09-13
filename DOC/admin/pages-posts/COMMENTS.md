# 365CMS – Projektdokumentation | Abschnitt: Admin – Kommentare
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

Comment administration is exposed at `/admin/comments` by `CMS/admin/comments.php` and `CMS/admin/modules/comments/CommentsModule.php`. The module normalizes filters and actions before querying or changing comment data and requires the `comments.view` capability for access.

Use the list and action controls supplied by the admin screen. Read requests are used for viewing and filtering; state changes must pass the shared admin authentication, capability, CSRF, and input-validation checks.

## Deutsch

Die Kommentarverwaltung ist unter `/admin/comments` erreichbar und wird durch `CMS/admin/comments.php` sowie `CMS/admin/modules/comments/CommentsModule.php` implementiert. Das Modul normalisiert Filter und Aktionen vor dem Lesen oder Ändern und verlangt für den Zugriff die Capability `comments.view`.

Verwenden Sie die Listen- und Aktionssteuerung der Admin-Seite. Leseanfragen dienen Anzeige und Filterung; Änderungen müssen Authentifizierung, Capability, CSRF- und Eingabeprüfungen des Admin-Systems passieren.
