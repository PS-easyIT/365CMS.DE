# SECURITY AUDIT – 365CMS (`/CMS`)

## Scope
- Verzeichnis: `CMS/`
- Stichproben: `core/Security.php`, `core/Auth.php`, `core/Database.php`, `admin/themes.php`, `admin/performance.php`
- Fokus: AuthN/AuthZ, CSRF, Session, Input/Output, Header, Admin-Härtung

## Positiv bewertet
- **Admin-Gatekeeper** über `Auth::instance()->isAdmin()` auf Admin-Seiten.
- **CSRF-Tokens** (`generateToken`/`verifyToken`) breit eingesetzt.
- **Session-Härtung** mit `httponly`, `secure`, `use_strict_mode`, `session_regenerate_id`.
- **Output-Escaping** über `htmlspecialchars(...)` in vielen Views.
- **Prepared Statements** in zentralem DB-Layer.

## Kritische / relevante Findings
1. **CSP mit `unsafe-inline`** (`CMS/core/Security.php`)
   - Aktuelle Policy erlaubt Inline-Skripte und Inline-Styles.
   - Risiko: reduzierte Wirksamkeit gegen XSS-Klassen.

2. **Rate-Limiting nur session-basiert** (`Security::checkRateLimit`)
   - Keine zentrale Persistenz, keine globale Sicht über mehrere Sessions/Nodes.
   - Risiko: Umgehbarkeit bei Session-Rotation/Distributed Setups.

3. **Nutzung von `window.confirm()` in Admin-Flow**
   - Beispiel in `CMS/admin/themes.php` beim Löschen.
   - Sicherheitstechnisch kein direkter Bruch, aber UX-/Hardening-Defizit ggü. konsistenten Modal-Workflows.

4. **Fehlertexte direkt aus Exceptions**
   - Mehrere Stellen geben technische Details im Admin-Feedback aus.
   - Risiko: Informationsleck über interne Struktur/SQL im Fehlerfall.

## Empfehlungen (priorisiert)
### P1 – kurzfristig
- CSP schrittweise härten (Nonce-/Hash-basiert, Reduktion `unsafe-inline`).
- Admin-Fehlermeldungen auf generische User-Messages + internes Logging umstellen.
- Kritische Admin-Aktionen (Löschen/Deaktivieren) mit eigenem Confirm-Modal standardisieren.

### P2 – mittelfristig
- Persistentes Rate-Limit (DB/Cache) nach Identifier (IP, User, Endpoint).
- Security-Header-Set um `Strict-Transport-Security` (bei HTTPS-only) und `Cross-Origin-*` Policies ergänzen.
- Token-Rotation/One-Time-Tokens für besonders sensible Aktionen prüfen.

### P3 – langfristig
- Zentrales Security-Audit-Logging mit Korrelation (Login, CSRF-Fails, Rate-Limit-Events).
- Regelmäßige automatisierte Security-Regression-Checks in CI etablieren.

## Ergebnis (Ampel)
- **Gesamtstatus:** 🟡 **Gute Basissicherheit, gezielte Hardening-Maßnahmen empfohlen**
- **Haupthebel:** CSP-Härtung, persistentes Rate-Limit, sichere Fehlerkommunikation.
