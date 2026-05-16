# 365CMS – Medienverwaltung

Kurzbeschreibung: Überblick über Medienbibliothek, Upload-Workflows, Schutzbereiche, Admin-Tabs und verknüpfte Member-/Asset-Dokumentation.

Letzte Aktualisierung: 2026-05-16 · Version 3.0.6

Die Medienverwaltung ist unter `/admin/media` erreichbar und bündelt Bibliothek, Beitrags-/Site-Medien, Medien-Check, Kategorien und Einstellungen über Query-Tabs statt über getrennte Legacy-Routen.

---

## Admin-Routen

| Route | Zweck |
|---|---|
| `/admin/media` | Bibliothek mit Listen-/Grid-Ansicht, Upload, Suche, erweiterten Filtern, Rename/Move/Delete |
| `/admin/media?tab=featured` | fokussierte Übersicht aller als Beitrags- oder Seitenbild verwendeten Medien mit Ersetzen-Workflow |
| `/admin/media?tab=check` | read-only Medien-Check für fehlende oder defekte Featured-Image-Zuordnungen |
| `/admin/media?tab=categories` | Medien-Kategorien anlegen und pflegen |
| `/admin/media?tab=settings` | Upload-Limits, erlaubte Typen und globale Medienoptionen |

---

## Kernfunktionen im aktuellen Stand

- Listen- **und** Grid-Ansicht
- Such-, Kategorien-, Verwendungs- und erweiterte Dateifilter
- native Upload-Modalstrecke statt aktiver FilePond-Runtime
- kompakte Dropdown-Aktionen für Dateien und Ordner
- zentrale Rename-/Move-Modale mit vorbereiteten Zielordnern
- Admin-Bulk-Aktionen für Löschen, Verschieben, Kategorisieren, Tagging und Alt-Texte
- aktionsspezifische Bulk-Schaltfläche statt generischem „Auf Auswahl anwenden“
- serverseitig normalisierte Pfade, CSRF-geschützte POST-Flows und PRG-Redirects
- geschützte Systempfade mit zusätzlicher Member-Bestätigung am `member`-Bereich
- verwaltete Upload-Pipeline für Bibliothek und Member-Bereich mit Maximalmaßen, optionalen Thumbnails und Auto-WebP
- gemeinsamer Featured-Image-Picker für Beiträge und Seiten mit fail-soft Verschiebung temporärer Uploads beim Speichern
- hashbasierte Wiederverwendung identischer permanenter Beitrags-/Seitenbilder im Featured-Image-Upload, damit gleiche Titelbilder nicht mehrfach physisch abgelegt werden
- Rücksprung in den **tatsächlich verwendeten** Zielordner, wenn Uploads automatisch in Jahres-/Monats-Unterordner einsortiert werden
- strengere Dateinamens-Härtung mit Längenlimit und ohne irreführende Mehrfach-Punkte im Basenamen
- read-only Duplikat-Erkennung in der Bibliothek: sichtbare gleich große Dateien werden per SHA-256-Inhalts-Hash verglichen und als identische Gruppen markiert
- erweiterte Bibliotheksfilter nach Dateityp, Endung, Größenklasse und Änderungszeitraum; ungültige GET-Werte werden allowlist-basiert auf sichere Defaults normalisiert
- speicherbare Filter-Presets pro Admin-Benutzer für die Bibliotheksansicht, inklusive sicherem Persistenzpfad in `settings` ohne Autoload-Ballast und vollständigem Filterzustand bis zur Verwaist-Altersgrenze
- sichtbarer Filter-Link für den aktuellen Bibliothekszustand; der Link bleibt bewusst tokenfrei und basiert ausschließlich auf Query-Parametern
- globale read-only Orphan-Prüfung für Dateien, die laut `MediaUsageService` nirgends verwendet werden und älter als ein definierter Zeitraum sind
- Bulk-Kategorisierung und Bulk-Tagging über die bestehende Toolbar; Tag-Metadaten werden begrenzt im bestehenden Medien-Meta-Store gehalten
- Bulk-Alt-Text-Editor über dieselbe Toolbar; Alt-Texte werden fail-soft aus `cms_media` geladen und nur für ausgewählte sichtbare Dateien gespeichert
- direkte Verwendungsanzeige pro sichtbarem Medium mit Beitrags-/Seiten- und Feld-Badges, Bearbeitungslinks und aufklappbaren weiteren Referenzen
- Nachhärtung der direkten Verwendungsanzeige: Bearbeitungslinks werden in der View fail-closed auf interne Beitrags-/Seiten-Edit-Routen begrenzt
- chunkbasierte WebP-/Thumbnail-Nachverarbeitung für bestehende Bilder unter `/admin/media?tab=settings`, inklusive Fortschritt, Fehlerzählung und Abbruchmöglichkeit ohne lange Einzelrequests
- Medienjob-Statusdateien werden beim Laden größen- und schema-validiert, damit beschädigte Jobdaten den Settings-Tab nicht destabilisieren
- Unterpunkt **Beitrags & Site Medien** für Featured Images aus Beiträgen und Seiten inklusive Suche, Filter nach Beiträgen/Seiten, Drag-&-Drop-Ersetzen, lokaler Mini-Vorschau, Mehrfach-Ersetzung vorbereiteter Zeilen und Erfolgshinweis pro Bild
- eigener Unterpunkt **Medien Check** für die read-only Konsistenzliste von Beiträgen und Seiten ohne Bild oder mit defekter Featured-Image-Referenz inklusive Deep-Link in den bestehenden Editor-Pfad mit Medienbibliothek
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
- Mehrfach-Ersetzung: Wenn in mehreren Zeilen neue Dateien ausgewählt wurden, verarbeitet ein Klick auf „Bild ersetzen“ alle vorbereiteten Zielpfad-/Datei-Paare gemeinsam über denselben POST-/CSRF-/PRG-Vertrag
- PRG-Redirect mit Erfolgshinweis an der ersetzten Bildzeile

