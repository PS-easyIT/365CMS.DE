# 365CMS – Medienverwaltung

Kurzbeschreibung: Überblick über Medienbibliothek, Upload-Workflows, Schutzbereiche, Admin-Tabs und verknüpfte Member-/Asset-Dokumentation.

Letzte Aktualisierung: 2026-05-10 · Version 2.9.728

Die Medienverwaltung ist unter `/admin/media` erreichbar und bündelt Bibliothek, Beitrags-/Site-Medien, Kategorien und Einstellungen über Query-Tabs statt über getrennte Legacy-Routen.

---

## Admin-Routen

| Route | Zweck |
|---|---|
| `/admin/media` | Bibliothek mit Listen-/Grid-Ansicht, Upload, Suche, erweiterten Filtern, Rename/Move/Delete |
| `/admin/media?tab=featured` | fokussierte Übersicht aller als Beitrags- oder Seitenbild verwendeten Medien mit Ersetzen-Workflow |
| `/admin/media?tab=categories` | Medien-Kategorien anlegen und pflegen |
| `/admin/media?tab=settings` | Upload-Limits, erlaubte Typen und globale Medienoptionen |

---

## Kernfunktionen in 2.9.614

- Listen- **und** Grid-Ansicht
- Such-, Kategorien-, Verwendungs- und erweiterte Dateifilter
- native Upload-Modalstrecke statt aktiver FilePond-Runtime
- kompakte Dropdown-Aktionen für Dateien und Ordner
- zentrale Rename-/Move-Modale mit vorbereiteten Zielordnern
- Admin-Bulk-Aktionen für Löschen und Verschieben
- aktionsspezifische Bulk-Schaltfläche statt generischem „Auf Auswahl anwenden“
- serverseitig normalisierte Pfade, CSRF-geschützte POST-Flows und PRG-Redirects
- geschützte Systempfade mit zusätzlicher Member-Bestätigung am `member`-Bereich
- verwaltete Upload-Pipeline für Bibliothek und Member-Bereich mit Maximalmaßen, optionalen Thumbnails und Auto-WebP
- gemeinsamer Featured-Image-Picker für Beiträge und Seiten mit fail-soft Verschiebung temporärer Uploads beim Speichern
- Rücksprung in den **tatsächlich verwendeten** Zielordner, wenn Uploads automatisch in Jahres-/Monats-Unterordner einsortiert werden
- strengere Dateinamens-Härtung mit Längenlimit und ohne irreführende Mehrfach-Punkte im Basenamen
- read-only Duplikat-Erkennung in der Bibliothek: sichtbare gleich große Dateien werden per SHA-256-Inhalts-Hash verglichen und als identische Gruppen markiert
- erweiterte Bibliotheksfilter nach Dateityp, Endung, Größenklasse und Änderungszeitraum; ungültige GET-Werte werden allowlist-basiert auf sichere Defaults normalisiert
- direkte Verwendungsanzeige pro sichtbarem Medium mit Beitrags-/Seiten- und Feld-Badges, Bearbeitungslinks und aufklappbaren weiteren Referenzen
- Nachhärtung der direkten Verwendungsanzeige: Bearbeitungslinks werden in der View fail-closed auf interne Beitrags-/Seiten-Edit-Routen begrenzt
- chunkbasierte WebP-/Thumbnail-Nachverarbeitung für bestehende Bilder unter `/admin/media?tab=settings`, inklusive Fortschritt, Fehlerzählung und Abbruchmöglichkeit ohne lange Einzelrequests
- Medienjob-Statusdateien werden beim Laden größen- und schema-validiert, damit beschädigte Jobdaten den Settings-Tab nicht destabilisieren
- Unterpunkt **Beitrags & Site Medien** für Featured Images aus Beiträgen und Seiten inklusive Suche, Filter nach Beiträgen/Seiten, Drag-&-Drop-Ersetzen, lokaler Mini-Vorschau und Erfolgshinweis pro Bild
- der Featured-Replace-Flow erzwingt seinen Bildvertrag seit `2.9.618` serverseitig unabhängig von den allgemeinen Bibliotheks-Typ-Häkchen, damit Beitrags-/Seitenbilder immer nur als JPG/JPEG, PNG, GIF, WebP, BMP oder ICO ersetzt werden

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

