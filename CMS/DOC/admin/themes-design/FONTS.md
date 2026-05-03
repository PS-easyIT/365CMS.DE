# 365CMS – Font Manager

Kurzbeschreibung: Verwaltung lokal gehosteter Schriftarten inklusive Theme-Scan, Google-Font-Spiegelung und CSS-Bereitstellung.

Letzte Aktualisierung: 2026-05-03 · Version 2.9.513

---

## Überblick

Der Font Manager bündelt drei Aufgaben:

- aktive Theme-Dateien auf externe oder bekannte Fonts scannen
- Google-Fonts kontrolliert lokal nach `/uploads/fonts` spiegeln
- Schriftfamilien für Überschriften und Body-Text im Frontend zuordnen

Damit lässt sich der Remote-Fallback im Theme zunächst beibehalten und später gezielt auf lokales Hosting umstellen.

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

Erkannte Fonts werden mit Quellen, Installationsstatus und Warnhinweisen in der UI gespiegelt.

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

Bleibt die Option deaktiviert, kann das Theme weiterhin bewusst externe Google-Fonts als Fallback verwenden.

---

## Asset-Status und Löschung

Die lokale Font-Liste zeigt nicht nur die Primärdatei, sondern auch:

- zugehörige CSS-Datei
- verknüpfte Font-Assets aus der CSS
- fehlende verknüpfte Dateien
- Asset-Status (`complete` / `warning`)

Beim Löschen versucht der Font Manager, sowohl CSS als auch verknüpfte lokale Font-Dateien kontrolliert zu entfernen.

---

## Dokumentationshinweis

Verweise auf `admin/fonts-local.php` sind veraltet. Für aktuelle Dokumentation und Supportfälle ausschließlich `/admin/font-manager` verwenden.
