# 365CMS – Projektdokumentation | Abschnitt: API reference

> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

# API reference

This document reflects the API surface currently registered in `CMS/core/Routing/ApiRouter.php` and the request flow in `CMS/core/Api.php`. No route or behavior is listed unless it is present in the shipped runtime.

## Active route set

| Method | Route | Notes |
| --- | --- | --- |
| `GET` | `/api/v1/status` | Public status endpoint |
| `GET` | `/api/v1/pages` | List pages |
| `GET` | `/api/v1/pages/:slug` | Fetch a page by slug |
| `POST` | `/api/v1/analytics/web-vitals` | Captures core web vitals payload |
| `GET` | `/api/v1/admin/posts` | Requires admin capability |
| `GET` | `/api/v1/admin/pages` | Requires admin capability |
| `GET` | `/api/v1/admin/users` | Requires admin capability |
| `GET` | `/api/v1/admin/mail/logs` | Requires admin capability |
| `POST` | `/api/v1/admin/mail/test` | Requires admin capability and CSRF verification |
| `POST` | `/api/v1/admin/graph/test` | Requires admin capability and CSRF verification |
| `POST` | `/api/upload` | Upload endpoint |
| `GET` | `/api/media` | Media listing |
| `POST` | `/api/media` | Media create/upload |

## Request flow

The runtime resolves request handling through the router and API wrapper:

```php
$router = new Router();
$router->registerDefaultRoutes();
$router->dispatch();
```

The active router behavior is delegated by prefix:

- `/api` -> `CMS\Routing\ApiRouter`
- `/admin` -> `CMS\Routing\AdminRouter`
- `/member` and `/dashboard` -> `CMS\Routing\MemberRouter`
- other requests -> `CMS\Routing\PublicRouter` and `CMS\Routing\ThemeRouter`

## Security assumptions

API calls are subject to the security layer in `CMS/core/Security.php` and the session checks in `CMS/core/Auth.php`. This includes CSRF validation for protected admin routes, same-origin enforcement for `web-vitals`, and rate-limit enforcement for request abuse.

## Deutsch

# API-Referenz

Dieses Dokument spiegelt die API-Oberfläche wider, die aktuell in `CMS/core/Routing/ApiRouter.php` registriert ist, sowie den Anfragefluss in `CMS/core/Api.php`. Keine Route oder Funktion wird aufgeführt, wenn sie nicht in der ausgelieferten Laufzeit vorhanden ist.

## Aktives Routenset

| Methode | Route | Hinweise |
| --- | --- | --- |
| `GET` | `/api/v1/status` | Öffentlicher Status-Endpunkt |
| `GET` | `/api/v1/pages` | Seitenliste |
| `GET` | `/api/v1/pages/:slug` | Seite nach Slug |
| `POST` | `/api/v1/analytics/web-vitals` | Erfasst Core-Web-Vitals-Payload |
| `GET` | `/api/v1/admin/posts` | Erfordert Admin-Rechte |
| `GET` | `/api/v1/admin/pages` | Erfordert Admin-Rechte |
| `GET` | `/api/v1/admin/users` | Erfordert Admin-Rechte |
| `GET` | `/api/v1/admin/mail/logs` | Erfordert Admin-Rechte |
| `POST` | `/api/v1/admin/mail/test` | Erfordert Admin-Rechte und CSRF-Validierung |
| `POST` | `/api/v1/admin/graph/test` | Erfordert Admin-Rechte und CSRF-Validierung |
| `POST` | `/api/upload` | Upload-Endpunkt |
| `GET` | `/api/media` | Medienliste |
| `POST` | `/api/media` | Medien-Erstellung/Upload |

## Anfragefluss

Die Laufzeit löst die Anfrage über Router und API-Wrapper:

```php
$router = new Router();
$router->registerDefaultRoutes();
$router->dispatch();
```

Das aktive Router-Verhalten wird nach Präfixen aufgeteilt:

- `/api` -> `CMS\Routing\ApiRouter`
- `/admin` -> `CMS\Routing\AdminRouter`
- `/member` und `/dashboard` -> `CMS\Routing\MemberRouter`
- andere Anfragen -> `CMS\Routing\PublicRouter` und `CMS\Routing\ThemeRouter`

## Sicherheitsannahmen

API-Aufrufe unterliegen der Sicherheits-Schicht in `CMS/core/Security.php` und den Session-Prüfungen in `CMS/core/Auth.php`. Dazu gehören CSRF-Validierung für geschützte Admin-Routen, Same-Origin-Prüfung für `web-vitals` und Rate-Limit-Prüfung gegen Missbrauch.
