# Design-Einstellungen

Kurzbeschreibung: Einordnung des früheren globalen Design-Pfads und seiner heutigen Rolle im produktiven Admin.

Letzte Aktualisierung: 2026-05-03 · Version 2.9.513

---

## Aktueller Status

`/admin/design-settings` ist im aktuellen Core **kein eigenständiger Settings-Screen mehr**, sondern ein Legacy-Alias auf `/admin/theme-editor`.

| Eigenschaft | Wert |
|---|---|
| Öffentliche Admin-Route | `/admin/design-settings` |
| Aktuelles Verhalten | Redirect auf `/admin/theme-editor` |
| Entry Point | `CMS/admin/design-settings.php` |
| Produktive Bearbeitungsoberfläche | `CMS/admin/theme-editor.php` |

---

## Was nicht mehr gilt

Ältere Dokumentationsstände beschrieben `/admin/design-settings` als vollständige Oberfläche mit eigener POST-Verarbeitung und eigenem CSRF-Kontext. Das trifft auf die aktuelle Runtime nicht mehr zu.

Auch `CMS/admin/modules/themes/DesignSettingsModule.php` und `CMS/admin/views/themes/settings.php` sind derzeit **keine Standard-Adminroute** im laufenden Navigationsfluss.

---

## Wo globale Design-Aspekte heute landen

Statt einer einzelnen globalen Design-Seite verteilt der aktuelle Core Design-nahe Pflege auf mehrere spezialisierte Werkzeuge:

- `/admin/theme-editor` für theme-spezifische Customizer-Oberflächen
- `/admin/theme-explorer` für kontrollierte Dateiansicht/-bearbeitung
- `/admin/font-manager` für Schriftzuordnung und lokales Font-Hosting
- `/admin/cms-loginpage` für Core-Auth-Branding, Farben und Texte
- `/admin/settings` für allgemeine Core-Einstellungen, auf die früher `theme-settings` teils verwies

---

## Abgrenzung zum Theme-Editor

| Aspekt | Legacy `design-settings` | Aktiver `theme-editor` |
|---|---|---|
| Status | Alias / Redirect | produktive Oberfläche |
| Scope | historisch global | aktives Theme / sicherer Customizer-Einstieg |
| Route | `/admin/design-settings` | `/admin/theme-editor` |
| Runtime | keine eigene Bearbeitungslogik | validiertes `admin/customizer.php` oder Fallback |

---

## Verwandte Seiten

- [Theme-Editor](EDITOR.md)
- [Customizer](CUSTOMIZER.md)
- [Themes & Design – Übersicht](README.md)
