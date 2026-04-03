# PhinIT Live-Audit – 03.04.2026

## Zielbild

Geprüft wurde der aktuell per FTP deployte Live-Stand von `https://phinit.de/`.
Der Fokus lag nicht auf lokalem Quellcode, sondern auf dem **tatsächlich ausgelieferten Produktionsverhalten**:

- Sprachtrennung DE/EN
- Routing, Slugs und Sprachwechsel
- EN-Pflichtseiten (`Impressum`, `Datenschutz`, `Kontakt`)
- Archiv- und Suchqualität
- Konsistenz von UI, Footer und Artikelhülle

## Kurzfazit

Der Live-Stand ist **funktional erreichbar**, aber die englische Site ist inhaltlich und strukturell noch nicht konsistent ausgeliefert.
Besonders kritisch sind:

1. **unübersetzte EN-Pflichtseiten**
2. **gemischte DE/EN-Archive mit Doppelcontent**
3. **defekte oder inkonsistente Sprachwechsel-Links**
4. **deutsche UI-/Footer-Bausteine auf EN-Seiten**
5. **zu breite Live-Suchergebnisse für konkrete Fachbegriffe**

## Live-Snapshot-Score

| Bereich | Score | Kurzbegründung |
|---|---:|---|
| Localization | 38,00 | EN-Routen liefern teils weiterhin deutsche Texte, Labels und Footer-Navigation |
| Routing & SEO-Integrität | 52,00 | Sprachwechsel zeigt auf falsche Slugs, mindestens ein EN-Ziel läuft in 404, Duplicate-Routen bleiben sichtbar |
| Search Relevance | 64,00 | Suche funktioniert, streut aber für konkrete Queries noch zu breit |
| UI-/Content-Konsistenz | 46,00 | EN-Seiten mischen englischen Content mit deutschen Hüllen, CTA-Texten und Kommentarformularen |
| EN Legal & Contact Coverage | 28,00 | `/en/impressum`, `/en/datenschutz`, `/en/contact/kontakt`, `/en/datenschutzerklaerung` sind weiterhin deutsch |
| **Gesamt** | **45,60** | Produktions-Snapshot deutlich unter dem dokumentierten Core-/Repo-Qualitätsniveau |

## Geprüfte Live-URLs

### Start- und Archivseiten

- `https://phinit.de/`
- `https://phinit.de/en`
- `https://phinit.de/blog?page=2`
- `https://phinit.de/en/blog?page=3`
- `https://phinit.de/search?q=Browser%20Protection`

### Rechtstexte und Kontakt

- `https://phinit.de/impressum`
- `https://phinit.de/datenschutz`
- `https://phinit.de/datenschutzerklaerung`
- `https://phinit.de/contact/kontakt`
- `https://phinit.de/en/impressum`
- `https://phinit.de/en/datenschutz`
- `https://phinit.de/en/datenschutzerklaerung`
- `https://phinit.de/en/contact/kontakt`

### Artikel / Sprachrouting

- `https://phinit.de/2026/02/07/microsoft-purview-dlp-browser-web-schutz`
- `https://phinit.de/en/2026/02/07/microsoft-purview-dlp-browser-web-protection`
- `https://phinit.de/en/2026/02/07/microsoft-purview-dlp-browser-web-schutz`
- `https://phinit.de/2026/02/17/purview-custom-dlp-dialoge-im-neuen-outlook`
- `https://phinit.de/2026/02/17/purview-custom-dlp-dialogs-in-the-new-outlook`

## Kritische Befunde

| Severity | Befund | Beleg |
|---|---|---|
| kritisch | EN-Pflichtseiten sind nicht übersetzt | `/en/impressum`, `/en/datenschutz`, `/en/contact/kontakt`, `/en/datenschutzerklaerung` rendern deutschsprachige Inhalte |
| kritisch | Sprachwechsel ist inkonsistent | DE-Artikel verlinkt auf `/en/2026/02/07/microsoft-purview-dlp-browser-web-schutz` – dieses Ziel liefert 404 |
| hoch | EN-Artikel verwenden deutsche Shell-Elemente | Kommentarformular, Footer-Bereiche und Linktexte bleiben auf EN-Seiten teilweise deutsch |
| hoch | Archive zeigen DE/EN-Dubletten nebeneinander | `/blog?page=2` und `/en/blog?page=3` listen Sprachvarianten desselben Fachthemas parallel |
| hoch | EN-Homepage mischt englische Artikel mit deutschen CTA-/Bereichstexten | u. a. `Aktuelle Beiträge`, `Folge uns`, `Themenbereiche`, `Alle Anleitungen ansehen →`, `Compliance-Center →` auf `/en` |
| mittel | Suche liefert noch zu breite Trefferbilder | Query `Browser Protection` liefert 26 Resultate inkl. nur schwach passender Seiten |
| mittel | Datenschutz ist doppelt adressierbar | `/datenschutz` und `/datenschutzerklaerung` sowie deren EN-Pendants erzeugen Duplicate-Content-Risiko |

## Erwartetes Zielverhalten

### Sprache

- EN-Routen dürfen **keine deutschsprachigen Pflichtseiten** mehr rendern.
- EN-Artikel dürfen **keine deutschen Kommentar-, Footer- oder CTA-Texte** mehr enthalten.
- Footer, Navigation, CTA-Karten und Sprachumschalter müssen vollständig sprachgebunden sein.

### Routing

- Sprachumschalter müssen immer auf den **tatsächlich existierenden Gegenslug** zeigen.
- `/en/...` darf keine deutschen Slug-Varianten als Primärziel ausliefern.
- Rechtstexte müssen je Sprache auf **eine kanonische Route** reduziert werden.

### Archive und Suche

- Deutsche Archive dürfen nur deutsche Inhalte listen.
- Englische Archive dürfen nur englische Inhalte listen.
- Die Suche muss sprach- und querynäher ranken; unpassende Rechtstexte oder generische Seiten dürfen nicht so weit oben landen.

## Empfohlene Remediation-Reihenfolge

1. **EN-Pflichtseiten korrekt übersetzen und routen**
2. **Sprachwechsel/Slug-Mapping für Beiträge reparieren**
3. **Archive sprachrein filtern**
4. **EN-Layout-/Footer-/Kommentarstrings vollständig lokalisieren**
5. **Datenschutz-Routen kanonisieren**
6. **Suchranking für exakte Fachbegriffe nachschärfen**

## Verwandte Detailberichte

- `DOC/audit/PHINIT-LIVE-LOCALIZATION-2026-04-03.md`
- `DOC/audit/PHINIT-LIVE-SEARCH-ARCHIVE-2026-04-03.md`

## Hinweis zur Einordnung

Dieser Report bewertet den **Live-Auslieferungsstand von `phinit.de`**.
Er ersetzt **nicht** die dokumentierten Core-/Repo-Werte aus dem Hauptaudit, sondern ergänzt sie um einen Produktions-Snapshot der tatsächlich ausgelieferten Site.