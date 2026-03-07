# 365CMS – Seitenverwaltung

Kurzbeschreibung: Verwaltung statischer CMS-Seiten im Admin inklusive Editor, SEO-Feldern, Slugs und Revisionsbezug.

Letzte Aktualisierung: 2026-03-07

**Admin-Route:** `/admin/pages`

---

## Überblick

Seiten bilden die statischen Inhalte des Systems, etwa Startseite, Kontakt, Impressum oder Datenschutz. Sie unterscheiden sich von Beiträgen vor allem durch ihre eher dauerhafte Struktur und ihre enge Verzahnung mit Navigation, Legal-Sites und Theme-Templates.

---

## Typische Felder

| Feld | Zweck |
|---|---|
| Titel | Anzeigename und Grundbasis für den Slug |
| Slug | URL-Pfad der Seite |
| Inhalt | Rich-Text- oder Blockinhalt |
| Auszug | Kurzbeschreibung für Listen- und SEO-Kontexte |
| Status | Redaktionsstatus |
| Featured Image | Vorschaubild für Cards und Social Preview |
| SEO-Felder | seitenspezifische Meta-Informationen |

---

## Editor und Vorschau

Der Seiteneditor kombiniert im aktuellen Stand:

- klassischen Inhaltseditor
- Editor.js-Komponenten
- SEO-/Readability-/Preview-Karten unter dem Editor

Dadurch werden Titel, Beschreibung, Snippet-Vorschau und Social-Vorschau direkt im Redaktionsablauf mit gepflegt.

---

## Relevante Integrationen

| Integration | Bedeutung |
|---|---|
| Medienbibliothek | Auswahl von Featured Images und eingebetteten Medien |
| SEO-Center | globale Templates und technisches SEO |
| Legal Sites | rechtliche Seitenzuordnung |
| Theme-Routing | Ausgabe über Theme-Templates |
| Inhaltsverzeichnis | TOC-Auswertung langer Inhalte |

---

## Aktueller Arbeitsstand

- Single-Delete-Workflows wurden robuster gemacht.
- Seiten-SEO ist direkt im Editor sichtbar.
- Slug-, Redirect- und Preview-Bezüge greifen stärker ineinander als in älteren Dokumentationsständen.

---

## Verwandte Dokumente

- [POSTS.md](POSTS.md)
- [../seo/SEO.md](../seo/SEO.md)
- [../media/MEDIA.md](../media/MEDIA.md)
- [../legal/LEGAL.md](../legal/LEGAL.md)

