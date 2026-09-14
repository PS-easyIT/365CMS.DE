# 365CMS – Projektdokumentation | Abschnitt: Core classes

> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

# Core class inventory

This inventory reflects the active implementation under `CMS/core`. It is intentionally limited to classes that are present in the source tree and to behavior directly visible in the code.

## Class overview

| Class | File | Responsibility |
| --- | --- | --- |
| `Bootstrap` | `CMS/core/Bootstrap.php` | startup, runtime mode detection, dependency loading, platform validation |
| `Container` | `CMS/core/Container.php` | DI container for lazy and singleton bindings |
| `Router` | `CMS/core/Router.php` | request URI resolution and route dispatch |
| `Api` | `CMS/core/Api.php` | generic JSON API request handling and rate limiting |
| `Hooks` | `CMS/core/Hooks.php` | action/filter registry |
| `Security` | `CMS/core/Security.php` | CSP, headers, CSRF and token checks |
| `Auth` | `CMS/core/Auth.php` | session validation, login, MFA awareness, capability checks |
| `Database` | `CMS/core/Database.php` | PDO wrapper with prepared statements and migration delegation |
| `Version` | `CMS/core/Version.php` | runtime version information |

## Bootstrap

`Bootstrap` is the startup entry point. It sets `CMS_MODE`, validates the PHP platform requirements for bundled assets, loads dependencies, and initializes the core system. The mode detection logic is implemented in `Bootstrap::detectMode()`.

## Container

`Container` exposes a small DI container with `bind()`, `singleton()`, `bindInstance()`, `make()`, `get()`, `has()`, and `registered()`. Singletons are cached and reused; factory callbacks remain lazy until resolution time.

## Router

`Router` resolves the request URI, chooses the relevant route module by prefix, and calls `dispatch()`. It normalizes the URI, checks redirect rules, and enforces CSRF on protected methods outside the API/admin/member paths.

## Api

`Api` processes generic JSON endpoints such as `status`, `pages`, and `users`. It validates rate limits, normalizes search or slug input, and returns structured JSON responses or HTTP error codes.

## Hooks

`Hooks` provides WordPress-like action/filter semantics. It stores callbacks by tag and priority and exposes `doAction()`, `applyFilters()`, `removeAction()`, and `removeFilter()`.

## Security

`Security` generates a per-request nonce, builds a CSP policy, sets security headers such as `X-Content-Type-Options` and `X-Frame-Options`, and verifies tokens for CSRF.

## Auth

`Auth` initializes by validating the current session. It checks expiry based on role-defined session lifetimes, clears stale sessions without redirecting during constructor-time processing, and exposes `hasCapability()` for role-based permission checks.

## Database

`Database` is a PDO wrapper. It uses `prepare()`, `query()`, `execute()`, and `getPrefix()`. It keeps the database prefix and delegates schema creation and repair to `SchemaManager` and `MigrationManager`.

## Version

`Version` exposes `CURRENT`, `RELEASE_DATE`, and `STATUS` via `Version::CURRENT` and `Version::releaseDate()`. The active values are `3.4.00` and `stable`.

## Deutsch

# Inventar der Core-Klassen

Dieses Inventar spiegelt die aktive Implementierung unter `CMS/core` wider. Es ist bewusst auf Klassen begrenzt, die im Source-Tree vorhanden sind, und auf Verhalten, das unmittelbar im Code sichtbar ist.

## Klassenübersicht

| Klasse | Datei | Verantwortung |
| --- | --- | --- |
| `Bootstrap` | `CMS/core/Bootstrap.php` | Start, Moduserkennung, Abhängigkeitsladen, Plattformvalidierung |
| `Container` | `CMS/core/Container.php` | DI-Container für Lazy- und Singleton-Bindings |
| `Router` | `CMS/core/Router.php` | Request-URI-Auflösung und Routing-Dispatch |
| `Api` | `CMS/core/Api.php` | generische JSON-API-Anfragebehandlung und Rate-Limits |
| `Hooks` | `CMS/core/Hooks.php` | Action-/Filter-Registry |
| `Security` | `CMS/core/Security.php` | CSP, Header, CSRF und Token-Prüfung |
| `Auth` | `CMS/core/Auth.php` | Session-Validierung, Login, MFA-Awareness, Capability-Prüfungen |
| `Database` | `CMS/core/Database.php` | PDO-Wrapper mit Prepared Statements und Migrationsdelegation |
| `Version` | `CMS/core/Version.php` | Laufzeit-Versionen |

## Bootstrap

`Bootstrap` ist der Startpunkt. Er setzt `CMS_MODE`, validiert die PHP-Plattformanforderungen für gebündelte Assets, lädt Abhängigkeiten und initialisiert das Core-System. Die Moduserkennung ist in `Bootstrap::detectMode()` implementiert.

## Container

`Container` bietet einen kleinen DI-Container mit `bind()`, `singleton()`, `bindInstance()`, `make()`, `get()`, `has()` und `registered()`. Singletons werden gecacht und erneut verwendet; Factory-Callbacks bleiben bis zur Auflösung lazy.

## Router

`Router` löst die Request-URI auf, wählt das relevante Routemodul nach Präfix und ruft `dispatch()` auf. Es normalisiert die URI, prüft Redirect-Regeln und erzwingt CSRF auf geschützten Methoden außerhalb der API-/Admin-/Member-Pfade.

## Api

`Api` verarbeitet generische JSON-Endpunkte wie `status`, `pages` und `users`. Es prüft Rate-Limits, normalisiert Such- oder Slug-Parameter und liefert strukturierte JSON-Antworten oder HTTP-Fehlercodes.

## Hooks

`Hooks` bietet WordPress-ähnliche Action-/Filter-Semantik. Es speichert Callback-Handler nach Tag und Priorität und stellt `doAction()`, `applyFilters()`, `removeAction()` und `removeFilter()` bereit.

## Security

`Security` erzeugt ein per-Request-Nonce, baut eine CSP-Policy, setzt Sicherheitsheader wie `X-Content-Type-Options` und `X-Frame-Options` und validiert Tokens für CSRF.

## Auth

`Auth` initialisiert durch Validierung der aktuellen Session. Es prüft die Ablaufzeit anhand rollenbasierter Session-Lebensdauern, räumt veraltete Sessions ohne Redirect im Konstruktor auf und stellt `hasCapability()` für rollenbasierte Berechtigungsprüfungen bereit.

## Database

`Database` ist ein PDO-Wrapper. Er nutzt `prepare()`, `query()`, `execute()` und `getPrefix()`. Er verwaltet das Datenbank-Präfix und delegiert Schema-Erstellung und Reparatur an `SchemaManager` und `MigrationManager`.

## Version

`Version` stellt `CURRENT`, `RELEASE_DATE` und `STATUS` über `Version::CURRENT` und `Version::releaseDate()` bereit. Die aktiven Werte sind `3.4.00` und `stable`.
