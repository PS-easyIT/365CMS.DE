# 365CMS – Benutzer- & Authentifizierungseinstellungen

Kurzbeschreibung: Zentrale Admin-Seite für Registrierung, rollenbezogene Standardwerte und technische Auth-Provider-Informationen.

Letzte Aktualisierung: 2026-03-28 · Version 2.8.0 RC

Route: `/admin/user-settings`

---

## Zweck

Die Seite bündelt alle **authentifizierungsnahen Einstellungen**, die fachlich zum Bereich **Benutzer & Gruppen** gehören.

Sie ersetzt insbesondere die bisher verstreute Platzierung von Registrierungsoptionen im generischen Bereich `/admin/settings`.

---

## Bearbeitbare Einstellungen

Diese Werte werden in der Tabelle `cms_settings` gespeichert:

| Setting | Bedeutung |
|---|---|
| `registration_enabled` | Globaler Schalter für öffentliche Benutzerregistrierung |
| `member_registration_enabled` | Registrierung im Member-Bereich |
| `member_email_verification` | Erzwingt E-Mail-Verifizierung für neue Mitglieder |
| `member_default_role` | Standardrolle für neue Registrierungen |

---

## Technische Statuskarten

Zusätzlich zeigt die Seite **read-only** Informationen zu Auth-Providern und Sicherheitsvorgaben an:

- Session-Login
- Passkeys / WebAuthn
- LDAP
- TOTP / MFA
- Backup-Codes
- JWT / API-Authentifizierung
- Login-Rate-Limits
- Passwort-Policy

Diese Werte kommen aktuell überwiegend aus `CMS/config/app.php` oder aus den jeweiligen Auth-Services und werden bewusst nicht direkt über die Seite bearbeitet.

---

## Abgrenzung zu anderen Admin-Bereichen

| Bereich | Inhalt |
|---|---|
| `/admin/user-settings` | Registrierung, Authentifizierung, Provider-Status |
| `/admin/member-dashboard-general` | Dashboard-Aktivierung, Begrüßung, Header, Onboarding-nahe Texte |
| `/admin/settings` | Allgemeine System-, Inhalts- und Website-Einstellungen |

---

## Hinweise

- Die Seite folgt dem üblichen PRG-Flow mit CSRF-Prüfung und Session-Alert.
- Änderungen werden im Audit-Log als Setting-Änderung protokolliert.
- LDAP-, JWT- und Login-Limit-Parameter bleiben aktuell in der Systemkonfiguration (`CMS/config/app.php`).