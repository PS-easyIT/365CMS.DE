# 365CMS – Projektdokumentation | Abschnitt: Dashboard widgets
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English
### Purpose
Member dashboard widget visibility and order are managed at `/admin/member-dashboard-widgets`.

### Implementation
- Entry/view: `CMS/admin/member-dashboard-widgets.php`, `CMS/admin/views/member/widgets.php`
- Module: `CMS/admin/modules/member/MemberDashboardModule.php`
- Core registry: `CMS/core/Member/PluginDashboardRegistry.php`

### Administration
Change only registered widget identifiers. Check capability and CSRF/nonce, validate order and visibility server-side, and verify the dashboard after redirect.

## Deutsch
### Zweck
Sichtbarkeit und Reihenfolge der Member-Dashboard-Widgets werden unter `/admin/member-dashboard-widgets` verwaltet.

### Implementierung
- Einstieg/View: `CMS/admin/member-dashboard-widgets.php`, `CMS/admin/views/member/widgets.php`
- Modul: `CMS/admin/modules/member/MemberDashboardModule.php`
- Core-Registry: `CMS/core/Member/PluginDashboardRegistry.php`

### Administration
Nur registrierte Widget-IDs ändern. Capability und CSRF/Nonce prüfen, Reihenfolge und Sichtbarkeit serverseitig validieren und das Dashboard nach der Weiterleitung kontrollieren.
