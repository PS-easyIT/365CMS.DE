# 365CMS – Member Profil

Kurzbeschreibung: Detaildokumentation der Profilseite unter `/member/profile` für persönliche Stammdaten und Kontoinformationen.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

**Route:** `/member/profile`

---

## Überblick

Die Profilseite rendert ein Formular für die wichtigsten Benutzerdaten und zeigt zusätzlich technische Kontoinformationen wie Rolle, Status und letzte Aktualisierung.

Die Speicherung läuft per POST mit CSRF-Token `member_profile`.

---

## Bearbeitbare Felder

| Feld | Typ | Hinweis |
|---|---|---|
| `username` | Text | öffentlicher Benutzername |
| `email` | E-Mail | Kontakt- und Login-Mail |
| `first_name` | Text | optional |
| `last_name` | Text | optional |
| `bio` | Editor/Textarea | über `EditorService`, falls verfügbar |
| `phone` | Text | optional |
| `website` | URL | optional |

---

## Aktueller UI-Stand

- Das „Avatar“-Element in der aktuellen View ist eine **Initialen-Vorschau**, kein vollständiger Avatar-Upload-Workflow.
- Passwortänderungen werden **nicht** auf dieser Seite durchgeführt, sondern im Sicherheitsbereich unter `/member/security`.
- Social-Profileingaben sind in der aktuellen Profil-View nicht Bestandteil des Standardformulars.

---

## Zusätzliche Kontoinformationen

Unterhalb des Formulars zeigt die Seite unter anderem:

- Benutzer-ID
- Rolle
- Status
- Registrierungsdatum
- letzter Login
- letzte Aktualisierung

---

## Verwandte Dokumente

- [SECURITY.md](SECURITY.md)
- [../CONTROLLERS.md](../CONTROLLERS.md)
- [../VIEWS.md](../VIEWS.md)
