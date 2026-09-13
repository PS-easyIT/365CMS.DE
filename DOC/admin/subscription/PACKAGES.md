# 365CMS – Projektdokumentation | Abschnitt: Packages
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English
### Purpose
The packages screen maintains the subscription packages offered by the site at `/admin/packages`.

### Implementation
- Entry point: `CMS/admin/packages.php`
- Module: `CMS/admin/modules/subscriptions/PackagesModule.php`
- View: `CMS/admin/views/subscriptions/packages.php`
- Shared services: `CMS/core/Services/SettingsService.php`, `CMS/core/AuditLogger.php`

### Operating rules
Review existing package data before editing. Submit changes through the page; capability, CSRF/nonce, field allowlists, validation, prepared queries, and escaped output are required. Verify the redirected state and retain a backup before destructive or bulk changes.

## Deutsch
### Zweck
Die Paketverwaltung pflegt die auf der Website angebotenen Abonnement-Pakete unter `/admin/packages`.

### Implementierung
- Einstieg: `CMS/admin/packages.php`
- Modul: `CMS/admin/modules/subscriptions/PackagesModule.php`
- View: `CMS/admin/views/subscriptions/packages.php`
- Gemeinsame Services: `CMS/core/Services/SettingsService.php`, `CMS/core/AuditLogger.php`

### Betriebsregeln
Prüfen Sie vorhandene Paketdaten vor der Bearbeitung. Änderungen erfolgen über die Seite; Capability, CSRF/Nonce, Feld-Allowlist, Validierung, vorbereitete Abfragen und Escaping sind erforderlich. Prüfen Sie den Zustand nach der Weiterleitung und sichern Sie vor Lösch- oder Sammeländerungen.
