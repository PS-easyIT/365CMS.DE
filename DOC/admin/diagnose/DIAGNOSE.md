> **Website:** [365CMS.DE](https://365cms.de/) | **Version:** 3.4.00
> **Datum:** 2026-09-06 | **Status:** Abgeschlossen – **Zuletzt aktualisiert am:** 2026-09-06
> **Kurzbeschreibung:** Administrator guide and technical reference for operations, logs, and monitoring. It reflects the implementation in the current `CMS/admin` tree and its core interfaces.

# 365CMS Admin – Diagnose

## English

### Administrator guide

This document covers operations, logs, and monitoring. Open `/admin/diagnose` after signing in through the CMS admin entry point. The sidebar is capability-aware; a missing menu item means that the current user, module state, or feature gate does not permit the operation.

Use the page in this order:

1. Review the current status, filters, and warnings before changing data.
2. Make the smallest required change and use the supplied form controls rather than crafting requests manually.
3. Save through the page action, wait for the Post/Redirect/Get response, and verify the resulting state.
4. For destructive, security-sensitive, or bulk operations, confirm the target, keep a recent backup, and review the audit or operational log.

Empty results, unavailable optional modules, and service errors are displayed as safe empty or warning states. They do not grant additional access and should be investigated through the linked system or log page.

### Technical reference

**Entry, routing, and views.** The PHP entry points live below `CMS/admin/`; `CMS/core/Routing/AdminRouter.php` and `CMS/core/Router.php` resolve the friendly `/admin/...` paths. Shared layout, navigation, flash messages, and request shells are in `CMS/admin/partials/`; rendered screens are in `CMS/admin/views/`. The implementation files relevant to this document are `CMS/admin/diagnose.php`, `CMS/admin/views/system/diagnose.php`.

**Authentication and CSRF.** `CMS/core/Auth.php` and `CMS/core/Auth/AuthManager.php` establish the authenticated administrator and capability checks. Every state-changing form must use the shared admin nonce/CSRF contract from the admin shell; handlers validate the token, capability, action, and normalized input before writing. GET requests are read-only, and successful POST requests redirect to an internal allowlisted admin path.

**Settings, persistence, and CRUD.** Settings are read and written through `CMS/core/Services/SettingsService.php` (with domain stores where present). CRUD handlers use the core database and service layer, prepared statements, explicit allowlists, and server-side validation. Views do not own persistence logic. Optional modules fail closed when disabled.

**APIs, AJAX, uploads, and media.** Admin actions may expose WordPress AJAX or REST-compatible handlers registered by the corresponding module. Requests require authentication, capability, CSRF protection where applicable, and strict parameter validation. Uploads are delegated to `CMS/core/Services/FileUploadService.php` and media services; MIME, size, ownership, and destination checks run before storage. Returned URLs and HTML are escaped for their output context.

**Logs and monitoring.** Security and business events use `CMS/core/AuditLogger.php`; operational diagnostics use `CMS/core/Logger.php` and the monitoring services. Secrets, tokens, raw prompts, and unnecessary personal data are excluded from UI and logs. A degraded dependency must produce a bounded warning or fallback, never an unhandled fatal response.

**Modules, legacy routes, and fallbacks.** Feature classes under `CMS/admin/modules/` register the current module screens and hooks. Older PHP entry files remain compatibility shims where present; prefer the documented friendly route and the current module/view. When a module or optional data source is unavailable, the page keeps its shell, reports the condition, and links to the canonical diagnostic or log route.

## Deutsch

### Anwenderleitfaden

Dieses Dokument beschreibt Betrieb, Protokolle und Überwachung. Öffnen Sie nach der Anmeldung über den Admin-Einstieg die Route `/admin/diagnose`. Die Sidebar berücksichtigt Capabilities; ein fehlender Menüpunkt bedeutet, dass Benutzer, Modulstatus oder Feature-Gate den Vorgang nicht erlauben.

Empfohlener Ablauf:

1. Status, Filter und Warnungen vor Änderungen prüfen.
2. Nur die notwendige Änderung über die vorhandenen Formulare durchführen.
3. Speichern, die Weiterleitung nach POST abwarten und den Zielzustand kontrollieren.
4. Vor Lösch-, Sicherheits- oder Sammelaktionen Ziel, Backup und Audit- beziehungsweise Betriebslog prüfen.

Leere Ergebnisse, deaktivierte optionale Module und Dienstfehler erscheinen als sichere Leer- oder Warnzustände. Sie erweitern keine Berechtigungen; die Ursache ist über die verlinkte System- oder Logseite zu prüfen.

### Technische Referenz

**Einstieg, Routing und Views.** Die PHP-Einstiege liegen unter `CMS/admin/`; `CMS/core/Routing/AdminRouter.php` und `CMS/core/Router.php` lösen die sprechenden `/admin/...`-Pfade auf. Gemeinsames Layout, Navigation, Flash-Meldungen und Request-Shells liegen in `CMS/admin/partials/`, die Bildschirme in `CMS/admin/views/`. Für dieses Dokument maßgeblich sind `CMS/admin/diagnose.php`, `CMS/admin/views/system/diagnose.php`.

**Authentifizierung und CSRF.** `CMS/core/Auth.php` und `CMS/core/Auth/AuthManager.php` stellen den angemeldeten Administrator und Capability-Prüfungen bereit. Zustandsändernde Formulare verwenden den gemeinsamen Admin-Nonce-/CSRF-Vertrag; Handler prüfen Token, Capability, Aktion und normalisierte Eingaben vor jedem Schreiben. GET bleibt lesend, erfolgreiche POST-Anfragen leiten auf einen internen Allowlist-Adminpfad weiter.

**Settings, Persistenz und CRUD.** Einstellungen laufen über `CMS/core/Services/SettingsService.php` und vorhandene Fachdienste. CRUD nutzt Core-Datenbank und Services, vorbereitete Statements, Allowlists und serverseitige Validierung. Views enthalten keine Persistenzlogik. Deaktivierte optionale Module bleiben geschlossen.

**APIs, AJAX, Uploads und Medien.** Admin-Aktionen können WordPress-AJAX- oder REST-kompatible Handler registrieren. Authentifizierung, Capability, gegebenenfalls CSRF und strenge Parameterprüfung sind erforderlich. Uploads laufen über `CMS/core/Services/FileUploadService.php` und Media-Services; MIME-Typ, Größe, Besitz und Ziel werden vor dem Speichern geprüft. URLs und HTML werden kontextgerecht escaped.

**Logs und Monitoring.** Sicherheits- und Fachereignisse schreiben über `CMS/core/AuditLogger.php`; Betriebsdiagnosen verwenden `CMS/core/Logger.php` und Monitoring-Services. Geheimnisse, Tokens, Rohprompts und unnötige personenbezogene Daten bleiben aus UI und Logs heraus. Abhängigkeitfehler werden begrenzt als Warnung oder Fallback behandelt.

**Module, Legacy-Routen und Fallbacks.** Aktuelle Modulklassen unter `CMS/admin/modules/` registrieren Screens und Hooks. Ältere PHP-Einstiege sind, sofern vorhanden, Kompatibilitätsschichten; bevorzugt wird die dokumentierte sprechende Route mit aktuellem Modul/View. Bei deaktiviertem Modul oder fehlender Datenquelle bleibt die Shell renderbar und verweist auf Diagnose oder Logs.
