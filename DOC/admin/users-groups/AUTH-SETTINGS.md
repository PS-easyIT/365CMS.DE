# 365CMS – Benutzer- & Authentifizierungseinstellungen

Kurzbeschreibung: Zentrale Admin-Seite für Registrierung, rollenbezogene Standardwerte und technische Auth-Provider-Informationen – ergänzt durch die neue Core-Auth-Strecke über die CMS Loginpage.

Letzte Aktualisierung: 2026-04-07 · Version 2.9.0

Route: `/admin/user-settings`

---

## Zweck

Die Seite bündelt alle **authentifizierungsnahen Einstellungen**, die fachlich zum Bereich **Benutzer & Gruppen** gehören.

Sie ersetzt insbesondere die bisher verstreute Platzierung von Registrierungsoptionen im generischen Bereich `/admin/settings`.

Für die **visuelle und textliche Steuerung der öffentlichen Auth-Seiten** gibt es zusätzlich die eigenständige Admin-Seite [`/admin/cms-loginpage`](../themes-design/CMS-LOGINPAGE.md). Dort werden Darstellung, Texte, Rechtslinks und Reset-Mail-Texte gepflegt; hier unter `/admin/user-settings` bleiben dagegen die fachlichen Auth-Grundschalter und Provider-Informationen gebündelt.

---

## Bearbeitbare Einstellungen

Diese Werte werden in der Tabelle `cms_settings` gespeichert:

| Setting | Bedeutung |
|---|---|
| `registration_enabled` | Globaler Schalter für öffentliche Benutzerregistrierung |
| `member_registration_enabled` | Registrierung im Member-Bereich |
| `member_email_verification` | Erzwingt E-Mail-Verifizierung für neue Mitglieder |
| `member_default_role` | Standardrolle für neue Registrierungen |

> Hinweis: `registration_enabled` und `member_registration_enabled` werden zusätzlich von der **CMS Loginpage** verwendet. Die Core-Auth-Strecke liest damit dieselben globalen Registrierungsschalter wie der restliche Member-/User-Bereich – kein zweiter Schatten-Schalter, kein Konfigurations-Doppelgänger.

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

Die öffentliche Anmeldestrecke arbeitet seit `2.9.0` standardmäßig über:

- `/cms-login`
- `/cms-register`
- `/cms-password-forgot`

Legacy-Pfade wie `/login`, `/register` und `/forgot-password` werden intern weiter auf die Core-Auth-Strecke gezogen.

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
- MFA-, Backup-Code-, Passkey- und LDAP-Logins finalisieren seit `2.9.0` dieselbe Session wie der klassische Passwort-Login. Wenn ein MFA-Nutzer also früher auf die Startseite zurückfiel: genau dieser kleine Zirkus wurde hiermit beendet.