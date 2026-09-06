# 365CMS Member Routes
> **Website:** 365CMS.DE | **Version:** 3.4.00 | **Date:** 2026-09-06 | **Status:** Current | **Last updated:** 2026-09-06

This reference lists the routes and dispatch rules for the member area. It is intended for support users first and for developers second.

## User guide

All member routes require login. The canonical start page is `/member/dashboard`; `/member` redirects there. Plugin features use `/member/plugin/{slug}` and may add an action and numeric ID.

## Technical reference

`MemberRouter::registerRoutes()` registers:

| Method | Route | Handler |
|---|---|---|
| GET, POST | `/dashboard` | Theme dashboard or redirect |
| GET, POST | `/member` | Redirect to dashboard |
| GET, POST | `/member/:page` | Theme override, then `CMS/member/{page}.php` |
| GET, POST | `/member/plugin/:slug` | Plugin registry |
| GET, POST | `/member/plugin/:slug/:action` | Plugin registry with action |
| GET, POST | `/member/plugin/:slug/:action/:id` | Plugin registry with action and ID |

Page slugs accept only `[a-zA-Z0-9_-]+`. Unauthenticated requests use the configured public login path with the original request as `redirect`. Every dispatched member and plugin feature is recorded by `FeatureUsageService`.

---

# 365CMS-Member-Routen
> **Website:** 365CMS.DE | **Version:** 3.4.00 | **Datum:** 2026-09-06 | **Status:** Aktuell | **Zuletzt aktualisiert:** 2026-09-06

Diese Referenz listet Routen und Dispatch-Regeln des Mitgliederbereichs. Sie ist zuerst für Support-Anwender und danach für Entwickler gedacht.

## Anwenderleitfaden

Alle Member-Routen benötigen einen Login. Die zentrale Startseite ist `/member/dashboard`; `/member` leitet dorthin weiter. Plugin-Funktionen verwenden `/member/plugin/{slug}` und können Aktion sowie numerische ID ergänzen.

## Technische Referenz

`MemberRouter::registerRoutes()` registriert die oben genannten GET-/POST-Routen. Theme-Dateien werden vor den Core-Dateien geladen. Nicht authentifizierte Anfragen nutzen den konfigurierten öffentlichen Login-Pfad mit ursprünglicher Anfrage als `redirect`. Jede Member- und Plugin-Funktion wird über `FeatureUsageService` erfasst.
