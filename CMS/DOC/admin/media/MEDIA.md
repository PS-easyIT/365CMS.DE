# 365CMS – Medienbibliothek

Kurzbeschreibung: Verwaltung hochgeladener Dateien und Ordner, Kategorien, Medieneinstellungen und kontrollierter Auslieferung über interne Services.

Letzte Aktualisierung: 2026-05-15 · Version 3.0.3

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
| `/admin/media?tab=check` | `views/media/check.php` | fehlende oder defekte Featured-Image-Zuordnungen read-only prüfen |
| `/admin/media?tab=categories` | `views/media/categories.php` | Medien-Kategorien anlegen und pflegen |
| `/admin/media?tab=settings` | `views/media/settings.php` | Upload-Limits, erlaubte Typen und globale Medienoptionen |

---

## Aktuelle Kernfunktionen

| Funktion | Beschreibung |
|---|---|
| Listen- und Grid-Ansicht | kompakte Darstellung für Dateien und Ordner |
| Suchfeld | Filterung nach Dateien und Medienbegriffen |
| Kategorien-Filter | Eingrenzung nach Mediengruppen |
| Erweiterte Filter | Serverbasierte Eingrenzung nach Dateityp, Endung, Größenklasse und Änderungszeitraum |
| Filter-Presets & Permalink | persönliche Presets pro Admin und tokenfreier Query-String-Link für den aktuellen Bibliothekszustand inklusive Orphan-Altersfilter |
| Verwaiste Medien | globale read-only Kandidatenliste für nirgends verwendete ältere Medien mit Ordner-/Datei-Prüflinks |
| Direkte Verwendungsanzeige | sichtbare Beitrags-/Seitenreferenzen pro Medium mit Feldkontext und Bearbeitungslink |
| Duplikat-Erkennung | read-only Hinweise auf sichtbare Dateien mit identischem SHA-256-Inhalts-Hash |
| Native Uploads | Mehrfachauswahl über interne API-/Form-Flows |
| Rename-/Move-Modale | zentrale Dialoge statt breiter Inline-Formulare |
| Bulk-Löschen / Bulk-Verschieben | Mehrfachauswahl im Admin mit vorbereiteten Zielordnern |
| Bulk-Kategorisierung / Bulk-Tagging | Sammelpflege von Kategorien und Medien-Tags mit Audit-Zählwerten |
| Bulk-Alt-Texte | Sammelpflege von Alt-Texten für sichtbare, ausgewählte Dateien über dieselbe Toolbar |
| Vorschaulogik | robuste Dateivorschau und Proxy-/Preview-URLs |
| Verwaltete Bildverarbeitung | Maximalmaße, Thumbnail-Sätze und optionale WebP-Derivate |
| WebP-/Thumbnail-Jobs | fortsetzbare Bestandsverarbeitung mit Fortschritt und kleinen Server-Batches |
| Beitrags-/Site-Medien | fokussierte Übersicht der in Beiträgen und Seiten hinterlegten Featured Images mit globalem Replace-in-place |
| Medien Check | read-only Prüfliste für fehlende oder defekte Featured-Image-Zuordnungen mit Deep-Links in bestehende Arbeitswege |

Der gemeinsame Featured-Image-Picker für Seiten und Beiträge akzeptiert nur die Backend-Bildformate JPG, PNG, GIF, WebP, BMP und ICO. Bei neuen Inhalten werden Uploads temporär abgelegt und beim Speichern in den Slug-Ordner verschoben; Verschiebe- oder Metadatenfehler werden protokolliert, sollen aber keine leeren HTTP-500-Antworten nach erfolgreicher Bildübernahme mehr verursachen.

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

Der fortsetzbare Medienjob speichert seinen aktuellen Fortschritt in `CMS/config/media-processing-job.json`. Diese Datei enthält nur relative Medienpfade, Zähler, Status und letzte begrenzte Fehlerhinweise; sie ersetzt keinen dauerhaften Metadatenindex und kann durch einen neuen Job überschrieben werden. Beim Laden wird die Jobdatei größen- und schema-validiert; beschädigte, übergroße oder pfadseitig ungültige Zustände werden ignoriert, statt den Einstellungs-Tab zu brechen. Unerwartete Exceptions werden serverseitig protokolliert und im UI generisch angezeigt.

