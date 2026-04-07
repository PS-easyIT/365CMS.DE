# Themes & Design

Kurzbeschreibung: Übersicht über die aktuellen Design-, Theme- und Frontend-Werkzeuge im Admin-Bereich – inklusive der neuen CMS-eigenen Loginpage, die trotz eigenem Core-Rendering organisatorisch unter „Themes & Design“ geführt wird.

Letzte Aktualisierung: 2026-04-07 · Version 2.9.0

---

## Überblick

Der Bereich **Themes & Design** bündelt die Werkzeuge zur Verwaltung des aktiven Themes, zur Bearbeitung theme-spezifischer Einstellungen sowie zu Navigation, Landingpages und Schriften.

Die Standardnavigation in `CMS/admin/partials/sidebar.php` führt aktuell auf folgende Routen:

- `/admin/themes`
- `/admin/theme-editor`
- `/admin/theme-explorer`
- `/admin/cms-loginpage`
- `/admin/menu-editor`
- `/admin/landing-page`
- `/admin/font-manager`

Ein separater Theme-Marketplace (`/admin/theme-marketplace`) existiert ebenfalls, wird aber derzeit nicht als Standardpunkt in der Sidebar geführt.

---

## Kernbereiche

### Theme-Verwaltung

- Route: `/admin/themes`
- Zweck: Installierte Themes anzeigen, aktives Theme erkennen, Theme-Wechsel steuern.

### Theme-Editor

- Route: `/admin/theme-editor`
- Zweck: Lädt den Customizer des aktiven Themes über `admin/customizer.php`, falls vorhanden.
- Fallback: Zeigt eine Info-Seite, wenn das aktive Theme keinen eigenen Customizer bereitstellt.

### Theme-Explorer

- Route: `/admin/theme-explorer`
- Zweck: Dateien, Struktur und Assets eines Themes kontrolliert durchsuchen.

### CMS Loginpage

- Route: `/admin/cms-loginpage`
- Zweck: Die CMS-eigene Auth-Strecke für Login, Registrierung und Passwort-Reset design- und textseitig konfigurieren.
- Besonderheit: Die Seite rendert **Core-Auth-Ansichten**, nicht Theme-Templates. Sie sitzt hier im Navigationsbereich, weil Branding, Farben und UX-Texte dort fachlich am besten aufgehoben sind.

### Menü-Editor

- Route: `/admin/menu-editor`
- Zweck: Navigationsstrukturen, Positionen und Einträge der Theme-Menüs pflegen.

### Landing Page

- Route: `/admin/landing-page`
- Zweck: Landingpage-nahe Inhalte und Präsentationsblöcke verwalten, sofern das aktive Setup diese Oberfläche nutzt.

### Font Manager

- Route: `/admin/font-manager`
- Zweck: lokale Schriften, Zuordnungen und Font-Assets pflegen.

---

## Theme-Editor versus Datei-Editor

Im aktuellen 365CMS ist der „Theme Editor“ in erster Linie ein **Theme-Customizer-Einstiegspunkt**.

Er arbeitet wie folgt:

1. Ermittelt das aktive Theme über `CMS\ThemeManager`
2. Prüft, ob `admin/customizer.php` im Theme existiert
3. Lädt diese Datei direkt als Theme-spezifische Admin-Oberfläche
4. Zeigt andernfalls einen Fallback mit Verweisen zur Theme-Verwaltung und zum Theme-Explorer

Das ist bewusst etwas anderes als ein generischer Browser-Codeeditor mit freier Dateibearbeitung.

---

## Zusätzliche Module außerhalb der Sidebar

Neben den Standardpunkten existieren weitere themennahe Routen, darunter:

- `/admin/theme-marketplace`
- Legacy-Weiterleitungen wie `/admin/design-settings` → `/admin/theme-editor`

Solche Seiten sind funktional vorhanden, aber nicht zwingend Teil des Standard-Navigationsflusses in `2.8.0 RC`.

---

## Dokumentationsstruktur

- [Theme Editor](EDITOR.md)
- [Design-Einstellungen](DESIGN-SETTINGS.md)
- [Theme & Plugin Marketplace](MARKETPLACE.md)
- [Menü-Editor](MENUS.md)
- [Member-Dashboard-Widgets](DASHBOARD-WIDGETS.md)
- [Theme Customizer](CUSTOMIZER.md)
- [CMS Loginpage](CMS-LOGINPAGE.md)
- [Font Manager](FONTS.md)

---

## Verwandte Seiten

- [Theme-Entwicklung](../../theme/DEVELOPMENT.md)
- [Admin-Guide](../GUIDE.md)
- [System & Einstellungen](../system-settings/README.md)
