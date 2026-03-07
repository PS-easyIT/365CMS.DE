# 365CMS – Sicherheit im Mitgliederbereich

Kurzbeschreibung: Sicherheitsmodell des Member-Bereichs mit Login-Pflicht, CSRF-Schutz, Sanitizing und Escaping.

Letzte Aktualisierung: 2026-03-07

---

## Sicherheitsprinzip

Der Member-Bereich folgt einem mehrschichtigen Schutzmodell:

1. Login-Pflicht vor jeder Member-Seite
2. serverseitige CSRF-Prüfung für Formulare
3. zentrale Sanitization über `getPost()`
4. Escaping in allen Views
5. Schutz gemeinsamer Services über Core-Klassen

---

## Zugriffsschutz

Die Basisprüfung erfolgt im Konstruktor des `MemberController`:

- nicht eingeloggte Nutzer werden weitergeleitet
- angemeldete Nutzer dürfen fortfahren
- Administratoren werden im aktuellen Stand nicht pauschal ausgeschlossen

---

## CSRF-Schutz

Wichtige Token-Namen im Member-Bereich:

| Kontext | Token |
|---|---|
| Profil | `member_profile` |
| Passwort ändern | `change_password` |
| 2FA umschalten | `toggle_2fa` |
| Benachrichtigungen | `member_notifications` |
| Datenschutz speichern | `privacy_settings` |
| Datenexport | `data_export` |
| Account-Löschung | `account_delete` |

Die Erzeugung und Prüfung läuft über `Security::instance()` und die Hilfsmethoden des Controllers.

---

## Eingabesäuberung

`MemberController::getPost()` unterstützt:

- `text`
- `email`
- `url`
- `textarea`
- `int`
- `bool`

Direkte Verarbeitung unbereinigter Formulardaten sollte im Member-Bereich vermieden werden.

---

## Ausgabehärtung

Für View-Dateien gilt:

- Texte mit `htmlspecialchars()` escapen
- Attribute mit `htmlspecialchars(..., ENT_QUOTES)` absichern, wenn nötig
- numerische Werte casten
- URLs nur escaped ausgeben

Besonders wichtig sind dynamische Bereiche wie:

- Notification-Farben und Labels
- Session- und Geräteinformationen
- Benutzernamen und frei pflegbare Profilfelder

---

## Datenschutz- und Löschprozesse

Die Löschanforderung läuft nicht als Sofortlöschung, sondern als markierter Prozess mit Karenzzeit. Export- und Löschfunktionen sind damit sowohl sicherheits- als auch DSGVO-relevant.

---

## Bekannte Grenzen des aktuellen Stands

Einige clientseitig sichtbare Komfortfunktionen sind eher Platzhalter bzw. Integrationspunkte als vollständig ausgebauter Security-Workflow, etwa:

- Session-Terminierung per UI
- komplette „alle Benachrichtigungen gelesen“-Automatik
- Paketauswahl mit nachgelagertem Payment-Flow

---

## Checkliste für Erweiterungen

- eigene Member-Routen auf Login prüfen
- eigene Formulare mit CSRF absichern
- Eingaben sanitizen
- Ausgaben escapen
- für Datenzugriffe Prepared Statements verwenden

---

## Verwandte Dokumente

- [README.md](README.md)
- [CONTROLLERS.md](CONTROLLERS.md)
- [HOOKS.md](HOOKS.md)
- [../core/SECURITY.md](../core/SECURITY.md)
