# 365CMS – Medienbibliothek

Kurzbeschreibung: Verwaltung hochgeladener Dateien und Ordner, Kategorien, Medieneinstellungen und kontrollierter Auslieferung über interne Services.

Letzte Aktualisierung: 2026-05-06 · Version 2.9.613

---

## Überblick

Die Medienbibliothek verwaltet Bilder, Dokumente und weitere Uploads zentral für Frontend, Admin und Member-Bereich. Der aktuelle Stand setzt auf native Listen-/Grid-Flows, modulvorbereitete View-Daten und serverseitig normalisierte Mutationspfade.

---

## Admin-Routen

Die Medienverwaltung bündelt Bibliothek, Beitrags-/Site-Medien, Kategorien und Einstellungen unter einer Route mit Query-Tabs:

| Route | View | Zweck |
|---|---|---|
| `/admin/media` | `views/media/library.php` | Dateibrowser, Upload, Suche, Bulk-Aktionen und Vorschau |
| `/admin/media?tab=featured` | `views/media/featured.php` | Beitrags- und Seitenbilder finden, filtern und global unter stabiler Referenz ersetzen |
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
| Verwaltete Bildverarbeitung | Maximalmaße, Thumbnail-Sätze und optionale WebP-Derivate |
| Beitrags-/Site-Medien | fokussierte Übersicht der in Beiträgen und Seiten hinterlegten Featured Images mit globalem Replace-in-place |

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

Zusätzliche derivative Dateien entstehen – abhängig von den Einstellungen – direkt neben dem Original mit bekannten Suffixen wie `-small`, `-medium`, `-large`, `-banner` sowie optional `.webp`.

---

## Technische Grundlage

Die Bibliothek verteilt Verantwortlichkeiten bewusst:

- `CMS/admin/media.php` normalisiert Actions, Payloads und Redirects
- `CMS/admin/modules/media/MediaModule.php` bereitet View-Modelle, Constraints, Optionen und Ergebnis-Alerts auf
- `CMS/core/Services/MediaService.php` bündelt Upload-, Move-, Rename-, Delete- und Settings-Logik
- `CMS/core/Services/MediaUsageService.php` ermittelt Dateiverwendungen und baut die Karte der als Beitrags-/Seitenbild referenzierten Medien
- `CMS/core/Services/Media/MediaRepository.php` liefert Items, Metadaten und Schutzlogik
- `CMS/core/Services/Media/UploadHandler.php` übernimmt Dateisystem-Mutationen inklusive Zielpfad-Organisation und Dateinamen-Regeln für verwaltete Uploads
- `CMS/core/Services/Media/ImageProcessor.php` erzeugt WebP-Derivate, skaliert Originalbilder auf Maximalmaße und erstellt Thumbnail-Varianten

---

## Laufzeitvertrag der Upload-Einstellungen

Die Medien-Einstellungen unter `/admin/media?tab=settings` wirken für Bibliotheks- und Member-Uploads jetzt direkt in der Runtime:

| Einstellung | Laufzeitwirkung |
|---|---|
| `max_upload_size` / `member_max_upload_size` | Byte-Limit für Admin- bzw. Member-Uploads |
| `allowed_types` / `member_allowed_types` | Extension-/Typgruppen-Allowlist |
| `organize_month_year` | hängt `YYYY/MM` an den Zielpfad von Bibliotheks-/Member-Uploads an |
| `sanitize_filenames` | bereinigt Dateinamen auf den serverseitig erlaubten Zeichensatz |
| zusätzliche Dateinamens-Härtung | begrenzt die Speicherlänge und entfernt mehrdeutige Zusatzpunkte im Basenamen |
| `lowercase_filenames` | speichert Dateinamen optional vollständig in Kleinschreibung |
| `unique_filenames` | hängt bei Bedarf fortlaufende Suffixe an oder blockiert Namenskollisionen fail-closed |
| `strip_exif` | schreibt klassische Browserbildformate neu, um EXIF-/Darstellungsprobleme zu entschärfen |
| `max_width` / `max_height` | skaliert Bilder nach dem Upload auf eine Obergrenze herunter |
| `generate_thumbnails` | erstellt `-small`, `-medium`, `-large`, `-banner`-Varianten |
| `auto_webp` | erzeugt zusätzlich ein WebP-Derivat für konvertierbare Originale |
| Upload-Authentifizierung | bleibt für den internen Upload-Endpunkt fail-closed auf angemeldete Admin-/Member-Kontexte beschränkt |

Wichtig: Dieser verwaltete Upload-Vertrag gilt bewusst für die Medienbibliothek und den Member-Bereich. Kontextgebundene Spezialpfade wie Theme-Logo- oder Editor.js-Uploads können weiterhin eigene Zielordnerverträge haben.

---

## Beitrags-/Site-Medien und globales Ersetzen

Der Spezialtab `/admin/media?tab=featured` ist für Bilder gedacht, die im Header, in Karten oder auf Übersichtsseiten als Beitrags- oder Seitenbild erscheinen. Er arbeitet deshalb nicht wie ein freier Dateibrowser, sondern nur auf tatsächlich gefundenen `featured_image`-Referenzen aus Beiträgen und Seiten.

Der Replace-Flow ersetzt die Datei am bestehenden verwalteten Medienpfad. Dadurch müssen Beiträge und Seiten nicht massenhaft umgeschrieben werden: Alle Inhalte, die denselben Pfad referenzieren, zeigen nach dem Austausch automatisch die neue Datei.

Absicherungen und UX-Details:

- `replace_item` akzeptiert serverseitig nur Pfade aus der aktuellen Featured-Image-Usage-Map.
- Uploadvalidierung bleibt im `MediaService`: erlaubte Extension, MIME-/Signaturprüfung, Bilddatenprüfung, Größenlimit und SVG-Block.
- Die Oberfläche nennt nur die serverseitig unterstützten Bildformate JPG/JPEG, PNG, GIF, WebP, BMP und ICO; `accept` ist dabei nur ein Browser-Hinweis und ersetzt keine Serverprüfung.
- Drag-&-Drop wird auf genau eine Datei begrenzt; ungültige Drops außerhalb der Zielzone werden abgefangen, damit der Browser keine Datei versehentlich öffnet.
- Vor dem Speichern wird lokal eine Mini-Vorschau über Objekt-URL bzw. FileReader gezeigt und beim Seitenwechsel wieder freigegeben.
- Nach erfolgreichem POST springt die Ansicht per Redirect zurück und markiert die ersetzte Bildzeile mit Erfolgshinweis.

---

## UI-Vertrag nach erfolgreichem Upload

Sowohl das Admin-Upload-Modal als auch der Member-Uploader aktualisieren nach erfolgreichen Uploads nun den tatsächlich verwendeten Zielordner. Dadurch bleiben automatisch angelegte Jahres-/Monats-Unterordner nicht mehr „unsichtbar“, sondern der View springt direkt dorthin zurück.

Zusätzlich benennt die Bulk-Schaltfläche in der Bibliothek die ausgewählte Aktion jetzt explizit und bleibt gesperrt, bis Auswahl **und** gültige Aktion zusammenpassen.

---

## Verwandte Dokumente

- [README.md](README.md)
- [../../core/SERVICES.md](../../core/SERVICES.md)
- [../../member/README.md](../../member/README.md)
- [../../workflow/MEDIA-UPLOAD-WORKFLOW.md](../../workflow/MEDIA-UPLOAD-WORKFLOW.md)