Die Duplikat-Erkennung arbeitet nicht als dauerhafter Metadatenindex, sondern wird in der aktuellen Bibliotheksansicht read-only berechnet: sichtbare Dateien werden zuerst nach Byte-Größe gruppiert; nur Gruppen mit mindestens zwei gleich großen Dateien werden anschließend per `hash_file('sha256', ...)` auf identische Inhalte geprüft. Fehlende, nicht lesbare, ungültige oder sehr große Pfade werden übersprungen, damit die Bibliothek fail-soft sichtbar und auch bei großen Upload-Beständen bedienbar bleibt.

---

## Technische Grundlage

Die Bibliothek verteilt Verantwortlichkeiten bewusst:

- `CMS/admin/media.php` normalisiert Actions, Payloads und Redirects
- `CMS/admin/modules/media/MediaModule.php` bereitet View-Modelle, Constraints, Optionen, serverseitige GET-Filter und Ergebnis-Alerts auf
- `CMS/core/Services/MediaService.php` bündelt Upload-, Move-, Rename-, Delete- und Settings-Logik
- `CMS/core/Services/MediaUsageService.php` ermittelt Dateiverwendungen und baut die Karte der als Beitrags-/Seitenbild referenzierten Medien
- `CMS/core/Services/Media/MediaRepository.php` liefert Items, Metadaten und Schutzlogik
- `CMS/core/Services/Media/UploadHandler.php` übernimmt Dateisystem-Mutationen inklusive Zielpfad-Organisation und Dateinamen-Regeln für verwaltete Uploads
- `CMS/core/Services/Media/ImageProcessor.php` erzeugt WebP-Derivate, skaliert Originalbilder auf Maximalmaße und erstellt Thumbnail-Varianten

Der WebP-/Thumbnail-Job in `/admin/media?tab=settings` nutzt denselben Service-Stack: `MediaModule` verwaltet Jobstatus und View-Daten, `MediaService` sammelt geeignete Quellbilder und verarbeitet einzelne Job-Items, und `ImageProcessor` führt die eigentliche Derivat-Erzeugung aus.

Die direkte Verwendungsanzeige in der Bibliothek verwendet ebenfalls den vorhandenen Service-Stack: `MediaUsageService` findet Referenzen in `featured_image`, `content` und `content_en` von Beiträgen und Seiten; `MediaModule` verdichtet diese Informationen zu Zählern und Feld-Badges; `views/media/library.php` rendert sie read-only mit escaped Titeln und fail-closed normalisierten internen Bearbeitungslinks.

Seit `2.9.742` nutzt dieselbe Bibliothek zusätzlich einen rekursiven, begrenzten Inventurpfad aus `MediaService`, um mögliche verwaiste Medien global zu prüfen. Der Pfad bleibt bewusst lesend: Er scannt Uploads nur bis zu einer festen Obergrenze, übernimmt vorhandene Metadaten wie `uploaded_at`, blendet geschützte System- und Member-Pfade aus und markiert Treffer ausschließlich als manuelle Prüfkandidaten.

Seit `2.9.743` nutzt die Bibliothek ergänzend die bestehende Tabelle `cms_media` als fail-soften Sidecar für Alt-Texte. `MediaModule` lädt Alt-Texte für sichtbare Dateien optional aus der Tabelle, aktualisiert sie über die vorhandene Bulk-POST-Strecke und ergänzt bei Bedarf minimale Datensätze, damit Performance-/SEO-Auswertungen denselben Alt-Text-Bestand sehen. Die Tabelle bleibt bewusst optional: Fehlt sie oder ist sie temporär nicht lesbar, blendet die Bibliothek die Alt-Text-Bulk-Aktion kontrolliert aus bzw. liefert eine generische Fehlermeldung statt eines HTTP-500.

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

Seit `3.0.3` bleibt dieser Tab bewusst auf die Übersicht der verwendeten Featured Images, Filterung nach Beiträgen/Seiten und den Replace-in-place-Flow fokussiert. Die Konsistenzprüfung wurde in den separaten Unterpunkt `/admin/media?tab=check` ausgelagert.

## Medien Check

Der Unterpunkt `/admin/media?tab=check` listet Beiträge und Seiten auf,

- die noch gar kein Featured Image hinterlegt haben,
- oder deren gespeicherte Referenz auf keine vorhandene lokale Mediendatei mehr zeigt.

