# 365CMS – Medienbibliothek

Kurzbeschreibung: Verwaltung hochgeladener Dateien und Ordner, Kategorien, Medieneinstellungen und kontrollierter Auslieferung über interne Services.

Letzte Aktualisierung: 2026-04-07 · Version 2.9.0

---

## Überblick

Die Medienbibliothek verwaltet Bilder, Dokumente und weitere Uploads zentral für Frontend, Admin und Member-Bereich. Der aktuelle Stand setzt auf native Listen-/Grid-Flows, modulvorbereitete View-Daten und serverseitig normalisierte Mutationspfade.

---

## Admin-Routen

Die Medienverwaltung bündelt Bibliothek, Kategorien und Einstellungen unter einer Route mit Query-Tabs:

| Route | View | Zweck |
|---|---|---|
| `/admin/media` | `views/media/library.php` | Dateibrowser, Upload, Suche, Bulk-Aktionen und Vorschau |
| `/admin/media?tab=categories` | `views/media/categories.php` | Medien-Kategorien anlegen und pflegen |
| `/admin/media?tab=settings` | `views/media/settings.php` | Upload-Limits, erlaubte Typen und globale Medienoptionen |

---

## Aktuelle Kernfunktionen

| Funktion | Beschreibung |
|---|---|
| Listen- und Grid-Ansicht | kompakte Darstellung für Dateien und Ordner |
| Suchfeld | Filterung nach Dateien und Medienbegriffen |
| Kategorien-Filter | Eingrenzung nach Mediengruppen |
| Native Uploads | Mehrfachauswahl über interne API-/Form-Flows |
| Rename-/Move-Modale | zentrale Dialoge statt breiter Inline-Formulare |
| Bulk-Löschen / Bulk-Verschieben | Mehrfachauswahl im Admin mit vorbereiteten Zielordnern |
| Vorschaulogik | robuste Dateivorschau und Proxy-/Preview-URLs |

---

## Schutzbereiche

Geschützte Systempfade werden über Repository-Logik und Modul-/Controller-Verträge abgesichert.

Im aktuellen Stand bedeutet das:

- Top-Level-Systemordner wie `themes`, `plugins`, `assets`, `fonts`, `dl-manager`, `form-uploads` und `member` sind geschützt.
- Unter `member/` bleibt die direkte User-Root (z. B. `member/user-7`) ebenfalls geschützt.
- Member-Unterordner darunter (`member/user-7/fotos`, `member/user-7/rechnungen`) sind reguläre Inhalte und werden nicht mehr als Systemordner klassifiziert.
- Der Einstieg in den Member-Bereich im Admin verlangt weiterhin eine zusätzliche Bestätigung.

---

## Datenmodell

Die aktive Medienverwaltung arbeitet primär dateisystem- und metadatenbasiert.

Wichtige Quellen:

- Dateisystem unter `CMS/uploads/`
- Metadaten unter `CMS/config/media-meta.json`
- Einstellungen unter `CMS/config/media-settings.json`

Typische Metadaten umfassen:

- Dateiname und relativer Pfad
- Kategorie
- Größe / Änderungszeitpunkt
- Uploader / Uploader-ID
- abgeleitete Preview-/Public-URLs
- Systempfad-Klassifikation

---

## Technische Grundlage

Die Bibliothek verteilt Verantwortlichkeiten bewusst:

- `CMS/admin/media.php` normalisiert Actions, Payloads und Redirects
- `CMS/admin/modules/media/MediaModule.php` bereitet View-Modelle, Constraints, Optionen und Ergebnis-Alerts auf
- `CMS/core/Services/MediaService.php` bündelt Upload-, Move-, Rename-, Delete- und Settings-Logik
- `CMS/core/Services/Media/MediaRepository.php` liefert Items, Metadaten und Schutzlogik
- `CMS/core/Services/Media/UploadHandler.php` übernimmt Dateisystem-Mutationen

---

## Verwandte Dokumente

- [README.md](README.md)
- [../../core/SERVICES.md](../../core/SERVICES.md)
- [../../member/README.md](../../member/README.md)
- [../../workflow/MEDIA-UPLOAD-WORKFLOW.md](../../workflow/MEDIA-UPLOAD-WORKFLOW.md)

