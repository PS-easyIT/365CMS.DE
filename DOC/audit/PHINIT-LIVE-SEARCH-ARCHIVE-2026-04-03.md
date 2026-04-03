# PhinIT Live-Audit – Archive, Suche & Duplicate-Content – 03.04.2026

## Fokus

Geprüft wurden die live ausgelieferten Archive, Suchergebnisse und auffällige Duplicate-/Routing-Muster.

## Hauptbefunde

### 1. Deutsche und englische Archive sind nicht sprachrein

#### DE-Archiv

Auf `https://phinit.de/blog?page=2` erscheinen thematisch doppelte Sprachvarianten nebeneinander, u. a.:

- `Purview | Custom DLP dialogs in the new Outlook`
- `Purview | Custom DLP Dialoge im neuen Outlook`
- `Exchange Online | EWS shutdown May 2027`
- `Exchange Online | EWS Abschaltung Mai 2027`

#### EN-Archiv

Auf `https://phinit.de/en/blog?page=3` erscheinen ebenfalls gemischte Varianten:

- `Microsoft Purview DLP | Browser & Web Protection`
- `Microsoft Purview DLP | Browser & Web-Schutz`
- `Dynamic M365 groupsets without Entra P1`
- `Dynamische M365 Gruppen ohne Entra P1`

### Bewertung

Das ist nicht nur ein UX-Problem, sondern ein **Content-Integrity- und SEO-Problem**:

- Archive sind nicht eindeutig sprachsegmentiert
- ähnliche Inhalte konkurrieren im selben Listing
- Duplicate-/Near-Duplicate-Content bleibt sichtbar
- Nutzer sehen auf EN-Listen deutsche Fachartikel oder deutsche Titelvarianten

## 2. Live-Suche für exakte Fachbegriffe noch zu breit

### Testquery

- `https://phinit.de/search?q=Browser%20Protection`

### Beobachtung

Die Suche liefert **26 Ergebnisse**. Darunter finden sich zwar passende Fachartikel, aber auch nur schwach passende oder thematisch breit gestreute Seiten.

Beobachtetes Symptom:

- Query ist fachlich präzise
- Resultset bleibt dennoch relativ breit
- Suchergebnisqualität wirkt nicht ausreichend auf Exaktheit/Sprachkontext verdichtet

### Risiko

- fachlich präzise Queries verlieren Relevanz
- wichtige Kernartikel konkurrieren gegen allgemeine Seiten
- Nutzer erhalten kein klar priorisiertes Fachtrefferbild

## 3. Sprachrouting und Slug-Mapping sind inkonsistent

### Nachgewiesene 404

- `https://phinit.de/en/2026/02/07/microsoft-purview-dlp-browser-web-schutz` → **404**

Diese Route wird gleichzeitig als Sprachwechselziel von der DE-Seite verwendet.

### Verdächtige Gegenslug-Inkonsistenz

- EN-Seite: `/en/2026/02/07/microsoft-purview-dlp-browser-web-protection`
- Zur-DE-Version-Link: `/2026/02/07/microsoft-purview-dlp-browser-web-protection`

Damit ist das Sprachrouting sichtbar nicht stabil an echte Gegenslugs gekoppelt.

## 4. Rechtstexte sind doppelt adressierbar

Zusätzlich zu den bekannten Pflichtseiten existiert ein weiterer Datenschutzpfad:

- `https://phinit.de/datenschutz`
- `https://phinit.de/datenschutzerklaerung`
- `https://phinit.de/en/datenschutz`
- `https://phinit.de/en/datenschutzerklaerung`

Die EN-Varianten sind dabei weiterhin deutschsprachig.

### Auswirkung

- Duplicate-Content-Risiko für Rechtstexte
- unklare kanonische Zielroute
- gemischte Verlinkung im Footer und in der Suche

## 5. Artikelnavigation wirkt chronologisch verdächtig

Beim Test einzelner Februar-Artikel fielen Previous-/Next-Verlinkungen auf, die auf Pfade vom `03.04.2026` zeigen.

### Einordnung

Das kann legitim sein, wenn die chronologische Navigation global statt nur thematisch erfolgt.
Auffällig ist aber, dass es im Kontext der übrigen Routing-/Slug-Probleme als zusätzlicher Prüffall dokumentiert werden sollte.

## Priorisierte Maßnahmen

### Priorität 1

- Archive strikt nach Sprache filtern
- keine DE-/EN-Dubletten im selben Listing ausgeben

### Priorität 2

- Sprachwechsler auf echte Gegenslugs umstellen
- 404-Ziele im Sprachrouting eliminieren

### Priorität 3

- Datenschutz-Routen auf eine kanonische Zielseite je Sprache reduzieren
- Footer und Suche auf dieselben kanonischen Pfade ausrichten

### Priorität 4

- Suchranking für exakte Fachbegriffe enger priorisieren
- sprachfremde oder generische Seiten für konkrete Fachqueries weiter nach hinten stellen

## Abnahme-Kriterien

Ein Fix gilt erst als tragfähig, wenn:

- `/blog` nur DE-Inhalte listet
- `/en/blog` nur EN-Inhalte listet
- Sprachwechsel keine 404-Ziele mehr erzeugen
- Datenschutz nur noch über je eine kanonische Route pro Sprache sichtbar ist
- die Suche für `Browser Protection` primär einschlägige Fachartikel priorisiert