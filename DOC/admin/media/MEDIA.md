# 365CMS – Medienbibliothek

Kurzbeschreibung: Verwaltung hochgeladener Dateien, Ordner, Metadaten und sicherer Auslieferung über den Media-Proxy.

Letzte Aktualisierung: 2026-03-07

**Admin-Route:** `/admin/media`

---

## Überblick

Die Medienbibliothek verwaltet Bilder, Dokumente und weitere Uploads zentral für Frontend, Admin und Member-Bereich.

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
- [../system-settings/PERFORMANCE.md](../system-settings/PERFORMANCE.md)
- [../../workflow/MEDIA-UPLOAD-WORKFLOW.md](../../workflow/MEDIA-UPLOAD-WORKFLOW.md)

