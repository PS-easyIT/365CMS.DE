# 365CMS – Projektdokumentation | Abschnitt: Subscription
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

### Scope
This section documents the administrator screens for packages, orders, and subscription settings. Use the links exposed by the capability-aware admin sidebar; do not construct request URLs manually.

### Supported screens
| Area | Admin route | Current implementation |
|---|---|---|
| Packages | `/admin/packages` | `CMS/admin/modules/subscriptions/PackagesModule.php`, `CMS/admin/packages.php` |
| Orders | `/admin/orders` | `CMS/admin/modules/subscriptions/OrdersModule.php`, `CMS/admin/orders.php` |
| Settings | `/admin/subscription-settings` | `CMS/admin/views/subscriptions/settings.php` |

### Safe workflow
Review status and filters, change only the required record, submit through the page, and verify the redirected result. Destructive or bulk operations require a recent backup and an audit-log review. Authentication, capability, CSRF/nonce, allowlisted fields, server-side validation, prepared queries, and escaped output are mandatory.

### Shared implementation
Admin routing is handled by `CMS/core/Routing/AdminRouter.php` and `CMS/core/Router.php`. Shared layout and navigation are in `CMS/admin/partials/`; settings persistence uses `CMS/core/Services/SettingsService.php`; audit and operational events use `CMS/core/AuditLogger.php` and `CMS/core/Logger.php`.

## Deutsch

### Umfang
Dieser Abschnitt beschreibt die Admin-Seiten für Pakete, Bestellungen und Abonnement-Einstellungen. Verwenden Sie die capability-gesteuerte Admin-Navigation und bauen Sie keine Request-URLs selbst.

### Unterstützte Seiten
| Bereich | Admin-Route | Aktuelle Implementierung |
|---|---|---|
| Pakete | `/admin/packages` | `CMS/admin/modules/subscriptions/PackagesModule.php`, `CMS/admin/packages.php` |
| Bestellungen | `/admin/orders` | `CMS/admin/modules/subscriptions/OrdersModule.php`, `CMS/admin/orders.php` |
| Einstellungen | `/admin/subscription-settings` | `CMS/admin/views/subscriptions/settings.php` |

### Sicherer Ablauf
Status und Filter prüfen, nur den benötigten Datensatz ändern, über die Seite speichern und das Ergebnis nach der Weiterleitung prüfen. Lösch- und Sammelaktionen erfordern ein aktuelles Backup und eine Audit-Prüfung. Authentifizierung, Capability, CSRF/Nonce, Allowlists, serverseitige Validierung, vorbereitete Abfragen und kontextgerechtes Escaping sind Pflicht.

### Gemeinsame Implementierung
Das Routing übernehmen `CMS/core/Routing/AdminRouter.php` und `CMS/core/Router.php`. Layout und Navigation liegen in `CMS/admin/partials/`; Einstellungen werden über `CMS/core/Services/SettingsService.php` persistiert; Audit- und Betriebsereignisse verwenden `CMS/core/AuditLogger.php` und `CMS/core/Logger.php`.
