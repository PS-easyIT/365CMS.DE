# 365CMS – Seitenverwaltung

Kurzbeschreibung: Verwaltung statischer CMS-Seiten im Admin inklusive getrennter DE/EN-Bearbeitung, SEO-Feldern, Slugs, Redirects, Bulk-Aktionen und Delete-/Preview-Pfaden.

Letzte Aktualisierung: 2026-05-25 · Release 3.3.30

---

## Überblick

Seiten bilden die statischen Inhalte des Systems, etwa Startseite, Kontakt, Impressum oder Datenschutz. Sie unterscheiden sich von Beiträgen vor allem durch ihre eher dauerhafte Struktur und ihre enge Verzahnung mit Navigation, Legal-Sites und Theme-Templates.

---

## Typische Felder

| Feld | Zweck |
|---|---|
| Titel | Anzeigename und Grundbasis für den Slug |
| Slug | URL-Pfad der Standardsprache |
| EN-Slug | Optionaler lokalisierter Pfad für `/en/...` |
| Inhalt | Rich-Text- oder Blockinhalt |
| Status | Redaktionsstatus |
| Kategorie | Optionale Gruppierung für Admin-Filter und Content-Kontext |
| Header-TOC | Optionales, standardmäßig eingeklapptes Inhaltsverzeichnis unter dem Seitentitel |
| Featured Image | Vorschaubild für Cards und Social Preview |
| SEO-Felder | seitenspezifische Meta-Informationen |
| SEO-Tags / Keywords | Kommagetrennte, nicht sichtbar ausgegebene Begriffe für die SEO-Meta-Ausgabe |

Neue Seitenbilder laufen über den gemeinsamen Featured-Image-Picker: Uploads landen bei neuen Seiten zunächst temporär und werden beim Speichern in den Slug-Ordner verschoben. Fehler in diesem Verschiebe-/Metadaten-Schritt werden geloggt und dürfen den Save-Flow nicht mehr als HTTP-500 abbrechen. Öffentliche Seitenbilder werden als direkte relative `/uploads/...`-Referenzen gespeichert und nach dem Verschieben nochmals mit webserverlesbaren Dateirechten versehen, damit die Seite nach dem Aktualisieren nicht an browserlokalen 403-Fehlern auf dem Bild scheitert.

---

## Editor und Vorschau

Der Seiteneditor kombiniert im aktuellen Stand:

- klassischen Inhaltseditor oder Editor.js
- getrennte DE- und EN-Bearbeitungsseiten statt eines fragilen In-Page-Sprachwechsels
- drei obere Karten analog zum Beiträge-Editor: Inhalt/Slug links, Bild plus Aktionen mittig, Veröffentlichung rechts
- SEO-/Readability-/Preview-Karten unter dem Editor
- unsichtbare SEO-Tags/Keywords in der SEO-Card; sie werden im Head als Meta-Keywords ausgegeben, aber nicht als sichtbare Tagliste gerendert
- eine seitenspezifische Header-TOC-Option, die unabhängig vom aktiven Theme und unabhängig von den globalen TOC-Einstellungen rendert
- read-only Revisionsvergleich der letzten gespeicherten Seiten-Snapshots direkt im Editor
- sichtbare Public-Preview-Links für DE und EN
- einen direkten Einzel-Löschpfad für bestehende Seiten innerhalb der Aktionskarte

Dadurch werden Titel, Slugs, Snippet-Vorschau, Social-Vorschau und Sprachvarianten direkt im Redaktionsablauf mit gepflegt, ohne dass ein Sprachwechsel unbeabsichtigt einen Save-POST auslöst.

---

## Revisionen und Vergleich

Seit Release `2.9.708` zeigt der Seiteneditor zusätzlich die letzten gespeicherten Revisionen der aktuellen Seite direkt im Admin an.

