# 365CMS – Rollen & Berechtigungen

Kurzbeschreibung: Dokumentation der dynamischen Rollen- und Rechteverwaltung im Admin.

Letzte Aktualisierung: 2026-04-07 · Version 2.9.0

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
- Die gemeinsame Rollenmatrix enthält neben modernen `pages.*`-/`settings.*`-Rechten auch die weiterhin produktiv genutzten Legacy-Core-Capabilities wie `manage_settings`, `manage_users`, `manage_pages`, `edit_all_posts`, `manage_media` oder `view_analytics`.
- `Auth::hasCapability()` löst Nicht-Admin-Rechte über diese gemeinsame Matrix auf, statt auf lokale Rollenhartcodes zurückzufallen.

---

## Verwandte Dokumente

- [USERS.md](USERS.md)
- [GROUPS.md](GROUPS.md)
- [../../core/SECURITY.md](../../core/SECURITY.md)

