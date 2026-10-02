# 365CMS – Projektdokumentation | Abschnitt: Admin – Weiterleitungen & 404-Monitor

> **Stand:** 2026-10-02 | **Version:** 3.4.00 (Changelog bis 3.4.08) | **Status:** Stable
> **Routen:** `/admin/redirect-manager`, `/admin/not-found-monitor` | **Capability:** `manage_settings` | **Core-Modul:** `seo`

## English (summary)

Redirect rules are managed at `/admin/redirect-manager` (`CMS/admin/redirect-manager.php` → `CMS/admin/modules/seo/RedirectManagerModule.php` → `CMS\Services\RedirectService`). The router checks every request against `cms_redirect_rules` (`Router` → `RedirectService::findRedirect()`); unmatched requests that end in a 404 are logged in `cms_not_found_logs` and listed at `/admin/not-found-monitor`, where a redirect can be created directly.

- Redirect actions: `save_redirect`, `delete_redirect`, `delete_redirects_by_slug`, `toggle_redirect`, `clear_logs`.
- 404 monitor actions: `save_redirect`, `clear_logs`.
- Types: `301` (default) and `302`. Optional site scope (host) per rule.

## Deutsch

### Weiterleitungen (`/admin/redirect-manager`)

| Feld | Hinweis |
|---|---|
| Quellpfad (`source_path`) | Pfad ab `/`, z. B. `/alter-artikel`; `/` allein ist nicht erlaubt |
| Ziel (`target_url`) | interner Pfad oder absolute `http(s)`-URL; darf nicht identisch mit der Quelle sein |
| Typ (`redirect_type`) | `301` (dauerhaft, Standard) oder `302` (temporär) |
| Site-Scope (`site_scope`) | optionaler Host (z. B. für Hub-Domains); leer = alle Domains |
| Aktiv (`is_active`) | Regeln lassen sich per `toggle_redirect` pausieren |
| Notiz | freier Kommentar |

Pro Kombination aus Quelle und Site-Scope ist nur **eine** Regel erlaubt. Jede Regel zählt Treffer (`hits`) und den letzten Aufruf (`last_hit_at`).

`delete_redirects_by_slug` entfernt alle Regeln, die auf einen bestimmten Slug zeigen (z. B. nach dem Löschen eines Beitrags).

### Automatische Weiterleitungen

Ist unter *SEO → Technisches SEO* `auto_redirect_slug` aktiv, legt `RedirectService::createAutomaticRedirect()` bei einer Slug-Änderung von Seiten/Beiträgen eine 301-Regel vom alten auf den neuen Pfad an (Notiz „Automatisch bei Slug-Änderung angelegt“).

### 404-Monitor (`/admin/not-found-monitor`)

- Liste der nicht gefundenen Pfade mit Host, Methode, Referrer, Anzahl, erstem und letztem Auftreten.
- Direkt aus einer Zeile eine Weiterleitung anlegen (`save_redirect`).
- `clear_logs` leert das Protokoll.

**Nicht protokolliert** werden: `/`, Pfade unter `/admin`, `/member`, `/api`, `/assets`, `/uploads`, `/vendor`, statische Dateien (`.css`, `.js`, Bilder, Schriften, `.pdf`, `.xml`, `.txt` …) sowie `/favicon.ico`, `/robots.txt`, `/sitemap.xml`.

### Ablauf einer Anfrage

1. `Router` prüft vor dem Routing `RedirectService::findRedirect($path, $host)`; passt eine aktive Regel (Host-spezifisch vor global), wird mit dem Regeltyp umgeleitet und der Trefferzähler erhöht.
2. Findet keine Route, ruft `Router::render404()` `logNotFound()` auf (einmal je Anfrage) und liefert die 404-Seite des Themes.

### Datenschutz

Das 404-Protokoll speichert IP-Adresse und User-Agent im Klartext. Protokoll regelmäßig leeren (`clear_logs`) oder über die Datenbankwartung bereinigen und in der Datenschutzerklärung berücksichtigen.

### Tabellen

`cms_redirect_rules` (`source_path`, `site_scope`, `target_url`, `redirect_type`, `is_active`, `notes`, `hits`, `last_hit_at`) und `cms_not_found_logs` (`request_path`, `request_host`, `request_method`, `referrer_url`, `ip_address`, `user_agent`, `hit_count`, `first_seen_at`, `last_seen_at`). Beide legt `RedirectService::ensureTables()` bei Bedarf an.

### Verwandte Dokumente

[SEO.md](SEO.md) · [../pages-posts/SETTINGS.md](../pages-posts/SETTINGS.md) · [../pages-posts/HUBSITES.md](../pages-posts/HUBSITES.md)
