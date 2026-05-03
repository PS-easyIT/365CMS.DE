# 365CMS – Admin-Dashboard

Kurzbeschreibung: Überblick über die Startseite des Admin-Bereichs mit KPI-Karten, Statushinweisen und Schnellzugriffen.

Letzte Aktualisierung: 2026-05-03 · Stand: Dashboard-Audit Mai 2026 · Release 2.9.500

**Admin-Route:** `/admin`

---

## Überblick

Das Dashboard ist der Standard-Einstieg nach dem Login und fasst Kerninformationen aus Inhalt, Benutzern, Technik und Verwaltung zusammen.

Die konkrete Ausprägung kann sich mit neuen Modulen und Widgets verändern; für die Navigation bleibt die Sidebar maßgeblich.

Im aktuellen Stand bildet das Dashboard vor allem den Überblick über:

- kompakte KPI-Karten für Kernzahlen und Sofortnavigation
- Statusblöcke für System, Sicherheit und Performance
- Aktivitätslisten/Audit-nahe Ereignisse
- Schnellzugriffe in häufig genutzte Admin-Bereiche
- priorisierte Hinweise für offene To-dos oder Sicherheitsauffälligkeiten

---

## Typische Inhalte

| Bereich | Inhalt |
|---|---|
| KPI-Karten | Benutzer, Seiten, Beiträge, Medien und optional Bestell-/Umsatzkennzahlen |
| Highlight-Karten | verdichtete Zusatzsignale wie neue Benutzer, Entwürfe, geplante Beiträge oder Upload-Volumen |
| Prioritäten | offene Bestellungen, fehlgeschlagene Logins oder HTTPS-Hinweise |
| Systemstatus | PHP-, CMS- und MySQL-Version sowie Laufzeit-/Upload-Kontext |
| Sicherheit & Performance | Security-Score, HTTPS, fehlgeschlagene Logins, RAM- und Performance-Score |
| Schnellzugriffe | Header-Aktionen für neue Inhalte, Medien und zentrale Admin-Bereiche |
| Aktivitätsbezug | letzte relevante Änderungen oder Aktionen aus dem Audit-/Aktivitätskontext |

---

## UI-Hinweise

- KPI-Karten sind bewusst kompakt und scan-orientiert aufgebaut, damit auf kleineren Screens mehr Kernsignale gleichzeitig sichtbar bleiben.
- Karten dienen primär als Einstieg in Detailseiten; längere Erklärtexte gehören in die Zielbereiche statt in die Startansicht.
- Warnungen auf der Startseite bleiben auf relevante `warning`-/`danger`-Fälle begrenzt, damit das Dashboard nicht zur Alert-Wand mutiert.
- Quicklinks und Filter-/Sortierlogik gehören außerhalb einzelner Kartenblöcke, damit die Kartensammlung visuell ruhig bleibt.

---

## Wichtige Referenzen im aktuellen Stand

- CMS-Versionen dürfen nicht mehr als historische 0.x-Stände dokumentiert werden
- für vertiefte Technikzustände sind heute spezialisierte Unterseiten zuständig, z. B. Diagnose oder Performance
- das Dashboard ist Startpunkt, aber nicht mehr die alleinige Systemübersicht
- KPI- und Highlight-Karten arbeiten aus derselben Stats-Basis wie Attention-Items, damit Kennzahlen konsistent bleiben
- Bestellbezogene Blöcke erscheinen nur, wenn die zugehörigen Subscription-/Orders-Module aktiv sind

---

## Verwandte Dokumente

- [DASHBOARD.md](DASHBOARD.md)
- [../README.md](../README.md)
- [../system-settings/README.md](../system-settings/README.md)
