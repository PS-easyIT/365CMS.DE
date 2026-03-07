# 365CMS – Benutzerverwaltung

Kurzbeschreibung: Verwaltung von Benutzerkonten, Status, Rollen, Gruppenbezug und Bearbeitungsabläufen im Admin.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

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
| Statussteuerung | aktivieren, deaktivieren, sperren |
| Gruppenbezug | Mitgliedschaften sichtbar machen |

---

## Aktueller Stand

Wichtige Korrekturen der neueren Releases:

- Rollen-Dropdowns und Filter greifen auf die gleiche dynamische Rollenquelle zu wie die Rechteverwaltung.
- Capability-Prüfungen arbeiten mit den in `role_permissions` gespeicherten Rechten.
- Medien- und andere abhängige Bereiche können Benutzerzustände konsistenter auswerten.

---

## Datenbasis

Relevant sind insbesondere:

- `users`
- `user_meta`
- `sessions`
- `login_attempts`
- `failed_logins`

---

## Verwandte Dokumente

- [GROUPS.md](GROUPS.md)
- [RBAC.md](RBAC.md)
- [../member/README.md](../member/README.md)

