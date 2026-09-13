# 365CMS – Projektdokumentation | Abschnitt: Orders
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English
### Purpose
The orders screen lists and manages subscription orders. Open it through the capability-aware admin navigation at `/admin/orders`.

### Implementation
- Entry point: `CMS/admin/orders.php`
- Module: `CMS/admin/modules/subscriptions/OrdersModule.php`
- View: `CMS/admin/views/subscriptions/orders.php`
- Shared routing/layout: `CMS/core/Routing/AdminRouter.php`, `CMS/core/Router.php`, `CMS/admin/partials/`

### Operating rules
Use the supplied filters and actions, confirm the target before a state change, and verify the post-redirect result. Handlers must authenticate the administrator, check capability and CSRF/nonce, validate allowlisted values, use prepared database operations, and escape output. Audit significant changes with `CMS/core/AuditLogger.php`.

## Deutsch
### Zweck
Die Bestellseite listet und verwaltet Abonnement-Bestellungen. Öffnen Sie sie über die capability-gesteuerte Admin-Navigation unter `/admin/orders`.

### Implementierung
- Einstieg: `CMS/admin/orders.php`
- Modul: `CMS/admin/modules/subscriptions/OrdersModule.php`
- View: `CMS/admin/views/subscriptions/orders.php`
- Gemeinsames Routing/Layout: `CMS/core/Routing/AdminRouter.php`, `CMS/core/Router.php`, `CMS/admin/partials/`

### Betriebsregeln
Verwenden Sie Filter und Aktionen der Seite, bestätigen Sie das Ziel vor Änderungen und prüfen Sie das Ergebnis nach der Weiterleitung. Handler müssen Authentifizierung, Capability und CSRF/Nonce prüfen, Allowlists validieren, vorbereitete Datenbankoperationen nutzen und Ausgaben escapen. Wichtige Änderungen werden über `CMS/core/AuditLogger.php` protokolliert.
