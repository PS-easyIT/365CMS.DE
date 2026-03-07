# 365CMS – Sicherheits-Center

Kurzbeschreibung: Detaildokumentation der Sicherheitsseite mit Passwortformular, 2FA-Hinweisen, Session-Ansicht und Login-Verlauf.

Letzte Aktualisierung: 2026-03-07

**Route:** `/member/security`

---

## Überblick

Die View kombiniert vier Hauptblöcke:

- Sicherheits-Score und Empfehlungen
- Passwortänderung
- Zwei-Faktor-Authentifizierung
- aktive Sessions und Login-Historie

---

## Passwortänderung

Das Passwortformular arbeitet aktuell mit:

- `action=change_password`
- CSRF-Token `change_password`

Die Verarbeitung läuft über `MemberController::handleSecurityActions()` und `MemberService::changePassword()`.

---

## Zwei-Faktor-Authentifizierung

Die aktuelle View schaltet 2FA nicht über den alten Toggle-POST des Controllers um, sondern verweist auf dedizierte Routen:

- `/mfa-setup`
- `/mfa-disable`

Damit ist für die aktuelle Doku wichtig: Die Oberfläche zeigt 2FA-Status und Aktionen, die eigentliche Aktivierung/Deaktivierung ist aber in einen separaten Flow ausgelagert.

---

## Sitzungen und Login-Verlauf

Die Seite kann aktive Sitzungen mit Gerät, IP, Ort und Aktivitätszeit anzeigen.

Wichtig:

- Einzel- und Sammel-Buttons zum Beenden von Sessions sind in der aktuellen View JavaScript-Platzhalter.
- Die UI ist vorhanden, die Endpunkte müssen für produktive Nutzung separat verifiziert werden.

Zusätzlich wird ein Login-Verlauf mit Erfolg/Fehlschlag dargestellt.

---

## Sicherheits-Score

`securityData` enthält in der View u. a.:

- `score`
- `score_message`
- `password_changed`
- `2fa_enabled`
- `recommendations`
- `login_history`

---

## Verwandte Dokumente

- [PROFILE.md](PROFILE.md)
- [PRIVACY.md](PRIVACY.md)
- [../SECURITY.md](../SECURITY.md)
