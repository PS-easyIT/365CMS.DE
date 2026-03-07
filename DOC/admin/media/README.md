# 365CMS – Medienverwaltung

Kurzbeschreibung: Überblick über Medienbibliothek, Upload-Workflows, Schutzbereiche und zugehörige Dokumente.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

Die Medienverwaltung ist unter `/admin/media` erreichbar und steuert Upload, Suche, Filter, Dateioperationen und Vorschaulogik für den globalen Medienbestand.

Wesentliche Merkmale im aktuellen Stand:

- Standardmäßig **Listenansicht**
- Such- und Kategorien-Filter
- Datei- und Ordnerlöschung
- robusterer Redirect nach Aktionen
- URL-sichere Vorschaulinks auch bei Leerzeichen und Umlauten
- geschützter `member`-Ordner mit zusätzlicher Bestätigung

---

## Admin-Routen

| Route | Zweck |
|---|---|
| `/admin/media` | Dateibrowser, Upload, Suche und Vorschau |
| `/admin/media-categories` | Medien-Kategorien anlegen und pflegen |
| `/admin/media-settings` | Upload-Limits, erlaubte Typen und globale Medienoptionen |

---

## Dokumente in diesem Bereich

| Dokument | Schwerpunkt |
|---|---|
| [MEDIA.md](MEDIA.md) | Funktionen, Datenmodell und Sicherheitsaspekte |

---

## Verknüpfte Bereiche

| Bereich | Bezug |
|---|---|
| Seiten & Beiträge | Featured Images und Einbettungen |
| Media-Proxy | kontrollierte Auslieferung |
| Performance | Bildgrößen, WebP und Medienoptimierung |
| Member-Bereich | geschützter Medienordner |

---

## Listenspalten

Die Bibliotheksansicht zeigt pro Eintrag:

| Spalte | Beschreibung |
|---|---|
| Thumbnail | Kleines Vorschaubild |
| Dateiname | Original-Dateiname mit Link zur Bearbeitungsseite |
| Typ | MIME-Type |
| Größe | Dateigröße in KB/MB |
| Abmessungen | Breite × Höhe (nur für Bilder) |
| Hochgeladen | Datum und Uhrzeit |
| Benutzer | Wer hat die Datei hochgeladen |
| Aktionen | Bearbeiten, Löschen, URL kopieren |

---

## Upload und Grenzwerte

Dateien können per Drag & Drop oder über den klassischen Datei-Dialog hochgeladen werden. Mehrfachauswahl ist möglich.

Upload-Grenzen sind über `/admin/media-settings` konfigurierbar:

- maximale Dateigröße (Standard: 10 MB)
- maximale Bildbreite mit Auto-Resize
- erlaubte MIME-Typen (Whitelist)

**WebP-Konvertierung** ist optional über `/admin/performance-media` aktivierbar. Dabei wird automatisch eine WebP-Kopie erstellt; das Original bleibt erhalten.

**Automatische Thumbnails** werden in drei Größen generiert: `thumbnail` (150×150), `medium` (300×300), `large` (1024×1024).

---

## Metadaten

Pro Datei pflegbar: Titel, Alt-Text, Beschreibung und Bildunterschrift. Alt-Text ist für SEO und Barrierefreiheit (WCAG 2.1) besonders relevant.

---

## Verwandte Dokumente

- [MEDIA.md](MEDIA.md)
- [../pages-posts/README.md](../pages-posts/README.md)
- [../performance/PERFORMANCE.md](../performance/PERFORMANCE.md)
- [../../workflow/MEDIA-UPLOAD-WORKFLOW.md](../../workflow/MEDIA-UPLOAD-WORKFLOW.md)
