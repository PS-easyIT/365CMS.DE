# 365CMS - Benutzer, Gruppen und Rollen

**Stand:** 2026-09-06 | **Dokumentationsversion:** 3.4.00 | **Status:** Aktuelle Admin-Referenz

Diese Dokumentation beschreibt den gesamten Admin-Bereich **Benutzer & Gruppen**. Die Seiten sind fachlich getrennt, verwenden aber dieselbe Authentifizierungs-, Rollen- und Sicherheitslogik.

## Schnellzugriff

| Route | Zweck | Schreibberechtigung |
|---|---|---|
| `/admin/users` | Benutzer suchen, anlegen, bearbeiten, aktivieren, sperren und löschen | `manage_users` |
| `/admin/groups` | Gruppen, Mitgliedschaften, Aktivstatus und Paketbezug verwalten | `manage_users` |
| `/admin/roles` | Rollen, Capabilities und Berechtigungsmatrix pflegen | `manage_users` |
| `/admin/user-settings` | Registrierung, Standardrolle und Auth-Provider-Status | `manage_users` |

Die Sidebar registriert diese vier Seiten als gemeinsame Gruppe. `/admin/rbac` gehört zum Legacy-/Kompatibilitätsbereich und ist nicht die primäre Pflegeoberfläche der aktuellen Rollenmatrix.

## Dokumente

| Dokument | Inhalt |
|---|---|
| [USERS.md](USERS.md) | Benutzerliste, Profil-Editor, Filter, Support-Kontext und Sammelaktionen |
| [GROUPS.md](GROUPS.md) | Gruppenmodell, Mitglieder, Paketzuordnung und transaktionale Aktionen |
| [RBAC.md](RBAC.md) | Rollen, Capabilities, Rechte-Matrix und Rollenvergleich |
| [AUTH-SETTINGS.md](AUTH-SETTINGS.md) | Registrierung, Passwort-Policy, Provider-Status, LDAP und JWT |

## Gemeinsamer Bedienablauf

1. Seite über die Sidebar oder die dokumentierte Route öffnen.
2. Lesen und Filtern erfolgt über GET-Parameter.
3. Schreibende Aktionen werden ausschließlich per POST ausgeführt.
4. Das Formular überträgt ein seitenbezogenes CSRF-Token.
5. Die Capability `manage_users` wird zusammen mit dem Admin-Status geprüft.
6. Nach der Aktion erfolgt ein Redirect (PRG). Das Ergebnis erscheint als Session-Alert.
7. Erfolgreiche und sicherheitsrelevante Änderungen werden im Audit-Log protokolliert.

## Rollen, Gruppen und Pakete richtig unterscheiden

- **Rolle:** technische Berechtigungs- und Capability-Zuordnung.
- **Gruppe:** organisatorische Mitgliedschaft; ein Benutzer kann mehreren Gruppen angehören.
- **Paket/Plan:** fachliche Leistungs- oder Modulzuordnung, direkt am Benutzer oder an einer Gruppe.
- **Member-Bereich:** aus Rolle und Konfiguration abgeleitete sichtbare Bereiche; er ersetzt weder Rolle noch Paket.

Ein Rollenwechsel ändert bestehende Gruppenmitgliedschaften und Abos nicht automatisch. Die Wirkungsvorschau zeigt mögliche Auswirkungen nur lesend an.

## Sicherheits- und Datenschutzregeln

- Direkte Aufrufe ohne Admin-Status und `manage_users` werden abgewiesen.
- POST-Aktionen akzeptieren nur bekannte Aktionsnamen und normalisierte IDs.
- Tokens, Passwörter, Session-Inhalte und Roh-Provider-Secrets werden nicht in der Oberfläche angezeigt.
- Fehlermeldungen bleiben für Administratoren generisch; technische Details werden serverseitig protokolliert.
- Support-Kontext, Rollenvergleich und Provider-Karten sind read-only und ändern keine Geschäftsdaten.

## Datenbasis

Je nach Funktion werden unter anderem `users`, `user_meta`, `roles`, `role_permissions`, `user_groups`, `user_group_members`, `settings`, `subscription_plans`, `user_subscriptions`, `sessions`, `login_attempts`, `failed_logins`, `audit_log` und bei Passkeys `passkey_credentials` verwendet.

## Betriebs-Hinweise

- Änderungen an Rollen und Capabilities wirken auf nachfolgende Capability-Prüfungen.
- Gruppenlöschungen entfernen zuerst die zugehörigen Mitgliedschaften.
- LDAP-Synchronisation ist eine ausdrücklich auszulösende Schreibaktion und auf 250 Datensätze pro Lauf begrenzt.
- Bei fehlenden optionalen Tabellen zeigen die Übersichten einen neutralen Zustand statt unvollständige Sicherheitsdaten zu erfinden.
