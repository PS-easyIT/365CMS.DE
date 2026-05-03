# Themes & Design

Kurzbeschreibung: Übersicht über die aktuell produktiven Theme-, Design- und Frontend-Werkzeuge im Admin-Bereich – inklusive der Core-eigenen CMS Loginpage.

Letzte Aktualisierung: 2026-05-03 · Version 2.9.513

---

## Überblick

Der Bereich **Themes & Design** bündelt die Core-Oberflächen für Theme-Verwaltung, Theme-Customizer-Einstieg, kontrollierte Dateiinspektion, Schriftverwaltung und die themeunabhängige Auth-UI.

Die Standardnavigation in `CMS/admin/partials/sidebar.php` führt aktuell auf folgende produktive Routen:

- `/admin/themes`
- `/admin/theme-editor`
- `/admin/theme-explorer`
- `/admin/cms-loginpage`
- `/admin/menu-editor`
- `/admin/landing-page`
- `/admin/font-manager`

Ergänzend existieren weitere themennahe Admin-Pfade:

- `/admin/theme-marketplace`
- `/admin/design-settings` → Legacy-Alias auf `/admin/theme-editor`
- `/admin/theme-settings` → Legacy-Alias auf `/admin/settings`

---

## Kernbereiche

### Theme-Verwaltung

- Route: `/admin/themes`
- Zweck: installierte Themes anzeigen, aktivieren und – sofern nicht aktiv – löschen.
- Laufzeitvertrag: Theme-Wechsel und Löschungen laufen über `CMS\ThemeManager` inklusive Health-Check, Locking und Audit-Logging.

### Theme-Editor

- Route: `/admin/theme-editor`
- Zweck: sicherer Einstieg in `admin/customizer.php` des aktiven Themes.
- Kein Roh-Dateieditor: Die Route lädt nur einen validierten Theme-Customizer oder einen strukturierten Fallback.

### Theme-Explorer

- Route: `/admin/theme-explorer`
- Zweck: Dateien des aktiven Themes kontrolliert durchsuchen und ausgewählte Textdateien im Browser bearbeiten.
- Laufzeitvertrag: Pfad-Whitelist, Dateigrößenlimits, erlaubte Endungen, PHP-Syntaxprüfung und atomisches Schreiben mit Integritätscheck.

### CMS Loginpage

- Route: `/admin/cms-loginpage`
- Zweck: Branding, Farben, Texte und rechtliche Verknüpfungen für `/cms-login`, `/cms-register` und `/cms-password-forgot` steuern.
- Besonderheit: rendert Core-Auth-Ansichten statt Theme-Templates und bleibt damit unabhängig vom aktiven Frontend-Theme stabil.

### Font Manager

- Route: `/admin/font-manager`
- Zweck: Theme-Fonts scannen, Google-Fonts lokal spiegeln, Font-Zuordnungen speichern und lokales Frontend-Hosting aktivieren.
- Laufzeitvertrag: Remote-Downloads nur von freigegebenen Hosts, lokale Ablage in `/uploads/fonts`, Scan-/Download-Limits und Audit-Logging.

### Menü-Editor

- Route: `/admin/menu-editor`
- Zweck: Menüs, Positionen und Navigationsstrukturen pflegen.

### Landing Page

- Route: `/admin/landing-page`
- Zweck: landingpage-nahe Präsentationsblöcke verwalten, sofern das Setup diese Oberfläche nutzt.

---

## Abgrenzung der Werkzeuge

| Route | Rolle |
|---|---|
| `/admin/theme-editor` | lädt den sicheren Theme-Customizer-Einstieg |
| `/admin/theme-explorer` | kontrollierte Theme-Dateiansicht und Browser-Save-Pfad |
| `/admin/themes` | operative Theme-Verwaltung |
| `/admin/theme-marketplace` | katalogbasierte Theme-Entdeckung und Installation |
| `/admin/cms-loginpage` | themeunabhängige Core-Auth-Oberfläche |
| `/admin/font-manager` | lokale Font-Verwaltung und Theme-Font-Scan |

Wichtig: Der Theme-Editor ist **kein** generischer Code-Editor. Für Dateibearbeitung ist der Theme-Explorer zuständig.

---

## Sicherheits- und Betriebsbild

- Theme-Marketplace arbeitet mit HTTPS-Quellen, Host-Allowlist, ZIP-only, SHA-256-Prüfung, Paketgrößenlimits und Install-Locks.
- Theme-Explorer begrenzt Pfade, Dateitypen, Baumtiefe und Browser-Dateigrößen und speichert nur atomisch mit Hash-Prüfung.
- Theme-Editor validiert `admin/customizer.php` vor dem Einbinden auf Pfad, Größe, Syntax, Binärinhalte und unsichere Funktionsaufrufe.
- Font Manager scannt und löscht Font-Assets kontrolliert und priorisiert lokale Fonts optional vor externen Fallbacks.
- CMS Loginpage speichert ihre Werte serverseitig validiert und schützt Reset- und Login-Flows mit Core-CSRF sowie Rate-Limits auf Passwort-Reset-Aktionen.

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
