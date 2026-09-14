# 365CMS – Projektdokumentation | Abschnitt: Architecture

> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

# Core architecture

This document reflects the current code in `CMS/core` and the route modules in `CMS/core/Routing`. It does not describe a hypothetical architecture; every statement is grounded in the active implementation.

## Runtime bootstrap

The bootstrap lifecycle is started in `CMS/core/Bootstrap.php`. The class is a singleton and resolves the effective runtime mode in `Bootstrap::detectMode()`. The mode is determined as follows:

1. `PHP_SAPI === 'cli'` -> `cli`
2. request path begins with `/api/` or equals `/api` -> `api`
3. request path begins with `/admin/` or equals `/admin` -> `admin`
4. otherwise -> `web`

The constructor loads dependencies, validates bundled PHP platform requirements, initializes the core container, and sets `CMS_MODE` when available.

## DI container and core services

`CMS/core/Container.php` provides a small DI container with explicit binding, singleton resolution, and instance registration. The container keeps a binding map and a resolved cache, and exposes `bind()`, `singleton()`, `bindInstance()`, `make()`, `get()`, `has()`, `registered()`, `forget()`, and `flush()`.

## Routing model

`CMS/core/Router.php` is the dispatch hub. It resolves the request URI, registers the default route modules, and then calls `dispatch()`. The default registration logic is:

| Request prefix | Route module |
| --- | --- |
| `/api` | `CMS\Routing\ApiRouter` |
| `/admin` | `CMS\Routing\AdminRouter` |
| `/member`, `/dashboard` | `CMS\Routing\MemberRouter` |
| all other requests | `CMS\Routing\PublicRouter` and `CMS\Routing\ThemeRouter` |

The `Router` class also applies request cache headers, optional redirects, and a CSRF guard for protected methods outside the API/admin/member request paths.

## API layer

The API route module is `CMS/core/Routing/ApiRouter.php`. It registers a concrete set of route handlers via `Router::addRoute()`. The active route set includes status, page lookup, analytics web-vitals capture, admin mail and graph checks, file upload, and media handling. The generic request logic is handled by `CMS/core/Api.php`, which validates rate limits, normalizes URL parameters, and delegates page/user endpoints to the relevant handlers.

## Security and auth model

`CMS/core/Security.php` is the current security layer. It builds a nonce-based CSP, sets security headers, and handles HTTPS/HSTS detection. It also provides token verification and no-store cache policy in admin/API mode.

`CMS/core/Auth.php` is the current authentication model. It validates the current session, enforces session lifetime based on role, clears expired sessions without redirecting during constructor-time checks, and exposes capability checks such as `hasCapability()`.

## Database layer

`CMS/core/Database.php` is a PDO-based database abstraction layer. It establishes a MySQL/MariaDB connection, uses native prepared statements with `PDO::ATTR_EMULATE_PREPARES => false`, and binds integers explicitly for pagination-style SQL. Schema creation and repair are delegated to `SchemaManager` and `MigrationManager`.

## Hooks and extension surface

The extension API is implemented in `CMS/core/Hooks.php`. It exposes `addAction()`, `doAction()`, `hasAction()`, `addFilter()`, `applyFilters()`, `removeAction()`, and `removeFilter()`.

## Deutsch

# Kernarchitektur

Dieses Dokument spiegelt den aktuellen Code in `CMS/core` und die Routemodule in `CMS/core/Routing` wider. Es beschreibt keine hypothetische Architektur; jede Aussage basiert auf der aktiven Implementierung.

## Bootstrap der Laufzeit

Der Startzyklus wird in `CMS/core/Bootstrap.php` initiiert. Die Klasse ist ein Singleton und bestimmt den wirksamen Laufzeitmodus in `Bootstrap::detectMode()`. Die Moduserkennung erfolgt wie folgt:

1. `PHP_SAPI === 'cli'` -> `cli`
2. Request-Pfad beginnt mit `/api/` oder ist `/api` -> `api`
3. Request-Pfad beginnt mit `/admin/` oder ist `/admin` -> `admin`
4. ansonsten -> `web`

Im Konstruktor werden Abhängigkeiten geladen, gebündelte PHP-Plattformanforderungen validiert, der Core-Container initialisiert und `CMS_MODE` gesetzt, sofern vorhanden.

## Dependency Injection und Core-Services

`CMS/core/Container.php` stellt einen kleinen DI-Container mit expliziter Bindung, Singleton-Auflösung und Instanzregistrierung bereit. Der Container verwaltet eine Binding-Map und einen Cache aufgelöster Instanzen und bietet `bind()`, `singleton()`, `bindInstance()`, `make()`, `get()`, `has()`, `registered()`, `forget()` und `flush()`.

## Routing-Modell

`CMS/core/Router.php` ist das Dispatch-Frontend. Es löst die Request-URI auf, registriert die Standard-Routemodule und ruft anschließend `dispatch()` auf. Die Default-Registrierung ist:

| Request-Präfix | Routemodul |
| --- | --- |
| `/api` | `CMS\Routing\ApiRouter` |
| `/admin` | `CMS\Routing\AdminRouter` |
| `/member`, `/dashboard` | `CMS\Routing\MemberRouter` |
| alle übrigen Anfragen | `CMS\Routing\PublicRouter` und `CMS\Routing\ThemeRouter` |

Die `Router`-Klasse setzt außerdem Request-Cache-Header, optionale Redirects und einen CSRF-Schutz für geschützte Methoden außerhalb der API-/Admin-/Member-Pfade.

## API-Schicht

Das API-Routemodul ist `CMS/core/Routing/ApiRouter.php`. Es registriert einen konkreten Satz an Route-Handlern über `Router::addRoute()`. Das aktive Route-Set umfasst Status, Seitenabfragen, Web-Vitals-Erfassung, Admin-Mail-/Graph-Prüfung, Datei-Upload und Media-Handling. Die generische Request-Logik wird über `CMS/core/Api.php` ausgeführt, das Rate-Limits prüft, URL-Parameter normalisiert und Seiten-/Benutzer-Endpunkte an die jeweiligen Handler delegiert.

## Sicherheits- und Authentifizierungsmodell

`CMS/core/Security.php` ist die aktuelle Sicherheits-Schicht. Sie baut eine nonce-basierte CSP, setzt Sicherheitsheader und verarbeitet HTTPS/HSTS-Erkennung. Zudem stellt sie Token-Validierung und `no-store`-Cache-Politik im Admin-/API-Modus bereit.

`CMS/core/Auth.php` ist das aktuelle Authentifizierungsmodell. Es validiert die aktuelle Session, erzwingt Session-Lebenszeiten je nach Rolle, räumt abgelaufene Sessions ohne Redirect bei Konstruktor-Prüfungen auf und stellt Capability-Prüfungen wie `hasCapability()` bereit.

## Datenbankschicht

`CMS/core/Database.php` ist eine PDO-basierte Datenbank-Abstraktion. Sie baut eine MySQL/MariaDB-Verbindung auf, verwendet native Prepared Statements mit `PDO::ATTR_EMULATE_PREPARES => false` und bindet Integer explizit für paginationsartige SQL-Anweisungen. Schema-Erstellung und Reparatur werden an `SchemaManager` und `MigrationManager` delegiert.

## Hooks und Erweiterungsoberfläche

Die Erweiterungs-API ist in `CMS/core/Hooks.php` implementiert. Sie bietet `addAction()`, `doAction()`, `hasAction()`, `addFilter()`, `applyFilters()`, `removeAction()` und `removeFilter()`.

