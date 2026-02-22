# SECURITY AUDIT 2026 – 365CMS (`/CMS`)

## 1) Prüfrahmen (Best Practice 2026)

**Security-Basis 2026:** Zero-Trust-by-Default, least privilege, CSP ohne `unsafe-inline` als Zielbild, sichere Session- und Token-Lebenszyklen, auditierbare Admin-Aktionen.  
**Prüfmethode:** statische Prüfung kritischer Pfade in Core/Admin (Auth, CSRF, Dateisystem, externe Requests).

Geprüfte Stichproben:
- `CMS/core/Security.php`
- `CMS/core/Auth.php`
- `CMS/admin/media.php`
- `CMS/admin/themes.php`
- `CMS/core/Services/UpdateService.php`
- `CMS/core/CacheManager.php`

---

## 2) Executive Summary

**Gesamtstatus:** 🟡 **Gute Grundsicherheit, mehrere High-Impact-Hardening-Punkte offen.**

Stärken:
- Admin-Check (`isAdmin`) breit umgesetzt.
- CSRF-Token-Mechanik vorhanden.
- Session-Härtung (httponly/secure/strict mode + Regeneration) implementiert.

Kritische Schwerpunkte:
- Inkonsistente CSRF-Anwendung in einzelnen AJAX-Pfaden.
- CSP aktuell noch mit `unsafe-inline`.
- Dateipfad-/Dateioperationen und externe Fetch-Pfade benötigen stärkeres Hardening.

---

## 3) Positive Befunde

1. **Auth-Gate vor Admin-Seiten** konsistent eingesetzt.  
2. **CSRF-Primitiven vorhanden** (`generateToken`, `verifyToken`).  
3. **Sicherheitsheader vorhanden** (`X-Frame-Options`, `X-Content-Type-Options`, etc.).  
4. **Prepared Statements** in vielen DB-Pfaden.

---

## 4) Findings (priorisiert)

### P1-CRITICAL: CSRF-Check in Media-AJAX derzeit auskommentiert
- Evidenz: `CMS/admin/media.php` (Nonce/Verify-Block kommentiert).
- Risiko: State-changing Requests im Medienbereich können ohne wirksamen Token-Check missbraucht werden.
- 2026-Maßnahme:
  - Verpflichtender CSRF-Check für **alle** POST/AJAX-Operationen.
  - Einheitlicher Middleware-Guard statt Seiteneinzelprüfung.

### P1-HIGH: CSP enthält `unsafe-inline`
- Evidenz: `CMS/core/Security.php` (CSP-Header).
- Risiko: reduzierte XSS-Abwehrwirkung.
- 2026-Maßnahme:
  - Nonce-/Hash-basierte CSP einführen.
  - Inline-Skripte/-Styles sukzessive eliminieren.

### P1-HIGH: `serialize/unserialize` in Cache-Pfad
- Evidenz: `CMS/core/CacheManager.php`.
- Risiko: Bei manipuliertem Cachefile potenziell unsafe object handling.
- 2026-Maßnahme:
  - JSON-Storage oder restriktive sichere Deserialisierung.

### P1-HIGH: Externe Requests mit `@file_get_contents`
- Evidenz: `CMS/core/Services/UpdateService.php` (sowie weitere Stellen mit `@file_get_contents`).
- Risiko: Fehlerunterdrückung erschwert Monitoring; URL-Validierung/Timeout/Retry nicht überall konsistent.
- 2026-Maßnahme:
  - Zentralen HTTP-Client (Timeout, TLS-Checks, Retry, Circuit-Breaker, Logging) verwenden.

### P2-MEDIUM: Mehrere Admin-Flows nutzen `window.confirm()`
- Evidenz: `themes.php`, `backup.php`, `site-tables.php`, `orders.php`, u. a.
- Risiko: UX-/Sicherheitsprozess inkonsistent, kein zentral auditierbarer Confirm-Flow.
- 2026-Maßnahme:
  - Einheitliches serverbestätigtes Modal-Pattern mit CSRF/Intent-Token.

### P2-MEDIUM: Session-basiertes Rate-Limit nur lokal
- Evidenz: `Security::checkRateLimit` arbeitet über `$_SESSION`.
- Risiko: In Multi-Node-Setups und bei Sessionwechsel weniger wirksam.
- 2026-Maßnahme:
  - Persistentes Rate-Limit (DB/Redis) pro IP/User/Route.

---

## 5) 2026 Härtungs-Backlog (PHP 8.3+, MySQL/MariaDB)

1. **Security Middleware Layer** für CSRF/AuthZ/Input-Policy über alle Admin-Actions.  
2. **File-System Guard** (canonical path + allowlist + deny symlinks) vor Delete/Rename/Write.  
3. **Security Event Logging** für CSRF-Fail, Login-Fail, Permission-Denials, destructive actions.  
4. **DB-seitige Schutzmaßnahmen**: restriktive DB-User-Rechte, getrennte RW-Accounts optional.  
5. **Headerset erweitern** (HSTS bei HTTPS-only, COOP/COEP/CORP nach Kompatibilitätsprüfung).

---

## 6) Maßnahmenplan

### 0–14 Tage
- CSRF im Media-AJAX aktiv und verpflichtend machen.
- Externe Fetches zentralisieren, Fehlerunterdrückung (`@`) abbauen.
- Kritische Delete/Restore/Theme-Operationen auditierbar protokollieren.

### 15–60 Tage
- CSP-Migration auf Nonce/Hash-Strategie.
- Persistentes Rate-Limit implementieren.

### >60 Tage
- Security-Regression-Suite (automatisierte Header-, CSRF-, AuthZ-Checks) etablieren.

---

## 7) Abschlussbewertung

**Security-Reifegrad:** **B-**  
**Urteil:** Gute Basis, aber mit sofortigem Handlungsbedarf bei CSRF-Konsistenz und CSP/Fetch-Hardening.