Die Seite bleibt dabei bewusst ein reiner GET-/Lesepfad: Die Liste selbst schreibt nichts um, sondern verweist nur in bestehende, bereits abgesicherte Arbeitswege – entweder in den Editor mit dem vorhandenen Featured-Image-Picker aus der Medienbibliothek oder bei geteilten defekten Referenzen in den bestehenden Replace-in-place-Flow unter `/admin/media?tab=featured`.

Absicherungen und UX-Details:

- `replace_item` akzeptiert serverseitig nur Pfade aus der aktuellen Featured-Image-Usage-Map.
- Uploadvalidierung bleibt im `MediaService`: erlaubte Extension, MIME-/Signaturprüfung, Bilddatenprüfung, Größenlimit und SVG-Block.
- Seit `2.9.618` hängt dieser Spezialpfad dabei nicht mehr an den allgemeinen `allowed_types` der Bibliothek, sondern erzwingt serverseitig immer die feste Bild-Allowlist `jpg`, `jpeg`, `png`, `gif`, `webp`, `bmp`, `ico`.
- Die Oberfläche nennt nur die serverseitig unterstützten Bildformate JPG/JPEG, PNG, GIF, WebP, BMP und ICO; `accept` ist dabei nur ein Browser-Hinweis und ersetzt keine Serverprüfung.
- Drag-&-Drop wird auf genau eine Datei begrenzt; ungültige Drops außerhalb der Zielzone werden abgefangen, damit der Browser keine Datei versehentlich öffnet.
- Vor dem Speichern wird lokal eine Mini-Vorschau über Objekt-URL bzw. FileReader gezeigt und beim Seitenwechsel wieder freigegeben.
- Nach erfolgreichem POST springt die Ansicht per Redirect zurück und markiert die ersetzte Bildzeile mit Erfolgshinweis.
- Der Medien Check zeigt fehlende oder gebrochene Referenzen nur an; es gibt bewusst keinen automatischen GET-Fix, keine Token-URL und keinen verdeckten Massen-Update-Pfad.

---

## UI-Vertrag nach erfolgreichem Upload

Sowohl das Admin-Upload-Modal als auch der Member-Uploader aktualisieren nach erfolgreichen Uploads nun den tatsächlich verwendeten Zielordner. Dadurch bleiben automatisch angelegte Jahres-/Monats-Unterordner nicht mehr „unsichtbar“, sondern der View springt direkt dorthin zurück.

Zusätzlich benennt die Bulk-Schaltfläche in der Bibliothek die ausgewählte Aktion jetzt explizit und bleibt gesperrt, bis Auswahl **und** gültige Aktion zusammenpassen.

Seit `2.9.741` deckt die Bulk-Toolbar neben Löschen und Verschieben auch Metadatenpflege ab. `assign_category` setzt oder entfernt Kategorien für ausgewählte Dateien, während `tag_add`, `tag_replace`, `tag_remove` und `tag_clear` die neue begrenzte Tag-Liste im vorhandenen Medien-Meta-Store pflegen. Die Serverlogik normalisiert Tags erneut, überspringt Ordner bei Datei-Metadatenaktionen kontrolliert und protokolliert jede Sammelaktion mit Auswahl-, Erfolgs-, Skip- und Fehlerzahlen im Audit-Log. Dadurch bleibt der Komfortgewinn im bestehenden POST/CSRF/PRG-Vertrag, ohne neue Token-URLs oder unkontrollierte Metadaten-Payloads einzuführen.

Seit `2.9.743` gehört auch `alt_text_update` zu diesem Vertrag. Die Bibliothek zeigt pro sichtbarer Datei ein Alt-Text-Feld, speichert Änderungen aber weiterhin nur für markierte Dateien. Ordner werden dabei wie bei anderen Datei-Metadatenaktionen gezählt übersprungen. Zusätzlich halten Rename-, Move- und Delete-Aktionen vorhandene `cms_media.filepath`-Einträge fail-soft synchron, damit Alt-Text-Daten nach Aufräumarbeiten nicht verwaisen.

Seit `2.9.721` markiert die Bibliothek identische sichtbare Dateien zusätzlich mit einem Duplikat-Badge, Kurz-Hash und den ersten weiteren Pfaden derselben Hash-Gruppe. Diese Anzeige ersetzt keine redaktionelle Entscheidung: Admins löschen oder verschieben Duplikate weiterhin bewusst über die vorhandenen Einzel- oder Bulk-Aktionen.

