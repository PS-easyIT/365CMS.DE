# 365CMS - Benutzer- und Authentifizierungseinstellungen

**Stand:** 2026-09-06 | **Dokumentationsversion:** 3.4.00 | **Route:** `/admin/user-settings`

## Zweck

Die Seite bündelt fachliche Authentifizierungs- und Registrierungsoptionen. Die visuelle Gestaltung, Texte, Rechtslinks und Reset-Mail-Texte der öffentlichen Seiten werden getrennt unter [`/admin/cms-loginpage`](../themes-design/CMS-LOGINPAGE.md) gepflegt.

## Bearbeitbare Einstellungen

Die folgenden Werte liegen in `settings` beziehungsweise `cms_settings` (je nach Datenbankpräfix und Schema):

| Schlüssel | Bedeutung |
|---|---|
| `registration_enabled` | globale öffentliche Registrierung |
| `member_registration_enabled` | Registrierung im Member-Bereich |
| `member_email_verification` | E-Mail-Verifizierung für neue Mitglieder |
| `member_default_role` | Standardrolle für neue Registrierungen |

Die Standardrolle wird zur Laufzeit auf registrierungsgeeignete, nicht-administrative Rollen begrenzt. Ungültige Altwerte fallen fail-closed auf `member` oder die erste zulässige Rolle zurück. Die öffentlichen Auth-Seiten und der Admin verwenden damit dieselben Registrierungsschalter.

## Provider- und Sicherheitsstatus

Die Statuskarten sind read-only und zeigen unter anderem:

- Session-Login,
- Passkeys/WebAuthn,
- TOTP/MFA,
- Backup-Codes,
- LDAP inklusive Extension-, Konfigurations-, SSL/TLS- und Sync-Status,
- JWT/API-Authentifizierung inklusive TTL und Issuer-Status,
- Login-Limit und Timeout,
- aktuelle Passwort-Policy,
- Auth-Statistiken für aktive Konten, MFA, Backup-Codes und Passkey-Credentials.

Secrets werden nicht angezeigt. LDAP-Synchronisation verarbeitet maximal 250 Konten pro Lauf und wird separat als POST-Aktion ausgelöst.

## Passwort-Policy

Die Runtime-Policy gilt für öffentliche Registrierung, Passwort-Reset sowie Admin-Erstellung und -Bearbeitung:

- mindestens 12 Zeichen,
- mindestens ein Großbuchstabe,
- mindestens ein Kleinbuchstabe,
- mindestens eine Ziffer,
- mindestens ein Sonderzeichen.

Der lokale Policy-Tester prüft nur die Eingabe im Browser. Die Testeingabe wird nicht gespeichert und nicht mit dem Settings-Formular übertragen.

## Öffentliche Auth-Routen

Die Core-Strecke verwendet:

- `/cms-login`
- `/cms-register`
- `/cms-password-forgot`

Legacy-Pfade wie `/login`, `/register` und `/forgot-password` werden intern auf die aktuelle Strecke geführt. Passkeys, MFA, Backup-Codes und LDAP finalisieren die gleiche Session wie der Passwort-Login.

## Speichern und Synchronisieren

1. Zugriff wird mit Admin-Status und `manage_users` geprüft.
2. POST wird mit `admin_user_settings` geschützt.
3. `save` schreibt ausschließlich die vier Allowlist-Settings.
4. `sync_ldap` startet ausdrücklich die LDAP-Synchronisation.
5. Beide Aktionen protokollieren ein Audit-Ereignis.
6. Nach der Verarbeitung erfolgt Redirect mit generischem Session-Alert.

LDAP-, JWT- und Login-Limit-Parameter bleiben technische Konfiguration und werden nicht über diese Seite editiert. Typische Quellen sind `CMS/config/app.php` und die jeweiligen Auth-Services.

## Fehler- und Datenschutzverhalten

- Technische Providerfehler werden serverseitig protokolliert.
- Die Admin-Oberfläche erhält keine internen Exception-Texte oder Secrets.
- Fehlende optionale Erweiterungen werden als nicht verfügbar angezeigt.
- Änderungen an Registrierung und Standardrolle wirken auf neue Registrierungen; bestehende Konten werden nicht automatisch umgerollt.

## Verwandte Dokumente

- [USERS.md](USERS.md)
- [RBAC.md](RBAC.md)
- [../themes-design/CMS-LOGINPAGE.md](../themes-design/CMS-LOGINPAGE.md)
