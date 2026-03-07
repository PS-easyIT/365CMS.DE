# 365CMS – Gruppenverwaltung

Kurzbeschreibung: Verwaltung von Benutzergruppen, Mitgliedschaften und Paketbezügen im Admin-Bereich.

Letzte Aktualisierung: 2026-03-07

**Admin-Route:** `/admin/groups`

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
- Für Rechte und Capabilities ist heute vorrangig die Rollenverwaltung unter `/admin/roles` zuständig.

---

## Verwandte Dokumente

- [USERS.md](USERS.md)
- [RBAC.md](RBAC.md)
- [../subscription/SUBSCRIPTION-SYSTEM.md](../subscription/SUBSCRIPTION-SYSTEM.md)

