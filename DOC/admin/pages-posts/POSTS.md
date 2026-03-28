# 365CMS – Beiträge & Blog

Kurzbeschreibung: Verwaltung chronologischer Inhalte wie News und Blog-Beiträge im Admin-Bereich.

Letzte Aktualisierung: 2026-03-28 · Version 2.8.0 RC

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