Seit `3.0.3` bleibt dieser Tab bewusst auf tatsächlich verwendete Featured Images und den Replace-in-place-Flow fokussiert. Die bisher hier mitgerenderte read-only Prüfliste lebt nun separat unter `/admin/media?tab=check`, damit Suche, Filter und Navigation im Redaktionsalltag klarer getrennt sind.

Seit `3.0.6` kann dieser Replace-in-place-Flow mehrere vorbereitete Zeilen in einem Request ausführen. Die Browseroberfläche sammelt dafür alle per „Durchsuchen“ bzw. Drag-&-Drop ausgewählten Dateien in `replacement_files[]` und übermittelt die passenden `item_paths[]`; der Server verarbeitet sie über `replace_items` einzeln weiter, sodass Teilfehler begrenzt gemeldet werden und erfolgreiche Ersetzungen trotzdem erhalten bleiben.

## Medien Check

Der Unterpunkt `/admin/media?tab=check` bündelt die read-only Konsistenzprüfung für Featured Images. Er listet Beiträge und Seiten auf,

- die noch gar kein Featured Image hinterlegt haben,
- oder deren gespeicherte Referenz auf keine vorhandene lokale Mediendatei mehr zeigt.

Die Empfehlung bleibt bewusst innerhalb bestehender Pfade: Entweder direkt im Editor über den vorhandenen Featured-Image-Picker aus der Medienbibliothek auswählen oder – bei geteilten defekten Referenzen – über den verlinkten Replace-in-place-Flow unter `/admin/media?tab=featured` arbeiten. Es gibt keine neue Schreibroute, keinen zusätzlichen Token in URLs und keinen automatischen Massen-Fix im GET-Pfad.

Sicherheitsvertrag:

- Replace-POSTs werden serverseitig nur für Pfade akzeptiert, die in der aktuellen Beitrags-/Seitenbild-Karte vorkommen.
- Mehrfach-Replace-POSTs akzeptieren nur geordnete Paare aus erlaubtem Featured-Image-Pfad und Upload-Datei; doppelte Zielpfade werden übersprungen.
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
- Bulk-Aktionen für Löschen, Verschieben, Kategorisieren, Tagging und Alt-Texte
- direkte Dateiaktionen für Umbenennen, Verschieben, Löschen und Kategorie-Zuordnung

Die Duplikat-Erkennung ist bewusst nur ein Hinweis- und Prüfpfad: Sie löscht keine Dateien automatisch und schreibt keine bestehenden Medienreferenzen um. Dadurch können Admins zuerst prüfen, ob identische Dateien tatsächlich redundant sind oder bewusst mehrfach in unterschiedlichen Ordnerkontexten liegen.