Für die Medienbibliothek und den Member-Bereich gilt jetzt ein gemeinsamer verwalteter Upload-Vertrag:

- Uploads über `/admin/media` und `/api/upload` respektieren die Einstellungen aus `config/media-settings.json` direkt in der Laufzeit
- `organize_month_year` hängt bei Bibliotheks-/Member-Uploads automatisch `YYYY/MM` an den gewählten Zielpfad an
- `sanitize_filenames`, `lowercase_filenames` und `unique_filenames` steuern den gespeicherten Dateinamen und das Verhalten bei Kollisionen
- gespeicherte Dateinamen werden zusätzlich auf eine sichere Maximal­länge begrenzt; mehrdeutige Basenamen wie `bild.php.jpg` werden serverseitig auf einen eindeutigen, ungefährlichen Speichername reduziert
- `max_width` und `max_height` skalieren zu große Bilder nach dem Speichern auf die konfigurierte Obergrenze herunter
- `generate_thumbnails` erzeugt zusätzliche Varianten mit den Suffixen `-small`, `-medium`, `-large` und `-banner`
- `auto_webp` erzeugt für konvertierbare Bildformate weiterhin ein zusätzliches WebP-Derivat, ohne das Original zu ersetzen
- der interne Upload-Endpunkt bleibt bewusst authentifiziert; ein anonymer Public-Upload-Modus gehört nicht zum aktuellen Medien-Vertrag

---

## WebP-/Thumbnail-Jobs für Bestandsbilder

Seit `2.9.726` kann `/admin/media?tab=settings` bestehende Bilder nachträglich in eine kleine Derivat-Warteschlange legen. Der Ablauf ist bewusst nicht als langer Komplettrequest umgesetzt:

- Start, Verarbeitung und Abbruch laufen über die bestehende Admin-Route mit Capability-Prüfung, CSRF-Token und PRG-Redirect.
- Der Jobstatus liegt atomar in `CMS/config/media-processing-job.json` und enthält Fortschritt, Zähler und die letzte Fehlerliste.
- Pro Verarbeitungsschritt werden nur wenige Quellbilder verarbeitet; dadurch bleiben Requests kurz und defekte Bilder reißen nicht die komplette Seite als HTTP-500 ab.
- Unerwartete Verarbeitungsfehler werden im UI generisch gemeldet und serverseitig protokolliert, statt rohe Exception-Texte oder interne Details in die Job-Fehlerliste zu übernehmen.
- Bereits erzeugte Thumbnail-Varianten mit `-small`, `-medium`, `-large` oder `-banner` werden nicht erneut als Quelle in die Queue gelegt.
- Die eigentliche Bildarbeit nutzt weiterhin `ImageProcessor`, damit WebP- und Thumbnail-Erzeugung denselben Runtime-Vertrag wie Uploads verwenden.

---

## Beitrags & Site Medien

Der Unterpunkt `/admin/media?tab=featured` zeigt ausschließlich Medienpfade, die aktuell als `featured_image` in Beiträgen oder Seiten referenziert sind. Die Ansicht ist bewusst enger als die allgemeine Bibliothek:

- Suchfeld für Dateiname, Pfad, Inhaltstitel und Inhaltstyp
- Filter auf **alle**, **nur Beiträge** oder **nur Seiten**
- Verwendungsnachweis mit Direktlinks in die jeweilige Bearbeitung
- globales Ersetzen unter derselben Medienreferenz, sodass alle verknüpften Beiträge und Seiten automatisch die neue Datei anzeigen
- Drag-&-Drop oder klassische Dateiauswahl mit lokaler Mini-Vorschau vor dem Speichern
- PRG-Redirect mit Erfolgshinweis an der ersetzten Bildzeile

Sicherheitsvertrag:

- Replace-POSTs werden serverseitig nur für Pfade akzeptiert, die in der aktuellen Beitrags-/Seitenbild-Karte vorkommen.
- Die Client-Auswahl erlaubt nur die vom Backend unterstützten Bildendungen JPG/JPEG, PNG, GIF, WebP, BMP und ICO.
- Der Browser-MIME-Typ ist nur ein UX-Signal; die verbindliche Prüfung erfolgt weiterhin serverseitig über Extension-Allowlist, MIME-/Signaturprüfung, Größenlimit, Bildvalidierung sowie den blockierten SVG-Typ.
- Ersatzdateien überschreiben die bestehende verwaltete Datei mit Backup-/Restore-Pfad, damit die Referenz in Beiträgen und Seiten stabil bleibt.

