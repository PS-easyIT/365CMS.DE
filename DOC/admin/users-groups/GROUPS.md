# 365CMS - Gruppenverwaltung

**Stand:** 2026-09-06 | **Dokumentationsversion:** 3.4.00 | **Route:** `/admin/groups`

## Zweck

Gruppen bilden organisatorische oder fachliche Einheiten. Ein Benutzer kann mehreren Gruppen angehören. Eine Gruppe kann zusätzlich mit einem aktiven oder inaktiven Paket aus `subscription_plans` verbunden werden.

Gruppen ersetzen keine Rollen. Berechtigungen werden unter `/admin/roles` gepflegt; Gruppenmitgliedschaften und Paketbezüge werden hier verwaltet.

## Übersicht

Die Seite lädt Gruppen nach Aktivstatus und Namen. Eine Gruppenkarte kann anzeigen:

- Name, eindeutigen Slug und Beschreibung,
- Aktiv-/Inaktiv-Status,
- Mitgliederzahl und Mitgliederliste,
- verknüpftes Paket,
- Paketmodule,
- sichtbare Member-Bereiche,
- bis zu drei auslaufende oder fällige Verträge von Mitgliedern,
- Anzahl überfälliger Verträge.

Der Support-Kontext ist read-only. Er verändert keine Laufzeit, kein Paket und keine Mitgliedschaft.

## Gruppe anlegen oder bearbeiten

Das Formular beziehungsweise Modal verarbeitet in einem Flow:

- Gruppenname (Pflichtfeld),
- optionalen Slug,
- Beschreibung,
- Aktivstatus,
- Paket/Plan,
- ausgewählte Benutzer.

Fehlt ein Slug, wird er aus dem Namen erzeugt. Kollisionen werden serverseitig eindeutig aufgelöst. Benutzer-IDs und Paket-IDs werden gegen vorhandene Datensätze geprüft.

Beim Speichern wird die Mitgliedschaft innerhalb einer Datenbanktransaktion synchronisiert: bestehende Zuordnungen der Gruppe werden entfernt und die neue Auswahl wird eingefügt. Dadurch entspricht die Datenbank exakt der im Formular bestätigten Auswahl.

## Sammelaktionen

Für ausgewählte Gruppen stehen folgende serverseitig erlaubte Aktionen zur Verfügung:

| Aktion | Wirkung |
|---|---|
| `activate` | Gruppen aktivieren |
| `deactivate` | Gruppen deaktivieren |
| `set_plan` | ein vorhandenes Paket zuweisen |
| `clear_plan` | Paketbezug entfernen |
| `delete` | Gruppen samt Mitgliedschaftszeilen löschen |

Die Auswahl wird auf maximal 200 positive, eindeutige IDs begrenzt. Aktionsnamen stammen aus einer festen Allowlist. Fehlende Gruppen oder ungültige Pakete führen zu einer Fehlermeldung statt zu einer Teilverarbeitung.

Sammellöschungen und Einzel-Löschungen entfernen zuerst `user_group_members` und löschen anschließend die Gruppe innerhalb einer Transaktion. Erfolgreiche Aktionen werden als User-Audit-Ereignis protokolliert.

## Datenmodell

| Tabelle | Zweck |
|---|---|
| `user_groups` | Name, Slug, Beschreibung, `plan_id`, `is_active`, Zeitstempel |
| `user_group_members` | Zuordnung von `user_id` zu `group_id` mit `joined_at` |
| `users` | auswählbare Benutzer und Status |
| `subscription_plans` | verfügbare Pakete und Paketmodule |
| `user_subscriptions` | Vertrags- und Fälligkeitsdaten für Support-Hinweise |

## Technischer Ablauf

Der Entry-Point `/admin/groups` verwendet den gemeinsamen `section-page-shell`-Ablauf. Er prüft `manage_users`, erzeugt das Token `admin_groups`, normalisiert Einzel- und Bulk-Payloads und leitet nach POST per Redirect zurück. Die `GroupsModule`-Methoden `save()`, `delete()` und `bulkAction()` sind die einzigen fachlichen Schreibpfade.

## Abgrenzung

- Paket setzen/entfernen ändert nur den Gruppenbezug; es erstellt nicht automatisch Benutzerabos.
- Vertragsstatus wird nur angezeigt und nicht aus der Gruppenansicht heraus verlängert.
- Rolle und Capability eines Benutzers ändern sich durch eine Gruppenmitgliedschaft nicht.

## Verwandte Dokumente

- [USERS.md](USERS.md)
- [RBAC.md](RBAC.md)
- [../subscription/SUBSCRIPTION-SYSTEM.md](../subscription/SUBSCRIPTION-SYSTEM.md)