Seit `2.9.728` überspringt diese View-Berechnung sehr große Dateien beim SHA-256-Hashing opportunistisch. Dadurch bleibt die Bibliothek auch bei großen Video-, Archiv- oder Backup-Dateien bedienbar; die Anzeige ist weiterhin ein read-only Komfortsignal und kein vollständiger forensischer Duplikatindex.

Seit `2.9.725` bleibt die erweiterte Suche bewusst ein reiner GET-/View-Pfad: Dateityp, Endung, Größenklasse und Änderungszeitraum werden serverseitig normalisiert, in Ordner-, Breadcrumb- und Listen-/Grid-Links mitgeführt und können über einen Reset-Link entfernt werden. Dadurch entstehen keine zusätzlichen CSRF-Token oder Schreibaktionen; fehlerhafte Filterwerte führen zu neutralen Defaults statt zu einem Serverfehler.

Seit `2.9.740` ergänzt die Bibliothek darauf aufbauend persönliche Filter-Presets pro Admin-Benutzer. Gespeichert wird nur ein begrenzter, allowlist-normalisierter Filterzustand im bestehenden `settings`-Speicher unter einer benutzerbezogenen Option mit `autoload = 0`; der aktuelle Bibliothekszustand lässt sich zusätzlich als sichtbarer Filter-Link kopieren. Der Link enthält ausschließlich Query-Parameter für Pfad, Ansicht und Filter – bewusst keine CSRF- oder Sicherheitstokens. Gleichzeitig behalten PRG-Redirects nach Bibliotheks-POSTs nun auch `usage_filter`, Dateityp, Endung, Größenklasse, Änderungszeitraum und die read-only Verwaist-Altersgrenze zuverlässig bei, statt auf Teildefaults zurückzufallen.

Seit `2.9.741` nutzt dieselbe Toolbar zusätzlich Bulk-Kategorisierung und Bulk-Tagging. Die neuen Aktionen bleiben POST-only und CSRF-geschützt: Kategorien können gesammelt gesetzt oder entfernt werden, Tags können hinzugefügt, ersetzt, entfernt oder vollständig geleert werden. Ordner in gemischten Auswahlen werden bei Datei-Metadatenaktionen gezählt übersprungen; erfolgreiche und teilweise fehlgeschlagene Sammelaktionen schreiben datensparsame Audit-Einträge mit Auswahl-, Erfolgs-, Skip- und Fehlerzahlen. Tags landen bewusst im vorhandenen `media-meta.json`-Store und werden serverseitig auf Anzahl und Länge begrenzt, damit defekte Eingaben keine 500er oder unbounded Meta-Payloads erzeugen.

Seit `2.9.743` ergänzt dieselbe Bulk-Toolbar außerdem die Alt-Text-Pflege für SEO- und Accessibility-Aufräumarbeiten. Die Bibliothek rendert Alt-Text-Felder direkt an sichtbaren Dateien in Listen- und Grid-Ansicht; gespeichert werden sie weiterhin nur über den bestehenden POST-/CSRF-/PRG-Vertrag und ausschließlich für ausgewählte Dateien. Die Werte werden fail-soft aus `cms_media.alt_text` geladen; existiert zu einem sichtbaren Medium noch kein Datensatz, ergänzt die Medienverwaltung beim Speichern einen minimalen Eintrag per Upsert. Rename-, Move- und Delete-Aktionen synchronisieren vorhandene `cms_media.filepath`-Einträge bestmöglich mit, damit Alt-Texte nicht an alten Pfaden hängen bleiben.

Seit `2.9.742` ergänzt die Bibliothek eine globale, read-only Orphan-Prüfung. Über einen tokenfreien GET-Parameter lässt sich eine Altersgrenze von 30, 90, 180 oder 365 Tagen aktivieren; die Analyse scannt Uploads rekursiv über einen bewusst begrenzten Inventurpfad, schließt geschützte System- und Member-Pfade aus und gleicht die verbleibenden Dateien gegen die bestehende `MediaUsageService`-Karte ab. Treffer erscheinen ausschließlich als Prüfliste mit Link in den Ordner und optionalem Dateiaufruf – bewusst ohne automatische Löschfunktion und ohne neuen POST-Pfad.

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
