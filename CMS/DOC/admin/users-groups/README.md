# 365CMS – Benutzer, Gruppen & Rollen

Kurzbeschreibung: Überblick über die aktuelle Benutzerverwaltung mit dynamischen Rollen, Gruppen und Rechtezuordnung.

Letzte Aktualisierung: 2026-05-09 · Version 2.9.619

Der Bereich ist im aktuellen Stand auf vier Hauptbereiche verteilt:

| Route | Zweck |
|---|---|
| `/admin/users` | Benutzerkonten und Profile |
| `/admin/groups` | Gruppen und Mitgliedschaften |
| `/admin/roles` | Rollen, Capabilities und Rechte-Matrix |
| `/admin/user-settings` | Registrierung, Authentifizierung und Provider-Status |

---

## Wichtige Dokumente

| Dokument | Schwerpunkt |
|---|---|
| [USERS.md](USERS.md) | Benutzerkonten und Listenansicht |
| [GROUPS.md](GROUPS.md) | Gruppenverwaltung |
| [RBAC.md](RBAC.md) | Rollen und Berechtigungen |
| [AUTH-SETTINGS.md](AUTH-SETTINGS.md) | Registrierung, Login-Schutz, LDAP, JWT, Passkeys |

---

## Aktuelle Hinweise

- Rollen werden nicht mehr starr aus lokalen Auth-Hartcodes abgeleitet, sondern über die gemeinsame Rollenmatrix geladen.
- `Auth::hasCapability()` löst Nicht-Admin-Capabilities über dieselbe Rollenquelle auf wie Rollenverwaltung und Benutzer-Service, inklusive der weiterhin produktiv genutzten Legacy-Core-Capabilities wie `manage_settings`, `manage_users`, `manage_pages`, `edit_all_posts` oder `manage_media`.
- Filter- und Dropdown-Logik der Benutzerverwaltung nutzt dieselbe Rollenquelle wie die Rechteverwaltung.
- Benutzer- und Authentifizierungseinstellungen sind jetzt unter `/admin/user-settings` zentralisiert.
- Öffentliche Registrierungen respektieren die unter `/admin/user-settings` gewählte Standardrolle jetzt tatsächlich, wobei nur registrierungsgeeignete, nicht-administrative Rollen angeboten und angenommen werden.
- Die Passwort-Policy ist für öffentliche Registrierung, Passwort-Reset sowie Admin-Erstellen/-Bearbeiten von Benutzern auf denselben 12-Zeichen-/Komplexitätsvertrag vereinheitlicht; Default-Theme- und Core-Auth-Formulare bewerben jetzt denselben Vertrag auch sichtbar im UI.
- Gruppen pflegen nun neben Name/Beschreibung auch Slug, Paketbezug, Aktiv-Status und Mitgliedschaften direkt in `/admin/groups`.
- Passkeys/WebAuthn, MFA/TOTP, Backup-Codes, LDAP und Session-/Registrierungsparameter werden im aktuellen Stand als zusammenhängender Auth-/Provider-Kontext gelesen.

