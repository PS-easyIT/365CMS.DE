# Theme Editor

Kurzbeschreibung: Dokumentiert den aktuellen Theme-Editor als sicheren Einstieg in den theme-spezifischen Customizer des aktiven Themes.

Letzte Aktualisierung: 2026-05-03 · Version 2.9.513

---

## Route und Aufgabe

- Route: `/admin/theme-editor`
- Entry Point: `CMS/admin/theme-editor.php`

Der Theme-Editor ist **kein** freier Code-Editor. Er versucht, die Datei `admin/customizer.php` des aktiven Themes kontrolliert einzubinden. Wenn das nicht sicher möglich ist, rendert er einen erklärenden Fallback.

---

## Aktuelles Laufzeitverhalten

Beim Aufruf werden folgende Schritte ausgeführt:

1. Admin-Berechtigung prüfen
2. aktives Theme über `CMS\ThemeManager::instance()` bestimmen
3. Theme-Pfad sicher auflösen
4. `admin/customizer.php` suchen
5. Datei vor dem Einbinden validieren
6. entweder Customizer inline laden oder `views/themes/customizer-missing.php` rendern

---

## Validierungen vor dem Einbinden

Die aktuelle Runtime bindet `admin/customizer.php` nur ein, wenn alle Schutzprüfungen bestehen:

- Datei liegt innerhalb des aktiven Theme-Verzeichnisses
- Datei ist lesbar
- Datei überschreitet nicht das Inline-Limit von 256 KB
- Datei enthält keine Binärdaten / NUL-Bytes
- PHP-Syntax ist gültig
- bestimmte riskante Funktionsaufrufe kommen nicht vor

Aktuell blockierte Funktionsnamen:

- `eval`
- `exec`
- `system`
- `shell_exec`
- `passthru`
- `proc_open`
- `popen`
- `base64_decode`

---

## Fallback-Verhalten

Wenn der Customizer nicht geladen werden kann, zeigt die Fallback-Ansicht strukturiert an:

- aktives Theme
- Reason-Code und Erklärung
- erwarteten Pfad `admin/customizer.php`
- Verweise auf `/admin/themes` und `/admin/theme-explorer`

Das verhindert irreführende weiße Seiten und macht sichtbar, ob ein Theme schlicht keinen Customizer mitbringt oder ob eine Sicherheitsprüfung fehlgeschlagen ist.

---

## Bedeutung für Theme-Entwickler

Wenn ein Theme eine eigene Admin-Konfiguration bereitstellen soll, ist `admin/customizer.php` der zentrale Einstiegspunkt.

Die Datei bleibt theme-spezifisch. Der Core stellt den sicheren Ladepfad, das Shell-Layout und den Fallback bereit, erzwingt aber keine universelle UI.

---

## Sicherheit und Grenzen

- Zugriff nur für Admins mit `manage_settings`
- keine freie Bearbeitung beliebiger Dateien über diese Route
- POST-Requests werden absichtlich inline an den eingebetteten Customizer durchgereicht
- die Section-Shell nutzt persistent validierte CSRF-Allowlists für eingebettete Theme-Customizer

Für tatsächliche Dateibearbeitung ist stattdessen `/admin/theme-explorer` vorgesehen.

---

## Verwandte Seiten

- [Themes & Design – Überblick](README.md)
- [Theme-Entwicklung](../../theme/DEVELOPMENT.md)
- [Theme Customizer](CUSTOMIZER.md)
