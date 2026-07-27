# 365CMS – Menü-Editor

Kurzbeschreibung: Verwaltung von Navigationsmenüs, Zuordnungen und Menüeinträgen im aktuellen Theme-/Design-Bereich.

Letzte Aktualisierung: 2026-04-07 · Version 2.9.0

---

## Überblick

Der Menü-Editor wird über `CMS/admin/menu-editor.php` bereitgestellt und nutzt `MenuEditorModule` für Laden, Speichern und Löschen von Menüs sowie Menüeinträgen.

---

## Serverseitige Aktionen

Die Seite verwendet das CSRF-Token `admin_menu_editor`.

| Aktion | Bedeutung |
|---|---|
| `save_menu` | Menü anlegen oder Stammdaten speichern |
| `delete_menu` | komplettes Menü löschen |
| `save_items` | Menüeinträge und Reihenfolge speichern |

Nach erfolgreicher Verarbeitung wird per Redirect zurück auf `/admin/menu-editor` navigiert.

---

## Typische Arbeitsabläufe

### Menü anlegen

1. Menüname vergeben
2. Menü speichern
3. Menü im Editor auswählen

### Menüeinträge verwalten

Je nach Modul- und Theme-Stand können Einträge aus Seiten, Beiträgen oder als freie Links angelegt werden. Die konkrete Oberfläche rendert die View `views/menus/editor.php`.

Aktueller URL-Vertrag im Editor:

- interne Ziele dürfen als Slug oder Pfad eingegeben werden, z. B. `kontakt`, `unternehmen/team` oder `/kontakt`
- Startseiten-Ziele werden zusätzlich robuster auf `/` normalisiert, auch wenn im Editor Aliasformen wie `index.php`, `home` oder `startseite` auftauchen oder ein Home-Eintrag ohne explizite URL gespeichert wird
- absolute Links bleiben auf `http`/`https` begrenzt
- neue Container-/Elternpunkte dürfen im Editor zunächst ohne URL angelegt werden; erst beim finalen Speichern müssen echte Ziel-Items eine valide URL haben
- Elternpunkte ohne echte Ziel-URL werden beim Speichern fail-safe auf `#` normalisiert, statt den kompletten Save-Vorgang mit einem generischen URL-Fehler zu blockieren
- echte Fehler nennen im aktuellen Stand zusätzlich Titel und Zeile des betroffenen Menü-Items

### Menü löschen

Ein Menü kann über die Löschaktion vollständig entfernt werden.

---

## Datenmodell

Für die Menüverwaltung sind im aktuellen Stand insbesondere relevant:

- `menus`
- `menu_items`

Historische Verweise auf nur `cms_menu_items` ohne separate Menü-Tabelle sind für den aktuellen Stand unvollständig.

---

## Theme-Bezug

Die eigentlichen Menüpositionen stammen vom aktiven Theme. Der Menü-Editor verwaltet daher Inhalte und Zuordnungen, während das Theme festlegt, welche Positionen wie ausgegeben werden.

Typische Positionen sind zum Beispiel:

- Hauptnavigation
- Footer-Navigation
- Legal-/Footer-Links
- mobile Navigation

---

## Wichtige Umstellung

Verweise auf `/admin/menus.php` sind veraltet. Die maßgebliche aktuelle Route lautet `/admin/menu-editor`.

---

## Verwandte Dokumente

- [README.md](README.md)
- [CUSTOMIZER.md](CUSTOMIZER.md)
- [../../theme/README.md](../../theme/README.md)
