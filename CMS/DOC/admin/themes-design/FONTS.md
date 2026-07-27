# 365CMS – Font Manager

Kurzbeschreibung: Verwaltung lokal gehosteter Schriftarten inklusive Theme-Scan, Google-Font-Spiegelung, Asset-Prüfung und read-only Font-Nutzungsanalyse.

Letzte Aktualisierung: 2026-05-10 · Version 2.9.746

---

## Überblick

Der Font Manager bündelt vier Aufgaben:

- aktive Theme-Dateien auf externe oder bekannte Fonts scannen
- Google-Fonts kontrolliert lokal nach `/uploads/fonts` spiegeln
- Schriftfamilien für Überschriften und Body-Text im Frontend zuordnen
- die aktuelle Font-Nutzung aus Theme-Scan, lokaler Bibliothek und Runtime-Konfiguration read-only auswerten

Damit lässt sich der Remote-Fallback im Theme zunächst beibehalten, später gezielt auf lokales Hosting umstellen und transparent prüfen, welche Fonts tatsächlich aktiv oder noch offen sind.

---

## Datenmodell und Speicherung

Die aktuelle Runtime kombiniert:

- Tabelle `custom_fonts` für lokal verwaltete Fonts
- Settings wie `font_heading`, `font_body`, `font_size_base`, `font_line_height` und `privacy_use_local_fonts`
- Theme-bezogene Scan-Caches im Settings-Speicher
- lokale Font-Dateien und CSS unter `/uploads/fonts`

Ältere Dokumentationsstände, die nur von einer einzelnen JSON-Datei oder nur von einer einzelnen Tabelle ausgehen, sind zu kurz gegriffen.

---

## Theme-Scan

Der Font Manager scannt das aktive Theme kontrolliert mit festen Schutzgrenzen:

- nur definierte Text-Endungen
- Größenlimit pro Datei
- Gesamtlimit für gescannten Textinhalt
- übersprungene Segmente wie `vendor`, `node_modules`, `cache`, `.git`
- Cache-TTL von 900 Sekunden pro aktivem Theme

Erkannte Fonts werden mit Quellen, Installationsstatus und Warnhinweisen in der UI gespiegelt. Die Scan-Daten werden zusätzlich für die Nutzungsanalyse wiederverwendet; fehlende oder veraltete Scan-Ergebnisse führen nur zu reduzierter Aussagekraft, nicht zu einem Fehlerpfad.

## Font-Nutzungsanalyse

Die read-only Analyse unter `/admin/font-manager` kombiniert vier Datenquellen:

- Theme-Scan-Ergebnisse des aktiven Themes
- konfigurierte `font_heading`- und `font_body`-Zuordnungen
- lokale Fonts aus `custom_fonts`
- Asset-Status aus gespeicherter CSS-Datei und referenzierten Font-Dateien

Die Oberfläche unterscheidet insbesondere:

- **lokal aktiv**: konfigurierte lokale Fonts, die bei aktivem `privacy_use_local_fonts = 1` tatsächlich im Frontend bevorzugt eingebunden werden können
- **konfiguriert, aber nicht aktiv**: lokale Fonts, die als Heading/Body gewählt sind, aber noch nicht über den lokalen Font-Modus ausgeliefert werden
- **extern erkannt**: im Theme gescannte Fonts, die lokal noch nicht im Font Manager vorhanden sind
- **lokal vorhanden**: lokal gespeicherte Fonts, die im Theme zwar erkannt werden, aber nicht als Heading/Body konfiguriert sind
- **lokal gespeichert, aktuell ungenutzt**: Fonts ohne aktive Zuweisung und ohne aktuellen Theme-Scan-Treffer

Zusätzlich werden Asset-Warnungen angezeigt, wenn die CSS-Datei oder referenzierte Font-Dateien unvollständig sind. Die Analyse bleibt rein lesend: keine neue POST-Aktion, kein zusätzlicher Token-Pfad, keine Ausgabe roher Dateiinhalte.

---

## Remote-Download und lokales Hosting

Google-Fonts werden nur von freigegebenen Hosts geladen:

- `fonts.googleapis.com`
- `fonts.gstatic.com`

Zusätzliche Härtungen:

- CSS-Endpunkt-Fallback bei blockierten `css2`-Antworten
- Limit für Anzahl und Größe heruntergeladener Font-Dateien
- atomisches Schreiben lokaler Dateien
- Binär-/Header-Prüfung für `woff`, `woff2`, `ttf`, `otf`
- Audit-Logging für Scan, Download und Löschung

---

## Lokale Font-Aktivierung

Sobald `privacy_use_local_fonts = 1` gesetzt ist, priorisiert das Frontend lokale Fonts und unterdrückt den vorgesehenen Google-Fonts-Fallback, sofern passende lokale Dateien vorhanden sind.

Bleibt die Option deaktiviert, kann das Theme weiterhin bewusst externe Google-Fonts als Fallback verwenden. Die Nutzungsanalyse markiert solche Konfigurationen explizit als „konfiguriert, aber nicht aktiv“, damit der Unterschied zwischen gespeicherter Wahl und echter Runtime sichtbar bleibt.

---

## Asset-Status und Löschung

Die lokale Font-Liste zeigt nicht nur die Primärdatei, sondern auch:

- zugehörige CSS-Datei
- verknüpfte Font-Assets aus der CSS
- fehlende verknüpfte Dateien
- Asset-Status (`complete` / `warning`)

Beim Löschen versucht der Font Manager, sowohl CSS als auch verknüpfte lokale Font-Dateien kontrolliert zu entfernen.

War die gelöschte Schrift noch als `font_heading` oder `font_body` aktiv hinterlegt, setzt der Löschpfad diese Runtime-Zuordnung jetzt sofort fail-soft auf `system-ui` zurück und entfernt den zugehörigen `font_stack_<slug>`-Eintrag. Damit bleiben Frontend und Admin nicht länger an verwaisten Font-Slugs hängen, bis irgendwann zufällig erneut gespeichert wird.

Die Nutzungsanalyse greift denselben Asset-Status wieder auf, um Fonts mit beschädigten oder unvollständigen Verknüpfungen nicht stillschweigend als „gesund“ darzustellen.

---

## Sicherheits- und Betriebsvertrag

- Remote-Font-Downloads bleiben HTTPS- und Host-allowlisted.
- Die Analyse bleibt rein read-only und nutzt nur bestehende Datenquellen.
- Fehlende Dateien, reduzierte Scan-Daten oder veraltete Caches führen zu neutralen Hinweisen statt zu einem HTTP-500.
- Alle Dateinamen, Fontnamen und Hinweise werden escaped gerendert.

## Dokumentationshinweis

Verweise auf `admin/fonts-local.php` sind veraltet. Für aktuelle Dokumentation und Supportfälle ausschließlich `/admin/font-manager` verwenden.
