# 365CMS – Theme-Editor & Design-Anpassung

Kurzbeschreibung: Aktueller Einstieg für visuelle Theme-Anpassungen, Customizer-Werte und Export/Import von Design-Einstellungen – ergänzt um die Abgrenzung zur neuen CMS Loginpage.

Letzte Aktualisierung: 2026-04-07 · Version 2.9.0

---

## Überblick

Der frühere separate „Theme-Customizer“ ist im aktuellen Stand funktional im Theme-Editor aufgegangen. Dort werden visuelle Einstellungen, Theme-spezifische Optionen und generierte Styles zusammengeführt.

Der Theme-Bereich umfasst derzeit vor allem:

- `/admin/themes`
- `/admin/theme-editor`
- `/admin/theme-explorer`
- `/admin/cms-loginpage`
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

## Abgrenzung zur CMS Loginpage

Seit `2.9.0` existiert mit [`/admin/cms-loginpage`](CMS-LOGINPAGE.md) zusätzlich eine **Core-eigene** Auth-Oberfläche.

Wichtig ist die Trennung:

- `/admin/theme-editor` steuert **Theme-spezifische** Darstellung über den Customizer des aktiven Themes.
- `/admin/cms-loginpage` steuert **themeunabhängige Auth-Seiten** des Core für `/cms-login`, `/cms-register` und `/cms-password-forgot`.

Die CMS Loginpage ist also kein weiterer Theme-Customizer, sondern bewusst eine stabile Core-Strecke für Login, Registrierung und Reset – auch dann, wenn ein Theme eigene Login-Templates hat oder eben nicht hat. Kleiner, aber wichtiger Unterschied mit großem Effekt auf Support-Tickets.
