# 365CMS – Seitenverwaltung

Kurzbeschreibung: Verwaltung statischer CMS-Seiten im Admin inklusive getrennter DE/EN-Bearbeitung, SEO-Feldern, Slugs, Redirects, Bulk-Aktionen und Delete-/Preview-Pfaden.

Letzte Aktualisierung: 2026-05-06 · Release 2.9.613

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
| Featured Image | Vorschaubild für Cards und Social Preview |
| SEO-Felder | seitenspezifische Meta-Informationen |

---

## Editor und Vorschau

Der Seiteneditor kombiniert im aktuellen Stand:

- klassischen Inhaltseditor oder Editor.js
- getrennte DE- und EN-Bearbeitungsseiten statt eines fragilen In-Page-Sprachwechsels
- drei obere Karten analog zum Beiträge-Editor: Inhalt/Slug links, Bild plus Aktionen mittig, Veröffentlichung rechts
- SEO-/Readability-/Preview-Karten unter dem Editor
- sichtbare Public-Preview-Links für DE und EN
- einen direkten Einzel-Löschpfad für bestehende Seiten innerhalb der Aktionskarte

Dadurch werden Titel, Slugs, Snippet-Vorschau, Social-Vorschau und Sprachvarianten direkt im Redaktionsablauf mit gepflegt, ohne dass ein Sprachwechsel unbeabsichtigt einen Save-POST auslöst.

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
| Inhaltsverzeichnis | TOC-Auswertung langer Inhalte |

---

## Aktueller Arbeitsstand

- Einzel- und Bulk-Delete-Pfade validieren bestehende Seiten jetzt fail-closed statt still auf fragilen DB-Rückgaben zu vertrauen.
- Inhalts-Cache-Clears greifen nicht mehr nur beim Speichern, sondern auch bei Delete- und Bulk-Mutationen.
- Seiten-SEO ist direkt im Editor sichtbar.
- Slug-, Redirect- und Preview-Bezüge greifen konsistent über DE/EN-Pfade ineinander.
- Die Admin-UI bündelt Speichern, DE-/EN-Vorschau und Einzel-Löschen jetzt in einer gemeinsamen Aktionskarte mit klarer visueller Hierarchie statt in getrennten Top-/Delete-Bereichen.

---

## Verwandte Dokumente

- [POSTS.md](POSTS.md)
- [../seo/SEO.md](../seo/SEO.md)
- [../media/MEDIA.md](../media/MEDIA.md)
- [../legal/LEGAL.md](../legal/LEGAL.md)

