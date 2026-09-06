> **Website:** [365CMS](https://365cms.de/)
> **Version:** 3.4.00
> **Datum:** 2026-09-06
> **Status:** Aktuell – **Zuletzt aktualisiert am: 2026-09-06**
> **Kurzbeschreibung:** Anwender- und Entwicklerreferenz für die produktiven Core-API-Routen, Authentifizierung, Antworten und Limits. Die Route-Liste wurde gegen `CMS/core/Routing/ApiRouter.php` und `CMS/core/Api.php` geprüft.

## English

### User-friendly
Use this document to discover the available 365CMS API endpoints, required authentication, response formats and safe request limits. The public status and page routes are separated from protected administration, upload and media routes.

### Technical
The verified runtime source is `CMS/core/Routing/ApiRouter.php`: status, pages, web-vitals, admin lists, upload and media routes are registered there. Authorization, CSRF handling, JSON responses and endpoint dispatch are implemented by `CMS/core/Api.php`, `CMS/core/Security.php` and the related services. The complete detailed reference is retained in the German technical section below.

## Deutsch

### Anwenderfreundlich
Diese Referenz erklärt die nutzbaren API-Endpunkte, Anmeldung, Antwortformate und Schutzlimits. Öffentliche Status-/Seitenrouten sind von geschützten Admin-, Upload- und Medienrouten getrennt.

### Technisch
Die nachfolgende vollständige Referenz bleibt die verbindliche deutsche Detailbeschreibung. Sie wurde gegen den aktuellen Code unter `CMS/core/` ergänzt und geprüft.

## Deutsch

### Anwenderfreundlich

Diese Referenz erklärt die verfügbaren API-Endpunkte, die benötigte Authentifizierung, Antwortformate und sicheren Anfragegrenzen.

### Technisch

# 365CMS – API-Referenz
> **Stand:** 2026-09-06 | **Version:** 3.4.00 | **Status:** Aktuell

Dokumentation der REST-API (`/api/v1/`) mit Authentifizierung, Endpunkten, Fehlerbehandlung und Beispielen.

---

<!-- UPDATED: 2026-09-06 -->
## 1 · Übersicht

Die 365CMS REST-API folgt dem Muster `/api/v1/{endpoint}/{id}`. Alle Antworten werden als `Content-Type: application/json` zurückgegeben.

Die Datei beschreibt die produktive 3.4.00-API-Linie. Für Detailabweichungen in Einzelfällen bleibt die tatsächliche Laufzeitimplementierung in `CMS/core/Api.php`, `CMS/core/Routing/ApiRouter.php`, `CMS/admin/api/` und den registrierten Router-Endpunkten führend.

| Eigenschaft | Wert |
|-------------|------|
| Base-URL | `https://example.com/api/v1/` |
| Controller | `CMS\Api` (`core/Api.php`) |
| Routing | `handleRequest(string $endpoint, ?string $id)` |
| Format | JSON |
| Authentifizierung | JWT Bearer-Token, Session, API-Key |
| Rate-Limiting | 60 Requests / 60 Sekunden pro IP |

### Verifizierte Runtime-Routen (`3.4.00`)

Die registrierten Routen aus `CMS/core/Routing/ApiRouter.php` sind:

| Methode | Route | Besonderheit |
|---|---|---|
| `GET` | `/api/v1/status` | öffentlich, flache Statusantwort |
| `GET` | `/api/v1/pages` | Seitenabfrage über `CMS\Api` |
| `GET` | `/api/v1/pages/:slug` | Seite nach Slug |
| `POST` | `/api/v1/analytics/web-vitals` | Same-Origin, 8-KB-Payload, 40 Requests/Minute |
| `GET` | `/api/v1/admin/posts` | Capability `edit_all_posts` |
| `GET` | `/api/v1/admin/pages` | Admin-Capability |
| `GET` | `/api/v1/admin/users` | Administratorzugriff |
| `GET` | `/api/v1/admin/mail/logs` | Capability `manage_settings` |
| `POST` | `/api/v1/admin/mail/test` | Admin + CSRF |
| `POST` | `/api/v1/admin/graph/test` | Admin + CSRF |
| `POST` | `/api/upload` | zentraler Upload-Service |
| `GET` / `POST` | `/api/media` | Medien-API für Editor/Administration |

> Hinweis: Die meisten klassischen Controller-Endpunkte antworten über `CMS\Api::sendResponse()` im Format `{"data": ...}`. Der öffentliche Health-/Status-Endpunkt `/api/v1/status` ist davon bewusst ausgenommen und liefert direkt ein flaches JSON aus `CMS\Routing\ApiRouter::status()`.

### Erfolgs-Response

```json
{
    "data": { ... }
}
```

### Fehler-Response

```json
{
    "error": "Fehlermeldung"
}
```

---

<!-- UPDATED: 2026-09-06 -->
## 2 · Authentifizierung

Die API unterstützt drei Authentifizierungsmethoden:

### 2.1 JWT Bearer-Token (empfohlen)

**Bibliothek:** firebase/php-jwt (HS256)
**Service:** `CMS\Services\JwtService` (`core/Services/JwtService.php`)

| Konfiguration | Beschreibung | Standard |
|---------------|-------------|----------|
| `JWT_SECRET` | HMAC-Schlüssel für HS256 (Pflicht) | `AUTH_KEY` |
| `JWT_TTL` | Token-Lebensdauer in Sekunden | `3600` (1 Stunde) |
| `JWT_ISSUER` | `iss`-Claim | `SITE_URL` |

```php
// Token generieren
$jwt = CMS\Services\JwtService::getInstance();
$token = $jwt->generate(['user_id' => 42, 'role' => 'admin']);

// Token validieren
$payload = $jwt->validate($token);
// → ['user_id' => 42, 'role' => 'admin', 'iat' => ..., 'exp' => ..., 'iss' => ...]
```

**Header-Format:**

```
Authorization: Bearer eyJhbGciOiJIUzI1NiIs...
```

### 2.2 Session-Authentifizierung

Für Browser-basierte Aufrufe (z. B. aus dem Admin-Panel) wird die bestehende PHP-Session genutzt. `Auth::instance()->isLoggedIn()` prüft den Session-Status.

### 2.3 API-Key

API-Keys können über die Admin-Einstellungen generiert und im Header übergeben werden:

```
X-API-Key: dein-api-key-hier
```

---

<!-- UPDATED: 2026-09-06 -->
## 3 · Endpunkte

| Methode | Route | Auth | Parameter | Response | Beschreibung |
|---------|-------|------|-----------|----------|-------------|
| `GET` | `/api/v1/status` | Keine | – | `{"status":"ok","version":"3.4.00"}` | System-Status (flat JSON, kein `data`-Wrapper) |
| `GET` | `/api/v1/pages` | Session/JWT | `?q=suchbegriff` | `{"data":[...]}` | Seiten durchsuchen |
| `GET` | `/api/v1/pages/{slug}` | Session/JWT | – | `{"data":{...}}` | Einzelne Seite per Slug |
| `GET` | `/api/v1/admin/users` | Admin | `page`, `search`, Filter | `{"items":[...],"pagination":{...}}` | Benutzerliste für die Administration |
| `GET` | `/api/v1/admin/posts` | Admin | `page`, `search`, Filter | `{"items":[...],"pagination":{...}}` | Admin-Post-Liste als JSON |
| `GET` | `/api/v1/admin/pages` | Admin | `page`, `search`, Filter | `{"items":[...],"pagination":{...}}` | Admin-Seitenliste als JSON |
| `GET` | `/api/v1/admin/mail/logs` | Admin | `page`, `limit`, `search`, `status` | `{"items":[...],"pagination":{...}}` | Versandprotokolle für Diagnose/UI |
| `POST` | `/api/v1/admin/mail/test` | Admin + CSRF | `recipient` | `{"success":true}` | Test-Mail aus dem Admin |
| `POST` | `/api/v1/admin/graph/test` | Admin + CSRF | – | `{"success":true}` | Graph-/Transporttest aus dem Admin |
| `POST` | `/api/upload` | Session/JWT | Datei + Metadaten | `{"success":true,"file":{...}}` | Zentraler Upload-Endpunkt |
| `GET/POST` | `/api/media` | Session/JWT | Query / Aktion | JSON | Medienliste und Aktionen |

### Berechtigungen

| Endpunkt | Mindest-Rolle |
|----------|--------------|
| `status` | Öffentlich (keine Authentifizierung nötig) |
| `pages` | Authentifizierter Benutzer (`isLoggedIn()`) |
| `admin/users` | Administrator (`isAdmin()`) |
| `admin/*` | Administrator (`isAdmin()`) + bei POST gültiger CSRF-Token |

---

<!-- UPDATED: 2026-09-06 -->
## 4 · Error-Codes und Error-Response-Format

Alle Fehler werden als JSON mit passendem HTTP-Statuscode zurückgegeben:

```json
{
    "error": "Beschreibung des Fehlers"
}
```

| HTTP-Code | Bedeutung | Typischer Auslöser |
|-----------|-----------|-------------------|
| `400` | Bad Request | Ungültige Parameter |
| `401` | Unauthorized | Fehlende oder ungültige Authentifizierung |
| `403` | Forbidden | Unzureichende Berechtigungen (z. B. kein Admin) |
| `404` | Not Found | Endpunkt oder Ressource nicht gefunden |
| `429` | Too Many Requests | Rate-Limit überschritten |
| `500` | Internal Server Error | Unerwarteter Serverfehler |

### JWT-spezifische Fehler

| Fehler | Ursache |
|--------|---------|
| `Token expired` | Token-Lebensdauer (`JWT_TTL`) überschritten |
| `Signature invalid` | Falscher `JWT_SECRET` oder manipulierter Token |
| `Token not yet valid` | `nbf`-Claim liegt in der Zukunft |

---

<!-- UPDATED: 2026-04-07 -->
## 5 · Rate Limiting

Die API verwendet DB-basiertes Rate-Limiting über `Security::checkDbRateLimit()`:

| Parameter | Wert |
|-----------|------|
| Max. Anfragen | 60 pro IP |
| Zeitfenster | 60 Sekunden |
| Identifier | Client-IP (`Security::getClientIp()`) |
| Scope | `api` |

Bei Überschreitung:

```
HTTP/1.1 429 Too Many Requests
Retry-After: 60
Content-Type: application/json

{"error": "Rate limit exceeded. Please try again later."}
```

---

<!-- UPDATED: 2026-09-06 -->
## 6 · WebAuthn/Passkey API-Endpunkte

Die WebAuthn-Integration ermöglicht passwortlose Authentifizierung via FIDO2/Passkeys. Die Endpunkte werden über den Auth-Controller bereitgestellt:

| Methode | Route | Auth | Beschreibung |
|---------|-------|------|-------------|
| `POST` | `/api/v1/webauthn/register/options` | Session | Registrierungs-Challenge generieren |
| `POST` | `/api/v1/webauthn/register/verify` | Session | Passkey-Registrierung verifizieren |
| `POST` | `/api/v1/webauthn/login/options` | Keine | Login-Challenge generieren |
| `POST` | `/api/v1/webauthn/login/verify` | Keine | Passkey-Login verifizieren |

### Registrierungs-Flow

```bash
# 1. Challenge anfordern (eingeloggt)
curl -s -X POST https://example.com/api/v1/webauthn/register/options \
  -H "Cookie: PHPSESSID=abc123"

# 2. Browser führt navigator.credentials.create() aus

# 3. Ergebnis verifizieren
curl -s -X POST https://example.com/api/v1/webauthn/register/verify \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=abc123" \
  -d '{"attestation": "..."}'
```

### Login-Flow

```bash
# 1. Challenge anfordern
curl -s -X POST https://example.com/api/v1/webauthn/login/options \
  -H "Content-Type: application/json" \
  -d '{"username": "max"}'

# 2. Browser führt navigator.credentials.get() aus

# 3. Ergebnis verifizieren
curl -s -X POST https://example.com/api/v1/webauthn/login/verify \
  -H "Content-Type: application/json" \
  -d '{"assertion": "..."}'
```

---

<!-- UPDATED: 2026-09-06 -->
## 7 · curl-Beispiele

### System-Status abfragen

```bash
curl -s https://example.com/api/v1/status | jq
```

```json
{
  "status": "ok",
  "version": "3.4.00"
}
```

### JWT-Token generieren und verwenden

```bash
# Token per Login erhalten (Implementierung via Auth-Endpunkt)
TOKEN="eyJhbGciOiJIUzI1NiIs..."

# Seiten auflisten
curl -s https://example.com/api/v1/pages \
  -H "Authorization: Bearer $TOKEN" | jq

# Seite per Slug laden
curl -s https://example.com/api/v1/pages/ueber-uns \
  -H "Authorization: Bearer $TOKEN" | jq
```

### Seiten durchsuchen

```bash
curl -s "https://example.com/api/v1/pages?q=kontakt" \
  -H "Authorization: Bearer $TOKEN" | jq
```

### Benutzer verwalten (Admin)

```bash
# Alle Benutzer auflisten
curl -s "https://example.com/api/v1/admin/users?page=1&limit=50" \
  -H "Authorization: Bearer $ADMIN_TOKEN" | jq

# Einzelnen Benutzer laden
curl -s https://example.com/api/v1/admin/users \
  -H "Authorization: Bearer $ADMIN_TOKEN" | jq
```

### Fehler-Beispiele

```bash
# 401 – Nicht authentifiziert
curl -s https://example.com/api/v1/pages
# → {"error": "Unauthorized"}

# 403 – Kein Admin
curl -s https://example.com/api/v1/admin/users \
  -H "Authorization: Bearer $MEMBER_TOKEN"
# → {"error": "Forbidden"}

# 404 – Seite nicht gefunden
curl -s https://example.com/api/v1/pages/nicht-vorhanden \
  -H "Authorization: Bearer $TOKEN"
# → {"error": "Page not found"}

# 429 – Rate-Limit überschritten
# → {"error": "Rate limit exceeded. Please try again later."}
```
