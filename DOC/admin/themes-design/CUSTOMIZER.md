# 365CMS – Theme-Editor & Design-Anpassung

Kurzbeschreibung: Aktueller Einstieg für visuelle Theme-Anpassungen, Customizer-Werte und Export/Import von Design-Einstellungen.

Letzte Aktualisierung: 2026-03-28 · Version 2.8.0 RC

---

## Überblick

Der frühere separate „Theme-Customizer“ ist im aktuellen Stand funktional im Theme-Editor aufgegangen. Dort werden visuelle Einstellungen, Theme-spezifische Optionen und generierte Styles zusammengeführt.

Der Theme-Bereich umfasst derzeit vor allem:

- `/admin/themes`
- `/admin/theme-editor`
- `/admin/theme-explorer`
- `/admin/menu-editor`
- `/admin/landing-page`
- `/admin/font-manager`

---

## Typische Funktionen im Theme-Editor

- Farben und Design-Tokens anpassen
- Layout-Parameter pflegen
- Typografie konfigurieren
- Kategorien zurücksetzen
- Gesamte Einstellungen zurücksetzen
- Einstellungen exportieren oder importieren
- generiertes CSS ableiten

---

## Datenmodell

Theme-Anpassungen werden weiterhin über Theme- und Core-Pfade gespeichert; je nach Funktion spielen dabei `theme_customizations` sowie ergänzende Settings-/Exportpfade zusammen.

---

## Dokumentationshinweis

Verweise auf `admin/theme-customizer.php` sind veraltet. Für aktuelle Dokumentation und Supportfälle ist `/admin/theme-editor` die maßgebliche Referenz.
