# Theme Editor

Kurzbeschreibung: Dokumentiert den aktuellen Theme-Editor als Einstieg in den theme-spezifischen Customizer des aktiven Themes.

Letzte Aktualisierung: 2026-04-07 · Version 2.9.0

---

## Route und Aufgabe

- Route: `/admin/theme-editor`
- Entry Point: `CMS/admin/theme-editor.php`

Der Theme-Editor ist kein generischer Roh-Dateieditor, sondern lädt bevorzugt die vom aktiven Theme bereitgestellte Datei:

- `admin/customizer.php`

Existiert diese Datei nicht oder schlägt die Prüfung fehl, wird stattdessen eine strukturierte Fallback-Seite mit Hinweisen angezeigt.

---

## Aktuelles Laufzeitverhalten

Beim Aufruf werden folgende Schritte ausgeführt:

1. Admin-Berechtigung prüfen
2. Aktives Theme über `CMS\ThemeManager::instance()` bestimmen
3. `admin/customizer.php` des aktiven Themes suchen und Theme-Pfade prüfen
4. Falls vorhanden: über den Theme-Editor-Flow laden
5. Falls nicht vorhanden oder ungültig: Fallback-Ansicht mit Links zu
	- `/admin/themes`
	- `/admin/theme-explorer`

---

## Bedeutung für Theme-Entwickler

Wenn ein Theme eigene Einstellungsoberflächen im Admin bereitstellen soll, ist `admin/customizer.php` der zentrale Einstiegspunkt.

Empfohlene Aufgaben dieser Datei:

- Theme-Farben verwalten
- Typografie konfigurieren
- Header-/Footer-Optionen pflegen
- Layout-Defaults ändern
- CSS-Ausgabe aus Theme-Settings erzeugen

Die genaue Umsetzung ist theme-spezifisch. Der Core erzwingt hier keine universelle UI, sondern nur den Einstiegspfad.

---

## Fallback-Verhalten

Wenn kein Customizer vorhanden ist, zeigt die Seite explizit an:

- welches Theme aktiv ist
- dass keine `admin/customizer.php` gefunden wurde
- welche Alternativen verfügbar sind

Das schützt vor irreführenden Fehlermeldungen und macht sichtbar, dass der fehlende Editor kein Systemfehler ist, sondern eine Eigenschaft des Themes.

---

## Sicherheit und Grenzen

- Zugriff nur für Admins
- Keine freie Bearbeitung beliebiger Core-Dateien über diese Route
- Kein automatischer Syntax-Check für Theme-PHP außerhalb des geladenen Theme-Customizers

Wenn ein Theme eigene Formulare einbindet, muss es selbst auf folgende Punkte achten:

- CSRF-Schutz
- Ausgabe-Escaping
- Sanitizing von Theme-Optionen
- konsistente Speicherung in `settings` oder theme-spezifischen Datenstrukturen

---

## Verwandte Seiten

- [Themes & Design – Überblick](README.md)
- [Theme-Entwicklung](../../theme/DEVELOPMENT.md)
- [Theme Customizer](CUSTOMIZER.md)
