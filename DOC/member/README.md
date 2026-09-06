> **Website:** [365CMS.DE](https://365cms.de/) | **Version:** 3.4.00
> **Datum:** 2026-09-06 | **Status:** Abgeschlossen – **Zuletzt aktualisiert am:** 2026-09-06
> **Kurzbeschreibung:** Vollständige Referenz des privaten Member-Bereichs mit Seiten, Routing, Einstellungen, Erweiterungspunkten, Sicherheit und Fallbacks. Die Angaben entsprechen dem Code-Stand von Version 3.4.00.

# 365CMS Member Area

## English — user guide

The member area is the private, authenticated workspace for profile data, security, notifications, messages, media, favourites, privacy, subscriptions, and plugin features. Open `/member/dashboard` after signing in; `/member` is an alias that redirects to the dashboard. If the dashboard is disabled, the runtime redirects the member to `/member/profile`.

### Runtime pages

| Page | URL | What the member can do |
|---|---|---|
| Dashboard | `/member/dashboard` | View greeting, quick links, statistics, widgets, onboarding, notifications, profile completion, and plugin tiles |
| Profile | `/member/profile` | Edit account data and configured custom fields |
| Security | `/member/security` | Change password; configure TOTP/MFA, backup codes, passkeys, and sessions |
| Notifications | `/member/notifications` | Read the notification centre and save preferences |
| Messages | `/member/messages` | Read inbox/sent mail, open threads, and send messages |
| Media | `/member/media` | Browse the personal file tree, upload, rename, move, and delete where enabled |
| Favourites | `/member/favorites` | Review saved content |
| Privacy | `/member/privacy` | Manage privacy choices and submit data requests |
| Subscription | `/member/subscription` | View package, limits, renewal, and orders when the module is enabled |
| Plugin area | `/member/plugin/{slug}[/{action}[/{id}]]` | Use a registered plugin section |

All pages require a logged-in session. A failed authentication check redirects to the configured public login page and preserves the original request in `redirect`.

## English — technical reference

The runtime entry point is `CMS/member/includes/bootstrap.php`. It loads `CMS\MemberArea\MemberController`, calls `requireAuth()`, sends private cache headers, and exposes normalized settings to the page. Each page includes the shared header, sidebar, alerts, and footer; the footer fires `body_end` and `member_body_end`.

`CMS/core/Routing/MemberRouter.php` registers GET and POST routes for `/dashboard`, `/member`, `/member/:page`, and the three plugin route shapes. `/dashboard` requires a theme file at `member/dashboard.php`; otherwise it redirects to `/member/dashboard`. A page slug must match `[a-zA-Z0-9_-]+`. The resolver prefers the active theme's `member/{page}.php`, then falls back to `CMS/member/{page}.php`, and finally renders 404 while logging the miss.

The controller obtains settings from `MemberDashboardModule::getRuntimeSettings()` and combines them with safe defaults. It loads plugin widgets through `PluginDashboardRegistry`, applies `member_dashboard_widgets`, and adds registered menu entries through `member_menu_items`. Optional database tables, unknown widgets, missing plugin sections, failed statistics callbacks, and invalid settings fail soft rather than breaking the whole area.

Member data is scoped to the authenticated user. Media is rooted at `member/user-{id}`; normalized paths reject traversal. The subscription page is shown only when `subscription_visible` is enabled and `subscription_member_area` is active. The admin portal link is displayed only when `canAccessAdminPortal()` succeeds.

See [MEMBER-ROUTES.md](MEMBER-ROUTES.md), [MEMBER-DASHBOARD.md](MEMBER-DASHBOARD.md), and [MEMBER-SECURITY.md](MEMBER-SECURITY.md) for contracts and extension details.

---

# 365CMS Mitgliederbereich

## Deutsch — Anwenderblock

Der Member-Bereich ist der private, authentifizierte Arbeitsbereich für Profil, Sicherheit, Benachrichtigungen, Nachrichten, Medien, Favoriten, Datenschutz, Abonnements und Plugin-Funktionen. Nach der Anmeldung öffnet `/member/dashboard` das Dashboard; `/member` ist ein Alias und leitet dorthin weiter. Ist das Dashboard deaktiviert, leitet die Runtime auf `/member/profile` um.

### Runtime-Seiten

| Seite | URL | Zweck |
|---|---|---|
| Dashboard | `/member/dashboard` | Begrüßung, Schnelllinks, Statistiken, Widgets, Onboarding, Meldungen, Profilfortschritt und Plugin-Kacheln |
| Profil | `/member/profile` | Kontodaten und konfigurierte eigene Felder bearbeiten |
| Sicherheit | `/member/security` | Passwort, TOTP/MFA, Backup-Codes, Passkeys und Sitzungen verwalten |
| Benachrichtigungen | `/member/notifications` | Benachrichtigungen und Einstellungen |
| Nachrichten | `/member/messages` | Posteingang, Gesendet, Threads und neue Nachrichten |
| Medien | `/member/media` | Eigene Dateien und Ordner durchsuchen, hochladen, umbenennen, verschieben und löschen, sofern erlaubt |
| Favoriten | `/member/favorites` | Gespeicherte Inhalte prüfen |
| Datenschutz | `/member/privacy` | Datenschutzoptionen und Datenanfragen |
| Abonnement | `/member/subscription` | Paket, Limits, Verlängerung und Bestellungen bei aktivem Modul |
| Plugin-Bereich | `/member/plugin/{slug}[/{action}[/{id}]]` | Registrierte Plugin-Funktionen nutzen |

Alle Seiten benötigen eine eingeloggte Sitzung. Bei fehlender Authentifizierung erfolgt eine Weiterleitung zur konfigurierten öffentlichen Login-Seite mit der ursprünglichen Anfrage im Parameter `redirect`.

## Deutsch — Technikblock

Der Runtime-Einstieg ist `CMS/member/includes/bootstrap.php`. Er lädt `CMS\MemberArea\MemberController`, ruft `requireAuth()` auf, setzt private Cache-Header und stellt normalisierte Einstellungen für die Seite bereit. Gemeinsamer Header, Sidebar, Meldungen und Footer werden von jeder Seite verwendet; der Footer feuert `body_end` und `member_body_end`.

`CMS/core/Routing/MemberRouter.php` registriert GET- und POST-Routen für `/dashboard`, `/member`, `/member/:page` sowie die drei Plugin-Routen. `/dashboard` erwartet `member/dashboard.php` im aktiven Theme; fehlt die Datei, erfolgt eine Weiterleitung nach `/member/dashboard`. Seiten-Slugs müssen `[a-zA-Z0-9_-]+` entsprechen. Der Resolver bevorzugt das Theme, fällt danach auf `CMS/member/{page}.php` zurück und rendert bei Fehlen eine 404-Seite mit Logeintrag.

Die Steuerung liest `MemberDashboardModule::getRuntimeSettings()` und ergänzt sichere Defaults. Plugin-Widgets kommen aus `PluginDashboardRegistry`, der Filter `member_dashboard_widgets` wird angewendet und `member_menu_items` erweitert das Menü. Optionale Tabellen, unbekannte Widgets, fehlende Plugin-Bereiche, fehlerhafte Statistik-Callbacks und ungültige Einstellungen werden fail-soft behandelt.

Alle Mitgliedsdaten sind auf den authentifizierten Benutzer begrenzt. Medien liegen unter `member/user-{id}`; normalisierte Pfade verhindern Traversal. Das Abonnement erscheint nur bei aktivem `subscription_visible` und aktiviertem Modul `subscription_member_area`. Ein Admin-Portal-Link erscheint nur bei erfolgreichem `canAccessAdminPortal()`.

Details stehen in [MEMBER-ROUTES.md](MEMBER-ROUTES.md), [MEMBER-DASHBOARD.md](MEMBER-DASHBOARD.md) und [MEMBER-SECURITY.md](MEMBER-SECURITY.md).
