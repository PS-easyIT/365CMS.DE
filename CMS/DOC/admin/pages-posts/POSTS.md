# 365CMS – Beiträge & Blog

Kurzbeschreibung: Verwaltung chronologischer Inhalte wie News und Blog-Beiträge im Admin-Bereich.

Letzte Aktualisierung: 2026-04-07 · Version 2.9.0

---

## Überblick

Beiträge sind die dynamischen, chronologischen Inhalte des Systems. Sie werden typischerweise in Blog-Listen, Feeds, thematischen Übersichten und Suchergebnissen verwendet.

---

## Typische Inhalte eines Beitrags

| Feld | Zweck |
|---|---|
| Titel | Überschrift des Beitrags |
| Slug | URL-Segment |
| Inhalt | Hauptinhalt des Beitrags |
| Auszug | Kurztext für Listen, Cards und Suchergebnisse |
| Featured Image | Bild für Listen, Social und SEO-Vorschau |
| Kategorien | thematische Einordnung |
| Tags | zusätzliche Schlagwörter |
| SEO-Daten | Meta- und Vorschauinformationen |

---

## Redaktioneller Workflow

Im aktuellen Redaktionsfluss greifen mehrere Systeme zusammen:

- Editor mit klassischem und blockbasiertem Inhalt
- Featured-Image-Picker aus der Medienbibliothek
- SEO-Karten unter dem Editor
- Listen- und Einzellöschung mit stabilisiertem Delete-Flow
- kompakter Top-Bereich mit separater Aktions-Card unter dem Beitragsbild
- Einzel-Kategorie statt zusätzlicher Mehrfach-Kategorien im Editor
- einmalige Initialkopie DE → EN beim ersten Wechsel in den noch leeren englischen Editor

---

## Editor-Aufbau im aktuellen Stand

Die obere Editor-Zone ist in drei Bereiche gegliedert:

| Bereich | Inhalt |
|---|---|
| Card 1 | Titel, Slug, Kategorie, Tags |
| Card 2 | Beitragsbild |
| Card 2b | Aktionen mit `Erstellen/Aktualisieren` sowie öffentlicher DE-/EN-Vorschau |
| Card 3 | Status, Veröffentlichungsdatum/-zeit und Autoren-Anzeigename |

Wichtig: Für Beiträge gibt es im Editor aktuell **nur noch eine primäre Kategorie**. Die frühere UI für „Zusätzliche Kategorien“ wird nicht mehr angezeigt.

---

## Mehrsprachiger Editor-Flow

Der englische Beitrags-Editor wird lazy beim Umschalten geladen. Wenn die EN-Fassung beim **ersten Wechsel** noch leer ist, übernimmt 365CMS automatisch den aktuellen deutschen Inhalt als Ausgangspunkt.

- Die Kopie erfolgt **nicht** bereits beim Laden der Seite.
- Die Kopie läuft **nur einmal** beim ersten Wechsel auf die EN-Ansicht.
- Bereits vorhandener oder später bearbeiteter EN-Inhalt wird **nicht** automatisch überschrieben.

---

## Besondere Bezüge

| Bereich | Nutzen |
|---|---|
| Kategorien und Tags | Organisation und spätere Filterung |
| SEO-Center | globale SEO-Vorgaben und technische Auswertung |
| Redirect-Manager | 404- und Umleitungsbezug bei URL-Änderungen |
| Sitemap | Veröffentlichung fließt in SEO-Sitemaps ein |

---

## Aktuelle Hinweise

- Die Dokumentation alter Monolithen wie `/admin/seo.php` ist für den Beitragsworkflow nicht mehr aktuell.
- Beitragslöschungen wurden in den neueren 2.1.x-Ständen robuster gemacht.
- SEO-Vorschau und Redaktionshilfen sind direkter Teil des Editors geworden.

---

## Verwandte Dokumente

- [PAGES.md](PAGES.md)
- [../seo/SEO.md](../seo/SEO.md)
- [../media/MEDIA.md](../media/MEDIA.md)
