# Design-Einstellungen

Kurzbeschreibung: Globale Design-Tokens (Farben, Layout, Header, Footer) unabhängig vom aktiven Theme.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

---

## Route und Technik

| Eigenschaft | Wert |
|---|---|
| Route | `/admin/design-settings` |
| Modul | `CMS/admin/modules/themes/DesignSettingsModule.php` |
| CSRF-Kontext | `admin_design_settings` |

---

## Funktionsumfang

`getData()` liefert die aktuellen globalen Design-Einstellungen. `saveSettings(array $post)` speichert Änderungen.

### Typische Einstellungsbereiche

- **Farben**: Primär-, Sekundär-, Akzentfarben, Hintergrund
- **Layout**: Container-Breite, Abstände, Spaltenraster
- **Header**: Header-Typ, Sticky-Verhalten, Logo-Position
- **Footer**: Footer-Layout, Spaltenanzahl, Copyright
- **Performance**: Lazy Loading, Asset-Optimierung

Die Design-Einstellungen wirken systemweit und können vom Theme-Customizer theme-spezifisch überschrieben werden.

---

## Abgrenzung zum Theme-Editor

| Aspekt | Design-Einstellungen | Theme-Editor |
|---|---|---|
| Scope | Global / systemweit | Theme-spezifisch |
| Route | `/admin/design-settings` | `/admin/theme-editor` |
| Modul | `DesignSettingsModule` | Theme-eigene `customizer.php` |

---

## Verwandte Seiten

- [Theme-Editor](EDITOR.md)
- [Customizer](CUSTOMIZER.md)
- [Themes & Design – Übersicht](README.md)
