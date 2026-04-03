# PhinIT Live-Audit – Localization, EN-Routing & UI-Mix – 03.04.2026

## Fokus

Dieser Bericht bündelt alle Live-Befunde rund um:

- EN-/DE-Sprachtrennung
- Sprachwechsel-Links
- EN-Pflichtseiten
- englische Artikelhülle, Footer und Kommentarbereich

## Befundmatrix

| Bereich | URL | Beobachtung | Auswirkung |
|---|---|---|---|
| EN-Homepage | `https://phinit.de/en` | Bereichstitel und CTA-Texte bleiben teils deutsch (`Aktuelle Beiträge`, `Folge uns`, `Themenbereiche`, `Alle Anleitungen ansehen →`, `Compliance-Center →`) | EN-Startseite wirkt unfertig und sprachlich inkonsistent |
| EN-Homepage | `https://phinit.de/en` | Footer-/Projektlinks zeigen teilweise auf DE-Ziele wie `https://phinit.de/ms365-phinit-sites` oder `https://phinit.de/m365-lizenzberater` | Sprach- und Routingmix bereits auf der Startseite |
| EN-Homepage | `https://phinit.de/en` | Artikel-URLs sind teils englisch, Slugs aber weiterhin deutsch, z. B. `/en/2026/03/19/m365-lizenzberater-schluss-mit-dem-lizenz-wildwuchs` | EN-Site bleibt semantisch und SEO-seitig uneinheitlich |
| EN-Impressum | `https://phinit.de/en/impressum` | Seite ist vollständig deutsch | EN-Pflichtseite nicht ausgeliefert |
| EN-Datenschutz | `https://phinit.de/en/datenschutz` | Seite ist vollständig deutsch | EN-Pflichtseite nicht ausgeliefert |
| EN-Datenschutzerklärung | `https://phinit.de/en/datenschutzerklaerung` | Seite ist vollständig deutsch | Duplicate-/Fallback-Risiko zusätzlich zur Pflichtseitenlücke |
| EN-Kontakt | `https://phinit.de/en/contact/kontakt` | Formularüberschrift, Datenschutztext, Checkbox-Label und Hilfetexte bleiben deutsch | EN-Kontaktseite nicht produktionsreif |
| DE-Artikel → EN-Switch | `https://phinit.de/2026/02/07/microsoft-purview-dlp-browser-web-schutz` | Sprachumschalter zeigt auf `/en/2026/02/07/microsoft-purview-dlp-browser-web-schutz` | Ziel liefert 404; Sprachwechsel defekt |
| EN-Artikel → DE-Switch | `https://phinit.de/en/2026/02/07/microsoft-purview-dlp-browser-web-protection` | Sprachumschalter zeigt auf `/2026/02/07/microsoft-purview-dlp-browser-web-protection` | Gegenslug wirkt inkonsistent zum tatsächlichen DE-Slug |
| EN-Artikel | `https://phinit.de/en/2026/02/07/microsoft-purview-dlp-browser-web-protection` | Kommentarbereich enthält deutsche Strings wie `Kommentar hinterlassen` und `Dein Beitrag wird vor der Veröffentlichung kurz geprüft` | EN-Detailseite bleibt in der Hülle deutsch |
| EN-Artikel | `https://phinit.de/en/2026/02/07/microsoft-purview-dlp-browser-web-protection` | Next-/Footer-Umfeld verlinkt auf deutsche Titel wie `Exchange Online | EWS Abschaltung Mai 2027` | Sprachmischung auch in interner Navigation |
| EN-Artikel | `https://phinit.de/2026/02/17/purview-custom-dlp-dialogs-in-the-new-outlook` | Englischer Hauptcontent, aber deutsche Kommentar-/Footer-Hülle | Spürbarer Bruch zwischen Content und Layout-Sprache |

## Konkrete Live-Belege

### 1. EN-Pflichtseiten sind faktisch DE-Seiten

