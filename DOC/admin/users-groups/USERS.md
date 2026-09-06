# 365CMS - Benutzerverwaltung

**Stand:** 2026-09-06 | **Dokumentationsversion:** 3.4.00 | **Route:** `/admin/users`

## Zweck

Die Benutzerverwaltung ist die zentrale Admin-Oberfläche für Konten, Rollen und Kontostatus. Sie unterstützt eine paginierte Liste und einen getrennten Profil-Editor.

## Liste

Die Liste lädt standardmäßig 25 Benutzer pro Seite, sortiert nach Erstellungszeit absteigend. Verfügbar sind:

- Suche über Benutzername, Anzeigename oder E-Mail-Kontext.
- Filter nach dynamisch geladenen Rollen.
- Filter nach dynamisch geladenen Statuswerten.
- Kennzahlen für Gesamtbestand, aktive, inaktive und gesperrte Konten.
- Anzeige der Gruppenanzahl und eines kompakten Support-Kontexts.
- Seitenweise Navigation ohne Änderung von Kontodaten.

Der Support-Kontext kann ein direktes aktives Paket, bis zu drei Gruppenpakete, sichtbare Member-Bereiche und den Vertrags-/Fälligkeitsstatus enthalten. Er ist eine Orientierungshilfe und keine Paket- oder Vertragsaktion.

## Benutzer anlegen und bearbeiten

Der Editor ist erreichbar über:

- `/admin/users?action=edit` für einen neuen Benutzer.
- `/admin/users?action=edit&id=<ID>` für ein bestehendes Konto.

Pflicht- und Kernfelder sind Benutzername, E-Mail, Rolle, Status und bei neuen Konten ein Passwort. Vor dem Speichern werden Werte normalisiert und durch den User-Service validiert. Die Passwort-Policy gilt einheitlich: mindestens 12 Zeichen sowie Großbuchstabe, Kleinbuchstabe, Ziffer und Sonderzeichen.

### Rollen-Wirkungsvorschau

Bei bestehenden Konten zeigt der Editor vor dem Speichern read-only:

- aktuelle und mögliche Capabilities,
- gewonnene und verlorene Rechte,
- sichtbare Member-Bereiche,
- verfügbare Plugin-Widgets,
- mögliche Paket-/Abo-Kontextänderungen.

Die Vorschau verwendet die vorhandene Rollenmatrix, löst keine AJAX-Schreibaktion aus und verändert weder Rolle noch Paket. Erst das normale Speichern übernimmt die Auswahl.

### Sicherheitsereignisse

Bestehende Profile können begrenzte relevante Login- und Security-Audit-Ereignisse aus `audit_log` anzeigen. Roh-Metadaten, Tokens und Session-Inhalte werden nicht ausgegeben. Ist die Auditquelle nicht verfügbar, wird dies neutral angezeigt.

## Sammelaktionen

Die Benutzerliste kann mehrere Konten auswählen. Zulässige Aktionen sind:

| Aktion | Wirkung |
|---|---|
| `activate` | Benutzer aktivieren |
| `deactivate` | Benutzer deaktivieren |
| `delete` | Benutzer löschen |
| `hard_delete` | endgültige Löschung gemäß User-Service auslösen |

IDs werden serverseitig dedupliziert, auf positive Integer begrenzt und gegen tatsächlich vorhandene Benutzer geprüft. Eine leere oder ungültige Auswahl wird abgewiesen. Die konkrete Löschwirkung ist durch den User-Service definiert; vor produktiven Löschungen ist die Backup- und Datenschutzlage zu prüfen.

## Technischer Ablauf

1. Der Entry-Point normalisiert Ansicht, ID, Aktion und Bulk-Payload.
2. Der gemeinsame Section-Shell prüft Route, Capability und CSRF-Token.
3. `UsersModule` delegiert Benutzeränderungen an `UserService`.
4. Fehler werden als generische UI-Meldung und mit technischen Details nur im Server-Log behandelt.
5. Erfolgreiche Aktionen führen per Redirect zurück zur Liste oder zum gespeicherten Profil.

Die erlaubten Schreibaktionen sind `save`, `delete` und `bulk`. Unbekannte Aktionen werden nicht ausgeführt.

## Relevante Tabellen und Services

- `users`: Identität, Rolle und Status.
- `user_meta`: optionale Auth- und Profilinformationen.
- `user_groups` / `user_group_members`: Gruppenbezug.
- `user_subscriptions` / `subscription_plans`: Paketkontext.
- `audit_log`: zusammengefasste Sicherheits- und Änderungsereignisse.
- `UserService`: Validierung, Erstellung, Aktualisierung, Löschung und Bulk-Verarbeitung.

## Verwandte Dokumente

- [GROUPS.md](GROUPS.md)
- [RBAC.md](RBAC.md)
- [AUTH-SETTINGS.md](AUTH-SETTINGS.md)