Typische Stellschrauben:

- maximale Upload-Größe
- erlaubte Typgruppen für Admin und Member
- Dateinamen-Sanitisierung / Eindeutigkeit
- Auto-WebP / EXIF-Strip
- Thumbnail-Generierung
- automatische Ordnerorganisation nach Jahr/Monat

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

## Bibliotheksansicht

Die Bibliotheksansicht zeigt Ordner und Dateien in Listen- oder Grid-Darstellung, ergänzt um:

- direkte Verwendungsanzeige bzw. Referenzen aus Seiten/Beiträgen mit kompakten Badges und Bearbeitungslinks
- Duplikat-Hinweise für sichtbare Dateien mit identischem SHA-256-Inhalts-Hash, Kurz-Hash und weiteren Pfaden
- Kategorien-, Such-, Verwendungs-, Typ-, Endungs-, Größen- und Änderungszeitraumfilter
- Bulk-Aktionen für Löschen und Verschieben
- direkte Dateiaktionen für Umbenennen, Verschieben, Löschen und Kategorie-Zuordnung

Die Duplikat-Erkennung ist bewusst nur ein Hinweis- und Prüfpfad: Sie löscht keine Dateien automatisch und schreibt keine bestehenden Medienreferenzen um. Dadurch können Admins zuerst prüfen, ob identische Dateien tatsächlich redundant sind oder bewusst mehrfach in unterschiedlichen Ordnerkontexten liegen.

Seit `2.9.728` überspringt diese View-Berechnung sehr große Dateien beim SHA-256-Hashing opportunistisch. Dadurch bleibt die Bibliothek auch bei großen Video-, Archiv- oder Backup-Dateien bedienbar; die Anzeige ist weiterhin ein read-only Komfortsignal und kein vollständiger forensischer Duplikatindex.

Seit `2.9.725` bleibt die erweiterte Suche bewusst ein reiner GET-/View-Pfad: Dateityp, Endung, Größenklasse und Änderungszeitraum werden serverseitig normalisiert, in Ordner-, Breadcrumb- und Listen-/Grid-Links mitgeführt und können über einen Reset-Link entfernt werden. Dadurch entstehen keine zusätzlichen CSRF-Token oder Schreibaktionen; fehlerhafte Filterwerte führen zu neutralen Defaults statt zu einem Serverfehler.

Seit `2.9.727` ist die Verwendungsanzeige nicht mehr nur ein Filterkriterium: Jede sichtbare Datei erhält in der Listenansicht direkte Referenzzeilen mit Inhaltstyp, Titel, Feldkontext und Link zur Bearbeitung; weitere Treffer bleiben per `<details>` aufklappbar. Die Grid-Ansicht zeigt denselben Zustand kompakt über Zähler- und Feld-Badges. Der Pfad bleibt read-only und nutzt die bestehende `MediaUsageService`-Map, statt neue Schreibaktionen oder lange Zusatzläufe einzuführen.

Seit `2.9.728` werden diese Bearbeitungslinks zusätzlich direkt im Renderpfad auf interne `/admin/posts?action=edit&id=...`- und `/admin/pages?action=edit&id=...`-Ziele normalisiert. Unerwartete oder beschädigte Zielwerte werden als reine Textreferenz angezeigt.

Bei erfolgreich hochgeladenen Dateien bleibt die Oberfläche nicht mehr am alten Pfad hängen, sondern aktualisiert auf den effektiven Zielordner. Das ist besonders relevant, wenn `organize_month_year` aktiv ist.

---

## Verwandte Dokumente

- [MEDIA.md](MEDIA.md)
- [../performance/PERFORMANCE.md](../performance/PERFORMANCE.md)
- [../../member/README.md](../../member/README.md)
- [../../workflow/MEDIA-UPLOAD-WORKFLOW.md](../../workflow/MEDIA-UPLOAD-WORKFLOW.md)
- [../../assets/README.md](../../assets/README.md)
