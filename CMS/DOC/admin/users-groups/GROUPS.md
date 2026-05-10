# 365CMS – Gruppenverwaltung

Kurzbeschreibung: Verwaltung von Benutzergruppen, Mitgliedschaften und Paketbezügen im Admin-Bereich.

Letzte Aktualisierung: 2026-05-10 · Version 2.9.712

---

## Überblick

Gruppen ergänzen das Rollenmodell um organisatorische und funktionale Zuordnungen. Ein Benutzer kann mehreren Gruppen angehören; Gruppen können wiederum für Paket- und Featurezuordnungen herangezogen werden.

---

## Typische Aufgaben

| Aufgabe | Beschreibung |
|---|---|
| Gruppe anlegen | neue organisatorische Einheit erstellen |
| Mitglieder zuordnen | Benutzer zu Gruppen hinzufügen oder entfernen |
| Paketbezug sichtbar machen | Gruppen mit Abo- oder Planlogik verbinden |
| Überblick behalten | Gruppenstatus und Mitgliederzahlen prüfen |

---

## Datenmodell

Im aktuellen Basisschema sind insbesondere relevant:

- `user_groups`
- `user_group_members`
- `subscription_plans`

---

## Aktuelle Hinweise

- Gruppen sind stärker mit der Paket- und Zuweisungslogik verzahnt als ältere Dokumentationsstände vermuten lassen.
- Das Admin-Modal pflegt jetzt Name, optionalen Slug, Aktiv-Status und Mitgliederzuordnung in einem Flow; fehlende Slugs werden serverseitig eindeutig erzeugt.
- Gruppen können direkt mit einem Paket/Plan aus `subscription_plans` verknüpft werden, damit gruppenbasierte Abo-Zuordnungen nicht mehr nur auf Datenbankebene existieren.
- Gruppen-Löschungen entfernen Mitgliedschaften transaktional, damit keine verwaisten `user_group_members`-Zuordnungen zurückbleiben.
- Die Gruppenübersicht unterstützt Sammelaktionen für Aktivieren, Deaktivieren, Paket setzen/entfernen und Löschen. Bulk-IDs und Aktionsnamen werden serverseitig gegen feste Allowlists validiert; Sammellöschungen löschen Mitgliedschaften und Gruppen innerhalb einer Transaktion und schreiben einen Audit-Eintrag.
- Für Rechte und Capabilities ist heute vorrangig die Rollenverwaltung unter `/admin/roles` zuständig.

---

## Verwandte Dokumente

- [USERS.md](USERS.md)
- [RBAC.md](RBAC.md)
- [../subscription/SUBSCRIPTION-SYSTEM.md](../subscription/SUBSCRIPTION-SYSTEM.md)

