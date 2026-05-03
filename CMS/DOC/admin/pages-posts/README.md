# 365CMS – Inhalte: Seiten, Beiträge, Hub-Sites & Landing Pages

Kurzbeschreibung: Überblick über die Content-Module im Admin-Bereich und ihre aktuelle Aufgabenverteilung.

Letzte Aktualisierung: 2026-05-03 · Version 2.9.509

Der Content-Bereich ist auf mehrere spezialisierte Bereiche verteilt:

| Route | Zweck |
|---|---|
| `/admin/pages` | statische Seiten verwalten |
| `/admin/posts` | Blog- und News-Beiträge verwalten |
| `/admin/comments` | Kommentare moderieren und verwalten |
| `/admin/hub-sites` | Hub-Sites und thematische Sammelseiten verwalten |
| `/admin/landing-page` | Landing-Page-Bausteine und Homepage-Sektionen |
| `/admin/table-of-contents` | Inhaltsverzeichnis-Logik und TOC-Einstellungen |
| `/admin/site-tables` | benutzerdefinierte Datentabellen |
| `/admin/settings?tab=content` | Inhalts-Einstellungen (Editor-Optionen, Defaults, Permalinks, Archiv-Basen) |

---

## Editor-Stack

Im aktuellen Stand nutzt 365CMS im Content-Bereich mehrere Editorkomponenten:

- **SunEditor** für klassische Rich-Text-Bearbeitung
- **Editor.js** für blockbasierte Inhalte
- **SEO-Karten** unter Seiten und Beiträgen für Meta-Daten, Lesbarkeit und Vorschau
- **Lazy Language Switching** im Beitrags-Editor mit einmaliger DE→EN-Initialkopie bei leerer englischer Fassung

Die SEO-spezifischen Global-Einstellungen liegen nicht mehr in einem alten Monolithen `seo.php`, sondern im [SEO-Center](../seo/SEO.md).

---

## Wichtige Fachdokumente

| Dokument | Schwerpunkt |
|---|---|
| [PAGES.md](PAGES.md) | statische Seiten |
| [POSTS.md](POSTS.md) | Beiträge und Blog-Workflow |
| [COMMENTS.md](COMMENTS.md) | Kommentar-Moderation und Massenaktion |
| [HUBSITES.md](HUBSITES.md) | Hub-Sites, Slugs, Zusatzdomains und Public-Routing |
| [SETTINGS.md](SETTINGS.md) | Inhalts-Einstellungen für Editor, Defaults, Permalinks und Archive |
| [TABLES.md](TABLES.md) | Wiederverwendbare Site-Tabellen |
| [TOC.md](TOC.md) | Inhaltsverzeichnis-Einstellungen |
| [../landing-page/LANDING-PAGE.md](../landing-page/LANDING-PAGE.md) | Landing-Page-Builder |
| [../media/MEDIA.md](../media/MEDIA.md) | Medienbibliothek und Dateinutzung |

---

## Aktuelle Hinweise

- Lösch-Workflows für Seiten und Beiträge folgen im aktuellen Stand dem konsolidierten Admin-Flow mit Redirects, Alerts und serverseitiger Validierung.
- Featured Images, Slugs und Redaktionshilfen sind stärker mit SEO und Medienverwaltung verzahnt.
- Der Beitrags-Editor nutzt im Top-Bereich jetzt eine kompaktere Kartenverteilung: Kategorie und Tags sitzen direkt beim Slug, während Speichern und öffentliche DE-/EN-Vorschau in einer eigenen Aktions-Card unter dem Beitragsbild liegen.
- Beiträge arbeiten im Editor aktuell mit **einer primären Kategorie**; die frühere UI für zusätzliche Kategorien wird nicht mehr angeboten.
- Hub-Sites reservieren nun auch statische Public-Routen und Archivbasen als Slugs, damit neu angelegte Hubs nicht an `/contact`, `/authors`, `/feed`, `/category`, `/tag` oder ähnlichen Frontend-Routen unsichtbar vorbeiplanen.
- Site-Tabellen schließen ihren Public-Vertrag jetzt sichtbar an den Editor an: Suche, Sortierung, Paginierung und Zeilenhervorhebung wirken im Frontend tatsächlich; die nicht implementierte Excel-Option wird im Admin nicht länger als scheinbar produktiver Export angeboten.
- Der Unterbereich „Einstellungen“ lebt technisch unter `/admin/settings?tab=content`, bündelt jetzt auch Permalink-/Archiv-Basen direkt im Inhalts-Tab und behält Formulareingaben bei Validierungsfehlern über den Redirect hinweg.
- Listenansichten mit Mehrfachauswahl machen die beabsichtigte Bulk-Aktion jetzt direkt am Button sichtbar; destruktive Aktionen werden so nicht mehr hinter einem generischen „Anwenden“ versteckt. Gleichzeitig öffnen Aktions-Dropdowns in scrollbaren Tabellen sichtbar, statt in der horizontalen Overflow-Zone abgeschnitten zu werden.
- Historische Verweise auf `/admin/seo.php` oder alte Monolith-Seiten sind in diesem Bereich nicht mehr korrekt.
