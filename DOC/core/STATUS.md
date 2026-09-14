# 365CMS – Projektdokumentation | Abschnitt: Status
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

### Current runtime status
This documentation set is aligned with the implementation in [CMS/core](../../CMS/core/) as it exists on 2026-09-13. It intentionally documents only code paths that are verifiable in the runtime and omits legacy claims that are not backed by the shipped code.

### Verified status indicators
| Area | Status | Evidence |
| --- | --- | --- |
| Runtime version | Stable | [CMS/core/Version.php](../../CMS/core/Version.php) defines `CURRENT = '3.4.00'` and `STATUS = 'stable'` |
| Runtime modes | Confirmed | `Bootstrap::detectMode()` resolves `cli`, `api`, `admin`, `web` |
| Routing | Confirmed | `Router::registerDefaultRoutes()` routes by request prefix |
| API contract | Confirmed | [CMS/core/Routing/ApiRouter.php](../../CMS/core/Routing/ApiRouter.php) enumerates the active routes |
| Security | Confirmed | [CMS/core/Security.php](../../CMS/core/Security.php) includes request validation and rate limiting |
| Authentication | Confirmed | [CMS/core/Auth.php](../../CMS/core/Auth.php) validates session state and capability checks |
| Database layer | Confirmed | [CMS/core/Database.php](../../CMS/core/Database.php) is a PDO abstraction with prepared statements |

### Documentation quality rule
The source-of-truth principle is: if a path, route, class, or behavior is not visible in the current runtime, it is not documented as active.

## Deutsch

### Aktueller Laufzeit-Status
Dieser Dokumentationssatz ist auf die Implementierung in [CMS/core](../../CMS/core/) zum Stand 2026-09-13 ausgerichtet. Er dokumentiert bewusst nur Codepfade, die in der Laufzeit verifizierbar sind, und lässt veraltete Aussagen weg, die nicht durch den ausgelieferten Code gestützt werden.

### Verifizierte Statusindikatoren
| Bereich | Status | Nachweis |
| --- | --- | --- |
| Laufzeitversion | Stable | [CMS/core/Version.php](../../CMS/core/Version.php) definiert `CURRENT = '3.4.00'` und `STATUS = 'stable'` |
| Laufzeitmodi | Bestätigt | `Bootstrap::detectMode()` löst `cli`, `api`, `admin`, `web` auf |
| Routing | Bestätigt | `Router::registerDefaultRoutes()` leitet anhand von Request-Präfixen weiter |
| API-Vertrag | Bestätigt | [CMS/core/Routing/ApiRouter.php](../../CMS/core/Routing/ApiRouter.php) listet die aktiven Routen |
| Security | Bestätigt | [CMS/core/Security.php](../../CMS/core/Security.php) enthält Request-Validierung und Rate-Limits |
| Authentication | Bestätigt | [CMS/core/Auth.php](../../CMS/core/Auth.php) validiert Session-Status und Capability-Prüfungen |
| Datenbankschicht | Bestätigt | [CMS/core/Database.php](../../CMS/core/Database.php) ist eine PDO-Abstraktion mit Prepared Statements |

### Qualitätsregel für Dokumentation
Die Quelle-der-Wahrheit-Regel lautet: Wenn ein Pfad, eine Route, Klasse oder ein Verhalten in der aktuellen Laufzeit nicht sichtbar ist, wird es nicht als aktiv dokumentiert.
