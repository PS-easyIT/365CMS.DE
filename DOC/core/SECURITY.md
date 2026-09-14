# 365CMS – Projektdokumentation | Abschnitt: Security
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

### Security implementation in the current core
The active security layer is in [CMS/core/Security.php](../../CMS/core/Security.php). It provides per-request hardening, nonce generation, token verification, and request-rate controls.

### Confirmed controls
| Control | Evidence | Notes |
| --- | --- | --- |
| CSP nonce | `Security::getNonce()`, `Security::nonceAttr()` | A nonce is generated once per request and reused in templates |
| Basic headers | `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `Cross-Origin-*` | Set via `setSecurityHeaders()` |
| API/admin caching | `Cache-Control: no-store, max-age=0`, `Pragma: no-cache` | Applied when `CMS_MODE` is `admin` or `api` |
| HSTS | `getHstsValue()` and `Strict-Transport-Security` | Enabled only when HTTPS is detected and not disabled in debug mode |
| Token validation | `verifyToken()` / `verifyPersistentToken()` | Used for form and API checks |
| DB rate limiting | `checkDbRateLimit()` and `recordDbRateLimitAttempt()` | Used for login and API abuse prevention |
| Sanitization | `sanitizeDiagnosticText()` | Removes sensitive tokens from diagnostic output |

### Auth and session security
[CMS/core/Auth.php](../../CMS/core/Auth.php) enforces session lifetime checks by role, clears expired sessions, and reloads the current user object. The code explicitly avoids redirect-based invalidation in the constructor path and instead invalidates the session in place.

### Route-level validation
The runtime also validates request security in route handlers, notably in [CMS/core/Routing/ApiRouter.php](../../CMS/core/Routing/ApiRouter.php):
- `captureWebVitals()` rejects requests that are not same-origin.
- `testAdminMail()` and `testAdminGraph()` require admin access and verify a CSRF token.
- `Router::dispatch()` includes CSRF checks for protected methods outside specific allowlists.

## Deutsch

### Sicherheitsimplementierung im aktuellen Core
Die aktive Sicherheitslogik liegt in [CMS/core/Security.php](../../CMS/core/Security.php). Sie stellt per-Request-Hardening, CSP-Nonces, Token-Validierung und Request-Rate-Limits bereit.

### Bestätigte Controls
| Control | Nachweis | Hinweise |
| --- | --- | --- |
| CSP-Nonce | `Security::getNonce()`, `Security::nonceAttr()` | Ein Nonce wird pro Request erzeugt und in Templates wiederverwendet |
| Basis-Header | `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `Cross-Origin-*` | Werden über `setSecurityHeaders()` gesetzt |
| API-/Admin-Cache | `Cache-Control: no-store, max-age=0`, `Pragma: no-cache` | Aktiv bei `CMS_MODE = admin` oder `api` |
| HSTS | `getHstsValue()` und `Strict-Transport-Security` | Nur bei HTTPS und nicht im deaktivierten Debug-Fall |
| Token-Validierung | `verifyToken()` / `verifyPersistentToken()` | Für Form- und API-Prüfungen |
| DB-Rate-Limit | `checkDbRateLimit()` und `recordDbRateLimitAttempt()` | Gegen Login- und API-Missbrauch |
| Sanitization | `sanitizeDiagnosticText()` | Entfernt sensible Token aus Diagnose-Ausgaben |

### Authentifizierung und Sitzungssicherheit
[CMS/core/Auth.php](../../CMS/core/Auth.php) erzwingt Sitzungs-Lifetime-Prüfungen je Rolle, räumt abgelaufene Sessions auf und lädt das aktuelle Benutzerobjekt neu. Der Code vermeidet bewusst Redirect-basierte Invalidierung im Konstruktorpfad und invalidiert die Session direkt.

### Validierung auf Route-Ebene
Die Laufzeit validiert Sicherheit auch in Route-Handlern, vor allem in [CMS/core/Routing/ApiRouter.php](../../CMS/core/Routing/ApiRouter.php):
- `captureWebVitals()` lehnt nicht-same-origin-Anfragen ab.
- `testAdminMail()` und `testAdminGraph()` erfordern Admin-Zugriff und validieren ein CSRF-Token.
- `Router::dispatch()` enthält CSRF-Prüfungen für geschützte Methoden außerhalb definierter Allowlists.