- Die Revisions-Snapshots enthalten DE-/EN-Titel, Slugs, Inhalte und den Status der Seite.
- Die Vergleichsansicht bleibt bewusst **read-only** und stellt den aktuellen Stand einer älteren Revision gegenüber.
- Unterschiede werden pro Feld zusammengefasst, bei Inhalten mit Vorschau, Zeichenanzahl und – bei Editor.js – Blockanzahl.
- Es werden aus Performance-Gründen nur die letzten Revisionen direkt in der Oberfläche angezeigt; ältere Snapshots bleiben in der Revisionstabelle erhalten.

---

## Listenansicht und Bulk-Workflows

Die Seitenliste bündelt den Bereich aktuell in drei klare Laufzeitpfade:

- Status-/Kategorie-/Suchfilter für schnelle Redaktionsnavigation
- Bulk-Aktionen für Veröffentlichen, Entwurf, Kategorie setzen/entfernen und Löschen
- klare Sichtbarkeit von EN-Varianten, EN-only-Inhalten und den zuletzt geänderten Zeitpunkten

Destruktive Bulk-Löschungen werden vor dem Submit bestätigt. Die Auswahl wird serverseitig auf echte, positive IDs normalisiert und gegen den aktuellen Bestand validiert.

---

## Redirect- und Lokalisierungsvertrag

Seiten folgen im Public-Routing dem Prefix-Schema:

- Deutsch: `/<slug>`
- Englisch: `/en/<slug>`

Bei Slug-Änderungen legt die Seitenverwaltung automatische Redirects an. Seit Release `2.9.501` werden lokalisierte Redirects wieder korrekt auf das Präfix-Schema `/en/...` geschrieben; zusätzlich bleiben Legacy-Weiterleitungen aus älteren `.../en`-Pfaden kompatibel.

---

## Relevante Integrationen

| Integration | Bedeutung |
|---|---|
| Medienbibliothek | Auswahl von Featured Images und eingebetteten Medien; globale Ersetzung verwendeter Seitenbilder unter `/admin/media?tab=featured` |
| SEO-Center | globale Templates und technisches SEO |
| Legal Sites | rechtliche Seitenzuordnung |
| Theme-Routing | Ausgabe über Theme-Templates |
| Inhaltsverzeichnis | Globale TOC-Auswertung langer Inhalte plus seitenspezifisches Header-TOC unter dem Titel |

---

## Aktueller Arbeitsstand

- Einzel- und Bulk-Delete-Pfade validieren bestehende Seiten jetzt fail-closed statt still auf fragilen DB-Rückgaben zu vertrauen.
- Inhalts-Cache-Clears greifen nicht mehr nur beim Speichern, sondern auch bei Delete- und Bulk-Mutationen.
- Seiten-SEO und Lesbarkeitsprüfungen sind direkt im Editor sichtbar.
- Seiten können interne SEO-Tags/Keywords speichern; sie bleiben frontend-unsichtbar und werden nur von der SEO-Meta-Ausgabe bzw. dem SEO-Audit gelesen.
- Slug-, Redirect- und Preview-Bezüge greifen konsistent über DE/EN-Pfade ineinander.
- Die Admin-UI bündelt Speichern, DE-/EN-Vorschau und Einzel-Löschen jetzt in einer gemeinsamen Aktionskarte mit klarer visueller Hierarchie statt in getrennten Top-/Delete-Bereichen.
- Revisions-Snapshots lassen sich direkt im Seiteneditor gegen den aktuellen Stand vergleichen, ohne Restore-Aktionen still mitzuschleusen.
- Seiten können ein eigenes eingeklapptes Inhaltsverzeichnis direkt unter dem Titel erzwingen; dieses nutzt `show_title_toc` und bleibt von globalen TOC-Auto-Insert-Settings unabhängig.

---

## Verwandte Dokumente

- [POSTS.md](POSTS.md)
- [../seo/SEO.md](../seo/SEO.md)
- [../media/MEDIA.md](../media/MEDIA.md)
- [../legal/LEGAL.md](../legal/LEGAL.md)

