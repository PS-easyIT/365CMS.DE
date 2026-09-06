# 365CMS Member Area
> **Website:** 365CMS.DE | **Version:** 3.4.00 | **Date:** 2026-09-06 | **Status:** Current | **Last updated:** 2026-09-06

This document explains the logged-in member area for users and maintainers. It covers the available pages, dashboard behavior, authentication, media handling, and extension points.

## User guide

Members can use the dashboard, profile, security, notifications, messages, media, favorites, privacy, subscription, and installed-plugin areas. The area is private, requires an authenticated session, and redirects unauthenticated visitors to the configured login page.

The dashboard can be enabled or disabled by an administrator. Its welcome text, widgets, layout, onboarding panel, notification panel, profile fields, colors, and subscription visibility are centrally configurable.

### Available pages

| Page | URL | Purpose |
|---|---|---|
| Dashboard | `/member/dashboard` | Personal overview, activity, widgets, onboarding |
| Profile | `/member/profile` | Personal data and configured profile fields |
| Security | `/member/security` | Password, TOTP/MFA, passkeys, backup codes, sessions |
| Notifications | `/member/notifications` | Notification center and preferences |
| Messages | `/member/messages` | Internal conversations |
| Media | `/member/media` | Personal files and folders |
| Favorites | `/member/favorites` | Saved content |
| Privacy | `/member/privacy` | Privacy settings and data requests |
| Subscription | `/member/subscription` | Package, limits, renewal and orders |
| Plugin section | `/member/plugin/{slug}` | Member features supplied by plugins |

## Technical reference

The runtime entry point is `CMS/member/includes/bootstrap.php`. It creates the singleton `CMS\MemberArea\MemberController`, requires authentication, sends private cache headers, and loads normalized dashboard settings.

The controller reads runtime settings from `MemberDashboardModule::getRuntimeSettings()` and combines them with safe defaults. Dashboard settings are independent from the admin read capability; the `member_dashboard` module gate still controls whether the dashboard is active. Unknown widgets, unavailable plugins, and missing optional tables fail safely.

The member router is implemented by `CMS/core/Routing/MemberRouter.php`. It supports GET and POST requests, allows a theme to override `member/{page}.php`, falls back to `CMS/member/{page}.php`, validates page slugs, and records feature usage through `FeatureUsageService`.

### Security and data boundaries

- Every member page calls `MemberController::requireAuth()`.
- Form and action handlers use action-specific CSRF tokens such as `member_profile`, `member_media_action`, and `member_security_*`.
- Media paths are rooted at `member/user-{id}` and normalized to prevent traversal.
- Private responses receive private cache headers.
- Dashboard links and custom assets are normalized before output.
- Profile custom fields are allowlisted and type-validated before storage.

See [MEMBER-ROUTES.md](MEMBER-ROUTES.md), [MEMBER-SECURITY.md](MEMBER-SECURITY.md), and [MEMBER-DASHBOARD.md](MEMBER-DASHBOARD.md) for the detailed contracts.

---

# 365CMS Mitgliederbereich
> **Website:** 365CMS.DE | **Version:** 3.4.00 | **Datum:** 2026-09-06 | **Status:** Aktuell | **Zuletzt aktualisiert:** 2026-09-06

Dieses Dokument erklärt den eingeloggten Mitgliederbereich für Anwender und Maintainer. Es beschreibt Seiten, Dashboard-Verhalten, Authentifizierung, Medienverwaltung und Erweiterungspunkte.

## Anwenderleitfaden

Mitglieder können Dashboard, Profil, Sicherheit, Benachrichtigungen, Nachrichten, Medien, Favoriten, Datenschutz, Abonnement und installierte Plugin-Bereiche nutzen. Der Bereich ist privat, benötigt eine authentifizierte Sitzung und leitet nicht eingeloggte Besucher zur konfigurierten Login-Seite weiter.

Das Dashboard kann durch Administratoren aktiviert oder deaktiviert werden. Begrüßung, Widgets, Layout, Onboarding, Benachrichtigungen, Profilfelder, Farben und Sichtbarkeit des Abonnements werden zentral konfiguriert.

### Verfügbare Seiten

| Seite | URL | Zweck |
|---|---|---|
| Dashboard | `/member/dashboard` | Persönliche Übersicht, Aktivitäten, Widgets, Onboarding |
| Profil | `/member/profile` | Persönliche Daten und konfigurierte Profilfelder |
| Sicherheit | `/member/security` | Passwort, TOTP/MFA, Passkeys, Backup-Codes, Sitzungen |
| Benachrichtigungen | `/member/notifications` | Benachrichtigungszentrale und Einstellungen |
| Nachrichten | `/member/messages` | Interne Unterhaltungen |
| Medien | `/member/media` | Eigene Dateien und Ordner |
| Favoriten | `/member/favorites` | Gespeicherte Inhalte |
| Datenschutz | `/member/privacy` | Datenschutzeinstellungen und Datenanfragen |
| Abonnement | `/member/subscription` | Paket, Limits, Verlängerung und Bestellungen |
| Plugin-Bereich | `/member/plugin/{slug}` | Member-Funktionen installierter Plugins |

## Technische Referenz

Der Runtime-Einstieg ist `CMS/member/includes/bootstrap.php`. Er erzeugt den Singleton `CMS\MemberArea\MemberController`, verlangt Authentifizierung, setzt private Cache-Header und lädt normalisierte Dashboard-Einstellungen.

Die Runtime liest Einstellungen über `MemberDashboardModule::getRuntimeSettings()` und ergänzt sichere Defaults. Der Frontend-Lesepfad ist von der Admin-Leseberechtigung getrennt; das Modul-Gate `member_dashboard` entscheidet weiterhin über die Aktivierung. Unbekannte Widgets, fehlende Plugins und optionale Tabellen blockieren den Bereich nicht.

Der Member-Router liegt in `CMS/core/Routing/MemberRouter.php`. Er unterstützt GET und POST, erlaubt Theme-Overrides unter `member/{page}.php`, fällt auf `CMS/member/{page}.php` zurück, validiert Slugs und protokolliert Feature-Nutzung über `FeatureUsageService`.

### Sicherheit und Datengrenzen

- Jede Member-Seite ruft `MemberController::requireAuth()` auf.
- Formulare und Aktionen verwenden aktionsbezogene CSRF-Tokens wie `member_profile`, `member_media_action` und `member_security_*`.
- Medienpfade sind auf `member/user-{id}` begrenzt und gegen Traversal normalisiert.
- Private Antworten erhalten private Cache-Header.
- Dashboard-Links und eigene Assets werden vor der Ausgabe normalisiert.
- Eigene Profilfelder werden vor der Speicherung per Allowlist und Typprüfung validiert.

Siehe [MEMBER-ROUTES.md](MEMBER-ROUTES.md), [MEMBER-SECURITY.md](MEMBER-SECURITY.md) und [MEMBER-DASHBOARD.md](MEMBER-DASHBOARD.md) für die detaillierten Verträge.
