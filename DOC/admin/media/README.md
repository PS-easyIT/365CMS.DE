# 365CMS – Medienverwaltung

Kurzbeschreibung: Überblick über Medienbibliothek, Upload-Workflows, Schutzbereiche, Admin-Tabs und verknüpfte Member-/Asset-Dokumentation.

Letzte Aktualisierung: 2026-03-28 · Version 2.8.0 RC

Die Medienverwaltung ist unter `/admin/media` erreichbar und bündelt Bibliothek, Kategorien und Einstellungen über Query-Tabs statt über getrennte Legacy-Routen.

---

## Admin-Routen

| Route | Zweck |
|---|---|
| `/admin/media` | Bibliothek mit Listen-/Grid-Ansicht, Upload, Suche, Rename/Move/Delete |
| `/admin/media?tab=categories` | Medien-Kategorien anlegen und pflegen |
| `/admin/media?tab=settings` | Upload-Limits, erlaubte Typen und globale Medienoptionen |

---

## Kernfunktionen in 2.8.0 RC

- Listen- **und** Grid-Ansicht
- Such- und Kategorien-Filter
- native Upload-Modalstrecke statt aktiver FilePond-Runtime
- kompakte Dropdown-Aktionen für Dateien und Ordner
- zentrale Rename-/Move-Modale mit vorbereiteten Zielordnern
- Admin-Bulk-Aktionen für Löschen und Verschieben
- serverseitig normalisierte Pfade, CSRF-geschützte POST-Flows und PRG-Redirects
- geschützte Systempfade mit zusätzlicher Member-Bestätigung am `member`-Bereich

---

## Schutzbereiche und Member-Kontext

Der Bereich `member` bleibt im Admin ein geschützter Sonderpfad.

Wichtig im aktuellen Stand:

- der Root `member` und direkte User-Roots wie `member/user-42` gelten als geschützte Systempfade
- Member-Unterordner darunter (z. B. `member/user-42/rechnungen`) sind reguläre Inhalte und werden nicht mehr fälschlich als Systemordner behandelt
- das Öffnen des Member-Bereichs erfordert im Admin weiterhin eine zusätzliche Bestätigung

---

## Upload und Grenzwerte

Uploads laufen über native Formulare und interne APIs. Die konkrete Laufzeit-Validierung orientiert sich an `config/media-settings.json` und den vom Modul vorbereiteten Constraints.

Typische Stellschrauben:

- maximale Upload-Größe
- erlaubte Typgruppen für Admin und Member
- Dateinamen-Sanitisierung / Eindeutigkeit
- Auto-WebP / EXIF-Strip
- Thumbnail-Generierung

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
| Performance | Bildgrößen, WebP und Medienoptimierung |
| Member-Bereich | persönliche Upload-Wurzelpfade, Breadcrumbs, Rename/Move/Delete |
| Assets | Altbestände von FilePond/elFinder und native Upload-/Picker-Ablösung |

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

Upload-Grenzen sind über den Settings-Tab unter `/admin/media?tab=settings` konfigurierbar:

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
- [../performance/PERFORMANCE.md](../performance/PERFORMANCE.md)
- [../../member/README.md](../../member/README.md)
- [../../workflow/MEDIA-UPLOAD-WORKFLOW.md](../../workflow/MEDIA-UPLOAD-WORKFLOW.md)
- [../../assets/README.md](../../assets/README.md)
