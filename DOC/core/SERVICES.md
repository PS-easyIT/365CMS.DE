# 365CMS – Projektdokumentation | Abschnitt: Services
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

### Core service layer
The current runtime does not document a single monolithic service registry. The implemented services are distributed under [CMS/core](../../CMS/core/) and its subfolders, and the active bootstrap/DI wiring is defined in [CMS/core/Bootstrap.php](../../CMS/core/Bootstrap.php), [CMS/core/Container.php](../../CMS/core/Container.php), and [CMS/core/Router.php](../../CMS/core/Router.php).

### Verified runtime service areas
| Area | Path | Implementation evidence |
| --- | --- | --- |
| Bootstrap | [CMS/core/Bootstrap.php](../../CMS/core/Bootstrap.php) | Runtime mode detection and startup wiring |
| DI / container | [CMS/core/Container.php](../../CMS/core/Container.php) | Shared object resolution and dependency management |
| Routing | [CMS/core/Router.php](../../CMS/core/Router.php) | Prefix-based dispatch to API, admin, member, public, and theme handlers |
| API | [CMS/core/Routing/ApiRouter.php](../../CMS/core/Routing/ApiRouter.php) | Active API routes and admin mail/media endpoints |
| Security | [CMS/core/Security.php](../../CMS/core/Security.php) | Request hardening, nonce, headers, and DB rate limiting |
| Authentication | [CMS/core/Auth.php](../../CMS/core/Auth.php) | Session validation, expiration, and capability checks |
| Database | [CMS/core/Database.php](../../CMS/core/Database.php) | PDO wrapper with prepared statements and prefix support |
| Versioning | [CMS/core/Version.php](../../CMS/core/Version.php) | `CURRENT = '3.4.00'`, `STATUS = 'stable'` |

### Service design note
The codebase uses a lightweight service-oriented runtime rather than a verbose per-file service catalog. The correctness of the service surface should be checked in the actual implementation files, especially when a feature is used in production.

## Deutsch

### Service-Schicht im aktuellen Core
Die aktuelle Laufzeit dokumentiert kein einzelnes monolithisches Service-Registry. Die implementierten Services sind über [CMS/core](../../CMS/core/) und Unterordner verteilt; das aktive Bootstrap-/DI-Wiring ist in [CMS/core/Bootstrap.php](../../CMS/core/Bootstrap.php), [CMS/core/Container.php](../../CMS/core/Container.php) und [CMS/core/Router.php](../../CMS/core/Router.php) definiert.

### Verifizierte Laufzeit-Bereiche
| Bereich | Pfad | Nachweis der Implementierung |
| --- | --- | --- |
| Bootstrap | [CMS/core/Bootstrap.php](../../CMS/core/Bootstrap.php) | Laufzeitmodus-Erkennung und Start-Initialisierung |
| DI / Container | [CMS/core/Container.php](../../CMS/core/Container.php) | Gemeinsame Objektauflösung und Dependency-Management |
| Routing | [CMS/core/Router.php](../../CMS/core/Router.php) | Prefix-basierte Dispatching an API-, Admin-, Member-, Public- und Theme-Handler |
| API | [CMS/core/Routing/ApiRouter.php](../../CMS/core/Routing/ApiRouter.php) | Aktive API-Routen und Admin-Mail-/Media-Endpunkte |
| Security | [CMS/core/Security.php](../../CMS/core/Security.php) | Request-Hardening, Nonce, Header und DB-Rate-Limits |
| Authentication | [CMS/core/Auth.php](../../CMS/core/Auth.php) | Session-Validierung, Ablauf und Capability-Prüfungen |
| Database | [CMS/core/Database.php](../../CMS/core/Database.php) | PDO-Wrapper mit Prepared Statements und Prefix-Unterstützung |
| Versionierung | [CMS/core/Version.php](../../CMS/core/Version.php) | `CURRENT = '3.4.00'`, `STATUS = 'stable'` |

### Designhinweis zu Services
Das Codebase verwendet eine schlanke service-orientierte Laufzeit statt eines ausführlichen, per-Datei katalogisierten Service-Registers. Die Gültigkeit der Service-Oberfläche muss in den tatsächlichen Implementierungsdateien geprüft werden, insbesondere bei produktiv genutzten Funktionen.
