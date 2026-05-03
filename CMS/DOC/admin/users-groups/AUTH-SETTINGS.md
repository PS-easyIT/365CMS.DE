# 365CMS – Benutzer- & Authentifizierungseinstellungen

Kurzbeschreibung: Zentrale Admin-Seite für Registrierung, rollenbezogene Standardwerte und technische Auth-Provider-Informationen – ergänzt durch die neue Core-Auth-Strecke über die CMS Loginpage.

Letzte Aktualisierung: 2026-05-03 · Version 2.9.512

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
| `member_default_role` | Standardrolle für neue Registrierungen; wird zur Laufzeit auf registrierungsgeeignete Rollen begrenzt und direkt im Core-Registrierungsfluss angewendet |

> Hinweis: `registration_enabled` und `member_registration_enabled` werden zusätzlich von der **CMS Loginpage** verwendet. Die Core-Auth-Strecke liest damit dieselben globalen Registrierungsschalter wie der restliche Member-/User-Bereich – kein zweiter Schatten-Schalter, kein Konfigurations-Doppelgänger.

> Sicherheitsnotiz: Die Rollen-Auswahl für neue Registrierungen wird auf nicht-administrative, öffentliche Rollen eingeschränkt. Selbst wenn Altstände noch andere Werte in `cms_settings` enthalten, fällt die Registrierung fail-closed auf `member` zurück.

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

Die Passwort-Policy ist im aktuellen Stand nicht mehr nur auf die öffentliche Auth-Strecke beschränkt: Auch das Admin-Erstellen und -Bearbeiten von Benutzern verwendet denselben Vertrag mit mindestens 12 Zeichen sowie Groß-/Kleinbuchstaben, Ziffer und Sonderzeichen.

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