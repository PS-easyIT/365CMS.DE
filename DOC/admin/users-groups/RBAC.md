# 365CMS – Rollen & Berechtigungen

Kurzbeschreibung: Dokumentation der dynamischen Rollen- und Rechteverwaltung im Admin.

Letzte Aktualisierung: 2026-03-28 · Version 2.8.0 RC

---

## Überblick

Die Rechteverwaltung basiert auf Rollen und Capabilities. Anders als in älteren Dokumentationsständen werden Rollen nicht mehr ausschließlich statisch behandelt, sondern können aus dem Datenmodell heraus erweitert und in der Oberfläche gepflegt werden.

---

## Typische Aufgaben

| Aufgabe | Beschreibung |
|---|---|
| Rollen anzeigen | bestehende Rollen prüfen |
| Rechte-Matrix pflegen | Capabilities pro Rolle setzen |
| neue Rolle anlegen | zusätzliche Rollen definieren |
| neue Rechte anlegen | Capability-Satz erweitern |

---

## Datenmodell

Für die aktuelle Rollenlogik sind vor allem relevant:

- `roles`
- `role_permissions`

Die Capability-Prüfung im Core liest diese Daten mit aus, sodass neu angelegte Rollen sofort wirksam werden können.

---

## Aktueller Stand

- Rollen- und Rechteverwaltung ist dynamisch.
- Benutzerverwaltung und Rechteverwaltung greifen auf dieselbe Rollenquelle zu.
- Neue Rollen und Capabilities lassen sich direkt über die Admin-Oberfläche ergänzen.

---

## Verwandte Dokumente

- [USERS.md](USERS.md)
- [GROUPS.md](GROUPS.md)
- [../../core/SECURITY.md](../../core/SECURITY.md)

