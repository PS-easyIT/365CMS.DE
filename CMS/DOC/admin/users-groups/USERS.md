# 365CMS – Benutzerverwaltung

Kurzbeschreibung: Verwaltung von Benutzerkonten, Status, Rollen, Gruppenbezug und Bearbeitungsabläufen im Admin.

Letzte Aktualisierung: 2026-05-10 · Version 2.9.731

---

## Überblick

Die Benutzerverwaltung ist die zentrale Oberfläche für alle registrierten Accounts. Sie bündelt Listenansicht, Filter, Bearbeitung und statusabhängige Aktionen.

---

## Typische Funktionen

| Funktion | Beschreibung |
|---|---|
| Suche | Benutzer schnell nach Name oder Mail finden |
| Rollenfilter | Ansicht nach dynamisch verfügbaren Rollen eingrenzen |
| Bearbeiten | Profil- und Kontodaten ändern |
| Sicherheitsereignisse | Begrenzte Login-/Security-Audit-Einträge direkt im Profil prüfen |
| Statussteuerung | aktivieren, deaktivieren, sperren |
| Gruppenbezug | Mitgliedschaften sichtbar machen |

---

## Aktueller Stand

Wichtige Korrekturen der neueren Releases:

- Rollen-Dropdowns und Filter greifen auf die gleiche dynamische Rollenquelle zu wie die Rechteverwaltung.
- Capability-Prüfungen arbeiten mit den in `role_permissions` gespeicherten Rechten.
- Die Listenansicht zeigt den Gruppenbezug pro Benutzer wieder sichtbar als Gruppenanzahl an.
- Bulk-Aktionen benennen die gewählte Operation jetzt explizit und bleiben ohne valide Auswahl/Aktion gesperrt.
- Benutzererstellung und -bearbeitung erzwingen dieselbe Passwort-Policy wie Registrierung und Passwort-Reset: mindestens 12 Zeichen plus Groß-/Kleinbuchstaben, Ziffer und Sonderzeichen.
- Bestehende Benutzerprofile zeigen die letzten relevanten Login- und Sicherheitsereignisse aus `audit_log` read-only an; bei Audit-Log-Problemen fällt die Karte fail-soft auf einen neutralen Hinweis zurück.
- Interne Exception-Texte aus Benutzer-Speichern oder -Löschen werden seit `2.9.731` nur noch serverseitig protokolliert; Admin-Alerts und Fehlerreport-Payloads bleiben generisch.
- Medien- und andere abhängige Bereiche können Benutzerzustände konsistenter auswerten.

---

## Datenbasis

Relevant sind insbesondere:

- `users`
- `user_meta`
- `sessions`
- `login_attempts`
- `failed_logins`
- `audit_log` für zusammenfassende Login-/Sicherheitsereignisse im Profil

---

## Verwandte Dokumente

- [GROUPS.md](GROUPS.md)
- [RBAC.md](RBAC.md)
- [../member/README.md](../member/README.md)

