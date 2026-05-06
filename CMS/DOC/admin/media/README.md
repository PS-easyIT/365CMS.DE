# 365CMS – Medienverwaltung

Kurzbeschreibung: Überblick über Medienbibliothek, Upload-Workflows, Schutzbereiche, Admin-Tabs und verknüpfte Member-/Asset-Dokumentation.

Letzte Aktualisierung: 2026-05-06 · Version 2.9.613

Die Medienverwaltung ist unter `/admin/media` erreichbar und bündelt Bibliothek, Beitrags-/Site-Medien, Kategorien und Einstellungen über Query-Tabs statt über getrennte Legacy-Routen.

---

## Admin-Routen

| Route | Zweck |
|---|---|
| `/admin/media` | Bibliothek mit Listen-/Grid-Ansicht, Upload, Suche, Rename/Move/Delete |
| `/admin/media?tab=featured` | fokussierte Übersicht aller als Beitrags- oder Seitenbild verwendeten Medien mit Ersetzen-Workflow |
| `/admin/media?tab=categories` | Medien-Kategorien anlegen und pflegen |
| `/admin/media?tab=settings` | Upload-Limits, erlaubte Typen und globale Medienoptionen |

---

## Kernfunktionen in 2.9.613

- Listen- **und** Grid-Ansicht
- Such- und Kategorien-Filter
- native Upload-Modalstrecke statt aktiver FilePond-Runtime
- kompakte Dropdown-Aktionen für Dateien und Ordner
- zentrale Rename-/Move-Modale mit vorbereiteten Zielordnern
- Admin-Bulk-Aktionen für Löschen und Verschieben
- aktionsspezifische Bulk-Schaltfläche statt generischem „Auf Auswahl anwenden“
- serverseitig normalisierte Pfade, CSRF-geschützte POST-Flows und PRG-Redirects
- geschützte Systempfade mit zusätzlicher Member-Bestätigung am `member`-Bereich
- verwaltete Upload-Pipeline für Bibliothek und Member-Bereich mit Maximalmaßen, optionalen Thumbnails und Auto-WebP
- Rücksprung in den **tatsächlich verwendeten** Zielordner, wenn Uploads automatisch in Jahres-/Monats-Unterordner einsortiert werden
- strengere Dateinamens-Härtung mit Längenlimit und ohne irreführende Mehrfach-Punkte im Basenamen
- Unterpunkt **Beitrags & Site Medien** für Featured Images aus Beiträgen und Seiten inklusive Suche, Filter nach Beiträgen/Seiten, Drag-&-Drop-Ersetzen, lokaler Mini-Vorschau und Erfolgshinweis pro Bild

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

- Verwendungsstatus bzw. Referenzen aus Seiten/Beiträgen
- Kategorien- und Suchfilter
- Bulk-Aktionen für Löschen und Verschieben
- direkte Dateiaktionen für Umbenennen, Verschieben, Löschen und Kategorie-Zuordnung

Bei erfolgreich hochgeladenen Dateien bleibt die Oberfläche nicht mehr am alten Pfad hängen, sondern aktualisiert auf den effektiven Zielordner. Das ist besonders relevant, wenn `organize_month_year` aktiv ist.

---

## Verwandte Dokumente

- [MEDIA.md](MEDIA.md)
- [../performance/PERFORMANCE.md](../performance/PERFORMANCE.md)
- [../../member/README.md](../../member/README.md)
- [../../workflow/MEDIA-UPLOAD-WORKFLOW.md](../../workflow/MEDIA-UPLOAD-WORKFLOW.md)
- [../../assets/README.md](../../assets/README.md)