Seit `2.9.728` bleibt diese Prüfung bewusst opportunistisch: sehr große Dateien werden im View-Pfad nicht mehr gehasht, weil die Medienliste kein lang laufender Integritätsjob ist. Falls ein Projekt forensische Duplikatprüfungen für große Archive oder Videos braucht, gehört das in einen separaten Batch-/Diagnosepfad.

Seit `2.9.725` ergänzt die Bibliothek die Suche um erweiterte Filter für Dateityp, Dateiendung, Größenklasse und Änderungszeitraum. Der Pfad bleibt bewusst nicht-destruktiv: Alle neuen Filter sind GET-Parameter, werden im Modul per Allowlist normalisiert, bleiben in Ordnernavigation sowie Listen-/Grid-Umschaltung erhalten und fallen bei ungültigen Werten auf `all` bzw. leere Endung zurück. Damit entstehen keine neuen CSRF-/POST-Pfade und keine unnötigen 500-Risiken durch manipulierte Filterwerte.

Seit `2.9.740` können Admins diesen normalisierten Bibliothekszustand zusätzlich als persönliches Filter-Preset speichern. Die Persistenz bleibt klein und fail-soft: Pro Admin werden maximal acht Presets im bestehenden `settings`-Store unter einer benutzerbezogenen Option mit `autoload = 0` gehalten, beschädigte oder unvollständige JSON-Payloads werden beim Laden ignoriert und unbekannte Filterwerte erneut über dieselben Allowlists normalisiert. Zusätzlich rendert die Bibliothek einen sichtbaren, bewusst tokenfreien Filter-Link für den aktuellen Query-String-Zustand. POST-/PRG-Flows behalten die erweiterten GET-Filter jetzt konsistent bei, sodass Kategoriezuweisung, Rename, Move oder Bulk-Aktionen nicht mehr ungewollt Dateityp-, Endungs-, Größen-, Datums- oder Orphan-Filter verlieren.

Seit `2.9.742` kommt als weiterer read-only Komfortpfad die Orphan-Prüfung hinzu: Admins können sich Dateien anzeigen lassen, die laut `MediaUsageService` nirgends verwendet werden und deren Upload- oder Änderungsdatum seit mindestens 30, 90, 180 oder 365 Tagen zurückliegt. Die Analyse bleibt ein reiner GET-Zustand ohne neue Schreibaktion; die Oberfläche empfiehlt nur die manuelle Prüfung im Ordner oder per Direktöffnung der Datei.

Seit `2.9.726` lassen sich vorhandene Bildbestände zusätzlich über einen fortsetzbaren WebP-/Thumbnail-Job nachverarbeiten. Start, nächster Batch und Abbruch sind normale Admin-POST-Aktionen mit bestehendem CSRF-/PRG-Vertrag; die Fortschrittsanzeige liest den gespeicherten Jobstatus. Einzelschäden wie nicht lesbare Dateien werden gezählt und geloggt, während der Job weiter fortgesetzt werden kann.

Seit `2.9.728` wird auch die gespeicherte Jobdatei selbst defensiver behandelt: zu große, leere, beschädigte oder schemafremde Inhalte werden beim Laden verworfen; Pfadlisten werden erneut normalisiert und auf die Job-Grenze begrenzt. Dadurch bleibt `/admin/media?tab=settings` auch nach manueller Dateibeschädigung oder unvollständigem Schreibvorgang erreichbar.

Seit `2.9.727` zeigt die Bibliothek pro Medium direkt an, ob es in Beiträgen oder Seiten verwendet wird. Die Anzeige bleibt auf die bereits berechneten sichtbaren Dateien begrenzt, nutzt gruppierte Badges für Inhaltstyp und Feldkontext und klappt zusätzliche Referenzen nur bei Bedarf auf. Damit bleibt die Medienliste auch bei mehrfach verwendeten Dateien übersichtlich und vermeidet neue Token-, POST- oder 500-riskante Verarbeitungspfade.

Seit `2.9.728` werden die zugehörigen Bearbeitungslinks zusätzlich in der View auf interne Beitrags-/Seiten-Edit-Routen begrenzt. Falls ein unerwarteter Zielwert in den Usage-Payload gelangt, bleibt die Referenz sichtbar, aber ohne klickbaren Link.

---

## Verwandte Dokumente

- [README.md](README.md)
- [../../core/SERVICES.md](../../core/SERVICES.md)
- [../../member/README.md](../../member/README.md)
- [../../workflow/MEDIA-UPLOAD-WORKFLOW.md](../../workflow/MEDIA-UPLOAD-WORKFLOW.md)

