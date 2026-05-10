# 365CMS – Rollen & Berechtigungen

Kurzbeschreibung: Dokumentation der dynamischen Rollen- und Rechteverwaltung im Admin.

Letzte Aktualisierung: 2026-05-10 · Version 2.9.729

---

## Überblick

Die Rechteverwaltung basiert auf Rollen und Capabilities. Anders als in älteren Dokumentationsständen werden Rollen nicht mehr ausschließlich statisch behandelt, sondern können aus dem Datenmodell heraus erweitert und in der Oberfläche gepflegt werden.

---

## Typische Aufgaben

| Aufgabe | Beschreibung |
|---|---|
| Rollen anzeigen | bestehende Rollen prüfen |
| Rechte-Matrix pflegen | Capabilities pro Rolle setzen |
| Rollen vergleichen | gemeinsame und abweichende Capabilities zweier Rollen read-only prüfen |
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
- Seit `2.9.729` kann `/admin/roles` zwei Rollen direkt vergleichen. Der Vergleich nutzt nur GET-Parameter, normalisiert beide Rollen serverseitig gegen die bekannten Rollen und zeigt gemeinsame sowie nur einseitig gesetzte Capabilities gruppiert an. Dadurch entsteht kein zusätzlicher Schreib-, CSRF- oder Sicherheitstoken-Pfad.

## Rollenvergleich / Capability-Diff

Der Rollenvergleich ist als Audit- und Least-Privilege-Werkzeug gedacht. Typische Prüfungen sind:

- Welche zusätzlichen Rechte besitzt `editor` gegenüber `author`?
- Haben eigene Rollen unerwartet mehr Rechte als die Vorlage?
- Welche Capabilities fehlen einer Rolle gegenüber einer internen Zielrolle?

Ungültige oder manipulierte Vergleichsrollen fallen auf bekannte Defaults bzw. die erste passende Rolle zurück. Die Anzeige schreibt keine Daten, ruft keine Speichern-Aktion auf und verwendet dieselbe bereits geladene Rechte-Matrix wie die Bearbeitungsansicht.

---

## Verwandte Dokumente

- [USERS.md](USERS.md)
- [GROUPS.md](GROUPS.md)
- [../../core/SECURITY.md](../../core/SECURITY.md)

