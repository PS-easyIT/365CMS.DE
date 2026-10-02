# 365CMS – Projektdokumentation | Abschnitt: Core – API-Referenz

> **Stand:** 2026-10-02 | **Version:** 3.4.00 (Changelog bis 3.4.08) | **Status:** Stable

## English (summary)

The JSON API is registered by `CMS\Routing\ApiRouter` (`CMS/core/Routing/ApiRouter.php`) and – for the generic `pages` endpoint – handled by `CMS\Api`. Requests below `/api` run in runtime mode `api` (`Cache-Control: no-store`). Authentication is the regular CMS session cookie; admin endpoints additionally require role `admin` and a capability, state-changing admin endpoints a CSRF token. There is no public write API, no CORS configuration and – in 3.4.00 – no Bearer/JWT authentication on these routes.

## Deutsch

### Übersicht

| Methode | Route | Zugriff | Antwort |
|---|---|---|---|
| GET | `/api/v1/status` | öffentlich | `{"status":"ok","version":"3.4.00"}` |
| GET | `/api/v1/pages?q=<suche>` | angemeldet | `{"data":[…]}` – Seitensuche (`PageManager::search()`, leere Suche → leere Liste) |
| GET | `/api/v1/pages/:slug` | angemeldet | `{"data":{…}}` oder `404 {"error":"Page not found"}` |
| POST | `/api/v1/analytics/web-vitals` | gleiche Herkunft | `204` |
| GET | `/api/v1/admin/posts` | Admin + `edit_all_posts` | `{"data":[…],"total":n,"page":p,"limit":l}` |
| GET | `/api/v1/admin/pages` | Admin + `manage_pages` | wie oben |
| GET | `/api/v1/admin/users` | Admin + `manage_users` | wie oben |
| GET | `/api/v1/admin/mail/logs` | Admin + `manage_settings` | Mail-Log-Seite |
| POST | `/api/v1/admin/mail/test` | Admin + CSRF `admin_mail_api` | Ergebnis des Testversands |
| POST | `/api/v1/admin/graph/test` | Admin + CSRF `admin_mail_api` | Ergebnis des Graph-Tests |
| POST | `/api/upload` | angemeldet + CSRF `media_action` | Upload-Ergebnis (`FileUploadService`) |
| GET/POST | `/api/media` | Editor-Token `editorjs_media` | Editor.js-Medien (Upload, Bibliothek, Remote-Bild) |

### Allgemeines

- **Format:** `Content-Type: application/json`; Fehler immer als `{"error":"…"}`.
- **Statuscodes:** 200, 204, 400 (ungültige Eingabe), 401 (nicht angemeldet), 403 (Rechte/CSRF/Herkunft), 404, 413 (zu groß), 422 (ungültiges JSON), 429 (Rate-Limit, Header `Retry-After: 60`), 500 (generische Meldung, Details nur im Log).
- **Rate-Limit `CMS\Api`:** 60 Anfragen je 60 Sekunden je IP (Tabelle `cms_login_attempts`, Aktion `api`).
- **Web Vitals:** Body max. 8 KB, max. 40 Meldungen je Minute (Cache-basiert), nur Same-Origin.

### Parameter der Admin-Listen

| Endpunkt | Parameter |
|---|---|
| `/api/v1/admin/posts` | `page` (≥1), `limit` (5–100, Standard 20), `status` (`all`, `published`, `scheduled`, `draft`, `private`, `trash`), `search`, `sort` (`title`, `status`, `published_at`, `views`, `updated_at`, `created_at`), `order` |
| `/api/v1/admin/pages` | `page`, `limit`, `status` (`published`, `draft`, `private`), `search`, `sort` (`title`, `slug`, `status`, `updated_at`, `created_at`), `order` |
| `/api/v1/admin/users` | `page`, `limit`, `search`, `role` (Rolle, `all` oder `banned`), `sort` (`username`, `email`, `display_name`, `role`, `status`, `created_at`), `order` |
| `/api/v1/admin/mail/logs` | `page`, `limit` (10–200), `search`, `status` |

Beiträge enthalten zusätzlich `effective_status` (`scheduled`, wenn `published_at` in der Zukunft liegt).

### Beispiele

```bash
# Status (öffentlich)
curl -s https://example.com/api/v1/status

# Beitragsliste als angemeldeter Administrator (Session-Cookie aus dem Browser)
curl -s -b "PHPSESSID=<session>" "https://example.com/api/v1/admin/posts?status=scheduled&limit=50"
```

```js
// Upload aus einer Admin-Seite (Token im Template erzeugt: Security::generateToken('media_action'))
const body = new FormData();
body.append('file', fileInput.files[0]);
body.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
const res = await fetch('/api/upload', { method: 'POST', body, credentials: 'same-origin' });
```

### Eigene Endpunkte

Plugins registrieren Routen im Hook `register_routes`. Für JSON-Endpunkte unter `/api/…` gilt: Modus `api` (no-store), eigene Authentifizierung/Capability-Prüfung, CSRF für schreibende Browser-Requests, Antwort mit `json_encode(…, JSON_UNESCAPED_UNICODE)`. Siehe [../admin/ADMIN-API-AJAX.md](../admin/ADMIN-API-AJAX.md) und [../workflow/API-INTEGRATION-WORKFLOW.md](../workflow/API-INTEGRATION-WORKFLOW.md).

### Hinweis JWT

`CMS\Services\JwtService` (HMAC mit `JWT_SECRET`, Fallback `AUTH_KEY`; Laufzeit `JWT_TTL`; Refresh-Tokens) ist vorhanden, wird aber von keiner Route zur Anmeldung ausgewertet. Für Maschine-zu-Maschine-Zugriffe muss ein Plugin den `Authorization: Bearer`-Header selbst prüfen (`JwtService::validateToken()`).

### Verwandte Dokumente

[ARCHITECTURE.md](ARCHITECTURE.md) · [SECURITY.md](SECURITY.md) · [../admin/ADMIN-API-AJAX.md](../admin/ADMIN-API-AJAX.md)
