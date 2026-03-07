# 365CMS – Benutzer, Gruppen & Rollen

Kurzbeschreibung: Überblick über die aktuelle Benutzerverwaltung mit dynamischen Rollen, Gruppen und Rechtezuordnung.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

Der Bereich ist im aktuellen Stand auf drei Hauptbereiche verteilt:

| Route | Zweck |
|---|---|
| `/admin/users` | Benutzerkonten und Profile |
| `/admin/groups` | Gruppen und Mitgliedschaften |
| `/admin/roles` | Rollen, Capabilities und Rechte-Matrix |

---

## Wichtige Dokumente

| Dokument | Schwerpunkt |
|---|---|
| [USERS.md](USERS.md) | Benutzerkonten und Listenansicht |
| [GROUPS.md](GROUPS.md) | Gruppenverwaltung |
| [RBAC.md](RBAC.md) | Rollen und Berechtigungen |

---

## Aktuelle Hinweise

- Rollen werden nicht mehr starr aus Hardcodes abgeleitet, sondern dynamisch geladen.
- `Auth::hasCapability()` berücksichtigt gespeicherte Rollenrechte aus `role_permissions`.
- Filter- und Dropdown-Logik der Benutzerverwaltung nutzt dieselbe Rollenquelle wie die Rechteverwaltung.