Auf den folgenden EN-Routen wurden deutsche Inhalte ausgeliefert:

- `/en/impressum`
- `/en/datenschutz`
- `/en/datenschutzerklaerung`
- `/en/contact/kontakt`

Beobachtete deutsche Live-Begriffe:

- `Datenschutzerklärung`
- `Impressum`
- `Schreib uns eine Nachricht!`
- deutschsprachige Datenschutz-/Checkbox-Texte im Kontaktformular

### 2. EN-Homepage hat weiterhin deutsche Strukturtexte

Die Route `/en` zeigt zwar englische Artikelkarten, aber weiterhin deutsche oder gemischtsprachige Strukturbausteine wie:

- `Aktuelle Beiträge`
- `Folge uns`
- `Themenbereiche`
- `Alle Anleitungen ansehen →`
- `Compliance-Center →`
- deutsche Projekt-/Zielseiten im Footer-/Kartenumfeld

### 3. Sprachumschalter ist nicht vertrauenswürdig

#### Nachgewiesener Defekt

- DE-Seite: `/2026/02/07/microsoft-purview-dlp-browser-web-schutz`
- Sprachwechsel-Ziel: `/en/2026/02/07/microsoft-purview-dlp-browser-web-schutz`
- Ergebnis: **404**

#### Verdächtige Gegenseite

- EN-Seite: `/en/2026/02/07/microsoft-purview-dlp-browser-web-protection`
- Zur-DE-Version-Link: `/2026/02/07/microsoft-purview-dlp-browser-web-protection`

Damit sind Sprachwechsel und Slug-Mapping sichtbar nicht symmetrisch.

### 4. EN-Artikel sind nur im Body wirklich englisch

Auf den EN-Artikelrouten bleibt die Rahmen-UI teilweise deutsch:

- Kommentarüberschrift
- Kommentar-Hinweistext
- Footer-Abschnitte
- einzelne Next-/Related-Linktitel
- Footer-Linkgruppen und Rechtstexte

Das Problem ist deshalb nicht nur ein Übersetzungsdefizit im Content, sondern ein **inkonsistenter Template-/Layout-Vertrag**.

## Technische Hypothesen für die Behebung

1. **Sprachkontext wird im Template nicht konsequent durchgereicht**
   - Footer, Kommentarformular, CTA-Kacheln und Section-Labels rendern offenbar nicht strikt sprachgebunden.

2. **Beitragssprache und Gegenslug werden nicht sauber gemappt**
   - Sprachwechsellinks scheinen teils aus falschem Slug oder falschem Fallback konstruiert zu werden.

3. **EN-Pflichtseiten verwenden noch DE-Content-Objekte oder DE-Template-Fallbacks**
   - Besonders wahrscheinlich bei Impressum/Datenschutz/Kontakt.

4. **Footer-/Linksammlungen greifen auf nicht lokalisierte Menü- oder Settings-Daten zurück**
   - EN-Seiten zeigen wiederholt DE-Footerziele.

## Mindestmaßnahmen

### Sofort

- EN-Impressum, EN-Datenschutz und EN-Kontakt als echte EN-Inhalte ausliefern
- Sprachwechsler an echten Gegenslug binden
- Kommentarformular und Footer vollständig lokalisieren

### Danach

- CTA-Karten, Projektlinks und Footer-Menüs sprachabhängig rendern
- gemischtsprachige Beitrags-Slugs unter EN-Routen bereinigen oder sauber kanonisieren
- automatische Regressionstests für EN-/DE-Routen ergänzen

## Abnahme-Kriterien

Ein Fix gilt erst als ausreichend, wenn:

- `/en/impressum`, `/en/datenschutz`, `/en/contact/kontakt` vollständig englisch sind
- `/en/...`-Artikel keine deutschen Kommentar-/Footerstrings mehr enthalten
- Sprachumschalter beidseitig auf funktionierende Gegenseiten zeigt
- `/en` keine deutschen CTA-, Footer- oder Bereichstitel mehr ausliefert