> **Website:** [365CMS.DE](https://365cms.de/) | **Version:** 3.4.00
> **Datum:** 2026-09-06 | **Status:** Abgeschlossen – **Zuletzt aktualisiert am:** 2026-09-06
> **Kurzbeschreibung:** Routenreferenz für authentifizierte Member-Seiten, Theme-Overrides, Plugin-Dispatch, Weiterleitungen, Nutzungs-Tracking und 404-Fallbacks. Sie entspricht dem Stand von `MemberRouter.php` in Version 3.4.00.

# 365CMS Member Routes

## English — user guide

Use `/member/dashboard` as the canonical entry point. `/member` redirects to it. Plugin sections start at `/member/plugin/{slug}`; an action and a numeric ID may be appended. Visitors who are not signed in are sent to the public login page and returned to the requested URL after login.

## English — technical reference

`MemberRouter::registerRoutes()` registers the following routes:

| Methods | Route | Handler and outcome |
|---|---|---|
| GET, POST | `/dashboard` | Requires login; includes theme `member/dashboard.php`, otherwise redirects to `/member/dashboard` |
| GET, POST | `/member` | Requires login; redirects to `/member/dashboard` |
| GET, POST | `/member/:page` | Requires login; theme page first, then `CMS/member/{page}.php` |
| GET, POST | `/member/plugin/:slug` | Registry dispatcher with no action |
| GET, POST | `/member/plugin/:slug/:action` | Registry dispatcher; sanitized action becomes `$_GET['action']` |
| GET, POST | `/member/plugin/:slug/:action/:id` | Registry dispatcher; numeric ID becomes `$_GET['id']` |

`renderMemberPage()` rejects a page value unless it matches `/^[a-zA-Z0-9_-]+$/`. Theme overrides are resolved with `ThemeManager::getThemePath()`. If neither override nor core file exists, `Logger` channel `member-router` records the page and fallback file before `render404()`.

`renderMemberPluginSection()` initializes `CMS\Member\PluginDashboardRegistry`, tracks `member.plugin.{slug}[.{action}]` through `FeatureUsageService`, and calls `handleRoute()`. The registry chooses a theme wrapper `member/plugin-section.php` before the generic `CMS/member/plugin-section.php`. Unknown slugs and failed capability checks render `CMS/member/partials/plugin-not-found.php`.

Every normal page is tracked as `member.{page}` with category `member`, route path, user ID when available, label, and route group. Unauthenticated requests use `CmsAuthPageService::getPublicPath('login', locale)` and URL-encode `REQUEST_URI` as `redirect`.

---

# 365CMS-Member-Routen

## Deutsch — Anwenderblock

Der zentrale Einstieg ist `/member/dashboard`; `/member` leitet dorthin weiter. Plugin-Bereiche beginnen mit `/member/plugin/{slug}` und können um Aktion und numerische ID ergänzt werden. Nicht eingeloggte Besucher gelangen zur öffentlichen Login-Seite und nach der Anmeldung zurück zur ursprünglichen URL.

## Deutsch — Technikblock

`MemberRouter::registerRoutes()` registriert:

| Methoden | Route | Handler und Ergebnis |
|---|---|---|
| GET, POST | `/dashboard` | Login erforderlich; Theme-Datei `member/dashboard.php`, sonst Weiterleitung zu `/member/dashboard` |
| GET, POST | `/member` | Login erforderlich; Weiterleitung zu `/member/dashboard` |
| GET, POST | `/member/:page` | Login erforderlich; zuerst Theme-Datei, dann `CMS/member/{page}.php` |
| GET, POST | `/member/plugin/:slug` | Registry-Dispatcher ohne Aktion |
| GET, POST | `/member/plugin/:slug/:action` | Registry-Dispatcher; Aktion wird bereinigt in `$_GET['action']` gesetzt |
| GET, POST | `/member/plugin/:slug/:action/:id` | Registry-Dispatcher; numerische ID wird in `$_GET['id']` gesetzt |

`renderMemberPage()` akzeptiert nur Slugs nach `/^[a-zA-Z0-9_-]+$/`. Theme-Overrides werden über `ThemeManager::getThemePath()` aufgelöst. Fehlen Override und Core-Datei, schreibt der Logger im Kanal `member-router` Seite und Fallback-Datei und rendert 404.

`renderMemberPluginSection()` initialisiert `CMS\Member\PluginDashboardRegistry`, protokolliert `member.plugin.{slug}[.{action}]` über `FeatureUsageService` und ruft `handleRoute()` auf. Die Registry bevorzugt `member/plugin-section.php` des Themes vor dem generischen `CMS/member/plugin-section.php`. Unbekannte Slugs und fehlende Berechtigungen rendern `CMS/member/partials/plugin-not-found.php`.

Jede normale Seite wird als `member.{page}` mit Kategorie `member`, Route, verfügbarer Benutzer-ID, Label und Route-Gruppe erfasst. Nicht authentifizierte Aufrufe verwenden `CmsAuthPageService::getPublicPath('login', locale)` und kodieren `REQUEST_URI` als `redirect`.
