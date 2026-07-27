# 365CMS – Theme-Customizer-Vertrag

Kurzbeschreibung: Einordnung des heutigen Theme-Customizers, seiner Ladeverträge im Theme-Editor und der Abgrenzung zu anderen Design-Oberflächen.

Letzte Aktualisierung: 2026-05-03 · Version 2.9.513

---

## Überblick

Der frühere separate „Theme-Customizer“ ist im aktuellen Core kein eigener Standard-Entry-Point mehr. Maßgebliche Referenz ist `/admin/theme-editor`.

Der Core-Customizer-Vertrag lautet heute:

- aktives Theme bestimmen
- `admin/customizer.php` des aktiven Themes suchen
- Datei sicher validieren
- Inline im Admin-Shell-Kontext rendern

Wenn diese Kette nicht erfüllt ist, erscheint der sichere Theme-Editor-Fallback statt einer halb kaputten UI.

---

## Produktiver Einstieg

- Route: `/admin/theme-editor`
- erwartete Theme-Datei: `admin/customizer.php`
- Fallback bei Fehlern oder fehlender Datei: `CMS/admin/views/themes/customizer-missing.php`

Der Legacy-Pfad `/admin/design-settings` zeigt in der aktuellen Runtime nicht auf einen separaten Customizer, sondern leitet auf `/admin/theme-editor` um.

---

## POST- und CSRF-Vertrag

Eingebettete Theme-Customizer werden nicht wie normale Module per Standard-POST-Handler verarbeitet. Stattdessen lässt die Section-Shell den POST inline an das Theme-Fragment weiterlaufen.

Aktive Schutzmerkmale des Theme-Editor-Shells:

- `csrf_action = admin_theme_editor`
- zusätzliche Allowlist für eingebettete Theme-Customizer-Aktionen
- persistente CSRF-Vorvalidierung, damit One-Time-Tokens des eingebetteten Customizers nicht vorzeitig verbraucht werden

Das ist relevant für Themes, die eigene Save-Aktionen direkt in `admin/customizer.php` behandeln.

---

## Typische Aufgaben im Theme-Customizer

Die konkrete Oberfläche bleibt Theme-Sache. Häufige Aufgaben sind:

- Farben und Design-Tokens pflegen
- Typografie und Abstände konfigurieren
- Header-/Footer-Optionen speichern
- Theme-spezifische CSS-Ausgabe generieren
- Exporte/Importe oder Reset-Aktionen des Themes anbieten

Die Umsetzung kann je Theme variieren; der Core garantiert nur den sicheren Einstiegspfad.

---

## Abgrenzung zu anderen Oberflächen

- `/admin/theme-editor` → theme-spezifischer Customizer des aktiven Themes
- `/admin/theme-explorer` → kontrollierte Dateibearbeitung im aktiven Theme
- `/admin/font-manager` → lokale Schriften, Theme-Scan und Font-Zuordnungen
- `/admin/cms-loginpage` → themeunabhängige Core-Auth-Seiten für Login/Registrierung/Reset

---

## Dokumentationshinweis

Verweise auf `admin/theme-customizer.php` oder auf einen eigenständigen globalen Customizer sind veraltet. Für Support- und Entwicklungsfälle ist `/admin/theme-editor` die führende Referenz.
