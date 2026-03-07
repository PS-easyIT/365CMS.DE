# 365CMS – Medienbibliothek

Kurzbeschreibung: Verwaltung hochgeladener Dateien, Ordner, Metadaten und sicherer Auslieferung über den Media-Proxy.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

---

## Überblick

Die Medienbibliothek verwaltet Bilder, Dokumente und weitere Uploads zentral für Frontend, Admin und Member-Bereich.

---

## Admin-Routen

Die Medienverwaltung umfasst seit 2.3.x drei Unterseiten:

| Route | View | Zweck |
|---|---|---|
| `/admin/media` | `views/media/library.php` | Dateibrowser, Upload, Suche und Vorschau |
| `/admin/media-categories` | `views/media/categories.php` | Medien-Kategorien anlegen und pflegen |
| `/admin/media-settings` | `views/media/settings.php` | Upload-Limits, erlaubte Typen und globale Medienoptionen |

---

## Aktuelle Kernfunktionen

| Funktion | Beschreibung |
|---|---|
| Listenansicht | Standardansicht für Medien und Ordner |
| Suchfeld | Filterung nach Dateien und Medienbegriffen |
| Kategorien-Filter | Eingrenzung nach Mediengruppen |
| Upload | neue Dateien einspielen |
| Datei-/Ordnerlöschung | Verwaltungsoperationen direkt im Admin |
| Vorschaulogik | robuste Dateivorschau auch bei problematischen Dateinamen |

---

## Schutzbereiche

Der Ordner `member` wird im aktuellen Stand als geschützter Systembereich behandelt.

Das bedeutet insbesondere:

- zusätzlicher Bestätigungsdialog beim Öffnen
- restriktivere Behandlung im Admin
- Member-Bilder werden in bestimmten Selektoren ausgeblendet

---

## Media-Proxy

Die kontrollierte Auslieferung erfolgt über `media-proxy.php`.

Ziele:

- sichere Auslieferung
- zentralisierte Zugriffskontrolle
- konsistente URL-Verarbeitung

---

## Datenmodell

Zentrale Tabelle für den Medienbestand ist `media`.

Typische Felder umfassen:

- Dateiname
- Pfad
- MIME-Typ
- Größe
- Alt-Text
- Beschreibung
- Uploader
- Erstellungszeitpunkt

---

## Verwandte Dokumente

- [../pages-posts/README.md](../pages-posts/README.md)
- [../performance/PERFORMANCE.md](../performance/PERFORMANCE.md)
- [../../workflow/MEDIA-UPLOAD-WORKFLOW.md](../../workflow/MEDIA-UPLOAD-WORKFLOW.md)

