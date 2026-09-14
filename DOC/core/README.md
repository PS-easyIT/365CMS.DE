# 365CMS – Projektdokumentation | Abschnitt: Overview

> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

# Core documentation overview

This folder documents the active runtime implementation in `CMS/core` and intentionally stays within the facts confirmed by the source code. The authoritative files for this revision are the runtime classes under `CMS/core` and the route modules under `CMS/core/Routing`.

## Source-of-truth files

| File | Role |
| --- | --- |
| `CMS/core/Bootstrap.php` | startup bootstrap, mode detection, runtime validation |
| `CMS/core/Container.php` | dependency injection container |
| `CMS/core/Router.php` | request dispatch and routing rules |
| `CMS/core/Api.php` | generic API request handling |
| `CMS/core/Routing/ApiRouter.php` | active API route surface |
| `CMS/core/Hooks.php` | action/filter hook registry |
| `CMS/core/Security.php` | CSP, headers, CSRF, session hardening |
| `CMS/core/Auth.php` | session, login, capability checks |
| `CMS/core/Database.php` | PDO wrapper and schema/migration delegation |
| `CMS/core/Version.php` | version metadata |

## Current runtime facts

- `Version::CURRENT` is `3.4.00`.
- `Bootstrap::detectMode()` resolves the runtime mode to `cli`, `api`, `admin`, or `web`.
- `Router::registerDefaultRoutes()` routes API requests to `CMS\Routing\ApiRouter`, admin requests to `CMS\Routing\AdminRouter`, member requests to `CMS\Routing\MemberRouter`, and the rest to `PublicRouter` and `ThemeRouter`.
- The active API endpoints are defined in `CMS/core/Routing/ApiRouter.php`.
- `Hooks` implements the action and filter registry.
- `Security` generates a nonce-based CSP, sets security headers, validates CSRF, and adds no-store caching in admin/API mode.
- `Database` uses native PDO prepared statements and delegates schema creation and repair to manager classes.

## Validation notes

The documentation in this directory is intentionally conservative. It only describes contracts and files that exist in the current repository and avoids invented endpoints or configuration values.

## Deutsch

# Überblick zur Core-Dokumentation

Dieser Ordner dokumentiert die aktive Laufzeitimplementierung in `CMS/core` und bleibt bewusst innerhalb der Fakten, die im Code bestätigt sind. Die maßgeblichen Dateien für diese Revision sind die Laufzeitklassen unter `CMS/core` und die Routemodule unter `CMS/core/Routing`.

## Verbindliche Quell-Dateien

| Datei | Rolle |
| --- | --- |
| `CMS/core/Bootstrap.php` | Bootstrap, Moduserkennung, Laufzeitvalidierung |
| `CMS/core/Container.php` | Dependency-Injection-Container |
| `CMS/core/Router.php` | Request-Dispatch und Routing-Regeln |
| `CMS/core/Api.php` | generische API-Anfragebehandlung |
| `CMS/core/Routing/ApiRouter.php` | aktives API-Route-Set |
| `CMS/core/Hooks.php` | Action-/Filter-Registry |
| `CMS/core/Security.php` | CSP, Header, CSRF, Session-Hardening |
| `CMS/core/Auth.php` | Session, Login, Capability-Prüfungen |
| `CMS/core/Database.php` | PDO-Wrapper und Schema-/Migrationsdelegation |
| `CMS/core/Version.php` | Versionsmetadaten |

## Aktuelle Laufzeitfakten

- `Version::CURRENT` ist `3.4.00`.
- `Bootstrap::detectMode()` ermittelt den Laufzeitmodus als `cli`, `api`, `admin` oder `web`.
- `Router::registerDefaultRoutes()` leitet API-Anfragen an `CMS\Routing\ApiRouter`, Admin-Anfragen an `CMS\Routing\AdminRouter`, Member-Anfragen an `CMS\Routing\MemberRouter` und den Rest an `PublicRouter` und `ThemeRouter`.
- Das aktive API-Endpunkt-Set befindet sich in `CMS/core/Routing/ApiRouter.php`.
- `Hooks` implementiert die Action- und Filter-Registry.
- `Security` erzeugt eine nonce-basierte CSP, setzt Sicherheitsheader, validiert CSRF und fügt im Admin-/API-Modus `no-store`-Caching hinzu.
- `Database` nutzt native PDO-Prepared-Statements und delegiert Schema-Erstellung und Reparatur an Manager-Klassen.

## Validierungsnotizen

Die Dokumentation in diesem Verzeichnis ist bewusst konservativ. Sie beschreibt nur Contracts und Dateien, die im aktuellen Repository existieren, und vermeidet erfundene Endpunkte oder Konfigurationswerte.
